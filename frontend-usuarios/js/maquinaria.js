// ============================================================================
//  SiGeRU 2026 - maquinaria.js
//  Maquinaria de los centros de acopio: listado, alta, edicion y baja logica.
//
//  Es el modulo que cierra el "CRUD de centros de acopio y maquinaria basica"
//  que pide la letra de Programacion Full Stack para la 2da entrega. El alcance
//  es de inventario, no de mantenimiento: ver Adenda_Alcance_Maquinaria.
//
//  El desplegable de centros se carga DE LA BASE, igual que las zonas en
//  contenedores.js: si se agrega un centro nuevo, el formulario lo ofrece sin
//  tocar codigo.
// ============================================================================

Sesion.exigir();

const COLUMNAS = 6;
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formMaquinaria = document.getElementById("form-maquinaria");
const mensaje = document.getElementById("mensaje");
const modalMaquinaria = document.getElementById("modal-maquinaria");

let idEditando = null;

// La base guarda el tipo como ENUM. "cinta_clasificadora" es correcto para la
// base y feo en pantalla; aca se traduce solo para mostrar.
const TIPOS = {
  compactadora: "Compactadora",
  pala_cargadora: "Pala cargadora",
  cinta_clasificadora: "Cinta de clasificación",
  retroexcavadora: "Retroexcavadora",
  montacargas: "Montacargas",
  otra: "Otra",
};

function colorEstado(estado) {
  if (estado === "operativa") return "verde";
  if (estado === "mantenimiento") return "naranja";
  return "gris";
}

async function cargarMaquinaria() {
  cuerpoTabla.innerHTML = filaEsqueleto(COLUMNAS, 4);

  try {
    const datos = await apiGet(API.gestion + "/maquinaria");
    dibujarTabla(datos.datos);
  } catch (error) {
    cuerpoTabla.innerHTML = filaError(COLUMNAS, mensajeDeFallo(error));
    actualizarContador();
  }
}

function dibujarTabla(maquinas) {
  if (!maquinas || maquinas.length === 0) {
    cuerpoTabla.innerHTML = filaVacia(
      COLUMNAS,
      "Todavía no hay maquinaria registrada",
      "Cargá la primera con «Nueva máquina» para tener el inventario de los centros de acopio."
    );
    actualizarContador();
    return;
  }

  cuerpoTabla.innerHTML = maquinas.map(function (m) {
    const marcaModelo = [m.marca, m.modelo].filter(Boolean).join(" ");
    return '<tr role="row">' +
      celda("Código", m.codigo) +
      celda("Tipo", TIPOS[m.tipo] || m.tipo) +
      celda("Marca y modelo", marcaModelo) +
      celda("Centro de acopio", m.centro) +
      celdaEstado("Estado", m.estado, colorEstado(m.estado)) +
      celdaAcciones(m.id_maquinaria, "Editar " + m.codigo, "Dar de baja " + m.codigo) +
      "</tr>";
  }).join("");

  actualizarContador();
}


// --- Desplegable que sale de la base ----------------------------------------

async function cargarCentros() {
  try {
    const centros = await apiGet(API.gestion + "/centros");
    const activos = centros.datos.filter(function (c) {
      return c.estado === "activo";
    });

    if (activos.length === 0) {
      // Sin centros no se puede dar de alta nada: la clave foranea es NOT NULL.
      // Mejor decirlo antes de que la persona complete el formulario entero.
      avisoPaginaError("No hay centros de acopio activos. Cargá uno antes de " +
                       "registrar maquinaria.");
    }

    formMaquinaria.id_centro.innerHTML = activos.map(function (c) {
      return '<option value="' + c.id_centro + '">' + escaparHtml(c.nombre) + "</option>";
    }).join("");
  } catch (error) {
    avisoPaginaError("No se pudieron cargar los centros de acopio. " +
                     mensajeDeFallo(error));
  }
}


// --- Alta y edicion ---------------------------------------------------------

function prepararAlta() {
  idEditando = null;
  document.getElementById("titulo-modal-maquinaria").textContent = "Nueva máquina";
  formMaquinaria.querySelector('button[type="submit"]').textContent = "Guardar máquina";
}

function prepararEdicion(m) {
  idEditando = m.id_maquinaria;
  document.getElementById("titulo-modal-maquinaria").textContent = "Editar máquina";
  formMaquinaria.querySelector('button[type="submit"]').textContent = "Guardar cambios";

  formMaquinaria.codigo.value = m.codigo || "";
  formMaquinaria.tipo.value = m.tipo || "otra";
  formMaquinaria.marca.value = m.marca || "";
  formMaquinaria.modelo.value = m.modelo || "";
  formMaquinaria.id_centro.value = m.id_centro || "";
  formMaquinaria.estado.value = m.estado || "operativa";
}

formMaquinaria.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrar("", "");

  if (!validarFormulario(formMaquinaria)) {
    return;
  }

  const boton = bloquearEnvio(formMaquinaria, "Guardando...");
  const datos = {
    codigo:    formMaquinaria.codigo.value,
    tipo:      formMaquinaria.tipo.value,
    marca:     formMaquinaria.marca.value,
    modelo:    formMaquinaria.modelo.value,
    id_centro: formMaquinaria.id_centro.value,
    estado:    formMaquinaria.estado.value,
  };

  try {
    const { ok, resultado } = idEditando === null
      ? await apiPost(API.gestion + "/maquinaria", datos)
      : await apiPut(API.gestion + "/maquinaria/" + idEditando, datos);

    if (ok) {
      cerrarModal("modal-maquinaria");
      avisoPagina(idEditando === null
        ? "Maquinaria creada correctamente."
        : "Maquinaria actualizada correctamente.");
      cargarMaquinaria();
    } else {
      const texto = resultado.mensaje || "No se pudo guardar la maquinaria.";
      if (!marcarErrorDeApi(formMaquinaria, texto)) {
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
    cargarMaquinaria();
    return;
  }

  const botonEditar = evento.target.closest(".js-editar");
  if (botonEditar) {
    const { datos } = await apiGet(API.gestion + "/maquinaria/" + botonEditar.dataset.id);
    prepararEdicion(datos);
    bootstrap.Modal.getOrCreateInstance(modalMaquinaria).show();
    return;
  }

  const botonBaja = evento.target.closest(".js-baja");
  if (botonBaja) {
    const codigo = botonBaja.closest("tr").cells[0].textContent;

    const seguro = await confirmar({
      titulo: "Dar de baja " + codigo,
      mensaje: "Pasa a «fuera de servicio» y deja de contar como equipamiento disponible " +
               "del centro. No se borra: el inventario de un organismo público es " +
               "patrimonio y tiene que poder listarse igual.",
      textoBoton: "Dar de baja",
    });
    if (!seguro) {
      return;
    }

    const { ok, resultado } = await apiDelete(API.gestion + "/maquinaria/" + botonBaja.dataset.id);
    if (ok) {
      avisoPagina("Maquinaria dada de baja.");
      cargarMaquinaria();
    } else {
      avisoPaginaError(resultado.mensaje || "No se pudo dar de baja la maquinaria.");
    }
  }
});

function mostrar(texto, tipo) {
  mensaje.textContent = texto;
  mensaje.className = texto ? "mensaje " + tipo : "mensaje";
}

document.getElementById("btn-recargar").addEventListener("click", cargarMaquinaria);
document.getElementById("btn-nuevo").addEventListener("click", prepararAlta);

activarValidacion(formMaquinaria);
prepararModal("modal-maquinaria", formMaquinaria);
activarOrden("tabla-maquinaria");
cargarCentros();
cargarMaquinaria();
