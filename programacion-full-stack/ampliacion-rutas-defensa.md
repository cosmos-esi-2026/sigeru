---
materia: Programación Full Stack
entrega: 3ra (plan de rutas para la defensa, parte 1: datos reales, rutas con contenedores y trazado por calles)
fecha: 2026-10-23
---

# Ampliación: rutas con contenedores, datos reales de la Intendencia y trazado por calles

Origen: la nota del profe de Programación Full Stack del 22/10/2026 (ver `plan-rutas-defensa.md`, en la raíz del proyecto). Esta ampliación **no reescribe** lo entregado: suma una tabla
(`ruta_contenedor`, la 23 del modelo), columnas de trazado en `ruta`, endpoints, una pantalla y un seed de defensa. Las decisiones que tomó Claude sin poder preguntarle a Fornos están marcadas **«SIN Fornos»**.

## 1. Qué se hizo

- **Una ruta ahora lleva contenedores en orden** y, si se pidió, su recorrido por las calles. Antes era solo una etiqueta (nombre, día, hora, zona).
- **El administrador arma las rutas en un mapa** («Armar ruta»): toca contenedores, los ordena, guarda, y el servidor traza el camino por las calles. La cuadrilla no puede crear ni editar rutas (403).
- **Seed de defensa con datos reales:** 8 municipios, 800 contenedores de la Intendencia de Montevideo (100 por municipio), 40 rutas predefinidas de 20 paradas (5 por municipio) con su trazado por calles ya calculado.
- **Topes:** 100 contenedores en servicio por zona (municipio) y 25 paradas por ruta (`Limites`).

## 2. Los datos de la Intendencia

- **Fuente:** Catálogo de Datos Abiertos, «Contenedores de residuos domiciliarios - Ubicación, circuitos y frecuencia de recolección» (División Limpieza de la Intendencia de Montevideo). **Licencia de DAG de Uruguay: hay que citar la fuente.**
  Descargado el 22/10/2026, con hashes, en `sigeru/database/fuentes-intendencia/` (ver su `LEEME.md` y `INFORME-IMPORTACION.md`). Los datos se regeneran todos los días; la copia está congelada.
- **Qué trae:** 11.035 contenedores (10.457 activos) y 130 circuitos de recolección con su municipio (A, B, C, CH, D, E, F, G). Coordenadas en SIRGAS2000 / UTM 21S. **No trae dirección ni tipo de residuo ni capacidad.**
- **Conversión de coordenadas** (`generadores/utm.py`, sin librerías externas): serie de Krüger de 3er orden. Verificaciones independientes: ida y vuelta con 0,3 mm de error, el arco de meridiano integrado a mano (Simpson) coincide con la serie, y el factor de escala coincide
  con la distancia sobre el elipsoide. **Validación contra el mundo real:** los 10 contenedores del seed viejo, ubicados a mano por su dirección (por ejemplo «Plaza Independencia 750»), quedaron a entre 19 y 146 m de un contenedor real; el 99,3 % de los contenedores cae dentro del polígono de su propio circuito;
  ninguno cae fuera de Montevideo; y al dibujar las rutas sobre el mapa de OpenStreetMap la línea pasa por las calles y las paradas quedan a 22 m o menos de ella.
- **Muestra para la demostración** (`generadores/importar_contenedores_intendencia.py`, determinista): por municipio, 100 contenedores activos elegidos con k-means (k = 100) para cubrir **todo** el municipio en proporción a su densidad; después se agrupan en 5 rutas compactas de 20 y se ordenan
  (vecino más cercano + 2-opt, con el centro de acopio como extremo). **Es una muestra, no un inventario real.** SIN Fornos: 5 rutas de 20 (y no 4 de 25) por municipio.
- **Dirección de los contenedores:** «Municipio X, circuito Y, id Z» (SIN Fornos, por decisión de Fornos de no pedir calles): la latitud y la longitud son las exactas de la Intendencia. Capacidad vacía (la fuente no la trae y no se inventa). Tipo de residuo «Mixto».
- Los 10 contenedores del seed de pruebas (CNT-001 a CNT-010) se conservan con su código, estado y tipo, y toman la ubicación real más cercana; así los datos de demostración que los referencian (incidencias, historiales) siguen valiendo. Unos pocos `lleno` son **datos de demostración** (marcados en el seed).

## 3. El modelo (migración 017)

- Tabla **`ruta_contenedor`** `(id_ruta, id_contenedor, orden)`: clave primaria `(id_ruta, id_contenedor)`, único `(id_ruta, orden)`, `ON DELETE CASCADE` hacia la ruta y el contenedor (es un vínculo, no un hecho que haya que conservar).
- En **`ruta`**: `trazado` (GeoJSON), `trazado_tipo` (`calles` o `recta`), `trazado_firma`, `trazado_servicio`, `trazado_distancia_m`, `trazado_duracion_s`, `trazado_en`.
- **Un contenedor pertenece a una sola ruta activa** (lo valida la API; la base no puede expresar «una sola activa»). La **firma** es el sha256 de las coordenadas de las paradas en orden: si cambia la lista o la ubicación de un contenedor, el trazado guardado deja de estar «vigente».
- La migración se probó sobre una copia de la base anterior (hasta la 016): la estructura de `ruta` y `ruta_contenedor` quedó idéntica a la de un esquema nuevo. 23 tablas del modelo; la huella nueva se documenta en el cierre.

## 4. La API (`api-recoleccion`, solo Administrador)

| Pedido | Qué hace |
|---|---|
| `GET /rutas` | Lista con la cantidad de contenedores y el tipo de trazado |
| `GET /rutas/{id}` | La ruta con sus paradas (en orden, con ubicación) y su trazado (`vigente` o no) |
| `PUT /rutas/{id}/contenedores` | Reemplaza la lista entera, **en una transacción**: `{"contenedores": [ids en orden], "mover": false}` |
| `GET /rutas/{id}/candidatos` | Los contenedores de la zona de la ruta que se pueden agregar, y en qué otra ruta activa está cada uno |
| `POST /rutas/{id}/trazado` | Pide el camino por las calles (o cae a línea recta) y lo guarda |
| `GET /rutas/mapa?id_zona=N` | Las rutas activas de una zona con su trazado, para el mapa |
| `DELETE /rutas/{id}` | Baja lógica (queda «inactiva»); `?definitivo=1` la borra de verdad si **nunca** tuvo una recolección |

Reglas del `PUT`, todas en el servidor: contenedor existente, con ubicación, no retirado, **no en mantenimiento si es nuevo en la ruta** (los que ya estaban se conservan y la ruta los muestra como «no se recolecta»), sin repetir, hasta 25, **de la misma zona (municipio)** que la ruta,
**no en otra ruta activa** (409 que dice en cuál, con la lista de conflictos, salvo `mover: true`, que lo saca de la otra y la renumera), y la ruta (y la de origen al mover) **no puede estar en una recolección en curso**. Si la lista cambia, el trazado se borra. Si la ruta tiene recolecciones
planificadas, se le avisa a esa cuadrilla («Tu ruta cambió»). Otras reglas: a una ruta con contenedores no se le cambia la zona; reactivar una ruta no puede dejar un contenedor en dos rutas activas; dar de baja una ruta con recolecciones planificadas o en curso es 409.
Todo cambio queda en la auditoría. SIN Fornos: no hay «duplicar ruta» (un contenedor no puede estar en dos rutas activas).

## 5. El trazado por calles

- **Servicios evaluados** (22/10/2026, con 3 rutas reales de 20 paradas): Valhalla público de FOSSGIS con perfil `truck`; OSRM de FOSSGIS (perfil auto) y el OSRM de demostración (`router.project-osrm.org`, mismos resultados). **OpenRouteService queda afuera:** pide una cuenta y una clave, que tendría que crear Fornos.
  Los tres siguen las calles (la parada más lejana queda a 9-14 m de la línea). **Elegido como principal: Valhalla con perfil de camión** (el más apropiado para un recolector); OSRM de FOSSGIS y OSRM demo quedan de respaldo en cascada, y si ninguno responde, **línea recta** con un aviso en pantalla y un botón «Reintentar el trazado».
- **Límites del servidor público de Valhalla:** hasta 10 puntos por pedido, así que una ruta de 20 paradas son 3 pedidos que se solapan en un punto. Con el perfil de camión se usaron dimensiones **supuestas** de un recolector rígido (9 m de largo, 2,5 de ancho, 3,5 de alto, 16 t): **son un supuesto del equipo, no medidas.**
- **Política de uso** (FOSSGIS e.V., leída el 22/10/2026 en `fossgis.de/arbeitsgruppen/osm-server/nutzungsbedingungen`): User-Agent propio que identifica la aplicación (sin datos personales), una sola conexión, **como máximo un pedido por segundo**, URL no fijas (se cambian con `SIGERU_VALHALLA_URL`, `SIGERU_OSRM_URL`, `SIGERU_OSRM_RESPALDO_URL`),
  atribución a OpenStreetMap (el mapa la muestra, con el enlace «Reportar un error del mapa»), y no usar el servicio de forma masiva. Los servicios son voluntarios, sin garantía de disponibilidad: por eso la cascada y los 40 trazados **precalculados** (se calcularon una vez, a 1 pedido por segundo, ~6 minutos) que ya vienen en el seed.
- Una respuesta se descarta si una parada queda a más de 250 m de la línea. Se calcula **al guardar** (no en cada vista) y no se repite si la firma no cambió. `SIGERU_TRAZADO_MODO=recta` apaga los pedidos externos (las pruebas automáticas lo usan).
- Resultado de los 40 trazados del seed: 572 km en total, todos por Valhalla con perfil de camión, con la parada más lejana a 22 m o menos de la línea (el promedio de «parada más lejos» ronda los 12 m).
- **Verificado de punta a punta** con el PHP de Apache: el mismo pedido da exactamente el resultado del precálculo (12.134 m y 213 puntos para «Municipio B - Ruta 1», 3 s). El PHP de línea de comandos del proyecto (`herramientas/php`) **no** trae el certificado raíz: no se desactivó la verificación; en el servidor `sigeru-srv` PHP usa los certificados del sistema.

## 6. Las pantallas

- **Armar ruta** (`ruta-armar.html`): mapa con los contenedores de la zona (los de esta ruta numerados, los de otra ruta en gris, los llenos con símbolo y los en mantenimiento con «M»), lista de paradas con subir/bajar/quitar, buscador de candidatos con «Agregar», contador «N de 25»,
  **Ordenar por cercanía** (vecino más cercano + 2-opt a partir de la primera parada), **Guardar y trazar**, **Descartar cambios** y **Reintentar el trazado**. Agregar un contenedor que está en otra ruta pide confirmación y lo mueve al guardar. **Todo se puede hacer también desde las listas con teclado**; el mapa no es la única forma.
  Advierte al salir con cambios sin guardar. Sin internet, el fondo queda liso y avisa. Se verificó a 375 y 768 px (sin desborde) y en el navegador con datos reales.
- **Rutas:** columnas «Contenedores» (N de 25) y «Trazado», y las acciones Armar, Editar, Dar de baja y Borrar.
- **Mapa del sistema:** casilla «Rutas de la zona» (solo Administrador): dibuja las rutas del municipio elegido con colores distinguibles y una lista con sus nombres (los contenedores pasan a puntos chicos para no tapar las líneas).

## 7. Pruebas

Línea base antes de empezar: 737 unitarias y 965 HTTP, 0 omitidas. Las pruebas nuevas cubren: la conversión de coordenadas (autoprueba), los topes y su 409 en alta, mover y reactivar; el esquema y su migración; la firma (PHP contra la de Python para las 40 rutas), la decodificación de polilíneas, los tramos de 10 puntos, la cascada de servicios con respuestas
simuladas (incluidos todos los fallos), la forma del pedido de paradas, y por HTTP: permisos (401/403) de todos los pedidos nuevos, validaciones, atomicidad, conflictos y mover, rutas inactivas, recolecciones en curso, avisos a la cuadrilla, trazado en modo recta, borrado, y el endpoint del mapa. Los números finales están en el informe de la mañana (`informe-rutas-defensa.md`).

## 8. Para el servidor y para el equipo

- Cargar la defensa: `herramientas/xampp/restaurar-base-defensa.cmd` (hace una copia de la base en `respaldo-bd/` antes). Para las pruebas automáticas, `restaurar-base-demo.cmd` (el seed chico).
- En `sigeru-srv`: aplicar la migración 017 (`php database/migrar.php`) y cargar el seed de defensa si se quiere; el trazado necesita que el servidor salga a internet (si no, cae a línea recta, y los 40 trazados del seed ya están guardados).
- **No verificado:** el comportamiento con muchos administradores guardando rutas a la vez contra los servicios públicos (el límite es un pedido por segundo por servidor); el trazado desde el servidor `sigeru-srv`.
