Sesion.exigir();

const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formUsuario = document.getElementById("form-usuario");
const mensaje = document.getElementById("mensaje");

async function cargarUsuarios() {
  cuerpoTabla.innerHTML = filaAviso(6, "Cargando...");
  try {
    const datos = await apiGet(API.usuarios + "/usuarios");
    dibujarTabla(datos.datos);
  } catch (e) {
    cuerpoTabla.innerHTML =
      filaAviso(6, "No se pudo conectar con la API. Verifica que este corriendo.");
  }
}

function dibujarTabla(usuarios) {
  if (!usuarios || usuarios.length === 0) {
    cuerpoTabla.innerHTML = filaVacia(6, "Todavia no hay usuarios cargados.");
    return;
  }

  cuerpoTabla.innerHTML = "";
  usuarios.forEach(function (u) {
    const color = u.estado === "activo" ? "verde" : "gris";
    const fila = `
      <tr>
        <td>${u.id_usuario}</td>
        <td>${u.nombre}</td>
        <td>${u.apellido}</td>
        <td>${u.email}</td>
        <td>${u.rol}</td>
        <td><span class="estado ${color}">${u.estado}</span></td>
      </tr>`;
    cuerpoTabla.innerHTML += fila;
  });
}

formUsuario.addEventListener("submit", async function (evento) {
  evento.preventDefault();

  const datos = {
    nombre:   formUsuario.nombre.value,
    apellido: formUsuario.apellido.value,
    email:    formUsuario.email.value,
    rol:      formUsuario.rol.value,
  };

  try {
    const { ok, resultado } = await apiPost(API.usuarios + "/usuarios", datos);
    if (ok) {
      // El alta salio bien: se limpia el formulario, se cierra el modal y el
      // aviso se muestra arriba de la tabla (el de adentro ya no se ve).
      mostrar("", "");
      formUsuario.reset();
      cerrarModal("modal-usuario");
      avisoPagina("Usuario creado correctamente.");
      cargarUsuarios();
    } else {
      mostrar(resultado.mensaje || "No se pudo crear el usuario.", "error");
    }
  } catch (e) {
    mostrar("No se pudo conectar con la API.", "error");
  }
});

function mostrar(texto, tipo) {
  mensaje.textContent = texto;
  mensaje.className = "mensaje " + tipo;
}

document.getElementById("btn-recargar").addEventListener("click", cargarUsuarios);
cargarUsuarios();