Sesion.exigir();

const cuerpoLlenos = document.getElementById("cuerpo-llenos");
const COLUMNAS_LLENOS = 4;

function pintarMetrica(id, valor, detalle) {
  const elemento = document.getElementById(id);
  elemento.textContent = valor;
  if (detalle) {
    elemento.title = detalle;
  } else {
    elemento.removeAttribute("title");
  }
}

function cargandoMetrica(id) {
  document.getElementById(id).innerHTML = '<span class="esqueleto esqueleto-metrica"></span>';
}

async function cargarContenedores() {
  try {
    const respuesta = await apiGet(API.gestion + "/contenedores");
    const contenedores = respuesta.datos || [];

    pintarMetrica("metrica-contenedores", contenedores.length);

    const llenos = contenedores.filter(function (c) {
      return c.estado === "lleno";
    });
    pintarMetrica("metrica-llenos", llenos.length);
    dibujarLlenos(llenos);
  } catch (error) {
    pintarMetrica("metrica-contenedores", "—", mensajeDeFallo(error));
    pintarMetrica("metrica-llenos", "—", mensajeDeFallo(error));
    cuerpoLlenos.innerHTML = filaError(COLUMNAS_LLENOS, mensajeDeFallo(error));
  }
}

async function cargarCamiones() {
  try {
    const respuesta = await apiGet(API.recoleccion + "/camiones");
    const camiones = respuesta.datos || [];

    const operativos = camiones.filter(function (c) {
      return c.estado === "operativo";
    });
    pintarMetrica("metrica-camiones", operativos.length);
  } catch (error) {
    pintarMetrica("metrica-camiones", "—", mensajeDeFallo(error));
  }
}

async function cargarUsuarios() {
  try {
    const respuesta = await apiGet(API.usuarios + "/usuarios");
    pintarMetrica("metrica-usuarios", (respuesta.datos || []).length);
  } catch (error) {
    pintarMetrica("metrica-usuarios", "—", mensajeDeFallo(error));
  }
}

function dibujarLlenos(llenos) {
  if (llenos.length === 0) {
    cuerpoLlenos.innerHTML = filaVacia(
      COLUMNAS_LLENOS,
      "Ningún contenedor lleno",
      "Cuando un contenedor pase a estado «lleno» va a aparecer acá."
    );
    return;
  }

  cuerpoLlenos.innerHTML = llenos.map(function (c) {
    return '<tr role="row">' +
      celda("Código", c.codigo) +
      celda("Dirección", c.direccion) +
      celda("Capacidad", c.capacidad ? c.capacidad + " L" : null) +
      celdaEstado("Estado", c.estado, "rojo") +
      "</tr>";
  }).join("");
}

const NIVELES_URGENCIA = {
  critica:  { texto: "Crítica",        color: "rojo" },
  urgente:  { texto: "Urgente",        color: "naranja" },
  atencion: { texto: "Atención",       color: "azul" },
  normal:   { texto: "En seguimiento", color: "gris" },
};

function plural(cantidad, singular, pluralTexto) {
  return cantidad + " " + (cantidad === 1 ? singular : pluralTexto);
}

function haceCuanto(horas, dias) {
  return dias >= 1 ? plural(dias, "día", "días") : plural(horas, "hora", "horas");
}

function avisosDelPanel(datos, recoleccionesEnCurso) {
  const i = datos.indicadores;
  const avisos = [];
  if (i.criticas > 0) {
    avisos.push(["es-critico", plural(i.criticas, "incidencia crítica", "incidencias críticas") +
      " (prioridad alta y más de " + plural(datos.umbral_dias, "día", "días") + " abierta)."]);
  }
  if (i.sin_asignar > 0) {
    avisos.push(["", plural(i.sin_asignar, "incidencia sin cuadrilla asignada", "incidencias sin cuadrilla asignada") + "."]);
  }
  if (i.vencidas > 0) {
    avisos.push(["", plural(i.vencidas, "incidencia abierta", "incidencias abiertas") + " hace más de " +
      plural(datos.umbral_dias, "día", "días") + "."]);
  }
  if (i.contenedores_llenos > 0) {
    avisos.push(["", plural(i.contenedores_llenos, "contenedor lleno", "contenedores llenos") + " esperando recolección."]);
  }
  if (i.contenedores_mantenimiento > 0) {
    avisos.push(["", plural(i.contenedores_mantenimiento, "contenedor en mantenimiento", "contenedores en mantenimiento") + "."]);
  }
  if (recoleccionesEnCurso > 0) {
    avisos.push(["", plural(recoleccionesEnCurso, "recolección en curso", "recolecciones en curso") + " ahora."]);
  }
  if (avisos.length === 0) {
    avisos.push(["es-bien", "Todo en orden: no hay incidencias abiertas ni contenedores llenos."]);
  }
  return avisos;
}

async function cargarUrgencias() {
  const cuerpo = document.getElementById("cuerpo-urgencias");
  const lista = document.getElementById("avisos-panel");
  cuerpo.innerHTML = filaEsqueleto(7, 2);
  try {
    const resultados = await Promise.allSettled([
      apiGet(API.gestion + "/estadisticas/urgencias"),
      apiGet(API.recoleccion + "/recolecciones?estado=en_curso"),
    ]);
    if (resultados[0].status === "rejected") {
      throw resultados[0].reason;
    }
    const datos = resultados[0].value.datos;
    const enCurso = resultados[1].status === "fulfilled" ? (resultados[1].value.datos || []).length : 0;

    pintarMetrica("metrica-incidencias", datos.indicadores.abiertas);
    pintarMetrica("metrica-criticas", datos.indicadores.criticas);

    lista.innerHTML = avisosDelPanel(datos, enCurso).map(function (a) {
      return '<li class="' + a[0] + '">' + escaparHtml(a[1]) + "</li>";
    }).join("");

    if (datos.incidencias.length === 0) {
      cuerpo.innerHTML = filaVacia(7, "No hay incidencias abiertas", "Cuando un vecino informe un problema va a aparecer acá, ordenado por urgencia.");
    } else {
      cuerpo.innerHTML = datos.incidencias.map(function (inc) {
        const nivel = NIVELES_URGENCIA[inc.nivel] || NIVELES_URGENCIA.normal;
        return '<tr role="row">' +
          '<td role="cell" data-etiqueta="Urgencia"><span class="estado ' + nivel.color + ' nivel">' + escaparHtml(nivel.texto) + "</span></td>" +
          celdaEnlace("Número", inc.numero, "incidencia.html?id=" + inc.id_incidencia) +
          celda("Contenedor", inc.contenedor) + celda("Zona", inc.zona) +
          celdaMarca("Prioridad", "prioridad", inc.prioridad, PESO_PRIORIDAD[inc.prioridad] || 0) +
          celda("Abierta hace", haceCuanto(inc.horas_abierta, inc.dias_abierta), inc.horas_abierta) +
          celda("Cuadrilla", inc.cuadrilla || "Sin asignar") + "</tr>";
      }).join("");
    }
    document.getElementById("nota-urgencias").textContent = datos.indicadores.abiertas > datos.incidencias.length
      ? "Se muestran las " + datos.incidencias.length + " más urgentes de " + datos.indicadores.abiertas + " abiertas."
      : "";
  } catch (error) {
    pintarMetrica("metrica-incidencias", "—", mensajeDeFallo(error));
    pintarMetrica("metrica-criticas", "—", mensajeDeFallo(error));
    lista.innerHTML = "";
    cuerpo.innerHTML = filaError(7, mensajeDeFallo(error));
  }
}

function cargarPanel() {
  const esAdministrador = Sesion.esAdministrador();
  const metricas = ["metrica-contenedores", "metrica-llenos"];
  if (esAdministrador) {
    metricas.push("metrica-camiones", "metrica-usuarios", "metrica-incidencias", "metrica-criticas");
  }
  metricas.forEach(cargandoMetrica);
  cuerpoLlenos.innerHTML = filaEsqueleto(COLUMNAS_LLENOS, 2);

  cargarContenedores();
  if (esAdministrador) {
    cargarCamiones();
    cargarUsuarios();
    cargarUrgencias();
  }
}

document.getElementById("contenido-principal").addEventListener("click", function (evento) {
  if (evento.target.closest(".js-reintentar")) {
    cargarPanel();
  }
});

cargarPanel();
