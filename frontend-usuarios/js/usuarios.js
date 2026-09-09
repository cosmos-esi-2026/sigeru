// ============================================================================
//  SiGeRU 2026 - usuarios.js
//  Listado, alta, edicion y baja logica de usuarios.
// ============================================================================

Sesion.exigir();

const COLUMNAS = 7;
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formUsuario = document.getElementById("form-usuario");
const mensaje = document.getElementById("mensaje");
const modalUsuario = document.getElementById("modal-usuario");

// null = el modal esta creando. Un numero = esta editando ese usuario.
let idEditando = null;

async function cargarUsuarios() {
  cuerpoTabla.innerHTML = filaEsqueleto(COLUMNAS, 4);

  try {
    const datos = await apiGet(API.usuarios + "/usuarios");
    dibujarTabla(datos.datos);
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(COLUMNAS, mensajeDeFallo(error));
    actualizarContador();
  }
}

function dibujarTabla(usuarios) {
  if (!usuarios || usuarios.length === 0) {
    cuerpoTabla.innerHTML = filaVacia(
      COLUMNAS,
      "Todavía no hay usuarios",
      "Las altas entran por «Nuevo usuario» o aprobando una solicitud de registro."
    );
    actualizarContador();
    return;
  }

  cuerpoTabla.innerHTML = usuarios.map(function (u) {
    const color = u.estado === "activo" ? "verde" : "gris";
    return '<tr role="row">' +
      celda("ID", u.id_usuario) +
      celda("Nombre", u.nombre) +
      celda("Apellido", u.apellido) +
      celda("Email", u.email) +
      celda("Rol", u.rol) +
      celdaEstado("Estado", u.estado, color) +
      celdaAcciones(u.id_usuario, "Editar " + u.nombre, "Dar de baja a " + u.nombre) +
      "</tr>";
  }).join("");

  actualizarContador();
}


// --- Alta y edicion ---------------------------------------------------------
// El mismo modal sirve para las dos cosas: cambia el titulo, el texto del boton
// y el verbo HTTP. Tener dos formularios casi iguales seria mas codigo y una
// fuente mas de inconsistencias.

function prepararAlta() {
  idEditando = null;
  document.getElementById("titulo-modal-usuario").textContent = "Nuevo usuario";
  formUsuario.querySelector('button[type="submit"]').textContent = "Guardar usuario";
}

function prepararEdicion(usuario) {
  idEditando = usuario.id_usuario;
  document.getElementById("titulo-modal-usuario").textContent = "Editar usuario";
  formUsuario.querySelector('button[type="submit"]').textContent = "Guardar cambios";

  formUsuario.nombre.value = usuario.nombre || "";
  formUsuario.apellido.value = usuario.apellido || "";
  formUsuario.email.value = usuario.email || "";
  formUsuario.rol.value = usuario.rol || "Vecino";
}

formUsuario.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrar("", "");

  if (!validarFormulario(formUsuario)) {
    return;
  }

  const boton = bloquearEnvio(formUsuario, "Guardando...");
  const datos = {
    nombre:   formUsuario.nombre.value,
    apellido: formUsuario.apellido.value,
    email:    formUsuario.email.value,
    rol:      formUsuario.rol.value,
  };

  try {
    const { ok, resultado } = idEditando === null
      ? await apiPost(API.usuarios + "/usuarios", datos)
      : await apiPut(API.usuarios + "/usuarios/" + idEditando, datos);

    if (ok) {
      cerrarModal("modal-usuario");
      avisoPagina(idEditando === null
        ? "Usuario creado correctamente."
        : "Usuario actualizado correctamente.");
      cargarUsuarios();
    } else {
      const texto = resultado.mensaje || "No se pudo guardar el usuario.";
      if (!marcarErrorDeApi(formUsuario, texto)) {
        mostrar(texto, "error");
      }
    }
  } catch (e) {
    mostrar("No se pudo conectar con el servidor. Revisá que esté corriendo.", "error");
  } finally {
    liberarEnvio(boton);
  }
});


// --- Acciones de cada fila --------------------------------------------------

cuerpoTabla.addEventListener("click", async function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargarUsuarios();
    return;
  }

  const botonEditar = evento.target.closest(".js-editar");
  if (botonEditar) {
    const { datos } = await apiGet(API.usuarios + "/usuarios/" + botonEditar.dataset.id);
    prepararEdicion(datos);
    bootstrap.Modal.getOrCreateInstance(modalUsuario).show();
    return;
  }

  const botonBaja = evento.target.closest(".js-baja");
  if (botonBaja) {
    const fila = botonBaja.closest("tr");
    const nombre = fila.cells[1].textContent + " " + fila.cells[2].textContent;

    const seguro = await confirmar({
      titulo: "Dar de baja a " + nombre,
      mensaje: "La cuenta pasa a inactiva y no va a poder iniciar sesión. No se borra: " +
               "las incidencias que reportó siguen teniendo autor.",
      textoBoton: "Dar de baja",
    });
    if (!seguro) {
      return;
    }

    const { ok, resultado } = await apiDelete(API.usuarios + "/usuarios/" + botonBaja.dataset.id);
    if (ok) {
      avisoPagina("Usuario dado de baja.");
      cargarUsuarios();
    } else {
      avisoPaginaError(resultado.mensaje || "No se pudo dar de baja al usuario.");
    }
  }
});

function mostrar(texto, tipo) {
  mensaje.textContent = texto;
  mensaje.className = texto ? "mensaje " + tipo : "mensaje";
}

document.getElementById("btn-recargar").addEventListener("click", cargarUsuarios);
document.getElementById("btn-nuevo").addEventListener("click", prepararAlta);

activarValidacion(formUsuario);
prepararModal("modal-usuario", formUsuario);
activarOrden("tabla-usuarios");
cargarUsuarios();
