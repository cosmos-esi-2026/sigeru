/* Maqueta Low-Fi de SiGeRU — armado de la barra de contexto y del menu por rol.
 *
 * Este archivo NO contiene logica del sistema: no valida, no calcula y no guarda
 * datos. Lo unico que hace es (a) rotular cada pantalla para que quien la recorra
 * sepa que esta viendo una maqueta y en que numero de pantalla esta, y (b) mostrar
 * en el menu solamente las opciones que corresponden al rol elegido, que es uno de
 * los criterios de navegabilidad definidos en la 1ra entrega ("lo que un usuario no
 * puede hacer, no se muestra"). El rol se elige desde la barra superior para poder
 * recorrer la maqueta con los cuatro perfiles sin necesidad de un backend.
 */

var PANTALLAS = {
  'index':              { n: 1, titulo: 'Landing',               publica: true },
  'login':              { n: 2, titulo: 'Inicio de sesion',      publica: true },
  'panel':              { n: 3, titulo: 'Panel principal',       publica: false },
  'contenedores':       { n: 4, titulo: 'Contenedores',          publica: false },
  'incidencia-nueva':   { n: 5, titulo: 'Registrar incidencia',  publica: false },
  'incidencia-detalle': { n: 6, titulo: 'Detalle de incidencia', publica: false },
  'camiones':           { n: 7, titulo: 'Camiones',              publica: false },
  'reportes':           { n: 8, titulo: 'Reportes',              publica: false }
};

var ROLES = {
  admin:     'Administrador municipal',
  cuadrilla: 'Cuadrilla de recoleccion',
  acopio:    'Operario de centro de acopio',
  vecino:    'Vecino'
};

/* Que ve cada rol en el menu. Sale de la tabla 12.2 del documento de la 1ra
 * entrega, para que la maqueta y el documento no se contradigan. */
var MENU = {
  admin: [
    ['panel.html', 'Panel'],
    ['contenedores.html', 'Contenedores'],
    ['incidencia-detalle.html', 'Incidencias'],
    ['camiones.html', 'Camiones'],
    ['reportes.html', 'Reportes']
  ],
  cuadrilla: [
    ['panel.html', 'Panel'],
    ['incidencia-detalle.html', 'Mis incidencias']
  ],
  acopio: [
    ['panel.html', 'Panel'],
    ['contenedores.html', 'Centro de acopio']
  ],
  vecino: [
    ['panel.html', 'Mis reclamos'],
    ['incidencia-nueva.html', 'Informar un problema']
  ]
};

function rolActual() {
  try {
    return sessionStorage.getItem('sigeru_rol') || 'admin';
  } catch (e) {
    return 'admin';               // navegador con almacenamiento bloqueado
  }
}

function guardarRol(rol) {
  try { sessionStorage.setItem('sigeru_rol', rol); } catch (e) { /* se ignora */ }
}

function idPantalla() {
  var archivo = location.pathname.split('/').pop() || 'index.html';
  return archivo.replace('.html', '') || 'index';
}

function barraAviso() {
  var p = PANTALLAS[idPantalla()] || { n: '-', titulo: '' };
  var rol = rolActual();
  var opciones = '';
  for (var k in ROLES) {
    opciones += '<option value="' + k + '"' + (k === rol ? ' selected' : '') + '>' +
                ROLES[k] + '</option>';
  }
  var barra = document.createElement('div');
  barra.className = 'aviso-maqueta';
  barra.innerHTML =
    '<span><strong>Maqueta Low-Fi</strong> &nbsp;·&nbsp; SiGeRU &nbsp;·&nbsp; ' +
    'Pantalla ' + p.n + ' de 8: ' + p.titulo + '</span>' +
    '<span>Recorrer como: <select id="sel-rol">' + opciones + '</select></span>';
  document.body.insertBefore(barra, document.body.firstChild);

  document.getElementById('sel-rol').addEventListener('change', function () {
    guardarRol(this.value);
    location.reload();
  });
}

function armarMenu() {
  var caja = document.querySelector('[data-menu]');
  if (!caja) return;
  var rol = rolActual();
  var actual = idPantalla() + '.html';
  var html = '';
  MENU[rol].forEach(function (item) {
    html += '<a href="' + item[0] + '"' +
            (item[0] === actual ? ' class="activo"' : '') + '>' + item[1] + '</a>';
  });
  html += '<a href="index.html" class="salir">Cerrar sesion</a>';
  caja.innerHTML = html;
}

/* Muestra solo los bloques que corresponden al rol. Un bloque sin data-roles se ve
 * siempre; uno con data-roles="admin acopio" se ve solo con esos perfiles. Sostiene el
 * criterio de navegabilidad "lo que un usuario no puede hacer, no se muestra", que de
 * otro modo la maqueta contradecia: el vecino veia las incidencias de toda la ciudad. */
function filtrarPorRol() {
  var rol = rolActual();
  var bloques = document.querySelectorAll('[data-roles]');
  for (var i = 0; i < bloques.length; i++) {
    var permitidos = bloques[i].getAttribute('data-roles').split(' ');
    bloques[i].hidden = permitidos.indexOf(rol) === -1;
  }
}

function rotularSesion() {
  var etiqueta = document.querySelector('[data-sesion]');
  if (etiqueta) etiqueta.textContent = ROLES[rolActual()];
}

document.addEventListener('DOMContentLoaded', function () {
  barraAviso();
  armarMenu();
  filtrarPorRol();
  rotularSesion();
});
