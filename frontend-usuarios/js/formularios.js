// ============================================================================
//  SiGeRU 2026 - formularios.js
//
//  Validacion de formularios y bloqueo del boton mientras se envia.
//  Lo usan login, registro y los tres formularios de alta (usuario, contenedor
//  y camion). Se carga antes que el resto de los scripts de cada pagina.
//
//  No valida por su cuenta: usa la validacion que ya trae el navegador
//  (required, type="email", min...) y solo se encarga de MOSTRARLA bien, en
//  español y pegada al campo que falla, en vez del globo gris del navegador.
// ============================================================================


// --- Mensajes ---------------------------------------------------------------
// El texto del navegador cambia segun el idioma del sistema operativo y suele
// ser generico. Estos son fijos y hablan del campo concreto.

function mensajeDeError(campo) {
  const estado = campo.validity;

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
  if (estado.tooShort) {
    return "Tiene que tener al menos " + campo.minLength + " caracteres.";
  }
  return campo.validationMessage;
}


// --- Marcar y limpiar el error de un campo ----------------------------------

function contenedorError(campo) {
  const id = "error-" + (campo.form ? campo.form.id : "campo") + "-" + campo.name;
  let elemento = document.getElementById(id);

  if (!elemento) {
    elemento = document.createElement("span");
    elemento.className = "error-campo";
    elemento.id = id;
    // El error va adentro del <label>, justo despues del campo.
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


// --- Validar ----------------------------------------------------------------

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

// Valida todo el formulario y deja el foco en el primer campo con problema,
// que es lo que evita que alguien mande el formulario y no encuentre el error.
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

// Se llama una vez por formulario. Desactiva los globos del navegador y valida
// cuando el campo pierde el foco (no mientras se escribe, que interrumpe).
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

  // Si el campo ya estaba marcado en rojo, el error se borra apenas se corrige.
  formulario.addEventListener("input", function (evento) {
    const campo = evento.target;
    if (campo.getAttribute("aria-invalid") === "true" && campo.checkValidity()) {
      limpiarError(campo);
    }
  });
}


// --- Errores que devuelve la API --------------------------------------------
// La API contesta con un texto plano ("Ya existe un usuario con ese email").
// Si ese texto nombra a un campo del formulario, el error se muestra pegado a
// ese campo, que es donde la persona tiene que corregir. Si no lo nombra, la
// pantalla lo muestra como aviso general.
// Devuelve true si encontro a que campo pegarlo.

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


// --- Bloqueo del boton mientras se envia ------------------------------------
// Sin esto, dos clicks seguidos mandan el alta dos veces.

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
