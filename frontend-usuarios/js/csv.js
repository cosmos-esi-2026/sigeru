// CSV para planillas en español: separador «;», fin de línea CRLF y UTF-8 con marca de orden
// de bytes, que es lo que Excel y LibreOffice necesitan para leer bien las tildes.

function celdaCsv(valor) {
  let texto = valor === null || valor === undefined ? "" : String(valor);
  // Una planilla toma como fórmula lo que empieza con estos signos: se lo marca como texto.
  if (/^[=+\-@\t\r]/.test(texto)) {
    texto = "'" + texto;
  }
  return /[";\r\n]/.test(texto) ? '"' + texto.replace(/"/g, '""') + '"' : texto;
}

function construirCsv(encabezado, filas) {
  return [encabezado].concat(filas).map(function (fila) {
    return fila.map(celdaCsv).join(";");
  }).join("\r\n") + "\r\n";
}

function descargarCsv(nombre, texto) {
  const archivo = new Blob(["﻿" + texto], { type: "text/csv;charset=utf-8" });
  const enlace = document.createElement("a");
  enlace.href = URL.createObjectURL(archivo);
  enlace.download = nombre;
  document.body.appendChild(enlace);
  enlace.click();
  document.body.removeChild(enlace);
  setTimeout(function () { URL.revokeObjectURL(enlace.href); }, 1000);
}
