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

// Los pedidos que cambian algo NUNCA lanzan: si se corta la red o el servidor no contesta JSON (un error de Apache, por ejemplo),
// devuelven { ok: false, resultado: { ok: false, mensaje } } y la pantalla muestra ese mensaje. Antes los botones de baja y «Aprobar» se quedaban mudos.
async function pedirConCuerpo(metodo, url, datos) {
  let respuesta;
  try {
    respuesta = await fetch(url, opciones(metodo, datos));
  } catch (e) {
    return { ok: false, resultado: { ok: false, mensaje: "No se pudo conectar con el servidor. Revisá que esté corriendo e intentá de nuevo." } };
  }
  try {
    return { ok: respuesta.ok, resultado: await respuesta.json() };
  } catch (e) {
    return { ok: false, resultado: { ok: false, mensaje: "El servidor respondió algo inesperado (código " + respuesta.status + "). Intentá de nuevo." } };
  }
}

function apiPost(url, datos) {
  return pedirConCuerpo("POST", url, datos);
}

function apiPut(url, datos) {
  return pedirConCuerpo("PUT", url, datos);
}

function apiDelete(url, datos) {
  return pedirConCuerpo("DELETE", url, datos || {});
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
  // Sin sesion, va al login con la pagina actual en ?volver= (el QR pegado en un contenedor abre
  // informar-problema.html?contenedor=..., y sin esto el contenedor se perdia al iniciar sesion).
  exigir() {
    if (!this.obtener()) {
      this.irAlLogin();
    }
  },
  // Unico camino al login cuando falta la sesion (lo usan exigir() y shell.js).
  irAlLogin() {
    const aca = window.location.pathname.split("/").pop() + window.location.search;
    const volver = this.destinoSeguro(aca);
    window.location.replace("login.html" + (volver ? "?volver=" + encodeURIComponent(volver) : ""));
  },
  // Contra la redireccion abierta (OWASP A01): solo una pagina .html de esta misma carpeta, con un query
  // simple. Nada de "//", esquemas, barras, "..", "#" ni el propio login. Devuelve el destino o null.
  destinoSeguro(texto) {
    if (typeof texto !== "string" || texto.length === 0 || texto.length > 300) {
      return null;
    }
    if (!/^[a-z0-9-]+\.html(\?[A-Za-z0-9_=&%.\-]*)?$/.test(texto) || /^(login|registro)\.html/.test(texto)) {
      return null;
    }
    return texto;
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
