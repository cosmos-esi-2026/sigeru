---
materia: Ciberseguridad
entrega: 2da (validaciones aplicadas en frontend y backend)
fecha: 2026-09-14
---

# Validaciones en frontend y backend — SiGeRU

## 1. Por qué hace falta mirar las dos capas

El frontend de SiGeRU ya usaba atributos HTML5 (`required`, `type="email"`,
`maxlength`) en sus formularios. Eso ayuda a la experiencia de uso, pero
**no es un control de seguridad**: cualquiera puede mandar la petición
directo a la API con `curl` o Postman, saltando el navegador por completo.
Antes del 14/09, el backend de `api-usuarios` solo comprobaba que ciertos
campos no vinieran vacíos (`empty($datos["nombre"])`), sin mirar su formato.
Un email como `"no-es-un-email"` o una contraseña de un solo carácter pasaban
igual. Esta es la brecha real que se cerró para esta entrega.

## 2. Frontend (ya existía, sin cambios)

| Campo | Formulario | Validación HTML |
|---|---|---|
| Email | `usuarios.html`, `registro.html`, `login.html` | `type="email"`, `required` |
| Campos obligatorios (nombre, dirección, etc.) | Todos los formularios de alta | `required` |
| Longitud | Varios | `maxlength` |

Verificado con `grep` sobre `frontend-usuarios/`: los 9 formularios usan estos
atributos de forma consistente.

## 3. Backend — lo que se agregó el 14/09

Nueva clase `core/Validacion.php` en `api-usuarios` (mismo patrón que
`Auth.php`/`Response.php`: una responsabilidad, usada desde los
controladores):

```php
class Validacion
{
    public const CLAVE_MINIMA = 8;

    public static function email(string $valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function contrasena(string $valor): bool
    {
        return strlen($valor) >= self::CLAVE_MINIMA;
    }
}
```

Aplicada en `UsuarioController`:

| Endpoint | Qué valida ahora que antes no |
|---|---|
| `POST /usuarios` (alta desde el panel) | Formato de email; largo mínimo de la contraseña, si viene una |
| `PUT /usuarios/{id}` (modificar) | Formato de email, si se manda uno nuevo |
| `POST /solicitudes` (registro público) | Formato de email |

En los tres casos, la respuesta ante un dato inválido es `400` **antes** de
tocar la base de datos — ni siquiera se consulta si el email ya existe. No se
tocó `login()`: una cuenta con un email o una contraseña de antes de esta
política tiene que poder seguir entrando; la validación de formato rige altas
y modificaciones nuevas, no invalida lo que ya existía.

## 4. Por qué no se tocó todo el resto de las APIs

`api-gestion` (contenedores, camiones, centros, maquinaria, zonas, tipos de
residuo) sigue con las mismas validaciones que tenía: presencia de campos
obligatorios y pertenencia a un conjunto cerrado de valores válidos (estado,
tipo, rol), pero sin `filter_var` ni chequeos de formato porque esos campos
(código de contenedor, matrícula, dirección) no tienen un formato único que
valga la pena validar con una expresión — a diferencia de un email o una
contraseña, que sí. Se documenta como decisión de alcance, no como omisión:
el foco de esta entrega es la superficie más sensible (identidad y acceso),
que es exactamente donde vivía la brecha real.

## 5. Prueba

Cuatro pruebas nuevas en `tests/correr-tests.php`, sección 4:

```
✓ Un email sin formato valido: 400
✓ Una contrasena de menos de 8 caracteres: 400
✓ Modificar con un email invalido: 400
✓ Una solicitud de registro con email invalido: 400
```

Corridas junto con las 47 que ya existían: **51 de 51, todas en verde**
(`pruebas/2026-09-14-prueba-validaciones-backend.txt`).
