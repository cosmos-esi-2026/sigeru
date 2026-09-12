# SiGeRU — Sistema de Gestión de Residuos Urbanos

Proyecto de Pasaje de Grado 2026 — Escuela Superior de Informática (ESI), CETP/UTU.
Equipo **COSMOS**, grupo 3°MG.

Sistema de gestión de residuos urbanos para una intendencia: registro centralizado de
contenedores, camiones e incidencias, con seguimiento de estado, asignación de
responsables y reportes.

## Aplicaciones

| Carpeta | Qué es | Estado |
| --- | --- | --- |
| `landing/` | Página pública y formulario de solicitud de registro | Funcionando |
| `frontend-usuarios/` | Interfaz de gestión: login, panel, usuarios, contenedores, camiones, centros de acopio, maquinaria y solicitudes | Funcionando |
| `api-usuarios/` | API de usuarios, login, sesión y solicitudes de registro | Funcionando, sobre MySQL |
| `api-gestion/` | API de contenedores, camiones, centros de acopio, maquinaria, tipos de residuo y zonas | Funcionando, sobre MySQL |
| `database/` | Modelo físico: DDL de creación, datos de prueba y volcado de la estructura | 15 tablas |
| `tests/` | Pruebas de las dos APIs por HTTP, con lanzador propio | 47 pruebas |

Los datos **se persisten en MySQL** desde el 09/09/2026: las dos APIs abren su conexión
por PDO y todas las consultas van preparadas. La sesión es de servidor (cookie `httponly`)
y el rol se relee de la base en cada petición.

## Documentación por materia

| Carpeta | Unidad curricular |
| --- | --- |
| `ingenieria-software/` | Ingeniería de Software |
| `proyecto-utu-lab/` | Proyecto UTU LAB (incluye el prototipo Low-Fi navegable) |
| `Emprendedurismo y Gestion/` | Emprendedurismo y Gestión |
| `administracion-sistemas-operativos/` | Administración de Sistemas Operativos |
| `docs/` | Notas técnicas de decisiones de implementación |

## Cómo levantarlo desde cero

Hay dos maneras de correr SiGeRU. Las dos levantan **lo mismo** (Apache + PHP 8.2 + MySQL
con las 15 tablas y los datos de prueba) y se usan igual desde el navegador.

| | Con el pendrive (XAMPP portable) | Con Docker |
| --- | --- | --- |
| Qué necesita la PC | Nada. Todo corre desde el pendrive | Docker Desktop instalado |
| Dirección | `http://localhost/sigeru/landing/` | `http://localhost:8080/sigeru/landing/` |
| Para qué sirve | Desarrollar y presentar | Instalarlo en cualquier máquina o servidor |

Usuarios de prueba (todos con contraseña `123456`): `admin@sigeru.uy` (Administrador),
`operario@sigeru.uy`, `cuadrilla@sigeru.uy` y `vecino@sigeru.uy`.

### Camino 1 — Con el pendrive (no instala nada)

1. Enchufar el pendrive y abrir la carpeta `SIGERU 2026` → `herramientas` → `xampp`.
2. Doble clic en **`iniciar-xampp.cmd`**. Se abren dos ventanas negras: una es Apache (el
   servidor web) y la otra MySQL (la base de datos). **No cerrarlas**: mientras están
   abiertas, el sistema está encendido.
3. Abrir el navegador y entrar a **`http://localhost/sigeru/landing/`**. Es la página
   pública. El botón de ingresar lleva al login.
4. Entrar con `admin@sigeru.uy` / `123456`. Aparece el panel de control con los
   contenedores, camiones, centros, maquinaria, usuarios y solicitudes.
5. Para mostrar que las pruebas pasan: doble clic en **`sigeru/tests/correr-tests.cmd`**.
   Corre 47 pruebas contra las dos APIs por HTTP y termina con `Pasaron: 47 Fallaron: 0`.
   Los registros que crea los borra solos.
6. Para apagar: doble clic en **`detener-xampp.cmd`**, esperar a que diga `Listo`, y recién
   después expulsar el pendrive. El pendrive es exFAT y no tiene journaling: si se saca con
   los servidores corriendo, la base se puede corromper.

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

### Camino 2 — Con Docker (cualquier máquina, sin el pendrive)

1. Tener **Docker Desktop** instalado y abierto. Esperar a que abajo a la izquierda diga
   *Engine running*.
2. Conseguir el proyecto en el **disco de la PC** (no en el pendrive, ver más abajo):
   `git clone https://github.com/cosmos-esi-2026/sigeru.git`, o copiar la carpeta
   `sigeru` del pendrive a, por ejemplo, `C:\sigeru`.
3. Abrir una terminal (PowerShell o la que sea) **dentro de la carpeta `sigeru/docker`** y
   correr:

       docker compose up --build

   La primera vez baja dos imágenes (unos 600 MB) y MySQL crea las 15 tablas y carga los
   datos: puede tardar varios minutos en una PC lenta. Está listo cuando la terminal deja de
   moverse y muestra líneas de Apache y MySQL esperando.
4. Abrir **`http://localhost:8080/sigeru/landing/`** y entrar con el mismo usuario.
5. Para las pruebas, en **otra** terminal, en la misma carpeta:

       docker compose exec web php /var/www/html/sigeru/tests/correr-tests.php

   Corre las 47 pruebas adentro del contenedor y termina con `Todo verde.`
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
