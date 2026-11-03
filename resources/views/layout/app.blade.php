<!DOCTYPE html>
<html lang="en" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr"
    data-theme="theme-default" data-assets-path='{{ url('/') }}/assets/' data-template="vertical-menu-template">

<head>
    @include('layout.partials.head')
    <link rel="stylesheet" href="{{ asset('assets/css/buttons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/select2/select2.css') }}">
    <style>
        .light-style .select2-container--default .select2-selection--single .select2-selection__rendered,
        .light-style .select2-container--default .select2-selection--multiple .select2-selection__choice {
            color: var(--text-color);
        }

        .light-style .select2-container--default .select2-selection__placeholder {
            color: #b4bdc6;
        }
    </style>
    @stack('styles')
    @googlefonts
</head>
@if (Auth::check())
    <style>
        body {
            background: #F7F8FB !important;
            background-image: none !important;
        }
    </style>
@else
    <style>
        body {
            background-image: linear-gradient(100deg, #00000075 55%, #ECEEF1 55.1%), url("/assets/images/bg-image.jpeg");
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            height: 100vh;
            position: relative;
        }
    </style>
@endif


<style>
    .offcanvas.mobile-sidebar {
        background: var(--header2-bg);
        color: #fff;
    }

    .mobile-sidebar .navbar-nav .nav-link.active {
        background: #000;
        color: #fff !important;
    }

    .mobile-sidebar .navbar-nav .nav-link {
        padding-inline: 10px;
    }
</style>

<body>
    <div id="root">
        @include('layout.partials.header')
        <div class="main-section" id="main-section">
            @include('layout.partials.flash-messages')
            @yield('content')
        </div>
    </div>

    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('assets/select2/select2.js') }}"></script>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/serviceworker.js')
                    .then(function(registration) {
                        console.log('Service Worker registered with scope:', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('Service Worker registration failed:', error);
                    });
            });
        }
    </script>

    <script>
        $(document).ready(function() {
            $('.multiple-select2').select2();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchElement = document.getElementById('search');
            const searchIcon = document.getElementById('searchIcon');

            if (searchElement && searchIcon) {
                searchElement.addEventListener('show.bs.collapse', () => {
                    searchIcon.classList.remove('fa-caret-up');
                    searchIcon.classList.add('fa-caret-down');
                });

                searchElement.addEventListener('hide.bs.collapse', () => {
                    searchIcon.classList.remove('fa-caret-down');
                    searchIcon.classList.add('fa-caret-up');
                });
            }
        });
    </script>
    <script>
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>

    @if (Auth::check())
    <script>
        (function() {
            const storageKey = 'notif_visit_prompt_last_dismissed_at';
            const remindAfterMs = 24 * 60 * 60 * 1000; // 24 hours
            const permission = (typeof Notification !== 'undefined') ? Notification.permission : 'default';
            let promptTimer = null;

            // No reminder when browser already granted notifications.
            if (permission === 'granted') return;

            let lastDismissedAt = 0;
            try {
                lastDismissedAt = Number(localStorage.getItem(storageKey) || '0');
            } catch (e) {
                lastDismissedAt = 0;
            }
            if (Date.now() - lastDismissedAt < remindAfterMs) return;

            function userIsEditing() {
                const active = document.activeElement;
                return !!active && (
                    active.matches('input, textarea, select, button') ||
                    active.closest('.select2-container')
                );
            }

            function buildPrompt() {
                if (document.getElementById('visit-notification-prompt') || userIsEditing()) {
                    return;
                }

                const box = document.createElement('div');
                box.id = 'visit-notification-prompt';
                box.style.cssText = [
                    'position:fixed',
                    'right:16px',
                    'bottom:16px',
                    'max-width:360px',
                    'z-index:9999',
                    'background:#fff',
                    'border:1px solid #d9d9d9',
                    'border-radius:10px',
                    'box-shadow:0 8px 24px rgba(0,0,0,.15)',
                    'padding:12px'
                ].join(';');

                box.innerHTML = `
                    <div style="font-weight:600; margin-bottom:6px;">Enable Push Notifications</div>
                    <div style="font-size:var(--font-size-body); color:#555; margin-bottom:10px;">
                        Stay updated with important alerts. Enable push notifications to receive real-time updates.
                    </div>
                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                        <button type="button" id="visit-notif-later" class="btn btn-sm btn-outline-secondary">Later</button>
                        <button type="button" id="visit-notif-enable" class="btn btn-sm btn-primary">Enable</button>
                    </div>
                `;

                document.body.appendChild(box);

                const laterBtn = document.getElementById('visit-notif-later');
                const enableBtn = document.getElementById('visit-notif-enable');

                laterBtn?.addEventListener('click', function() {
                    try { localStorage.setItem(storageKey, String(Date.now())); } catch (e) {}
                    box.remove();
                });

                enableBtn?.addEventListener('click', function() {
                    if (typeof Notification !== 'undefined' && typeof Notification.requestPermission === 'function') {
                        Notification.requestPermission()
                            .catch(function() { return 'default'; })
                            .finally(function() {
                                window.location.href = "{{ route('notifications.settings') }}";
                            });
                        return;
                    }

                    window.location.href = "{{ route('notifications.settings') }}";
                });
            }

            function schedulePrompt(delayMs = 5000) {
                if (promptTimer) {
                    clearTimeout(promptTimer);
                }
                promptTimer = setTimeout(function() {
                    if (userIsEditing()) {
                        schedulePrompt(3000);
                        return;
                    }
                    buildPrompt();
                }, delayMs);
            }

            window.addEventListener('load', function() {
                schedulePrompt();
            });

            document.addEventListener('focusin', function(event) {
                if (event.target.matches('input, textarea, select') || event.target.closest('.select2-container')) {
                    if (promptTimer) {
                        clearTimeout(promptTimer);
                    }
                }
            });

            document.addEventListener('focusout', function(event) {
                if (event.target.matches('input, textarea, select') || event.target.closest('.select2-container')) {
                    schedulePrompt(3000);
                }
            });
        })();
    </script>
    @endif

    @stack('scripts')

</body>

</html>
