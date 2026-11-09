---
materia: Ciberseguridad
entrega: 3ra (certificado SSL/TLS y acceso HTTPS)
fecha: 2026-10-17
estado: HTTPS probado en XAMPP el 17/10/2026. Revisión del 06/11: sin evidencia de HTTPS instalado o probado en las tres VM Rocky.
---

# HTTPS y certificados

## 1. Qué se pide y qué hay

La letra reserva para esta entrega «implementación de certificado SSL dentro del servidor» y que se pueda probar desde un
cliente. La prueba de XAMPP y el laboratorio Rocky son entornos distintos:

| Entorno | Estado | Dónde |
|---|---|---|
| **Apache del pendrive (XAMPP)**, PC de desarrollo y de la defensa | **Hecho y probado** el 17/10/2026 | `herramientas/xampp/` |
| **`sigeru-app`** (Rocky Linux, Apache tradicional y contenedor web) | **No veo instalación ni prueba HTTPS en la carpeta** | Despliegue actual: `sistemas/manual/Manual_Despliegue_Tradicional_Docker_SiGeRU.pdf` |

El script `sistemas/base-scripts/administracion-sistemas-operativos/scripts/https/configurar-https.sh` pertenece a la etapa Ubuntu. No demuestra que HTTPS esté configurado en Rocky y habría que adaptarlo y probarlo antes de usarlo.

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
**Qué falta como evidencia:** una captura del navegador con el detalle del certificado de XAMPP y, por separado, instalación y prueba desde un cliente contra `sigeru-app` Rocky, con hostname y fecha visibles. No veo esas capturas en la carpeta.

## 4. Acceso HTTPS: forzado o no

- **En el pendrive no se fuerza.** Las pruebas de XAMPP usaron `http://localhost` y también se comprobó `https://localhost/sigeru/landing/` con certificado propio. Como es autofirmado, el navegador avisa si no se importa.
- **En las VM Rocky no se comprobó HTTPS.** El despliegue tradicional documentado sirve por el puerto 80 y Docker por el 8081. No corresponde afirmar que hoy redirigen al 443 o mandan HSTS.
- **Si se configura en Rocky**, hay que comprobar la redirección, el certificado, la cookie `Secure` y la conexión desde otra máquina. Hasta entonces es una tarea pendiente.

## 5. Cómo se prueba en `sigeru-srv` (para quien tenga acceso)

El título conserva el nombre de la VM Ubuntu anterior para mantener la estructura del documento. Esa VM ya no es el laboratorio vigente. Los comandos de abajo son una guía pendiente para `sigeru-app` Rocky; no son resultados obtenidos.

```bash
curl -vk https://192.168.56.11/sigeru/landing/   # solo después de instalar y habilitar TLS en Rocky
openssl s_client -connect 192.168.56.11:443 </dev/null
curl -sI http://192.168.56.11/sigeru/landing/    # comprobar si redirige; hoy no hay prueba de 301
```

Son comandos propuestos para la comprobación, no resultados obtenidos. Para validar el nombre del certificado hará falta uno que incluya la IP o el nombre real de la VM en SAN; el certificado de `localhost` de XAMPP no sirve para esa dirección.

## 6. Riesgos y límites

- Autofirmado: sin importar el certificado, un atacante en la red podría presentar uno propio y el usuario acostumbrado a aceptar la
  excepción no lo notaría. Por eso la huella de arriba figura en el documento: se puede comparar.
- La clave privada (`sigeru.key`) de XAMPP está sin contraseña para que Apache pueda iniciar. Si se instala una clave en Rocky, habría que protegerla con permisos 600 y comprobarlos. En el pendrive (exFAT) no hay permisos: quien tenga el pendrive tiene la clave. Es aceptable para un
  certificado de `localhost`, no para uno de producción.
- No se configuró OCSP, `Expect-CT` ni TLS mutuo: no aplican.
