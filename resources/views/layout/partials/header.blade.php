@if (Auth::check())
    @php
        $user = Auth::user();
        $balanceAlertBadge = null;

        if (
            (int) $user->role_id === 1 &&
            \Illuminate\Support\Facades\Schema::hasTable('dwolla_balance_alert_snapshots')
        ) {
            $latestBalanceSnapshot = \App\Models\DwollaBalanceAlertSnapshot::query()->latest('created_at')->first();

            if ($latestBalanceSnapshot && $latestBalanceSnapshot->is_breach) {
                $balanceAlertBadge = strtoupper((string) ($latestBalanceSnapshot->severity ?: 'warning'));
            }
        }
    @endphp

    {{-- QuickBooks banner for Admin & Manager --}}
    @if ($user->role_id == 1 || $user->role_id == 6)
        @php
            $qbConnected = \App\Helper\QuickBooksHelper::isConnected();
            $qbAuthRoute = $user->role_id == 6 ? 'manager.quickbooks.auth' : 'admin.quickbooks.auth';
        @endphp
        @if (!$qbConnected)
            <div class="alert alert-warning mb-0 text-center py-2"
                style="border-radius: 0; background-color: #fff3cd; border-color: #ffc107;">
                <strong>QuickBooks Connection:</strong> Not connected or expired.
                <a href="{{ route($qbAuthRoute) }}" class="btn btn-sm btn-dark ms-2" style="padding: 4px 12px;">Connect
                    QuickBooks</a>
            </div>
        @endif
    @endif

    @if ($user->role_id == 1)
        <header>
            <div class="header2">
                <nav class="navbar py-0">
                    <div class="container-xxl d-flex flex-nowrap justify-content-xl-center">
                        <a class="navbar-brand py-0" href="{{ route('admin.calendar') }}">
                            <img src="{{ asset('assets/images/logo.png') }}">
                        </a>
                        <button class="navbar-toggler shadow-none btn-sm fs-6 d-xl-none p-2 my-2" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar"
                            aria-label="Toggle navigation">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div class="menu d-none d-xl-block">
                            <div class="d-flex">
                                <ul class="nav mx-xxl-4">
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('admin/calendar') ? 'active' : '' }}"
                                            href="{{ route('admin.calendar') }}">Calendar</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('admin/events') ? 'active' : '' }}"
                                            href="{{ route('admin.events') }}">Event</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('admin/recaps') ? 'active' : '' }}"
                                            href="{{ route('admin.recaps') }}">Recap</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('admin/reimbursements') ? 'active' : '' }}"
                                            href="{{ route('admin.reimbursements.index') }}">Reimbursement</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('admin/brands') ? 'active' : '' }}"
                                            href="{{ route('admin.brands') }}">Brand</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('admin/bill-to') ? 'active' : '' }}"
                                            href="{{ route('admin.bills') }}">Bill To</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('admin/bulks') ? 'active' : '' }}"
                                            href="{{ route('admin.bulks') }}">Bulk</a>
                                    </li>
                                    {{-- <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('admin/import-data') ? 'active' : '' }}"
                                            href="{{ route('admin.import-data') }}">Import Data</a>
                                    </li> --}}
                                    {{-- <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('admin/receipt-ocr') ? 'active' : '' }}"
                                            href="{{ route('admin.receipt-ocr.index') }}">Receipt OCR</a>
                                    </li> --}}
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('admin/ambassadors') ? 'active' : '' }}"
                                            href="{{ route('admin.ambassadors') }}">Ambassadors</a>
                                    </li>
                                    <li>
                                        <div class="dropdown">
                                            <a class="nav-link dropdown-toggle py-3" href="#" role="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                Quickbooks
                                            </a>
                                            <ul class="dropdown-menu">

                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.expense-categories.index') }}">Expense
                                                        Category</a>
                                                </li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.payment-methods.index') }}">Payment
                                                        Methods</a>
                                                </li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.invoices') }}">Invoices</a>
                                                </li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.expenses') }}">Expenses</a>
                                                </li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.vendor-1099.index') }}">1099 Vendor
                                                        Report</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.services.index') }}">Services</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.event-invoicing') }}">Event invoicing
                                                        (prepare)</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.invoices') }}">QuickBooks invoices
                                                        (synced)</a></li>
                                            </ul>
                                        </div>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('admin/messaging') ? 'active' : '' }}"
                                            href="{{ route('admin.messaging') }}">Messaging</a>
                                    </li>
                                    <li>
                                        <div class="dropdown">
                                            <a class="nav-link dropdown-toggle py-3" href="#" role="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                Pay
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.payment-forecast') }}">Payment
                                                        Forecast</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.payments') }}">Payments</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.webhooks.index') }}">Webhooks</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.dwolla.balance-alerts') }}">
                                                        Balance Alerts
                                                        @if ($balanceAlertBadge)
                                                            <span
                                                                class="badge bg-danger ms-1">{{ $balanceAlertBadge }}</span>
                                                        @endif
                                                    </a></li>
                                            </ul>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="dropdown">
                                            <a class="nav-link dropdown-toggle py-3" href="#" role="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                More
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.event-department') }}">Event
                                                        Departments</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.event-destributor') }}">Event
                                                        Distributor</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.eventtypes') }}">Event Types</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.documents') }}">Aras
                                                        Documents</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.markets') }}">Markets</a></li>
                                                {{--
                                                <li><a class="dropdown-item" href="{{ route('admin.venues') }}">Venue
                                                        Type</a></li>
                                                --}}
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.event.locations') }}">Venue/Locations</a>
                                                </li>
                                                {{-- <li><a class="dropdown-item"
                                                        href="{{ route('admin.locations') }}">User
                                                        Locations</a></li> --}}
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.managers') }}">Manager</a></li>

                                                {{-- <li><a class="dropdown-item" href="{{ route('admin.pos.user') }}">POS
                                                        User</a></li> --}}
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.setting') }}">Settings</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.unapprove-ambassador-for-events') }}">Ambassadors
                                                        Pending Approval</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('notifications.index') }}">Notifications</a>
                                                </li>
                                                <li><a class="dropdown-item" href="{{ route('admin.profile') }}">My
                                                        Account</a></li>
                                            </ul>
                                        </div>
                                    </li>
                                    <li class="nav-item">
                                        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="nav-link p-3"
                                                style="background:none; border:none; padding:0; font-size: inherit; color: inherit; cursor: pointer;font-weight: bold;font-weight: bold;">Exit</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
                <div class="offcanvas mobile-sidebar" tabindex="-1" id="offcanvasNavbar"
                    aria-labelledby="offcanvasNavbarLabel">
                    <div class="offcanvas-header">
                        <a class="navbar-brand" href="{{ route('admin.events') }}">
                            <img src="{{ asset('assets/images/logo.png') }}">
                        </a>
                    </div>
                    <div class="offcanvas-body">
                        <ul class="navbar-nav pe-3">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/events') ? 'active' : '' }}"
                                    href="{{ route('admin.events') }}">Event</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/recaps') ? 'active' : '' }}"
                                    href="{{ route('admin.recaps') }}">Recap</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/brands') ? 'active' : '' }}"
                                    href="{{ route('admin.brands') }}">Brand</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/bill-to') ? 'active' : '' }}"
                                    href="{{ route('admin.bills') }}">Bill To</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/bulks') ? 'active' : '' }}"
                                    href="{{ route('admin.bulks') }}">Bulk</a>
                            </li>
                            {{-- <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/import-data') ? 'active' : '' }}"
                                    href="{{ route('admin.import-data') }}">Import Data</a>
                            </li> --}}
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/receipt-ocr') ? 'active' : '' }}"
                                    href="{{ route('admin.receipt-ocr.index') }}">Receipt OCR</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/models') ? 'active' : '' }}"
                                    href="{{ route('admin.models') }}">Model</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/event-invoicing*') ? 'active' : '' }}"
                                    href="{{ route('admin.event-invoicing') }}">Event billing</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/invoices*') ? 'active' : '' }}"
                                    href="{{ route('admin.invoices') }}">QB invoices</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/messaging') ? 'active' : '' }}"
                                    href="{{ route('admin.messaging') }}">Messaging</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/dwolla/balance-alerts') ? 'active' : '' }}"
                                    href="{{ route('admin.dwolla.balance-alerts') }}">
                                    Balance Alerts
                                    @if ($balanceAlertBadge)
                                        <span class="badge bg-danger ms-1">{{ $balanceAlertBadge }}</span>
                                    @endif
                                </a>
                            </li>
                            <li>
                                <div class="dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Pay
                                    </a>
                                </div>
                            </li>
                            <li>
                                <div class="dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        More
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item"
                                                href="{{ route('admin.event-department') }}">Event Departments</a>
                                        </li>
                                        <li><a class="dropdown-item"
                                                href="{{ route('admin.event-destributor') }}">Event Distributor</a>
                                        </li>
                                        <li><a class="dropdown-item" href="{{ route('admin.eventtypes') }}">Event
                                                Types</a>
                                        </li>
                                        <li><a class="dropdown-item" href="{{ route('admin.documents') }}">Aras
                                                Documents</a>
                                        </li>
                                        <li><a class="dropdown-item" href="{{ route('admin.markets') }}">Markets</a>
                                        </li>
                                        {{--
                                        <li><a class="dropdown-item" href="{{ route('admin.venues') }}">Venue
                                                Type</a></li>
                                        --}}
                                        <li><a class="dropdown-item"
                                                href="{{ route('admin.locations') }}">Locations</a></li>
                                        <li><a class="dropdown-item"
                                                href="{{ route('admin.ambassadors') }}">Ambassadors</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.managers') }}">Manager</a>
                                        </li>
                                        <li><a class="dropdown-item" href="{{ route('admin.setting') }}">Settings</a>
                                        </li>
                                        <li><a class="dropdown-item"
                                                href="{{ route('admin.unapprove-ambassador-for-events') }}">Ambassadors
                                                Pending Approval
                                                Ambassador for Event</a></li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="nav-link p-3"
                                        style="background:none; border:none; padding:0; font-size: inherit; color: inherit; cursor: pointer;font-weight: bold;font-weight: bold;">Exit</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
    @elseif ($user->role_id == 2)
        <!--Brand--->
        <header>
            <div class="header2">
                <nav class="navbar py-0">
                    <div class="container-xl justify-content-xl-start">
                        <a class="navbar-brand py-0 ms-xl-3" href="{{ route('brand.events') }}">
                            <img src="{{ asset('assets/images/logo.png') }}">
                        </a>
                        <button class="navbar-toggler shadow-none btn-sm fs-6 d-xl-none p-2 my-2" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar"
                            aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div class="menu d-none d-xl-block">
                            <div class="d-flex">
                                <ul class="nav mx-xxl-4">
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('brand/calendar') ? 'active' : '' }}"
                                            href="{{ route('brand.calendar') }}">Calendar</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('brand/events') ? 'active' : '' }}"
                                            href="{{ route('brand.events') }}">Events</a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('notifications*') ? 'active' : '' }}"
                                            href="{{ route('notifications.index') }}">Notifications</a>
                                    </li>

                                    <li class="nav-item">
                                        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="nav-link p-3"
                                                style="background:none; border:none; padding:0; font-size: inherit; color: inherit; cursor: pointer;font-weight: bold;font-weight: bold;">Logout</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
                <div class="offcanvas mobile-sidebar" tabindex="-1" id="offcanvasNavbar"
                    aria-labelledby="offcanvasNavbarLabel">
                    <div class="offcanvas-header">
                        <a class="navbar-brand" href="#">
                            <img src="{{ asset('assets/images/logo.png') }}">
                        </a>
                    </div>
                    <div class="offcanvas-body">
                        <ul class="navbar-nav pe-3">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('brand/events') ? 'active' : '' }}"
                                    href="{{ route('brand.events') }}">Events</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('notifications*') ? 'active' : '' }}"
                                    href="{{ route('notifications.index') }}">Notifications</a>
                            </li>

                            <li class="nav-item">
                                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="nav-link py-3"
                                        style="background:none; border:none; padding:0; font-size: inherit; color: inherit; cursor: pointer;font-weight: bold;font-weight: bold;">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
    @elseif ($user->role_id == 3)
        <!--Client--->
        <header>
            <div class="header2">
                <nav class="navbar py-0">
                    <div class="container-xl justify-content-xl-start">
                        <a class="navbar-brand py-0 ms-xl-3" href="{{ route('client.calendar') }}">
                            <img src="{{ asset('assets/images/logo.png') }}">
                        </a>
                        <button class="navbar-toggler shadow-none btn-sm fs-6 d-xl-none p-2 my-2" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar"
                            aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div class="menu d-none d-xl-block">
                            <div class="d-flex">
                                <ul class="nav mx-xxl-4">
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('client/calendar') ? 'active' : '' }}"
                                            href="{{ route('client.calendar') }}">Calendar</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('client/events') ? 'active' : '' }}"
                                            href="{{ route('client.events') }}">Event</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('client/recaps') ? 'active' : '' }}"
                                            href="{{ route('client.recaps') }}">Recap</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('client/profile') ? 'active' : '' }}"
                                            href="{{ route('client.profile') }}">My Account</a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('notifications*') ? 'active' : '' }}"
                                            href="{{ route('notifications.index') }}">Notifications</a>
                                    </li>

                                    <li class="nav-item">
                                        <form action="{{ route('logout') }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            <button type="submit" class="nav-link p-3"
                                                style="background:none; border:none; padding:0; font-size: inherit; color: inherit; cursor: pointer;font-weight: bold;font-weight: bold;">Logout</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
                <div class="offcanvas mobile-sidebar" tabindex="-1" id="offcanvasNavbar"
                    aria-labelledby="offcanvasNavbarLabel">
                    <div class="offcanvas-header">
                        <a class="navbar-brand" href="#">
                            <img src="{{ asset('assets/images/logo.png') }}">
                        </a>
                    </div>
                    <div class="offcanvas-body">
                        <ul class="navbar-nav pe-3">
                            <li class="nav-item">
                                <a class="nav-link py-3 {{ request()->is('client/calendar') ? 'active' : '' }}"
                                    href="{{ route('client.calendar') }}">Calendar</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('client/events') ? 'active' : '' }}"
                                    href="{{ route('client.events') }}">Events</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('client/recaps') ? 'active' : '' }}"
                                    href="{{ route('client.recaps') }}">Recap</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('client/profile') ? 'active' : '' }}"
                                    href="{{ route('client.profile') }}">My Account</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('notifications*') ? 'active' : '' }}"
                                    href="{{ route('notifications.index') }}">Notifications</a>
                            </li>

                            <li class="nav-item">
                                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="nav-link py-3"
                                        style="background:none; border:none; padding:0; font-size: inherit; color: inherit; cursor: pointer;font-weight: bold;font-weight: bold;">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
    @elseif ($user->role_id == 4)
        <!--Location--->
        <header>
            <div class="header2">
                <nav class="navbar py-0">
                    <div class="container-xl justify-content-xl-start">
                        <a class="navbar-brand py-0 ms-xl-3" href="{{ route('location.calendar') }}">
                            <img src="{{ asset('assets/images/logo.png') }}">
                        </a>
                        <button class="navbar-toggler shadow-none btn-sm fs-6 d-xl-none p-2 my-2" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar"
                            aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div class="menu d-none d-xl-block">
                            <div class="d-flex">
                                <ul class="nav mx-xxl-4">
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('location/calendar') ? 'active' : '' }}"
                                            href="{{ route('location.calendar') }}">Calendar</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('location/events') ? 'active' : '' }}"
                                            href="{{ route('location.events') }}">Events</a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('location/profile') ? 'active' : '' }}"
                                            href="{{ route('location.profile') }}">My Account</a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('notifications*') ? 'active' : '' }}"
                                            href="{{ route('notifications.index') }}">Notifications</a>
                                    </li>

                                    <li class="nav-item">
                                        <form action="{{ route('logout') }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            <button type="submit" class="nav-link p-3"
                                                style="background:none; border:none; padding:0; font-size: inherit; color: inherit; cursor: pointer;font-weight: bold;font-weight: bold;">Logout</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
                <div class="offcanvas mobile-sidebar" tabindex="-1" id="offcanvasNavbar"
                    aria-labelledby="offcanvasNavbarLabel">
                    <div class="offcanvas-header">
                        <a class="navbar-brand" href="#">
                            <img src="{{ asset('assets/images/logo.png') }}">
                        </a>
                    </div>
                    <div class="offcanvas-body">
                        <ul class="navbar-nav pe-3">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('location/calendar') ? 'active' : '' }}"
                                    href="{{ route('location.calendar') }}">Calendar</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('location/events') ? 'active' : '' }}"
                                    href="{{ route('location.events') }}">Events</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('location/profile') ? 'active' : '' }}"
                                    href="{{ route('location.profile') }}">My Account</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('notifications*') ? 'active' : '' }}"
                                    href="{{ route('notifications.index') }}">Notifications</a>
                            </li>

                            <li class="nav-item">
                                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="nav-link py-3"
                                        style="background:none; border:none; padding:0; font-size: inherit; color: inherit; cursor: pointer;font-weight: bold;font-weight: bold;">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
    @elseif($user->role_id == 6)
        <header>
            <div class="header2">
                <nav class="navbar py-0">
                    <div class="container-xxl d-flex flex-nowrap justify-content-xl-center">
                        <a class="navbar-brand py-0" href="{{ route('manager.calendar') }}">
                            <img src="{{ asset('assets/images/logo.png') }}">
                        </a>
                        <button class="navbar-toggler shadow-none btn-sm fs-6 d-xl-none p-2 my-2" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar"
                            aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div class="menu d-none d-xl-block">
                            <div class="d-flex">
                                <ul class="nav mx-xxl-4">
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('manager/calendar') ? 'active' : '' }}"
                                            href="{{ route('manager.calendar') }}">Calendar</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('manager/events') ? 'active' : '' }}"
                                            href="{{ route('manager.events') }}">Event</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('manager/recaps') ? 'active' : '' }}"
                                            href="{{ route('manager.recaps') }}">Recap</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('manager/reimbursements') ? 'active' : '' }}"
                                            href="{{ route('manager.reimbursements.index') }}">Reimbursement</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('manager/brands') ? 'active' : '' }}"
                                            href="{{ route('manager.brands') }}">Brand</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('manager/bill-to') ? 'active' : '' }}"
                                            href="{{ route('manager.bills') }}">Bill To</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('manager/bulks') ? 'active' : '' }}"
                                            href="{{ route('manager.bulks') }}">Bulk</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('manager/ambassadors') ? 'active' : '' }}"
                                            href="{{ route('manager.ambassadors') }}">Ambassadors</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('manager/messaging') ? 'active' : '' }}"
                                            href="{{ route('manager.messaging') }}">Messaging</a>
                                    </li>

                                    {{-- QuickBooks dropdown for managers --}}
                                    <li>
                                        <div class="dropdown">
                                            <a class="nav-link dropdown-toggle py-3" href="#" role="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                Quickbooks
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.expense-categories.index') }}">Expense
                                                        Category</a>
                                                </li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.payment-methods.index') }}">Payment
                                                        Methods</a>
                                                </li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.invoices') }}">Invoices</a>
                                                </li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.expenses') }}">Expenses</a>
                                                </li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.vendor-1099.index') }}">1099 Vendor
                                                        Report</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.services.index') }}">Services</a>
                                                </li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.event-invoicing') }}">Event invoicing
                                                        (prepare)</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.invoices') }}">QuickBooks invoices
                                                        (synced)</a></li>
                                            </ul>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="dropdown">
                                            <a class="nav-link dropdown-toggle py-3" href="#" role="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                More
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.event-department') }}">Event
                                                        Departments</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.event-destributor') }}">Event
                                                        Distributor</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.markets') }}">Markets</a></li>

                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.event.locations') }}">Locations</a>
                                                </li>
                                                {{-- <li><a class="dropdown-item"
                                                        href="{{ route('manager.locations') }}">User Locations</a>
                                                </li> --}}
                                                <!--<li><a class="dropdown-item"-->
                                                <!--        href="{{ route('manager.managers') }}">Manager</a></li>-->

                                                {{-- <li><a class="dropdown-item"
                                                        href="{{ route('manager.pos.user') }}">POS User</a></li> --}}
                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.setting') }}">Settings</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.unapprove-ambassador-for-events') }}">Ambassadors
                                                        Pending Approval</a></li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('notifications.index') }}">Notifications</a>
                                                </li>
                                                <li><a class="dropdown-item"
                                                        href="{{ route('manager.profile') }}">My Account</a></li>
                                            </ul>
                                        </div>
                                    </li>
                                    <li class="nav-item">
                                        <form action="{{ route('logout') }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            <button type="submit" class="nav-link p-3"
                                                style="background:none; border:none; padding:0; font-size: inherit; color: inherit; cursor: pointer;font-weight: bold;font-weight: bold;">Exit</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
                <div class="offcanvas mobile-sidebar" tabindex="-1" id="offcanvasNavbar"
                    aria-labelledby="offcanvasNavbarLabel">
                    <div class="offcanvas-header">
                        <a class="navbar-brand" href="{{ route('manager.events') }}">
                            <img src="{{ asset('assets/images/logo.png') }}">
                        </a>
                    </div>
                    <div class="offcanvas-body">
                        <ul class="navbar-nav pe-3">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('manager/events') ? 'active' : '' }}"
                                    href="{{ route('manager.events') }}">Event</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('manager/recaps') ? 'active' : '' }}"
                                    href="{{ route('manager.recaps') }}">Recap</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('manager/brands') ? 'active' : '' }}"
                                    href="{{ route('manager.brands') }}">Brand</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('manager/bill-to') ? 'active' : '' }}"
                                    href="{{ route('manager.bills') }}">Bill To</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('manager/bulks') ? 'active' : '' }}"
                                    href="{{ route('manager.bulks') }}">Bulk</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('manager/models') ? 'active' : '' }}"
                                    href="{{ route('manager.models') }}">Model</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('manager/messaging') ? 'active' : '' }}"
                                    href="{{ route('manager.messaging') }}">Messaging</a>
                            </li>

                            <li>
                                <div class="dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Pay
                                    </a>
                                </div>
                            </li>
                            <li>
                                <div class="dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        More
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item"
                                                href="{{ route('manager.event-department') }}">Event Departments</a>
                                        </li>
                                        <li><a class="dropdown-item"
                                                href="{{ route('manager.event-destributor') }}">Event Distributor</a>
                                        </li>
                                        <li><a class="dropdown-item" href="{{ route('manager.documents') }}">Aras
                                                Documents</a>
                                        </li>
                                        <li><a class="dropdown-item"
                                                href="{{ route('manager.markets') }}">Markets</a>
                                        </li>
                                        {{--
                                        <li><a class="dropdown-item" href="{{ route('manager.venues') }}">Venue
                                                Type</a></li>
                                        --}}
                                        <li><a class="dropdown-item"
                                                href="{{ route('manager.locations') }}">Locations</a></li>
                                        <li><a class="dropdown-item"
                                                href="{{ route('manager.ambassadors') }}">Ambassadors</a></li>
                                        <!--<li><a class="dropdown-item"-->
                                        <!--        href="{{ route('manager.managers') }}">Manager</a></li>-->
                                        <li><a class="dropdown-item"
                                                href="{{ route('manager.setting') }}">Settings</a></li>
                                        <li><a class="dropdown-item" <li><a class="dropdown-item"
                                                    href="{{ route('manager.unapprove-ambassador-for-events') }}">Ambassadors
                                                    Pending Approval
                                                    Ambassador for Event</a></li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="nav-link p-3"
                                        style="background:none; border:none; padding:0; font-size: inherit; color: inherit; cursor: pointer;font-weight: bold;font-weight: bold;">Exit</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
    @else
        <!--Ambassador--->
        @php
            $ambassadorUser = auth()->user();
            $dwollaMissingFields = [];
            if ($ambassadorUser && (int) $ambassadorUser->role_id === 5 && !$ambassadorUser->dwollaCustomer) {
                $dwollaMissingFields = app(\App\Services\Dwolla\CustomerService::class)->getMissingDwollaFields(
                    $ambassadorUser,
                );
            }
            if ($dwollaMissingFields === [] && session('dwolla_missing_fields')) {
                $dwollaMissingFields = session('dwolla_missing_fields', []);
            }
        @endphp
        @include('ambassador.partials.dwolla-payment-setup-prompt', [
            'dwollaMissingFields' => $dwollaMissingFields,
        ])
        @include('ambassador.partials.bank-setup-required-banner')
        @include('ambassador.partials.location-setup-banner')
        <header>
            <div class="header2">
                <nav class="navbar py-0">
                    <div class="container-xxl d-flex flex-nowrap justify-content-xl-center">
                        <a class="navbar-brand py-0" href="{{ route('ambassador.calendar') }}">
                            <img src="{{ asset('assets/images/logo.png') }}">
                        </a>
                        <button class="navbar-toggler shadow-none btn-sm fs-6 d-xl-none p-2 my-2" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar"
                            aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div class="menu d-none d-xl-block">
                            <div class="d-flex">
                                <ul class="nav mx-xxl-4">
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('ambassador/calendar') ? 'active' : '' }}"
                                            href="{{ route('ambassador.calendar') }}">Calendar</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('ambassador/events') ? 'active' : '' }}"
                                            href="{{ route('ambassador.events') }}">Events</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('ambassador/recaps') ? 'active' : '' }}"
                                            href="{{ route('ambassador.recaps') }}">Create Recap</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('ambassador/submitted-recap') ? 'active' : '' }}"
                                            href="{{ route('ambassador.submitted-recap') }}">Submitted Recap</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('ambassador/reimbursements') ? 'active' : '' }}"
                                            href="{{ route('ambassador.reimbursements.index') }}">Reimbursement</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('ambassador/pay-forecast') ? 'active' : '' }}"
                                            href="{{ route('ambassador.pay-forecast') }}">Pay Forecast</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link py-3 {{ request()->is('ambassador/payment') ? 'active' : '' }}"
                                            href="{{ route('ambassador.payment') }}">Payments</a>
                                    </li>
                                    {{-- Bug 10 (MC cmon1a7u4005d3r1h5cdct2vh): "Aras Document" used
                                        to link back to ambassador.recaps; ambassadors don't have a
                                        documents route yet, so hide the broken link rather than send
                                        them in a confusing loop. Re-enable when the route exists. --}}
                                    <li class="nav-item">
                                        <div class="dropdown">
                                            <a class="nav-link dropdown-toggle py-3" href="#" role="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                My Account
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item"
                                                        href="{{ route('ambassador.profile') }}">Account Settings</a>
                                                </li>

                                                <li><a class="dropdown-item"
                                                        href="{{ route('notifications.index') }}">Notifications</a>
                                                </li>

                                                <li><a class="dropdown-item"
                                                        href="{{ route('ambassador.dwolla.bank-account') }}">Bank
                                                        Account</a>
                                                </li>

                                                <li><a class="dropdown-item"
                                                        href="{{ route('ambassador.w9.show') }}">W9
                                                        Form</a>
                                                </li>

                                                <li><a class="dropdown-item"
                                                        href="{{ route('ambassador.dwolla.bank.transactions') }}">Transactions</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>

                                    <li class="nav-item">
                                        <form action="{{ route('logout') }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            <button type="submit" class="nav-link p-3"
                                                style="background:none; border:none; padding:0; font-size: inherit; color: inherit; cursor: pointer;font-weight: bold;font-weight: bold;">logout</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
                <div class="offcanvas mobile-sidebar" tabindex="-1" id="offcanvasNavbar"
                    aria-labelledby="offcanvasNavbarLabel">
                    <div class="offcanvas-header">
                        <a class="navbar-brand" href="{{ route('admin.events') }}">
                            <img src="{{ asset('assets/images/logo.png') }}">
                        </a>
                    </div>
                    <div class="offcanvas-body">
                        <ul class="navbar-nav pe-3">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('ambassador/calendar') ? 'active' : '' }}"
                                    href="{{ route('ambassador.calendar') }}">Calendar</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('ambassador/events') ? 'active' : '' }}"
                                    href="{{ route('ambassador.events') }}">Events</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('ambassador/recaps') ? 'active' : '' }}"
                                    href="{{ route('ambassador.recaps') }}">Create Recap</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('ambassador/submitted-recap') ? 'active' : '' }}"
                                    href="{{ route('ambassador.submitted-recap') }}">Submitted Recap</a>
                            </li>
                            {{-- Bug 10 (MC cmon1a7u4005d3r1h5cdct2vh): mobile-menu links
                                for Pay Forecast, Payments, and Aras Document all pointed at
                                ambassador.recaps, hiding those screens on mobile. --}}
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('ambassador/pay-forecast') ? 'active' : '' }}"
                                    href="{{ route('ambassador.pay-forecast') }}">Pay Forecast</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('ambassador/payment') ? 'active' : '' }}"
                                    href="{{ route('ambassador.payment') }}">Payments</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('ambassador/profile') ? 'active' : '' }}"
                                    href="{{ route('ambassador.profile') }}">My Account</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('notifications*') ? 'active' : '' }}"
                                    href="{{ route('notifications.index') }}">Notifications</a>
                            </li>
                            <li class="nav-item">
                                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="nav-link py-3"
                                        style="background:none; border:none; padding:0; font-size: inherit; color: inherit; cursor: pointer;font-weight: bold;font-weight: bold;">Exit</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
    @endif
@endif
