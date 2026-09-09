// ============================================================================
//  SiGeRU 2026 - landing/js/main.js
//  Envia la solicitud de registro del vecino sin recargar la pagina.
//
//  La validacion y el bloqueo del boton salen de
//  ../frontend-usuarios/js/formularios.js, que ya usa el panel: asi el mismo
//  formulario se comporta igual en las dos partes del sistema y los mensajes
//  se escriben una sola vez.
// ============================================================================

const API_USUARIOS = "/sigeru/api-usuarios";

const formulario = document.getElementById("form-registro");
const mensaje = document.getElementById("mensaje-registro");

activarValidacion(formulario);

formulario.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrarMensaje("", "");

  if (!validarFormulario(formulario)) {
    return;
  }

  const boton = bloquearEnvio(formulario, "Enviando...");
  const datos = {
    nombre:    formulario.nombre.value,
    apellido:  formulario.apellido.value,
    email:     formulario.email.value,
    telefono:  formulario.telefono.value,
    direccion: formulario.direccion.value,
  };

  try {
    const respuesta = await fetch(API_USUARIOS + "/solicitudes", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(datos),
    });

    const resultado = await respuesta.json();

    if (respuesta.ok) {
      mostrarMensaje(
        "Listo, recibimos tu solicitud. Un administrador la revisa y te avisa " +
        "por email cuando esté aprobada.",
        "ok"
      );
      formulario.reset();
      limpiarErrores(formulario);
    } else {
      const texto = resultado.mensaje || "No se pudo enviar la solicitud.";
      if (!marcarErrorDeApi(formulario, texto)) {
        mostrarMensaje(texto, "error");
      }
    }
  } catch (error) {
    mostrarMensaje(
      "No se pudo conectar con el servidor. Probá de nuevo en un momento.",
      "error"
    );
  }

  liberarEnvio(boton);
});

// El icono distingue exito de error sin depender solo del color.
function mostrarMensaje(texto, tipo) {
  if (!texto) {
    mensaje.textContent = "";
    mensaje.className = "mensaje";
    return;
  }

  const icono = tipo === "ok"
    ? '<path d="M20 6 9 17l-5-5"/>'
    : '<circle cx="12" cy="12" r="9"/><path d="M12 8v4.5M12 16h.01"/>';

  mensaje.innerHTML =
    '<svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
    'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    icono + "</svg><span></span>";
  mensaje.querySelector("span").textContent = texto;
  mensaje.className = "mensaje " + tipo;
}
