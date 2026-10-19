// Pruebas de frontend-usuarios/js/cola-offline.js: logica pura + IndexedDB real del navegador + fetch simulado.
var Sesion = { usuario: { id_usuario: 7, rol: "Cuadrilla" }, obtener: function () { return this.usuario; } };
const salida = document.getElementById("resultado");
const lineas = [];
let pasaron = 0, fallaron = 0;

function afirmar(descripcion, esperado, obtenido) {
  const ok = JSON.stringify(esperado) === JSON.stringify(obtenido);
  ok ? pasaron++ : fallaron++;
  lineas.push((ok ? "OK    " : "FALLA ") + descripcion + (ok ? "" : "\n      esperado: " + JSON.stringify(esperado) + "\n      obtenido: " + JSON.stringify(obtenido)));
}

const URL_VACIADO = "/sigeru/api-recoleccion/recolecciones/12/vaciados";
const URL_AVANCE = "/sigeru/api-gestion/incidencias/5/avance";
const original = window.fetch;

function respuesta(estado, cuerpo) {
  return Promise.resolve({ status: estado, ok: estado >= 200 && estado < 300, json: function () { return Promise.resolve(cuerpo || {}); } });
}
async function vaciar() {
  for (const op of await ColaOffline.listar()) { await ColaOffline.eliminar(op.clave); }
}

async function logicaPura() {
  const claves = new Set();
  for (let i = 0; i < 200; i++) { claves.add(ColaOffline.nuevaClave()); }
  afirmar("200 claves nuevas: todas distintas y con forma de UUID", [200, true], [claves.size, Array.from(claves).every(function (c) { return /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/.test(c); })]);
  afirmar("clasificar: sin respuesta = red; 200/201 = enviado; 401 = sesion",
    ["red", "enviado", "enviado", "sesion"], [ColaOffline.clasificar(null), ColaOffline.clasificar(200), ColaOffline.clasificar(201), ColaOffline.clasificar(401)]);
  afirmar("clasificar: 400, 403, 404, 409, 422 = rechazado; 429, 500, 503 = servidor",
    [["rechazado", "rechazado", "rechazado", "rechazado", "rechazado"], ["servidor", "servidor", "servidor"]],
    [[400, 403, 404, 409, 422].map(ColaOffline.clasificar), [429, 500, 503].map(ColaOffline.clasificar)]);
  const k = ColaOffline.nuevaClave();
  afirmar("esOperacionValida: vaciado y avance de la Cuadrilla con su clave", [true, true], [
    ColaOffline.esOperacionValida({ tipo: "vaciado", url: URL_VACIADO, clave: k }, "Cuadrilla"), ColaOffline.esOperacionValida({ tipo: "avance", url: URL_AVANCE, clave: k }, "Cuadrilla")]);
  afirmar("esOperacionValida: no para otros roles, otras acciones, otras APIs, rutas raras ni claves malas", [false, false, false, false, false, false, false], [
    ColaOffline.esOperacionValida({ tipo: "vaciado", url: URL_VACIADO, clave: k }, "Administrador"),
    ColaOffline.esOperacionValida({ tipo: "vaciado", url: URL_VACIADO, clave: k }, "Vecino"),
    ColaOffline.esOperacionValida({ tipo: "resolucion", url: "/sigeru/api-gestion/incidencias/5/resolucion", clave: k }, "Cuadrilla"),
    ColaOffline.esOperacionValida({ tipo: "vaciado", url: "/sigeru/api-usuarios/usuarios", clave: k }, "Cuadrilla"),
    ColaOffline.esOperacionValida({ tipo: "vaciado", url: URL_VACIADO + "/../../usuarios", clave: k }, "Cuadrilla"),
    ColaOffline.esOperacionValida({ tipo: "vaciado", url: URL_VACIADO, clave: "no-es-uuid" }, "Cuadrilla"),
    ColaOffline.esOperacionValida(null, "Cuadrilla")]);
  afirmar("ordenar: de la mas vieja a la mas nueva", [1, 2, 3], ColaOffline.ordenar([{ creada: 30, clave: "c" }, { creada: 10, clave: "a" }, { creada: 20, clave: "b" }]).map(function (o) { return o.creada / 10; }));
  afirmar("resumir: esperando, rechazadas y de otras personas", { total: 3, esperando: 2, rechazadas: 1, deOtros: 1 }, ColaOffline.resumir([
    { id_usuario: 7, estado: "pendiente" }, { id_usuario: 7, estado: "pendiente" }, { id_usuario: 7, estado: "rechazada" }, { id_usuario: 9, estado: "pendiente" }], 7));
  afirmar("textoDePendientes: singular y plural", ["1 pendiente de enviar", "3 pendientes de enviar"], [ColaOffline.textoDePendientes(1), ColaOffline.textoDePendientes(3)]);
  afirmar("esFalloDeRed: un TypeError de fetch si; un error comun no", [true, false], [ColaOffline.esFalloDeRed(new TypeError("Failed to fetch")), ColaOffline.esFalloDeRed(new SyntaxError("json"))]);
}

async function indexedDb() {
  await vaciar();
  const c1 = ColaOffline.nuevaClave();
  await ColaOffline.encolar({ tipo: "vaciado", url: URL_VACIADO, cuerpo: { codigo_contenedor: "CNT-001", peso_kg: "50" }, clave: c1, etiqueta: "Vaciado de CNT-001" });
  const lista = await ColaOffline.listar();
  afirmar("encolar guarda en IndexedDB: 1 pendiente con su clave, su dueno y el estado «pendiente»", [1, c1, 7, "pendiente"], [lista.length, lista[0].clave, lista[0].id_usuario, lista[0].estado]);
  afirmar("contar: 1 esperando", { total: 1, esperando: 1, rechazadas: 0, deOtros: 0 }, await ColaOffline.contar());
  let fallo = null;
  try { await ColaOffline.encolar({ tipo: "resolucion", url: "/sigeru/api-gestion/incidencias/5/resolucion", cuerpo: {}, clave: ColaOffline.nuevaClave() }); } catch (e) { fallo = e.message; }
  afirmar("encolar rechaza una accion que no se puede guardar sin conexion", true, fallo !== null);
  Sesion.usuario = { id_usuario: 8, rol: "Administrador" };
  fallo = null;
  try { await ColaOffline.encolar({ tipo: "vaciado", url: URL_VACIADO, cuerpo: {}, clave: ColaOffline.nuevaClave() }); } catch (e) { fallo = e.message; }
  afirmar("encolar rechaza a quien no es Cuadrilla", true, fallo !== null);
  Sesion.usuario = { id_usuario: 7, rol: "Cuadrilla" };
  await ColaOffline.eliminar(c1);
  afirmar("eliminar la saca de la cola", 0, (await ColaOffline.listar()).length);
}

async function sincronizacion() {
  const pedidos = [];
  let modo = "red";
  window.fetch = function (url, config) {
    pedidos.push({ url: url, clave: config.headers["Idempotency-Key"], cuerpo: JSON.parse(config.body), tipo: config.headers["Content-Type"] });
    if (modo === "red") { return Promise.reject(new TypeError("Failed to fetch")); }
    if (modo === "ok") { return respuesta(201, { ok: true, mensaje: "Vaciado registrado" }); }
    if (modo === "sesion") { return respuesta(401, { ok: false, mensaje: "Hay que iniciar sesión" }); }
    if (modo === "cancelada") { return respuesta(409, { ok: false, mensaje: "La recolección está cancelada" }); }
    if (modo === "caido") { return respuesta(503, {}); }
    return respuesta(200, {});
  };
  let evento = 0;
  document.addEventListener("sigeru:cola-enviada", function () { evento++; });
  const kv = ColaOffline.nuevaClave(), ka = ColaOffline.nuevaClave();
  await ColaOffline.encolar({ tipo: "vaciado", url: URL_VACIADO, cuerpo: { codigo_contenedor: "CNT-001", peso_kg: "50" }, clave: kv, etiqueta: "Vaciado" });
  await new Promise(function (r) { setTimeout(r, 5); });
  await ColaOffline.encolar({ tipo: "avance", url: URL_AVANCE, cuerpo: { observacion: "Llegamos" }, clave: ka, etiqueta: "Avance" });

  modo = "red";
  let r = await ColaOffline.sincronizar();
  afirmar("Sin red: no se envia nada, queda todo pendiente y se corta en el primero", [true, 0, 2, 1], [r.sinRed, r.enviados, (await ColaOffline.listar()).length, pedidos.length]);
  modo = "sesion"; pedidos.length = 0;
  r = await ColaOffline.sincronizar();
  afirmar("Sesion vencida: queda todo guardado y se avisa para volver a entrar", [true, 2, 1], [r.sesionVencida, (await ColaOffline.listar()).length, pedidos.length]);
  modo = "caido"; pedidos.length = 0;
  r = await ColaOffline.sincronizar();
  let lista = await ColaOffline.listar();
  afirmar("Servidor caido (503): queda pendiente y se cuenta el intento", [2, 1, "pendiente"], [lista.length, lista[0].intentos, lista[0].estado]);
  modo = "ok"; pedidos.length = 0; evento = 0;
  r = await ColaOffline.sincronizar();
  afirmar("Vuelve la red: se envian los 2 en orden (vaciado y despues avance), cada uno con su clave en Idempotency-Key", [2, [URL_VACIADO, URL_AVANCE], [kv, ka], "application/json"], [r.enviados, pedidos.map(function (p) { return p.url; }), pedidos.map(function (p) { return p.clave; }), pedidos[0].tipo]);
  afirmar("... y la cola queda vacia y se avisa a la pantalla", [0, 1], [(await ColaOffline.listar()).length, evento]);
  pedidos.length = 0;
  await ColaOffline.sincronizar();
  afirmar("Sincronizar de nuevo no manda nada mas (nada se envia dos veces)", 0, pedidos.length);

  // El primer envio llego al servidor pero la respuesta se perdio: el reenvio lleva la MISMA clave.
  const kd = ColaOffline.nuevaClave();
  await ColaOffline.encolar({ tipo: "vaciado", url: URL_VACIADO, cuerpo: { codigo_contenedor: "CNT-002", peso_kg: "10" }, clave: kd, etiqueta: "Vaciado dudoso" });
  modo = "red"; pedidos.length = 0;
  await ColaOffline.sincronizar();
  modo = "ok";
  await ColaOffline.sincronizar();
  afirmar("Si el primer intento falla y el segundo sale, los dos llevan la misma clave (el servidor ignora el duplicado)", [2, kd, kd, 0], [pedidos.length, pedidos[0].clave, pedidos[1].clave, (await ColaOffline.listar()).length]);

  // Rechazada por el servidor: no se pierde el dato.
  const kc = ColaOffline.nuevaClave();
  await ColaOffline.encolar({ tipo: "vaciado", url: URL_VACIADO, cuerpo: { codigo_contenedor: "CNT-003", peso_kg: "20" }, clave: kc, etiqueta: "Vaciado en recoleccion cancelada" });
  modo = "cancelada"; pedidos.length = 0;
  r = await ColaOffline.sincronizar();
  lista = await ColaOffline.listar();
  afirmar("Recoleccion cancelada mientras tanto: queda guardada como rechazada, con el motivo y el dato intacto", [1, 1, "rechazada", "La recolección está cancelada", "CNT-003"], [r.rechazadas, lista.length, lista[0].estado, lista[0].mensaje, lista[0].cuerpo.codigo_contenedor]);
  pedidos.length = 0;
  await ColaOffline.sincronizar();
  afirmar("Una rechazada no se reenvia sola una y otra vez", 0, pedidos.length);
  modo = "ok";
  r = await ColaOffline.reintentar(kc);
  afirmar("«Reintentar» la manda de nuevo (con la misma clave) y, si ahora sale, la saca", [1, kc, 0], [r.enviados, pedidos[0].clave, (await ColaOffline.listar()).length]);

  // Lo de otra persona no se toca.
  const ko = ColaOffline.nuevaClave();
  Sesion.usuario = { id_usuario: 9, rol: "Cuadrilla" };
  await ColaOffline.encolar({ tipo: "avance", url: URL_AVANCE, cuerpo: { observacion: "de otra persona" }, clave: ko, etiqueta: "Ajeno" });
  Sesion.usuario = { id_usuario: 7, rol: "Cuadrilla" };
  pedidos.length = 0;
  await ColaOffline.sincronizar();
  afirmar("Con otra persona en la sesion, lo guardado por alguien mas ni se envia ni se borra", [0, 1, { total: 0, esperando: 0, rechazadas: 0, deOtros: 1 }], [pedidos.length, (await ColaOffline.listar()).length, await ColaOffline.contar()]);
  await ColaOffline.descartar(ko);

  const guardado = JSON.stringify(await ColaOffline.listar());
  afirmar("En la cola no hay contrasenas ni datos de sesion", false, /password|contrase|cookie/i.test(guardado + JSON.stringify(Object.keys(localStorage))));
}

logicaPura().then(indexedDb).then(sincronizacion).catch(function (e) { fallaron++; lineas.push("ERROR no esperado: " + (e && e.stack || e)); }).then(function () {
  window.fetch = original;
  lineas.push("", "Pasaron: " + pasaron + "   Fallaron: " + fallaron);
  salida.textContent = lineas.join("\n");
  salida.className = fallaron === 0 ? "bien" : "mal";
  document.title = (fallaron === 0 ? "OK " : "FALLAN ") + pasaron + "/" + (pasaron + fallaron);
});
