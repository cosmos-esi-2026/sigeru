---
materia: Ciberseguridad
entrega: 3ra (especificación de hashing, cifrado y firma digital)
fecha: 2026-10-17
estado: Cada fila dice si está IMPLEMENTADO (y dónde se probó) o PROPUESTO (y qué falta). Nada propuesto se presenta como hecho.
---

# Especificación criptográfica de SiGeRU

Tres técnicas distintas, que no se confunden:

| Técnica | Qué garantiza | ¿Es reversible? | Ejemplo en SiGeRU |
|---|---|---|---|
| **Hashing** | Que un dato no cambió, o que alguien conoce un secreto sin guardarlo | No | Contraseñas (bcrypt), enlaces de acceso, huellas de respaldos |
| **Cifrado** | Que nadie lea un dato sin la clave (confidencialidad) | Sí, con la clave | Conexión HTTPS/TLS y SMTP con TLS; **no** hay cifrado de datos guardados |
| **Firma digital** | Quién produjo un dato y que no cambió (autenticidad e integridad) | No aplica | Firma de los respaldos |

## 1. Hashing

| Uso | Algoritmo y parámetros | Estado | Dónde / cómo se probó |
|---|---|---|---|
| Contraseñas de usuario | `password_hash(..., PASSWORD_DEFAULT)` = **bcrypt**, costo 10 (PHP 8.2), sal aleatoria de 128 bits por contraseña dentro del hash; verificación con `password_verify` (tiempo constante) | **Implementado** | `models/Usuario.php`; el seed tiene un hash **distinto** por usuario; prueba unitaria: todos los usuarios entran con la clave de prueba y ninguno con la vieja |
| Respuesta del login cuando el correo no existe | Se hace igual un `password_verify` contra un hash falso | **Implementado** | Evita que el tiempo de respuesta revele si el correo existe |
| Enlaces de acceso (crear o recuperar contraseña) | Token de 32 bytes del generador seguro del sistema (`random_bytes`); en la base solo `SHA-256(token)` | **Implementado** | `EnlaceClave`; unas 30 pruebas HTTP (sección 7b). Aquí SHA-256 rápido alcanza porque el token ya tiene 256 bits de entropía (no se puede adivinar ni probar por diccionario) |
| Integridad de los respaldos | `sha256sum` del `.tar.gz`, verificado al terminar y al restaurar | **Implementado** | `scripts/respaldo/backup.sh`, `restaurar.sh` (2da entrega) |
| Integridad de las librerías | SHA-256 de Leaflet contra los publicados por su sitio | **Implementado** | `frontend-usuarios/vendor/leaflet/LEEME.md`; prueba unitaria |
| Identificadores de archivos y de enlaces en disco | Nombre al azar de 128 bits (fotos); `SHA-256(correo)` (correo de prueba) | **Implementado** | `FotoIncidencia`, `Correo` |
| Huella del certificado TLS | SHA-256 | **Implementado** | `https-y-certificados-3ra.md` |

**Qué se eligió y por qué:** bcrypt porque está en PHP sin dependencias, es lento a propósito (frena la prueba masiva de claves) y
guarda su propia sal. Argon2id sería más moderno (`PASSWORD_ARGON2ID`); no se cambió porque depende de cómo se compiló PHP en cada
entorno (XAMPP, Docker y Ubuntu) y bcrypt cumple. **Propuesto:** `password_needs_rehash()` en el login para subir el costo con
el tiempo (hoy un hash viejo no se actualiza solo).

**Lo que NO se hace, a propósito:** no se usa MD5 ni SHA-1 para nada de seguridad; no se guardan contraseñas en texto ni cifradas;
`password_hash` nunca sale de la capa de modelo hacia las respuestas (`sinClave()`).

## 2. Cifrado

| Uso | Algoritmo | Estado | Dónde / cómo se probó |
|---|---|---|---|
| Navegador ↔ Apache | **TLS 1.2 y 1.3**, suites con secreto hacia adelante (`ECDHE-RSA-AES256-GCM-SHA384`, `TLS_AES_256_GCM_SHA384`), certificado RSA 2048 / SHA-256 | **Implementado en la PC** (autofirmado); **script del servidor sin probar** | `https-y-certificados-3ra.md` |
| Aplicación → servidor de correo | **SMTP con TLS** (`smtps://smtp.gmail.com:465`), verificación del certificado y del nombre del servidor (`CURLOPT_SSL_VERIFYPEER`, `VERIFYHOST=2`) | **Implementado**, **no probado contra Gmail real** (sin credenciales en la PC de desarrollo) | `api-usuarios/core/Correo.php`; pruebas con el modo archivo |
| Administración remota | **SSH**, clave Ed25519 dedicada, `root` rechazado, `fail2ban` | **Implementado** | `administracion-sistemas-operativos/ssh.md` |
| Cookie de sesión | `HttpOnly`, `SameSite=Lax`, `Secure` con HTTPS; id de sesión de 128 bits que genera PHP | **Implementado** | `Auth.php`; pruebas de seguridad |
| **Datos guardados** (base, respaldos, fotos) | — | **NO implementado** | Ver la propuesta |

**Propuesta para el cifrado en reposo (no implementada):**
- *Respaldos:* cifrarlos antes de sacarlos del servidor — `gpg --symmetric --cipher-algo AES256 respaldo.tar.gz` (clave en un gestor
  de contraseñas, nunca en el servidor) o `age`. Los respaldos contienen la base entera, con teléfonos y direcciones de vecinos.
- *Base de datos:* cifrado de tablespace de InnoDB (MySQL 8) o del disco del servidor (LUKS) si el servidor sale de una sala cerrada.
- *Fotos:* están en `almacen/` sin cifrar; cubiertas por los permisos del sistema y por no ser servidas por la web.
- *Qué datos justifican esto:* nombre, correo, teléfono y dirección de los vecinos (datos personales, Ley 18.331). Las contraseñas
  ya están protegidas por hash.

## 3. Firma digital

| Uso | Algoritmo | Estado | Dónde / cómo se probó |
|---|---|---|---|
| Autenticidad de los respaldos | **RSA 3072 con SHA-256**, firma desprendida (`archivo.firma`), clave privada solo en el servidor (600) y **clave pública copiada fuera** | **Implementado y probado en la PC de desarrollo**; falta correrlo en `sigeru-srv` | `scripts/respaldo/firma-respaldo.sh`; `pruebas/prueba-firma-respaldo.sh`: 10 de 10 (firma correcta, archivo alterado, firma alterada, otra clave, sin firma) |
| Código y entregas | Commits y etiquetas firmados con GPG (`git tag -s`) | **PROPUESTO** | El equipo no tiene claves GPG cargadas en GitHub |
| Certificado TLS | Autofirmado (firma el propio certificado) | **Implementado** | Ver `https-y-certificados-3ra.md` para el límite de confianza |

**Cómo se usa la firma de respaldos:** `firma-respaldo.sh generar-claves` (una vez) → después de cada `backup.sh`,
`firma-respaldo.sh firmar /backups/sigeru-AAAAMMDD-HHMM.tar.gz` → para restaurar o auditar, en otro equipo,
`firma-respaldo.sh verificar archivo.tar.gz clave-publica.pem`. Qué agrega al `.sha256`: quien entre al servidor puede cambiar un
respaldo y recalcular su `.sha256`, pero no puede producir una firma válida sin la clave privada; y si la clave pública ya está
guardada afuera, tampoco puede cambiarla sin que se note.
**Límite:** si roban la clave privada del servidor se pueden firmar respaldos falsos; la protección es el permiso 600 y
guardar una copia de la huella de la clave pública en papel o en otro equipo.
