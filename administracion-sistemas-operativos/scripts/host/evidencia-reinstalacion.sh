#!/usr/bin/env bash
# Reinstala en la VM la versión de SiGeRU previamente desplegada.
# Se ejecuta por etapas en una consola virtual para documentar salidas reales.
set -euo pipefail

ETAPA="${1:?falta la etapa}"
PAQUETE=/tmp/sigeru-reinstalacion-20261012.tar.gz
DESTINO=/var/www/sigeru
COPIA=/var/www/sigeru-respaldo-20261012
PREPARADO=/var/www/sigeru-preparado-20261012

export TERM=linux
setfont /usr/share/consolefonts/Uni2-TerminusBold32x16.psf.gz
clear
printf 'SiGeRU - reinstalacion documentada en Ubuntu Server\n'
printf 'Etapa %s | servidor: ' "$ETAPA"
hostname
printf '%s\n\n' '-------------------------------------------------------'

case "$ETAPA" in
  1)
    echo '1. Identificar la maquina virtual y el sistema operativo'
    set -x
    lsb_release -ds
    ip -4 -br address show enp0s3
    df -h /var/www
    set +x
    ;;
  2)
    echo '2. Comprobar paquetes y servicios ya instalados'
    set -x
    dpkg-query -W -f='${binary:Package} ${Version}\n' apache2 php mysql-server openssh-server
    php -v | head -n 1
    systemctl is-active apache2 mysql ssh
    set +x
    ;;
  3)
    echo '3. Confirmar el paquete recibido de la PC anfitriona'
    set -x
    ls -lh "$PAQUETE"
    sha256sum "$PAQUETE"
    tar -tzf "$PAQUETE" | sed -n '1,8p'
    set +x
    ;;
  4)
    echo '4. Respaldar y preparar una copia limpia del proyecto'
    set -x
    test ! -e "$COPIA"
    cp -a "$DESTINO" "$COPIA"
    mkdir -p "$PREPARADO"
    tar -xzf "$PAQUETE" -C "$PREPARADO" --strip-components=1
    test -f "$PREPARADO/landing/index.html"
    test -f "$PREPARADO/api-usuarios/index.php"
    du -sh "$COPIA" "$PREPARADO"
    set +x
    ;;
  5)
    echo '5. Reinstalar archivos y recargar Apache'
    set -x
    rsync -aI "$PREPARADO/" "$DESTINO/"
    chown -R www-data:www-data "$DESTINO"
    apache2ctl configtest
    systemctl reload apache2
    set +x
    ;;
  6)
    echo '6. Verificar base de datos, servicios y rutas web'
    set -x
    mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='sigeru'"
    systemctl is-active apache2 mysql ssh
    set +x
    for ruta in landing/ frontend-usuarios/login.html api-usuarios/ api-gestion/; do
      printf '%s -> ' "$ruta"
      curl -sS -o /dev/null -w '%{http_code}\n' "http://127.0.0.1/sigeru/$ruta"
    done
    ;;
  *)
    echo "Etapa desconocida: $ETAPA" >&2
    exit 2
    ;;
esac

echo
echo "ETAPA $ETAPA COMPLETADA"
touch "/tmp/sigeru-evidencia-etapa-$ETAPA.done"
sleep 12
