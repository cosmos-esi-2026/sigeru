# SiGeRU — casos de uso y trazabilidad revisados

**Fecha:** 05/11/2026 · **Versión:** 3.0 · **Estado:** documento para revisión del equipo y la docente.

## 1. Criterio y corte

Conservamos los 32 códigos anteriores y agregamos CU-033 a CU-039 para los recorridos del tercer avance. Hay 36 objetivos actuales de actor. CU-004 y CU-027 son controles internos; CU-024 quedó retirado con la foto. Los códigos antiguos no se reutilizan. Una ficha describe el recorrido; no demuestra por sí sola que alguien lo haya probado.

## 2. Correcciones principales

- La cuadrilla entra con cédula; los demás perfiles, con correo. La opción de foto salió de la interfaz y CU-024 queda histórico.
- El operario trabaja solo con su centro asignado. Antes esta ficha decía lo contrario.
- Se agregan rutas, recolecciones, mapa, panel, recepción, stock y notificaciones. Los repuestos e idioma siguen pendientes.
- El CSV del informe descarga el detalle visible y puede quedar truncado; no equivale a exportar el informe completo.

## 3. Relaciones UML vigentes

| Origen | Relación | Destino | Cuándo ocurre |
|---|---|---|---|
| CU-010 | extiende | CU-009 | El administrador aplica filtros al listado de usuarios. |

CU-024 ya no se dibuja. La autorización por rol y el historial de estados se explican como reglas internas, no como objetivos de un usuario.

## 4. Catálogo

| Código | Qué hace | Actor | Estado |
|---|---|---|---|
| CU-001 | Iniciar sesión | Persona registrada, antes de iniciar sesión | Recorrido actual o propuesto |
| CU-002 | Cerrar sesión | Usuario autenticado | Recorrido actual o propuesto |
| CU-003 | Solicitar registro | Persona sin cuenta (vecino o integrante de cuadrilla) | Recorrido actual o propuesto |
| CU-004 | Verificar permisos de rol | Ninguno; regla de autorización de la API | Control interno |
| CU-005 | Aprobar o rechazar solicitud de registro | Administrador municipal | Recorrido actual o propuesto |
| CU-006 | Registrar usuario | Administrador municipal | Recorrido actual o propuesto |
| CU-007 | Modificar usuario | Administrador municipal | Recorrido actual o propuesto |
| CU-008 | Deshabilitar usuario | Administrador municipal | Recorrido actual o propuesto |
| CU-009 | Consultar usuarios | Administrador municipal | Recorrido actual o propuesto |
| CU-010 | Filtrar listado de usuarios | Administrador municipal | Recorrido actual o propuesto |
| CU-011 | Asignar rol a usuario | Administrador municipal | Recorrido actual o propuesto |
| CU-012 | Registrar contenedor | Administrador municipal | Recorrido actual o propuesto |
| CU-013 | Modificar contenedor | Administrador municipal | Recorrido actual o propuesto |
| CU-014 | Dar de baja contenedor | Administrador municipal | Recorrido actual o propuesto |
| CU-015 | Consultar contenedores | Administrador municipal, Operario de centro, Cuadrilla, Vecino registrado | Recorrido actual o propuesto |
| CU-016 | Registrar camión | Administrador municipal | Recorrido actual o propuesto |
| CU-017 | Modificar camión | Administrador municipal | Recorrido actual o propuesto |
| CU-018 | Dar de baja camión | Administrador municipal | Recorrido actual o propuesto |
| CU-019 | Consultar camiones | Administrador municipal | Recorrido actual o propuesto |
| CU-020 | Administrar centros de acopio | Administrador municipal | Recorrido actual o propuesto |
| CU-021 | Actualizar capacidad del centro de acopio | Operario del centro asignado | Recorrido actual o propuesto |
| CU-022 | Administrar tipos de residuo | Administrador municipal | Recorrido actual o propuesto |
| CU-023 | Registrar incidencia | Vecino registrado | Recorrido actual o propuesto |
| CU-024 | Adjuntar evidencia fotográfica | Vecino registrado | Retirado |
| CU-025 | Asignar incidencia a cuadrilla | Administrador municipal | Recorrido actual o propuesto |
| CU-026 | Actualizar estado de incidencia | Cuadrilla asignada (avance o resolución); Administrador municipal (resolución) | Recorrido actual o propuesto |
| CU-027 | Registrar estado de contenedor | Ninguno; regla condicional de estado e historial de contenedor | Control interno |
| CU-028 | Consultar incidencias | Administrador municipal, Cuadrilla asignada, Vecino que informó | Recorrido actual o propuesto |
| CU-029 | Consultar inventario de recursos | Administrador municipal | Recorrido actual o propuesto |
| CU-030 | Generar reporte de incidencias por zona | Administrador municipal | Recorrido actual o propuesto |
| CU-031 | Administrar maquinaria del centro de acopio | Administrador municipal | Recorrido actual o propuesto |
| CU-032 | Consultar maquinaria | Administrador municipal, Operario de centro | Recorrido actual o propuesto |
| CU-033 | Armar una ruta | Administrador municipal | Recorrido actual o propuesto |
| CU-034 | Planificar una recolección | Administrador municipal | Recorrido actual o propuesto |
| CU-035 | Ejecutar una recolección | Cuadrilla asignada | Recorrido actual o propuesto |
| CU-036 | Consultar mapa | Público o usuario autenticado, según capa | Recorrido actual o propuesto |
| CU-037 | Consultar panel y alertas | Administrador municipal | Recorrido actual o propuesto |
| CU-038 | Registrar recepción y movimiento de stock | Operario del centro asignado | Recorrido actual o propuesto |
| CU-039 | Consultar notificaciones | Usuario autenticado | Recorrido actual o propuesto |

## 5. Fichas corregidas

### CU-001 — Iniciar sesión

**Actor:** Persona registrada, antes de iniciar sesión. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** A.

**Objetivo:** Entrar con la credencial que corresponde al rol: correo para los perfiles generales y cédula para la cuadrilla.

**Precondiciones:**

- La persona tiene una cuenta activa; no hay sesión iniciada.

**Flujo normal:**

1. La persona abre la pantalla de ingreso y elige su pestaña.
2. Escribe correo o cédula, según el rol, y contraseña.
3. La API comprueba la cuenta, la clave y el límite de intentos.
4. El sistema abre la sesión y muestra las opciones permitidas.

**Postcondición:** El usuario queda autenticado y visualiza únicamente las funcionalidades que su rol tiene habilitadas.

**Alternativas:**

- **A1:** Credencial o contraseña incorrecta: se informa el error sin abrir sesión.
- **A2:** Una cuadrilla intenta entrar por correo: la API rechaza el acceso y pide usar cédula.
- **A3:** Cinco fallos en quince minutos: bloqueo temporal.

**Requisitos relacionados:** RF-002, RF-164, RF-177, RF-178.

### CU-002 — Cerrar sesión

**Actor:** Usuario autenticado. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** A.

**Objetivo:** El usuario finaliza su sesión de trabajo en el sistema.

**Precondiciones:**

- El usuario debe haber iniciado sesión (CU-001).

**Flujo normal:**

1. El usuario selecciona la opción «Cerrar sesión».
2. El sistema cierra la sesión y descarta los datos de autenticación.
3. El sistema redirige a la pantalla de inicio de sesión.

**Postcondición:** La sesión queda cerrada; no se puede acceder a ninguna pantalla interna sin volver a autenticarse.

**Alternativas:**

- **A1.0:** Si la sesión ya había expirado por inactividad, el sistema redirige igualmente a la pantalla de inicio de sesión.

**Requisitos relacionados:** RF-003.

### CU-003 — Solicitar registro

**Actor:** Persona sin cuenta (vecino o integrante de cuadrilla). **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** A.

**Objetivo:** Pedir acceso desde la página pública. La cuadrilla informa una cédula válida.

**Precondiciones:**

- El solicitante accede a la landing page pública.
- No se requiere autenticación previa.

**Flujo normal:**

1. La persona abre el formulario que corresponde a su perfil.
2. Completa sus datos; si pide acceso de cuadrilla, agrega cédula.
3. El sistema valida y registra la solicitud pendiente.
4. La administración puede revisarla en Solicitudes.

**Postcondición:** La solicitud queda registrada con estado pendiente y disponible para que el administrador la evalúe.

**Alternativas:**

- **A1.0:** Si algún campo obligatorio está vacío o el correo tiene un formato inválido, el sistema señala el campo y no envía la solicitud.
- **A2.0:** Si ya existe una solicitud pendiente o un usuario registrado con el mismo correo, el sistema informa la situación y no registra una solicitud nueva.

**Requisitos relacionados:** RF-001, RF-160, RF-161, RF-181.

### CU-004 — Verificar permisos de rol

**Actor:** Ninguno; regla de autorización de la API. **Tipo:** control interno, fuera del diagrama UML. **Prioridad:** Obligatorio. **Paquete:** A.

**Objetivo:** Código histórico reservado para la regla transversal de autorización. La sesión, el rol y el alcance de cada recurso son guardas de los CU protegidos, no un objetivo autónomo de un actor.

**Precondiciones:**

- Debe existir una sesión activa.

**Flujo normal:**

1. El caso de uso base solicita la verificación antes de ejecutar la operación.
2. El sistema recupera el rol del usuario autenticado.
3. El sistema compara la operación solicitada con las operaciones habilitadas para ese rol.
4. El sistema devuelve el resultado al caso de uso base.

**Postcondición:** Operación autorizada o rechazada sin cambios de datos.

**Alternativas:**

- **A1.0:** Si el rol no tiene el permiso requerido, el sistema deniega la operación e informa al usuario.
- **A2.0:** Si la sesión expiró, el sistema la cierra y solicita autenticarse nuevamente.

**Requisitos relacionados:** RF-009, RF-166, RF-182, RNF-002.

### CU-005 — Aprobar o rechazar solicitud de registro

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Recomendado. **Paquete:** B.

**Objetivo:** El administrador evalúa una solicitud de registro enviada desde la landing page y decide si se convierte en un usuario del sistema.

**Precondiciones:**

- Inicio de sesión del administrador (CU-001).
- Debe existir al menos una solicitud en estado pendiente.

**Flujo normal:**

1. El administrador ingresa al listado de solicitudes pendientes.
2. El sistema muestra las solicitudes con sus datos y su fecha.
3. El administrador selecciona una solicitud y revisa los datos declarados.
4. El administrador aprueba o rechaza la solicitud.
5. Si la aprueba, el administrador asigna el rol que corresponde.
6. El sistema crea el usuario a partir de la solicitud, la marca como aprobada y persiste ambos registros.

**Postcondición:** La solicitud queda aprobada —con su usuario asociado creado— o rechazada, y en ambos casos conserva el registro del trámite.

**Alternativas:**

- **A1.0:** Si el administrador cancela la operación, el sistema vuelve al listado sin modificar nada.
- **A2.0:** Si al aprobar ya existe un usuario con ese correo, el sistema informa el conflicto y no crea el usuario.

**Requisitos relacionados:** RF-010, RF-181.

### CU-006 — Registrar usuario

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** B.

**Objetivo:** El administrador da de alta un usuario nuevo directamente en el sistema, sin que medie una solicitud previa.

**Precondiciones:**

- Inicio de sesión del administrador.
- Ingresar al menú de gestión de usuarios.

**Flujo normal:**

1. El administrador selecciona la opción de alta de usuario.
2. El sistema muestra el formulario de alta.
3. El administrador completa nombre, apellido, correo, teléfono, dirección, rol y contraseña inicial.
4. El administrador confirma el guardado.
5. El sistema valida los datos, almacena la contraseña cifrada y persiste el usuario en la base de datos.
6. El sistema muestra un mensaje confirmando la operación.

**Postcondición:** El usuario queda creado y habilitado para iniciar sesión.

**Alternativas:**

- **A1.0:** Si el administrador cancela, el sistema vuelve al menú de gestión sin crear nada.
- **A2.0:** Si el correo ya está registrado, el sistema muestra un mensaje de error y no crea el usuario.
- **A3.0:** Si falta algún campo obligatorio, el sistema lo señala y no permite guardar.

**Requisitos relacionados:** RF-001, RF-006, RF-163, RF-169, RF-176, RNF-001.

### CU-007 — Modificar usuario

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** B.

**Objetivo:** El administrador actualiza los datos de un usuario ya registrado.

**Precondiciones:**

- Inicio de sesión del administrador.
- Ingresar al menú de gestión de usuarios.

**Flujo normal:**

1. El administrador busca al usuario por su correo electrónico y selecciona la opción de modificar.
2. El sistema muestra en pantalla los datos actuales del usuario.
3. El administrador modifica los datos necesarios. El identificador del usuario no es modificable.
4. El administrador confirma el guardado.
5. El sistema valida los datos, persiste los cambios y muestra un mensaje confirmando la operación.

**Postcondición:** Los datos del usuario quedan actualizados en la base de datos.

**Alternativas:**

- **A1.0:** Si el administrador cancela, el sistema vuelve al menú sin aplicar cambios.
- **A2.0:** Si el usuario buscado no existe, el sistema muestra un mensaje de error.
- **A3.0:** Si el nuevo correo ya pertenece a otro usuario, el sistema informa el conflicto y no guarda.

**Requisitos relacionados:** RF-005, RF-006, RF-169, RF-179.

### CU-008 — Deshabilitar usuario

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** B.

**Objetivo:** El administrador desactiva la cuenta de un usuario, impidiéndole el acceso sin eliminar su información histórica.

**Precondiciones:**

- Inicio de sesión del administrador.
- Ingresar al menú de gestión de usuarios.

**Flujo normal:**

1. El administrador selecciona el usuario del listado y elige la opción de deshabilitar.
2. El sistema solicita confirmación de la acción.
3. El administrador confirma.
4. El sistema cambia el estado del usuario a inactivo y persiste el cambio.
5. El sistema informa que la operación se realizó correctamente.

**Postcondición:** El usuario queda inactivo y no puede iniciar sesión. Sus datos y su historial se conservan.

**Alternativas:**

- **A1.0:** Si el administrador cancela, el sistema vuelve al listado sin cambios.
- **A2.0:** Si el usuario a deshabilitar es el único administrador activo, el sistema impide la operación e informa el motivo.

**Requisitos relacionados:** RF-006, RF-169.

### CU-009 — Consultar usuarios

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** B.

**Objetivo:** El administrador consulta el listado de usuarios registrados en el sistema.

**Precondiciones:**

- Inicio de sesión del administrador.

**Flujo normal:**

1. El administrador ingresa al menú de gestión de usuarios.
2. El sistema consulta la base de datos y muestra el listado con nombre, correo, rol y estado de cada usuario.
3. El administrador puede seleccionar un usuario para ver su detalle completo.

**Postcondición:** Se muestra en pantalla el listado de usuarios registrados.

**Alternativas:**

- **A1.0:** Si no hay usuarios que mostrar, el sistema informa que el listado está vacío.

**Requisitos relacionados:** RF-011, RF-167, RF-182.

### CU-010 — Filtrar listado de usuarios

**Actor:** Administrador municipal. **Tipo:** extend. **Prioridad:** Recomendado. **Paquete:** B.

**Objetivo:** Amplía el caso de uso «Consultar usuarios» permitiendo acotar el listado por rol o por estado. Se ejecuta solo si el administrador aplica algún criterio de filtro. Condición: [el administrador aplica al menos un criterio].

**Precondiciones:**

- El caso de uso «Consultar usuarios» (CU-009) está en ejecución.

**Flujo normal:**

1. El administrador selecciona un rol, un estado, o ambos.
2. El sistema aplica los criterios y vuelve a consultar la base de datos.
3. El sistema muestra únicamente los usuarios que cumplen los criterios.

**Postcondición:** Se muestra el subconjunto de usuarios que cumple los criterios aplicados.

**Alternativas:**

- **A1.0:** Si ningún usuario cumple los criterios, el sistema informa que no hay coincidencias y ofrece limpiar los filtros.

**Requisitos relacionados:** RF-012.

### CU-011 — Asignar rol a usuario

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** B.

**Objetivo:** El administrador cambia el rol de un usuario, con lo que modifica las funcionalidades que ese usuario tiene habilitadas.

**Precondiciones:**

- Inicio de sesión del administrador.
- El usuario a modificar debe existir en el sistema.

**Flujo normal:**

1. El administrador selecciona el usuario del listado.
2. El sistema muestra el rol actual y los roles disponibles.
3. El administrador selecciona el nuevo rol.
4. El administrador confirma el cambio.
5. El sistema persiste el nuevo rol; los permisos se aplican a partir de la siguiente sesión del usuario.

**Postcondición:** El usuario queda asociado al nuevo rol.

**Alternativas:**

- **A1.0:** Si el administrador cancela, el sistema vuelve al listado sin cambios.
- **A2.0:** Si el cambio dejaría al sistema sin ningún administrador activo, la operación se impide.

**Requisitos relacionados:** RF-007, RF-008, RF-180.

### CU-012 — Registrar contenedor

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** C.

**Objetivo:** El administrador incorpora al sistema un contenedor instalado en la vía pública, con su ubicación, su capacidad y el tipo de residuo que admite.

**Precondiciones:**

- Inicio de sesión del administrador.
- Deben existir zonas y tipos de residuo cargados.

**Flujo normal:**

1. El administrador selecciona la opción de alta de contenedor.
2. El sistema muestra el formulario de alta.
3. El administrador completa código, dirección, coordenadas, capacidad, zona y tipo de residuo.
4. El administrador confirma el guardado.
5. El sistema valida que el código no exista, persiste el contenedor con estado operativo y confirma la operación.

**Postcondición:** El contenedor queda registrado y disponible para las consultas y para el registro de incidencias.

**Alternativas:**

- **A1.0:** Si el administrador cancela, el sistema vuelve al listado.
- **A2.0:** Si el código de contenedor ya existe, el sistema muestra un error y no guarda.
- **A3.0:** Si las coordenadas están fuera de rango válido, el sistema señala el campo.

**Requisitos relacionados:** RF-013, RF-017, RF-019, RF-170, RF-183, RF-018.

### CU-013 — Modificar contenedor

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** C.

**Objetivo:** El administrador actualiza los datos de un contenedor ya registrado, por ejemplo cuando se lo reubica o cambia el tipo de residuo que admite.

**Precondiciones:**

- Inicio de sesión del administrador.
- El contenedor debe existir en el sistema.

**Flujo normal:**

1. El administrador busca el contenedor por su código y elige modificar.
2. El sistema muestra los datos actuales del contenedor.
3. El administrador modifica los datos necesarios.
4. El administrador confirma el guardado.
5. El sistema valida, persiste los cambios y confirma la operación.

**Postcondición:** Los datos del contenedor quedan actualizados.

**Alternativas:**

- **A1.0:** Si el administrador cancela, no se aplica ningún cambio.
- **A2.0:** Si el contenedor no existe, el sistema muestra un mensaje de error.

**Requisitos relacionados:** RF-015, RF-170, RF-184, RF-018.

### CU-014 — Dar de baja contenedor

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** C.

**Objetivo:** El administrador retira un contenedor del servicio activo, conservando su historial de incidencias y vaciados.

**Precondiciones:**

- Inicio de sesión del administrador.
- El contenedor debe existir y estar activo.

**Flujo normal:**

1. El administrador selecciona el contenedor y elige la opción de dar de baja.
2. El sistema solicita confirmación e informa que el historial se conservará.
3. El administrador confirma.
4. El sistema cambia el estado del contenedor a retirado y persiste el cambio.

**Postcondición:** El contenedor queda retirado: no aparece en los listados operativos, pero su historial sigue disponible para los reportes.

**Alternativas:**

- **A1.0:** Si el administrador cancela, el contenedor no se modifica.
- **A2.0:** Si el contenedor tiene incidencias abiertas, el sistema advierte la situación y solicita una confirmación adicional.

**Requisitos relacionados:** RF-016, RF-170, RF-185, RF-018.

### CU-015 — Consultar contenedores

**Actor:** Administrador municipal, Operario de centro, Cuadrilla, Vecino registrado. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** C.

**Objetivo:** El usuario consulta los contenedores registrados con su ubicación, su estado y el tipo de residuo que admiten.

**Precondiciones:**

- Sesión activa; la API determina el alcance de la lectura según el rol.

**Flujo normal:**

1. El usuario abre el listado de contenedores.
2. El sistema muestra código, dirección, zona, tipo y estado de los contenedores.
3. El usuario abre una ficha; solo Administrador y Cuadrilla pueden consultar el historial completo de estados.

**Postcondición:** Se muestra en pantalla el listado de contenedores.

**Alternativas:**

- **A1.0:** No hay contenedores: se muestra el listado vacío.
- **A2.0:** Operario o Vecino solicita historial: la API deniega la lectura según permisos.

**Requisitos relacionados:** RF-014, RF-167, RF-186.

### CU-016 — Registrar camión

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** C.

**Objetivo:** El administrador incorpora un camión recolector a la flota registrada en el sistema.

**Precondiciones:**

- Inicio de sesión del administrador.

**Flujo normal:**

1. El administrador selecciona la opción de alta de camión.
2. El sistema muestra el formulario de alta.
3. El administrador completa matrícula, marca, modelo y capacidad.
4. El administrador confirma el guardado.
5. El sistema valida que la matrícula no exista, persiste el camión con estado disponible y confirma.

**Postcondición:** El camión queda registrado y disponible para ser asignado a recolecciones.

**Alternativas:**

- **A1.0:** Si el administrador cancela, no se registra nada.
- **A2.0:** Si la matrícula ya está registrada, el sistema muestra un error.

**Requisitos relacionados:** RF-028, RF-032, RF-171.

### CU-017 — Modificar camión

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** C.

**Objetivo:** El administrador actualiza los datos o el estado de un camión de la flota.

**Precondiciones:**

- Inicio de sesión del administrador.
- El camión debe existir.

**Flujo normal:**

1. El administrador busca el camión por su matrícula y elige modificar.
2. El sistema muestra los datos actuales.
3. El administrador modifica los datos o el estado del vehículo.
4. El administrador confirma el guardado.
5. El sistema persiste los cambios y confirma la operación.

**Postcondición:** Los datos del camión quedan actualizados.

**Alternativas:**

- **A1.0:** Si el administrador cancela, no se aplica ningún cambio.
- **A2.0:** Si el camión no existe, el sistema muestra un mensaje de error.

**Requisitos relacionados:** RF-030, RF-171.

### CU-018 — Dar de baja camión

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** C.

**Objetivo:** El administrador retira un camión de la flota activa.

**Precondiciones:**

- Inicio de sesión del administrador.
- El camión debe existir y estar activo.

**Flujo normal:**

1. El administrador selecciona el camión y elige darlo de baja.
2. El sistema solicita confirmación.
3. El administrador confirma.
4. El sistema cambia el estado del camión y persiste el cambio.

**Postcondición:** El camión deja de estar disponible para nuevas asignaciones; su historial de recolecciones se conserva.

**Alternativas:**

- **A1.0:** Si el administrador cancela, el camión no se modifica.
- **A2.0:** Si el camión está asignado a una recolección en curso, el sistema impide la baja e informa el motivo.

**Requisitos relacionados:** RF-031, RF-171.

### CU-019 — Consultar camiones

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** C.

**Objetivo:** El administrador consulta la flota registrada con el estado de cada vehículo.

**Precondiciones:**

- Inicio de sesión del administrador.

**Flujo normal:**

1. El administrador ingresa a la sección de flota.
2. El sistema consulta la base y muestra matrícula, marca, modelo, capacidad y estado de cada camión.
3. El administrador puede seleccionar un camión para ver su detalle.

**Postcondición:** Se muestra en pantalla el listado de camiones registrados.

**Alternativas:**

- **A1.0:** Si no hay camiones registrados, el sistema informa que el listado está vacío.

**Requisitos relacionados:** RF-029, RF-101, RF-167.

### CU-020 — Administrar centros de acopio

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Exigido en 2.º avance. **Paquete:** C.

**Objetivo:** El administrador registra, modifica o da de baja los centros de acopio donde los camiones descargan lo recolectado.

**Precondiciones:**

- Inicio de sesión del administrador.

**Flujo normal:**

1. El administrador ingresa a la sección de centros de acopio.
2. El sistema muestra los centros registrados.
3. El administrador elige registrar uno nuevo, modificar uno existente o darlo de baja.
4. El administrador completa o corrige nombre, dirección, coordenadas y capacidad.
5. El sistema valida los datos, persiste la operación y confirma.

**Postcondición:** El catálogo de centros de acopio queda actualizado.

**Alternativas:**

- **A1.0:** Si el administrador cancela, no se aplica ningún cambio.
- **A2.0:** Si el centro tiene referencias que impiden la baja, el sistema la rechaza y explica la dependencia.
- **A3.0:** Al dar de baja un centro con maquinaria asociada, informa cuántas máquinas deben reasignarse (RF-254).

**Requisitos relacionados:** RF-080, RF-081, RF-082, RF-083, RF-187, RF-254.

### CU-021 — Actualizar capacidad del centro de acopio

**Actor:** Operario del centro asignado. **Tipo:** base. **Prioridad:** Exigido en 2.º avance. **Paquete:** C.

**Objetivo:** Actualizar la capacidad del centro que tiene asignado, sin cambiar otros centros.

**Precondiciones:**

- Sesión activa del operario y centro de acopio asignado por administración.

**Flujo normal:**

1. El operario abre su centro.
2. Consulta la capacidad y escribe el nuevo valor.
3. La API comprueba que sea su centro y que el dato sea válido.
4. El sistema guarda el cambio y lo confirma.

**Postcondición:** La capacidad del centro asignado queda actualizada.

**Alternativas:**

- **A1:** No tiene centro asignado: se rechaza la operación.
- **A2:** Pide otro centro: no se muestran sus datos ni se modifica.
- **A3:** Capacidad inválida: se informa el error y no se guarda.

**Requisitos relacionados:** RF-088, RF-189.

### CU-022 — Administrar tipos de residuo

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** C.

**Objetivo:** El administrador mantiene el catálogo de tipos de residuo que se asocian a los contenedores y a los centros de acopio.

**Precondiciones:**

- Inicio de sesión del administrador.

**Flujo normal:**

1. El administrador ingresa a la sección de tipos de residuo.
2. El sistema muestra el catálogo actual.
3. El administrador agrega, modifica o elimina un tipo de residuo.
4. El administrador confirma la operación.
5. El sistema valida, persiste el cambio y confirma.

**Postcondición:** El catálogo de tipos de residuo queda actualizado.

**Alternativas:**

- **A1.0:** Si se intenta eliminar un tipo de residuo asociado a contenedores existentes, el sistema impide la operación e informa el motivo.
- **A2.0:** Si el nombre del tipo ya existe, el sistema muestra un error.

**Requisitos relacionados:** RF-147, RF-148, RF-190, RF-211.

### CU-023 — Registrar incidencia

**Actor:** Vecino registrado. **Tipo:** base. **Prioridad:** Alcance mínimo. **Paquete:** D.

**Objetivo:** Informar un problema sobre un contenedor. La versión actual no permite adjuntar fotos.

**Precondiciones:**

- Inicio de sesión del vecino.
- El contenedor sobre el que se reporta debe estar registrado.

**Flujo normal:**

1. El vecino abre «Informar un problema».
2. Identifica el contenedor por código o desde el mapa.
3. Indica tipo de problema y descripción.
4. Confirma; el sistema valida y crea la incidencia abierta con su historial.
5. Recibe el número INC para seguir el estado.

**Postcondición:** La incidencia queda registrada, abierta y visible para el administrador.

**Alternativas:**

- **A1.0:** Contenedor inexistente o retirado: se rechaza el informe.
- **A2.0:** Incidencia abierta del mismo tipo: respuesta de conflicto con el número existente; la adhesión sigue pendiente.
- **A3.0:** Sesión ausente o rol distinto de Vecino: se rechaza sin crear la incidencia.

**Requisitos relacionados:** sin RF directo en la ERS 3.0; revisar el alcance.

### CU-024 — Adjuntar evidencia fotográfica

**Actor:** Vecino registrado. **Tipo:** retirado; no se dibuja como objetivo actual. **Prioridad:** Retirado el 30/10/2026. **Paquete:** D.

**Objetivo:** La opción de adjuntar foto fue retirada. Este código queda solo para explicar el cambio respecto de la entrega anterior.

**Precondiciones:**

- No aplica a la versión actual.

**Flujo normal:**

No aplica a la versión actual.

**Postcondición:** No aplica.

**Alternativas:**

No aplica.

**Requisitos relacionados:** sin RF directo en la ERS 3.0; revisar el alcance.

### CU-025 — Asignar incidencia a cuadrilla

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Alcance mínimo. **Paquete:** D.

**Objetivo:** El administrador revisa una incidencia abierta, le asigna una prioridad y la deriva a la cuadrilla que debe atenderla.

**Precondiciones:**

- Inicio de sesión del administrador.
- Debe existir al menos una incidencia abierta y una cuadrilla activa.

**Flujo normal:**

1. El administrador ingresa al listado de incidencias abiertas.
2. El sistema muestra las incidencias con su tipo, contenedor, zona y fecha de reporte.
3. El administrador selecciona una incidencia y revisa su detalle.
4. El administrador asigna la prioridad y selecciona la cuadrilla.
5. El sistema persiste la asignación y cambia el estado de la incidencia a «en curso».

**Postcondición:** La incidencia queda asignada a una cuadrilla, con prioridad definida y estado «en curso».

**Alternativas:**

- **A1:** No hay cuadrilla disponible: no se asigna la incidencia.
- **A2:** La incidencia ya está cerrada o cambió: no se modifica.
- **A3:** El administrador cancela: no se guarda ningún cambio.

**Requisitos relacionados:** sin RF directo en la ERS 3.0; revisar el alcance.

### CU-026 — Actualizar estado de incidencia

**Actor:** Cuadrilla asignada (avance o resolución); Administrador municipal (resolución). **Tipo:** base. **Prioridad:** Alcance mínimo. **Paquete:** D.

**Objetivo:** La cuadrilla informa el avance de una incidencia que tiene asignada, hasta darla por resuelta.

**Precondiciones:**

- Sesión activa.
- La incidencia está abierta o en curso; la Cuadrilla solo actúa sobre las que tiene asignadas.

**Flujo normal:**

1. La Cuadrilla consulta sus incidencias asignadas o el Administrador selecciona una.
2. La Cuadrilla registra avance o resolución; el Administrador puede resolver.
3. El sistema valida rol, alcance y transición; registra actor, fecha y observación en historial.
4. Si al resolver corresponde recuperar un contenedor lleno o en mantenimiento y no queda otra incidencia abierta, actualiza el estado y su historial.

**Postcondición:** La incidencia y su historial reflejan la transición; el contenedor cambia solo si se cumple la regla condicional.

**Alternativas:**

- **A1.0:** Incidencia ajena para la Cuadrilla: lectura no encontrada o escritura denegada.
- **A2.0:** Incidencia ya cerrada o transición inválida: sin cambios.
- **A3.0:** Cancelación: sin cambios.

**Requisitos relacionados:** sin RF directo en la ERS 3.0; revisar el alcance.

### CU-027 — Registrar estado de contenedor

**Actor:** Ninguno; regla condicional de estado e historial de contenedor. **Tipo:** control interno, fuera del diagrama UML. **Prioridad:** Alcance mínimo. **Paquete:** D.

**Objetivo:** Código histórico reservado para la regla de persistencia. Se aplica al modificar o retirar un contenedor y, condicionalmente, al informar o resolver incidencias. No se ejecuta en todo avance de CU-026.

**Precondiciones:**

- El contenedor debe estar registrado en el sistema.

**Flujo normal:**

1. El caso de uso base solicita el registro del nuevo estado.
2. El sistema crea un registro en el historial con la fecha y hora, el estado y la observación.
3. El sistema actualiza el estado vigente del contenedor.
4. El sistema devuelve el control al caso de uso base.

**Postcondición:** Si el estado cambia, queda un asiento nuevo y el estado vigente concordante; los asientos previos se conservan.

**Alternativas:**

- **A1.0:** Si el estado informado coincide con el vigente, el sistema no duplica el registro y devuelve el control sin cambios.

**Requisitos relacionados:** sin RF directo en la ERS 3.0; revisar el alcance.

### CU-028 — Consultar incidencias

**Actor:** Administrador municipal, Cuadrilla asignada, Vecino que informó. **Tipo:** base. **Prioridad:** Alcance mínimo. **Paquete:** D.

**Objetivo:** El usuario consulta incidencias: el Administrador ve todas, la Cuadrilla solo las asignadas y el Vecino solo las que informó.

**Precondiciones:**

- Inicio de sesión del usuario.

**Flujo normal:**

1. El usuario abre el listado de incidencias.
2. La API filtra por rol: todas para Administrador, asignadas para Cuadrilla y propias para Vecino.
3. El usuario aplica filtros permitidos y abre el detalle.
4. Se muestra estado e historial; los datos personales de quien informó se reservan al Administrador.

**Postcondición:** Se muestran únicamente las incidencias y datos permitidos al usuario.

**Alternativas:**

- **A1.0:** Si no hay incidencias que mostrar, el sistema informa que el listado está vacío.

**Requisitos relacionados:** sin RF directo en la ERS 3.0; revisar el alcance.

### CU-029 — Consultar inventario de recursos

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Parcial: repuestos pendientes. **Paquete:** E.

**Objetivo:** Consultar contenedores y camiones. El stock de contenedores de repuesto sigue sin modelo ni pantalla.

**Precondiciones:**

- Inicio de sesión del administrador.

**Flujo normal:**

1. El Administrador abre el inventario de recursos.
2. El sistema presenta los camiones y contenedores registrados con su estado.
3. La consulta de repuestos se habilitará al definir unidad de stock y persistencia.

**Postcondición:** Se muestran los recursos existentes; no se declara cubierto el stock de repuestos.

**Alternativas:**

- **A1.0:** Si no hay recursos registrados, el sistema informa que el inventario está vacío.

**Requisitos relacionados:** RF-101, RF-102, RF-192.

### CU-030 — Generar reporte de incidencias por zona

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Informe de la tercera entrega; CSV disponible con límite de filas en el detalle. **Paquete:** E.

**Objetivo:** Consultar el informe de incidencias por período y zona, con totales, gráficos y detalle. Se puede imprimir y exportar CSV.

**Precondiciones:**

- Sesión activa del Administrador.

**Flujo normal:**

1. El administrador abre Reportes y elige un período y, si quiere, una zona.
2. El sistema muestra totales, incidencias por zona y el detalle del período.
3. Si lo necesita, imprime el informe o descarga el CSV del detalle o de las zonas.

**Postcondición:** El informe queda visible y se puede imprimir; los CSV contienen datos del filtro elegido. El detalle puede estar limitado.

**Alternativas:**

- **A1:** No hay incidencias: se muestran totales en cero.
- **A2:** El detalle supera el límite: el CSV puede contener solo la parte visible y el sistema lo avisa.

**Requisitos relacionados:** RF-242, RF-243, RF-244, RF-245.

### CU-031 — Administrar maquinaria del centro de acopio

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Exigido en 2.º avance. **Paquete:** C.

**Objetivo:** Dar de alta, modificar o dejar fuera de servicio la maquinaria asociada a un centro de acopio.

**Precondiciones:**

- Sesión activa del Administrador.
- Existe el centro de acopio al que se asociará la máquina.

**Flujo normal:**

1. El Administrador abre maquinaria y selecciona alta, modificación o baja.
2. Ingresa o corrige código, tipo, marca, modelo, centro y estado.
3. El sistema valida y guarda; la baja conserva el registro fuera de servicio.

**Postcondición:** Inventario de maquinaria actualizado sin perder registros dados de baja.

**Alternativas:**

- **A1.0:** Código duplicado: se rechaza el alta.
- **A2.0:** Centro inexistente o ausente: se rechaza.
- **A3.0:** Tipo o estado inválido: se rechaza.
- **A4.0:** Máquina ya fuera de servicio: no se repite la baja.
- **A5.0:** Cancelación: sin cambios.

**Requisitos relacionados:** RF-246, RF-247, RF-248, RF-249, RF-250, RF-251, RF-252, RF-253.

### CU-032 — Consultar maquinaria

**Actor:** Administrador municipal, Operario de centro. **Tipo:** base. **Prioridad:** Exigido en 2.º avance. **Paquete:** C.

**Objetivo:** Consultar maquinaria, centro y estado de disponibilidad.

**Precondiciones:**

- Sesión activa del Administrador o del Operario.

**Flujo normal:**

1. El usuario abre maquinaria.
2. El sistema muestra código, tipo, marca, modelo, centro y estado.
3. El usuario busca o ordena el listado cuando esa función esté disponible.

**Postcondición:** Inventario visible sin modificar datos.

**Alternativas:**

- **A1.0:** Sin registros: se muestra estado vacío.
- **A2.0:** Fallo de lectura: se informa y permite reintentar.

**Requisitos relacionados:** RF-255, RF-256.

### CU-033 — Armar una ruta

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Tercer avance; revisar con el equipo. **Paquete:** D.

**Objetivo:** Elegir los contenedores y su orden para una ruta de recolección.

**Precondiciones:**

- Sesión activa y permiso correspondiente, salvo que se indique acceso público.

**Flujo normal:**

1. Abre Rutas y elige crear o editar una ruta.
2. Selecciona zona y paradas en orden.
3. La API valida y guarda la ruta y sus paradas.

**Postcondición:** El resultado queda visible para el perfil autorizado.

**Alternativas:**

- **A1:** Una parada no está disponible: no se guarda esa selección.

**Requisitos relacionados:** sin RF directo en la ERS 3.0; revisar el alcance.

### CU-034 — Planificar una recolección

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Tercer avance; revisar con el equipo. **Paquete:** D.

**Objetivo:** Asignar ruta, fecha, camión y cuadrilla para un recorrido.

**Precondiciones:**

- Sesión activa y permiso correspondiente, salvo que se indique acceso público.

**Flujo normal:**

1. Abre Recolecciones y elige planificar.
2. Selecciona ruta, fecha, camión y cuadrilla.
3. El sistema valida y guarda la tarea planificada.

**Postcondición:** El resultado queda visible para el perfil autorizado.

**Alternativas:**

- **A1:** Falta un dato o el recurso no está disponible: se informa el motivo.

**Requisitos relacionados:** sin RF directo en la ERS 3.0; revisar el alcance.

### CU-035 — Ejecutar una recolección

**Actor:** Cuadrilla asignada. **Tipo:** base. **Prioridad:** Tercer avance; revisar con el equipo. **Paquete:** D.

**Objetivo:** Iniciar la tarea, registrar vaciados y finalizarla.

**Precondiciones:**

- Sesión activa y permiso correspondiente, salvo que se indique acceso público.

**Flujo normal:**

1. La cuadrilla consulta Mis tareas.
2. Inicia la recolección asignada.
3. Registra contenedores vaciados con peso.
4. Finaliza la tarea y el camión vuelve a operativo.

**Postcondición:** El resultado queda visible para el perfil autorizado.

**Alternativas:**

- **A1:** La tarea pertenece a otra cuadrilla: se rechaza.
- **A2:** Datos del vaciado inválidos: no se registra.

**Requisitos relacionados:** RF-257, RF-258.

### CU-036 — Consultar mapa

**Actor:** Público o usuario autenticado, según capa. **Tipo:** base. **Prioridad:** Tercer avance; revisar con el equipo. **Paquete:** C.

**Objetivo:** Ver contenedores y abrir la información permitida por el perfil.

**Precondiciones:**

- Sesión activa y permiso correspondiente, salvo que se indique acceso público.

**Flujo normal:**

1. Abre el mapa.
2. El sistema muestra las capas permitidas.
3. Selecciona un punto y consulta su ficha o inicia un reporte si tiene permiso.

**Postcondición:** El resultado queda visible para el perfil autorizado.

**Alternativas:**

- **A1:** No hay conexión con el mapa de fondo: los datos propios siguen visibles si cargaron.

**Requisitos relacionados:** RF-259, RF-260.

### CU-037 — Consultar panel y alertas

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Tercer avance; revisar con el equipo. **Paquete:** E.

**Objetivo:** Ver cifras de incidencias y avisos que requieren atención.

**Precondiciones:**

- Sesión activa y permiso correspondiente, salvo que se indique acceso público.

**Flujo normal:**

1. Abre el panel.
2. Consulta los totales y las incidencias urgentes.
3. Abre el detalle de una incidencia para actuar.

**Postcondición:** El resultado queda visible para el perfil autorizado.

**Alternativas:**

- **A1:** No hay datos para el período: se muestran cifras en cero.

**Requisitos relacionados:** RF-261, RF-262, RF-263.

### CU-038 — Registrar recepción y movimiento de stock

**Actor:** Operario del centro asignado. **Tipo:** base. **Prioridad:** Tercer avance; revisar con el equipo. **Paquete:** C.

**Objetivo:** Registrar lo recibido de una recolección y los retiros o correcciones de stock del propio centro.

**Precondiciones:**

- Sesión activa y permiso correspondiente, salvo que se indique acceso público.

**Flujo normal:**

1. Abre las recepciones de su centro.
2. Confirma los kilos por tipo recibido.
3. Si corresponde, registra un retiro o una corrección con motivo.

**Postcondición:** El resultado queda visible para el perfil autorizado.

**Alternativas:**

- **A1:** Intenta operar otro centro: la API lo rechaza.
- **A2:** El retiro excede el saldo: no se registra.

**Requisitos relacionados:** sin RF directo en la ERS 3.0; revisar el alcance.

### CU-039 — Consultar notificaciones

**Actor:** Usuario autenticado. **Tipo:** base. **Prioridad:** Tercer avance; revisar con el equipo. **Paquete:** A.

**Objetivo:** Leer los avisos de la campanita y marcar los propios como leídos.

**Precondiciones:**

- Sesión activa y permiso correspondiente, salvo que se indique acceso público.

**Flujo normal:**

1. Abre la campanita.
2. Consulta sus avisos.
3. Abre uno o lo marca como leído.

**Postcondición:** El resultado queda visible para el perfil autorizado.

**Alternativas:**

- **A1:** Intenta leer avisos de otra persona: la API lo rechaza.

**Requisitos relacionados:** sin RF directo en la ERS 3.0; revisar el alcance.

## 6. Matriz RF → CU → prueba

Mostramos solo los recorridos principales. Las pruebas y sus resultados completos están en el Plan de testing; no asignamos códigos de pruebas que no existen.

| Recorrido | Requisitos | Prueba registrada | Estado |
|---|---|---|---|
| Inicio de sesión | RF-002, RF-177 | CP-ACC-01 a 06; CP-UI-02 | Corrida guardada; interfaz probada por IA. |
| Incidencias | RF-217 a RF-241 | CP-INC-01 a 15; CP-UI-01 | Corrida guardada; interfaz probada por IA. |
| Recolección | RF-257, RF-258 | CP-REC-01 a 06; CP-UI-07 | Corrida guardada; interfaz probada por IA. |
| Mapa y panel | RF-259 a RF-263 | CP-PUB-01; CP-UI-06 | Celular real pendiente. |
| Informe | RF-242 a RF-245 | CP-PUB-03; CP-UI-08 | CSV parcial; falta abrirlo en planilla. |
| Foto, repuestos e idioma | RF-222, RF-223, RF-026, RF-027, RF-216 | Ninguna | Foto retirada; repuestos e idioma pendientes. |

## 7. Brechas y próxima decisión

- El equipo y la docente tienen que revisar los CU-033 a CU-039. Sus recorridos se tomaron del código y de los documentos de octubre, pero el catálogo anterior no los tenía.
- Recepción, stock, rutas y notificaciones necesitan requisitos propios en una futura revisión de la ERS si el equipo los presenta como alcance formal.
- Quedan sin prueba de usuario las sesiones externas; las capturas de interfaz del 03/11 fueron hechas por IA y están declaradas en el plan de testing.

## 8. Fuentes

- ERS_Consolidada_SiGeRU_v3.md y Plan_de_Testing_SiGeRU.md.
- schema.sql, ampliacion-operario-3ra.md, ampliacion-cedula-cuadrilla-3ra.md y ampliacion-simplificacion-2-3ra.md.
- Casos_de_uso_y_trazabilidad_SiGeRU_v2.md, conservado como registro anterior.
