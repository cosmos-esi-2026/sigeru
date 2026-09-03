// ============================================================================
//  SiGeRU 2026 - sidebar.js
//
//  Dos cosas, comunes a las cuatro pantallas con sesion iniciada:
//    1. Abrir y cerrar la barra lateral en pantallas chicas.
//    2. Mostrar el nombre y el rol del usuario logueado en el pie de la barra.
//
//  Cual es el enlace activo NO se decide aca: cada pagina ya trae su enlace
//  marcado con class="activo" en el HTML, asi se ve bien incluso antes de que
//  corra el JavaScript.
// ============================================================================

(function () {

  // --- 1. Barra lateral en pantallas chicas ---------------------------------

  const sidebar = document.getElementById("sidebar");
  const botonMenu = document.getElementById("btn-menu");
  let fondo = null;   // capa oscura detras de la barra abierta

  function abrir() {
    sidebar.classList.add("abierto");

    fondo = document.createElement("div");
    fondo.className = "fondo-sidebar";
    fondo.addEventListener("click", cerrar);
    document.body.appendChild(fondo);
  }

  function cerrar() {
    sidebar.classList.remove("abierto");
    if (fondo) {
      fondo.remove();
      fondo = null;
    }
  }

  if (botonMenu && sidebar) {
    botonMenu.addEventListener("click", function () {
      if (sidebar.classList.contains("abierto")) {
        cerrar();
      } else {
        abrir();
      }
    });

    document.addEventListener("keydown", function (evento) {
      if (evento.key === "Escape") {
        cerrar();
      }
    });
  }

  // --- 2. Datos del usuario en el pie de la barra ----------------------------

  const usuario = Sesion.obtener();
  if (usuario) {
    const nombre = document.getElementById("sidebar-nombre");
    const rol = document.getElementById("sidebar-rol");

    if (nombre) {
      nombre.textContent = usuario.nombre + " " + (usuario.apellido || "");
    }
    if (rol) {
      rol.textContent = usuario.rol || "Usuario";
    }
  }

})();
