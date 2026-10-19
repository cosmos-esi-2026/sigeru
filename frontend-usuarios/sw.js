// Service worker de SiGeRU (extras, parte 21). Alcance: /sigeru/frontend-usuarios/ (el de la carpeta donde vive este archivo).
//
// Reglas, todas a proposito:
//  - Guarda en cache SOLO archivos estaticos de esta aplicacion (paginas, estilos, scripts, iconos, fuentes). Los archivos estaticos
//    llevan version: si cambia VERSION, al activarse se borra todo lo anterior.
//  - NUNCA toca un pedido a /sigeru/api-*: ni lo guarda ni lo contesta desde la cache. Lo que viene de las APIs (personas, reclamos,
//    sesion, contrasenas) no se guarda en este telefono por este medio. Tampoco toca pedidos que no sean GET ni de otro origen
//    (los mosaicos del mapa).
//  - Paginas: primero la red; si no hay red, la copia guardada, y si tampoco, "sin-conexion.html".
//  - Estaticos: se contesta con lo guardado y se actualiza en segundo plano.
const VERSION = "sigeru-estaticos-v1";
const PRECARGA = [
  "sin-conexion.html", "login.html", "dashboard.html", "mis-tareas.html", "recoleccion.html", "mis-incidencias.html", "incidencia.html",
  "css/base.css", "css/styles.css",
  "js/api.js", "js/formularios.js", "js/shell.js", "js/tabla.js", "js/incidencias-comun.js", "js/cola-offline.js", "js/pendientes.js", "js/pwa.js",
  "js/recolecciones.js", "js/recoleccion-detalle.js", "js/incidencias.js", "js/incidencia-detalle.js", "js/sin-conexion.js", "js/bootstrap.bundle.min.js",
  "img/logo.svg", "img/logo-claro.svg", "img/icono-192.png",
];

self.addEventListener("install", function (evento) {
  evento.waitUntil(
    caches.open(VERSION).then(function (cache) {
      // Uno por uno: si un archivo falta, no se cae toda la instalacion.
      return Promise.all(PRECARGA.map(function (url) { return cache.add(url).catch(function () { /* se guarda cuando se pida */ }); }));
    }).then(function () { return self.skipWaiting(); })
  );
});

self.addEventListener("activate", function (evento) {
  evento.waitUntil(
    caches.keys().then(function (nombres) {
      return Promise.all(nombres.filter(function (n) { return n !== VERSION; }).map(function (n) { return caches.delete(n); }));
    }).then(function () { return self.clients.claim(); })
  );
});

function esDeLaApi(url) {
  return /\/api-[a-z]+(\/|$)/.test(url.pathname);
}

function esEstatico(url) {
  return /\.(css|js|png|jpg|jpeg|webp|svg|ico|woff2?|webmanifest)$/i.test(url.pathname);
}

self.addEventListener("fetch", function (evento) {
  const pedido = evento.request;
  const url = new URL(pedido.url);
  if (pedido.method !== "GET" || url.origin !== self.location.origin || esDeLaApi(url)) {
    return;   // el navegador lo resuelve como siempre
  }

  if (pedido.mode === "navigate") {
    evento.respondWith(
      fetch(pedido).then(function (respuesta) {
        if (respuesta.ok && respuesta.type === "basic") {
          const copia = respuesta.clone();
          caches.open(VERSION).then(function (cache) { cache.put(pedido, copia); });
        }
        return respuesta;
      }).catch(function () {
        return caches.match(pedido).then(function (guardada) { return guardada || caches.match("sin-conexion.html"); });
      })
    );
    return;
  }

  if (esEstatico(url)) {
    evento.respondWith(
      caches.match(pedido).then(function (guardada) {
        const actualizada = fetch(pedido).then(function (respuesta) {
          if (respuesta.ok && respuesta.type === "basic") {
            const copia = respuesta.clone();
            caches.open(VERSION).then(function (cache) { cache.put(pedido, copia); });
          }
          return respuesta;
        }).catch(function () { return guardada; });
        return guardada || actualizada;
      })
    );
  }
});
