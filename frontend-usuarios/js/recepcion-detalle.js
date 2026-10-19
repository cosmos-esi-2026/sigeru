Sesion.exigir();

const idRecoleccion = parametroEntero("id");
const zonaDatos = document.getElementById("zona-datos");
const zonaRecepcion = document.getElementById("zona-recepcion");
const cuerpoEsperado = document.getElementById("cuerpo-esperado");

let tiposDeResiduo = [];

function kg(valor) {
  return valor === null || valor === undefined
    ? "-"
    : Number(valor).toLocaleString("es-UY", { maximumFractionDigits: 2 }) + " kg";
}

function filaFicha(titulo, valorHtml) {
  return "<div><dt>" + escaparHtml(titulo) + "</dt><dd>" + valorHtml + "</dd></div>";
}

function dibujarDatos(r) {
  zonaDatos.innerHTML = '<dl class="ficha ficha-columnas">' +
    filaFicha("Estado", marcaDeEstado("recoleccion", r.estado)) +
    filaFicha("Inicio", escaparHtml(fechaLegible(r.fecha_hora_inicio))) +
    (r.fecha_hora_fin ? filaFicha("Fin", escaparHtml(fechaLegible(r.fecha_hora_fin))) : "") +
    filaFicha("Ruta", escaparHtml(r.ruta)) +
    filaFicha("Zona", escaparHtml(r.zona)) +
    filaFicha("Camión", escaparHtml(r.matricula)) +
    filaFicha("Cuadrilla", escaparHtml(r.cuadrilla)) +
    filaFicha("Centro de destino", '<a href="centro.html?id=' + $entero(r.id_centro) + '">' + escaparHtml(r.centro) + "</a>") +
    filaFicha("Vaciados", escaparHtml(r.vaciados + (r.vaciados === 1 ? " contenedor" : " contenedores") + " · " + kg(r.peso_esperado_kg))) +
    "</dl>";
  const declarado = r.esperado_por_tipo || [];
  cuerpoEsperado.innerHTML = declarado.length === 0
    ? filaVacia(2, "Todavía no hay vaciados", "El camión no registró ningún contenedor vaciado.")
    : declarado.map(function (t) {
        return '<tr role="row">' + celda("Tipo de residuo", t.tipo) + celda("Peso declarado", kg(t.kg)) + "</tr>";
      }).join("");
}

function diferencia(declarado, recibido) {
  const d = Math.round((recibido - declarado) * 100) / 100;
  if (d === 0) {
    return '<span class="texto-tenue">igual</span>';
  }
  return '<span class="' + (d > 0 ? "diferencia-mas" : "diferencia-menos") + '">' + (d > 0 ? "+" : "") + kg(d) + "</span>";
}

function dibujarRecibida(r) {
  const declarado = {};
  (r.esperado_por_tipo || []).forEach(function (t) { declarado[t.id_tipo_residuo] = t.kg; });
  const filas = (r.recibido_por_tipo || []).map(function (t) {
    return '<tr role="row">' + celda("Tipo de residuo", t.tipo) + celda("Recibido", kg(t.kg) + (t.corregido ? " (corregido)" : "")) +
      '<td role="cell" data-etiqueta="Diferencia">' + diferencia(declarado[t.id_tipo_residuo] || 0, t.kg) + "</td></tr>";
  }).join("");
  zonaRecepcion.innerHTML =
    '<p class="mensaje ok" role="status">Recibida el ' + escaparHtml(fechaLegible(r.fecha_recepcion)) +
    (r.recibida_por ? " por " + escaparHtml(r.recibida_por) : "") + ".</p>" +
    '<div class="tabla-scroll"><table class="tabla tabla-corta" role="table"><thead role="rowgroup"><tr role="row">' +
    '<th role="columnheader">Tipo</th><th role="columnheader">Recibido</th><th role="columnheader">Diferencia</th></tr></thead>' +
    '<tbody role="rowgroup">' + filas + "</tbody></table></div>" +
    '<p class="texto-tenue">Total recibido: <strong>' + escaparHtml(kg(r.peso_recibido_kg)) + "</strong>. Declarado: " + escaparHtml(kg(r.peso_esperado_kg)) + ".</p>" +
    (r.observacion_recepcion ? '<h3 class="subtitulo-panel">Observación</h3><p class="texto-largo">' + escaparHtml(r.observacion_recepcion) + "</p>" : "") +
    '<p><a class="boton boton-secundario boton-chico" href="centro.html?id=' + $entero(r.id_centro) + '">Ver el stock del centro</a></p>' +
    '<p class="texto-tenue">Un error de pesaje se corrige desde el stock del centro, con un movimiento inverso.</p>';
}

function dibujarFormulario(r) {
  const declarado = {};
  (r.esperado_por_tipo || []).forEach(function (t) { declarado[t.id_tipo_residuo] = t.kg; });
  const ordenados = tiposDeResiduo.slice().sort(function (a, b) {
    return (declarado[b.id_tipo_residuo] !== undefined) - (declarado[a.id_tipo_residuo] !== undefined);
  });
  const filas = ordenados.map(function (t) {
    const previsto = declarado[t.id_tipo_residuo];
    const id = "kg-" + $entero(t.id_tipo_residuo);
    return '<tr role="row"><td role="cell" data-etiqueta="Tipo"><label for="' + id + '">' + escaparHtml(t.nombre) + "</label></td>" +
      celda("Declarado", previsto === undefined ? "-" : kg(previsto)) +
      '<td role="cell" data-etiqueta="Recibido (kg)"><input type="number" id="' + id + '" name="' + id + '" data-tipo="' + $entero(t.id_tipo_residuo) + '"' +
      ' min="0" max="1000000" step="0.01" inputmode="decimal" value="' + (previsto === undefined ? "" : escaparHtml(previsto)) + '"></td></tr>';
  }).join("");
  const aviso = r.estado === "en_curso"
    ? '<p class="ayuda">La recolección todavía está en curso: podés recibir lo que ya llegó.</p>' : "";
  zonaRecepcion.innerHTML = aviso +
    '<form class="formulario" id="form-recepcion" novalidate>' +
    '<p class="ayuda">Pesá lo que descargó el camión y anotá los kg de cada tipo. Ya viene lo que declararon los vaciados; corregilo si el pesaje da distinto.</p>' +
    '<div class="tabla-scroll"><table class="tabla tabla-lineas" role="table"><thead role="rowgroup"><tr role="row">' +
    '<th role="columnheader">Tipo de residuo</th><th role="columnheader">Declarado</th><th role="columnheader">Recibido (kg)</th></tr></thead>' +
    '<tbody role="rowgroup">' + filas + "</tbody></table></div>" +
    '<label>Observación<textarea name="observacion" rows="2" maxlength="300" placeholder="Opcional: por ejemplo, bolsas rotas o material mezclado"></textarea></label>' +
    '<button type="submit" class="boton boton-primario">Confirmar la recepción</button></form>';
}

function dibujar(r) {
  document.title = r.numero + " (recepción) - SiGeRU";
  document.getElementById("titulo-pagina").textContent = "Recepción de " + r.numero;
  document.getElementById("bajada-pagina").textContent = r.ruta + " · " + r.zona + " · hacia " + r.centro;
  dibujarDatos(r);
  if (r.recibida) {
    dibujarRecibida(r);
  } else {
    dibujarFormulario(r);
  }
}

function mostrarFallo(titulo, detalle, conReintento) {
  document.getElementById("detalle").innerHTML = '<section class="panel">' + bloqueEstado(titulo, detalle, conReintento) + "</section>";
}

async function cargar() {
  if (idRecoleccion === null) {
    mostrarFallo("Falta indicar la recolección", "Entrá desde la lista de recepciones.", false);
    return;
  }
  try {
    const tipos = await apiGet(API.gestion + "/tipos-residuo");
    tiposDeResiduo = (tipos.datos || []).slice().sort(function (a, b) { return a.nombre.localeCompare(b.nombre, "es"); });
    const respuesta = await apiGet(API.gestion + "/recepciones/" + idRecoleccion);
    dibujar(respuesta.datos);
  } catch (error) {
    if (error.estado === 404) {
      mostrarFallo("No encontramos esa recolección", "Puede que no exista, que todavía no haya salido o que no vaya a tu centro.", false);
    } else {
      mostrarFallo("No se pudo cargar la recepción", mensajeDeFallo(error), true);
    }
  }
}

zonaRecepcion.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  const form = evento.target;
  const lineas = [];
  let primerError = null;
  form.querySelectorAll("input[data-tipo]").forEach(function (campo) {
    campo.removeAttribute("aria-invalid");
    if (campo.value.trim() === "") {
      return;
    }
    const valor = Number(campo.value);
    if (!(valor > 0) || valor > 1000000 || Math.abs(valor * 100 - Math.round(valor * 100)) > 1e-6) {
      campo.setAttribute("aria-invalid", "true");
      primerError = primerError || campo;
      return;
    }
    lineas.push({ id_tipo_residuo: parseInt(campo.dataset.tipo, 10), kg: valor });
  });
  if (primerError) {
    avisoPaginaError("Los kg tienen que ser un número mayor que 0, con hasta 2 decimales.");
    primerError.focus();
    return;
  }
  if (lineas.length === 0) {
    avisoPaginaError("Anotá los kg de al menos un tipo de residuo.");
    return;
  }
  const seguro = await confirmar({
    titulo: "Confirmar la recepción",
    mensaje: "Se suman " + lineas.length + (lineas.length === 1 ? " tipo" : " tipos") + " al stock del centro. Si después hay un error, se corrige con un movimiento inverso: no se borra.",
    textoBoton: "Confirmar",
  });
  if (!seguro) {
    return;
  }
  const boton = bloquearEnvio(form, "Guardando...");
  try {
    const { ok, resultado } = await apiPost(API.gestion + "/recepciones/" + idRecoleccion, {
      lineas: lineas, observacion: form.elements.observacion.value.trim(),
    });
    if (ok) {
      dibujar(resultado.datos);
      avisoPagina(resultado.mensaje + "." + (resultado.aviso ? " " + resultado.aviso : ""));
    } else {
      liberarEnvio(boton);
      avisoPaginaError(resultado.mensaje || "No se pudo registrar la recepción.");
    }
  } catch (e) {
    liberarEnvio(boton);
    avisoPaginaError("No se pudo conectar con el servidor. Revisá que esté corriendo.");
  }
});

document.getElementById("detalle").addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    window.location.reload();
  }
});

document.getElementById("mensaje-pagina").tabIndex = -1;
cargar();
