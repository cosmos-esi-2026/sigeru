# SiGeRU — Especificación de Requerimientos de Software (ERS) consolidada

**Versión:** 3.0, revisión del alcance vigente · **Fecha:** 05/11/2026 · **Entrega:** 06/11/2026

## 1. Introducción (IEEE 830)

### 1.1 Propósito
Acá reunimos los requerimientos de la ERS inicial y sus ampliaciones, y anotamos qué cambió antes de la tercera entrega. La usamos para revisar el sistema con el equipo y la docente. No tenemos una validación de una intendencia.

### 1.2 Alcance y versiones
La segunda entrega queda como registro de lo entregado en esa fecha. Esta revisión conserva **117 RF** y **17 RNF**, incluidos los códigos históricos de funciones retiradas. No borramos ni reutilizamos esos códigos porque así se puede seguir el cambio. «Exigido» señala lo que pide la consigna; «propuesto», una decisión del equipo que todavía se puede revisar. Que un requerimiento figure acá no prueba que esté implementado.

### 1.3 Fuentes y convenciones
Mantenemos los saltos de numeración del catálogo original. Las fuentes son la ERS de julio, las ampliaciones de incidencias y maquinaria, la consigna y las decisiones de alcance del equipo. Los criterios de carga, disponibilidad y usabilidad siguen pendientes de acuerdo.


### 1.4 Definiciones y organización
RF: requerimiento funcional; RNF: requerimiento no funcional; CU: caso de uso; CRUD: alta, consulta, modificación y baja; API: interfaz HTTP. Las secciones 2 y 3 siguen descripción general y requerimientos específicos de IEEE 830.

## 2. Descripción general (IEEE 830)

### 2.1 Perspectiva del producto
SiGeRU tiene una página pública, pantallas de gestión, APIs de usuarios, gestión y recolección, y una base de datos. Los perfiles son administrador municipal, cuadrilla, operario de centro y vecino registrado. No encontramos confirmación de un organismo piloto real.

### 2.2 Funciones y límites
La consigna incluye usuarios, contenedores, camiones, repuestos, incidencias e informe simple. También pide centros y maquinaria en el segundo avance, y API de recolección, mapa, panel y alertas en el tercero. En el código actual se retiró la foto de las incidencias. Los repuestos siguen sin evidencia de implementación. Lo que se hizo y lo que falta se distinguen en cada requerimiento.

### 2.3 Clases de usuario
El administrador gestiona recursos, cuentas e incidencias. La cuadrilla atiende las tareas asignadas y entra con cédula y contraseña. El operario trabaja con los recursos de su centro. El vecino consulta e informa incidencias. Falta validar estos recorridos con personas ajenas al desarrollo.

### 2.4 Restricciones
La consigna fija PHP 8, MySQL 8, Linux, Docker y Git. La corrida de pruebas guardada el 03/11 usó MariaDB 10.4.32; esa diferencia con MySQL 8 sigue abierta y no la damos por resuelta. También hay que controlar los permisos por rol y revisar el tratamiento legal de los datos personales.

### 2.5 Suposiciones y dependencias
Hay datos de prueba de zonas, recursos y usuarios; no hay datos operativos confirmados por una intendencia. La entrevista intentada no dio información utilizable. La validación externa del prototipo sigue pendiente. RF-257 a RF-263 detallan funciones de la consigna, pero su redacción todavía necesita revisión del equipo y la docente.

## 3. Requerimientos específicos (IEEE 830)

### 3.1 Interfaces externas
La interfaz se usa desde navegadores de escritorio y móvil. Las APIs reciben pedidos HTTP y controlan la sesión y los permisos. En el entorno de pruebas la base corre sobre MariaDB 10.4.32. El contrato detallado de las funciones RF-257 a RF-263 todavía requiere revisión.

### 3.2 Requerimientos funcionales

#### RF: Gestión de usuarios y roles

| Código | Requerimiento |
|---|---|
| RF-001 | El sistema debe permitir registrar nuevos usuarios. |
| RF-002 | El sistema debe permitir iniciar sesión con correo electrónico y contraseña para los perfiles generales, y con cédula y contraseña para la cuadrilla. |
| RF-003 | El sistema debe permitir cerrar sesión. |
| RF-004 | El sistema debe permitir visualizar el perfil del usuario autenticado. |
| RF-005 | El sistema debe permitir modificar datos básicos del perfil del usuario. |
| RF-006 | El sistema debe permitir administrar usuarios mediante alta, baja, modificación y consulta. |
| RF-007 | El sistema debe permitir asignar roles a los usuarios. |
| RF-008 | El sistema debe manejar diferentes roles: administrador municipal, cuadrilla de recolección, operario de vertedero o centro de acopio y vecino. |
| RF-009 | El sistema debe mostrar funcionalidades diferentes según el rol del usuario. |
| RF-010 | El sistema debe permitir aprobar o rechazar solicitudes de registro de usuarios. |
| RF-011 | El sistema debe permitir consultar el listado de usuarios registrados. |
| RF-012 | El sistema debe permitir filtrar usuarios por rol o estado. |

#### RF: Gestión de contenedores

| Código | Requerimiento |
|---|---|
| RF-013 | El sistema debe permitir registrar nuevos contenedores. |
| RF-014 | El sistema debe permitir consultar contenedores registrados. |
| RF-015 | El sistema debe permitir modificar datos de un contenedor. |
| RF-016 | El sistema debe dar de baja lógicamente un contenedor, conservando su registro y su historial. |
| RF-017 | El sistema debe registrar la ubicación del contenedor. |
| RF-018 | El sistema debe registrar el estado del contenedor. |
| RF-019 | El sistema debe registrar el tipo de residuo asociado al contenedor. |
| RF-026 | El sistema debe permitir registrar contenedores de repuesto. |
| RF-027 | El sistema debe permitir consultar el stock de contenedores de repuesto. |

#### RF: Gestión de camiones y flota

| Código | Requerimiento |
|---|---|
| RF-028 | El sistema debe permitir registrar camiones recolectores. |
| RF-029 | El sistema debe permitir consultar camiones registrados. |
| RF-030 | El sistema debe permitir modificar datos de un camión. |
| RF-031 | El sistema debe dar de baja lógicamente un camión, conservando su registro histórico. |
| RF-032 | El sistema debe registrar el estado del camión. |

#### RF: Gestión de centros de acopio

| Código | Requerimiento |
|---|---|
| RF-080 | El sistema debe permitir registrar centros de acopio. |
| RF-081 | El sistema debe permitir consultar centros de acopio. |
| RF-082 | El sistema debe permitir modificar datos de centros de acopio. |
| RF-083 | El sistema debe permitir dar de baja centros de acopio. |
| RF-088 | El sistema debe registrar la capacidad de los centros de acopio. |
| RF-089 | El sistema debe registrar el tipo de residuos que gestiona cada centro de acopio. |

#### RF: Inventario básico

| Código | Requerimiento |
|---|---|
| RF-101 | El sistema debe permitir consultar inventario de camiones recolectores. |
| RF-102 | El sistema debe permitir consultar inventario de contenedores de repuesto. |
| RF-103 | El sistema debe permitir actualizar el stock de contenedores de repuesto. |

#### RF: Tipos de residuo

| Código | Requerimiento |
|---|---|
| RF-147 | El sistema debe permitir registrar tipos de residuos. |
| RF-148 | El sistema debe permitir asociar tipos de residuos a contenedores. |

#### RF: Página pública (landing page)

| Código | Requerimiento |
|---|---|
| RF-158 | El sistema debe mostrar una landing page pública. |
| RF-159 | El sistema debe brindar información general sobre el sistema. |
| RF-160 | El sistema debe permitir enviar solicitudes de registro de usuario. |
| RF-161 | El sistema debe enviar las solicitudes de registro de la landing page a la API correspondiente. |
| RF-162 | El sistema no debe permitir, desde la landing page, el acceso directo a datos internos. |

#### RF: Frontend de usuarios

| Código | Requerimiento |
|---|---|
| RF-163 | El sistema debe permitir el registro de usuarios. |
| RF-164 | El sistema debe permitir el inicio de sesión. |
| RF-165 | El sistema debe permitir visualizar perfiles. |
| RF-166 | El sistema debe adaptar la interfaz según el rol del usuario. |
| RF-167 | El sistema debe permitir acceder a listados de usuarios, contenedores y camiones. |
| RF-168 | El sistema debe permitir completar formularios de usuarios, contenedores y camiones. |
| RF-169 | El sistema debe permitir realizar CRUD de usuarios. |
| RF-170 | El sistema debe permitir realizar CRUD de contenedores. |
| RF-171 | El sistema debe permitir realizar CRUD de camiones. |

#### RF: API de usuarios

| Código | Requerimiento |
|---|---|
| RF-176 | El sistema debe permitir registrar usuarios. |
| RF-177 | El sistema debe autenticar con correo y contraseña a los perfiles generales, y con cédula y contraseña a la cuadrilla. |
| RF-178 | El sistema debe gestionar inicio de sesión. |
| RF-179 | El sistema debe gestionar perfiles de usuario. |
| RF-180 | El sistema debe administrar roles. |
| RF-181 | El sistema debe recibir solicitudes de registro para aprobación posterior. |
| RF-182 | El sistema debe devolver los datos de usuarios al frontend según los permisos. |

#### RF: API de gestión

| Código | Requerimiento |
|---|---|
| RF-183 | El sistema debe permitir registrar contenedores. |
| RF-184 | El sistema debe permitir modificar contenedores. |
| RF-185 | El sistema debe permitir dar de baja contenedores. |
| RF-186 | El sistema debe permitir consultar contenedores. |
| RF-187 | El sistema debe administrar centros de acopio. |
| RF-189 | El sistema debe gestionar capacidad de centros de acopio. |
| RF-190 | El sistema debe gestionar tipos de residuos. |
| RF-191 | El sistema debe administrar stock de repuestos. |
| RF-192 | El sistema debe permitir consultar inventario de contenedores de repuesto. |

#### RF: Parametrización del sistema

| Código | Requerimiento |
|---|---|
| RF-210 | El sistema debe permitir parametrizar datos generales. |
| RF-211 | El sistema debe permitir administrar tipos de residuos. |
| RF-216 | El sistema debe permitir cambiar el idioma del software. |

#### RF: Incidencias

| Código | Requerimiento |
|---|---|
| RF-217 | El sistema debe permitir a un vecino registrado informar una incidencia sobre un contenedor, indicando el tipo de problema y una descripción. |
| RF-218 | El sistema debe permitir identificar el contenedor afectado por su código; cuando esté disponible el mapa interactivo, también debe permitir seleccionarlo allí. |
| RF-219 | El sistema debe registrar toda incidencia nueva con estado «abierta», la fecha y hora del reporte y el usuario que la informó. |
| RF-220 | El sistema debe informar al usuario el número de incidencia generado al confirmar el registro. |
| RF-221 | El sistema debe rechazar el registro de una incidencia sobre un contenedor que no esté dado de alta. |
| RF-222 | El sistema debe permitir adjuntar una imagen a la incidencia en el momento de registrarla. |
| RF-223 | El sistema debe validar el formato y el tamaño de la imagen adjunta antes de asociarla a la incidencia. |
| RF-224 | El sistema debe permitir al administrador municipal consultar el listado de incidencias abiertas con su tipo, contenedor, zona y fecha de reporte. |
| RF-225 | El sistema debe permitir asignar una prioridad a cada incidencia. |
| RF-226 | El sistema debe permitir asignar la incidencia a una cuadrilla activa. |
| RF-227 | El sistema debe cambiar el estado de la incidencia a «en curso» al registrarse la asignación. |
| RF-228 | El sistema debe ofrecer para la asignación únicamente las cuadrillas y los camiones que estén disponibles. |
| RF-229 | El sistema debe permitir a una cuadrilla consultar únicamente las incidencias que tiene asignadas. |
| RF-230 | El sistema debe permitir a la cuadrilla registrar el avance de una incidencia asignada, con una observación. |
| RF-231 | El sistema debe permitir dar una incidencia por resuelta y registrar su fecha de resolución. |
| RF-232 | El sistema debe conservar el historial de cambios de estado de cada incidencia, con fecha, usuario y observación. |
| RF-233 | El sistema debe registrar un asiento en el historial del contenedor cada vez que su estado cambie, con la fecha y hora, el estado y una observación. |
| RF-234 | El sistema debe actualizar el estado vigente del contenedor al registrarse un nuevo asiento en su historial. |
| RF-235 | El sistema no debe sobrescribir ni eliminar los asientos anteriores del historial de un contenedor. |
| RF-236 | El sistema debe permitir consultar el historial completo de estados de un contenedor desde su ficha. |
| RF-237 | El sistema debe mostrar el listado de incidencias con contenedor, zona, tipo, estado, prioridad y fecha. |
| RF-238 | El sistema debe limitar el alcance de la consulta según el rol del usuario: el administrador ve todas las incidencias y la cuadrilla solo las asignadas a ella. |
| RF-239 | El sistema debe permitir filtrar el listado de incidencias por estado, zona y período. |
| RF-240 | El sistema debe permitir abrir el detalle de una incidencia con su responsable y su historial de cambios. |
| RF-241 | El sistema debe permitir a un vecino consultar el estado de las incidencias que él mismo informó. |
| RF-242 | El sistema debe generar un informe de incidencias abiertas y cerradas para un período seleccionado. |
| RF-243 | El sistema debe mostrar el total de incidencias abiertas y cerradas y el detalle de las incidencias incluidas. |
| RF-244 | El sistema debe calcular el tiempo de resolución de las incidencias cerradas dentro del período consultado. |
| RF-245 | El sistema debe permitir exportar el reporte generado. |

#### RF: Maquinaria

| Código | Requerimiento |
|---|---|
| RF-246 | El sistema debe permitir al administrador municipal registrar una máquina indicando su código patrimonial, su tipo, su marca, su modelo y el centro de acopio en el que se encuentra. |
| RF-247 | El sistema debe rechazar el alta de una máquina cuyo código ya pertenezca a otra, e informar la duplicación. |
| RF-248 | El sistema debe exigir que toda máquina esté asociada a un centro de acopio existente, y rechazar el alta que no lo indique. |
| RF-249 | El sistema debe admitir únicamente los tipos de maquinaria previstos —compactadora, pala cargadora, cinta de clasificación, retroexcavadora, montacargas y otra— y rechazar cualquier otro valor. |
| RF-250 | El sistema debe registrar toda máquina nueva con estado «operativa» si no se indica otro de los estados admitidos. |
| RF-251 | El sistema debe permitir modificar el código, el tipo, la marca, el modelo, el estado y el centro de acopio de una máquina ya registrada. |
| RF-252 | El sistema debe permitir dar de baja una máquina, dejándola con estado «fuera de servicio» sin eliminarla del inventario. |
| RF-253 | El sistema debe impedir dar de baja una máquina que ya se encuentre fuera de servicio, e informar la situación. |
| RF-254 | El sistema debe advertir, al dar de baja un centro de acopio, cuánta maquinaria queda asociada a él, para que pueda reasignarse. |
| RF-255 | El sistema debe permitir consultar el inventario de maquinaria mostrando, para cada máquina, su código, su tipo, su marca y modelo, el nombre del centro de acopio y su estado. |
| RF-256 | El sistema debe permitir buscar y ordenar el inventario por cualquiera de los datos mostrados. |

#### RF: Recolección

| Código | Requerimiento |
|---|---|
| RF-257 | El sistema debe devolver a cada cuadrilla autenticada las rutas y tareas que le fueron asignadas, con fecha, camión y contenedores previstos. |
| RF-258 | El sistema debe permitir a la cuadrilla registrar la finalización de una tarea asignada con fecha, hora y observación, sin alterar tareas de otra cuadrilla. |

#### RF: Mapa

| Código | Requerimiento |
|---|---|
| RF-259 | El sistema debe mostrar los contenedores activos con sus coordenadas y estado, y permitir abrir su ficha. |
| RF-260 | El sistema debe mostrar las incidencias abiertas asociadas a un contenedor y permitir abrir su detalle según los permisos del usuario. |

#### RF: Panel

| Código | Requerimiento |
|---|---|
| RF-261 | El sistema debe mostrar los totales de incidencias abiertas y cerradas para un período seleccionado. |
| RF-262 | El sistema debe mostrar los contenedores con más incidencias de desborde dentro del período seleccionado, ordenados por cantidad. |

#### RF: Alertas

| Código | Requerimiento |
|---|---|
| RF-263 | El sistema debe mostrar al administrador una alerta visual por cada incidencia abierta con prioridad alta; al cerrarse la incidencia, la alerta debe desaparecer del listado calculado. |

### 3.3 Requerimientos no funcionales

#### RNF: Seguridad

| Código | Requerimiento |
|---|---|
| RNF-001 | El sistema debe almacenar cada contraseña únicamente como un hash resistente a ataques de fuerza bruta generado por password_hash de PHP, verificarla con password_verify y no registrar la contraseña en texto plano. |
| RNF-002 | El sistema debe restringir las operaciones disponibles según el rol del usuario autenticado, tanto en el frontend como en el backend. |
| RNF-003 | El sistema debe transmitir credenciales y datos personales entre navegador y APIs mediante HTTPS/TLS; las solicitudes HTTP deben redirigirse a HTTPS o rechazarse en el entorno de despliegue. |
| RNF-004 | El sistema debe cumplir con la Ley N.º 18.331 de Protección de Datos Personales en el tratamiento de datos personales de usuarios y vecinos. |

#### RNF: Usabilidad

| Código | Requerimiento |
|---|---|
| RNF-005 | El sistema debe tener una interfaz utilizable por personal municipal sin conocimientos técnicos, con formularios simples y mensajes de error claros. |
| RNF-006 | El sistema debe permitir parametrizar el idioma de la interfaz, sin textos fijos incrustados en el código. |

#### RNF: Rendimiento y disponibilidad

| Código | Requerimiento |
|---|---|
| RNF-007 | El sistema debe responder en 3 segundos o menos, en el percentil 95, al iniciar sesión, consultar 100 registros y dar de alta un registro, en el entorno de referencia y con la carga de prueba definidos para la aceptación. |
| RNF-008 | El sistema debe tener una disponibilidad mensual de al menos 99 % durante la ventana de servicio semanal acordada con el organismo piloto, medida con una sonda externa cada cinco minutos. |

#### RNF: Compatibilidad y portabilidad

| Código | Requerimiento |
|---|---|
| RNF-009 | El sistema debe funcionar correctamente en los navegadores más usados (Chrome, Firefox, Edge) en sus versiones recientes. |
| RNF-010 | El sistema debe poder desplegarse mediante contenedores Docker sobre un servidor Linux. |

#### RNF: Arquitectura y mantenibilidad

| Código | Requerimiento |
|---|---|
| RNF-011 | El sistema debe implementarse como microservicios independientes, cada uno con su propia base de código y contenedor Docker. |
| RNF-012 | El sistema debe implementar la arquitectura MVC en cada aplicación. |
| RNF-013 | El sistema debe conservar el código fuente de cada aplicación y sus cambios en el repositorio Git de la organización, con commits identificables por fecha, autor y descripción. |

#### RNF: Calidad y documentación

| Código | Requerimiento |
|---|---|
| RNF-014 | El sistema debe contemplar pruebas automatizadas, al menos unitarias, sobre la lógica de las APIs. |
| RNF-015 | El sistema debe contar, en cada entrega, con versiones del manual de usuario, la guía de instalación y la guía de administración acordes con las funciones efectivamente disponibles. |

#### RNF: Integridad y trazabilidad

| Código | Requerimiento |
|---|---|
| RNF-016 | El sistema debe guardar el historial de estados de una incidencia y de un contenedor como solo agregado: ningún asiento puede modificarse ni eliminarse una vez registrado. Es la condición para que el sistema sirva como evidencia de qué se hizo y cuándo. |
| RNF-017 | El sistema debe asociar toda operación que cambie el estado de una incidencia al usuario que la realizó y a su fecha y hora. |

## 4. Matriz de trazabilidad

Esta tabla muestra algunos recorridos importantes y dónde están sus pruebas. Los resultados corresponden a la corrida guardada del 03/11 y a los casos de interfaz hechos por IA ese día; no son una prueba nueva ni una revisión del equipo. El detalle está en `testing-3ra/Plan_de_Testing_SiGeRU.md`.

| Parte del sistema | Requerimientos | Prueba registrada | Estado |
|---|---|---|---|
| Iniciar sesión | RF-002, RF-177 | CP-ACC-01 a 06; CP-UI-02 | Pruebas automáticas guardadas; caso de pantalla hecho por IA. |
| Permisos por rol | RF-009, RNF-002 | Tabla de decisión; HTTP 8, 11 y 19 | Pruebas automáticas guardadas. |
| Contenedores | RF-013 a RF-019 | CP-CON-01 a 16 | Pruebas guardadas; el valor mínimo de CP-CON-03 sigue sin prueba. |
| Informar y atender incidencias | RF-217 a RF-241 | CP-INC-01 a 15; CP-UI-01 | Pruebas automáticas guardadas; pantalla hecha por IA. |
| Recolecciones | RF-257, RF-258 | CP-REC-01 a 06; CP-UI-07 | Pruebas automáticas guardadas; recorrido de pantalla hecho por IA. |
| Informe y descarga | RF-242 a RF-245 | CP-PUB-03; CP-UI-08 | CSV parcial: falta abrirlo en una planilla y comprobar el archivo. |
| Fotos de incidencias | RF-222, RF-223 | No corresponde probarlas en esta versión. | Función retirada el 30/10. |
| Repuestos e idioma | RF-026, RF-027, RF-103, RF-191, RF-216; RNF-006 | No hay pruebas de funciones que no están implementadas. | No implementado en esta versión. |
| Firefox | RNF-009 | CP-UI-05 | Solo se probó en Chromium. |

Los demás requerimientos siguen en las secciones 3.2 y 3.3. El plan de testing tiene la lista completa de pruebas y también dice qué quedó sin comprobar.

## 5. Hallazgos y decisiones de revisión

- **D-01:** RF-001/163/176, RF-013/170/183 y equivalentes describen una misma capacidad en sistema, interfaz y API. Se conservan los códigos; la prueba debe cubrir una operación integral y sus capas.
- **D-02:** RF-027/102 y RF-103/191 duplican consulta y actualización de repuestos. Se conservan como referencias históricas; falta precisar si el stock se mide por unidades físicas o cantidad agregada.
- **D-03:** RF-080 a 083, RF-088/089, RF-187/189 y RF-246 a 256 figuraban como recomendados. La sección 9.4 exige centros y maquinaria en el 2.º avance; la prioridad vigente es exigida para ese corte.
- **D-04:** RF-242/243 figuraban recomendados. El informe simple está en el mínimo general y en el 3.er avance; sus totales abiertos/cerrados pasan a exigidos. RF-244/245 quedan como mejoras propuestas.
- **D-05:** RF-218 mezclaba identificación por código y mapa. El código permite registrar la incidencia; RF-259/260 cubren el mapa exigido en el 3.er avance.
- **D-06:** RNF-001 confundía cifrado reversible con hash de contraseña. RNF-007/008 todavía tienen métricas provisionales: faltan el entorno y la ventana acordados.
- **D-07:** RF-210 y otros requerimientos de parametrización carecen de catálogo de datos y resultado observable; RNF-004 requiere revisión jurídica y RNF-005 una prueba con usuarios. Siguen abiertos.
- **D-08:** RF-257 a 263 detallan API de recolección, mapa, panel y alertas del 3.er avance. El código tiene estas áreas, pero falta revisar con el equipo y la docente si cada criterio de aceptación está bien redactado y cubierto.
- **D-09:** CU-004 y CU-027 quedan como controles internos, fuera de los óvalos UML. El catálogo de casos de uso todavía necesita una revisión contra el sistema actual.
- **D-10:** RF-222 y RF-223 se conservan por trazabilidad, con estado retirado. El 30/10 salieron las rutas, las pantallas y la tabla de fotos. Aún quedan ProcesadorFoto.php y FotoIncidencia.php en la carpeta, sin referencias en las rutas actuales. Sus pruebas ya no aplican a esta versión.
- **D-11:** La cuadrilla entra con cédula y contraseña desde el 31/10. Por eso RF-002 y RF-177 se ajustaron; los manuales y casos de uso anteriores todavía requieren revisión.
- **D-12:** La consigna exige MySQL 8. La corrida de pruebas del 03/11 registra MariaDB 10.4.32. No hay prueba de compatibilidad con MySQL 8 en esta revisión.

## 6. Control de cambios

- Esta versión 3.0 conserva los 117 RF y 17 RNF. RF-222 y RF-223 quedan como retirados; RF-002 y RF-177 reflejan el acceso de la cuadrilla con cédula.
- RF-245 registra la descarga CSV incorporada el 03/11. Solo incluye el detalle visible y puede quedar truncado; no equivale a exportar el informe completo. RF-102 y RF-192 no se presentan como implementados: el inventario de repuestos sigue pendiente.
