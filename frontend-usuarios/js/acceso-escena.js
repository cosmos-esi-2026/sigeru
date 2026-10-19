// Boton para pausar la escena del login (WCAG 2.2.2: lo que se mueve sin parar tiene que poder detenerse).
// Con "reducir movimiento" la escena ya queda quieta por CSS y el boton no se muestra.
(function () {
  const escena = document.querySelector(".acceso-escena");
  const boton = document.querySelector(".acceso-pausa");
  if (!escena || !boton || window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    return;
  }
  boton.hidden = false;
  boton.addEventListener("click", function () {
    const pausado = escena.classList.toggle("pausado");
    boton.setAttribute("aria-pressed", pausado ? "true" : "false");
    boton.textContent = pausado ? "Reanudar la animación de fondo" : "Pausar la animación de fondo";
  });
})();
