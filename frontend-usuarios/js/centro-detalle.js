Sesion.exigir();

const usuarioActual = Sesion.obtener() || {};
const idCentro = parametroEntero("id") || (usuarioActual.rol === "Operario" && usuarioActual.id_centro ? parseInt(usuarioActual.id_centro, 10) : null);
const zonaOcupacion = document.getElementById("zona-ocupacion");
const cuerpoStock = document.getElementById("cuerpo-stock");
const cuerpoMovimientos = document.getElementById("cuerpo-movimientos");
const formRetiro = document.getElementById("form-retiro");
const formCorreccion = document.getElementById("form-correccion");
const filtroSentido = document.getElementById("filtro-sentido");
const paginador = crearPaginador("paginacion", "movimientos", function () { cargarMovimientos(); });

const MOTIVOS = {
  inventario_inicial: "Inventario inicial", recepcion: "Recepción", venta: "Venta",
  traslado: "Traslado", disposicion: "Disposición final", correccion: "Corrección",
};
let movimientoACorregir = null;

function kg(valor) {
  return Number(valor).toLocaleString("es-UY", { maximumFractionDigits: 2 }) + " kg";
}

function porcentaje(valor) {
  return Number(valor).toLocaleString("es-UY", { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + " %";
}

function dibujarOcupacion(c) {
  let html;
  if (c.ocupacion_pct === null) {
    html = '<p class="texto-tenue">Este centro no tiene capacidad cargada, así que no se calcula la ocupación. Hay <strong>' +
           escaparHtml(kg(c.stock_kg)) + "</strong> en stock.</p>";
  } else {
    const ancho = Math.min(100, c.ocupacion_pct);
    html = '<p class="metrica-valor">' + escaparHtml(porcentaje(c.ocupacion_pct)) + "</p>" +
      '<div class="barra-ocupacion" data-nivel="' + escaparHtml(c.nivel) + '" role="img" aria-label="Ocupación: ' +
      escaparHtml(porcentaje(c.ocupacion_pct)) + '"><span style="width:' + ancho + '%"></span></div>' +
      '<p class="texto-tenue">' + escaparHtml(kg(c.stock_kg)) + " de " + escaparHtml(kg(c.capacidad)) + " de capacidad.</p>";
    if (c.nivel === "alerta" || c.nivel === "lleno") {
      html += '<p class="aviso-centro" data-nivel="' + escaparHtml(c.nivel) + '" role="status">' +
        (c.nivel === "lleno" ? "El centro llegó a su capacidad: hay que retirar material." : "El centro pasó del 80 % de su capacidad: conviene planificar un retiro.") + "</p>";
    }
  }
  zonaOcupacion.innerHTML = html;
}

function dibujarStock(c) {
  cuerpoStock.innerHTML = c.saldos.map(function (s) {
    return '<tr role="row">' + celda("Tipo de residuo", s.tipo) + celda("Reciclable", s.reciclable ? "Sí" : "No") +
           celda("Stock", kg(s.kg), s.kg) + "</tr>";
  }).join("");
  const conStock = c.saldos.filter(function (s) { return s.kg > 0; });
  formRetiro.elements.id_tipo_residuo.innerHTML = conStock.map(function (s) {
    return '<option value="' + $entero(s.id_tipo_residuo) + '">' + escaparHtml(s.tipo + " (hay " + kg(s.kg) + ")") + "</option>";
  }).join("");
  Array.prototype.forEach.call(formRetiro.elements, function (campo) { campo.disabled = conStock.length === 0 && campo.tagName !== "FIELDSET"; });
}

async function cargarCentro() {
  try {
    const respuesta = await apiGet(API.gestion + "/centros/" + idCentro);
    const c = respuesta.datos;
    document.title = c.nombre + " - SiGeRU";
    document.getElementById("titulo-pagina").textContent = c.nombre;
    document.getElementById("bajada-pagina").textContent = (c.direccion || "Centro de acopio") + (c.estado === "inactivo" ? " · inactivo" : "");
    dibujarOcupacion(c);
    dibujarStock(c);
  } catch (error) {
    mostrarFallo(error);
  }
}

function mostrarFallo(error) {
  document.getElementById("detalle").innerHTML = '<section class="panel">' + (error.estado === 404
    ? bloqueEstado("No encontramos ese centro", "Puede que no exista o que no sea el tuyo.", false)
    : bloqueEstado("No se pudo cargar el centro", mensajeDeFallo(error), true)) + "</section>";
}

function filaMovimiento(m) {
  const entrada = m.sentido === "entrada";
  const detalle = [m.recoleccion, m.observacion].filter(Boolean).join(" · ");
  const puedeCorregir = m.motivo !== "correccion" && !m.corregido;
  return '<tr role="row"' + (m.corregido ? ' class="movimiento-corregido"' : "") + ">" +
    celda("Fecha", fechaLegible(m.fecha_hora), m.fecha_hora) +
    celdaEstado("Movimiento", (entrada ? "Entrada" : "Salida") + ": " + (MOTIVOS[m.motivo] || m.motivo), entrada ? "verde" : "naranja") +
    celda("Tipo", m.tipo) + celda("Peso", kg(m.kg), m.kg) + celda("Detalle", detalle) + celda("Registró", m.usuario) +
    '<td role="cell" data-etiqueta="Acciones">' + (puedeCorregir
      ? '<button type="button" class="boton boton-secundario boton-chico js-corregir" data-id="' + $entero(m.id_movimiento) + '" data-texto="' +
        escaparHtml((entrada ? "Entrada" : "Salida") + " de " + kg(m.kg) + " de " + m.tipo) + '">Corregir<span class="solo-lectores"> ese movimiento</span></button>'
      : "") + "</td></tr>";
}

async function cargarMovimientos() {
  cuerpoMovimientos.innerHTML = filaEsqueleto(7, 3);
  const consulta = new URLSearchParams({ pagina: paginador.estado.pagina, por_pagina: paginador.estado.porPagina });
  if (filtroSentido.value) {
    consulta.set("sentido", filtroSentido.value);
  }
  try {
    const respuesta = await apiGet(API.gestion + "/centros/" + idCentro + "/movimientos?" + consulta.toString());
    if (paginador.ajustar(respuesta.meta)) {
      cargarMovimientos();
      return;
    }
    cuerpoMovimientos.innerHTML = respuesta.datos.length === 0
      ? filaVacia(7, "Todavía no hay movimientos", "Las recepciones y los retiros aparecen acá.")
      : respuesta.datos.map(filaMovimiento).join("");
    paginador.dibujar(respuesta.meta);
  } catch (error) {
    cuerpoMovimientos.innerHTML = filaError(7, mensajeDeFallo(error));
    paginador.dibujar(null);
  }
}

function recargarTodo() {
  cargarCentro();
  cargarMovimientos();
}

formRetiro.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  const valor = Number(formRetiro.elements.kg.value);
  if (!(valor > 0) || valor > 1000000 || Math.abs(valor * 100 - Math.round(valor * 100)) > 1e-6) {
    marcarError(formRetiro.elements.kg, "Los kg tienen que ser un número mayor que 0, con hasta 2 decimales.");
    formRetiro.elements.kg.focus();
    return;
  }
  limpiarError(formRetiro.elements.kg);
  const motivo = formRetiro.elements.motivo.selectedOptions[0].textContent.toLowerCase();
  const seguro = await confirmar({
    titulo: "Registrar el retiro",
    mensaje: "Salen " + kg(valor) + " de " + formRetiro.elements.id_tipo_residuo.selectedOptions[0].textContent.split(" (")[0] +
             " por " + motivo + ". Si hay un error, se corrige con un movimiento inverso.",
    textoBoton: "Registrar el retiro",
  });
  if (!seguro) {
    return;
  }
  const boton = bloquearEnvio(formRetiro, "Guardando...");
  try {
    const { ok, resultado } = await apiPost(API.gestion + "/centros/" + idCentro + "/movimientos", {
      id_tipo_residuo: parseInt(formRetiro.elements.id_tipo_residuo.value, 10), kg: valor,
      motivo: formRetiro.elements.motivo.value, observacion: formRetiro.elements.observacion.value.trim(),
    });
    if (ok) {
      avisoPagina("Retiro registrado.");
      formRetiro.reset();
      paginador.volverAlPrincipio();
      recargarTodo();
    } else {
      avisoPaginaError(resultado.mensaje || "No se pudo registrar el retiro.");
    }
  } catch (e) {
    avisoPaginaError("No se pudo conectar con el servidor. Revisá que esté corriendo.");
  } finally {
    liberarEnvio(boton);
  }
});

cuerpoMovimientos.addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargarMovimientos();
    return;
  }
  const boton = evento.target.closest(".js-corregir");
  if (!boton) {
    return;
  }
  movimientoACorregir = parseInt(boton.dataset.id, 10);
  document.getElementById("texto-correccion").textContent =
    "Se registra un movimiento inverso de: " + boton.dataset.texto + ". El original queda en el historial.";
  formCorreccion.reset();
  limpiarError(formCorreccion.elements.observacion);
  bootstrap.Modal.getOrCreateInstance(document.getElementById("modal-correccion")).show();
});

formCorreccion.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  const campo = formCorreccion.elements.observacion;
  if (campo.value.trim() === "") {
    marcarError(campo, "Contá por qué se corrige.");
    campo.focus();
    return;
  }
  const boton = bloquearEnvio(formCorreccion, "Guardando...");
  try {
    const { ok, resultado } = await apiPost(API.gestion + "/centros/" + idCentro + "/movimientos/" + movimientoACorregir + "/correccion",
                                            { observacion: campo.value.trim() });
    if (ok) {
      cerrarModal("modal-correccion");
      avisoPagina("Corrección registrada.");
      recargarTodo();
    } else {
      marcarError(campo, resultado.mensaje || "No se pudo registrar la corrección.");
    }
  } catch (e) {
    marcarError(campo, "No se pudo conectar con el servidor.");
  } finally {
    liberarEnvio(boton);
  }
});

filtroSentido.addEventListener("change", function () { paginador.volverAlPrincipio(); cargarMovimientos(); });
document.getElementById("form-filtros").addEventListener("submit", function (evento) { evento.preventDefault(); });
document.getElementById("detalle").addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar") && !evento.target.closest("#cuerpo-movimientos")) {
    window.location.reload();
  }
});
document.getElementById("mensaje-pagina").tabIndex = -1;

if (idCentro === null) {
  mostrarFallo({ estado: 404 });
} else {
  recargarTodo();
}
prepararModal("modal-correccion", formCorreccion);
