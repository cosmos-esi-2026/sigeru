Sesion.exigir();

const COLUMNAS = 8;
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formRuta = document.getElementById("form-ruta");
const mensaje = document.getElementById("mensaje");
const modalRuta = document.getElementById("modal-ruta");
const TEXTO_DIA = { lunes: "Lunes", martes: "Martes", miercoles: "Miércoles", jueves: "Jueves", viernes: "Viernes", sabado: "Sábado", domingo: "Domingo" };

const TOPE_POR_RUTA = 25;

let idEditando = null;

function textoTrazado(r) {
  if (r.cantidad < 2) {
    return "Faltan contenedores";
  }
  return r.trazado_tipo === "calles" ? "Por las calles" : (r.trazado_tipo === "recta" ? "En línea recta" : "Sin trazar");
}

// Armar lleva a la pantalla del mapa; editar cambia nombre, dia, hora y zona; dar de baja la deja inactiva; borrar la elimina de verdad (solo si nunca se uso).
function celdaAccionesRuta(r) {
  const icono = function (trazos) {
    return '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + trazos + "</svg>";
  };
  return '<td role="cell" data-etiqueta="Acciones"><div class="acciones-fila acciones-fila-ancha">' +
    '<a class="boton boton-secundario" href="ruta-armar.html?id=' + $entero(r.id_ruta) + '" aria-label="Armar ' + escaparHtml(r.nombre) + ' en el mapa">Armar</a>' +
    (r.estado === "activa" && r.cantidad > 0 ? '<a class="boton boton-secundario" href="asignacion.html?ruta=' + $entero(r.id_ruta) + '" aria-label="Asignar ' + escaparHtml(r.nombre) + ' a una cuadrilla">Asignar</a>' : "") +
    '<button type="button" class="boton boton-icono js-editar" data-id="' + $entero(r.id_ruta) + '" aria-label="Editar ' + escaparHtml(r.nombre) + '" title="Editar ' + escaparHtml(r.nombre) + '">' +
    icono('<path d="M4 20h4l10-10a2.8 2.8 0 0 0-4-4L4 16z"/><path d="M13.5 6.5l4 4"/>') + "</button>" +
    (r.estado === "activa" ? '<button type="button" class="boton boton-icono js-baja" data-id="' + $entero(r.id_ruta) + '" aria-label="Dar de baja ' + escaparHtml(r.nombre) + '" title="Dar de baja ' + escaparHtml(r.nombre) + '">' +
      icono('<path d="M5 12h14"/>') + "</button>" : "") +
    '<button type="button" class="boton boton-icono js-borrar" data-id="' + $entero(r.id_ruta) + '" aria-label="Borrar ' + escaparHtml(r.nombre) + '" title="Borrar ' + escaparHtml(r.nombre) + '">' +
    icono('<path d="M4 7h16"/><path d="M9.5 7V4.5h5V7"/><path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/>') + "</button>" +
    "</div></td>";
}

async function cargarRutas() {
  cuerpoTabla.innerHTML = filaEsqueleto(COLUMNAS, 4);

  try {
    const datos = await apiGet(API.recoleccion + "/rutas");
    dibujarTabla(datos.datos);
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(COLUMNAS, mensajeDeFallo(error));
    actualizarContador();
  }
}

function dibujarTabla(rutas) {
  if (!rutas || rutas.length === 0) {
    cuerpoTabla.innerHTML = filaVacia(
      COLUMNAS,
      "Todavía no hay rutas",
      "Cargá la primera con «Nueva ruta» para poder planificar recolecciones."
    );
    actualizarContador();
    return;
  }

  cuerpoTabla.innerHTML = rutas.map(function (r) {
    return '<tr role="row">' +
      celda("Nombre", r.nombre) +
      celda("Día", TEXTO_DIA[r.dia_semana] || r.dia_semana) +
      celda("Hora", r.hora_inicio) +
      celda("Zona", r.zona) +
      celda("Contenedores", r.cantidad + " de " + TOPE_POR_RUTA, r.cantidad) +
      celda("Trazado", textoTrazado(r)) +
      celdaEstado("Estado", r.estado, r.estado === "activa" ? "verde" : "gris") +
      celdaAccionesRuta(r) +
      "</tr>";
  }).join("");

  actualizarContador();
}

async function cargarZonas() {
  try {
    const zonas = await apiGet(API.gestion + "/zonas");
    formRuta.id_zona.innerHTML = zonas.datos.map(function (z) {
      return '<option value="' + z.id_zona + '">' + escaparHtml(z.nombre) + "</option>";
    }).join("");
  } catch (error) {
    avisoPaginaError("No se pudieron cargar las zonas. " + mensajeDeFallo(error));
  }
}

function prepararAlta() {
  idEditando = null;
  document.getElementById("titulo-modal-ruta").textContent = "Nueva ruta";
  formRuta.querySelector('button[type="submit"]').textContent = "Guardar ruta";
}

function prepararEdicion(r) {
  idEditando = r.id_ruta;
  document.getElementById("titulo-modal-ruta").textContent = "Editar ruta";
  formRuta.querySelector('button[type="submit"]').textContent = "Guardar cambios";

  formRuta.nombre.value = r.nombre || "";
  formRuta.dia_semana.value = r.dia_semana || "lunes";
  formRuta.hora_inicio.value = r.hora_inicio || "06:00";
  formRuta.id_zona.value = r.id_zona || "";
  formRuta.estado.value = r.estado || "activa";
}

formRuta.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrar("", "");

  if (!validarFormulario(formRuta)) {
    return;
  }

  const boton = bloquearEnvio(formRuta, "Guardando...");
  const datos = {
    nombre:      formRuta.nombre.value.trim(),
    dia_semana:  formRuta.dia_semana.value,
    hora_inicio: formRuta.hora_inicio.value,
    id_zona:     formRuta.id_zona.value,
    estado:      formRuta.estado.value,
  };

  try {
    const { ok, resultado } = idEditando === null
      ? await apiPost(API.recoleccion + "/rutas", datos)
      : await apiPut(API.recoleccion + "/rutas/" + idEditando, datos);

    if (ok) {
      cerrarModal("modal-ruta");
      avisoPagina(idEditando === null ? "Ruta creada correctamente." : "Ruta actualizada correctamente.");
      cargarRutas();
    } else {
      const texto = resultado.mensaje || "No se pudo guardar la ruta.";
      if (!marcarErrorDeApi(formRuta, texto)) {
        mostrar(texto, "error");
      }
    }
  } catch (e) {
    mostrar("No se pudo conectar con el servidor. Revisá que esté corriendo.", "error");
  } finally {
    liberarEnvio(boton);
  }
});

cuerpoTabla.addEventListener("click", async function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargarRutas();
    return;
  }

  const botonEditar = evento.target.closest(".js-editar");
  if (botonEditar) {
    const { datos } = await apiGet(API.recoleccion + "/rutas/" + botonEditar.dataset.id);
    prepararEdicion(datos);
    bootstrap.Modal.getOrCreateInstance(modalRuta).show();
    return;
  }

  const botonBorrar = evento.target.closest(".js-borrar");
  if (botonBorrar) {
    const nombreRuta = botonBorrar.closest("tr").cells[0].textContent;
    const seguro = await confirmar({
      titulo: "Borrar la ruta " + nombreRuta,
      mensaje: "Se elimina de verdad, con su lista de contenedores (los contenedores siguen existiendo). Solo se puede si la ruta nunca tuvo una recolección: si ya la tuvo, usá «Dar de baja».",
      textoBoton: "Borrar la ruta",
    });
    if (!seguro) {
      return;
    }
    const { ok, resultado } = await apiDelete(API.recoleccion + "/rutas/" + botonBorrar.dataset.id + "?definitivo=1");
    if (ok) {
      avisoPagina("Ruta borrada.");
      cargarRutas();
    } else {
      avisoPaginaError(resultado.mensaje || "No se pudo borrar la ruta.");
    }
    return;
  }

  const botonBaja = evento.target.closest(".js-baja");
  if (botonBaja) {
    const nombre = botonBaja.closest("tr").cells[0].textContent;

    const seguro = await confirmar({
      titulo: "Dar de baja la ruta " + nombre,
      mensaje: "Pasa a «inactiva» y deja de poder planificarse. No se borra: las recolecciones " +
               "que ya hizo la siguen referenciando.",
      textoBoton: "Dar de baja",
    });
    if (!seguro) {
      return;
    }

    const { ok, resultado } = await apiDelete(API.recoleccion + "/rutas/" + botonBaja.dataset.id);
    if (ok) {
      avisoPagina("Ruta dada de baja.");
      cargarRutas();
    } else {
      avisoPaginaError(resultado.mensaje || "No se pudo dar de baja la ruta.");
    }
  }
});

function mostrar(texto, tipo) {
  mensaje.textContent = texto;
  mensaje.className = texto ? "mensaje " + tipo : "mensaje";
}

document.getElementById("btn-recargar").addEventListener("click", cargarRutas);
document.getElementById("btn-nuevo").addEventListener("click", prepararAlta);

activarValidacion(formRuta);
prepararModal("modal-ruta", formRuta);
activarOrden("tabla-rutas");
cargarZonas();
cargarRutas();
