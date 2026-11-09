// Ayudas para las listas que filtran y paginan en el servidor (parte 9).

function retrasar(funcion, milisegundos) {
  let temporizador = null;
  return function () {
    const argumentos = arguments;
    clearTimeout(temporizador);
    temporizador = setTimeout(function () { funcion.apply(null, argumentos); }, milisegundos);
  };
}

// Arma "?a=1&b=2" con los campos de un formulario que tengan valor, mas lo extra.
function consultaDeFormulario(formulario, campos, extra) {
  const partes = [];
  campos.forEach(function (campo) {
    const control = formulario.elements[campo];
    if (control && control.value.trim() !== "") {
      partes.push(campo + "=" + encodeURIComponent(control.value.trim()));
    }
  });
  Object.keys(extra || {}).forEach(function (clave) {
    partes.push(clave + "=" + encodeURIComponent(extra[clave]));
  });
  return partes.length ? "?" + partes.join("&") : "";
}

// Controles de pagina dentro de <nav id=...>. alCambiar(pagina, porPagina) vuelve a pedir la lista.
function crearPaginador(idNav, sustantivo, alCambiar) {
  const nav = document.getElementById(idNav);
  const estado = { pagina: 1, porPagina: 10 };

  function dibujar(meta) {
    if (!meta || meta.total === 0) {
      nav.innerHTML = "";
      return;
    }
    nav.innerHTML =
      '<p class="paginacion-texto" aria-live="polite">Mostrando <strong>' + meta.desde + "–" + meta.hasta +
      "</strong> de " + meta.total + " " + escaparHtml(sustantivo) + ".</p>" +
      '<label class="paginacion-tamano">Por página <select class="js-por-pagina">' +
      [10, 25, 50].map(function (n) {
        return '<option value="' + n + '"' + (n === meta.por_pagina ? " selected" : "") + ">" + n + "</option>";
      }).join("") + "</select></label>" +
      '<div class="paginacion-botones">' +
      '<button type="button" class="boton boton-secundario boton-chico js-anterior"' + (meta.pagina <= 1 ? " disabled" : "") + ">Anterior</button>" +
      '<span class="paginacion-pagina">Página ' + meta.pagina + " de " + meta.paginas + "</span>" +
      '<button type="button" class="boton boton-secundario boton-chico js-siguiente"' + (meta.pagina >= meta.paginas ? " disabled" : "") + ">Siguiente</button>" +
      "</div>";
  }

  nav.addEventListener("click", function (evento) {
    if (evento.target.closest(".js-anterior") && estado.pagina > 1) {
      estado.pagina -= 1;
      alCambiar(estado.pagina, estado.porPagina);
    } else if (evento.target.closest(".js-siguiente")) {
      estado.pagina += 1;
      alCambiar(estado.pagina, estado.porPagina);
    }
  });
  nav.addEventListener("change", function (evento) {
    if (evento.target.classList.contains("js-por-pagina")) {
      estado.porPagina = parseInt(evento.target.value, 10);
      estado.pagina = 1;
      alCambiar(estado.pagina, estado.porPagina);
    }
  });

  return {
    estado: estado,
    dibujar: dibujar,
    volverAlPrincipio: function () { estado.pagina = 1; },
    // Si se borro el ultimo registro de la ultima pagina, pedir la anterior.
    ajustar: function (meta) {
      if (meta && meta.total > 0 && meta.desde === 0 && estado.pagina > 1) {
        estado.pagina = meta.paginas;
        return true;
      }
      return false;
    },
  };
}
