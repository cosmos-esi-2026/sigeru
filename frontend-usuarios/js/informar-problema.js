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

async function cargarSugerencias() {
  try {
    const respuesta = await apiGet(API.gestion + "/contenedores");
    document.getElementById("lista-contenedores").innerHTML = (respuesta.datos || [])
      .filter(function (c) { return c.estado !== "retirado"; })
      .map(function (c) {
        return '<option value="' + escaparHtml(c.codigo) + '">' +
               escaparHtml(c.direccion + (c.zona ? " · " + c.zona : "")) + "</option>";
      }).join("");
  } catch (error) {
    document.getElementById("ayuda-contenedor").textContent =
      "No se pudo traer la lista de sugerencias: escribí el código tal como figura en el contenedor.";
  }
}

const TIPOS_DE_FOTO = ["image/jpeg", "image/png", "image/webp"];
const TAMANO_MAXIMO_FOTO = 2 * 1024 * 1024;
const vistaFoto = document.getElementById("vista-foto");
let direccionDeVista = null;

function quitarFoto() {
  formIncidencia.foto.value = "";
  vistaFoto.classList.add("oculto");
  if (direccionDeVista) {
    URL.revokeObjectURL(direccionDeVista);
    direccionDeVista = null;
  }
  limpiarError(formIncidencia.foto);
}

// La revision del navegador es solo una ayuda: el servidor vuelve a comprobar el contenido de la foto.
function elegirFoto() {
  const archivo = formIncidencia.foto.files[0];
  if (!archivo) {
    quitarFoto();
    return;
  }
  if (TIPOS_DE_FOTO.indexOf(archivo.type) === -1) {
    quitarFoto();
    marcarError(formIncidencia.foto, "La foto tiene que ser JPG, PNG o WebP.");
    return;
  }
  if (archivo.size > TAMANO_MAXIMO_FOTO) {
    quitarFoto();
    marcarError(formIncidencia.foto, "La foto pesa más de 2 MB.");
    return;
  }
  limpiarError(formIncidencia.foto);
  if (direccionDeVista) {
    URL.revokeObjectURL(direccionDeVista);
  }
  direccionDeVista = URL.createObjectURL(archivo);
  document.getElementById("vista-foto-imagen").src = direccionDeVista;
  vistaFoto.classList.remove("oculto");
}

// Devuelve un texto para la confirmacion: se informo el problema aunque la foto falle.
async function enviarFoto(idIncidencia) {
  const archivo = formIncidencia.foto.files[0];
  if (!archivo) {
    return "Sin foto";
  }
  const datos = new FormData();
  datos.append("foto", archivo);
  try {
    const respuesta = await fetch(API.gestion + "/incidencias/" + $entero(idIncidencia) + "/foto", {
      method: "POST", body: datos, credentials: "same-origin",
    });
    const resultado = await respuesta.json().catch(function () { return {}; });
    return respuesta.ok
      ? "Adjuntada"
      : "No se pudo adjuntar: " + (resultado.mensaje || "error " + respuesta.status) + " El reporte quedó registrado igual.";
  } catch (error) {
    return "No se pudo adjuntar por un problema de conexión. El reporte quedó registrado igual.";
  }
}

function actualizarCuenta() {
  const largo = formIncidencia.descripcion.value.trim().length;
  cuentaDescripcion.textContent = largo === 0
    ? "Entre 5 y 500 caracteres."
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
    return formIncidencia.tipo_problema;
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

function mostrarConfirmacion(incidencia, textoFoto) {
  document.getElementById("ficha-confirmacion").innerHTML =
    "<dt>Número</dt><dd><strong>" + escaparHtml(incidencia.numero) + "</strong></dd>" +
    "<dt>Estado</dt><dd>" + marcaDeEstado("estado", incidencia.estado) + "</dd>" +
    "<dt>Contenedor</dt><dd>" + escaparHtml(incidencia.contenedor + " · " + incidencia.direccion) + "</dd>" +
    "<dt>Problema</dt><dd>" + escaparHtml(textoTipoProblema(incidencia.tipo_problema)) + "</dd>" +
    "<dt>Registrado</dt><dd>" + escaparHtml(fechaLegible(incidencia.fecha_reporte)) + "</dd>" +
    "<dt>Foto</dt><dd>" + escaparHtml(textoFoto || "Sin foto") + "</dd>" +
    "<dt>Responsable</dt><dd>Sin asignar todavía</dd>";
  document.getElementById("enlace-reclamo").href = "incidencia.html?id=" + $entero(incidencia.id_incidencia);

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
    prioridad:         formIncidencia.prioridad.value,
  };

  try {
    const { ok, resultado } = await apiPost(API.gestion + "/incidencias", datos);
    if (ok) {
      const textoFoto = await enviarFoto(resultado.datos.id_incidencia);
      mostrarConfirmacion(resultado.datos, textoFoto);
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
  quitarFoto();
  limpiarErrores(formIncidencia);
  mostrar("", "");
  actualizarCuenta();
  panelConfirmacion.classList.add("oculto");
  panelFormulario.classList.remove("oculto");
  formIncidencia.codigo_contenedor.focus();
});

formIncidencia.descripcion.addEventListener("input", actualizarCuenta);
formIncidencia.foto.addEventListener("change", elegirFoto);
document.getElementById("quitar-foto").addEventListener("click", quitarFoto);

const codigoInicial = new URLSearchParams(window.location.search).get("contenedor");
if (codigoInicial && /^[A-Za-z0-9-]{1,20}$/.test(codigoInicial)) {
  formIncidencia.codigo_contenedor.value = codigoInicial.toUpperCase();
}

activarValidacion(formIncidencia);
cargarSugerencias();
