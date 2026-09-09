// ============================================================================
//  SiGeRU 2026 - sidebar.js
//
//  Tres cosas, comunes a las cuatro pantallas con sesion iniciada:
//    1. Marcar en que pagina estamos.
//    2. Abrir y cerrar la barra lateral en pantallas chicas.
//    3. Mostrar el nombre y el rol del usuario logueado en el pie de la barra.
// ============================================================================

(function () {

  // --- 1. Pagina actual -----------------------------------------------------
  // El HTML de cada pagina ya trae su enlace con class="activo" para que se vea
  // bien incluso antes de que corra el JavaScript. Aca se vuelve a calcular
  // desde la URL: si alguien copia una pagina para crear otra y se olvida de
  // mover la clase, igual queda bien. Ademas agrega aria-current, que es lo que
  // anuncia un lector de pantalla.

  const paginaActual = window.location.pathname.split("/").pop() || "dashboard.html";

  document.querySelectorAll(".sidebar-nav .sidebar-link").forEach(function (enlace) {
    const destino = enlace.getAttribute("href");
    const esActual = destino === paginaActual;

    enlace.classList.toggle("activo", esActual);
    if (esActual) {
      enlace.setAttribute("aria-current", "page");
    } else {
      enlace.removeAttribute("aria-current");
    }
  });


  // --- 2. Barra lateral en pantallas chicas ---------------------------------

  const sidebar = document.getElementById("sidebar");
  const botonMenu = document.getElementById("btn-menu");
  let fondo = null;   // capa oscura detras de la barra abierta

  function abrir() {
    sidebar.classList.add("abierto");
    botonMenu.setAttribute("aria-expanded", "true");

    fondo = document.createElement("div");
    fondo.className = "fondo-sidebar";
    fondo.addEventListener("click", cerrar);
    document.body.appendChild(fondo);

    // El foco entra a la barra: si no, con el teclado se seguiria navegando
    // por detras de una capa que tapa la pantalla.
    const primerEnlace = sidebar.querySelector(".sidebar-link");
    if (primerEnlace) {
      primerEnlace.focus();
    }
  }

  function cerrar(devolverFoco) {
    sidebar.classList.remove("abierto");
    botonMenu.setAttribute("aria-expanded", "false");

    if (fondo) {
      fondo.remove();
      fondo = null;
    }
    if (devolverFoco === true) {
      botonMenu.focus();
    }
  }

  if (botonMenu && sidebar) {
    botonMenu.setAttribute("aria-expanded", "false");
    botonMenu.setAttribute("aria-controls", "sidebar");

    botonMenu.addEventListener("click", function () {
      if (sidebar.classList.contains("abierto")) {
        cerrar(true);
      } else {
        abrir();
      }
    });

    document.addEventListener("keydown", function (evento) {
      if (evento.key === "Escape" && sidebar.classList.contains("abierto")) {
        cerrar(true);
      }
    });
  }


  // --- 3. Datos del usuario en el pie de la barra ----------------------------

  const usuario = Sesion.obtener();
  if (usuario) {
    const nombre = document.getElementById("sidebar-nombre");
    const rol = document.getElementById("sidebar-rol");

    if (nombre) {
      nombre.textContent = (usuario.nombre + " " + (usuario.apellido || "")).trim();
    }
    if (rol) {
      rol.textContent = usuario.rol || "Usuario";
    }
  }

})();
