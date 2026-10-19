---
materia: Administración de Sistemas Operativos
entrega: 3ra (qué cambió en la aplicación que el servidor necesita)
fecha: 2026-10-17
estado: Lista de tareas para `sigeru-srv`. NADA de esto se aplicó en el servidor: esa sesión no tuvo acceso. Marcar cada ítem cuando se haga y guardar la evidencia (hostname y fecha a la vista).
---

# Qué necesita el servidor desde octubre

El código de la aplicación cambió en la 3ra entrega. Si se copia a `sigeru-srv` sin estos pasos, **partes del sistema no
funcionan** o quedan sin la protección que prometen los documentos. Orden sugerido:

## 1. Base de datos

- [ ] Aplicar las migraciones nuevas (de la `004` a la `008`: enlaces de contraseña, estado pendiente, foto, auditoría, freno por IP):
  `sudo -u www-data env SIGERU_DB_HOST=127.0.0.1 SIGERU_DB_NAME=sigeru SIGERU_DB_USER=sigeru SIGERU_DB_PASS='...' php database/migrar.php`
  (las mismas cinco variables que usa Apache; ver «Detalle para quien reproduzca esto» en `gestion-de-paquetes.md`). `php database/migrar.php --estado` muestra qué falta.
- [ ] Si se prefiere partir de cero: `schema.sql` + `datos_prueba.sql` (ya traen todo). **La clave de los usuarios de prueba pasó de `123456` a `sigeru2026`.**
- [ ] Comprobar que el usuario de la base puede crear tablas (las migraciones lo piden una vez).

## 2. PHP y Apache

- [ ] `sudo apt install php-gd` — **sin GD no se pueden subir fotos de incidencias** (el servidor contesta 503 «no puede procesar fotos»). Comprobar con `php -m | grep -i gd` y recargar Apache.
- [ ] `sudo a2enmod headers rewrite ssl` — `headers` pone las cabeceras de seguridad (Content-Security-Policy y otras) desde el `.htaccess` de la raíz; sin él faltan y las pruebas lo avisan.
- [ ] Menos datos del servidor: `ServerTokens Prod`, `ServerSignature Off` y `expose_php = Off` (el script `configurar-https.sh` pone los dos primeros).
- [ ] Carpetas que escribe Apache (`www-data`): `sudo install -d -o www-data -g www-data -m 750 /var/www/sigeru/almacen /var/www/sigeru/tmp`. `almacen/` guarda las fotos (no es público: el `.htaccess` no lo sirve) y `tmp/` los correos de prueba.
- [ ] Correo para los accesos de usuarios: en `conf-available/sigeru.conf`, `SetEnv SIGERU_SMTP_URL`, `SIGERU_SMTP_USER`, `SIGERU_SMTP_PASS`, `SIGERU_MAIL_FROM` y `SIGERU_PUBLIC_URL` (con **https** y el nombre real del servidor). Sin ellos el administrador **no puede crear usuarios** (el sistema lo avisa con un 503). La contraseña de la cuenta de correo es de aplicación y **no se guarda en el repositorio**.

## 3. HTTPS

- [ ] `sudo scripts/https/configurar-https.sh sigeru-srv` (ver `ciberseguridad/https-y-certificados-3ra.md`). Probar desde otra máquina con `curl -vk` y `openssl s_client`, y guardar la salida con captura.
- [ ] Con HTTPS, `SIGERU_PUBLIC_URL` tiene que empezar con `https://` (para que el enlace de los correos sirva).

## 4. Respaldos

- [ ] El respaldo ya incluye `/var/www/sigeru` (y con él `almacen/`, las fotos). Si `SIGERU_ALMACEN` se mueve a otra carpeta, agregarla a `RUTAS_CONFIG` en `backup.conf`.
- [ ] Firmar los respaldos: `scripts/respaldo/firma-respaldo.sh generar-claves` (una vez), copiar la **clave pública a otro equipo**, y firmar cada respaldo después de `backup.sh`. Prueba: `pruebas/prueba-firma-respaldo.sh` (10 de 10 en la PC de desarrollo).
- [ ] Probar una restauración completa **con fotos**: después de restaurar, abrir una incidencia con foto.

## 5. Comprobar que quedó bien

```bash
php /var/www/sigeru/tests/unitarias.php                                  # sin base ni servidor
SIGERU_MAIL_MODE=archivo php /var/www/sigeru/tests/correr-tests.php      # contra el sistema (necesita las variables de la base)
curl -sI https://sigeru-srv/sigeru/landing/ | grep -iE 'HTTP/|content-security|x-frame'
curl -s  https://sigeru-srv/sigeru/api-gestion/publico/mapa | head -c 200
```

Si `correr-tests.php` dice `Omitidas: 2`, el correo no está en modo archivo: es normal con SMTP real y las pruebas no mandan correos de verdad.
Los puntos 1-5 no están probados en el servidor: **son la primera tarea** antes de la defensa de Administración de S.O.

## 6. Novedades de los extras (partes 12 a 21, 19/10/2026)

- [ ] **Migraciones nuevas, de la `009` a la `016`** (operario con centro y stock, notificaciones, participación, zona del vecino, factor ambiental, claves de operación): el mismo `php database/migrar.php` del punto 1 las aplica en orden.
- [ ] **La aplicación instalable y el trabajo sin señal de la Cuadrilla (parte 21) necesitan HTTPS en el servidor:** los navegadores solo registran el service worker (y ofrecen «instalar») en `localhost` o con HTTPS. Sin HTTPS la página anda igual, pero sin instalación ni caché. Con el certificado propio del punto 3, el celular tiene que **confiar en el certificado** (instalarlo) o no lo aceptará.
- [ ] **Apache sirve `.webmanifest` con su tipo** (`AddType application/manifest+json .webmanifest`, ya está en el `.htaccess` de la raíz; hace falta `mod_mime`, que viene activo).
- [ ] **Reportes en PDF (parte 20):** el generador es propio (PHP puro), no hace falta instalar nada; solo `php-mbstring` y `php-zlib`, que vienen con `php` en Ubuntu Server (`php -m | grep -iE 'mbstring|zlib'`).
- [ ] Las pruebas HTTP nuevas dejan datos propios (`TOPE`, `TNOT`, `TPAR`, `TPAS`, `TPRED`, `TIMP`, `TRUT`, `TIDEM`) y los borran al final; si una corrida se corta, revisar que no queden restos antes de comparar la base con el seed.
