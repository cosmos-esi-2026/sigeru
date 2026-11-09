const formLogin = document.getElementById("form-login");
const mensaje = document.getElementById("mensaje");
const campoUsuario = document.getElementById("login-usuario");
const etiquetaUsuario = document.getElementById("login-usuario-etiqueta");
const pestanias = document.querySelectorAll("[data-modo-acceso]");

// Dos maneras de entrar: la cuadrilla con cédula y contraseña, el resto con correo y contraseña.
const MODOS = {
  correo: { nombre: "email", etiqueta: "Correo electrónico", tipo: "email", teclado: "email", auto: "email", ejemplo: "nombre@correo.com", descripcion: "Ingresá con el correo y la contraseña de tu cuenta. Las cuadrillas entran por la pestaña «Cuadrilla»." },
  cedula: { nombre: "cedula", etiqueta: "Cédula de identidad", tipo: "text", teclado: "numeric", auto: "username", ejemplo: "1.234.567-8", descripcion: "Las cuadrillas entran con su cédula y la contraseña que les dio la administración." },
};
let modo = "correo";

function elegirModo(nuevo, enfocar) {
  const m = MODOS[nuevo];
  modo = nuevo;
  limpiarErrores(formLogin);
  mostrarEstado(mensaje, "", "");
  campoUsuario.name = m.nombre;
  campoUsuario.type = m.tipo;
  campoUsuario.setAttribute("inputmode", m.teclado);
  campoUsuario.setAttribute("autocomplete", m.auto);
  campoUsuario.placeholder = m.ejemplo;
  campoUsuario.value = "";
  etiquetaUsuario.textContent = m.etiqueta;
  document.querySelector(".acceso-descripcion").textContent = m.descripcion;
  pestanias.forEach(function (p) {
    const activa = p.dataset.modoAcceso === nuevo;
    p.setAttribute("aria-selected", activa ? "true" : "false");
    p.tabIndex = activa ? 0 : -1;
    p.classList.toggle("activa", activa);
  });
  if (enfocar) campoUsuario.focus();
}

pestanias.forEach(function (p) {
  p.addEventListener("click", function () { elegirModo(p.dataset.modoAcceso, true); });
  // Flechas para pasar de una pestaña a la otra, como en cualquier grupo de pestañas.
  p.addEventListener("keydown", function (e) {
    if (e.key !== "ArrowLeft" && e.key !== "ArrowRight") return;
    const otra = p.dataset.modoAcceso === "correo" ? "cedula" : "correo";
    elegirModo(otra, false);
    document.querySelector('[data-modo-acceso="' + otra + '"]').focus();
    e.preventDefault();
  });
});

// Si se llega con ?cuadrilla (desde «Pedí acceso» ya aprobado, o un enlace del equipo), se abre directo en la pestaña de cédula.
if (new URLSearchParams(window.location.search).has("cuadrilla")) {
  elegirModo("cedula", false);
}

activarValidacion(formLogin);

formLogin.addEventListener("submit", async function (evento) {
  evento.preventDefault();
  mostrarEstado(mensaje, "", "");
  if (!validarFormulario(formLogin)) return;
  if (modo === "cedula" && !revisarCedula(campoUsuario)) return;

  const boton = bloquearEnvio(formLogin, "Ingresando...");
  try {
    const cuerpo = { password: formLogin.elements.password.value };
    cuerpo[MODOS[modo].nombre] = campoUsuario.value.trim();
    const { ok, resultado } = await apiPost(API.usuarios + "/login", cuerpo);
    if (ok) {
      Sesion.guardar(resultado.datos);
      window.location.href = Sesion.destinoSeguro(new URLSearchParams(window.location.search).get("volver")) || "dashboard.html";
      return;
    }
    const texto = resultado.mensaje || "No pudimos iniciar sesión. Revisá tus datos.";
    mostrarEstado(mensaje, texto, "error");
  } catch (error) {
    mostrarEstado(mensaje, "No pudimos conectar con SIGERU. Probá de nuevo en unos minutos.", "error");
  } finally {
    liberarEnvio(boton);
  }
});
