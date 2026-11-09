function mensajeDeError(campo) {
  const estado = campo.validity;

  if (estado.valueMissing && campo.type === "radio") {
    return "Elegí una opción.";
  }
  if (estado.valueMissing) {
    return "Completá este campo.";
  }
  if (estado.typeMismatch && campo.type === "email") {
    return "Escribí un email válido, con @ y dominio.";
  }
  if (estado.rangeUnderflow) {
    return "Tiene que ser un número mayor o igual a " + campo.min + ".";
  }
  if (estado.stepMismatch || estado.badInput) {
    return "Escribí un número válido.";
  }
  if (estado.patternMismatch && campo.getAttribute("inputmode") === "numeric") {
    return "Escribí solo números enteros, sin letras, puntos ni signos.";
  }
  if (estado.tooShort) {
    return "Tiene que tener al menos " + campo.minLength + " caracteres.";
  }
  return campo.validationMessage;
}

function contenedorError(campo) {
  const id = "error-" + (campo.form ? campo.form.id : "campo") + "-" + campo.name;
  let elemento = document.getElementById(id);

  if (!elemento) {
    elemento = document.createElement("span");
    elemento.className = "error-campo";
    elemento.id = id;
    campo.insertAdjacentElement("afterend", elemento);
  }
  return elemento;
}

function marcarError(campo, texto) {
  const elemento = contenedorError(campo);
  elemento.textContent = texto;

  campo.setAttribute("aria-invalid", "true");
  campo.setAttribute("aria-describedby", elemento.id);
}

function limpiarError(campo) {
  const elemento = document.getElementById(
    "error-" + (campo.form ? campo.form.id : "campo") + "-" + campo.name
  );
  if (elemento) {
    elemento.textContent = "";
  }
  campo.removeAttribute("aria-invalid");
  campo.removeAttribute("aria-describedby");
}

function limpiarErrores(formulario) {
  Array.prototype.forEach.call(formulario.elements, function (campo) {
    if (campo.name) {
      limpiarError(campo);
    }
  });
}

function validarCampo(campo) {
  if (!campo.name || campo.disabled || campo.type === "submit") {
    return true;
  }
  if (campo.checkValidity()) {
    limpiarError(campo);
    return true;
  }
  marcarError(campo, mensajeDeError(campo));
  return false;
}

function validarFormulario(formulario) {
  let primerError = null;

  Array.prototype.forEach.call(formulario.elements, function (campo) {
    if (!validarCampo(campo) && !primerError) {
      primerError = campo;
    }
  });

  if (primerError) {
    primerError.focus();
    return false;
  }
  return true;
}

function activarValidacion(formulario) {
  if (!formulario) {
    return;
  }
  formulario.setAttribute("novalidate", "novalidate");

  formulario.addEventListener("focusout", function (evento) {
    const campo = evento.target;
    if (campo.name && campo.value !== "") {
      validarCampo(campo);
    }
  });

  formulario.addEventListener("input", function (evento) {
    const campo = evento.target;
    if (campo.getAttribute("aria-invalid") === "true" && campo.checkValidity()) {
      limpiarError(campo);
    }
  });
}

function marcarErrorDeApi(formulario, texto) {
  if (!texto) {
    return false;
  }
  const enMinusculas = texto.toLowerCase();

  const campo = Array.prototype.find.call(formulario.elements, function (elemento) {
    return elemento.name && enMinusculas.indexOf(elemento.name.toLowerCase()) !== -1;
  });

  if (!campo) {
    return false;
  }
  marcarError(campo, texto);
  campo.focus();
  return true;
}

function bloquearEnvio(formulario, textoOcupado) {
  const boton = formulario.querySelector('button[type="submit"]');
  if (!boton) {
    return null;
  }

  boton.dataset.textoOriginal = boton.textContent.trim();
  boton.textContent = textoOcupado || "Enviando...";
  boton.disabled = true;
  boton.setAttribute("aria-busy", "true");
  return boton;
}

function liberarEnvio(boton) {
  if (!boton) {
    return;
  }
  boton.textContent = boton.dataset.textoOriginal || "Guardar";
  boton.disabled = false;
  boton.removeAttribute("aria-busy");
}
