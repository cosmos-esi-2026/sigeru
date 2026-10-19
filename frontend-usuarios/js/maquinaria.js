Sesion.exigir();

const COLUMNAS = 6;
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const formMaquinaria = document.getElementById("form-maquinaria");
const mensaje = document.getElementById("mensaje");
const modalMaquinaria = document.getElementById("modal-maquinaria");

let idEditando = null;

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
      celdaAccionesMaquina(m) +
      "</tr>";
  }).join("");

  actualizarContador();
}

function celdaAccionesMaquina(m) {
  const id = $entero(m.id_maquinaria);
  const admin = celdaAcciones(m.id_maquinaria, "Editar " + m.codigo, "Dar de baja " + m.codigo)
    .replace(/^<td[^>]*>/, "").replace(/<\/td>$/, "");
  return '<td role="cell" data-etiqueta="Acciones"><div class="acciones-fila">' +
    '<button type="button" class="boton boton-secundario boton-chico js-estado" data-id="' + id + '" data-estado="' + escaparHtml(m.estado) + '">' +
    'Estado<span class="solo-lectores"> de ' + escaparHtml(m.codigo) + "</span></button>" +
    '<button type="button" class="boton boton-secundario boton-chico js-historial" data-id="' + id + '">' +
    'Historial<span class="solo-lectores"> de ' + escaparHtml(m.codigo) + "</span></button>" +
    '<span class="solo-admin acciones-fila">' + admin.replace(/^<div class="acciones-fila">/, "").replace(/<\/div>$/, "") + "</span>" +
    "</div></td>";
}

async function cargarCentros() {
  try {
    const centros = await apiGet(API.gestion + "/centros");
    const activos = centros.datos.filter(function (c) {
      return c.estado === "activo";
    });

    if (activos.length === 0) {
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

const formEstado = document.getElementById("form-estado");
let idEstado = null;

cuerpoTabla.addEventListener("click", async function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargarMaquinaria();
    return;
  }

  const botonEstado = evento.target.closest(".js-estado");
  if (botonEstado) {
    idEstado = botonEstado.dataset.id;
    const codigo = botonEstado.closest("tr").cells[0].textContent;
    document.getElementById("titulo-modal-estado").textContent = "Cambiar el estado de " + codigo;
    document.getElementById("texto-estado").textContent = "Hoy está " + textoEstado(botonEstado.dataset.estado).toLowerCase() + ". El cambio queda en el historial de la máquina, con tu nombre y la nota.";
    formEstado.reset();
    formEstado.estado.value = botonEstado.dataset.estado === "operativa" ? "mantenimiento" : "operativa";
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modal-estado")).show();
    return;
  }

  const botonHistorial = evento.target.closest(".js-historial");
  if (botonHistorial) {
    const codigo = botonHistorial.closest("tr").cells[0].textContent;
    document.getElementById("titulo-modal-historial").textContent = "Historial de " + codigo;
    const zona = document.getElementById("zona-historial");
    zona.innerHTML = '<span class="esqueleto"></span>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modal-historial")).show();
    try {
      const respuesta = await apiGet(API.gestion + "/maquinaria/" + botonHistorial.dataset.id + "/historial");
      zona.innerHTML = '<ul class="lista-historial">' + (respuesta.datos || []).map(function (h) {
        return "<li><strong>" + escaparHtml(textoEstado(h.estado)) + '</strong> <span class="texto-tenue">' + escaparHtml(fechaDelHistorial(h.fecha_hora)) +
               " · " + escaparHtml(h.usuario + " (" + h.rol + ")") + "</span><br>" + escaparHtml(h.observacion || "Sin nota") + "</li>";
      }).join("") + "</ul>";
    } catch (error) {
      zona.innerHTML = "<p>" + escaparHtml(mensajeDeFallo(error)) + "</p>";
    }
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

function fechaDelHistorial(valor) {
  const partes = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/.exec(valor || "");
  return partes ? partes[3] + "/" + partes[2] + "/" + partes[1] + " " + partes[4] + ":" + partes[5] : "-";
}

formEstado.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  const nota = formEstado.observacion;
  if (nota.value.trim() === "") {
    marcarError(nota, "Contá qué se hizo o por qué cambia.");
    nota.focus();
    return;
  }
  const boton = bloquearEnvio(formEstado, "Guardando...");
  try {
    const { ok, resultado } = await apiPost(API.gestion + "/maquinaria/" + idEstado + "/estado",
                                            { estado: formEstado.estado.value, observacion: nota.value.trim() });
    if (ok) {
      cerrarModal("modal-estado");
      avisoPagina("Estado de la máquina actualizado.");
      cargarMaquinaria();
    } else {
      marcarError(nota, resultado.mensaje || "No se pudo cambiar el estado.");
    }
  } catch (e) {
    marcarError(nota, "No se pudo conectar con el servidor.");
  } finally {
    liberarEnvio(boton);
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
prepararModal("modal-estado", formEstado);
activarOrden("tabla-maquinaria");
cargarCentros();
cargarMaquinaria();
