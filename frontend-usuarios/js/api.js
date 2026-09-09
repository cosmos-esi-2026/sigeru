// ============================================================================
//  SiGeRU 2026 - api.js
//  Configuracion y funciones para hablar con las dos APIs.
//
//  SESION: la identidad ya NO viaja en cabeceras que el navegador pueda
//  inventar. El login crea una sesion en el servidor y el navegador guarda solo
//  una cookie de sesion, que viaja sola en cada peticion gracias a
//  credentials: "same-origin". Lo que se guarda en localStorage es unicamente
//  para pintar el nombre y el rol en la pantalla: quien manda es el servidor.
// ============================================================================

const API = {
  usuarios: "/sigeru/api-usuarios",
  gestion:  "/sigeru/api-gestion",
};

// Mismas opciones para todas las peticiones: la cookie de sesion siempre viaja.
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
    // El codigo viaja en el error para que la pantalla pueda distinguir una
    // sesion vencida (401) o una falta de permiso (403) de una caida del
    // servidor, y decirle a la persona que le pasa realmente.
    const error = new Error("Error al obtener datos (" + respuesta.status + ")");
    error.estado = respuesta.status;
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

// La baja siempre es logica del lado del servidor (cambio de estado), aunque el
// verbo HTTP correcto para pedirla siga siendo DELETE.
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
  cerrar() {
    localStorage.removeItem("sigeru_usuario");
  },
  // Guarda de interfaz, no de seguridad: evita mostrar una pantalla vacia a
  // quien no inicio sesion. Lo que protege los datos es el 401 del servidor.
  exigir() {
    if (!this.obtener()) {
      window.location.href = "login.html";
    }
  },
  async cerrarEnServidor() {
    try {
      await fetch(API.usuarios + "/logout", opciones("POST", {}));
    } catch (e) {
      // Si el servidor no responde igual se limpia el navegador.
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
