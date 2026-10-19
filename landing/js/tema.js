// Tema de colores: automatico (sigue al sistema), oscuro o claro. Es una COPIA IDENTICA en frontend-usuarios y landing (tests/unitarias.php lo comprueba).
// La eleccion se guarda en localStorage; si el navegador no lo permite (ventana privada, datos bloqueados) todo anda igual, solo que no se recuerda.
// Se carga en el <head> para aplicar el tema antes de pintar la pagina (sin destello) y despues agrega el boton para cambiarlo.
(function () {
  "use strict";
  var CLAVE = "sigeru_tema";
  var PASOS = ["auto", "oscuro", "claro"];
  var TEXTOS = { auto: "automático (como el sistema)", oscuro: "oscuro", claro: "claro" };

  function leer() {
    try {
      var v = window.localStorage.getItem(CLAVE);
      return PASOS.indexOf(v) !== -1 ? v : "auto";
    } catch (e) { return "auto"; }
  }

  function guardar(v) {
    try {
      if (v === "auto") { window.localStorage.removeItem(CLAVE); } else { window.localStorage.setItem(CLAVE, v); }
    } catch (e) { /* no se puede recordar: se usa solo en esta pagina */ }
  }

  function aplicar(v) {
    var raiz = document.documentElement;
    if (v === "auto") { raiz.removeAttribute("data-tema"); } else { raiz.setAttribute("data-tema", v); }
  }

  var actual = leer();
  aplicar(actual);

  var ICONOS = {
    auto: '<circle cx="12" cy="12" r="8"/><path d="M12 4v16" /><path d="M12 4a8 8 0 0 1 0 16z" fill="currentColor"/>',
    oscuro: '<path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5z"/>',
    claro: '<circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1"/>',
  };

  function dibujar(boton) {
    boton.innerHTML = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + ICONOS[actual] + "</svg>";
    var texto = "Tema de colores: " + TEXTOS[actual] + ". Tocá para cambiarlo.";
    boton.setAttribute("aria-label", texto);
    boton.setAttribute("title", texto);
  }

  function agregarBoton() {
    if (document.getElementById("btn-tema")) { return; }
    var boton = document.createElement("button");
    boton.type = "button";
    boton.id = "btn-tema";
    boton.className = "boton-tema";
    dibujar(boton);
    boton.addEventListener("click", function () {
      actual = PASOS[(PASOS.indexOf(actual) + 1) % PASOS.length];
      guardar(actual);
      aplicar(actual);
      dibujar(boton);
    });
    document.body.appendChild(boton);
  }

  if (document.readyState === "loading") { document.addEventListener("DOMContentLoaded", agregarBoton); } else { agregarBoton(); }
})();
