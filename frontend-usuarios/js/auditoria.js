Sesion.exigir();

const COLUMNAS = 6;
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formFiltros = document.getElementById("form-filtros");
const campoBusqueda = document.getElementById("buscar");
const paginador = crearPaginador("paginacion", "registros", function () { cargarRegistro(); });
let hayFiltros = false;

// De la ruta y la operacion a una frase. Lo que no se reconoce se muestra tal cual, sin inventar.
const FRASES = [
  ["POST", /^\/api-usuarios\/login$/, "Inició sesión"],
  ["POST", /^\/api-usuarios\/logout$/, "Cerró sesión"],
  ["POST", /^\/api-usuarios\/registro$/, "Se registró como vecino"],
  ["POST", /^\/api-usuarios\/usuarios$/, "Creó un usuario"],
  ["PUT", /^\/api-usuarios\/usuarios\/\d+$/, "Modificó un usuario"],
  ["DELETE", /^\/api-usuarios\/usuarios\/\d+$/, "Dio de baja a un usuario"],
  ["POST", /^\/api-usuarios\/usuarios\/\d+\/enlace$/, "Envió un acceso por correo"],
  ["POST", /^\/api-usuarios\/solicitudes$/, "Pidió que lo registren"],
  ["PUT", /^\/api-usuarios\/solicitudes\/\d+$/, "Resolvió una solicitud de registro"],
  ["POST", /^\/api-gestion\/contenedores$/, "Creó un contenedor"],
  ["PUT", /^\/api-gestion\/contenedores\/\d+$/, "Modificó un contenedor"],
  ["DELETE", /^\/api-gestion\/contenedores\/\d+$/, "Retiró un contenedor"],
  ["POST", /^\/api-gestion\/centros$/, "Creó un centro de acopio"],
  ["PUT", /^\/api-gestion\/centros\/\d+$/, "Modificó un centro de acopio"],
  ["DELETE", /^\/api-gestion\/centros\/\d+$/, "Dio de baja un centro de acopio"],
  ["POST", /^\/api-gestion\/maquinaria$/, "Creó una maquinaria"],
  ["PUT", /^\/api-gestion\/maquinaria\/\d+$/, "Modificó una maquinaria"],
  ["DELETE", /^\/api-gestion\/maquinaria\/\d+$/, "Dio de baja una maquinaria"],
  ["POST", /^\/api-gestion\/(tipos-residuo|zonas)$/, "Creó un tipo de residuo o una zona"],
  ["PUT", /^\/api-gestion\/tipos-residuo\/\d+$/, "Modificó un tipo de residuo"],
  ["DELETE", /^\/api-gestion\/tipos-residuo\/\d+$/, "Eliminó un tipo de residuo"],
  ["POST", /^\/api-gestion\/incidencias$/, "Informó un problema"],
  ["PUT", /^\/api-gestion\/incidencias\/\d+$/, "Cambió la prioridad de una incidencia"],
  ["POST", /^\/api-gestion\/incidencias\/\d+\/asignacion$/, "Asignó una incidencia a una cuadrilla"],
  ["POST", /^\/api-gestion\/incidencias\/\d+\/avance$/, "Registró un avance en una incidencia"],
  ["POST", /^\/api-gestion\/incidencias\/\d+\/resolucion$/, "Resolvió una incidencia"],
  ["POST", /^\/api-gestion\/incidencias\/\d+\/descarte$/, "Descartó una incidencia"],
  ["POST", /^\/api-gestion\/incidencias\/\d+\/foto$/, "Adjuntó una foto a una incidencia"],
  ["POST", /^\/api-recoleccion\/cuadrillas$/, "Creó una cuadrilla"],
  ["PUT", /^\/api-recoleccion\/cuadrillas\/\d+$/, "Modificó una cuadrilla"],
  ["DELETE", /^\/api-recoleccion\/cuadrillas\/\d+$/, "Dio de baja una cuadrilla"],
  ["POST", /^\/api-recoleccion\/camiones$/, "Creó un camión"],
  ["PUT", /^\/api-recoleccion\/camiones\/\d+$/, "Modificó un camión"],
  ["DELETE", /^\/api-recoleccion\/camiones\/\d+$/, "Dio de baja un camión"],
  ["POST", /^\/api-recoleccion\/rutas$/, "Creó una ruta"],
  ["PUT", /^\/api-recoleccion\/rutas\/\d+$/, "Modificó una ruta"],
  ["PUT", /^\/api-recoleccion\/rutas\/\d+\/contenedores$/, "Cambió los contenedores de una ruta"],
  ["POST", /^\/api-recoleccion\/rutas\/\d+\/trazado$/, "Trazó una ruta por las calles"],
  ["DELETE", /^\/api-recoleccion\/rutas\/\d+$/, "Dio de baja o borró una ruta"],
  ["POST", /^\/api-recoleccion\/recolecciones$/, "Planificó una recolección"],
  ["POST", /^\/api-recoleccion\/recolecciones\/\d+\/inicio$/, "Inició una recolección"],
  ["POST", /^\/api-recoleccion\/recolecciones\/\d+\/vaciados$/, "Registró un vaciado"],
  ["POST", /^\/api-recoleccion\/recolecciones\/\d+\/fin$/, "Finalizó una recolección"],
  ["POST", /^\/api-recoleccion\/recolecciones\/\d+\/cancelacion$/, "Canceló una recolección"],
];

function frase(metodo, ruta) {
  const hallada = FRASES.find(function (f) { return f[0] === metodo && f[1].test(ruta); });
  return hallada ? hallada[2] : metodo + " " + ruta;
}

function consultaDeLaLista(extra) {
  const q = campoBusqueda.value.trim();
  const base = consultaDeFormulario(formFiltros, ["resultado", "metodo", "desde", "hasta"], extra);
  if (q === "") return base;
  return (base === "" ? "?" : base + "&") + "q=" + encodeURIComponent(q);
}

function dibujarTabla(registros) {
  if (!registros || registros.length === 0) {
    cuerpoTabla.innerHTML = hayFiltros
      ? filaVacia(COLUMNAS, "Ningún registro coincide", "Probá con otras fechas o limpiá los filtros.")
      : filaVacia(COLUMNAS, "Todavía no hay registros", "Cada cambio que se haga en el sistema va a aparecer acá.");
    return;
  }
  cuerpoTabla.innerHTML = registros.map(function (r) {
    const negado = r.codigo === 403;
    return '<tr role="row">' +
      celda("Fecha", fechaLegible(r.fecha_hora), r.fecha_hora) +
      celda("Persona", r.usuario ? r.usuario + (r.email ? " (" + r.email + ")" : "") : "Sin sesión") +
      celda("Rol", r.rol || "-") +
      celda("Qué hizo", frase(r.metodo, r.ruta)) +
      celdaEstado("Resultado", negado ? "Negado" : "Hecho", negado ? "rojo" : "verde") +
      celda("Dirección IP", r.ip) +
      "</tr>";
  }).join("");
}

async function cargarRegistro() {
  cuerpoTabla.innerHTML = filaEsqueleto(COLUMNAS, 4);
  hayFiltros = consultaDeLaLista({}) !== "";
  try {
    const datos = await apiGet(API.usuarios + "/auditoria" + consultaDeLaLista({
      pagina: paginador.estado.pagina, por_pagina: paginador.estado.porPagina,
    }));
    if (paginador.ajustar(datos.meta)) {
      return cargarRegistro();
    }
    dibujarTabla(datos.datos);
    paginador.dibujar(datos.meta);
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(COLUMNAS, mensajeDeFallo(error));
    paginador.dibujar(null);
  }
}

function volverALaPrimera() { paginador.volverAlPrincipio(); cargarRegistro(); }

cuerpoTabla.addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargarRegistro();
  }
});
document.getElementById("btn-recargar").addEventListener("click", cargarRegistro);
formFiltros.addEventListener("change", volverALaPrimera);
formFiltros.addEventListener("reset", function () { campoBusqueda.value = ""; setTimeout(volverALaPrimera, 0); });
campoBusqueda.addEventListener("input", retrasar(volverALaPrimera, 350));

cargarRegistro();
