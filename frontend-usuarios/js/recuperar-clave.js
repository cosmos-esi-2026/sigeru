const formRecuperar = document.getElementById("form-recuperar");
const mensajeRecuperar = document.getElementById("mensaje-recuperar");

activarValidacion(formRecuperar);

formRecuperar.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrarEstado(mensajeRecuperar, "", "");
  if (!validarFormulario(formRecuperar)) return;

  const boton = bloquearEnvio(formRecuperar, "Enviando...");
  try {
    const { ok, resultado } = await apiPost(API.usuarios + "/recuperar-clave", {
      email: formRecuperar.elements.email.value.trim(),
    });
    if (ok) {
      formRecuperar.hidden = true;
      mostrarEstado(mensajeRecuperar, resultado.mensaje, "ok");
      return;
    }
    mostrarEstado(mensajeRecuperar, resultado.mensaje || "No pudimos procesar el pedido. Probá más tarde.", "error");
  } catch (error) {
    mostrarEstado(mensajeRecuperar, "No pudimos conectar con SIGERU. Probá más tarde.", "error");
  } finally {
    liberarEnvio(boton);
  }
});
