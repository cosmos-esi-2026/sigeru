---
materia: Ciberseguridad
entrega: 3ra (ampliación de controles de acceso y roles)
fecha: 2026-10-12
---

# Ampliación: matriz de permisos por rol, límite de intentos y archivos que no se sirven

Amplía `controles-de-acceso.md` (14/09) sin reemplazarlo: lo que ese documento describe sigue
valiendo, salvo lo que se corrige expresamente en la sección 2. Se escribió en la parte 2 de la
mejora de la página de la 3ra entrega.

## 1. Matriz de permisos

Sale de la tabla 12.2 del documento de UTU LAB (1ra entrega) y de los actores de los casos de uso
(CU-015, CU-019, CU-020, CU-021, CU-031 y CU-032). Es la **única** definición: vive en
`api-gestion/core/Permisos.php` y la usa el router de la API.

| Recurso | Administrador | Operario | Cuadrilla | Vecino |
|---|---|---|---|---|
| Usuarios y solicitudes de registro | todo | — | — | — |
| Contenedores (lista y ficha) | todo | leer | leer | leer |
| Historial de un contenedor | leer | — | leer | — |
| Camiones | todo | — | — | — |
| Centros de acopio | todo | leer y actualizar capacidad y datos (no el estado, ni alta ni baja) | — | — |
| Maquinaria | todo | leer | — | — |
| Zonas y tipos de residuo | todo | leer | leer | leer |
| Incidencias: ver | todas | — | solo las asignadas a su cuadrilla | solo las que informó |
| Incidencias: informar | — | — | — | sí |
| Incidencias: prioridad, asignar y descartar | sí | — | — | — |
| Incidencias: avance | — | — | sí (solo las suyas) | — |
| Incidencias: resolver | sí | — | sí (solo las suyas) | — |
| Cuadrillas (con su disponibilidad) | leer | — | — | — |
| Sin sesión | solo `POST /login` y `POST /solicitudes` | | | |

La **denegación es el valor por defecto**: si un recurso o un método no figura en la tabla, nadie
puede usarlo (responde 403 a quien tiene sesión y 401 a quien no). Una prueba unitaria falla si el
router de la API tiene un recurso sin regla.

## 2. Qué se corrigió respecto del 14/09

- Las lecturas de contenedores y camiones eran **públicas «por diseño»** (sin sesión). Ahora piden
  sesión: sin ella 401, y por rol según la tabla. La consulta pública que necesitan la landing y el
  mapa se hará aparte, con un endpoint propio de campos limitados.
- Un Vecino ya no puede consultar maquinaria ni camiones (no figuran en sus casos de uso).
- Una cuenta dada de baja deja de servir **en la misma sesión**: `GET /sesion` responde 403 y las
  APIs también (el rol y el estado se releen de la base en cada petición, como ya se hacía).

Las incidencias se agregaron en la parte 3 de la mejora (ver
`programacion-full-stack/ampliacion-incidencias-3ra.md`). Además del permiso por rol, el **alcance** lo
impone la API sobre cada incidencia: una cuadrilla que pide una ajena recibe 404 al leer y 403 al
escribir, y el nombre de quien reportó lo ve solo el administrador (Ley 18.331).

## 3. Cómo se aplica

1. `Auth::exigirRol(...$roles)` acepta varios roles. Devuelve 401 si no hay sesión, 403 si la cuenta
   no está activa o el rol no está en la lista.
2. El frontend **oculta** lo que el rol no puede hacer (menú por rol, botones de alta y baja, y
   redirección si se abre una pantalla ajena), pero eso es solo comodidad: quien impone el permiso
   es la API. Al cargar cada pantalla se consulta `/sesion` al servidor, no se confía en lo guardado
   en el navegador.
3. Un Operario puede actualizar centros de acopio pero no cambiar su estado: si manda un `estado`
   distinto del actual, 403.

## 4. Límite de intentos de login

- **5 fallos en 15 minutos** para un mismo email bloquean ese email hasta que el quinto fallo más
  reciente salga de la ventana. Respuesta `429` con `Retry-After` en segundos.
- Se cuenta por email normalizado (minúsculas, sin espacios), así que cambiar mayúsculas no sirve.
  Un acceso correcto reinicia el conteo. No se usa la IP porque detrás de un NAT (como el reenvío de
  puertos de la VM) todos los clientes comparten una.
- Cada intento, bueno o malo, queda en la tabla técnica `intento_login` (creada con la migración
  `001-intento-login.sql`); se conservan 30 días. Sirve también como registro de accesos.
- El mensaje de error es el mismo para un email inexistente y para una clave equivocada, y se
  verifica un hash falso cuando el email no existe, para que el tiempo de respuesta no delate qué
  cuentas existen.
- **Compromiso asumido:** quien conozca el email de una persona puede bloquearla 15 minutos con
  fallos a propósito. Se acepta a cambio de frenar la fuerza bruta; no hay desbloqueo manual.

## 5. Cabeceras y archivos que no se sirven

- Las respuestas JSON llevan `Cache-Control: no-store` y `X-Content-Type-Options: nosniff`.
- `sigeru/.htaccess` es una **lista blanca**: solo `landing/`, `frontend-usuarios/`, `api-usuarios/`
  y `api-gestion/` se sirven por web; todo lo demás (`.git`, `tests/`, `docker/`, `database/`, los
  documentos) responde 403, y el listado de directorios está apagado. Al agregar una aplicación hay
  que sumarla ahí.
- **Hallazgo:** antes de este cambio `GET /sigeru/tests/correr-tests.php` **ejecutaba la suite de
  pruebas dentro del servidor** (creaba y borraba datos, iniciaba sesiones), y la propia suite, al
  pedir esa URL, se llamaba a sí misma en bucle. Con el repositorio expuesto también eran
  descargables `.git`, el esquema y los datos de prueba.

## 6. Limitaciones conocidas

- **El Operario no está atado a un centro**: puede actualizar la capacidad de cualquiera. Para
  corregirlo hace falta una columna `id_centro` en `usuario` (migración nueva) y un selector en la
  pantalla de Usuarios; queda como mejora prevista.
- Sin token anti-CSRF: se confía en la cookie `SameSite=Lax`, que no viaja en peticiones
  cruzadas con método que cambia datos, y en que las APIs solo aceptan JSON.
- La cookie de sesión no lleva `Secure` porque el entorno de pruebas es HTTP; en un despliegue con
  HTTPS hay que activarla. Las sesiones usan el vencimiento por defecto de PHP.
- El panel muestra al Vecino el resumen de contenedores; su vista propia («Mis reclamos») llega con
  las pantallas de incidencias (parte 4). La API ya le entrega solo las suyas.

## 7. Verificación

- `tests/correr-tests.php`, secciones 8 a 10: cada recurso con los cuatro roles y sin sesión, el
  Operario y los centros, el límite de intentos (bloqueo, mayúsculas, ventana vencida, reinicio),
  la cuenta dada de baja, las cabeceras y los archivos que no se sirven.
- `tests/unitarias.php`: la política de bloqueo (función pura) y que el router no tenga recursos sin
  regla.
- Pruebas de mutación: se rompió a propósito cada regla (lectura abierta, control omitido, límite
  apagado, estado del centro, sesión de cuenta inactiva, cierre del `.htaccess`, cabecera) y alguna
  prueba falló en cada caso.

## 8. Actualización del 14/10/2026 (parte 4: pantallas de incidencias)

- **Menú por rol:** el Administrador suma «Incidencias»; la Cuadrilla, «Mis incidencias»; el Vecino,
  «Informar un problema» y «Mis reclamos» (ya no se queda solo con el resumen de contenedores). El
  Operario no cambia. El detalle de una incidencia y la ficha del contenedor quedan declarados en
  `shell.js` con los roles que la API admite; una prueba unitaria compara cada pantalla con su permiso.
- **Corrección (Ley 18.331):** la regla de la sección 2 («el nombre de quien reportó lo ve solo el
  administrador») no se cumplía en el **historial** del detalle, que mostraba el nombre y el id del vecino a
  la cuadrilla. Corregido en la API (`FlujoIncidencia::historialVisible`), con pruebas unitarias y por HTTP.

## 9. Actualización del 14/10/2026 (parte 5: `api-recoleccion`)

Camiones y cuadrillas se mudaron a `api-recoleccion` con los mismos permisos. Reglas nuevas, en
`api-recoleccion/core/Permisos.php` (misma forma que la de gestión, denegación por defecto):

| Recurso | Administrador | Operario | Cuadrilla | Vecino |
|---|---|---|---|---|
| Camiones (todo) | Sí | — | — | — |
| Cuadrillas (leer) | Sí | — | — | — |
| Rutas (todo) | Sí | — | — | — |
| Recolecciones: leer | Todas | — | Solo las de su cuadrilla | — |
| Recolecciones: planificar y cancelar | Sí | — | — | — |
| Recolecciones: iniciar, registrar vaciados, finalizar | Sí | — | Solo las de su cuadrilla | — |

Una recolección ajena responde 404 al leerla y 403 al intentar una acción. En el menú, «Recolecciones» es
del Administrador y «Mis tareas» de la Cuadrilla; una prueba unitaria compara cada pantalla con el permiso
de la API que la alimenta, también entre aplicaciones distintas.

## 10. Actualización del 17/10/2026 (parte 7: estadísticas y reportes)

Recurso nuevo `estadisticas`, en las dos APIs, **solo lectura y solo del Administrador** (denegación por defecto
para todo lo demás: un `POST` o `DELETE` responde 403 incluso al administrador, porque no hay regla que lo permita).

| Recurso | Administrador | Operario | Cuadrilla | Vecino |
|---|---|---|---|---|
| `GET /api-gestion/estadisticas/incidencias` (informe de un período) | Sí | — | — | — |
| `GET /api-gestion/estadisticas/urgencias` (panel) | Sí | — | — | — |
| `GET /api-recoleccion/estadisticas/recoleccion` (kilos y recolecciones) | Sí | — | — | — |

Por qué solo el administrador: el informe agrega todas las zonas y todas las cuadrillas y su detalle trae el
código y la dirección de cada contenedor de cada incidencia; ninguno de los otros roles tiene hoy ese alcance
(la cuadrilla ve solo lo suyo y el vecino solo lo que reportó). El detalle del informe **no** trae el nombre de
quien reportó (regla de privacidad de la sección 2). En el menú, «Reportes» es del Administrador; una prueba
unitaria compara la pantalla con el permiso de la API. Los filtros del período se validan en el servidor
(fechas reales, período no invertido, zona numérica y existente) y todas las consultas van preparadas.

## 11. Actualización del 17/10/2026 (parte 8: lo público de la landing)

Primer recurso **sin sesión**: `GET /api-gestion/publico/mapa`. Se agregó la regla `ANONIMO` a `Permisos.php` de
`api-gestion` (solo para ese recurso y solo para `GET`; cualquier otro método responde 401 sin sesión y 403 con
ella). Qué muestra y qué no está en `programacion-full-stack/ampliacion-landing-3ra.md`: lugares públicos y
cifras agregadas, **nunca** datos de personas ni incidencias individuales. Una prueba por HTTP verifica que cada
contenedor tenga exactamente los siete campos públicos y que la respuesta no contenga correos, teléfonos ni ids de usuario.

## 12. Actualización del 17/10/2026 (P4b: foto de la incidencia)

| Recurso | Administrador | Operario | Cuadrilla | Vecino |
|---|---|---|---|---|
| `POST /incidencias/{id}/foto` (subir) | — | — | — | Solo **quien informó**, una vez, con la incidencia abierta |
| `GET /incidencias/{id}/foto` (ver) | Todas | — | Solo las de su cuadrilla | Solo las suyas |

La regla de alcance es la misma de la incidencia (`FlujoIncidencia::alcanza`): una ajena responde 404. El archivo no
se sirve como archivo: pasa siempre por la API, con sesión, y vive fuera de la lista blanca del `.htaccess`.
