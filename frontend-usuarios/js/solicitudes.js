Sesion.exigir();

const COLUMNAS = 8;
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const filtroEstado = document.getElementById("filtro-estado");
const formAprobar = document.getElementById("form-aprobar");
const mensaje = document.getElementById("mensaje");
const modalAprobar = document.getElementById("modal-aprobar");

let solicitudElegida = null;
let solicitudesPorId = {};

function colorEstado(estado) {
  if (estado === "aprobada") return "verde";
  if (estado === "rechazada") return "rojo";
  return "naranja";
}

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

function textoTipo(s) {
  return s.tipo_cuenta === "cuadrilla" ? "Cuadrilla" + (s.cuadrilla_indicada ? " («" + s.cuadrilla_indicada + "»)" : "") : "Vecino";
}

function dibujarTabla(solicitudes) {
  solicitudesPorId = {};
  (solicitudes || []).forEach(function (s) { solicitudesPorId[s.id_solicitud] = s; });
  if (!solicitudes || solicitudes.length === 0) {
    const filtrando = filtroEstado.value === "pendiente";
    cuerpoTabla.innerHTML = filaVacia(
      COLUMNAS,
      filtrando ? "No hay solicitudes pendientes" : "No hay solicitudes",
      filtrando
        ? "No quedan solicitudes del sistema de registro anterior."
        : "No hay solicitudes anteriores para mostrar."
    );
    actualizarContador();
    return;
  }

  cuerpoTabla.innerHTML = solicitudes.map(function (s) {
    return '<tr role="row">' +
      celda("Fecha", soloFecha(s.fecha_solicitud), s.fecha_solicitud) +
      celda("Nombre", s.nombre + " " + s.apellido) +
      celda("Email", s.email) +
      celda("Cédula", s.cedula ? formatearCedula(s.cedula) : null) +
      celda("Teléfono", s.telefono) +
      celda("Pide ser", textoTipo(s)) +
      celdaEstado("Estado", s.estado, colorEstado(s.estado)) +
      acciones(s) +
      "</tr>";
  }).join("");

  actualizarContador();
}

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
      cedula: (solicitudesPorId[botonAprobar.dataset.id] || {}).cedula || "",
      esDeCuadrilla: (solicitudesPorId[botonAprobar.dataset.id] || {}).tipo_cuenta === "cuadrilla",
    };
    prepararCampoCuadrilla(solicitudElegida.esDeCuadrilla);

    document.getElementById("detalle-solicitud").textContent =
      "Se va a crear la cuenta de " + solicitudElegida.nombre +
      " (" + solicitudElegida.email + "). Definí su contraseña inicial y entregásela en mano.";
    formAprobar.password.value = "";
    formAprobar.cedula.value = solicitudElegida.cedula ? formatearCedula(solicitudElegida.cedula) : "";

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

// Quien pidio acceso como cuadrilla no elige rol: solo puede ser Cuadrilla, y el administrador elige la cuadrilla real.
async function prepararCampoCuadrilla(esDeCuadrilla) {
  document.getElementById("campo-rol").classList.toggle("oculto", esDeCuadrilla);
  document.getElementById("campo-cuadrilla").classList.toggle("oculto", !esDeCuadrilla);
  document.getElementById("campo-cedula").classList.toggle("oculto", !esDeCuadrilla);
  formAprobar.cedula.required = esDeCuadrilla;
  formAprobar.id_cuadrilla.required = esDeCuadrilla;
  formAprobar.rol.value = esDeCuadrilla ? "Cuadrilla" : "Vecino";
  if (!esDeCuadrilla) {
    return;
  }
  try {
    const respuesta = await apiGet(API.recoleccion + "/cuadrillas");
    formAprobar.id_cuadrilla.innerHTML = '<option value="">Elegí una cuadrilla</option>' +
      (respuesta.datos || []).filter(function (c) { return c.estado === "activa"; }).map(function (c) {
        return '<option value="' + $entero(c.id_cuadrilla) + '">' + escaparHtml(c.nombre + " (" + c.integrantes + " integrante" + (c.integrantes === 1 ? "" : "s") + ")") + "</option>";
      }).join("");
  } catch (error) {
    mostrar("No se pudo traer la lista de cuadrillas. Cerrá y probá de nuevo.", "error");
  }
}

formAprobar.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrar("", "");

  if (solicitudElegida === null || !validarFormulario(formAprobar)) {
    return;
  }
  if (solicitudElegida.esDeCuadrilla && !cedulaUruguayaValida(formAprobar.cedula.value)) {
    marcarError(formAprobar.cedula, "La cédula no es válida: revisá los dígitos (7 u 8, con el verificador).");
    formAprobar.cedula.focus();
    return;
  }

  const boton = bloquearEnvio(formAprobar, "Aprobando...");

  try {
    const { ok, resultado } = await apiPut(
      API.usuarios + "/solicitudes/" + solicitudElegida.id,
      solicitudElegida.esDeCuadrilla
        ? { decision: "aprobada", rol: "Cuadrilla", id_cuadrilla: parseInt(formAprobar.id_cuadrilla.value, 10), cedula: soloDigitos(formAprobar.cedula.value), password: formAprobar.password.value }
        : { decision: "aprobada", rol: formAprobar.rol.value, password: formAprobar.password.value }
    );

    if (ok) {
      cerrarModal("modal-aprobar");
      avisoPagina("Solicitud aprobada. La cuenta de " + solicitudElegida.email + " ya está activa.");
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

activarValidacion(formAprobar);
prepararModal("modal-aprobar", formAprobar);
activarOrden("tabla-solicitudes");
cargarSolicitudes();
