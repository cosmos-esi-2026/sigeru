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
| `pruebas/` | Transcripciones, sin editar, de las pruebas del 12/09 (scripts en un contenedor Ubuntu) y del 13/09 (todo contra el servidor real `sigeru-srv`) | **2da** |
| `plan-de-instalacion.md` | El plan original para instalar el servidor, escrito el 12/09 para el primer sprint de la 3ra. Se adelantó al 13/09 (ver los `.md` de abajo); queda como referencia de las decisiones que ya se tomaron | **2da** (el plan) |
| `manual-de-instalacion.md` | La VM `sigeru-srv` tal como quedó instalada el 13/09: parámetros reales, las 16 pantallas con lo que se planeaba vs. lo que se hizo, y los desvíos (usuario `cosmos2026`, idioma en español) | **2da** |
| `red-estatica.md` | IP fija con netplan, aplicada y verificada contra el servidor real | **2da** |
| `gestion-de-paquetes.md` | Apache/PHP/MySQL instalados y SiGeRU desplegado en el servidor real, con los 47 tests pasando | **2da** |
| `ssh.md` | SSH endurecido y probado contra el servidor real (root rechazado, cuenta permitida aceptada) | **2da** |

## Qué se entrega

Los 8 ítems que la letra pide a la materia (4 de la 1ra + 4 de la 2da) están hechos:

| Ítem | Estado | Evidencia |
|---|---|---|
| Relevamiento del S.O. | ✔ | `Relevamiento_Sistema_Operativo_SiGeRU.pdf` |
| Medios de respaldo a largo plazo y alta disponibilidad | ✔ | `respaldo-y-alta-disponibilidad.md` y sección 3 del PDF (2da entrega) |
| Rutinas de backup y sus scripts | ✔ | `scripts/respaldo/` + `pruebas/2026-09-12-prueba-respaldo.txt` (contenedor) + `pruebas/2026-09-13-prueba-scripts-servidor-real.txt` (servidor real) |
| Script de administración del servidor, v1 modular | ✔ | `scripts/admin/` + `pruebas/2026-09-12-prueba-admin.txt` (contenedor) + `pruebas/2026-09-13-prueba-scripts-servidor-real.txt` (servidor real) |
| Configuración del servicio SSH | ✔ (13/09) | `ssh.md` + `pruebas/2026-09-13-prueba-ssh.txt` |
| Manual de instalación del S.O. servidor | ✔ (13/09) | `manual-de-instalacion.md` — sin capturas por decisión (ver nota abajo) |
| Instalación y configuración de la red (estática) | ✔ (13/09) | `red-estatica.md` + `pruebas/2026-09-13-prueba-red-estatica.txt` |
| Gestión y administración de paquetes | ✔ (13/09) | `gestion-de-paquetes.md` + `pruebas/2026-09-13-prueba-despliegue-servidor-real.txt` |

Los cuatro últimos se adelantaron del primer sprint de la 3ra al 13/09: la ficha de Falcon
de esa fecha aclaró que la 2da entrega también completa la calificación de la 1ra.
`Administracion_SO_2da_Entrega_SiGeRU.pdf` se **regeneró el mismo 13/09** para mostrar los
8 de 8 en la entrega de mañana 14/09 (`doc15_admin_so.py` ampliado con la sección 6 y un
Anexo C con las transcripciones del servidor real). Sobre las capturas del manual: la
instalación ya había terminado cuando se armó el documento, así que las 16 pantallas no se
podían volver a fotografiar sin reinstalar; se documentó cada una con una tabla en vez de
crear una VM descartable solo para las imágenes.

Pendiente menor, no bloqueante: cargar la clave SSH de cada integrante del equipo en
`~cosmos2026/.ssh/authorized_keys` y recién entonces pasar `PasswordAuthentication` a `no`
en `ssh.md` / `sshd_config.d/sigeru.conf`.

## El servidor real

`sigeru-srv` es una VM VirtualBox en la PC de Fornos (modo NAT, no puente: el dominio
Ceibal puede bloquear el puente). Se entra por SSH con clave, desde esa misma PC:

```bash
ssh -p 2222 cosmos2026@127.0.0.1
```

La clave privada vive en `vm-ubuntu/ssh/` (fuera del repositorio). El usuario del sistema
operativo es `cosmos2026`, no `sigeru` — ver `manual-de-instalacion.md` para el porqué.

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
