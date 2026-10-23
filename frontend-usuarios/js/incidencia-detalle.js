Sesion.exigir();

const idIncidencia = parametroEntero("id");
const zonaDatos = document.getElementById("zona-datos");
const zonaHistorial = document.getElementById("zona-historial");
const zonaAcciones = document.getElementById("zona-acciones");
const panelAcciones = document.getElementById("panel-acciones");

const LISTA_POR_ROL = {
  Administrador: "incidencias.html",
  Cuadrilla: "mis-incidencias.html",
  Vecino: "mis-reclamos.html",
};

let incidenciaActual = null;

function rolActual() {
  return document.body.dataset.rol || Sesion.rol() || "";
}

function prepararVolver() {
  document.getElementById("enlace-volver").href = LISTA_POR_ROL[rolActual()] || "dashboard.html";
}

function filaFicha(titulo, valorHtml) {
  return "<div><dt>" + escaparHtml(titulo) + "</dt><dd>" + valorHtml + "</dd></div>";
}

function dibujarDatos(i) {
  const verFicha = rolActual() === "Administrador" || rolActual() === "Cuadrilla";
  const contenedor = verFicha
    ? '<a href="contenedor.html?id=' + $entero(i.id_contenedor) + '">' + escaparHtml(i.contenedor) + "</a>"
    : escaparHtml(i.contenedor);

  zonaDatos.innerHTML = '<dl class="ficha ficha-columnas">' +
    filaFicha("Estado", marcaDeEstado("estado", i.estado)) +
    filaFicha("Prioridad", marcaDeEstado("prioridad", i.prioridad)) +
    filaFicha("Tipo de problema", escaparHtml(textoTipoProblema(i.tipo_problema))) +
    filaFicha("Cuadrilla", escaparHtml(i.cuadrilla || "Sin asignar")) +
    filaFicha("Contenedor", contenedor) +
    filaFicha("Estado del contenedor", escaparHtml(textoEstado(i.estado_contenedor))) +
    filaFicha("Dirección", escaparHtml(i.direccion)) +
    filaFicha("Zona", escaparHtml(i.zona)) +
    filaFicha("Registrada", escaparHtml(fechaLegible(i.fecha_reporte))) +
    (i.fecha_resolucion ? filaFicha("Cerrada", escaparHtml(fechaLegible(i.fecha_resolucion))) : "") +
    (i.reportada_por ? filaFicha("Informada por", escaparHtml(i.reportada_por)) : "") +
    (i.derivada ? filaFicha("Mantenimiento", escaparHtml("Derivada el " + fechaLegible(i.derivada_en) + (i.derivada_nota ? ": " + i.derivada_nota : ""))) : "") +
    "</dl>" +
    (i.de_mantenimiento && !i.derivada && (i.estado === "abierta" || i.estado === "en_curso")
      ? '<p class="ayuda">Un contenedor roto o dañado no lo recolecta el camión: el administrador tiene que derivarlo a mantenimiento.</p>' : "") +
    '<h3 class="subtitulo-panel">Descripción</h3>' +
    '<p class="texto-largo">' + escaparHtml(i.descripcion) + "</p>" +
    (i.tiene_foto
      ? '<h3 class="subtitulo-panel">Foto</h3>' +
        '<a href="' + API.gestion + "/incidencias/" + $entero(i.id_incidencia) + '/foto" target="_blank" rel="noopener">' +
        '<img class="foto-incidencia" src="' + API.gestion + "/incidencias/" + $entero(i.id_incidencia) + '/foto" ' +
        'alt="Foto del problema, enviada por quien lo informó"></a>'
      : "");
}

function campoObservacion(nombre, etiqueta, obligatoria, ayuda) {
  return "<label>" + escaparHtml(etiqueta) + (obligatoria ? " *" : "") +
    '<textarea name="' + nombre + '" rows="3" maxlength="300"' + (obligatoria ? " required" : "") +
    ' placeholder="' + escaparHtml(ayuda) + '"></textarea></label>';
}

// Cada accion es un bloque plegable: solo queda abierta la que corresponde al estado de la incidencia.
function formAccion(accion, titulo, contenido, textoBoton, clase, abierta) {
  return '<details class="accion-plegable"' + (abierta ? " open" : "") + "><summary>" + escaparHtml(titulo) + "</summary>" +
    '<form class="formulario form-accion" data-accion="' + accion + '" id="form-' + accion + '">' +
    contenido +
    '<button type="submit" class="boton ' + (clase || "boton-secundario") + '">' + escaparHtml(textoBoton) + "</button>" +
    "</form></details>";
}

function opcionesPrioridad(actual) {
  return ["alta", "media", "baja"].map(function (valor) {
    return '<option value="' + valor + '"' + (valor === actual ? " selected" : "") + ">" +
           escaparHtml(etiquetaIncidencia("prioridad", valor).texto) + "</option>";
  }).join("");
}

function accionesDelAdministrador(i) {
  const conCuadrilla = Boolean(i.id_cuadrilla);
  // Roto o dañado: no se asigna a una cuadrilla, se deriva a mantenimiento (otra gente) y se cierra cuando lo arreglan.
  if (i.de_mantenimiento) {
    const derivar = i.derivada
      ? '<p class="ayuda">Derivada a mantenimiento el ' + escaparHtml(fechaLegible(i.derivada_en)) + (i.derivada_nota ? ": " + escaparHtml(i.derivada_nota) : "") +
        ". Cuando lo arreglen, cerrala como resuelta: el contenedor vuelve a estar disponible.</p>"
      : formAccion("derivacion", "Derivar a mantenimiento",
          '<p class="ayuda-campo">Un contenedor roto o dañado no lo recolecta el camión: avisá a mantenimiento y anotá a quién llamaste.</p>' +
          campoObservacion("observacion", "A quién se llamó (opcional)", false, "Por ejemplo: Mantenimiento de la Intendencia, reclamo 1234"),
          "Derivar a mantenimiento", "boton-primario", true);
    const prioridadM = formAccion("prioridad", "Cambiar la prioridad",
      '<label>Nueva prioridad<select name="prioridad">' + opcionesPrioridad(i.prioridad) + "</select></label>", "Cambiar la prioridad", "boton-secundario", false);
    const resolverM = formAccion("resolucion", "Cerrar como resuelta", campoObservacion("observacion", "Qué se hizo", false, "Opcional"),
      "Marcar como resuelta", i.derivada ? "boton-primario" : "boton-secundario", Boolean(i.derivada));
    const descartarM = formAccion("descarte", "Descartar", campoObservacion("observacion", "Motivo", true, "Por ejemplo: reporte duplicado o problema inexistente"),
      "Descartar la incidencia", "boton-peligro", false);
    return derivar + resolverM + prioridadM + descartarM;
  }
  const asignar = formAccion("asignacion", conCuadrilla ? "Reasignar la cuadrilla" : "Asignar una cuadrilla",
    '<label>Cuadrilla disponible *<select name="id_cuadrilla" required disabled>' +
    '<option value="">Cargando cuadrillas...</option></select></label>' +
    '<p class="ayuda-campo" id="ayuda-cuadrillas">Solo se ofrecen las cuadrillas activas que no están en una recolección.</p>',
    conCuadrilla ? "Reasignar" : "Asignar", "boton-primario", !conCuadrilla);
  const prioridad = formAccion("prioridad", "Cambiar la prioridad",
    '<label>Nueva prioridad<select name="prioridad">' + opcionesPrioridad(i.prioridad) + "</select></label>",
    "Cambiar la prioridad", "boton-secundario", false);
  const resolver = formAccion("resolucion", "Cerrar como resuelta",
    campoObservacion("observacion", "Qué se hizo", false, "Opcional"),
    "Marcar como resuelta", "boton-primario", conCuadrilla);
  const descartar = formAccion("descarte", "Descartar",
    campoObservacion("observacion", "Motivo", true, "Por ejemplo: reporte duplicado o problema inexistente"),
    "Descartar la incidencia", "boton-peligro", false);
  // Sin cuadrilla lo primero es asignar; con una atendiendo lo primero es cerrar.
  return conCuadrilla ? resolver + asignar + prioridad + descartar : asignar + prioridad + resolver + descartar;
}

function accionesDeLaCuadrilla(i) {
  if (i.estado !== "en_curso") {
    return "";
  }
  return formAccion("avance", "Registrar un avance",
      campoObservacion("observacion", "Qué se hizo hasta ahora", true, "Por ejemplo: se pidió una tapa al depósito"),
      "Registrar el avance", "boton-primario", true) +
    formAccion("resolucion", "Cerrar como resuelta",
      campoObservacion("observacion", "Qué se hizo", false, "Opcional"),
      "Marcar como resuelta", "boton-secundario", false);
}

function dibujarAcciones(i) {
  const rol = rolActual();
  const abierta = i.estado === "abierta" || i.estado === "en_curso";
  let html = "";

  if (!abierta) {
    html = '<p class="ayuda">La incidencia está ' + escaparHtml(etiquetaIncidencia("estado", i.estado).texto.toLowerCase()) +
           " y ya no admite cambios. Su historial queda guardado.</p>";
  } else if (rol === "Administrador") {
    html = accionesDelAdministrador(i);
  } else if (rol === "Cuadrilla") {
    html = accionesDeLaCuadrilla(i);
  }

  panelAcciones.classList.toggle("oculto", html === "");
  zonaAcciones.innerHTML = html;
  zonaAcciones.querySelectorAll("form").forEach(activarValidacion);

  if (abierta && rol === "Administrador" && document.getElementById("form-asignacion")) {
    cargarCuadrillas(i);
  }
}

async function cargarCuadrillas(i) {
  const form = document.getElementById("form-asignacion");
  const selector = form.elements.id_cuadrilla;
  const boton = form.querySelector('button[type="submit"]');
  const ayuda = document.getElementById("ayuda-cuadrillas");

  try {
    const respuesta = await apiGet(API.recoleccion + "/cuadrillas?disponibles=1");
    const cuadrillas = (respuesta.datos || []).filter(function (c) {
      return c.id_cuadrilla !== i.id_cuadrilla;
    });

    if (cuadrillas.length === 0) {
      selector.innerHTML = '<option value="">No hay cuadrillas disponibles</option>';
      boton.disabled = true;
      ayuda.textContent = "Todas las cuadrillas activas están en una recolección o ya tienen esta incidencia. Probá más tarde.";
      return;
    }

    selector.innerHTML = '<option value="">Elegí una cuadrilla</option>' + cuadrillas.map(function (c) {
      const carga = c.incidencias_en_curso === 1 ? "1 incidencia en curso" : c.incidencias_en_curso + " incidencias en curso";
      return '<option value="' + $entero(c.id_cuadrilla) + '">' +
             escaparHtml(c.nombre + " · turno " + c.turno + " · " + carga) + "</option>";
    }).join("");
    selector.disabled = false;
  } catch (error) {
    selector.innerHTML = '<option value="">No se pudieron cargar</option>';
    boton.disabled = true;
    ayuda.textContent = mensajeDeFallo(error);
  }
}

const ETIQUETAS_PUNTAJE = { 1: "Muy mala", 2: "Mala", 3: "Regular", 4: "Buena", 5: "Excelente" };

function estrellas(n) {
  return "★".repeat(n) + "☆".repeat(5 - n);
}

// Calificacion de la atencion (extras, parte 14): el vecino que informo califica un reclamo resuelto, una sola vez.
function dibujarCalificacion(i) {
  const panel = document.getElementById("panel-calificacion");
  const zona = document.getElementById("zona-calificacion");
  const rol = rolActual();
  let html = "";
  if (i.calificacion) {
    html = '<p class="calificacion-valor" aria-label="' + i.calificacion.puntaje + ' de 5">' + estrellas(i.calificacion.puntaje) +
      " <strong>" + i.calificacion.puntaje + " de 5</strong> · " + escaparHtml(ETIQUETAS_PUNTAJE[i.calificacion.puntaje]) + "</p>" +
      (i.calificacion.comentario ? '<p class="texto-largo">«' + escaparHtml(i.calificacion.comentario) + "»</p>" : "") +
      '<p class="texto-tenue">Calificado el ' + escaparHtml(fechaLegible(i.calificacion.fecha_hora)) + ".</p>";
  } else if (i.puede_calificar) {
    html = '<form class="formulario" id="form-calificacion" novalidate>' +
      '<p class="ayuda">Tu reclamo se resolvió. Contanos cómo fue la atención: sumás ' + 5 + ' puntos. Podés calificar hasta el ' +
      escaparHtml(fechaLegible(i.calificar_hasta).slice(0, 10)) + " y no se puede cambiar después.</p>" +
      '<fieldset class="grupo-radios"><legend>¿Cómo fue la atención?</legend>' +
      [1, 2, 3, 4, 5].map(function (n) {
        return '<label class="radio-puntaje"><input type="radio" name="puntaje" value="' + n + '"> ' + n + " · " + ETIQUETAS_PUNTAJE[n] + "</label>";
      }).join("") + "</fieldset>" +
      '<label>Comentario (opcional)<textarea name="comentario" rows="2" maxlength="300" placeholder="Qué estuvo bien o qué se puede mejorar"></textarea></label>' +
      '<button type="submit" class="boton boton-primario">Enviar la calificación</button></form>';
  } else if (i.estado === "resuelta" && rol === "Vecino") {
    html = '<p class="texto-tenue">El plazo para calificar este reclamo (30 días desde que se resolvió) venció.</p>';
  } else if (i.estado === "resuelta") {
    html = '<p class="texto-tenue">El vecino todavía no calificó la atención.</p>';
  }
  panel.classList.toggle("oculto", html === "");
  zona.innerHTML = html;
}

function dibujar(i) {
  incidenciaActual = i;
  document.title = i.numero + " - SiGeRU";
  document.getElementById("titulo-pagina").textContent = "Incidencia " + i.numero;
  document.getElementById("bajada-pagina").textContent =
    textoTipoProblema(i.tipo_problema) + " en " + i.contenedor + " · " + i.direccion;
  dibujarDatos(i);
  dibujarCalificacion(i);
  dibujarAcciones(i);
  zonaHistorial.innerHTML = lineaDeTiempo(i.historial);
}

function mostrarFallo(titulo, detalle, conReintento) {
  document.getElementById("detalle").innerHTML =
    '<section class="panel">' + bloqueEstado(titulo, detalle, conReintento) + "</section>";
}

async function cargarIncidencia() {
  if (idIncidencia === null) {
    mostrarFallo("Falta indicar la incidencia", "Entrá desde la lista para abrir una incidencia.", false);
    return;
  }
  try {
    const respuesta = await apiGet(API.gestion + "/incidencias/" + idIncidencia);
    dibujar(respuesta.datos);
  } catch (error) {
    if (error.estado === 404) {
      mostrarFallo("No encontramos esa incidencia", "Puede que no exista o que no esté a tu cargo.", false);
    } else {
      mostrarFallo("No se pudo cargar la incidencia", mensajeDeFallo(error), true);
    }
  }
}

const CONFIRMACIONES = {
  derivacion: {
    titulo: "Derivar a mantenimiento",
    mensaje: "El contenedor pasa a «en mantenimiento» y sale de las rutas como «no se recolecta». Cuando resuelvas la incidencia vuelve a estar disponible. Se le avisa a quien la informó.",
    textoBoton: "Derivar",
    peligro: false,
  },
  resolucion: {
    titulo: "Marcar como resuelta",
    mensaje: "La incidencia se cierra y ya no admite cambios. Si el contenedor estaba lleno o en mantenimiento por esto, vuelve a disponible.",
    textoBoton: "Marcar como resuelta",
  },
  descarte: {
    titulo: "Descartar la incidencia",
    mensaje: "La incidencia se cierra como descartada y ya no admite cambios. El motivo queda en el historial.",
    textoBoton: "Descartar",
  },
};

function pedidoDe(accion, form) {
  const base = API.gestion + "/incidencias/" + idIncidencia;
  if (accion === "prioridad") {
    return { enviar: apiPut, url: base, datos: { prioridad: form.elements.prioridad.value } };
  }
  if (accion === "asignacion") {
    return { enviar: apiPost, url: base + "/asignacion", datos: { id_cuadrilla: parseInt(form.elements.id_cuadrilla.value, 10) } };
  }
  if (accion === "derivacion") {
    return { enviar: apiPost, url: base + "/derivacion", datos: { nota: form.elements.observacion.value.trim() } };
  }
  return { enviar: apiPost, url: base + "/" + accion, datos: { observacion: form.elements.observacion.value.trim() } };
}

zonaAcciones.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  const form = evento.target;
  const accion = form.dataset.accion;

  if (!validarFormulario(form)) {
    return;
  }
  if (accion === "prioridad" && form.elements.prioridad.value === incidenciaActual.prioridad) {
    avisoPaginaError("La prioridad ya es esa: elegí otra para cambiarla.");
    return;
  }
  if (CONFIRMACIONES[accion] && !(await confirmar(CONFIRMACIONES[accion]))) {
    return;
  }

  const pedido = pedidoDe(accion, form);
  const boton = bloquearEnvio(form, "Guardando...");
  // El avance de la Cuadrilla lleva una clave unica (un reenvio no lo duplica) y, sin red, se guarda en el telefono.
  const clave = accion === "avance" && typeof ColaOffline !== "undefined" && ColaOffline.disponible() ? ColaOffline.nuevaClave() : null;
  try {
    const { ok, resultado } = clave ? await apiPost(pedido.url, pedido.datos, { "Idempotency-Key": clave }) : await pedido.enviar(pedido.url, pedido.datos);
    if (ok) {
      dibujar(resultado.datos);
      avisoPagina((resultado.mensaje || "Cambio guardado") + ".");
      document.getElementById("mensaje-pagina").focus();
    } else {
      liberarEnvio(boton);
      avisoPaginaError(resultado.mensaje || "No se pudo guardar el cambio.");
    }
  } catch (e) {
    liberarEnvio(boton);
    if (clave && ColaOffline.esFalloDeRed(e)) {
      try {
        await ColaOffline.encolar({ tipo: "avance", url: pedido.url, cuerpo: pedido.datos, clave: clave, etiqueta: "Avance de la incidencia " + idIncidencia + ": " + String(pedido.datos.observacion).slice(0, 60) });
        form.reset();
        avisoPagina("Sin conexión: el avance quedó guardado en este teléfono y se envía solo cuando vuelva la señal");
        return;
      } catch (guardado) { /* no se pudo guardar: se avisa abajo */ }
    }
    avisoPaginaError("No se pudo conectar con el servidor. Revisá que esté corriendo.");
  }
});

// Cuando la cola envia algo, se vuelve a leer la incidencia para que el avance aparezca en el historial.
document.addEventListener("sigeru:cola-enviada", function () { cargarIncidencia(); });

document.getElementById("zona-calificacion").addEventListener("submit", async function (evento) {
  evento.preventDefault();
  const form = evento.target;
  const elegido = form.querySelector('input[name="puntaje"]:checked');
  if (!elegido) {
    avisoPaginaError("Elegí un puntaje de 1 a 5.");
    form.querySelector('input[name="puntaje"]').focus();
    return;
  }
  const boton = bloquearEnvio(form, "Enviando...");
  try {
    const { ok, resultado } = await apiPost(API.gestion + "/incidencias/" + idIncidencia + "/calificacion", {
      puntaje: parseInt(elegido.value, 10), comentario: form.elements.comentario.value.trim(),
    });
    if (ok) {
      await cargarIncidencia();
      avisoPagina((resultado.mensaje || "Calificación enviada") + ".");
    } else {
      liberarEnvio(boton);
      avisoPaginaError(resultado.mensaje || "No se pudo enviar la calificación.");
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
prepararVolver();
cargarIncidencia();
