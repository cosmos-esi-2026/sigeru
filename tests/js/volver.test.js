// Pruebas de Sesion.destinoSeguro (frontend-usuarios/js/api.js): a donde vuelve el login con ?volver=.
// Lo que importa es que nunca acepte un destino fuera de esta carpeta (redireccion abierta, OWASP A01).
const salida = document.getElementById("resultado");
const lineas = [];
let pasaron = 0, fallaron = 0;

function afirmar(descripcion, esperado, obtenido) {
  const ok = JSON.stringify(esperado) === JSON.stringify(obtenido);
  ok ? pasaron++ : fallaron++;
  lineas.push((ok ? "OK    " : "FALLA ") + descripcion + (ok ? "" : "\n      esperado: " + JSON.stringify(esperado) + "\n      obtenido: " + JSON.stringify(obtenido)));
}

const validos = [
  "informar-problema.html?contenedor=MVD-172712",
  "informar-problema.html",
  "mis-reclamos.html",
  "incidencia.html?id=12",
  "recolecciones.html?estado=en_curso&zona=3",
];
validos.forEach(function (d) { afirmar("acepta " + d, d, Sesion.destinoSeguro(d)); });

const peligrosos = [
  "https://otro.sitio/informar-problema.html",
  "//otro.sitio/x.html",
  "/sigeru/frontend-usuarios/dashboard.html",
  "../landing/index.html",
  "javascript:alert(1)",
  "data:text/html,hola.html",
  "informar-problema.html#x",
  "informar-problema.html?contenedor=<script>",
  "informar-problema.html?a=b c",
  "carpeta/pagina.html",
  "pagina.php",
  "login.html?volver=dashboard.html",
  "registro.html",
  "",
  null,
  undefined,
  42,
  "a".repeat(296) + ".html",
];
peligrosos.forEach(function (d) { afirmar("rechaza " + JSON.stringify(d), null, Sesion.destinoSeguro(d)); });

afirmar("el codigo de un contenedor con el URL codificado entra", "informar-problema.html?contenedor=MVD%2D172712",
  Sesion.destinoSeguro("informar-problema.html?contenedor=MVD%2D172712"));

const ok = fallaron === 0;
document.title = (ok ? "OK " : "FALLA ") + pasaron + "/" + (pasaron + fallaron);
salida.className = ok ? "bien" : "mal";
salida.textContent = lineas.join("\n") + "\n\n" + pasaron + " pasaron, " + fallaron + " fallaron.";
