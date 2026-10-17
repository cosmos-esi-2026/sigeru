---
materia: Ciberseguridad
entrega: 3ra (certificado SSL/TLS y acceso HTTPS)
fecha: 2026-10-17
estado: Probado en la PC de desarrollo (hostname `frannxito`, 17/10/2026). El script del servidor `sigeru-srv` está escrito y revisado, NO probado.
---

# HTTPS y certificados

## 1. Qué se pide y qué hay

La letra reserva para esta entrega «implementación de certificado SSL dentro del servidor» y que se pueda probar desde un
cliente. Hay dos entornos y no se confunden:

| Entorno | Estado | Dónde |
|---|---|---|
| **Apache del pendrive (XAMPP)**, PC de desarrollo y de la defensa | **Hecho y probado** el 17/10/2026 | `herramientas/xampp/` |
| **`sigeru-srv`** (Ubuntu Server, Apache) | **Script escrito, sin probar** | `administracion-sistemas-operativos/scripts/https/configurar-https.sh` |

## 2. El certificado

- **Por qué no se usa el de XAMPP:** el `server.crt` que trae es RSA de 1024 bits, firmado con SHA-1, de 2009, vencido en 2019, y
  su clave privada es **la misma para todas las instalaciones**: quien la conoce (está publicada) puede hacerse pasar por el servidor.
- **El nuevo** (`herramientas/xampp/generar-certificado.cmd`; guarda `sigeru.crt` en `apache/conf/ssl.crt/` y `sigeru.key` en
  `apache/conf/ssl.key/`, que **no van al repositorio**): RSA 2048, SHA-256, válido para `localhost`, `127.0.0.1` y `::1`, 825 días
  (hasta el 19/01/2029), `CA:FALSE`, uso `serverAuth`. Huella SHA-256:
  `5A:5B:D2:83:BB:58:3A:5A:50:79:C7:57:0D:81:2C:BA:AD:C2:3B:3F:73:A1:6A:E3:F9:C4:95:69:F1:D3:D7:8F`.
- **Es autofirmado:** el navegador avisa. Para una defensa se acepta la excepción una vez; para quitar el aviso hay que importar
  `sigeru.crt` como «autoridad raíz de confianza» en esa PC. Un certificado que todos los navegadores acepten necesita un nombre
  público (por ejemplo, con Let's Encrypt), que un servidor en una red NAT interna no tiene.
- **Configuración TLS** (`apache/conf/extra/httpd-ssl.conf`, el original quedó como `.original`): `SSLProtocol -all +TLSv1.2 +TLSv1.3`,
  `SSLCipherSuite HIGH:!aNULL:!MD5:!RC4:!3DES:!kRSA` (solo suites con secreto hacia adelante).

## 3. Prueba desde un cliente (17/10/2026, `frannxito`)

```text
$ curl --cacert herramientas/xampp/apache/conf/ssl.crt/sigeru.crt -I https://localhost/sigeru/landing/index.html
HTTP/1.1 200 OK
Server: Apache
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: same-origin
Content-Security-Policy: default-src 'self'; script-src 'self'; ...

$ openssl s_client -connect localhost:443 -servername localhost -CAfile conf/ssl.crt/sigeru.crt
subject=C = UY, O = COSMOS ESI 2026, CN = localhost
issuer=C = UY, O = COSMOS ESI 2026, CN = localhost
New, TLSv1.3, Cipher is TLS_AES_256_GCM_SHA384
Server public key is 2048 bit
Verify return code: 0 (ok)

$ ... s_client -tls1_2  ->  Protocol: TLSv1.2   Cipher: ECDHE-RSA-AES256-GCM-SHA384

$ curl -i --cacert ... -H 'Content-Type: application/json' -d '{...}' https://localhost/sigeru/api-usuarios/login
Set-Cookie: SIGERUSESSID=...; path=/; secure; HttpOnly; SameSite=Lax
```

Sin `--cacert`, `curl` rechaza la conexión (el certificado no es de una autoridad conocida): es el comportamiento correcto.
**Qué falta como evidencia:** una captura del navegador con el candado y el detalle del certificado, y la misma prueba contra
`sigeru-srv` con el hostname y la fecha a la vista (regla del proyecto: una salida de texto sola no alcanza para ASO).

## 4. Acceso HTTPS: forzado o no

- **En el pendrive no se fuerza.** Toda la documentación, el `SIGERU_PUBLIC_URL` de los correos (`http://localhost/sigeru`) y las
  pruebas usan `http://localhost`. Forzar HTTPS en la PC de la defensa agregaría un aviso del navegador sin ganancia real (el tráfico
  no sale de la máquina). Se documenta que `https://localhost/sigeru/landing/` funciona.
- **En el servidor sí:** `configurar-https.sh` redirige el puerto 80 al 443 (301) y manda `Strict-Transport-Security`
  (`max-age=31536000`). Con HTTPS la cookie de sesión sale `Secure` sola.
- **Docker** sirve en el 8080 sin TLS (es un entorno de verificación, no de producción).

## 5. Cómo se prueba en `sigeru-srv` (para quien tenga acceso)

```bash
sudo ./configurar-https.sh sigeru-srv           # crea el certificado, el sitio 443, la redirección; recarga Apache
curl -vk https://sigeru-srv/sigeru/landing/ 2>&1 | grep -E 'SSL connection|subject|expire|HTTP/'
openssl s_client -connect sigeru-srv:443 -servername sigeru-srv </dev/null 2>/dev/null | grep -E 'Protocol|Cipher|Verify'
curl -sI http://sigeru-srv/sigeru/landing/ | head -3     # tiene que ser 301 hacia https
```

## 6. Riesgos y límites

- Autofirmado: sin importar el certificado, un atacante en la red podría presentar uno propio y el usuario acostumbrado a aceptar la
  excepción no lo notaría. Por eso la huella de arriba figura en el documento: se puede comparar.
- La clave privada (`sigeru.key`) está sin contraseña, como es habitual para que Apache arranque sola; se protege con permisos
  (600 en el servidor). En el pendrive (exFAT) no hay permisos: quien tenga el pendrive tiene la clave. Es aceptable para un
  certificado de `localhost`, no para uno de producción.
- No se configuró OCSP, `Expect-CT` ni TLS mutuo: no aplican.
