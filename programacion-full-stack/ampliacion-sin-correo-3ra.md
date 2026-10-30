---
materia: Programación Full Stack (y Ciberseguridad)
entrega: 3ra (recorte antes del congelamiento: se saca el correo)
fecha: 2026-10-30
---

# Ampliación: se saca el correo y «Olvidé mi contraseña»

Decisión del equipo el 30/10/2026, el día del congelamiento: SiGeRU **no envía correo**. Se sacó junto con la recuperación de contraseña. Es un recorte más de la simplificación
del 25/10 (`ampliacion-simplificacion-3ra.md`): menos piezas que defender y nada que dependa de un servidor de correo externo (el envío real contra Gmail nunca se pudo probar).

## 1. Qué se sacó

- `api-usuarios/core/Correo.php` (SMTP, modo archivo, bienvenida y enlace) y `models/EnlaceClave.php`.
- Rutas `POST /recuperar-clave`, `POST /crear-clave` y `POST /usuarios/{id}/enlace` (ahora 404).
- Pantallas `recuperar-clave.html` y `crear-clave.html`, y el botón «Enviar acceso» de Usuarios.
- La tabla `enlace_clave` y la migración 004 (no se renumeró: quedan 001 a 003, 005 a 011, 014 y 017 a 020).
- `iniciar-xampp-con-correo.cmd` / `.ps1`, las variables `SIGERU_SMTP_*`, `SIGERU_MAIL_*` y `SIGERU_PUBLIC_URL`, `tests/unitarias-correo.php` y el estado `pendiente` en la pantalla de Usuarios (el valor del ENUM de la base queda: ninguna cuenta nueva lo usa).
- El correo de bienvenida del registro del vecino.

## 2. Cómo se crea una cuenta ahora

| Caso | Qué pasa |
|---|---|
| El vecino se registra solo | Elige su contraseña (sin cambios) |
| El administrador crea un usuario | **Contraseña inicial obligatoria** (8 caracteres o más); la cuenta nace `activo` y el administrador se la entrega en mano |
| El administrador aprueba una solicitud de acceso (Cuadrilla) | La contraseña inicial se define en el mismo paso; sin ella, 400 y la solicitud sigue pendiente |
| Alguien perdió su contraseña | El administrador edita el usuario y escribe una nueva; con el campo vacío no se cambia. No hay «Olvidé mi contraseña» |

Los avisos del sistema son solo la campanita.

## 3. Lo que hay que decir en la defensa

- **Es una limitación aceptada, no un olvido:** la entrega de la contraseña es manual y depende de que el administrador la comunique por un medio seguro. A cambio no hay un servidor de correo que mantener ni un enlace que interceptar.
- La contraseña sigue guardándose con hash (bcrypt) y el administrador no la vuelve a ver; solo puede reemplazarla. La auditoría no guarda el cuerpo de los pedidos.
- Si el sistema creciera, lo primero que se agregaría es un enlace de un solo uso por correo (está en `respaldo-partes/antes-de-sacar-correo-20261030.zip` y en `ciberseguridad/retiradas/ampliacion-acceso-por-enlace-3ra.md`).

## 4. Verificación (30/10/2026)

- **654 unitarias y 0 fallas** (eran 671: salen las 17 de `unitarias-correo.php`).
- **995 por HTTP y 0 fallas, 1 omitida** (el trazado por calles sin `SIGERU_TRAZADO_MODO=recta`). La sección 7b se reescribió: alta sin contraseña (400), con menos de 8 caracteres (400), alta correcta, restablecer, contraseña vieja que ya no sirve, y las tres rutas retiradas con 404. Ya no hace falta arrancar Apache con `SIGERU_MAIL_MODE=archivo`.
- Huella de la base con el seed de pruebas: **`232ba1b2758a7a3e`**, la misma de antes (`enlace_clave` no entraba en la huella). 21 tablas del modelo y 5 de soporte = **26**.
- A ojo en el navegador: el login muestra «¿La perdiste? Pedísela a la administración», «Nuevo usuario» pide la contraseña y Usuarios ya no tiene «Enviar acceso». Sin errores en la consola.

## 5. Lo que no se verificó

- Aprobar una solicitud desde la pantalla «Solicitudes» a mano (se probó por HTTP, no con el navegador).
- Los PDF del integrador de la 3ra y de los manuales **no se regeneraron**: hay que correr `doc_integrador_3ra.py` y `verificar_integrador.py --3ra`.
- Los documentos de las partes 11 a 27 que hablan del correo describen el sistema de su fecha y no se tocaron.
