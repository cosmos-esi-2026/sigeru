// Pedido de acceso de una cuadrilla: no crea ninguna cuenta, deja una solicitud pendiente que resuelve un administrador.
const formPedido = document.getElementById("form-pedido");
const mensajePedido = document.getElementById("mensaje-pedido");

activarValidacion(formPedido);

formPedido.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrarEstado(mensajePedido, "", "");
  if (!validarFormulario(formPedido)) return;

  const boton = bloquearEnvio(formPedido, "Enviando...");
  try {
    const { ok, resultado } = await apiPost(API.usuarios + "/solicitudes", {
      nombre: formPedido.elements.nombre.value.trim(),
      apellido: formPedido.elements.apellido.value.trim(),
      email: formPedido.elements.email.value.trim(),
      telefono: formPedido.elements.telefono.value.trim() || null,
      tipo_cuenta: "cuadrilla",
      cuadrilla_indicada: formPedido.elements.cuadrilla_indicada.value.trim(),
    });
    if (ok) {
      formPedido.hidden = true;
      document.getElementById("pedido-exito").hidden = false;
      formPedido.reset();
      return;
    }
    const texto = resultado.mensaje || "No pudimos enviar el pedido.";
    if (!marcarErrorDeApi(formPedido, texto)) mostrarEstado(mensajePedido, texto, "error");
  } catch (error) {
    mostrarEstado(mensajePedido, "No pudimos conectar con SIGERU. Probá de nuevo en unos minutos.", "error");
  } finally {
    liberarEnvio(boton);
  }
});
