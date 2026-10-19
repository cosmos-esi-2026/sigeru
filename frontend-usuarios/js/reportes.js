Sesion.exigir();

const formPeriodo = document.getElementById("form-periodo");
const NUMERO = new Intl.NumberFormat("es-UY", { maximumFractionDigits: 1 });
const NUMERO_2 = new Intl.NumberFormat("es-UY", { maximumFractionDigits: 2 });
const DIAS = new Intl.NumberFormat("es-UY", { minimumFractionDigits: 1, maximumFractionDigits: 1 });

let informeIncidencias = null;
let informeCalidad = null;

function formatoNumero(valor) {
  return valor === null || valor === undefined ? "-" : NUMERO.format(valor);
}

function formatoDuracion(horas) {
  if (horas === null || horas === undefined) return "-";
  return horas < 48 ? NUMERO.format(horas) + " h" : DIAS.format(horas / 24) + " días";
}

function fechaIso(fecha) {
  const dos = function (n) { return String(n).padStart(2, "0"); };
  return fecha.getFullYear() + "-" + dos(fecha.getMonth() + 1) + "-" + dos(fecha.getDate());
}

function periodoRapido(tipo) {
  const hoy = new Date();
  if (tipo === "30") {
    const inicio = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - 30);
    return { desde: fechaIso(inicio), hasta: fechaIso(hoy) };
  }
  if (tipo === "mes") {
    return { desde: fechaIso(new Date(hoy.getFullYear(), hoy.getMonth(), 1)), hasta: fechaIso(hoy) };
  }
  return { desde: "", hasta: "" };
}

function consultaDelPeriodo() {
  const partes = [];
  ["desde", "hasta", "zona"].forEach(function (campo) {
    const valor = formPeriodo.elements[campo].value;
    if (valor !== "") partes.push(campo + "=" + encodeURIComponent(valor));
  });
  return partes.length ? "?" + partes.join("&") : "";
}

function textoDelPeriodo(periodo) {
  const desde = periodo.desde ? fechaLegible(periodo.desde) : null;
  const hasta = periodo.hasta ? fechaLegible(periodo.hasta) : null;
  let rango = "Todo el historial";
  if (desde && hasta) rango = "Del " + desde + " al " + hasta;
  else if (desde) rango = "Desde el " + desde;
  else if (hasta) rango = "Hasta el " + hasta;
  return rango + " · " + (periodo.zona ? "Zona " + periodo.zona : "Todas las zonas") +
         " · Generado el " + fechaLegible(periodo.generado_en);
}

function filaVaciaSimple(cuerpo, columnas, texto) {
  cuerpo.innerHTML = '<tr class="fila-estado"><td colspan="' + columnas + '" class="texto-tenue">' +
                     escaparHtml(texto) + "</td></tr>";
}

function mostrarIncidencias(datos) {
  const t = datos.totales;
  document.getElementById("m-total").textContent = t.total;
  document.getElementById("m-pendientes").textContent = t.pendientes;
  document.getElementById("m-cerradas").textContent = t.cerradas;
  document.getElementById("m-tiempo").textContent = formatoDuracion(datos.tiempo_resolucion.medio_horas);
  document.getElementById("texto-periodo").textContent = textoDelPeriodo(datos.periodo);

  const cuerpoZonas = document.getElementById("cuerpo-zonas");
  if (datos.por_zona.length === 0) {
    filaVaciaSimple(cuerpoZonas, 7, "No hay incidencias en este período.");
  } else {
    cuerpoZonas.innerHTML = datos.por_zona.map(function (z) {
      return '<tr role="row">' + celda("Zona", z.zona) + celda("Abiertas", z.abiertas) +
             celda("En curso", z.en_curso) + celda("Resueltas", z.resueltas) +
             celda("Descartadas", z.descartadas) + celda("Total", z.total) +
             celda("Tiempo medio de resolución", formatoDuracion(z.tiempo_medio_horas)) + "</tr>";
    }).join("");
  }
  graficoBarras(document.getElementById("grafico-zonas"), datos.por_zona.map(function (z) {
    return { etiqueta: z.zona, valor: z.total };
  }), { descripcion: "Incidencias por zona", vacio: "No hay incidencias en este período." });

  graficoBarras(document.getElementById("grafico-tipos"), datos.por_tipo.map(function (x) {
    return { etiqueta: textoTipoProblema(x.tipo_problema), valor: x.total };
  }), { descripcion: "Incidencias por tipo de problema", vacio: "No hay incidencias en este período." });

  const p = datos.por_prioridad;
  document.getElementById("resumen-prioridad").innerHTML =
    '<p class="texto-tenue">Por prioridad:</p><p>' +
    marcaDeEstado("prioridad", "alta") + " " + p.alta + " &nbsp; " +
    marcaDeEstado("prioridad", "media") + " " + p.media + " &nbsp; " +
    marcaDeEstado("prioridad", "baja") + " " + p.baja + "</p>";

  const r = datos.tiempo_resolucion;
  document.getElementById("datos-tiempo").innerHTML = r.cantidad === 0
    ? '<p class="texto-tenue">Todavía no hay incidencias resueltas en este período.</p>'
    : [["Resueltas", r.cantidad], ["Tiempo medio", formatoDuracion(r.medio_horas)],
       ["Mediana", formatoDuracion(r.mediano_horas)], ["Más rápida", formatoDuracion(r.minimo_horas)],
       ["Más lenta", formatoDuracion(r.maximo_horas)]].map(function (par) {
         return "<div><dt>" + escaparHtml(par[0]) + "</dt><dd>" + escaparHtml(par[1]) + "</dd></div>";
       }).join("");

  const cuerpoTop = document.getElementById("cuerpo-top");
  if (datos.top_contenedores.length === 0) {
    filaVaciaSimple(cuerpoTop, 6, "No hay incidencias en este período.");
  } else {
    cuerpoTop.innerHTML = datos.top_contenedores.map(function (c) {
      return '<tr role="row">' + celda("Código", c.codigo) + celda("Dirección", c.direccion) +
             celda("Zona", c.zona) + celda("Incidencias", c.total) + celda("Pendientes", c.pendientes) +
             celda("Problema más frecuente", c.problema_frecuente ? textoTipoProblema(c.problema_frecuente) : "-") + "</tr>";
    }).join("");
  }

  const cuerpoDetalle = document.getElementById("cuerpo-detalle");
  if (datos.detalle.length === 0) {
    filaVaciaSimple(cuerpoDetalle, 8, "No hay incidencias en este período.");
  } else {
    cuerpoDetalle.innerHTML = datos.detalle.map(function (i) {
      return '<tr role="row">' + celdaEnlace("Número", i.numero, "incidencia.html?id=" + i.id_incidencia) +
             celda("Fecha", fechaLegible(i.fecha_reporte), i.fecha_reporte) +
             celda("Contenedor", i.contenedor) + celda("Zona", i.zona) +
             celda("Tipo", textoTipoProblema(i.tipo_problema)) +
             celdaMarca("Prioridad", "prioridad", i.prioridad, PESO_PRIORIDAD[i.prioridad] || 0) +
             celdaMarca("Estado", "estado", i.estado) +
             celda("Horas hasta resolver", formatoNumero(i.horas_resolucion), i.horas_resolucion === null ? "" : i.horas_resolucion) +
             "</tr>";
    }).join("");
  }
  document.getElementById("contador-detalle").innerHTML = datos.detalle_truncado
    ? "Se muestran las primeras <strong>" + datos.detalle.length + "</strong> de " + t.total + " incidencias; acotá el período para ver el resto."
    : "<strong>" + datos.detalle.length + "</strong> incidencias.";
}

function mostrarRecoleccion(datos) {
  const t = datos.totales;
  document.getElementById("m-kg").textContent = NUMERO.format(t.peso_kg) + " kg";
  document.getElementById("texto-recoleccion").textContent =
    t.recolecciones + " recolecciones en el período (" + t.por_estado.finalizada + " finalizadas, " +
    t.por_estado.en_curso + " en curso, " + t.por_estado.planificada + " planificadas, " +
    t.por_estado.cancelada + " canceladas) y " + t.vaciados + " vaciados" +
    (t.vaciados_sin_peso ? " (" + t.vaciados_sin_peso + " sin peso registrado)" : "") + ".";

  graficoBarras(document.getElementById("grafico-residuos"), datos.por_tipo_residuo.map(function (x) {
    return { etiqueta: x.tipo_residuo, valor: x.peso_kg };
  }), { descripcion: "Kilos por tipo de residuo", formato: function (v) { return NUMERO_2.format(v) + " kg"; },
        vacio: "No hay vaciados en este período." });
  graficoBarras(document.getElementById("grafico-kg-zonas"), datos.por_zona.map(function (x) {
    return { etiqueta: x.zona, valor: x.peso_kg, clase: "es-secundaria" };
  }), { descripcion: "Kilos por zona", formato: function (v) { return NUMERO_2.format(v) + " kg"; },
        vacio: "No hay vaciados en este período." });

  const cuerpo = document.getElementById("cuerpo-residuos");
  if (datos.por_tipo_residuo.length === 0) {
    filaVaciaSimple(cuerpo, 4, "No hay vaciados en este período.");
  } else {
    cuerpo.innerHTML = datos.por_tipo_residuo.map(function (x) {
      return '<tr role="row">' + celda("Tipo de residuo", x.tipo_residuo) + celda("Vaciados", x.vaciados) +
             celda("Peso (kg)", NUMERO_2.format(x.peso_kg)) + celda("Volumen (L)", NUMERO_2.format(x.volumen_l)) + "</tr>";
    }).join("");
  }
}

function promedioTexto(valor) {
  return valor === null || valor === undefined ? "Sin calificaciones" : DIAS.format(valor) + " de 5";
}

function filaCalidad(nombre, a, b, c, d, e) {
  return '<tr role="row">' + celda("Nombre", nombre) + celda("Incidencias", a) + celda("Resueltas", b) + celda("Calificaciones", c) +
         celda("Satisfacción promedio", promedioTexto(d)) + celda("Tiempo medio de resolución", e === null ? "-" : formatoDuracion(e)) + "</tr>";
}

function mostrarCalidad(datos) {
  const g = datos.general;
  document.getElementById("texto-calidad").textContent = g.calificaciones === 0
    ? "Todavía no hay calificaciones en este período. Los vecinos califican la atención de sus reclamos resueltos, de 1 a 5."
    : g.calificaciones + (g.calificaciones === 1 ? " calificación" : " calificaciones") + " en el período: satisfacción promedio " + DIAS.format(g.promedio) + " de 5.";
  document.getElementById("cuerpo-calidad-zonas").innerHTML = datos.por_zona.length === 0
    ? filaVacia(6, "Sin incidencias en este período", "")
    : datos.por_zona.map(function (z) { return filaCalidad(z.zona, z.incidencias, z.resueltas, z.calificaciones, z.promedio, z.tiempo_medio_horas); }).join("");
  document.getElementById("cuerpo-calidad-cuadrillas").innerHTML = datos.por_cuadrilla.length === 0
    ? filaVacia(6, "Ninguna incidencia asignada en este período", "")
    : datos.por_cuadrilla.map(function (c) { return filaCalidad(c.cuadrilla, c.incidencias, c.resueltas, c.calificaciones, c.promedio, c.tiempo_medio_horas); }).join("");
}

function exportarCalidad() {
  if (!informeCalidad) {
    avisoPaginaError("Primero generá el informe.");
    return;
  }
  const filas = [["Tipo", "Nombre", "Incidencias", "Resueltas", "Calificaciones", "Satisfacción promedio (de 5)", "Tiempo medio de resolución (horas)"]];
  informeCalidad.por_zona.forEach(function (z) {
    filas.push(["Zona", z.zona, z.incidencias, z.resueltas, z.calificaciones, decimalCsv(z.promedio), decimalCsv(z.tiempo_medio_horas)]);
  });
  informeCalidad.por_cuadrilla.forEach(function (c) {
    filas.push(["Cuadrilla", c.cuadrilla, c.incidencias, c.resueltas, c.calificaciones, decimalCsv(c.promedio), decimalCsv(c.tiempo_medio_horas)]);
  });
  descargarCsv("calidad-del-servicio_" + sufijoDeArchivo() + ".csv", filas);
}

function fallaDeSeccion(ids, error) {
  ids.forEach(function (id) {
    document.getElementById(id).innerHTML = '<p class="texto-tenue">' + escaparHtml(mensajeDeFallo(error)) + "</p>";
  });
}

async function generar() {
  avisoPagina("");
  const desde = formPeriodo.elements.desde.value;
  const hasta = formPeriodo.elements.hasta.value;
  if (desde && hasta && desde > hasta) {
    avisoPaginaError("La fecha «desde» no puede ser posterior a «hasta».");
    return;
  }
  const consulta = consultaDelPeriodo();

  const resultados = await Promise.allSettled([
    apiGet(API.gestion + "/estadisticas/incidencias" + consulta),
    apiGet(API.recoleccion + "/estadisticas/recoleccion" + consulta),
    apiGet(API.gestion + "/estadisticas/satisfaccion" + consulta),
  ]);

  if (resultados[0].status === "fulfilled") {
    informeIncidencias = resultados[0].value.datos;
    mostrarIncidencias(informeIncidencias);
  } else {
    informeIncidencias = null;
    fallaDeSeccion(["grafico-zonas", "grafico-tipos", "datos-tiempo"], resultados[0].reason);
    avisoPaginaError("No se pudo generar el informe de incidencias. " + mensajeDeFallo(resultados[0].reason));
  }
  if (resultados[1].status === "fulfilled") {
    mostrarRecoleccion(resultados[1].value.datos);
  } else {
    fallaDeSeccion(["grafico-residuos", "grafico-kg-zonas"], resultados[1].reason);
  }
  if (resultados[2].status === "fulfilled") {
    informeCalidad = resultados[2].value.datos;
    mostrarCalidad(informeCalidad);
  } else {
    informeCalidad = null;
    document.getElementById("texto-calidad").textContent = mensajeDeFallo(resultados[2].reason);
  }
}

async function cargarZonas() {
  try {
    const respuesta = await apiGet(API.gestion + "/zonas");
    const select = formPeriodo.elements.zona;
    (respuesta.datos || []).forEach(function (zona) {
      const opcion = document.createElement("option");
      opcion.value = zona.id_zona;
      opcion.textContent = zona.nombre;
      select.appendChild(opcion);
    });
  } catch (error) {
    avisoPaginaError("No se pudieron cargar las zonas. " + mensajeDeFallo(error));
  }
}

function sufijoDeArchivo() {
  const desde = formPeriodo.elements.desde.value || "inicio";
  const hasta = formPeriodo.elements.hasta.value || fechaIso(new Date());
  return desde + "_a_" + hasta;
}

function decimalCsv(valor) {
  return valor === null || valor === undefined ? "" : String(valor).replace(".", ",");
}

function exportarDetalle() {
  if (!informeIncidencias) {
    avisoPaginaError("Primero generá el informe.");
    return;
  }
  const filas = [["Número", "Fecha de reporte", "Contenedor", "Dirección", "Zona", "Tipo", "Prioridad", "Estado",
                  "Fecha de resolución", "Horas hasta resolver"]];
  informeIncidencias.detalle.forEach(function (i) {
    filas.push([i.numero, i.fecha_reporte, i.contenedor, i.direccion, i.zona, textoTipoProblema(i.tipo_problema),
                etiquetaIncidencia("prioridad", i.prioridad).texto, etiquetaIncidencia("estado", i.estado).texto,
                i.fecha_resolucion, decimalCsv(i.horas_resolucion)]);
  });
  descargarCsv("incidencias_" + sufijoDeArchivo() + ".csv", filas);
}

function exportarZonas() {
  if (!informeIncidencias) {
    avisoPaginaError("Primero generá el informe.");
    return;
  }
  const filas = [["Zona", "Abiertas", "En curso", "Resueltas", "Descartadas", "Total", "Tiempo medio de resolución (horas)"]];
  informeIncidencias.por_zona.forEach(function (z) {
    filas.push([z.zona, z.abiertas, z.en_curso, z.resueltas, z.descartadas, z.total, decimalCsv(z.tiempo_medio_horas)]);
  });
  descargarCsv("incidencias-por-zona_" + sufijoDeArchivo() + ".csv", filas);
}

formPeriodo.addEventListener("submit", function (evento) {
  evento.preventDefault();
  generar();
});

document.querySelectorAll("[data-rapido]").forEach(function (boton) {
  boton.addEventListener("click", function () {
    const periodo = periodoRapido(boton.dataset.rapido);
    formPeriodo.elements.desde.value = periodo.desde;
    formPeriodo.elements.hasta.value = periodo.hasta;
    generar();
  });
});

document.getElementById("btn-csv-detalle").addEventListener("click", exportarDetalle);
document.getElementById("btn-csv-zonas").addEventListener("click", exportarZonas);
document.getElementById("btn-csv-calidad").addEventListener("click", exportarCalidad);
document.getElementById("btn-imprimir").addEventListener("click", function () { window.print(); });

document.querySelectorAll("#tabla-detalle th").forEach(function (th) { th.setAttribute("data-orden", ""); });
activarOrden("tabla-detalle");

cargarZonas().then(generar);
