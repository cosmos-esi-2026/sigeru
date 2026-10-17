# SiGeRU — Especificación de Requisitos de Software (ERS) consolidada

**Versión:** 2.1, borrador de trabajo · **Fecha:** 12/10/2026 · **Corte de la entrega evaluada:** 14/09/2026

## 1. Introducción (IEEE 830)

### 1.1 Propósito
Consolidar los requisitos de la ERS inicial, la ampliación de incidencias y la adenda de maquinaria, y hacer visible el alcance exigido para la tercera entrega. Está dirigida al equipo y a la evaluación académica. No constituye validación de una intendencia.

### 1.2 Alcance y versiones
La segunda entrega congelada permanece intacta. Esta es una versión de trabajo posterior: 70 RF iniciales + 29 de incidencias + 11 de maquinaria + 7 propuestos para detallar el tercer avance = **117 RF**; 15 RNF iniciales + 2 de incidencias = **17 RNF**. Los códigos no se reciclan. «Exigido» indica obligación de la consigna en el avance indicado; «propuesto» indica decisión técnica todavía revisable. La existencia de un requisito no demuestra que esté implementado.

### 1.3 Fuentes y convenciones
La numeración RF conserva los saltos del catálogo original. `CU —` indica falta de caso de uso directo; `PA` y `PN` son pruebas de aceptación **previstas, no ejecutadas**. Los criterios de carga, disponibilidad y usabilidad son propuestas de aceptación pendientes de acuerdo.

- **E1:** ERS inicial (17/07/2026), generadores/int_ers_met.py y PDF de la 1.ª entrega
- **E2:** Ampliación de incidencias (03/09/2026), generadores/doc12_requerimientos_incidencias.py
- **E3:** Adenda de maquinaria (09/09/2026), generadores/doc13_adenda_maquinaria.py
- **C:** Consigna Proyecto_ESI_2026.pdf, secciones 5 y 9.4
- **P:** Detalle propuesto por el equipo para el 3.er avance (12/10/2026); pendiente de validación
- **A:** Alcance_vigente_y_decisiones_SiGeRU.md, decisiones A-01 a A-10

### 1.4 Definiciones y organización
RF: requisito funcional; RNF: requisito no funcional; CU: caso de uso; CRUD: alta, consulta, modificación y baja; API: interfaz HTTP; PA/PN: prueba de aceptación prevista. Las secciones 2 y 3 siguen descripción general y requisitos específicos de IEEE 830.

## 2. Descripción general (IEEE 830)

### 2.1 Perspectiva del producto
Sistema web con landing, interfaz de gestión, APIs de usuarios, gestión y recolección, y persistencia. Los perfiles previstos son administrador municipal, cuadrilla, operario de centro y vecino registrado. El organismo piloto es propuesto; no se ha confirmado un cliente real.

### 2.2 Funciones y límites
El mínimo general incluye usuarios, contenedores, camiones, repuestos, incidencias e informe simple. Full Stack exige además centros y maquinaria en el segundo avance, y API de recolección, mapa, panel y alertas en el tercero. La planificación avanzada de rutas, predicciones y trazabilidad completa de reciclaje quedan fuera del mínimo general. Véase el registro de alcance A-01 a A-10.

### 2.3 Clases de usuario
Administrador municipal: administra recursos, cuentas e incidencias. Cuadrilla: atiende tareas e incidencias asignadas. Operario de centro: consulta y opera recursos del centro según permisos. Vecino registrado: consulta y reporta sus incidencias. Las tareas exactas de cada perfil requieren validación.

### 2.4 Restricciones
La consigna fija PHP 8, MySQL 8, Linux, Docker y Git. La separación por roles y el tratamiento de datos personales son restricciones transversales; la conformidad legal requiere revisión específica.

### 2.5 Suposiciones y dependencias
Se necesitan datos de zonas, recursos y usuarios de prueba, además de criterios operativos del organismo que todavía no se obtuvieron. La entrevista intentada no produjo información utilizable y la validación del prototipo sigue pendiente. La sección 9.4 fija funcionalidades, pero no define todos sus flujos; RF-257 a 263 son una propuesta verificable para discutir.

## 3. Requisitos específicos (IEEE 830)

### 3.1 Interfaces externas
La interfaz web se usa en navegadores de escritorio y móvil. Las APIs exponen operaciones HTTP con autenticación y respuestas de error definidas por contrato; la base relacional es MySQL. La especificación de rutas, esquemas de petición/respuesta y pantallas del tercer avance sigue pendiente para RF-257 a 263.

Cada fila expresa la prioridad vigente y el horizonte. El campo «estado» advierte los criterios que aún requieren definición o evidencia.

### 3.2 Requisitos funcionales

#### RF: Gestión de usuarios y roles

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-001 | El sistema debe permitir registrar nuevos usuarios. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-002 | El sistema debe permitir iniciar sesión con usuario y contraseña. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-003 | El sistema debe permitir cerrar sesión. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-004 | El sistema debe permitir visualizar el perfil del usuario autenticado. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-005 | El sistema debe permitir modificar datos básicos del perfil del usuario. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-006 | El sistema debe permitir administrar usuarios mediante alta, baja, modificación y consulta. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-007 | El sistema debe permitir asignar roles a los usuarios. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-008 | El sistema debe manejar diferentes roles: administrador municipal, cuadrilla de recolección, operario de vertedero o centro de acopio y vecino. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-009 | El sistema debe mostrar funcionalidades diferentes según el rol del usuario. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-010 | El sistema debe permitir aprobar o rechazar solicitudes de registro de usuarios. | Propuesto por el equipo | 3.º avance / validar | Vigente |
| RF-011 | El sistema debe permitir consultar el listado de usuarios registrados. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-012 | El sistema debe permitir filtrar usuarios por rol o estado. | Propuesto por el equipo | 3.º avance / validar | Vigente |

#### RF: Gestión de contenedores

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-013 | El sistema debe permitir registrar nuevos contenedores. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-014 | El sistema debe permitir consultar contenedores registrados. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-015 | El sistema debe permitir modificar datos de un contenedor. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-016 | El sistema debe dar de baja lógicamente un contenedor, conservando su registro y su historial. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-017 | El sistema debe registrar la ubicación del contenedor. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-018 | El sistema debe registrar el estado del contenedor. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-019 | El sistema debe registrar el tipo de residuo asociado al contenedor. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-026 | El sistema debe permitir registrar contenedores de repuesto. | Compromiso de la ERS original | 2.º avance | Pendiente de definición o evidencia de aceptación |
| RF-027 | El sistema debe permitir consultar el stock de contenedores de repuesto. | Compromiso de la ERS original | 2.º avance | Pendiente de definición o evidencia de aceptación |

#### RF: Gestión de camiones y flota

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-028 | El sistema debe permitir registrar camiones recolectores. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-029 | El sistema debe permitir consultar camiones registrados. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-030 | El sistema debe permitir modificar datos de un camión. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-031 | El sistema debe dar de baja lógicamente un camión, conservando su registro histórico. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-032 | El sistema debe registrar el estado del camión. | Compromiso de la ERS original | 2.º avance | Vigente |

#### RF: Gestión de centros de acopio

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-080 | El sistema debe permitir registrar centros de acopio. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-081 | El sistema debe permitir consultar centros de acopio. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-082 | El sistema debe permitir modificar datos de centros de acopio. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-083 | El sistema debe permitir dar de baja centros de acopio. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-088 | El sistema debe registrar la capacidad de los centros de acopio. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-089 | El sistema debe registrar el tipo de residuos que gestiona cada centro de acopio. | Exigido 2.º avance | 2.º avance | Pendiente de definición o evidencia de aceptación |

#### RF: Inventario básico

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-101 | El sistema debe permitir consultar inventario de camiones recolectores. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-102 | El sistema debe permitir consultar inventario de contenedores de repuesto. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-103 | El sistema debe permitir actualizar el stock de contenedores de repuesto. | Compromiso de la ERS original | 2.º avance | Pendiente de definición o evidencia de aceptación |

#### RF: Tipos de residuo

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-147 | El sistema debe permitir registrar tipos de residuos. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-148 | El sistema debe permitir asociar tipos de residuos a contenedores. | Compromiso de la ERS original | 2.º avance | Vigente |

#### RF: Página pública (landing page)

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-158 | El sistema debe mostrar una landing page pública. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-159 | La landing page debe brindar información general sobre el sistema. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-160 | La landing page debe permitir enviar solicitudes de registro de usuario. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-161 | La landing page debe enviar las solicitudes de registro a la API correspondiente. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-162 | La landing page no debe permitir acceso directo a datos internos del sistema. | Compromiso de la ERS original | 2.º avance | Vigente |

#### RF: Frontend de usuarios

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-163 | El frontend debe permitir el registro de usuarios. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-164 | El frontend debe permitir el inicio de sesión. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-165 | El frontend debe permitir visualizar perfiles. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-166 | El frontend debe adaptar la interfaz según el rol del usuario. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-167 | El frontend debe permitir acceder a listados de usuarios, contenedores y camiones. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-168 | El frontend debe permitir completar formularios de usuarios, contenedores y camiones. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-169 | El frontend debe permitir realizar CRUD de usuarios. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-170 | El frontend debe permitir realizar CRUD de contenedores. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-171 | El frontend debe permitir realizar CRUD de camiones. | Compromiso de la ERS original | 2.º avance | Vigente |

#### RF: API de usuarios

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-176 | La API de usuarios debe permitir registrar usuarios. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-177 | La API de usuarios debe permitir autenticar usuarios. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-178 | La API de usuarios debe gestionar inicio de sesión. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-179 | La API de usuarios debe gestionar perfiles de usuario. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-180 | La API de usuarios debe administrar roles. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-181 | La API de usuarios debe recibir solicitudes de registro para aprobación posterior. | Propuesto por el equipo | 3.º avance / validar | Vigente |
| RF-182 | La API de usuarios debe devolver datos de usuarios al frontend según permisos. | Compromiso de la ERS original | 2.º avance | Vigente |

#### RF: API de gestión

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-183 | La API de gestión debe permitir registrar contenedores. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-184 | La API de gestión debe permitir modificar contenedores. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-185 | La API de gestión debe permitir dar de baja contenedores. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-186 | La API de gestión debe permitir consultar contenedores. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-187 | La API de gestión debe administrar centros de acopio. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-189 | La API de gestión debe gestionar capacidad de centros de acopio. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-190 | La API de gestión debe gestionar tipos de residuos. | Compromiso de la ERS original | 2.º avance | Vigente |
| RF-191 | La API de gestión debe administrar stock de repuestos. | Compromiso de la ERS original | 2.º avance | Pendiente de definición o evidencia de aceptación |
| RF-192 | La API de gestión debe permitir consultar inventario de contenedores de repuesto. | Compromiso de la ERS original | 2.º avance | Vigente |

#### RF: Parametrización del sistema

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-210 | El sistema debe permitir parametrizar datos generales. | Compromiso de la ERS original | 3.º avance / validar | Pendiente de definición o evidencia de aceptación |
| RF-211 | El sistema debe permitir administrar tipos de residuos. | Compromiso de la ERS original | 3.º avance / validar | Vigente |
| RF-216 | El sistema debe permitir cambiar el idioma del software. | Compromiso de la ERS original | 3.º avance / validar | Pendiente de definición o evidencia de aceptación |

#### RF: Incidencias

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-217 | El sistema debe permitir a un vecino registrado informar una incidencia sobre un contenedor, indicando el tipo de problema y una descripción. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-218 | El sistema debe permitir identificar el contenedor afectado por su código; cuando esté disponible el mapa interactivo, también debe permitir seleccionarlo allí. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-219 | El sistema debe registrar toda incidencia nueva con estado «abierta», la fecha y hora del reporte y el usuario que la informó. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-220 | El sistema debe informar al usuario el número de incidencia generado al confirmar el registro. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-221 | El sistema debe rechazar el registro de una incidencia sobre un contenedor que no esté dado de alta. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-222 | El sistema debe permitir adjuntar una imagen a la incidencia en el momento de registrarla. | Propuesto por el equipo | 3.º avance | Vigente |
| RF-223 | El sistema debe validar el formato y el tamaño de la imagen adjunta antes de asociarla a la incidencia. | Propuesto por el equipo | 3.º avance | Vigente |
| RF-224 | El sistema debe permitir al administrador municipal consultar el listado de incidencias abiertas con su tipo, contenedor, zona y fecha de reporte. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-225 | El sistema debe permitir asignar una prioridad a cada incidencia. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-226 | El sistema debe permitir asignar la incidencia a una cuadrilla activa. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-227 | El sistema debe cambiar el estado de la incidencia a «en curso» al registrarse la asignación. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-228 | El sistema debe ofrecer para la asignación únicamente las cuadrillas y los camiones que estén disponibles. | Propuesto por el equipo | 3.º avance | Pendiente de definición o evidencia de aceptación |
| RF-229 | El sistema debe permitir a una cuadrilla consultar únicamente las incidencias que tiene asignadas. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-230 | El sistema debe permitir a la cuadrilla registrar el avance de una incidencia asignada, con una observación. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-231 | El sistema debe permitir dar una incidencia por resuelta y registrar su fecha de resolución. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-232 | El sistema debe conservar el historial de cambios de estado de cada incidencia, con fecha, usuario y observación. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-233 | El sistema debe registrar un asiento en el historial del contenedor cada vez que su estado cambie, con la fecha y hora, el estado y una observación. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-234 | El sistema debe actualizar el estado vigente del contenedor al registrarse un nuevo asiento en su historial. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-235 | El sistema no debe sobrescribir ni eliminar los asientos anteriores del historial de un contenedor. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-236 | El sistema debe permitir consultar el historial completo de estados de un contenedor desde su ficha. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-237 | El sistema debe mostrar el listado de incidencias con contenedor, zona, tipo, estado, prioridad y fecha. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-238 | El sistema debe limitar el alcance de la consulta según el rol del usuario: el administrador ve todas las incidencias y la cuadrilla solo las asignadas a ella. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-239 | El sistema debe permitir filtrar el listado de incidencias por estado, zona y período. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-240 | El sistema debe permitir abrir el detalle de una incidencia con su responsable y su historial de cambios. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-241 | El sistema debe permitir a un vecino consultar el estado de las incidencias que él mismo informó. | Compromiso del mínimo general | 3.º avance | Vigente |
| RF-242 | El sistema debe generar un informe de incidencias abiertas y cerradas para un período seleccionado. | Exigido 3.º avance | 3.º avance | Vigente |
| RF-243 | El informe debe mostrar el total de incidencias abiertas y cerradas y el detalle de las incidencias incluidas. | Exigido 3.º avance | 3.º avance | Vigente |
| RF-244 | El sistema debe calcular el tiempo de resolución de las incidencias cerradas dentro del período consultado. | Propuesto por el equipo | 3.º avance | Vigente |
| RF-245 | El sistema debe permitir exportar el reporte generado. | Propuesto por el equipo | 3.º avance | Pendiente de definición o evidencia de aceptación |

#### RF: Maquinaria

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-246 | El sistema debe permitir al administrador municipal registrar una máquina indicando su código patrimonial, su tipo, su marca, su modelo y el centro de acopio en el que se encuentra. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-247 | El sistema debe rechazar el alta de una máquina cuyo código ya pertenezca a otra, e informar la duplicación. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-248 | El sistema debe exigir que toda máquina esté asociada a un centro de acopio existente, y rechazar el alta que no lo indique. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-249 | El sistema debe admitir únicamente los tipos de maquinaria previstos —compactadora, pala cargadora, cinta de clasificación, retroexcavadora, montacargas y otra— y rechazar cualquier otro valor. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-250 | El sistema debe registrar toda máquina nueva con estado «operativa» si no se indica otro de los estados admitidos. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-251 | El sistema debe permitir modificar el código, el tipo, la marca, el modelo, el estado y el centro de acopio de una máquina ya registrada. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-252 | El sistema debe permitir dar de baja una máquina, dejándola con estado «fuera de servicio» sin eliminarla del inventario. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-253 | El sistema debe impedir dar de baja una máquina que ya se encuentre fuera de servicio, e informar la situación. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-254 | El sistema debe advertir, al dar de baja un centro de acopio, cuánta maquinaria queda asociada a él, para que pueda reasignarse. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-255 | El sistema debe permitir consultar el inventario de maquinaria mostrando, para cada máquina, su código, su tipo, su marca y modelo, el nombre del centro de acopio y su estado. | Exigido 2.º avance | 2.º avance | Vigente |
| RF-256 | El sistema debe permitir buscar y ordenar el inventario por cualquiera de los datos mostrados. | Exigido 2.º avance | 2.º avance | Pendiente de definición o evidencia de aceptación |

#### RF: Recolección

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-257 | La API de recolección debe devolver a cada cuadrilla autenticada las rutas y tareas que le fueron asignadas, con fecha, camión y contenedores previstos. | Exigido 3.º avance | 3.º avance | Propuesta de detalle para validar con el equipo y la docente |
| RF-258 | La API de recolección debe permitir a la cuadrilla registrar la finalización de una tarea asignada con fecha, hora y observación, sin alterar tareas de otra cuadrilla. | Exigido 3.º avance | 3.º avance | Propuesta de detalle para validar con el equipo y la docente |

#### RF: Mapa

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-259 | El mapa interactivo debe mostrar los contenedores activos con sus coordenadas y estado, y permitir abrir su ficha. | Exigido 3.º avance | 3.º avance | Propuesta de detalle para validar con el equipo y la docente |
| RF-260 | El mapa interactivo debe mostrar las incidencias abiertas asociadas a un contenedor y permitir abrir su detalle según los permisos del usuario. | Exigido 3.º avance | 3.º avance | Propuesta de detalle para validar con el equipo y la docente |

#### RF: Panel

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-261 | El panel de control debe mostrar los totales de incidencias abiertas y cerradas para un período seleccionado. | Exigido 3.º avance | 3.º avance | Propuesta de detalle para validar con el equipo y la docente |
| RF-262 | El panel de control debe mostrar los contenedores con más incidencias de desborde dentro del período seleccionado, ordenados por cantidad. | Exigido 3.º avance | 3.º avance | Propuesta de detalle para validar con el equipo y la docente |

#### RF: Alertas

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RF-263 | El panel debe mostrar al administrador una alerta visual por cada incidencia abierta con prioridad alta; al cerrarse la incidencia, la alerta debe desaparecer del listado calculado. | Exigido 3.º avance | 3.º avance | Propuesta de detalle para validar con el equipo y la docente |

### 3.3 Requisitos no funcionales

#### RNF: Seguridad

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RNF-001 | El sistema debe almacenar cada contraseña únicamente como un hash resistente a ataques de fuerza bruta generado por password_hash de PHP, verificarla con password_verify y no registrar la contraseña en texto plano. | Compromiso de la ERS original | Transversal | Vigente |
| RNF-002 | El sistema debe restringir las operaciones disponibles según el rol del usuario autenticado, tanto en el frontend como en el backend. | Compromiso de la ERS original | Transversal | Vigente |
| RNF-003 | El sistema debe transmitir credenciales y datos personales entre navegador y APIs mediante HTTPS/TLS; las solicitudes HTTP deben redirigirse a HTTPS o rechazarse en el entorno de despliegue. | Compromiso de la ERS original | Transversal | Vigente |
| RNF-004 | El tratamiento de datos personales de usuarios y vecinos debe cumplir con la Ley N.º 18.331 de Protección de Datos Personales. | Compromiso de la ERS original | Transversal | Pendiente de definición o evidencia de aceptación |

#### RNF: Usabilidad

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RNF-005 | La interfaz debe ser utilizable por personal municipal sin conocimientos técnicos, con formularios simples y mensajes de error claros. | Compromiso de la ERS original | Transversal | Pendiente de definición o evidencia de aceptación |
| RNF-006 | El idioma de la interfaz debe ser parametrizable, sin textos fijos incrustados en el código. | Compromiso de la ERS original | Transversal | Pendiente de definición o evidencia de aceptación |

#### RNF: Rendimiento y disponibilidad

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RNF-007 | En el entorno de referencia y con la carga de prueba definidos para la aceptación, el percentil 95 de inicio de sesión, consulta de 100 registros y alta de un registro debe ser de 3 segundos o menos. | Criterio propuesto | Transversal | Criterio provisional: entorno, carga o ventana pendientes de acordar |
| RNF-008 | Durante la ventana de servicio semanal acordada con el organismo piloto, la disponibilidad mensual del servicio debe ser de al menos 99 %, medida con una sonda externa cada cinco minutos. | Criterio propuesto | Transversal | Criterio provisional: entorno, carga o ventana pendientes de acordar |

#### RNF: Compatibilidad y portabilidad

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RNF-009 | El frontend debe funcionar correctamente en los navegadores más usados (Chrome, Firefox, Edge) en sus versiones recientes. | Compromiso de la ERS original | Transversal | Vigente |
| RNF-010 | El sistema debe poder desplegarse mediante contenedores Docker sobre un servidor Linux. | Compromiso de la ERS original | Transversal | Vigente |

#### RNF: Arquitectura y mantenibilidad

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RNF-011 | El sistema debe implementarse como microservicios independientes, cada uno con su propia base de código y contenedor Docker. | Compromiso de la ERS original | Transversal | Vigente |
| RNF-012 | Cada aplicación debe implementar la arquitectura MVC. | Compromiso de la ERS original | Transversal | Vigente |
| RNF-013 | El código fuente de cada aplicación y sus cambios deben conservarse en el repositorio Git de la organización, con commits identificables por fecha, autor y descripción. | Compromiso de la ERS original | Transversal | Vigente |

#### RNF: Calidad y documentación

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RNF-014 | El sistema debe contemplar pruebas automatizadas, al menos unitarias, sobre la lógica de las APIs. | Compromiso de la ERS original | Transversal | Vigente |
| RNF-015 | En cada entrega deben publicarse versiones del manual de usuario, la guía de instalación y la guía de administración acordes con las funciones efectivamente disponibles. | Compromiso de la ERS original | Transversal | Vigente |

#### RNF: Integridad y trazabilidad

| Código | Requisito v2.1 | Prioridad | Horizonte | Estado |
|---|---|---|---|---|
| RNF-016 | El historial de estados —tanto el de una incidencia como el de un contenedor— debe ser de solo agregado: ningún asiento puede modificarse ni eliminarse una vez registrado. Es la condición para que el sistema sirva como evidencia de qué se hizo y cuándo. | Compromiso de la ampliación 03/09 | Transversal | Vigente |
| RNF-017 | Toda operación que cambie el estado de una incidencia debe quedar asociada al usuario que la realizó y a su fecha y hora. | Compromiso de la ampliación 03/09 | Transversal | Vigente |

## 4. Matriz de trazabilidad requisito → fuente → caso de uso → prueba prevista

La prueba indicada es un identificador de planificación. Su criterio de aceptación es el texto de la fila de requisito; los guiones detallados y su ejecución quedan para el trabajo de pruebas. Los enlaces a CU reflejan el catálogo revisado en el paso 5.

| Requisito | Fuente | CU vigente | Prueba prevista | Objetivo de prueba |
|---|---|---|---|---|
| RF-001 | E1 | CU-003, CU-006 | PA-RF-001 | Ejecutar CU-003, CU-006; comprobar la condición y el resultado descritos en RF-001, incluyendo el caso de rechazo si corresponde. |
| RF-002 | E1 | CU-001 | PA-RF-002 | Ejecutar CU-001; comprobar la condición y el resultado descritos en RF-002, incluyendo el caso de rechazo si corresponde. |
| RF-003 | E1 | CU-002 | PA-RF-003 | Ejecutar CU-002; comprobar la condición y el resultado descritos en RF-003, incluyendo el caso de rechazo si corresponde. |
| RF-004 | E1 | — | PA-RF-004 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-004, incluyendo el caso de rechazo si corresponde. |
| RF-005 | E1 | CU-007 | PA-RF-005 | Ejecutar CU-007; comprobar la condición y el resultado descritos en RF-005, incluyendo el caso de rechazo si corresponde. |
| RF-006 | E1 | CU-006, CU-007, CU-008 | PA-RF-006 | Ejecutar CU-006, CU-007, CU-008; comprobar la condición y el resultado descritos en RF-006, incluyendo el caso de rechazo si corresponde. |
| RF-007 | E1 | CU-011 | PA-RF-007 | Ejecutar CU-011; comprobar la condición y el resultado descritos en RF-007, incluyendo el caso de rechazo si corresponde. |
| RF-008 | E1 | CU-011 | PA-RF-008 | Ejecutar CU-011; comprobar la condición y el resultado descritos en RF-008, incluyendo el caso de rechazo si corresponde. |
| RF-009 | E1 | — | PA-RF-009 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-009, incluyendo el caso de rechazo si corresponde. |
| RF-010 | E1 | CU-005 | PA-RF-010 | Ejecutar CU-005; comprobar la condición y el resultado descritos en RF-010, incluyendo el caso de rechazo si corresponde. |
| RF-011 | E1 | CU-009 | PA-RF-011 | Ejecutar CU-009; comprobar la condición y el resultado descritos en RF-011, incluyendo el caso de rechazo si corresponde. |
| RF-012 | E1 | CU-010 | PA-RF-012 | Ejecutar CU-010; comprobar la condición y el resultado descritos en RF-012, incluyendo el caso de rechazo si corresponde. |
| RF-013 | E1 | CU-012 | PA-RF-013 | Ejecutar CU-012; comprobar la condición y el resultado descritos en RF-013, incluyendo el caso de rechazo si corresponde. |
| RF-014 | E1 | CU-015 | PA-RF-014 | Ejecutar CU-015; comprobar la condición y el resultado descritos en RF-014, incluyendo el caso de rechazo si corresponde. |
| RF-015 | E1 | CU-013 | PA-RF-015 | Ejecutar CU-013; comprobar la condición y el resultado descritos en RF-015, incluyendo el caso de rechazo si corresponde. |
| RF-016 | E1 | CU-014 | PA-RF-016 | Ejecutar CU-014; comprobar la condición y el resultado descritos en RF-016, incluyendo el caso de rechazo si corresponde. |
| RF-017 | E1 | CU-012 | PA-RF-017 | Ejecutar CU-012; comprobar la condición y el resultado descritos en RF-017, incluyendo el caso de rechazo si corresponde. |
| RF-018 | E1 | CU-012, CU-013, CU-014 | PA-RF-018 | Ejecutar CU-012, CU-013, CU-014; comprobar la condición y el resultado descritos en RF-018, incluyendo el caso de rechazo si corresponde. |
| RF-019 | E1 | CU-012 | PA-RF-019 | Ejecutar CU-012; comprobar la condición y el resultado descritos en RF-019, incluyendo el caso de rechazo si corresponde. |
| RF-026 | E1 | — | PA-RF-026 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-026, incluyendo el caso de rechazo si corresponde. |
| RF-027 | E1 | — | PA-RF-027 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-027, incluyendo el caso de rechazo si corresponde. |
| RF-028 | E1 | CU-016 | PA-RF-028 | Ejecutar CU-016; comprobar la condición y el resultado descritos en RF-028, incluyendo el caso de rechazo si corresponde. |
| RF-029 | E1 | CU-019 | PA-RF-029 | Ejecutar CU-019; comprobar la condición y el resultado descritos en RF-029, incluyendo el caso de rechazo si corresponde. |
| RF-030 | E1 | CU-017 | PA-RF-030 | Ejecutar CU-017; comprobar la condición y el resultado descritos en RF-030, incluyendo el caso de rechazo si corresponde. |
| RF-031 | E1 | CU-018 | PA-RF-031 | Ejecutar CU-018; comprobar la condición y el resultado descritos en RF-031, incluyendo el caso de rechazo si corresponde. |
| RF-032 | E1 | CU-016 | PA-RF-032 | Ejecutar CU-016; comprobar la condición y el resultado descritos en RF-032, incluyendo el caso de rechazo si corresponde. |
| RF-080 | E1+C | CU-020 | PA-RF-080 | Ejecutar CU-020; comprobar la condición y el resultado descritos en RF-080, incluyendo el caso de rechazo si corresponde. |
| RF-081 | E1+C | CU-020 | PA-RF-081 | Ejecutar CU-020; comprobar la condición y el resultado descritos en RF-081, incluyendo el caso de rechazo si corresponde. |
| RF-082 | E1+C | CU-020 | PA-RF-082 | Ejecutar CU-020; comprobar la condición y el resultado descritos en RF-082, incluyendo el caso de rechazo si corresponde. |
| RF-083 | E1+C | CU-020 | PA-RF-083 | Ejecutar CU-020; comprobar la condición y el resultado descritos en RF-083, incluyendo el caso de rechazo si corresponde. |
| RF-088 | E1+C | CU-021 | PA-RF-088 | Ejecutar CU-021; comprobar la condición y el resultado descritos en RF-088, incluyendo el caso de rechazo si corresponde. |
| RF-089 | E1+C | — | PA-RF-089 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-089, incluyendo el caso de rechazo si corresponde. |
| RF-101 | E1 | CU-019, CU-029 | PA-RF-101 | Ejecutar CU-019, CU-029; comprobar la condición y el resultado descritos en RF-101, incluyendo el caso de rechazo si corresponde. |
| RF-102 | E1 | CU-029 | PA-RF-102 | Ejecutar CU-029; comprobar la condición y el resultado descritos en RF-102, incluyendo el caso de rechazo si corresponde. |
| RF-103 | E1 | — | PA-RF-103 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-103, incluyendo el caso de rechazo si corresponde. |
| RF-147 | E1 | CU-022 | PA-RF-147 | Ejecutar CU-022; comprobar la condición y el resultado descritos en RF-147, incluyendo el caso de rechazo si corresponde. |
| RF-148 | E1 | CU-022 | PA-RF-148 | Ejecutar CU-022; comprobar la condición y el resultado descritos en RF-148, incluyendo el caso de rechazo si corresponde. |
| RF-158 | E1 | — | PA-RF-158 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-158, incluyendo el caso de rechazo si corresponde. |
| RF-159 | E1 | — | PA-RF-159 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-159, incluyendo el caso de rechazo si corresponde. |
| RF-160 | E1 | CU-003 | PA-RF-160 | Ejecutar CU-003; comprobar la condición y el resultado descritos en RF-160, incluyendo el caso de rechazo si corresponde. |
| RF-161 | E1 | CU-003 | PA-RF-161 | Ejecutar CU-003; comprobar la condición y el resultado descritos en RF-161, incluyendo el caso de rechazo si corresponde. |
| RF-162 | E1 | — | PA-RF-162 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-162, incluyendo el caso de rechazo si corresponde. |
| RF-163 | E1 | CU-006 | PA-RF-163 | Ejecutar CU-006; comprobar la condición y el resultado descritos en RF-163, incluyendo el caso de rechazo si corresponde. |
| RF-164 | E1 | CU-001 | PA-RF-164 | Ejecutar CU-001; comprobar la condición y el resultado descritos en RF-164, incluyendo el caso de rechazo si corresponde. |
| RF-165 | E1 | — | PA-RF-165 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-165, incluyendo el caso de rechazo si corresponde. |
| RF-166 | E1 | — | PA-RF-166 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-166, incluyendo el caso de rechazo si corresponde. |
| RF-167 | E1 | CU-009, CU-015, CU-019 | PA-RF-167 | Ejecutar CU-009, CU-015, CU-019; comprobar la condición y el resultado descritos en RF-167, incluyendo el caso de rechazo si corresponde. |
| RF-168 | E1 | — | PA-RF-168 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-168, incluyendo el caso de rechazo si corresponde. |
| RF-169 | E1 | CU-006, CU-007, CU-008 | PA-RF-169 | Ejecutar CU-006, CU-007, CU-008; comprobar la condición y el resultado descritos en RF-169, incluyendo el caso de rechazo si corresponde. |
| RF-170 | E1 | CU-012, CU-013, CU-014 | PA-RF-170 | Ejecutar CU-012, CU-013, CU-014; comprobar la condición y el resultado descritos en RF-170, incluyendo el caso de rechazo si corresponde. |
| RF-171 | E1 | CU-016, CU-017, CU-018 | PA-RF-171 | Ejecutar CU-016, CU-017, CU-018; comprobar la condición y el resultado descritos en RF-171, incluyendo el caso de rechazo si corresponde. |
| RF-176 | E1 | CU-006 | PA-RF-176 | Ejecutar CU-006; comprobar la condición y el resultado descritos en RF-176, incluyendo el caso de rechazo si corresponde. |
| RF-177 | E1 | CU-001 | PA-RF-177 | Ejecutar CU-001; comprobar la condición y el resultado descritos en RF-177, incluyendo el caso de rechazo si corresponde. |
| RF-178 | E1 | CU-001 | PA-RF-178 | Ejecutar CU-001; comprobar la condición y el resultado descritos en RF-178, incluyendo el caso de rechazo si corresponde. |
| RF-179 | E1 | CU-007 | PA-RF-179 | Ejecutar CU-007; comprobar la condición y el resultado descritos en RF-179, incluyendo el caso de rechazo si corresponde. |
| RF-180 | E1 | CU-011 | PA-RF-180 | Ejecutar CU-011; comprobar la condición y el resultado descritos en RF-180, incluyendo el caso de rechazo si corresponde. |
| RF-181 | E1 | CU-003, CU-005 | PA-RF-181 | Ejecutar CU-003, CU-005; comprobar la condición y el resultado descritos en RF-181, incluyendo el caso de rechazo si corresponde. |
| RF-182 | E1 | CU-009 | PA-RF-182 | Ejecutar CU-009; comprobar la condición y el resultado descritos en RF-182, incluyendo el caso de rechazo si corresponde. |
| RF-183 | E1 | CU-012 | PA-RF-183 | Ejecutar CU-012; comprobar la condición y el resultado descritos en RF-183, incluyendo el caso de rechazo si corresponde. |
| RF-184 | E1 | CU-013 | PA-RF-184 | Ejecutar CU-013; comprobar la condición y el resultado descritos en RF-184, incluyendo el caso de rechazo si corresponde. |
| RF-185 | E1 | CU-014 | PA-RF-185 | Ejecutar CU-014; comprobar la condición y el resultado descritos en RF-185, incluyendo el caso de rechazo si corresponde. |
| RF-186 | E1 | CU-015 | PA-RF-186 | Ejecutar CU-015; comprobar la condición y el resultado descritos en RF-186, incluyendo el caso de rechazo si corresponde. |
| RF-187 | E1+C | CU-020 | PA-RF-187 | Ejecutar CU-020; comprobar la condición y el resultado descritos en RF-187, incluyendo el caso de rechazo si corresponde. |
| RF-189 | E1+C | CU-021 | PA-RF-189 | Ejecutar CU-021; comprobar la condición y el resultado descritos en RF-189, incluyendo el caso de rechazo si corresponde. |
| RF-190 | E1 | CU-022 | PA-RF-190 | Ejecutar CU-022; comprobar la condición y el resultado descritos en RF-190, incluyendo el caso de rechazo si corresponde. |
| RF-191 | E1 | — | PA-RF-191 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-191, incluyendo el caso de rechazo si corresponde. |
| RF-192 | E1 | CU-029 | PA-RF-192 | Ejecutar CU-029; comprobar la condición y el resultado descritos en RF-192, incluyendo el caso de rechazo si corresponde. |
| RF-210 | E1 | — | PA-RF-210 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-210, incluyendo el caso de rechazo si corresponde. |
| RF-211 | E1 | CU-022 | PA-RF-211 | Ejecutar CU-022; comprobar la condición y el resultado descritos en RF-211, incluyendo el caso de rechazo si corresponde. |
| RF-216 | E1 | — | PA-RF-216 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-216, incluyendo el caso de rechazo si corresponde. |
| RF-217 | E2+C | CU-023 | PA-RF-217 | Ejecutar CU-023; comprobar la condición y el resultado descritos en RF-217, incluyendo el caso de rechazo si corresponde. |
| RF-218 | E2+C | CU-023 | PA-RF-218 | Ejecutar CU-023; comprobar la condición y el resultado descritos en RF-218, incluyendo el caso de rechazo si corresponde. |
| RF-219 | E2+C | CU-023 | PA-RF-219 | Ejecutar CU-023; comprobar la condición y el resultado descritos en RF-219, incluyendo el caso de rechazo si corresponde. |
| RF-220 | E2+C | CU-023 | PA-RF-220 | Ejecutar CU-023; comprobar la condición y el resultado descritos en RF-220, incluyendo el caso de rechazo si corresponde. |
| RF-221 | E2+C | CU-023 | PA-RF-221 | Ejecutar CU-023; comprobar la condición y el resultado descritos en RF-221, incluyendo el caso de rechazo si corresponde. |
| RF-222 | E2+C | CU-024 | PA-RF-222 | Ejecutar CU-024; comprobar la condición y el resultado descritos en RF-222, incluyendo el caso de rechazo si corresponde. |
| RF-223 | E2+C | CU-024 | PA-RF-223 | Ejecutar CU-024; comprobar la condición y el resultado descritos en RF-223, incluyendo el caso de rechazo si corresponde. |
| RF-224 | E2+C | CU-025 | PA-RF-224 | Ejecutar CU-025; comprobar la condición y el resultado descritos en RF-224, incluyendo el caso de rechazo si corresponde. |
| RF-225 | E2+C | CU-025 | PA-RF-225 | Ejecutar CU-025; comprobar la condición y el resultado descritos en RF-225, incluyendo el caso de rechazo si corresponde. |
| RF-226 | E2+C | CU-025 | PA-RF-226 | Ejecutar CU-025; comprobar la condición y el resultado descritos en RF-226, incluyendo el caso de rechazo si corresponde. |
| RF-227 | E2+C | CU-025 | PA-RF-227 | Ejecutar CU-025; comprobar la condición y el resultado descritos en RF-227, incluyendo el caso de rechazo si corresponde. |
| RF-228 | E2+C | CU-025 | PA-RF-228 | Ejecutar CU-025; comprobar la condición y el resultado descritos en RF-228, incluyendo el caso de rechazo si corresponde. |
| RF-229 | E2+C | CU-026, CU-028 | PA-RF-229 | Ejecutar CU-026, CU-028; comprobar la condición y el resultado descritos en RF-229, incluyendo el caso de rechazo si corresponde. |
| RF-230 | E2+C | CU-026 | PA-RF-230 | Ejecutar CU-026; comprobar la condición y el resultado descritos en RF-230, incluyendo el caso de rechazo si corresponde. |
| RF-231 | E2+C | CU-026 | PA-RF-231 | Ejecutar CU-026; comprobar la condición y el resultado descritos en RF-231, incluyendo el caso de rechazo si corresponde. |
| RF-232 | E2+C | CU-026 | PA-RF-232 | Ejecutar CU-026; comprobar la condición y el resultado descritos en RF-232, incluyendo el caso de rechazo si corresponde. |
| RF-233 | E2+C | CU-013, CU-014, CU-023, CU-026 | PA-RF-233 | Ejecutar CU-013, CU-014, CU-023, CU-026; comprobar la condición y el resultado descritos en RF-233, incluyendo el caso de rechazo si corresponde. |
| RF-234 | E2+C | CU-013, CU-014, CU-023, CU-026 | PA-RF-234 | Ejecutar CU-013, CU-014, CU-023, CU-026; comprobar la condición y el resultado descritos en RF-234, incluyendo el caso de rechazo si corresponde. |
| RF-235 | E2+C | CU-013, CU-014, CU-023, CU-026 | PA-RF-235 | Ejecutar CU-013, CU-014, CU-023, CU-026; comprobar la condición y el resultado descritos en RF-235, incluyendo el caso de rechazo si corresponde. |
| RF-236 | E2+C | CU-015 | PA-RF-236 | Ejecutar CU-015; comprobar la condición y el resultado descritos en RF-236, incluyendo el caso de rechazo si corresponde. |
| RF-237 | E2+C | CU-028 | PA-RF-237 | Ejecutar CU-028; comprobar la condición y el resultado descritos en RF-237, incluyendo el caso de rechazo si corresponde. |
| RF-238 | E2+C | CU-028 | PA-RF-238 | Ejecutar CU-028; comprobar la condición y el resultado descritos en RF-238, incluyendo el caso de rechazo si corresponde. |
| RF-239 | E2+C | CU-028 | PA-RF-239 | Ejecutar CU-028; comprobar la condición y el resultado descritos en RF-239, incluyendo el caso de rechazo si corresponde. |
| RF-240 | E2+C | CU-028 | PA-RF-240 | Ejecutar CU-028; comprobar la condición y el resultado descritos en RF-240, incluyendo el caso de rechazo si corresponde. |
| RF-241 | E2+C | CU-028 | PA-RF-241 | Ejecutar CU-028; comprobar la condición y el resultado descritos en RF-241, incluyendo el caso de rechazo si corresponde. |
| RF-242 | E2+C | CU-030 | PA-RF-242 | Ejecutar CU-030; comprobar la condición y el resultado descritos en RF-242, incluyendo el caso de rechazo si corresponde. |
| RF-243 | E2+C | CU-030 | PA-RF-243 | Ejecutar CU-030; comprobar la condición y el resultado descritos en RF-243, incluyendo el caso de rechazo si corresponde. |
| RF-244 | E2+C | CU-030 | PA-RF-244 | Ejecutar CU-030; comprobar la condición y el resultado descritos en RF-244, incluyendo el caso de rechazo si corresponde. |
| RF-245 | E2+C | CU-030 | PA-RF-245 | Ejecutar CU-030; comprobar la condición y el resultado descritos en RF-245, incluyendo el caso de rechazo si corresponde. |
| RF-246 | E3+C | CU-031 | PA-RF-246 | Ejecutar CU-031; comprobar la condición y el resultado descritos en RF-246, incluyendo el caso de rechazo si corresponde. |
| RF-247 | E3+C | CU-031 | PA-RF-247 | Ejecutar CU-031; comprobar la condición y el resultado descritos en RF-247, incluyendo el caso de rechazo si corresponde. |
| RF-248 | E3+C | CU-031 | PA-RF-248 | Ejecutar CU-031; comprobar la condición y el resultado descritos en RF-248, incluyendo el caso de rechazo si corresponde. |
| RF-249 | E3+C | CU-031 | PA-RF-249 | Ejecutar CU-031; comprobar la condición y el resultado descritos en RF-249, incluyendo el caso de rechazo si corresponde. |
| RF-250 | E3+C | CU-031 | PA-RF-250 | Ejecutar CU-031; comprobar la condición y el resultado descritos en RF-250, incluyendo el caso de rechazo si corresponde. |
| RF-251 | E3+C | CU-031 | PA-RF-251 | Ejecutar CU-031; comprobar la condición y el resultado descritos en RF-251, incluyendo el caso de rechazo si corresponde. |
| RF-252 | E3+C | CU-031 | PA-RF-252 | Ejecutar CU-031; comprobar la condición y el resultado descritos en RF-252, incluyendo el caso de rechazo si corresponde. |
| RF-253 | E3+C | CU-031 | PA-RF-253 | Ejecutar CU-031; comprobar la condición y el resultado descritos en RF-253, incluyendo el caso de rechazo si corresponde. |
| RF-254 | E3+C | CU-020 | PA-RF-254 | Ejecutar CU-020; comprobar la condición y el resultado descritos en RF-254, incluyendo el caso de rechazo si corresponde. |
| RF-255 | E3+C | CU-032 | PA-RF-255 | Ejecutar CU-032; comprobar la condición y el resultado descritos en RF-255, incluyendo el caso de rechazo si corresponde. |
| RF-256 | E3+C | CU-032 | PA-RF-256 | Ejecutar CU-032; comprobar la condición y el resultado descritos en RF-256, incluyendo el caso de rechazo si corresponde. |
| RF-257 | C+P | — | PA-RF-257 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-257, incluyendo el caso de rechazo si corresponde. |
| RF-258 | C+P | — | PA-RF-258 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-258, incluyendo el caso de rechazo si corresponde. |
| RF-259 | C+P | — | PA-RF-259 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-259, incluyendo el caso de rechazo si corresponde. |
| RF-260 | C+P | — | PA-RF-260 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-260, incluyendo el caso de rechazo si corresponde. |
| RF-261 | C+P | — | PA-RF-261 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-261, incluyendo el caso de rechazo si corresponde. |
| RF-262 | C+P | — | PA-RF-262 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-262, incluyendo el caso de rechazo si corresponde. |
| RF-263 | C+P | — | PA-RF-263 | Ejecutar flujo por definir; comprobar la condición y el resultado descritos en RF-263, incluyendo el caso de rechazo si corresponde. |
| RNF-001 | E1 | CU-006 | PN-RNF-001 | Registrar usuario, inspeccionar password_hash y autenticar con password_verify; comprobar que no persiste ni se registra la clave. |
| RNF-002 | E1 | CU-004 | PN-RNF-002 | Ejecutar la matriz de permisos por rol tanto contra la API como desde la interfaz; verificar 403 y ausencia de controles prohibidos. |
| RNF-003 | E1 | — | PN-RNF-003 | Probar el despliegue por HTTP/HTTPS y capturar el tráfico de login; comprobar TLS y rechazo/redirección de HTTP. |
| RNF-004 | E1 | — | PN-RNF-004 | Revisión jurídica de datos recolectados, base de tratamiento, acceso, retención y derechos; registrar dictamen. Pendiente. |
| RNF-005 | E1 | — | PN-RNF-005 | Observar a cuatro usuarios de prueba en altas, consulta e incidencia; medir finalización y errores. Criterio final pendiente. |
| RNF-006 | E1 | — | PN-RNF-006 | Cambiar idioma y recorrer todas las pantallas y mensajes; verificar ausencia de textos fijos. |
| RNF-007 | E1 | — | PN-RNF-007 | Medir p95 de login, listado de 100 filas y alta con carga y equipo de referencia acordados; comparar con 3 s. |
| RNF-008 | E1 | — | PN-RNF-008 | Sondear cada cinco minutos durante un mes la ventana de servicio acordada; calcular disponibilidad. |
| RNF-009 | E1 | — | PN-RNF-009 | Ejecutar las funciones principales en Chrome, Firefox y Edge de versiones fijadas en el acta de aceptación. |
| RNF-010 | E1 | — | PN-RNF-010 | Construir y desplegar los contenedores en Linux, migrar base y ejecutar pruebas de humo. |
| RNF-011 | E1 | — | PN-RNF-011 | Revisar separación de aplicaciones y contenedores; simular caída de una API y observar el efecto. |
| RNF-012 | E1 | — | PN-RNF-012 | Revisar estructura y responsabilidades de modelo, vista y controlador por aplicación. |
| RNF-013 | E1 | — | PN-RNF-013 | Inspeccionar repositorio Git, autores, fechas, mensajes y correspondencia con la versión entregada. |
| RNF-014 | E1 | — | PN-RNF-014 | Ejecutar la batería unitaria en CI y adjuntar resultado por API y versión. |
| RNF-015 | E1 | — | PN-RNF-015 | Comparar manuales publicados con funciones y procedimiento de instalación de la versión entregada. |
| RNF-016 | E2 | CU-023, CU-026, CU-027 | PN-RNF-016 | Intentar UPDATE/DELETE de asientos de historial desde API y SQL; comprobar rechazo y conservación. |
| RNF-017 | E2 | CU-023, CU-026, CU-027 | PN-RNF-017 | Cambiar estado de incidencia y verificar usuario y marca temporal en el historial. |

**RF sin CU directo (22):** RF-004, RF-009, RF-026, RF-027, RF-089, RF-103, RF-158, RF-159, RF-162, RF-165, RF-166, RF-168, RF-191, RF-210, RF-216, RF-257, RF-258, RF-259, RF-260, RF-261, RF-262, RF-263. No se inventa una relación para llenar la matriz. El catálogo del paso 5 identifica los objetivos y reglas que requieren definición adicional.

## 5. Hallazgos y decisiones de revisión

- **D-01:** RF-001/163/176, RF-013/170/183 y equivalentes describen una misma capacidad en sistema, interfaz y API. Se conservan los códigos; la prueba debe cubrir una operación integral y sus capas.
- **D-02:** RF-027/102 y RF-103/191 duplican consulta y actualización de repuestos. Se conservan como referencias históricas; falta precisar si el stock se mide por unidades físicas o cantidad agregada.
- **D-03:** RF-080 a 083, RF-088/089, RF-187/189 y RF-246 a 256 figuraban como recomendados. La sección 9.4 exige centros y maquinaria en el 2.º avance; la prioridad vigente es exigida para ese corte.
- **D-04:** RF-242/243 figuraban recomendados. El informe simple está en el mínimo general y en el 3.er avance; sus totales abiertos/cerrados pasan a exigidos. RF-244/245 quedan como mejoras propuestas.
- **D-05:** RF-218 mezclaba identificación por código y mapa. El código permite registrar la incidencia; RF-259/260 cubren el mapa exigido en el 3.er avance.
- **D-06:** RNF-001 confundía cifrado reversible con hash de contraseña. RNF-007/008 usaban condiciones indeterminadas; v2.1 conserva métricas provisionales, pendientes de entorno y ventana acordados.
- **D-07:** RF-210 y otros requisitos de parametrización carecen de catálogo de datos y resultado observable; RNF-004 requiere revisión jurídica y RNF-005 una prueba con usuarios. Siguen abiertos.
- **D-08:** RF-257 a 263 descomponen API de recolección, mapa, panel y alertas exigidos en el 3.er avance. El detalle funcional y sus casos de uso requieren validación; no se presenta como implementación existente.
- **D-09:** CU-004 y CU-027 quedan como controles internos, fuera de los óvalos UML. La matriz de la v2.1 enlaza RF-233 a 236 con objetivos de actor y deja RF-009/166 como reglas transversales sin CU directo.

## 6. Control de cambios y pendientes

- La versión inicial y las adendas históricas conservan su texto y fecha. Esta v2.1 consolida sin reescribir la entrega congelada.
- Redacción ajustada: RF-016, RF-031, RF-218, RF-242, RF-243, RNF-001, RNF-003, RNF-007, RNF-008, RNF-013 y RNF-015.
- Prioridad corregida por la sección 9.4: centros, maquinaria e informe simple. RF-257 a 263 son nuevos y mantienen el compromiso del tercer avance.
- Pendiente: validación de RF-257 a 263 con equipo/docente; catálogo de parametrización y stock; entorno y carga de rendimiento; ventana de disponibilidad; revisión jurídica; sesiones de usabilidad; CU ausentes y pruebas detalladas.
- El integrador v1.1 y los PDF anteriores siguen mostrando su estado histórico. La incorporación de esta ERS al integrador corresponde al paso 11 del plan.
