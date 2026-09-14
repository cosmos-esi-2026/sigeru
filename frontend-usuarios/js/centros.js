Sesion.exigir();

const COLUMNAS = 5;
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formCentro = document.getElementById("form-centro");
const mensaje = document.getElementById("mensaje");
const modalCentro = document.getElementById("modal-centro");

let idEditando = null;

async function cargarCentros() {
  cuerpoTabla.innerHTML = filaEsqueleto(COLUMNAS, 3);

  try {
    const datos = await apiGet(API.gestion + "/centros");
    dibujarTabla(datos.datos);
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(COLUMNAS, mensajeDeFallo(error));
    actualizarContador();
  }
}

function dibujarTabla(centros) {
  if (!centros || centros.length === 0) {
    cuerpoTabla.innerHTML = filaVacia(
      COLUMNAS,
      "Todavía no hay centros de acopio",
      "Cargá el primero con «Nuevo centro» para poder asignarle zonas y recolecciones."
    );
    actualizarContador();
    return;
  }

  cuerpoTabla.innerHTML = centros.map(function (c) {
    const color = c.estado === "activo" ? "verde" : "gris";
    return '<tr role="row">' +
      celda("Nombre", c.nombre) +
      celda("Dirección", c.direccion) +
      celda("Capacidad",
            c.capacidad ? Number(c.capacidad).toLocaleString("es-UY") + " kg" : null,
            c.capacidad) +
      celdaEstado("Estado", c.estado, color) +
      celdaAcciones(c.id_centro, "Editar " + c.nombre, "Dar de baja " + c.nombre) +
      "</tr>";
  }).join("");

  actualizarContador();
}

function prepararAlta() {
  idEditando = null;
  document.getElementById("titulo-modal-centro").textContent = "Nuevo centro";
  formCentro.querySelector('button[type="submit"]').textContent = "Guardar centro";
}

function prepararEdicion(c) {
  idEditando = c.id_centro;
  document.getElementById("titulo-modal-centro").textContent = "Editar centro";
  formCentro.querySelector('button[type="submit"]').textContent = "Guardar cambios";

  formCentro.nombre.value = c.nombre || "";
  formCentro.direccion.value = c.direccion || "";
  formCentro.capacidad.value = c.capacidad || "";
  formCentro.estado.value = c.estado || "activo";
}

formCentro.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrar("", "");

  if (!validarFormulario(formCentro)) {
    return;
  }

  const boton = bloquearEnvio(formCentro, "Guardando...");
  const datos = {
    nombre:    formCentro.nombre.value,
    direccion: formCentro.direccion.value,
    capacidad: formCentro.capacidad.value,
    estado:    formCentro.estado.value,
  };

  try {
    const { ok, resultado } = idEditando === null
      ? await apiPost(API.gestion + "/centros", datos)
      : await apiPut(API.gestion + "/centros/" + idEditando, datos);

    if (ok) {
      cerrarModal("modal-centro");
      avisoPagina(idEditando === null
        ? "Centro de acopio creado correctamente."
        : "Centro de acopio actualizado correctamente.");
      cargarCentros();
    } else {
      const texto = resultado.mensaje || "No se pudo guardar el centro de acopio.";
      if (!marcarErrorDeApi(formCentro, texto)) {
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
    cargarCentros();
    return;
  }

  const botonEditar = evento.target.closest(".js-editar");
  if (botonEditar) {
    const { datos } = await apiGet(API.gestion + "/centros/" + botonEditar.dataset.id);
    prepararEdicion(datos);
    bootstrap.Modal.getOrCreateInstance(modalCentro).show();
    return;
  }

  const botonBaja = evento.target.closest(".js-baja");
  if (botonBaja) {
    const nombre = botonBaja.closest("tr").cells[0].textContent;

    const seguro = await confirmar({
      titulo: "Dar de baja " + nombre,
      mensaje: "Pasa a inactivo y deja de poder asignarse a nuevas recolecciones. " +
               "No se borra: las zonas y recolecciones que lo referencian lo siguen necesitando.",
      textoBoton: "Dar de baja",
    });
    if (!seguro) {
      return;
    }

    const { ok, resultado } = await apiDelete(API.gestion + "/centros/" + botonBaja.dataset.id);
    if (ok) {
      avisoPagina("Centro de acopio dado de baja.");
      cargarCentros();
    } else {
      avisoPaginaError(resultado.mensaje || "No se pudo dar de baja el centro.");
    }
  }
});

function mostrar(texto, tipo) {
  mensaje.textContent = texto;
  mensaje.className = texto ? "mensaje " + tipo : "mensaje";
}

document.getElementById("btn-recargar").addEventListener("click", cargarCentros);
document.getElementById("btn-nuevo").addEventListener("click", prepararAlta);

activarValidacion(formCentro);
prepararModal("modal-centro", formCentro);
activarOrden("tabla-centros");
cargarCentros();
