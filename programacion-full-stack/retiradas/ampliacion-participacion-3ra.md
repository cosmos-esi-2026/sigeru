> **RETIRADA el 25/10/2026.** Esta funcionalidad se sacó del sistema antes del congelamiento para dejar la aplicación más simple y defendible (ver `../ampliacion-simplificacion-3ra.md`). El documento se conserva solo como registro de lo que se hizo; **nada de lo que describe existe ya en el código ni se va a defender.**

---
materia: Programación Full Stack
entrega: 3ra (extras, parte 14: participación del vecino y calidad del servicio)
fecha: 2026-10-19
---

# Ampliación: participación del vecino

Parte 14 del plan de extras. Objetivo: que el vecino participe más y que el administrador mida la calidad del servicio. Sesión autónoma
(Fornos pidió seguir sin responder): las decisiones marcadas «SIN Fornos» se pueden corregir.

## 1. Decisiones (SIN Fornos)

- **Plazo para calificar: 30 días desde la resolución** (recomendación del plan). Pasado el plazo el sistema responde 409 y la pantalla lo explica.
- **Escala de puntos: 10 por reclamo resuelto y 5 por calificar.** Se suman **solo al resolverse** (en la misma transacción que la resolución), no por informar (para evitar
  abusos). Un reclamo descartado no suma ni se califica. Cada puntaje queda como un movimiento propio (quién, cuándo, por qué reclamo y por qué motivo): eso es su auditoría,
  además del registro general de acciones.
- **El ranking sale solo con sesión** (no está en la landing) y **es por zona, nunca por persona:** zona, reclamos resueltos y puntos, sin nombres ni datos personales.
- **Quién ve qué:** califica solo el autor (para cualquier otro, el reclamo «no existe»: 404). La calificación (puntaje y comentario) la ven el autor, el administrador y la
  cuadrilla asignada a ese reclamo; **nadie más llega al reclamo** (otra cuadrilla y otros vecinos reciben 404).
- **Una calificación no se edita ni se borra** (triggers); una sola por reclamo (restricción de unicidad en la base).
- **Insignias calculadas, no guardadas:** *Primer aviso resuelto* (1 reclamo resuelto), *Vecino activo* (5), *Vecino ejemplar* (10) y *Opina para mejorar* (1 calificación).
- **Datos de demostración:** la única incidencia resuelta del seed (INC-0002) ya está calificada (4 de 5, marcada «de demostración») y de ahí salen los 15 puntos de Marta. Para
  demostrar el flujo completo en vivo: el vecino informa un problema, el administrador lo asigna, la cuadrilla lo resuelve y el vecino lo califica.

## 2. Modelo de datos (migraciones 012 y 013)

| Tabla | Detalle |
|---|---|
| `calificacion` | `id_incidencia` único, autor, puntaje 1 a 5 (`CHECK`), comentario ≤300 y fecha. Sin edición ni borrado (013) |
| `movimiento_punto` | Usuario, incidencia, motivo (`reclamo_resuelto` o `calificacion`), puntos y fecha; único por (incidencia, motivo): no se duplica. Solo agregado (013) |

**22 tablas del modelo** (eran 20). Borrar la incidencia o el usuario completos se lleva sus calificaciones y puntos en cascada (no dispara los triggers).

## 3. API (`api-gestion`)

| Endpoint | Quién | Qué hace |
|---|---|---|
| `POST /incidencias/{id}/calificacion` | Vecino (el autor) | `puntaje` (1 a 5) y `comentario` opcional. 409 si no está resuelto, está descartado, ya se calificó o venció el plazo |
| `GET /incidencias/{id}` | quien ya la veía | Ahora trae `calificacion`, `puede_calificar` y `calificar_hasta` |
| `GET /participacion/progreso` | Vecino | Puntos, resueltos, calificados, insignias, últimos movimientos y los reclamos que todavía puede calificar |
| `GET /participacion/ranking` | cualquier rol con sesión | Zonas con posición, reclamos resueltos y puntos (los empates comparten posición) |
| `GET /estadisticas/satisfaccion?desde=&hasta=&zona=` | Administrador | Por zona y por cuadrilla: incidencias, resueltas, calificaciones, satisfacción promedio y tiempo medio de resolución; general con la distribución de puntajes |

## 4. Pantallas

- **Detalle de la incidencia:** panel «Calificación de la atención» (cinco opciones accesibles con texto, comentario opcional y el plazo); ya calificado, muestra las estrellas y el comentario.
- **Inicio del Vecino:** «Mi progreso» (puntos, insignias con lo que falta, últimos puntos y reclamos para calificar) y «Ranking de zonas».
- **Reportes (Administrador):** sección «Calidad del servicio» con las dos tablas y **Exportar (CSV)**; el ranking de zonas con más incidencias y el tiempo medio ya estaban en el informe.

## 5. Pruebas

Unitarias nuevas (53, en `unitarias-participacion.php`): puntajes válidos e inválidos, el plazo (justo en el límite y un segundo después), cada regla de calificación (otro vecino, otros roles,
abierta, en curso, descartada, repetida, vencida), insignias, ranking con empates y la calidad del servicio con una cuenta hecha a mano. HTTP nuevas (66, `correr-tests-participacion.php`): 401 y 403,
que los puntos lleguen al resolver y no al informar, que otro vecino no pueda calificar ni ver, que no se califique dos veces ni una incidencia sin resolver o descartada, el plazo de 30 días, que la base
bloquee editar o borrar por fuera de la API y no deje duplicar puntos ni un puntaje fuera de escala, el ranking sin datos personales y los informes con filtros. Todo con registros propios `TPAR` que se
borran al final.
