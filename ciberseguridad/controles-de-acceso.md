---
materia: Ciberseguridad
entrega: 1ra (controles de acceso y roles de usuarios)
fecha: 2026-09-14
---

# Controles de acceso y roles de usuarios — SiGeRU

No es un documento nuevo desde cero: describe, con el lente de Ciberseguridad,
el control de acceso que Programación Full Stack ya construyó y probó el
03/09 y el 09/09 (ver el checklist de la 2da entrega, sección 2). Acá se
explica el **modelo** (roles, sesión, autorización); la implementación vive en
`api-usuarios/core/Auth.php` y es idéntica en `api-gestion/core/Auth.php`.

## 1. Modelo de roles

| Rol | Qué puede hacer (resumen) |
|---|---|
| **Administrador** | Todo: alta/baja/modificación de usuarios, contenedores, camiones, centros, maquinaria; aprobar o rechazar solicitudes de registro |
| **Operario** | Rol operativo intermedio (según los 32 casos de uso, ligado a la gestión diaria de recolección) |
| **Cuadrilla** | Rol de campo, ligado a las rutas y recolecciones que le asigna un Operario/Administrador |
| **Vecino** | Solo consulta pública (contenedores, camiones) y reporte de incidencias; no puede crear ni modificar recursos administrativos |

Los cuatro roles son una constante cerrada en el código
(`Usuario::ROLES`), no un campo de texto libre: cualquier valor que no esté en
esa lista se rechaza con `400` antes de tocar la base.

## 2. Cómo se identifica quién hace una petición

1. El login (`POST /login`) verifica email + contraseña contra el hash
   guardado, y si coincide, `Auth::iniciarSesion()` guarda **solo el
   `id_usuario`** en la sesión de PHP — nunca el rol, nunca el email.
2. `session_regenerate_id(true)` cambia el identificador de sesión en ese
   momento, para que una sesión fijada de antemano (*session fixation*) no
   sirva.
3. La cookie de sesión (`SIGERUSESSID`) es `httponly` (JavaScript no puede
   leerla) y `SameSite=Lax` (no viaja en peticiones disparadas desde otro
   sitio), con `path=/` para que las dos APIs y el frontend, que se sirven
   desde el mismo origen bajo Apache, compartan la misma sesión.
4. En **cada** petición siguiente, `Auth::identificar()` vuelve a consultar la
   base por `id_usuario` y trae el rol y el estado **actuales** — no lo que
   guardó la sesión al loguearse. Consecuencia directa: si un Administrador da
   de baja a alguien que tiene la sesión abierta, la próxima petición de esa
   persona ya no pasa (estado ≠ `activo`).

## 3. Cómo se autoriza una operación

`Auth::exigirRol($rol)` es el único punto por el que pasa una operación que
requiere un rol específico:

```php
public static function exigirRol(string $rol): array
{
    $usuario = self::identificar();
    if ($usuario === null)               Response::error(..., 401); // no hay sesión
    if ($usuario["estado"] !== "activo") Response::error(..., 403); // cuenta dada de baja
    if ($usuario["rol"] !== $rol)        Response::error(..., 403); // rol distinto
    return $usuario;
}
```

Distinción entre `401` y `403`, que sí importa: `401` es "no sé quién sos",
`403` es "sé quién sos y no alcanza". Cada endpoint administrativo (crear,
modificar, dar de baja, aprobar solicitudes) empieza con una llamada a
`exigirRol("Administrador")` antes de tocar el modelo — la autorización nunca
queda en manos del modelo ni del frontend.

Las consultas de solo lectura que son públicas por diseño (listar
contenedores, listar camiones) no llaman a `exigirRol`: el control de acceso
es explícito por endpoint, no una regla global que haya que recordar apagar.

## 4. Reglas de negocio que refuerzan el control de acceso

- Un Administrador **no puede darse de baja a sí mismo** (evita quedarse sin
  ninguna cuenta administrativa activa).
- El registro público (`POST /solicitudes`) nunca crea una cuenta activa: crea
  una solicitud `pendiente`, y solo un Administrador puede aprobarla y elegir
  con qué rol entra la persona (`CU-011`).
- El rol de un usuario **nunca** sale del cuerpo de la petición que hace esa
  misma persona: en el alta, lo decide el Administrador que llama al
  endpoint; en la aprobación de una solicitud, también.

## 5. Verificación

Probado con tests automáticos (`tests/correr-tests.php`, sección 1 —
"Control de acceso"): sin sesión no se puede listar usuarios (401), sin sesión
no se puede crear un administrador (401), falsificar la cabecera de rol que
se usaba antes del 03/09 ya no sirve (401), con sesión de administrador sí se
puede listar (200), un Vecino no puede listar usuarios (403) ni crear
contenedores (403) pero sí puede consultarlos (200). Corrida completa: **51 de
51** (ver `pruebas/2026-09-14-prueba-validaciones-backend.txt`).
