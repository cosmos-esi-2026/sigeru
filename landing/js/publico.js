// Datos reales del sistema en la landing: las cifras de "El sistema hoy" y el mapa publico.
// Todo sale de GET /api-gestion/publico/mapa, que no pide sesion y no trae datos de personas.
(function () {
  const ENDPOINT = "/sigeru/api-gestion/publico/mapa";
  const CENTRO_MONTEVIDEO = [-34.885, -56.165];
  const COLORES = { disponible: "#15654a", lleno: "#b3261e", mantenimiento: "#8a5000" };
  const TEXTO_ESTADO = { disponible: "Disponible", lleno: "Lleno", mantenimiento: "En mantenimiento" };
  const TIPOS_CON_COLOR = { Organico: "organico", Plastico: "plastico", Vidrio: "vidrio", Papel: "papel", Metal: "metal" };
  const TILDES = { Organico: "Orgánico", Plastico: "Plástico" };

  const nota = document.getElementById("nota-cifras");
  const notaMapa = document.getElementById("nota-mapa");
  const avisoSinRed = document.getElementById("aviso-sin-red");

  function esc(valor) {
    return String(valor === null || valor === undefined ? "" : valor)
      .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
  }

  function conUbicacion(p) {
    return typeof p.latitud === "number" && typeof p.longitud === "number";
  }

  function mostrarCifras(r) {
    const valores = {
      "cifra-contenedores": r.contenedores, "cifra-disponibles": r.disponibles, "cifra-llenos": r.llenos,
      "cifra-zonas": r.zonas, "cifra-centros": r.centros_de_acopio, "cifra-resueltas": r.incidencias_resueltas,
    };
    Object.keys(valores).forEach(function (id) {
      document.getElementById(id).textContent = valores[id];
    });
    nota.textContent = "Datos reales del sistema, al momento de abrir la página.";
  }

  function mostrarTipos(tipos) {
    const lista = document.getElementById("tipos-lista");
    if (!tipos || tipos.length === 0) return;
    lista.innerHTML = tipos.map(function (t) {
      const color = TIPOS_CON_COLOR[t.nombre];
      return '<li class="tipo"' + (color ? ' data-tipo="' + color + '"' : "") + ">" +
             esc(TILDES[t.nombre] || t.nombre) + (t.reciclable ? " (reciclable)" : "") + "</li>";
    }).join("");
  }

  function popupContenedor(c) {
    return '<div class="popup-mapa"><strong>' + esc(c.codigo) + "</strong>" +
      "<span>" + esc(c.direccion) + "</span>" +
      "<span>" + esc([c.zona, c.tipo_residuo].filter(Boolean).join(" · ")) + "</span>" +
      '<span class="popup-estado" data-estado="' + esc(c.estado) + '">' + esc(TEXTO_ESTADO[c.estado] || c.estado) + "</span>" +
      '<a href="../frontend-usuarios/login.html">Ingresá para informar un problema</a></div>';
  }

  function dibujarMapa(datos) {
    const mapa = L.map("mapa-contenedores", { scrollWheelZoom: false }).setView(CENTRO_MONTEVIDEO, 12);
    const fondo = L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      referrerPolicy: "origin",
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    });
    function sinRed(si) {
      avisoSinRed.classList.toggle("oculto", !si);
      document.getElementById("mapa-contenedores").classList.toggle("sin-fondo", si);
    }
    fondo.on("tileerror", function () { sinRed(true); });
    fondo.on("tileload", function () { sinRed(false); });
    fondo.addTo(mapa);
    if (navigator.onLine === false) sinRed(true);

    const puntos = [];
    datos.contenedores.filter(conUbicacion).forEach(function (c) {
      const posicion = [c.latitud, c.longitud];
      puntos.push(posicion);
      L.circleMarker(posicion, {
        radius: 9, color: "#ffffff", weight: 2, fillColor: COLORES[c.estado] || "#64756c", fillOpacity: 0.95,
      }).bindPopup(popupContenedor(c)).bindTooltip(c.codigo + " - " + (TEXTO_ESTADO[c.estado] || c.estado)).addTo(mapa);
    });
    datos.centros.filter(conUbicacion).forEach(function (c) {
      const posicion = [c.latitud, c.longitud];
      puntos.push(posicion);
      L.marker(posicion, {
        icon: L.divIcon({ className: "marca-centro", iconSize: [18, 18] }),
        title: "Centro de acopio: " + c.nombre, alt: "Centro de acopio: " + c.nombre,
      }).bindPopup('<div class="popup-mapa"><strong>' + esc(c.nombre) + "</strong><span>" + esc(c.direccion || "") +
                   "</span><span>Centro de acopio</span></div>").addTo(mapa);
    });
    if (puntos.length > 1) mapa.fitBounds(puntos, { padding: [30, 30], maxZoom: 15 });

    const sin = datos.contenedores.length - datos.contenedores.filter(conUbicacion).length;
    notaMapa.textContent = sin > 0
      ? sin + " contenedores no tienen la ubicación cargada y no aparecen en el mapa; están en la lista."
      : "";
  }

  function dibujarLista(datos) {
    const filasContenedores = datos.contenedores.map(function (c) {
      return "<tr><td>" + esc(c.codigo) + "</td><td>" + esc(c.direccion) + "</td><td>" + esc(c.zona) + "</td><td>" +
             esc(c.tipo_residuo) + "</td><td>" + esc(TEXTO_ESTADO[c.estado] || c.estado) + "</td></tr>";
    });
    const filasCentros = datos.centros.map(function (c) {
      return "<tr><td>Centro de acopio</td><td>" + esc(c.direccion || "") + " (" + esc(c.nombre) + ")</td><td>-</td><td>-</td><td>Activo</td></tr>";
    });
    document.getElementById("cuerpo-lista").innerHTML = filasContenedores.concat(filasCentros).join("");
  }

  function fallo() {
    nota.textContent = "No pudimos cargar los datos del sistema ahora. Probá recargar la página en un momento.";
    notaMapa.textContent = "El mapa no está disponible porque no se pudieron cargar los datos.";
    document.getElementById("mapa-contenedores").classList.add("sin-fondo");
  }

  // Impacto ambiental: cifras agregadas de GET /api-gestion/publico/impacto (sin sesion, con freno por IP). Siempre rotulado como estimacion.
  function numero(valor, decimales) {
    return Number(valor).toLocaleString("es-UY", { minimumFractionDigits: 0, maximumFractionDigits: decimales });
  }

  function mostrarImpacto(d) {
    document.getElementById("impacto-kg").textContent = numero(d.kg_total, 1) + " kg";
    document.getElementById("impacto-pct").textContent = d.pct_reciclable === null ? "-" : numero(d.pct_reciclable, 1) + " %";
    document.getElementById("impacto-co2").textContent = d.kg_total > 0 ? "≈ " + numero(d.co2_evitado_kg, 0) + " kg" : "-";
    document.getElementById("impacto-recibidos").textContent = numero(d.kg_recibidos_en_centros, 1) + " kg";
    document.getElementById("nota-impacto").textContent = "Estimación a partir de los kilos recolectados de los tipos reciclables. " +
      (d.tipos_sin_factor.length ? "Sin fuente verificada para " + d.tipos_sin_factor.join(" y ") + ": solo se informan sus kilos." : "");
    const lista = document.getElementById("fuentes-impacto");
    lista.innerHTML = "";
    (d.fuentes || []).forEach(function (f) {
      const li = document.createElement("li");
      li.textContent = f.tipos.join(", ") + ": " + f.fuente + ". ";
      if (typeof f.url === "string" && f.url.indexOf("https://www.epa.gov/") === 0) {
        const a = document.createElement("a");
        a.href = f.url;
        a.rel = "noopener";
        a.textContent = "Ver la fuente";
        li.appendChild(a);
      }
      lista.appendChild(li);
    });
  }

  fetch("/sigeru/api-gestion/publico/impacto", { headers: { Accept: "application/json" } })
    .then(function (r) { if (!r.ok) throw new Error("estado " + r.status); return r.json(); })
    .then(function (r) { mostrarImpacto(r.datos); })
    .catch(function () { document.getElementById("nota-impacto").textContent = "No se pudieron cargar las cifras de impacto en este momento."; });

  fetch(ENDPOINT, { headers: { Accept: "application/json" } })
    .then(function (respuesta) {
      if (!respuesta.ok) throw new Error("estado " + respuesta.status);
      return respuesta.json();
    })
    .then(function (resultado) {
      const datos = resultado.datos;
      mostrarCifras(datos.resumen);
      mostrarTipos(datos.resumen.tipos_de_residuo);
      dibujarLista(datos);
      dibujarMapa(datos);
    })
    .catch(fallo);
})();
