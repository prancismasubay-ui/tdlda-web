/**
 * Kalinga Dialects Learning Dictionary — Service Worker
 * Gives the PUBLIC dictionary basic offline access:
 *  - Static assets (CSS, logo, audio) are cached on first use and served
 *    from cache thereafter (cache-first).
 *  - The dictionary homepage (index.php, including search/filter URLs) is
 *    served network-first, falling back to the last cached version when
 *    offline, so once a search/browse result has been seen it stays
 *    available without a connection.
 *  - Admin pages, login, and everything under /process/ are intentionally
 *    NOT intercepted — they always go straight to the network.
 */

const CACHE_NAME = 'kalinga-dictionary-v1';
const PRECACHE_URLS = [
  'index.php',
  'assets/css/style.css',
  'assets/images/logo.svg',
  'manifest.json',
];

const ADMIN_PATH_FRAGMENTS = [
  '/process/', '/login.php', '/logout.php', '/dashboard.php', '/words.php',
  '/words_import.php', '/categories.php', '/dialects.php', '/parts_of_speech.php',
  '/contributors.php', '/feedback.php', '/users.php', '/results.php', '/api/',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS)).catch(() => {})
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

function isAdminPath(url) {
  return ADMIN_PATH_FRAGMENTS.some((frag) => url.pathname.includes(frag));
}

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // Only handle same-origin GET requests; never touch admin/API/process routes
  if (event.request.method !== 'GET' || url.origin !== self.location.origin || isAdminPath(url)) {
    return;
  }

  const isStaticAsset = /\.(css|svg|png|jpg|jpeg|mp3|wav|ogg|m4a|woff2?)$/.test(url.pathname);

  if (isStaticAsset) {
    // Cache-first for static, rarely-changing assets
    event.respondWith(
      caches.match(event.request).then((cached) => {
        if (cached) return cached;
        return fetch(event.request).then((response) => {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
          return response;
        });
      })
    );
    return;
  }

  // Network-first for dictionary pages (index.php and its search/filter variants)
  event.respondWith(
    fetch(event.request)
      .then((response) => {
        const clone = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
        return response;
      })
      .catch(() =>
        caches.match(event.request).then((cached) => cached || caches.match('index.php'))
      )
  );
});
