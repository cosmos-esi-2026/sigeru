#!/usr/bin/env bash
# ============================================================================
#  SiGeRU 2026 - sigeru-admin: funciones comunes a todos los modulos
#
#  No se ejecuta: se carga con "source" desde sigeru-admin.sh o desde
#  cualquier modulo de lib/ que se use suelto. Solo tiene ayudas de pantalla
#  (titulos, mensajes de exito y error) y la pregunta de confirmacion.
#  Se mantiene chico a proposito: si una funcion sirve para un solo modulo,
#  va en ese modulo, no aca.
# ============================================================================

# Colores solo si la salida es una terminal (en un log o en una tuberia no
# tiene sentido meter codigos de escape).
if [ -t 1 ]; then
    C_TIT=$'\e[1;35m'   # violeta, como el acento de los documentos del proyecto
    C_OK=$'\e[1;32m'
    C_ERR=$'\e[1;31m'
    C_FIN=$'\e[0m'
else
    C_TIT=""; C_OK=""; C_ERR=""; C_FIN=""
fi

titulo() {
    echo
    echo "${C_TIT}== $* ==${C_FIN}"
}

ok() {
    echo "${C_OK}OK:${C_FIN} $*"
}

error() {
    echo "${C_ERR}ERROR:${C_FIN} $*" >&2
    return 1
}

aviso() {
    echo "AVISO: $*"
}

# Lee una respuesta con un texto adelante, como "read -p", pero mostrando el
# texto tambien cuando la entrada no es una terminal (una prueba automatizada
# que manda las opciones por una tuberia): "read -p" solo lo muestra con tty.
#   preguntar <variable> <texto>
preguntar() {
    local __variable="$1" __texto="$2"
    [ -t 0 ] || printf '%s' "$__texto"
    read -r -p "$__texto" "$__variable"
}

# Pregunta y devuelve 0 solo si la respuesta es "SI" en mayusculas. Lee de la
# entrada estandar, asi que tambien funciona en una prueba automatizada
# (echo SI | ...). Se puede saltear con la variable SIGERU_SIN_CONFIRMAR=1.
confirmar() {
    local respuesta
    if [ "${SIGERU_SIN_CONFIRMAR:-0}" = "1" ]; then
        return 0
    fi
    preguntar respuesta "$*  Escribi SI para continuar: " || respuesta=""
    [ "$respuesta" = "SI" ]
}

# Devuelve 0 si el comando existe.
existe_comando() {
    command -v "$1" >/dev/null 2>&1
}

# Pausa hasta que se apriete Enter (solo cuando hay una persona del otro lado).
pausa() {
    if [ -t 0 ]; then
        read -r -p "Enter para seguir..." _ || true
    fi
}
