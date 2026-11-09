Sesion.exigir();

const formIncidencia = document.getElementById("form-incidencia");
const mensaje = document.getElementById("mensaje");
const panelFormulario = document.getElementById("panel-formulario");
const panelConfirmacion = document.getElementById("panel-confirmacion");
const cuentaDescripcion = document.getElementById("cuenta-descripcion");

function mostrar(html, tipo) {
  mensaje.innerHTML = html;
  mensaje.className = html ? "mensaje " + tipo : "mensaje";
}

// Con cientos de contenedores no se baja la lista entera: las sugerencias salen de una busqueda de a 10 mientras se escribe,
// y los cercanos usan los puntos publicos del mapa (comprimidos, ~20 KB) que se piden recien al tocar «Usar mi ubicacion».
// La posicion sigue sin salir del telefono: la distancia se calcula aca.
let contenedoresCargados = [];
let promesaContenedores = null;

function cargarPuntosCercanos() {
  if (promesaContenedores === null) {
    promesaContenedores = apiGet(API.gestion + "/publico/mapa").then(function (respuesta) {
      contenedoresCargados = (respuesta.datos.contenedores || []).filter(function (c) { return c.estado !== "retirado"; });
    }).catch(function () {
      promesaContenedores = null;
      contenedoresCargados = [];
    });
  }
  return promesaContenedores;
}

let ultimaBusqueda = 0;
async function sugerirContenedores() {
  const texto = formIncidencia.codigo_contenedor.value.trim();
  const numero = ++ultimaBusqueda;
  if (texto.length < 2) {
    document.getElementById("lista-contenedores").innerHTML = "";
    return;
  }
  try {
    const respuesta = await apiGet(API.gestion + "/contenedores?q=" + encodeURIComponent(texto) + "&por_pagina=10");
    if (numero !== ultimaBusqueda) {
      return;
    }
    document.getElementById("lista-contenedores").innerHTML = (respuesta.datos || [])
      .filter(function (c) { return c.estado !== "retirado"; })
      .map(function (c) {
        return '<option value="' + escaparHtml(c.codigo) + '">' +
               escaparHtml(c.direccion + (c.zona ? " · " + c.zona : "")) + "</option>";
      }).join("");
  } catch (error) {
    document.getElementById("ayuda-contenedor").textContent =
      "No se pudieron traer sugerencias: escribí el código tal como figura en el contenedor.";
  }
}

const btnUbicacion = document.getElementById("btn-ubicacion");
const estadoUbicacion = document.getElementById("estado-ubicacion");
const listaCercanos = document.getElementById("lista-cercanos");
const TEXTO_PRIVACIDAD = estadoUbicacion.textContent;
const CERCA_EN_METROS = 3000;

function ubicacionDelContenedor(c) {
  const latitud = parseFloat(c.latitud);
  const longitud = parseFloat(c.longitud);
  return isNaN(latitud) || isNaN(longitud) ? null : [latitud, longitud];
}

// Distancia en linea recta (haversine): alcanza para ordenar los contenedores de una ciudad.
function distanciaEnMetros(a, b) {
  const rad = Math.PI / 180;
  const dLat = (b[0] - a[0]) * rad;
  const dLon = (b[1] - a[1]) * rad;
  const h = Math.pow(Math.sin(dLat / 2), 2) +
            Math.cos(a[0] * rad) * Math.cos(b[0] * rad) * Math.pow(Math.sin(dLon / 2), 2);
  return 2 * 6371000 * Math.asin(Math.sqrt(h));
}

function textoDistancia(metros) {
  if (metros < 1000) {
    return "a " + Math.max(10, Math.round(metros / 10) * 10) + " m";
  }
  return "a " + (metros / 1000).toFixed(1).replace(".", ",") + " km";
}

function pedirPosicion() {
  return new Promise(function (resolver, rechazar) {
    navigator.geolocation.getCurrentPosition(resolver, rechazar,
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
  });
}

function marcarCercano(codigo) {
  listaCercanos.querySelectorAll(".cercano").forEach(function (boton) {
    boton.setAttribute("aria-pressed", boton.dataset.codigo === codigo ? "true" : "false");
  });
}

function reiniciarCercanos() {
  listaCercanos.innerHTML = "";
  listaCercanos.classList.add("oculto");
  estadoUbicacion.textContent = TEXTO_PRIVACIDAD;
}

function mostrarCercanos(posicion) {
  const origen = [posicion.coords.latitude, posicion.coords.longitude];
  const cercanos = contenedoresCargados.map(function (c) {
    const lugar = ubicacionDelContenedor(c);
    return lugar ? { contenedor: c, metros: distanciaEnMetros(origen, lugar) } : null;
  }).filter(Boolean).sort(function (a, b) { return a.metros - b.metros; }).slice(0, 3);

  if (cercanos.length === 0) {
    estadoUbicacion.textContent = "No se pudo traer la lista de contenedores con su ubicación: escribí el código o buscalo en el mapa.";
    return;
  }
  listaCercanos.innerHTML = cercanos.map(function (item) {
    const c = item.contenedor;
    return '<li><button type="button" class="cercano" aria-pressed="false" data-codigo="' + escaparHtml(c.codigo) + '">' +
      '<span class="cercano-codigo">' + escaparHtml(c.codigo) + "</span>" +
      '<span class="cercano-distancia">' + textoDistancia(item.metros) + "</span>" +
      '<span class="cercano-lugar">' + escaparHtml(c.direccion + (c.zona ? " · " + c.zona : "")) + "</span>" +
      "</button></li>";
  }).join("");
  listaCercanos.classList.remove("oculto");
  marcarCercano(formIncidencia.codigo_contenedor.value.trim().toUpperCase());
  estadoUbicacion.textContent = cercanos[0].metros > CERCA_EN_METROS
    ? "Ninguno está cerca de donde estás (el más próximo queda " + textoDistancia(cercanos[0].metros) +
      "). Si el problema es en otro lugar, escribí el código o buscalo en el mapa."
    : "Tocá el contenedor del problema.";
}

async function buscarCercanos() {
  if (!navigator.geolocation) {
    estadoUbicacion.textContent = "Este navegador no puede saber dónde estás: escribí el código o buscalo en el mapa.";
    return;
  }
  btnUbicacion.disabled = true;
  btnUbicacion.setAttribute("aria-busy", "true");
  estadoUbicacion.textContent = "Buscando tu ubicación...";
  try {
    const posicion = await pedirPosicion();
    await cargarPuntosCercanos();
    mostrarCercanos(posicion);
  } catch (error) {
    estadoUbicacion.textContent = error && error.code === 1
      ? "No pudimos usar tu ubicación porque el permiso está bloqueado. Escribí el código o buscalo en el mapa."
      : "No se pudo saber dónde estás. Escribí el código o buscalo en el mapa.";
  } finally {
    btnUbicacion.disabled = false;
    btnUbicacion.removeAttribute("aria-busy");
  }
}

// La descripcion es opcional (el tipo ya dice que pasa), salvo en «Otro»: ahi es obligatoria. Misma regla que FlujoIncidencia::errorDeNueva.
function descripcionObligatoria() {
  const elegido = formIncidencia.querySelector('input[name="tipo_problema"]:checked');
  return !!elegido && elegido.value === "otro";
}

function actualizarCuenta() {
  const largo = formIncidencia.descripcion.value.trim().length;
  const obligatoria = descripcionObligatoria();
  formIncidencia.descripcion.required = obligatoria;
  document.getElementById("rotulo-descripcion").textContent = obligatoria ? "Descripción *" : "Descripción (opcional)";
  cuentaDescripcion.textContent = largo === 0
    ? (obligatoria ? "Obligatoria en «Otro»: entre 5 y 500 caracteres." : "Opcional: si la escribís, entre 5 y 500 caracteres.")
    : largo + " de 500 caracteres" + (largo < 5 ? " (mínimo 5)." : ".");
}

function campoDelError(texto) {
  const minusculas = (texto || "").toLowerCase();
  if (minusculas.indexOf("contenedor") !== -1) {
    return formIncidencia.codigo_contenedor;
  }
  if (minusculas.indexOf("caracteres") !== -1 || minusculas.indexOf("descripci") !== -1) {
    return formIncidencia.descripcion;
  }
  if (minusculas.indexOf("tipo") !== -1) {
    return formIncidencia.querySelector('input[name="tipo_problema"]');
  }
  return null;
}

async function avisarRepetido(resultado) {
  const existente = resultado.datos || {};
  const numero = escaparHtml(existente.numero || "");
  let esPropio = false;

  try {
    await apiGet(API.gestion + "/incidencias/" + $entero(existente.id_incidencia));
    esPropio = true;
  } catch (error) {
    esPropio = false;
  }

  if (esPropio) {
    mostrar("Ya informaste este problema en ese contenedor y sigue abierto: " +
            '<a href="incidencia.html?id=' + $entero(existente.id_incidencia) + '">ver ' + numero + "</a>.", "error");
  } else {
    mostrar("Otra persona ya informó este mismo problema en ese contenedor (" + numero + ") y sigue abierto, " +
            "así que no hace falta repetirlo.", "error");
  }
}

function mostrarConfirmacion(incidencia) {
  document.getElementById("ficha-confirmacion").innerHTML =
    "<dt>Número</dt><dd><strong>" + escaparHtml(incidencia.numero) + "</strong></dd>" +
    "<dt>Estado</dt><dd>" + marcaDeEstado("estado", incidencia.estado) + "</dd>" +
    "<dt>Contenedor</dt><dd>" + escaparHtml(incidencia.contenedor + " · " + incidencia.direccion) + "</dd>" +
    "<dt>Problema</dt><dd>" + escaparHtml(textoTipoProblema(incidencia.tipo_problema)) + "</dd>" +
    "<dt>Registrado</dt><dd>" + escaparHtml(fechaLegible(incidencia.fecha_reporte)) + "</dd>" +
    "<dt>Responsable</dt><dd>Sin asignar todavía</dd>";
  document.getElementById("enlace-reclamo").href = "incidencia.html?id=" + $entero(incidencia.id_incidencia);
  // A la cuadrilla no le sirve «se va a asignar a una cuadrilla»: el aviso queda para el administrador (hallazgo del 23/10/2026).
  if (Sesion.rol() === "Cuadrilla") {
    document.getElementById("ayuda-confirmacion").textContent = "Guardá el número para seguirlo en «Mis incidencias». El administrador ya lo ve y decide quién lo atiende.";
  }

  panelFormulario.classList.add("oculto");
  panelConfirmacion.classList.remove("oculto");
  panelConfirmacion.focus();
}

formIncidencia.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrar("", "");

  if (!validarFormulario(formIncidencia)) {
    return;
  }

  const boton = bloquearEnvio(formIncidencia, "Enviando...");
  const datos = {
    codigo_contenedor: formIncidencia.codigo_contenedor.value.trim().toUpperCase(),
    tipo_problema:     formIncidencia.tipo_problema.value,
    descripcion:       formIncidencia.descripcion.value.trim(),
    // Vacio = «Según el problema»: no se manda y el servidor aplica FlujoIncidencia::PRIORIDAD_POR_TIPO.
    // Antes siempre viajaba «media» y la regla por tipo nunca corria desde esta pantalla (hallazgo del 23/10/2026).
    prioridad:         formIncidencia.prioridad.value || undefined,
  };

  try {
    const { ok, resultado } = await apiPost(API.gestion + "/incidencias", datos);
    if (ok) {
      mostrarConfirmacion(resultado.datos);
    } else if (resultado.datos && resultado.datos.numero) {
      await avisarRepetido(resultado);
    } else {
      const texto = resultado.mensaje || "No se pudo registrar el reporte.";
      const campo = campoDelError(texto);
      if (campo) {
        marcarError(campo, texto);
        campo.focus();
      } else {
        mostrar(escaparHtml(texto), "error");
      }
    }
  } catch (e) {
    mostrar("No se pudo conectar con el servidor. Revisá que esté corriendo.", "error");
  } finally {
    liberarEnvio(boton);
  }
});

document.getElementById("btn-otro").addEventListener("click", function () {
  formIncidencia.reset();
  limpiarErrores(formIncidencia);
  mostrar("", "");
  actualizarCuenta();
  reiniciarCercanos();
  panelConfirmacion.classList.add("oculto");
  panelFormulario.classList.remove("oculto");
  formIncidencia.codigo_contenedor.focus();
});

formIncidencia.descripcion.addEventListener("input", actualizarCuenta);
btnUbicacion.addEventListener("click", buscarCercanos);
listaCercanos.addEventListener("click", function (evento) {
  const boton = evento.target.closest(".cercano");
  if (!boton) {
    return;
  }
  formIncidencia.codigo_contenedor.value = boton.dataset.codigo;
  limpiarError(formIncidencia.codigo_contenedor);
  marcarCercano(boton.dataset.codigo);
  estadoUbicacion.textContent = "Elegiste " + boton.dataset.codigo + ".";
});
formIncidencia.codigo_contenedor.addEventListener("input", function () {
  marcarCercano(formIncidencia.codigo_contenedor.value.trim().toUpperCase());
});
formIncidencia.querySelectorAll('input[name="tipo_problema"]').forEach(function (opcion) {
  opcion.addEventListener("change", function () {
    formIncidencia.querySelectorAll('input[name="tipo_problema"]').forEach(limpiarError);
    limpiarError(formIncidencia.descripcion);
    actualizarCuenta();
  });
});

const codigoInicial = new URLSearchParams(window.location.search).get("contenedor");
if (codigoInicial && /^[A-Za-z0-9-]{1,20}$/.test(codigoInicial)) {
  formIncidencia.codigo_contenedor.value = codigoInicial.toUpperCase();
}

activarValidacion(formIncidencia);
formIncidencia.codigo_contenedor.addEventListener("input", retrasarBusqueda(sugerirContenedores, 300));

function retrasarBusqueda(funcion, milisegundos) {
  let espera = null;
  return function () {
    clearTimeout(espera);
    espera = setTimeout(funcion, milisegundos);
  };
}
