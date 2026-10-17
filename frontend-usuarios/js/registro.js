const formRegistro = document.getElementById("form-registro");
const mensajeRegistro = document.getElementById("mensaje-registro");

activarValidacion(formRegistro);

formRegistro.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrarEstado(mensajeRegistro, "", "");
  if (!validarFormulario(formRegistro)) return;
  if (formRegistro.elements.password.value !== formRegistro.elements.confirmacion.value) {
    marcarError(formRegistro.elements.confirmacion, "Las contraseñas no coinciden.");
    formRegistro.elements.confirmacion.focus();
    return;
  }

  const boton = bloquearEnvio(formRegistro, "Creando cuenta...");
  try {
    const { ok, resultado } = await apiPost(API.usuarios + "/registro", {
      nombre: formRegistro.elements.nombre.value.trim(),
      apellido: formRegistro.elements.apellido.value.trim(),
      email: formRegistro.elements.email.value.trim(),
      password: formRegistro.elements.password.value,
    });
    if (ok) {
      formRegistro.hidden = true;
      document.getElementById("registro-exito").hidden = false;
      formRegistro.reset();
      return;
    }
    const texto = resultado.mensaje || "No pudimos crear la cuenta.";
    if (!marcarErrorDeApi(formRegistro, texto)) mostrarEstado(mensajeRegistro, texto, "error");
  } catch (error) {
    mostrarEstado(mensajeRegistro, "No pudimos conectar con SIGERU. Probá de nuevo en unos minutos.", "error");
  } finally {
    liberarEnvio(boton);
  }
});
