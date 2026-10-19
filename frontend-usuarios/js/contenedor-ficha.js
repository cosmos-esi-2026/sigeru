Sesion.exigir();

const idContenedor = parametroEntero("id");
const zonaDatos = document.getElementById("zona-datos");
const zonaHistorial = document.getElementById("zona-historial");
const cuerpoIncidencias = document.getElementById("cuerpo-incidencias");
const COLUMNAS_INCIDENCIAS = 5;

function filaFicha(titulo, valorHtml) {
  return "<div><dt>" + escaparHtml(titulo) + "</dt><dd>" + valorHtml + "</dd></div>";
}

function dibujarDatos(c) {
  document.title = c.codigo + " - SiGeRU";
  document.getElementById("titulo-pagina").textContent = "Contenedor " + c.codigo;
  document.getElementById("bajada-pagina").textContent = c.direccion + (c.zona ? " · " + c.zona : "");

  zonaDatos.innerHTML = '<dl class="ficha ficha-columnas">' +
    filaFicha("Estado", marcaDeEstado("contenedor", c.estado)) +
    filaFicha("Tipo de residuo", escaparHtml(c.tipo_residuo || "-")) +
    filaFicha("Capacidad", escaparHtml(c.capacidad ? c.capacidad + " L" : "-")) +
    filaFicha("Zona", escaparHtml(c.zona || "-")) +
    filaFicha("Dirección", escaparHtml(c.direccion)) +
    filaFicha("Coordenadas", escaparHtml(c.latitud && c.longitud ? c.latitud + ", " + c.longitud : "Sin cargar")) +
    "</dl>";
}

function dibujarIncidencias(incidencias) {
  document.getElementById("ayuda-incidencias").textContent = document.body.dataset.rol === "Cuadrilla"
    ? "Solo las asignadas a tu cuadrilla."
    : "Todas las informadas sobre este contenedor, la más reciente primero.";

  if (incidencias.length === 0) {
    cuerpoIncidencias.innerHTML = filaVacia(COLUMNAS_INCIDENCIAS, "Sin incidencias", "Nadie informó problemas en este contenedor.");
    return;
  }
  cuerpoIncidencias.innerHTML = incidencias.map(function (i) {
    return '<tr role="row">' +
      celdaEnlace("Número", i.numero, "incidencia.html?id=" + i.id_incidencia) +
      celda("Tipo", textoTipoProblema(i.tipo_problema)) +
      celdaMarca("Prioridad", "prioridad", i.prioridad) +
      celdaMarca("Estado", "estado", i.estado) +
      celda("Fecha", fechaLegible(i.fecha_reporte)) +
      "</tr>";
  }).join("");
}

function mostrarFallo(titulo, detalle, conReintento) {
  document.getElementById("detalle").innerHTML =
    '<section class="panel">' + bloqueEstado(titulo, detalle, conReintento) + "</section>";
}

async function cargarLlenado() {
  const zona = document.getElementById("zona-llenado");
  document.getElementById("explicacion-prediccion").innerHTML = EXPLICACION_PREDICCION;
  try {
    const { mapa } = await traerPredicciones();
    const p = mapa[idContenedor];
    if (!p) {
      zona.textContent = "Este contenedor está retirado o en mantenimiento: no se estima cuándo se llena.";
      return;
    }
    zona.innerHTML = "<strong>" + escaparHtml(textoDePrediccion(p)) + "</strong>" +
      (p.estado === "estimada"
        ? '<br><span class="texto-tenue">Nivel estimado hoy: ' + parseInt(p.nivel_estimado_pct, 10) + " % · intervalo medio entre vaciados: " + escaparHtml(String(p.intervalo_medio_horas)) + " h (" + parseInt(p.vaciados, 10) + " vaciados).</span>"
        : "");
  } catch (error) {
    zona.textContent = mensajeDeFallo(error);
  }
}

async function cargarFicha() {
  if (idContenedor === null) {
    mostrarFallo("Falta indicar el contenedor", "Entrá desde la lista de contenedores.", false);
    return;
  }
  cuerpoIncidencias.innerHTML = filaEsqueleto(COLUMNAS_INCIDENCIAS, 2);

  try {
    const contenedor = await apiGet(API.gestion + "/contenedores/" + idContenedor);
    dibujarDatos(contenedor.datos);
  } catch (error) {
    if (error.estado === 404) {
      mostrarFallo("No encontramos ese contenedor", "Puede que el enlace esté mal.", false);
    } else {
      mostrarFallo("No se pudo cargar el contenedor", mensajeDeFallo(error), true);
    }
    return;
  }

  cargarLlenado();
  const [historial, incidencias] = await Promise.allSettled([
    apiGet(API.gestion + "/contenedores/" + idContenedor + "/historial"),
    apiGet(API.gestion + "/incidencias?contenedor=" + idContenedor),
  ]);

  zonaHistorial.innerHTML = historial.status === "fulfilled"
    ? lineaDeTiempo(historial.value.datos, "contenedor")
    : bloqueEstado("No se pudo cargar el historial", mensajeDeFallo(historial.reason), true);

  if (incidencias.status === "fulfilled") {
    dibujarIncidencias(incidencias.value.datos || []);
  } else {
    cuerpoIncidencias.innerHTML = filaError(COLUMNAS_INCIDENCIAS, mensajeDeFallo(incidencias.reason));
  }
}

document.getElementById("detalle").addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    window.location.reload();
  }
});

cargarFicha();
