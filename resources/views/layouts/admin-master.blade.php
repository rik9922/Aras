<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact " dir="ltr" data-theme="theme-default" data-assets-path='{{ url("/") }}/assets/' data-template="vertical-menu-template">

<head>
    @include('layouts.partials.head')
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            @include('layouts.partials.sidebar')
            <div class="layout-page">
                @include('layouts.partials.header')
                <div class="content-wrapper">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
    @include('layouts.partials.footer')
</body>

</html>
