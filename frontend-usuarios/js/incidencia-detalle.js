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
    "</dl>" +
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

function formAccion(accion, titulo, contenido, textoBoton, clase) {
  return '<form class="formulario form-accion" data-accion="' + accion + '" id="form-' + accion + '">' +
    '<h3 class="subtitulo-panel">' + escaparHtml(titulo) + "</h3>" +
    contenido +
    '<button type="submit" class="boton ' + (clase || "boton-secundario") + '">' + escaparHtml(textoBoton) + "</button>" +
    "</form>";
}

function opcionesPrioridad(actual) {
  return ["alta", "media", "baja"].map(function (valor) {
    return '<option value="' + valor + '"' + (valor === actual ? " selected" : "") + ">" +
           escaparHtml(etiquetaIncidencia("prioridad", valor).texto) + "</option>";
  }).join("");
}

function accionesDelAdministrador(i) {
  return formAccion("asignacion", i.id_cuadrilla ? "Reasignar la cuadrilla" : "Asignar una cuadrilla",
      '<label>Cuadrilla disponible *<select name="id_cuadrilla" required disabled>' +
      '<option value="">Cargando cuadrillas...</option></select></label>' +
      '<p class="ayuda-campo" id="ayuda-cuadrillas">Solo se ofrecen las cuadrillas activas que no están en una recolección.</p>',
      i.id_cuadrilla ? "Reasignar" : "Asignar", "boton-primario") +
    formAccion("prioridad", "Prioridad",
      '<label>Nueva prioridad<select name="prioridad">' + opcionesPrioridad(i.prioridad) + "</select></label>",
      "Cambiar la prioridad") +
    formAccion("resolucion", "Cerrar como resuelta",
      campoObservacion("observacion", "Qué se hizo", false, "Opcional"),
      "Marcar como resuelta") +
    formAccion("descarte", "Descartar",
      campoObservacion("observacion", "Motivo", true, "Por ejemplo: reporte duplicado o problema inexistente"),
      "Descartar la incidencia", "boton-peligro");
}

function accionesDeLaCuadrilla(i) {
  if (i.estado !== "en_curso") {
    return "";
  }
  return formAccion("avance", "Registrar un avance",
      campoObservacion("observacion", "Qué se hizo hasta ahora", true, "Por ejemplo: se pidió una tapa al depósito"),
      "Registrar el avance", "boton-primario") +
    formAccion("resolucion", "Cerrar como resuelta",
      campoObservacion("observacion", "Qué se hizo", false, "Opcional"),
      "Marcar como resuelta");
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

  if (abierta && rol === "Administrador") {
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

function dibujar(i) {
  incidenciaActual = i;
  document.title = i.numero + " - SiGeRU";
  document.getElementById("titulo-pagina").textContent = "Incidencia " + i.numero;
  document.getElementById("bajada-pagina").textContent =
    textoTipoProblema(i.tipo_problema) + " en " + i.contenedor + " · " + i.direccion;
  dibujarDatos(i);
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
  try {
    const { ok, resultado } = await pedido.enviar(pedido.url, pedido.datos);
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
