Sesion.exigir();

const contenido = document.getElementById("contenido-principal");
const vista = contenido.dataset.vista;
const tablaIncidencias = document.getElementById("tabla-incidencias");
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formFiltros = document.getElementById("form-filtros");
const CAMPOS_FILTRO = ["estado", "zona", "desde", "hasta"];

const COLUMNAS_POR_VISTA = {
  admin:     ["numero", "contenedor", "zona", "tipo", "prioridad", "estado", "cuadrilla", "fecha"],
  cuadrilla: ["numero", "contenedor", "direccion", "tipo", "prioridad", "estado", "fecha"],
  vecino:    ["numero", "contenedor", "direccion", "tipo", "estado", "fecha"],
};

const COLUMNAS = {
  numero:     { titulo: "Número",     celda: function (i) { return celdaEnlace("Número", i.numero, "incidencia.html?id=" + i.id_incidencia); } },
  contenedor: { titulo: "Contenedor", celda: function (i) { return celda("Contenedor", i.contenedor); } },
  direccion:  { titulo: "Dirección",  celda: function (i) { return celda("Dirección", i.direccion); } },
  zona:       { titulo: "Zona",       celda: function (i) { return celda("Zona", i.zona); } },
  tipo:       { titulo: "Tipo",       celda: function (i) { return celda("Tipo", textoTipoProblema(i.tipo_problema)); } },
  prioridad:  { titulo: "Prioridad",  celda: function (i) { return celdaMarca("Prioridad", "prioridad", i.prioridad, PESO_PRIORIDAD[i.prioridad] || 0); } },
  estado:     { titulo: "Estado",     celda: function (i) { return celdaMarca("Estado", "estado", i.estado); } },
  cuadrilla:  { titulo: "Cuadrilla",  celda: function (i) { return celda("Cuadrilla", i.cuadrilla || "Sin asignar"); } },
  fecha:      { titulo: "Fecha",      celda: function (i) { return celda("Fecha", fechaLegible(i.fecha_reporte), i.fecha_reporte); } },
};

const VACIO = {
  admin:     ["No hay incidencias", "Cuando un vecino informe un problema va a aparecer acá."],
  cuadrilla: ["Tu cuadrilla no tiene incidencias", "Cuando el administrador te asigne una, va a aparecer acá."],
  vecino:    ["Todavía no informaste ningún problema", "Usá «Informar un problema» para avisar de un contenedor desbordado, roto o mal ubicado."],
};

const columnas = COLUMNAS_POR_VISTA[vista] || COLUMNAS_POR_VISTA.vecino;

function armarEncabezado() {
  tablaIncidencias.querySelector("thead").innerHTML = '<tr role="row">' + columnas.map(function (clave) {
    return '<th role="columnheader" data-orden>' + escaparHtml(COLUMNAS[clave].titulo) + "</th>";
  }).join("") + "</tr>";
  activarOrden("tabla-incidencias");
}

function filtrosElegidos() {
  const filtros = {};
  if (!formFiltros) {
    return filtros;
  }
  CAMPOS_FILTRO.forEach(function (campo) {
    const control = formFiltros.elements[campo];
    if (control && control.value !== "") {
      filtros[campo] = control.value;
    }
  });
  return filtros;
}

function cargarFiltrosDeLaDireccion() {
  if (!formFiltros) {
    return;
  }
  const parametros = new URLSearchParams(window.location.search);
  CAMPOS_FILTRO.forEach(function (campo) {
    const control = formFiltros.elements[campo];
    if (control && parametros.has(campo)) {
      control.value = parametros.get(campo);
    }
  });
}

function guardarFiltrosEnLaDireccion(filtros) {
  const consulta = new URLSearchParams(filtros).toString();
  history.replaceState(null, "", window.location.pathname + (consulta ? "?" + consulta : ""));
}

async function cargarIncidencias() {
  const filtros = filtrosElegidos();
  const consulta = new URLSearchParams(filtros).toString();
  guardarFiltrosEnLaDireccion(filtros);
  cuerpoTabla.innerHTML = filaEsqueleto(columnas.length, 4);

  try {
    const respuesta = await apiGet(API.gestion + "/incidencias" + (consulta ? "?" + consulta : ""));
    dibujarTabla(respuesta.datos || [], consulta !== "");
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(columnas.length, mensajeDeFallo(error));
    actualizarContador();
  }
}

function dibujarTabla(incidencias, hayFiltros) {
  if (incidencias.length === 0) {
    cuerpoTabla.innerHTML = hayFiltros
      ? filaVacia(columnas.length, "Ninguna incidencia coincide con los filtros", "Probá con otro estado o con un período más amplio.")
      : filaVacia(columnas.length, VACIO[vista][0], VACIO[vista][1]);
    actualizarContador();
    return;
  }

  cuerpoTabla.innerHTML = incidencias.map(function (incidencia) {
    return '<tr role="row">' + columnas.map(function (clave) {
      return COLUMNAS[clave].celda(incidencia);
    }).join("") + "</tr>";
  }).join("");

  const buscador = document.getElementById("buscador");
  if (buscador && buscador.value.trim() !== "") {
    buscador.dispatchEvent(new Event("input"));
  } else {
    actualizarContador();
  }
}

async function cargarZonas() {
  const selector = formFiltros && formFiltros.elements.zona;
  if (!selector) {
    return;
  }
  try {
    const zonas = await apiGet(API.gestion + "/zonas");
    const elegida = new URLSearchParams(window.location.search).get("zona") || "";
    selector.insertAdjacentHTML("beforeend", (zonas.datos || []).map(function (zona) {
      return '<option value="' + $entero(zona.id_zona) + '">' + escaparHtml(zona.nombre) + "</option>";
    }).join(""));
    selector.value = elegida;
  } catch (error) {
    selector.disabled = true;
  }
}

if (formFiltros) {
  formFiltros.addEventListener("submit", function (evento) {
    evento.preventDefault();
    const desde = formFiltros.elements.desde;
    const hasta = formFiltros.elements.hasta;
    if (desde && hasta && desde.value && hasta.value && desde.value > hasta.value) {
      avisoPaginaError("La fecha «desde» no puede ser posterior a «hasta».");
      desde.focus();
      return;
    }
    document.getElementById("mensaje-pagina").textContent = "";
    document.getElementById("mensaje-pagina").className = "mensaje";
    cargarIncidencias();
  });

  formFiltros.addEventListener("change", function (evento) {
    if (evento.target.tagName === "SELECT" || evento.target.type === "date") {
      formFiltros.requestSubmit();
    }
  });

  formFiltros.addEventListener("reset", function () {
    setTimeout(cargarIncidencias, 0);
  });
}

cuerpoTabla.addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargarIncidencias();
  }
});

document.getElementById("btn-recargar").addEventListener("click", cargarIncidencias);

armarEncabezado();
cargarFiltrosDeLaDireccion();
cargarZonas().then(cargarIncidencias);
