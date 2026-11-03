<style>
    #pwaPopup {
        display: none;
        position: fixed;
        top: 12%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        border-radius: 8px;
        text-align: center;
    }

    #pwaPopup button {
        margin: 10px 2px;
        padding: 10px 20px;
        border: none;
        cursor: pointer;
        border-radius: 5px;
    }

    #installConfirm {
        background-color: #232426;
        color: white;
    }

    #installCancel {
        background-color: #ccc;
        color: #ed1e7e;
    }

    .welocome-admin-img img {
        width: 100%;
        object-fit: contain;
    }

    @media only screen and (max-width: 540px) {
        #pwaPopup {
            top: 16%;
        }
    }
</style>
<div id="pwaPopup">
    <p><b>Install our app on your home screen for quick and easy access.</b></p>
    <button id="installConfirm">Install</button>
    <button id="installCancel">Remind me later</button>
</div>
<script src="{{asset('assets/js/jquery-3.6.0.min.js')}}"></script>

<script>
    let deferredPrompt;

    $(document).ready(function() {
        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            deferredPrompt = event;

            // Check 24-hour reminder before showing popup
            var stored = localStorage.getItem('pwa_install_remind_until');
            if (stored) {
                var remindUntil = parseInt(stored, 10);
                if (!isNaN(remindUntil) && Date.now() < remindUntil) {
                    // Reminder still active — do not show popup
                    return;
                }
                // Reminder expired — clean up
                localStorage.removeItem('pwa_install_remind_until');
            }

            $("#pwaPopup").fadeIn();
        });

        $("#installConfirm").click(function() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    } else {
                        console.log('User dismissed the install prompt');
                    }
                    deferredPrompt = null;
                });
            }
            $("#pwaPopup").fadeOut();
        });

        $("#installCancel").click(function() {
            var remindUntil = Date.now() + 24 * 60 * 60 * 1000;
            localStorage.setItem('pwa_install_remind_until', remindUntil.toString());
            $("#pwaPopup").fadeOut();
        });

        // Hide popup after installation
        window.addEventListener('appinstalled', () => {
            console.log('PWA was installed');
            $("#pwaPopup").hide();
        });
    });
</script>
