// ============================================================================
//  SiGeRU 2026 - tabla.js
//
//  Lo que comparten las tres pantallas de listado (usuarios, contenedores y
//  camiones): el buscador, cerrar el modal despues de un alta correcta, y el
//  aviso que se muestra arriba de la tabla.
//
//  Se carga antes de usuarios.js / contenedores.js / camiones.js.
// ============================================================================

// --- Buscador ---------------------------------------------------------------
// Filtra las filas que ya estan en la tabla comparando el texto escrito contra
// el contenido de cada fila. No le pide nada a la API.

document.addEventListener("DOMContentLoaded", function () {
  const buscador = document.getElementById("buscador");
  const cuerpo = document.getElementById("cuerpo-tabla");

  if (!buscador || !cuerpo) {
    return;
  }

  buscador.addEventListener("input", function () {
    const texto = buscador.value.trim().toLowerCase();

    Array.prototype.forEach.call(cuerpo.rows, function (fila) {
      // Las filas de aviso ("cargando", "no hay datos") no se filtran
      if (fila.querySelector(".fila-aviso")) {
        return;
      }
      const coincide = fila.textContent.toLowerCase().indexOf(texto) !== -1;
      fila.style.display = coincide ? "" : "none";
    });
  });
});


// --- Modal ------------------------------------------------------------------
// Cierra el modal de alta usando la instancia que creo Bootstrap.

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


// --- Filas que ocupan toda la tabla ----------------------------------------
// "Cargando...", un error de conexion, o la lista vacia. Se arman aca para que
// las cuatro pantallas muestren lo mismo.

function filaAviso(columnas, texto) {
  return '<tr><td colspan="' + columnas + '" class="fila-aviso">' +
         "<p>" + texto + "</p></td></tr>";
}

function filaVacia(columnas, texto) {
  return '<tr><td colspan="' + columnas + '" class="fila-aviso">' +
         '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
         'stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
         '<path d="M3 8.5 12 4l9 4.5v7L12 20l-9-4.5z"/>' +
         '<path d="M3 8.5 12 13l9-4.5"/><path d="M12 13v7"/>' +
         "</svg>" +
         "<p>" + texto + "</p></td></tr>";
}


// --- Aviso de la pagina -----------------------------------------------------
// Mensaje verde arriba de la tabla, para confirmar un alta despues de que el
// modal ya se cerro. Se borra solo a los 4 segundos.

let temporizadorAviso = null;

function avisoPagina(texto) {
  const elemento = document.getElementById("mensaje-pagina");
  if (!elemento) {
    return;
  }

  elemento.textContent = texto;
  elemento.className = "mensaje ok";

  clearTimeout(temporizadorAviso);
  temporizadorAviso = setTimeout(function () {
    elemento.textContent = "";
  }, 4000);
}
