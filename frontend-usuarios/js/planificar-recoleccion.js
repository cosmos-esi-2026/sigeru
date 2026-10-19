const formRecoleccion = document.getElementById("form-recoleccion");
const mensajePlan = document.getElementById("mensaje");
const DIAS_SEMANA = ["domingo", "lunes", "martes", "miercoles", "jueves", "viernes", "sabado"];

let rutasActivas = [];
let catalogosCargados = false;

function opcion(valor, texto) {
  return '<option value="' + escaparHtml(valor) + '">' + escaparHtml(texto) + "</option>";
}

function mostrarPlan(texto, tipo) {
  mensajePlan.textContent = texto;
  mensajePlan.className = texto ? "mensaje " + tipo : "mensaje";
}

async function cargarCatalogos() {
  const [rutas, camiones, cuadrillas, centros] = await Promise.allSettled([
    apiGet(API.recoleccion + "/rutas"),
    apiGet(API.recoleccion + "/camiones"),
    apiGet(API.recoleccion + "/cuadrillas"),
    apiGet(API.gestion + "/centros"),
  ]);

  if (rutas.status !== "fulfilled" || camiones.status !== "fulfilled" || cuadrillas.status !== "fulfilled") {
    mostrarPlan("No se pudieron cargar las rutas, los camiones o las cuadrillas. Cerrá y probá de nuevo.", "error");
    return;
  }

  rutasActivas = (rutas.value.datos || []).filter(function (r) { return r.estado === "activa"; });
  formRecoleccion.id_ruta.innerHTML = opcion("", "Elegí una ruta") + rutasActivas.map(function (r) {
    return opcion(r.id_ruta, r.nombre + " · " + r.zona + " · " + r.dia_semana + " " + r.hora_inicio);
  }).join("");

  formRecoleccion.id_camion.innerHTML = opcion("", "Elegí un camión") + (camiones.value.datos || [])
    .filter(function (c) { return c.estado === "operativo" || c.estado === "en_ruta"; })
    .map(function (c) {
      return opcion(c.id_camion, c.matricula + (c.marca ? " · " + c.marca : "") + " · " + etiquetaIncidencia("camion", c.estado).texto.toLowerCase());
    }).join("");

  formRecoleccion.id_cuadrilla.innerHTML = opcion("", "Elegí una cuadrilla") + (cuadrillas.value.datos || [])
    .filter(function (q) { return q.estado === "activa"; })
    .map(function (q) {
      return opcion(q.id_cuadrilla, q.nombre + " · turno " + q.turno + (q.ocupada ? " · en una recolección en curso" : ""));
    }).join("");

  const listaCentros = centros.status === "fulfilled" ? (centros.value.datos || []) : [];
  formRecoleccion.id_centro.innerHTML = opcion("", "Sin definir") + listaCentros
    .filter(function (c) { return c.estado === "activo"; })
    .map(function (c) { return opcion(c.id_centro, c.nombre); }).join("");

  catalogosCargados = true;
}

function proximaFecha(diaSemana, hora) {
  const objetivo = DIAS_SEMANA.indexOf(diaSemana);
  if (objetivo === -1 || !/^[0-9]{2}:[0-9]{2}$/.test(hora)) {
    return "";
  }
  const fecha = new Date();
  const partes = hora.split(":");
  fecha.setHours(parseInt(partes[0], 10), parseInt(partes[1], 10), 0, 0);
  let dias = (objetivo - fecha.getDay() + 7) % 7;
  if (dias === 0 && fecha < new Date()) {
    dias = 7;
  }
  fecha.setDate(fecha.getDate() + dias);
  const dos = function (n) { return String(n).padStart(2, "0"); };
  return fecha.getFullYear() + "-" + dos(fecha.getMonth() + 1) + "-" + dos(fecha.getDate()) + "T" + hora;
}

formRecoleccion.id_ruta.addEventListener("change", function () {
  const ruta = rutasActivas.find(function (r) { return String(r.id_ruta) === formRecoleccion.id_ruta.value; });
  if (ruta && formRecoleccion.fecha_hora_inicio.value === "") {
    formRecoleccion.fecha_hora_inicio.value = proximaFecha(ruta.dia_semana, ruta.hora_inicio);
  }
});

const CLAVE_PRECARGA = "sigeru.rutaSugerida";
const avisoPrecarga = document.getElementById("aviso-precarga");
let precargaPendiente = null;

// Viene de «Ruta sugerida»: se abre el alta con lo propuesto ya cargado. No se crea nada hasta que el administrador confirme.
// recolecciones.js reescribe la direccion al filtrar, asi que no se puede confiar en un parametro: se lee lo guardado por la otra pantalla
// y se descarta si es viejo (10 minutos) para que una propuesta olvidada no reabra el alta mas tarde.
function leerPrecarga() {
  try {
    const guardada = sessionStorage.getItem(CLAVE_PRECARGA);
    sessionStorage.removeItem(CLAVE_PRECARGA);
    const p = guardada ? JSON.parse(guardada) : null;
    return p && Array.isArray(p.codigos) && p.codigos.length > 0 && Date.now() - (p.guardada_en || 0) < 600000 ? p : null;
  } catch (e) {
    return null;
  }
}

function textoDeObservaciones(p) {
  const km = String(p.km).replace(".", ",");
  const encabezado = "Ruta sugerida (" + p.codigos.length + " paradas, " + km + " km aprox.): ";
  const lista = p.codigos.join(" > ");
  const completo = encabezado + lista;
  return completo.length <= 300 ? completo : completo.slice(0, 297) + "...";
}

function aplicarPrecarga(p) {
  const delaZona = rutasActivas.find(function (r) { return p.id_zona !== null && r.id_zona === p.id_zona; });
  if (delaZona) {
    formRecoleccion.id_ruta.value = String(delaZona.id_ruta);
    formRecoleccion.id_ruta.dispatchEvent(new Event("change"));
  }
  if (p.id_camion) { formRecoleccion.id_camion.value = String(p.id_camion); }
  if (p.id_centro) { formRecoleccion.id_centro.value = String(p.id_centro); }
  formRecoleccion.observaciones.value = textoDeObservaciones(p);
  avisoPrecarga.textContent = "Precargado desde la ruta sugerida (" + p.codigos.length + " paradas). Revisá la ruta, la fecha, el camión y la cuadrilla: no se crea nada hasta que toques «Planificar».";
  avisoPrecarga.classList.remove("oculto");
}

document.getElementById("modal-recoleccion").addEventListener("show.bs.modal", async function () {
  mostrarPlan("", "");
  if (!catalogosCargados) {
    await cargarCatalogos();
  }
  if (precargaPendiente) {
    aplicarPrecarga(precargaPendiente);
    precargaPendiente = null;
  } else if (!avisoPrecarga.classList.contains("oculto") && formRecoleccion.observaciones.value === "") {
    avisoPrecarga.classList.add("oculto");
  }
});

precargaPendiente = leerPrecarga();
if (precargaPendiente) {
  bootstrap.Modal.getOrCreateInstance(document.getElementById("modal-recoleccion")).show();
}

formRecoleccion.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrarPlan("", "");

  if (!validarFormulario(formRecoleccion)) {
    return;
  }

  const boton = bloquearEnvio(formRecoleccion, "Planificando...");
  const datos = {
    id_ruta:           parseInt(formRecoleccion.id_ruta.value, 10),
    fecha_hora_inicio: formRecoleccion.fecha_hora_inicio.value,
    id_camion:         parseInt(formRecoleccion.id_camion.value, 10),
    id_cuadrilla:      parseInt(formRecoleccion.id_cuadrilla.value, 10),
    observaciones:     formRecoleccion.observaciones.value.trim(),
  };
  if (formRecoleccion.id_centro.value !== "") {
    datos.id_centro = parseInt(formRecoleccion.id_centro.value, 10);
  }

  try {
    const { ok, resultado } = await apiPost(API.recoleccion + "/recolecciones", datos);
    if (ok) {
      cerrarModal("modal-recoleccion");
      avisoPagina((resultado.mensaje || "Recolección planificada") + ".");
      catalogosCargados = false;
      cargarRecolecciones();
    } else {
      mostrarPlan(resultado.mensaje || "No se pudo planificar la recolección.", "error");
    }
  } catch (e) {
    mostrarPlan("No se pudo conectar con el servidor. Revisá que esté corriendo.", "error");
  } finally {
    liberarEnvio(boton);
  }
});

activarValidacion(formRecoleccion);
prepararModal("modal-recoleccion", formRecoleccion);
