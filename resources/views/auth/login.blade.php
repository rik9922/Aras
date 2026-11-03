@extends('layout.app', ['page_name' => 'Sign in'])
<style>
    @media screen and (max-width: 1024px) {
        body {
            background-image: none !important;
            background: rgb(245 245 245);
        }
    }
</style>
@section('content')
    <div class="page-content">
        <div class="row" style="--bs-gutter-x: 0rem;">
            <div class="col-xl-7 d-none d-xl-block">
                <div class="row justify-content-center align-items-center" style="height: 100vh;">
                    <img src="{{ asset('assets/images/logo.png') }}" style="height: fit-content; width: 440px;">
                </div>
            </div>
            <div class="col-xl-5">
                <div class="form-section">
                    <div class="form-card">
                        <form action="{{ route('login-user') }}" method="POST">
                            @csrf
                            <input type="hidden" name="timezone" id="timezone">
                            <div class="h4 fw-bold mb-4">Sign in</div>
                            <div class="mb-3">
                                <label for="emailAddress" class="form-label">Email address</label>
                                <div class="inner-field rounded overflow-hidden" style="border: 1px solid #747D8D;">
                                    <i class="fa-solid fa-envelope"></i>
                                    <input type="email" name="email" id="emailAddress"
                                        class="form-control shadow-none @error('email') is-invalid @enderror"
                                        placeholder="Enter Email address" />
                                </div>
                                @error('email')
                                    <span class="text-danger" role="alert" style="font-size: var(--font-size-body);">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label for="password" class="form-label">Password</label>
                                    <a class="forgot-password-link" href="/forgot-password">Forgot Password?</a>
                                </div>
                                <div class="input-group flex-nowrap rounded overflow-hidden"
                                    style="border: 1px solid #747D8D;">
                                    <div class="inner-field">
                                        <i class="fa-solid fa-lock"></i>
                                        <input type="password" name="password"
                                            class="form-control shadow-none  @error('password') is-invalid @enderror"
                                            id="password" placeholder="Enter Password" />
                                    </div>
                                    <button class="btn eye-btn" type="button" id="toggle-password"> <i
                                            class="fa-solid fa-eye-slash"></i> </button>
                                </div>
                                @error('password')
                                    <span class="text-danger" role="alert" style="font-size: var(--font-size-body);">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="d-grid pt-3">
                                <button type="submit" class="btn btn-design py-2">Sign in</button>
                            </div>
                            <div class="d-flex justify-content-center py-4">
                                <div class="form-check">
                                    <input class="form-check-input shadow-none" type="checkbox" value=""
                                        id="remember_me" checked>
                                    <label class="form-check-label" for="remember_me"> <small>Remember me</small> </label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<script>
    document.addEventListener("DOMContentLoaded", function() {
        //get timezone
        let timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        document.getElementById('timezone').value = timeZone;

        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('toggle-password');
        if (togglePassword) {
            const passwordIcon = togglePassword.querySelector('i');
            togglePassword.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    passwordIcon.classList.remove('fa-eye-slash');
                    passwordIcon.classList.add('fa-eye');
                } else {
                    passwordInput.type = 'password';
                    passwordIcon.classList.remove('fa-eye');
                    passwordIcon.classList.add('fa-eye-slash');
                }
            });
        }
    });
</script>
