# SiGeRU — casos de uso y trazabilidad revisados

**Fecha:** 12/10/2026 · **Paso:** 5 de la corrección de Ingeniería de Software · **Estado:** revisión de trabajo, pendiente de validar con docentes y usuarios.

## 1. Criterio y corte

Se auditaron los 30 códigos del análisis de julio y CU-031/032 de la adenda de maquinaria. Se conservan los 32 identificadores por trazabilidad: **30 son objetivos de actor** y **CU-004/027 son reglas internas**, fuera de los óvalos UML. La entrega congelada no cambia. La ERS consolidada v2.1 es la referencia de requisitos; este catálogo incorpora el estado de la API de octubre sin presentarlo como existente al 14/09.

Una ficha especifica la conducta esperada; su presencia no acredita interfaz completa ni prueba ejecutada. `PA-RF-nnn` es un identificador y criterio de aceptación **previsto**, no un guion ni un resultado. `PC-CU-nnn` identifica los recorridos normal y alternativos por diseñar. Existen pruebas de código, pero todavía no están enlazadas caso por caso a esta matriz.

## 2. Correcciones principales

- **CU-001:** inicia una persona registrada antes de autenticarse. Se agrega el bloqueo temporal por intentos fallidos.
- **CU-004:** autorización y alcance por rol son guardas de cada operación y de la API. Se retiran las relaciones `include` desde los paquetes.
- **CU-027:** historial/estado del contenedor es una regla de persistencia condicional. Ni cada avance de incidencia ni cada reporte cambian el estado.
- **CU-010 `extend` CU-009** y **CU-024 `extend` CU-023** se mantienen como opciones con condición explícita; CU-024 depende de la foto pendiente.
- **CU-021:** no puede exigir «su centro» porque el modelo actual no asigna centro al Operario; sí puede actualizar capacidad, y la API le prohíbe cambiar el estado.
- **CU-023/025:** seleccionar desde mapa, adherirse a un reporte duplicado y considerar camiones disponibles quedan marcados como pendientes; el flujo actual no los cumple.
- **CU-028:** se añade Vecino con alcance de sus propios reportes. **CU-029:** el stock de repuestos sigue sin modelo.
- **CU-030:** un período sin incidencias es un resultado de cero, no una precondición incumplida. Se distingue informe simple exigido de métricas/exportación.
- **CU-020/021/031/032:** se alinean con la exigencia de centros y maquinaria en el segundo avance.

## 3. Relaciones UML vigentes

| Origen | Relación | Destino | Condición / punto de extensión |
|---|---|---|---|
| CU-010 | `extend` | CU-009 | El Administrador aplica filtros al listado de usuarios. |
| CU-024 | `extend` | CU-023 | El Vecino añade foto al informe, cuando esté implementado. |

No se justifica otro `include` común entre los 30 objetivos: el control de rol y el registro de historial son reglas internas. El control de acceso no se reduce a una sesión: la API comprueba rol y alcance.

## 4. Catálogo

| Código | Objetivo / regla | Paquete | Actor iniciador | Tipo | Prioridad | RF directos | Prueba CU |
|---|---|---|---|---|---|---|---|
| CU-001 | Iniciar sesión | A | Usuario registrado, antes de autenticarse | base | Obligatorio | RF-002, RF-164, RF-177, RF-178 | PC-CU-001-N (a diseñar) |
| CU-002 | Cerrar sesión | A | Usuario autenticado | base | Obligatorio | RF-003 | PC-CU-002-N (a diseñar) |
| CU-003 | Solicitar registro | A | Vecino (persona no registrada) | base | Obligatorio | RF-001, RF-160, RF-161, RF-181 | PC-CU-003-N (a diseñar) |
| CU-004 | Verificar permisos de rol | A | Ninguno; regla de autorización de la API | control interno, fuera del diagrama UML | Obligatorio | — | — (regla transversal) |
| CU-005 | Aprobar o rechazar solicitud de registro | B | Administrador municipal | base | Recomendado | RF-010, RF-181 | PC-CU-005-N (a diseñar) |
| CU-006 | Registrar usuario | B | Administrador municipal | base | Obligatorio | RF-001, RF-006, RF-163, RF-169, RF-176 | PC-CU-006-N (a diseñar) |
| CU-007 | Modificar usuario | B | Administrador municipal | base | Obligatorio | RF-005, RF-006, RF-169, RF-179 | PC-CU-007-N (a diseñar) |
| CU-008 | Deshabilitar usuario | B | Administrador municipal | base | Obligatorio | RF-006, RF-169 | PC-CU-008-N (a diseñar) |
| CU-009 | Consultar usuarios | B | Administrador municipal | base | Obligatorio | RF-011, RF-167, RF-182 | PC-CU-009-N (a diseñar) |
| CU-010 | Filtrar listado de usuarios | B | Administrador municipal | extend | Recomendado | RF-012 | PC-CU-010-N (a diseñar) |
| CU-011 | Asignar rol a usuario | B | Administrador municipal | base | Obligatorio | RF-007, RF-008, RF-180 | PC-CU-011-N (a diseñar) |
| CU-012 | Registrar contenedor | C | Administrador municipal | base | Obligatorio | RF-013, RF-017, RF-018, RF-019, RF-170, RF-183 | PC-CU-012-N (a diseñar) |
| CU-013 | Modificar contenedor | C | Administrador municipal | base | Obligatorio | RF-015, RF-018, RF-170, RF-184, RF-233, RF-234, RF-235 | PC-CU-013-N (a diseñar) |
| CU-014 | Dar de baja contenedor | C | Administrador municipal | base | Obligatorio | RF-016, RF-018, RF-170, RF-185, RF-233, RF-234, RF-235 | PC-CU-014-N (a diseñar) |
| CU-015 | Consultar contenedores | C | Administrador municipal, Operario de centro, Cuadrilla, Vecino registrado | base | Obligatorio | RF-014, RF-167, RF-186, RF-236 | PC-CU-015-N (a diseñar) |
| CU-016 | Registrar camión | C | Administrador municipal | base | Obligatorio | RF-028, RF-032, RF-171 | PC-CU-016-N (a diseñar) |
| CU-017 | Modificar camión | C | Administrador municipal | base | Obligatorio | RF-030, RF-171 | PC-CU-017-N (a diseñar) |
| CU-018 | Dar de baja camión | C | Administrador municipal | base | Obligatorio | RF-031, RF-171 | PC-CU-018-N (a diseñar) |
| CU-019 | Consultar camiones | C | Administrador municipal | base | Obligatorio | RF-029, RF-101, RF-167 | PC-CU-019-N (a diseñar) |
| CU-020 | Administrar centros de acopio | C | Administrador municipal | base | Exigido en 2.º avance | RF-080, RF-081, RF-082, RF-083, RF-187, RF-254 | PC-CU-020-N (a diseñar) |
| CU-021 | Actualizar capacidad del centro de acopio | C | Operario de centro de acopio | base | Exigido en 2.º avance | RF-088, RF-189 | PC-CU-021-N (a diseñar) |
| CU-022 | Administrar tipos de residuo | C | Administrador municipal | base | Obligatorio | RF-147, RF-148, RF-190, RF-211 | PC-CU-022-N (a diseñar) |
| CU-023 | Registrar incidencia | D | Vecino registrado | base | Alcance mínimo | RF-217, RF-218, RF-219, RF-220, RF-221, RF-233, RF-234, RF-235 | PC-CU-023-N (a diseñar) |
| CU-024 | Adjuntar evidencia fotográfica | D | Vecino registrado | extend | Ampliación propuesta; pendiente P4b | RF-222, RF-223 | PC-CU-024-N (a diseñar) |
| CU-025 | Asignar incidencia a cuadrilla | D | Administrador municipal | base | Alcance mínimo | RF-224, RF-225, RF-226, RF-227, RF-228 | PC-CU-025-N (a diseñar) |
| CU-026 | Actualizar estado de incidencia | D | Cuadrilla asignada (avance o resolución); Administrador municipal (resolución) | base | Alcance mínimo | RF-229, RF-230, RF-231, RF-232, RF-233, RF-234, RF-235 | PC-CU-026-N (a diseñar) |
| CU-027 | Registrar estado de contenedor | D | Ninguno; regla condicional de estado e historial de contenedor | control interno, fuera del diagrama UML | Alcance mínimo | — | — (regla transversal) |
| CU-028 | Consultar incidencias | D | Administrador municipal, Cuadrilla asignada, Vecino que informó | base | Alcance mínimo | RF-229, RF-237, RF-238, RF-239, RF-240, RF-241 | PC-CU-028-N (a diseñar) |
| CU-029 | Consultar inventario de recursos | E | Administrador municipal | base | Obligatorio | RF-101, RF-102, RF-192 | PC-CU-029-N (a diseñar) |
| CU-030 | Generar reporte de incidencias por zona | E | Administrador municipal | base | Informe simple exigido en 3.er avance; métricas y exportación complementarias | RF-242, RF-243, RF-244, RF-245 | PC-CU-030-N (a diseñar) |
| CU-031 | Administrar maquinaria del centro de acopio | C | Administrador municipal | base | Exigido en 2.º avance | RF-246, RF-247, RF-248, RF-249, RF-250, RF-251, RF-252, RF-253 | PC-CU-031-N (a diseñar) |
| CU-032 | Consultar maquinaria | C | Administrador municipal, Operario de centro | base | Exigido en 2.º avance | RF-255, RF-256 | PC-CU-032-N (a diseñar) |

## 5. Fichas corregidas

### CU-001 — Iniciar sesión

**Actor:** Usuario registrado, antes de autenticarse. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** A.

**Objetivo:** El usuario ingresa al sistema con su correo electrónico y su contraseña para acceder a las funcionalidades que le corresponden según su rol.

**Precondiciones:**

- La persona tiene una cuenta activa; no hay sesión iniciada.

**Flujo normal:**

1. El usuario ingresa a la pantalla de inicio de sesión.
2. El usuario completa los campos de correo electrónico y contraseña.
3. El sistema verifica las credenciales contra los datos almacenados.
4. El sistema identifica el rol del usuario y habilita las opciones correspondientes.
5. El sistema muestra la pantalla principal del rol.

**Postcondición:** El usuario queda autenticado y visualiza únicamente las funcionalidades que su rol tiene habilitadas.

**Alternativas:**

- **A1.0:** Credenciales inválidas: se informa el fallo sin revelar si existe el correo.
- **A2.0:** Cuenta inactiva: se deniega el acceso.
- **A3.0:** Cinco fallos del mismo correo en 15 minutos: bloqueo temporal y tiempo restante.

**RF directos revisados:** RF-002, RF-164, RF-177, RF-178.
**Prueba de recorrido:** PC-CU-001-N y PC-CU-001-A1…A3 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-003.
**Prueba de recorrido:** PC-CU-002-N y PC-CU-002-A1…A1 (guiones y evidencia por enlazar).

### CU-003 — Solicitar registro

**Actor:** Vecino (persona no registrada). **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** A.

**Objetivo:** Una persona que todavía no es usuaria del sistema envía, desde la landing page pública, una solicitud de alta para obtener acceso.

**Precondiciones:**

- El solicitante accede a la landing page pública.
- No se requiere autenticación previa.

**Flujo normal:**

1. El solicitante ingresa a la landing page y selecciona la opción de solicitar registro.
2. El sistema muestra el formulario de solicitud.
3. El solicitante completa nombre, apellido, correo electrónico, teléfono y dirección.
4. El solicitante confirma el envío.
5. El sistema valida los datos y registra la solicitud con estado «pendiente».
6. El sistema informa que la solicitud fue recibida y que quedará a la espera de aprobación.

**Postcondición:** La solicitud queda registrada con estado pendiente y disponible para que el administrador la evalúe.

**Alternativas:**

- **A1.0:** Si algún campo obligatorio está vacío o el correo tiene un formato inválido, el sistema señala el campo y no envía la solicitud.
- **A2.0:** Si ya existe una solicitud pendiente o un usuario registrado con el mismo correo, el sistema informa la situación y no registra una solicitud nueva.

**RF directos revisados:** RF-001, RF-160, RF-161, RF-181.
**Prueba de recorrido:** PC-CU-003-N y PC-CU-003-A1…A2 (guiones y evidencia por enlazar).

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

**RF directos revisados:** ninguno; regla interna / correspondencia indirecta.
**Prueba de recorrido:** regla verificable mediante las PA de sus RF, sin PC autónoma.

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

**RF directos revisados:** RF-010, RF-181.
**Prueba de recorrido:** PC-CU-005-N y PC-CU-005-A1…A2 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-001, RF-006, RF-163, RF-169, RF-176.
**Prueba de recorrido:** PC-CU-006-N y PC-CU-006-A1…A3 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-005, RF-006, RF-169, RF-179.
**Prueba de recorrido:** PC-CU-007-N y PC-CU-007-A1…A3 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-006, RF-169.
**Prueba de recorrido:** PC-CU-008-N y PC-CU-008-A1…A2 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-011, RF-167, RF-182.
**Prueba de recorrido:** PC-CU-009-N y PC-CU-009-A1…A1 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-012.
**Prueba de recorrido:** PC-CU-010-N y PC-CU-010-A1…A1 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-007, RF-008, RF-180.
**Prueba de recorrido:** PC-CU-011-N y PC-CU-011-A1…A2 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-013, RF-017, RF-018, RF-019, RF-170, RF-183.
**Prueba de recorrido:** PC-CU-012-N y PC-CU-012-A1…A3 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-015, RF-018, RF-170, RF-184, RF-233, RF-234, RF-235.
**Prueba de recorrido:** PC-CU-013-N y PC-CU-013-A1…A2 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-016, RF-018, RF-170, RF-185, RF-233, RF-234, RF-235.
**Prueba de recorrido:** PC-CU-014-N y PC-CU-014-A1…A2 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-014, RF-167, RF-186, RF-236.
**Prueba de recorrido:** PC-CU-015-N y PC-CU-015-A1…A2 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-028, RF-032, RF-171.
**Prueba de recorrido:** PC-CU-016-N y PC-CU-016-A1…A2 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-030, RF-171.
**Prueba de recorrido:** PC-CU-017-N y PC-CU-017-A1…A2 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-031, RF-171.
**Prueba de recorrido:** PC-CU-018-N y PC-CU-018-A1…A2 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-029, RF-101, RF-167.
**Prueba de recorrido:** PC-CU-019-N y PC-CU-019-A1…A1 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-080, RF-081, RF-082, RF-083, RF-187, RF-254.
**Prueba de recorrido:** PC-CU-020-N y PC-CU-020-A1…A3 (guiones y evidencia por enlazar).

### CU-021 — Actualizar capacidad del centro de acopio

**Actor:** Operario de centro de acopio. **Tipo:** base. **Prioridad:** Exigido en 2.º avance. **Paquete:** C.

**Objetivo:** El operario informa la capacidad disponible del centro de acopio, dato que el administrador utiliza para planificar hacia dónde derivar las recolecciones.

**Precondiciones:**

- Sesión activa del Operario.
- Existe un centro activo. La versión actual no vincula al Operario con un centro específico.

**Flujo normal:**

1. El Operario consulta la lista de centros autorizada por la API.
2. Selecciona un centro activo y ve su capacidad actual.
3. Ingresa la capacidad disponible y confirma.
4. El sistema valida rango y permiso, persiste el cambio y confirma.

**Postcondición:** Capacidad actualizada. La restricción a «su centro» queda pendiente de modelo y API.

**Alternativas:**

- **A1.0:** Capacidad fuera de rango: no se guarda y se informa el valor admitido.
- **A2.0:** Intento de cambiar el estado del centro: 403; la capacidad no se modifica.
- **A3.0:** Cancelación: no se modifica el registro.

**RF directos revisados:** RF-088, RF-189.
**Prueba de recorrido:** PC-CU-021-N y PC-CU-021-A1…A3 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-147, RF-148, RF-190, RF-211.
**Prueba de recorrido:** PC-CU-022-N y PC-CU-022-A1…A2 (guiones y evidencia por enlazar).

### CU-023 — Registrar incidencia

**Actor:** Vecino registrado. **Tipo:** base. **Prioridad:** Alcance mínimo. **Paquete:** D.

**Objetivo:** El vecino informa un problema sobre un contenedor: desborde, rotura o falta de recolección.

**Precondiciones:**

- Inicio de sesión del vecino.
- El contenedor sobre el que se reporta debe estar registrado.

**Flujo normal:**

1. El Vecino inicia «Informar incidencia».
2. Identifica un contenedor activo por código; la selección desde el mapa queda pendiente de RF-259/260.
3. Indica tipo de problema y descripción; puede adjuntar una foto cuando esté disponible CU-024.
4. Confirma el envío.
5. El sistema valida, crea la incidencia abierta y su primer asiento de historial en una transacción.
6. El sistema devuelve el número INC y, solo para desborde de un contenedor disponible, actualiza su estado e historial.

**Postcondición:** La incidencia queda registrada, abierta y visible para el administrador.

**Alternativas:**

- **A1.0:** Contenedor inexistente o retirado: se rechaza el informe.
- **A2.0:** Incidencia abierta del mismo tipo: respuesta de conflicto con el número existente; la adhesión sigue pendiente.
- **A3.0:** Sesión ausente o rol distinto de Vecino: se rechaza sin crear la incidencia.

**RF directos revisados:** RF-217, RF-218, RF-219, RF-220, RF-221, RF-233, RF-234, RF-235.
**Prueba de recorrido:** PC-CU-023-N y PC-CU-023-A1…A3 (guiones y evidencia por enlazar).

### CU-024 — Adjuntar evidencia fotográfica

**Actor:** Vecino registrado. **Tipo:** extend. **Prioridad:** Ampliación propuesta; pendiente P4b. **Paquete:** D.

**Objetivo:** Extensión opcional de CU-023 para adjuntar una imagen; aún no está implementada.

**Precondiciones:**

- El caso de uso «Registrar incidencia» (CU-023) está en ejecución.

**Flujo normal:**

1. El vecino selecciona la opción de adjuntar una imagen.
2. El sistema abre el selector de archivos.
3. El vecino selecciona la imagen y confirma.
4. El sistema valida el formato y el tamaño, y asocia la imagen a la incidencia.

**Postcondición:** La incidencia queda registrada con la imagen adjunta.

**Alternativas:**

- **A1.0:** Si el archivo no es una imagen o supera el tamaño máximo, el sistema lo rechaza e informa el motivo; la incidencia se registra igualmente sin adjunto.

**RF directos revisados:** RF-222, RF-223.
**Prueba de recorrido:** PC-CU-024-N y PC-CU-024-A1…A1 (guiones y evidencia por enlazar).

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

- **A1.0:** No hay cuadrilla activa o disponible: la asignación no se realiza.
- **A2.0:** Cancelación: la incidencia conserva su estado y asignación.
- **A3.0:** La verificación de disponibilidad del camión indicada en RF-228 requiere la API de recolección de P5.

**RF directos revisados:** RF-224, RF-225, RF-226, RF-227, RF-228.
**Prueba de recorrido:** PC-CU-025-N y PC-CU-025-A1…A3 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-229, RF-230, RF-231, RF-232, RF-233, RF-234, RF-235.
**Prueba de recorrido:** PC-CU-026-N y PC-CU-026-A1…A3 (guiones y evidencia por enlazar).

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

**RF directos revisados:** ninguno; regla interna / correspondencia indirecta.
**Prueba de recorrido:** regla verificable mediante las PA de sus RF, sin PC autónoma.

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

**RF directos revisados:** RF-229, RF-237, RF-238, RF-239, RF-240, RF-241.
**Prueba de recorrido:** PC-CU-028-N y PC-CU-028-A1…A1 (guiones y evidencia por enlazar).

### CU-029 — Consultar inventario de recursos

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Obligatorio. **Paquete:** E.

**Objetivo:** El Administrador consulta contenedores y camiones. El stock de repuestos prometido en RF-027/102 aún carece de modelo y pantalla; por ello el inventario completo está pendiente.

**Precondiciones:**

- Inicio de sesión del administrador.

**Flujo normal:**

1. El Administrador abre el inventario de recursos.
2. El sistema presenta los camiones y contenedores registrados con su estado.
3. La consulta de repuestos se habilitará al definir unidad de stock y persistencia.

**Postcondición:** Se muestran los recursos existentes; no se declara cubierto el stock de repuestos.

**Alternativas:**

- **A1.0:** Si no hay recursos registrados, el sistema informa que el inventario está vacío.

**RF directos revisados:** RF-101, RF-102, RF-192.
**Prueba de recorrido:** PC-CU-029-N y PC-CU-029-A1…A1 (guiones y evidencia por enlazar).

### CU-030 — Generar reporte de incidencias por zona

**Actor:** Administrador municipal. **Tipo:** base. **Prioridad:** Informe simple exigido en 3.er avance; métricas y exportación complementarias. **Paquete:** E.

**Objetivo:** El administrador obtiene un reporte de las incidencias registradas en un período, agrupadas por zona, para sustentar decisiones de gestión.

**Precondiciones:**

- Sesión activa del Administrador.

**Flujo normal:**

1. El Administrador abre reportes y elige un período válido.
2. El sistema agrupa por zona y estado las incidencias del período y muestra totales y detalle (RF-242/243).
3. El tiempo de resolución y la exportación amplían el informe según RF-244/245.

**Postcondición:** El informe por zona se muestra, incluso si los totales son cero; métricas y exportación quedan pendientes.

**Alternativas:**

- **A1.0:** Sin incidencias en el período: se muestran totales cero y una explicación.
- **A2.0:** Período inválido: se señala el error y no se consulta.

**RF directos revisados:** RF-242, RF-243, RF-244, RF-245.
**Prueba de recorrido:** PC-CU-030-N y PC-CU-030-A1…A2 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-246, RF-247, RF-248, RF-249, RF-250, RF-251, RF-252, RF-253.
**Prueba de recorrido:** PC-CU-031-N y PC-CU-031-A1…A5 (guiones y evidencia por enlazar).

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

**RF directos revisados:** RF-255, RF-256.
**Prueba de recorrido:** PC-CU-032-N y PC-CU-032-A1…A2 (guiones y evidencia por enlazar).

## 6. Matriz RF → CU → prueba

La prueba de aceptación se identifica con el mismo RF. La ERS aporta un criterio general, pero faltan guion, datos, versión, entorno y evidencia enlazada. «—» indica requisito sin objetivo de actor directo en el catálogo actual; no se le asigna un CU por conveniencia.

| RF | Requisito (resumen) | CU directos | Prueba prevista | Situación |
|---|---|---|---|---|
| RF-001 | El sistema debe permitir registrar nuevos usuarios. | CU-003, CU-006 | PA-RF-001 | Criterio previsto; evidencia sin enlazar |
| RF-002 | El sistema debe permitir iniciar sesión con usuario y contraseña. | CU-001 | PA-RF-002 | Criterio previsto; evidencia sin enlazar |
| RF-003 | El sistema debe permitir cerrar sesión. | CU-002 | PA-RF-003 | Criterio previsto; evidencia sin enlazar |
| RF-004 | El sistema debe permitir visualizar el perfil del usuario autenticado. | — | PA-RF-004 | Brecha: definir objetivo/flujo o regla transversal |
| RF-005 | El sistema debe permitir modificar datos básicos del perfil del usuario. | CU-007 | PA-RF-005 | Criterio previsto; evidencia sin enlazar |
| RF-006 | El sistema debe permitir administrar usuarios mediante alta, baja, modificación y consulta. | CU-006, CU-007, CU-008 | PA-RF-006 | Criterio previsto; evidencia sin enlazar |
| RF-007 | El sistema debe permitir asignar roles a los usuarios. | CU-011 | PA-RF-007 | Criterio previsto; evidencia sin enlazar |
| RF-008 | El sistema debe manejar diferentes roles: administrador municipal, cuadrilla de recolección, operario de vertedero o centro de acopio y vecino. | CU-011 | PA-RF-008 | Criterio previsto; evidencia sin enlazar |
| RF-009 | El sistema debe mostrar funcionalidades diferentes según el rol del usuario. | — | PA-RF-009 | Brecha: definir objetivo/flujo o regla transversal |
| RF-010 | El sistema debe permitir aprobar o rechazar solicitudes de registro de usuarios. | CU-005 | PA-RF-010 | Criterio previsto; evidencia sin enlazar |
| RF-011 | El sistema debe permitir consultar el listado de usuarios registrados. | CU-009 | PA-RF-011 | Criterio previsto; evidencia sin enlazar |
| RF-012 | El sistema debe permitir filtrar usuarios por rol o estado. | CU-010 | PA-RF-012 | Criterio previsto; evidencia sin enlazar |
| RF-013 | El sistema debe permitir registrar nuevos contenedores. | CU-012 | PA-RF-013 | Criterio previsto; evidencia sin enlazar |
| RF-014 | El sistema debe permitir consultar contenedores registrados. | CU-015 | PA-RF-014 | Criterio previsto; evidencia sin enlazar |
| RF-015 | El sistema debe permitir modificar datos de un contenedor. | CU-013 | PA-RF-015 | Criterio previsto; evidencia sin enlazar |
| RF-016 | El sistema debe dar de baja lógicamente un contenedor, conservando su registro y su historial. | CU-014 | PA-RF-016 | Criterio previsto; evidencia sin enlazar |
| RF-017 | El sistema debe registrar la ubicación del contenedor. | CU-012 | PA-RF-017 | Criterio previsto; evidencia sin enlazar |
| RF-018 | El sistema debe registrar el estado del contenedor. | CU-012, CU-013, CU-014 | PA-RF-018 | Criterio previsto; evidencia sin enlazar |
| RF-019 | El sistema debe registrar el tipo de residuo asociado al contenedor. | CU-012 | PA-RF-019 | Criterio previsto; evidencia sin enlazar |
| RF-026 | El sistema debe permitir registrar contenedores de repuesto. | — | PA-RF-026 | Brecha: definir objetivo/flujo o regla transversal |
| RF-027 | El sistema debe permitir consultar el stock de contenedores de repuesto. | — | PA-RF-027 | Brecha: definir objetivo/flujo o regla transversal |
| RF-028 | El sistema debe permitir registrar camiones recolectores. | CU-016 | PA-RF-028 | Criterio previsto; evidencia sin enlazar |
| RF-029 | El sistema debe permitir consultar camiones registrados. | CU-019 | PA-RF-029 | Criterio previsto; evidencia sin enlazar |
| RF-030 | El sistema debe permitir modificar datos de un camión. | CU-017 | PA-RF-030 | Criterio previsto; evidencia sin enlazar |
| RF-031 | El sistema debe dar de baja lógicamente un camión, conservando su registro histórico. | CU-018 | PA-RF-031 | Criterio previsto; evidencia sin enlazar |
| RF-032 | El sistema debe registrar el estado del camión. | CU-016 | PA-RF-032 | Criterio previsto; evidencia sin enlazar |
| RF-080 | El sistema debe permitir registrar centros de acopio. | CU-020 | PA-RF-080 | Criterio previsto; evidencia sin enlazar |
| RF-081 | El sistema debe permitir consultar centros de acopio. | CU-020 | PA-RF-081 | Criterio previsto; evidencia sin enlazar |
| RF-082 | El sistema debe permitir modificar datos de centros de acopio. | CU-020 | PA-RF-082 | Criterio previsto; evidencia sin enlazar |
| RF-083 | El sistema debe permitir dar de baja centros de acopio. | CU-020 | PA-RF-083 | Criterio previsto; evidencia sin enlazar |
| RF-088 | El sistema debe registrar la capacidad de los centros de acopio. | CU-021 | PA-RF-088 | Criterio previsto; evidencia sin enlazar |
| RF-089 | El sistema debe registrar el tipo de residuos que gestiona cada centro de acopio. | — | PA-RF-089 | Brecha: definir objetivo/flujo o regla transversal |
| RF-101 | El sistema debe permitir consultar inventario de camiones recolectores. | CU-019, CU-029 | PA-RF-101 | Criterio previsto; evidencia sin enlazar |
| RF-102 | El sistema debe permitir consultar inventario de contenedores de repuesto. | CU-029 | PA-RF-102 | Criterio previsto; evidencia sin enlazar |
| RF-103 | El sistema debe permitir actualizar el stock de contenedores de repuesto. | — | PA-RF-103 | Brecha: definir objetivo/flujo o regla transversal |
| RF-147 | El sistema debe permitir registrar tipos de residuos. | CU-022 | PA-RF-147 | Criterio previsto; evidencia sin enlazar |
| RF-148 | El sistema debe permitir asociar tipos de residuos a contenedores. | CU-022 | PA-RF-148 | Criterio previsto; evidencia sin enlazar |
| RF-158 | El sistema debe mostrar una landing page pública. | — | PA-RF-158 | Brecha: definir objetivo/flujo o regla transversal |
| RF-159 | La landing page debe brindar información general sobre el sistema. | — | PA-RF-159 | Brecha: definir objetivo/flujo o regla transversal |
| RF-160 | La landing page debe permitir enviar solicitudes de registro de usuario. | CU-003 | PA-RF-160 | Criterio previsto; evidencia sin enlazar |
| RF-161 | La landing page debe enviar las solicitudes de registro a la API correspondiente. | CU-003 | PA-RF-161 | Criterio previsto; evidencia sin enlazar |
| RF-162 | La landing page no debe permitir acceso directo a datos internos del sistema. | — | PA-RF-162 | Brecha: definir objetivo/flujo o regla transversal |
| RF-163 | El frontend debe permitir el registro de usuarios. | CU-006 | PA-RF-163 | Criterio previsto; evidencia sin enlazar |
| RF-164 | El frontend debe permitir el inicio de sesión. | CU-001 | PA-RF-164 | Criterio previsto; evidencia sin enlazar |
| RF-165 | El frontend debe permitir visualizar perfiles. | — | PA-RF-165 | Brecha: definir objetivo/flujo o regla transversal |
| RF-166 | El frontend debe adaptar la interfaz según el rol del usuario. | — | PA-RF-166 | Brecha: definir objetivo/flujo o regla transversal |
| RF-167 | El frontend debe permitir acceder a listados de usuarios, contenedores y camiones. | CU-009, CU-015, CU-019 | PA-RF-167 | Criterio previsto; evidencia sin enlazar |
| RF-168 | El frontend debe permitir completar formularios de usuarios, contenedores y camiones. | — | PA-RF-168 | Brecha: definir objetivo/flujo o regla transversal |
| RF-169 | El frontend debe permitir realizar CRUD de usuarios. | CU-006, CU-007, CU-008 | PA-RF-169 | Criterio previsto; evidencia sin enlazar |
| RF-170 | El frontend debe permitir realizar CRUD de contenedores. | CU-012, CU-013, CU-014 | PA-RF-170 | Criterio previsto; evidencia sin enlazar |
| RF-171 | El frontend debe permitir realizar CRUD de camiones. | CU-016, CU-017, CU-018 | PA-RF-171 | Criterio previsto; evidencia sin enlazar |
| RF-176 | La API de usuarios debe permitir registrar usuarios. | CU-006 | PA-RF-176 | Criterio previsto; evidencia sin enlazar |
| RF-177 | La API de usuarios debe permitir autenticar usuarios. | CU-001 | PA-RF-177 | Criterio previsto; evidencia sin enlazar |
| RF-178 | La API de usuarios debe gestionar inicio de sesión. | CU-001 | PA-RF-178 | Criterio previsto; evidencia sin enlazar |
| RF-179 | La API de usuarios debe gestionar perfiles de usuario. | CU-007 | PA-RF-179 | Criterio previsto; evidencia sin enlazar |
| RF-180 | La API de usuarios debe administrar roles. | CU-011 | PA-RF-180 | Criterio previsto; evidencia sin enlazar |
| RF-181 | La API de usuarios debe recibir solicitudes de registro para aprobación posterior. | CU-003, CU-005 | PA-RF-181 | Criterio previsto; evidencia sin enlazar |
| RF-182 | La API de usuarios debe devolver datos de usuarios al frontend según permisos. | CU-009 | PA-RF-182 | Criterio previsto; evidencia sin enlazar |
| RF-183 | La API de gestión debe permitir registrar contenedores. | CU-012 | PA-RF-183 | Criterio previsto; evidencia sin enlazar |
| RF-184 | La API de gestión debe permitir modificar contenedores. | CU-013 | PA-RF-184 | Criterio previsto; evidencia sin enlazar |
| RF-185 | La API de gestión debe permitir dar de baja contenedores. | CU-014 | PA-RF-185 | Criterio previsto; evidencia sin enlazar |
| RF-186 | La API de gestión debe permitir consultar contenedores. | CU-015 | PA-RF-186 | Criterio previsto; evidencia sin enlazar |
| RF-187 | La API de gestión debe administrar centros de acopio. | CU-020 | PA-RF-187 | Criterio previsto; evidencia sin enlazar |
| RF-189 | La API de gestión debe gestionar capacidad de centros de acopio. | CU-021 | PA-RF-189 | Criterio previsto; evidencia sin enlazar |
| RF-190 | La API de gestión debe gestionar tipos de residuos. | CU-022 | PA-RF-190 | Criterio previsto; evidencia sin enlazar |
| RF-191 | La API de gestión debe administrar stock de repuestos. | — | PA-RF-191 | Brecha: definir objetivo/flujo o regla transversal |
| RF-192 | La API de gestión debe permitir consultar inventario de contenedores de repuesto. | CU-029 | PA-RF-192 | Criterio previsto; evidencia sin enlazar |
| RF-210 | El sistema debe permitir parametrizar datos generales. | — | PA-RF-210 | Brecha: definir objetivo/flujo o regla transversal |
| RF-211 | El sistema debe permitir administrar tipos de residuos. | CU-022 | PA-RF-211 | Criterio previsto; evidencia sin enlazar |
| RF-216 | El sistema debe permitir cambiar el idioma del software. | — | PA-RF-216 | Brecha: definir objetivo/flujo o regla transversal |
| RF-217 | El sistema debe permitir a un vecino registrado informar una incidencia sobre un contenedor, indicando el tipo de problema y una descripción. | CU-023 | PA-RF-217 | Criterio previsto; evidencia sin enlazar |
| RF-218 | El sistema debe permitir identificar el contenedor afectado por su código; cuando esté disponible el mapa interactivo, también debe permitir seleccionarlo allí. | CU-023 | PA-RF-218 | Criterio previsto; evidencia sin enlazar |
| RF-219 | El sistema debe registrar toda incidencia nueva con estado «abierta», la fecha y hora del reporte y el usuario que la informó. | CU-023 | PA-RF-219 | Criterio previsto; evidencia sin enlazar |
| RF-220 | El sistema debe informar al usuario el número de incidencia generado al confirmar el registro. | CU-023 | PA-RF-220 | Criterio previsto; evidencia sin enlazar |
| RF-221 | El sistema debe rechazar el registro de una incidencia sobre un contenedor que no esté dado de alta. | CU-023 | PA-RF-221 | Criterio previsto; evidencia sin enlazar |
| RF-222 | El sistema debe permitir adjuntar una imagen a la incidencia en el momento de registrarla. | CU-024 | PA-RF-222 | Criterio previsto; evidencia sin enlazar |
| RF-223 | El sistema debe validar el formato y el tamaño de la imagen adjunta antes de asociarla a la incidencia. | CU-024 | PA-RF-223 | Criterio previsto; evidencia sin enlazar |
| RF-224 | El sistema debe permitir al administrador municipal consultar el listado de incidencias abiertas con su tipo, contenedor, zona y fecha de reporte. | CU-025 | PA-RF-224 | Criterio previsto; evidencia sin enlazar |
| RF-225 | El sistema debe permitir asignar una prioridad a cada incidencia. | CU-025 | PA-RF-225 | Criterio previsto; evidencia sin enlazar |
| RF-226 | El sistema debe permitir asignar la incidencia a una cuadrilla activa. | CU-025 | PA-RF-226 | Criterio previsto; evidencia sin enlazar |
| RF-227 | El sistema debe cambiar el estado de la incidencia a «en curso» al registrarse la asignación. | CU-025 | PA-RF-227 | Criterio previsto; evidencia sin enlazar |
| RF-228 | El sistema debe ofrecer para la asignación únicamente las cuadrillas y los camiones que estén disponibles. | CU-025 | PA-RF-228 | Criterio previsto; evidencia sin enlazar |
| RF-229 | El sistema debe permitir a una cuadrilla consultar únicamente las incidencias que tiene asignadas. | CU-026, CU-028 | PA-RF-229 | Criterio previsto; evidencia sin enlazar |
| RF-230 | El sistema debe permitir a la cuadrilla registrar el avance de una incidencia asignada, con una observación. | CU-026 | PA-RF-230 | Criterio previsto; evidencia sin enlazar |
| RF-231 | El sistema debe permitir dar una incidencia por resuelta y registrar su fecha de resolución. | CU-026 | PA-RF-231 | Criterio previsto; evidencia sin enlazar |
| RF-232 | El sistema debe conservar el historial de cambios de estado de cada incidencia, con fecha, usuario y observación. | CU-026 | PA-RF-232 | Criterio previsto; evidencia sin enlazar |
| RF-233 | El sistema debe registrar un asiento en el historial del contenedor cada vez que su estado cambie, con la fecha y hora, el estado y una observación. | CU-013, CU-014, CU-023, CU-026 | PA-RF-233 | Criterio previsto; evidencia sin enlazar |
| RF-234 | El sistema debe actualizar el estado vigente del contenedor al registrarse un nuevo asiento en su historial. | CU-013, CU-014, CU-023, CU-026 | PA-RF-234 | Criterio previsto; evidencia sin enlazar |
| RF-235 | El sistema no debe sobrescribir ni eliminar los asientos anteriores del historial de un contenedor. | CU-013, CU-014, CU-023, CU-026 | PA-RF-235 | Criterio previsto; evidencia sin enlazar |
| RF-236 | El sistema debe permitir consultar el historial completo de estados de un contenedor desde su ficha. | CU-015 | PA-RF-236 | Criterio previsto; evidencia sin enlazar |
| RF-237 | El sistema debe mostrar el listado de incidencias con contenedor, zona, tipo, estado, prioridad y fecha. | CU-028 | PA-RF-237 | Criterio previsto; evidencia sin enlazar |
| RF-238 | El sistema debe limitar el alcance de la consulta según el rol del usuario: el administrador ve todas las incidencias y la cuadrilla solo las asignadas a ella. | CU-028 | PA-RF-238 | Criterio previsto; evidencia sin enlazar |
| RF-239 | El sistema debe permitir filtrar el listado de incidencias por estado, zona y período. | CU-028 | PA-RF-239 | Criterio previsto; evidencia sin enlazar |
| RF-240 | El sistema debe permitir abrir el detalle de una incidencia con su responsable y su historial de cambios. | CU-028 | PA-RF-240 | Criterio previsto; evidencia sin enlazar |
| RF-241 | El sistema debe permitir a un vecino consultar el estado de las incidencias que él mismo informó. | CU-028 | PA-RF-241 | Criterio previsto; evidencia sin enlazar |
| RF-242 | El sistema debe generar un informe de incidencias abiertas y cerradas para un período seleccionado. | CU-030 | PA-RF-242 | Criterio previsto; evidencia sin enlazar |
| RF-243 | El informe debe mostrar el total de incidencias abiertas y cerradas y el detalle de las incidencias incluidas. | CU-030 | PA-RF-243 | Criterio previsto; evidencia sin enlazar |
| RF-244 | El sistema debe calcular el tiempo de resolución de las incidencias cerradas dentro del período consultado. | CU-030 | PA-RF-244 | Criterio previsto; evidencia sin enlazar |
| RF-245 | El sistema debe permitir exportar el reporte generado. | CU-030 | PA-RF-245 | Criterio previsto; evidencia sin enlazar |
| RF-246 | El sistema debe permitir al administrador municipal registrar una máquina indicando su código patrimonial, su tipo, su marca, su modelo y el centro de acopio en el que se encuentra. | CU-031 | PA-RF-246 | Criterio previsto; evidencia sin enlazar |
| RF-247 | El sistema debe rechazar el alta de una máquina cuyo código ya pertenezca a otra, e informar la duplicación. | CU-031 | PA-RF-247 | Criterio previsto; evidencia sin enlazar |
| RF-248 | El sistema debe exigir que toda máquina esté asociada a un centro de acopio existente, y rechazar el alta que no lo indique. | CU-031 | PA-RF-248 | Criterio previsto; evidencia sin enlazar |
| RF-249 | El sistema debe admitir únicamente los tipos de maquinaria previstos —compactadora, pala cargadora, cinta de clasificación, retroexcavadora, montacargas y otra— y rechazar cualquier otro valor. | CU-031 | PA-RF-249 | Criterio previsto; evidencia sin enlazar |
| RF-250 | El sistema debe registrar toda máquina nueva con estado «operativa» si no se indica otro de los estados admitidos. | CU-031 | PA-RF-250 | Criterio previsto; evidencia sin enlazar |
| RF-251 | El sistema debe permitir modificar el código, el tipo, la marca, el modelo, el estado y el centro de acopio de una máquina ya registrada. | CU-031 | PA-RF-251 | Criterio previsto; evidencia sin enlazar |
| RF-252 | El sistema debe permitir dar de baja una máquina, dejándola con estado «fuera de servicio» sin eliminarla del inventario. | CU-031 | PA-RF-252 | Criterio previsto; evidencia sin enlazar |
| RF-253 | El sistema debe impedir dar de baja una máquina que ya se encuentre fuera de servicio, e informar la situación. | CU-031 | PA-RF-253 | Criterio previsto; evidencia sin enlazar |
| RF-254 | El sistema debe advertir, al dar de baja un centro de acopio, cuánta maquinaria queda asociada a él, para que pueda reasignarse. | CU-020 | PA-RF-254 | Criterio previsto; evidencia sin enlazar |
| RF-255 | El sistema debe permitir consultar el inventario de maquinaria mostrando, para cada máquina, su código, su tipo, su marca y modelo, el nombre del centro de acopio y su estado. | CU-032 | PA-RF-255 | Criterio previsto; evidencia sin enlazar |
| RF-256 | El sistema debe permitir buscar y ordenar el inventario por cualquiera de los datos mostrados. | CU-032 | PA-RF-256 | Criterio previsto; evidencia sin enlazar |
| RF-257 | La API de recolección debe devolver a cada cuadrilla autenticada las rutas y tareas que le fueron asignadas, con fecha, camión y contenedores previstos. | — | PA-RF-257 | Brecha: definir objetivo/flujo o regla transversal |
| RF-258 | La API de recolección debe permitir a la cuadrilla registrar la finalización de una tarea asignada con fecha, hora y observación, sin alterar tareas de otra cuadrilla. | — | PA-RF-258 | Brecha: definir objetivo/flujo o regla transversal |
| RF-259 | El mapa interactivo debe mostrar los contenedores activos con sus coordenadas y estado, y permitir abrir su ficha. | — | PA-RF-259 | Brecha: definir objetivo/flujo o regla transversal |
| RF-260 | El mapa interactivo debe mostrar las incidencias abiertas asociadas a un contenedor y permitir abrir su detalle según los permisos del usuario. | — | PA-RF-260 | Brecha: definir objetivo/flujo o regla transversal |
| RF-261 | El panel de control debe mostrar los totales de incidencias abiertas y cerradas para un período seleccionado. | — | PA-RF-261 | Brecha: definir objetivo/flujo o regla transversal |
| RF-262 | El panel de control debe mostrar los contenedores con más incidencias de desborde dentro del período seleccionado, ordenados por cantidad. | — | PA-RF-262 | Brecha: definir objetivo/flujo o regla transversal |
| RF-263 | El panel debe mostrar al administrador una alerta visual por cada incidencia abierta con prioridad alta; al cerrarse la incidencia, la alerta debe desaparecer del listado calculado. | — | PA-RF-263 | Brecha: definir objetivo/flujo o regla transversal |

## 7. Brechas y próxima decisión

**22 RF sin CU directo:** RF-004, RF-009, RF-026, RF-027, RF-089, RF-103, RF-158, RF-159, RF-162, RF-165, RF-166, RF-168, RF-191, RF-210, RF-216, RF-257, RF-258, RF-259, RF-260, RF-261, RF-262, RF-263.

**Casos sin RF directo:** CU-004 y CU-027 son controles internos y se trazan a RF-009/166/182 y RF-233/235, respectivamente; no se cuentan como objetivos de actor. Los 30 objetivos de actor tienen al menos un RF directo. **Casos sin prueba enlazada:** los 30; los identificadores PC y PA no sustituyen el vínculo con las pruebas de código existentes ni su resultado.

Se necesitan nuevos objetivos para perfil/idioma, repuestos, tipos gestionados por centro, consulta pública de landing y funciones del tercer avance (RF-257/263), o una decisión explícita de integrar cada uno en un CU existente con flujo y actor verificables. No se inventan CU-033 en este paso.

Pendientes de validación: actor y alcance real del Operario; política de duplicados; disponibilidad de camión al asignar; qué estados del contenedor pueden cambiar por incidencia; alcance exacto de foto, inventario y reporte. El paso 11 deberá incorporar estas fichas y diagramas al integrador y verificar la numeración cruzada.

## 8. Fuentes

- ERS_Consolidada_SiGeRU_v2.md (versión 2.1, matriz RF actualizada en este paso).
- generadores/casos_uso_datos.py; generadores/doc12_requerimientos_incidencias.py; generadores/doc13_adenda_maquinaria.py.
- sigeru/ciberseguridad/ampliacion-matriz-de-permisos-3ra.md; sigeru/api-gestion/core/Permisos.php; sigeru/programacion-full-stack/ampliacion-incidencias-3ra.md.
- Consigna Proyecto_ESI_2026.pdf, secciones 5 y 9.4.
