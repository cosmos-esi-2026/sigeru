#!/usr/bin/env bash
# ============================================================================
#  SiGeRU 2026 - Firma digital de los respaldos
#
#  QUE RESUELVE: el .sha256 que deja backup.sh prueba que el archivo NO SE DANO (integridad), pero no prueba
#  QUIEN lo hizo: quien entre al servidor puede cambiar el respaldo y recalcular el .sha256. La firma digital
#  agrega eso: se firma el respaldo con una clave PRIVADA que solo tiene quien administra, y cualquiera puede
#  comprobar la firma con la clave PUBLICA (que se guarda FUERA del servidor, por ejemplo en otro equipo).
#
#  COMO SE USA:
#    ./firma-respaldo.sh generar-claves             crea el par de claves (una sola vez)
#    ./firma-respaldo.sh firmar ARCHIVO             deja ARCHIVO.firma (firma RSA-SHA256 desprendida)
#    ./firma-respaldo.sh verificar ARCHIVO [CLAVE_PUBLICA]
#                                                   sale con 0 si la firma es de esa clave y el archivo no cambio;
#                                                   con 1 si no coincide o falta algo
#
#  DONDE GUARDA LAS CLAVES: SIGERU_DIR_FIRMA (por omision /etc/sigeru/firma):
#    respaldo-privada.pem  (permiso 600, SOLO root; nunca sale de ahi ni va al repositorio)
#    respaldo-publica.pem  (permiso 644; esta SI se copia a otro equipo y se anota su huella)
#
#  QUE NECESITA: openssl (viene con Ubuntu Server). Algoritmo: RSA de 3072 bits con SHA-256 (PKCS#1 v1.5).
#  Probado el 17/10/2026 en la PC de desarrollo (clave nueva, firma, verificacion correcta, archivo alterado
#  rechazado, firma alterada rechazada, otra clave rechazada); falta correrlo en sigeru-srv.
# ============================================================================
set -euo pipefail

DIR_FIRMA="${SIGERU_DIR_FIRMA:-/etc/sigeru/firma}"
PRIVADA="$DIR_FIRMA/respaldo-privada.pem"
PUBLICA="$DIR_FIRMA/respaldo-publica.pem"

uso() {
    sed -n '/COMO SE USA/,/con 1 si no coincide/p' "${BASH_SOURCE[0]}" | sed 's/^#  *//' >&2
    exit 2
}

[ $# -ge 1 ] || uso
ORDEN="$1"
shift

case "$ORDEN" in
    generar-claves)
        if [ -e "$PRIVADA" ]; then
            echo "Ya existe $PRIVADA: no se pisa. Para empezar de nuevo, moverla a mano." >&2
            exit 1
        fi
        mkdir -p "$DIR_FIRMA"
        chmod 700 "$DIR_FIRMA"
        umask 077
        openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:3072 -out "$PRIVADA" 2>/dev/null
        openssl pkey -in "$PRIVADA" -pubout -out "$PUBLICA"
        chmod 600 "$PRIVADA"
        chmod 644 "$PUBLICA"
        echo "Claves creadas en $DIR_FIRMA"
        echo "Huella de la clave publica (anotarla en un lugar aparte del servidor):"
        openssl pkey -pubin -in "$PUBLICA" -outform DER 2>/dev/null | openssl dgst -sha256 | sed 's/^.*= /  sha256: /'
        echo "Copiar $PUBLICA a otro equipo: sin esa copia, quien tome el servidor puede cambiar la clave y firmar lo que quiera."
        ;;
    firmar)
        [ $# -eq 1 ] || uso
        ARCHIVO="$1"
        [ -f "$ARCHIVO" ] || { echo "No existe el archivo: $ARCHIVO" >&2; exit 1; }
        [ -f "$PRIVADA" ] || { echo "No hay clave privada en $PRIVADA. Primero: $0 generar-claves" >&2; exit 1; }
        openssl dgst -sha256 -sign "$PRIVADA" -out "$ARCHIVO.firma" "$ARCHIVO"
        echo "Firmado: $ARCHIVO.firma"
        ;;
    verificar)
        [ $# -ge 1 ] && [ $# -le 2 ] || uso
        ARCHIVO="$1"
        CLAVE="${2:-$PUBLICA}"
        [ -f "$ARCHIVO" ] || { echo "No existe el archivo: $ARCHIVO" >&2; exit 1; }
        [ -f "$ARCHIVO.firma" ] || { echo "Falta la firma: $ARCHIVO.firma" >&2; exit 1; }
        [ -f "$CLAVE" ] || { echo "No existe la clave publica: $CLAVE" >&2; exit 1; }
        if openssl dgst -sha256 -verify "$CLAVE" -signature "$ARCHIVO.firma" "$ARCHIVO" >/dev/null 2>&1; then
            echo "FIRMA CORRECTA: $ARCHIVO es el que firmo esa clave y no cambio."
        else
            echo "FIRMA INCORRECTA: el archivo cambio, la firma no es suya o no es de esa clave." >&2
            exit 1
        fi
        ;;
    *)
        uso
        ;;
esac
