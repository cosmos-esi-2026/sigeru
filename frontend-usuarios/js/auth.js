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

    const datos = {
      nombre:   formRegistro.nombre.value,
      apellido: formRegistro.apellido.value,
      email:    formRegistro.email.value,
      password: formRegistro.password.value,
    };

    try {
      const { ok, resultado } = await apiPost(API.usuarios + "/usuarios", datos);

      if (ok) {
        mostrar("Cuenta creada. Ya podes iniciar sesion.", "ok");
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