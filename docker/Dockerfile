# ============================================================================
#  SiGeRU 2026 - Imagen del servidor web
#
#  PROBADA el 11/09/2026 (ver docker-compose.yml): construye sin cambios y los
#  los tests pasan contra ella. Reproduce el stack que ya funciona en XAMPP:
#  Apache + PHP 8.2 + pdo_mysql.
#
#  Se parte de la imagen oficial php:8.2-apache porque es exactamente la
#  combinacion que usa XAMPP (PHP 8.2 detras de Apache con mod_php), no
#  php-fpm + nginx, que seria otro modelo de despliegue.
# ============================================================================

FROM php:8.2-apache

# pdo_mysql conecta a la base, curl envía correo por SMTP con TLS y gd vuelve a dibujar las fotos
# de las incidencias (así se les sacan los datos de ubicación de la cámara). La imagen oficial
# ya incluye las demás extensiones usadas por el proyecto.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev libjpeg-dev libpng-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql curl gd \
    && rm -rf /var/lib/apt/lists/*

# Las fotos de las incidencias se guardan fuera de la carpeta publica. En el contenedor van a una carpeta
# que Apache (www-data) puede escribir aunque el codigo venga montado desde el host.
RUN mkdir -p /var/almacen-sigeru && chown www-data:www-data /var/almacen-sigeru

# Las tres APIs dependen de su .htaccess con RewriteRule para funcionar como
# router. Sin mod_rewrite, cualquier ruta que no sea la raiz da 404.
RUN a2enmod rewrite headers

# Cabeceras de seguridad (las pone el .htaccess de la raiz y necesitan mod_headers) y menos datos del servidor:
# sin version de Apache ni de PHP en las respuestas.
RUN printf 'ServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-available/seguridad.conf \
    && a2enconf seguridad
RUN printf 'expose_php = Off\n' > /usr/local/etc/php/conf.d/seguridad.ini

# Y sin AllowOverride, Apache ignora esos .htaccess aunque mod_rewrite este
# activo. Es el mismo ajuste que hace sigeru.conf en XAMPP.
RUN printf '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/sigeru.conf \
    && a2enconf sigeru

# Misma zona horaria que el php.ini del pendrive. Sin esto el contenedor usa
# UTC y las fechas que muestra el sistema quedan tres horas adelantadas.
ENV TZ=America/Montevideo
RUN printf 'date.timezone = America/Montevideo\n' \
    > /usr/local/etc/php/conf.d/zona-horaria.ini

# La raiz redirige a la landing, igual que en XAMPP.
RUN printf '<?php header("Location: /sigeru/landing/index.html"); ?>' \
    > /var/www/html/index.php

EXPOSE 80
