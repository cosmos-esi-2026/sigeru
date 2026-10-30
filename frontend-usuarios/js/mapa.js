Sesion.exigir();

const CENTRO_MONTEVIDEO = [-34.885, -56.165];
const COLORES = { disponible: "#15654a", lleno: "#b3261e", mantenimiento: "#8a5000", retirado: "#64756c" };
// Cada estado se distingue por color y por simbolo: el color solo no alcanza para quien no lo percibe bien.
const SIMBOLOS = { disponible: "✓", lleno: "▲", mantenimiento: "M", retirado: "–" };
const EN_SERVICIO = ["disponible", "lleno", "mantenimiento"];
const COLUMNAS = 6;

const formFiltros = document.getElementById("form-filtros");
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const avisoSinRed = document.getElementById("aviso-sin-red");
const rol = document.body.dataset.rol || Sesion.rol() || "";

const PUEDE = {
  centros:     ["Administrador", "Operario"].indexOf(rol) !== -1,
  incidencias: ["Administrador", "Cuadrilla", "Vecino"].indexOf(rol) !== -1,
  ficha:       ["Administrador", "Cuadrilla"].indexOf(rol) !== -1,
  informar:    rol === "Vecino",
  rutas:       rol === "Administrador",
  misRutas:    rol === "Cuadrilla",
};
const PALETA_RUTAS = ["#0072B2", "#E69F00", "#009E73", "#CC79A7", "#D55E00", "#56B4E9", "#7a5195", "#5c6f66"];

let contenedores = [];
let centros = [];
let incidenciasAbiertas = [];
const marcadores = {};

const mapa = L.map("mapa", { scrollWheelZoom: false }).setView(CENTRO_MONTEVIDEO, 12);
const capaContenedores = L.layerGroup().addTo(mapa);
const capaCentros = L.layerGroup();
const capaIncidencias = L.layerGroup();
const capaRutas = L.layerGroup();
const rutasPorZona = {};
mapa.createPane("rutas");
mapa.getPane("rutas").style.zIndex = 380;   // las lineas de las rutas quedan por debajo de los contenedores

function mostrarSinRed(sinRed) {
  avisoSinRed.classList.toggle("oculto", !sinRed);
  document.getElementById("mapa").classList.toggle("sin-fondo", sinRed);
}

const fondo = L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
  maxZoom: 19,
  referrerPolicy: "origin",
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
});
fondo.on("tileerror", function () { mostrarSinRed(true); });
fondo.on("tileload", function () { mostrarSinRed(false); });
fondo.addTo(mapa);
if (navigator.onLine === false) {
  mostrarSinRed(true);
}

function tieneUbicacion(c) {
  return c.latitud !== null && c.latitud !== undefined && c.longitud !== null && c.longitud !== undefined &&
         !isNaN(parseFloat(c.latitud)) && !isNaN(parseFloat(c.longitud));
}

function posicion(c) {
  return [parseFloat(c.latitud), parseFloat(c.longitud)];
}

function enlaceDe(c) {
  if (PUEDE.ficha) {
    return '<a href="contenedor.html?id=' + $entero(c.id_contenedor) + '">Ver la ficha</a>';
  }
  if (PUEDE.informar && c.estado !== "retirado") {
    return '<a href="informar-problema.html?contenedor=' + escaparHtml(encodeURIComponent(c.codigo)) + '">Informar un problema acá</a>';
  }
  return "";
}

function popupContenedor(c) {
  const enlace = enlaceDe(c);
  return '<div class="popup-mapa"><strong>' + escaparHtml(c.codigo) + "</strong>" +
    "<span>" + escaparHtml(c.direccion) + "</span>" +
    "<span>" + escaparHtml([c.zona, c.tipo_residuo].filter(Boolean).join(" · ")) + "</span>" +
    marcaDeEstado("contenedor", c.estado) +
    (enlace ? '<span class="popup-accion">' + enlace + "</span>" : "") + "</div>";
}

function visibles() {
  const estado = formFiltros.elements.estado.value;
  const tipo = formFiltros.elements.tipo.value;
  const zona = formFiltros.elements.zona.value;
  return contenedores.filter(function (c) {
    const porEstado = estado === "todos" || (estado === "en_servicio" ? EN_SERVICIO.indexOf(c.estado) !== -1 : c.estado === estado);
    return porEstado && (tipo === "" || c.tipo_residuo === tipo) && (zona === "" || String(c.id_zona) === zona);
  });
}

// Con las rutas de una zona a la vista, los contenedores se dibujan chicos para no taparlas (el estado se distingue por color; la tabla de abajo lo dice con texto).
function enModoRutas() {
  return PUEDE.rutas && formFiltros.elements.rutas.checked && formFiltros.elements.zona.value !== "";
}

function dibujarContenedores(lista) {
  capaContenedores.clearLayers();
  Object.keys(marcadores).forEach(function (id) { delete marcadores[id]; });
  const compactos = enModoRutas();

  lista.filter(tieneUbicacion).forEach(function (c) {
    const titulo = c.codigo + ": " + textoEstado(c.estado);
    if (compactos) {
      const punto = L.circleMarker(posicion(c), { radius: 5, color: "#ffffff", weight: 1.5, fillColor: COLORES[c.estado] || COLORES.retirado, fillOpacity: 1 })
        .bindPopup(popupContenedor(c)).bindTooltip(titulo, { direction: "top" });
      punto.addTo(capaContenedores);
      marcadores[c.id_contenedor] = punto;
      return;
    }
    const marcador = L.marker(posicion(c), {
      icon: L.divIcon({
        className: "marca-contenedor estado-" + (COLORES[c.estado] ? c.estado : "retirado"),
        html: SIMBOLOS[c.estado] || SIMBOLOS.retirado,
        iconSize: [26, 26],
        iconAnchor: [13, 13],
        popupAnchor: [0, -13],
      }),
      title: titulo,
      alt: titulo,
    }).bindPopup(popupContenedor(c)).bindTooltip(c.codigo, { direction: "top", offset: [0, -12] });
    marcador.addTo(capaContenedores);
    marcadores[c.id_contenedor] = marcador;
  });
}

function dibujarIncidencias(lista) {
  capaIncidencias.clearLayers();
  const porContenedor = {};
  incidenciasAbiertas.forEach(function (i) {
    (porContenedor[i.id_contenedor] = porContenedor[i.id_contenedor] || []).push(i);
  });

  lista.filter(tieneUbicacion).forEach(function (c) {
    const abiertas = porContenedor[c.id_contenedor];
    if (!abiertas) {
      return;
    }
    const titulo = abiertas.length === 1
      ? abiertas[0].numero + ": " + textoTipoProblema(abiertas[0].tipo_problema) + " en " + c.codigo
      : abiertas.length + " incidencias abiertas en " + c.codigo;
    L.marker(posicion(c), {
      icon: L.divIcon({ className: "marca-incidencia", html: "!", iconSize: [20, 20], iconAnchor: [-2, 22] }),
      title: titulo,
      alt: titulo,
    }).bindPopup('<div class="popup-mapa"><strong>' + escaparHtml(c.codigo) + "</strong>" + abiertas.map(function (i) {
      return '<a href="incidencia.html?id=' + $entero(i.id_incidencia) + '">' + escaparHtml(i.numero) + "</a> " +
             escaparHtml(textoTipoProblema(i.tipo_problema)) + " " + marcaDeEstado("estado", i.estado);
    }).join("") + "</div>").addTo(capaIncidencias);
  });
}

function dibujarCentros() {
  capaCentros.clearLayers();
  centros.filter(tieneUbicacion).forEach(function (centro) {
    L.marker(posicion(centro), {
      icon: L.divIcon({ className: "marca-centro", iconSize: [18, 18] }),
      title: "Centro de acopio: " + centro.nombre,
      alt: "Centro de acopio: " + centro.nombre,
    }).bindPopup('<div class="popup-mapa"><strong>' + escaparHtml(centro.nombre) + "</strong>" +
      "<span>" + escaparHtml(centro.direccion || "") + "</span>" +
      "<span>Centro de acopio · " + escaparHtml(centro.estado) + "</span></div>").addTo(capaCentros);
  });
}

function dibujarTabla(lista) {
  const conUbicacion = lista.filter(tieneUbicacion).length;
  const sinUbicacion = lista.length - conUbicacion;
  document.getElementById("contador-resultados").innerHTML = lista.length === 0 ? "" :
    "En el mapa: <strong>" + conUbicacion + "</strong> de " + lista.length + " contenedores" +
    (sinUbicacion > 0 ? " (" + sinUbicacion + " sin ubicación cargada)." : ".");

  if (lista.length === 0) {
    cuerpoTabla.innerHTML = filaVacia(COLUMNAS, "Ningún contenedor coincide con los filtros", "Probá con otro estado, tipo o zona.");
    return;
  }

  cuerpoTabla.innerHTML = lista.map(function (c) {
    const ver = tieneUbicacion(c)
      ? '<button type="button" class="boton boton-secundario boton-chico js-ver" data-id="' + $entero(c.id_contenedor) + '">Ver en el mapa</button>'
      : '<span class="texto-tenue">Sin ubicación</span>';
    return '<tr role="row">' +
      celda("Código", c.codigo) +
      celda("Dirección", c.direccion) +
      celda("Zona", c.zona) +
      celda("Tipo", c.tipo_residuo) +
      celdaMarca("Estado", "contenedor", c.estado) +
      '<td role="cell" data-etiqueta="Acciones"><div class="acciones-fila">' + ver + enlaceDe(c) + "</div></td>" +
      "</tr>";
  }).join("");
}

function encuadrar(lista) {
  const puntos = lista.filter(tieneUbicacion).map(posicion);
  if (puntos.length === 1) {
    mapa.setView(puntos[0], 15);
  } else if (puntos.length > 1) {
    mapa.fitBounds(puntos, { padding: [30, 30], maxZoom: 15 });
  }
}

function redibujar(conEncuadre) {
  const lista = visibles();
  dibujarContenedores(lista);
  if (PUEDE.incidencias) {
    dibujarIncidencias(lista);
  }
  dibujarTabla(lista);
  if (conEncuadre) {
    encuadrar(lista);
  }
}

function llenarSelector(nombre, opciones) {
  formFiltros.elements[nombre].insertAdjacentHTML("beforeend", opciones.map(function (o) {
    return '<option value="' + escaparHtml(o.valor) + '">' + escaparHtml(o.texto) + "</option>";
  }).join(""));
}

// La Cuadrilla ve en el mapa SOLO sus rutas (las de sus recolecciones planificadas o en curso): el servidor no le da otras.
let misRutasCargadas = null;
async function dibujarMisRutas() {
  const activa = formFiltros.elements.rutas && formFiltros.elements.rutas.checked;
  const ayuda = document.getElementById("ayuda-rutas");
  const lista = document.getElementById("lista-rutas-mapa");
  capaRutas.clearLayers();
  lista.innerHTML = "";
  lista.classList.toggle("oculto", !activa);
  document.getElementById("atribucion-rutas").classList.toggle("oculto", !activa);
  if (!activa) {
    ayuda.classList.add("oculto");
    mapa.removeLayer(capaRutas);
    return;
  }
  try {
    if (!misRutasCargadas) {
      misRutasCargadas = await apiGet(API.recoleccion + "/mis-rutas");
    }
  } catch (error) {
    ayuda.textContent = "No se pudieron cargar tus rutas. " + mensajeDeFallo(error);
    ayuda.classList.remove("oculto");
    return;
  }
  const filas = (misRutasCargadas.datos || []).filter(function (r) { return r.recorrido; });
  if (misRutasCargadas.sin_cuadrilla) {
    ayuda.textContent = "Tu usuario todavía no está en una cuadrilla: pedile al administrador que te asigne a una.";
  } else if (filas.length === 0) {
    ayuda.textContent = "Todavía no tenés rutas asignadas.";
  } else {
    ayuda.textContent = "";
  }
  ayuda.classList.toggle("oculto", !ayuda.textContent);
  const limites = [];
  filas.forEach(function (f, i) {
    const rec = f.recorrido;
    const color = PALETA_RUTAS[i % PALETA_RUTAS.length];
    let linea = null;
    if (rec.trazado && rec.trazado.vigente && rec.trazado.geojson) {
      linea = L.geoJSON(rec.trazado.geojson, { style: { color: color, weight: 4, opacity: 0.85 }, pane: "rutas" });
    } else if (rec.paradas.length > 1) {
      linea = L.polyline(rec.paradas.map(function (p) { return [p.latitud, p.longitud]; }), { color: color, weight: 3, opacity: 0.75, dashArray: "6 8", pane: "rutas" });
    }
    if (linea) {
      linea.bindTooltip(rec.nombre, { sticky: true }).bindPopup('<div class="popup-mapa"><strong>' + escaparHtml(rec.nombre) + "</strong><span>" + escaparHtml(f.numero + " · " + textoEstado(f.estado)) +
        '</span><span class="popup-accion"><a href="recoleccion.html?id=' + $entero(f.id_recoleccion) + '">Abrir la recolección</a></span></div>').addTo(capaRutas);
      limites.push(linea.getBounds());
    }
    lista.insertAdjacentHTML("beforeend", '<li><span class="leyenda-linea" style="background:' + color + '" aria-hidden="true"></span><a href="recoleccion.html?id=' + $entero(f.id_recoleccion) + '">' +
      escaparHtml(rec.nombre) + "</a> (" + rec.visitadas + " de " + rec.paradas.length + " contenedores vaciados · " + escaparHtml(textoEstado(f.estado)) + ")</li>");
  });
  capaRutas.addTo(mapa);
  if (limites.length) {
    mapa.fitBounds(limites.reduce(function (acum, b) { return acum.extend(b); }, L.latLngBounds(limites[0])), { padding: [30, 30], maxZoom: 15 });
  }
}

// Las rutas de UNA zona (todas juntas son demasiado para un mapa): cada una con su color, su nombre y, en la lista de abajo, un enlace para armarla.
async function dibujarRutas() {
  if (PUEDE.misRutas) {
    return dibujarMisRutas();
  }
  const activa = PUEDE.rutas && formFiltros.elements.rutas && formFiltros.elements.rutas.checked;
  const ayuda = document.getElementById("ayuda-rutas");
  const lista = document.getElementById("lista-rutas-mapa");
  capaRutas.clearLayers();
  lista.innerHTML = "";
  lista.classList.toggle("oculto", !activa);
  document.getElementById("atribucion-rutas").classList.toggle("oculto", !activa);
  if (!activa) {
    ayuda.classList.add("oculto");
    mapa.removeLayer(capaRutas);
    return;
  }
  const zona = formFiltros.elements.zona.value;
  if (zona === "") {
    ayuda.textContent = "Elegí una zona (municipio) en el filtro para ver sus rutas.";
    ayuda.classList.remove("oculto");
    return;
  }
  ayuda.textContent = "Cargando las rutas…";
  ayuda.classList.remove("oculto");
  try {
    if (!rutasPorZona[zona]) {
      rutasPorZona[zona] = (await apiGet(API.recoleccion + "/rutas/mapa?id_zona=" + encodeURIComponent(zona))).datos || [];
    }
  } catch (error) {
    ayuda.textContent = "No se pudieron cargar las rutas. " + mensajeDeFallo(error);
    return;
  }
  if (formFiltros.elements.zona.value !== zona || !formFiltros.elements.rutas.checked) {
    return;  // cambió el filtro mientras se cargaba
  }
  const rutas = rutasPorZona[zona];
  const limites = [];
  rutas.forEach(function (r, i) {
    const color = PALETA_RUTAS[i % PALETA_RUTAS.length];
    const km = r.distancia_m ? " · " + (r.distancia_m / 1000).toLocaleString("es-UY", { maximumFractionDigits: 1 }) + " km" : "";
    let linea = null;
    if (r.geojson) {
      linea = L.geoJSON(r.geojson, { style: { color: color, weight: 4, opacity: 0.85 }, pane: "rutas" });
    } else if (r.paradas && r.paradas.length > 1) {
      linea = L.polyline(r.paradas, { color: color, weight: 3, opacity: 0.75, dashArray: "6 8", pane: "rutas" });
    }
    if (linea) {
      linea.bindTooltip(r.nombre + km, { sticky: true }).bindPopup('<div class="popup-mapa"><strong>' + escaparHtml(r.nombre) + "</strong><span>" +
        escaparHtml(r.dia_semana + " " + r.hora_inicio + " · " + r.cantidad + " contenedores" + km) + '</span><span class="popup-accion"><a href="ruta-armar.html?id=' + $entero(r.id_ruta) + '">Armar esta ruta</a></span></div>').addTo(capaRutas);
      limites.push(linea.getBounds());
    }
    lista.insertAdjacentHTML("beforeend", '<li><span class="leyenda-linea" style="background:' + color + '" aria-hidden="true"></span><a href="ruta-armar.html?id=' + $entero(r.id_ruta) + '">' +
      escaparHtml(r.nombre) + "</a> (" + r.cantidad + " contenedores" + km + (r.geojson ? "" : ", sin trazar: línea recta") + ")</li>");
  });
  capaRutas.addTo(mapa);
  ayuda.classList.toggle("oculto", rutas.length > 0);
  ayuda.textContent = rutas.length ? "" : "Esa zona todavía no tiene rutas activas.";
  if (limites.length) {
    mapa.fitBounds(limites.reduce(function (acum, b) { return acum.extend(b); }, L.latLngBounds(limites[0])), { padding: [30, 30], maxZoom: 15 });
  }
}

function alternarCapa(capa, activa) {
  if (activa) {
    capa.addTo(mapa);
  } else {
    mapa.removeLayer(capa);
  }
}

async function cargar() {
  cuerpoTabla.innerHTML = filaEsqueleto(COLUMNAS, 4);

  const pedidos = [
    apiGet(API.gestion + "/contenedores"),
    apiGet(API.gestion + "/zonas"),
    apiGet(API.gestion + "/tipos-residuo"),
    PUEDE.centros ? apiGet(API.gestion + "/centros") : Promise.resolve({ datos: [] }),
    PUEDE.incidencias ? apiGet(API.gestion + "/incidencias") : Promise.resolve({ datos: [] }),
  ];
  const [respContenedores, respZonas, respTipos, respCentros, respIncidencias] = await Promise.allSettled(pedidos);

  if (respZonas.status === "fulfilled") {
    llenarSelector("zona", (respZonas.value.datos || []).map(function (z) { return { valor: z.id_zona, texto: z.nombre }; }));
  }
  if (respTipos.status === "fulfilled") {
    llenarSelector("tipo", (respTipos.value.datos || []).map(function (t) { return { valor: t.nombre, texto: t.nombre }; }));
  }

  if (respContenedores.status !== "fulfilled") {
    cuerpoTabla.innerHTML = filaError(COLUMNAS, mensajeDeFallo(respContenedores.reason));
    return;
  }
  contenedores = respContenedores.value.datos || [];

  if (PUEDE.centros) {
    document.getElementById("capa-centros").classList.remove("oculto");
    document.getElementById("leyenda-centros").classList.remove("oculto");
    if (respCentros.status === "fulfilled") {
      centros = respCentros.value.datos || [];
      dibujarCentros();
      capaCentros.addTo(mapa);
    } else {
      avisoPaginaError("No se pudieron cargar los centros de acopio. " + mensajeDeFallo(respCentros.reason));
    }
  }
  if (PUEDE.rutas) {
    document.getElementById("capa-rutas").classList.remove("oculto");
  }
  if (PUEDE.misRutas) {
    const etiqueta = document.getElementById("capa-rutas");
    etiqueta.classList.remove("oculto");
    etiqueta.lastChild.textContent = " Mis rutas";
    formFiltros.elements.rutas.checked = true;
  }
  if (PUEDE.incidencias) {
    document.getElementById("capa-incidencias").classList.remove("oculto");
    document.getElementById("leyenda-incidencias").classList.remove("oculto");
    if (respIncidencias.status === "fulfilled") {
      incidenciasAbiertas = (respIncidencias.value.datos || []).filter(function (i) {
        return i.estado === "abierta" || i.estado === "en_curso";
      });
      capaIncidencias.addTo(mapa);
    } else {
      avisoPaginaError("No se pudieron cargar las incidencias. " + mensajeDeFallo(respIncidencias.reason));
    }
  }

  redibujar(true);
  enfocarContenedorPedido();
  if (PUEDE.misRutas) {
    dibujarMisRutas();
  }
}

// «Ver en el mapa» desde la ficha o el reclamo: ?contenedor=CODIGO centra el mapa en ese contenedor y abre su ficha.
// Es lo que reemplaza a una calle: los datos abiertos de la Intendencia traen el circuito y las coordenadas, no la direccion (H-05).
function enfocarContenedorPedido() {
  const codigo = new URLSearchParams(window.location.search).get("contenedor");
  if (!codigo || !/^[A-Za-z0-9-]{1,20}$/.test(codigo)) {
    return;
  }
  const c = contenedores.find(function (x) { return x.codigo.toUpperCase() === codigo.toUpperCase(); });
  const marcador = c && marcadores[c.id_contenedor];
  if (!marcador) {
    avisoPaginaError("No encontramos " + codigo + " en el mapa: puede que esté retirado, no tenga ubicación o no sea de tus rutas.");
    return;
  }
  mapa.setView(marcador.getLatLng(), 17);
  marcador.openPopup();
}

formFiltros.addEventListener("change", function (evento) {
  if (evento.target.name === "centros") {
    alternarCapa(capaCentros, evento.target.checked);
  } else if (evento.target.name === "incidencias") {
    alternarCapa(capaIncidencias, evento.target.checked);
  } else if (evento.target.name === "rutas") {
    redibujar(false);
    dibujarRutas();
  } else {
    redibujar(true);
    if (evento.target.name === "zona") {
      dibujarRutas();
    }
  }
});

formFiltros.addEventListener("submit", function (evento) {
  evento.preventDefault();
});

cuerpoTabla.addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargar();
    return;
  }
  const boton = evento.target.closest(".js-ver");
  const marcador = boton && marcadores[boton.dataset.id];
  if (marcador) {
    document.getElementById("mapa").scrollIntoView({ block: "center" });
    mapa.setView(marcador.getLatLng(), 16);
    marcador.openPopup();
  }
});

if (PUEDE.informar) {
  document.getElementById("bajada-mapa").textContent =
    "Tocá un contenedor para ver su estado e informar un problema ahí.";
}

cargar();
