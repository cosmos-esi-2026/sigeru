---
materia: Ciberseguridad
entrega: 3ra (análisis OWASP del sitio)
fecha: 2026-10-17
estado: Hecho por la sesión autónoma de la madrugada del 17/10, sobre el sistema real en la PC de desarrollo. NO incluye el servidor `sigeru-srv`.
---

# Análisis OWASP Top 10 (2021) del sitio de SiGeRU

Se recorrieron las diez categorías contra el sistema **encendido** (Apache, PHP y MariaDB de la PC de desarrollo), con
pruebas hechas a propósito y no solo leyendo el código. Cada hallazgo tiene riesgo, evidencia, corrección y la prueba
nueva que la vigila (`tests/correr-tests-seguridad.php` y `tests/unitarias-seguridad.php`, que corren con el resto).
**Alcance:** la aplicación (landing, panel y tres APIs) y la configuración de Apache/PHP del pendrive. **Fuera de
alcance:** el servidor `sigeru-srv` (otra configuración, ver `firewall-fail2ban.md` y `ssh.md`), el navegador del
usuario y ataques de red. **No se hizo** una prueba de intrusión con herramientas automáticas (ZAP, Nikto, sqlmap):
son pruebas dirigidas, no una auditoría exhaustiva.

## 1. Resumen

| # | Categoría | Estado | Hallazgos |
|---|---|---|---|
| A01 | Control de acceso roto | **Bien**, con evidencia | Sin hallazgos nuevos |
| A02 | Fallas criptográficas | **Mejorado** | H1 (HTTPS), H2 (certificado de XAMPP) |
| A03 | Inyección | **Bien**, con evidencia | Sin hallazgos |
| A04 | Diseño inseguro | **Corregido** | H3 (pedidos públicos sin freno) |
| A05 | Configuración incorrecta | **Corregido** | H4 (banners), H5 (cabeceras), H6 (código interno ejecutable) |
| A06 | Componentes vulnerables | **Aceptado** con plan | H7 (MariaDB 10.4 de XAMPP) |
| A07 | Fallas de autenticación | **Corregido** | H8 (sesión), H9 (CSRF) |
| A08 | Integridad de software y datos | **Bien** | Sin hallazgos |
| A09 | Registro y monitoreo | **Corregido** | H10 (sin auditoría) |
| A10 | SSRF | No aplica | — |

## 2. Hallazgos, uno por uno

### H1 — Sin HTTPS (A02) · riesgo alto en una red compartida
- **Evidencia:** todo el uso normal es `http://localhost/sigeru/...`; la cookie de sesión viajaría en claro.
- **Corrección:** el Apache del pendrive ya escucha en el 443; ahora con certificado propio y solo TLS 1.2 y 1.3. Con HTTPS
  la cookie sale con `Secure` (la sesión la prende sola si la conexión es HTTPS). Para el servidor hay un script
  (`administracion-sistemas-operativos/scripts/https/configurar-https.sh`) que además redirige el 80 al 443 y manda HSTS.
  Detalle y capturas de comandos en `https-y-certificados-3ra.md`.
- **Nueva prueba:** `curl --cacert` verifica la cadena (resultado 0), `openssl s_client` muestra TLS 1.2 y 1.3, y el login
  por HTTPS devuelve `Set-Cookie: ...; secure; HttpOnly; SameSite=Lax`.
- **Pendiente:** probarlo en `sigeru-srv`; el certificado autofirmado hace que el navegador avise.

### H2 — Certificado y clave privada de XAMPP conocidos (A02) · riesgo alto
- **Evidencia:** `openssl x509` sobre `conf/ssl.crt/server.crt`: RSA de **1024 bits**, firmado con **SHA-1**, emitido en
  2009 y **vencido en 2019**; es el mismo archivo (y la misma clave privada) en todas las instalaciones de XAMPP.
- **Corrección:** certificado nuevo (RSA 2048, SHA-256, `localhost`, `127.0.0.1`, `::1`, 825 días) con
  `herramientas/xampp/generar-certificado.cmd`; `httpd-ssl.conf` apunta a él y exige TLS 1.2 o 3 (original guardado como
  `httpd-ssl.conf.original`).

### H3 — Pedidos públicos sin freno (A04) · riesgo medio
- **Evidencia:** `POST /registro` crea una cuenta activa y `POST /solicitudes` una solicitud, sin cuenta ni límite: una máquina
  podía llenar la base con miles de cuentas de vecino (y de reportes falsos).
- **Corrección:** tabla `limite_peticion` (migración 008) y `LimitePeticion::exigir()`: **10 por hora por IP** en registro,
  solicitud y recuperación, y 30 para crear contraseña con enlace; el siguiente recibe 429 con `Retry-After`. Cuenta todos
  los intentos, buenos o malos. Usa la IP de la conexión (`REMOTE_ADDR`): cambiar `X-Forwarded-For` no lo esquiva.
- **Nueva prueba:** tres rutas se mandan hasta que dicen 429 y se verifica que fue antes del undécimo pedido.
- **Límite:** detrás de un proxy toda la gente parecería una sola IP; si hace falta, configurar la IP real.

### H4 — El servidor se presentaba con sus versiones (A05) · riesgo bajo
- **Evidencia:** `Server: Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12` y `X-Powered-By: PHP/8.2.12`.
- **Corrección:** `ServerTokens Prod`, `ServerSignature Off`, `expose_php = Off` (XAMPP, Docker y el script del servidor).
- **Nueva prueba:** no hay `X-Powered-By` y `Server` no lleva número de versión.

### H5 — Páginas sin cabeceras de seguridad (A05) · riesgo medio
- **Evidencia:** `curl -I` sobre la landing: ni `Content-Security-Policy`, ni `X-Frame-Options`, ni `Referrer-Policy`.
  Una página de SiGeRU se podía meter en un marco de otro sitio (clickjacking) y no había freno contra un XSS que se colara.
- **Corrección:** el `.htaccess` de la raíz agrega, para todo lo que sirve el sistema: `Content-Security-Policy`
  (`script-src 'self'` —nada de código inline ni `eval`—, imágenes solo propias y los mosaicos de OpenStreetMap,
  `object-src 'none'`, `frame-ancestors 'self'`), `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: same-origin` (el
  enlace de activación lleva un token en la URL: no debe salir hacia otro sitio), `X-Content-Type-Options: nosniff`,
  `Permissions-Policy` (sin cámara, micrófono ni ubicación) y `Cross-Origin-Opener-Policy`. La foto de una incidencia
  manda la suya, más estricta (`default-src 'none'; sandbox`), y la general no la pisa (`setifempty`).
- **Se comprobó que no rompe nada:** 8 pantallas vistas como administrador y la landing, sin una sola violación en la consola;
  el mapa carga sus mosaicos (8 de 8).
- **Nueva prueba:** la landing, el panel y una API llevan las cabeceras; la política no tiene `unsafe-eval`.
- **Límite:** `style-src` admite estilos en línea (Leaflet y varios `style=""` los necesitan).

### H6 — El código interno de las APIs se podía ejecutar desde la web (A05) · riesgo bajo
- **Evidencia:** `GET /api-usuarios/core/Auth.php` respondía **200** (se ejecutaba el archivo; sin salida, pero cualquier
  archivo con efectos al cargarse sería un problema). El `RewriteCond !-f` dejaba pasar los archivos que existen.
- **Corrección:** en las tres APIs, `config`, `controllers`, `core` y `models` van siempre al router (404).
- **Nueva prueba:** esas rutas, más `.htaccess`, `.github`, `database`, `tests`, `docker`, `tmp` y `almacen`, no se exponen;
  tampoco se listan directorios.

### H7 — MariaDB 10.4 de XAMPP está fuera de soporte (A06) · riesgo medio, aceptado
- **Evidencia:** `SELECT VERSION()` → 10.4.32; la rama 10.4 dejó de recibir parches en junio de 2024.
- **Decisión:** se acepta para la demostración local (la base solo escucha en `127.0.0.1` y no hay datos reales). Docker usa
  MySQL 8 y el servidor el paquete de la distribución (versión por verificar); el pendrive trae además MariaDB 11.8 LTS (`herramientas/mariadb/`, soporte hasta 2030) como destino
  de una migración. Otros componentes: Bootstrap 5.3.3, Leaflet 1.9.4 (hashes verificados), PHP 8.2 (soporte de seguridad hasta fin de 2026).

### H8 — Sesión: id impuesto y cookie sin `Secure` (A07) · riesgo medio
- **Evidencia:** PHP aceptaba cualquier id de sesión que llegara en la cookie y la cookie nunca llevaba `Secure`. (La fijación de sesión ya
  estaba cubierta por el cambio de id al iniciar sesión; se probó que sigue siendo así.)
- **Corrección:** `session.use_strict_mode` y `use_only_cookies`; `secure` se prende solo con HTTPS; ya se regeneraba el id al
  iniciar sesión y se destruía al cerrarla. Mínimo de contraseña 8 caracteres y bloqueo por 5 intentos fallidos en 15 minutos (ya existían).
- **Nueva prueba:** una cookie `SIGERUSESSID=fijadaporelatacante...` no vuelve en la respuesta; y cerrar sesión invalida la
  cookie vieja en el servidor (401). *Mutación:* quitar `use_strict_mode` **no** hace fallar ninguna prueba, porque el cambio de id
  al iniciar sesión ya basta para la fijación; el modo estricto queda como defensa en profundidad.

### H9 — CSRF: dependía solo de `SameSite=Lax` (A07/A01) · riesgo bajo
- **Evidencia:** `Response::leerJson()` aceptaba cualquier `Content-Type` con un cuerpo JSON dentro.
- **Corrección:** defensa en profundidad: solo se lee `application/json`; un formulario de otro sitio no puede mandar ese tipo
  sin un permiso previo (CORS) que las APIs no dan, y la cookie `SameSite=Lax` tampoco viaja en un `POST` entre sitios.
- **Nueva prueba:** `text/plain` y `x-www-form-urlencoded` reciben 415; un formulario `multipart` no inicia sesión; otro origen no
  recibe `Access-Control-Allow-Origin`.

### H10 — Sin registro de quién hizo qué (A09) · riesgo medio
- **Evidencia:** nadie podía responder «¿quién dio de baja a este usuario?» (el propio documento de amenazas lo decía).
- **Corrección:** tabla `auditoria` (migración 007) y pantalla **Auditoría** del administrador. Registra cada cambio que salió
  bien (POST, PUT, DELETE) y cada acceso **negado** por permisos, con persona, rol, ruta, resultado e IP; **no** guarda el
  cuerpo (ahí viajan contraseñas y enlaces) ni las lecturas. Cerrar sesión recuerda quién era.
- **Nueva prueba:** unas 25 comprobaciones: se registra lo que debe, no se registra lo que no debe, no aparecen secretos, el
  filtrado, la paginación y que nadie más que el administrador la lee.
- **Límite:** la aplicación solo agrega y lee, pero el usuario de base de datos es `root`: quien tenga acceso a la base puede
  alterar la tabla. En un despliegue real: usuario con solo `INSERT` y `SELECT`, y copia de la tabla a otro servidor. No hay
  alertas automáticas ni política de retención.

## 3. Lo que se comprobó y estaba bien

- **A01 (control de acceso):** matriz de permisos probada con los cuatro roles y sin sesión en cada recurso (`ampliacion-matriz-de-permisos-3ra.md`);
  recursos ajenos responden 404 (no confirman que existan); el rol se relee de la base en cada pedido; las fotos pasan por la
  API con la misma regla de alcance.
- **A03 (inyección):** consultas preparadas en todo; pruebas con `' OR '1'='1`, `'; DROP TABLE ...`, filtros numéricos y fechas con SQL: ninguna entra.
  **XSS almacenado:** se cargó el texto `<img src=x onerror=...>` en **diez tipos de dato** (dirección y código de contenedor, centro,
  camión, maquinaria, ruta, zona, tipo de residuo, solicitud, cuenta de vecino e incidencia) y se abrieron **16 pantallas**: el
  texto aparece como texto, no se creó ningún elemento (cero `[onerror]`). Después se borraron esos datos.
- **A08 (integridad):** ninguna librería viene de un CDN; Leaflet y Bootstrap están dentro del repositorio; los CSV neutralizan fórmulas;
  la foto se vuelve a dibujar; el workflow de GitHub repite toda la verificación.
- **A10 (SSRF):** el servidor no pide nunca una URL que escriba un usuario (el único destino de red es el SMTP de las variables de entorno).

## 4. Lo que queda abierto
1. HTTPS probado en `sigeru-srv` y con un certificado que el navegador acepte (necesita un nombre público o importar la autoridad).
2. Usuario de base de datos con privilegios mínimos (hoy `root` sin contraseña en XAMPP; `sigeru` con contraseña en el servidor).
3. Alertas sobre la auditoría (muchos 403 seguidos, por ejemplo) y política de retención.
4. Una prueba con herramientas automáticas (OWASP ZAP) y una revisión por alguien que no escribió el código.
5. El registro público revela si un correo ya existe (409): es una decisión de usabilidad; el freno por IP encarece recorrer una lista.
