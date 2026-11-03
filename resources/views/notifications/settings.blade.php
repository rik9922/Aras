@extends('layout.app', ['page_name' => 'Notification Settings'])

@section('content')
    <div class="container py-3">
        <div class="page-card-design">
            <div class="row mt-3 px-2">
                <div class="col-12 pb-3 px-0 border-bottom">
                    <div class="h5 fw-bold text-uppercase">Notification Settings</div>
                    <div class="text-muted" style="font-size: var(--font-size-body);">Choose whether you want push and/or email notifications.</div>
                </div>
            </div>

            <div class="p-3">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form id="notification-preferences-form" action="{{ route('notifications.settings.update') }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3 form-check">
                                <input type="hidden" name="push_enabled" value="0">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="push_enabled"
                                    name="push_enabled"
                                    value="1"
                                    @if ($preference->push_enabled) checked @endif
                                >
                                <label class="form-check-label" for="push_enabled">
                                    Push notifications
                                </label>
                            </div>
                            <div class="text-muted" style="font-size: var(--font-size-body);">
                                Delivered via OneSignal.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3 form-check">
                                <input type="hidden" name="email_enabled" value="0">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="email_enabled"
                                    name="email_enabled"
                                    value="1"
                                    @if ($preference->email_enabled) checked @endif
                                >
                                <label class="form-check-label" for="email_enabled">
                                    Email notifications
                                </label>
                            </div>
                            <div class="text-muted" style="font-size: var(--font-size-body);">
                                Delivered via Laravel email.
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-design text-uppercase">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container pb-4" style="padding-left: 0; padding-right: 0;">
        <div class="p-3">
            <button
                type="button"
                id="onesignal-subscribe-btn"
                class="btn btn-design text-uppercase w-100"
                style="{{ $preference->push_enabled ? '' : 'display:none;' }}"
            >
                Subscribe to Push Notifications
            </button>
            <div id="push-subscribe-feedback" class="alert alert-success mt-2" style="display:none;">
                Push notifications are enabled for this browser.
            </div>
            <div id="push-subscribe-error" class="alert alert-danger mt-2" style="display:none;"></div>
        </div>
    </div>

    {{-- OneSignal web push preference sync + device token registration --}}
    <script>
        (function() {
            const userId = {{ Auth::id() }};
            const appId = @json(config('app.app_id'));
            
            // Use OneSignal's dedicated worker (subdirectory approach).
            // OneSignal's docs expect `serviceWorkerPath` to be the directory,
            // and it will look for `OneSignalSDKWorker.js` inside it.
            const swPathDir = '/onesignal/';
            const swScriptUrl = '/onesignal/OneSignalSDKWorker.js';

            let oneSignal = null;
            let oneSignalReadyPromise = null;
            let oneSignalPreloadPromise = null;
            let oneSignalReady = false;

            function debugLog(debugPanel, message, data) {
                // UI/console debug logs disabled for production flow.
                return;
            }

            function loadOneSignalSdk() {
                return new Promise((resolve, reject) => {
                    if (window.OneSignal || window.OneSignalDeferred) {
                        resolve();
                        return;
                    }

                    const existing = document.querySelector('script[data-onesignal-sdk="1"]');
                    if (existing) {
                        existing.addEventListener('load', () => resolve(), { once: true });
                        existing.addEventListener('error', () => reject(new Error('Failed to load OneSignal SDK')), { once: true });
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = 'https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js';
                    script.async = true;
                    script.dataset.onesignalSdk = '1';
                    script.onload = () => resolve();
                    script.onerror = () => reject(new Error('Failed to load OneSignal SDK'));
                    document.head.appendChild(script);
                });
            }

            async function ensureIndexedDbAvailable(debugPanel) {
                if (!('indexedDB' in window)) {
                    throw new Error('IndexedDB is not supported in this browser');
                }
                await new Promise((resolve, reject) => {
                    const req = indexedDB.open('onesignal_debug_probe', 1);
                    let settled = false;
                    const cleanup = () => {
                        try {
                            if (req.result) {
                                req.result.close();
                                indexedDB.deleteDatabase('onesignal_debug_probe');
                            }
                        } catch (e) {}
                    };
                    req.onupgradeneeded = function() {};
                    req.onsuccess = function() {
                        if (settled) return;
                        settled = true;
                        cleanup();
                        resolve();
                    };
                    req.onerror = function() {
                        if (settled) return;
                        settled = true;
                        reject(new Error('IndexedDB unavailable in this browser context'));
                    };
                });
                debugLog(debugPanel, 'IndexedDB availability check passed');
            }

            async function retryOperation(debugPanel, label, task, attempts = 3, delayMs = 1200) {
                let lastError = null;
                for (let i = 1; i <= attempts; i++) {
                    try {
                        debugLog(debugPanel, `${label} attempt ${i}/${attempts}`);
                        return await task();
                    } catch (e) {
                        lastError = e;
                        debugLog(debugPanel, `${label} attempt failed`, { attempt: i, error: String(e?.message || e) });
                        if (i < attempts) {
                            await new Promise((r) => setTimeout(r, delayMs));
                        }
                    }
                }
                throw lastError || new Error(`${label} failed`);
            }

            function getOneSignalFromDeferred(debugPanel) {
                return new Promise((resolve, reject) => {
                    let settled = false;
                    const timeout = setTimeout(() => {
                        if (settled) return;
                        settled = true;
                        reject(new Error('Timed out waiting for OneSignalDeferred callback'));
                    }, 10000);

                    window.OneSignalDeferred = window.OneSignalDeferred || [];
                    window.OneSignalDeferred.push(async function(OneSignal) {
                        if (settled) return;
                        settled = true;
                        clearTimeout(timeout);
                        debugLog(debugPanel, 'OneSignalDeferred callback received');
                        resolve(OneSignal);
                    });
                });
            }

            async function ensureServiceWorkerRegistered() {
                if (!('serviceWorker' in navigator)) return;
                try {
                    // Ensure there's a registration for the exact scope OneSignal uses.
                    await navigator.serviceWorker.register(swScriptUrl, { scope: '/onesignal/' });
                } catch (e) {
                    // If already registered or registration fails, we still attempt to wait.
                }

                try {
                    await navigator.serviceWorker.ready;
                } catch (e) {
                    // If ready can't be awaited (rare), we'll let OneSignal handle it.
                }
            }

            async function unregisterOneSignalWorker() {
                if (!('serviceWorker' in navigator)) return;
                try {
                    const regs = await navigator.serviceWorker.getRegistrations();
                    await Promise.all(
                        regs
                            .filter((r) => typeof r?.scope === 'string' && r.scope.includes('/onesignal/'))
                            .map((r) => r.unregister().catch(() => {}))
                    );
                } catch (e) {
                    // Ignore; unsubscribe should still proceed even if unregister fails.
                }
            }

            async function getOneSignal(debugPanel) {
                if (oneSignal) return oneSignal;
                if (!oneSignalReadyPromise) {
                    oneSignalReadyPromise = (async () => {
                        await loadOneSignalSdk();
                        const os = await getOneSignalFromDeferred(debugPanel);
                        await ensureServiceWorkerRegistered();
                        await os.init({
                            appId: appId,
                            serviceWorkerPath: swPathDir,
                            serviceWorkerParam: { scope: '/onesignal/' },
                            autoResubscribe: false,
                            notifyButton: { enable: false },
                            promptOptions: { slidedown: { enabled: false } }
                        });

                        oneSignal = os;
                        oneSignalReady = true;
                        debugLog(debugPanel, 'OneSignal initialized');
                        return oneSignal;
                    })();
                }
                return oneSignalReadyPromise;
            }

            async function withTimeout(promise, ms, label) {
                let timeoutId = null;
                const timeout = new Promise((_, reject) => {
                    timeoutId = setTimeout(() => reject(new Error(`${label} timed out after ${ms}ms`)), ms);
                });
                try {
                    return await Promise.race([promise, timeout]);
                } finally {
                    if (timeoutId) clearTimeout(timeoutId);
                }
            }

            async function waitForSubscriptionId(os, debugPanel, timeoutMs) {
                const startedAt = Date.now();
                while ((Date.now() - startedAt) < timeoutMs) {
                    const currentId = os?.User?.PushSubscription?.id ?? null;
                    const currentOptedIn = !!os?.User?.PushSubscription?.optedIn;
                    if (currentOptedIn && currentId) {
                        debugLog(debugPanel, 'Subscription id + optedIn ready', { id: currentId });
                        return currentId;
                    }
                    await new Promise((r) => setTimeout(r, 500));
                }
                return null;
            }

            function watchAndPersistSubscriptionId(os, debugPanel, feedback, persistFn) {
                let persisted = false;
                const startedAt = Date.now();
                const maxWaitMs = 60000;

                async function tryPersistFromCurrentState(source) {
                    if (persisted) return;
                    const id = os?.User?.PushSubscription?.id ?? null;
                    const optedIn = !!os?.User?.PushSubscription?.optedIn;
                    debugLog(debugPanel, 'Checking subscription state', { source, id, optedIn });
                    if (optedIn && id) {
                        const result = await persistFn(os);
                        const fullySubscribed = !!(
                            result?.ok &&
                            result?.subscriptionId &&
                            result?.body?.token_stored === true
                        );
                        if (fullySubscribed) {
                            if (feedback) feedback.style.display = '';
                            persisted = true;
                            debugLog(debugPanel, 'Subscription id persisted successfully', { id, source });
                        }
                    }
                }

                const interval = setInterval(async () => {
                    if (persisted) {
                        clearInterval(interval);
                        return;
                    }
                    if ((Date.now() - startedAt) > maxWaitMs) {
                        clearInterval(interval);
                        debugLog(debugPanel, 'Stopped background subscription watcher (timeout)');
                        return;
                    }
                    try {
                        await tryPersistFromCurrentState('poll');
                    } catch (e) {
                        debugLog(debugPanel, 'Background persist failed', { error: String(e?.message || e) });
                    }
                }, 1500);

                try {
                    os.User.PushSubscription.addEventListener('change', async function() {
                        try {
                            await tryPersistFromCurrentState('change-event');
                        } catch (e) {
                            debugLog(debugPanel, 'Change-event persist failed', { error: String(e?.message || e) });
                        }
                    });
                } catch (e) {
                    debugLog(debugPanel, 'Could not attach subscription change listener', { error: String(e?.message || e) });
                }

                return async function waitUntilPersisted(timeoutMs = 20000) {
                    const started = Date.now();
                    while ((Date.now() - started) < timeoutMs) {
                        if (persisted) return true;
                        await new Promise((r) => setTimeout(r, 400));
                    }
                    return false;
                };
            }

            document.addEventListener('DOMContentLoaded', function() {
                const subscribeBtn = document.getElementById('onesignal-subscribe-btn');
                const pushCheckbox = document.getElementById('push_enabled');
                const form = document.getElementById('notification-preferences-form');
                const saveBtn = form ? form.querySelector('button[type="submit"]') : null;
                const feedback = document.getElementById('push-subscribe-feedback');
                const errorBox = document.getElementById('push-subscribe-error');
                const debugPanel = null; // disabled for production
                const subscribeInitialLabel = subscribeBtn ? subscribeBtn.textContent : '';

                if (!subscribeBtn || !pushCheckbox || !form) return;
                let internalSubmit = false;

                function updateSubscribeButtonVisibility() {
                    subscribeBtn.style.display = pushCheckbox.checked ? '' : 'none';
                    if (!pushCheckbox.checked && feedback) feedback.style.display = 'none';
                }

                async function persistPushState(os, overrides = {}) {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const timeZone = (() => {
                        try { return Intl.DateTimeFormat().resolvedOptions().timeZone; } catch (e) { return null; }
                    })();
                    const subscriptionId =
                        overrides.token !== undefined
                            ? overrides.token
                            : (os?.User?.PushSubscription?.id ?? null);
                    const optedIn =
                        overrides.optedIn !== undefined
                            ? !!overrides.optedIn
                            : !!os?.User?.PushSubscription?.optedIn;

                    debugLog(debugPanel, 'Persisting push state', { subscriptionId, optedIn, timeZone });
                    const response = await fetch("{{ route('notifications.push-token') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                        },
                        body: JSON.stringify({
                            token: subscriptionId,
                            opted_in: optedIn,
                            time_zone: timeZone
                        })
                    });
                    let body = null;
                    try {
                        body = await response.json();
                    } catch (e) {
                        body = null;
                    }
                    debugLog(debugPanel, 'Persist push response', { status: response.status, body });
                    return {
                        ok: response.ok,
                        status: response.status,
                        body: body,
                        subscriptionId: subscriptionId,
                        optedIn: optedIn
                    };
                }

                updateSubscribeButtonVisibility();
                pushCheckbox.addEventListener('change', updateSubscribeButtonVisibility);

                // Preload OneSignal so when user clicks subscribe we don't have to
                // await initialization (browser permission prompts require it to
                // be triggered directly from the click event).
                subscribeBtn.disabled = true;
                if (errorBox) {
                    errorBox.style.display = 'none';
                    errorBox.textContent = '';
                }

                oneSignalPreloadPromise = (async () => {
                    try {
                        await ensureIndexedDbAvailable(debugPanel);
                        await getOneSignal(debugPanel);
                    } catch (e) {
                        oneSignalReady = false;
                        if (errorBox) {
                            errorBox.style.display = '';
                            errorBox.textContent = `Failed to prepare push notifications: ${String(e?.message || e)}`;
                        }
                    } finally {
                        subscribeBtn.disabled = false;
                        subscribeBtn.textContent = subscribeInitialLabel;
                    }
                })();

                form.addEventListener('submit', async function(e) {
                    if (internalSubmit) return;
                    if (pushCheckbox.checked) return; // only handle unsubscribe path

                    e.preventDefault();

                    if (saveBtn) saveBtn.disabled = true;
                    subscribeBtn.disabled = true;
                    if (errorBox) {
                        errorBox.style.display = 'none';
                        errorBox.textContent = '';
                    }

                    try {
                        const os = await getOneSignal(debugPanel);
                        await retryOperation(
                            debugPanel,
                            'optOut',
                            () => withTimeout(os.User.PushSubscription.optOut(), 12000, 'optOut'),
                            2,
                            1000
                        );
                        if (feedback) feedback.style.display = 'none';
                    } catch (err) {
                        if (errorBox) {
                            errorBox.style.display = '';
                            errorBox.textContent = `Push unsubscribe warning: ${String(err?.message || err)}`;
                        }
                    } finally {
                        // User requested unsubscribe; always clear stored token(s) in the backend.
                        // We do this regardless of whether optOut() succeeded, because the SDK's
                        // in-memory `optedIn` flag may not flip immediately.
                        try {
                            await persistPushState(null, { token: null, optedIn: false });
                        } catch (e) {}

                        // Remove OneSignal's SW registration so the browser doesn't keep it around.
                        await unregisterOneSignalWorker();
                        if (saveBtn) saveBtn.disabled = false;
                        subscribeBtn.disabled = false;
                        internalSubmit = true;
                        form.submit();
                    }
                });

                subscribeBtn.addEventListener('click', async function() {
                    subscribeBtn.disabled = true;
                    const initialLabel = subscribeBtn.textContent;
                    subscribeBtn.textContent = 'Subscribing...';
                    if (feedback) feedback.style.display = 'none';
                    if (errorBox) {
                        errorBox.style.display = 'none';
                        errorBox.textContent = '';
                    }
                    debugLog(debugPanel, 'Subscribe button clicked');

                    const hardTimeout = setTimeout(() => {
                        debugLog(debugPanel, 'Timeout waiting for OneSignal flow');
                        subscribeBtn.disabled = false;
                        subscribeBtn.textContent = initialLabel;
                    }, 30000);

                    try {
                        // If preload isn't done yet, don't try to request permission
                        // here (it would require async waits and can prevent the prompt).
                        if (!oneSignalReady || !oneSignal) {
                            throw new Error('OneSignal is still loading. Please try again in a moment.');
                        }

                        const os = oneSignal;
                        debugLog(debugPanel, 'OneSignal ready', {
                            id: os?.User?.PushSubscription?.id ?? null,
                            optedIn: !!os?.User?.PushSubscription?.optedIn
                        });

                        // Trigger the permission prompt immediately from the click.
                        await withTimeout(
                            os.User.PushSubscription.optIn(),
                            12000,
                            'optIn'
                        );
                        debugLog(debugPanel, 'optIn finished', {
                            id: os?.User?.PushSubscription?.id ?? null,
                            optedIn: !!os?.User?.PushSubscription?.optedIn
                        });

                        const waitPersisted = watchAndPersistSubscriptionId(os, debugPanel, feedback, persistPushState);

                        const subId = await waitForSubscriptionId(os, debugPanel, 15000);
                        if (subId) {
                            const result = await persistPushState(os);
                            const fullySubscribed = !!(
                                result?.ok &&
                                result?.subscriptionId &&
                                result?.body?.token_stored === true
                            );
                            if (fullySubscribed && feedback) {
                                feedback.style.display = '';
                            }
                        } else {
                            // Don't call persistPushState here: if opted-in isn't confirmed yet,
                            // it may accidentally persist `opted_in=false` and disable push.
                            const persistedInBackground = await waitPersisted(45000);
                            if (!persistedInBackground && feedback) {
                                feedback.style.display = 'none';
                            }
                        }

                        // Associate with logged-in user
                        if (userId) {
                            try {
                                await os.User.login(userId.toString());
                                debugLog(debugPanel, 'OneSignal.User.login() successful');
                            } catch (loginErr) {
                                debugLog(debugPanel, 'OneSignal login failed (non-blocking)', { error: String(loginErr?.message || loginErr) });
                            }
                        }

                    } catch (e) {
                        debugLog(debugPanel, 'Subscribe flow failed', { error: String(e?.message || e) });
                        console.warn('Subscribe flow failed', e);
                        if (feedback) feedback.style.display = 'none';
                        if (errorBox) {
                            errorBox.style.display = '';
                            errorBox.textContent = `Subscribe failed: ${String(e?.message || e)}`;
                        }
                    } finally {
                        clearTimeout(hardTimeout);
                        subscribeBtn.disabled = false;
                        subscribeBtn.textContent = initialLabel;
                        debugLog(debugPanel, 'Subscribe flow finished');
                    }
                });
            });
        })();
    </script>
@endsection