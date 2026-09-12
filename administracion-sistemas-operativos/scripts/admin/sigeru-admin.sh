#!/usr/bin/env bash
# ============================================================================
#  SiGeRU 2026 - Script de administracion del servidor (version 1, modular)
#
#  QUE ES: un menu para las tareas de todos los dias del servidor de SiGeRU
#  (usuarios, red, respaldos y servicios), pensado para que lo use alguien
#  del equipo sin acordarse de cada comando de Linux.
#
#  POR QUE "MODULAR": este archivo casi no hace nada solo. Cada tema vive en
#  un archivo de funciones dentro de lib/:
#
#      lib/comun.sh      titulos, mensajes, confirmacion (lo usan todos)
#      lib/usuarios.sh   usuarios_listar, usuarios_alta, usuarios_clave, usuarios_baja
#      lib/red.sh        red_ip, red_gateway, red_dns, red_ping, red_puertos
#      lib/respaldo.sh   respaldo_ahora, respaldo_listar, respaldo_restaurar, respaldo_log
#      lib/servicios.sh  servicios_estado, servicios_reiniciar, servicios_iniciar, ...
#
#  Este script los carga con "source" y arma el menu. Cada modulo se puede
#  probar y reutilizar SOLO, sin el menu:
#
#      source lib/red.sh && red_puertos
#
#  y para agregar un tema nuevo (la 3ra entrega pide grupos, base de datos,
#  firewall y logs) alcanza con sumar lib/<tema>.sh con funciones <tema>_* y
#  una funcion menu_<tema>: el menu lo encuentra solo. Nada se repite: el
#  modulo de respaldo llama a los mismos backup.sh y restaurar.sh que corre
#  cron, no tiene una copia propia.
#
#  COMO SE CORRE (siempre como root, porque crea usuarios y reinicia servicios):
#      sudo ./sigeru-admin.sh                    menu interactivo
#      sudo ./sigeru-admin.sh red puertos        una accion directa, sin menu
#      sudo ./sigeru-admin.sh usuarios alta ana  (= funcion usuarios_alta ana)
#      ./sigeru-admin.sh --ayuda                 lista de modulos y acciones
#  Instalado con instalar.sh queda en /opt/sigeru/admin y se llama sigeru-admin.
#
#  QUE NECESITA: bash 4 o mas nuevo, y los comandos que usa cada modulo (ver
#  la cabecera de cada lib/*.sh). Todo viene con Ubuntu Server.
# ============================================================================

# -e: para en el primer error. -u: variable sin definir es error.
# -o pipefail: en "a | b" cuenta tambien el error de a.
set -euo pipefail

# readlink -f resuelve el enlace simbolico: instalado, el comando es
# /usr/local/sbin/sigeru-admin -> /opt/sigeru/admin/sigeru-admin.sh, y lib/
# esta al lado del archivo real, no del enlace.
DIR_SCRIPT="$(cd "$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")" && pwd)"
DIR_LIB="$DIR_SCRIPT/lib"
# Orden de carga: comun primero (los demas la necesitan); despues los temas,
# en el orden en que aparecen en el menu.
MODULOS="usuarios red respaldo servicios"
VERSION="1.0 (2da entrega, 12/09/2026)"

# ------------------------------------------------------- cargar modulos ----
if [ ! -r "$DIR_LIB/comun.sh" ]; then
    echo "ERROR: falta $DIR_LIB/comun.sh. El script necesita su carpeta lib/ al lado." >&2
    exit 1
fi
# shellcheck source=lib/comun.sh
. "$DIR_LIB/comun.sh"
for modulo in $MODULOS; do
    if [ ! -r "$DIR_LIB/$modulo.sh" ]; then
        echo "ERROR: falta el modulo $DIR_LIB/$modulo.sh" >&2
        exit 1
    fi
    # shellcheck disable=SC1090
    . "$DIR_LIB/$modulo.sh"
done

# ------------------------------------------------------------- ayuda -------
ayuda() {
    echo "sigeru-admin $VERSION"
    echo
    echo "Uso:  sudo $0                       menu interactivo"
    echo "      sudo $0 <modulo> <accion> [argumentos]"
    echo "      $0 --ayuda"
    echo
    echo "Modulos y acciones disponibles (cada una es una funcion <modulo>_<accion> de lib/):"
    for modulo in $MODULOS; do
        printf '  %-10s' "$modulo"
        declare -F | awk -v m="$modulo" '$3 ~ "^" m "_" {sub("^" m "_", "", $3); printf "%s ", $3}'
        echo
    done
    echo
    echo "Ejemplos:  sudo $0 usuarios listar"
    echo "           sudo $0 red ping 8.8.8.8"
    echo "           sudo $0 servicios reiniciar apache2"
}

if [ "${1:-}" = "--ayuda" ] || [ "${1:-}" = "-h" ] || [ "${1:-}" = "--help" ]; then
    ayuda
    exit 0
fi

# ------------------------------------------------------------ exigir root --
if [ "$(id -u)" -ne 0 ]; then
    echo "Este script administra el servidor: hay que correrlo como root." >&2
    echo "    sudo $0 $*" >&2
    exit 1
fi

# ------------------------------------------- modo directo: modulo accion ---
if [ $# -ge 1 ]; then
    modulo="$1"; accion="${2:-}"
    if [ -z "$accion" ]; then
        echo "Falta la accion. Uso: $0 $modulo <accion>. Ver: $0 --ayuda" >&2
        exit 1
    fi
    shift 2
    funcion="${modulo}_${accion}"
    if ! declare -F "$funcion" >/dev/null 2>&1; then
        echo "No existe la accion '$accion' en el modulo '$modulo'. Ver: $0 --ayuda" >&2
        exit 1
    fi
    # Se llama tal cual; el codigo de salida es el de la funcion.
    "$funcion" "$@"
    exit $?
fi

# ------------------------------------------------- modo menu interactivo ---
# En el menu, una accion que falla (un ping que no responde, un usuario que
# no existe) tiene que mostrar su error y volver al menu, no cerrar el
# programa. Por eso aca se apaga -e; en el modo directo sigue prendido.
set +e

while true; do
    titulo "SiGeRU - Administracion del servidor  ($(hostname), $(date '+%d/%m/%Y %H:%M'))"
    echo "  1) Usuarios     (listar, alta, contrasena, baja)"
    echo "  2) Red          (IP, puerta de enlace, DNS, ping, puertos)"
    echo "  3) Respaldos    (hacer, listar, restaurar, log)"
    echo "  4) Servicios    (estado y reinicio de apache2, mysql, ssh, cron)"
    echo "  0) Salir"
    preguntar opcion "Opcion: " || { echo; break; }
    case "$opcion" in
        1) menu_usuarios ;;
        2) menu_red ;;
        3) menu_respaldo ;;
        4) menu_servicios ;;
        0) break ;;
        *) echo "Opcion invalida." ;;
    esac
done
echo "Hasta luego."
