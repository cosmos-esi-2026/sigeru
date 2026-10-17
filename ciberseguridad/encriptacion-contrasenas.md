---
materia: Ciberseguridad
entrega: 2da (encriptación de contraseñas aplicada en el proyecto)
fecha: 2026-09-14
---

# Encriptación de contraseñas — SiGeRU

Nota de vocabulario: la letra dice "encriptación", pero lo que hace SiGeRU es
más fuerte que cifrado — es **hashing** con `bcrypt`. Un cifrado se puede
revertir con la clave correcta; un hash de `bcrypt` no se puede revertir bajo
ningún caso: ni siquiera un administrador con acceso total a la base puede
recuperar la contraseña original de nadie, solo verificar si una que le dan
coincide.

## 1. Qué hace SiGeRU (desde antes del 03/09, verificado el 09/09 y vigente)

| Operación | Función de PHP | Dónde |
|---|---|---|
| Guardar una contraseña | `password_hash($clave, PASSWORD_DEFAULT)` | `models/Usuario.php::crear()` |
| Verificar un login | `password_verify($clave, $hash)` | `models/Usuario.php::login()` |
| Nunca comparar en texto plano | — | Ni un solo `===` contra `password` en todo el código |

`PASSWORD_DEFAULT` es `bcrypt` en todas las versiones de PHP hasta la fecha
(8.5, la instalada en `sigeru-srv`). Cada hash incluye su propio **salt**
aleatorio de 22 caracteres, generado por la propia función: dos usuarios con
la misma contraseña ("123456", el valor de prueba de `datos_prueba.sql")
tienen hashes completamente distintos en la base, así que un ataque de tabla
arcoíris (*rainbow table*) no sirve.

## 2. Qué nunca sale de la aplicación

`Usuario::sinClave()` quita el campo `password_hash` de cualquier registro
antes de devolverlo hacia afuera:

```php
private static function sinClave(array $u): array
{
    unset($u["password_hash"]);
    return $u;
}
```

Auditado el 09/09 (checklist de la 2da entrega, sección 2): ninguna respuesta
de la API — ni `GET /usuarios`, ni el resultado de un login, ni una solicitud
resuelta — devuelve el hash. La única consulta que sí lo trae
(`Usuario::porEmail()`, con `u.password_hash` explícito en el `SELECT`) es de
uso interno, para poder llamar a `password_verify()`, y nunca se expone
directamente a un controlador sin pasar antes por `sinClave()`.

## 3. Contraseñas generadas por el sistema

Cuando el alta de un usuario no incluye una contraseña propia (el caso normal:
ningún formulario del frontend pide una), `Usuario::crear()` genera una
provisoria:

```php
$clave = $datos["password"] ?? "";
if ($clave === "") {
    $clave = bin2hex(random_bytes(8));   // 16 caracteres hexadecimales
}
```

`random_bytes()` usa el generador criptográficamente seguro del sistema
operativo (no `rand()` ni `mt_rand()`), así que la contraseña provisoria no es
adivinable. Esa cadena pasa por el mismo `password_hash()` que cualquier otra.

## 4. Verificación

Probado indirectamente en cada corrida de `tests/correr-tests.php`: el login
con la contraseña correcta de los datos de prueba (`123456`, hasheada de
verdad en `datos_prueba.sql`) funciona, y con una incorrecta da `401`
("Contrasena incorrecta: 401", sección 4). Auditado a mano el 09/09 y de nuevo
el 14/09 junto con las validaciones nuevas: **51 de 51** pruebas en verde.

## Actualización del 17/10/2026

- La contraseña de prueba de los usuarios del seed pasó de `123456` (6 caracteres, por debajo de la política de 8) a una de 10
  caracteres, y **cada usuario del seed tiene ahora su propio hash**: antes los seis compartían uno, lo que contradecía lo que dice
  la sección 1 sobre las sales.
- La especificación completa de hashing, cifrado y firma digital (qué está implementado y qué es propuesta) está en
  `especificacion-criptografica-3ra.md`.
