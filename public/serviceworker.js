// PWA service worker (your app's offline/caching logic).
// OneSignal has its own dedicated service worker to avoid scope conflicts.

// var staticCacheName = "pwa-v" + new Date().getTime();
// var filesToCache = ['/offline']; 
// var dynamicRoutes = [];

// // Install Event - Cache Required Files & Fetch API Routes
// self.addEventListener('install', function(event) {
//     event.waitUntil(
//         caches.open(staticCacheName).then(cache => {
//             return cache.addAll(filesToCache);
//         }).then(() => {
//             return fetch('/all-routes')
//                 .then(response => response.json())
//                 .then(routes => {
//                     dynamicRoutes = routes.map(route => self.location.origin + '/' + route);
//                 })
//                 .catch(error => console.error("Failed to fetch routes:", error));
//         })
//     );
// });

// // Fetch Event - Network First for API Data, Cache First for Others
// self.addEventListener('fetch', function(event) {
//     const requestUrl = event.request.url;

//     if (!requestUrl.startsWith(self.location.origin)) return; // Ignore third-party requests

//     // if (requestUrl.includes('/login') || requestUrl.includes('/logout') || requestUrl.includes('/csrf-token')) return;

//     const isApiRequest = dynamicRoutes.some(route => requestUrl.includes(route));

//     if (isApiRequest) {
//         event.respondWith(
//             fetch(event.request.clone()).then(fetchResponse => {
//                 return caches.open(staticCacheName).then(cache => {
//                     cache.put(event.request, fetchResponse.clone()); // Update cache
//                     return fetchResponse;
//                 });
//             }).catch(() => {
//                 return caches.match(event.request).then(cacheResponse => {
//                     return cacheResponse || caches.match('/offline');
//                 });
//             })
//         );
//     } else {
//         event.respondWith(
//             caches.match(event.request).then(response => {
//                 return response || fetch(event.request.clone()).then(fetchResponse => {
//                     return caches.open(staticCacheName).then(cache => {
//                         if (event.request.method === "GET") {
//                             cache.put(event.request, fetchResponse.clone()); // Cache new assets
//                         }
//                         return fetchResponse;
//                     });
//                 });
//             }).catch(() => caches.match('/offline')) //  Ensure offline page is returned
//         );
//     }
// });

// // Activate Event - Cleanup Old Caches
// self.addEventListener('activate', function(event) {
//     event.waitUntil(
//         caches.keys().then(cacheNames => {
//             return Promise.all(
//                 cacheNames.filter(name => name !== staticCacheName)
//                           .map(name => caches.delete(name))
//             );
//         })
//     );
// });