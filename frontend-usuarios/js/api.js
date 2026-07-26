const API = {
  usuarios: "http://localhost:8081",
  gestion:  "http://localhost:8082",
};

async function apiGet(url) {
  const respuesta = await fetch(url);
  if (!respuesta.ok) {
    throw new Error("Error al obtener datos (" + respuesta.status + ")");
  }
  return await respuesta.json();
}

async function apiPost(url, datos) {
  const respuesta = await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(datos),
  });
  const resultado = await respuesta.json();
  return { ok: respuesta.ok, resultado };
}

const Sesion = {
  guardar(usuario) {
    localStorage.setItem("sigeru_usuario", JSON.stringify(usuario));
  },
  obtener() {
    const dato = localStorage.getItem("sigeru_usuario");
    return dato ? JSON.parse(dato) : null;
  },
  cerrar() {
    localStorage.removeItem("sigeru_usuario");
  },
  exigir() {
    if (!this.obtener()) {
      window.location.href = "login.html";
    }
  },
};

document.addEventListener("DOMContentLoaded", function () {
  const botonSalir = document.getElementById("btn-salir");
  if (botonSalir) {
    botonSalir.addEventListener("click", function (e) {
      e.preventDefault();
      Sesion.cerrar();
      window.location.href = "login.html";
    });
  }
});