---
materia: Ciberseguridad
entrega: 3ra (especificación de hashing, cifrado y firma digital)
fecha: 2026-10-17
estado: Revisión del 06/11 contra el código y el laboratorio Rocky. Cada fila distingue lo probado en XAMPP, lo observado en Rocky y lo pendiente.
---

# Especificación criptográfica de SiGeRU

Tres técnicas distintas, que no se confunden:

| Técnica | Qué garantiza | ¿Es reversible? | Ejemplo en SiGeRU |
|---|---|---|---|
| **Hashing** | Que un dato no cambió, o que alguien conoce un secreto sin guardarlo | No | Contraseñas (bcrypt); huellas de respaldos como propuesta para Rocky |
| **Cifrado** | Que nadie lea un dato sin la clave (confidencialidad) | Sí, con la clave | HTTPS/TLS probado en XAMPP; **no** hay cifrado de datos guardados |
| **Firma digital** | Quién produjo un dato y que no cambió (autenticidad e integridad) | No aplica | Firma de respaldos probada con el script anterior, todavía sin evidencia en Rocky |

## 1. Hashing

| Uso | Algoritmo y parámetros | Estado | Dónde / cómo se probó |
|---|---|---|---|
| Contraseñas de usuario | `password_hash(..., PASSWORD_DEFAULT)` = **bcrypt**, costo 10 (PHP 8.2), sal aleatoria de 128 bits por contraseña dentro del hash; verificación con `password_verify` (tiempo constante) | **Implementado** | `models/Usuario.php`; el seed tiene un hash **distinto** por usuario; prueba unitaria: todos los usuarios entran con la clave de prueba y ninguno con la vieja |
| Respuesta del login cuando el correo no existe | Se hace igual un `password_verify` contra un hash falso | **Implementado** | Evita que el tiempo de respuesta revele si el correo existe |
| Enlaces de acceso (crear o recuperar contraseña) | Se usó `SHA-256` sobre un token aleatorio en una versión anterior | **Retirado el 30/10** | El correo, las rutas de recuperación y la tabla `enlace_clave` salieron del sistema actual; ver `programacion-full-stack/ampliacion-sin-correo-3ra.md` |
| Integridad de los respaldos | `sha256sum` del archivo respaldado | **Pendiente en Rocky** | El script actual `sistemas/scripts/respaldo/backup.sh` usa carpetas y `rsync`, sin generar una huella SHA-256; el `.tar.gz.sha256` pertenece al script anterior de `sistemas/base-scripts/` |
| Integridad de las librerías | SHA-256 de Leaflet contra los publicados por su sitio | **Implementado** | `frontend-usuarios/vendor/leaflet/LEEME.md`; prueba unitaria |
| Identificadores de fotos y correo de prueba | Nombres aleatorios y `SHA-256(correo)` en la versión anterior | **Retirado el 30/10** | Las rutas de fotos y correo salieron de la aplicación; quedan clases antiguas sin uso, que no prueban una función vigente |
| Huella del certificado TLS de XAMPP | SHA-256 | **Probado en la PC de desarrollo** | `https-y-certificados-3ra.md`; falta certificado y huella de Rocky |

**Qué se eligió y por qué:** bcrypt porque está en PHP sin dependencias, es lento a propósito (frena la prueba masiva de claves) y
guarda su propia sal. Argon2id sería más moderno (`PASSWORD_ARGON2ID`); no se cambió porque depende de cómo se compiló PHP en cada
entorno (XAMPP y las VM Rocky con Docker) y bcrypt cumple. **Propuesto:** `password_needs_rehash()` en el login para subir el costo con
el tiempo (hoy un hash viejo no se actualiza solo).

**Lo que NO se hace, a propósito:** no se usa MD5 ni SHA-1 para nada de seguridad; no se guardan contraseñas en texto ni cifradas;
`password_hash` nunca sale de la capa de modelo hacia las respuestas (`sinClave()`).

## 2. Cifrado

| Uso | Algoritmo | Estado | Dónde / cómo se probó |
|---|---|---|---|
| Navegador ↔ Apache | **TLS 1.2 y 1.3**, suites con secreto hacia adelante (`ECDHE-RSA-AES256-GCM-SHA384`, `TLS_AES_256_GCM_SHA384`), certificado RSA 2048 / SHA-256 | **Probado en XAMPP** con certificado autofirmado; **sin evidencia en Rocky** | `https-y-certificados-3ra.md` |
| Aplicación → servidor de correo | SMTP con TLS de una versión anterior | **Retirado el 30/10** | El sistema actual no manda correos; no corresponde presentarlo como cifrado vigente |
| Administración remota | **SSH** con clave pública y `PermitRootLogin no` | **Verificado con capturas de las tres VM Rocky** | `sistemas/manual/Manual_SSH_Rocky_SiGeRU.pdf`; no veo evidencia de `fail2ban` en estas VM |
| Cookie de sesión | `HttpOnly`, `SameSite=Lax`, `Secure` con HTTPS; id de sesión de 128 bits que genera PHP | **Implementado** | `Auth.php`; pruebas de seguridad |
| **Datos guardados** (base y respaldos) | — | **NO implementado** | Ver la propuesta |

**Propuesta para el cifrado en reposo (no implementada):**
- *Respaldos:* cifrarlos antes de sacarlos del servidor, por ejemplo con GPG o `age`, y guardar la clave fuera de él. Los respaldos contienen la base entera, con teléfonos y direcciones de vecinos.
- *Base de datos:* cifrado de tablespace de InnoDB (MySQL 8) o del disco del servidor (LUKS) si el servidor sale de una sala cerrada.
- *Qué datos justifican esto:* nombre, correo, teléfono y dirección de los vecinos (datos personales, Ley 18.331). Las contraseñas
  ya están protegidas por hash.

## 3. Firma digital

| Uso | Algoritmo | Estado | Dónde / cómo se probó |
|---|---|---|---|
| Autenticidad de los respaldos | **RSA 3072 con SHA-256**, firma desprendida (`archivo.firma`) | **Probado en la PC de desarrollo con el script anterior; pendiente en Rocky** | `sistemas/base-scripts/administracion-sistemas-operativos/scripts/respaldo/firma-respaldo.sh`; no veo esa firma integrada a `sistemas/scripts/respaldo/backup.sh` ni capturas de la firma en las VM |
| Código y entregas | Commits y etiquetas firmados con GPG (`git tag -s`) | **PROPUESTO** | El equipo no tiene claves GPG cargadas en GitHub |
| Certificado TLS | Autofirmado (firma el propio certificado) | **Implementado** | Ver `https-y-certificados-3ra.md` para el límite de confianza |

**Cómo se usaba la firma con el respaldo anterior:** `firma-respaldo.sh generar-claves` (una vez) → después del `backup.sh` anterior,
`firma-respaldo.sh firmar /backups/sigeru-AAAAMMDD-HHMM.tar.gz` → para restaurar o auditar, en otro equipo,
`firma-respaldo.sh verificar archivo.tar.gz clave-publica.pem`. Esa secuencia no está integrada al respaldo actual con `rsync` en Rocky. Qué agrega al `.sha256`: quien entre al servidor puede cambiar un
respaldo y recalcular su `.sha256`, pero no puede producir una firma válida sin la clave privada; y si la clave pública ya está
guardada afuera, tampoco puede cambiarla sin que se note.
**Límite:** si roban la clave privada del servidor se pueden firmar respaldos falsos; la protección es el permiso 600 y
guardar una copia de la huella de la clave pública en papel o en otro equipo.
