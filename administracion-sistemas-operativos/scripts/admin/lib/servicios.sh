#!/usr/bin/env bash
# ============================================================================
#  SiGeRU 2026 - sigeru-admin: modulo de SERVICIOS
#
#  Funciones:
#    servicios_estado [servicio...]   estado de apache2, mysql, ssh y cron
#                                     (o de los que se indiquen)
#    servicios_reiniciar <servicio>   reinicia uno
#    servicios_iniciar <servicio>     lo arranca
#    servicios_detener <servicio>     lo para (pide SI)
#    menu_servicios                   submenu interactivo
#
#  COMO DETECTA CON QUE MANEJAR LOS SERVICIOS:
#    - Si existe systemctl Y systemd esta corriendo (hay /run/systemd/system),
#      usa systemctl. Es el caso de Ubuntu Server instalado normalmente.
#    - Si no, pero existe el comando "service", usa service (init.d).
#    - Si no hay ninguno de los dos, lo dice y no hace nada. Pasa dentro de
#      un contenedor Docker, donde no hay init: los procesos se arrancan a
#      mano y el "estado" de un servicio no significa lo mismo.
#  Tener systemctl instalado no alcanza: en un contenedor puede estar el
#  binario sin que systemd sea el proceso 1, y ahi systemctl falla con
#  "System has not been booted with systemd". Por eso se mira /run/systemd.
#
#  Se puede usar suelto:  source lib/servicios.sh && servicios_estado
#  Desde el principal:    sigeru-admin.sh servicios reiniciar apache2
# ============================================================================

if ! declare -F titulo >/dev/null 2>&1; then
    # shellcheck source=comun.sh
    . "$(dirname "${BASH_SOURCE[0]}")/comun.sh"
fi

# Los servicios del servidor de SiGeRU: web, base, acceso remoto y el cron
# que dispara el respaldo. Se pueden cambiar con SIGERU_SERVICIOS.
SERVICIOS_SIGERU="${SIGERU_SERVICIOS:-apache2 mysql ssh cron}"

# Imprime "systemd", "service" o "ninguno".
_servicios_gestor() {
    if existe_comando systemctl && [ -d /run/systemd/system ]; then
        echo systemd
    elif existe_comando service; then
        echo service
    else
        echo ninguno
    fi
}

_servicios_explicar_gestor() {
    case "$(_servicios_gestor)" in
        systemd) echo "Gestor de servicios: systemd (systemctl)." ;;
        service) echo "Gestor de servicios: 'service' (init.d). No hay systemd corriendo en este equipo." ;;
        ninguno) echo "No hay systemctl ni service en este equipo: no se pueden consultar ni reiniciar"
                 echo "servicios. Es lo normal dentro de un contenedor Docker, donde no hay init." ;;
    esac
}

# Devuelve 0 si el servicio esta instalado en este equipo.
_servicios_instalado() {
    local servicio="$1"
    case "$(_servicios_gestor)" in
        systemd) systemctl list-unit-files "$servicio.service" --no-legend 2>/dev/null | grep -q . ;;
        service) [ -x "/etc/init.d/$servicio" ] ;;
        *)       return 1 ;;
    esac
}

servicios_estado() {
    local lista="${*:-$SERVICIOS_SIGERU}" servicio gestor activo arranque
    titulo "Estado de los servicios"
    _servicios_explicar_gestor
    gestor="$(_servicios_gestor)"
    [ "$gestor" = "ninguno" ] && return 0
    echo
    printf '%-10s %-12s %s\n' SERVICIO ESTADO 'ARRANCA SOLO'
    for servicio in $lista; do
        if ! _servicios_instalado "$servicio"; then
            printf '%-10s %-12s %s\n' "$servicio" "no instalado" "-"
            continue
        fi
        if [ "$gestor" = "systemd" ]; then
            activo="$(systemctl is-active "$servicio" 2>/dev/null || true)"
            arranque="$(systemctl is-enabled "$servicio" 2>/dev/null || true)"
        else
            # service <x> status devuelve 0 si corre, otro valor si no.
            if service "$servicio" status >/dev/null 2>&1; then activo="active"; else activo="inactive"; fi
            arranque="?"
        fi
        printf '%-10s %-12s %s\n' "$servicio" "$activo" "$arranque"
    done
}

# Accion generica: _servicios_accion <start|stop|restart> <servicio>
_servicios_accion() {
    local accion="$1" servicio="${2:-}" gestor
    if [ -z "$servicio" ]; then
        error "falta el nombre del servicio"; return 1
    fi
    gestor="$(_servicios_gestor)"
    if [ "$gestor" = "ninguno" ]; then
        _servicios_explicar_gestor; return 1
    fi
    if ! _servicios_instalado "$servicio"; then
        error "el servicio '$servicio' no esta instalado en este equipo"; return 1
    fi
    if [ "$gestor" = "systemd" ]; then
        systemctl "$accion" "$servicio"
    else
        service "$servicio" "$accion"
    fi
}

servicios_reiniciar() {
    titulo "Reiniciar ${1:-?}"
    _servicios_accion restart "${1:-}" && ok "'$1' reiniciado." && servicios_estado "$1"
}

servicios_iniciar() {
    titulo "Iniciar ${1:-?}"
    _servicios_accion start "${1:-}" && ok "'$1' iniciado." && servicios_estado "$1"
}

servicios_detener() {
    titulo "Detener ${1:-?}"
    if [ "${1:-}" = "ssh" ]; then
        aviso "detener ssh corta el acceso remoto: solo desde la consola fisica."
    fi
    if ! confirmar "Se detiene '${1:-?}'."; then
        aviso "cancelado."; return 0
    fi
    _servicios_accion stop "${1:-}" && ok "'$1' detenido."
}

menu_servicios() {
    local opcion servicio
    while true; do
        titulo "Servicios"
        echo "  1) Estado de apache2, mysql, ssh y cron"
        echo "  2) Reiniciar un servicio"
        echo "  3) Iniciar un servicio"
        echo "  4) Detener un servicio"
        echo "  0) Volver"
        preguntar opcion "Opcion: " || return 0
        case "$opcion" in
            1) servicios_estado ;;
            2) preguntar servicio "Servicio: " || return 0; servicios_reiniciar "$servicio" ;;
            3) preguntar servicio "Servicio: " || return 0; servicios_iniciar "$servicio" ;;
            4) preguntar servicio "Servicio: " || return 0; servicios_detener "$servicio" ;;
            0) return 0 ;;
            *) echo "Opcion invalida." ;;
        esac
    done
}
