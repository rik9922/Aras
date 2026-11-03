<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme" style="background: rgb(46 81 124) !important;">
    <div class="app-brand demo">
        <a href="{{ route('admin.dashoard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="{{ asset('assets/img/logo.png') }}" alt="" style="background-color:rgb(46 81 124) !important">
            </span>
            <span class="app-brand-text demo menu-text fw-bolder ms-2">Aras Promotions</span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item {{ request()->is('admin/dashboard') ? 'active' : '' }}">
            <a href="{{ route('admin.dashoard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Dashboard">Dashboard</div>
            </a>
        </li>

        <li class="menu-item {{ request()->is('admin/users', 'admin/users/*') ? 'active' : '' }}">
            <a href="{{ route('users.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div data-i18n="Users">Users</div>
            </a>
        </li>
        <li class="menu-item {{ request()->is('admin/delete-request', 'admin/delete-request/*') ? 'active' : '' }}">
            <a href="{{ route('delete-request.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-user-minus"></i>
                <div data-i18n="Delete Account Request">Delete Account Request</div>
            </a>
        </li>
        
        <li class="menu-item {{ request()->is('admin/contact-us') ? 'active' : '' }}">
            <a href="{{ route('contactUs') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-phone"></i>
                <div data-i18n="Contact Us">Contact Us</div>
            </a>
        </li>
        
        <li class="menu-item {{ request()->is('admin/info-pages', 'admin/info-pages/*') ? 'active' : '' }}">
            <a href="{{ route('info-pages.index') }}" class="menu-link">
                {{-- <i class="menu-icon tf-icons bx bx-wallet"></i> --}}
                <i class="menu-icon tf-icons bx bx-cog"></i>
                <div data-i18n="Info Pages">Info Pages </div>
            </a>
        </li>
       
        
       

    </ul>
</aside>
