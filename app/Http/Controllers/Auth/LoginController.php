<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Dwolla\CustomerService;
use App\Services\QuickBooksService;
use App\Trait\Auth\AuthenticateUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

// use Illuminate\Foundation\Auth\AuthenticatesUsers;
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    // use AuthenticatesUsers;
    use AuthenticateUser;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */

    // protected $redirectTo = '/admin/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except(['logout', 'loginUser']);
    }

    public function loginForm()
    {
        return view('auth.login');
    }

    public function loginUser(Request $request)
    {

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($response = $this->rejectDeactivatedManager($request, $user)) {
                return $response;
            }

            $user->timezone = $request->timezone;
            $user->save();

            return redirect($this->redirectTo());
        }

        return back()->withErrors(['email' => 'Invalid credentials']);
    }

    protected function authenticated(Request $request, $user)
    {
        return $this->rejectDeactivatedManager($request, $user);
    }

    private function rejectDeactivatedManager(Request $request, $user)
    {
        if ((int) $user->role_id !== 6 || (string) $user->status === '1') {
            return null;
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back()
            ->withErrors(['email' => 'Your account has been deactivated.'])
            ->onlyInput('email');
    }

    protected function redirectTo()
    {
        $user = Auth::user();
        session()->flash('success', 'Welcome back, '.$user->first_name.'! You have successfully logged in.');
        switch ($user->role->name) {
            case 'Administrator':
                return '/admin/events';
            case 'Brand':
                return '/brand/events';
            case 'Client':
                return '/client/calendar';
            case 'Location':
                return '/location/calendar';
            case 'Ambassador':
                $customerService = app(CustomerService::class);

                if (! $user->dwollaCustomer) {
                    $missingFields = $customerService->getMissingDwollaFields($user);

                    if ($missingFields === []) {
                        try {
                            $customerService->create($user);
                        } catch (\Throwable $e) {
                            Log::warning('Dwolla customer creation failed during ambassador login (non-blocking)', [
                                'user_id' => $user->id,
                                'message' => $e->getMessage(),
                            ]);
                        }
                    } else {
                        session([
                            'dwolla_payment_setup_required' => true,
                            'dwolla_missing_fields' => $missingFields,
                        ]);
                    }
                }

                if (! $user->hasVerifiedBankAccount()) {
                    session()->flash(
                        'warning',
                        'You have not completed your bank account setup. Payments cannot be processed until setup and verification are complete.'
                    );
                }

                // If QuickBooks is connected, ensure the Expense account exists once per realm.
                if (Cache::has('qb_access_token') && Cache::has('qb_realm_id')) {
                    try {
                        app(QuickBooksService::class)->ensureExpenseAccountExists();
                    } catch (\Throwable $e) {
                        Log::warning('QuickBooks ensureExpenseAccountExists failed during ambassador login (non-blocking)', [
                            'message' => $e->getMessage(),
                            'user_id' => $user->id ?? null
                        ]);
                    }
                }

                return '/ambassador/calendar';
            case 'Manager':
                return '/manager/events';
            default:
                Auth::logout();
                flash()->error('You are not authorized');

                return route('login');
        }

    }
}
