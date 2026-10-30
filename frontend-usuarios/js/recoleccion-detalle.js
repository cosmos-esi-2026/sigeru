Sesion.exigir();

const idRecoleccion = parametroEntero("id");
const zonaDatos = document.getElementById("zona-datos");
const zonaAcciones = document.getElementById("zona-acciones");
const panelAcciones = document.getElementById("panel-acciones");
const cuerpoVaciados = document.getElementById("cuerpo-vaciados");

const LISTA_POR_ROL = {
  Administrador: "recolecciones.html",
  Cuadrilla: "mis-tareas.html",
};

let recoleccionActual = null;
let sugerenciasCargadas = false;
let mapaRecorrido = null;
let capaRecorrido = null;
const NOMBRE_SERVICIO = { "valhalla-truck": "Valhalla (perfil de camión)", "osrm-fossgis-auto": "OSRM (FOSSGIS)", "osrm-demo-auto": "OSRM (servidor de demostración)" };

function rolActual() {
  return document.body.dataset.rol || Sesion.rol() || "";
}

function filaFicha(titulo, valorHtml) {
  return "<div><dt>" + escaparHtml(titulo) + "</dt><dd>" + valorHtml + "</dd></div>";
}

function kg(valor) {
  return valor === null || valor === undefined
    ? "-"
    : Number(valor).toLocaleString("es-UY", { maximumFractionDigits: 2 }) + " kg";
}

function dibujarDatos(r) {
  const planificada = r.estado === "planificada";
  zonaDatos.innerHTML = '<dl class="ficha ficha-columnas">' +
    filaFicha("Estado", marcaDeEstado("recoleccion", r.estado) + marcaVencida(r)) +
    filaFicha(planificada ? "Inicio previsto" : "Inicio", escaparHtml(fechaLegible(r.fecha_hora_inicio))) +
    (r.fecha_hora_fin ? filaFicha("Fin", escaparHtml(fechaLegible(r.fecha_hora_fin))) : "") +
    filaFicha("Ruta", escaparHtml(r.ruta)) +
    filaFicha("Zona", escaparHtml(r.zona)) +
    filaFicha("Camión", escaparHtml(r.matricula) + " " + marcaDeEstado("camion", r.estado_camion)) +
    filaFicha("Cuadrilla", escaparHtml(r.cuadrilla)) +
    filaFicha("Centro de destino", escaparHtml(r.centro || "Sin definir")) +
    filaFicha("Vaciados", escaparHtml(r.vaciados + (r.vaciados === 1 ? " contenedor · " : " contenedores · ") + kg(r.peso_total_kg))) +
    "</dl>" +
    (r.observaciones
      ? '<h3 class="subtitulo-panel">Observaciones</h3><p class="texto-largo">' + escaparHtml(r.observaciones) + "</p>"
      : "");
}

// ---- El recorrido: la ruta de ESTA recoleccion (el servidor solo se la da a la cuadrilla asignada y al administrador) ----

function iniciarMapaRecorrido() {
  if (mapaRecorrido) {
    return;
  }
  mapaRecorrido = L.map("mapa-recorrido", { scrollWheelZoom: false }).setView([-34.885, -56.165], 12);
  capaRecorrido = L.layerGroup().addTo(mapaRecorrido);
  const fondo = L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    referrerPolicy: "origin",
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> (ODbL) · <a href="https://www.openstreetmap.org/fixthemap">Reportar un error del mapa</a>',
  });
  const aviso = document.getElementById("aviso-sin-red");
  const sinRed = function (valor) {
    aviso.classList.toggle("oculto", !valor);
    document.getElementById("mapa-recorrido").classList.toggle("sin-fondo", valor);
  };
  fondo.on("tileerror", function () { sinRed(true); });
  fondo.on("tileload", function () { sinRed(false); });
  fondo.addTo(mapaRecorrido);
  if (navigator.onLine === false) {
    sinRed(true);
  }
}

function claseDeParada(p) {
  if (p.visitada) {
    return "hecha";
  }
  if (!p.recolectable) {
    return "mant";
  }
  return p.prioridad === "alta" ? "alta" : (p.prioridad === "media" ? "media" : "");
}

function simboloDeParada(p) {
  return p.visitada ? "✓" : (!p.recolectable ? "M" : (p.prioridad === "alta" ? "!" : String(p.orden)));
}

function textoDeParada(p) {
  return p.visitada ? "ya vaciada" : (!p.recolectable ? "no se recolecta (en mantenimiento o roto)" : (p.prioridad === "alta" ? "urgente: desbordado" : (p.prioridad === "media" ? "lleno" : "")));
}

function dibujarRecorrido(r) {
  const rec = r.recorrido;
  const panel = document.getElementById("panel-recorrido");
  if (!rec || !rec.paradas || rec.paradas.length === 0) {
    panel.classList.add("oculto");
    return;
  }
  panel.classList.remove("oculto");
  const pendientes = rec.paradas.filter(function (p) { return !p.visitada && p.recolectable; }).length;
  document.getElementById("resumen-recorrido").textContent = rec.nombre + " · " + rec.visitadas + " de " + rec.paradas.length + " contenedores vaciados" +
    (pendientes ? " · faltan " + pendientes : "") + ".";

  const enCurso = r.estado === "en_curso";
  document.getElementById("lista-recorrido").innerHTML = rec.paradas.map(function (p) {
    const nota = textoDeParada(p);
    return '<li class="parada' + (p.visitada ? " parada-hecha" : "") + '" data-codigo="' + escaparHtml(p.codigo) + '">' +
      '<span class="parada-orden parada-' + claseDeParada(p) + '" aria-hidden="true">' + escaparHtml(simboloDeParada(p)) + "</span>" +
      '<div class="parada-texto"><strong>' + p.orden + ". " + escaparHtml(p.codigo) + "</strong><span>" + escaparHtml(p.direccion) + "</span>" +
      (nota ? '<span class="etiqueta ' + (p.prioridad === "alta" && !p.visitada ? "etiqueta-lleno" : (p.recolectable ? "" : "etiqueta-mant")) + '">' + escaparHtml(nota) + "</span>" : "") + "</div>" +
      (enCurso && !p.visitada && p.recolectable
        ? '<div class="parada-acciones"><button type="button" class="boton boton-secundario js-vaciar" data-codigo="' + escaparHtml(p.codigo) + '" aria-label="Registrar el vaciado de ' + escaparHtml(p.codigo) + '">Registrar vaciado</button></div>'
        : "") + "</li>";
  }).join("");

  iniciarMapaRecorrido();
  capaRecorrido.clearLayers();
  const puntos = [];
  const t = rec.trazado;
  if (t && t.vigente && t.geojson) {
    L.geoJSON(t.geojson, { style: { color: "#15654a", weight: 4, opacity: 0.85 } }).addTo(capaRecorrido);
    document.getElementById("atribucion-recorrido").innerHTML = "Recorrido calculado con " + escaparHtml(NOMBRE_SERVICIO[t.servicio] || t.servicio || "un servicio de rutas") +
      " (FOSSGIS) sobre datos de OpenStreetMap (ODbL). <a href=\"https://www.openstreetmap.org/fixthemap\">Reportar un error del mapa</a>.";
  } else {
    document.getElementById("atribucion-recorrido").textContent = "El recorrido se dibuja en línea recta entre las paradas: todavía no se trazó por las calles.";
  }
  rec.paradas.forEach(function (p) {
    puntos.push([p.latitud, p.longitud]);
    const titulo = p.orden + ". " + p.codigo + (textoDeParada(p) ? " (" + textoDeParada(p) + ")" : "");
    L.marker([p.latitud, p.longitud], {
      icon: L.divIcon({ className: "marca-orden " + claseDeParada(p), html: simboloDeParada(p), iconSize: [28, 28], iconAnchor: [14, 14], popupAnchor: [0, -14] }),
      title: titulo, alt: titulo,
    }).bindPopup('<div class="popup-mapa"><strong>' + escaparHtml(p.orden + ". " + p.codigo) + "</strong><span>" + escaparHtml(p.direccion) + "</span><span>" + escaparHtml(textoDeParada(p) || "Pendiente") + "</span></div>").addTo(capaRecorrido);
  });
  if (!(t && t.vigente && t.geojson) && puntos.length > 1) {
    L.polyline(puntos, { color: "#15654a", weight: 3, opacity: 0.7, dashArray: "6 8" }).addTo(capaRecorrido);
  }
  setTimeout(function () {
    mapaRecorrido.invalidateSize();
    mapaRecorrido.fitBounds(L.latLngBounds(puntos), { padding: [30, 30], maxZoom: 16 });
  }, 50);
}

function dibujarVaciados(vaciados) {
  if (!vaciados || vaciados.length === 0) {
    cuerpoVaciados.innerHTML = filaVacia(5, "Todavía no hay vaciados",
      recoleccionActual.estado === "en_curso" ? "Registrá cada contenedor a medida que se vacía." : "");
    return;
  }
  cuerpoVaciados.innerHTML = vaciados.map(function (v) {
    return '<tr role="row">' +
      celda("Contenedor", v.contenedor) +
      celda("Dirección", v.direccion) +
      celda("Hora", fechaLegible(v.fecha_hora).slice(11) || "-") +
      celda("Peso", kg(v.peso_kg)) +
      celda("Volumen", v.volumen_l === null ? null : Number(v.volumen_l).toLocaleString("es-UY") + " L") +
      "</tr>";
  }).join("");
}

function formAccion(accion, titulo, contenidoHtml, textoBoton, clase) {
  return '<form class="formulario form-accion" data-accion="' + accion + '" id="form-' + accion + '">' +
    '<h3 class="subtitulo-panel">' + escaparHtml(titulo) + "</h3>" + contenidoHtml +
    '<button type="submit" class="boton ' + (clase || "boton-secundario") + '">' + escaparHtml(textoBoton) + "</button>" +
    "</form>";
}

function campoTexto(nombre, etiqueta, obligatorio, ayuda) {
  return "<label>" + escaparHtml(etiqueta) + (obligatorio ? " *" : "") +
    '<textarea name="' + nombre + '" rows="2" maxlength="300"' + (obligatorio ? " required" : "") +
    ' placeholder="' + escaparHtml(ayuda) + '"></textarea></label>';
}

function formVaciado() {
  return formAccion("vaciados", "Registrar un vaciado",
    '<label>Contenedor *<input type="text" name="codigo_contenedor" list="lista-contenedores" required ' +
    'autocomplete="off" spellcheck="false" maxlength="20" placeholder="CNT-004"></label>' +
    '<div class="formulario-columnas">' +
    '<label>Peso (kg) *<input type="number" name="peso_kg" required min="0.01" max="5000" step="0.01" inputmode="decimal"></label>' +
    '<label>Volumen (L)<input type="number" name="volumen_l" min="0" step="0.01" inputmode="decimal" placeholder="Opcional"></label>' +
    "</div>",
    "Registrar el vaciado", "boton-primario");
}

function dibujarAcciones(r) {
  const rol = rolActual();
  const esAdmin = rol === "Administrador";
  let html = "";

  if (r.estado === "planificada") {
    html = formAccion("inicio", "Salir a recolectar",
        '<p class="ayuda">El camión ' + escaparHtml(r.matricula) + " pasa a «en ruta» y la hora de inicio queda registrada.</p>",
        "Iniciar la recolección", "boton-primario") +
      (esAdmin ? formAccion("cancelacion", "Cancelar", campoTexto("observacion", "Motivo", true, "Por ejemplo: se reprograma"), "Cancelar la recolección", "boton-peligro") : "");
  } else if (r.estado === "en_curso") {
    html = formVaciado() +
      formAccion("fin", "Terminar el recorrido", campoTexto("observacion", "Observaciones", false, "Opcional"), "Finalizar la recolección") +
      (esAdmin ? formAccion("cancelacion", "Cancelar", campoTexto("observacion", "Motivo", true, "Por ejemplo: se rompió el camión"), "Cancelar la recolección", "boton-peligro") : "");
  } else {
    html = '<p class="ayuda">La recolección está ' + escaparHtml(etiquetaIncidencia("recoleccion", r.estado).texto.toLowerCase()) +
           " y ya no admite cambios.</p>";
  }

  panelAcciones.classList.remove("oculto");
  zonaAcciones.innerHTML = html;
  zonaAcciones.querySelectorAll("form").forEach(activarValidacion);
  if (r.estado === "en_curso") {
    cargarSugerencias();
  }
}

// Las sugerencias del campo «Contenedor» son las paradas de ESTA ruta que faltan vaciar (no todos los contenedores del sistema).
async function cargarSugerencias() {
  const rec = recoleccionActual && recoleccionActual.recorrido;
  document.getElementById("lista-contenedores").innerHTML = ((rec && rec.paradas) || [])
    .filter(function (p) { return !p.visitada && p.recolectable; })
    .map(function (p) {
      return '<option value="' + escaparHtml(p.codigo) + '">' + escaparHtml(p.orden + ". " + p.direccion) + "</option>";
    }).join("");
  sugerenciasCargadas = true;
}

function dibujar(r) {
  recoleccionActual = r;
  document.title = r.numero + " - SiGeRU";
  document.getElementById("titulo-pagina").textContent = "Recolección " + r.numero;
  document.getElementById("bajada-pagina").textContent = r.ruta + " · " + r.zona + " · " + fechaLegible(r.fecha_hora_inicio);
  dibujarDatos(r);
  dibujarRecorrido(r);
  dibujarVaciados(r.detalle_vaciados);
  dibujarAcciones(r);
}

function mostrarFallo(titulo, detalle, conReintento) {
  document.getElementById("detalle").innerHTML =
    '<section class="panel">' + bloqueEstado(titulo, detalle, conReintento) + "</section>";
}

async function cargarRecoleccion() {
  if (idRecoleccion === null) {
    mostrarFallo("Falta indicar la recolección", "Entrá desde la lista para abrir una recolección.", false);
    return;
  }
  try {
    const respuesta = await apiGet(API.recoleccion + "/recolecciones/" + idRecoleccion);
    dibujar(respuesta.datos);
  } catch (error) {
    if (error.estado === 404) {
      mostrarFallo("No encontramos esa recolección", "Puede que no exista o que no sea de tu cuadrilla.", false);
    } else {
      mostrarFallo("No se pudo cargar la recolección", mensajeDeFallo(error), true);
    }
  }
}

const CONFIRMACIONES = {
  fin: {
    titulo: "Finalizar la recolección",
    mensaje: "Se registra la hora de fin, el camión vuelve a «operativo» y ya no se pueden agregar vaciados.",
    textoBoton: "Finalizar",
  },
  cancelacion: {
    titulo: "Cancelar la recolección",
    mensaje: "Se cancela y ya no admite cambios. Si el camión estaba en ruta por esta recolección, vuelve a «operativo».",
    textoBoton: "Cancelar la recolección",
  },
};

function campoDelError(form, texto) {
  const minusculas = (texto || "").toLowerCase();
  const claves = [["volumen", "volumen_l"], ["peso", "peso_kg"], ["contenedor", "codigo_contenedor"], ["motivo", "observacion"]];
  const encontrada = claves.find(function (par) { return minusculas.indexOf(par[0]) !== -1 && form.elements[par[1]]; });
  return encontrada ? form.elements[encontrada[1]] : null;
}

function datosDe(accion, form) {
  if (accion === "vaciados") {
    const datos = {
      codigo_contenedor: form.elements.codigo_contenedor.value.trim().toUpperCase(),
      peso_kg: form.elements.peso_kg.value,
    };
    if (form.elements.volumen_l.value !== "") {
      datos.volumen_l = form.elements.volumen_l.value;
    }
    return datos;
  }
  if (form.elements.observacion) {
    return { observacion: form.elements.observacion.value.trim() };
  }
  return {};
}

zonaAcciones.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  const form = evento.target;
  const accion = form.dataset.accion;

  if (!validarFormulario(form)) {
    return;
  }
  if (CONFIRMACIONES[accion] && !(await confirmar(CONFIRMACIONES[accion]))) {
    return;
  }

  const boton = bloquearEnvio(form, "Guardando...");
  const urlAccion = API.recoleccion + "/recolecciones/" + idRecoleccion + "/" + accion;
  const datosAccion = datosDe(accion, form);
  try {
    const { ok, resultado } = await apiPost(urlAccion, datosAccion);
    if (ok) {
      dibujar(resultado.datos);
      avisoPagina((resultado.mensaje || "Cambio guardado") + ".");
      if (accion === "vaciados") {
        document.querySelector("#form-vaciados input").focus();
      } else {
        document.getElementById("mensaje-pagina").focus();
      }
    } else {
      liberarEnvio(boton);
      const texto = resultado.mensaje || "No se pudo guardar el cambio.";
      const campo = campoDelError(form, texto);
      if (campo) {
        marcarError(campo, texto);
        campo.focus();
      } else {
        avisoPaginaError(texto);
      }
    }
  } catch (e) {
    liberarEnvio(boton);
    avisoPaginaError("No se pudo conectar con el servidor. Revisá que esté corriendo.");
  }
});


document.getElementById("detalle").addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    window.location.reload();
  }
});

document.getElementById("mensaje-pagina").tabIndex = -1;
document.getElementById("enlace-volver").href = LISTA_POR_ROL[rolActual()] || "dashboard.html";
document.getElementById("lista-recorrido").addEventListener("click", function (evento) {
  const boton = evento.target.closest(".js-vaciar");
  const campo = document.querySelector('#form-vaciados [name="codigo_contenedor"]');
  if (boton && campo) {
    campo.value = boton.dataset.codigo;
    campo.dispatchEvent(new Event("input", { bubbles: true }));
    const peso = document.querySelector('#form-vaciados [name="peso_kg"]');
    document.getElementById("form-vaciados").scrollIntoView({ block: "center" });
    if (peso) {
      peso.focus();
    }
  }
});

cargarRecoleccion();
