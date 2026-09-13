# Gestión de paquetes y despliegue de SiGeRU en sigeru-srv

**Hecho el 13/09/2026.** Todo por SSH desde la PC anfitriona.

## dpkg, apt y snap

- **`dpkg`** instala/quita/lista paquetes `.deb` uno por uno, sin resolver dependencias.
  Se usa para consultar (`dpkg -l`, `dpkg -L <paquete>`).
- **`apt`** es la que se usa a diario: resuelve dependencias contra los repositorios de
  Ubuntu (firmados, con parches de seguridad hasta 2031) y llama a dpkg por debajo.
- **`snap`** es el formato autocontenido de Canonical. No se usó para nada de este
  servidor: Apache y MySQL se instalan con `apt`, que es lo que hay que poder documentar
  y lo que coincide con XAMPP y Docker.

## Lo que se instaló

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y apache2 php libapache2-mod-php php-mysql php-curl mysql-server mysql-client git
sudo a2enmod rewrite
```

| Paquete | Versión instalada |
|---|---|
| Apache | 2.4.66 |
| PHP | **8.5.4** |
| MySQL | 8.4.11 |
| Git | 2.53.0 |

**Sobre PHP 8.5 vs 8.2:** Ubuntu Server 26.04 trae PHP 8.5 por defecto en sus repositorios;
Docker (`php:8.2-apache`) y XAMPP portable usan 8.2. No se instaló una versión distinta vía
PPA para no sumar una dependencia externa al repositorio de paquetes de Ubuntu. Es una
diferencia de entorno real, pero los 47 tests del proyecto pasan igual en el servidor
(ver más abajo), así que no generó una incompatibilidad concreta.

## Despliegue de SiGeRU

El repositorio de GitHub (`cosmos-esi-2026/sigeru`) es privado y la VM no tiene
credenciales cargadas (ni se le van a cargar por una sesión SSH no interactiva). En vez de
`git clone` desde GitHub, el código se copió directo desde la PC anfitriona por SSH
(`tar` empaquetado en el momento, sin pasar por disco intermedio) a `/var/www/sigeru`,
con dueño `www-data`.

### Base de datos

```sql
CREATE DATABASE IF NOT EXISTS sigeru CHARACTER SET utf8mb4;
CREATE USER IF NOT EXISTS 'sigeru'@'127.0.0.1' IDENTIFIED BY '<generada con openssl rand>';
GRANT ALL PRIVILEGES ON sigeru.* TO 'sigeru'@'127.0.0.1';
```

Se crea un usuario de MySQL dedicado (`sigeru`) en vez de usar `root` por TCP: en Ubuntu,
`root` de MySQL autentica por `auth_socket` (solo localmente, sin contraseña), no sirve
para una conexión PDO por `127.0.0.1:3306` como hace `config/database.php`. La contraseña
generada vive fuera del repositorio (`vm-ubuntu/ssh/sigeru-db-password.txt` en la PC
anfitriona, y en `/opt/sigeru/respaldo/backup.conf` en el servidor, permisos 640).

```bash
mysql -h127.0.0.1 -usigeru -p'...' sigeru < database/schema.sql
mysql -h127.0.0.1 -usigeru -p'...' sigeru < database/datos_prueba.sql
```

Quedaron **15 tablas**, coincidiendo con el modelo vigente desde el 09/09.

### Apache

`/etc/apache2/conf-available/sigeru.conf` (activado con `a2enconf sigeru`):

```apache
Alias /sigeru /var/www/sigeru

<Directory /var/www/sigeru>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
    SetEnv SIGERU_DB_HOST 127.0.0.1
    SetEnv SIGERU_DB_PORT 3306
    SetEnv SIGERU_DB_NAME sigeru
    SetEnv SIGERU_DB_USER sigeru
    SetEnv SIGERU_DB_PASS <la generada>
</Directory>
```

Mismo criterio que XAMPP y Docker: el proyecto se monta bajo `/sigeru`, no en la raíz, para
que las rutas del frontend sean las mismas en los tres entornos. `AllowOverride All` es
necesario porque las dos APIs dependen de su `.htaccess` con `RewriteRule` para funcionar
como router. Las variables `SetEnv` son las que lee `config/database.php` (con
`getenv()`); sin ellas, el código cae a sus valores por defecto (`root` sin contraseña,
pensados para XAMPP), que no sirven en este servidor.

**Detalle para quien reproduzca esto:** las variables `SetEnv` de Apache no llegan al PHP
que se corre por línea de comandos (por ejemplo, `tests/correr-tests.php`, que en su etapa
de limpieza conecta directo a la base sin pasar por Apache). Para correr ese script hay que
exportar las mismas cinco variables en la sesión de shell antes de invocarlo.

## Prueba: rutas y los 47 tests

```
/sigeru/landing/                      -> 200
/sigeru/frontend-usuarios/login.html  -> 200
/sigeru/api-usuarios/                 -> 200
/sigeru/api-gestion/                  -> 200

$ php tests/correr-tests.php
Pasaron: 47   Fallaron: 0
Todo verde.
```

Transcripción completa en `pruebas/2026-09-13-prueba-despliegue-servidor-real.txt`.
