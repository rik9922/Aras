<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light-style customizer-hide"
    dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Rise Shine & Swing</title>
    
    @include('layouts.partials.head')
</head>

<body>
    <div id="app">

        <main>
            @yield('content')
        </main>
    </div>
    @include('layouts.partials.footer')
</body>

</html>
