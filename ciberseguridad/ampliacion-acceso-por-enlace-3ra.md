---
materia: Ciberseguridad (y Programación Full Stack)
entrega: 3ra (ampliación: activación de cuentas y recuperación de contraseña por enlace de correo)
fecha: 2026-10-17
---

# Ampliación: acceso por enlace de correo

Se construyó entre la noche del 16/10 y la madrugada del 17/10/2026 (fuera de las partes P0-P10 del plan) y se
documentó y probó el 17/10. Cambia **cómo nace una contraseña**: el administrador ya no la define ni la conoce.
La persona la crea abriendo un enlace de un solo uso que le llega a su correo.

## 1. Qué cambió

| Antes | Ahora |
|---|---|
| El administrador creaba el usuario escribiendo su contraseña | Crea el usuario sin contraseña: la cuenta nace **`pendiente`** y se envía un enlace |
| Aprobar una solicitud de registro creaba una cuenta ya activa | La cuenta nace `pendiente` y se envía el enlace al correo de la solicitud |
| Sin recuperación de contraseña | «¿Olvidaste tu contraseña?» en el login (solo vecinos) |
| Sin forma de renovar una clave | En **Usuarios**, «Enviar acceso» (administrador) |

El registro público del vecino en la landing **no cambia**: el vecino elige su contraseña al registrarse.

## 2. Piezas

- **Base de datos.** Migración `004-enlace-clave.sql` (tabla `enlace_clave`: `id_usuario`, `token_hash` CHAR(64)
  único, `vence_en`; `ON DELETE CASCADE`) y `005-estado-pendiente.sql` (`usuario.estado` pasa a
  `activo / inactivo / pendiente`). `schema.sql` ya las incluye. Con esto el modelo físico tiene 17 tablas
  (las 16 del modelo + `enlace_clave`, que es de soporte, como `intento_login` y `migracion`).
- **API (`api-usuarios`).** `POST /crear-clave` (público: token + contraseña nueva), `POST /recuperar-clave`
  (público: correo), `POST /usuarios/{id}/enlace` (administrador). `POST /usuarios` y la aprobación de solicitudes
  envían el enlace. Modelo `EnlaceClave` y `core/Correo.php`.
- **Pantallas.** `crear-clave.html` (+ `js/crear-clave.js`), `recuperar-clave.html`, retoques en `login.html`,
  `registro.html`, `js/usuarios.js`, `js/solicitudes.js` y la landing; estilos en `css/acceso.css`.
- **Arranque con correo real.** `iniciar-xampp-con-correo.cmd` / `.ps1` piden la contraseña de aplicación de Gmail
  y la ponen solo en el entorno de ese arranque (no se guarda en ningún archivo).

## 3. Propiedades de seguridad (lo que se puede defender)

| Propiedad | Cómo |
|---|---|
| El secreto no se puede adivinar | Token de 32 bytes aleatorios (`random_bytes`), 64 caracteres hexadecimales |
| Una filtración de la base no sirve | En la base está solo `sha256(token)`; el token real vive solo en el correo |
| Vida corta | Vence a la **hora** (`vence_en`, comparado con `NOW()` en el servidor de base) |
| Un solo uso | Al consumirlo se borra; se consume dentro de una transacción con `SELECT ... FOR UPDATE` (dos usos simultáneos no pasan los dos) |
| Un enlace por persona | Emitir uno nuevo borra los anteriores del mismo usuario |
| No se puede enumerar cuentas | `recuperar-clave` responde **siempre lo mismo** (200 y el mismo mensaje) exista o no el correo, o sea de un rol que no admite recuperación |
| Freno al abuso | Si ya hay un enlace con más de 55 minutos de vida, no se manda otro (un pedido cada 5 minutos por cuenta) |
| Las cuentas privilegiadas no se recuperan por correo | `recuperar-clave` solo actúa sobre cuentas **Vecino activas**; administrador, operario y cuadrilla las renueva un administrador |
| Una baja es baja | Una cuenta `inactivo` no recibe enlaces (409) ni puede consumirlos |
| Nadie conoce la contraseña de otro | Si el administrador manda un campo `password` al crear, se ignora (se sortea una aleatoria inservible hasta que la persona cree la suya) |
| Inyección de cabeceras de correo | El destino con salto de línea se rechaza; el asunto va codificado |
| Transporte | SMTP con TLS (`smtps://` o STARTTLS), verificación de certificado y de nombre de servidor |
| Enlaces correctos | `SIGERU_PUBLIC_URL` debe ser HTTPS, salvo `localhost` / `127.0.0.1` |
| Atomicidad | Si el correo no sale, se cancela la creación de la cuenta (nada queda a medias) |
| Credenciales fuera del código | Variables de entorno (`SIGERU_SMTP_*`, `SIGERU_MAIL_FROM`, `SIGERU_PUBLIC_URL`); `.env` está en `.gitignore` |
| El token sale de la barra de direcciones | `crear-clave.js` lo lee y lo borra de la URL con `history.replaceState` |

## 4. Modo de prueba: correo a archivo

Para probar sin cuenta de correo ni internet existe `SIGERU_MAIL_MODE=archivo`: en lugar de enviar, el mensaje se
escribe en `sigeru/tmp/correo/<sha256 del correo>.txt`. **Solo se activa si `SIGERU_PUBLIC_URL` apunta a
localhost** (o está vacía); en cualquier otra URL se ignora y se exige SMTP real, de modo que no puede quedar
encendido por error en un servidor. `tmp/` está en `.gitignore`.

Se prende al arrancar Apache, en el mismo terminal: `$env:SIGERU_MAIL_MODE = 'archivo'` y después `httpd.exe`.
El workflow de GitHub (`docker.yml`) y `docker-compose.yml` lo pasan al contenedor para que la verificación
automática pueda leer el enlace.

Para saber en qué modo está Apache, `GET /api-usuarios/` devuelve `"correo": "archivo" | "smtp" | "sin_configurar"`,
**solo si la petición viene de la propia máquina** (desde afuera devuelve `null`). El corredor de pruebas lo usa:
si no está en `archivo`, **omite** las pruebas que dependen de leer el mensaje (aprobar una solicitud y toda la
sección 7b) y lo dice en el resumen (`Omitidas: 2`), en vez de fallar o, peor, mandar correos reales de prueba.

## 5. Pruebas (17/10/2026)

- **Unitarias: 166 → 180** (`tests/unitarias-correo.php`): configuración exigida (SMTP completo, HTTPS salvo
  localhost, remitente con formato), modo archivo solo en localhost, modo desconocido sin efecto, el mensaje
  trae el enlace con el token y el aviso de una hora y un solo uso, asunto de activación y de recuperación,
  inyección de cabeceras rechazada.
- **HTTP: 311 → 339** (sección 7b de `tests/correr-tests.php`): alta con enlace, la contraseña enviada por el
  administrador se ignora, validaciones de `crear-clave` (corta, token inexistente, token mal formado, vencido),
  reenvío (permiso, 404, enlace nuevo invalida el anterior), uso y reuso, activación (`pendiente` → `activo`) y
  login, recuperación (vecino activo, freno, correo inexistente y administrador con la misma respuesta y sin
  correo), baja sin enlaces. Antes de esto, 4 de las 311 pruebas **fallaban** (503 por no tener SMTP): se
  reescribieron para el flujo nuevo.
- **En el navegador:** crear usuario por API → abrir el enlace del correo → crear la contraseña → «Contraseña
  guardada» → login correcto con la cuenta ya `activo`.
- **No probado:** el envío real por Gmail (no hay credenciales en esta máquina). Sí está probado todo lo que lo
  rodea, y el contenido del mensaje.

## 6. Límites

- El enlace de la demo apunta a `http://localhost/sigeru`: solo abre en la misma computadora.
- El freno de `recuperar-clave` es por cuenta, no por dirección IP.
- Los usuarios del seed tenían la clave de prueba de 6 caracteres (`123456`), por debajo de la política de 8; en la
  parte 10 (17/10) pasó a `sigeru2026`.
- El correo es texto plano sin marca; un correo real necesitaría registros SPF/DKIM del dominio remitente.
