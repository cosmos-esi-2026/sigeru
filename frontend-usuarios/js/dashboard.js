Sesion.exigir();

const cuerpoLlenos = document.getElementById("cuerpo-llenos");
const COLUMNAS_LLENOS = 4;
const MAXIMO_LLENOS = 10;

function pintarMetrica(id, valor, detalle) {
  const elemento = document.getElementById(id);
  elemento.textContent = valor;
  if (detalle) {
    elemento.title = detalle;
  } else {
    elemento.removeAttribute("title");
  }
}

function cargandoMetrica(id) {
  document.getElementById(id).innerHTML = '<span class="esqueleto esqueleto-metrica"></span>';
}

async function cargarContenedores() {
  try {
    // Con cientos de contenedores no se bajan todos para contar: el servidor cuenta (meta.total) y la tabla trae solo los primeros llenos.
    const [todos, llenos] = await Promise.all([
      apiGet(API.gestion + "/contenedores?por_pagina=1"),
      apiGet(API.gestion + "/contenedores?estado=lleno&por_pagina=" + MAXIMO_LLENOS),
    ]);
    pintarMetrica("metrica-contenedores", todos.meta.total);
    pintarMetrica("metrica-llenos", llenos.meta.total);
    dibujarLlenos(llenos.datos || [], llenos.meta.total);
  } catch (error) {
    pintarMetrica("metrica-contenedores", "—", mensajeDeFallo(error));
    pintarMetrica("metrica-llenos", "—", mensajeDeFallo(error));
    cuerpoLlenos.innerHTML = filaError(COLUMNAS_LLENOS, mensajeDeFallo(error));
  }
}

async function cargarCamiones() {
  try {
    const respuesta = await apiGet(API.recoleccion + "/camiones");
    const camiones = respuesta.datos || [];

    const operativos = camiones.filter(function (c) {
      return c.estado === "operativo";
    });
    pintarMetrica("metrica-camiones", operativos.length);
  } catch (error) {
    pintarMetrica("metrica-camiones", "—", mensajeDeFallo(error));
  }
}

async function cargarUsuarios() {
  try {
    const respuesta = await apiGet(API.usuarios + "/usuarios?por_pagina=1");
    pintarMetrica("metrica-usuarios", respuesta.meta.total);
  } catch (error) {
    pintarMetrica("metrica-usuarios", "—", mensajeDeFallo(error));
  }
}

function dibujarLlenos(llenos, total) {
  if (llenos.length === 0) {
    cuerpoLlenos.innerHTML = filaVacia(
      COLUMNAS_LLENOS,
      "Ningún contenedor lleno",
      "Cuando un contenedor pase a estado «lleno» va a aparecer acá."
    );
    return;
  }

  cuerpoLlenos.innerHTML = llenos.map(function (c) {
    return '<tr role="row">' +
      celda("Código", c.codigo) +
      celda("Dirección", c.direccion) +
      celda("Capacidad", c.capacidad ? c.capacidad + " L" : null) +
      celdaEstado("Estado", c.estado, "rojo") +
      "</tr>";
  }).join("") + (total > llenos.length
    ? '<tr class="fila-estado"><td colspan="' + COLUMNAS_LLENOS + '">Se muestran los primeros ' + llenos.length + " de " + total + ". El resto está en «Ver todos los contenedores».</td></tr>"
    : "");
}

const NIVELES_URGENCIA = {
  critica:  { texto: "Crítica",        color: "rojo" },
  urgente:  { texto: "Urgente",        color: "naranja" },
  atencion: { texto: "Atención",       color: "azul" },
  normal:   { texto: "En seguimiento", color: "gris" },
};

function plural(cantidad, singular, pluralTexto) {
  return cantidad + " " + (cantidad === 1 ? singular : pluralTexto);
}

function haceCuanto(horas, dias) {
  return dias >= 1 ? plural(dias, "día", "días") : plural(horas, "hora", "horas");
}

function avisosDelPanel(datos, recoleccionesEnCurso) {
  const i = datos.indicadores;
  const avisos = [];
  if (i.criticas > 0) {
    avisos.push(["es-critico", plural(i.criticas, "incidencia crítica", "incidencias críticas") +
      " (prioridad alta y más de " + plural(datos.umbral_dias, "día", "días") + " abierta).",
      "incidencias.html", "Ver incidencias"]);
  }
  if (i.sin_asignar > 0) {
    avisos.push(["", plural(i.sin_asignar, "incidencia sin cuadrilla asignada", "incidencias sin cuadrilla asignada") + ".",
      "incidencias.html?estado=abierta", "Asignar"]);
  }
  if (i.vencidas > 0) {
    avisos.push(["", plural(i.vencidas, "incidencia abierta", "incidencias abiertas") + " hace más de " +
      plural(datos.umbral_dias, "día", "días") + ".", "incidencias.html", "Ver incidencias"]);
  }
  if (i.contenedores_llenos > 0) {
    avisos.push(["", plural(i.contenedores_llenos, "contenedor lleno", "contenedores llenos") + " esperando recolección.",
      "contenedores.html?estado=lleno", "Ver contenedores"]);
  }
  if (i.contenedores_mantenimiento > 0) {
    avisos.push(["", plural(i.contenedores_mantenimiento, "contenedor en mantenimiento", "contenedores en mantenimiento") + ".",
      "contenedores.html?estado=mantenimiento", "Ver contenedores"]);
  }
  if (recoleccionesEnCurso > 0) {
    avisos.push(["", plural(recoleccionesEnCurso, "recolección en curso", "recolecciones en curso") + " ahora.",
      "recolecciones.html", "Ver recolecciones"]);
  }
  if (avisos.length === 0) {
    avisos.push(["es-bien", "Todo en orden: no hay incidencias abiertas ni contenedores llenos."]);
  }
  return avisos;
}

const AVISOS_VISIBLES = 3;

// Los avisos ya vienen ordenados por gravedad: se muestran los primeros y el resto se despliega.
function dibujarAvisos(lista, avisos) {
  const boton = document.getElementById("mas-avisos");
  lista.innerHTML = avisos.map(function (a, posicion) {
    return '<li class="' + a[0] + (posicion >= AVISOS_VISIBLES ? " oculto aviso-extra" : "") + '">' +
      "<span>" + escaparHtml(a[1]) + "</span>" +
      (a[2] ? '<a class="aviso-enlace" href="' + escaparHtml(a[2]) + '">' + escaparHtml(a[3]) + "</a>" : "") +
      "</li>";
  }).join("");

  const extra = avisos.length - AVISOS_VISIBLES;
  boton.classList.toggle("oculto", extra <= 0);
  boton.setAttribute("aria-expanded", "false");
  boton.textContent = "Mostrar " + plural(extra, "aviso más", "avisos más");
  boton.dataset.extra = String(Math.max(extra, 0));
}

document.getElementById("mas-avisos").addEventListener("click", function (evento) {
  const boton = evento.currentTarget;
  const abrir = boton.getAttribute("aria-expanded") !== "true";
  document.querySelectorAll("#avisos-panel .aviso-extra").forEach(function (li) {
    li.classList.toggle("oculto", !abrir);
  });
  boton.setAttribute("aria-expanded", abrir ? "true" : "false");
  boton.textContent = abrir ? "Mostrar menos" : "Mostrar " + plural(Number(boton.dataset.extra), "aviso más", "avisos más");
});

async function cargarUrgencias() {
  const cuerpo = document.getElementById("cuerpo-urgencias");
  const lista = document.getElementById("avisos-panel");
  cuerpo.innerHTML = filaEsqueleto(7, 2);
  try {
    const resultados = await Promise.allSettled([
      apiGet(API.gestion + "/estadisticas/urgencias"),
      apiGet(API.recoleccion + "/recolecciones?estado=en_curso"),
    ]);
    if (resultados[0].status === "rejected") {
      throw resultados[0].reason;
    }
    const datos = resultados[0].value.datos;
    const enCurso = resultados[1].status === "fulfilled" ? (resultados[1].value.datos || []).length : 0;

    pintarMetrica("metrica-incidencias", datos.indicadores.abiertas);
    pintarMetrica("metrica-criticas", datos.indicadores.criticas);

    dibujarAvisos(lista, avisosDelPanel(datos, enCurso));

    if (datos.incidencias.length === 0) {
      cuerpo.innerHTML = filaVacia(7, "No hay incidencias abiertas", "Cuando un vecino informe un problema va a aparecer acá, ordenado por urgencia.");
    } else {
      cuerpo.innerHTML = datos.incidencias.map(function (inc) {
        const nivel = NIVELES_URGENCIA[inc.nivel] || NIVELES_URGENCIA.normal;
        return '<tr role="row">' +
          '<td role="cell" data-etiqueta="Urgencia"><span class="estado ' + nivel.color + ' nivel">' + escaparHtml(nivel.texto) + "</span></td>" +
          celdaEnlace("Número", inc.numero, "incidencia.html?id=" + inc.id_incidencia) +
          celda("Contenedor", inc.contenedor) + celda("Zona", inc.zona) +
          celdaMarca("Prioridad", "prioridad", inc.prioridad, PESO_PRIORIDAD[inc.prioridad] || 0) +
          celda("Abierta hace", haceCuanto(inc.horas_abierta, inc.dias_abierta), inc.horas_abierta) +
          celda("Cuadrilla", inc.cuadrilla || "Sin asignar") + "</tr>";
      }).join("");
    }
    document.getElementById("nota-urgencias").textContent = datos.indicadores.abiertas > datos.incidencias.length
      ? "Se muestran las " + datos.incidencias.length + " más urgentes de " + datos.indicadores.abiertas + " abiertas."
      : "";
  } catch (error) {
    pintarMetrica("metrica-incidencias", "—", mensajeDeFallo(error));
    pintarMetrica("metrica-criticas", "—", mensajeDeFallo(error));
    lista.innerHTML = "";
    cuerpo.innerHTML = filaError(7, mensajeDeFallo(error));
  }
}

const EXPLICACION_ESTADO = {
  abierta:    "Recibido. La administración lo va a revisar y asignar a una cuadrilla.",
  en_curso:   "Una cuadrilla ya lo está atendiendo.",
  resuelta:   "Resuelto. Gracias por avisar.",
  descartada: "La administración lo descartó. Abrilo para ver el motivo.",
};

function dibujarReclamos(incidencias) {
  const zona = document.getElementById("zona-reclamos");
  if (incidencias.length === 0) {
    zona.innerHTML = '<div class="estado-bloque"><h3>Todavía no informaste ningún problema</h3>' +
      "<p>Si ves un contenedor desbordado, roto o mal ubicado, avisá y seguí acá cómo se resuelve.</p>" +
      '<a class="boton boton-primario" href="informar-problema.html">Informar un problema</a></div>';
    return;
  }

  const ordenadas = incidencias.slice().sort(function (a, b) {
    return String(b.fecha_reporte).localeCompare(String(a.fecha_reporte));
  });
  const abiertas = ordenadas.filter(function (i) { return i.estado === "abierta" || i.estado === "en_curso"; }).length;

  zona.innerHTML = '<p class="resumen-reclamos">' +
    (abiertas === 0
      ? "No tenés reclamos abiertos."
      : "Tenés <strong>" + plural(abiertas, "reclamo abierto", "reclamos abiertos") + "</strong>.") +
    "</p>" +
    '<ul class="lista-reclamos">' + ordenadas.slice(0, 3).map(function (i) {
      return '<li class="reclamo" data-estado="' + escaparHtml(i.estado) + '">' +
        '<div class="reclamo-cabecera">' +
        '<a class="reclamo-numero" href="incidencia.html?id=' + $entero(i.id_incidencia) + '">' + escaparHtml(i.numero) + "</a>" +
        marcaDeEstado("estado", i.estado) +
        '<time class="reclamo-fecha">' + escaparHtml(fechaLegible(i.fecha_reporte)) + "</time></div>" +
        '<p class="reclamo-titulo">' + escaparHtml(textoTipoProblema(i.tipo_problema) +
          (i.direccion ? " · " + i.direccion : "")) + "</p>" +
        '<p class="reclamo-explicacion">' + escaparHtml(EXPLICACION_ESTADO[i.estado] || "") + "</p></li>";
    }).join("") + "</ul>";
}

const DIAS_SEMANA = ["domingo", "lunes", "martes", "miércoles", "jueves", "viernes", "sábado"];

// "2026-11-16 06:30:00" -> "lunes 16/11 a las 06:30"
function diaYHora(valor) {
  const partes = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/.exec(valor || "");
  if (!partes) {
    return "";
  }
  const dia = DIAS_SEMANA[new Date(parseInt(partes[1], 10), parseInt(partes[2], 10) - 1, parseInt(partes[3], 10)).getDay()];
  return dia + " " + partes[3] + "/" + partes[2] + " a las " + partes[4] + ":" + partes[5];
}

function textoDelPasaje(d) {
  if (d.estado === "sin_zona") {
    return "Elegí tu zona para saber cuándo pasa la recolección.";
  }
  const aviso = d.cancelada ? " Se canceló la recolección del " + diaYHora(d.cancelada) + "." : "";
  if (d.estado === "en_curso") {
    return "Hoy el camión está pasando por " + d.zona + "." + (d.proximo ? " La siguiente: " + diaYHora(d.proximo) + "." : "") + aviso;
  }
  if (d.estado === "sin_planificadas") {
    return "Todavía no hay recolecciones planificadas para tu zona (" + d.zona + ")." + aviso;
  }
  return "Próximo pasaje en " + d.zona + ": " + diaYHora(d.proximo) + "." + aviso;
}

async function cargarPasaje() {
  const zona = document.getElementById("zona-pasaje");
  const selector = document.getElementById("select-zona");
  try {
    if (selector.options.length === 1) {
      const zonas = (await apiGet(API.gestion + "/zonas")).datos || [];
      selector.innerHTML = '<option value="">Elegí tu zona</option>' + zonas.map(function (z) {
        return '<option value="' + $entero(z.id_zona) + '">' + escaparHtml(z.nombre) + "</option>";
      }).join("");
    }
    const d = (await apiGet(API.recoleccion + "/proximo-pasaje")).datos;
    selector.value = d.id_zona ? String(d.id_zona) : "";
    document.getElementById("boton-zona").textContent = d.id_zona ? "Cambiar mi zona" : "Guardar mi zona";
    zona.innerHTML = '<p class="pasaje-texto' + (d.estado === "programado" || d.estado === "en_curso" ? " con-pasaje" : "") + '">' + escaparHtml(textoDelPasaje(d)) + "</p>";
  } catch (error) {
    zona.innerHTML = bloqueEstado("No se pudo traer el pasaje", mensajeDeFallo(error), true);
  }
}

document.getElementById("form-zona").addEventListener("submit", async function (evento) {
  evento.preventDefault();
  const selector = document.getElementById("select-zona");
  if (selector.value === "") {
    avisoPaginaError("Elegí una zona de la lista.");
    selector.focus();
    return;
  }
  try {
    const { ok, resultado } = await apiPut(API.usuarios + "/mi-zona", { id_zona: parseInt(selector.value, 10) });
    if (ok) {
      avisoPagina("Zona guardada.");
      cargarPasaje();
    } else {
      avisoPaginaError(resultado.mensaje || "No se pudo guardar la zona.");
    }
  } catch (error) {
    avisoPaginaError("No se pudo conectar con el servidor. Revisá que esté corriendo.");
  }
});

function plural2(n, uno, varios) {
  return n + " " + (n === 1 ? uno : varios);
}

async function cargarProgreso() {
  const zona = document.getElementById("zona-progreso");
  zona.innerHTML = '<span class="esqueleto"></span>';
  try {
    const p = (await apiGet(API.gestion + "/participacion/progreso")).datos;
    zona.innerHTML =
      '<div class="progreso-cabecera"><span class="progreso-puntos">' + parseInt(p.puntos, 10) + '</span><span>puntos · ' +
      escaparHtml(plural2(p.resueltos, "reclamo resuelto", "reclamos resueltos")) + " · " +
      escaparHtml(plural2(p.calificados, "calificación", "calificaciones")) + "</span></div>" +
      '<p class="texto-tenue">Sumás ' + parseInt(p.puntos_por_resuelto, 10) + " puntos cuando un reclamo tuyo se resuelve y " + parseInt(p.puntos_por_calificar, 10) +
      " por calificar la atención. Informar un problema no suma (para que nadie abuse); un reclamo descartado tampoco.</p>" +
      (p.por_calificar.length === 0 ? "" : '<h3 class="subtitulo-panel">Reclamos para calificar</h3><ul class="lista-simple">' + p.por_calificar.map(function (r) {
        return '<li><a href="incidencia.html?id=' + $entero(r.id_incidencia) + '">' + escaparHtml(r.numero + " · " + r.direccion) + "</a><span>hasta el " +
               escaparHtml(fechaLegible(r.hasta).slice(0, 10)) + "</span></li>";
      }).join("") + "</ul>") +
      '<h3 class="subtitulo-panel">Insignias</h3><ul class="insignias">' + p.insignias.map(function (i) {
        return '<li class="insignia" data-lograda="' + (i.lograda ? "true" : "false") + '"><strong>' + escaparHtml(i.nombre) + "</strong><small>" +
               escaparHtml(i.descripcion) + '</small><br><span class="marca">' + (i.lograda ? "✓ Lograda" : "Te faltan " + (i.meta - i.progreso) + " (" + i.progreso + " de " + i.meta + ")") + "</span></li>";
      }).join("") + "</ul>" +
      (p.movimientos.length === 0 ? "" : '<h3 class="subtitulo-panel">Últimos puntos</h3><ul class="lista-simple">' + p.movimientos.map(function (m) {
        return "<li><span>" + escaparHtml(m.motivo + " (" + m.numero + ")") + "</span><span>+" + parseInt(m.puntos, 10) + " · " + escaparHtml(fechaLegible(m.fecha_hora).slice(0, 10)) + "</span></li>";
      }).join("") + "</ul>");
  } catch (error) {
    zona.innerHTML = bloqueEstado("No se pudo traer tu progreso", mensajeDeFallo(error), true);
  }
}

async function cargarRanking() {
  const cuerpo = document.getElementById("cuerpo-ranking");
  cuerpo.innerHTML = filaEsqueleto(4, 3);
  try {
    const filas = (await apiGet(API.gestion + "/participacion/ranking")).datos || [];
    cuerpo.innerHTML = filas.length === 0 ? filaVacia(4, "Todavía no hay zonas", "") : filas.map(function (f) {
      return '<tr role="row">' + celda("Posición", f.posicion) + celda("Zona", f.zona) + celda("Reclamos resueltos", f.resueltos) + celda("Puntos", f.puntos) + "</tr>";
    }).join("");
  } catch (error) {
    cuerpo.innerHTML = filaError(4, mensajeDeFallo(error));
  }
}

async function cargarPreferencia() {
  const casilla = document.getElementById("pref-correo");
  const estado = document.getElementById("pref-estado");
  try {
    const respuesta = await apiGet(API.usuarios + "/preferencias");
    casilla.checked = respuesta.datos.avisos_por_correo === true;
  } catch (error) {
    estado.textContent = "No se pudo leer tu preferencia de avisos por correo.";
  }
  casilla.addEventListener("change", async function () {
    const quiere = casilla.checked;
    casilla.disabled = true;
    try {
      const { ok } = await apiPut(API.usuarios + "/preferencias", { avisos_por_correo: quiere });
      if (!ok) {
        throw new Error("no se pudo guardar");
      }
      estado.textContent = quiere ? "Listo: te vamos a avisar por correo." : "Listo: no te vamos a mandar correos (los avisos siguen en la campanita).";
    } catch (error) {
      casilla.checked = !quiere;
      estado.textContent = "No se pudo guardar el cambio. Probá de nuevo.";
    } finally {
      casilla.disabled = false;
    }
  });
}

async function cargarReclamos() {
  const zona = document.getElementById("zona-reclamos");
  zona.innerHTML = '<span class="esqueleto"></span>';
  try {
    const respuesta = await apiGet(API.gestion + "/incidencias");
    dibujarReclamos(respuesta.datos || []);
  } catch (error) {
    zona.innerHTML = bloqueEstado("No se pudieron traer tus reclamos", mensajeDeFallo(error), true);
  }
}

async function cargarMiCentro() {
  const metricas = ["metrica-pendientes", "metrica-ocupacion", "metrica-mantenimiento"];
  metricas.forEach(cargandoMetrica);
  try {
    const d = (await apiGet(API.gestion + "/estadisticas/centro")).datos;
    document.getElementById("nombre-mi-centro").textContent = d.centro;
    const enlace = "centro.html?id=" + $entero(d.id_centro);
    document.getElementById("enlace-mi-centro").href = enlace;
    document.getElementById("tarjeta-ocupacion").href = enlace;
    pintarMetrica("metrica-pendientes", d.recepciones_pendientes, d.recepciones_pendientes === 1 ? "1 recolección para recibir" : d.recepciones_pendientes + " recolecciones para recibir");
    pintarMetrica("metrica-mantenimiento", d.maquinas_en_mantenimiento);
    if (d.ocupacion_pct === null) {
      pintarMetrica("metrica-ocupacion", "—");
      document.getElementById("detalle-ocupacion").textContent = "Sin capacidad cargada";
    } else {
      pintarMetrica("metrica-ocupacion", Number(d.ocupacion_pct).toLocaleString("es-UY", { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + " %");
      document.getElementById("detalle-ocupacion").textContent = Number(d.stock_kg).toLocaleString("es-UY") + " de " + Number(d.capacidad).toLocaleString("es-UY") + " kg";
    }
    const aviso = document.getElementById("aviso-mi-centro");
    if (d.nivel === "alerta" || d.nivel === "lleno") {
      aviso.dataset.nivel = d.nivel;
      aviso.textContent = d.nivel === "lleno" ? "El centro llegó a su capacidad: hay que retirar material." : "El centro pasó del 80 % de su capacidad: conviene planificar un retiro.";
      aviso.classList.remove("oculto");
    } else {
      aviso.classList.add("oculto");
    }
  } catch (error) {
    metricas.forEach(function (id) { pintarMetrica(id, "—", mensajeDeFallo(error)); });
    const aviso = document.getElementById("aviso-mi-centro");
    aviso.textContent = mensajeDeFallo(error);
    aviso.removeAttribute("data-nivel");
    aviso.classList.remove("oculto");
  }
}

async function cargarPronto() {
  const lista = document.getElementById("lista-pronto");
  lista.innerHTML = '<li><span class="esqueleto"></span></li>';
  try {
    const { filas } = await traerPredicciones(48);
    lista.innerHTML = filas.length === 0
      ? '<li class="texto-tenue">Ningún contenedor se llena en las próximas 48 horas según el ritmo de vaciado (o todavía no hay datos suficientes).</li>'
      : filas.slice(0, 6).map(function (f) {
          return '<li class="tarjeta-pronto"><a href="contenedor.html?id=' + $entero(f.id_contenedor) + '">' + escaparHtml(f.codigo + " · " + f.direccion) + "</a><span>" +
                 escaparHtml(textoDePrediccion(f.prediccion)) + "</span></li>";
        }).join("") + (filas.length > 6 ? '<li class="texto-tenue">Y ' + (filas.length - 6) + " más: mirá la lista completa en Contenedores.</li>" : "");
  } catch (error) {
    lista.innerHTML = '<li class="texto-tenue">' + escaparHtml(mensajeDeFallo(error)) + "</li>";
  }
}

function cargarPanel() {
  if (Sesion.obtener().rol === "Vecino") {
    document.getElementById("bajada-panel").textContent = "Cómo van los problemas que informaste.";
    cargarReclamos();
    cargarPasaje();
    cargarProgreso();
    cargarRanking();
    cargarPreferencia();
    return;
  }
  if (Sesion.obtener().rol === "Operario") {
    document.getElementById("bajada-panel").textContent = "Lo que pasa hoy en tu centro de acopio.";
    cargarMiCentro();
    return;
  }
  const esAdministrador = Sesion.esAdministrador();
  const metricas = ["metrica-contenedores", "metrica-llenos"];
  if (esAdministrador) {
    metricas.push("metrica-camiones", "metrica-usuarios", "metrica-incidencias", "metrica-criticas");
  }
  metricas.forEach(cargandoMetrica);
  cuerpoLlenos.innerHTML = filaEsqueleto(COLUMNAS_LLENOS, 2);

  cargarContenedores();
  if (esAdministrador) {
    cargarCamiones();
    cargarPronto();
    cargarUsuarios();
    cargarUrgencias();
  }
}

document.getElementById("contenido-principal").addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargarPanel();
  }
});

cargarPanel();
