Sesion.exigir();

const CENTRO_MONTEVIDEO = [-34.885, -56.165];
const CLAVE_PRECARGA = "sigeru.rutaSugerida";
const form = document.getElementById("form-ruta-sugerida");
const panelResultado = document.getElementById("panel-resultado");
const cuerpoRuta = document.getElementById("cuerpo-ruta");
const btnCrear = document.getElementById("btn-crear");
const avisoSinRed = document.getElementById("aviso-sin-red");

let mapa = null;
let capaRuta = null;
let propuesta = null;

function opcionDe(valor, texto) {
  return '<option value="' + escaparHtml(valor) + '">' + escaparHtml(texto) + "</option>";
}

async function cargarListas() {
  const [zonas, centros, camiones] = await Promise.allSettled([
    apiGet(API.gestion + "/zonas"),
    apiGet(API.gestion + "/centros"),
    apiGet(API.recoleccion + "/camiones"),
  ]);
  if (zonas.status === "fulfilled") {
    form.id_zona.innerHTML = opcionDe("", "Todas las zonas") + (zonas.value.datos || []).map(function (z) { return opcionDe(z.id_zona, z.nombre); }).join("");
  }
  if (centros.status === "fulfilled") {
    form.id_centro.innerHTML = opcionDe("", "Sin definir") + (centros.value.datos || [])
      .filter(function (c) { return c.estado === "activo" && c.latitud !== null && c.longitud !== null; })
      .map(function (c) { return opcionDe(c.id_centro, c.nombre); }).join("");
  }
  if (camiones.status === "fulfilled") {
    form.id_camion.innerHTML = opcionDe("", "Sin elegir") + (camiones.value.datos || [])
      .filter(function (c) { return c.estado === "operativo" || c.estado === "en_ruta"; })
      .map(function (c) { return opcionDe(c.id_camion, c.matricula + (c.marca ? " · " + c.marca : "")); }).join("");
  }
  if ([zonas, centros, camiones].some(function (r) { return r.status !== "fulfilled"; })) {
    avisoPaginaError("No se pudieron cargar todas las listas (zonas, centros o camiones). Podés sugerir la ruta igual.");
  }
}

function iniciarMapa() {
  if (mapa) {
    return;
  }
  mapa = L.map("mapa-ruta", { scrollWheelZoom: false }).setView(CENTRO_MONTEVIDEO, 12);
  capaRuta = L.layerGroup().addTo(mapa);
  const fondo = L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    referrerPolicy: "origin",
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  });
  const sinRed = function (valor) {
    avisoSinRed.classList.toggle("oculto", !valor);
    document.getElementById("mapa-ruta").classList.toggle("sin-fondo", valor);
  };
  fondo.on("tileerror", function () { sinRed(true); });
  fondo.on("tileload", function () { sinRed(false); });
  fondo.addTo(mapa);
  if (navigator.onLine === false) {
    sinRed(true);
  }
}

function horasYMinutos(min) {
  if (min < 60) {
    return min + " min";
  }
  const h = Math.floor(min / 60);
  const m = min % 60;
  return h + " h" + (m ? " " + m + " min" : "");
}

function cifra(titulo, valor) {
  return "<div><dt>" + escaparHtml(titulo) + "</dt><dd>" + escaparHtml(valor) + "</dd></div>";
}

function dibujarMapa(d) {
  iniciarMapa();
  capaRuta.clearLayers();
  const puntos = d.paradas.map(function (p) { return [p.latitud, p.longitud]; });
  const linea = puntos.slice();
  if (d.destino) {
    linea.push([d.destino.latitud, d.destino.longitud]);
  }
  if (linea.length > 1) {
    L.polyline(linea, { color: "#15654a", weight: 4, opacity: 0.85 }).addTo(capaRuta);
  }
  d.paradas.forEach(function (p) {
    const titulo = p.orden + ". " + p.codigo + ": " + p.se_llena;
    L.marker([p.latitud, p.longitud], {
      icon: L.divIcon({ className: "marca-orden" + (p.estado === "lleno" ? " lleno" : ""), html: String(p.orden), iconSize: [28, 28], iconAnchor: [14, 14], popupAnchor: [0, -14] }),
      title: titulo,
      alt: titulo,
    }).bindPopup('<div class="popup-mapa"><strong>' + escaparHtml(p.codigo) + "</strong><span>" + escaparHtml(p.direccion) + "</span><span>" +
      escaparHtml(p.se_llena) + "</span></div>").addTo(capaRuta);
  });
  if (d.destino) {
    L.marker([d.destino.latitud, d.destino.longitud], {
      icon: L.divIcon({ className: "marca-centro", iconSize: [18, 18] }),
      title: "Centro de destino: " + d.destino.nombre,
      alt: "Centro de destino: " + d.destino.nombre,
    }).bindPopup('<div class="popup-mapa"><strong>' + escaparHtml(d.destino.nombre) + "</strong><span>" + escaparHtml(d.destino.direccion || "") +
      "</span><span>Centro de destino</span></div>").addTo(capaRuta);
    puntos.push([d.destino.latitud, d.destino.longitud]);
  }
  setTimeout(function () {
    mapa.invalidateSize();
    mapa.fitBounds(L.latLngBounds(puntos), { padding: [30, 30], maxZoom: 16 });
  }, 50);
}

function dibujarResultado(d) {
  propuesta = d;
  panelResultado.classList.remove("oculto");
  document.getElementById("cifras-ruta").innerHTML = d.cantidad === 0 ? "" :
    cifra("Paradas", String(d.cantidad)) +
    cifra("Distancia estimada", d.distancia_km.toLocaleString("es-UY", { minimumFractionDigits: 1, maximumFractionDigits: 2 }) + " km") +
    cifra("Tiempo estimado", horasYMinutos(d.tiempo_min)) +
    cifra("Horizonte", d.horizonte_horas + " h") +
    (d.destino ? cifra("Termina en", d.destino.nombre) : "");
  document.getElementById("supuestos-ruta").textContent = d.supuestos +
    (d.descartados_por_tope > 0 ? " Quedaron " + d.descartados_por_tope + " contenedores afuera por el tope: son los menos urgentes." : "");

  if (d.cantidad === 0) {
    cuerpoRuta.innerHTML = filaVacia(6, "Ningún contenedor necesita recolección en ese horizonte",
      "Probá con 48 horas, otra zona o mirá la columna «Se llena en» de Contenedores (hace falta historial de vaciados).");
    btnCrear.classList.add("oculto");
    document.getElementById("mapa-ruta").classList.add("oculto");
    avisoSinRed.classList.add("oculto");
    return;
  }

  document.getElementById("mapa-ruta").classList.remove("oculto");
  btnCrear.classList.remove("oculto");
  cuerpoRuta.innerHTML = d.paradas.map(function (p) {
    return '<tr role="row">' +
      celda("N.º", p.orden) +
      celdaEnlace("Contenedor", p.codigo, "contenedor.html?id=" + $entero(p.id_contenedor)) +
      celda("Dirección", p.direccion) +
      celda("Zona", p.zona) +
      celda("Se llena", p.se_llena) +
      celda("Desde la anterior", p.orden === 1 ? "Punto de partida" : p.km_desde_anterior.toLocaleString("es-UY") + " km") +
      "</tr>";
  }).join("") + (d.destino ? '<tr role="row">' + celda("N.º", "Final") + celda("Contenedor", "Centro de acopio") + celda("Dirección", d.destino.nombre) +
    celda("Zona", "-") + celda("Se llena", "-") + celda("Desde la anterior", d.km_al_destino.toLocaleString("es-UY") + " km") + "</tr>" : "");
  dibujarMapa(d);
}

form.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  avisoPagina("");
  const boton = document.getElementById("btn-sugerir");
  boton.disabled = true;
  boton.textContent = "Calculando...";
  const consulta = new URLSearchParams({ horizonte: form.horizonte.value });
  if (form.id_zona.value) { consulta.set("id_zona", form.id_zona.value); }
  if (form.id_centro.value) { consulta.set("id_centro", form.id_centro.value); }
  try {
    const respuesta = await apiGet(API.gestion + "/contenedores/ruta-sugerida?" + consulta.toString());
    dibujarResultado(respuesta.datos);
  } catch (error) {
    avisoPaginaError("No se pudo calcular la ruta. " + mensajeDeFallo(error));
  } finally {
    boton.disabled = false;
    boton.textContent = "Sugerir ruta";
  }
});

// No crea nada: guarda la propuesta en la pestana y abre Recolecciones con el alta precargada para que el administrador la revise y confirme.
btnCrear.addEventListener("click", function () {
  if (!propuesta || propuesta.cantidad === 0) {
    return;
  }
  const precarga = {
    id_zona: propuesta.id_zona,
    id_camion: form.id_camion.value || null,
    id_centro: propuesta.destino ? propuesta.destino.id_centro : null,
    codigos: propuesta.paradas.map(function (p) { return p.codigo; }),
    km: propuesta.distancia_km,
    horizonte: propuesta.horizonte_horas,
    guardada_en: Date.now(),
  };
  try {
    sessionStorage.setItem(CLAVE_PRECARGA, JSON.stringify(precarga));
  } catch (e) {
    avisoPaginaError("El navegador no permite guardar la propuesta para precargar el alta. Planificá la recolección a mano.");
    return;
  }
  window.location.href = "recolecciones.html?desde=ruta-sugerida";
});

cargarListas();
