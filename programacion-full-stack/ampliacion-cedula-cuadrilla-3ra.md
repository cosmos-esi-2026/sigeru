# Ampliación — cédula de la cuadrilla y paneles de acceso (3ra entrega, 30–31/10/2026)

Pedido de Fornos el día del congelamiento: (1) que el panel izquierdo del inicio de sesión se mueva, (2) que la cuadrilla pida
su acceso con la cédula y después inicie sesión con **cédula y contraseña**, y (3) rediseñar el panel izquierdo del registro de
vecino y del pedido de cuadrilla, que habían quedado genéricos. Plan de trabajo: `plan-acceso-cuadrilla-cedula.md` (raíz).

## 1. Qué se cambió

| Tema | Antes | Ahora |
|---|---|---|
| Panel del login | Foto fija (`acceso-poster.jpg`): el video se había sacado en la parte 27 | Escena animada solo con CSS: la calle (`acceso-calle.jpg`) pasa y el camión (`acceso-camion.png`) queda fijo al centro. Sin archivo de video |
| Entrar | Solo correo + contraseña | Dos pestañas: **Vecino y personal** (correo) y **Cuadrilla** (cédula). `?cuadrilla` abre directo la de cédula |
| Pedido de cuadrilla | Nombre, apellido, correo, teléfono, cuadrilla | Suma la **cédula** (obligatoria, con dígito verificador). El pedido aprobado deja la cédula en la cuenta |
| Registro de vecino | Texto sobre fondo verde | Una tarjeta de reclamo que avanza de «Informado» a «Resuelto» |
| Pedido de cuadrilla (panel) | Texto sobre fondo verde | Un mapa con la ruta del día que se recorre parada por parada |
| Administración | — | Usuarios y Solicitudes muestran la cédula (con puntos y guion) y la piden al crear una cuenta de rol Cuadrilla o al aprobar un pedido |

## 2. Cédula

- Se guarda **solo con dígitos** (7 u 8; el último es el verificador) en `usuario.cedula` (única) y `solicitud_registro.cedula`.
  Migración **022** (se puede repetir); `schema.sql` y `dump_estructura.sql` al día.
- Validación: solo dígitos, puntos, guion y espacios; 7 u 8 dígitos; verificador con los pesos `2987634`
  (`Validacion::cedula()` en PHP y `cedulaUruguayaValida()` en `js/cedula.js`, que es el mismo cálculo).
  `1.234.567-2` es válida y `1.234.567-3` no.
- Es **obligatoria para toda cuenta nueva de rol Cuadrilla**: en el pedido público, al crearla el administrador y al pasar a alguien
  a ese rol. En los demás roles es opcional. No se puede repetir entre usuarios (409) ni tener dos pedidos pendientes con la misma.
- El registro público de vecino la **ignora** (el vecino entra con correo).
- Es dato personal: solo la ve el administrador (Usuarios, Solicitudes) y la propia persona en su sesión.

## 3. Inicio de sesión

`POST /api-usuarios/login` acepta `{cedula, password}` **o** `{email, password}`. **Una cuenta de rol Cuadrilla entra solo con cédula** (decisión de Fornos, 31/10): con el correo, aunque la contraseña sea la correcta, responde 403 «Las cuadrillas ingresan con su cédula: usá la pestaña «Cuadrilla»» y no abre sesión (cuenta como intento fallido). Los demás roles siguen entrando con correo.
- El límite de intentos fallidos (5 en 15 minutos) cuenta por lo que se usó para entrar; la cédula va solo con dígitos, así que
  nunca se confunde con un correo. Una cédula sin ningún dígito responde 401 sin registrar nada (no hay a quién bloquear).
- Los mensajes distinguen el modo: «La cédula o la contraseña no coinciden» / «El email o la contraseña no coinciden».
- **Consecuencia:** una cuenta de cuadrilla sin cédula (de antes de esta parte) no puede entrar hasta que el administrador se la cargue
  en Usuarios; por eso editar una cuenta de ese rol exige la cédula. La suite entra con cédula a las cuentas de cuadrilla del seed
  (`CEDULAS_DEL_SEED` en `tests/correr-tests.php`). Los kits y manuales que digan «entrá con `cuadrilla@sigeru.uy`» hay que cambiarlos por la cédula.

## 4. Los paneles

Todo es CSS (y un SVG en línea): nada de video ni de librerías. En las tres pantallas:
- **Pausa**: botón «Pausar la animación de fondo» (`js/acceso-escena.js`), por WCAG 2.2.2.
- **Movimiento reducido**: sin animación; queda una imagen fija (reclamo «En curso», ruta a mitad de camino).
- **Modo oscuro**: el fondo del panel usa `--barra` (en modo oscuro `--verde-oscuro` se aclara y el texto blanco no se leía).
- **Celular (≤ 850 px)**: el panel decorativo se oculta, como antes.
- Son ilustraciones (`aria-hidden`) con una leyenda visible («Así seguís un reclamo desde tu cuenta»): no muestran datos reales.

## 5. Datos de prueba

Cédulas ficticias con verificador válido, en `datos_prueba.sql`, `datos_defensa.sql` y `usuarios-de-prueba.sql`:
A 4.100.001-9 · B 4.100.002-5 · C 4.100.003-1 · CH 4.100.004-7 · D 4.100.005-3 · E 4.100.006-9 · F 4.100.007-5 · G 4.100.008-1
(en los seeds de prueba y de defensa, `cuadrilla@sigeru.uy` = A y `carla.nunez@sigeru.uy` = B). Con la contraseña de siempre de cada seed.

## 6. Verificación

Ver el resultado de las pruebas en el checklist de la 3ra. Pruebas nuevas: `tests/unitarias-cedula.php` (sección 9 y 9b) y, en
`tests/correr-tests-cuadrillas.php`, el login por cédula, la cédula obligatoria, inválida y repetida, el pedido con cédula y la
aprobación que la lleva a la cuenta.

## 7. Qué no cubre

- La cuadrilla sigue sin crearse sola: el administrador aprueba el pedido y define la contraseña inicial.
- El correo sigue siendo obligatorio y único en `usuario` (la cuenta de cuadrilla tiene correo y cédula).
- No se probó en un celular real ni en la VM Rocky.
- `restaurar-base-demo.cmd` y `restaurar-base-defensa.cmd` fallan sobre una base sin la migración **021** (`DROP TABLE incidencia`
  choca con `foto_incidencia`, que `schema.sql` ya no borra): correr `database/migrar.php` antes de restaurar.
