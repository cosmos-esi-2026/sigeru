// Guia "donde tiro esto": busca en data/residuos.json, sin API y sin internet.
// La logica de busqueda esta separada de la pantalla para poder probarla sola.
(function () {
  "use strict";

  var ETIQUETAS = {
    Organico: "Orgánico", Plastico: "Plástico", Vidrio: "Vidrio", Papel: "Papel",
    Metal: "Metal", Mixto: "Mixto", Especial: "Especial"
  };

  function normalizar(texto) {
    return String(texto || "")
      .toLowerCase()
      .normalize("NFD").replace(/[̀-ͯ]/g, "")
      .replace(/[^a-z0-9 ]+/g, " ")
      .replace(/\s+/g, " ")
      .trim();
  }

  // Todas las palabras escritas tienen que aparecer en el nombre, los sinonimos o el tipo.
  function buscar(items, consulta, tipo) {
    var palabras = normalizar(consulta).split(" ").filter(Boolean);
    return items.filter(function (it) {
      if (tipo && it.tipo !== tipo) { return false; }
      if (palabras.length === 0) { return true; }
      var pajar = normalizar([it.nombre, (it.sinonimos || []).join(" "), it.tipo, ETIQUETAS[it.tipo] || ""].join(" "));
      return palabras.every(function (p) { return pajar.indexOf(p) !== -1; });
    });
  }

  if (typeof module !== "undefined" && module.exports) {
    module.exports = { normalizar: normalizar, buscar: buscar };
  }
  if (typeof document === "undefined") { return; }

  var datos = null;
  var el = function (id) { return document.getElementById(id); };

  function crear(tag, clase, texto) {
    var n = document.createElement(tag);
    if (clase) { n.className = clase; }
    if (texto !== undefined) { n.textContent = texto; }
    return n;
  }

  function enlace(f) {
    var li = document.createElement("li");
    var a = crear("a", "", f.texto);
    a.href = f.url; a.rel = "noopener"; a.target = "_blank";
    li.appendChild(a);
    return li;
  }

  function dibujarFuentes() {
    el("aviso-guia").textContent = datos.aviso;
    datos.fuentes.forEach(function (f) { el("fuentes-guia").appendChild(enlace(f)); });
    datos.enlaces_del_manual.forEach(function (f) { el("enlaces-guia").appendChild(enlace(f)); });
  }

  function tarjeta(it) {
    var li = crear("li", "resultado-guia tipo-" + it.tipo.toLowerCase());
    var cab = crear("div", "resultado-cab");
    cab.appendChild(crear("h2", "", it.nombre));
    cab.appendChild(crear("span", "insignia-tipo", ETIQUETAS[it.tipo] || it.tipo));
    li.appendChild(cab);
    var p = crear("p", "");
    p.appendChild(crear("strong", "", "Va en: "));
    p.appendChild(document.createTextNode(it.contenedor));
    li.appendChild(p);
    if (it.consejo) { li.appendChild(crear("p", "consejo-guia", it.consejo)); }
    li.appendChild(crear("p", "fuente-item", "Fuente: Manual de la Intendencia de Montevideo (2024), página " + it.pagina + "."));
    return li;
  }

  function dibujar() {
    var q = el("q").value;
    var tipo = el("tipo").value;
    var lista = buscar(datos.items, q, tipo);
    var ul = el("resultados-guia");
    ul.textContent = "";
    lista.forEach(function (it) { ul.appendChild(tarjeta(it)); });

    var hayEspecial = lista.some(function (it) { return it.tipo === "Especial"; });
    el("nota-especial").hidden = !hayEspecial;

    var estado = el("estado-guia");
    if (lista.length === 0) {
      estado.textContent = "No encontramos «" + q.trim() + "». Probá con otra palabra o consultá a tu intendencia.";
    } else if (q.trim() === "" && !tipo) {
      estado.textContent = lista.length + " residuos en la guía (orden alfabético).";
    } else {
      estado.textContent = lista.length === 1 ? "1 resultado." : lista.length + " resultados.";
    }
  }

  function iniciar() {
    fetch("data/residuos.json", { cache: "no-cache" })
      .then(function (r) { if (!r.ok) { throw new Error("http " + r.status); } return r.json(); })
      .then(function (d) {
        datos = d;
        datos.items.sort(function (a, b) { return a.nombre.localeCompare(b.nombre, "es"); });
        var sel = el("tipo");
        Object.keys(ETIQUETAS).forEach(function (k) {
          var o = document.createElement("option");
          o.value = k; o.textContent = ETIQUETAS[k];
          sel.appendChild(o);
        });
        dibujarFuentes();
        var inicial = new URLSearchParams(location.search).get("q");
        if (inicial) { el("q").value = inicial; }
        el("q").addEventListener("input", dibujar);
        sel.addEventListener("change", dibujar);
        dibujar();
      })
      .catch(function () {
        el("estado-guia").textContent = "No se pudo cargar la guía. Revisá tu conexión y recargá la página.";
      });
  }

  iniciar();
})();
