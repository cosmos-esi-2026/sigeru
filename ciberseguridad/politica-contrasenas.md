---
materia: Ciberseguridad
entrega: 1ra (definición de políticas de contraseñas) / 2da (aplicada en el backend)
fecha: 2026-09-14
---

# Política de contraseñas — SiGeRU

## 1. La política

| Regla | Valor | Se aplica en |
|---|---|---|
| Largo mínimo | 8 caracteres | Backend, al crear un usuario con contraseña propia (`core/Validacion.php::contrasena()`, `api-usuarios`) |
| Complejidad forzada (mayúscula + número + símbolo) | **No exigida** | — (ver justificación abajo) |
| Almacenamiento | Nunca en texto plano. Hash con `password_hash()` (bcrypt, costo por defecto de PHP) | `models/Usuario.php::crear()` |
| Verificación | `password_verify()`, nunca comparación directa | `models/Usuario.php::login()` |
| Contraseñas generadas por el sistema | 16 caracteres hexadecimales al azar (`bin2hex(random_bytes(8))`), cuando el alta no incluye una propia | `models/Usuario.php::crear()` |
| Expiración / rotación forzada | **No exigida** | — (ver justificación abajo) |
| Reutilización de contraseñas anteriores | No se controla (no hay historial de contraseñas) | Pendiente, 3ra entrega |
| Bloqueo por intentos fallidos | No implementado a nivel aplicación (sí a nivel SSH, con `fail2ban`) | Pendiente, ver `amenazas-y-vulnerabilidades.md`, sección 4 |

## 2. Por qué el mínimo es "largo", no "complejidad"

La política se apoya en **NIST SP 800-63B** (*Digital Identity Guidelines —
Authentication and Lifecycle Management*), que desde su revisión recomienda:

- Priorizar el **largo mínimo** por sobre reglas de composición forzada.
- **No** exigir cambios periódicos de contraseña sin motivo (una contraseña
  fuerte que se rota cada 90 días termina, en la práctica, en variaciones
  predecibles: `Verano2026!`, `Verano2026!!`).
- **No** exigir combinaciones de mayúscula/número/símbolo, porque empíricamente
  llevan a los mismos patrones predecibles y no aumentan la entropía real
  tanto como el largo.

Aplicado a SiGeRU: casi ninguna contraseña la elige una persona. El circuito
real (`CU-011`, aprobación de solicitudes) genera una contraseña provisoria al
azar de 16 caracteres hexadecimales — muy por encima de cualquier política de
complejidad manual — y la única vía donde alguien podría escribir una propia
es el alta directa desde el panel de administración
(`POST /usuarios` con `password` en el cuerpo), que hoy **ningún formulario
del frontend expone** (se verificó: `frontend-usuarios/usuarios.html` no tiene
campo de contraseña). El mínimo de 8 caracteres cubre igual ese camino, por si
se usa directamente contra la API.

## 3. Qué se valida y dónde

Antes del 14/09 el backend solo comprobaba que el campo no viniera vacío
(`empty($datos["password"])`), sin mirar su largo. Ahora (`core/Validacion.php`,
`api-usuarios`):

```php
public static function contrasena(string $valor): bool
{
    return strlen($valor) >= self::CLAVE_MINIMA;   // 8
}
```

Se aplica en `UsuarioController::crear()` **solo cuando viene una contraseña
en el cuerpo** — si no viene, `Usuario::crear()` genera la provisoria al azar,
que siempre cumple el mínimo de sobra. No se aplica dentro de `login()`: una
cuenta creada antes de esta política, con una contraseña más corta, tiene que
poder seguir entrando; la política rige altas nuevas, no invalida las viejas.

Prueba automatizada (`tests/correr-tests.php`, sección 4, agregada el 14/09):
crear un usuario con contraseña de 5 caracteres devuelve `400`. Corrida
completa en `pruebas/2026-09-14-prueba-validaciones-backend.txt`
(**51 de 51 pruebas en verde**, las 47 que ya existían más las 4 nuevas de
Ciberseguridad).

## 4. Pendiente, documentado a propósito

- **Bloqueo de cuenta por intentos fallidos** (ver
  `amenazas-y-vulnerabilidades.md`, sección 4, ítem 1).
- **Historial de contraseñas** para impedir reutilizar la misma al cambiarla —
  hoy no aplica del todo porque **todavía no existe un endpoint de "cambiar mi
  contraseña"**: la única forma de fijar una es en el alta. Se deja para
  cuando ese endpoint se construya (3ra entrega), junto con el historial.
