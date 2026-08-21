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
  cuerpoTabla.innerHTML = filaAviso(6, "Cargando...");
  try {
    const datos = await apiGet(API.gestion + "/camiones");
    dibujarTabla(datos.datos);
  } catch (e) {
    cuerpoTabla.innerHTML =
      filaAviso(6, "No se pudo conectar con la API. Verifica que este corriendo.");
  }
}

function dibujarTabla(camiones) {
  if (!camiones || camiones.length === 0) {
    cuerpoTabla.innerHTML = filaVacia(6, "Todavia no hay camiones cargados.");
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
      // El alta salio bien: se limpia el formulario, se cierra el modal y el
      // aviso se muestra arriba de la tabla (el de adentro ya no se ve).
      mostrar("", "");
      formCamion.reset();
      cerrarModal("modal-camion");
      avisoPagina("Camion creado correctamente.");
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