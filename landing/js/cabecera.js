// ============================================================================
//  SiGeRU 2026 - cabecera.js
//
//  La cabecera arranca transparente encima del hero. Cuando se baja un poco,
//  se le agrega la clase "solida" para que se pinte de verde y los enlaces se
//  sigan leyendo sobre el contenido blanco.
// ============================================================================

const cabecera = document.querySelector(".cabecera");

function actualizarCabecera() {
  if (window.scrollY > 60) {
    cabecera.classList.add("solida");
  } else {
    cabecera.classList.remove("solida");
  }
}

window.addEventListener("scroll", actualizarCabecera);
actualizarCabecera();   // por si la pagina se abre ya scrolleada
