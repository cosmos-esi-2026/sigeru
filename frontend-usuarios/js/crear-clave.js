const formClave = document.getElementById("form-clave");
const mensajeClave = document.getElementById("mensaje");
const tokenClave = new URLSearchParams(window.location.search).get("token");
window.history.replaceState(null, "", window.location.pathname);

activarValidacion(formClave);
if (!tokenClave) {
  formClave.hidden = true;
  mostrarEstado(mensajeClave, "Falta el enlace de acceso. Si sos vecino, podés pedir uno nuevo desde Recuperar contraseña.", "error");
}

formClave.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrarEstado(mensajeClave, "", "");
  if (!validarFormulario(formClave)) return;
  if (formClave.elements.password.value !== formClave.elements.confirmacion.value) {
    marcarError(formClave.elements.confirmacion, "Las contraseñas no coinciden.");
    formClave.elements.confirmacion.focus();
    return;
  }

  const boton = bloquearEnvio(formClave, "Guardando...");
  try {
    const { ok, resultado } = await apiPost(API.usuarios + "/crear-clave", {
      token: tokenClave,
      password: formClave.elements.password.value,
    });
    if (ok) {
      formClave.hidden = true;
      mostrarEstado(mensajeClave, "Contraseña guardada. Ya podés iniciar sesión.", "ok");
      return;
    }
    mostrarEstado(mensajeClave, resultado.mensaje || "No pudimos guardar la contraseña.", "error");
  } catch (error) {
    mostrarEstado(mensajeClave, "No pudimos conectar con SIGERU. Probá de nuevo.", "error");
  } finally {
    liberarEnvio(boton);
  }
});
