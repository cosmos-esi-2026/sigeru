// ============================================================================
//  SiGeRU 2026 - contenedores.js
//  Listado, alta, edicion y baja logica de contenedores.
//
//  Los desplegables de zona y tipo de residuo se cargan DE LA BASE, no estan
//  escritos en el HTML: si alguien agrega una zona nueva, el formulario la
//  ofrece sin tocar codigo.
// ============================================================================

Sesion.exigir();

const COLUMNAS = 7;
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

async function cargarContenedores() {
  cuerpoTabla.innerHTML = filaEsqueleto(COLUMNAS, 4);

  try {
    const datos = await apiGet(API.gestion + "/contenedores");
    dibujarTabla(datos.datos);
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(COLUMNAS, mensajeDeFallo(error));
    actualizarContador();
  }
}

function dibujarTabla(contenedores) {
  if (!contenedores || contenedores.length === 0) {
    cuerpoTabla.innerHTML = filaVacia(
      COLUMNAS,
      "Todavía no hay contenedores",
      "Cargá el primero con «Nuevo contenedor» para empezar a seguir su estado."
    );
    actualizarContador();
    return;
  }

  cuerpoTabla.innerHTML = contenedores.map(function (c) {
    return '<tr role="row">' +
      celda("Código", c.codigo) +
      celda("Dirección", c.direccion) +
      celda("Zona", c.zona) +
      // El valor de orden va sin la unidad: "1100 L" y "900 L" como texto se
      // ordenarian mal (el "1" antes que el "9").
      celda("Capacidad", c.capacidad ? c.capacidad + " L" : null, c.capacidad) +
      celda("Tipo", c.tipo_residuo) +
      celdaEstado("Estado", c.estado, colorEstado(c.estado)) +
      celdaAcciones(c.id_contenedor, "Editar " + c.codigo, "Retirar " + c.codigo) +
      "</tr>";
  }).join("");

  actualizarContador();
}


// --- Desplegables que salen de la base --------------------------------------

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
  } catch (error) {
    // Si fallan los catalogos el alta no puede funcionar bien: mejor decirlo.
    avisoPaginaError("No se pudieron cargar las zonas y los tipos de residuo. " +
                     mensajeDeFallo(error));
  }
}


// --- Alta y edicion ---------------------------------------------------------

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


// --- Acciones de cada fila --------------------------------------------------

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
document.getElementById("btn-nuevo").addEventListener("click", prepararAlta);

activarValidacion(formContenedor);
prepararModal("modal-contenedor", formContenedor);
activarOrden("tabla-contenedores");
cargarCatalogos();
cargarContenedores();
