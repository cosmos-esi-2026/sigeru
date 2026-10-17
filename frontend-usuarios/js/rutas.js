Sesion.exigir();

const COLUMNAS = 6;
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formRuta = document.getElementById("form-ruta");
const mensaje = document.getElementById("mensaje");
const modalRuta = document.getElementById("modal-ruta");
const TEXTO_DIA = { lunes: "Lunes", martes: "Martes", miercoles: "Miércoles", jueves: "Jueves", viernes: "Viernes", sabado: "Sábado", domingo: "Domingo" };

let idEditando = null;

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
      celdaEstado("Estado", r.estado, r.estado === "activa" ? "verde" : "gris") +
      celdaAcciones(r.id_ruta, "Editar " + r.nombre, "Dar de baja " + r.nombre) +
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
