---
materia: Programación Full Stack
entrega: 3ra (ampliación: panel por urgencias, estadísticas y reportes)
fecha: 2026-10-17
---

# Ampliación: panel por urgencias, estadísticas y reportes

Parte 7 de la mejora de la página de la 3ra entrega. Cubre el hallazgo H6 («panel pobre») y los requerimientos
RF-242 a RF-245 (informe de incidencias abiertas y cerradas de un período, con totales y detalle; tiempo de
resolución; exportar). **No cambia el modelo de datos:** todo se calcula con las tablas que ya existían.

## 1. Decisiones (las tomó la sesión autónoma de la madrugada del 17/10; Fornos puede corregirlas)

La propuesta de la parte 7 dejaba cuatro preguntas para Fornos, que no estaba. Se eligió lo más sencillo y
reversible y queda anotado para revisarlo:

| Pregunta | Se decidió | Cómo cambiarlo |
|---|---|---|
| ¿Qué alertas lleva el panel y con qué umbral? | Críticas (prioridad alta y vencida), sin cuadrilla, vencidas, contenedores llenos, en mantenimiento y recolecciones en curso. **Vencida = 3 días abierta** | `Estadisticas::UMBRAL_DIAS` (y `?dias=` de 1 a 60 en la API) |
| ¿El reporte incluye recolecciones? | **Sí**: kilos y litros por tipo de residuo y por zona, recolecciones por estado | Es otra API (`api-recoleccion`); sacar la sección no toca incidencias |
| ¿Formato de exportación? | **CSV** (para planilla: separador `;`, UTF-8 con BOM, decimales con coma) **e imprimir** (CSS de impresión, A4) | `js/reportes.js` |
| ¿Los otros roles reciben algo en su inicio? | **No en esta parte**; el panel por urgencias es del Administrador | — |

Las alertas siguen **calculadas en el momento**, sin tabla nueva (decisión de Fornos del 12/10).

## 2. API

| Endpoint | Para qué | Respuesta |
|---|---|---|
| `GET /api-gestion/estadisticas/incidencias?desde=&hasta=&zona=` | Informe de incidencias, por fecha de reporte | `totales` (por estado; `pendientes` = abiertas + en curso, `cerradas` = resueltas + descartadas), `por_prioridad`, `por_tipo`, `por_zona` (con tiempo medio), `tiempo_resolucion` (cantidad, medio, mediano, mínimo, máximo, en horas), `top_contenedores` (5, con su problema más frecuente), `detalle` (hasta 1000 filas; `detalle_truncado` avisa) y `periodo` |
| `GET /api-gestion/estadisticas/urgencias?dias=` | Panel del administrador | `indicadores`, las 10 incidencias abiertas más urgentes, los contenedores llenos y el umbral usado |
| `GET /api-recoleccion/estadisticas/recoleccion?desde=&hasta=&zona=` | Lo recolectado | `totales` (recolecciones por estado, vaciados, kg, litros, vaciados sin peso), `por_tipo_residuo`, `por_zona` |

**Urgencia de una incidencia abierta:** *crítica* = prioridad alta **y** vencida · *urgente* = alta **o** vencida ·
*atención* = sin cuadrilla asignada · *en seguimiento* = el resto. Se ordena por nivel y, dentro del nivel, la
más vieja primero. **Tiempo de resolución** = horas entre `fecha_reporte` y `fecha_resolucion`, solo de las
*resueltas* (las descartadas no se resolvieron) y descartando fechas incoherentes (resolución anterior al reporte).

Los cálculos viven en clases **puras** (`api-gestion/models/Estadisticas.php`,
`api-recoleccion/models/ResumenRecoleccion.php`): reciben las filas ya leídas y no tocan la base, por eso se
prueban sin servidor. Los controladores solo arman la consulta (preparada) y llaman a esas clases.

## 3. Pantallas

- **Inicio del administrador** (`dashboard.html`): dos indicadores nuevos (incidencias abiertas y críticas), la
  sección **«Requiere atención»** con los avisos en palabras y la tabla de las incidencias más urgentes (con
  enlace a cada una), además de los contenedores llenos que ya estaban. Los otros roles no cambian.
- **Reportes** (`reportes.html`, menú del administrador): período y zona (atajos *últimos 30 días*, *este mes*,
  *todo el historial*), cinco indicadores, incidencias por zona (gráfico y tabla), por tipo de problema, tiempo
  de resolución, contenedores con más incidencias, recolección (kilos por tipo de residuo y por zona) y el
  detalle ordenable. **Exportar detalle (CSV)**, **Exportar por zona (CSV)** e **Imprimir**.
- **Gráficos:** SVG propio (`js/graficos.js`), sin librería: barras horizontales con la etiqueta y el valor
  escritos en el dibujo y un texto alternativo para lectores de pantalla. Se dibujan al ancho real (no se
  achican en el celular) y se rehacen al cambiar el tamaño de la ventana. Las mismas cifras están en una tabla.
- **Seguridad del CSV:** las celdas de texto que empiezan con `=`, `+`, `-` o `@` llevan una comilla delante
  para que una planilla no las ejecute como fórmula (inyección de fórmulas); las comillas se duplican.

## 4. Pruebas (17/10/2026)

- **Unitarias: 180 → 231.** Matemática (mediana, promedio, horas entre fechas, tiempos), informe con datos
  armados a mano (totales, zonas, tipos, ranking, truncado a 1000, fechas incoherentes), niveles de urgencia
  (incluida la frontera de las 72 horas), orden y recorte del panel, resumen de recolección, y menú ↔ permisos.
- **HTTP: 339 → 396** (con el correo en modo archivo; sin él, 364 y 2 bloques omitidos, ver la ampliación de acceso por enlace). Permisos de los tres endpoints con los cuatro roles y sin sesión; validaciones del
  período; y, sobre todo, que **las cifras coincidan con las listas de siempre**: el total del informe es el de
  `GET /incidencias`, las abiertas del panel son las abiertas y en curso del listado, los llenos son los del
  listado de contenedores, los kilos por tipo y por zona suman el total y el filtro por zona da lo mismo que el
  listado filtrado.
- **Mutaciones: 7 de 7 detectadas** (mediana, frontera de «vencida», conteo de cerradas, vaciados sin peso,
  fechas al revés, permiso abierto a todos y filtro de zona de recolección; esta última **no se detectaba** y se
  agregó la prueba que faltaba).
- **En el navegador:** los cuatro roles (solo el administrador ve «Reportes» y «Requiere atención»; un
  no-administrador que abre `reportes.html` vuelve al inicio); sin desborde horizontal (reportes a 375, 768 y
  1000 px, inicio a 375 y 1000 px); consola sin errores; el atajo de 30 días (período vacío, con todos sus
  mensajes); y el CSV descargado (BOM, separador, decimales, escape de fórmulas).
- **No verificado:** la vista impresa (no se pudo abrir el diálogo de impresión) y el aspecto visual en
  pantalla (las capturas no funcionaron con la ventana oculta; se midió por DOM). **Revisar a ojo.**

## 5. Limitaciones

- El seed tiene 5 incidencias y todas de hace más de un mes: la demo se ve «todo vencido». El panel se aprecia
  mejor reportando incidencias nuevas.
- El informe filtra por fecha de **reporte**, no de resolución: una incidencia reportada en agosto y resuelta en
  octubre cuenta en agosto.
- El detalle se corta en 1000 filas (con aviso); el CSV exporta lo mismo que se ve.
- «Imprimir» usa el diálogo del navegador (sirve también para «Guardar como PDF»); no hay PDF generado en el servidor.
- Los vaciados sin peso registrado cuentan como vaciado pero suman 0 kg (el resumen lo avisa).
