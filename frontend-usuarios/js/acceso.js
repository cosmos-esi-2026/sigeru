function mostrarEstado(elemento, texto, tipo) {
  elemento.textContent = texto;
  elemento.className = "mensaje" + (texto ? " " + tipo : "");
}

document.querySelectorAll("[data-mostrar-clave]").forEach(function (boton) {
  const campo = document.getElementById(boton.dataset.mostrarClave);
  if (!campo) return;
  boton.addEventListener("click", function () {
    const visible = campo.type === "password";
    campo.type = visible ? "text" : "password";
    boton.textContent = visible ? "Ocultar" : "Mostrar";
    boton.setAttribute("aria-label", visible ? "Ocultar contraseña" : "Mostrar contraseña");
    campo.focus();
  });
});
