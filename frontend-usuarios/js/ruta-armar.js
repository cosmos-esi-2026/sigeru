Sesion.exigir();

// «Armar ruta» (plan de rutas para la defensa, bloque 2): el administrador elige los contenedores de una ruta, los ordena y guarda; el servidor traza
// el recorrido por las calles. Todo lo que se hace en el mapa se puede hacer tambien desde las listas (teclado y lectores de pantalla).

const CENTRO_MONTEVIDEO = [-34.885, -56.165];
const idRuta = parseInt(new URLSearchParams(window.location.search).get("id"), 10);

const panel = document.getElementById("panel-armar");
const listaParadas = document.getElementById("lista-paradas");
const listaCandidatos = document.getElementById("lista-candidatos");
const buscador = document.getElementById("buscar-candidato");
const btnGuardar = document.getElementById("btn-guardar");
const btnDescartar = document.getElementById("btn-descartar");
const btnReintentar = document.getElementById("btn-reintentar");
const avisoSinRed = document.getElementById("aviso-sin-red");
const estadoTrazado = document.getElementById("estado-trazado");

const NOMBRE_SERVICIO = { "valhalla-truck": "Valhalla (perfil de camión)", "osrm-fossgis-auto": "OSRM (FOSSGIS)", "osrm-demo-auto": "OSRM (servidor de demostración)" };
const MAXIMO_CANDIDATOS_VISIBLES = 60;

let ruta = null;           // el detalle que devuelve el servidor (paradas guardadas y trazado)
let candidatos = [];       // los contenedores de la zona de la ruta
let lista = [];            // ids de los contenedores en el orden de trabajo (puede tener cambios sin guardar)
let mover = new Set();     // ids que estan en otra ruta activa y se mueven a esta al guardar
let sucio = false;
let ocupado = false;
let avisoDeMovidos = "";   // texto sobre las rutas que quedaron sin trazar por haberles sacado un contenedor
let mapa = null;
let capaPuntos = null;
let capaLinea = null;
const porId = {};

function recolectable(c) {
  return c.estado !== "mantenimiento" && c.estado !== "retirado";
}

function distanciaKm(a, b) {
  const rad = Math.PI / 180;
  const dLat = (b.latitud - a.latitud) * rad;
  const dLon = (b.longitud - a.longitud) * rad;
  const h = Math.pow(Math.sin(dLat / 2), 2) + Math.cos(a.latitud * rad) * Math.cos(b.latitud * rad) * Math.pow(Math.sin(dLon / 2), 2);
  return 2 * 6371.0088 * Math.asin(Math.sqrt(h));
}

function largoRectaKm() {
  let suma = 0;
  for (let i = 1; i < lista.length; i++) {
    suma += distanciaKm(porId[lista[i - 1]], porId[lista[i]]);
  }
  return suma;
}

function kilometros(km) {
  return km.toLocaleString("es-UY", { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + " km";
}

function duracion(segundos) {
  const min = Math.round(segundos / 60);
  return min < 60 ? min + " min" : Math.floor(min / 60) + " h " + (min % 60) + " min";
}

function mostrarEstado(texto, tipo) {
  estadoTrazado.textContent = texto || "";
  estadoTrazado.className = "mensaje " + (tipo || "aviso") + (texto ? "" : " oculto");
}

// ---------------------------------------------------------------------------------------------- mapa

function iniciarMapa() {
  if (mapa) {
    return;
  }
  mapa = L.map("mapa-armar", { scrollWheelZoom: false }).setView(CENTRO_MONTEVIDEO, 12);
  capaLinea = L.layerGroup().addTo(mapa);
  capaPuntos = L.layerGroup().addTo(mapa);
  const fondo = L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    referrerPolicy: "origin",
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> (ODbL) · <a href="https://www.openstreetmap.org/fixthemap">Reportar un error del mapa</a>',
  });
  const sinRed = function (valor) {
    avisoSinRed.classList.toggle("oculto", !valor);
    document.getElementById("mapa-armar").classList.toggle("sin-fondo", valor);
  };
  fondo.on("tileerror", function () { sinRed(true); });
  fondo.on("tileload", function () { sinRed(false); });
  fondo.addTo(mapa);
  if (navigator.onLine === false) {
    sinRed(true);
  }
}

function popupDe(c, enLista) {
  const caja = document.createElement("div");
  caja.className = "popup-mapa";
  caja.innerHTML = "<strong>" + escaparHtml(c.codigo) + "</strong><span>" + escaparHtml(c.direccion) + "</span><span>" + escaparHtml(textoEstado(c.estado)) + "</span>" +
    (c.id_otra_ruta && !enLista ? "<span>Está en «" + escaparHtml(c.otra_ruta) + "»</span>" : "");
  const boton = document.createElement("button");
  boton.type = "button";
  boton.className = "boton boton-secundario";
  boton.textContent = enLista ? "Quitar de la ruta" : (recolectable(c) ? (c.id_otra_ruta ? "Agregar y mover" : "Agregar a la ruta") : "En mantenimiento: no se recolecta");
  boton.disabled = !enLista && !recolectable(c);
  boton.addEventListener("click", function () {
    mapa.closePopup();
    if (enLista) {
      quitar(c.id_contenedor);
    } else {
      agregar(c.id_contenedor);
    }
  });
  caja.appendChild(boton);
  return caja;
}

function dibujarMapa(reencuadrar) {
  iniciarMapa();
  capaPuntos.clearLayers();
  capaLinea.clearLayers();

  candidatos.forEach(function (c) {
    if (lista.indexOf(c.id_contenedor) !== -1) {
      return;
    }
    let marca;
    if (c.estado === "mantenimiento") {
      marca = L.marker([c.latitud, c.longitud], { icon: L.divIcon({ className: "marca-pequena marca-mant", html: "M", iconSize: [20, 20], iconAnchor: [10, 10], popupAnchor: [0, -10] }) });
    } else {
      const color = c.estado === "lleno" ? "#b3261e" : (c.id_otra_ruta ? "#7a8a82" : "#1b6fa8");
      marca = L.circleMarker([c.latitud, c.longitud], { radius: c.estado === "lleno" ? 8 : 6, color: "#ffffff", weight: 1.5, fillColor: color, fillOpacity: 0.95 });
    }
    marca.bindTooltip(c.codigo + (c.id_otra_ruta ? " · en " + c.otra_ruta : ""), { direction: "top" }).bindPopup(popupDe(c, false)).addTo(capaPuntos);
  });

  const puntos = [];
  lista.forEach(function (id, i) {
    const c = porId[id];
    puntos.push([c.latitud, c.longitud]);
    const titulo = (i + 1) + ". " + c.codigo + (recolectable(c) ? "" : " (no se recolecta)");
    L.marker([c.latitud, c.longitud], {
      icon: L.divIcon({
        className: "marca-orden" + (c.estado === "lleno" ? " lleno" : "") + (recolectable(c) ? "" : " mant"),
        html: String(i + 1), iconSize: [28, 28], iconAnchor: [14, 14], popupAnchor: [0, -14],
      }),
      title: titulo,
      alt: titulo,
      zIndexOffset: 500,
    }).bindPopup(popupDe(c, true)).addTo(capaPuntos);
  });

  const traza = ruta && ruta.trazado && ruta.trazado.vigente && ruta.trazado.geojson && !sucio;
  if (traza) {
    L.geoJSON(ruta.trazado.geojson, { style: { color: "#15654a", weight: 4, opacity: 0.85 } }).addTo(capaLinea);
  } else if (puntos.length > 1) {
    L.polyline(puntos, { color: "#15654a", weight: 3, opacity: 0.7, dashArray: "6 8" }).bindTooltip("Línea recta entre paradas: se traza por las calles al guardar").addTo(capaLinea);
  }

  if (reencuadrar) {
    const base = puntos.length ? puntos : candidatos.map(function (c) { return [c.latitud, c.longitud]; });
    setTimeout(function () {
      mapa.invalidateSize();
      if (base.length) {
        mapa.fitBounds(L.latLngBounds(base), { padding: [30, 30], maxZoom: 16 });
      }
    }, 50);
  }
}

// ---------------------------------------------------------------------------------------------- listas y cifras

function dibujarParadas() {
  if (lista.length === 0) {
    listaParadas.innerHTML = '<li class="vacio-lista">Todavía no hay contenedores en la ruta. Agregalos desde el mapa o desde la lista de abajo.</li>';
  } else {
    listaParadas.innerHTML = lista.map(function (id, i) {
      const c = porId[id];
      const marcas = (c.estado === "lleno" ? '<span class="etiqueta etiqueta-lleno">▲ Lleno</span>' : "") +
        (recolectable(c) ? "" : '<span class="etiqueta etiqueta-mant">M No se recolecta (en mantenimiento)</span>') +
        (mover.has(id) ? '<span class="etiqueta etiqueta-mueve">Se mueve desde «' + escaparHtml(c.otra_ruta) + "»</span>" : "");
      return '<li class="parada" data-id="' + $entero(id) + '">' +
        '<span class="parada-orden" aria-hidden="true">' + (i + 1) + "</span>" +
        '<div class="parada-texto"><strong>' + escaparHtml(c.codigo) + "</strong><span>" + escaparHtml(c.direccion) + "</span>" + marcas + "</div>" +
        '<div class="parada-acciones">' +
        '<button type="button" class="boton boton-icono js-subir" data-id="' + $entero(id) + '" aria-label="Subir ' + escaparHtml(c.codigo) + ' (ahora es la parada ' + (i + 1) + ')"' + (i === 0 ? " disabled" : "") + ">↑</button>" +
        '<button type="button" class="boton boton-icono js-bajar" data-id="' + $entero(id) + '" aria-label="Bajar ' + escaparHtml(c.codigo) + ' (ahora es la parada ' + (i + 1) + ')"' + (i === lista.length - 1 ? " disabled" : "") + ">↓</button>" +
        '<button type="button" class="boton boton-secundario js-quitar" data-id="' + $entero(id) + '" aria-label="Quitar ' + escaparHtml(c.codigo) + ' de la ruta">Quitar</button>' +
        "</div></li>";
    }).join("");
  }
  document.getElementById("contador-paradas").textContent = lista.length + " de " + ruta.tope + " contenedores" + (lista.length >= ruta.tope ? " (el máximo de una ruta)" : "") + ".";
}

function dibujarCandidatos() {
  const texto = buscador.value.trim().toLowerCase();
  const libres = candidatos.filter(function (c) {
    return lista.indexOf(c.id_contenedor) === -1 && (texto === "" || c.codigo.toLowerCase().indexOf(texto) !== -1 || c.direccion.toLowerCase().indexOf(texto) !== -1);
  });
  if (libres.length === 0) {
    listaCandidatos.innerHTML = '<li class="vacio-lista">' + (texto ? "Ningún contenedor de la zona coincide con la búsqueda." : "Ya están todos los contenedores de la zona en esta ruta.") + "</li>";
    return;
  }
  listaCandidatos.innerHTML = libres.slice(0, MAXIMO_CANDIDATOS_VISIBLES).map(function (c) {
    const nota = c.estado === "mantenimiento" ? "En mantenimiento: no se recolecta, no se puede agregar" :
      (c.id_otra_ruta ? "Está en «" + c.otra_ruta + "»: se mueve a esta ruta al guardar" : (c.estado === "lleno" ? "Lleno" : ""));
    return '<li class="candidato"><div class="parada-texto"><strong>' + escaparHtml(c.codigo) + "</strong><span>" + escaparHtml(c.direccion) + "</span>" +
      (nota ? '<span class="nota-candidato">' + escaparHtml(nota) + "</span>" : "") + "</div>" +
      '<button type="button" class="boton boton-secundario js-agregar" data-id="' + $entero(c.id_contenedor) + '" aria-label="Agregar ' + escaparHtml(c.codigo) + ' a la ruta"' +
      (recolectable(c) ? "" : " disabled") + ">Agregar</button></li>";
  }).join("") + (libres.length > MAXIMO_CANDIDATOS_VISIBLES ? '<li class="vacio-lista">Se muestran ' + MAXIMO_CANDIDATOS_VISIBLES + " de " + libres.length + ". Escribí en el buscador para acotar.</li>" : "");
}

function dibujarCifras() {
  const t = ruta.trazado;
  const vigente = t && t.vigente && !sucio;
  let km = null, tiempo = null, como = "Sin trazar";
  if (lista.length >= 2) {
    if (vigente && t.distancia_m !== null) {
      km = t.distancia_m / 1000;
      tiempo = t.duracion_s;
      como = t.tipo === "calles" ? "Por las calles · " + (NOMBRE_SERVICIO[t.servicio] || t.servicio || "") : "En línea recta (aproximado)";
    } else {
      km = largoRectaKm();
      como = sucio ? "Sin guardar: se traza al guardar" : "Sin trazar";
    }
  }
  const cifra = function (titulo, valor) { return "<div><dt>" + escaparHtml(titulo) + "</dt><dd>" + escaparHtml(valor) + "</dd></div>"; };
  document.getElementById("cifras-armar").innerHTML =
    cifra("Paradas", lista.length + " de " + ruta.tope) +
    cifra(vigente && t.tipo === "calles" ? "Distancia" : "Distancia en línea recta", km === null ? "—" : kilometros(km)) +
    (tiempo ? cifra("Tiempo de manejo", duracion(tiempo)) : "") +
    cifra("Trazado", como);
  document.getElementById("atribucion-trazado").innerHTML = vigente && t.tipo === "calles"
    ? "Recorrido calculado con " + escaparHtml(NOMBRE_SERVICIO[t.servicio] || t.servicio || "un servicio de rutas") + " (FOSSGIS) sobre datos de OpenStreetMap (ODbL), el " + escaparHtml((t.calculado_en || "").slice(0, 16).replace("T", " ")) + ". <a href=\"https://www.openstreetmap.org/fixthemap\">Reportar un error del mapa</a>."
    : "";
}

function sincronizarBotones() {
  btnGuardar.disabled = ocupado || !sucio;
  btnDescartar.disabled = ocupado || !sucio;
  const t = ruta && ruta.trazado;
  const hayQueTrazar = !sucio && lista.length >= 2 && (!t || t.tipo !== "calles" || !t.vigente);
  btnReintentar.classList.toggle("oculto", !hayQueTrazar);
  btnReintentar.disabled = ocupado;
  btnGuardar.textContent = ocupado ? "Guardando…" : "Guardar y trazar";
}

function refrescar(reencuadrar) {
  dibujarParadas();
  dibujarCandidatos();
  dibujarCifras();
  dibujarMapa(reencuadrar === true);
  sincronizarBotones();
}

// ---------------------------------------------------------------------------------------------- cambios de la lista

async function agregar(id) {
  const c = porId[id];
  if (!c || lista.indexOf(id) !== -1) {
    return;
  }
  if (!recolectable(c)) {
    avisoPaginaError("El contenedor " + c.codigo + " está en mantenimiento: no se recolecta, así que no se puede agregar a una ruta.");
    return;
  }
  if (lista.length >= ruta.tope) {
    avisoPaginaError("Una ruta admite hasta " + ruta.tope + " contenedores: armá otra ruta para el resto.");
    return;
  }
  if (c.id_otra_ruta && !mover.has(id)) {
    const si = await confirmar({
      titulo: "Mover el contenedor a esta ruta",
      mensaje: c.codigo + " ya está en «" + c.otra_ruta + "». Un contenedor va en una sola ruta: si lo agregás acá, se saca de la otra cuando guardes.",
      textoBoton: "Agregar y mover",
      peligro: false,
    });
    if (!si) {
      return;
    }
    mover.add(id);
  }
  avisoPagina("");
  lista.push(id);
  sucio = true;
  refrescar();
}

function quitar(id) {
  lista = lista.filter(function (x) { return x !== id; });
  mover.delete(id);
  sucio = true;
  refrescar();
}

function desplazar(id, delta) {
  const i = lista.indexOf(id);
  const j = i + delta;
  if (i === -1 || j < 0 || j >= lista.length) {
    return;
  }
  const otro = lista[j];
  lista[j] = id;
  lista[i] = otro;
  sucio = true;
  refrescar();
  const boton = listaParadas.querySelector('[data-id="' + $entero(id) + '"].' + (delta < 0 ? "js-subir" : "js-bajar"));
  if (boton && !boton.disabled) {
    boton.focus();
  }
}

// ---------------------------------------------------------------------------------------------- servidor

function armarPorId() {
  Object.keys(porId).forEach(function (k) { delete porId[k]; });
  candidatos.forEach(function (c) { porId[c.id_contenedor] = c; });
  // Un contenedor de la ruta que ya no esta entre los candidatos (por ejemplo, retirado despues) se conserva desde las paradas guardadas.
  ruta.paradas.forEach(function (p) {
    if (!porId[p.id_contenedor]) {
      porId[p.id_contenedor] = { id_contenedor: p.id_contenedor, codigo: p.codigo, direccion: p.direccion, latitud: p.latitud, longitud: p.longitud, estado: p.estado, en_esta_ruta: true, id_otra_ruta: null, otra_ruta: null };
      candidatos.push(porId[p.id_contenedor]);
    }
  });
}

async function cargarCandidatos() {
  const respuesta = await apiGet(API.recoleccion + "/rutas/" + idRuta + "/candidatos");
  candidatos = respuesta.datos || [];
  armarPorId();
}

async function cargar() {
  if (!idRuta) {
    document.getElementById("bajada-ruta").textContent = "Elegí la ruta que querés armar desde el listado de Rutas.";
    avisoPaginaError("No se indicó qué ruta armar. Volvé a Rutas y tocá «Armar» en la ruta que quieras.");
    return;
  }
  try {
    const [detalle, cands] = await Promise.all([apiGet(API.recoleccion + "/rutas/" + idRuta), apiGet(API.recoleccion + "/rutas/" + idRuta + "/candidatos")]);
    ruta = detalle.datos;
    candidatos = cands.datos || [];
    armarPorId();
    lista = ruta.paradas.map(function (p) { return p.id_contenedor; });
    mover.clear();
    sucio = false;
    document.getElementById("titulo-ruta").textContent = ruta.nombre;
    document.getElementById("bajada-ruta").textContent = ruta.zona + " · " + ruta.dia_semana + " " + ruta.hora_inicio + (ruta.estado === "inactiva" ? " · ruta inactiva" : "") +
      ". Elegí los contenedores, ordenalos y guardá.";
    panel.classList.remove("oculto");
    refrescar(true);
  } catch (error) {
    avisoPaginaError(error.estado === 404 ? "Esa ruta no existe." : "No se pudo cargar la ruta. " + mensajeDeFallo(error));
  }
}

async function trazar() {
  mostrarEstado("Guardando el recorrido…", "aviso");
  ocupado = true;
  sincronizarBotones();
  try {
    const { ok, resultado } = await apiPost(API.recoleccion + "/rutas/" + idRuta + "/trazado", {});
    if (!ok) {
      mostrarEstado(resultado.mensaje || "No se pudo trazar la ruta.", "error");
      return;
    }
    ruta = resultado.datos;
    lista = ruta.paradas.map(function (p) { return p.id_contenedor; });
    refrescar(true);
    if (ruta.trazado && ruta.trazado.tipo === "calles") {
      mostrarEstado("", "ok");
      avisoPagina("Ruta guardada y trazada por las calles: " + kilometros(ruta.trazado.distancia_m / 1000) + "." + avisoDeMovidos);
      avisoDeMovidos = "";
    } else {
      mostrarEstado("", "ok");
      avisoPagina("Ruta guardada: se dibuja en línea recta entre sus paradas." + avisoDeMovidos);
      avisoDeMovidos = "";
    }
  } catch (error) {
    mostrarEstado("No se pudo conectar con el servidor para trazar la ruta. " + mensajeDeFallo(error), "error");
  } finally {
    ocupado = false;
    sincronizarBotones();
  }
}

async function guardar() {
  if (ocupado || !sucio) {
    return;
  }
  ocupado = true;
  sincronizarBotones();
  avisoPagina("");
  mostrarEstado("", "ok");
  let guardada = false;
  try {
    const { ok, resultado } = await apiPut(API.recoleccion + "/rutas/" + idRuta + "/contenedores", { contenedores: lista, mover: mover.size > 0 });
    if (!ok) {
      avisoPaginaError(resultado.mensaje || "No se pudo guardar la ruta.");
      return;
    }
    ruta = resultado.datos;
    lista = ruta.paradas.map(function (p) { return p.id_contenedor; });
    mover.clear();
    sucio = false;
    const sinTrazo = Array.from(new Set((resultado.movidos || []).map(function (m) { return m.ruta; })));
    avisoDeMovidos = sinTrazo.length ? " Los contenedores movidos salieron de «" + sinTrazo.join("», «") + "»: esa ruta quedó sin trazar, abrila y tocá «Reintentar el trazado»." : "";
    await cargarCandidatos();
    refrescar(true);
    guardada = true;
  } catch (error) {
    avisoPaginaError("No se pudo guardar la ruta. " + mensajeDeFallo(error));
  } finally {
    ocupado = false;
    sincronizarBotones();
  }
  if (!guardada) {
    return;
  }
  if (lista.length < 2) {
    avisoPagina("Ruta guardada. Con menos de 2 contenedores no hay recorrido que trazar." + avisoDeMovidos);
    avisoDeMovidos = "";
    return;
  }
  await trazar();
}

async function descartar() {
  if (!sucio) {
    return;
  }
  const si = await confirmar({ titulo: "Descartar los cambios", mensaje: "Se pierde lo que cambiaste desde la última vez que guardaste.", textoBoton: "Descartar" });
  if (!si) {
    return;
  }
  lista = ruta.paradas.map(function (p) { return p.id_contenedor; });
  mover.clear();
  sucio = false;
  avisoPagina("");
  refrescar(true);
}

listaParadas.addEventListener("click", function (evento) {
  const boton = evento.target.closest("button");
  if (!boton || boton.disabled) {
    return;
  }
  const id = parseInt(boton.dataset.id, 10);
  if (boton.classList.contains("js-subir")) {
    desplazar(id, -1);
  } else if (boton.classList.contains("js-bajar")) {
    desplazar(id, 1);
  } else if (boton.classList.contains("js-quitar")) {
    quitar(id);
  }
});
listaCandidatos.addEventListener("click", function (evento) {
  const boton = evento.target.closest(".js-agregar");
  if (boton && !boton.disabled) {
    agregar(parseInt(boton.dataset.id, 10));
  }
});
buscador.addEventListener("input", dibujarCandidatos);
btnGuardar.addEventListener("click", guardar);
btnDescartar.addEventListener("click", descartar);
btnReintentar.addEventListener("click", function () { trazar(); });
window.addEventListener("beforeunload", function (evento) {
  if (sucio) {
    evento.preventDefault();
    evento.returnValue = "";
  }
});

cargar();
