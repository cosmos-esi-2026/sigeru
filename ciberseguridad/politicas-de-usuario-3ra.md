---
materia: Ciberseguridad
entrega: 3ra (políticas de seguridad de usuario, consolidadas)
fecha: 2026-10-17
estado: Revisión del 06/11 contra el código actual y las VM Rocky. Las prácticas aún sin evidencia quedan indicadas.
---

# Políticas de seguridad de usuario

Junta en un solo lugar lo que estaba repartido entre `politica-contrasenas.md`, `controles-de-acceso.md` y
`politicas-y-medidas-de-seguridad.md`, más lo que se agregó en octubre. Una política sin forma de comprobarse es un deseo: la
columna «Cómo se cumple» indica dónde está y qué prueba la vigila.

## 1. Cuentas y contraseñas

| Política | Cómo se cumple |
|---|---|
| El administrador define la contraseña inicial de las cuentas que crea o aprueba | Desde el 30/10 no se usa el enlace por correo: `POST /usuarios` y la aprobación piden `password`; la cuenta nace activa. Falta acordar y documentar cómo se entrega esa clave sin exponerla |
| Largo mínimo de 8 caracteres | `Validacion::contrasena`; la clave de prueba del seed ahora cumple (prueba unitaria) |
| Contraseñas guardadas con hash, sin recuperación posible | bcrypt con sal propia; «recuperar» = que el administrador defina una nueva (no hay correo ni «Olvidé mi contraseña») |
| Sin recuperación automática | Toda contraseña perdida la restablece un administrador; no hay correo ni «Olvidé mi contraseña» |
| El sistema no revela si una cuenta existe al iniciar sesión | Respuesta equivalente con credencial inexistente o contraseña mala; los perfiles generales entran con correo y la Cuadrilla con cédula. El registro público sí avisa si el correo ya existe (limitación aceptada) |
| Una cuenta dada de baja no entra | `estado = inactivo` se comprueba en **cada** pedido; pruebas de la sección 9 |

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
| 5 intentos fallidos de entrar en 15 minutos bloquean esa credencial | `IntentosLogin`; 429 con `Retry-After`; cuenta por correo normalizado o cédula normalizada según el perfil, y se reinicia con un acceso correcto |
| Los pedidos públicos vigentes (registro y solicitud) tienen tope por IP | `LimitePeticion`; 429; las rutas de recuperar y crear contraseña por enlace fueron retiradas el 30/10 |

## 4. Mínimo privilegio

| Política | Cómo se cumple |
|---|---|
| Cada rol hace lo mínimo que necesita | Tabla de permisos por recurso y método, **negada por defecto**: `ampliacion-matriz-de-permisos-3ra.md`; probada con los cuatro roles y sin sesión |
| Lo ajeno no se confirma que exista | Una incidencia o recolección ajena responde 404. La foto de incidencia ya no es una función vigente |
| El administrador es el único rol que ve datos agregados, la auditoría y los datos de las personas | Estadísticas, auditoría y usuarios: solo `Administrador` |
| Los servicios de la base y del servidor corren con el mínimo | **Parcial:** XAMPP de desarrollo usa `root`; en el despliegue tradicional Rocky se creó `sigeru@192.168.56.11` con permisos sobre la base `sigeru` (`sistemas/CHECKLIST.md` §3.1). Falta revisar los privilegios efectivos de ambos despliegues |

## 5. Privacidad (Ley 18.331 de protección de datos personales)

| Política | Cómo se cumple |
|---|---|
| Se pide solo lo necesario | La pantalla de registro pide solo nombre, apellido, correo y contraseña (la API admite además teléfono y dirección, opcionales) |
| Quién reportó solo lo ve la administración | Ni la cuadrilla ni otros vecinos ven su nombre ni su id (ni en el historial); **el personal se muestra al vecino por su rol, no por su nombre** (decisión del 17/10) |
| La página pública no muestra personas | `/publico/mapa` trae lugares y totales; una prueba falla si aparece un `@`, un teléfono o un id de usuario |
| No se reciben fotos de incidencias | La función se retiró el 30/10; los archivos y clases de la versión anterior no prueban que siga activa |
| Nada sensible en direcciones web ni en registros | No hay enlaces de acceso con token. La auditoría no guarda el cuerpo de los pedidos, donde puede viajar una contraseña |
| Las exportaciones no llevan contraseñas | El CSV de reportes no debe incluir hashes. La exportación de usuarios mencionada en la versión anterior necesita comprobarse contra las pantallas actuales antes de afirmarla |
| **Pendiente:** derecho de acceso, rectificación y baja de datos del vecino | Hoy la baja es lógica (se conservan sus reportes); falta un procedimiento y una pantalla |

## 6. Responsabilidades

| Quién | Responde por |
|---|---|
| Administrador del sistema | Crear cuentas con el rol justo, dar de baja a quien se va, revisar la auditoría, no compartir su sesión |
| Equipo desarrollador | Parches, pruebas, el repositorio sin secretos (`.env`, claves y contraseñas fuera de Git), el estado de los respaldos |
| Cada persona usuaria | Cuidar su contraseña, cerrar sesión en equipos compartidos, avisar de cualquier uso raro |

## 7. Incidentes: qué se hace

1. Detectar: pantalla **Auditoría** (muchos «Negado», accesos a deshoras), bloqueos 429, y los registros de Apache.
2. Contener: dar de baja la cuenta (rige en el pedido siguiente) y, según el entorno afectado, detener XAMPP o el servicio de la VM. No veo un ensayo de respuesta a incidentes en Rocky.
3. Investigar: la auditoría dice quién, qué, cuándo y desde qué IP; el historial de incidencias y de contenedores no se puede editar.
4. Recuperar: restaurar desde un respaldo comprobado. Hay una prueba de restauración de archivo y de base MySQL en `sistemas/manual/Manual_Backup_Cron_SiGeRU.pdf`; no veo firma ni huella SHA-256 integradas al script actual de Rocky.
5. Aprender: anotar en el vault y agregar la prueba que habría detectado el problema.
