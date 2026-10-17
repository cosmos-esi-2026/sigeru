Sesion.exigir();

const COLUMNAS = 7;
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formUsuario = document.getElementById("form-usuario");
const mensaje = document.getElementById("mensaje");
const modalUsuario = document.getElementById("modal-usuario");

let idEditando = null;

const formFiltros = document.getElementById("form-filtros");
const campoBusqueda = document.getElementById("buscar");
const paginador = crearPaginador("paginacion", "usuarios", function () { cargarUsuarios(); });
let hayFiltros = false;

function consultaDeLaLista(extra) {
  const q = campoBusqueda.value.trim();
  const base = consultaDeFormulario(formFiltros, ["rol", "estado"], extra);
  if (q === "") return base;
  return (base === "" ? "?" : base + "&") + "q=" + encodeURIComponent(q);
}

async function cargarUsuarios() {
  cuerpoTabla.innerHTML = filaEsqueleto(COLUMNAS, 4);
  hayFiltros = consultaDeLaLista({}) !== "";

  try {
    const datos = await apiGet(API.usuarios + "/usuarios" + consultaDeLaLista({
      pagina: paginador.estado.pagina, por_pagina: paginador.estado.porPagina,
    }));
    if (paginador.ajustar(datos.meta)) {
      return cargarUsuarios();
    }
    dibujarTabla(datos.datos);
    paginador.dibujar(datos.meta);
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(COLUMNAS, mensajeDeFallo(error));
    paginador.dibujar(null);
  }
}

async function exportarUsuarios() {
  try {
    const datos = await apiGet(API.usuarios + "/usuarios" + consultaDeLaLista({}));
    const filas = [["ID", "Nombre", "Apellido", "Correo", "Teléfono", "Rol", "Estado", "Fecha de registro"]];
    datos.datos.forEach(function (u) {
      filas.push([u.id_usuario, u.nombre, u.apellido, u.email, u.telefono, u.rol, textoEstado(u.estado), u.fecha_registro]);
    });
    descargarCsv("usuarios_" + fechaParaArchivo() + ".csv", filas);
    avisoPagina("Se exportaron " + datos.datos.length + " usuarios (con los filtros activos). El archivo no incluye contraseñas.");
  } catch (error) {
    avisoPaginaError("No se pudo exportar. " + mensajeDeFallo(error));
  }
}

function dibujarTabla(usuarios) {
  if (!usuarios || usuarios.length === 0) {
    cuerpoTabla.innerHTML = hayFiltros
      ? filaVacia(COLUMNAS, "Ningún usuario coincide", "Probá con otra búsqueda o limpiá los filtros.")
      : filaVacia(
          COLUMNAS,
          "Todavía no hay usuarios",
          "Las altas entran por «Nuevo usuario» o por el registro público de vecinos."
        );
    return;
  }

  cuerpoTabla.innerHTML = usuarios.map(function (u) {
    const color = u.estado === "activo" ? "verde" : u.estado === "pendiente" ? "naranja" : "gris";
    return '<tr role="row">' +
      celda("ID", u.id_usuario) +
      celda("Nombre", u.nombre) +
      celda("Apellido", u.apellido) +
      celda("Email", u.email) +
      celda("Rol", u.rol) +
      celdaEstado("Estado", u.estado, color) +
      celdaAcciones(u.id_usuario, "Editar " + u.nombre, "Dar de baja a " + u.nombre)
        .replace("</div></td>", (u.estado === "inactivo" ? "" :
          '<button type="button" class="boton boton-secundario boton-chico js-enlace" ' +
          'data-id="' + parseInt(u.id_usuario, 10) + '">Enviar acceso</button>') + "</div></td>") +
      "</tr>";
  }).join("");
}

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
        ? "Usuario creado. Se envió un enlace para que defina su contraseña."
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

cuerpoTabla.addEventListener("click", async function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargarUsuarios();
    return;
  }

  const botonEnlace = evento.target.closest(".js-enlace");
  if (botonEnlace) {
    botonEnlace.disabled = true;
    try {
      const { ok, resultado } = await apiPost(
        API.usuarios + "/usuarios/" + botonEnlace.dataset.id + "/enlace", {}
      );
      if (ok) avisoPagina("Se envió un enlace para crear o renovar la contraseña.");
      else avisoPaginaError(resultado.mensaje || "No se pudo enviar el correo.");
    } catch (e) {
      avisoPaginaError("No se pudo conectar con el servidor.");
    } finally {
      botonEnlace.disabled = false;
    }
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
document.getElementById("btn-exportar").addEventListener("click", exportarUsuarios);
function volverALaPrimera() { paginador.volverAlPrincipio(); cargarUsuarios(); }
formFiltros.addEventListener("change", volverALaPrimera);
formFiltros.addEventListener("reset", function () { campoBusqueda.value = ""; setTimeout(volverALaPrimera, 0); });
campoBusqueda.addEventListener("input", retrasar(volverALaPrimera, 350));
document.getElementById("btn-nuevo").addEventListener("click", prepararAlta);

activarValidacion(formUsuario);
prepararModal("modal-usuario", formUsuario);
activarOrden("tabla-usuarios");
cargarUsuarios();
