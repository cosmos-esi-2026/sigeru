---
materia: Programación Full Stack (y base de datos)
entrega: 3ra (ampliación: módulo de incidencias, modelo y API)
fecha: 2026-10-12
---

# Ampliación: módulo de incidencias — modelo y API

Amplía el modelo de datos de la 2da entrega sin reescribirlo. Se hizo en la parte 3 de la mejora de la
página de la 3ra entrega. Implementa los requerimientos RF-217 a RF-241 y RNF-016 y RNF-017 de
`ingenieria-software/Requerimientos_Incidencias_SiGeRU.pdf`. Las pantallas llegan en la parte 4; la foto
adjunta (RF-222 y RF-223) y los reportes (RF-242 a RF-245) quedan para partes posteriores.

## 1. Qué cambió en el modelo

- **Modelo de 15 a 16 clases.** Nace `historial_incidencia`. La ampliación del 03/09 decía «modelo de
  datos sin cambios», pero RF-232 pide conservar cada cambio de estado de una incidencia con su fecha,
  su autor y su observación, y la tabla `incidencia` sola no puede guardarlo. Es composición de la
  incidencia (`ON DELETE CASCADE`) y es de **solo agregado**.
- **`incidencia.tipo_problema`** (nueva columna): desbordado, roto, residuo_no_permitido,
  ausente_o_mal_ubicado, otro. Son los tipos del prototipo Low-Fi.
- **Estados renombrados**, para coincidir con la especificación (RF-219 y RF-227):

| Antes (2da entrega) | Ahora | Significado |
|---|---|---|
| `reportada` | `abierta` | recién informada, sin cuadrilla |
| `en_proceso` | `en_curso` | asignada a una cuadrilla |
| `resuelta` | `resuelta` | final |
| `descartada` | `descartada` | final |

  El *Diseño del Sistema* y la guía de Ingeniería de Software de la 2da usan los nombres de antes
  («mismo significado con distinto nombre»); esta tabla es la equivalencia. Esos documentos no se tocan.
- **Migraciones** (`database/migraciones/`): `002-incidencias-historial.sql` (estados, tipo, tabla) y
  `003-historial-solo-agregado.sql` (triggers). `schema.sql` queda en el estado final, y se comprobó
  que una base nueva y una migrada tienen exactamente la misma estructura. Para una base existente:
  `php database/migrar.php`.

## 2. Flujo de una incidencia

| Acción | Quién | Desde | Queda en |
|---|---|---|---|
| Informar | Vecino | — | abierta |
| Cambiar prioridad | Administrador | abierta, en_curso | (igual) |
| Asignar o reasignar | Administrador | abierta, en_curso | en_curso |
| Registrar avance | Cuadrilla asignada | en_curso | en_curso |
| Resolver | Administrador, Cuadrilla asignada | abierta, en_curso | resuelta |
| Descartar | Administrador | abierta, en_curso | descartada |

Una incidencia cerrada (resuelta o descartada) no admite nada más (409). Cada acción deja un asiento
en `historial_incidencia`, firmado por quien la hizo (RNF-017), dentro de la misma transacción.

**Efecto sobre el contenedor** (CU-027, «include» de CU-023 y CU-026), con dos reglas:

1. Informar un `desbordado` sobre un contenedor **disponible** lo pasa a **lleno**.
2. Cerrar una incidencia (resuelta o descartada) devuelve el contenedor a **disponible** si estaba lleno o
   en mantenimiento **y no queda otra incidencia abierta sobre él**.

Cada cambio deja su asiento en el historial del contenedor. Si el estado ya era ese, no se duplica.

## 3. API (en `api-gestion`)

| Método y ruta | Quién | Notas |
|---|---|---|
| `GET /incidencias` | Administrador, Cuadrilla, Vecino | Alcance por rol: el administrador ve todas, la cuadrilla las suyas, el vecino las que informó. Filtros: `estado`, `tipo`, `zona`, `contenedor`, `cuadrilla`, `desde`, `hasta` (AAAA-MM-DD, el día completo). |
| `GET /incidencias/{id}` | igual | Con su historial. Fuera de alcance responde 404. |
| `POST /incidencias` | Vecino | `codigo_contenedor` o `id_contenedor`, `tipo_problema`, `descripcion` (5 a 500), `prioridad` opcional. Devuelve 201 con el número `INC-NNNN`. |
| `PUT /incidencias/{id}` | Administrador | `{prioridad}` |
| `POST /incidencias/{id}/asignacion` | Administrador | `{id_cuadrilla, prioridad?}` |
| `POST /incidencias/{id}/avance` | Cuadrilla | `{observacion}` obligatoria |
| `POST /incidencias/{id}/resolucion` | Administrador, Cuadrilla | `{observacion?}` |
| `POST /incidencias/{id}/descarte` | Administrador | `{observacion}` obligatoria (el motivo) |
| `GET /cuadrillas[?disponibles=1]` | Administrador | Con `ocupada`, `disponible` e `incidencias_en_curso`. |

Respuestas: 400 dato inválido · 401 sin sesión · 403 rol sin permiso o incidencia ajena (escrituras) ·
404 no existe o fuera de alcance (lecturas) · 409 estado que no admite la acción, contenedor retirado,
cuadrilla inactiva u ocupada, o reporte repetido.

## 4. Decisiones de diseño

- **Disponibilidad de una cuadrilla (RF-228):** activa y sin una recolección `en_curso`. El servidor lo
  vuelve a comprobar al asignar (no basta con que la pantalla no la ofrezca). Los camiones disponibles
  se ofrecerán cuando exista `api-recoleccion` (parte 5): la incidencia no tiene camión en el modelo.
- **Reporte repetido (CU-023, A2.0):** si ya hay una incidencia abierta del mismo tipo sobre ese contenedor,
  se responde 409 con el número de la existente. «Adherirse» al reporte existente no se implementó (pediría
  una tabla de adhesiones); queda como limitación.
- **Quién reporta:** solo un vecino con sesión (RF-217). El administrador no informa por otra persona.
- **Prioridad:** el vecino puede proponerla (el prototipo se la muestra); el administrador la fija (RF-225).
- **Privacidad (Ley 18.331, RNF-004):** el nombre de quien reportó lo ve solo el administrador. La
  cuadrilla ve qué hay que hacer y dónde, no quién llamó.
- **Resolver sin asignar:** el administrador puede resolver o descartar una incidencia abierta sin pasarla
  por una cuadrilla (por ejemplo, un reporte duplicado). La cuadrilla solo ve las que tiene asignadas.
- **Cerrar por descarte también libera al contenedor:** si un desborde falso lo había marcado lleno y no
  queda otro reporte abierto, vuelve a disponible.

## 5. Historial de solo agregado (RNF-016 y RF-235)

Los triggers de la migración 003 impiden `UPDATE` y `DELETE` sobre `historial_incidencia` y sobre
`estado_contenedor`, con el mensaje «…es de solo agregado». Valen aunque alguien entre por fuera de la API
(phpMyAdmin, otra herramienta). Borrar la incidencia o el contenedor completos sí se puede: el
`ON DELETE CASCADE` no dispara triggers, y así la composición sigue funcionando.

Consecuencias para las pruebas: nada que toque un contenedor del seed se puede deshacer a mano. Las pruebas
crean su propio contenedor y lo borran al final (el historial se va en cascada).

**Para instalar en un servidor MySQL 8:** crear un trigger pide el permiso `TRIGGER` y, si el registro
binario está activo (viene activo por defecto en MySQL 8), también `SUPER` o la variable
`log_bin_trust_function_creators=1`. Por eso `migrar.php` (o `schema.sql`) se corre con un usuario
administrador, nunca con el usuario que usa la aplicación. Una vez creados, los triggers funcionan para
cualquier usuario. Con el contenedor Docker del proyecto (`root`) y con XAMPP no hace falta ningún ajuste.

## 6. Transacciones (cierra el hallazgo H11a)

`Database::transaccion()` abre una transacción o se suma a la que ya está abierta, y revierte todo si algo
falla. `Contenedor::cambiarEstado()` es el único camino para cambiar el estado de un contenedor: bloquea la
fila, actualiza y deja el asiento. Antes `actualizar` y `darDeBaja` hacían el `UPDATE` y el asiento por
separado. Las operaciones de incidencias (informar, asignar, avance, cierre) son una sola transacción con
el asiento de la incidencia y, si corresponde, el del contenedor.

## 7. Pruebas

- **Unitarias ({{UNIT}}, sin base ni servidor):** el flujo de estados (qué se puede hacer en cada uno),
  quién alcanza cada incidencia, las dos reglas sobre el contenedor, la validación de lo que llega, los
  filtros, los permisos, que el router y las reglas coincidan, y que los ENUM de `schema.sql` y las
  constantes del código no se desfasen.
- **Por HTTP ({{HTTP}}):** la matriz de permisos con los cuatro roles y sin sesión, el recorrido completo
  (informar, repetido, filtros, prioridad, asignar y reasignar, cuadrilla ajena, avance, resolver, cerrada,
  descarte), el estado del contenedor en cada paso, que la base rechace modificar o borrar un asiento, la
  transacción que revierte y la limpieza en cascada.

## 8. Limitaciones y lo que sigue

- Foto adjunta (RF-222, RF-223): parte 4b, si sobra el tiempo.
- Reportes de incidencias (RF-242 a RF-245) y panel por urgencias: parte 7.
- Camiones disponibles en la asignación y mover las cuadrillas a `api-recoleccion`: parte 5.
- Sin paginación del listado (parte 9).
- Los triggers y `FOR UPDATE` se probaron en MariaDB 10.4 (XAMPP). En MySQL 8 los cubre el workflow de
  GitHub al subir los cambios (Docker no corre en la PC de desarrollo): hasta entonces es lo único de esta
  parte sin probar en el motor del contenedor.
- Un Operario no tiene acceso a incidencias: no figura en los casos de uso CU-023 a CU-028.

## 9. Pantallas (parte 4, 14/10/2026)

La API de las secciones anteriores ya tiene cara en `frontend-usuarios`, con el diseño del prototipo Low-Fi
(`incidencia-nueva` e `incidencia-detalle`). Nombres del menú y alta en página propia: decisión de Fornos
del 14/10.

| Pantalla | Quién | Qué hace |
|---|---|---|
| `informar-problema.html` («Informar un problema») | Vecino | Formulario de un paso: contenedor por código (con lista de sugerencias, sin los retirados), tipo, descripción de 5 a 500 caracteres y prioridad propuesta. Al guardar muestra la confirmación con el número `INC-NNNN`. Si el reporte se repite avisa con el número: con enlace si el reporte es del mismo vecino, sin enlace si es de otra persona (no lo puede abrir). Acepta `?contenedor=CODIGO` para que el mapa de la parte 6 llegue con el contenedor elegido. |
| `mis-reclamos.html` («Mis reclamos») | Vecino | Sus incidencias, con filtro por estado. |
| `incidencias.html` («Incidencias») | Administrador | Todas, con filtros por estado, zona y período (los filtros van al servidor y quedan en la dirección, así que «volver» los conserva). |
| `mis-incidencias.html` («Mis incidencias») | Cuadrilla | Las asignadas a su cuadrilla, con filtro por estado. |
| `incidencia.html?id=N` | Los tres | Datos, historial y acciones según el rol y el estado: el administrador asigna o reasigna (solo cuadrillas disponibles, desde `GET /cuadrillas?disponibles=1`), cambia la prioridad, resuelve y descarta (motivo obligatorio); la cuadrilla registra avances y resuelve; el vecino solo mira. Resolver y descartar piden confirmación porque no se deshacen. |
| `contenedor.html?id=N` (ficha, RF-236) | Administrador, Cuadrilla | Datos del contenedor, sus incidencias (la cuadrilla, solo las suyas) y el historial de estados. Se llega tocando el código en «Contenedores». |

El menú (`js/shell.js`) suma las entradas por rol; las dos pantallas de detalle no aparecen en el menú, pero
están declaradas ahí con sus roles, así que abrirlas con otro rol redirige al inicio. Inicio muestra el atajo
«Informar un problema» al vecino y «Ver mis incidencias» a la cuadrilla. La seguridad sigue siendo la de la API.

**Corrección de privacidad (encontrada al hacer las pantallas).** La sección 4 dice que la cuadrilla no ve
quién reportó, y `reportada_por` ya se ocultaba, pero el **historial** del detalle traía el nombre y el id del
vecino en su primer asiento. Desde el 14/10, `FlujoIncidencia::historialVisible()` deja el historial completo
solo para el administrador; para los demás, los asientos de un vecino que no es quien mira dicen «Vecino» sin
nombre, y ningún asiento lleva `id_usuario`. Los nombres del personal (administrador y cuadrillas) se siguen
mostrando. Tiene pruebas unitarias y por HTTP.

**Pruebas al 14/10:** 241 por HTTP y 129 unitarias (las de menú ahora cuentan 13 pantallas de gestión y atan
cada pantalla de incidencias a su permiso en la API).

**Límites de esta parte:** elegir el contenedor en un mapa es de la parte 6; la foto adjunta, de la 4b; el
panel por urgencias y los reportes, de la 7; la paginación, de la 9. El diálogo de confirmación es el que ya
usaban las otras pantallas: al abrirse el foco queda en el diálogo y no en un botón (hay que tabular hasta
«Descartar» o «Marcar como resuelta»).

## 10. Nota de la parte 5 (14/10/2026)

`GET /cuadrillas` se mudó a `api-recoleccion` (ver `ampliacion-recoleccion-3ra.md`); la pantalla de detalle
de una incidencia ya la pide ahí. La comprobación al asignar sigue en `api-gestion`, con una copia idéntica
del modelo `Cuadrilla.php`. Los camiones siguen sin figurar en la asignación: la incidencia no tiene camión
en el modelo.
