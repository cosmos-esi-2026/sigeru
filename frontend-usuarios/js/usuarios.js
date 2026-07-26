Sesion.exigir();

const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formUsuario = document.getElementById("form-usuario");
const mensaje = document.getElementById("mensaje");

async function cargarUsuarios() {
  cuerpoTabla.innerHTML = "<tr><td colspan='6'>Cargando...</td></tr>";
  try {
    const datos = await apiGet(API.usuarios + "/usuarios");
    dibujarTabla(datos.datos);
  } catch (e) {
    cuerpoTabla.innerHTML =
      "<tr><td colspan='6'>No se pudo conectar con la API. Verifica que este corriendo.</td></tr>";
  }
}
function dibujarTabla(usuarios) {
  if (!usuarios || usuarios.length === 0) {
    cuerpoTabla.innerHTML = "<tr><td colspan='6'>No hay usuarios.</td></tr>";
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
      mostrar("Usuario creado correctamente.", "ok");
      formUsuario.reset();
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