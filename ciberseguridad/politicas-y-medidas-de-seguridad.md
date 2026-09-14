---
materia: Ciberseguridad
entrega: 1ra (políticas y medidas de seguridad)
fecha: 2026-09-14
---

# Políticas y medidas de seguridad — SiGeRU

Documento de síntesis: reúne, en un solo lugar, las políticas que ya están
escritas en detalle en otros documentos del proyecto (no se repiten acá) más
las que son propias de Ciberseguridad.

## 1. Mapa de políticas vigentes

| Política | Qué cubre | Dónde está el detalle |
|---|---|---|
| Contraseñas | Largo mínimo, hashing, generación al azar | `politica-contrasenas.md` |
| Control de acceso | Roles, sesión de servidor, autorización por endpoint | `controles-de-acceso.md` |
| Amenazas y vulnerabilidades | Modelo STRIDE, vulnerabilidades históricas y vigentes | `amenazas-y-vulnerabilidades.md` |
| Validaciones de entrada | Qué se valida en frontend y en backend | `validaciones-frontend-backend.md` |
| Cifrado de contraseñas | Detalle de `password_hash`/`password_verify` | `encriptacion-contrasenas.md` |
| Red y perímetro | Esquema de red, qué puertos están expuestos | `esquema-de-red-y-seguridad.md` |
| Firewall y detección de intrusos en el host | `ufw` + `fail2ban` en `sigeru-srv` | `firewall-fail2ban.md` |
| Respaldo y continuidad | RPO/RTO, regla 3-2-1, prueba de restauración mensual, plan de alta disponibilidad | `administracion-sistemas-operativos/respaldo-y-alta-disponibilidad.md` |
| Endurecimiento de acceso remoto (SSH) | `PermitRootLogin no`, `MaxAuthTries 3`, `AllowUsers`, sin X11 | `administracion-sistemas-operativos/ssh.md` |
| Red estática del servidor | IP fija, DNS, sin exposición innecesaria | `administracion-sistemas-operativos/red-estatica.md` |

Las últimas tres ya se entregaron con Administración de Sistemas Operativos
(13/09): Ciberseguridad no las reescribe, las **referencia** — son la misma
medida de seguridad vista desde otra materia, y duplicarlas arriesgaría que
las dos copias se desactualicen de forma distinta.

## 2. Principios que ordenan las decisiones de seguridad del proyecto

Estos cinco principios no están escritos en ningún archivo de configuración,
pero se pueden leer en las decisiones tomadas (con su fecha) a lo largo del
proyecto:

1. **Mínimo privilegio.** Cada rol solo puede hacer lo que su función
   necesita (`controles-de-acceso.md`); MySQL solo escucha en `localhost`,
   ni siquiera depende del firewall para no estar expuesto
   (`amenazas-y-vulnerabilidades.md`, sección 5).
2. **Defensa en profundidad.** Ningún control es el único: la base no está
   expuesta por bind address *y* el firewall la taparía si lo estuviera;
   SSH tiene `MaxAuthTries` *y* `fail2ban` *y* restricción de usuario
   (`AllowUsers`) *y* sin acceso de `root`.
3. **Nunca confiar en el cliente.** El rol no viaja en una cabecera que el
   navegador pueda escribir; las validaciones de formato viven en el backend,
   no solo en el HTML (`validaciones-frontend-backend.md`).
4. **Fallar cerrado, no abierto.** Sin sesión es `401` (rechaza), no `200`
   con datos vacíos. Un email con formato inválido es `400` antes de tocar la
   base, no un intento de guardarlo igual.
5. **Decisiones medidas, no por preferencia.** Igual que el resto del
   proyecto (ver el cálculo de puntos de función o la adenda de maquinaria):
   la política de contraseñas cita la fuente (NIST SP 800-63B) en vez de
   "parece razonable", y la lista blanca de `fail2ban` existe porque **pasó
   de verdad** durante esta misma entrega (`firewall-fail2ban.md`).

## 3. Qué queda fuera de esta entrega, y por qué

Coincide con la lista de "vulnerabilidades vigentes" de
`amenazas-y-vulnerabilidades.md`, sección 4: HTTPS (reservado por la propia
letra para la 3ra entrega), bloqueo de login por intentos fallidos a nivel
aplicación, tabla de auditoría de acciones administrativas, e IDS/IPS de red
(se evaluó y no se justifica todavía con el servidor detrás de NAT sin IP
pública). Ninguno es un olvido: cada uno tiene su razón documentada en el
lugar donde se lo evaluó.
