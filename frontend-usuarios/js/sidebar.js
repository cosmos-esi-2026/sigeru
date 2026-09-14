(function () {
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

  const sidebar = document.getElementById("sidebar");
  const botonMenu = document.getElementById("btn-menu");
  let fondo = null;

  function abrir() {
    sidebar.classList.add("abierto");
    botonMenu.setAttribute("aria-expanded", "true");

    fondo = document.createElement("div");
    fondo.className = "fondo-sidebar";
    fondo.addEventListener("click", cerrar);
    document.body.appendChild(fondo);

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
