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
      roles: ["Vecino", "Cuadrilla"],
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
      href: "cuadrillas.html",
      texto: "Cuadrillas",
      roles: ["Administrador"],
      trazos: '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/>' +
              '<circle cx="17" cy="9" r="2.4"/><path d="M16.5 14.2c2.6.3 4.5 2.5 4.5 5.3"/>',
    },
    {
      href: "rutas.html",
      texto: "Rutas",
      roles: ["Administrador"],
      incluye: ["ruta-armar.html"],
      trazos: '<circle cx="6" cy="18" r="2.2"/><circle cx="18" cy="6" r="2.2"/><path d="M6 15.8V9a3 3 0 0 1 3-3h6.8"/>',
    },
    {
      href: "asignacion.html",
      texto: "Asignación",
      roles: ["Administrador"],
      trazos: '<path d="M4 6h16"/><path d="M4 12h10"/><path d="M4 18h6"/><path d="m16 15 2 2 4-4"/>',
    },
    {
      href: "ruta-sugerida.html",
      texto: "Ruta sugerida",
      roles: ["Administrador"],
      trazos: '<circle cx="5" cy="19" r="2"/><circle cx="19" cy="5" r="2"/><path d="M5 17c0-5 4-4 6-7s1-5 6-5"/><path d="m14 9 3 .5.5-3"/>',
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
      incluye: ["centro.html"],
      trazos: '<path d="M3 10.5 12 5l9 5.5V20H3z"/><path d="M8 20v-6h8v6"/>',
    },
    {
      href: "recepciones.html",
      texto: "Recepciones",
      roles: ["Administrador", "Operario"],
      incluye: ["recepcion.html"],
      trazos: '<path d="M3 7l9-4 9 4-9 4z"/><path d="M3 12l9 4 9-4"/><path d="M3 17l9 4 9-4"/>',
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
      href: "recepcion.html",
      texto: "Recepción",
      roles: ["Administrador", "Operario"],
      oculta: true,
    },
    {
      href: "centro.html",
      texto: "Centro de acopio",
      roles: ["Administrador", "Operario"],
      oculta: true,
    },
    {
      href: "contenedor.html",
      texto: "Ficha del contenedor",
      roles: ["Administrador", "Cuadrilla"],
      oculta: true,
    },
    {
      href: "ruta-armar.html",
      texto: "Armar ruta",
      roles: ["Administrador"],
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
      '<a class="sidebar-marca" href="dashboard.html"><img src="img/logo-claro.svg" alt="">SiGeRU</a>' +
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

  // La campanita de avisos (extras, parte 13): contador de no leidas, panel accesible por teclado y sondeo cada
  // 60 segundos que se pausa con la pestana oculta. Cada persona ve solo sus avisos (lo garantiza la API).
  const TRAZOS_CAMPANA = '<path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>';
  const PERIODO_AVISOS = 60000;

  function fechaDelAviso(valor) {
    const partes = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/.exec(valor || "");
    return partes ? partes[3] + "/" + partes[2] + "/" + partes[1] + " " + partes[4] + ":" + partes[5] : "";
  }

  function textoSeguro(valor) {
    return String(valor === null || valor === undefined ? "" : valor)
      .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
  }

  function enlaceSeguro(enlace) {
    return /^[a-z0-9-]+\.html(\?[a-z0-9=&_-]*)?$/i.test(enlace || "") ? enlace : null;
  }

  function activarCampanita() {
    const barra = document.querySelector(".topbar");
    const titulo = barra ? barra.querySelector(".topbar-titulo") : null;
    if (!titulo || typeof API === "undefined") {
      return;
    }
    titulo.insertAdjacentHTML("afterend",
      '<div class="campanita" id="campanita">' +
      '<button type="button" class="boton boton-icono campanita-boton" id="campanita-boton" aria-haspopup="dialog" ' +
      'aria-expanded="false" aria-controls="campanita-panel" aria-label="Avisos">' + icono(TRAZOS_CAMPANA) +
      '<span class="campanita-cuenta oculto" id="campanita-cuenta" aria-hidden="true"></span></button>' +
      '<div class="campanita-panel oculto" id="campanita-panel" role="dialog" aria-label="Avisos">' +
      '<div class="campanita-cabecera"><strong>Avisos</strong>' +
      '<button type="button" class="boton boton-secundario boton-chico" id="campanita-todas">Marcar todos como leídos</button></div>' +
      '<ul class="campanita-lista" id="campanita-lista"></ul></div>' +
      '<span class="solo-lectores" id="campanita-anuncio" role="status" aria-live="polite"></span></div>');

    const contenedor = document.getElementById("campanita");
    const boton = document.getElementById("campanita-boton");
    const panel = document.getElementById("campanita-panel");
    const lista = document.getElementById("campanita-lista");
    const cuenta = document.getElementById("campanita-cuenta");
    const anuncio = document.getElementById("campanita-anuncio");
    let anterior = null;

    function pintar(datos, noLeidas) {
      cuenta.textContent = noLeidas > 99 ? "99+" : String(noLeidas);
      cuenta.classList.toggle("oculto", noLeidas === 0);
      boton.setAttribute("aria-label", noLeidas === 0 ? "Avisos: no hay nada sin leer" : "Avisos: " + noLeidas + (noLeidas === 1 ? " sin leer" : " sin leer"));
      if (anterior !== null && noLeidas > anterior) {
        anuncio.textContent = "Tenés " + noLeidas + (noLeidas === 1 ? " aviso nuevo" : " avisos sin leer");
      }
      anterior = noLeidas;
      document.getElementById("campanita-todas").disabled = noLeidas === 0;
      lista.innerHTML = datos.length === 0
        ? '<li class="campanita-vacio">No tenés avisos todavía. Acá vas a ver los cambios de tus reclamos y tareas.</li>'
        : datos.map(function (n) {
            const destino = enlaceSeguro(n.enlace);
            const cabeza = destino
              ? '<a class="campanita-enlace" href="' + destino + '" data-id="' + parseInt(n.id_notificacion, 10) + '">' + textoSeguro(n.titulo) + "</a>"
              : "<strong>" + textoSeguro(n.titulo) + "</strong>";
            return '<li class="campanita-item' + (n.leida ? "" : " sin-leer") + '">' + cabeza +
              "<p>" + textoSeguro(n.mensaje) + "</p>" +
              '<div class="campanita-pie"><time>' + textoSeguro(fechaDelAviso(n.fecha_hora)) + "</time>" +
              (n.leida ? "" : '<button type="button" class="boton boton-chico js-leida" data-id="' + parseInt(n.id_notificacion, 10) +
                '">Marcar como leído<span class="solo-lectores">: ' + textoSeguro(n.titulo) + "</span></button>") + "</div></li>";
          }).join("");
    }

    async function actualizar() {
      try {
        const respuesta = await fetch(API.usuarios + "/notificaciones?limite=20", { credentials: "same-origin" });
        if (!respuesta.ok) {
          return;
        }
        const cuerpo = await respuesta.json();
        pintar(cuerpo.datos || [], cuerpo.no_leidas || 0);
      } catch (error) {
        return;
      }
    }

    async function marcar(ruta) {
      try {
        await fetch(API.usuarios + ruta, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/json" }, body: "{}", keepalive: true });
      } catch (error) {
        return;
      }
      actualizar();
    }

    function abrir() {
      panel.classList.remove("oculto");
      boton.setAttribute("aria-expanded", "true");
      actualizar();
      const primero = panel.querySelector("a, button:not([disabled])");
      if (primero) {
        primero.focus();
      }
    }

    function cerrar(devolverFoco) {
      panel.classList.add("oculto");
      boton.setAttribute("aria-expanded", "false");
      if (devolverFoco === true) {
        boton.focus();
      }
    }

    boton.addEventListener("click", function () {
      if (panel.classList.contains("oculto")) {
        abrir();
      } else {
        cerrar(true);
      }
    });
    document.getElementById("campanita-todas").addEventListener("click", function () { marcar("/notificaciones/leer-todas"); });
    lista.addEventListener("click", function (evento) {
      const marcarUno = evento.target.closest(".js-leida");
      if (marcarUno) {
        marcar("/notificaciones/" + parseInt(marcarUno.dataset.id, 10) + "/leida");
        return;
      }
      const enlace = evento.target.closest(".campanita-enlace");
      if (enlace) {
        marcar("/notificaciones/" + parseInt(enlace.dataset.id, 10) + "/leida");
      }
    });
    contenedor.addEventListener("keydown", function (evento) {
      if (evento.key === "Escape" && !panel.classList.contains("oculto")) {
        cerrar(true);
      }
    });
    contenedor.addEventListener("focusout", function (evento) {
      if (!panel.classList.contains("oculto") && evento.relatedTarget && !contenedor.contains(evento.relatedTarget)) {
        cerrar(false);
      }
    });
    document.addEventListener("click", function (evento) {
      if (!panel.classList.contains("oculto") && !contenedor.contains(evento.target)) {
        cerrar(false);
      }
    });
    document.addEventListener("visibilitychange", function () {
      if (!document.hidden) {
        actualizar();
      }
    });
    window.setInterval(function () {
      if (!document.hidden) {
        actualizar();
      }
    }, PERIODO_AVISOS);
    actualizar();
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
  activarCampanita();
  verificarSesion();
})();
