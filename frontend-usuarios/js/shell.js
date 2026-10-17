(function () {
  const SVG_ABRE = '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
                   'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';

  const TODOS = ["Administrador", "Operario", "Cuadrilla", "Vecino"];
  const TRAZOS_RECOLECCION = '<path d="M4 6h10M4 12h10M4 18h7"/><path d="m15 17 2 2 4-4"/>';
  const TRAZOS_INCIDENCIA = '<path d="M12 3l9 16H3z"/><path d="M12 9v5M12 17h.01"/>';

  const SECCIONES = [
    {
      href: "dashboard.html",
      texto: "Inicio",
      roles: TODOS,
      trazos: '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-4v-6H9v6H4a1 1 0 0 1-1-1z"/>',
    },
    {
      href: "mapa.html",
      texto: "Mapa",
      roles: TODOS,
      trazos: '<path d="M9 4 3 6.5v13.5l6-2.5 6 2.5 6-2.5V4l-6 2.5z"/><path d="M9 4v13.5M15 6.5V20"/>',
    },
    {
      href: "usuarios.html",
      texto: "Usuarios",
      roles: ["Administrador"],
      trazos: '<circle cx="9" cy="8" r="3.2"/><path d="M2.8 20a6.2 6.2 0 0 1 12.4 0"/>' +
              '<path d="M16.5 5.3a3.2 3.2 0 0 1 0 5.4"/><path d="M17.8 14.4A6.2 6.2 0 0 1 21.2 20"/>',
    },
    {
      href: "contenedores.html",
      texto: "Contenedores",
      roles: ["Administrador", "Cuadrilla"],
      incluye: ["contenedor.html"],
      trazos: '<path d="M4 7h16"/><path d="M9.5 7V4.5h5V7"/>' +
              '<path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/><path d="M10 11v6M14 11v6"/>',
    },
    {
      href: "incidencias.html",
      texto: "Incidencias",
      roles: ["Administrador"],
      incluye: ["incidencia.html"],
      trazos: TRAZOS_INCIDENCIA,
    },
    {
      href: "reportes.html",
      texto: "Reportes",
      roles: ["Administrador"],
      incluye: [],
      trazos: '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    },
    {
      href: "mis-incidencias.html",
      texto: "Mis incidencias",
      roles: ["Cuadrilla"],
      incluye: ["incidencia.html"],
      trazos: TRAZOS_INCIDENCIA,
    },
    {
      href: "informar-problema.html",
      texto: "Informar un problema",
      roles: ["Vecino"],
      trazos: '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
    },
    {
      href: "mis-reclamos.html",
      texto: "Mis reclamos",
      roles: ["Vecino"],
      incluye: ["incidencia.html"],
      trazos: '<path d="M5 4h14v16l-3-2-2 2-2-2-2 2-2-2-3 2z"/><path d="M9 9h6M9 13h4"/>',
    },
    {
      href: "camiones.html",
      texto: "Camiones",
      roles: ["Administrador"],
      trazos: '<path d="M2 6h11v10H2z"/><path d="M13 9.5h4l4 3.5V16h-8z"/>' +
              '<circle cx="7" cy="18.3" r="1.9"/><circle cx="17" cy="18.3" r="1.9"/>',
    },
    {
      href: "rutas.html",
      texto: "Rutas",
      roles: ["Administrador"],
      trazos: '<circle cx="6" cy="18" r="2.2"/><circle cx="18" cy="6" r="2.2"/><path d="M6 15.8V9a3 3 0 0 1 3-3h6.8"/>',
    },
    {
      href: "recolecciones.html",
      texto: "Recolecciones",
      roles: ["Administrador"],
      incluye: ["recoleccion.html"],
      trazos: TRAZOS_RECOLECCION,
    },
    {
      href: "mis-tareas.html",
      texto: "Mis tareas",
      roles: ["Cuadrilla"],
      incluye: ["recoleccion.html"],
      trazos: TRAZOS_RECOLECCION,
    },
    {
      href: "centros.html",
      texto: "Centros de acopio",
      roles: ["Administrador", "Operario"],
      trazos: '<path d="M3 10.5 12 5l9 5.5V20H3z"/><path d="M8 20v-6h8v6"/>',
    },
    {
      href: "maquinaria.html",
      texto: "Maquinaria",
      roles: ["Administrador", "Operario"],
      trazos: '<circle cx="12" cy="12" r="3.2"/><path d="M12 3v2.4M12 18.6V21M3 12h2.4M18.6 12H21"/>' +
              '<path d="m5.6 5.6 1.7 1.7M16.7 16.7l1.7 1.7M18.4 5.6l-1.7 1.7M7.3 16.7l-1.7 1.7"/>',
    },
    {
      href: "auditoria.html",
      texto: "Auditoría",
      roles: ["Administrador"],
      trazos: '<path d="M12 3l8 3v6c0 4.5-3.2 7.8-8 9-4.8-1.2-8-4.5-8-9V6z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
    },
    {
      href: "solicitudes.html",
      texto: "Solicitudes",
      roles: ["Administrador"],
      trazos: '<path d="M3 12h5l2 3h4l2-3h5"/><path d="M4 6h16v12H4z"/>',
    },
    {
      href: "incidencia.html",
      texto: "Incidencia",
      roles: ["Administrador", "Cuadrilla", "Vecino"],
      oculta: true,
    },
    {
      href: "recoleccion.html",
      texto: "Recolección",
      roles: ["Administrador", "Cuadrilla"],
      oculta: true,
    },
    {
      href: "contenedor.html",
      texto: "Ficha del contenedor",
      roles: ["Administrador", "Cuadrilla"],
      oculta: true,
    },
  ];

  const TRAZOS_SALIR = '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/>' +
                       '<path d="M10 8l-4 4 4 4"/><path d="M6 12h11"/>';
  const TRAZOS_MENU = '<path d="M3 6h18M3 12h18M3 18h18"/>';

  const paginaActual = window.location.pathname.split("/").pop() || "dashboard.html";
  const sidebar = document.getElementById("sidebar");

  function icono(trazos) {
    return SVG_ABRE + trazos + "</svg>";
  }

  function permitida(pagina, rol) {
    const seccion = SECCIONES.find(function (s) { return s.href === pagina; });
    return !seccion || seccion.roles.indexOf(rol) !== -1;
  }

  function armarSidebar() {
    sidebar.innerHTML =
      '<a class="sidebar-marca" href="dashboard.html"><img src="img/logo.svg" alt="">SiGeRU</a>' +
      '<div class="banda" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>' +
      '<nav class="sidebar-nav" aria-label="Secciones del sistema"></nav>' +
      '<div class="sidebar-pie">' +
      '<div class="sidebar-usuario"><div class="nombre" id="sidebar-nombre"></div>' +
      '<div class="rol" id="sidebar-rol"></div></div>' +
      '<a class="sidebar-link" href="#" id="btn-salir">' + icono(TRAZOS_SALIR) + "Cerrar sesión</a>" +
      "</div>";
  }

  function pintarMenu(rol) {
    const nav = sidebar.querySelector(".sidebar-nav");

    nav.innerHTML = SECCIONES.filter(function (seccion) {
      return !seccion.oculta && seccion.roles.indexOf(rol) !== -1;
    }).map(function (seccion) {
      const esActual = seccion.href === paginaActual;
      const esPadre = (seccion.incluye || []).indexOf(paginaActual) !== -1;
      return '<a class="sidebar-link' + (esActual || esPadre ? " activo" : "") + '" href="' + seccion.href + '"' +
             (esActual ? ' aria-current="page"' : esPadre ? ' aria-current="true"' : "") + ">" +
             icono(seccion.trazos) + seccion.texto + "</a>";
    }).join("");
  }

  function mostrarUsuario(usuario) {
    document.getElementById("sidebar-nombre").textContent =
      (usuario.nombre + " " + (usuario.apellido || "")).trim();
    document.getElementById("sidebar-rol").textContent = usuario.rol || "Usuario";
  }

  function aplicarRol(usuario) {
    const rol = usuario.rol || "";

    document.body.dataset.rol = rol;
    pintarMenu(rol);
    mostrarUsuario(usuario);

    document.querySelectorAll("[data-pagina]").forEach(function (elemento) {
      elemento.classList.toggle("oculto", !permitida(elemento.dataset.pagina, rol));
    });

    if (!permitida(paginaActual, rol)) {
      window.location.replace("dashboard.html");
    }
  }

  async function verificarSesion() {
    let respuesta;
    try {
      respuesta = await fetch(API.usuarios + "/sesion", { credentials: "same-origin" });
    } catch (error) {
      return;
    }

    if (respuesta.status === 401 || respuesta.status === 403) {
      Sesion.cerrar();
      window.location.replace("login.html");
      return;
    }
    if (respuesta.ok) {
      const resultado = await respuesta.json();
      Sesion.guardar(resultado.datos);
      aplicarRol(resultado.datos);
    }
  }

  function agregarBotonMenu() {
    const barra = document.querySelector(".topbar");
    if (!barra) {
      return null;
    }
    barra.insertAdjacentHTML(
      "afterbegin",
      '<button class="btn-menu" id="btn-menu" aria-label="Abrir menú">' + icono(TRAZOS_MENU) + "</button>"
    );
    return barra.querySelector("#btn-menu");
  }

  function activarMenuMovil(botonMenu) {
    if (!botonMenu) {
      return;
    }
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

  if (!sidebar) {
    return;
  }

  const usuarioLocal = Sesion.obtener();
  if (!usuarioLocal) {
    window.location.replace("login.html");
    return;
  }

  armarSidebar();
  aplicarRol(usuarioLocal);
  activarMenuMovil(agregarBotonMenu());
  verificarSesion();
})();
