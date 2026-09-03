# Rediseño del frontend de SiGeRU

Fecha: 2026-07-30
Estado: implementado y verificado. Faltan las dos fotografías de la sección 7.

## 1. Objetivo

Reemplazar el diseño actual del frontend por uno nuevo, con otra distribución de
pantalla y otra identidad visual, sin modificar el backend.

El diseño de hoy tiene cinco problemas concretos que este rediseño resuelve:

1. Los formularios de alta ocupan la parte superior de la página y empujan las
   tablas hacia abajo. Al entrar a `contenedores.html` lo primero que se ve es un
   formulario vacío, no los datos.
2. El dashboard no muestra información: es un saludo y tres enlaces que repiten
   los de la barra de navegación.
3. La barra de navegación no indica en qué página está el usuario y se amontona
   en pantallas chicas.
4. Las tablas no tienen buscador ni acciones por fila.
5. No hay ninguna imagen, logo ni favicon.

## 2. Alcance

Se rediseñan siete páginas:

| Página | Ruta |
| --- | --- |
| Landing | `landing/index.html` |
| Inicio de sesión | `frontend-usuarios/login.html` |
| Registro | `frontend-usuarios/registro.html` |
| Panel | `frontend-usuarios/dashboard.html` |
| Usuarios | `frontend-usuarios/usuarios.html` |
| Contenedores | `frontend-usuarios/contenedores.html` |
| Camiones | `frontend-usuarios/camiones.html` |

### Fuera de alcance

No se modifica nada de `api-usuarios/`, `api-gestion/` ni `database/`. Tampoco
se toca la junction de XAMPP ni la configuración de Apache. Si un cambio del
rediseño rompe una llamada a la API, es un error a corregir dentro del
rediseño, no una ampliación del alcance.

## 3. Tecnología

| Pieza | Decisión |
| --- | --- |
| Base de componentes | Bootstrap 5.3.3, archivo descargado al repositorio |
| Estilos propios | `styles.css` reescrito, cargado después de Bootstrap |
| Iconografía | SVG escrito directamente en el HTML |
| Tipografía | Fuente del sistema: `system-ui, "Segoe UI", Roboto, sans-serif` |
| JavaScript | Vanilla, más el bundle de Bootstrap para el modal |

**Bootstrap se usa únicamente para el componente de modal**, y por eso se carga
solo en las tres pantallas que lo tienen: `usuarios.html`, `contenedores.html` y
`camiones.html`. El panel, el login, el registro y la landing no lo cargan.

Esto es un cambio respecto a la intención original, que era usarlo también para
el sidebar colapsable y la grilla. Al implementarlo, el layout de sidebar más
contenido resultó ser cuatro reglas de CSS Grid propias, y el colapso en
pantallas chicas unas treinta líneas en `sidebar.js`; meter la librería en el
medio agregaba más complejidad de la que sacaba. Queda anotado porque cambia
la respuesta a "para qué usaron Bootstrap": para el modal, no para el diseño.

Todo lo visual lo define `styles.css`, que se carga después de Bootstrap y pisa
sus colores.

### Iconografía sin fuente de iconos

Se descartó Bootstrap Icons. Los ocho iconos del sistema (inicio, usuarios,
contenedores, camiones, menú, buscar, agregar, salir) van como SVG escritos en
el HTML. Bootstrap Icons hubiera obligado a descargar además los archivos de
fuente `.woff2`, unos 130 KB, y a depender de que carguen bien. Con SVG en el
HTML no hay ningún archivo que cargar.

El costo es que el marcado de los iconos se repite en las cuatro pantallas del
panel, igual que el resto del sidebar.

### Sin CDN

Bootstrap, Bootstrap Icons y las imágenes se guardan dentro del repositorio y se
enlazan con rutas relativas. Nada se carga desde un servidor externo: si el día
de la defensa la conexión falla, el sistema tiene que verse igual.

Por el mismo motivo se descarta descargar una tipografía web. La fuente del
sistema da Segoe UI en Windows, que es una mejora clara sobre Arial y no agrega
ningún archivo ni dependencia.

## 4. Identidad visual

| Rol | Color |
| --- | --- |
| Fondo del sidebar | `#1b3a2b` |
| Fondo del contenido | `#fafcfb` |
| Fondo de tarjetas | `#ffffff` |
| Borde de tarjetas | `#e6ece9` |
| Acento y botones de acción | `#97c459`, con texto `#173404` |
| Texto principal | `#1b3a2b` |
| Texto secundario | `#6b7c76` |
| Texto sobre el sidebar | `#ffffff` activo, `#a8c4b3` inactivo |

Colores de estado, usados en las pastillas de las tablas y en los números de las
métricas:

| Estado | Texto | Fondo de pastilla |
| --- | --- | --- |
| Libre / operativo / activo | `#3b6d11` | `#eaf3de` |
| Lleno / fuera de servicio | `#a32d2d` | `#fcebeb` |
| Mantenimiento / en ruta | `#854f0b` | `#faeeda` |
| Retirado / inactivo | `#6b7c76` | `#eef2f0` |

Todos estos colores se declaran como variables CSS en `:root`, siguiendo la
convención que ya usa el `styles.css` actual.

## 5. Estructura de archivos

```
frontend-usuarios/
  css/
    bootstrap.min.css        (descargado, 232 KB)
    styles.css               (reescrito)
  js/
    bootstrap.bundle.min.js  (descargado, 80 KB)
    api.js                   (sin cambios)
    auth.js                  (sin cambios)
    sidebar.js               (nuevo)
    tabla.js                 (nuevo)
    dashboard.js             (nuevo)
    usuarios.js              (adaptado al modal y al buscador)
    contenedores.js          (adaptado al modal y al buscador)
    camiones.js              (adaptado al modal y al buscador)
  img/
    logo.svg                 (hecho a mano, sirve tambien de favicon)
    login.jpg                (a descargar)
landing/
  css/styles.css             (reescrito)
  js/cabecera.js             (nuevo)
  js/main.js                 (sin cambios)
  img/hero.jpg               (a descargar)
```

`api.js`, `auth.js` y `landing/js/main.js` no se modifican: el rediseño no
cambia cómo se habla con las APIs.

`tabla.js` concentra lo que comparten las tres pantallas de listado — el
buscador, el cierre del modal y las filas de aviso — para no escribir la misma
lógica tres veces. `dashboard.js` también usa sus filas de aviso.

No hay archivo de favicon aparte: `logo.svg` cumple las dos funciones.

La landing no usa Bootstrap. No tiene modal ni menú colapsable, así que no
justifica cargar la librería: mantiene su propio `styles.css`, como hoy. Los
iconos de sus tarjetas van como SVG escritos a mano en el HTML.

## 6. Diseño de las pantallas

### 6.1 Login y registro

Dos columnas de mitad y mitad.

- Izquierda: la fotografía `login.jpg` cubriendo toda la altura, con una capa
  verde oscuro semitransparente encima y el logo centrado.
- Derecha: fondo blanco, el formulario centrado vertical y horizontalmente, con
  un ancho máximo de 400px.

Por debajo de 768px de ancho la columna de la fotografía se oculta y el
formulario ocupa la pantalla completa.

### 6.2 Estructura de las páginas internas

Las cuatro páginas con sesión iniciada comparten la misma estructura.

**Sidebar**, fijo a la izquierda, 240px de ancho, fondo `#1b3a2b`:

- Arriba: logo y la palabra SiGeRU.
- Al medio: cuatro enlaces con icono — Inicio, Usuarios, Contenedores,
  Camiones. El de la página actual lleva fondo `#97c459` y texto `#173404`.
- Abajo: nombre del usuario, su rol, y el enlace de cerrar sesión.

**Área de contenido**, a la derecha, fondo `#fafcfb`:

- Encabezado con el título de la sección.
- El contenido propio de cada página.

Por debajo de 992px el sidebar sale de la pantalla y se abre con un botón
hamburguesa que aparece en el encabezado. Esa lógica vive en `sidebar.js`.

El marcado del sidebar se repite en las cuatro páginas. Es duplicación
deliberada: no hay motor de plantillas del lado del cliente en este proyecto, y
cargarlo por JavaScript haría parpadear el menú en cada carga.

### 6.3 Panel

Cuatro tarjetas de métricas en una fila, cada una con una etiqueta chica arriba
y el número en grande abajo:

| Métrica | Origen |
| --- | --- |
| Contenedores | largo de `GET /contenedores` |
| Contenedores llenos | contar `estado === "lleno"` |
| Camiones operativos | contar `estado === "operativo"` |
| Usuarios registrados | largo de `GET /usuarios` |

Los cuatro números salen de los tres endpoints de listado que ya existen; el
conteo se hace en `dashboard.js` sobre la respuesta.

Se descartó la métrica "Solicitudes pendientes" porque `api-usuarios` expone
`POST /solicitudes` pero no un `GET` que las liste. Agregarlo hubiera implicado
tocar el backend, que está fuera de alcance.

Debajo de las métricas, una tabla con los contenedores en estado `lleno`, que
son los que requieren acción.

### 6.4 Usuarios, contenedores y camiones

Las tres páginas comparten la misma estructura.

- Encabezado con el título, un campo de búsqueda a la izquierda y el botón
  `+ Nuevo` a la derecha.
- La tabla ocupa el resto del ancho, dentro de una tarjeta blanca.
- La columna de estado se muestra como pastilla de color, con los colores de la
  sección 4.
- El formulario de alta pasa a un modal de Bootstrap que abre el botón
  `+ Nuevo`. Deja de estar siempre visible en la página.
- La búsqueda filtra las filas ya cargadas en el navegador, comparando el texto
  escrito contra el contenido de la fila. No genera pedidos a la API.
- Si la lista viene vacía, en lugar de una tabla sin filas se muestra la
  ilustración `vacio.svg`, un texto corto y el botón para dar de alta el primer
  registro.

### 6.5 Landing

La landing conserva las secciones que ya tiene — cabecera, hero, "Qué es",
"Servicios", "Solicitar registro" y pie — y también sus anclas `#que-es`,
`#servicios` y `#registro`. Lo que cambia es cómo se ven:

- **Cabecera**: fondo transparente sobre el hero, se vuelve sólida `#1b3a2b` al
  hacer scroll. El botón "Ingresar" pasa al acento `#97c459`.
- **Hero**: `hero.jpg` de fondo con una capa verde oscuro encima, para que el
  texto blanco se lea. Hoy es un bloque de color plano.
- **Servicios**: las tres tarjetas suman un icono SVG arriba del título y pasan
  a la tarjeta blanca con borde `#e6ece9`.
- **Registro**: el formulario va dentro de una tarjeta blanca centrada, con los
  campos en dos columnas en pantallas anchas y una sola en celular.

El formulario sigue enviando a `POST /solicitudes` desde `main.js`, sin cambios.

## 7. Imágenes

Quedan **dos** fotografías por descargar. Las carpetas ya existen y el HTML ya
apunta a esas rutas: basta con dejar el archivo con ese nombre exacto.

| Archivo | Contenido | Fuente sugerida |
| --- | --- | --- |
| `frontend-usuarios/img/login.jpg` | Camión de recolección o contenedores urbanos, apaisada | Pexels, Unsplash |
| `landing/img/hero.jpg` | Ciudad limpia o reciclaje, apaisada | Pexels, Unsplash |

Se bajan a un ancho máximo de 1600px para que ninguna supere unos cientos de
kilobytes. Ambas fuentes permiten uso gratuito sin atribución obligatoria; de
todas formas conviene anotar de dónde salió cada una, por si el tribunal
pregunta por las licencias.

**Mientras no estén, las pantallas se ven bien igual**: tanto el login como el
hero declaran el verde `#1b3a2b` como color de fondo, así que en lugar de una
imagen rota queda un bloque de color liso.

Ya no hace falta descargar la ilustración de estado vacío que preveía la versión
anterior de este documento: el icono que acompaña a "todavía no hay datos
cargados" se dibuja como SVG desde `tabla.js`.

## 8. Verificación

Ejecutada el 2026-07-30 sobre Apache en `http://localhost:8012/sigeru/`,
manejando el navegador de verdad.

| # | Qué se probó | Resultado |
| --- | --- | --- |
| 1 | Las siete páginas cargan sin errores de consola | Sin errores en ninguna |
| 2 | Login con `admin@sigeru.uy` | Redirige al panel; el sidebar muestra "Ana Perez / Administrador" |
| 3 | Las cuatro métricas del panel | 3 contenedores, 1 lleno, 1 camión operativo, 3 usuarios — coinciden con los listados |
| 4 | Los tres listados leen de las APIs | 3 filas con datos reales en cada uno |
| 5 | Alta desde el modal | Probada en las tres pantallas: el modal se cierra, el fondo se limpia, el formulario queda vacío y aparece el aviso |
| 6 | Buscador | 3 filas → 1 al filtrar "lleno" → 0 sin coincidencias → 3 al vaciarlo |
| 7 | Sidebar a 375px | Cerrado en `left: -240px`, abierto en `0`; cierra al tocar el fondo; sin scroll horizontal |
| 8 | Nada se carga desde internet | Ninguna referencia `http(s)://` en el `src`, `href` o `url()` de las siete páginas |
| 9 | Solicitud de registro desde la landing | Responde "Solicitud enviada correctamente" y limpia el formulario |

Dos aclaraciones sobre el punto 5. La fila nueva **no** aparece en la tabla
después del alta, y eso no es un defecto del rediseño: las APIs guardan los
datos en un arreglo en memoria, que PHP reinicia en cada petición. Va a
funcionar cuando se conecte MySQL. Y el cierre del modal depende de que la
animación de apertura haya terminado, que es el comportamiento normal de
Bootstrap; en el uso real nadie llega a enviar el formulario en los 150 ms que
dura.

## 9. Decisiones tomadas

| Decisión | Motivo |
| --- | --- |
| Bootstrap solo para el modal | Es la única pieza que salía cara a mano; el resto del layout terminó siendo CSS propio |
| Bootstrap solo en las tres pantallas con modal | Las otras cuatro no lo necesitan y cargan menos |
| Archivos locales en vez de CDN | Sin conexión el día de la defensa, el sistema debe verse igual |
| Iconos SVG en el HTML en vez de Bootstrap Icons | Evita descargar 130 KB de archivos de fuente y un punto más de fallo |
| Fuente del sistema en vez de Inter | Mejora clara sobre Arial sin agregar dependencias |
| Sidebar oscuro con contenido claro | Es el patrón habitual de los paneles de gestión y da un lugar fijo al logo |
| Formulario de alta en modal | Las tablas pasan a ser lo primero que se ve |
| "Usuarios registrados" como cuarta métrica | Es la única de las candidatas que tiene endpoint disponible |
| Marcado del sidebar repetido en cada página | No hay motor de plantillas; cargarlo por JS haría parpadear el menú |
| El enlace activo se marca en el HTML, no por JS | La página se ve bien incluso antes de que corra el JavaScript |
| `logo.svg` hecho a mano en vez de descargado | Es un badge simple; además sirve de favicon sin generar un PNG |
| Botón "Recargar" conservado | Los tres `.js` lo buscan por `id`; quitarlo los rompía. Pasó a ser un icono |
