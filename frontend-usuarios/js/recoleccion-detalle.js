Sesion.exigir();

const idRecoleccion = parametroEntero("id");
const zonaDatos = document.getElementById("zona-datos");
const zonaAcciones = document.getElementById("zona-acciones");
const panelAcciones = document.getElementById("panel-acciones");
const cuerpoVaciados = document.getElementById("cuerpo-vaciados");

const LISTA_POR_ROL = {
  Administrador: "recolecciones.html",
  Cuadrilla: "mis-tareas.html",
};

let recoleccionActual = null;
let sugerenciasCargadas = false;

function rolActual() {
  return document.body.dataset.rol || Sesion.rol() || "";
}

function filaFicha(titulo, valorHtml) {
  return "<div><dt>" + escaparHtml(titulo) + "</dt><dd>" + valorHtml + "</dd></div>";
}

function kg(valor) {
  return valor === null || valor === undefined
    ? "-"
    : Number(valor).toLocaleString("es-UY", { maximumFractionDigits: 2 }) + " kg";
}

function dibujarDatos(r) {
  const planificada = r.estado === "planificada";
  zonaDatos.innerHTML = '<dl class="ficha ficha-columnas">' +
    filaFicha("Estado", marcaDeEstado("recoleccion", r.estado)) +
    filaFicha(planificada ? "Inicio previsto" : "Inicio", escaparHtml(fechaLegible(r.fecha_hora_inicio))) +
    (r.fecha_hora_fin ? filaFicha("Fin", escaparHtml(fechaLegible(r.fecha_hora_fin))) : "") +
    filaFicha("Ruta", escaparHtml(r.ruta)) +
    filaFicha("Zona", escaparHtml(r.zona)) +
    filaFicha("Camión", escaparHtml(r.matricula) + " " + marcaDeEstado("camion", r.estado_camion)) +
    filaFicha("Cuadrilla", escaparHtml(r.cuadrilla)) +
    filaFicha("Centro de destino", escaparHtml(r.centro || "Sin definir")) +
    filaFicha("Vaciados", escaparHtml(r.vaciados + (r.vaciados === 1 ? " contenedor · " : " contenedores · ") + kg(r.peso_total_kg))) +
    "</dl>" +
    (r.observaciones
      ? '<h3 class="subtitulo-panel">Observaciones</h3><p class="texto-largo">' + escaparHtml(r.observaciones) + "</p>"
      : "");
}

function dibujarVaciados(vaciados) {
  if (!vaciados || vaciados.length === 0) {
    cuerpoVaciados.innerHTML = filaVacia(5, "Todavía no hay vaciados",
      recoleccionActual.estado === "en_curso" ? "Registrá cada contenedor a medida que se vacía." : "");
    return;
  }
  cuerpoVaciados.innerHTML = vaciados.map(function (v) {
    return '<tr role="row">' +
      celda("Contenedor", v.contenedor) +
      celda("Dirección", v.direccion) +
      celda("Hora", fechaLegible(v.fecha_hora).slice(11) || "-") +
      celda("Peso", kg(v.peso_kg)) +
      celda("Volumen", v.volumen_l === null ? null : Number(v.volumen_l).toLocaleString("es-UY") + " L") +
      "</tr>";
  }).join("");
}

function formAccion(accion, titulo, contenidoHtml, textoBoton, clase) {
  return '<form class="formulario form-accion" data-accion="' + accion + '" id="form-' + accion + '">' +
    '<h3 class="subtitulo-panel">' + escaparHtml(titulo) + "</h3>" + contenidoHtml +
    '<button type="submit" class="boton ' + (clase || "boton-secundario") + '">' + escaparHtml(textoBoton) + "</button>" +
    "</form>";
}

function campoTexto(nombre, etiqueta, obligatorio, ayuda) {
  return "<label>" + escaparHtml(etiqueta) + (obligatorio ? " *" : "") +
    '<textarea name="' + nombre + '" rows="2" maxlength="300"' + (obligatorio ? " required" : "") +
    ' placeholder="' + escaparHtml(ayuda) + '"></textarea></label>';
}

function formVaciado() {
  return formAccion("vaciados", "Registrar un vaciado",
    '<label>Contenedor *<input type="text" name="codigo_contenedor" list="lista-contenedores" required ' +
    'autocomplete="off" spellcheck="false" maxlength="20" placeholder="CNT-004"></label>' +
    '<div class="formulario-columnas">' +
    '<label>Peso (kg) *<input type="number" name="peso_kg" required min="0.01" max="5000" step="0.01" inputmode="decimal"></label>' +
    '<label>Volumen (L)<input type="number" name="volumen_l" min="0" step="0.01" inputmode="decimal" placeholder="Opcional"></label>' +
    "</div>",
    "Registrar el vaciado", "boton-primario");
}

function dibujarAcciones(r) {
  const rol = rolActual();
  const esAdmin = rol === "Administrador";
  let html = "";

  if (r.estado === "planificada") {
    html = formAccion("inicio", "Salir a recolectar",
        '<p class="ayuda">El camión ' + escaparHtml(r.matricula) + " pasa a «en ruta» y la hora de inicio queda registrada.</p>",
        "Iniciar la recolección", "boton-primario") +
      (esAdmin ? formAccion("cancelacion", "Cancelar", campoTexto("observacion", "Motivo", true, "Por ejemplo: se reprograma"), "Cancelar la recolección", "boton-peligro") : "");
  } else if (r.estado === "en_curso") {
    html = formVaciado() +
      formAccion("fin", "Terminar el recorrido", campoTexto("observacion", "Observaciones", false, "Opcional"), "Finalizar la recolección") +
      (esAdmin ? formAccion("cancelacion", "Cancelar", campoTexto("observacion", "Motivo", true, "Por ejemplo: se rompió el camión"), "Cancelar la recolección", "boton-peligro") : "");
  } else {
    html = '<p class="ayuda">La recolección está ' + escaparHtml(etiquetaIncidencia("recoleccion", r.estado).texto.toLowerCase()) +
           " y ya no admite cambios.</p>";
  }

  panelAcciones.classList.remove("oculto");
  zonaAcciones.innerHTML = html;
  zonaAcciones.querySelectorAll("form").forEach(activarValidacion);
  if (r.estado === "en_curso" && !sugerenciasCargadas) {
    cargarSugerencias();
  }
}

async function cargarSugerencias() {
  try {
    const respuesta = await apiGet(API.gestion + "/contenedores");
    document.getElementById("lista-contenedores").innerHTML = (respuesta.datos || [])
      .filter(function (c) { return c.estado !== "retirado"; })
      .map(function (c) {
        return '<option value="' + escaparHtml(c.codigo) + '">' + escaparHtml(c.direccion + (c.zona ? " · " + c.zona : "")) + "</option>";
      }).join("");
    sugerenciasCargadas = true;
  } catch (error) {
    sugerenciasCargadas = false;
  }
}

function dibujar(r) {
  recoleccionActual = r;
  document.title = r.numero + " - SiGeRU";
  document.getElementById("titulo-pagina").textContent = "Recolección " + r.numero;
  document.getElementById("bajada-pagina").textContent = r.ruta + " · " + r.zona + " · " + fechaLegible(r.fecha_hora_inicio);
  dibujarDatos(r);
  dibujarVaciados(r.detalle_vaciados);
  dibujarAcciones(r);
}

function mostrarFallo(titulo, detalle, conReintento) {
  document.getElementById("detalle").innerHTML =
    '<section class="panel">' + bloqueEstado(titulo, detalle, conReintento) + "</section>";
}

async function cargarRecoleccion() {
  if (idRecoleccion === null) {
    mostrarFallo("Falta indicar la recolección", "Entrá desde la lista para abrir una recolección.", false);
    return;
  }
  try {
    const respuesta = await apiGet(API.recoleccion + "/recolecciones/" + idRecoleccion);
    dibujar(respuesta.datos);
  } catch (error) {
    if (error.estado === 404) {
      mostrarFallo("No encontramos esa recolección", "Puede que no exista o que no sea de tu cuadrilla.", false);
    } else {
      mostrarFallo("No se pudo cargar la recolección", mensajeDeFallo(error), true);
    }
  }
}

const CONFIRMACIONES = {
  fin: {
    titulo: "Finalizar la recolección",
    mensaje: "Se registra la hora de fin, el camión vuelve a «operativo» y ya no se pueden agregar vaciados.",
    textoBoton: "Finalizar",
  },
  cancelacion: {
    titulo: "Cancelar la recolección",
    mensaje: "Se cancela y ya no admite cambios. Si el camión estaba en ruta por esta recolección, vuelve a «operativo».",
    textoBoton: "Cancelar la recolección",
  },
};

function campoDelError(form, texto) {
  const minusculas = (texto || "").toLowerCase();
  const claves = [["volumen", "volumen_l"], ["peso", "peso_kg"], ["contenedor", "codigo_contenedor"], ["motivo", "observacion"]];
  const encontrada = claves.find(function (par) { return minusculas.indexOf(par[0]) !== -1 && form.elements[par[1]]; });
  return encontrada ? form.elements[encontrada[1]] : null;
}

function datosDe(accion, form) {
  if (accion === "vaciados") {
    const datos = {
      codigo_contenedor: form.elements.codigo_contenedor.value.trim().toUpperCase(),
      peso_kg: form.elements.peso_kg.value,
    };
    if (form.elements.volumen_l.value !== "") {
      datos.volumen_l = form.elements.volumen_l.value;
    }
    return datos;
  }
  if (form.elements.observacion) {
    return { observacion: form.elements.observacion.value.trim() };
  }
  return {};
}

zonaAcciones.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  const form = evento.target;
  const accion = form.dataset.accion;

  if (!validarFormulario(form)) {
    return;
  }
  if (CONFIRMACIONES[accion] && !(await confirmar(CONFIRMACIONES[accion]))) {
    return;
  }

  const boton = bloquearEnvio(form, "Guardando...");
  // El vaciado lleva una clave unica: si la respuesta se pierde, reenviarlo no lo carga dos veces. Si no hay red y es la Cuadrilla, se guarda en el telefono.
  const urlAccion = API.recoleccion + "/recolecciones/" + idRecoleccion + "/" + accion;
  const datosAccion = datosDe(accion, form);
  const clave = accion === "vaciados" && typeof ColaOffline !== "undefined" ? ColaOffline.nuevaClave() : null;
  try {
    const { ok, resultado } = await apiPost(urlAccion, datosAccion, clave ? { "Idempotency-Key": clave } : undefined);
    if (ok) {
      dibujar(resultado.datos);
      avisoPagina((resultado.mensaje || "Cambio guardado") + ".");
      if (accion === "vaciados") {
        document.querySelector("#form-vaciados input").focus();
      } else {
        document.getElementById("mensaje-pagina").focus();
      }
    } else {
      liberarEnvio(boton);
      const texto = resultado.mensaje || "No se pudo guardar el cambio.";
      const campo = campoDelError(form, texto);
      if (campo) {
        marcarError(campo, texto);
        campo.focus();
      } else {
        avisoPaginaError(texto);
      }
    }
  } catch (e) {
    liberarEnvio(boton);
    if (clave && ColaOffline.disponible() && ColaOffline.esFalloDeRed(e)) {
      try {
        await ColaOffline.encolar({ tipo: "vaciado", url: urlAccion, cuerpo: datosAccion, clave: clave, etiqueta: "Vaciado de " + datosAccion.codigo_contenedor + " (" + datosAccion.peso_kg + " kg) en la recolección " + idRecoleccion });
        form.reset();
        avisoPagina("Sin conexión: el vaciado quedó guardado en este teléfono y se envía solo cuando vuelva la señal");
        return;
      } catch (guardado) { /* no se pudo guardar: se avisa abajo */ }
    }
    avisoPaginaError("No se pudo conectar con el servidor. Revisá que esté corriendo.");
  }
});

// Cuando la cola envia algo, se vuelve a leer la recoleccion para que el vaciado aparezca en la lista.
document.addEventListener("sigeru:cola-enviada", function () { cargarRecoleccion(); });

document.getElementById("detalle").addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    window.location.reload();
  }
});

document.getElementById("mensaje-pagina").tabIndex = -1;
document.getElementById("enlace-volver").href = LISTA_POR_ROL[rolActual()] || "dashboard.html";
cargarRecoleccion();
