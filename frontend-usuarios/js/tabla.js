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

function celda(etiqueta, valor, valorOrden) {
  const orden = valorOrden !== undefined && valorOrden !== null
    ? ' data-orden-valor="' + escaparHtml(valorOrden) + '"'
    : "";

  return '<td role="cell" data-etiqueta="' + escaparHtml(etiqueta) + '"' + orden + ">" +
         escaparHtml(valor === null || valor === undefined || valor === "" ? "-" : valor) +
         "</td>";
}

function celdaEnlace(etiqueta, texto, destino) {
  return '<td role="cell" data-etiqueta="' + escaparHtml(etiqueta) + '">' +
         '<a href="' + escaparHtml(destino) + '">' + escaparHtml(texto) + "</a></td>";
}

function celdaEstado(etiqueta, valor, color) {
  return '<td role="cell" data-etiqueta="' + escaparHtml(etiqueta) + '">' +
         '<span class="estado ' + color + '">' + escaparHtml(textoEstado(valor)) +
         "</span></td>";
}

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

function mensajeDeFallo(error) {
  if (error && error.estado === 401) {
    return "Tu sesión venció. Cerrá sesión y volvé a entrar.";
  }
  if (error && error.estado === 403) {
    return error.mensaje || "Tu rol no tiene permiso para ver esta información.";
  }
  if (error && error.estado) {
    return "El servidor respondió con un error (código " + error.estado + ").";
  }
  return "No se pudo conectar con el servidor. Revisá que esté corriendo.";
}

function filasDeDatos(cuerpo) {
  return Array.prototype.filter.call(cuerpo.rows, function (fila) {
    return !fila.classList.contains("fila-estado") &&
           !fila.classList.contains("fila-esqueleto");
  });
}

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

function activarOrden(idTabla) {
  const tabla = document.getElementById(idTabla);
  if (!tabla) {
    return;
  }

  const encabezados = tabla.querySelectorAll("th[data-orden]");

  encabezados.forEach(function (th) {
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
    const celdaA = a.cells[columna];
    const celdaB = b.cells[columna];
    const textoA = (celdaA.dataset.ordenValor || celdaA.textContent).trim();
    const textoB = (celdaB.dataset.ordenValor || celdaB.textContent).trim();

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

function celdaAcciones(id, etiquetaEditar, etiquetaBaja, editaOperario) {
  const claseCelda = editaOperario ? "" : ' class="solo-admin"';
  const claseBaja = editaOperario ? " solo-admin" : "";

  return '<td role="cell" data-etiqueta="Acciones"' + claseCelda + ">" +
    '<div class="acciones-fila">' +
    '<button type="button" class="boton boton-icono js-editar" data-id="' + $entero(id) + '" ' +
    'aria-label="' + escaparHtml(etiquetaEditar) + '" title="' + escaparHtml(etiquetaEditar) + '">' +
    '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" ' +
    'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M4 20h4l10-10a2.8 2.8 0 0 0-4-4L4 16z"/><path d="M13.5 6.5l4 4"/></svg>' +
    "</button>" +
    '<button type="button" class="boton boton-icono js-baja' + claseBaja + '" data-id="' + $entero(id) + '" ' +
    'aria-label="' + escaparHtml(etiquetaBaja) + '" title="' + escaparHtml(etiquetaBaja) + '">' +
    '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" ' +
    'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M4 7h16"/><path d="M9.5 7V4.5h5V7"/>' +
    '<path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/></svg>' +
    "</button>" +
    "</div></td>";
}

function $entero(valor) {
  return String(parseInt(valor, 10) || 0);
}

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

    const anterior = modal.querySelector(".js-confirmar");
    const boton = anterior.cloneNode(true);
    boton.textContent = opciones.textoBoton || "Confirmar";
    // Rojo solo para lo que destruye o da de baja; una confirmacion positiva usa el boton principal.
    boton.className = "boton " + (opciones.peligro === false ? "boton-primario" : "boton-peligro") + " js-confirmar";
    anterior.replaceWith(boton);

    const instancia = bootstrap.Modal.getOrCreateInstance(modal);
    let confirmado = false;

    boton.addEventListener("click", function () {
      confirmado = true;
      instancia.hide();
    });
    // El foco va a "Cancelar": es lo seguro si alguien aprieta Enter sin leer.
    modal.addEventListener("shown.bs.modal", function alMostrar() {
      modal.removeEventListener("shown.bs.modal", alMostrar);
      const cancelar = modal.querySelector(".modal-footer .boton-secundario");
      if (cancelar) {
        cancelar.focus();
      }
    });
    modal.addEventListener("hidden.bs.modal", function alCerrar() {
      modal.removeEventListener("hidden.bs.modal", alCerrar);
      resolver(confirmado);
    });

    instancia.show();
  });
}

let temporizadorAviso = null;

function avisoPagina(texto) {
  const elemento = document.getElementById("mensaje-pagina");
  if (!elemento) {
    return;
  }

  // avisoPagina("") sirve para borrar el aviso: sin texto no se dibuja el recuadro verde con el tilde solo.
  if (!texto) {
    clearTimeout(temporizadorAviso);
    elemento.textContent = "";
    elemento.className = "mensaje";
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

// ---- Mejoras comunes de las listas ----

// Tocar cualquier parte de la fila abre su detalle. El enlace de la fila sigue siendo el camino con teclado.
document.addEventListener("click", function (evento) {
  const fila = evento.target.closest(".tabla tbody tr");
  if (!fila || evento.target.closest("a, button, input, select, textarea, label, summary")) {
    return;
  }
  // El mapa tiene su propia accion principal en cada fila («Ver en el mapa»): no se le agrega otra.
  if (fila.closest("table[data-sin-clic-fila]")) {
    return;
  }
  const enlace = fila.querySelector("td a[href]");
  if (!enlace || String(window.getSelection()).length > 0) {
    return;
  }
  if (evento.ctrlKey || evento.metaKey) {
    window.open(enlace.href, "_blank", "noopener");
  } else {
    window.location.href = enlace.href;
  }
});

function etiquetaDeFiltro(control) {
  const etiqueta = control.closest("label");
  let texto = "";
  if (etiqueta) {
    etiqueta.childNodes.forEach(function (nodo) {
      if (nodo.nodeType === 3) {
        texto += nodo.textContent;
      }
    });
  }
  return texto.trim() || control.getAttribute("aria-label") || control.name;
}

function valorDeFiltro(control) {
  if (control.tagName === "SELECT") {
    return control.options[control.selectedIndex].text;
  }
  const fecha = /^(\d{4})-(\d{2})-(\d{2})$/.exec(control.value);
  return fecha ? fecha[3] + "/" + fecha[2] + "/" + fecha[1] : control.value;
}

function opcionVacia(selector) {
  return Array.prototype.findIndex.call(selector.options, function (o) { return o.value === ""; });
}

// Muestra los filtros activos de un formulario como botones que se pueden quitar de a uno.
function dibujarChipsDeFiltros(formulario, zona) {
  const activos = Array.prototype.filter.call(formulario.elements, function (c) {
    if (!c.name || c.value === "" || ["submit", "reset", "button"].indexOf(c.type) !== -1) {
      return false;
    }
    return c.tagName !== "SELECT" || opcionVacia(c) !== -1;
  });
  zona.innerHTML = activos.map(function (c) {
    const texto = etiquetaDeFiltro(c) + ": " + valorDeFiltro(c);
    return '<button type="button" class="chip-filtro" data-campo="' + escaparHtml(c.name) + '" ' +
           'aria-label="Quitar el filtro ' + escaparHtml(texto) + '">' + escaparHtml(texto) + " ×</button>";
  }).join("");
}

document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll("form[data-chips]").forEach(function (formulario) {
    const zona = document.createElement("div");
    zona.className = "chips-filtros";
    zona.setAttribute("role", "group");
    zona.setAttribute("aria-label", "Filtros activos");
    formulario.insertAdjacentElement("afterend", zona);

    const refrescar = function () { dibujarChipsDeFiltros(formulario, zona); };
    formulario.addEventListener("change", refrescar);
    formulario.addEventListener("reset", function () { setTimeout(refrescar, 0); });
    // Las listas de zonas y tipos se cargan despues: cuando llegan, el valor de la direccion ya puede estar puesto.
    new MutationObserver(refrescar).observe(formulario, { childList: true, subtree: true });

    zona.addEventListener("click", function (evento) {
      const chip = evento.target.closest(".chip-filtro");
      if (!chip) {
        return;
      }
      const control = formulario.elements[chip.dataset.campo];
      if (control.tagName === "SELECT") {
        control.selectedIndex = opcionVacia(control);
      } else {
        control.value = "";
      }
      control.dispatchEvent(new Event("change", { bubbles: true }));
      control.focus();
    });
    refrescar();
  });
});
