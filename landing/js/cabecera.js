const cabecera = document.querySelector(".cabecera");

function actualizarCabecera() {
  if (window.scrollY > 60) {
    cabecera.classList.add("solida");
  } else {
    cabecera.classList.remove("solida");
  }
}

window.addEventListener("scroll", actualizarCabecera);
actualizarCabecera();
