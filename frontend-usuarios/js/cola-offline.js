// Cola de operaciones sin conexion (extras, parte 21). SOLO para la Cuadrilla y SOLO dos acciones: registrar un vaciado y registrar un
// avance de incidencia. Cuando el celular no puede hablar con el servidor, la operacion queda guardada en IndexedDB y se envia sola
// al volver la conexion. Cada operacion lleva una clave unica (UUID) que va en el encabezado "Idempotency-Key": si el primer envio
// si llego pero la respuesta se perdio, el reenvio no carga el dato dos veces (el servidor devuelve lo mismo).
// En la cola solo hay lo que se escribio en el formulario (codigo del contenedor, kilos, texto del avance): ninguna credencial.
const ColaOffline = (function () {
  "use strict";

  const BASE = "sigeru-cola";
  const ALMACEN = "operaciones";
  // Lo unico que se puede guardar sin conexion, y el rol que puede hacerlo.
  const PERMITIDAS = {
    vaciado: { rol: "Cuadrilla", destino: /^\/sigeru\/api-recoleccion\/recolecciones\/\d+\/vaciados$/, nombre: "vaciado" },
    avance:  { rol: "Cuadrilla", destino: /^\/sigeru\/api-gestion\/incidencias\/\d+\/avance$/, nombre: "avance" },
  };
  const FORMATO_CLAVE = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

  // ---- logica pura (se prueba sola, sin red ni base) ----

  function nuevaClave() {
    if (window.crypto && typeof window.crypto.randomUUID === "function") {
      return window.crypto.randomUUID();
    }
    const b = new Uint8Array(16);
    window.crypto.getRandomValues(b);
    b[6] = (b[6] & 0x0f) | 0x40;
    b[8] = (b[8] & 0x3f) | 0x80;
    const h = Array.prototype.map.call(b, function (x) { return ("0" + x.toString(16)).slice(-2); }).join("");
    return h.slice(0, 8) + "-" + h.slice(8, 12) + "-" + h.slice(12, 16) + "-" + h.slice(16, 20) + "-" + h.slice(20);
  }

  // Que hacer con la respuesta del servidor. estado = null cuando ni siquiera hubo respuesta (sin red).
  function clasificar(estado) {
    if (estado === null || estado === undefined) { return "red"; }
    if (estado >= 200 && estado < 300) { return "enviado"; }
    if (estado === 401) { return "sesion"; }
    if (estado === 429 || estado >= 500) { return "servidor"; }
    return "rechazado";
  }

  function esOperacionValida(op, rol) {
    const regla = PERMITIDAS[op && op.tipo];
    return !!regla && regla.rol === rol && regla.destino.test(String(op.url || "")) && FORMATO_CLAVE.test(String(op.clave || ""));
  }

  function ordenar(lista) {
    return lista.slice().sort(function (a, b) { return a.creada - b.creada || (a.clave < b.clave ? -1 : 1); });
  }

  function textoDePendientes(n) {
    return n + (n === 1 ? " pendiente de enviar" : " pendientes de enviar");
  }

  // Resumen para la barra: cuantos esperan, cuantos fueron rechazados por el servidor (hay que mirarlos) y cuantos son de otra persona.
  function resumir(lista, idUsuario) {
    const mios = lista.filter(function (o) { return o.id_usuario === idUsuario; });
    const rechazadas = mios.filter(function (o) { return o.estado === "rechazada"; });
    return { total: mios.length, esperando: mios.length - rechazadas.length, rechazadas: rechazadas.length, deOtros: lista.length - mios.length };
  }

  // ---- IndexedDB ----

  function abrir() {
    return new Promise(function (resolver, rechazar) {
      if (!window.indexedDB) { rechazar(new Error("Este navegador no guarda datos sin conexion.")); return; }
      const pedido = window.indexedDB.open(BASE, 1);
      pedido.onupgradeneeded = function () {
        pedido.result.createObjectStore(ALMACEN, { keyPath: "clave" });
      };
      pedido.onsuccess = function () { resolver(pedido.result); };
      pedido.onerror = function () { rechazar(pedido.error); };
    });
  }

  function transaccion(modo, accion) {
    return abrir().then(function (bd) {
      return new Promise(function (resolver, rechazar) {
        const t = bd.transaction(ALMACEN, modo);
        const almacen = t.objectStore(ALMACEN);
        let resultado;
        const pedido = accion(almacen);
        if (pedido) { pedido.addEventListener("success", function () { resultado = pedido.result; }); }
        t.oncomplete = function () { bd.close(); resolver(resultado); };
        t.onerror = function () { bd.close(); rechazar(t.error); };
        t.onabort = function () { bd.close(); rechazar(t.error); };
      });
    });
  }

  function avisarCambio() {
    document.dispatchEvent(new CustomEvent("sigeru:cola-cambio"));
  }

  function usuarioActual() {
    return (typeof Sesion !== "undefined" && Sesion.obtener()) || null;
  }

  function disponible() {
    const u = usuarioActual();
    return !!window.indexedDB && !!u && u.rol === "Cuadrilla";
  }

  function listar() {
    return transaccion("readonly", function (a) { return a.getAll(); }).then(function (l) { return ordenar(l || []); });
  }

  function contar() {
    const u = usuarioActual();
    return listar().then(function (l) { return resumir(l, u ? u.id_usuario : null); });
  }

  function eliminar(clave) {
    return transaccion("readwrite", function (a) { return a.delete(clave); }).then(avisarCambio);
  }

  function actualizar(clave, cambios) {
    return transaccion("readwrite", function (a) {
      const lectura = a.get(clave);
      lectura.addEventListener("success", function () {
        if (lectura.result) { a.put(Object.assign({}, lectura.result, cambios)); }
      });
      return lectura;
    }).then(avisarCambio);
  }

  // ---- guardar y enviar ----

  function esFalloDeRed(error) {
    return (typeof navigator !== "undefined" && navigator.onLine === false) || error instanceof TypeError;
  }

  // op: { tipo, url, cuerpo, etiqueta, clave }. Devuelve lo guardado. Rechaza si no es una de las dos acciones permitidas o no es Cuadrilla.
  function encolar(op) {
    const u = usuarioActual();
    if (!u || !esOperacionValida(op, u.rol)) {
      return Promise.reject(new Error("Esta accion no se puede guardar sin conexion."));
    }
    const item = {
      clave: op.clave, tipo: op.tipo, url: op.url, cuerpo: op.cuerpo, etiqueta: String(op.etiqueta || "").slice(0, 120),
      id_usuario: u.id_usuario, creada: Date.now(), estado: "pendiente", intentos: 0, mensaje: null,
    };
    return transaccion("readwrite", function (a) { return a.put(item); }).then(function () { avisarCambio(); return item; });
  }

  function enviarUna(op) {
    return fetch(op.url, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json", "Idempotency-Key": op.clave },
      body: JSON.stringify(op.cuerpo),
    }).then(function (respuesta) {
      return respuesta.json().catch(function () { return {}; }).then(function (json) {
        return { estado: respuesta.status, mensaje: json && json.mensaje, repetida: !!(json && json.repetida) };
      });
    }).catch(function () { return { estado: null }; });
  }

  let sincronizando = null;

  // Envia, en orden, lo pendiente de la persona que tiene la sesion abierta. Nunca borra un dato que el servidor no acepto:
  //  - sin red o servidor caido: queda pendiente y se corta (se reintenta despues);
  //  - sesion vencida: queda todo guardado y se avisa para volver a entrar;
  //  - rechazada (por ejemplo la recoleccion se cancelo): queda marcada con el motivo para que la persona decida.
  function sincronizar() {
    if (sincronizando) { return sincronizando; }
    const u = usuarioActual();
    if (!u) { return Promise.resolve({ enviados: 0, sinRed: false, sesionVencida: false, rechazadas: 0 }); }
    sincronizando = listar().then(function (lista) {
      const mias = lista.filter(function (o) { return o.id_usuario === u.id_usuario && o.estado !== "rechazada"; });
      const salida = { enviados: 0, sinRed: false, sesionVencida: false, rechazadas: 0 };
      return mias.reduce(function (cadena, op) {
        return cadena.then(function (seguir) {
          if (!seguir) { return false; }
          return enviarUna(op).then(function (r) {
            const clase = clasificar(r.estado);
            if (clase === "enviado") { salida.enviados++; return eliminar(op.clave).then(function () { return true; }); }
            if (clase === "red") { salida.sinRed = true; return false; }
            if (clase === "sesion") { salida.sesionVencida = true; return false; }
            if (clase === "servidor") { return actualizar(op.clave, { intentos: op.intentos + 1 }).then(function () { return false; }); }
            salida.rechazadas++;
            return actualizar(op.clave, { estado: "rechazada", mensaje: r.mensaje || ("El servidor no la aceptó (" + r.estado + ")."), intentos: op.intentos + 1 })
              .then(function () { return true; });
          });
        });
      }, Promise.resolve(true)).then(function () { return salida; });
    }).then(function (salida) {
      sincronizando = null;
      if (salida.enviados > 0) { document.dispatchEvent(new CustomEvent("sigeru:cola-enviada", { detail: salida })); }
      return salida;
    }, function (error) { sincronizando = null; throw error; });
    return sincronizando;
  }

  function reintentar(clave) {
    return actualizar(clave, { estado: "pendiente", mensaje: null }).then(sincronizar);
  }

  return {
    nuevaClave: nuevaClave, clasificar: clasificar, esOperacionValida: esOperacionValida, ordenar: ordenar, resumir: resumir,
    textoDePendientes: textoDePendientes, esFalloDeRed: esFalloDeRed, disponible: disponible, encolar: encolar, listar: listar,
    contar: contar, eliminar: eliminar, descartar: eliminar, sincronizar: sincronizar, reintentar: reintentar, PERMITIDAS: PERMITIDAS,
  };
})();
