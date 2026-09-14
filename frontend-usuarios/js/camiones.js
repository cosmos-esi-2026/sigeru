Sesion.exigir();

const COLUMNAS = 7;
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formCamion = document.getElementById("form-camion");
const mensaje = document.getElementById("mensaje");
const modalCamion = document.getElementById("modal-camion");

let idEditando = null;

function colorEstado(estado) {
  if (estado === "operativo") return "verde";
  if (estado === "en_ruta") return "naranja";
  if (estado === "mantenimiento") return "naranja";
  return "rojo";
}

async function cargarCamiones() {
  cuerpoTabla.innerHTML = filaEsqueleto(COLUMNAS, 4);

  try {
    const datos = await apiGet(API.gestion + "/camiones");
    dibujarTabla(datos.datos);
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(COLUMNAS, mensajeDeFallo(error));
    actualizarContador();
  }
}

function dibujarTabla(camiones) {
  if (!camiones || camiones.length === 0) {
    cuerpoTabla.innerHTML = filaVacia(
      COLUMNAS,
      "Todavía no hay camiones",
      "Cargá el primero con «Nuevo camión» para poder asignarlo a una recolección."
    );
    actualizarContador();
    return;
  }

  cuerpoTabla.innerHTML = camiones.map(function (c) {
    return '<tr role="row">' +
      celda("Matrícula", c.matricula) +
      celda("Marca", c.marca) +
      celda("Modelo", c.modelo) +
      celda("Capacidad", c.capacidad ? c.capacidad + " kg" : null, c.capacidad) +
      celdaEstado("Estado", c.estado, colorEstado(c.estado)) +
      celdaAcciones(c.id_camion, "Editar " + c.matricula, "Dar de baja " + c.matricula) +
      "</tr>";
  }).join("");

  actualizarContador();
}

function prepararAlta() {
  idEditando = null;
  document.getElementById("titulo-modal-camion").textContent = "Nuevo camión";
  formCamion.querySelector('button[type="submit"]').textContent = "Guardar camión";
}

function prepararEdicion(c) {
  idEditando = c.id_camion;
  document.getElementById("titulo-modal-camion").textContent = "Editar camión";
  formCamion.querySelector('button[type="submit"]').textContent = "Guardar cambios";

  formCamion.matricula.value = c.matricula || "";
  formCamion.marca.value = c.marca || "";
  formCamion.modelo.value = c.modelo || "";
  formCamion.capacidad.value = c.capacidad || "";
  formCamion.estado.value = c.estado || "operativo";
}

formCamion.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrar("", "");

  if (!validarFormulario(formCamion)) {
    return;
  }

  const boton = bloquearEnvio(formCamion, "Guardando...");
  const datos = {
    matricula: formCamion.matricula.value,
    marca:     formCamion.marca.value,
    modelo:    formCamion.modelo.value,
    capacidad: formCamion.capacidad.value,
    estado:    formCamion.estado.value,
  };

  try {
    const { ok, resultado } = idEditando === null
      ? await apiPost(API.gestion + "/camiones", datos)
      : await apiPut(API.gestion + "/camiones/" + idEditando, datos);

    if (ok) {
      cerrarModal("modal-camion");
      avisoPagina(idEditando === null
        ? "Camión creado correctamente."
        : "Camión actualizado correctamente.");
      cargarCamiones();
    } else {
      const texto = resultado.mensaje || "No se pudo guardar el camión.";
      if (!marcarErrorDeApi(formCamion, texto)) {
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
    cargarCamiones();
    return;
  }

  const botonEditar = evento.target.closest(".js-editar");
  if (botonEditar) {
    const { datos } = await apiGet(API.gestion + "/camiones/" + botonEditar.dataset.id);
    prepararEdicion(datos);
    bootstrap.Modal.getOrCreateInstance(modalCamion).show();
    return;
  }

  const botonBaja = evento.target.closest(".js-baja");
  if (botonBaja) {
    const matricula = botonBaja.closest("tr").cells[0].textContent;

    const seguro = await confirmar({
      titulo: "Dar de baja el camión " + matricula,
      mensaje: "Pasa a «fuera de servicio» y deja de poder asignarse a una recolección. " +
               "No se borra: las recolecciones que ya hizo lo siguen referenciando.",
      textoBoton: "Dar de baja",
    });
    if (!seguro) {
      return;
    }

    const { ok, resultado } = await apiDelete(API.gestion + "/camiones/" + botonBaja.dataset.id);
    if (ok) {
      avisoPagina("Camión dado de baja.");
      cargarCamiones();
    } else {
      avisoPaginaError(resultado.mensaje || "No se pudo dar de baja el camión.");
    }
  }
});

function mostrar(texto, tipo) {
  mensaje.textContent = texto;
  mensaje.className = texto ? "mensaje " + tipo : "mensaje";
}

document.getElementById("btn-recargar").addEventListener("click", cargarCamiones);
document.getElementById("btn-nuevo").addEventListener("click", prepararAlta);

activarValidacion(formCamion);
prepararModal("modal-camion", formCamion);
activarOrden("tabla-camiones");
cargarCamiones();
