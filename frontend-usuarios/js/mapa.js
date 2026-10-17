Sesion.exigir();

const CENTRO_MONTEVIDEO = [-34.885, -56.165];
const COLORES = { disponible: "#15654a", lleno: "#b3261e", mantenimiento: "#8a5000", retirado: "#64756c" };
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
};

let contenedores = [];
let centros = [];
let incidenciasAbiertas = [];
const marcadores = {};

const mapa = L.map("mapa", { scrollWheelZoom: false }).setView(CENTRO_MONTEVIDEO, 12);
const capaContenedores = L.layerGroup().addTo(mapa);
const capaCentros = L.layerGroup();
const capaIncidencias = L.layerGroup();

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

function dibujarContenedores(lista) {
  capaContenedores.clearLayers();
  Object.keys(marcadores).forEach(function (id) { delete marcadores[id]; });

  lista.filter(tieneUbicacion).forEach(function (c) {
    const marcador = L.circleMarker(posicion(c), {
      radius: 9,
      color: "#ffffff",
      weight: 2,
      fillColor: COLORES[c.estado] || COLORES.retirado,
      fillOpacity: 0.95,
    }).bindPopup(popupContenedor(c)).bindTooltip(c.codigo);
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
      icon: L.divIcon({ className: "marca-incidencia", html: "!", iconSize: [20, 20], iconAnchor: [-4, 26] }),
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
}

formFiltros.addEventListener("change", function (evento) {
  if (evento.target.name === "centros") {
    alternarCapa(capaCentros, evento.target.checked);
  } else if (evento.target.name === "incidencias") {
    alternarCapa(capaIncidencias, evento.target.checked);
  } else {
    redibujar(true);
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
