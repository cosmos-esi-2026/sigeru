Sesion.exigir();

const COLUMNAS = 8;
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formContenedor = document.getElementById("form-contenedor");
const mensaje = document.getElementById("mensaje");
const modalContenedor = document.getElementById("modal-contenedor");

let idEditando = null;

function colorEstado(estado) {
  if (estado === "disponible") return "verde";
  if (estado === "lleno") return "rojo";
  if (estado === "mantenimiento") return "naranja";
  return "gris";
}

const formFiltros = document.getElementById("form-filtros");
const campoBusqueda = document.getElementById("buscar");
const paginador = crearPaginador("paginacion", "contenedores", function () { cargarContenedores(); });
let hayFiltros = false;

function consultaDeLaLista(extra) {
  const q = campoBusqueda.value.trim();
  const base = consultaDeFormulario(formFiltros, ["estado", "zona", "tipo"], extra);
  if (q === "") return base;
  return (base === "" ? "?" : base + "&") + "q=" + encodeURIComponent(q);
}

let prediccionPorId = {};

function coincideConLosFiltros(c) {
  const q = campoBusqueda.value.trim().toLowerCase();
  const zona = formFiltros.zona.selectedOptions[0];
  const texto = [c.codigo, c.direccion, c.zona, c.tipo_residuo].join(" ").toLowerCase();
  return (q === "" || texto.indexOf(q) !== -1) &&
    (formFiltros.estado.value === "" || c.estado === formFiltros.estado.value) &&
    (formFiltros.zona.value === "" || (zona && c.zona === zona.textContent)) &&
    (formFiltros.tipo.value === "" || c.tipo_residuo === formFiltros.tipo.value);
}

async function cargarContenedores() {
  cuerpoTabla.innerHTML = filaEsqueleto(COLUMNAS, 4);
  hayFiltros = consultaDeLaLista({}) !== "" || formFiltros.llena.value !== "";

  try {
    // Con el filtro «se llena pronto» la lista sale de la prediccion (son pocos): se aplican los demas filtros en la pantalla y no se pagina.
    const dentroDe = formFiltros.llena.value;
    if (dentroDe !== "") {
      const { mapa, filas } = await traerPredicciones(dentroDe);
      prediccionPorId = mapa;
      dibujarTabla(filas.filter(coincideConLosFiltros));
      paginador.dibujar(null);
      return;
    }
    prediccionPorId = (await traerPredicciones()).mapa;

    const datos = await apiGet(API.gestion + "/contenedores" + consultaDeLaLista({
      pagina: paginador.estado.pagina, por_pagina: paginador.estado.porPagina,
    }));
    if (paginador.ajustar(datos.meta)) {
      return cargarContenedores();
    }
    dibujarTabla(datos.datos);
    paginador.dibujar(datos.meta);
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(COLUMNAS, mensajeDeFallo(error));
    paginador.dibujar(null);
  }
}

async function exportarContenedores() {
  try {
    const datos = await apiGet(API.gestion + "/contenedores" + consultaDeLaLista({}));
    const filas = [["Código", "Dirección", "Zona", "Tipo de residuo", "Capacidad (L)", "Estado", "Latitud", "Longitud"]];
    datos.datos.forEach(function (c) {
      filas.push([c.codigo, c.direccion, c.zona, c.tipo_residuo, c.capacidad, textoEstado(c.estado), c.latitud, c.longitud]);
    });
    descargarCsv("contenedores_" + fechaParaArchivo() + ".csv", filas);
    avisoPagina("Se exportaron " + datos.datos.length + " contenedores (con los filtros que están activos).");
  } catch (error) {
    avisoPaginaError("No se pudo exportar. " + mensajeDeFallo(error));
  }
}

function dibujarTabla(contenedores) {
  if (!contenedores || contenedores.length === 0) {
    cuerpoTabla.innerHTML = hayFiltros
      ? filaVacia(COLUMNAS, "Ningún contenedor coincide", "Probá con otra búsqueda o limpiá los filtros.")
      : filaVacia(
          COLUMNAS,
          "Todavía no hay contenedores",
          "Cargá el primero con «Nuevo contenedor» para empezar a seguir su estado."
        );
    return;
  }

  cuerpoTabla.innerHTML = contenedores.map(function (c) {
    return '<tr role="row">' +
      celdaEnlace("Código", c.codigo, "contenedor.html?id=" + $entero(c.id_contenedor)) +
      celda("Dirección", c.direccion) +
      celda("Zona", c.zona) +
      celda("Capacidad", c.capacidad ? c.capacidad + " L" : null, c.capacidad) +
      celda("Tipo", c.tipo_residuo) +
      celdaEstado("Estado", c.estado, colorEstado(c.estado)) +
      celda("Se llena en", c.estado === "retirado" ? null : textoDePrediccion(prediccionPorId[c.id_contenedor]),
            prediccionPorId[c.id_contenedor] && prediccionPorId[c.id_contenedor].horas_hasta_90 !== null ? prediccionPorId[c.id_contenedor].horas_hasta_90 : 99999) +
      celdaAcciones(c.id_contenedor, "Editar " + c.codigo, "Retirar " + c.codigo) +
      "</tr>";
  }).join("");
}

async function cargarCatalogos() {
  try {
    const [zonas, tipos] = await Promise.all([
      apiGet(API.gestion + "/zonas"),
      apiGet(API.gestion + "/tipos-residuo"),
    ]);

    formContenedor.id_zona.innerHTML = zonas.datos.map(function (z) {
      return '<option value="' + z.id_zona + '">' + escaparHtml(z.nombre) + "</option>";
    }).join("");

    formContenedor.tipo_residuo.innerHTML = tipos.datos.map(function (t) {
      return '<option value="' + escaparHtml(t.nombre) + '">' + escaparHtml(t.nombre) + "</option>";
    }).join("");

    formFiltros.zona.insertAdjacentHTML("beforeend", zonas.datos.map(function (z) {
      return '<option value="' + z.id_zona + '">' + escaparHtml(z.nombre) + "</option>";
    }).join(""));
    formFiltros.tipo.insertAdjacentHTML("beforeend", tipos.datos.map(function (t) {
      return '<option value="' + escaparHtml(t.nombre) + '">' + escaparHtml(t.nombre) + "</option>";
    }).join(""));
  } catch (error) {
    avisoPaginaError("No se pudieron cargar las zonas y los tipos de residuo. " +
                     mensajeDeFallo(error));
  }
}

function prepararAlta() {
  idEditando = null;
  document.getElementById("titulo-modal-contenedor").textContent = "Nuevo contenedor";
  formContenedor.querySelector('button[type="submit"]').textContent = "Guardar contenedor";
}

function prepararEdicion(c) {
  idEditando = c.id_contenedor;
  document.getElementById("titulo-modal-contenedor").textContent = "Editar contenedor";
  formContenedor.querySelector('button[type="submit"]').textContent = "Guardar cambios";

  formContenedor.codigo.value = c.codigo || "";
  formContenedor.direccion.value = c.direccion || "";
  formContenedor.capacidad.value = c.capacidad || "";
  formContenedor.estado.value = c.estado || "disponible";
  formContenedor.tipo_residuo.value = c.tipo_residuo || "";
  formContenedor.id_zona.value = c.id_zona || "";
}

formContenedor.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrar("", "");

  if (!validarFormulario(formContenedor)) {
    return;
  }

  const boton = bloquearEnvio(formContenedor, "Guardando...");
  const datos = {
    codigo:       formContenedor.codigo.value,
    direccion:    formContenedor.direccion.value,
    capacidad:    formContenedor.capacidad.value,
    tipo_residuo: formContenedor.tipo_residuo.value,
    id_zona:      formContenedor.id_zona.value,
    estado:       formContenedor.estado.value,
  };

  try {
    const { ok, resultado } = idEditando === null
      ? await apiPost(API.gestion + "/contenedores", datos)
      : await apiPut(API.gestion + "/contenedores/" + idEditando, datos);

    if (ok) {
      cerrarModal("modal-contenedor");
      avisoPagina(idEditando === null
        ? "Contenedor creado correctamente."
        : "Contenedor actualizado correctamente.");
      cargarContenedores();
    } else {
      const texto = resultado.mensaje || "No se pudo guardar el contenedor.";
      if (!marcarErrorDeApi(formContenedor, texto)) {
        mostrar(texto, "error");
      }
    }
  } catch (e) {
    mostrar("No se pudo conectar con el servidor. Revisá que esté corriendo.", "error");
  } finally {
    liberarEnvio(boton);
  }
});

cuerpoTabla.addEventListener("click", async function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargarContenedores();
    return;
  }

  const botonEditar = evento.target.closest(".js-editar");
  if (botonEditar) {
    const { datos } = await apiGet(API.gestion + "/contenedores/" + botonEditar.dataset.id);
    prepararEdicion(datos);
    bootstrap.Modal.getOrCreateInstance(modalContenedor).show();
    return;
  }

  const botonBaja = evento.target.closest(".js-baja");
  if (botonBaja) {
    const codigo = botonBaja.closest("tr").cells[0].textContent;

    const seguro = await confirmar({
      titulo: "Retirar el contenedor " + codigo,
      mensaje: "Pasa a estado «retirado» y queda asentado en su historial. No se borra: " +
               "sus vaciados y su historial son parte del registro del servicio.",
      textoBoton: "Retirar",
    });
    if (!seguro) {
      return;
    }

    const { ok, resultado } = await apiDelete(API.gestion + "/contenedores/" + botonBaja.dataset.id);
    if (ok) {
      avisoPagina("Contenedor retirado.");
      cargarContenedores();
    } else {
      avisoPaginaError(resultado.mensaje || "No se pudo retirar el contenedor.");
    }
  }
});

function mostrar(texto, tipo) {
  mensaje.textContent = texto;
  mensaje.className = texto ? "mensaje " + tipo : "mensaje";
}

document.getElementById("btn-recargar").addEventListener("click", cargarContenedores);
document.getElementById("btn-exportar").addEventListener("click", exportarContenedores);
function volverALaPrimera() { paginador.volverAlPrincipio(); cargarContenedores(); }
formFiltros.addEventListener("change", volverALaPrimera);
formFiltros.addEventListener("reset", function () { campoBusqueda.value = ""; setTimeout(volverALaPrimera, 0); });
campoBusqueda.addEventListener("input", retrasar(volverALaPrimera, 350));
document.getElementById("btn-nuevo").addEventListener("click", prepararAlta);

activarValidacion(formContenedor);
prepararModal("modal-contenedor", formContenedor);
activarOrden("tabla-contenedores");
// Las tarjetas y avisos del panel llegan con ?estado=lleno (o mantenimiento, etc.)
const estadoDeLaDireccion = new URLSearchParams(window.location.search).get("estado");
const llenaDeLaDireccion = new URLSearchParams(window.location.search).get("llena");
if (llenaDeLaDireccion && ["24", "48", "168"].indexOf(llenaDeLaDireccion) !== -1) {
  formFiltros.llena.value = llenaDeLaDireccion;
}
if (estadoDeLaDireccion) {
  formFiltros.estado.value = estadoDeLaDireccion;
}
cargarCatalogos();
cargarContenedores();
document.getElementById("explicacion-prediccion").innerHTML = EXPLICACION_PREDICCION;
