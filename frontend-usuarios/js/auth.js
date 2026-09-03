const formLogin = document.getElementById("form-login");
const formRegistro = document.getElementById("form-registro");
const mensaje = document.getElementById("mensaje");

if (formLogin) {
  formLogin.addEventListener("submit", async function (evento) {
    evento.preventDefault();

    const datos = {
      email:    formLogin.email.value,
      password: formLogin.password.value,
    };

    try {
      const { ok, resultado } = await apiPost(API.usuarios + "/login", datos);

      if (ok) {
       Sesion.guardar(resultado.datos);
        window.location.href = "dashboard.html";
      } else {
        mostrar(resultado.mensaje || "No se pudo iniciar sesion", "error");
      }
    } catch (e) {
      mostrar("No se pudo conectar con la API. Verifica que este corriendo.", "error");
    }
  });
}

if (formRegistro) {
  formRegistro.addEventListener("submit", async function (evento) {
    evento.preventDefault();

    // El registro publico crea una SOLICITUD, no un usuario: el alta efectiva la
    // hace un administrador al aprobarla. Es el circuito que modela la tabla
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
          "Solicitud enviada. Un administrador la va a revisar y vas a recibir " +
          "un aviso cuando este aprobada.",
          "ok"
        );
        formRegistro.reset();
      } else {
        mostrar(resultado.mensaje || "No se pudo registrar", "error");
      }
    } catch (e) {
      mostrar("No se pudo conectar con la API. Verifica que este corriendo.", "error");
    }
  });
}

function mostrar(texto, tipo) {
  mensaje.textContent = texto;
  mensaje.className = "mensaje " + tipo;
}