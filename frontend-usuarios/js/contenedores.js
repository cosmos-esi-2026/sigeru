Sesion.exigir();

const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formContenedor = document.getElementById("form-contenedor");
const mensaje = document.getElementById("mensaje");

function colorEstado(estado) {
  if (estado === "disponible") return "verde";
  if (estado === "lleno") return "rojo";
  if (estado === "mantenimiento") return "naranja";
  return "gris"; 
}

async function cargarContenedores() {
  cuerpoTabla.innerHTML = filaAviso(6, "Cargando...");
  try {
    const datos = await apiGet(API.gestion + "/contenedores");
    dibujarTabla(datos.datos);
  } catch (e) {
    cuerpoTabla.innerHTML =
      filaAviso(6, "No se pudo conectar con la API. Verifica que este corriendo.");
  }
}

function dibujarTabla(contenedores) {
  if (!contenedores || contenedores.length === 0) {
    cuerpoTabla.innerHTML = filaVacia(6, "Todavia no hay contenedores cargados.");
    return;
  }

  cuerpoTabla.innerHTML = "";
  contenedores.forEach(function (c) {
    const fila = `
      <tr>
        <td>${c.id_contenedor}</td>
        <td>${c.codigo}</td>
        <td>${c.direccion}</td>
        <td>${c.capacidad ?? "-"}</td>
        <td>${c.tipo_residuo ?? "-"}</td>
        <td><span class="estado ${colorEstado(c.estado)}">${c.estado}</span></td>
      </tr>`;
    cuerpoTabla.innerHTML += fila;
  });
}

formContenedor.addEventListener("submit", async function (evento) {
  evento.preventDefault();

  const datos = {
    codigo:       formContenedor.codigo.value,
    direccion:    formContenedor.direccion.value,
    capacidad:    formContenedor.capacidad.value,
    tipo_residuo: formContenedor.tipo_residuo.value,
    estado:       formContenedor.estado.value,
  };

  try {
    const { ok, resultado } = await apiPost(API.gestion + "/contenedores", datos);
    if (ok) {
      // El alta salio bien: se limpia el formulario, se cierra el modal y el
      // aviso se muestra arriba de la tabla (el de adentro ya no se ve).
      mostrar("", "");
      formContenedor.reset();
      cerrarModal("modal-contenedor");
      avisoPagina("Contenedor creado correctamente.");
      cargarContenedores();
    } else {
      mostrar(resultado.mensaje || "No se pudo crear el contenedor.", "error");
    }
  } catch (e) {
    mostrar("No se pudo conectar con la API.", "error");
  }
});

function mostrar(texto, tipo) {
  mensaje.textContent = texto;
  mensaje.className = "mensaje " + tipo;
}

document.getElementById("btn-recargar").addEventListener("click", cargarContenedores);
cargarContenedores();