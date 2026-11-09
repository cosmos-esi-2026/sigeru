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

// (el cálculo de la cédula está en cedula.js)
// Marca error en el campo de la cédula si no es válida. Devuelve true si está bien.
function revisarCedula(campo) {
  if (cedulaUruguayaValida(campo.value)) {
    limpiarError(campo);
    return true;
  }
  marcarError(campo, "La cédula no es válida: revisá los dígitos (7 u 8, con el verificador).");
  campo.focus();
  return false;
}
