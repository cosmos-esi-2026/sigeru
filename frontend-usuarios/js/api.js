const API = {
  usuarios: "/sigeru/api-usuarios",
  gestion:  "/sigeru/api-gestion",
  recoleccion: "/sigeru/api-recoleccion",
};

function opciones(metodo, datos) {
  const config = {
    method: metodo,
    credentials: "same-origin",
  };
  if (datos !== undefined) {
    config.headers = { "Content-Type": "application/json" };
    config.body = JSON.stringify(datos);
  }
  return config;
}

async function apiGet(url) {
  const respuesta = await fetch(url, opciones("GET"));
  if (!respuesta.ok) {
    const error = new Error("Error al obtener datos (" + respuesta.status + ")");
    error.estado = respuesta.status;
    try {
      error.mensaje = (await respuesta.json()).mensaje;
    } catch (e) {
      error.mensaje = undefined;
    }
    throw error;
  }
  return await respuesta.json();
}

async function apiPost(url, datos) {
  const respuesta = await fetch(url, opciones("POST", datos));
  const resultado = await respuesta.json();
  return { ok: respuesta.ok, resultado };
}

async function apiPut(url, datos) {
  const respuesta = await fetch(url, opciones("PUT", datos));
  const resultado = await respuesta.json();
  return { ok: respuesta.ok, resultado };
}

async function apiDelete(url, datos) {
  const respuesta = await fetch(url, opciones("DELETE", datos || {}));
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
  rol() {
    const usuario = this.obtener();
    return usuario ? usuario.rol : null;
  },
  esAdministrador() {
    return this.rol() === "Administrador";
  },
  cerrar() {
    localStorage.removeItem("sigeru_usuario");
  },
  exigir() {
    if (!this.obtener()) {
      window.location.href = "login.html";
    }
  },
  async cerrarEnServidor() {
    try {
      await fetch(API.usuarios + "/logout", opciones("POST", {}));
    } catch (e) {
    }
    this.cerrar();
  },
};

document.addEventListener("DOMContentLoaded", function () {
  const botonSalir = document.getElementById("btn-salir");
  if (botonSalir) {
    botonSalir.addEventListener("click", async function (e) {
      e.preventDefault();
      await Sesion.cerrarEnServidor();
      window.location.href = "login.html";
    });
  }
});
