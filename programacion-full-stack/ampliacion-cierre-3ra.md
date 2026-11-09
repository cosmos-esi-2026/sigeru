---
materia: Programación Full Stack
entrega: 3ra (cierre de la mejora de la página)
fecha: 2026-10-17
---

# Ampliación: cierre de la mejora de la página

Parte 10 del plan. Junta lo que faltaba para poder decir que la mejora está cerrada y para llegar a la defensa:
accesibilidad auditada, clave de prueba que cumple la política, documentación al día, medición de lo nuevo y
guion de defensa. Hecha en la madrugada del 17/10 en la sesión autónoma.

## 1. Qué se hizo

| Tema | Resultado |
|---|---|
| Clave de prueba (política de 8 caracteres) | De `123456` a **`sigeru2026`** para los 6 usuarios del seed, **cada uno con su propio hash** bcrypt. Cambiaron `datos_prueba.sql`, la base viva, `correr-tests.php`, el README y `docker/LEEME.md`. La **huella de la base** pasó de `03f328b99a135252` a **`60e98084441a8c3b`** (verificada contra una carga limpia del seed en otra base, tabla por tabla) |
| Accesibilidad (H15) | Auditoría (ver 2) y seis arreglos chicos; prueba estática nueva sobre las 23 páginas |
| README / Docker (H12b) | Cifras de pruebas al día, «tres APIs», instrucciones del modo de correo para correr las pruebas, workflow con `SIGERU_MAIL_MODE=archivo` |
| Medición de lo nuevo | `ingenieria-software/Adenda_Alcance_3ra_Entrega_SiGeRU.md`: +97 PF sin ajustar (326 en total, 359 ajustados) y la lista de lo que se hizo distinto o no se hizo |
| Guion de defensa | `programacion-full-stack/guion-de-defensa-3ra.md`: demo de 15 minutos por rol, preguntas probables con respuesta honesta, plan B y qué no decir |

## 2. Auditoría de accesibilidad

Se escribió un recorrido automático que se corrió en el navegador sobre **unas 30 pantallas con los roles que corresponden**
(administrador, vecino, cuadrilla y operario; a 375 y 1000 px), incluida la landing. Mira: idioma y título, un solo
`h1` y niveles de encabezado sin saltos, un solo `main`, enlace para saltar al contenido, imágenes con `alt`, SVG
decorativos ocultos, **etiqueta de cada control**, nombre accesible de cada botón y enlace, ids repetidos, tablas
con `th`, **contraste de texto** (4,5:1; 3:1 para texto grande) y **tamaño de los objetivos táctiles** (24 px,
criterio 2.5.8 de WCAG 2.2).

Lo que apareció y se arregló:

| Hallazgo | Arreglo |
|---|---|
| Botones de ordenar columnas y enlaces de las tablas de 17 px de alto | `min-height` y relleno: 24 px o más |
| Enlaces «Volver al inicio» y «¿Olvidaste tu contraseña?» de 21 px | Relleno vertical |
| Enlaces de las fichas de detalle de 20 px | Relleno vertical |
| Atribución de Leaflet con contraste 3,9:1 (color por defecto de la librería) | Color más oscuro en los dos mapas |
| Enlaces del cuerpo de la landing con el azul por defecto del navegador | Verde oscuro de la marca |
| Mensaje de error de las fichas con un `h3` bajo el `h1` (salto de nivel) | `h2` |

Queda, y es aceptable: las **casillas del filtro del mapa** miden 18 px de ancho pero están dentro de una
etiqueta de 44 px de alto que se puede tocar entera. **No se auditó** con lector de pantalla real ni con un
verificador externo (axe, Lighthouse), ni el orden de tabulación a mano, ni la vista impresa.

La prueba nueva (`tests/unitarias-accesibilidad.php`) vuelve a comprobar en cada corrida lo que se puede ver
en el HTML estático de las 23 páginas (idioma, título, `h1`, `main`, etiquetas, nombres, ids, enlace de salto).
Lo dinámico (contraste, tamaños, lo que arma el JavaScript) solo se audita en el navegador.

## 3. Revisión de seguridad de las partes 7 a 10

- Todas las rutas nuevas niegan por defecto y se prueban con los cuatro roles y sin sesión. El único recurso sin
  sesión (`/publico/mapa`) devuelve campos fijos, con una prueba que falla si aparece cualquier otro.
- Consultas preparadas en todo; la búsqueda escapa `%` y `_`; el CSV neutraliza fórmulas y duplica comillas;
  `innerHTML` siempre con escape.
- `GET /api-usuarios/` informa el modo del correo **solo si la petición viene de la propia máquina**. Detrás de un
  proxy en la misma máquina, toda petición parecería local: dato de bajo valor, pero anotado.
- El modo `SIGERU_MAIL_MODE=archivo` solo vale con la URL pública en `localhost`: no se puede dejar encendido por
  error en un servidor.
- Hay un riesgo que no se resolvió: **no hay límite de pedidos** en los endpoints públicos ni en
  `recuperar-clave` por dirección (sí hay freno por cuenta).

## 4. Estado de las pruebas al cerrar

**365 unitarias** y **515 por HTTP** (343 y 484 al cerrar la P10, antes de la foto y las rutas) (con el correo en modo archivo), ambas sin fallas. Con el arranque común
(sin ese modo) las de HTTP omiten dos bloques y lo dicen en el resumen.

## 5. Lo que queda abierto

- (Hechas después del cierre, en la misma sesión: la foto de la incidencia y la pantalla de rutas; ver `ampliacion-fotos-y-rutas-3ra.md`.)
- Probar el envío real por Gmail, el fondo de calles del mapa con internet, la vista impresa y el aspecto visual
  (esta sesión no pudo sacar capturas).
- Decisiones que tomé sin Fornos: están marcadas «SIN Fornos» en cada ampliación.
- Commit y push: **nada está subido** (hay dos commits locales sin pushear y muchos cambios sin commitear).
