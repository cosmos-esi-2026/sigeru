# Docker — cómo levantar SiGeRU sin XAMPP

El proyecto se desarrolla sobre **XAMPP portable** (todo corre desde el pendrive, sin
instalar nada). Estos archivos son la alternativa que pide la letra de Programación Full
Stack: levantar el sistema completo en cualquier máquina que tenga Docker, sin depender del
pendrive ni de configurar Apache y MySQL a mano.

**Probado el 11/09/2026** con Docker Desktop 29.7 / Compose 5.5 sobre WSL2, en una PC
modesta (Celeron N4500, 8 GB): `docker compose up --build` levanta todo, las rutas
principales responden y **los 47 tests pasan** corriendo dentro del contenedor web. El
workflow `.github/workflows/docker.yml` repite exactamente esa verificación en GitHub
Actions cada vez que cambia algo del despliegue.

## Qué levanta

| Contenedor | Qué es | Puerto en el host |
|---|---|---|
| `sigeru-web` | Apache + PHP 8.2 sirviendo los frontends y las dos APIs | **8080** |
| `sigeru-db` | MySQL 8 con el schema y los datos de prueba ya cargados | **3307** |

Los puertos son 8080 y 3307 a propósito: así **no chocan con XAMPP** (que usa 80 y 3306) y se
pueden tener los dos levantados al mismo tiempo para comparar.

## Cómo se usa

```
cd sigeru/docker
docker compose up --build
```

Cuando termine de arrancar:

- Landing: <http://localhost:8080/sigeru/landing/>
- Panel: <http://localhost:8080/sigeru/frontend-usuarios/login.html>
- API de usuarios: <http://localhost:8080/sigeru/api-usuarios/>
- API de gestión: <http://localhost:8080/sigeru/api-gestion/>

Usuarios de prueba (todos con contraseña `123456`): `admin@sigeru.uy` (Administrador),
`operario@sigeru.uy`, `cuadrilla@sigeru.uy`, `vecino@sigeru.uy`.

Para apagar: `Ctrl + C` y después `docker compose down`.
Para borrar también la base y volver a cargar los datos desde cero:
`docker compose down -v`.

La primera vez tarda: MySQL inicializa sus archivos y carga el schema y los datos de
prueba, y en una PC lenta eso lleva más de 2 minutos. `web` espera a que la base esté
sana antes de arrancar, así que si la landing todavía no responde, es eso.

## Correr los tests contra los contenedores

El runner `tests/correr-tests.php` apunta a `http://localhost/sigeru`, que **dentro del
contenedor web** es Apache en el puerto 80. Así que se corre ahí adentro, sin tocarlo:

```
docker compose exec web php /var/www/html/sigeru/tests/correr-tests.php
```

Para limpiar lo que crea usa `config/database.php`, que lee las variables `SIGERU_DB_*`
del compose. (Si el comando se corre desde Git Bash, anteponer `MSYS_NO_PATHCONV=1`:
si no, Git Bash reescribe `/var/www/...` como una ruta de Windows.)

## Si el proyecto está en un pendrive (Windows)

Docker Desktop sobre WSL2 **solo monta las unidades fijas** (`C:`) dentro de su VM. Una
unidad extraíble no se ve, y Docker, en vez de fallar, crea carpetas vacías en el lugar de
cada montaje: los `.sql` entran como directorios (`Can't initialize batch_readline`) y el
contenedor web arranca sin el proyecto. Es la limitación de WSL2 con unidades
extraíbles, no un error de estos archivos.

Se resuelve montando la unidad a mano en la VM, **antes** del `up`, con la letra que
tenga el pendrive en esa PC (acá es `D:`, en otra puede ser `E:` o `F:`):

```
wsl -d docker-desktop mount -t drvfs D: /mnt/host/d
```

Hay que repetirlo cada vez que se reinicia Docker Desktop. Si se corrió el `up` sin
esto, borrar el volumen antes de volver a intentar (`docker compose down -v`): MySQL
solo carga el schema la primera vez que arranca.

## Tres decisiones que conviene poder defender

**1. Un solo contenedor web, no uno por API.**
La primera versión de estos archivos (la del rediseño de julio) levantaba un contenedor por
cada API, cada uno en su puerto. Eso **rompería el login**: la sesión del servidor viaja en
una cookie con `path=/`, y funciona porque el frontend y las dos APIs comparten origen. Con
tres puertos distintos serían tres orígenes distintos y la cookie no se compartiría. El
despliegue tiene que parecerse a cómo está pensado el sistema.

**2. El proyecto se monta bajo `/sigeru`, no en la raíz.**
Así las rutas que usa el frontend (`/sigeru/api-usuarios`, `/sigeru/api-gestion`) son
**exactamente las mismas** que bajo XAMPP. Si se montara en la raíz habría que mantener dos
juegos de URLs, y tarde o temprano uno de los dos queda desactualizado.

**3. `db` tiene healthcheck y `web` espera a que esté sano.**
MySQL tarda varios segundos en aceptar conexiones la primera vez. Sin el healthcheck, PHP
intenta conectarse antes de tiempo y la primera carga falla.

## Cómo se conecta a la base

`config/database.php` lee variables de entorno (`SIGERU_DB_HOST`, `SIGERU_DB_PORT`,
`SIGERU_DB_NAME`, `SIGERU_DB_USER`, `SIGERU_DB_PASS`) y, **si no están, usa los valores de
XAMPP** (`127.0.0.1`, root sin contraseña). El `docker-compose.yml` define esas variables
apuntando al contenedor `db`.

Por eso el mismo archivo sirve en los dos entornos sin editarlo: no hay una versión "de
Docker" y otra "de XAMPP" que se puedan desincronizar.
