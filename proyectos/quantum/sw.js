const VERSION = "quantum-pwa-v1-20260818";
const CACHE_ESTATICO = `${VERSION}-estatico`;
const CACHE_PAGINAS = `${VERSION}-paginas`;
const BASE = new URL("./", self.registration.scope);

const RECURSOS_BASE = [
  "./offline.html",
  "./manifest.webmanifest",
  "./css/estilos.css",
  "./css/quantum.css",
  "./css/quantum-dark.css",
  "./js/app.js",
  "./assets/quantum-logo-horizontal.png",
  "./assets/quantum-logo-vertical.png",
  "./assets/quantum-logo-q.png",
  "./assets/pwa/icon-192.png",
  "./assets/pwa/icon-512.png",
  "./assets/pwa/icon-maskable-512.png",
  "./assets/pwa/apple-touch-icon.png"
].map(ruta => new URL(ruta, BASE).href);

self.addEventListener("install", event => {
  event.waitUntil(caches.open(CACHE_ESTATICO).then(cache => cache.addAll(RECURSOS_BASE)));
  self.skipWaiting();
});

self.addEventListener("activate", event => {
  event.waitUntil(
    caches.keys()
      .then(claves => Promise.all(claves.filter(clave => clave.startsWith("quantum-pwa-") && !clave.startsWith(VERSION)).map(clave => caches.delete(clave))))
      .then(() => self.clients.claim())
  );
});

const esRutaPrivada = url => /\/(admin|api)\//i.test(url.pathname) || /\/(conexion|logout)\.php$/i.test(url.pathname);
const esRecursoEstatico = url => /\.(?:css|js|png|jpe?g|gif|webp|svg|woff2?|ico)$/i.test(url.pathname);

async function redPrimero(request) {
  try {
    const respuesta = await fetch(request);
    if (respuesta.ok) {
      const cache = await caches.open(CACHE_PAGINAS);
      cache.put(request, respuesta.clone());
    }
    return respuesta;
  } catch (error) {
    return (await caches.match(request)) || caches.match(new URL("./offline.html", BASE).href);
  }
}

async function cacheConActualizacion(request) {
  const guardado = await caches.match(request);
  const actualizacion = fetch(request).then(async respuesta => {
    if (respuesta.ok) {
      const cache = await caches.open(CACHE_ESTATICO);
      await cache.put(request, respuesta.clone());
    }
    return respuesta;
  }).catch(() => null);
  if (guardado) {
    actualizacion.catch(() => null);
    return guardado;
  }
  return (await actualizacion) || Response.error();
}

self.addEventListener("fetch", event => {
  const request = event.request;
  const url = new URL(request.url);
  if (request.method !== "GET" || url.origin !== self.location.origin || esRutaPrivada(url)) return;

  if (request.mode === "navigate") {
    event.respondWith(redPrimero(request));
    return;
  }

  if (esRecursoEstatico(url)) event.respondWith(cacheConActualizacion(request));
});

self.addEventListener("message", event => {
  if (event.data?.tipo === "ACTUALIZAR_APP") self.skipWaiting();
});
