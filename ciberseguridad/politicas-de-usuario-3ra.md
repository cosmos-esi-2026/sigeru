---
materia: Ciberseguridad
entrega: 3ra (políticas de seguridad de usuario, consolidadas)
fecha: 2026-10-17
estado: Cada política dice cómo se hace cumplir en el sistema (código o configuración) y quién responde por ella.
---

# Políticas de seguridad de usuario

Junta en un solo lugar lo que estaba repartido entre `politica-contrasenas.md`, `controles-de-acceso.md` y
`politicas-y-medidas-de-seguridad.md`, más lo que se agregó en octubre. Una política sin forma de comprobarse es un deseo: la
columna «Cómo se cumple» indica dónde está y qué prueba la vigila.

## 1. Cuentas y contraseñas

| Política | Cómo se cumple |
|---|---|
| Nadie, ni el administrador, conoce la contraseña de otra persona | Las cuentas nuevas nacen `pendiente` y la persona crea su contraseña con un enlace de un solo uso (1 hora); la que envíe el administrador se ignora. Pruebas HTTP 7b |
| Largo mínimo de 8 caracteres | `Validacion::contrasena`; la clave de prueba del seed ahora cumple (prueba unitaria) |
| Contraseñas guardadas con hash, sin recuperación posible | bcrypt con sal propia; recuperar = crear una nueva por enlace |
| Recuperación solo para vecinos | Administrador, operario y cuadrilla las renueva un administrador (evita tomar una cuenta privilegiada con solo el correo) |
| El sistema no revela si una cuenta existe al recuperar o iniciar sesión | Misma respuesta en ambos casos; el registro público sí avisa si el correo ya existe (limitación aceptada) |
| Una cuenta dada de baja no entra ni recibe enlaces | `estado = inactivo` se comprueba en **cada** pedido; pruebas de la sección 9 |

## 2. Sesiones

| Política | Cómo se cumple |
|---|---|
| La sesión vive en el servidor; el navegador solo guarda un id | Cookie `SIGERUSESSID` con `HttpOnly`, `SameSite=Lax` y `Secure` bajo HTTPS |
| Al iniciar sesión se cambia el id, y un id impuesto desde afuera no sirve | `session_regenerate_id`, `use_strict_mode`; prueba de fijación de sesión |
| Cerrar sesión la invalida en el servidor | `session_destroy`; prueba: la cookie vieja da 401 |
| **Una sesión sin actividad por 2 horas se cierra sola** | `Auth::INACTIVIDAD_MAXIMA`; prueba unitaria de la regla (no hay prueba HTTP: no se puede envejecer una sesión desde afuera) |
| El rol y el estado se leen de la base en cada pedido | Un cambio de rol o una baja rige al instante, sin esperar a que la persona vuelva a entrar |

## 3. Bloqueo y abuso

| Política | Cómo se cumple |
|---|---|
| 5 intentos fallidos de entrar en 15 minutos bloquean ese correo | `IntentosLogin`; 429 con `Retry-After`; el conteo no distingue mayúsculas y se reinicia con un acceso correcto |
| Los pedidos públicos (registro, solicitud, recuperar, crear contraseña) tienen tope por IP: 10 por hora (30 el último) | `LimitePeticion`; 429; pruebas 22m |
| Un enlace de acceso: 1 hora, un solo uso, y emitir otro invalida el anterior; no más de uno cada 5 minutos por cuenta | `EnlaceClave` |

## 4. Mínimo privilegio

| Política | Cómo se cumple |
|---|---|
| Cada rol hace lo mínimo que necesita | Tabla de permisos por recurso y método, **negada por defecto**: `ampliacion-matriz-de-permisos-3ra.md`; probada con los cuatro roles y sin sesión |
| Lo ajeno no se confirma que exista | Una incidencia, recolección o foto ajena responde 404 |
| El administrador es el único rol que ve datos agregados, la auditoría y los datos de las personas | Estadísticas, auditoría y usuarios: solo `Administrador` |
| Los servicios de la base y del servidor corren con el mínimo | **Pendiente:** hoy la aplicación usa `root` en XAMPP; en el servidor, el usuario `sigeru` de la base |

## 5. Privacidad (Ley 18.331 de protección de datos personales)

| Política | Cómo se cumple |
|---|---|
| Se pide solo lo necesario | La pantalla de registro pide solo nombre, apellido, correo y contraseña (la API admite además teléfono y dirección, opcionales) |
| Quién reportó solo lo ve la administración | Ni la cuadrilla ni otros vecinos ven su nombre ni su id (ni en el historial); **el personal se muestra al vecino por su rol, no por su nombre** (decisión del 17/10) |
| La página pública no muestra personas | `/publico/mapa` trae lugares y totales; una prueba falla si aparece un `@`, un teléfono o un id de usuario |
| Las fotos no revelan dónde estaba la persona | Se vuelven a dibujar sin EXIF/GPS; solo las ven quien las subió, la administración y la cuadrilla asignada |
| Nada sensible en direcciones web ni en registros | El token del enlace sale de la barra con `history.replaceState` y `Referrer-Policy: same-origin`; la auditoría no guarda cuerpos ni parámetros |
| Las exportaciones no llevan contraseñas | CSV de usuarios sin hashes ni enlaces; prueba estática |
| **Pendiente:** derecho de acceso, rectificación y baja de datos del vecino | Hoy la baja es lógica (se conservan sus reportes); falta un procedimiento y una pantalla |

## 6. Responsabilidades

| Quién | Responde por |
|---|---|
| Administrador del sistema | Crear cuentas con el rol justo, dar de baja a quien se va, revisar la auditoría, no compartir su sesión |
| Equipo desarrollador | Parches, pruebas, el repositorio sin secretos (`.env`, claves y contraseñas fuera de Git), el estado de los respaldos |
| Cada persona usuaria | Cuidar su correo (el acceso depende de él), cerrar sesión en equipos compartidos, avisar de cualquier uso raro |

## 7. Incidentes: qué se hace

1. Detectar: pantalla **Auditoría** (muchos «Negado», accesos a deshoras), bloqueos 429, y los registros de Apache.
2. Contener: dar de baja la cuenta (rige en el pedido siguiente) y, si hace falta, `detener-xampp.cmd`.
3. Investigar: la auditoría dice quién, qué, cuándo y desde qué IP; el historial de incidencias y de contenedores no se puede editar.
4. Recuperar: `restaurar.sh` desde un respaldo cuya firma y huella se verificaron.
5. Aprender: anotar en el vault y agregar la prueba que habría detectado el problema.
