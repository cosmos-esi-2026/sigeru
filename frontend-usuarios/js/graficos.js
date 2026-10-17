// Graficos de barras en SVG propio (sin librerias). Cada barra lleva su etiqueta y su valor
// escritos en el dibujo, y el grafico entero tiene un texto alternativo; las mismas cifras
// estan siempre en una tabla al lado. El dibujo se hace al ancho real del contenedor para que
// el texto no se achique en el celular, y se rehace si cambia el ancho de la ventana.

const GRAFICOS_DIBUJADOS = [];

function graficoBarras(contenedor, filas, opciones) {
  const config = opciones || {};
  const formato = config.formato || function (valor) { return String(valor); };
  const datos = (filas || []).filter(function (fila) { return fila && typeof fila.valor === "number"; });

  if (datos.length === 0 || datos.every(function (fila) { return fila.valor === 0; })) {
    contenedor.innerHTML = '<p class="texto-tenue">' + escaparHtml(config.vacio || "Sin datos en este período.") + "</p>";
    return;
  }

  const ancho = Math.max(260, Math.min(640, contenedor.clientWidth || 560));
  const RESERVA_VALOR = 96;
  const ALTO_FILA = 40;
  const maximo = Math.max.apply(null, datos.map(function (fila) { return fila.valor; }));
  const alto = datos.length * ALTO_FILA + 4;
  const resumen = (config.descripcion ? config.descripcion + ": " : "") + datos.map(function (fila) {
    return fila.etiqueta + " " + formato(fila.valor);
  }).join("; ");

  const barras = datos.map(function (fila, i) {
    const y = i * ALTO_FILA + 2;
    const largo = fila.valor === 0 ? 0 : Math.max(3, Math.round((fila.valor / maximo) * (ancho - RESERVA_VALOR)));
    const clase = "grafico-barra" + (fila.clase ? " " + fila.clase : "");
    return '<text class="grafico-etiqueta" x="0" y="' + (y + 14) + '">' + escaparHtml(fila.etiqueta) + "</text>" +
           '<rect class="' + clase + '" x="0" y="' + (y + 20) + '" width="' + largo + '" height="12" rx="3"/>' +
           '<text class="grafico-valor" x="' + (largo + 8) + '" y="' + (y + 31) + '">' + escaparHtml(formato(fila.valor)) + "</text>";
  }).join("");

  contenedor.innerHTML =
    '<svg viewBox="0 0 ' + ancho + " " + alto + '" width="' + ancho + '" height="' + alto +
    '" role="img" aria-label="' + escaparHtml(resumen) + '" focusable="false">' +
    "<title>" + escaparHtml(resumen) + "</title>" + barras + "</svg>";

  const previo = GRAFICOS_DIBUJADOS.find(function (g) { return g.contenedor === contenedor; });
  if (previo) {
    previo.filas = filas;
    previo.opciones = opciones;
    previo.ancho = ancho;
  } else {
    GRAFICOS_DIBUJADOS.push({ contenedor: contenedor, filas: filas, opciones: opciones, ancho: ancho });
  }
}

let temporizadorGraficos = null;
window.addEventListener("resize", function () {
  clearTimeout(temporizadorGraficos);
  temporizadorGraficos = setTimeout(function () {
    GRAFICOS_DIBUJADOS.forEach(function (g) {
      const nuevo = Math.max(260, Math.min(640, g.contenedor.clientWidth || 560));
      if (nuevo !== g.ancho && g.contenedor.isConnected) {
        graficoBarras(g.contenedor, g.filas, g.opciones);
      }
    });
  }, 150);
});
