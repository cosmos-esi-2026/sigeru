Sesion.exigir();

// «Asignación de rutas» (plan de rutas para la defensa, bloque 6): las rutas del dia por prioridad, las cuadrillas y camiones libres, y una sugerencia
// que el administrador revisa y confirma. Nada se crea hasta «Asignar todas» (una sola transaccion en el servidor) o «Asignar» en una ruta.

const formDia = document.getElementById("form-dia");
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const cuerpoSugerencia = document.getElementById("cuerpo-sugerencia");
const cajaSugerencia = document.getElementById("tabla-sugerencia-caja");
const btnSugerir = document.getElementById("btn-sugerir");
const btnAsignarTodas = document.getElementById("btn-asignar-todas");
const btnDescartarSugerencia = document.getElementById("btn-descartar-sugerencia");
const formAsignar = document.getElementById("form-asignar");
const modalAsignar = document.getElementById("modal-asignar");

const COLUMNAS = 7;
const NIVELES = { alta: { texto: "▲ Alta", clase: "rojo" }, media: { texto: "● Media", clase: "naranja" }, normal: { texto: "Normal", clase: "gris" } };

// A que hora empieza cada turno (el nocturno cruza la medianoche). Es la misma tabla que Asignacion::TURNO_DESDE en PHP: tests/unitarias-asignacion.php comprueba que coinciden.
const TURNO_DESDE = { matutino: 5, vespertino: 13, nocturno: 21 };

let datos = null;            // lo ultimo que devolvio el servidor
let filas = [];              // la sugerencia en pantalla (editable)
let rutaEnModal = null;

function hoyMasUno() {
  const d = new Date();
  d.setDate(d.getDate() + 1);
  return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
}

function turnoDeLaHora(hora) {
  const h = parseInt(String(hora || "").slice(0, 2), 10);
  if (isNaN(h)) {
    return null;
  }
  return h >= TURNO_DESDE.nocturno || h < TURNO_DESDE.matutino ? "nocturno" : (h >= TURNO_DESDE.vespertino ? "vespertino" : "matutino");
}

// Lo que el administrador tiene que mirar antes de confirmar: la cuadrilla de otro municipio y el turno que no coincide. Solo avisa, no impide nada.
function avisosDe(ruta, cuadrilla, hora) {
  const avisos = [];
  if (ruta && cuadrilla && ruta.id_zona && cuadrilla.id_zona && ruta.id_zona !== cuadrilla.id_zona) {
    avisos.push("Fuera de su municipio (" + cuadrilla.zona + ")");
  }
  const turno = turnoDeLaHora(hora);
  if (cuadrilla && turno && cuadrilla.turno !== turno) {
    avisos.push("Turno " + cuadrilla.turno + ", la ruta sale de " + turno);
  }
  return avisos;
}

function dibujarAvisos(contenedor, avisos) {
  contenedor.innerHTML = avisos.map(function (a) { return '<span class="estado naranja">' + escaparHtml(a) + "</span>"; }).join("");
}

// La cuadrilla del mismo municipio que la ruta va primero (despues las sin municipio y al final las de otro), y dentro de cada grupo las libres antes que las ocupadas.
function ordenadasPara(ruta, cuadrillas) {
  const grupo = function (c) {
    if (!ruta || !ruta.id_zona || !c.id_zona) { return 1; }
    return c.id_zona === ruta.id_zona ? 0 : 2;
  };
  return cuadrillas.slice().sort(function (a, b) {
    return (a.libre ? 0 : 3) + grupo(a) - ((b.libre ? 0 : 3) + grupo(b)) || a.id_cuadrilla - b.id_cuadrilla;
  });
}

function opcionesCuadrilla(ruta, cuadrillas, elegido) {
  return ordenadasPara(ruta, cuadrillas).map(function (c) {
    const otro = ruta && ruta.id_zona && c.id_zona && c.id_zona !== ruta.id_zona;
    return '<option value="' + $entero(c.id_cuadrilla) + '"' + (c.id_cuadrilla === elegido ? " selected" : "") + (c.libre ? "" : ' class="opcion-ocupada"') + ">" +
      escaparHtml(c.nombre) + (otro ? " (otro municipio)" : "") + (c.libre ? "" : " (ocupada ese día)") + "</option>";
  }).join("");
}

function etiquetaNivel(p) {
  const n = NIVELES[p.nivel] || NIVELES.normal;
  return '<span class="estado ' + n.clase + '">' + escaparHtml(n.texto) + "</span>";
}

function queHay(r) {
  const p = r.prioridad;
  const partes = [r.cantidad + " contenedores"];
  if (p.urgentes) {
    partes.push(p.urgentes + " desbordado" + (p.urgentes > 1 ? "s" : ""));
  }
  if (p.llenos) {
    partes.push(p.llenos + " lleno" + (p.llenos > 1 ? "s" : ""));
  }
  if (p.no_recolectables) {
    partes.push(p.no_recolectables + " no se recolecta" + (p.no_recolectables > 1 ? "n" : ""));
  }
  return partes.join(" · ");
}

function fechaLegible(texto) {
  return String(texto || "").slice(0, 16).replace("T", " ");
}

function dibujarTabla() {
  const soloSinAsignar = formDia.elements.solo_sin_asignar.checked;
  const rutas = datos.rutas.filter(function (r) { return !soloSinAsignar || !r.asignada; });
  document.getElementById("contador-resultados").innerHTML = "Mostrando <strong>" + rutas.length + "</strong> de " + datos.rutas.length + " rutas.";
  if (rutas.length === 0) {
    cuerpoTabla.innerHTML = filaVacia(COLUMNAS, "No hay rutas para mostrar", soloSinAsignar ? "Todas las rutas de ese día ya tienen cuadrilla." : "Ninguna ruta activa tiene contenedores ese día de la semana.");
    return;
  }
  cuerpoTabla.innerHTML = rutas.map(function (r) {
    const a = r.asignada;
    const asignacion = a
      ? escaparHtml(a.cuadrilla + " · " + a.matricula + " · " + fechaLegible(a.fecha_hora_inicio)) + ' <span class="estado verde">' + escaparHtml(textoEstado(a.estado)) + "</span>"
      : '<span class="estado gris">Sin asignar</span>';
    const accion = a
      ? '<a class="boton boton-secundario" href="recoleccion.html?id=' + $entero(a.id_recoleccion) + '" aria-label="Ver la recolección ' + escaparHtml(a.numero) + '">Ver</a>'
      : '<button type="button" class="boton boton-secundario js-asignar" data-id="' + $entero(r.id_ruta) + '" aria-label="Asignar ' + escaparHtml(r.nombre) + ' a una cuadrilla">Asignar</button>';
    return '<tr role="row">' +
      '<td role="cell" data-etiqueta="Prioridad">' + etiquetaNivel(r.prioridad) + "</td>" +
      '<td role="cell" data-etiqueta="Ruta"><a href="ruta-armar.html?id=' + $entero(r.id_ruta) + '">' + escaparHtml(r.nombre) + "</a></td>" +
      celda("Zona", r.zona) +
      celda("Hora", r.hora_inicio + (r.es_del_dia ? "" : " (" + r.dia_semana + ")")) +
      celda("Qué hay", queHay(r)) +
      '<td role="cell" data-etiqueta="Asignación">' + asignacion + "</td>" +
      '<td role="cell" data-etiqueta="Acciones"><div class="acciones-fila">' + accion + "</div></td>" +
      "</tr>";
  }).join("");
}

function dibujarCifras() {
  const rutas = datos.rutas;
  const sin = rutas.filter(function (r) { return !r.asignada; }).length;
  const urgentes = rutas.filter(function (r) { return r.prioridad.nivel === "alta" && !r.asignada; }).length;
  const cifra = function (t, v) { return "<div><dt>" + escaparHtml(t) + "</dt><dd>" + escaparHtml(String(v)) + "</dd></div>"; };
  document.getElementById("cifras-dia").innerHTML =
    cifra("Rutas ese día", rutas.length) + cifra("Sin cuadrilla", sin) + cifra("Urgentes sin cuadrilla", urgentes) +
    cifra("Cuadrillas libres", datos.cuadrillas.filter(function (c) { return c.libre; }).length) +
    cifra("Camiones libres", datos.camiones.filter(function (c) { return c.libre; }).length);
}

// No llamarla «opciones»: api.js ya tiene una global con ese nombre (arma cada fetch) y esta la pisaba, asi que fallaba todo pedido de la pantalla.
function opcionesSelect(lista, clave, texto, elegido) {
  return lista.map(function (x) {
    return '<option value="' + $entero(x[clave]) + '"' + (x[clave] === elegido ? " selected" : "") + (x.libre ? "" : " class=\"opcion-ocupada\"") + ">" + escaparHtml(texto(x)) + (x.libre ? "" : " (ocupada ese día)") + "</option>";
  }).join("");
}

function nombreCuadrilla(c) { return c.nombre; }
function nombreCamion(c) { return c.matricula; }

function dibujarSugerencia() {
  if (filas.length === 0) {
    cajaSugerencia.classList.add("oculto");
    btnAsignarTodas.classList.add("oculto");
    btnDescartarSugerencia.classList.add("oculto");
    return;
  }
  cajaSugerencia.classList.remove("oculto");
  btnAsignarTodas.classList.remove("oculto");
  btnDescartarSugerencia.classList.remove("oculto");
  cuerpoSugerencia.innerHTML = filas.map(function (f, i) {
    const r = datos.rutas.find(function (x) { return x.id_ruta === f.id_ruta; });
    return '<tr role="row" data-i="' + i + '">' +
      '<td role="cell" data-etiqueta="Ruta">' + escaparHtml(r.nombre) + "</td>" +
      '<td role="cell" data-etiqueta="Prioridad">' + etiquetaNivel(r.prioridad) + "</td>" +
      '<td role="cell" data-etiqueta="Cuadrilla"><label class="solo-lectores" for="sug-cuadrilla-' + i + '">Cuadrilla para ' + escaparHtml(r.nombre) + '</label><select id="sug-cuadrilla-' + i + '" class="js-sug-cuadrilla">' + opcionesCuadrilla(r, datos.cuadrillas, f.id_cuadrilla) + '</select><div class="avisos-fila js-sug-avisos" role="status" aria-live="polite"></div></td>' +
      '<td role="cell" data-etiqueta="Camión"><label class="solo-lectores" for="sug-camion-' + i + '">Camión para ' + escaparHtml(r.nombre) + '</label><select id="sug-camion-' + i + '" class="js-sug-camion">' + opcionesSelect(datos.camiones, "id_camion", nombreCamion, f.id_camion) + "</select></td>" +
      '<td role="cell" data-etiqueta="Hora"><label class="solo-lectores" for="sug-hora-' + i + '">Hora de inicio de ' + escaparHtml(r.nombre) + '</label><input type="time" id="sug-hora-' + i + '" class="js-sug-hora" value="' + escaparHtml(f.hora) + '"></td>' +
      '<td role="cell" data-etiqueta="Quitar"><button type="button" class="boton boton-secundario js-sug-quitar" data-i="' + i + '" aria-label="Quitar ' + escaparHtml(r.nombre) + ' de la sugerencia">Quitar</button></td>' +
      "</tr>";
  }).join("");
  filas.forEach(function (f, i) { actualizarAvisos(i); });
}

// Los avisos de una fila de la sugerencia se calculan con la cuadrilla y la hora que estan elegidas ahora (el administrador puede cambiarlas).
function actualizarAvisos(i) {
  const fila = cuerpoSugerencia.querySelector('tr[data-i="' + i + '"]');
  if (!fila) {
    return;
  }
  const ruta = datos.rutas.find(function (x) { return x.id_ruta === filas[i].id_ruta; });
  const id = parseInt(fila.querySelector(".js-sug-cuadrilla").value, 10);
  const cuadrilla = datos.cuadrillas.find(function (c) { return c.id_cuadrilla === id; });
  dibujarAvisos(fila.querySelector(".js-sug-avisos"), avisosDe(ruta, cuadrilla, fila.querySelector(".js-sug-hora").value));
}

function notasSugerencia(s) {
  const partes = [];
  if (s.rutas_sin_asignar.length) {
    partes.push(s.rutas_sin_asignar.length + (s.rutas_sin_asignar.length > 1 ? " rutas quedan" : " ruta queda") + " sin cuadrilla (no alcanzan las cuadrillas o los camiones libres), las menos urgentes.");
  }
  if (s.cuadrillas_que_sobran.length) {
    partes.push("Sobran " + s.cuadrillas_que_sobran.length + " cuadrilla" + (s.cuadrillas_que_sobran.length > 1 ? "s" : "") + ": ya cubren todas las rutas de ese día; se pueden asignar a rutas de otros días con «Mostrar también las rutas de otros días».");
  }
  const fuera = s.propuestas.filter(function (p) { return p.fuera_de_zona; }).length;
  if (fuera) {
    partes.push(fuera + " propuesta" + (fuera > 1 ? "s usan" : " usa") + " una cuadrilla de otro municipio porque la del propio no estaba libre: está marcada, revisala antes de asignar.");
  }
  if (!partes.length && s.propuestas.length) {
    partes.push("Alcanzan las cuadrillas y los camiones para todas las rutas de ese día.");
  }
  if (!s.propuestas.length) {
    partes.push("No hay nada para sugerir: o todas las rutas ya tienen cuadrilla, o no hay cuadrillas o camiones libres ese día.");
  }
  document.getElementById("notas-sugerencia").textContent = partes.join(" ");
}

async function cargar() {
  const fecha = formDia.elements.fecha.value;
  if (!fecha) {
    return;
  }
  cuerpoTabla.innerHTML = filaEsqueleto(COLUMNAS, 5);
  filas = [];
  dibujarSugerencia();
  document.getElementById("notas-sugerencia").textContent = "";
  try {
    const respuesta = await apiGet(API.recoleccion + "/asignacion?fecha=" + encodeURIComponent(fecha) + (formDia.elements.otros_dias.checked ? "&otros_dias=1" : ""));
    datos = respuesta.datos;
    dibujarCifras();
    dibujarTabla();
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(COLUMNAS, mensajeDeFallo(error));
  }
}

function sugerir() {
  if (!datos) {
    return;
  }
  const s = datos.sugerencia;
  filas = s.propuestas.map(function (p) {
    const r = datos.rutas.find(function (x) { return x.id_ruta === p.id_ruta; });
    return { id_ruta: p.id_ruta, id_cuadrilla: p.id_cuadrilla, id_camion: p.id_camion, hora: r.hora_inicio };
  });
  dibujarSugerencia();
  notasSugerencia(s);
}

async function asignarTodas() {
  const lectura = filas.map(function (f, i) {
    const fila = cuerpoSugerencia.querySelector('tr[data-i="' + i + '"]');
    return {
      id_ruta: f.id_ruta,
      id_cuadrilla: parseInt(fila.querySelector(".js-sug-cuadrilla").value, 10),
      id_camion: parseInt(fila.querySelector(".js-sug-camion").value, 10),
      fecha_hora_inicio: formDia.elements.fecha.value + " " + (fila.querySelector(".js-sug-hora").value || f.hora),
    };
  });
  const confirmado = await confirmar({
    titulo: "Asignar " + lectura.length + " ruta" + (lectura.length > 1 ? "s" : ""),
    mensaje: "Se planifican " + lectura.length + " recolecciones y se avisa a cada cuadrilla. Si una no se puede, no se crea ninguna.",
    textoBoton: "Asignar todas",
    peligro: false,
  });
  if (!confirmado) {
    return;
  }
  btnAsignarTodas.disabled = true;
  try {
    const { ok, resultado } = await apiPost(API.recoleccion + "/recolecciones/lote", { asignaciones: lectura });
    if (ok) {
      avisoPagina(resultado.mensaje || "Rutas asignadas.");
      await cargar();
    } else {
      avisoPaginaError(resultado.mensaje || "No se pudieron asignar las rutas.");
    }
  } catch (error) {
    avisoPaginaError("No se pudo conectar con el servidor. " + mensajeDeFallo(error));
  } finally {
    btnAsignarTodas.disabled = false;
  }
}

function abrirModal(idRuta) {
  rutaEnModal = datos.rutas.find(function (r) { return r.id_ruta === idRuta; });
  document.getElementById("texto-asignar").textContent = rutaEnModal.nombre + " · prioridad " + (NIVELES[rutaEnModal.prioridad.nivel] || NIVELES.normal).texto.replace(/^\S+\s/, "").toLowerCase() + " · " + queHay(rutaEnModal);
  const libre = function (l) { return l.libre ? 0 : 1; };
  const cuadrillas = ordenadasPara(rutaEnModal, datos.cuadrillas);
  const camiones = datos.camiones.slice().sort(function (a, b) { return libre(a) - libre(b); });
  formAsignar.id_cuadrilla.innerHTML = opcionesCuadrilla(rutaEnModal, cuadrillas, cuadrillas[0] && cuadrillas[0].id_cuadrilla);
  formAsignar.id_camion.innerHTML = opcionesSelect(camiones, "id_camion", nombreCamion, camiones[0] && camiones[0].id_camion);
  formAsignar.fecha_hora_inicio.value = formDia.elements.fecha.value + "T" + rutaEnModal.hora_inicio;
  document.getElementById("mensaje").textContent = "";
  document.getElementById("mensaje").className = "mensaje";
  avisosDelModal();
  bootstrap.Modal.getOrCreateInstance(modalAsignar).show();
}

function avisosDelModal() {
  const id = parseInt(formAsignar.id_cuadrilla.value, 10);
  dibujarAvisos(document.getElementById("avisos-asignar"), avisosDe(rutaEnModal, datos.cuadrillas.find(function (c) { return c.id_cuadrilla === id; }), formAsignar.fecha_hora_inicio.value.slice(11, 16)));
}
formAsignar.addEventListener("change", avisosDelModal);

formAsignar.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  const boton = bloquearEnvio(formAsignar, "Asignando...");
  try {
    const { ok, resultado } = await apiPost(API.recoleccion + "/recolecciones", {
      id_ruta: rutaEnModal.id_ruta,
      id_cuadrilla: parseInt(formAsignar.id_cuadrilla.value, 10),
      id_camion: parseInt(formAsignar.id_camion.value, 10),
      fecha_hora_inicio: formAsignar.fecha_hora_inicio.value,
    });
    if (ok) {
      bootstrap.Modal.getOrCreateInstance(modalAsignar).hide();
      avisoPagina(resultado.mensaje || "Ruta asignada.");
      await cargar();
    } else {
      const m = document.getElementById("mensaje");
      m.textContent = resultado.mensaje || "No se pudo asignar la ruta.";
      m.className = "mensaje error";
    }
  } catch (error) {
    const m = document.getElementById("mensaje");
    m.textContent = "No se pudo conectar con el servidor. " + mensajeDeFallo(error);
    m.className = "mensaje error";
  } finally {
    liberarEnvio(boton);
  }
});

cuerpoTabla.addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargar();
    return;
  }
  const boton = evento.target.closest(".js-asignar");
  if (boton) {
    abrirModal(parseInt(boton.dataset.id, 10));
  }
});
cuerpoSugerencia.addEventListener("change", function (evento) {
  const fila = evento.target.closest("tr[data-i]");
  if (fila && (evento.target.matches(".js-sug-cuadrilla") || evento.target.matches(".js-sug-hora"))) {
    actualizarAvisos(parseInt(fila.dataset.i, 10));
  }
});
cuerpoSugerencia.addEventListener("click", function (evento) {
  const boton = evento.target.closest(".js-sug-quitar");
  if (boton) {
    filas.splice(parseInt(boton.dataset.i, 10), 1);
    dibujarSugerencia();
  }
});
formDia.addEventListener("submit", function (evento) {
  evento.preventDefault();
  cargar();
});
formDia.addEventListener("change", function (evento) {
  if (evento.target.name === "solo_sin_asignar" && datos) {
    dibujarTabla();
  } else {
    cargar();
  }
});
btnSugerir.addEventListener("click", sugerir);
btnAsignarTodas.addEventListener("click", asignarTodas);
btnDescartarSugerencia.addEventListener("click", function () {
  filas = [];
  dibujarSugerencia();
  document.getElementById("notas-sugerencia").textContent = "";
});

formDia.elements.fecha.value = hoyMasUno();
activarValidacion(formAsignar);
// Venir desde «Rutas» con ?ruta=N: se muestran las rutas de todos los dias y se abre directamente el formulario de esa ruta.
const rutaPedida = parseInt(new URLSearchParams(window.location.search).get("ruta"), 10) || null;
if (rutaPedida) {
  formDia.elements.otros_dias.checked = true;
}
cargar().then(function () {
  const r = rutaPedida && datos && datos.rutas.find(function (x) { return x.id_ruta === rutaPedida; });
  if (r && !r.asignada) {
    abrirModal(rutaPedida);
  } else if (rutaPedida && datos) {
    avisoPaginaError(r ? "Esa ruta ya tiene una recolección asignada ese día." : "Esa ruta no está disponible para asignar (¿está inactiva o sin contenedores?).");
  }
});
