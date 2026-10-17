# SiGeRU — revisión del modelo de datos y trazabilidad

**Fecha:** 12/10/2026 · **Paso:** 4, Ingeniería de Software · **Estado:** auditoría del modelo lógico vigente; decisiones de migración pendientes.  
**Cortes:** DER y pasaje a 3FN de julio; adenda de maquinaria del 09/09; `schema.sql` más migraciones 001–003 de octubre. La entrega congelada del 14/09 permanece intacta.

## 1. Qué representa cada artefacto

| Artefacto | Corte y propósito | Alcance comprobado |
|---|---|---|
| `sigeru/ingenieria-software/DER_SiGeRU.dia` | Modelo conceptual de julio | 14 entidades y 17 relaciones. La exportación original a PNG resulta ilegible al tamaño generado; no representa maquinaria ni historial de incidencias. |
| `generadores/doc1_pasaje_tablas.py` y `Pasaje_a_Tablas_3FN_SiGeRU.pdf` | Pasaje lógico de julio | 14 tablas y 17 FK declaradas. Sus reglas son históricas; varias no se aplicaron literalmente en el DDL posterior. |
| `generadores/doc13_adenda_maquinaria.py` | Ampliación del 09/09 | Agrega `maquinaria` y su relación obligatoria con `centro_acopio`: 15 entidades / 18 relaciones del segundo avance. |
| `sigeru/database/schema.sql`, migraciones 001–003 | Esquema físico de trabajo al 12/10 | 16 tablas funcionales, 20 FK y 2 tablas técnicas (`migracion`, `intento_login`): 18 tablas físicas en total. `historial_incidencia` y los estados nuevos son posteriores a la segunda entrega. |
| `generadores/der_modelo_v2.py` | Vista revisada del modelo lógico actual | Produce tres páginas PDF y tres PNG de las 20 FK reales, con códigos R01–R20. No presenta las brechas funcionales como si ya estuvieran modeladas. |

La **perspectiva conceptual** expresa recursos y reglas del dominio, independientemente de MySQL. El **modelo lógico vigente** son las 16 tablas funcionales y sus FK. El **esquema físico** añade `ENUM`, tipos, índices, triggers y dos tablas técnicas. La correspondencia «una clase = una tabla» fue una decisión del equipo, no una regla necesaria de normalización; el historial de incidencias y las tablas técnicas ya muestran su límite.

**DER para el integrador:** [PDF de tres vistas](DER_Logico_SiGeRU_v2.pdf) y [vista 1](DER_Logico_SiGeRU_v2_1.png), [vista 2](DER_Logico_SiGeRU_v2_2.png), [vista 3](DER_Logico_SiGeRU_v2_3.png). Son vistas del **modelo lógico implementado**, no un DER conceptual validado por una intendencia. Se incorporarán al integrador en el paso 11.

## 2. Inventario de entidades, claves y unicidad

`PK` significa clave primaria. `UQ` identifica unicidad **real en el SQL**, no la prometida por el pasaje a 3FN. Todas las tablas tienen PK; las claves sustitutas son autoincrementales salvo `migracion.nombre`.

| Tabla | PK | UQ adicional en SQL | Papel y observación |
|---|---|---|---|
| `rol` | `id_rol` | `nombre` | Catálogo de permisos. |
| `solicitud_registro` | `id_solicitud` | — | Solicitudes; el email puede repetirse entre solicitudes. |
| `usuario` | `id_usuario` | `email` | `id_solicitud` **no** es UQ aunque el documento de 3FN lo afirma. |
| `cuadrilla` | `id_cuadrilla` | `nombre` | Equipo operativo. |
| `centro_acopio` | `id_centro` | — | Centro, capacidad y estado. |
| `zona` | `id_zona` | `nombre` | División territorial. |
| `tipo_residuo` | `id_tipo_residuo` | `nombre` | Catálogo de tipos. |
| `contenedor` | `id_contenedor` | `codigo` | Solo contenedor con dirección, zona y tipo obligatorios; no identifica unidades de repuesto sin instalar. |
| `estado_contenedor` | `id_registro` | — | Asientos de estado, con trigger contra UPDATE/DELETE directos. |
| `camion` | `id_camion` | `matricula` | Flota. |
| `maquinaria` | `id_maquinaria` | `codigo` | Inventario de equipos asociados obligatoriamente a un centro. |
| `ruta` | `id_ruta` | — | Recorrido previsto en una zona. |
| `recoleccion` | `id_recoleccion` | — | Ejecución de ruta con camión y cuadrilla. |
| `vaciado` | `id_vaciado` | — | Asociación entre recolección y contenedor. El par `(id_recoleccion,id_contenedor)` **no** es UQ en SQL aunque el pasaje a 3FN lo afirma. |
| `incidencia` | `id_incidencia` | — | Reporte asociado a vecino, contenedor y quizá cuadrilla. |
| `historial_incidencia` | `id_registro` | — | Asientos de cambio; se agregó en octubre. |
| `migracion` | `nombre` | — | Técnica: controla DDL aplicado, fuera del dominio. |
| `intento_login` | `id_intento` | — | Técnica: intento por email/IP/fecha, sin FK a usuario porque también registra emails inexistentes. |

## 3. Matriz completa de relaciones y borrado

En la columna de cardinalidad se lee **hijos por un padre / padres por un hijo**. El mínimo 0 en el lado hijo significa que el DDL no obliga a que todo padre tenga al menos uno. `1` del lado padre corresponde a FK `NOT NULL`; `0..1`, a FK nullable. Si la FK no es UQ, el padre puede tener muchos hijos. `R` = `RESTRICT/NO ACTION` implícito de InnoDB al no escribir `ON DELETE`; `C` = coincide con la regla vigente; `D` = diferencia histórica justificada por bajas lógicas; `A` = brecha que requiere decisión/corrección. Ninguna FK define `ON UPDATE`; los identificadores sustitutos se consideran inmutables.

| ID | Relación hijo → padre | Cardinalidad actual | FK y nulidad | Borrado SQL | Estado y juicio |
|---|---|---|---|---|---|
| R01 | Usuario → Rol | 0..N / 1 | `usuario.id_rol` NOT NULL | R | C: usuario con rol existente. |
| R02 | Usuario → SolicitudRegistro | **0..N / 0..1** | `usuario.id_solicitud` NULL, sin UQ | R | **A:** el conceptual pide 0..1 usuario por solicitud; falta UQ. El documento decía SET NULL. |
| R03 | Usuario → Cuadrilla | 0..N / 0..1 | `usuario.id_cuadrilla` NULL | R | D: el documento decía SET NULL; la desvinculación puede ser UPDATE explícito y la baja de cuadrilla es lógica. |
| R04 | Zona → CentroAcopio | 0..N / 0..1 | `zona.id_centro` NULL | R | D: mismo criterio; el documento decía SET NULL. |
| R05 | Contenedor → Zona | 0..N / 1 | `contenedor.id_zona` NOT NULL | R | C: ubicación zonal obligatoria. |
| R06 | Contenedor → TipoResiduo | 0..N / 1 | `contenedor.id_tipo_residuo` NOT NULL | R | C: tipo obligatorio. |
| R07 | EstadoContenedor → Contenedor | 0..N / 1 | `estado_contenedor.id_contenedor` NOT NULL | CASCADE | **A:** el borrado del padre destruye historial aun con trigger de solo agregado. |
| R08 | Maquinaria → CentroAcopio | 0..N / 1 | `maquinaria.id_centro` NOT NULL | R | C: asociación exigida por la adenda; baja del centro exige reasignar o conservarlo inactivo. |
| R09 | Ruta → Zona | 0..N / 1 | `ruta.id_zona` NOT NULL | R | C. |
| R10 | Recolección → Ruta | 0..N / 1 | `recoleccion.id_ruta` NOT NULL | R | C. |
| R11 | Recolección → Camión | 0..N / 1 | `recoleccion.id_camion` NOT NULL | R | C. |
| R12 | Recolección → Cuadrilla | 0..N / 1 | `recoleccion.id_cuadrilla` NOT NULL | R | C. |
| R13 | Recolección → CentroAcopio | 0..N / 0..1 | `recoleccion.id_centro` NULL | R | D: el documento decía SET NULL; conservar el centro inactivo preserva dónde se descargó. |
| R14 | Vaciado → Recolección | 0..N / 1 | `vaciado.id_recoleccion` NOT NULL | CASCADE | **A:** borrado físico de una recolección elimina hechos históricos; falta además UQ del par de R14/R15. |
| R15 | Vaciado → Contenedor | 0..N / 1 | `vaciado.id_contenedor` NOT NULL | R | C en FK; la unicidad del par sigue abierta. |
| R16 | Incidencia → Usuario | 0..N / 1 | `incidencia.id_usuario` NOT NULL | R | C para identidad; el rol «vecino» se controla en API, no con FK. |
| R17 | Incidencia → Contenedor | 0..N / 1 | `incidencia.id_contenedor` NOT NULL | R | C. |
| R18 | Incidencia → Cuadrilla | 0..N / 0..1 | `incidencia.id_cuadrilla` NULL | R | D: el documento decía SET NULL; baja lógica y desasignación explícita evitan perder el vínculo histórico. |
| R19 | HistorialIncidencia → Incidencia | 0..N / 1 | `historial_incidencia.id_incidencia` NOT NULL | CASCADE | **A:** al borrar la incidencia se borra el historial pese a RNF-016. |
| R20 | HistorialIncidencia → Usuario | 0..N / 1 | `historial_incidencia.id_usuario` NOT NULL | R | C: autor existente y conservado. |

**Decisión provisional de modelado:** mantener bajas lógicas y preferir `RESTRICT` para hechos históricos antes que borrados físicos automáticos. No se aplica todavía una migración: las pruebas HTTP actuales borran incidencias y contenedores de prueba apoyándose en `CASCADE`, y hay que adaptar su limpieza y verificar datos existentes antes de cambiar la integridad referencial. Las cinco diferencias `SET NULL` del pasaje de julio se resuelven como **política física RESTRICT + desvinculación explícita**, sujeta a validación con el equipo. La cardinalidad R02 no queda resuelta por esa política.

## 4. Reglas de negocio → relación → tabla/restricción

| Regla | Fuente/objetivo | Relación o tabla | Restricción actual | Resultado |
|---|---|---|---|---|
| BN-01: cada usuario tiene un rol válido | RF-007/008, RNF-002 | R01 | FK NOT NULL + `rol.nombre` UQ | Parcial: permisos concretos viven en API. |
| BN-02: una solicitud aprobada origina a lo sumo un usuario | Documento 3FN, CU-005 | R02 | FK NULL, **sin UQ** | **Brecha:** se pueden crear varios usuarios desde una solicitud. |
| BN-03: solo vecino registrado informa incidencias | RF-217, A-05 | R16 | FK usuario existente | Parcial: la FK no verifica rol ni sesión; la API sí debe hacerlo. |
| BN-04: contenedor en una zona y con tipo | RF-017/019 | R05–R06 | FKs NOT NULL | Cubierta para contenedor instalado. |
| BN-05: registrar y consultar repuestos sin instalar | RF-026/027/102/103/191/192 | Ninguna relación específica | `contenedor` exige dirección y zona; no hay indicador de repuesto ni cantidad | **Brecha funcional:** la mera presencia de `contenedor` no demuestra stock. Requiere decisión sobre unidades individuales o cantidades. |
| BN-06: centro gestiona ciertos tipos de residuo | RF-089 | CentroAcopio–TipoResiduo | No existe FK ni tabla de asociación | **Brecha:** requiere cardinalidad y catálogo operativo definidos. |
| BN-07: una máquina pertenece a un centro | RF-246/248 | R08 | FK NOT NULL + código UQ | Cubierta; centro inactivo puede seguir referenciado. |
| BN-08: un contenedor se vacía una vez por recolección | Pasaje 3FN §3 | R14–R15 | Dos FK, **sin UQ del par** | **Brecha:** SQL permite duplicados. Confirmar primero si dos vaciados en una misma recolección son imposibles. |
| BN-09: todo cambio de estado del contenedor queda en historial | RF-233–236, RNF-016 | R07 + `contenedor.estado` | Tabla y triggers; API mantiene el vigente | Parcial: SQL directo puede desincronizar estado actual e historial; CASCADE puede borrar el historial. |
| BN-10: cada cambio de incidencia tiene autor y fecha | RF-232, RNF-017 | R19–R20 | FKs NOT NULL, fecha, trigger contra UPDATE/DELETE | Parcial por CASCADE del padre. |
| BN-11: incidencia asignada a cuadrilla activa | RF-226/227/228 | R18 | FK nullable; estado y actividad no vinculados por CHECK | Regla en API, no demostrada por el DDL. |
| BN-12: incidencia final tiene fecha de resolución | RF-231 | `incidencia` | `fecha_resolucion` NULL sin CHECK de coherencia | Regla en API; SQL acepta combinaciones inconsistentes. |
| BN-13: capacidades, pesos y volúmenes válidos | Recursos/recolección | `centro_acopio`, `contenedor`, `camion`, `vaciado` | Tipos numéricos sin CHECK positivo/no negativo | **Brecha de dominio:** validar API y considerar CHECK compatible con MySQL 8/MariaDB 10.4. |
| BN-14: coordenadas utilizables en el mapa | RF-259/260 | `centro_acopio`, `contenedor` | Latitud/longitud NULL; sin rango ni regla de par | Cobertura del mapa pendiente: decidir qué hacer con registros sin coordenadas. |
| BN-15: no borrar físicamente hechos históricos | Bajas lógicas, RNF-016 | R07, R14, R19 | CASCADE en tres relaciones | **Conflicto:** se necesita política de retención y migración tras adaptar pruebas. |
| BN-16: evitar duplicar incidencia abierta del mismo tipo/contenedor | Decisión de la API de octubre | `incidencia` | Sin UQ condicional; comprobación de aplicación | Parcial: verificar carrera concurrente en una prueba de integración. |

## 5. Normalización y diferencias justificadas

El pasaje de julio explica correctamente la transformación de relaciones 1:N mediante FK y la relación N:M Recolección–Contenedor mediante `vaciado`. Las 16 tablas funcionales usan atributos escalares y PK simples; **su forma es compatible con 1FN y 2FN**. La afirmación «todas en 3FN» solo puede sostenerse bajo las dependencias funcionales conocidas; faltan decisiones de dominio para repuestos y tipos por centro. La ausencia de `UNIQUE` en R02 y en el par de vaciado **no es en sí una violación de 3FN**: es una falla de cardinalidad/regla de negocio.

`contenedor.estado` repite el estado más reciente de `estado_contenedor`; `incidencia.estado` repite el último estado de `historial_incidencia`. Es una **desnormalización controlada** para consultas, válida si cada transición actualiza ambas piezas atómicamente. La API de incidencias usa transacciones, pero el DDL no impide que una escritura SQL directa las desincronice. Esa garantía debe describirse como comportamiento de aplicación y probarse, no deducirse de 3FN.

Otras diferencias físicas tienen explicación: `capacidad` conserva la unidad en comentarios SQL (litros para contenedor; kg para camión/centro), mientras el documento usa `capacidad_l`/`capacidad_kg`; `ENUM` implementa dominios cerrados; los índices sirven a consultas; `migracion` e `intento_login` son tablas operativas, fuera del DER funcional. La tabla `historial_incidencia` agregó en octubre el dato necesario para RF-232; por tanto, la frase de la ampliación de septiembre «modelo de datos sin cambios» ya no describe el estado actual.

## 6. Decisiones y trabajo necesario antes de cambiar el SQL

1. **Datos de repuestos:** definir si el stock registra cada contenedor físico o cantidades por tipo y ubicación. Modelar la entidad/estado correspondiente, su PK, FK, UQ y reglas de baja; alinear RF, CU e inventario.
2. **Tipos por centro:** confirmar si un centro recibe varios tipos y si un tipo se gestiona en varios centros. Si es N:M, diseñar la tabla de asociación con PK o UQ del par.
3. **Unicidades:** consultar duplicados antes de migrar R02 o `vaciado`; luego crear índices UQ si las reglas siguen vigentes. No crear restricciones a ciegas sobre una base con datos.
4. **Retención histórica:** decidir y documentar prohibición de borrado físico de contenedor, recolección e incidencia con historial. Adaptar limpieza de pruebas, crear migración para FK y verificar desde API y SQL.
5. **Dominio y sincronización:** añadir pruebas de capacidades, geocoordenadas, fechas, asignaciones y carreras concurrentes. Distinguir validación de API de `CHECK` real en el motor de base usado.
6. **Documentación:** cuando se acuerden las reglas, actualizar la fuente del pasaje a 3FN y el DER conceptual de la siguiente versión; incorporar diagramas y matriz al integrador en el paso 11.

Consultas previas recomendadas (solo lectura, **no ejecutadas aquí**):

```sql
SELECT id_solicitud, COUNT(*) AS total
FROM usuario WHERE id_solicitud IS NOT NULL
GROUP BY id_solicitud HAVING COUNT(*) > 1;

SELECT id_recoleccion, id_contenedor, COUNT(*) AS total
FROM vaciado GROUP BY id_recoleccion, id_contenedor
HAVING COUNT(*) > 1;
```

## 7. Verificación de este paso

Se inspeccionaron el DER de julio, el generador de 3FN, la adenda, el DDL y las migraciones. Un recuento independiente del `schema.sql` detectó **18 `CREATE TABLE` y 20 `FOREIGN KEY`**; se comprobaron nulidad, UQ, `ON DELETE` y triggers en el texto. El nuevo DER se generó desde su fuente Python y se inspeccionaron visualmente sus tres vistas. **No se modificó el esquema ni se ejecutaron migraciones sobre la base real**; por eso la matriz describe el DDL, no certifica el estado de una instancia en ejecución.
