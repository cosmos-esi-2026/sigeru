---
materia: Ciberseguridad
entrega: 1ra (identificación de amenazas, vulnerabilidades en infraestructura)
fecha: 2026-09-14
---

# Identificación de amenazas y vulnerabilidades — SiGeRU

## 1. Qué se protege

| Activo | Por qué importa |
|---|---|
| Base de datos MySQL (15 tablas) | Datos personales de usuarios (nombre, email, teléfono, dirección), contraseñas (hasheadas), historial operativo de la Intendencia (contenedores, camiones, incidencias, cuadrillas) |
| Sesión de servidor | Es lo único que separa a un Vecino de un Administrador. Si se puede robar o falsificar, se pierde todo el control de acceso |
| Las dos APIs (api-usuarios, api-gestion) | Superficie que recibe entrada de cualquiera, autenticado o no |
| El servidor `sigeru-srv` | Corre la base, las APIs y guarda los respaldos. Si se compromete, se compromete todo lo demás |
| Disponibilidad del servicio | La Intendencia opera la recolección con este sistema; una caída afecta un servicio público real |

## 2. Metodología

Se aplicó **STRIDE** (Spoofing, Tampering, Repudiation, Information disclosure,
Denial of service, Elevation of privilege) sobre los cuatro componentes reales
del sistema: frontend, APIs, base de datos y servidor. No es un ejercicio
teórico: la mitad de las filas de la tabla de abajo son vulnerabilidades que
**existieron de verdad** en este proyecto y se corrigieron con fecha y
evidencia — se documentan como amenaza igual, porque STRIDE describe la
categoría de ataque y esta es la prueba de que la categoría es real acá, no
en abstracto.

## 3. Amenazas por categoría STRIDE

| Categoría | Amenaza concreta contra SiGeRU | Estado |
|---|---|---|
| **S**poofing | Suplantar a un Administrador falsificando quién hace la petición | **Existió y se corrigió.** Hasta el 03/09 el rol viajaba en las cabeceras `X-Usuario-Email` / `X-Usuario-Rol`, que cualquiera podía escribir a mano. Se reemplazó por sesión real de servidor (`core/Auth.php`): la cookie no dice quién es, el rol se relee de la base en cada petición. Test automático (`tests/correr-tests.php`, sección 1) verifica que falsificar la cabecera vieja ya da 401 |
| **T**ampering | Modificar el rol propio mandándolo en el cuerpo de la petición (`POST /usuarios {"rol":"Administrador"}`) | **Existió y se corrigió** el 03/09, junto con lo anterior: `models/Usuario.php` ya no lee `rol` del cuerpo salvo cuando lo decide el controlador después de `Auth::exigirRol("Administrador")` |
| **T**ampering | Inyección SQL a través de cualquier campo (email, dirección, etc.) | **Mitigado desde el diseño.** Las seis clases modelo usan exclusivamente consultas preparadas con parámetros con nombre; auditado a mano el 09/09 (`grep` sobre los modelos, ningún `->query()` con SQL armado desde datos del usuario) |
| **R**epudiation | Nadie puede probar quién aprobó o rechazó una solicitud de registro | **Aceptado, riesgo bajo.** `solicitud_registro` guarda el estado final pero no quién la resolvió. No hay tabla de auditoría de acciones administrativas. Queda para la 3ra entrega si el tiempo lo permite; mitigado en parte porque solo un rol (Administrador) puede resolver solicitudes y el equipo es de 4 personas. **Actualización 17/10/2026: corregido** con la tabla `auditoria` (cada cambio y cada acceso negado deja quién, qué, cuándo y desde dónde; pantalla «Auditoría»), ver `analisis-owasp-3ra.md` |
| **I**nformation disclosure | Contraseñas en texto plano, comparadas contra un valor fijo (`"123456"`) | **Existió y se corrigió** antes del 03/09: `password_hash`/`password_verify` (bcrypt), y el hash nunca sale de `Usuario` (`sinClave()`) |
| **I**nformation disclosure | Un error de PHP no atajado devuelve el *stack trace* con rutas del servidor | **Mitigado desde el diseño.** `Response::manejarErrores()` intercepta toda excepción no atajada: log interno con `error_log()`, respuesta genérica de 500 hacia afuera |
| **I**nformation disclosure | La base de datos escuchando en todas las interfaces de red | **Mitigado.** MySQL en `sigeru-srv` solo escucha en `127.0.0.1:3306`; ni el firewall necesita cubrirlo, ya no es alcanzable desde la red aunque `ufw` fallara |
| **D**enial of service | Fuerza bruta contra `POST /login` o contra el servicio SSH | **Parcial.** SSH: mitigado (`fail2ban` + `MaxAuthTries 3`, ver `firewall-fail2ban.md` y `ssh.md`). **El login de la aplicación web NO tenía límite de intentos** (versión del 14/09). **Actualización 14/10/2026: corregido en la parte 2 de la mejora de la 3ra entrega:** 5 fallos en 15 minutos bloquean ese correo (429 con `Retry-After`), sin importar mayúsculas; detalle en `ampliacion-matriz-de-permisos-3ra.md` |
| **D**enial of service | Agotar la conexión del servidor abriendo miles de conexiones | **Aceptado, riesgo bajo por ahora.** Un solo servidor, sin balanceador (ver `respaldo-y-alta-disponibilidad.md`, sección de alta disponibilidad, que ya documenta el plan de un segundo nodo para la 3ra) |
| **E**levation of privilege | Un Vecino consultando o modificando datos de otro rol | **Mitigado.** `Auth::exigirRol()` corta con 403 antes de llegar al modelo; probado en la sección 1 de los tests (vecino no puede listar usuarios, no puede crear contenedores) |
| **E**levation of privilege | Iniciar sesión y que un cambio de rol/baja posterior no se refleje hasta volver a loguearse | **Mitigado desde el diseño.** `Auth::identificar()` relee el rol y el estado de la base en **cada** petición, no los guarda en la sesión |

## 4. Vulnerabilidades vigentes, aceptadas con plan

Ninguna de estas bloquea la 2da entrega; se documentan porque omitirlas sería
peor que aceptarlas con un plan:

1. **(Corregida el 14/10/2026: ver la actualización de la sección 3 y `ampliacion-matriz-de-permisos-3ra.md`.) Sin límite de intentos de login en la aplicación web.** `UsuarioController::login()`
   no contaba fallos ni bloqueaba una cuenta. Es distinto del caso de SSH (que sí
   tiene `fail2ban`) porque el login web pasa por Apache/PHP, no por `sshd`, y
   fail2ban solo lee logs de servicios del sistema. Mitigación planeada para
   la 3ra entrega: un contador de intentos fallidos por cuenta en la tabla
   `usuario` (o una tabla nueva), con bloqueo temporal — mismo principio que
   `fail2ban`, a nivel aplicación. Mitigación parcial ya vigente: las
   contraseñas activas hoy son en su enorme mayoría las generadas al azar por
   `Usuario::crear()` (16 caracteres hexadecimales), no elegidas por la
   persona, lo que ya encarece mucho un ataque de diccionario.
2. **Sin HTTPS.** *(Actualización 17/10/2026: la PC de desarrollo ya sirve HTTPS con un certificado propio y hay un script para el servidor; ver `https-y-certificados-3ra.md`. Falta probarlo en `sigeru-srv`.)* El tráfico entre el navegador y Apache viaja sin cifrar
   dentro de la red interna. Es exactamente el ítem que la letra de
   Ciberseguridad reserva para la **3ra entrega** ("Implementación de
   certificado SSL dentro del servidor"): no se adelanta acá porque un
   certificado real necesita un dominio público o una CA propia, y el
   servidor todavía vive en una red NAT interna sin nombre público.
3. **`PasswordAuthentication yes` en SSH.** Documentado y aceptado en `ssh.md`
   desde el 13/09: sigue así hasta cargar la clave de cada integrante del
   equipo. Mitigado en la práctica porque `AllowUsers cosmos2026` ya reduce
   la superficie a una sola cuenta, con `MaxAuthTries 3` y `fail2ban` encima.
4. **Sin tabla de auditoría de acciones administrativas** (quién aprobó una
   solicitud, quién dio de baja a quién). Ver *Repudiation* en la tabla de
   arriba.
5. **Un solo servidor, sin redundancia.** Ya documentado y con plan en
   `respaldo-y-alta-disponibilidad.md` — no es un hallazgo nuevo de este
   documento, se referencia porque es la misma amenaza (indisponibilidad)
   vista desde el ángulo de Ciberseguridad en vez de Administración de S.O.

## 5. Vulnerabilidades en infraestructura (servidor, base de datos, red interna)

| Componente | Vulnerabilidad posible | Estado en `sigeru-srv` |
|---|---|---|
| Servidor (SO) | Cuenta `root` accesible por SSH | Cerrado: `PermitRootLogin no` (`ssh.md`) |
| Servidor (SO) | Fuerza bruta contra SSH | Cerrado: `fail2ban` + `MaxAuthTries 3` (`firewall-fail2ban.md`) |
| Servidor (red) | Todos los puertos alcanzables desde la red | Cerrado: `ufw` con política `deny` por defecto, solo 22 y 80 permitidos (`firewall-fail2ban.md`) |
| Base de datos | MySQL alcanzable desde fuera del servidor | Cerrado: bind a `127.0.0.1` únicamente — ni siquiera depende del firewall |
| Base de datos | Usuario de MySQL con privilegios excesivos para la aplicación | **Pendiente de revisar** en la 3ra entrega: hoy la aplicación usa credenciales dedicadas (`gestion-de-paquetes.md`), pero no se verificó que el grant esté acotado a la base `sigeru` en vez de `*.*` |
| Red interna | La VM no tiene forma de detectar tráfico anómalo (escaneo de puertos, etc.) | **No hay IDS/IPS de red** (tipo Snort/Suricata) — se evaluó y se decidió no instalarlo en esta entrega: la VM vive detrás de NAT, sin IP pública, y el costo de configurarlo y afinar reglas sin generar falsos positivos no se justifica todavía. `fail2ban` cumple el rol de IDS/IPS **de host** (detecta el patrón de ataque más realista hoy, fuerza bruta SSH, y actúa solo) |
| Respaldo | Un respaldo sin verificar puede estar corrupto | Cerrado desde el 12/09: `restaurar.sh` verifica la huella SHA-256 antes de restaurar (`respaldo-y-alta-disponibilidad.md`) |

## 6. Trazabilidad

Este documento no reemplaza nada ya entregado: es la lectura de Ciberseguridad
sobre hechos que Ingeniería de Software, Programación Full Stack y
Administración de Sistemas Operativos ya registraron con su propia fecha y
evidencia. Fuentes: `ssh.md`, `red-estatica.md`, `gestion-de-paquetes.md`,
`respaldo-y-alta-disponibilidad.md`, el checklist de la 2da entrega
(`vault/mind/wiki/proyecto/Checklist 2da entrega.md`, secciones 2 y 3), y el
código fuente de `api-usuarios/` y `api-gestion/`.
