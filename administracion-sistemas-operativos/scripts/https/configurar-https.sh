#!/usr/bin/env bash
# ============================================================================
#  SiGeRU 2026 - HTTPS en el servidor (Apache en Ubuntu Server)
#
#  QUE HACE:
#    1. Activa los modulos ssl, headers y rewrite de Apache.
#    2. Crea un certificado propio (autofirmado, RSA 2048, SHA-256, 825 dias) para el nombre
#       del servidor, su direccion IP y localhost. Si ya existe, lo conserva.
#    3. Deja /etc/apache2/conf-available/sigeru-https.conf con:
#         - el sitio seguro en el puerto 443 (solo TLS 1.2 y 1.3, cifrados fuertes, HSTS);
#         - la redireccion de todo lo que llegue por el puerto 80 hacia https.
#       Y la activa, comprueba la configuracion y recarga Apache.
#    4. Menos datos del servidor en las respuestas (ServerTokens Prod, ServerSignature Off).
#
#  COMO SE CORRE:  sudo ./configurar-https.sh [nombre-del-servidor]
#                  (si no se indica el nombre, usa "hostname -f")
#  Se puede volver a correr: no pisa el certificado y reescribe la configuracion.
#
#  ESTADO: escrito el 17/10/2026 y revisado con "bash -n". NO se probo en sigeru-srv (esa sesion no tuvo acceso al
#  servidor): la primera vez, correrlo y guardar la evidencia (curl -v, openssl s_client). Un certificado autofirmado
#  hace que los navegadores avisen; para eliminarlo hace falta un nombre publico y Let's Encrypt, o importar
#  /etc/ssl/sigeru/sigeru.crt como autoridad en cada cliente.
# ============================================================================
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "Tiene que correr como root: sudo $0" >&2
    exit 1
fi

NOMBRE="${1:-$(hostname -f)}"
DIR_SSL="${SIGERU_DIR_SSL:-/etc/ssl/sigeru}"
CRT="$DIR_SSL/sigeru.crt"
CLAVE="$DIR_SSL/sigeru.key"
CONF="/etc/apache2/conf-available/sigeru-https.conf"

echo "== HTTPS para $NOMBRE =="

command -v openssl >/dev/null 2>&1 || apt-get install -y openssl
a2enmod ssl headers rewrite >/dev/null

mkdir -p "$DIR_SSL"
chmod 750 "$DIR_SSL"

if [ -f "$CRT" ] && [ -f "$CLAVE" ]; then
    echo "Ya hay certificado en $DIR_SSL: se conserva."
else
    # Nombres para los que vale el certificado: el del servidor, localhost y cada IP de la maquina.
    SAN="DNS:$NOMBRE,DNS:localhost,IP:127.0.0.1"
    for ip in $(hostname -I 2>/dev/null); do
        case "$ip" in
            *:*) SAN="$SAN,IP:$ip" ;;
            *.*) SAN="$SAN,IP:$ip" ;;
        esac
    done
    openssl req -x509 -newkey rsa:2048 -sha256 -nodes -days 825 \
        -keyout "$CLAVE" -out "$CRT" \
        -subj "/C=UY/O=COSMOS ESI 2026/CN=$NOMBRE" \
        -addext "subjectAltName=$SAN" \
        -addext "basicConstraints=CA:FALSE" \
        -addext "keyUsage=digitalSignature,keyEncipherment" \
        -addext "extendedKeyUsage=serverAuth"
    chmod 600 "$CLAVE"
    chmod 644 "$CRT"
    echo "Certificado creado para: $SAN"
fi

cat > "$CONF" <<CONFIG
# Generado por configurar-https.sh (SiGeRU 2026). No editar a mano: se reescribe al volver a correr el script.
ServerTokens Prod
ServerSignature Off

<VirtualHost *:80>
    ServerName $NOMBRE
    ServerAlias localhost
    RewriteEngine On
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName $NOMBRE
    ServerAlias localhost
    SSLEngine on
    SSLCertificateFile $CRT
    SSLCertificateKeyFile $CLAVE
    SSLProtocol -all +TLSv1.2 +TLSv1.3
    SSLCipherSuite HIGH:!aNULL:!MD5:!RC4:!3DES:!kRSA
    SSLHonorCipherOrder off
    Header always set Strict-Transport-Security "max-age=31536000"
</VirtualHost>
CONFIG

a2enconf sigeru-https >/dev/null
apache2ctl configtest
systemctl reload apache2

echo
echo "Listo. Para comprobarlo desde otra maquina:"
echo "  curl -vk https://$NOMBRE/sigeru/landing/ 2>&1 | grep -E 'SSL connection|subject|expire|HTTP/'"
echo "  openssl s_client -connect $NOMBRE:443 -servername $NOMBRE </dev/null 2>/dev/null | grep -E 'Protocol|Cipher|Verify'"
echo "Y desde el servidor, que el puerto 80 redirige:  curl -sI http://localhost/sigeru/landing/ | head -3"
