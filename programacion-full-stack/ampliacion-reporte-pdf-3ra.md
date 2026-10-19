---
materia: Programación Full Stack
entrega: 3ra (extras, parte 20: reporte en PDF)
fecha: 2026-10-19
---

# Ampliación: reporte de gestión en PDF

Parte 20 del plan de extras. Objetivo: que el administrador **descargue un reporte en PDF real**, no solo imprima la pantalla. Sesión autónoma (Fornos pidió seguir sin responder): las decisiones marcadas «SIN Fornos» se pueden corregir.
**No hay migración ni tablas nuevas** (la huella de la base no cambia).

## 1. Decisión principal: generador de PDF propio, sin descargar nada

El plan proponía incorporar FPDF (descargado, verificado contra su hash y copiado en `vendor/`) pidiendo permiso antes de bajar nada. Como no hay nadie para responder, **no se descargó ninguna librería**: se escribió un generador mínimo propio en PHP
(`api-gestion/models/PdfSimple.php`, unas 300 líneas), que hace solo lo que el reporte necesita. Ventajas: **cero dependencias** (la página sigue sin Composer ni `vendor/`), nada de terceros que verificar ni licencias que citar, y el equipo puede explicarlo entero.
Si el equipo prefiere FPDF, es un reemplazo de la clase `PdfSimple` (los métodos son los mismos que usa `ReportePdf`) y alcanza con que Fornos autorice la descarga.

Qué hace `PdfSimple` (PDF 1.4):
- Páginas A4, texto en **Helvetica y Helvetica-Bold** (fuentes estándar del PDF, no se incrustan), con las medidas de cada letra para partir las líneas y alinear.
- **Tildes y eñe:** el texto entra en UTF-8 y se pasa a Windows-1252 (la codificación `WinAnsiEncoding` del PDF); las letras acentuadas y `¿ ¡ ñ ü` salen bien. Los paréntesis y la barra invertida se escapan.
- Títulos, párrafos con salto de línea, **tablas que siguen en la hoja siguiente repitiendo el encabezado** (una fila nunca se parte), fondo alternado.
- **El logo se dibuja como vector**: se leen los `<path>` del `logo.svg` (comandos `M`, `L`, `C`, `Z` absolutos) y se pasan a operadores de PDF; no hace falta ninguna imagen ni la extensión GD.
- **Pie «Página X de N»** en todas las páginas (se escribe al final, cuando ya se sabe cuántas son), con el título del documento y la fecha de generación.
- Flujos comprimidos con zlib (`gzcompress`) y tabla de referencias (`xref`) correcta.

## 2. El reporte

`GET /api-gestion/reportes/pdf?desde=&hasta=&zona=` — **solo Administrador** (sin sesión 401, otros roles 403, filtros inválidos 400, zona inexistente 404). Reúne **los mismos datos que muestra la pantalla de Reportes**: no recalcula nada, le pide los datos a
`EstadisticaController` (`datosIncidencias`, `datosSatisfaccion`, `datosUrgencias`, `datosImpacto`: se extrajeron de los endpoints existentes, que ahora los llaman), así las cifras **no pueden diferir** de las de la pantalla.

Contenido (`api-gestion/models/ReportePdf.php`, clase pura): portada con el logo y el período · 1. Resumen · 2. Urgencias (las 10 más graves y los contenedores llenos) · 3. Incidencias del período (por zona, tipo, prioridad y contenedores con más incidencias) ·
4. Satisfacción del vecino (por zona y por cuadrilla) · 5. Impacto ambiental (con la nota de que es una **estimación** y las fuentes de los factores) · 6. Detalle de incidencias.
**Topes de filas:** el detalle muestra **100** incidencias (avisa «Se muestran las 100 más recientes de N» y manda al CSV para ver todas), los contenedores llenos **25**, las urgencias 10 y los contenedores con más incidencias 5.
Los números se escriben como en la pantalla (punto de miles, coma decimal; menos de 48 h en horas y después en días).

**Auditoría:** el sistema solo registra solo los cambios (POST, PUT, DELETE), no las lecturas, pero una descarga de reporte es información sensible: el controlador anota una fila en `auditoria` (método `GET`, ruta `/api-gestion/reportes/pdf`, usuario, rol, código 200, IP).
La pantalla de Auditoría la muestra. Los intentos negados (401 y 403) no se anotan, igual que las demás lecturas.

## 3. La pantalla

`reportes.html`: botón **«Descargar PDF»** (junto a «Imprimir» y «Exportar»), respeta el período y la zona elegidos. Pide el archivo con `fetch`, lo guarda con el nombre `sigeru-reporte-AAAA-MM-DD.pdf` y avisa «El reporte se descargó como PDF»;
si falla (sesión vencida, período inválido), muestra el mensaje del servidor en vez de bajar un archivo roto. Mientras se genera el botón dice «Generando PDF...».

## 4. Decisiones «SIN Fornos»

1. **PDF propio en lugar de FPDF** (explicado arriba). No hubo descargas.
2. **Una sola fuente (Helvetica estándar):** no se incrustan tipografías; a cambio el archivo es chico (unos 8 KB con 5 incidencias) y se ve igual en cualquier lector. No hay negrita en los números dentro de las tablas ni gráficos (el PDF es de lectura y archivo; los gráficos están en la pantalla).
3. **Se audita la descarga como `GET`** (la tabla `auditoria` guarda el método como texto, no hizo falta migrar).
4. **Los topes** se eligieron para que el PDF no pase de unas decenas de páginas con un historial grande; el CSV sigue siendo la vía para exportar todo.
5. **Sin firma ni sello:** el PDF no está firmado digitalmente; dice «Generado el» con fecha y hora del servidor.

## 5. Pruebas

- **Unitarias (`tests/unitarias-pdf.php`):** el PDF generado se abre y se lee su texto (los flujos se descomprimen): eñe, tildes, dieresis y `¿ ¡` salen bien; paréntesis y barra invertida; `%PDF-1.4`, `%%EOF` y la tabla de referencias apuntando a cada objeto; medidas de Helvetica (3 × 667 × 10 / 1000);
  salto de línea sin pasar del ancho; 150 filas en varias páginas con `/Count` correcto, encabezado repetido en cada una, «Página X de N», ninguna fila cortada ni repetida y ningún texto fuera de los márgenes; formato de números, duraciones, períodos y plurales;
  el reporte con datos de ejemplo (secciones, cifras, topes de 100 y 25 con aviso, logo vectorial, período sin datos).
- **HTTP (`tests/correr-tests-pdf.php`):** 401/403/400/404; cabeceras (`application/pdf`, adjunto con nombre `.pdf`, sin caché); archivo válido con varias páginas; **auditoría**; y para todo el historial, septiembre de 2026, una zona y un período sin datos,
  que el texto del PDF contiene **las mismas cifras, zonas e incidencias que devuelven los endpoints de la pantalla**.
- **A mano:** el PDF del seed se abrió con `pypdf` (texto y tildes correctos, 3 páginas) y se renderizó con `pypdfium2` para mirar la maquetación (logo, tablas, pie).
