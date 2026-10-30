---
materia: Programación Full Stack
entrega: 3ra (simplificación antes del congelamiento)
fecha: 2026-10-25
---

# Ampliación: simplificación antes del congelamiento (25/10/2026)

## 1. Por qué

Con las partes 12 a 22 el sistema había crecido bastante más de lo que el equipo iba a poder explicar en la defensa (33 pantallas, 3 APIs, 30 tablas físicas, funciones que ninguna
materia pedía). Franco Fornos lo dijo así: «quiero una web simple, pero que sea un 10». El criterio fue **sacar lo que no se iba a defender sin tocar lo que piden la letra del proyecto,
la nota de 9 puntos del profe de Programación (rutas, asignación a cuadrillas, incidencias con prioridad, usuarios) ni la seguridad**.

Esta ampliación documenta **qué se sacó, qué quedó y cómo se verificó**. No reescribe lo ya entregado (se sigue documentando por ampliación). Los documentos de las funciones sacadas
pasaron a `retiradas/` con un cartel arriba; no se borraron para que quede registro de lo que se hizo.

## 2. Qué se sacó

Se hizo en dos tandas: la primera la dejó hecha **otra sesión de trabajo que se cortó antes de dejar ningún informe** (se encontraron 39 archivos borrados del árbol, sin nota); se
revisó que no hubiera referencias colgadas y se verificó con las pruebas. La segunda se hizo en esta sesión.

| Función | Parte | Quién la sacó | Qué desapareció |
|---|---|---|---|
| Aplicación instalable y cola sin señal (PWA) | 21 | Sesión anterior | `sw.js`, `manifest.webmanifest`, íconos, `cola-offline.js`, `pendientes.js`, `pwa.js`, `sin-conexion.*`, pruebas del navegador (`tests/js/`) |
| Claves de operación (`Idempotency-Key`) | 21 | Sesión anterior | `core/Idempotencia.php` en las dos APIs, migración 016 y tabla `clave_idempotencia` |
| Reporte de gestión en PDF | 20 | Sesión anterior | `PdfSimple.php`, `ReportePdf.php`, `ReporteController.php` y el botón «Descargar PDF» |
| Ruta sugerida para el camión | 17 | Sesión anterior | `RutaSugerida.php`, pantalla `ruta-sugerida.html` y su JS |
| Participación del vecino: calificar, puntos, insignias, ranking | 14 | Sesión anterior | `Calificaciones.php`, `Participacion.php`, `Satisfaccion.php`, migraciones 012 y 013 |
| Factores de CO2 del impacto (EPA) | 18 | Sesión anterior | Migración 015 |
| Predicción de llenado de contenedores | 16 | Esta sesión | `Prediccion.php`, `GET /contenedores/prediccion`, `prediccion.js`, la columna «Se llena en», el filtro «Se llena pronto», el panel del Inicio, la sección de la ficha y `demo_historial.sql` (+ `cargar-historial-demo.cmd`) |
| Impacto ambiental completo (landing y Reportes) | 18 | Esta sesión | `Impacto.php`, `ImpactoDatos.php`, `GET /publico/impacto`, `GET /estadisticas/impacto`, sección de la landing y de Reportes |
| Guía pública «¿Dónde tiro esto?» | 19 | Esta sesión | `landing/donde-tiro.html`, su JS, `residuos.json` y los enlaces |
| Calidad / atención de las incidencias en Reportes | 14 | Esta sesión | `Resolucion.php`, `GET /estadisticas/resolucion`, la sección «Atención de las incidencias» y su CSV (el «Tiempo de resolución» general de Reportes **se queda**) |
| Correo de aviso al vecino y su preferencia | 13 | Esta sesión | `Correo::enviarAviso`, `GET/PUT /preferencias`, la casilla del Inicio y la columna `usuario.avisos_por_correo` (la campanita **se queda**) |
| Video y animación del login | — | Esta sesión | `video/acceso-loop.mp4` y `acceso-escena.js`: el login muestra ahora la imagen fija (`acceso-poster.jpg`), sin botón de pausa |
| Sección «Servicios» de la landing | — | Esta sesión | Repetía lo de «Qué es SiGeRU». Se corrigió además la frase que prometía correo al vecino |

**Lo que NO se sacó, aunque se pensó:** el modo oscuro (Fornos pidió mantenerlo), la campanita, «¿Cuándo pasa el camión por mi zona?», el Operario con recepciones y stock (lo exige Full Stack),
el mapa, el trazado por calles y todo lo del plan de rutas de la defensa.

## 3. Qué cambió en la base de datos

- **21 tablas del modelo + 6 técnicas = 27** (eran 23 + 7 = 30). Salieron `calificacion`, `movimiento_punto`, `factor_ambiental` y `clave_idempotencia`; y la columna `usuario.avisos_por_correo`.
- **Migraciones: 001 a 011, 014 y 017 a 020** (17 archivos). Se borraron 012, 013, 015 y 016 y se **editó la 011** para que no cree `avisos_por_correo`. Los números **no se renumeraron** a propósito:
  `migrar.php` guarda cuál aplicó y renumerar rompería cualquier base ya migrada. Una base vieja que ya las tenga aplicadas conserva tablas de más, inofensivas: lo limpio es `restaurar-base-demo.cmd`
  (o `restaurar-base-defensa.cmd`), que parte del `schema.sql` actual.
- **Huella de la base:** `232ba1b2758a7a3e` con el seed de pruebas y `2b25a0d53cf71e5d` con el de defensa (las anteriores, `4b14f77a9a5dd106` y `9f0bbccdb9730305`, ya no valen). El script es `generadores/extras-partes/huella.sh`.

## 4. Pruebas (todas en verde)

| | Antes (24/10) | Al empezar hoy (después del primer recorte) | Ahora |
|---|---|---|---|
| Unitarias (sin base) | 867 | 743 | **671** |
| Por HTTP | 1197 | 1025 + 9 fallas por el correo | **1000** con 0 fallas y 1 omitida |
| En el navegador (cola y service worker) | 36 | — | 0 (se sacaron con la función) |

- Las 9 fallas de la corrida de la mañana eran **del arranque de Apache y no del código**: con el arranque común no está `SIGERU_MAIL_MODE=archivo` y el alta de un usuario con rol Cuadrilla da 503
  (está documentado en el `README.md`). Con Apache arrancado con esa variable pasan todas.
- La omitida es la del trazado por calles: pide arrancar Apache con `SIGERU_TRAZADO_MODO=recta` para no depender de servicios externos.
- Lo que se hizo para sacar cada función **junto con su prueba**: no se borró ninguna prueba de algo que sigue existiendo; las pruebas de la campanita (`correr-tests-notificaciones.php`, `unitarias-notificaciones.php`) se
  recortaron solo en lo del correo y la preferencia.
- Se revisaron a mano en el navegador integrado: login, panel del administrador, Contenedores, ficha de un contenedor, Reportes y landing, sin errores de consola ni pedidos fallidos.
- **Tamaño:** de ~13,8 mil a ~13,4 mil líneas en `frontend-usuarios`, de 1720 a 1363 en `landing`, de 5368 a 4925 en `api-gestion` y de 6910 a 6424 en `tests`.

## 5. Decisiones que se tomaron sin el equipo («SIN Fornos»), todas reversibles

1. Sacar la **calidad del servicio por zona y cuadrilla** de Reportes (era el reemplazo de las calificaciones). Si se prefiere, se vuelve a poner desde la copia de seguridad (`Resolucion.php` nunca llegó a commitearse, no está en Git).
2. Sacar **todo** el impacto ambiental, también las dos cifras (kilos y % reciclable) de la landing.
3. Sacar el **correo de aviso al vecino** (la campanita queda). El correo sigue existiendo para la bienvenida y los enlaces de acceso.
4. Reemplazar el **video del login** por su imagen fija. El video lo había hecho Fornos: está en la copia de seguridad (ver más abajo).
5. No renumerar las migraciones.

## 6. Cómo volver atrás

- **Todo lo sacado de las partes 12 a 22 está en el último commit del repositorio** (`e0cb351`) mientras nadie lo commitee: `git checkout HEAD -- <ruta>` devuelve cualquier archivo. **Ojo: si se commitea
  el estado actual, esa red de seguridad se pierde.** Por eso se hizo además una copia completa: `respaldo-partes/antes-de-simplificar-20261025.zip` (el árbol de `sigeru/` sin `.git`, ni `tmp`, ni la carpeta de S.O., con el video del login)
  y `respaldo-bd/sigeru-antes-de-simplificar-20261025.sql`.
- Las funciones devueltas tendrían que volver con sus migraciones y sus pruebas; no se recomienda hacerlo antes de la defensa.

## 7. Lo que sigue en pie y se podría sacar (decide el equipo)

- «¿Cuándo pasa el camión por mi zona?» (parte 15, `usuario.id_zona`, `ProximoPasaje.php`).
- Los avisos a cuadrillas, administradores y operarios de la campanita (quedan: son baratos).
- Capas y filtros del mapa.
- Páginas de error propias y estados vacíos con dibujo.
- Quedan 31 pantallas, 3 APIs y 5 aplicaciones: es el núcleo que piden las materias. Más simple que esto ya es recortar lo obligatorio.

## 8. Lo que no se verificó

- Nada se probó en la **VM Rocky** (PHP 8.0, `mod_rewrite`, etc.): lo hace el equipo.
- No se corrió la suite con el trazado en modo recta.
- Firefox y Safari: solo el navegador integrado (Chromium).
- Los documentos de **Ingeniería de Software** ya tienen el recorte (adenda, sección 11), pero los **PDF del integrador de la 3ra no se regeneraron**: hay que correr `doc_integrador.py` y
  `verificar_integrador.py` antes de entregar.
