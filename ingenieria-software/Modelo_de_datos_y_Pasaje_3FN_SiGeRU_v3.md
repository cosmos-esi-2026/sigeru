# SiGeRU — Modelo de datos y pasaje a tablas en 3FN

**Versión:** 3.0 · **Fecha:** 06/11/2026 · **Estado:** revisión documental del SQL actual; validación del equipo pendiente.

## 1. Alcance y fuente

Leímos las sentencias `CREATE TABLE` de `sigeru/database/schema.sql`: 25 tablas físicas, 21 del dominio y 4 de soporte. El archivo contiene 36 claves foráneas. Este es el esquema escrito en el repositorio; no comprobamos aquí que una base instalada tenga exactamente esa estructura.

El DER anterior y el pasaje de julio quedan como historia de esas entregas. Esta versión muestra lo que declara el SQL para el tercer avance. Las tablas de soporte son `migracion`, `intento_login`, `limite_peticion` y `auditoria`.

## 2. Pasaje del modelo a tablas

Cada entidad persistente queda en una tabla. La relación 1:N se representa con una FK en el lado que puede repetirse. Para las relaciones N:M se usa una tabla intermedia: `ruta_contenedor` guarda además `orden`, y `vaciado` registra cada contenedor vaciado en una recolección. `recepcion` enlaza una recolección con su ingreso al centro y tiene `id_recoleccion` único. Estos son hechos del SQL, no una aprobación externa del modelo conceptual.

### 2.1 Inventario completo

| Tabla | Papel | PK declarada | Otras claves únicas | Columnas |
|---|---|---|---|---|
| `rol` | dominio | `id_rol` | `nombre` | `id_rol`, `nombre`, `descripcion` |
| `centro_acopio` | dominio | `id_centro` | — | `id_centro`, `nombre`, `direccion`, `latitud`, `longitud`, `capacidad`, `estado` |
| `zona` | dominio | `id_zona` | `nombre` | `id_zona`, `nombre`, `descripcion`, `id_centro` |
| `tipo_residuo` | dominio | `id_tipo_residuo` | `nombre` | `id_tipo_residuo`, `nombre`, `descripcion`, `reciclable` |
| `cuadrilla` | dominio | `id_cuadrilla` | `nombre` | `id_cuadrilla`, `nombre`, `turno`, `estado`, `id_zona` |
| `solicitud_registro` | dominio | `id_solicitud` | — | `id_solicitud`, `nombre`, `apellido`, `email`, `cedula`, `telefono`, `direccion`, `estado`, `fecha_solicitud`, `tipo_cuenta`, `cuadrilla_indicada` |
| `usuario` | dominio | `id_usuario` | `cedula`, `email` | `id_usuario`, `nombre`, `apellido`, `email`, `cedula`, `password_hash`, `telefono`, `direccion`, `id_rol`, `id_cuadrilla`, `id_centro`, `id_zona`, `id_solicitud`, `estado`, `fecha_registro` |
| `contenedor` | dominio | `id_contenedor` | `codigo` | `id_contenedor`, `codigo`, `direccion`, `latitud`, `longitud`, `capacidad`, `estado`, `id_zona`, `id_tipo_residuo` |
| `estado_contenedor` | dominio | `id_registro` | — | `id_registro`, `id_contenedor`, `fecha_hora`, `estado`, `observacion` |
| `camion` | dominio | `id_camion` | `matricula` | `id_camion`, `matricula`, `marca`, `modelo`, `capacidad`, `estado` |
| `maquinaria` | dominio | `id_maquinaria` | `codigo` | `id_maquinaria`, `codigo`, `tipo`, `marca`, `modelo`, `estado`, `id_centro` |
| `ruta` | dominio | `id_ruta` | — | `id_ruta`, `nombre`, `dia_semana`, `hora_inicio`, `estado`, `id_zona`, `trazado`, `trazado_tipo`, `trazado_firma`, `trazado_servicio`, `trazado_distancia_m`, `trazado_duracion_s`, `trazado_en` |
| `ruta_contenedor` | dominio | `id_ruta`, `id_contenedor` | `id_ruta, orden` | `id_ruta`, `id_contenedor`, `orden` |
| `recoleccion` | dominio | `id_recoleccion` | — | `id_recoleccion`, `fecha_hora_inicio`, `fecha_hora_fin`, `estado`, `observaciones`, `id_ruta`, `id_camion`, `id_cuadrilla`, `id_centro` |
| `vaciado` | dominio | `id_vaciado` | — | `id_vaciado`, `id_recoleccion`, `id_contenedor`, `fecha_hora`, `peso_kg`, `volumen_l` |
| `incidencia` | dominio | `id_incidencia` | — | `id_incidencia`, `descripcion`, `tipo_problema`, `fecha_reporte`, `fecha_resolucion`, `estado`, `prioridad`, `id_usuario`, `id_contenedor`, `id_cuadrilla`, `derivada_en`, `derivada_nota` |
| `historial_incidencia` | dominio | `id_registro` | — | `id_registro`, `id_incidencia`, `fecha_hora`, `estado`, `observacion`, `id_usuario` |
| `migracion` | soporte | `nombre` | — | `nombre`, `aplicada_en` |
| `intento_login` | soporte | `id_intento` | — | `id_intento`, `email`, `ip`, `fecha_hora`, `exitoso` |
| `limite_peticion` | soporte | `id_limite` | — | `id_limite`, `clave`, `fecha_hora` |
| `auditoria` | soporte | `id_auditoria` | — | `id_auditoria`, `fecha_hora`, `id_usuario`, `rol`, `metodo`, `ruta`, `codigo`, `ip` |
| `recepcion` | dominio | `id_recepcion` | `id_recoleccion` | `id_recepcion`, `id_recoleccion`, `id_centro`, `id_usuario`, `fecha_hora`, `observacion` |
| `movimiento_stock` | dominio | `id_movimiento` | `id_corregido` | `id_movimiento`, `id_centro`, `id_tipo_residuo`, `sentido`, `motivo`, `kg`, `id_recoleccion`, `id_corregido`, `id_usuario`, `fecha_hora`, `observacion` |
| `historial_maquinaria` | dominio | `id_registro` | — | `id_registro`, `id_maquinaria`, `fecha_hora`, `estado`, `observacion`, `id_usuario` |
| `notificacion` | dominio | `id_notificacion` | — | `id_notificacion`, `id_usuario`, `tipo`, `titulo`, `mensaje`, `enlace`, `leida`, `fecha_hora` |

### 2.2 Claves foráneas y borrado

La cardinalidad se lee desde el hijo: una FK `NOT NULL` exige un padre; una nullable permite no tenerlo. Si la FK no es única, un padre puede tener varios hijos. La tabla muestra **todas** las FK escritas en `schema.sql`.

| ID | Hijo y columna | Padre y columna | Nulidad | ON DELETE |
|---|---|---|---|---|
| R01 | `zona.id_centro` | `centro_acopio.id_centro` | opcional | RESTRICT IMPLÍCITO |
| R02 | `cuadrilla.id_zona` | `zona.id_zona` | opcional | RESTRICT IMPLÍCITO |
| R03 | `usuario.id_rol` | `rol.id_rol` | obligatoria | RESTRICT IMPLÍCITO |
| R04 | `usuario.id_cuadrilla` | `cuadrilla.id_cuadrilla` | opcional | RESTRICT IMPLÍCITO |
| R05 | `usuario.id_centro` | `centro_acopio.id_centro` | opcional | RESTRICT IMPLÍCITO |
| R06 | `usuario.id_zona` | `zona.id_zona` | opcional | RESTRICT IMPLÍCITO |
| R07 | `usuario.id_solicitud` | `solicitud_registro.id_solicitud` | opcional | RESTRICT IMPLÍCITO |
| R08 | `contenedor.id_zona` | `zona.id_zona` | obligatoria | RESTRICT IMPLÍCITO |
| R09 | `contenedor.id_tipo_residuo` | `tipo_residuo.id_tipo_residuo` | obligatoria | RESTRICT IMPLÍCITO |
| R10 | `estado_contenedor.id_contenedor` | `contenedor.id_contenedor` | obligatoria | CASCADE |
| R11 | `maquinaria.id_centro` | `centro_acopio.id_centro` | obligatoria | RESTRICT IMPLÍCITO |
| R12 | `ruta.id_zona` | `zona.id_zona` | obligatoria | RESTRICT IMPLÍCITO |
| R13 | `ruta_contenedor.id_ruta` | `ruta.id_ruta` | obligatoria | CASCADE |
| R14 | `ruta_contenedor.id_contenedor` | `contenedor.id_contenedor` | obligatoria | CASCADE |
| R15 | `recoleccion.id_ruta` | `ruta.id_ruta` | obligatoria | RESTRICT IMPLÍCITO |
| R16 | `recoleccion.id_camion` | `camion.id_camion` | obligatoria | RESTRICT IMPLÍCITO |
| R17 | `recoleccion.id_cuadrilla` | `cuadrilla.id_cuadrilla` | obligatoria | RESTRICT IMPLÍCITO |
| R18 | `recoleccion.id_centro` | `centro_acopio.id_centro` | opcional | RESTRICT IMPLÍCITO |
| R19 | `vaciado.id_recoleccion` | `recoleccion.id_recoleccion` | obligatoria | CASCADE |
| R20 | `vaciado.id_contenedor` | `contenedor.id_contenedor` | obligatoria | RESTRICT IMPLÍCITO |
| R21 | `incidencia.id_usuario` | `usuario.id_usuario` | obligatoria | RESTRICT IMPLÍCITO |
| R22 | `incidencia.id_contenedor` | `contenedor.id_contenedor` | obligatoria | RESTRICT IMPLÍCITO |
| R23 | `incidencia.id_cuadrilla` | `cuadrilla.id_cuadrilla` | opcional | RESTRICT IMPLÍCITO |
| R24 | `historial_incidencia.id_incidencia` | `incidencia.id_incidencia` | obligatoria | CASCADE |
| R25 | `historial_incidencia.id_usuario` | `usuario.id_usuario` | obligatoria | RESTRICT IMPLÍCITO |
| R26 | `recepcion.id_recoleccion` | `recoleccion.id_recoleccion` | obligatoria | CASCADE |
| R27 | `recepcion.id_centro` | `centro_acopio.id_centro` | obligatoria | CASCADE |
| R28 | `recepcion.id_usuario` | `usuario.id_usuario` | obligatoria | RESTRICT IMPLÍCITO |
| R29 | `movimiento_stock.id_centro` | `centro_acopio.id_centro` | obligatoria | CASCADE |
| R30 | `movimiento_stock.id_tipo_residuo` | `tipo_residuo.id_tipo_residuo` | obligatoria | RESTRICT IMPLÍCITO |
| R31 | `movimiento_stock.id_recoleccion` | `recoleccion.id_recoleccion` | opcional | SET NULL |
| R32 | `movimiento_stock.id_corregido` | `movimiento_stock.id_movimiento` | opcional | CASCADE |
| R33 | `movimiento_stock.id_usuario` | `usuario.id_usuario` | obligatoria | RESTRICT IMPLÍCITO |
| R34 | `historial_maquinaria.id_maquinaria` | `maquinaria.id_maquinaria` | obligatoria | CASCADE |
| R35 | `historial_maquinaria.id_usuario` | `usuario.id_usuario` | obligatoria | RESTRICT IMPLÍCITO |
| R36 | `notificacion.id_usuario` | `usuario.id_usuario` | obligatoria | CASCADE |

## 3. Reglas y límites comprobados

- `usuario.cedula` es única y opcional en SQL; la API exige cédula para el rol Cuadrilla. La FK a `rol` no puede imponer esa condición por sí sola.
- `usuario.id_solicitud` admite nulos y no tiene `UNIQUE`. El DDL no garantiza que una solicitud origine como máximo un usuario.
- `vaciado` tiene PK propia y dos FK, pero el par `(id_recoleccion, id_contenedor)` no es único en el SQL. Hay que decidir si repetir un vaciado en el mismo recorrido está permitido.
- `ruta_contenedor` tiene PK compuesta `(id_ruta, id_contenedor)` y unicidad `(id_ruta, orden)`. La regla de que un contenedor esté en una sola ruta activa se aplica fuera de esas claves.
- `recepcion.id_recoleccion` es único: una recolección puede tener como máximo una recepción registrada.
- Los historiales `estado_contenedor`, `historial_incidencia`, `historial_maquinaria` y `movimiento_stock` tienen triggers contra UPDATE/DELETE directos. Algunas FK usan `ON DELETE CASCADE`; borrar el registro padre puede borrar esos asientos. No llamamos a eso conservación absoluta del historial.
- No hay tabla de stock de contenedores de repuesto ni relación que limite los tipos de residuo que recibe cada centro. Eso sigue pendiente de definición.
- El comentario final de `schema.sql` se corrigió el 06/11 para indicar 21 tablas del dominio y cuatro de soporte. Las sentencias `CREATE TABLE` no se modificaron.

## 4. Revisión de formas normales

### 4.1 Primera forma normal (1FN)

Cada fila tiene una PK declarada y las columnas de uso corriente guardan un valor por campo. `ruta.trazado` contiene GeoJSON como texto; se guarda como un solo documento, no como paradas relacionales. Las paradas ordenadas sí están en `ruta_contenedor`. No analizamos el interior de ese GeoJSON como relaciones separadas.

### 4.2 Segunda forma normal (2FN)

Casi todas las tablas usan una PK de una columna, así que no pueden tener una dependencia parcial de una PK compuesta. La excepción es `ruta_contenedor`: `orden` se registra para el par ruta/contenedor y `UNIQUE(id_ruta, orden)` evita repetir un puesto dentro de la ruta. El SQL no demuestra por sí solo todas las dependencias funcionales que pueda exigir el negocio.

### 4.3 Tercera forma normal (3FN)

Los catálogos `rol`, `zona` y `tipo_residuo` separan datos que se repetirían en muchas filas. Las tablas de hechos guardan identificadores de esos catálogos, en vez de copiar sus nombres. Hay datos derivados o duplicados a propósito: `contenedor.estado` convive con `estado_contenedor`; `incidencia.estado` convive con `historial_incidencia`; `ruta.trazado` depende de la lista de paradas. Eso exige reglas de sincronización en la API y pruebas, aunque las tablas usen FK. Por eso presentamos **un esquema diseñado para 3FN con excepciones operativas**, no una demostración formal de que cada atributo cumple 3FN.

## 5. Qué queda por validar

- [Pendiente: revisar con el equipo las dependencias funcionales de cada atributo no clave y confirmar o corregir las excepciones de 3FN.]
- [Pendiente: decidir unicidad de `usuario.id_solicitud` y del par de `vaciado` antes de tocar datos o migraciones.]
- [Pendiente: decidir el tratamiento del borrado en cascada de historiales, recepciones y movimientos de stock.]
- [Pendiente: contrastar este `schema.sql` con la base instalada en las VM y con MySQL 8.]

## 6. Fuentes y asistencia

- `sigeru/database/schema.sql`, leído sin modificar.
- `sigeru/ingenieria-software/Modelo_de_datos_y_trazabilidad_SiGeRU_v2.md` y `generadores/der_modelo_v2.py`, usados para conservar el criterio visual y distinguir cortes.
