#!/usr/bin/env bash
# ============================================================================
#  SiGeRU 2026 - Instalacion de las rutinas de respaldo en el servidor
#
#  QUE HACE:
#    1. Copia backup.sh, restaurar.sh y backup.conf a /opt/sigeru/respaldo/.
#       Si ya hay un backup.conf editado, NO lo pisa: deja el nuevo como
#       backup.conf.nuevo para compararlos.
#    2. Si al lado esta la carpeta ../admin (el script de administracion),
#       la copia a /opt/sigeru/admin/ y deja el comando "sigeru-admin" en
#       /usr/local/sbin.
#    3. Crea /backups (solo root puede entrar: ahi adentro esta la base entera).
#    4. Crea el log /var/log/sigeru-backup.log.
#    5. Instala la tarea diaria de las 03:00 en /etc/cron.d/sigeru-backup.
#
#  COMO SE CORRE (desde una copia del repositorio en el servidor):
#    cd sigeru/administracion-sistemas-operativos/scripts/respaldo
#    sudo ./instalar.sh
#
#  QUE NECESITA: correr como root, y que exista cron (viene con Ubuntu Server).
#  Se puede volver a correr las veces que haga falta: actualiza los scripts y
#  respeta la configuracion que ya estaba.
# ============================================================================

# -e: para en el primer error. -u: variable sin definir es error.
# -o pipefail: en "a | b" cuenta tambien el error de a.
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "Este instalador tiene que correr como root: sudo $0" >&2
    exit 1
fi

DIR_SCRIPT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DESTINO="${SIGERU_DESTINO:-/opt/sigeru}"
DIR_RESPALDOS="${SIGERU_DIR_RESPALDOS:-/backups}"
ARCHIVO_LOG="${SIGERU_ARCHIVO_LOG:-/var/log/sigeru-backup.log}"

echo "== Instalando las rutinas de respaldo de SiGeRU en $DESTINO =="

# 1) scripts de respaldo ------------------------------------------------------
install -d -m 750 "$DESTINO/respaldo"
install -m 750 "$DIR_SCRIPT/backup.sh"    "$DESTINO/respaldo/backup.sh"
install -m 750 "$DIR_SCRIPT/restaurar.sh" "$DESTINO/respaldo/restaurar.sh"
if [ -f "$DESTINO/respaldo/backup.conf" ]; then
    install -m 640 "$DIR_SCRIPT/backup.conf" "$DESTINO/respaldo/backup.conf.nuevo"
    echo "   backup.conf ya existia: se respeta. El nuevo quedo como backup.conf.nuevo."
else
    install -m 640 "$DIR_SCRIPT/backup.conf" "$DESTINO/respaldo/backup.conf"
fi
echo "   backup.sh, restaurar.sh y backup.conf en $DESTINO/respaldo/"

# 2) script de administracion (si esta) ---------------------------------------
if [ -f "$DIR_SCRIPT/../admin/sigeru-admin.sh" ]; then
    install -d -m 750 "$DESTINO/admin/lib"
    install -m 750 "$DIR_SCRIPT/../admin/sigeru-admin.sh" "$DESTINO/admin/sigeru-admin.sh"
    install -m 640 "$DIR_SCRIPT"/../admin/lib/*.sh "$DESTINO/admin/lib/"
    ln -sfn "$DESTINO/admin/sigeru-admin.sh" /usr/local/sbin/sigeru-admin
    echo "   sigeru-admin.sh y sus modulos en $DESTINO/admin/ (comando: sigeru-admin)"
fi

# 3) carpeta de respaldos ------------------------------------------------------
install -d -m 700 "$DIR_RESPALDOS"
echo "   carpeta de respaldos: $DIR_RESPALDOS (solo root)"

# 4) log -----------------------------------------------------------------------
touch "$ARCHIVO_LOG"
chmod 640 "$ARCHIVO_LOG"
echo "   log: $ARCHIVO_LOG"

# 5) cron ----------------------------------------------------------------------
# /etc/cron.d exige: dueno root, permisos 644, y un nombre sin punto.
install -m 644 -o root -g root "$DIR_SCRIPT/cron/sigeru-backup" /etc/cron.d/sigeru-backup
echo "   tarea diaria (03:00) en /etc/cron.d/sigeru-backup"
if command -v systemctl >/dev/null 2>&1 && [ -d /run/systemd/system ]; then
    systemctl is-active --quiet cron && echo "   cron esta corriendo." \
        || echo "   AVISO: cron no esta activo; arrancarlo con: systemctl enable --now cron"
else
    echo "   (no hay systemd en esta maquina: si es un contenedor, cron no corre solo)"
fi

echo
echo "Listo. Pasos siguientes:"
echo "  1. Revisar $DESTINO/respaldo/backup.conf (usuario y clave de MySQL, ruta del codigo)."
echo "  2. Probar a mano: $DESTINO/respaldo/backup.sh"
echo "  3. Mirar el resultado en $DIR_RESPALDOS y en $ARCHIVO_LOG"
