Sesion.exigir();

const contenido = document.getElementById("contenido-principal");
const vista = contenido.dataset.vista;
const tablaRecolecciones = document.getElementById("tabla-recolecciones");
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formFiltros = document.getElementById("form-filtros");
const CAMPOS_FILTRO = ["estado", "desde", "hasta"];

const COLUMNAS = {
  numero:    { titulo: "Número",    celda: function (r) { return celdaEnlace("Número", r.numero, "recoleccion.html?id=" + r.id_recoleccion); } },
  fecha:     { titulo: "Inicio",    celda: function (r) { return celda("Inicio", fechaLegible(r.fecha_hora_inicio), r.fecha_hora_inicio); } },
  ruta:      { titulo: "Ruta",      celda: function (r) { return celda("Ruta", r.ruta); } },
  zona:      { titulo: "Zona",      celda: function (r) { return celda("Zona", r.zona); } },
  camion:    { titulo: "Camión",    celda: function (r) { return celda("Camión", r.matricula); } },
  cuadrilla: { titulo: "Cuadrilla", celda: function (r) { return celda("Cuadrilla", r.cuadrilla); } },
  estado:    { titulo: "Estado",    celda: function (r) { return celdaMarca("Estado", "recoleccion", r.estado, undefined, marcaVencida(r)); } },
  vaciados:  { titulo: "Vaciados",  celda: function (r) {
    return celda("Vaciados", r.vaciados === 0 ? "Ninguno" : r.vaciados + " · " + formatoKg(r.peso_total_kg), r.peso_total_kg);
  } },
};

const COLUMNAS_POR_VISTA = {
  admin:     ["numero", "fecha", "ruta", "zona", "camion", "cuadrilla", "estado", "vaciados"],
  cuadrilla: ["numero", "fecha", "ruta", "zona", "camion", "estado", "vaciados"],
};

const VACIO = {
  admin:     ["No hay recolecciones", "Planificá la primera con «Planificar recolección»."],
  cuadrilla: ["Tu cuadrilla no tiene tareas pendientes", "Cuando el administrador planifique una recolección para tu cuadrilla, va a aparecer acá."],
};

const columnas = COLUMNAS_POR_VISTA[vista] || COLUMNAS_POR_VISTA.cuadrilla;

function formatoKg(valor) {
  return Number(valor || 0).toLocaleString("es-UY", { maximumFractionDigits: 2 }) + " kg";
}

function armarEncabezado() {
  tablaRecolecciones.querySelector("thead").innerHTML = '<tr role="row">' + columnas.map(function (clave) {
    return '<th role="columnheader" data-orden>' + escaparHtml(COLUMNAS[clave].titulo) + "</th>";
  }).join("") + "</tr>";
  activarOrden("tabla-recolecciones");
}

function filtrosElegidos() {
  const filtros = {};
  CAMPOS_FILTRO.forEach(function (campo) {
    const control = formFiltros.elements[campo];
    if (control && control.value !== "") {
      filtros[campo] = control.value;
    }
  });
  return filtros;
}

function consultaDe(filtros) {
  const parametros = Object.assign({}, filtros);
  if (parametros.estado === "pendientes") {
    parametros.activas = "1";
  }
  if (parametros.estado === "pendientes" || parametros.estado === "todas") {
    delete parametros.estado;
  }
  return new URLSearchParams(parametros).toString();
}

function cargarFiltrosDeLaDireccion() {
  const parametros = new URLSearchParams(window.location.search);
  CAMPOS_FILTRO.forEach(function (campo) {
    const control = formFiltros.elements[campo];
    if (control && parametros.has(campo)) {
      control.value = parametros.get(campo);
    }
  });
}

async function cargarRecolecciones() {
  const filtros = filtrosElegidos();
  const enLaDireccion = new URLSearchParams(filtros).toString();
  history.replaceState(null, "", window.location.pathname + (enLaDireccion ? "?" + enLaDireccion : ""));
  cuerpoTabla.innerHTML = filaEsqueleto(columnas.length, 4);

  try {
    const consulta = consultaDe(filtros);
    const respuesta = await apiGet(API.recoleccion + "/recolecciones" + (consulta ? "?" + consulta : ""));
    dibujarTabla(respuesta.datos || [], Object.keys(filtros).some(function (c) { return c !== "estado" || ["pendientes", "todas"].indexOf(filtros[c]) === -1; }));
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(columnas.length, mensajeDeFallo(error));
    actualizarContador();
  }
}

function dibujarTabla(recolecciones, hayFiltros) {
  if (recolecciones.length === 0) {
    cuerpoTabla.innerHTML = hayFiltros
      ? filaVacia(columnas.length, "Ninguna recolección coincide con los filtros", "Probá con otro estado o con un período más amplio.")
      : filaVacia(columnas.length, VACIO[vista][0], VACIO[vista][1]);
    actualizarContador();
    return;
  }

  cuerpoTabla.innerHTML = recolecciones.map(function (recoleccion) {
    return '<tr role="row">' + columnas.map(function (clave) {
      return COLUMNAS[clave].celda(recoleccion);
    }).join("") + "</tr>";
  }).join("");

  const buscador = document.getElementById("buscador");
  if (buscador && buscador.value.trim() !== "") {
    buscador.dispatchEvent(new Event("input"));
  } else {
    actualizarContador();
  }
}

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
  cargarRecolecciones();
});

formFiltros.addEventListener("change", function (evento) {
  if (evento.target.tagName === "SELECT") {
    formFiltros.requestSubmit();
  }
});

formFiltros.addEventListener("reset", function () {
  setTimeout(cargarRecolecciones, 0);
});

cuerpoTabla.addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargarRecolecciones();
  }
});

document.getElementById("btn-recargar").addEventListener("click", cargarRecolecciones);

armarEncabezado();
cargarFiltrosDeLaDireccion();
cargarRecolecciones();
