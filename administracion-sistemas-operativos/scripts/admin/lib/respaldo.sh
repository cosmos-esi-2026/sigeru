#!/usr/bin/env bash
# ============================================================================
#  SiGeRU 2026 - sigeru-admin: modulo de RESPALDOS
#
#  Funciones:
#    respaldo_ahora               corre backup.sh (respaldo completo, ya)
#    respaldo_listar              lista los .tar.gz de /backups con fecha,
#                                 tamano y si tienen su huella .sha256
#    respaldo_restaurar [archivo] corre restaurar.sh (si no se indica el
#                                 archivo, deja elegir uno de la lista)
#    respaldo_log [lineas]        ultimas lineas de /var/log/sigeru-backup.log
#    menu_respaldo                submenu interactivo
#
#  Este modulo NO repite la logica del respaldo: llama a los scripts de
#  ../respaldo/ (backup.sh y restaurar.sh), que son los mismos que corre cron.
#  Asi hay una sola forma de hacer un respaldo, y es la que esta probada.
#
#  Donde busca esos scripts, en orden: la variable SIGERU_DIR_SCRIPTS_RESPALDO,
#  /opt/sigeru/respaldo (donde los deja instalar.sh) y, si se esta corriendo
#  desde el repositorio, la carpeta hermana scripts/respaldo/.
# ============================================================================

if ! declare -F titulo >/dev/null 2>&1; then
    # shellcheck source=comun.sh
    . "$(dirname "${BASH_SOURCE[0]}")/comun.sh"
fi

# Carpeta donde estan backup.sh y restaurar.sh (o vacio si no se encuentran).
_respaldo_dir_scripts() {
    local aca candidato
    aca="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    for candidato in "${SIGERU_DIR_SCRIPTS_RESPALDO:-}" /opt/sigeru/respaldo "$aca/../../respaldo"; do
        if [ -n "$candidato" ] && [ -x "$candidato/backup.sh" ]; then
            (cd "$candidato" && pwd)
            return 0
        fi
    done
    return 1
}

# Lee un valor de backup.conf (por ejemplo DIR_RESPALDOS) respetando el
# entorno, igual que hace backup.sh.
_respaldo_conf_valor() {
    local variable="$1" por_defecto="$2" dir conf valor
    if dir="$(_respaldo_dir_scripts)"; then
        conf="${SIGERU_BACKUP_CONF:-$dir/backup.conf}"
        valor="$(grep -E "^$variable=" "$conf" 2>/dev/null | tail -n 1 | cut -d= -f2- | tr -d '"')"
    fi
    echo "${valor:-$por_defecto}"
}

_respaldo_dir_respaldos() {
    echo "${SIGERU_DIR_RESPALDOS:-$(_respaldo_conf_valor DIR_RESPALDOS /backups)}"
}

_respaldo_log_archivo() {
    echo "${SIGERU_ARCHIVO_LOG:-$(_respaldo_conf_valor ARCHIVO_LOG /var/log/sigeru-backup.log)}"
}

respaldo_ahora() {
    local dir
    titulo "Respaldo completo ahora"
    if ! dir="$(_respaldo_dir_scripts)"; then
        error "no encuentro backup.sh (correr instalar.sh, o definir SIGERU_DIR_SCRIPTS_RESPALDO)"; return 1
    fi
    echo "Usando $dir/backup.sh"
    "$dir/backup.sh"
}

respaldo_listar() {
    local dir archivo huella
    dir="$(_respaldo_dir_respaldos)"
    titulo "Respaldos en $dir"
    if [ ! -d "$dir" ]; then
        aviso "la carpeta $dir no existe todavia (correr instalar.sh o un primer respaldo)."; return 0
    fi
    printf '%-32s %8s  %-17s %s\n' ARCHIVO TAMANO FECHA HUELLA
    # Del mas nuevo al mas viejo.
    for archivo in $(ls -1t "$dir"/sigeru-*.tar.gz 2>/dev/null); do
        if [ -f "$archivo.sha256" ]; then huella="si"; else huella="FALTA"; fi
        printf '%-32s %8s  %-17s %s\n' "$(basename "$archivo")" \
            "$(du -h "$archivo" | cut -f1)" \
            "$(date -r "$archivo" '+%Y-%m-%d %H:%M')" "$huella"
    done
    local cantidad
    cantidad="$(ls -1 "$dir"/sigeru-*.tar.gz 2>/dev/null | wc -l)"
    if [ "$cantidad" -eq 0 ]; then
        aviso "no hay ningun respaldo todavia."
    else
        echo "Total: $cantidad respaldo(s), $(du -sh "$dir" | cut -f1) en disco."
    fi
}

respaldo_restaurar() {
    local archivo="${1:-}" dir opciones
    titulo "Restaurar un respaldo"
    if ! dir="$(_respaldo_dir_scripts)"; then
        error "no encuentro restaurar.sh (correr instalar.sh, o definir SIGERU_DIR_SCRIPTS_RESPALDO)"; return 1
    fi
    if [ -z "$archivo" ]; then
        respaldo_listar
        preguntar archivo "Nombre del archivo a restaurar (Enter = cancelar): " || archivo=""
        [ -z "$archivo" ] && { aviso "cancelado."; return 0; }
        [[ "$archivo" = /* ]] || archivo="$(_respaldo_dir_respaldos)/$archivo"
    fi
    # Si quien llama ya confirmo (SIGERU_SIN_CONFIRMAR=1), no se vuelve a preguntar.
    opciones=""
    [ "${SIGERU_SIN_CONFIRMAR:-0}" = "1" ] && opciones="--si"
    # shellcheck disable=SC2086
    "$dir/restaurar.sh" "$archivo" $opciones
}

respaldo_log() {
    local lineas="${1:-20}" archivo
    archivo="$(_respaldo_log_archivo)"
    titulo "Ultimas $lineas lineas de $archivo"
    if [ -f "$archivo" ]; then
        tail -n "$lineas" "$archivo"
    else
        aviso "todavia no existe el log (no se corrio ningun respaldo)."
    fi
}

menu_respaldo() {
    local opcion
    while true; do
        titulo "Respaldos"
        echo "  1) Listar respaldos"
        echo "  2) Hacer un respaldo completo ahora"
        echo "  3) Restaurar un respaldo"
        echo "  4) Ver el log del respaldo"
        echo "  0) Volver"
        preguntar opcion "Opcion: " || return 0
        case "$opcion" in
            1) respaldo_listar ;;
            2) respaldo_ahora ;;
            3) respaldo_restaurar ;;
            4) respaldo_log ;;
            0) return 0 ;;
            *) echo "Opcion invalida." ;;
        esac
    done
}
