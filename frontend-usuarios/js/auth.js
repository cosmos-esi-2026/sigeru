// ============================================================================
//  SiGeRU 2026 - auth.js
//  Maneja el login y la solicitud de acceso. Funciona en login.html y en
//  registro.html: detecta cual de los dos formularios existe en la pagina.
// ============================================================================

const formLogin = document.getElementById("form-login");
const formRegistro = document.getElementById("form-registro");
const mensaje = document.getElementById("mensaje");


// --- Login ------------------------------------------------------------------

if (formLogin) {
  activarValidacion(formLogin);

  formLogin.addEventListener("submit", async function (evento) {
    evento.preventDefault();
    mostrar("", "");

    if (!validarFormulario(formLogin)) {
      return;
    }

    const boton = bloquearEnvio(formLogin, "Entrando...");
    const datos = {
      email:    formLogin.email.value,
      password: formLogin.password.value,
    };

    try {
      const { ok, resultado } = await apiPost(API.usuarios + "/login", datos);

      if (ok) {
        Sesion.guardar(resultado.datos);
        window.location.href = "dashboard.html";
        return;   // no se libera el boton: la pagina ya se esta yendo
      }
      mostrar(resultado.mensaje || "El email o la contraseña no coinciden.", "error");
    } catch (e) {
      mostrar("No se pudo conectar con el servidor. Revisá que esté corriendo.", "error");
    }
    liberarEnvio(boton);
  });
}


// --- Solicitud de acceso ----------------------------------------------------

if (formRegistro) {
  activarValidacion(formRegistro);

  formRegistro.addEventListener("submit", async function (evento) {
    evento.preventDefault();
    mostrar("", "");

    if (!validarFormulario(formRegistro)) {
      return;
    }

    const boton = bloquearEnvio(formRegistro, "Enviando...");

    // El registro publico crea una SOLICITUD, no un usuario: el alta efectiva
    // la hace un administrador al aprobarla. Es el circuito que modela la tabla
    // solicitud_registro del DER y que pide el CU-011.
    const datos = {
      nombre:    formRegistro.nombre.value,
      apellido:  formRegistro.apellido.value,
      email:     formRegistro.email.value,
      telefono:  formRegistro.telefono ? formRegistro.telefono.value : "",
      direccion: formRegistro.direccion ? formRegistro.direccion.value : "",
    };

    try {
      const { ok, resultado } = await apiPost(API.usuarios + "/solicitudes", datos);

      if (ok) {
        mostrar(
          "Listo, recibimos tu solicitud. Un administrador la revisa y te avisa " +
          "por email cuando esté aprobada.",
          "ok"
        );
        formRegistro.reset();
        limpiarErrores(formRegistro);
      } else {
        const texto = resultado.mensaje || "No se pudo enviar la solicitud.";
        if (!marcarErrorDeApi(formRegistro, texto)) {
          mostrar(texto, "error");
        }
      }
    } catch (e) {
      mostrar("No se pudo conectar con el servidor. Revisá que esté corriendo.", "error");
    }
    liberarEnvio(boton);
  });
}


// --- Aviso ------------------------------------------------------------------
// El icono ayuda a distinguir exito de error sin depender solo del color, que
// es lo que pide la pauta de accesibilidad para daltonismo.

function mostrar(texto, tipo) {
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
