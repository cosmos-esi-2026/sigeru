#!/usr/bin/env bash
# Prueba de firma-respaldo.sh: firma y verificacion correctas, y rechazo de un archivo alterado, de una firma
# alterada, de otra clave y de una firma que falta. Usa una carpeta temporal; no toca /etc/sigeru.
# Uso:  ./prueba-firma-respaldo.sh      (sale con 0 si todo da lo esperado)
set -uo pipefail

AQUI="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FIRMA="$AQUI/../scripts/respaldo/firma-respaldo.sh"
T="$(mktemp -d)"
trap 'rm -rf "$T"' EXIT
export SIGERU_DIR_FIRMA="$T/claves"
fallos=0

espero() {
    local descripcion="$1" esperado="$2"
    shift 2
    if "$@" >/dev/null 2>&1; then obtenido=0; else obtenido=1; fi
    if [ "$obtenido" = "$esperado" ]; then echo "  ok   $descripcion"; else echo "  FALLA $descripcion (salio $obtenido, se esperaba $esperado)"; fallos=$((fallos + 1)); fi
}

echo "Prueba de firma-respaldo.sh ($(date '+%Y-%m-%d %H:%M'), $(hostname))"
espero "genera el par de claves" 0 bash "$FIRMA" generar-claves
espero "no pisa unas claves que ya existen" 1 bash "$FIRMA" generar-claves
echo "contenido de un respaldo de prueba" > "$T/sigeru-prueba.tar.gz"
espero "firma un archivo" 0 bash "$FIRMA" firmar "$T/sigeru-prueba.tar.gz"
espero "verifica el archivo sin cambios" 0 bash "$FIRMA" verificar "$T/sigeru-prueba.tar.gz"
cp "$T/sigeru-prueba.tar.gz" "$T/original"
echo "x" >> "$T/sigeru-prueba.tar.gz"
espero "rechaza un archivo alterado" 1 bash "$FIRMA" verificar "$T/sigeru-prueba.tar.gz"
cp "$T/original" "$T/sigeru-prueba.tar.gz"
cp "$T/sigeru-prueba.tar.gz.firma" "$T/firma.original"
printf 'A' | dd of="$T/sigeru-prueba.tar.gz.firma" bs=1 seek=5 conv=notrunc 2>/dev/null
espero "rechaza una firma alterada" 1 bash "$FIRMA" verificar "$T/sigeru-prueba.tar.gz"
cp "$T/firma.original" "$T/sigeru-prueba.tar.gz.firma"
SIGERU_DIR_FIRMA="$T/otras" bash "$FIRMA" generar-claves >/dev/null 2>&1
espero "rechaza la firma frente a la clave publica de otra persona" 1 bash "$FIRMA" verificar "$T/sigeru-prueba.tar.gz" "$T/otras/respaldo-publica.pem"
espero "acepta la clave correcta indicada a mano" 0 bash "$FIRMA" verificar "$T/sigeru-prueba.tar.gz" "$T/claves/respaldo-publica.pem"
rm -f "$T/sigeru-prueba.tar.gz.firma"
espero "rechaza un archivo sin firma" 1 bash "$FIRMA" verificar "$T/sigeru-prueba.tar.gz"
espero "firmar un archivo que no existe falla" 1 bash "$FIRMA" firmar "$T/no-existe"

if [ "$fallos" -eq 0 ]; then echo "Todo bien."; else echo "$fallos prueba(s) fallaron."; fi
exit "$fallos"
