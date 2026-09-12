#!/usr/bin/env bash
# ============================================================================
#  SiGeRU 2026 - sigeru-admin: modulo de USUARIOS
#
#  Funciones (todas empiezan con "usuarios_", asi el menu principal las
#  encuentra solo):
#    usuarios_listar              usuarios reales del sistema (UID >= 1000)
#    usuarios_alta <nombre> [--sudo]   crea el usuario con su carpeta personal
#    usuarios_clave <nombre>      cambia la contrasena
#    usuarios_baja <nombre>       elimina el usuario y su carpeta (pide SI)
#    menu_usuarios                submenu interactivo
#
#  Se puede usar suelto, sin el menu:
#    source lib/usuarios.sh && usuarios_listar
#  o desde el script principal:
#    sigeru-admin.sh usuarios listar
#
#  Usa las herramientas que trae Ubuntu (useradd, userdel, chpasswd, passwd):
#  nada que instalar.
# ============================================================================

# Si se carga suelto, trae las funciones comunes (titulo, ok, error...).
if ! declare -F titulo >/dev/null 2>&1; then
    # shellcheck source=comun.sh
    . "$(dirname "${BASH_SOURCE[0]}")/comun.sh"
fi

# Un nombre de usuario valido en Linux: minusculas, digitos, guion y guion
# bajo, sin empezar con digito ni guion.
_usuarios_nombre_valido() {
    [[ "$1" =~ ^[a-z_][a-z0-9_-]{0,31}$ ]]
}

usuarios_listar() {
    titulo "Usuarios del sistema"
    printf '%-14s %6s  %-8s %-24s %-16s %s\n' USUARIO UID CLAVE CARPETA SHELL GRUPOS
    # Los usuarios "de verdad" tienen UID 1000 o mas (los menores son del
    # sistema: daemon, www-data...). root se muestra siempre.
    awk -F: '$3 == 0 || ($3 >= 1000 && $3 < 60000) {print $1, $3, $6, $7}' /etc/passwd |
    while read -r nombre uid carpeta shell; do
        # passwd -S: la segunda columna dice P (tiene clave), L (sin clave o
        # bloqueado: no puede entrar) o NP (clave vacia).
        estado="$(passwd -S "$nombre" 2>/dev/null | awk '{print $2}')"
        case "$estado" in
            P)  clave="si" ;;
            L)  clave="no" ;;
            NP) clave="VACIA" ;;
            *)  clave="?" ;;
        esac
        grupos="$(id -Gn "$nombre" 2>/dev/null | tr ' ' ',')"
        printf '%-14s %6s  %-8s %-24s %-16s %s\n' "$nombre" "$uid" "$clave" "$carpeta" "$shell" "$grupos"
    done
}

usuarios_alta() {
    local nombre="${1:-}" con_sudo=0
    [ "${2:-}" = "--sudo" ] && con_sudo=1
    if [ -z "$nombre" ]; then
        error "falta el nombre: usuarios_alta <nombre> [--sudo]"; return 1
    fi
    if ! _usuarios_nombre_valido "$nombre"; then
        error "'$nombre' no es un nombre valido (minusculas, digitos, - y _; hasta 32 caracteres)"; return 1
    fi
    if id "$nombre" >/dev/null 2>&1; then
        error "el usuario '$nombre' ya existe"; return 1
    fi
    titulo "Alta del usuario $nombre"
    # -m crea /home/<nombre> copiando /etc/skel; -s fija bash como shell.
    useradd -m -s /bin/bash "$nombre"
    if [ "$con_sudo" -eq 1 ]; then
        # En Ubuntu, pertenecer al grupo sudo es lo que da permiso de administrar.
        usermod -aG sudo "$nombre"
        ok "usuario '$nombre' creado con carpeta /home/$nombre y permiso de sudo."
    else
        ok "usuario '$nombre' creado con carpeta /home/$nombre (sin sudo)."
    fi
    aviso "todavia no tiene contrasena, no puede entrar: usuarios_clave $nombre"
}

usuarios_clave() {
    local nombre="${1:-}" clave
    if [ -z "$nombre" ]; then
        error "falta el nombre: usuarios_clave <nombre>"; return 1
    fi
    if ! id "$nombre" >/dev/null 2>&1; then
        error "el usuario '$nombre' no existe"; return 1
    fi
    titulo "Contrasena de $nombre"
    if [ -t 0 ]; then
        # Hay una persona: passwd la pide dos veces sin mostrarla.
        passwd "$nombre"
    else
        # Sin terminal (prueba automatizada): la clave llega por la entrada
        # estandar, una sola linea, y la aplica chpasswd.
        IFS= read -r clave || clave=""
        if [ -z "$clave" ]; then
            error "no llego ninguna contrasena por la entrada estandar"; return 1
        fi
        printf '%s:%s\n' "$nombre" "$clave" | chpasswd
    fi
    ok "contrasena de '$nombre' actualizada."
}

usuarios_baja() {
    local nombre="${1:-}"
    if [ -z "$nombre" ]; then
        error "falta el nombre: usuarios_baja <nombre>"; return 1
    fi
    if [ "$nombre" = "root" ] || [ "$nombre" = "$(logname 2>/dev/null || echo "${SUDO_USER:-}")" ]; then
        error "no se puede dar de baja a root ni al usuario con el que se esta trabajando"; return 1
    fi
    if ! id "$nombre" >/dev/null 2>&1; then
        error "el usuario '$nombre' no existe"; return 1
    fi
    titulo "Baja del usuario $nombre"
    echo "Se borra el usuario Y su carpeta /home/$nombre con todo lo que tenga."
    if ! confirmar "Seguro?"; then
        aviso "cancelado, no se toco nada."; return 0
    fi
    # Si tiene procesos corriendo, userdel se niega: se cierran primero.
    pkill -u "$nombre" 2>/dev/null || true
    # -r borra tambien la carpeta personal y el buzon de correo.
    userdel -r "$nombre" 2>/dev/null || userdel "$nombre"
    ok "usuario '$nombre' eliminado."
}

menu_usuarios() {
    local opcion nombre
    while true; do
        titulo "Usuarios"
        echo "  1) Listar usuarios"
        echo "  2) Dar de alta un usuario"
        echo "  3) Cambiar contrasena"
        echo "  4) Dar de baja un usuario"
        echo "  0) Volver"
        preguntar opcion "Opcion: " || return 0
        case "$opcion" in
            1) usuarios_listar ;;
            2) preguntar nombre "Nombre del nuevo usuario: " || return 0
               preguntar opcion "Con permiso de sudo? (s/N): " || opcion=""
               if [ "$opcion" = "s" ]; then usuarios_alta "$nombre" --sudo; else usuarios_alta "$nombre"; fi ;;
            3) preguntar nombre "Usuario: " || return 0; usuarios_clave "$nombre" ;;
            4) preguntar nombre "Usuario a eliminar: " || return 0; usuarios_baja "$nombre" ;;
            0) return 0 ;;
            *) echo "Opcion invalida." ;;
        esac
    done
}
