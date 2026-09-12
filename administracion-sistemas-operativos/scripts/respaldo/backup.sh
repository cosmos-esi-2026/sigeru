#!/usr/bin/env bash
# ============================================================================
#  SiGeRU 2026 - Respaldo diario de la base de datos y del codigo
#
#  QUE HACE, en orden:
#    1. Vuelca la base "sigeru" de MySQL con mysqldump --single-transaction
#       (una foto consistente de todas las tablas, sin bloquear la base).
#    2. Empaqueta ese volcado + la carpeta del codigo (/var/www/sigeru) + los
#       archivos de configuracion del servidor en UN solo archivo:
#           /backups/sigeru-AAAAMMDD-HHMM.tar.gz
#    3. Deja al lado su huella SHA-256 (sigeru-AAAAMMDD-HHMM.tar.gz.sha256)
#       para poder comprobar despues que el archivo no se corrompio.
#    4. Verifica que el .tar.gz se pueda leer entero (tar -tzf) y que la
#       huella coincida. Un respaldo que no se verifica no es un respaldo.
#    5. Borra los respaldos diarios de mas de DIAS_RETENCION dias (14).
#    6. Anota cada paso, con fecha y hora, en /var/log/sigeru-backup.log.
#
#  COMO SE CORRE:
#    A mano:        sudo /opt/sigeru/respaldo/backup.sh
#    Automatico:    todos los dias a las 03:00, por /etc/cron.d/sigeru-backup
#                   (lo instala instalar.sh).
#    Contra otra base sin editar nada (por ejemplo la del Docker del proyecto):
#                   SIGERU_DB_HOST=sigeru-db SIGERU_DB_PASS=root ./backup.sh
#
#  QUE NECESITA:
#    - Ubuntu Server con el paquete mysql-client (trae mysqldump y mysql).
#      tar, gzip y sha256sum vienen con el sistema.
#    - backup.conf al lado de este archivo (o la ruta en SIGERU_BACKUP_CONF).
#    - Permiso para escribir en DIR_RESPALDOS y en el log: en el servidor lo
#      corre root desde cron.
#
#  COMO SE RESTAURA: restaurar.sh <archivo.tar.gz>. La prueba de que este
#  respaldo se restaura de verdad esta en ../../pruebas/.
#
#  Si algo falla, el script termina con codigo 1 y deja escrito EN QUE PASO
#  fallo, tanto en pantalla como en el log.
# ============================================================================

# -e: si un comando falla, el script para (no sigue empaquetando un volcado
# roto). -u: usar una variable no definida es error (evita rutas vacias tipo
# "rm -rf /$VACIA"). -o pipefail: en una tuberia "a | b" cuenta el error de a,
# no solo el de b.
set -euo pipefail

# --------------------------------------------------------------- 1. config --
DIR_SCRIPT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ARCHIVO_CONF="${SIGERU_BACKUP_CONF:-$DIR_SCRIPT/backup.conf}"

if [ ! -r "$ARCHIVO_CONF" ]; then
    echo "ERROR: no encuentro el archivo de configuracion $ARCHIVO_CONF" >&2
    echo "       Tiene que estar al lado de backup.sh, o indicarse en SIGERU_BACKUP_CONF." >&2
    exit 1
fi

# Primero los valores del archivo...
# shellcheck source=backup.conf
. "$ARCHIVO_CONF"

# ...y despues el entorno, que tiene prioridad. Los cinco de la base usan los
# mismos nombres que config/database.php de las APIs.
DB_HOST="${SIGERU_DB_HOST:-$DB_HOST}"
DB_PUERTO="${SIGERU_DB_PORT:-$DB_PUERTO}"
DB_NOMBRE="${SIGERU_DB_NAME:-$DB_NOMBRE}"
DB_USUARIO="${SIGERU_DB_USER:-$DB_USUARIO}"
DB_CLAVE="${SIGERU_DB_PASS:-$DB_CLAVE}"
RUTA_CODIGO="${SIGERU_RUTA_CODIGO:-$RUTA_CODIGO}"
RUTAS_CONFIG="${SIGERU_RUTAS_CONFIG:-$RUTAS_CONFIG}"
DIR_RESPALDOS="${SIGERU_DIR_RESPALDOS:-$DIR_RESPALDOS}"
DIAS_RETENCION="${SIGERU_DIAS_RETENCION:-$DIAS_RETENCION}"
ARCHIVO_LOG="${SIGERU_ARCHIVO_LOG:-$ARCHIVO_LOG}"

# ------------------------------------------------------------ 2. utilidades --
# Escribe una linea con fecha y hora en el log y en pantalla.
registrar() {
    local linea
    linea="$(date '+%Y-%m-%d %H:%M:%S')  $*"
    echo "$linea"
    echo "$linea" >> "$ARCHIVO_LOG"
}

# En que paso estamos: si algo falla, el mensaje de error dice cual era.
PASO="inicio"
fallo() {
    registrar "ERROR: el respaldo fallo en el paso \"$PASO\". Revisar las lineas anteriores."
    exit 1
}
trap fallo ERR

# Carpeta temporal para el volcado; se borra siempre, salga bien o mal.
TMP="$(mktemp -d /tmp/sigeru-backup.XXXXXX)"
trap 'rm -rf "$TMP"' EXIT

# ---------------------------------------------------- 3. comprobaciones -----
# El log tiene que poder escribirse antes que nada: si no, no hay registro.
if ! touch "$ARCHIVO_LOG" 2>/dev/null; then
    echo "ERROR: no puedo escribir el log $ARCHIVO_LOG (hay que correr como root)" >&2
    exit 1
fi

FECHA="$(date '+%Y%m%d-%H%M')"
NOMBRE="sigeru-$FECHA"
ARCHIVO="$DIR_RESPALDOS/$NOMBRE.tar.gz"

registrar "===== Respaldo $NOMBRE: inicio ====="
registrar "Base $DB_NOMBRE en $DB_HOST:$DB_PUERTO (usuario $DB_USUARIO); codigo en $RUTA_CODIGO; destino $DIR_RESPALDOS"

PASO="comprobar herramientas"
for herramienta in mysqldump mysql tar gzip sha256sum; do
    if ! command -v "$herramienta" >/dev/null 2>&1; then
        registrar "ERROR: falta el comando '$herramienta'. En Ubuntu: sudo apt install mysql-client"
        exit 1
    fi
done

PASO="comprobar carpetas"
if [ ! -d "$RUTA_CODIGO" ]; then
    registrar "ERROR: la carpeta del codigo $RUTA_CODIGO no existe. Revisar RUTA_CODIGO en $ARCHIVO_CONF"
    exit 1
fi
mkdir -p "$DIR_RESPALDOS"
if [ ! -w "$DIR_RESPALDOS" ]; then
    registrar "ERROR: no puedo escribir en $DIR_RESPALDOS"
    exit 1
fi

# Las credenciales van en un archivo temporal solo legible por quien corre el
# script, y no en la linea de comandos: asi no aparecen en "ps" ni mysql
# protesta con "Using a password on the command line interface can be insecure".
CNF="$TMP/mysql.cnf"
umask 077
printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n' \
    "$DB_HOST" "$DB_PUERTO" "$DB_USUARIO" "$DB_CLAVE" > "$CNF"
umask 022

PASO="conectar a MySQL"
if ! mysql --defaults-extra-file="$CNF" --connect-timeout=10 \
        -e 'SELECT 1' "$DB_NOMBRE" >/dev/null 2>"$TMP/error-conexion.txt"; then
    registrar "ERROR: no pude conectarme a MySQL en $DB_HOST:$DB_PUERTO como $DB_USUARIO, base $DB_NOMBRE."
    registrar "       MySQL dijo: $(tr -d '\n' < "$TMP/error-conexion.txt")"
    registrar "       Revisar $ARCHIVO_CONF o las variables SIGERU_DB_*."
    exit 1
fi
registrar "Conexion a MySQL verificada."

# ------------------------------------------------------ 4. volcar la base ---
PASO="volcar la base con mysqldump"
mkdir -p "$TMP/base"
VOLCADO="$TMP/base/$DB_NOMBRE.sql"
# --single-transaction: foto consistente de InnoDB sin bloquear las tablas
#   (las APIs siguen funcionando mientras se respalda).
# --routines/--triggers: por si en el futuro el schema los tiene.
# --no-tablespaces: evita pedir el privilegio PROCESS, que un usuario de solo
#   respaldo no tiene por que tener.
mysqldump --defaults-extra-file="$CNF" \
    --single-transaction --routines --triggers --no-tablespaces \
    --default-character-set=utf8mb4 \
    "$DB_NOMBRE" > "$VOLCADO"
# mysqldump termina el archivo con "-- Dump completed": si no esta, se corto.
if ! grep -q -- '-- Dump completed' "$VOLCADO"; then
    registrar "ERROR: el volcado quedo incompleto (no termina en 'Dump completed')."
    exit 1
fi
TABLAS="$(grep -c '^CREATE TABLE' "$VOLCADO" || true)"
registrar "Base volcada: $TABLAS tablas, $(du -h "$VOLCADO" | cut -f1)."

# ---------------------------------------------- 5. empaquetar todo junto ----
PASO="empaquetar con tar"
# Manifiesto: que hay adentro y de donde salio, para quien restaure dentro de
# un ano sin acordarse de nada.
{
    echo "Respaldo SiGeRU $NOMBRE"
    echo "Fecha: $(date '+%Y-%m-%d %H:%M:%S')   Equipo: $(hostname)"
    echo "Base: $DB_NOMBRE en $DB_HOST:$DB_PUERTO -> base/$DB_NOMBRE.sql ($TABLAS tablas)"
    echo "Codigo: $RUTA_CODIGO"
    echo "Configuracion incluida:"
} > "$TMP/manifiesto.txt"

# Los archivos se agregan con rutas relativas a / (tar -C /): asi al restaurar
# vuelven exactamente al mismo lugar.
ARGS_TAR=(-C "$TMP" manifiesto.txt base -C / "${RUTA_CODIGO#/}")
for ruta in $RUTAS_CONFIG; do
    if [ -e "$ruta" ]; then
        ARGS_TAR+=("${ruta#/}")
        echo "  $ruta" >> "$TMP/manifiesto.txt"
    else
        registrar "Aviso: $ruta no existe en este equipo, no se incluye."
        echo "  $ruta (no existia)" >> "$TMP/manifiesto.txt"
    fi
done
tar -czf "$ARCHIVO" "${ARGS_TAR[@]}"
registrar "Archivo creado: $ARCHIVO ($(du -h "$ARCHIVO" | cut -f1))."

# ------------------------------------------------- 6. huella y verificacion --
PASO="calcular la huella SHA-256"
# Se calcula desde la carpeta, con el nombre relativo, para que
# "sha256sum -c" funcione aunque despues se copie a otro disco.
(cd "$DIR_RESPALDOS" && sha256sum "$NOMBRE.tar.gz" > "$NOMBRE.tar.gz.sha256")
registrar "Huella: $(cut -d' ' -f1 "$ARCHIVO.sha256")"

PASO="verificar el archivo"
ENTRADAS="$(tar -tzf "$ARCHIVO" | wc -l)"
(cd "$DIR_RESPALDOS" && sha256sum -c --quiet "$NOMBRE.tar.gz.sha256")
registrar "Verificado: el .tar.gz se lee entero ($ENTRADAS entradas) y la huella coincide."

# ------------------------------------------------------ 7. retencion --------
PASO="borrar respaldos viejos"
BORRADOS="$(find "$DIR_RESPALDOS" -maxdepth 1 -type f \
    \( -name 'sigeru-*.tar.gz' -o -name 'sigeru-*.tar.gz.sha256' \) \
    -mtime +"$DIAS_RETENCION" -print -delete)"
if [ -n "$BORRADOS" ]; then
    registrar "Borrados por tener mas de $DIAS_RETENCION dias:"
    echo "$BORRADOS" | while read -r viejo; do registrar "  $viejo"; done
else
    registrar "Nada para borrar (ninguno supera los $DIAS_RETENCION dias)."
fi

QUEDAN="$(find "$DIR_RESPALDOS" -maxdepth 1 -type f -name 'sigeru-*.tar.gz' | wc -l)"
registrar "===== Respaldo $NOMBRE: OK. Quedan $QUEDAN respaldos en $DIR_RESPALDOS ====="
