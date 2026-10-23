const formLogin = document.getElementById("form-login");
const mensaje = document.getElementById("mensaje");

activarValidacion(formLogin);

formLogin.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrarEstado(mensaje, "", "");
  if (!validarFormulario(formLogin)) return;

  const boton = bloquearEnvio(formLogin, "Ingresando...");
  try {
    const { ok, resultado } = await apiPost(API.usuarios + "/login", {
      email: formLogin.elements.email.value.trim(),
      password: formLogin.elements.password.value,
    });
    if (ok) {
      Sesion.guardar(resultado.datos);
      window.location.href = Sesion.destinoSeguro(new URLSearchParams(window.location.search).get("volver")) || "dashboard.html";
      return;
    }
    const texto = resultado.mensaje || "No pudimos iniciar sesión. Revisá tus datos.";
    mostrarEstado(mensaje, texto, "error");
  } catch (error) {
    mostrarEstado(mensaje, "No pudimos conectar con SIGERU. Probá de nuevo en unos minutos.", "error");
  } finally {
    liberarEnvio(boton);
  }
});
