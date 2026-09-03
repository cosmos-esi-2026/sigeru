const API = {
  usuarios: "/sigeru/api-usuarios",
  gestion:  "/sigeru/api-gestion",
};

// Cabeceras que identifican al usuario que inicio sesion. La API las usa para
// verificar el rol antes de dejar pasar una operacion que modifica datos.
// Cuando la persistencia este conectada y el login emita un token, lo unico
// que cambia es lo que se manda aca.
function cabeceras(conCuerpo) {
  const encabezados = {};
  if (conCuerpo) {
    encabezados["Content-Type"] = "application/json";
  }
  const usuario = Sesion.obtener();
  if (usuario) {
    encabezados["X-Usuario-Email"] = usuario.email;
    encabezados["X-Usuario-Rol"] = usuario.rol;
  }
  return encabezados;
}

async function apiGet(url) {
  const respuesta = await fetch(url, { headers: cabeceras(false) });
  if (!respuesta.ok) {
    throw new Error("Error al obtener datos (" + respuesta.status + ")");
  }
  return await respuesta.json();
}

async function apiPost(url, datos) {
  const respuesta = await fetch(url, {
    method: "POST",
    headers: cabeceras(true),
    body: JSON.stringify(datos),
  });
  const resultado = await respuesta.json();
  return { ok: respuesta.ok, resultado };
}

async function apiPut(url, datos) {
  const respuesta = await fetch(url, {
    method: "PUT",
    headers: cabeceras(true),
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