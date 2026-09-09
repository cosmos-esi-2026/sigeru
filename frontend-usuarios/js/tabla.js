// ============================================================================
//  SiGeRU 2026 - tabla.js
//
//  Lo que comparten las tres pantallas de listado (usuarios, contenedores y
//  camiones): el buscador, el contador de resultados, los estados de la tabla
//  (cargando / vacia / error), el orden por columna y el aviso de la pagina.
//
//  Se carga antes de usuarios.js / contenedores.js / camiones.js.
// ============================================================================


// --- Seguridad --------------------------------------------------------------
// Todo dato que venga de la API se escapa antes de entrar al HTML. Los nombres,
// direcciones y matriculas los carga gente desde un formulario: sin esto, un
// valor con etiquetas HTML se ejecutaria en el navegador de quien mire la tabla.

function escaparHtml(valor) {
  if (valor === null || valor === undefined) {
    return "";
  }
  return String(valor)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}


// --- Textos legibles --------------------------------------------------------
// La base guarda los estados como ENUM ("en_ruta", "fuera_servicio"). Eso es
// correcto para la base, pero en pantalla se lee como un dato tecnico. Aca se
// traduce solo para mostrar: lo que se le manda a la API no cambia.

const ETIQUETAS_ESTADO = {
  en_ruta: "En ruta",
  fuera_servicio: "Fuera de servicio",
};

function textoEstado(valor) {
  if (!valor) {
    return "-";
  }
  if (ETIQUETAS_ESTADO[valor]) {
    return ETIQUETAS_ESTADO[valor];
  }
  const texto = String(valor).replace(/_/g, " ");
  return texto.charAt(0).toUpperCase() + texto.slice(1);
}

// Celda de dato: agrega data-etiqueta para que en celular, cuando la fila pasa
// a ser una tarjeta, cada valor siga mostrando de que columna es.
//
// valorOrden es opcional y sirve para cuando lo que se MUESTRA no se puede
// ordenar como texto. El caso concreto: una fecha "07/09/2026" ordenada como
// texto se ordena por dia, no cronologicamente. Pasando ahi el valor original
// (2026-09-07) el orden sale bien sin cambiar lo que ve la persona.
function celda(etiqueta, valor, valorOrden) {
  const orden = valorOrden !== undefined && valorOrden !== null
    ? ' data-orden-valor="' + escaparHtml(valorOrden) + '"'
    : "";

  return '<td role="cell" data-etiqueta="' + escaparHtml(etiqueta) + '"' + orden + ">" +
         escaparHtml(valor === null || valor === undefined || valor === "" ? "-" : valor) +
         "</td>";
}

function celdaEstado(etiqueta, valor, color) {
  return '<td role="cell" data-etiqueta="' + escaparHtml(etiqueta) + '">' +
         '<span class="estado ' + color + '">' + escaparHtml(textoEstado(valor)) +
         "</span></td>";
}


// --- Estados de la tabla ----------------------------------------------------
// Las tres filas ocupan el ancho completo y llevan la clase .fila-estado, que
// el CSS usa para no convertirlas en tarjeta cuando la pantalla es chica.

// Cargando: se dibujan filas del alto real que van a tener los datos, asi la
// pagina no salta cuando responde la API.
function filaEsqueleto(columnas, filas) {
  let html = "";
  const cantidad = filas || 3;

  for (let f = 0; f < cantidad; f++) {
    html += '<tr class="fila-esqueleto" aria-hidden="true">';
    for (let c = 0; c < columnas; c++) {
      const corto = c === 0 ? " esqueleto-corto" : "";
      html += '<td><span class="esqueleto' + corto + '"></span></td>';
    }
    html += "</tr>";
  }
  return html;
}

function filaVacia(columnas, titulo, detalle) {
  return '<tr class="fila-estado"><td colspan="' + columnas + '">' +
         '<div class="estado-bloque">' +
         '<svg class="icono-grande" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
         'stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
         '<path d="M3 8.5 12 4l9 4.5v7L12 20l-9-4.5z"/>' +
         '<path d="M3 8.5 12 13l9-4.5"/><path d="M12 13v7"/>' +
         "</svg>" +
         "<h3>" + escaparHtml(titulo) + "</h3>" +
         (detalle ? "<p>" + escaparHtml(detalle) + "</p>" : "") +
         "</div></td></tr>";
}

// Error: ademas de avisar, ofrece la salida. Un mensaje que solo informa deja
// al usuario sin nada que hacer.
function filaError(columnas, detalle) {
  return '<tr class="fila-estado"><td colspan="' + columnas + '">' +
         '<div class="estado-bloque es-error">' +
         '<svg class="icono-grande" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
         'stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
         '<path d="M12 3l9 16H3z"/><path d="M12 9v5M12 17h.01"/>' +
         "</svg>" +
         "<h3>No se pudieron cargar los datos</h3>" +
         "<p>" + escaparHtml(detalle) + "</p>" +
         '<button type="button" class="boton boton-secundario boton-chico js-reintentar">' +
         'Reintentar</button>' +
         "</div></td></tr>";
}


// Traduce el fallo a algo que la persona pueda accionar. Antes cualquier error
// decia "no se pudo conectar con la API", incluso cuando el servidor habia
// contestado perfecto que no tenias permiso.
function mensajeDeFallo(error) {
  if (error && error.estado === 401) {
    return "Tu sesión venció. Cerrá sesión y volvé a entrar.";
  }
  if (error && error.estado === 403) {
    return "Tu rol no tiene permiso para ver esta información.";
  }
  if (error && error.estado) {
    return "El servidor respondió con un error (código " + error.estado + ").";
  }
  return "No se pudo conectar con el servidor. Revisá que esté corriendo.";
}


// --- Buscador y contador ----------------------------------------------------

function filasDeDatos(cuerpo) {
  return Array.prototype.filter.call(cuerpo.rows, function (fila) {
    return !fila.classList.contains("fila-estado") &&
           !fila.classList.contains("fila-esqueleto");
  });
}

// "3 de 3 usuarios" / "1 de 5 contenedores": sin esto no se distingue una lista
// corta de una lista filtrada.
function actualizarContador() {
  const contador = document.getElementById("contador-resultados");
  const cuerpo = document.getElementById("cuerpo-tabla");
  if (!contador || !cuerpo) {
    return;
  }

  const filas = filasDeDatos(cuerpo);
  const visibles = filas.filter(function (fila) {
    return fila.style.display !== "none";
  });
  const sustantivo = contador.dataset.sustantivo || "resultados";

  if (filas.length === 0) {
    contador.textContent = "";
    return;
  }
  contador.innerHTML = "Mostrando <strong>" + visibles.length + "</strong> de " +
                       filas.length + " " + escaparHtml(sustantivo) + ".";
}

document.addEventListener("DOMContentLoaded", function () {
  const buscador = document.getElementById("buscador");
  const cuerpo = document.getElementById("cuerpo-tabla");

  if (!buscador || !cuerpo) {
    return;
  }

  buscador.addEventListener("input", function () {
    const texto = buscador.value.trim().toLowerCase();

    filasDeDatos(cuerpo).forEach(function (fila) {
      const coincide = fila.textContent.toLowerCase().indexOf(texto) !== -1;
      fila.style.display = coincide ? "" : "none";
    });

    actualizarContador();
  });
});


// --- Orden por columna ------------------------------------------------------
// Se activa en los <th data-orden>. El th guarda en aria-sort la direccion
// actual, que es lo que anuncia un lector de pantalla.

function activarOrden(idTabla) {
  const tabla = document.getElementById(idTabla);
  if (!tabla) {
    return;
  }

  const encabezados = tabla.querySelectorAll("th[data-orden]");

  encabezados.forEach(function (th, indice) {
    const columna = Array.prototype.indexOf.call(th.parentNode.children, th);
    const etiqueta = th.textContent.trim();

    th.innerHTML = '<button type="button" class="th-orden">' +
                   escaparHtml(etiqueta) + "</button>";

    th.querySelector(".th-orden").addEventListener("click", function () {
      const ascendente = th.getAttribute("aria-sort") !== "ascending";

      encabezados.forEach(function (otro) { otro.removeAttribute("aria-sort"); });
      th.setAttribute("aria-sort", ascendente ? "ascending" : "descending");

      ordenarPor(tabla, columna, ascendente);
    });
  });
}

function ordenarPor(tabla, columna, ascendente) {
  const cuerpo = tabla.querySelector("tbody");
  const filas = filasDeDatos(cuerpo);
  if (filas.length === 0) {
    return;
  }

  filas.sort(function (a, b) {
    // Si la celda trae un valor de orden propio se usa ese (ver celda()).
    const celdaA = a.cells[columna];
    const celdaB = b.cells[columna];
    const textoA = (celdaA.dataset.ordenValor || celdaA.textContent).trim();
    const textoB = (celdaB.dataset.ordenValor || celdaB.textContent).trim();

    // Si las dos celdas son numeros, se ordena como numero y no como texto
    // (si no, 10 quedaria antes que 9).
    const numeroA = parseFloat(textoA);
    const numeroB = parseFloat(textoB);
    const sonNumeros = !isNaN(numeroA) && !isNaN(numeroB) &&
                       String(numeroA) === textoA && String(numeroB) === textoB;

    const comparacion = sonNumeros
      ? numeroA - numeroB
      : textoA.localeCompare(textoB, "es", { sensitivity: "base" });

    return ascendente ? comparacion : -comparacion;
  });

  filas.forEach(function (fila) { cuerpo.appendChild(fila); });
}


// --- Modal ------------------------------------------------------------------

function cerrarModal(id) {
  const elemento = document.getElementById(id);
  if (!elemento) {
    return;
  }
  const instancia = bootstrap.Modal.getInstance(elemento);
  if (instancia) {
    instancia.hide();
  }
}

// Bootstrap ya atrapa el foco y cierra con Escape. Lo que falta es que el foco
// caiga en el primer campo al abrir (no en el borde del dialogo) y que el
// formulario quede limpio al cerrar, sin errores viejos de un intento anterior.
function prepararModal(idModal, formulario) {
  const modal = document.getElementById(idModal);
  if (!modal || !formulario) {
    return;
  }

  modal.addEventListener("shown.bs.modal", function () {
    const primero = formulario.querySelector("input, select, textarea");
    if (primero) {
      primero.focus();
    }
  });

  modal.addEventListener("hidden.bs.modal", function () {
    formulario.reset();
    limpiarErrores(formulario);
    const aviso = modal.querySelector(".mensaje");
    if (aviso) {
      aviso.textContent = "";
      aviso.className = "mensaje";
    }
  });
}


// --- Botones de accion de cada fila -----------------------------------------
// Van en la ultima columna. Son botones de icono, con aria-label propio porque
// el icono solo no dice nada a un lector de pantalla.

function celdaAcciones(id, etiquetaEditar, etiquetaBaja) {
    return '<td role="cell" data-etiqueta="Acciones">' +
        '<div class="acciones-fila">' +
        '<button type="button" class="boton boton-icono js-editar" data-id="' + $entero(id) + '" ' +
        'aria-label="' + escaparHtml(etiquetaEditar) + '" title="' + escaparHtml(etiquetaEditar) + '">' +
        '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" ' +
        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="M4 20h4l10-10a2.8 2.8 0 0 0-4-4L4 16z"/><path d="M13.5 6.5l4 4"/></svg>' +
        "</button>" +
        '<button type="button" class="boton boton-icono js-baja" data-id="' + $entero(id) + '" ' +
        'aria-label="' + escaparHtml(etiquetaBaja) + '" title="' + escaparHtml(etiquetaBaja) + '">' +
        '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" ' +
        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="M4 7h16"/><path d="M9.5 7V4.5h5V7"/>' +
        '<path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/></svg>' +
        "</button>" +
        "</div></td>";
}

/** Los ids salen de la API, pero igual se fuerzan a entero antes de ir al HTML. */
function $entero(valor) {
    return String(parseInt(valor, 10) || 0);
}


// --- Confirmacion de acciones destructivas ----------------------------------
// Una baja no se ejecuta de un click. Se usa un modal propio y no el confirm()
// del navegador, que no se puede estilar, bloquea toda la pagina y se ve como
// un error del sistema y no como una decision del usuario.

function confirmar(opciones) {
    return new Promise(function (resolver) {
        let modal = document.getElementById("modal-confirmar");

        if (!modal) {
            modal = document.createElement("div");
            modal.className = "modal fade";
            modal.id = "modal-confirmar";
            modal.tabIndex = -1;
            modal.setAttribute("aria-labelledby", "titulo-modal-confirmar");
            modal.innerHTML =
                '<div class="modal-dialog modal-dialog-centered">' +
                '<div class="modal-content">' +
                '<div class="modal-header">' +
                '<h2 class="modal-title" id="titulo-modal-confirmar"></h2>' +
                '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>' +
                "</div>" +
                '<div class="modal-body"><p class="texto-confirmar"></p></div>' +
                '<div class="modal-footer">' +
                '<button type="button" class="boton boton-secundario" data-bs-dismiss="modal">Cancelar</button>' +
                '<button type="button" class="boton boton-peligro js-confirmar"></button>' +
                "</div></div></div>";
            document.body.appendChild(modal);
        }

        modal.querySelector(".modal-title").textContent = opciones.titulo;
        modal.querySelector(".texto-confirmar").textContent = opciones.mensaje;

        // Se reemplaza el boton para no acumular listeners de llamadas anteriores.
        const anterior = modal.querySelector(".js-confirmar");
        const boton = anterior.cloneNode(true);
        boton.textContent = opciones.textoBoton || "Confirmar";
        anterior.replaceWith(boton);

        const instancia = bootstrap.Modal.getOrCreateInstance(modal);
        let confirmado = false;

        boton.addEventListener("click", function () {
            confirmado = true;
            instancia.hide();
        });
        modal.addEventListener("hidden.bs.modal", function alCerrar() {
            modal.removeEventListener("hidden.bs.modal", alCerrar);
            resolver(confirmado);
        });

        instancia.show();
    });
}


// --- Aviso de la pagina -----------------------------------------------------
// Mensaje verde arriba de la tabla, para confirmar un alta despues de que el
// modal ya se cerro. Se borra solo a los 5 segundos.

let temporizadorAviso = null;

function avisoPagina(texto) {
  const elemento = document.getElementById("mensaje-pagina");
  if (!elemento) {
    return;
  }

  elemento.innerHTML =
    '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
    'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M20 6 9 17l-5-5"/></svg>' + escaparHtml(texto);
  elemento.className = "mensaje ok";

  clearTimeout(temporizadorAviso);
  temporizadorAviso = setTimeout(function () {
    elemento.textContent = "";
    elemento.className = "mensaje";
  }, 5000);
}

/**
 * Igual que avisoPagina pero para lo que salio mal. Este NO se borra solo: un
 * error que desaparece antes de que lo leas es peor que no mostrarlo.
 */
function avisoPaginaError(texto) {
  const elemento = document.getElementById("mensaje-pagina");
  if (!elemento) {
    return;
  }

  clearTimeout(temporizadorAviso);
  elemento.innerHTML =
    '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
    'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<circle cx="12" cy="12" r="9"/><path d="M12 8v4.5M12 16h.01"/></svg>' +
    escaparHtml(texto);
  elemento.className = "mensaje error";
}
