Sesion.exigir();

const COLUMNAS = 6;
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formCuadrilla = document.getElementById("form-cuadrilla");
const mensaje = document.getElementById("mensaje");
const modalCuadrilla = document.getElementById("modal-cuadrilla");
const zonaIntegrantes = document.getElementById("integrantes");
const listaIntegrantes = document.getElementById("lista-integrantes");

let idEditando = null;

const TURNOS = { matutino: "Matutino", vespertino: "Vespertino", nocturno: "Nocturno" };

async function cargarCuadrillas() {
  cuerpoTabla.innerHTML = filaEsqueleto(COLUMNAS, 4);

  try {
    const datos = await apiGet(API.recoleccion + "/cuadrillas");
    dibujarTabla(datos.datos);
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(COLUMNAS, mensajeDeFallo(error));
    actualizarContador();
  }
}

function dibujarTabla(cuadrillas) {
  if (!cuadrillas || cuadrillas.length === 0) {
    cuerpoTabla.innerHTML = filaVacia(
      COLUMNAS,
      "Todavía no hay cuadrillas",
      "Creá la primera con «Nueva cuadrilla» para poder asignarle rutas."
    );
    actualizarContador();
    return;
  }

  cuerpoTabla.innerHTML = cuadrillas.map(function (c) {
    return '<tr role="row">' +
      celda("Nombre", c.nombre) +
      celda("Turno", TURNOS[c.turno] || c.turno) +
      celdaEstado("Estado", c.estado, c.estado === "activa" ? "verde" : "gris") +
      celda("Integrantes", c.integrantes, c.integrantes) +
      celda("Recolecciones abiertas", c.recolecciones_abiertas, c.recolecciones_abiertas) +
      celdaAcciones(c.id_cuadrilla, "Editar " + c.nombre, "Dar de baja " + c.nombre) +
      "</tr>";
  }).join("");

  actualizarContador();
}

function dibujarIntegrantes(personas) {
  zonaIntegrantes.classList.toggle("oculto", false);
  listaIntegrantes.innerHTML = personas && personas.length
    ? personas.map(function (p) {
        return "<li>" + escaparHtml(p.nombre + " " + p.apellido) + (p.estado === "inactivo" ? " (dado de baja)" : p.estado === "pendiente" ? " (todavía sin contraseña)" : "") + "</li>";
      }).join("")
    : "<li>Todavía no tiene integrantes. Se asignan desde Usuarios.</li>";
}

function prepararAlta() {
  idEditando = null;
  document.getElementById("titulo-modal-cuadrilla").textContent = "Nueva cuadrilla";
  formCuadrilla.querySelector('button[type="submit"]').textContent = "Guardar cuadrilla";
  formCuadrilla.estado.value = "activa";
  formCuadrilla.estado.disabled = true;
  zonaIntegrantes.classList.add("oculto");
}

function prepararEdicion(c) {
  idEditando = c.id_cuadrilla;
  document.getElementById("titulo-modal-cuadrilla").textContent = "Editar cuadrilla";
  formCuadrilla.querySelector('button[type="submit"]').textContent = "Guardar cambios";

  formCuadrilla.nombre.value = c.nombre || "";
  formCuadrilla.turno.value = c.turno || "matutino";
  formCuadrilla.estado.disabled = false;
  formCuadrilla.estado.value = c.estado || "activa";
  dibujarIntegrantes(c.personas);
}

formCuadrilla.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrar("", "");

  if (!validarFormulario(formCuadrilla)) {
    return;
  }

  const boton = bloquearEnvio(formCuadrilla, "Guardando...");
  const datos = { nombre: formCuadrilla.nombre.value, turno: formCuadrilla.turno.value };
  if (idEditando !== null) {
    datos.estado = formCuadrilla.estado.value;
  }

  try {
    const { ok, resultado } = idEditando === null
      ? await apiPost(API.recoleccion + "/cuadrillas", datos)
      : await apiPut(API.recoleccion + "/cuadrillas/" + idEditando, datos);

    if (ok) {
      cerrarModal("modal-cuadrilla");
      avisoPagina(idEditando === null ? "Cuadrilla creada correctamente." : "Cuadrilla actualizada correctamente.");
      cargarCuadrillas();
    } else {
      const texto = resultado.mensaje || "No se pudo guardar la cuadrilla.";
      if (!marcarErrorDeApi(formCuadrilla, texto)) {
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
    cargarCuadrillas();
    return;
  }

  const botonEditar = evento.target.closest(".js-editar");
  if (botonEditar) {
    const { datos } = await apiGet(API.recoleccion + "/cuadrillas/" + botonEditar.dataset.id);
    prepararEdicion(datos);
    bootstrap.Modal.getOrCreateInstance(modalCuadrilla).show();
    return;
  }

  const botonBaja = evento.target.closest(".js-baja");
  if (botonBaja) {
    const nombre = botonBaja.closest("tr").cells[0].textContent;

    const seguro = await confirmar({
      titulo: "Dar de baja a " + nombre,
      mensaje: "Deja de poder recibir rutas e incidencias. No se borra: sus recolecciones anteriores siguen en el historial. " +
               "Si tiene una recolección planificada o en curso, primero hay que cancelarla o reasignarla.",
      textoBoton: "Dar de baja",
    });
    if (!seguro) {
      return;
    }

    const { ok, resultado } = await apiDelete(API.recoleccion + "/cuadrillas/" + botonBaja.dataset.id);
    if (ok) {
      avisoPagina("Cuadrilla dada de baja.");
      cargarCuadrillas();
    } else {
      avisoPaginaError(resultado.mensaje || "No se pudo dar de baja la cuadrilla.");
    }
  }
});

function mostrar(texto, tipo) {
  mensaje.textContent = texto;
  mensaje.className = texto ? "mensaje " + tipo : "mensaje";
}

document.getElementById("btn-recargar").addEventListener("click", cargarCuadrillas);
document.getElementById("btn-nuevo").addEventListener("click", prepararAlta);

activarValidacion(formCuadrilla);
prepararModal("modal-cuadrilla", formCuadrilla);
activarOrden("tabla-cuadrillas");
cargarCuadrillas();
