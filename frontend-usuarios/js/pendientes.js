// Barra «N pendientes de enviar» de la Cuadrilla (extras, parte 21). Muestra lo que quedo guardado sin conexion, lo envia sola cuando vuelve
// la red (y cada minuto mientras haya algo), tiene el boton «Enviar ahora» y deja ver, reintentar o descartar lo que el servidor rechazo.
(function () {
  "use strict";
  if (typeof Sesion === "undefined" || typeof ColaOffline === "undefined" || !window.indexedDB) { return; }
  const usuario = Sesion.obtener();
  const principal = document.querySelector(".principal");
  if (!usuario || usuario.rol !== "Cuadrilla" || !principal) { return; }

  const barra = document.createElement("section");
  barra.id = "barra-pendientes";
  barra.className = "barra-pendientes oculto";
  barra.setAttribute("aria-label", "Trabajo guardado sin conexión");
  barra.innerHTML = '<p class="pend-estado" id="pend-estado" role="status" aria-live="polite"></p>' +
    '<div class="pend-acciones"><button type="button" class="boton boton-secundario boton-chico" id="pend-enviar">Enviar ahora</button>' +
    '<button type="button" class="boton boton-secundario boton-chico" id="pend-ver" aria-expanded="false" aria-controls="pend-lista">Ver</button></div>' +
    '<ul class="pend-lista oculto" id="pend-lista"></ul>';
  const topbar = principal.querySelector(".topbar");
  if (topbar) { topbar.insertAdjacentElement("afterend", barra); } else { principal.insertBefore(barra, principal.firstChild); }

  const estado = barra.querySelector("#pend-estado");
  const lista = barra.querySelector("#pend-lista");
  const botonVer = barra.querySelector("#pend-ver");
  const botonEnviar = barra.querySelector("#pend-enviar");
  let sesionVencida = false;
  let enviando = false;

  function fila(op) {
    const li = document.createElement("li");
    const texto = document.createElement("span");
    texto.textContent = op.etiqueta + " — " + (op.estado === "rechazada" ? "rechazada: " + (op.mensaje || "el servidor no la aceptó") : "esperando para enviarse");
    li.appendChild(texto);
    if (op.estado === "rechazada") {
      const reintentar = document.createElement("button");
      reintentar.type = "button"; reintentar.className = "boton boton-secundario boton-chico"; reintentar.textContent = "Reintentar";
      reintentar.dataset.accion = "reintentar"; reintentar.dataset.clave = op.clave;
      li.appendChild(reintentar);
    }
    const descartar = document.createElement("button");
    descartar.type = "button"; descartar.className = "boton boton-secundario boton-chico"; descartar.textContent = "Descartar";
    descartar.dataset.accion = "descartar"; descartar.dataset.clave = op.clave;
    li.appendChild(descartar);
    return li;
  }

  async function dibujar() {
    let todas = [];
    try { todas = await ColaOffline.listar(); } catch (e) { return; }
    const mias = todas.filter(function (o) { return o.id_usuario === usuario.id_usuario; });
    const r = ColaOffline.resumir(todas, usuario.id_usuario);
    const sinRed = navigator.onLine === false;
    const partes = [];
    if (sinRed) { partes.push("Sin conexión: lo que cargues se guarda en este teléfono."); }
    if (r.esperando > 0) { partes.push(ColaOffline.textoDePendientes(r.esperando) + "."); }
    if (r.rechazadas > 0) { partes.push(r.rechazadas + (r.rechazadas === 1 ? " fue rechazada" : " fueron rechazadas") + " por el servidor: revisala."); }
    if (sesionVencida && r.total > 0) { partes.push("Tu sesión venció: volvé a entrar para enviarlas. Lo guardado no se pierde."); }
    estado.textContent = partes.join(" ");
    barra.classList.toggle("oculto", partes.length === 0);
    barra.classList.toggle("con-problema", r.rechazadas > 0 || (sesionVencida && r.total > 0));
    botonEnviar.disabled = enviando || r.esperando === 0 || sinRed;
    botonVer.classList.toggle("oculto", r.total === 0);
    lista.textContent = "";
    mias.forEach(function (op) { lista.appendChild(fila(op)); });
    if (r.total === 0) { lista.classList.add("oculto"); botonVer.setAttribute("aria-expanded", "false"); }
  }

  async function enviar() {
    if (enviando || navigator.onLine === false) { return; }
    enviando = true;
    try {
      const salida = await ColaOffline.sincronizar();
      sesionVencida = salida.sesionVencida;
    } catch (e) { /* queda pendiente */ }
    enviando = false;
    dibujar();
  }

  botonVer.addEventListener("click", function () {
    const abierta = lista.classList.toggle("oculto") === false;
    botonVer.setAttribute("aria-expanded", abierta ? "true" : "false");
  });
  botonEnviar.addEventListener("click", enviar);
  lista.addEventListener("click", async function (evento) {
    const boton = evento.target.closest("button[data-accion]");
    if (!boton) { return; }
    if (boton.dataset.accion === "descartar") { await ColaOffline.descartar(boton.dataset.clave); }
    else { try { const s = await ColaOffline.reintentar(boton.dataset.clave); sesionVencida = s.sesionVencida; } catch (e) { /* sigue rechazada */ } }
    dibujar();
  });
  document.addEventListener("sigeru:cola-cambio", dibujar);
  window.addEventListener("online", enviar);
  window.addEventListener("offline", dibujar);
  setInterval(function () { if (navigator.onLine !== false) { ColaOffline.contar().then(function (r) { if (r.esperando > 0) { enviar(); } }).catch(function () {}); } }, 60000);

  dibujar();
  ColaOffline.contar().then(function (r) { if (r.esperando > 0) { enviar(); } }).catch(function () {});
})();
