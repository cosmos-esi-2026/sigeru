# Ciberseguridad

Materia electiva, docente **Gabriela Borba** (la misma de Ingeniería de
Software). Nunca se había entregado nada: se detectó el 13/09/2026, a un día
de la 2da entrega general, y se resolvieron **la 1ra y la 2da entrega juntas**
el 14/09 — mismo criterio de puesta al día que ya usó el equipo con los RF de
incidencias (03/09) y la adenda de maquinaria (09/09): se documenta como
ampliación hecha ahora, no como si se hubiera entregado en su fecha original.

## Qué hay

| Archivo | Qué es | Entrega |
|---|---|---|
| `Ciberseguridad_1ra_y_2da_Entrega_SiGeRU.pdf` | El documento único de la materia, con las dos entregas | **1ra y 2da** |
| `amenazas-y-vulnerabilidades.md` | Modelo STRIDE aplicado a SiGeRU, vulnerabilidades históricas (encontradas y corregidas) y vigentes (aceptadas con plan) | 1ra |
| `politica-contrasenas.md` | Largo mínimo, hashing, por qué no hay reglas de complejidad forzada | 1ra / 2da |
| `controles-de-acceso.md` | Roles, sesión de servidor, `Auth::exigirRol()` | 1ra |
| `politicas-y-medidas-de-seguridad.md` | Síntesis: mapa de políticas y los 5 principios que ordenan las decisiones | 1ra |
| `validaciones-frontend-backend.md` | Qué valida el HTML (ya existía) y qué se sumó en el backend (`core/Validacion.php`) | 2da |
| `encriptacion-contrasenas.md` | `password_hash`/`password_verify`, por qué es hashing y no cifrado | 2da |
| `esquema-de-red-y-seguridad.md` + `Esquema_Red_Seguridad_SiGeRU.png` | Diagrama del perímetro, los tres servicios y el único canal de administración | 2da |
| `firewall-fail2ban.md` | `ufw` + `fail2ban` instalados y probados contra `sigeru-srv` real | 2da |
| `config/fail2ban/sigeru.conf` | Jail de `fail2ban`, listo para copiar a `/etc/fail2ban/jail.d/` | 2da |
| `analisis-owasp-3ra.md` | Análisis OWASP Top 10 con pruebas dirigidas: 10 hallazgos corregidos, lo que estaba bien y lo que queda abierto | **3ra** |
| `especificacion-criptografica-3ra.md` | Hashing, cifrado y firma digital: qué está implementado y qué es propuesta | **3ra** |
| `https-y-certificados-3ra.md` | Certificado TLS propio, prueba desde un cliente, acceso HTTPS y el script para el servidor | **3ra** |
| `politicas-de-usuario-3ra.md` | Contraseñas, sesiones, bloqueo, mínimo privilegio, privacidad, responsabilidades e incidentes | **3ra** |
| `pruebas/` | Transcripciones sin editar: la sesión completa de firewall/fail2ban y la corrida de tests con las validaciones nuevas | 2da |

## Cambios de código que salieron de esta materia

- `api-usuarios/core/Validacion.php` (nuevo): formato de email, largo mínimo
  de contraseña.
- `api-usuarios/controllers/UsuarioController.php`: usa `Validacion` en
  `crear()`, `actualizar()` y `crearSolicitud()`.
- `tests/correr-tests.php`: 4 pruebas nuevas en la sección "Validaciones".
  **51 de 51, todas en verde** (antes: 47 de 47).
- `sigeru-srv`: `ufw` y `fail2ban` instalados y activos (no solo
  documentados — ver `pruebas/2026-09-14-prueba-firewall-fail2ban.txt`).

## Por qué se pudo resolver tan rápido

La mitad de lo que pide la letra de esta materia ya existía como consecuencia
de decisiones tomadas en otras — hashing de contraseñas y control de acceso
por rol (Programación Full Stack, 03/09 y 09/09), endurecimiento de SSH
(Administración de Sistemas Operativos, 13/09). Ciberseguridad no repite ese
trabajo: lo audita, lo documenta con su propio lente, y construye encima lo
que faltaba de verdad (validación de formato en el backend, y el firewall más
`fail2ban`, que no existían en ningún lado).

## Lo que queda para la 3ra entrega

Certificado SSL, informe OWASP, hashing/cifrado/firma digital como documento
de especificación propio — son ítems que la letra reserva explícitamente para
esa entrega. Además, documentado como riesgo aceptado en
`amenazas-y-vulnerabilidades.md`: bloqueo de login por intentos fallidos a
nivel aplicación, tabla de auditoría de acciones administrativas, y pasar
`PasswordAuthentication` a `no` en SSH una vez cargadas las claves de los
cuatro integrantes.
