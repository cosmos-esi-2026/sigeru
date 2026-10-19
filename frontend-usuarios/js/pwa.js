// Registra el service worker (extras, parte 21). Solo funciona en localhost o con HTTPS: en cualquier otro caso el navegador no lo
// permite y la pagina anda igual, sin instalacion ni cache (por eso todo va en try/catch y nada depende de que se registre).
(function () {
  "use strict";
  try {
    if (!("serviceWorker" in navigator)) { return; }
    window.addEventListener("load", function () {
      navigator.serviceWorker.register("sw.js").catch(function () { /* sin permiso o sin HTTPS: se sigue sin cache */ });
    });
  } catch (e) { /* la pagina no depende del service worker */ }
})();
