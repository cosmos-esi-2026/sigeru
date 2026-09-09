// ============================================================================
//  SiGeRU 2026 - solicitudes.js
//  Aprobacion y rechazo de solicitudes de acceso. Es el CU-011, que tenia
//  endpoint en la API desde el 03/09 pero no tenia ninguna pantalla: la unica
//  forma de aprobar a alguien era con una peticion HTTP a mano.
// ============================================================================

Sesion.exigir();

const COLUMNAS = 6;
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const filtroEstado = document.getElementById("filtro-estado");
const formAprobar = document.getElementById("form-aprobar");
const mensaje = document.getElementById("mensaje");
const modalAprobar = document.getElementById("modal-aprobar");

// La solicitud que se esta por aprobar en el modal.
let solicitudElegida = null;

function colorEstado(estado) {
  if (estado === "aprobada") return "verde";
  if (estado === "rechazada") return "rojo";
  return "naranja";   // pendiente
}

/** "2026-09-07 11:03:00" -> "07/09/2026". La hora no aporta en el listado. */
function soloFecha(valor) {
  if (!valor) {
    return "-";
  }
  const partes = String(valor).split(" ")[0].split("-");
  return partes.length === 3 ? partes[2] + "/" + partes[1] + "/" + partes[0] : valor;
}

async function cargarSolicitudes() {
  cuerpoTabla.innerHTML = filaEsqueleto(COLUMNAS, 3);

  const estado = filtroEstado.value;
  const url = API.usuarios + "/solicitudes" + (estado ? "?estado=" + estado : "");

  try {
    const datos = await apiGet(url);
    dibujarTabla(datos.datos);
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(COLUMNAS, mensajeDeFallo(error));
    actualizarContador();
  }
}

function dibujarTabla(solicitudes) {
  if (!solicitudes || solicitudes.length === 0) {
    const filtrando = filtroEstado.value === "pendiente";
    cuerpoTabla.innerHTML = filaVacia(
      COLUMNAS,
      filtrando ? "No hay solicitudes pendientes" : "No hay solicitudes",
      filtrando
        ? "Cuando alguien pida acceso desde la página pública, va a aparecer acá."
        : "Todavía no entró ninguna solicitud por el registro público."
    );
    actualizarContador();
    return;
  }

  cuerpoTabla.innerHTML = solicitudes.map(function (s) {
    return '<tr role="row">' +
      // Se muestra dd/mm/aaaa pero se ordena por el valor original de la base,
      // que es el unico que ordena cronologicamente.
      celda("Fecha", soloFecha(s.fecha_solicitud), s.fecha_solicitud) +
      celda("Nombre", s.nombre + " " + s.apellido) +
      celda("Email", s.email) +
      celda("Teléfono", s.telefono) +
      celdaEstado("Estado", s.estado, colorEstado(s.estado)) +
      acciones(s) +
      "</tr>";
  }).join("");

  actualizarContador();
}

/**
 * Solo las pendientes se pueden resolver. Una solicitud ya aprobada o
 * rechazada no muestra botones: la decision ya se tomo y rehacerla no tiene
 * sentido (la API tambien lo rechaza con 409).
 */
function acciones(s) {
  if (s.estado !== "pendiente") {
    return '<td role="cell" data-etiqueta="Acciones"><span class="texto-tenue">Resuelta</span></td>';
  }

  return '<td role="cell" data-etiqueta="Acciones">' +
    '<div class="acciones-fila">' +
    '<button type="button" class="boton boton-secundario boton-chico js-aprobar" ' +
    'data-id="' + parseInt(s.id_solicitud, 10) + '">Aprobar</button>' +
    '<button type="button" class="boton boton-secundario boton-chico js-rechazar" ' +
    'data-id="' + parseInt(s.id_solicitud, 10) + '">Rechazar</button>' +
    "</div></td>";
}


// --- Resolver una solicitud -------------------------------------------------

cuerpoTabla.addEventListener("click", async function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargarSolicitudes();
    return;
  }

  const botonAprobar = evento.target.closest(".js-aprobar");
  if (botonAprobar) {
    const fila = botonAprobar.closest("tr");
    solicitudElegida = {
      id: botonAprobar.dataset.id,
      nombre: fila.cells[1].textContent,
      email: fila.cells[2].textContent,
    };

    document.getElementById("detalle-solicitud").textContent =
      "Se va a crear la cuenta de " + solicitudElegida.nombre +
      " (" + solicitudElegida.email + ") con una contraseña provisoria.";

    bootstrap.Modal.getOrCreateInstance(modalAprobar).show();
    return;
  }

  const botonRechazar = evento.target.closest(".js-rechazar");
  if (botonRechazar) {
    const fila = botonRechazar.closest("tr");
    const nombre = fila.cells[1].textContent;

    const seguro = await confirmar({
      titulo: "Rechazar la solicitud de " + nombre,
      mensaje: "La solicitud queda marcada como rechazada y no se crea ninguna cuenta. " +
               "La persona va a tener que solicitar el acceso de nuevo.",
      textoBoton: "Rechazar",
    });
    if (!seguro) {
      return;
    }

    const { ok, resultado } = await apiPut(
      API.usuarios + "/solicitudes/" + botonRechazar.dataset.id,
      { decision: "rechazada" }
    );

    if (ok) {
      avisoPagina("Solicitud rechazada.");
      cargarSolicitudes();
    } else {
      avisoPaginaError(resultado.mensaje || "No se pudo rechazar la solicitud.");
    }
  }
});

formAprobar.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrar("", "");

  if (solicitudElegida === null) {
    return;
  }

  const boton = bloquearEnvio(formAprobar, "Aprobando...");

  try {
    const { ok, resultado } = await apiPut(
      API.usuarios + "/solicitudes/" + solicitudElegida.id,
      { decision: "aprobada", rol: formAprobar.rol.value }
    );

    if (ok) {
      cerrarModal("modal-aprobar");
      avisoPagina("Solicitud aprobada. La cuenta de " + solicitudElegida.nombre + " ya está creada.");
      solicitudElegida = null;
      cargarSolicitudes();
    } else {
      mostrar(resultado.mensaje || "No se pudo aprobar la solicitud.", "error");
    }
  } catch (e) {
    mostrar("No se pudo conectar con el servidor. Revisá que esté corriendo.", "error");
  } finally {
    liberarEnvio(boton);
  }
});

function mostrar(texto, tipo) {
  mensaje.textContent = texto;
  mensaje.className = texto ? "mensaje " + tipo : "mensaje";
}

filtroEstado.addEventListener("change", cargarSolicitudes);
document.getElementById("btn-recargar").addEventListener("click", cargarSolicitudes);

prepararModal("modal-aprobar", formAprobar);
activarOrden("tabla-solicitudes");
cargarSolicitudes();
