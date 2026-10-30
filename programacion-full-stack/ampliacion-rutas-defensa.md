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


## 9. Asignación de rutas a cuadrillas (bloque 6, 23/10/2026)

El administrador asigna rutas a cuadrillas desde **Asignación** (`asignacion.html`): elige el día y ve las rutas ordenadas por prioridad (la prioridad de una ruta sale de sus contenedores: desbordado = alta,
lleno = media, y los rotos o en mantenimiento no cuentan porque no se recolectan), con una **sugerencia** de cuadrilla y camión (clase pura `Asignacion::sugerir`, solo lee). Confirmar crea las recolecciones **todas
o ninguna** (`POST /recolecciones/lote`). Una cuadrilla o un camión no puede tener dos recolecciones el mismo día. La cuadrilla ve **solo sus rutas** en el mapa («Mis rutas») y en el detalle de su recolección
(`recorrido`), y en el servidor solo ve los contenedores de esas rutas (`DE_LA_CUADRILLA`); desde ahí registra el vaciado con un botón por parada. El vaciado solo acepta contenedores de la ruta de la recolección.
Matriz de permisos: sección 23 de `ciberseguridad/ampliacion-matriz-de-permisos-3ra.md`.

## 10. Incidencias con prioridad y mantenimiento (bloque 7, 23/10/2026; migración 018)

- Tipo nuevo **«lleno»** (el contenedor está lleno pero todavía no desborda). Prioridad por defecto según el tipo (`FlujoIncidencia::PRIORIDAD_POR_TIPO`): desbordado alta, lleno media, roto media, el resto baja. **SIN Fornos:** los valores salen de lo que dijo el profe (desbordado más urgente que lleno); el administrador la puede cambiar.
- **Lo roto no se recolecta:** no se asigna a una cuadrilla; el administrador lo **deriva a mantenimiento** (`POST /incidencias/{id}/derivacion`, con una nota de a quién se llamó). El contenedor queda en «mantenimiento» y sale de las rutas; al resolverse vuelve a «disponible». La derivación se guarda en `incidencia.derivada_en`/`derivada_nota` y en el historial de solo agregado. Los administradores reciben un aviso cuando se informa uno roto.
- La **cuadrilla también informa** en terreno, solo sobre los contenedores de sus rutas.
- Pantallas: «Informar un problema» con la tarjeta «Contenedor lleno» (y el menú también para la Cuadrilla) y, en el detalle de la incidencia, el formulario «Derivar a mantenimiento» para el administrador.
- Datos de demostración (`datos_defensa.sql`): INC-0001 desbordado alta, INC-0004 lleno media, INC-0003 roto ya derivada (CNT-005 en mantenimiento). Pruebas: `unitarias-incidencias.php` y `correr-tests-incidencias-mant.php`.


## 11. Usuarios y cuadrillas (bloque 8, 23/10/2026; migración 019)

- **El bug del punto 9:** `api-usuarios` nunca guardaba `usuario.id_cuadrilla` (solo `id_centro`): un usuario Cuadrilla creado desde la pantalla no veía ninguna ruta. Ahora `usuarios.html` muestra el campo **«Cuadrilla»** (obligatorio) al elegir el rol Cuadrilla, la API lo valida (existe y está activa) y lo limpia si el rol cambia; la lista muestra y filtra por cuadrilla y el CSV la incluye.
- **Pantalla «Cuadrillas»** (`cuadrillas.html`, menú del Administrador) y `POST/PUT/DELETE /api-recoleccion/cuadrillas`: crear, renombrar, cambiar el turno, ver integrantes y recolecciones abiertas, dar de baja (bloqueada si tiene trabajo abierto). `models/Cuadrilla.php` es copia idéntica en `api-gestion` (una prueba lo exige).
- **Nadie se hace cuadrilla solo:** el registro público sigue creando solo vecinos (prueba que ignora `rol` e `id_cuadrilla`). En el login, «¿Sos de una cuadrilla de recolección? Pedí acceso» lleva a `acceso-cuadrilla.html`, que deja una solicitud `tipo_cuenta = 'cuadrilla'` con lo que la persona dice (`cuadrilla_indicada`). En «Solicitudes» el administrador la ve marcada («Pide ser: Cuadrilla («...»)»), **elige la cuadrilla real** y aprueba (o rechaza). **SIN Fornos:** no se pidió contraseña en el formulario; la persona la crea con el enlace que le llega por correo al aprobar, igual que un usuario dado de alta por el administrador.
- Pruebas: `correr-tests-cuadrillas.php` (60 comprobaciones) y las unitarias de menú y permisos.


## 12. Escala y fluidez (bloque 4, 23/10/2026): medido con el seed de defensa (800 contenedores, 40 rutas con trazado)

Medido en esta PC (Apache y MariaDB portables, `curl` con `Accept-Encoding: gzip` y el navegador integrado). **No se midió en la PC más floja de Fornos** (queda para él: abrir `mapa.html` y la portada ahí).

| Qué | Antes | Después |
|---|---|---|
| `GET /publico/mapa` (portada, 800 puntos) | 158.604 B | **17.708 B** comprimido |
| `GET /contenedores` (todos) | 216.562 B | **19.879 B** |
| `GET /contenedores/prediccion` | 305.932 B | **13.331 B** |
| `GET /rutas/mapa` (40 trazados) | 289.975 B, 0,4 a 0,5 s | **68.866 B, 0,04 s** |
| Portada completa en el navegador | — | carga en 286 ms, 803 marcadores, 227 KB en total |
| Mapa del sistema (Administrador) | — | 800 marcadores, 20 KB de datos; redibujar los 800 al cambiar un filtro: 0,37 s |

- **4.1 Compresión:** `mod_deflate` y `mod_filter` prendidos en `herramientas/xampp/apache/conf/httpd.conf`, y la regla `AddOutputFilterByType DEFLATE` (JSON, CSS, JS, HTML, SVG) en `herramientas/plantillas/sigeru.conf.plantilla` (que `iniciar-xampp.cmd` copia a `sigeru.conf`). **En el servidor `sigeru-srv` hay que hacer lo mismo:** `sudo a2enmod deflate` y reiniciar Apache (el tipo `application/json` entra en la configuración por defecto de Ubuntu, `/etc/apache2/mods-available/deflate.conf`; verificar con `curl -H 'Accept-Encoding: gzip' -I`). El archivo `novedades-3ra-para-el-servidor.md` ya no está en la carpeta (Fornos rehizo la de S.O.): esta nota es la que queda.
- **Una consulta lenta encontrada y arreglada:** `GET /rutas/mapa` ordenaba las filas con la columna `trazado` (LONGTEXT) adentro y MariaDB las ordena en disco: 0,4 s. Ahora el orden se hace sin ese texto y los trazados se piden aparte (`Ruta::paraMapa`): 0,007 s.
- **4.3 Inicio:** el panel del Administrador ya no baja todos los contenedores ni todos los usuarios: pide `?por_pagina=1` y usa `meta.total`, y la tabla de llenos trae los primeros 10 (`?estado=lleno&por_pagina=10`) con la nota «Se muestran los primeros N de T».
- **4.4 «Informar un problema»:** las sugerencias del código salen de una búsqueda de a 10 mientras se escribe (`/contenedores?q=&por_pagina=10`); los «cercanos» usan los puntos públicos del mapa, que se piden recién al tocar «Usar mi ubicación». **La posición sigue sin salir del teléfono** (la distancia se calcula en el navegador), así que el texto de privacidad no cambia.
- **4.5 Detalle de recolección:** las sugerencias del vaciado son las paradas de esa ruta (bloque 6).
- **No hizo falta (medido):** parámetro `?municipio=` en `/publico/mapa` (17 KB y 286 ms alcanzan), dibujo en canvas o racimos (800 marcadores, bajo el tope de 2.000), paginar la tabla del mapa (0,37 s al redibujar). Si algún día se cargaran los ~11.700 contenedores, esas tres cosas pasan a ser necesarias: está dicho en `plan-rutas-defensa.md`.
- **4.7 Índices:** `contenedor` ya tiene índices por `estado`, `id_zona` y `codigo`; `incidencia` por `estado`; las consultas medidas (predicción, ruta sugerida, asignación) responden en menos de 0,06 s con 800 contenedores. No se agregó ninguno (no hay consultas por latitud/longitud en SQL).

## 13. Parte 26 (24/10/2026): arreglos antes del congelamiento

Plan en `plan-arreglos-congelamiento.md` (raíz). Las decisiones D1 a D6 las tomó la sesión con la recomendación del plan, sin respuesta de Fornos: están marcadas **«SIN Fornos»**.

- **13.1 La sugerencia de asignación respeta el municipio (H-04; migración 020).** `cuadrilla.id_zona` (opcional, clave foránea a `zona`): la migración vincula sola las cuadrillas llamadas «Cuadrilla Municipio X» con la zona «Municipio X» y es idempotente (probada sobre la base de defensa de antes). `Asignacion::sugerir` recorre las rutas por prioridad y le busca a cada una la cuadrilla libre de su municipio; si no hay, una libre sin municipio; las rutas que quedan sin cuadrilla reciben, por orden, las que sobran de otros municipios, marcadas `fuera_de_zona`. Las propuestas salen en el orden de prioridad de las rutas y los camiones se reparten en ese mismo orden. El turno (matutino desde las 05:00, vespertino desde las 13:00, nocturno desde las 21:00) solo agrega el aviso `turno_distinto`. Sin ningún municipio empareja por posición, como antes. «Cuadrillas» tiene la columna y el campo «Municipio»; «Asignación» ordena las cuadrillas con la del municipio primero, avisa «Fuera de su municipio» y «Turno … la ruta sale de …», y vuelve a calcular los avisos cuando el administrador cambia la cuadrilla o la hora.
  - **SIN Fornos:** D1 el municipio es opcional (una cuadrilla sin municipio cubre cualquiera) · D2 si sobran cuadrillas se ofrecen fuera de su municipio **con aviso** · D3 el turno **solo avisa**. El seed de defensa dejó las ocho cuadrillas matutinas (las 40 rutas salen entre 06:00 y 09:30): antes eran matutino, vespertino y nocturno sin relación con la hora de las rutas, y habrían marcado «turno distinto» en cinco de los ocho municipios.
  - **Visto en el navegador** (seed de defensa): el lunes 16/11 la sugerencia propone 6 rutas, 5 con la cuadrilla de su municipio y una con la de otro (marcada y avisada en la nota). Hay 6 camiones libres: con 8 cuadrillas y 10 rutas el día, la sugerencia queda corta por camiones, no por cuadrillas.
- **13.2 Compatibilidad con el PHP del servidor y hora de Uruguay.** `array_is_list()` (PHP 8.1) se usaba en `RutaParadas` y `Asignacion`: Rocky Linux 9 trae PHP 8.0 por defecto, y con 8.0 «Armar ruta» y «Asignación» fallaban de entrada. Se reemplazó y `tests/unitarias-compatibilidad.php` impide que vuelva cualquier función o sintaxis posterior a PHP 8.0 (revisa los 100+ archivos con el analizador de PHP, ignorando comentarios y textos). La zona horaria de PHP la fija ahora el código (`America/Montevideo` en `Response::manejarErrores`) y la sesión de la base también (`SET time_zone = '-03:00'`, variable `SIGERU_DB_TZ`): el `php.ini` de XAMPP decía `Europe/Berlin`. Casi todo el código usa `NOW()` de la base, así que el efecto real era pequeño (el nombre del PDF cerca de medianoche); lo que importa es que `NOW()` no dependa de la zona del servidor de base de datos en las VM.
  - **Para el servidor Rocky:** PHP 8.0 o superior con `pdo_mysql`, `mbstring`, `gd`, `openssl`, `curl` y `fileinfo`; Apache con `mod_rewrite` y `mod_deflate`; `php database/migrar.php` (migraciones 001 a 020); en las dos páginas que llevan `og:image` (`landing/index.html` y `landing/donde-tiro.html`) reemplazar la ruta relativa por la dirección completa con el dominio real (las vistas previas al compartir piden dirección absoluta); la base de datos de las VM tiene que estar en la hora de Uruguay o dejar la variable `SIGERU_DB_TZ`. **No verificado:** correr todo esto en la VM.
- **13.3 Hallazgos de la evaluación heurística.** H-05 (mitigado): la dirección del seed ya no repite el id (está en el código `MVD-NNN`); la ficha del contenedor y el detalle del reclamo tienen «Ver en el mapa» (`mapa.html?contenedor=CÓDIGO` centra el mapa y abre la ficha). La calle sigue sin existir: los datos abiertos no la traen y no se geocodificó. H-06: la descripción de una incidencia es opcional salvo en «Otro»; si falta se guarda el nombre del tipo (`FlujoIncidencia::TEXTO_TIPO`). H-07: una recolección planificada con fecha pasada sale con `vencida` en la lista y en el detalle, calculado por el servidor. H-08: el impacto ambiental, el CSV y el PDF respetan el filtro de zona; los kilos recibidos en centros van en `null` con zona (un centro recibe de varias zonas). H-09: las acciones de las filas con cinco botones o con botones de texto tienen un piso de ancho (`.acciones-fila-ancha`; `:has` para tres o más) y «Rutas» se compacta entre 769 y 1200 px: la fila bajó de 253 a 113 px y entra sin barra a 800 px. **Auditoría de 17 pantallas con tablas a 768, 800, 900, 1024 y 1366 px: ninguna desborda la página.** Quedan con barra interna, por tener muchas columnas, «Usuarios» y «Maquinaria» en tabletas y «Rutas» a 768 y 1024 px (15 px).
  - B2: se borró `landing/js/formularios.js` (ninguna página de la landing lo cargaba) y se lo sacó de la lista de copias compartidas de `unitarias.php`. Aviso: el plan decía que «ninguna prueba lo mira» y no era cierto: una prueba exigía que fuera idéntico al del frontend.
- **13.4 Números.** **867 unitarias y 1197 HTTP en verde, 0 omitidas** (antes 835 y 1167). Mutación: 10 roturas de las unitarias y 6 de HTTP, todas detectadas. Huella de la base con el seed de pruebas **`4b14f77a9a5dd106`** (igual después de la suite que con un seed recién cargado); con el seed de defensa **`9f0bbccdb9730305`**.
