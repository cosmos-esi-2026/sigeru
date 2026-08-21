// ============================================================================
//  SiGeRU 2026 - dashboard.js
//
//  Calcula las cuatro metricas del panel y arma la tabla de contenedores
//  llenos. No hay endpoints de resumen en las APIs: se piden los tres listados
//  que ya existen y se cuenta aca, del lado del navegador.
// ============================================================================

Sesion.exigir();

const cuerpoLlenos = document.getElementById("cuerpo-llenos");

function pintarMetrica(id, valor) {
  document.getElementById(id).textContent = valor;
}

async function cargarPanel() {
  // Contenedores: total, cuantos estan llenos, y la tabla de esos llenos
  try {
    const respuesta = await apiGet(API.gestion + "/contenedores");
    const contenedores = respuesta.datos || [];

    pintarMetrica("metrica-contenedores", contenedores.length);

    const llenos = contenedores.filter(function (c) {
      return c.estado === "lleno";
    });
    pintarMetrica("metrica-llenos", llenos.length);
    dibujarLlenos(llenos);
  } catch (e) {
    pintarMetrica("metrica-contenedores", "-");
    pintarMetrica("metrica-llenos", "-");
    cuerpoLlenos.innerHTML = filaAviso(4, "No se pudo conectar con la API de gestion.");
  }

  // Camiones: cuantos estan operativos
  try {
    const respuesta = await apiGet(API.gestion + "/camiones");
    const camiones = respuesta.datos || [];

    const operativos = camiones.filter(function (c) {
      return c.estado === "operativo";
    });
    pintarMetrica("metrica-camiones", operativos.length);
  } catch (e) {
    pintarMetrica("metrica-camiones", "-");
  }

  // Usuarios: cuantos hay registrados
  try {
    const respuesta = await apiGet(API.usuarios + "/usuarios");
    const usuarios = respuesta.datos || [];

    pintarMetrica("metrica-usuarios", usuarios.length);
  } catch (e) {
    pintarMetrica("metrica-usuarios", "-");
  }
}

function dibujarLlenos(llenos) {
  if (llenos.length === 0) {
    cuerpoLlenos.innerHTML = filaVacia(4, "No hay contenedores llenos en este momento.");
    return;
  }

  cuerpoLlenos.innerHTML = "";
  llenos.forEach(function (c) {
    cuerpoLlenos.innerHTML += `
      <tr>
        <td>${c.codigo}</td>
        <td>${c.direccion}</td>
        <td>${c.capacidad ?? "-"}</td>
        <td><span class="estado rojo">${c.estado}</span></td>
      </tr>`;
  });
}

cargarPanel();
