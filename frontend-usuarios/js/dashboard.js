// ============================================================================
//  SiGeRU 2026 - dashboard.js
//
//  Calcula las cuatro metricas del panel y arma la tabla de contenedores
//  llenos. No hay endpoints de resumen en las APIs: se piden los listados que
//  ya existen y se cuenta aca, del lado del navegador.
//
//  Los tres pedidos salen en paralelo: son independientes entre si, y en serie
//  el panel tardaba lo que tardan los tres sumados.
// ============================================================================

Sesion.exigir();

const cuerpoLlenos = document.getElementById("cuerpo-llenos");
const COLUMNAS_LLENOS = 4;

function pintarMetrica(id, valor, detalle) {
  const elemento = document.getElementById(id);
  elemento.textContent = valor;
  if (detalle) {
    elemento.title = detalle;
  } else {
    elemento.removeAttribute("title");
  }
}

function cargandoMetrica(id) {
  document.getElementById(id).innerHTML = '<span class="esqueleto esqueleto-metrica"></span>';
}

async function cargarContenedores() {
  try {
    const respuesta = await apiGet(API.gestion + "/contenedores");
    const contenedores = respuesta.datos || [];

    pintarMetrica("metrica-contenedores", contenedores.length);

    const llenos = contenedores.filter(function (c) {
      return c.estado === "lleno";
    });
    pintarMetrica("metrica-llenos", llenos.length);
    dibujarLlenos(llenos);
  } catch (error) {
    pintarMetrica("metrica-contenedores", "—", mensajeDeFallo(error));
    pintarMetrica("metrica-llenos", "—", mensajeDeFallo(error));
    cuerpoLlenos.innerHTML = filaError(COLUMNAS_LLENOS, mensajeDeFallo(error));
  }
}

async function cargarCamiones() {
  try {
    const respuesta = await apiGet(API.gestion + "/camiones");
    const camiones = respuesta.datos || [];

    const operativos = camiones.filter(function (c) {
      return c.estado === "operativo";
    });
    pintarMetrica("metrica-camiones", operativos.length);
  } catch (error) {
    pintarMetrica("metrica-camiones", "—", mensajeDeFallo(error));
  }
}

async function cargarUsuarios() {
  try {
    const respuesta = await apiGet(API.usuarios + "/usuarios");
    pintarMetrica("metrica-usuarios", (respuesta.datos || []).length);
  } catch (error) {
    // Con rol Vecino este dato da 403: no es una falla, es que no corresponde.
    pintarMetrica("metrica-usuarios", "—", mensajeDeFallo(error));
  }
}

function dibujarLlenos(llenos) {
  if (llenos.length === 0) {
    cuerpoLlenos.innerHTML = filaVacia(
      COLUMNAS_LLENOS,
      "Ningún contenedor lleno",
      "Cuando un contenedor pase a estado «lleno» va a aparecer acá."
    );
    return;
  }

  cuerpoLlenos.innerHTML = llenos.map(function (c) {
    return '<tr role="row">' +
      celda("Código", c.codigo) +
      celda("Dirección", c.direccion) +
      celda("Capacidad", c.capacidad ? c.capacidad + " L" : null) +
      celdaEstado("Estado", c.estado, "rojo") +
      "</tr>";
  }).join("");
}

function cargarPanel() {
  ["metrica-contenedores", "metrica-llenos", "metrica-camiones", "metrica-usuarios"]
    .forEach(cargandoMetrica);
  cuerpoLlenos.innerHTML = filaEsqueleto(COLUMNAS_LLENOS, 2);

  cargarContenedores();
  cargarCamiones();
  cargarUsuarios();
}

cuerpoLlenos.addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargarPanel();
  }
});

cargarPanel();
