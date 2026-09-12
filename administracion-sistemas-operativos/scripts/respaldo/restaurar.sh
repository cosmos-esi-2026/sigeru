#!/usr/bin/env bash
# ============================================================================
#  SiGeRU 2026 - Restauracion de un respaldo hecho por backup.sh
#
#  QUE HACE, en orden:
#    1. Comprueba la huella SHA-256 del archivo (el .sha256 que backup.sh dejo
#       al lado). Si no coincide, el archivo esta danado y NO se restaura.
#    2. Muestra que hay adentro (el manifiesto) y pide confirmacion, porque
#       lo que sigue pisa datos.
#    3. Vuelve a cargar la base: crea la base si no existe y ejecuta el
#       volcado SQL. Las tablas actuales se reemplazan por las del respaldo.
#    4. Vuelve a poner el codigo en su lugar. La carpeta actual, si existe,
#       se renombra a <carpeta>.previo-AAAAMMDD-HHMM por si hay que volver atras.
#    5. Los archivos de configuracion del servidor (netplan, sshd_config,
#       apache) NO se pisan solos: se dejan extraidos en una carpeta aparte
#       para compararlos y copiarlos a mano. Restaurar a ciegas un
#       sshd_config puede dejar el servidor sin acceso remoto.
#    6. Anota cada paso en el mismo log que backup.sh.
#
#  COMO SE CORRE:
#    sudo /opt/sigeru/respaldo/restaurar.sh /backups/sigeru-20260912-0300.tar.gz
#    Opciones:
#      --si              no pide confirmacion (para pruebas automatizadas)
#      --sin-verificar   restaura aunque falte el .sha256 (nunca si no coincide)
#
#  QUE NECESITA: lo mismo que backup.sh (mysql-client, backup.conf al lado).
#  Se conecta a la base que dice backup.conf o las variables SIGERU_DB_*.
#
#  Despues de restaurar conviene correr los tests del proyecto
#  (tests/correr-tests.php): es la unica prueba real de que el respaldo servia.
# ============================================================================

# -e: para en el primer error. -u: variable sin definir es error.
# -o pipefail: en "a | b" cuenta tambien el error de a.
set -euo pipefail

# --------------------------------------------------------------- 1. config --
DIR_SCRIPT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ARCHIVO_CONF="${SIGERU_BACKUP_CONF:-$DIR_SCRIPT/backup.conf}"
if [ ! -r "$ARCHIVO_CONF" ]; then
    echo "ERROR: no encuentro $ARCHIVO_CONF (tiene que estar al lado de restaurar.sh)." >&2
    exit 1
fi
# shellcheck source=backup.conf
. "$ARCHIVO_CONF"
DB_HOST="${SIGERU_DB_HOST:-$DB_HOST}"
DB_PUERTO="${SIGERU_DB_PORT:-$DB_PUERTO}"
DB_NOMBRE="${SIGERU_DB_NAME:-$DB_NOMBRE}"
DB_USUARIO="${SIGERU_DB_USER:-$DB_USUARIO}"
DB_CLAVE="${SIGERU_DB_PASS:-$DB_CLAVE}"
RUTA_CODIGO="${SIGERU_RUTA_CODIGO:-$RUTA_CODIGO}"
DIR_RESPALDOS="${SIGERU_DIR_RESPALDOS:-$DIR_RESPALDOS}"
ARCHIVO_LOG="${SIGERU_ARCHIVO_LOG:-$ARCHIVO_LOG}"

# ---------------------------------------------------------- 2. argumentos --
ARCHIVO=""
CONFIRMAR=1
EXIGIR_HUELLA=1
for arg in "$@"; do
    case "$arg" in
        --si)            CONFIRMAR=0 ;;
        --sin-verificar) EXIGIR_HUELLA=0 ;;
        -h|--ayuda)
            sed -n '2,30p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,2\}//'
            exit 0 ;;
        -*) echo "Opcion desconocida: $arg (probar --ayuda)" >&2; exit 1 ;;
        *)  ARCHIVO="$arg" ;;
    esac
done
if [ -z "$ARCHIVO" ]; then
    echo "Uso: $0 <archivo.tar.gz> [--si] [--sin-verificar]" >&2
    echo "Respaldos disponibles en $DIR_RESPALDOS:" >&2
    ls -1 "$DIR_RESPALDOS"/sigeru-*.tar.gz 2>/dev/null >&2 || echo "  (ninguno)" >&2
    exit 1
fi
if [ ! -f "$ARCHIVO" ]; then
    echo "ERROR: no existe el archivo $ARCHIVO" >&2
    exit 1
fi

# ------------------------------------------------------------ 3. utilidades --
registrar() {
    local linea
    linea="$(date '+%Y-%m-%d %H:%M:%S')  $*"
    echo "$linea"
    echo "$linea" >> "$ARCHIVO_LOG"
}
PASO="inicio"
fallo() {
    registrar "ERROR: la restauracion fallo en el paso \"$PASO\". Revisar las lineas anteriores."
    exit 1
}
trap fallo ERR
TMP="$(mktemp -d /tmp/sigeru-restaurar.XXXXXX)"
trap 'rm -rf "$TMP"' EXIT

if ! touch "$ARCHIVO_LOG" 2>/dev/null; then
    echo "ERROR: no puedo escribir el log $ARCHIVO_LOG (hay que correr como root)" >&2
    exit 1
fi
registrar "===== Restauracion desde $ARCHIVO: inicio ====="

for herramienta in mysql tar gzip sha256sum; do
    if ! command -v "$herramienta" >/dev/null 2>&1; then
        registrar "ERROR: falta el comando '$herramienta'. En Ubuntu: sudo apt install mysql-client"
        exit 1
    fi
done

# ----------------------------------------------------- 4. verificar huella --
PASO="verificar la huella SHA-256"
HUELLA="$ARCHIVO.sha256"
if [ -f "$HUELLA" ]; then
    # No se usa "sha256sum -c": ese busca el archivo por el nombre que quedo
    # escrito adentro del .sha256, y un respaldo copiado a otro disco con
    # otro nombre daria "No such file" sin haber leido el archivo. Aca se
    # calcula la huella del archivo que se indico y se compara con la guardada.
    ESPERADA="$(cut -d' ' -f1 "$HUELLA")"
    REAL="$(sha256sum "$ARCHIVO" | cut -d' ' -f1)"
    if [ -n "$ESPERADA" ] && [ "$ESPERADA" = "$REAL" ]; then
        registrar "Huella SHA-256 verificada: el archivo esta integro ($REAL)."
    else
        registrar "ERROR: la huella NO coincide. El archivo esta danado o fue modificado. No se restaura."
        registrar "       esperada: $ESPERADA"
        registrar "       real:     $REAL"
        exit 1
    fi
elif [ "$EXIGIR_HUELLA" -eq 1 ]; then
    registrar "ERROR: no existe $HUELLA. Sin la huella no se puede saber si el archivo esta sano."
    registrar "       Si igual se quiere restaurar: $0 $ARCHIVO --sin-verificar"
    exit 1
else
    registrar "Aviso: se restaura SIN verificar la huella (--sin-verificar)."
fi

# --------------------------------------------------- 5. mostrar y confirmar --
PASO="leer el contenido del respaldo"
tar -xzf "$ARCHIVO" -C "$TMP" manifiesto.txt base
VOLCADO="$(find "$TMP/base" -maxdepth 1 -name '*.sql' | head -n 1)"
if [ -z "$VOLCADO" ]; then
    registrar "ERROR: el respaldo no trae ningun .sql en base/. No parece hecho por backup.sh."
    exit 1
fi
echo
echo "----- Contenido del respaldo -----"
cat "$TMP/manifiesto.txt"
echo "----------------------------------"
echo "Se va a restaurar:"
echo "  - la base '$DB_NOMBRE' en $DB_HOST:$DB_PUERTO (las tablas actuales se reemplazan)"
echo "  - el codigo en $RUTA_CODIGO (la carpeta actual se renombra a .previo-<fecha>)"
echo
if [ "$CONFIRMAR" -eq 1 ]; then
    # read -p solo muestra el texto si hay terminal; sin ella se imprime a mano.
    [ -t 0 ] || printf '%s' "Escribi SI (en mayusculas) para continuar: "
    read -r -p "Escribi SI (en mayusculas) para continuar: " respuesta || respuesta=""
    if [ "$respuesta" != "SI" ]; then
        registrar "Restauracion cancelada por el usuario."
        exit 0
    fi
fi

# ---------------------------------------------------------- 6. la base -----
PASO="conectar a MySQL"
CNF="$TMP/mysql.cnf"
umask 077
printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n' \
    "$DB_HOST" "$DB_PUERTO" "$DB_USUARIO" "$DB_CLAVE" > "$CNF"
umask 022
if ! mysql --defaults-extra-file="$CNF" --connect-timeout=10 -e 'SELECT 1' >/dev/null 2>"$TMP/error-conexion.txt"; then
    registrar "ERROR: no pude conectarme a MySQL en $DB_HOST:$DB_PUERTO como $DB_USUARIO."
    registrar "       MySQL dijo: $(tr -d '\n' < "$TMP/error-conexion.txt")"
    exit 1
fi

PASO="restaurar la base"
# El volcado no trae CREATE DATABASE (backup.sh lo hace asi a proposito, para
# poder restaurar en una base con otro nombre si hace falta), asi que la base
# se crea aca, con el mismo juego de caracteres que schema.sql.
mysql --defaults-extra-file="$CNF" -e \
    "CREATE DATABASE IF NOT EXISTS \`$DB_NOMBRE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql --defaults-extra-file="$CNF" --default-character-set=utf8mb4 "$DB_NOMBRE" < "$VOLCADO"
TABLAS="$(mysql --defaults-extra-file="$CNF" -N -e \
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NOMBRE'")"
registrar "Base '$DB_NOMBRE' restaurada: $TABLAS tablas."

# --------------------------------------------------------- 7. el codigo ----
PASO="restaurar el codigo"
RELATIVA="${RUTA_CODIGO#/}"
if tar -tzf "$ARCHIVO" "$RELATIVA" >/dev/null 2>&1; then
    if [ -d "$RUTA_CODIGO" ]; then
        PREVIO="$RUTA_CODIGO.previo-$(date '+%Y%m%d-%H%M')"
        mv "$RUTA_CODIGO" "$PREVIO"
        registrar "La carpeta actual se guardo como $PREVIO (borrarla cuando se confirme que todo anda)."
    fi
    tar -xzf "$ARCHIVO" -C / "$RELATIVA"
    registrar "Codigo restaurado en $RUTA_CODIGO ($(find "$RUTA_CODIGO" -type f | wc -l) archivos)."
else
    registrar "Aviso: el respaldo no contiene $RUTA_CODIGO; se restauro solo la base."
fi

# --------------------------------------------- 8. la configuracion, aparte --
PASO="extraer la configuracion"
DIR_CONF="$DIR_RESPALDOS/configuracion-restaurada-$(date '+%Y%m%d-%H%M')"
mkdir -p "$DIR_CONF"
# Todo lo que no sea el manifiesto, la base ni el codigo es configuracion.
tar -xzf "$ARCHIVO" -C "$DIR_CONF" --exclude='manifiesto.txt' --exclude='base' \
    --exclude="$RELATIVA" 2>/dev/null || true
if [ -n "$(find "$DIR_CONF" -type f 2>/dev/null | head -n 1)" ]; then
    registrar "Archivos de configuracion dejados en $DIR_CONF (compararlos con los actuales antes de copiarlos)."
else
    rmdir "$DIR_CONF"
    registrar "El respaldo no traia archivos de configuracion."
fi

registrar "===== Restauracion desde $(basename "$ARCHIVO"): OK ====="
echo
echo "Siguiente paso recomendado: correr los tests del proyecto para confirmar"
echo "que el sistema funciona con los datos restaurados."
