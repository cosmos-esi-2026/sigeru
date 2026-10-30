# SiGeRU — Sistema de Gestión de Residuos Urbanos

Proyecto de Pasaje de Grado 2026 — Escuela Superior de Informática (ESI), CETP/UTU.
Equipo **COSMOS**, grupo 3°MG.

Sistema de gestión de residuos urbanos para una intendencia: registro centralizado de
contenedores, camiones e incidencias, con seguimiento de estado, asignación de
responsables y reportes.

## Aplicaciones

| Carpeta | Qué es | Estado |
| --- | --- | --- |
| `landing/` | Página pública: cifras reales del sistema, mapa público de contenedores y centros de acopio, cómo informar un problema, enlace al registro y páginas de error propias | Funcionando |
| `frontend-usuarios/` | Interfaz de gestión: login, panel, mapa, usuarios, contenedores (con su ficha e historial), camiones, rutas, recolecciones («Mis tareas» de la cuadrilla), centros de acopio, maquinaria, solicitudes, incidencias por rol (con foto), reportes (panel por urgencias, informe del período y exportación a CSV), recepciones y stock del centro (Operario), notificaciones (la campanita), armado de rutas y asignación a cuadrillas, auditoría y modo oscuro | Funcionando |
| `api-usuarios/` | API de usuarios, login, sesión, solicitudes de registro, registro de vecinos, contraseñas definidas por el administrador y auditoría | Funcionando, sobre MySQL |
| `api-gestion/` | API de contenedores, centros de acopio, maquinaria, tipos de residuo, zonas e incidencias (informar, asignar, avance, resolver; historial de solo agregado) y estadísticas (informe de un período, panel por urgencias) | Funcionando, sobre MySQL |
| `api-recoleccion/` | API de camiones, cuadrillas, rutas y recolecciones (planificar, iniciar, registrar vaciados con peso y volumen, finalizar, cancelar) y estadísticas de lo recolectado | Funcionando, sobre MySQL (desde el 14/10/2026) |
| `database/` | Modelo físico: DDL de creación, datos de prueba, volcado de la estructura y migraciones del esquema (`migrar.php`) | 21 tablas del modelo y 27 con las de soporte (migraciones 001 a 011, 014 y 017 a 020; huella `232ba1b2758a7a3e` con el seed de pruebas, `2b25a0d53cf71e5d` con el de defensa) |
| `tests/` | Pruebas de las tres APIs por HTTP (`correr-tests`) y pruebas unitarias sin base (`unitarias.php`) | 1000 por HTTP + 671 unitarias (25/10/2026) |

Los datos **se persisten en MySQL** desde el 09/09/2026: las tres APIs abren su conexión
por PDO y todas las consultas van preparadas. La sesión es de servidor (cookie `httponly`)
y el rol se relee de la base en cada petición.

Las aplicaciones son independientes: `landing/` y `frontend-usuarios/` no se cargan archivos
entre sí. Lo que comparten (`css/base.css` con los colores, tipografías y botones,
`js/formularios.js`, `img/logo.svg` y la carpeta `fonts/`, que trae las fuentes con sus licencias
para funcionar sin internet) son **copias idénticas**: `frontend-usuarios/` es el origen y
`tests/unitarias.php` falla si las dos copias difieren. El menú lateral de las pantallas de
gestión vive en un solo lugar, `frontend-usuarios/js/shell.js`.

Las tres APIs también son independientes y comparten la base. Lo común (`core/Auth.php` y
`core/Response.php` en las tres; `config/database.php`, `core/ErrorDeNegocio.php` y el modelo
`Cuadrilla.php` entre `api-gestion/` y `api-recoleccion/`, porque la asignación de incidencias
comprueba si una cuadrilla está libre) son copias idénticas que vigila `tests/unitarias.php`.

## Documentación por materia

| Carpeta | Unidad curricular |
| --- | --- |
| `ingenieria-software/` | Ingeniería de Software |
| `proyecto-utu-lab/` | Proyecto UTU LAB (incluye el prototipo Low-Fi navegable) |
| `Emprendedurismo y Gestion/` | Emprendedurismo y Gestión |
| `administracion-sistemas-operativos/` | Administración de Sistemas Operativos |
| `ciberseguridad/` | Ciberseguridad |
| `programacion-full-stack/` | Programación Full Stack (una ampliación por cada mejora de la 3ra entrega y el guion de defensa) |
| `manuales/` | Manuales de uso de los cuatro roles |

## Cómo levantarlo desde cero

Hay dos maneras de correr SiGeRU: desde el pendrive (XAMPP portable, `http://localhost/sigeru/landing/`),
para desarrollar y presentar, o con un XAMPP común en otra máquina. Las dos levantan **lo mismo** (Apache + PHP 8.2 +
MySQL con las tablas del modelo y los datos de prueba) y se usan igual desde el navegador.

Usuarios de prueba (todos con contraseña `sigeru2026`): `admin@sigeru.uy` (Administrador),
`operario@sigeru.uy`, `cuadrilla@sigeru.uy` y `vecino@sigeru.uy`.

Cada rol ve su menú. Las **incidencias** se recorren con tres usuarios: el vecino entra a
«Informar un problema» (código del contenedor, tipo, descripción) y recibe el número `INC-NNNN`,
que después sigue en «Mis reclamos»; el administrador las ve en «Incidencias» (con filtros por
estado, zona y período), les cambia la prioridad, las asigna a una cuadrilla disponible, las
resuelve o las descarta; la cuadrilla las ve en «Mis incidencias», registra el avance y las
resuelve. Cada paso queda en el historial de la incidencia, que no se puede editar ni borrar.

Las **recolecciones** las planifica el administrador en «Recolecciones» (ruta, fecha, camión,
cuadrilla y centro de destino). La cuadrilla las ve en «Mis tareas»: inicia el recorrido (el
camión pasa a «en ruta»), registra cada contenedor vaciado con su peso y volumen, y lo finaliza
(el camión vuelve a «operativo»). El administrador puede cancelar una con su motivo.

El **mapa** (menú «Mapa», los cuatro roles) muestra los contenedores por estado, con filtros, y según el rol
los centros de acopio y las incidencias abiertas. El vecino puede tocar un contenedor e informar un problema
ahí. Usa Leaflet, guardado dentro del repo (`frontend-usuarios/vendor/leaflet/`); el fondo con las calles
viene de OpenStreetMap y, sin internet, el mapa sigue funcionando sobre fondo liso.

### Camino 1 — Con el pendrive (no instala nada)

1. Enchufar el pendrive y abrir la carpeta `SIGERU 2026` → `herramientas` → `xampp`.
2. Doble clic en **`iniciar-xampp.cmd`**. Se abren dos ventanas negras: una es Apache (el
   servidor web) y la otra MySQL (la base de datos). **No cerrarlas**: mientras están
   abiertas, el sistema está encendido.
3. Abrir el navegador y entrar a **`http://localhost/sigeru/landing/`**. Es la página
   pública. El botón de ingresar lleva al login.
4. Entrar con `admin@sigeru.uy` / `sigeru2026`. Aparece el inicio del administrador:
   indicadores y «Requiere atención» (lo urgente primero), con el menú a todo el sistema.
5. Para mostrar que las pruebas pasan: doble clic en **`sigeru/tests/correr-tests.cmd`**.
   Corre las pruebas contra las APIs por HTTP y termina con `Fallaron: 0` (al 30/10/2026
   son 995 y 1 omitida si Apache no arranca con el trazado en modo recta; el resumen avisa de lo que omite). Los registros que crea los borra solos.
6. Para apagar: doble clic en **`detener-xampp.cmd`**. MySQL tarda entre 10 y 15 segundos en
   terminar de escribir en el pendrive: el script espera a que termine y recién entonces dice
   `Listo. Ya se puede expulsar el pendrive.` Antes de ese mensaje **no** sacarlo. El pendrive
   es exFAT y no tiene journaling: si se saca con la base escribiendo, se corrompe (así se
   rompió la tabla `vaciado` el 06/10/2026).

La base de datos viaja adentro del pendrive (`herramientas/xampp/mysql/data/sigeru/`), así
que no hay que importar nada. Si se cambia de PC, el pendrive puede montar con otra letra
(`E:`, `F:`): no importa, los scripts calculan su propia ruta.

Si algo falla:

- **La página no carga** (`localhost` rechaza la conexión): Apache no arrancó, casi siempre
  porque otro programa usa el puerto 80 (otro XAMPP, IIS, Skype). Cerrarlo y volver a
  correr `iniciar-xampp.cmd`.
- **"No hay conexión con la base de datos"** en pantalla: MySQL no arrancó. Mirar qué dice
  su ventana negra. Si hubo un apagado brusco, suele alcanzar con `detener-xampp.cmd` y
  volver a iniciar.
- **La base quedó rota, o con datos que ya no sirven**: con MySQL encendido, doble clic en
  **`restaurar-base-demo.cmd`** (en `herramientas/xampp`). Pide confirmación, guarda antes
  una copia en `respaldo-bd/` y vuelve a crear la base con los datos de prueba.

### Camino 2 — Con un XAMPP común, sin el pendrive

Es la instalación manual, la que se haría en otro equipo: instalar XAMPP
(apachefriends.org), copiar la carpeta `sigeru` dentro de `htdocs`, abrir phpMyAdmin
(`http://localhost/phpmyadmin/`) e importar **primero** `database/schema.sql` y **después**
`database/datos_prueba.sql`. Con eso el sistema queda en `http://localhost/sigeru/landing/`,
igual que en el camino 1. Las APIs se conectan a `127.0.0.1` con `root` sin contraseña, que
es lo que XAMPP trae de fábrica; si el servidor tiene otros datos, se cambian con las
variables de entorno `SIGERU_DB_HOST`, `SIGERU_DB_PORT`, `SIGERU_DB_NAME`, `SIGERU_DB_USER`
y `SIGERU_DB_PASS`, sin tocar el código.

El prototipo Low-Fi de UTU LAB no necesita nada de esto: se abre con doble clic en
`proyecto-utu-lab/prototipo-lofi/index.html`.

## Equipo

Franco Fornos (coordinador) · Franco Astorino (subcoordinador) · Axel Delgado ·
Francisco Goycochea


## Cuentas y contraseñas (sin correo)

SiGeRU **no envía correo**: se sacó el 30/10/2026 junto con «Olvidé mi contraseña» (ver
`programacion-full-stack/ampliacion-sin-correo-3ra.md`). El vecino se registra solo desde la landing
(`frontend-usuarios/registro.html`) y elige su contraseña. Para el resto:

- **Alta por el administrador:** en **Usuarios**, «Nuevo usuario» pide una **contraseña inicial** (8 caracteres o más) y la cuenta
  queda activa; el administrador se la entrega a la persona en mano.
- **Solicitud de acceso** (Cuadrilla): al aprobarla, el administrador define también la contraseña inicial.
- **Contraseña perdida:** el administrador edita el usuario y escribe una nueva (campo opcional; vacío no la cambia).
- Los avisos del sistema son la campanita de las pantallas, no correo.

Una base existente se pone al día con `database/migrar.php`, que aplica solas las
migraciones que le falten; una base
nueva ya incluye todo en `database/schema.sql`.
