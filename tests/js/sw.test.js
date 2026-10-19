// Prueba la logica de frontend-usuarios/sw.js sin registrarlo: lo ejecuta con self, caches y fetch simulados.
const salida = document.getElementById("resultado");
const lineas = [];
let pasaron = 0, fallaron = 0;
const BASE = "http://localhost/sigeru/frontend-usuarios/";
// Desde la carpeta que sirve las pruebas (frontend-usuarios/pruebas-tmp) o desde tests/js, segun donde se abra.
const RUTA_SW = location.pathname.indexOf("/frontend-usuarios/") !== -1 ? "../sw.js" : "../../frontend-usuarios/sw.js";

function afirmar(descripcion, esperado, obtenido) {
  const ok = JSON.stringify(esperado) === JSON.stringify(obtenido);
  ok ? pasaron++ : fallaron++;
  lineas.push((ok ? "OK    " : "FALLA ") + descripcion + (ok ? "" : "\n      esperado: " + JSON.stringify(esperado) + "\n      obtenido: " + JSON.stringify(obtenido)));
}

function respuestaFalsa(url) {
  return { ok: true, type: "basic", url: url, clone: function () { return this; } };
}

async function montar(hayRed) {
  const oyentes = {};
  const almacenes = {};
  const pedidosAlaRed = [];
  const claveDe = function (p) { return new URL(typeof p === "string" ? p : p.url, BASE).href; };
  const caches = {
    open: function (nombre) {
      almacenes[nombre] = almacenes[nombre] || new Map();
      return Promise.resolve({
        add: function (url) { return fetchFalso(url).then(function (r) { almacenes[nombre].set(claveDe(url), r); }); },
        put: function (p, r) { almacenes[nombre].set(claveDe(p), r); return Promise.resolve(); },
      });
    },
    keys: function () { return Promise.resolve(Object.keys(almacenes)); },
    delete: function (n) { delete almacenes[n]; return Promise.resolve(true); },
    match: function (p) {
      for (const nombre of Object.keys(almacenes)) { if (almacenes[nombre].has(claveDe(p))) { return Promise.resolve(almacenes[nombre].get(claveDe(p))); } }
      return Promise.resolve(undefined);
    },
  };
  const red = { hay: hayRed };
  function fetchFalso(p) {
    pedidosAlaRed.push(claveDe(p));
    return red.hay ? Promise.resolve(respuestaFalsa(claveDe(p))) : Promise.reject(new TypeError("sin red"));
  }
  // La pagina impone una politica de seguridad que no admite eval: el service worker se carga como un script mas,
  // con los globales que usa (caches, fetch, skipWaiting, clients, addEventListener) reemplazados por simulados.
  const agregar = window.addEventListener.bind(window);
  window.addEventListener = function (tipo, f, o) { if (["install", "activate", "fetch"].indexOf(tipo) !== -1) { oyentes[tipo] = f; } else { agregar(tipo, f, o); } };
  Object.defineProperty(window, "caches", { value: caches, configurable: true });
  window.fetch = fetchFalso;
  window.skipWaiting = function () {};
  window.clients = { claim: function () {} };
  await new Promise(function (resolver, rechazar) {
    const s = document.createElement("script");
    s.src = RUTA_SW;
    s.onload = resolver; s.onerror = rechazar;
    document.head.appendChild(s);
  });
  window.addEventListener = agregar;
  async function evento(tipo) {
    let espera = Promise.resolve();
    oyentes[tipo]({ waitUntil: function (p) { espera = p; } });
    await espera;
  }
  async function pedir(metodo, url, modo) {
    const e = { request: { method: metodo, url: url, mode: modo || "no-cors" }, llamado: false, respondWith: function (p) { this.llamado = true; this.promesa = p; } };
    oyentes.fetch(e);
    return { atendido: e.llamado, respuesta: e.llamado ? await e.promesa : null };
  }
  return { evento: evento, pedir: pedir, almacenes: almacenes, red: red, pedidosAlaRed: pedidosAlaRed };
}

async function correr() {
  const fuente = await (await fetch(RUTA_SW)).text();
  const precarga = (fuente.match(/const PRECARGA = \[([\s\S]*?)\];/)[1].match(/"([^"]+)"/g) || []).map(function (s) { return s.replace(/"/g, ""); });
  const sw = await montar(true);
  await sw.evento("install");
  afirmar("Al instalarse precarga todos los archivos de la lista (" + precarga.length + ") en el cache versionado", [precarga.length, true], [sw.almacenes["sigeru-estaticos-v1"].size, precarga.every(function (a) { return sw.almacenes["sigeru-estaticos-v1"].has(BASE + a); })]);
  sw.almacenes["sigeru-estaticos-v0"] = new Map([["x", 1]]);
  sw.almacenes["otra-cosa"] = new Map();
  await sw.evento("activate");
  afirmar("Al activarse borra los caches de versiones anteriores y deja solo el actual", ["sigeru-estaticos-v1"], Object.keys(sw.almacenes));

  const apis = ["http://localhost/sigeru/api-gestion/incidencias", "http://localhost/sigeru/api-usuarios/sesion", "http://localhost/sigeru/api-recoleccion/recolecciones/5", "http://localhost/sigeru/api-gestion/incidencias/3/foto"];
  const atendidos = [];
  for (const u of apis) { atendidos.push((await sw.pedir("GET", u)).atendido, (await sw.pedir("GET", u, "navigate")).atendido); }
  afirmar("Nunca atiende un pedido a las APIs (ni GET ni navegacion): lo deja pasar al navegador", false, atendidos.some(Boolean));
  afirmar("No atiende lo que no es GET (un POST a un archivo estatico), ni otro origen (mosaicos del mapa)", [false, false, false], [
    (await sw.pedir("POST", BASE + "css/styles.css")).atendido, (await sw.pedir("GET", "https://tile.openstreetmap.org/12/1/1.png")).atendido, (await sw.pedir("PUT", BASE + "login.html", "navigate")).atendido]);

  const pag = await sw.pedir("GET", BASE + "recoleccion.html?id=5", "navigate");
  afirmar("Una pagina con red se trae de la red y se guarda en el cache", [true, true], [pag.atendido, sw.almacenes["sigeru-estaticos-v1"].has(BASE + "recoleccion.html?id=5")]);
  sw.red.hay = false;
  const sinRed = await sw.pedir("GET", BASE + "recoleccion.html?id=5", "navigate");
  afirmar("Sin red, esa pagina se contesta con la copia guardada", BASE + "recoleccion.html?id=5", sinRed.respuesta && sinRed.respuesta.url);
  const nueva = await sw.pedir("GET", BASE + "usuarios.html", "navigate");
  afirmar("Sin red, una pagina que nunca se guardo muestra «sin-conexion.html»", BASE + "sin-conexion.html", nueva.respuesta && nueva.respuesta.url);
  const css = await sw.pedir("GET", BASE + "css/styles.css");
  afirmar("Un estatico guardado se contesta aunque no haya red", BASE + "css/styles.css", css.respuesta && css.respuesta.url);
  sw.red.hay = true;
  await sw.pedir("GET", BASE + "img/nuevo.png");
  const guardados = Array.from(sw.almacenes["sigeru-estaticos-v1"].keys());
  afirmar("En todo el cache no hay ni una respuesta de las APIs", 0, guardados.filter(function (u) { return /\/api-/.test(u); }).length);
  afirmar("Solo se guardaron la precarga y lo que se pidio (" + guardados.length + " archivos: " + precarga.length + " de la precarga, la pagina y el estatico nuevo)", precarga.length + 2, guardados.length);
}

correr().catch(function (e) { fallaron++; lineas.push("ERROR no esperado: " + (e && e.stack || e)); }).then(function () {
  lineas.push("", "Pasaron: " + pasaron + "   Fallaron: " + fallaron);
  salida.textContent = lineas.join("\n");
  salida.className = fallaron === 0 ? "bien" : "mal";
  document.title = (fallaron === 0 ? "OK " : "FALLAN ") + pasaron + "/" + (pasaron + fallaron);
});
