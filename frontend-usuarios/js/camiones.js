Sesion.exigir();

const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formCamion = document.getElementById("form-camion");
const mensaje = document.getElementById("mensaje");

function colorEstado(estado) {
  if (estado === "operativo") return "verde";
  if (estado === "en_ruta") return "naranja";
  if (estado === "mantenimiento") return "naranja";
  return "rojo"; 
}

async function cargarCamiones() {
  cuerpoTabla.innerHTML = "<tr><td colspan='6'>Cargando...</td></tr>";
  try {
    const datos = await apiGet(API.gestion + "/camiones");
    dibujarTabla(datos.datos);
  } catch (e) {
    cuerpoTabla.innerHTML =
      "<tr><td colspan='6'>No se pudo conectar con la API. Verifica que este corriendo.</td></tr>";
  }
}

function dibujarTabla(camiones) {
  if (!camiones || camiones.length === 0) {
    cuerpoTabla.innerHTML = "<tr><td colspan='6'>No hay camiones.</td></tr>";
    return;
  }

  cuerpoTabla.innerHTML = "";
  camiones.forEach(function (c) {
    const fila = `
      <tr>
        <td>${c.id_camion}</td>
        <td>${c.matricula}</td>
        <td>${c.marca ?? "-"}</td>
        <td>${c.modelo ?? "-"}</td>
        <td>${c.capacidad ?? "-"}</td>
        <td><span class="estado ${colorEstado(c.estado)}">${c.estado}</span></td>
      </tr>`;
    cuerpoTabla.innerHTML += fila;
  });
}

formCamion.addEventListener("submit", async function (evento) {
  evento.preventDefault();

  const datos = {
    matricula: formCamion.matricula.value,
    marca:     formCamion.marca.value,
    modelo:    formCamion.modelo.value,
    capacidad: formCamion.capacidad.value,
    estado:    formCamion.estado.value,
  };

  try {
    const { ok, resultado } = await apiPost(API.gestion + "/camiones", datos);
    if (ok) {
      mostrar("Camion creado correctamente.", "ok");
      formCamion.reset();
      cargarCamiones();
    } else {
      mostrar(resultado.mensaje || "No se pudo crear el camion.", "error");
    }
  } catch (e) {
    mostrar("No se pudo conectar con la API.", "error");
  }
});

function mostrar(texto, tipo) {
  mensaje.textContent = texto;
  mensaje.className = "mensaje " + tipo;
}

document.getElementById("btn-recargar").addEventListener("click", cargarCamiones);
cargarCamiones();