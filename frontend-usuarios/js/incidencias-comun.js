const ETIQUETAS_INCIDENCIA = {
  estado: {
    abierta:    { texto: "Abierta",    color: "naranja" },
    en_curso:   { texto: "En curso",   color: "azul" },
    resuelta:   { texto: "Resuelta",   color: "verde" },
    descartada: { texto: "Descartada", color: "gris" },
  },
  prioridad: {
    alta:  { texto: "Alta",  color: "rojo" },
    media: { texto: "Media", color: "naranja" },
    baja:  { texto: "Baja",  color: "gris" },
  },
  contenedor: {
    disponible:    { texto: "Disponible",       color: "verde" },
    lleno:         { texto: "Lleno",            color: "rojo" },
    mantenimiento: { texto: "En mantenimiento", color: "naranja" },
    retirado:      { texto: "Retirado",         color: "gris" },
  },
  recoleccion: {
    planificada: { texto: "Planificada", color: "azul" },
    en_curso:    { texto: "En curso",    color: "naranja" },
    finalizada:  { texto: "Finalizada",  color: "verde" },
    cancelada:   { texto: "Cancelada",   color: "gris" },
  },
  camion: {
    operativo:      { texto: "Operativo",         color: "verde" },
    en_ruta:        { texto: "En ruta",           color: "azul" },
    mantenimiento:  { texto: "En mantenimiento",  color: "naranja" },
    fuera_servicio: { texto: "Fuera de servicio", color: "gris" },
  },
  tipo: {
    desbordado:            "Contenedor desbordado",
    lleno:                 "Contenedor lleno",
    roto:                  "Contenedor roto o dañado",
    residuo_no_permitido:  "Residuo no permitido",
    ausente_o_mal_ubicado: "Contenedor ausente o mal ubicado",
    otro:                  "Otro",
  },
};

const PESO_PRIORIDAD = { alta: 3, media: 2, baja: 1 };

function etiquetaIncidencia(grupo, valor) {
  const etiqueta = ETIQUETAS_INCIDENCIA[grupo][valor];
  if (!etiqueta) {
    return { texto: textoEstado(valor), color: "gris" };
  }
  return typeof etiqueta === "string" ? { texto: etiqueta, color: "gris" } : etiqueta;
}

function textoTipoProblema(valor) {
  return etiquetaIncidencia("tipo", valor).texto;
}

function marcaDeEstado(grupo, valor) {
  const etiqueta = etiquetaIncidencia(grupo, valor);
  return '<span class="estado ' + etiqueta.color + '">' + escaparHtml(etiqueta.texto) + "</span>";
}

function fechaLegible(valor) {
  const partes = /^(\d{4})-(\d{2})-(\d{2})(?: (\d{2}):(\d{2}))?/.exec(valor || "");
  if (!partes) {
    return "-";
  }
  const dia = partes[3] + "/" + partes[2] + "/" + partes[1];
  return partes[4] ? dia + " " + partes[4] + ":" + partes[5] : dia;
}

function celdaMarca(etiqueta, grupo, valor, valorOrden) {
  const orden = valorOrden !== undefined
    ? ' data-orden-valor="' + escaparHtml(valorOrden) + '"'
    : "";
  return '<td role="cell" data-etiqueta="' + escaparHtml(etiqueta) + '"' + orden + ">" +
         marcaDeEstado(grupo, valor) + "</td>";
}

function parametroEntero(nombre) {
  const valor = new URLSearchParams(window.location.search).get(nombre);
  return /^[1-9][0-9]{0,9}$/.test(valor || "") ? parseInt(valor, 10) : null;
}

function bloqueEstado(titulo, detalle, esError) {
  return '<div class="estado-bloque' + (esError ? " es-error" : "") + '">' +
         "<h2>" + escaparHtml(titulo) + "</h2>" +
         (detalle ? "<p>" + escaparHtml(detalle) + "</p>" : "") +
         (esError
           ? '<button type="button" class="boton boton-secundario boton-chico js-reintentar">Reintentar</button>'
           : "") +
         "</div>";
}

function lineaDeTiempo(asientos, grupo) {
  if (!asientos || asientos.length === 0) {
    return '<p class="texto-tenue">Todavía no hay registros.</p>';
  }

  return '<ol class="linea-tiempo">' + asientos.map(function (asiento) {
    const autor = asiento.usuario
      ? asiento.usuario + (asiento.rol ? " (" + asiento.rol + ")" : "")
      : (asiento.rol || "");
    return "<li>" +
      '<div class="linea-tiempo-cabecera">' +
      '<time class="linea-tiempo-fecha">' + escaparHtml(fechaLegible(asiento.fecha_hora)) + "</time>" +
      marcaDeEstado(grupo || "estado", asiento.estado) +
      "</div>" +
      '<p class="linea-tiempo-texto">' + escaparHtml(asiento.observacion) + "</p>" +
      (autor ? '<p class="linea-tiempo-autor">' + escaparHtml(autor) + "</p>" : "") +
      "</li>";
  }).join("") + "</ol>";
}
