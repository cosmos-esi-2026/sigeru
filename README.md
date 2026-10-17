# SiGeRU — Sistema de Gestión de Residuos Urbanos

Proyecto de Pasaje de Grado 2026 — Escuela Superior de Informática (ESI), CETP/UTU.
Equipo **COSMOS**, grupo 3°MG.

Sistema de gestión de residuos urbanos para una intendencia: registro centralizado de
contenedores, camiones e incidencias, con seguimiento de estado, asignación de
responsables y reportes.

## Aplicaciones

| Carpeta | Qué es | Estado |
| --- | --- | --- |
| `landing/` | Página pública: cifras reales del sistema, mapa público de contenedores y centros de acopio, cómo informar un problema y enlace al registro | Funcionando |
| `frontend-usuarios/` | Interfaz de gestión: login, panel, mapa, usuarios, contenedores (con su ficha e historial), camiones, recolecciones («Mis tareas» de la cuadrilla), centros de acopio, maquinaria, solicitudes, incidencias por rol y reportes (panel por urgencias, informe del período, exportación a CSV) | Funcionando |
| `api-usuarios/` | API de usuarios, login, sesión y solicitudes de registro | Funcionando, sobre MySQL |
| `api-gestion/` | API de contenedores, centros de acopio, maquinaria, tipos de residuo, zonas e incidencias (informar, asignar, avance, resolver; historial de solo agregado) y estadísticas (informe de un período, panel por urgencias) | Funcionando, sobre MySQL |
| `api-recoleccion/` | API de camiones, cuadrillas, rutas y recolecciones (planificar, iniciar, registrar vaciados con peso y volumen, finalizar, cancelar) y estadísticas de lo recolectado | Funcionando, sobre MySQL (desde el 14/10/2026) |
| `database/` | Modelo físico: DDL de creación, datos de prueba, volcado de la estructura y migraciones del esquema (`migrar.php`) | 16 tablas del modelo (desde el 12/10/2026) |
| `tests/` | Pruebas de las dos APIs por HTTP (`correr-tests`) y pruebas unitarias sin base (`unitarias.php`) | 590 por HTTP + 410 unitarias (17/10/2026) |

Los datos **se persisten en MySQL** desde el 09/09/2026: las dos APIs abren su conexión
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

## Cómo levantarlo desde cero

Hay dos maneras de correr SiGeRU. Las dos levantan **lo mismo** (Apache + PHP 8.2 + MySQL
con las tablas del modelo y los datos de prueba) y se usan igual desde el navegador.

| | Con el pendrive (XAMPP portable) | Con Docker |
| --- | --- | --- |
| Qué necesita la PC | Nada. Todo corre desde el pendrive | Docker Desktop instalado |
| Dirección | `http://localhost/sigeru/landing/` | `http://localhost:8080/sigeru/landing/` |
| Para qué sirve | Desarrollar y presentar | Instalarlo en cualquier máquina o servidor |

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
4. Entrar con `admin@sigeru.uy` / `sigeru2026`. Aparece el panel de control con los
   contenedores, camiones, centros, maquinaria, usuarios y solicitudes.
5. Para mostrar que las pruebas pasan: doble clic en **`sigeru/tests/correr-tests.cmd`**.
   Corre las pruebas contra las APIs por HTTP y termina con `Fallaron: 0` (al 17/10/2026
   son 590 si Apache arrancó con `SIGERU_MAIL_MODE=archivo`, ver «Correo»; con el arranque común faltan las 2 pruebas de enlaces por correo y el
   resumen avisa de las 2 pruebas de enlaces por correo que omitió). Los registros que crea los borra solos.
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

### Camino 2 — Con Docker (cualquier máquina, sin el pendrive)

1. Tener **Docker Desktop** instalado y abierto. Esperar a que abajo a la izquierda diga
   *Engine running*.
2. Conseguir el proyecto en el **disco de la PC** (no en el pendrive, ver más abajo):
   `git clone https://github.com/cosmos-esi-2026/sigeru.git`, o copiar la carpeta
   `sigeru` del pendrive a, por ejemplo, `C:\sigeru`.
3. Abrir una terminal (PowerShell o la que sea) **dentro de la carpeta `sigeru/docker`** y
   correr:

       docker compose up --build

   La primera vez baja dos imágenes (unos 600 MB) y MySQL crea las tablas y carga los
   datos: puede tardar varios minutos en una PC lenta. Está listo cuando la terminal deja de
   moverse y muestra líneas de Apache y MySQL esperando.
4. Abrir **`http://localhost:8080/sigeru/landing/`** y entrar con el mismo usuario.
5. Para las pruebas, en **otra** terminal, en la misma carpeta:

       docker compose exec web php /var/www/html/sigeru/tests/correr-tests.php

   Corre las pruebas adentro del contenedor y termina con `Todo verde.`
6. Para apagar: `Ctrl + C` en la primera terminal y después `docker compose down`. Para
   borrar también la base y que la próxima vez cargue todo de cero: `docker compose down -v`.

**Si el proyecto está en un pendrive**: Docker Desktop no ve las unidades extraíbles y, en
vez de avisar, monta carpetas vacías. Antes del `up`, correr una vez
`wsl -d docker-desktop mount -t drvfs D: /mnt/host/d` (con la letra del pendrive). Está
explicado con detalle en `docker/LEEME.md`, junto con por qué el despliegue está armado
así (un solo contenedor web, montado bajo `/sigeru`, healthcheck en la base).

### Camino 3 — Con un XAMPP común, sin el pendrive ni Docker

Es la instalación manual, la que se haría en un servidor sin Docker: instalar XAMPP
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


## Correo para activar cuentas y renovar contraseñas

El vecino se registra en `landing/` y elige su contraseña sin aprobación. Si un
administrador crea una cuenta o aprueba una solicitud antigua, SIGERU envía al
correo de esa persona un enlace de un solo uso, válido durante una hora. La cuenta
nueva queda pendiente hasta que la persona crea su contraseña. En **Usuarios**,
**Enviar acceso** permite renovar la contraseña de una cuenta existente sin
pedir al administrador que la conozca o la comunique.

Configurar estas variables de entorno en el proceso de Apache/PHP antes de
crear o aprobar cuentas; no guardar las credenciales SMTP en el repositorio:

| Variable | Ejemplo / uso |
| --- | --- |
| `SIGERU_SMTP_URL` | Con Gmail: `smtps://smtp.gmail.com:465` (SSL) o `smtp://smtp.gmail.com:587` (STARTTLS) |
| `SIGERU_SMTP_USER` | Usuario SMTP |
| `SIGERU_SMTP_PASS` | Clave o contraseña de aplicación SMTP |
| `SIGERU_MAIL_FROM` | Remitente autorizado, por ejemplo `acceso@ejemplo.org` |
| `SIGERU_PUBLIC_URL` | URL externa de la aplicación hasta `/sigeru`, con HTTPS; en pruebas locales, `http://localhost/sigeru` o `http://localhost:8080/sigeru` |

Para Gmail, usar la dirección completa en `SIGERU_SMTP_USER` y
`SIGERU_MAIL_FROM`. Se necesita la verificación en dos pasos y una contraseña
de aplicación de Google, no la contraseña normal de la cuenta. El ejemplo
`docker/.env.example` se puede copiar a `docker/.env` y completar sin subir
este último a Git. La configuración SMTP se valida antes de crear la cuenta.

En Docker Compose, declarar las variables en el entorno o en un archivo
`docker/.env` local que no se suba a Git.

**Para probar sin correo real** (las pruebas automáticas lo necesitan): arrancar Apache con
`SIGERU_MAIL_MODE=archivo`. El mensaje se escribe en `tmp/correo/` en vez de enviarse, y solo
funciona si `SIGERU_PUBLIC_URL` es `localhost` (o está vacía). Ver
`ciberseguridad/ampliacion-acceso-por-enlace-3ra.md`.

Para la defensa con XAMPP portable:

1. Detener el XAMPP anterior con `detener-xampp.cmd`.
2. Desde la terminal de PowerShell, ejecutar el lanzador:

   ```powershell
   & 'D:\SIGERU 2026\sigeru\iniciar-xampp-con-correo.cmd'
   ```

   El lanzador permite ejecutar el script solo en ese proceso, sin cambiar
   la política global de PowerShell.
3. Ingresar la contraseña de aplicación de Gmail cuando el script la pida.
   El script no la guarda en archivos. El remitente ya está configurado como
   `cosmoscorporation2026@gmail.com`.
4. Abrir el correo recibido en esa misma computadora. El enlace usa
   `http://localhost/sigeru` y no funciona desde un teléfono u otra PC.

La base existente necesita las migraciones `004-enlace-clave.sql` y
`005-estado-pendiente.sql` mediante `database/migrar.php`; una base nueva ya
incluye los cambios en `database/schema.sql`. Sin SMTP configurado o si el
envío falla, la creación o aprobación se cancela y se informa el error. Una
cuenta dada de baja no puede reactivarse con un enlace.
