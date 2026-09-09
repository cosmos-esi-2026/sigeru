# ============================================================================
#  SiGeRU 2026 - Imagen del servidor web
#
#  ATENCION: no probada. Docker no esta instalado en la maquina de desarrollo
#  (el proyecto corre desde un pendrive con XAMPP portable). Esta escrita para
#  reproducir el stack que ya funciona en XAMPP: Apache + PHP 8.2 + pdo_mysql.
#
#  Se parte de la imagen oficial php:8.2-apache porque es exactamente la
#  combinacion que usa XAMPP (PHP 8.2 detras de Apache con mod_php), no
#  php-fpm + nginx, que seria otro modelo de despliegue.
# ============================================================================

FROM php:8.2-apache

# pdo_mysql es la unica extension que hay que agregar: es la que usa
# config/database.php. Las demas que necesita el proyecto (mbstring, json)
# vienen de fabrica en la imagen oficial.
RUN docker-php-ext-install pdo_mysql

# Las dos APIs dependen de su .htaccess con RewriteRule para funcionar como
# router. Sin mod_rewrite, cualquier ruta que no sea la raiz da 404.
RUN a2enmod rewrite

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
