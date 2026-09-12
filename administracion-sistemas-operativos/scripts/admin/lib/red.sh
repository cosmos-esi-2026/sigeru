#!/usr/bin/env bash
# ============================================================================
#  SiGeRU 2026 - sigeru-admin: modulo de RED
#
#  Funciones:
#    red_ip            interfaces y direcciones IP (ip -brief address)
#    red_gateway       puerta de enlace por defecto (ip route)
#    red_dns           servidores DNS en uso (resolvectl o /etc/resolv.conf)
#    red_resumen       las tres anteriores juntas, mas el hostname
#    red_ping [host]   prueba de conectividad, 3 paquetes (por defecto 1.1.1.1)
#    red_puertos       puertos TCP/UDP escuchando (ss -tuln)
#    menu_red          submenu interactivo
#
#  Se puede usar suelto:  source lib/red.sh && red_resumen
#  Desde el principal:    sigeru-admin.sh red puertos
#
#  Necesita los paquetes iproute2 (ip, ss) e iputils-ping (ping). Los dos
#  vienen con Ubuntu Server; en un contenedor minimo hay que instalarlos.
#  Solo consulta: este modulo no cambia la configuracion de red (la IP fija
#  se define en netplan; ver plan-de-instalacion.md).
# ============================================================================

if ! declare -F titulo >/dev/null 2>&1; then
    # shellcheck source=comun.sh
    . "$(dirname "${BASH_SOURCE[0]}")/comun.sh"
fi

red_ip() {
    titulo "Direcciones IP"
    if ! existe_comando ip; then
        error "falta el comando 'ip' (paquete iproute2)"; return 1
    fi
    echo "Equipo: $(hostname)"
    # -brief: una linea por interfaz: nombre, estado, direcciones.
    ip -brief address
}

red_gateway() {
    titulo "Puerta de enlace"
    if ! existe_comando ip; then
        error "falta el comando 'ip' (paquete iproute2)"; return 1
    fi
    local ruta
    ruta="$(ip route show default 2>/dev/null)"
    if [ -n "$ruta" ]; then
        echo "$ruta"
    else
        aviso "no hay ruta por defecto: este equipo no sale a otras redes."
    fi
}

red_dns() {
    titulo "Servidores DNS"
    # En Ubuntu Server moderno los DNS los maneja systemd-resolved y
    # /etc/resolv.conf apunta a 127.0.0.53; resolvectl muestra los reales.
    if existe_comando resolvectl && resolvectl status >/dev/null 2>&1; then
        resolvectl status | grep -E 'DNS Servers|DNS Domain|Link|Global' || true
    else
        grep -E '^(nameserver|search)' /etc/resolv.conf 2>/dev/null \
            || aviso "no hay servidores DNS configurados en /etc/resolv.conf"
    fi
}

red_resumen() {
    red_ip
    red_gateway
    red_dns
}

red_ping() {
    local destino="${1:-1.1.1.1}"
    titulo "Ping a $destino"
    if ! existe_comando ping; then
        error "falta el comando 'ping' (paquete iputils-ping)"; return 1
    fi
    # -c 3: tres paquetes y termina; -W 2: espera 2 s cada respuesta.
    if ping -c 3 -W 2 "$destino"; then
        ok "$destino responde."
    else
        error "$destino no responde (sin red, cortafuegos, o el nombre no resuelve)."
    fi
}

red_puertos() {
    titulo "Puertos escuchando"
    if ! existe_comando ss; then
        error "falta el comando 'ss' (paquete iproute2)"; return 1
    fi
    # -t TCP, -u UDP, -l solo los que escuchan, -n numeros en vez de nombres,
    # -p el proceso dueno (solo lo muestra root).
    ss -tulnp
    echo
    echo "Referencia SiGeRU: 22 ssh, 80 apache2, 3306 mysql (solo debe escuchar en 127.0.0.1)."
}

menu_red() {
    local opcion destino
    while true; do
        titulo "Red"
        echo "  1) Resumen: IP, puerta de enlace y DNS"
        echo "  2) Ping a un equipo"
        echo "  3) Puertos escuchando"
        echo "  0) Volver"
        preguntar opcion "Opcion: " || return 0
        case "$opcion" in
            1) red_resumen ;;
            2) preguntar destino "Destino (Enter = 1.1.1.1): " || return 0
               red_ping "${destino:-1.1.1.1}" ;;
            3) red_puertos ;;
            0) return 0 ;;
            *) echo "Opcion invalida." ;;
        esac
    done
}
