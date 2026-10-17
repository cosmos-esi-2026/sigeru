---
materia: Programación Full Stack (y base de datos)
entrega: 3ra (ampliación: api-recoleccion, rutas y recolecciones)
fecha: 2026-10-14
---

# Ampliación: `api-recoleccion/` — camiones, cuadrillas, rutas y recolecciones

Amplía la arquitectura de la 2da entrega sin reescribirla. Se hizo en la parte 5 de la mejora de la página
de la 3ra entrega y cierra el hallazgo H3: la letra pide seis aplicaciones y faltaba la API de recolección.
**El modelo de datos no cambia:** `ruta`, `recoleccion` y `vaciado` ya estaban en el esquema (16 clases)
desde la 2da entrega, con datos de prueba, pero ninguna API las usaba.

## 1. Qué se movió y qué se agregó

Decisiones de Fornos (14/10/2026): **mover** (no duplicar) camiones y cuadrillas; el estado del camión
cambia **solo**; las pantallas «Mis tareas» y «Recolecciones» entran en esta parte, y el alta de rutas
queda solo en la API (su pantalla, para una parte 5b).

| Recurso | Antes | Ahora |
|---|---|---|
| `/camiones` | `api-gestion` | `api-recoleccion` (sin cambios de contrato; `api-gestion` responde 404) |
| `/cuadrillas` | `api-gestion` | `api-recoleccion` (ídem) |
| `/rutas` | — | `api-recoleccion` (nuevo) |
| `/recolecciones` y sus acciones | — | `api-recoleccion` (nuevo) |

`api-recoleccion/` tiene la misma estructura MVC que las otras dos (`index.php` como router, `.htaccess`,
`config/`, `core/`, `models/`, `controllers/`). Comparte la base. Las piezas comunes son **copias
idénticas**, vigiladas por `tests/unitarias.php`: `core/Auth.php` y `core/Response.php` en las tres APIs;
`config/database.php`, `core/ErrorDeNegocio.php`, `.htaccess` y `models/Cuadrilla.php` entre gestión y
recolección. El modelo `Cuadrilla.php` sigue en `api-gestion` porque asignar una incidencia comprueba,
dentro de la misma transacción, que la cuadrilla esté libre; pero `api-gestion` ya no expone `/cuadrillas`.
Se sumó `api-recoleccion` a la lista blanca de `sigeru/.htaccess`, al Docker y al workflow.

## 2. Flujo de una recolección

| Acción | Ruta | Quién | Desde | Queda en | Efecto |
|---|---|---|---|---|---|
| Planificar | `POST /recolecciones` | Administrador | — | planificada | Ruta activa, camión que no esté en mantenimiento ni fuera de servicio, cuadrilla activa, centro activo (opcional) |
| Iniciar | `POST /recolecciones/{id}/inicio` | Administrador, Cuadrilla asignada | planificada | en_curso | Exige camión **operativo** y que la cuadrilla no tenga otra en curso. El camión pasa a **en ruta**; la hora de inicio pasa a ser la real |
| Registrar vaciado | `POST /recolecciones/{id}/vaciados` | Administrador, Cuadrilla asignada | en_curso | en_curso | Contenedor existente y no retirado, una vez por recolección; peso obligatorio (0 a 5000 kg), volumen opcional, sin pasar la capacidad del contenedor |
| Finalizar | `POST /recolecciones/{id}/fin` | Administrador, Cuadrilla asignada | en_curso | finalizada | Hora de fin; el camión vuelve a **operativo** |
| Cancelar | `POST /recolecciones/{id}/cancelacion` | Administrador | planificada, en_curso | cancelada | Motivo obligatorio. Si estaba en curso, hora de fin y camión a operativo |

Una recolección cerrada (finalizada o cancelada) no admite nada más (409). Cada acción es una sola
transacción que bloquea las filas que toca (`FOR UPDATE`). Mientras un camión está en una recolección en
curso, su estado no se puede cambiar a mano en «Camiones» (409): cambia solo al finalizar o cancelar.
Se puede **planificar** con un camión que hoy está en ruta (es para otro momento), pero no **iniciar**.

Lecturas: `GET /recolecciones` (filtros `estado`, `activas=1`, `ruta`, `camion`, `cuadrilla`, `desde`,
`hasta`) y `GET /recolecciones/{id}` (con el detalle de los vaciados). El administrador ve todas; la
cuadrilla, solo las suyas (las ajenas responden 404 al leer y 403 al escribir, como en incidencias).
Rutas: `GET/POST /rutas`, `GET/PUT/DELETE /rutas/{id}` (la baja la deja inactiva), solo el administrador.

## 3. Pantallas

| Pantalla | Quién | Qué hace |
|---|---|---|
| `recolecciones.html` («Recolecciones») | Administrador | Lista con filtros (pendientes, todas o un estado; período) y «Planificar recolección»: al elegir la ruta propone su próximo día y hora; solo ofrece camiones operativos o en ruta y cuadrillas activas. |
| `mis-tareas.html` («Mis tareas») | Cuadrilla | Sus recolecciones, por defecto las pendientes (en curso primero, después las planificadas por fecha). |
| `recoleccion.html?id=N` | Administrador, Cuadrilla | Datos, vaciados y acciones según el estado: iniciar, registrar vaciados (el foco vuelve al contenedor para cargar el siguiente), finalizar y, el administrador, cancelar. Finalizar y cancelar piden confirmación. |

«Camiones», el panel de inicio y la asignación de una incidencia ahora leen de `api-recoleccion`.

## 4. Pruebas

- **Unitarias:** el flujo de estados (qué se puede en cada uno), quién alcanza cada recolección, cuándo
  puede salir un camión y a qué vuelve, la validación de planificaciones, vaciados y rutas, los filtros,
  que los ENUM de `schema.sql` y las constantes coincidan, que cada recurso del router tenga su regla, que
  el menú no muestre lo que la API niega y las copias idénticas.
- **Por HTTP:** la matriz de permisos de las rutas nuevas con los cuatro roles y sin sesión, que
  `api-gestion` ya no responda camiones ni cuadrillas, las rutas (alta, validaciones, edición, baja) y el
  recorrido completo con sus negativas, con un camión, una ruta y dos contenedores propios (`TREC-`) que
  se borran al final; y que los camiones y las recolecciones del seed queden como estaban.

## 5. Limitaciones y lo que sigue

- **Vaciar un contenedor no cambia su estado.** Las únicas reglas automáticas sobre el contenedor siguen
  siendo las dos de incidencias (decisión del 12/10). Que un vaciado deje «disponible» un contenedor lleno
  es razonable, pero pide una decisión: queda anotado.
- No se edita una recolección planificada: se cancela y se planifica de nuevo.
- La hora planificada se reemplaza por la real al iniciar (el esquema tiene una sola columna de inicio).
- El alta y la edición de rutas no tienen pantalla todavía (parte 5b, si sobra).
- Sin paginación del listado (parte 9). Los camiones no figuran en la asignación de incidencias: la
  incidencia no tiene camión en el modelo.
- `FOR UPDATE` y las transacciones se probaron en MariaDB 10.4 (XAMPP); en MySQL 8 las cubre el workflow
  al subir los cambios.
