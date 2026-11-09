// Boton para pausar lo que se mueve en el panel izquierdo de las pantallas de acceso (WCAG 2.2.2: lo que se anima sin parar
// tiene que poder detenerse). Con "reducir movimiento" el CSS ya deja todo quieto y el boton no se muestra.
(function () {
  const panel = document.querySelector(".acceso-presentacion");
  const boton = document.querySelector(".acceso-pausa");
  if (!panel || !boton || window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    return;
  }
  boton.hidden = false;
  boton.addEventListener("click", function () {
    const pausado = panel.classList.toggle("pausado");
    boton.setAttribute("aria-pressed", pausado ? "true" : "false");
    boton.textContent = pausado ? "Reanudar la animación de fondo" : "Pausar la animación de fondo";
  });
})();
