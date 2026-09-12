# Administración de Sistemas Operativos

Todo lo de la materia vive en esta carpeta. Sistema operativo elegido: **Ubuntu Server
26.04 LTS** (relevamiento de agosto).

## Qué hay

| Archivo / carpeta | Qué es | Entrega |
|---|---|---|
| `Relevamiento_Sistema_Operativo_SiGeRU.pdf` | Relevamiento de 7 sistemas operativos con matriz ponderada; elige Ubuntu Server 26.04 LTS y dimensiona los 3 servidores | 1ra (21/08) |
| `Administracion_SO_2da_Entrega_SiGeRU.pdf` | El documento de la 2da entrega: política de respaldo y alta disponibilidad, rutinas de backup con su prueba, script de administración modular, y el plan para lo que se debe. Generado por `generadores/doc15_admin_so.py` | **2da** |
| `respaldo-y-alta-disponibilidad.md` | La política: qué se protege, RPO/RTO, regla 3-2-1, calendario, retención 14/8/12, medios, prueba de restauración mensual, plan de alta disponibilidad | **2da** |
| `scripts/respaldo/` | `backup.sh` (respaldo diario), `restaurar.sh`, `backup.conf`, `cron/sigeru-backup` (03:00) e `instalar.sh` | **2da** |
| `scripts/admin/` | `sigeru-admin.sh` (menú) + `lib/usuarios.sh`, `lib/red.sh`, `lib/respaldo.sh`, `lib/servicios.sh`, `lib/comun.sh` | **2da** |
| `pruebas/` | Transcripciones, sin editar, de la prueba de los scripts del 12/09 en un contenedor Ubuntu contra la base MySQL del Docker del proyecto | **2da** |
| `plan-de-instalacion.md` | Plan exacto para instalar el servidor y pagar los 4 ítems que se deben | **2da** (el plan) → 3ra (la ejecución) |

## Qué se entrega en la 2da y qué se debe

De los 8 ítems que la letra pide a la materia hasta acá (4 de la 1ra + 4 de la 2da):

| Ítem | Estado | Evidencia |
|---|---|---|
| Relevamiento del S.O. | ✔ | `Relevamiento_Sistema_Operativo_SiGeRU.pdf` |
| Medios de respaldo a largo plazo y alta disponibilidad | ✔ | `respaldo-y-alta-disponibilidad.md` y sección 3 del PDF |
| Rutinas de backup y sus scripts | ✔ | `scripts/respaldo/` + `pruebas/2026-09-12-prueba-respaldo.txt` (se hizo el respaldo, se borró la base, se restauró y los 47 tests pasaron) |
| Script de administración del servidor, v1 modular | ✔ | `scripts/admin/` + `pruebas/2026-09-12-prueba-admin.txt` |
| Configuración del servicio SSH | ✗ deuda de la 2da | `plan-de-instalacion.md`, sección 7 |
| Manual de instalación del S.O. servidor | ✗ deuda de la 1ra | `plan-de-instalacion.md`, sección 4 |
| Instalación y configuración de la red (estática) | ✗ deuda de la 1ra | `plan-de-instalacion.md`, sección 5 |
| Gestión y administración de paquetes | ✗ deuda de la 1ra | `plan-de-instalacion.md`, sección 6 |

Los cuatro que se deben necesitan el servidor instalado, que al 12/09 no existe. Se
entregan con la 3ra, siguiendo el plan. No se maquilla: el PDF lo dice en la primera
página.

## Cómo se corre cada script

Los scripts están pensados para **Ubuntu Server**, como root. Se probaron en un contenedor
`ubuntu` con `mysql-client`, `cron`, `iproute2` e `iputils-ping` instalados (ver
`pruebas/`). Todos son bash con `set -euo pipefail`, en español, con la cabecera que
explica qué hacen, cómo se corren y qué necesitan.

### Instalar en el servidor

```bash
cd administracion-sistemas-operativos/scripts/respaldo
sudo ./instalar.sh
```

Deja los scripts en `/opt/sigeru/`, crea `/backups` y el log, instala la tarea diaria de
las 03:00 en `/etc/cron.d/sigeru-backup` y el comando `sigeru-admin`. Después hay que
revisar `/opt/sigeru/respaldo/backup.conf` (usuario y clave de MySQL, ruta del código).

### Respaldo

```bash
sudo /opt/sigeru/respaldo/backup.sh                        # respaldo completo ahora
sudo /opt/sigeru/respaldo/restaurar.sh /backups/sigeru-20260912-0300.tar.gz
sudo tail /var/log/sigeru-backup.log                       # qué pasó
```

Contra otra base, sin editar el `.conf` (por ejemplo la del Docker del proyecto):

```bash
sudo SIGERU_DB_HOST=sigeru-db SIGERU_DB_PASS=root ./backup.sh
```

Los nombres de las variables (`SIGERU_DB_HOST`, `_PORT`, `_NAME`, `_USER`, `_PASS`) son
los mismos que lee `config/database.php` de las APIs.

### Administración

```bash
sudo sigeru-admin                       # menú: usuarios, red, respaldos, servicios
sudo sigeru-admin red puertos           # una acción directa: <modulo> <accion>
sudo sigeru-admin usuarios alta ana     # = función usuarios_alta de lib/usuarios.sh
sigeru-admin --ayuda                    # todos los módulos y acciones
```

Cada `lib/*.sh` es un archivo de funciones que se puede usar sin el menú:

```bash
source scripts/admin/lib/red.sh && red_resumen
```

### Repetir la prueba del 12/09

Con los contenedores del proyecto levantados (`docker compose up` en `docker/`), un
contenedor Ubuntu en la misma red hace de servidor:

```bash
docker run -d --name prueba --network sigeru_default ubuntu:24.04 sleep infinity
docker exec prueba bash -c 'apt-get update && apt-get install -y mysql-client cron iproute2 iputils-ping'
docker cp administracion-sistemas-operativos/scripts prueba:/tmp/scripts
docker exec prueba bash /tmp/scripts/respaldo/instalar.sh
docker exec prueba sed -i 's/^DB_HOST=.*/DB_HOST=sigeru-db/; s/^DB_CLAVE=.*/DB_CLAVE=root/' /opt/sigeru/respaldo/backup.conf
docker exec prueba /opt/sigeru/respaldo/backup.sh
```

(El código se copia con `docker cp` a `/var/www/sigeru`; los `docker exec` con rutas
`/var/...` desde Git Bash llevan `MSYS_NO_PATHCONV=1` adelante.)

## Finales de línea

Los `.sh`, `.conf` y el archivo de cron **tienen que ser LF**. El `.gitattributes` del
repositorio lo fuerza (`*.sh text eol=lf`), porque con `core.autocrlf=true` en Windows Git
los convertiría a CRLF y en Ubuntu morirían con `\r: command not found`.
