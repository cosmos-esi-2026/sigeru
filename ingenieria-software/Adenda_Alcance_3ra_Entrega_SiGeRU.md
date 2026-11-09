---
documento: Adenda de alcance — tercera entrega
sistema: SiGeRU
fecha: 2026-10-17
estado: BORRADOR para revisión del equipo (secciones 1 a 15: registro histórico de octubre; sección 16: control de vigencia del 06/11; el total de PF no está validado para la versión actual)
---

# Adenda de alcance — 3ra entrega

Esta adenda hace lo que dejó pendiente la parte 10 del plan: **medir en puntos de función lo que se construyó en
octubre y que no estaba en los 228 PF del 02/09** (ni en los 252 con maquinaria), y registrar **qué se hizo, qué se
hizo distinto a la especificación y qué no se hizo**. Sigue el método de la adenda de maquinaria (IFPUG, factor de
ajuste 1,10 sin cambios). **Es una estimación mía: conviene que la revise quien firma la medición.**

**Estado al 06/11/2026:** los conteos y las funciones de las secciones 1 a 15 muestran cómo fue cambiando el proyecto; no son una medición cerrada del sistema actual. La sección 16 señala los cambios posteriores y la sección 17 trae el recálculo completo del 06/11/2026. El total vigente es el de la sección 17, no los 432 PF de la sección 15.

## 1. Qué se agregó desde el 14/09

| Parte | Qué | Dónde |
|---|---|---|
| P3-P4 | Incidencias con estados, prioridad, asignación, avance y resolución; historial de solo agregado | `api-gestion`, pantallas por rol |
| P5 | `api-recoleccion`, la API de la arquitectura que faltaba: camiones, cuadrillas, rutas, recolecciones con vaciados (peso y volumen) | `api-recoleccion`, pantallas de recolecciones |
| P6 | Mapa interactivo para los cuatro roles | `mapa.html` |
| P7 | Panel por urgencias, informe de un período, tiempo de resolución, exportación CSV | `dashboard.html`, `reportes.html` |
| P8 | Landing con datos reales y mapa público | `landing/`, `GET /publico/mapa` |
| P9 | Filtros, paginación y exportación en contenedores y usuarios; validaciones | listas |
| P4b, P5b | Foto de la incidencia; pantalla de rutas | `incidencia.html`, `rutas.html` |
| (16/10) | Cuentas por enlace de correo y recuperación de contraseña | `api-usuarios` |

## 2. Medición de lo nuevo

No se agrega ningún archivo lógico interno: `HistorialIncidencia` entra como **RET** de `Incidencia` (igual que
`EstadoContenedor` en `Contenedor`) y `EnlaceClave` e `IntentoLogin` son tablas técnicas que el usuario no reconoce
como datos de negocio. **Funciones transaccionales nuevas** (los pesos IFPUG: EI 3/4/6, EO 4/5/7, EQ 3/4/6):

| Proceso elemental | Tipo | FTR | DET | Compl. | PF |
|---|---|---|---|---|---|
| Planificar recolección (ruta, camión, cuadrilla, fecha) | EI | 4 | 8 | Alta | 6 |
| Iniciar recolección | EI | 2 | 4 | Baja | 3 |
| Registrar vaciado (contenedor, peso, volumen) | EI | 2 | 5 | Media | 4 |
| Finalizar recolección | EI | 2 | 4 | Baja | 3 |
| Cancelar recolección | EI | 2 | 3 | Baja | 3 |
| Registrar ruta | EI | 2 | 5 | Media | 4 |
| Modificar ruta | EI | 2 | 5 | Media | 4 |
| Dar de baja ruta | EI | 1 | 2 | Baja | 3 |
| Cambiar prioridad de una incidencia | EI | 1 | 2 | Baja | 3 |
| Crear contraseña con enlace de correo | EI | 1 | 3 | Baja | 3 |
| Pedir enlace para recuperar contraseña | EI | 1 | 1 | Baja | 3 |
| Enviar acceso a un usuario (administrador) | EI | 1 | 1 | Baja | 3 |
| **Subtotal EI (12)** | | | | | **42** |
| Consultar rutas | EQ | 2 | 6 | Media | 4 |
| Consultar recolecciones y sus vaciados | EQ | 3 | 10 | Alta | 6 |
| Consultar la ficha e historial de un contenedor | EQ | 2 | 7 | Media | 4 |
| Mapa interactivo (contenedores, centros, incidencias) | EQ | 3 | 9 | Alta | 6 |
| Mapa público de la landing | EQ | 5 | 9 | Alta | 6 |
| **Subtotal EQ (5)** | | | | | **26** |
| Panel por urgencias (nivel calculado, orden) | EO | 4 | 12 | Alta | 7 |
| Resumen de lo recolectado (kg y litros por tipo y zona) | EO | 4 | 8 | Alta | 7 |
| Exportar el informe de incidencias a CSV | EO | 3 | 10 | Media | 5 |
| Exportar contenedores a CSV | EO | 3 | 8 | Media | 5 |
| Exportar usuarios a CSV | EO | 2 | 8 | Media | 5 |
| **Subtotal EO (5)** | | | | | **29** |
| **Total de lo nuevo, sin ajustar** | | | | | **97** |

No se vuelven a contar el informe de incidencias por zona (CU-030) ni las demás funciones de incidencias, que ya
estaban en el conteo de 44 funciones; las mejoras de filtros y paginación son cambios de funciones existentes.

| | Sin ajustar | Ajustados (× 1,10) |
|---|---|---|
| Conteo del 02/09 | 207 | 228 |
| Con maquinaria (09/09) | 229 | 252 |
| **Con lo de octubre** | **326** | **359** |

A la tasa de referencia de 10 h/PF, 97 PF sin ajustar son unas 970 h; el equipo no las trabajó como 970 h de
tres personas: casi todo fue una persona más asistencia. Es la misma advertencia de la adenda de maquinaria
(la tasa supone construir desde cero y aquí se reusó mucho: el patrón de modelo-controlador-pantalla, las pruebas,
el menú único). **No debe leerse como productividad medida.**

## 3. Lo que el sistema hace distinto a la especificación

| Tema | Especificación / prototipo | Sistema | Motivo |
|---|---|---|---|
| Backoffice | Aplicación aparte (arquitectura de 6 aplicaciones) | **Sección de `frontend-usuarios`** | Decisión por defecto del 12/10 (Fornos: «no sé»); evita duplicar el menú, la sesión y los estilos |
| Notificaciones | Avisos al usuario | **Alertas calculadas en el panel**; el único correo es el de acceso | Decisión del 12/10: sin tabla nueva, sin cola de envío |
| Reporte de incidencias | Vecino sin cuenta (idea inicial) | **Solo vecino registrado** | Decisión del 12/10 (RF-217) |
| Estados de incidencia | abierta / en curso / resuelta / descartada | Igual; antes había otros nombres | Alineado a la especificación en P3 |
| Contraseñas | Las definía el administrador | **Las crea la persona con un enlace de un solo uso** | 16-17/10, ver `ampliacion-acceso-por-enlace-3ra.md` |
| Foto de la incidencia (RF-222/223) | Adjuntar evidencia | **Hecho** (P4b): una foto por reporte, reconvertida a JPEG sin datos de la cámara | Ver `ampliacion-fotos-y-rutas-3ra.md`. Ya estaba contada en la medición del 02/09 (CU-024, 3 PF) |

## 4. Lo que no se hizo (y por qué)

- **Reemplazar o borrar la foto de una incidencia**, y más de una foto por incidencia: decidido que no (la foto es
  evidencia, en el espíritu del historial de solo agregado).
- **Avisos por correo de cambios de estado de un reclamo**: decidido que no (alertas en el panel).
- **Envío real de correo probado**: el código y las pruebas cubren todo salvo la entrega por Gmail.
- **Instalación en el servidor `sigeru-srv`** y **MySQL 8**: la cubre el workflow de GitHub al pushear; no se
  probó en esta PC (Docker no corre).

## 5. Qué revisar

1. Los FTR/DET de la tabla de arriba, que son criterio mío.
2. Si las exportaciones a CSV se cuentan como EO (aquí sí) o se consideran parte de la consulta.
3. Si `HistorialIncidencia` como RET es el criterio que prefiere la docente (en 2da se hizo así con `EstadoContenedor`).

---

# Actualización del 19/10/2026 — partes 12 a 22 de las extras

**Estado: PENDIENTE DE REVISIÓN DEL EQUIPO.** Lo escribió la sesión autónoma que hizo las partes 12 a 22 del plan de extras (Fornos pidió seguir sin responder). Mismo método que arriba (IFPUG, factor de ajuste 1,10, pesos EI 3/4/6, EO 4/5/7, EQ 3/4/6, ILF 7/10/15).
**Los FTR, DET y complejidades son criterio de quien escribió esto; hay que revisarlos antes de citar el total.** El detalle de cada parte está en `programacion-full-stack/ampliacion-*-3ra.md` y el resumen en `ampliacion-extras-3ra.md`.

## 6. Qué se agregó (partes 12 a 22)

| Parte | Qué |
|---|---|
| 12 | Operario con trabajo real: recepciones, stock del centro con movimientos de solo agregado (corrección por movimiento inverso), estado de máquinas con historial |
| 13 | Notificaciones: campanita, avisos a vecino/cuadrilla/administrador/operario y correo opcional |
| 14 | Participación del vecino: calificar la atención, puntos, insignias, ranking de zonas; calidad del servicio en Reportes |
| 15 | «¿Cuándo pasa el camión por mi zona?» |
| 16 | Predicción de llenado de contenedores (estimación estadística, no IA) |
| 17 | Ruta sugerida para el camión (vecino más cercano + 2-opt) con alta precargada |
| 18 | Impacto ambiental con factores de la EPA (WARM v16) verificados |
| 19 | Guía pública «¿Dónde tiro esto?» (Manual de la Intendencia de Montevideo) |
| 20 | Reporte de gestión en PDF (generador propio) |
| 21 | Aplicación instalable y Cuadrilla sin señal (cola y claves de operación) |
| 22 | Pulido: modo oscuro, páginas de error, estados vacíos, imagen para compartir |

## 7. Medición de lo nuevo

**Archivos lógicos internos nuevos** (datos de negocio que el usuario reconoce y la aplicación mantiene): `Recepcion` (con su stock y movimientos como RET), `Notificacion`, `Calificacion` (con los puntos como RET) y `MovimientoStock` visto como el stock del centro. Los cuatro con 1 a 2 RET y pocos DET: complejidad baja, **7 PF cada uno = 28**.
No se cuentan como ILF `historial_maquinaria` (RET de `Maquinaria`), `clave_idempotencia` (tabla técnica, como `auditoria`) ni los datos estáticos de la guía (un archivo JSON con la fuente citada, que el usuario no mantiene).

| Proceso elemental | Tipo | FTR | DET | Compl. | PF |
|---|---|---|---|---|---|
| Confirmar una recepción en el centro | EI | 3 | 6 | Media | 4 |
| Retirar material del centro | EI | 2 | 5 | Media | 4 |
| Corregir un movimiento de stock | EI | 2 | 4 | Baja | 3 |
| Cambiar el estado de una máquina (con nota) | EI | 2 | 4 | Baja | 3 |
| Marcar una notificación como leída | EI | 1 | 2 | Baja | 3 |
| Marcar todas las notificaciones como leídas | EI | 1 | 1 | Baja | 3 |
| Elegir si quiero avisos por correo | EI | 1 | 2 | Baja | 3 |
| Calificar la atención de un reclamo | EI | 3 | 5 | Media | 4 |
| Elegir mi zona | EI | 1 | 2 | Baja | 3 |
| **Subtotal EI (9)** | | | | | **30** |
| Consultar recepciones | EQ | 2 | 8 | Media | 4 |
| Ver el detalle de una recepción | EQ | 3 | 8 | Media | 4 |
| Ver el stock y los movimientos de un centro | EQ | 3 | 9 | Media | 4 |
| Consultar el historial de una máquina | EQ | 2 | 6 | Media | 4 |
| Consultar mis notificaciones | EQ | 2 | 6 | Media | 4 |
| Ver mi progreso (puntos e insignias) | EQ | 3 | 8 | Media | 4 |
| Ver el ranking de zonas | EQ | 2 | 4 | Baja | 3 |
| Ver cuándo pasa el camión por mi zona | EQ | 3 | 6 | Media | 4 |
| Buscar en la guía «¿Dónde tiro esto?» | EQ | 1 | 8 | Baja | 3 |
| **Subtotal EQ (9)** | | | | | **34** |
| Predicción de llenado de contenedores | EO | 3 | 11 | Media | 5 |
| Ruta sugerida (orden, km y tiempo) | EO | 3 | 12 | Media | 5 |
| Calidad del servicio por zona y por cuadrilla | EO | 4 | 10 | Alta | 7 |
| Impacto ambiental público | EO | 3 | 8 | Media | 5 |
| Impacto ambiental por mes (Reportes) | EO | 4 | 10 | Alta | 7 |
| Reporte de gestión en PDF | EO | 5 | 40 | Alta | 7 |
| Exportar la calidad del servicio a CSV | EO | 3 | 8 | Media | 5 |
| Exportar el impacto ambiental a CSV | EO | 3 | 8 | Media | 5 |
| Panel del centro del Operario (ocupación y avisos) | EO | 3 | 8 | Media | 5 |
| **Subtotal EO (9)** | | | | | **51** |
| **Archivos lógicos internos (4 × 7)** | ILF | | | Baja | **28** |
| **Total de lo nuevo (partes 12 a 22), sin ajustar** | | | | | **143** |

La aplicación instalable, la cola sin conexión, las claves de operación, el modo oscuro, las páginas de error y la imagen para compartir **no suman puntos de función**: son requerimientos no funcionales (IFPUG no los mide; en COCOMO entrarían en el factor de ajuste, que acá no se modificó).

| | Sin ajustar | Ajustados (× 1,10) |
|---|---|---|
| Conteo del 02/09 | 207 | 228 |
| Con maquinaria (09/09) | 229 | 252 |
| Con lo de octubre (partes 3 a 10) | 326 | 359 |
| **Con las partes 12 a 22** | **469** | **516** |

Vale la misma advertencia de arriba: a 10 h/PF serían unas 1.430 h, y **no es productividad medida**: se reusó el patrón de modelo, controlador y pantalla, el menú, las pruebas y las clases puras; casi todo lo hizo una persona con asistencia.

## 8. Qué cambió respecto de lo que decía esta adenda

- **Notificaciones:** la sección 3 decía «alertas calculadas en el panel, sin tabla nueva» y la 4, «avisos por correo: decidido que no». **Ya no es así** (parte 13): hay tabla `notificacion`, campanita en todas las pantallas y correo opcional al vecino (por defecto activado, se puede apagar). Es una decisión de producto que tomó la sesión autónoma y **el equipo debe confirmar o revertir**.
- **Operario:** antes sin tarea propia ni vínculo con un centro; ahora cada Operario pertenece a un centro (`usuario.id_centro`), reemplazando la decisión del 12/10.
- **Modelo de datos:** de 16 a 22 tablas del modelo (recepcion, movimiento_stock, historial_maquinaria, notificacion, calificacion, movimiento_punto), más columnas en `usuario` y `tipo_residuo`, y 29 tablas físicas con las técnicas (migraciones 009 a 016). Los diagramas de clases y de DER **no se rehicieron**: describen el modelo de 16 clases (se documentan por ampliación, no se reescriben).

## 9. Lo que no se hizo / no se pudo verificar

- **FPDF:** el plan lo pedía y no se descargó (no había quien autorizara la descarga); el PDF se hace con un generador propio.
- **Instalación en un celular real, service worker registrado de verdad y HTTPS en `sigeru-srv`:** el navegador integrado no permite registrar service workers; ver `ampliacion-offline-3ra.md`.
- **Envío real de correo** (avisos y accesos): sigue sin probarse contra Gmail.
- **El servidor `sigeru-srv` no tiene nada de esto:** ver `administracion-sistemas-operativos/novedades-3ra-para-el-servidor.md`.
- Los factores ambientales son de la EPA (Estados Unidos): son una **estimación**, rotulada así en pantalla.

## 10. Qué revisar

1. Todos los FTR/DET de la sección 7 y si el reporte en PDF cuenta como un EO o como parte de los informes ya contados.
2. Si los cuatro ILF nuevos están bien elegidos (el stock del centro como ILF propio o como RET de `CentroAcopio`).
3. Si las exportaciones a CSV se cuentan como EO (aquí sí, igual que arriba).
4. La decisión de las notificaciones (sección 8).

---

# Actualización del 25/10/2026 — simplificación antes del congelamiento

**Estado: PENDIENTE DE REVISIÓN DEL EQUIPO.** Antes del congelamiento se sacó del sistema lo que no se iba a defender (detalle y motivos en `programacion-full-stack/ampliacion-simplificacion-3ra.md`).
**Las secciones 6 a 10 de arriba describen el sistema del 19/10 y quedan como registro**: lo que sigue corrige sus cifras. Mismo método (IFPUG, factor de ajuste 1,10, pesos EI 3/4/6, EO 4/5/7, EQ 3/4/6, ILF 7/10/15).

## 11. Qué quedó de las partes 12 a 22

| Parte | Estado |
|---|---|
| 12 Operario con trabajo real | **Sigue** |
| 13 Notificaciones | **Sigue la campanita**; se sacó el correo al vecino y su preferencia |
| 14 Participación del vecino | **Sacada** (calificar, puntos, insignias, ranking) y también la calidad del servicio de Reportes |
| 15 ¿Cuándo pasa el camión por mi zona? | **Sigue** |
| 16 Predicción de llenado | **Sacada** |
| 17 Ruta sugerida | **Sacada** |
| 18 Impacto ambiental | **Sacado** (landing y Reportes) |
| 19 Guía «¿Dónde tiro esto?» | **Sacada** |
| 20 Reporte en PDF | **Sacado** |
| 21 Aplicación instalable y cola sin señal | **Sacada** |
| 22 Pulido | **Sigue** (modo oscuro, errores, estados vacíos, imagen para compartir) |

## 12. Medición corregida

Se quitan de la tabla de la sección 7 los procesos de las funciones sacadas:

- **EI:** «Elegir si quiero avisos por correo» (3) y «Calificar la atención de un reclamo» (4). Quedan 7 EI: **23**.
- **EQ:** «Ver mi progreso», «Ver el ranking de zonas» y «Buscar en la guía» (4 + 3 + 3). Quedan 6 EQ: **24**.
- **EO:** predicción, ruta sugerida, calidad del servicio, impacto público, impacto por mes, reporte en PDF y las exportaciones a CSV de calidad e impacto (46). Queda 1 EO, el panel del centro del Operario: **5**.
- **ILF:** sale `Calificacion` (con los puntos como RET). Quedan `Recepcion`, `Notificacion` y `MovimientoStock`: 3 × 7 = **21**.

| | Sin ajustar | Ajustados (× 1,10) |
|---|---|---|
| Lo nuevo de las partes 12 a 22 que **sigue** | **73** (23 + 24 + 5 + 21) | 80 |
| Lo nuevo según la sección 7 (19/10) | 143 | 157 |
| Conteo acumulado del 19/10 (sección 7) | 469 | 516 |
| **Conteo acumulado hoy** (326 de antes + 73) | **399** | **439** |

La advertencia de siempre vale igual: **no es productividad medida**. Es una estimación **pendiente de revisión del equipo** (los FTR y DET son criterio de quien la escribió).

## 13. Qué más cambió respecto del 19/10

- **Modelo de datos:** 21 tablas del modelo (no 22) y 27 con las técnicas (no 29): salieron `calificacion`, `movimiento_punto`, `factor_ambiental` y `clave_idempotencia`, y la columna `usuario.avisos_por_correo` (migraciones 001 a 011, 014 y 017 a 020; las 012, 013, 015 y 016 se borraron).
- **Notificaciones:** la sección 8 decía «correo opcional al vecino»: **ya no existe**. La campanita queda para todos los roles.
- **La adenda ya no tiene que justificar** el generador de PDF propio ni la aplicación instalable: se fueron con sus funciones (sección 9, 1.º y 2.º puntos).
- **Diagramas de clases y DER:** siguen siendo los del modelo de 16 clases (se documenta por ampliación).

## 14. Qué revisar

1. Los 73 PF de la sección 12 (todos los FTR/DET se heredan de la sección 7).
2. Si el equipo quiere volver a poner alguna de las funciones sacadas: la lista está en `ampliacion-simplificacion-3ra.md` y la copia, en `respaldo-partes/antes-de-simplificar-20261025.zip`.

## 15. Actualización del 30/10/2026: se saca el correo

Se sacó el correo del sistema (bienvenida, enlace de acceso y «Olvidé mi contraseña»). El administrador define la contraseña al crear la cuenta o al aprobar una solicitud, y la restablece editando el usuario (ver `ampliacion-sin-correo-3ra.md`).

- **EI que salen** (de la tabla de la sección 7 de la adenda del 16/10): «Crear contraseña con enlace de correo» (3) y «Pedir enlace para recuperar contraseña» (3): **−6 PF sin ajustar**. «Alta de usuario» y «Aprobar solicitud» ya estaban contadas; solo suman un campo (DET).
- **Conteo acumulado hoy:** 399 − 6 = **393** sin ajustar y **432** ajustados (× 1,10). Sigue siendo una estimación **pendiente de revisión del equipo**.
- **Modelo de datos:** 21 tablas del modelo y 26 con las técnicas (sale `enlace_clave`); migraciones 001 a 003, 005 a 011, 014 y 017 a 020 (se borró la 004).
- Las menciones al «correo de acceso» en las secciones 2 a 8 describen lo que se hizo el 16-17/10 y ya no rigen.

## 16. Control de vigencia al 06/11/2026

El 30/10 se retiraron la foto de la incidencia, la consulta «¿Cuándo pasa el camión por mi zona?» y las descargas CSV de Usuarios y Contenedores. El 03/11 volvió una descarga CSV en **Reportes**, limitada al detalle visible de incidencias (hasta 1000 filas). Esto está documentado en `programacion-full-stack/ampliacion-simplificacion-2-3ra.md`, `frontend-usuarios/reportes.html`, `frontend-usuarios/js/reportes.js` y `api-gestion/models/Estadisticas.php`.

Por esos cambios, los 393 PF sin ajustar y 432 ajustados de la sección 15 incluyen funciones que ya no están, y el CSV de Reportes cambió después. No corresponde restar cifras a ojo, así que en la sección 17 se volvió a contar todo el sistema actual, proceso por proceso.

El modelo físico actual tiene 25 tablas: 21 del dominio y cuatro de soporte. El diseño actualizado de clases, casos de uso y paquetes está en `ingenieria-software/Diseno_del_Sistema_SiGeRU_v3.pdf` y `ingenieria-software/Casos_de_Uso_SiGeRU_v3.pdf`; la frase de la sección 13 sobre el diagrama de 16 clases quedó histórica. La ERS v3 registra también las funciones retiradas.

## 17. Recálculo de puntos de función al 06/11/2026

Se contó de nuevo todo el sistema que se entrega, sin partir de los conteos anteriores. Se usaron las rutas de las tres APIs (`api-usuarios`, `api-gestion` y `api-recoleccion`), las pantallas de `frontend-usuarios` y las 25 tablas de `schema.sql`. Los pesos son los de IFPUG (ILF 7/10/15; EI 3/4/6; EO 4/5/7; EQ 3/4/6) y la complejidad sale de las tablas de FTR y DET de IFPUG.

**Criterios.** Un proceso elemental es una operación que el usuario reconoce como una unidad (alta, modificación, baja, consulta o informe). La lista y la ficha de un mismo recurso se cuentan por separado solo cuando devuelven datos distintos. Los grupos de tablas que se leen y se escriben juntos son un solo archivo lógico con sus RET: por ejemplo, la incidencia con su historial. Las tablas técnicas (`migracion`, `intento_login`, `limite_peticion` y `auditoria`) no son archivos lógicos. No hay archivos de interfaz externos: los mosaicos del mapa son de OpenStreetMap y el sistema solo los muestra. Las altas, modificaciones y bajas de zonas, tipos de residuo, camiones y cuadrillas se cuentan como 3 procesos de la misma complejidad. No se cuentan cerrar sesión ni consultar la sesión actual, que son parte del acceso.

**Archivos lógicos internos (14 × 7 = 98 PF).** Todos son de complejidad baja (1 a 2 RET y menos de 20 DET):

| N.º | Archivo lógico | PF |
|---|---|---|
| 1 | Usuario (con su rol) | 7 |
| 2 | Solicitud de registro | 7 |
| 3 | Cuadrilla | 7 |
| 4 | Contenedor (con su estado) | 7 |
| 5 | Camión | 7 |
| 6 | Centro de acopio | 7 |
| 7 | Zona | 7 |
| 8 | Tipo de residuo | 7 |
| 9 | Maquinaria (con su historial) | 7 |
| 10 | Ruta (con sus paradas) | 7 |
| 11 | Recolección (con sus vaciados) | 7 |
| 12 | Incidencia (con su historial) | 7 |
| 13 | Recepción (con sus movimientos de stock) | 7 |
| 14 | Notificación | 7 |

**Procesos elementales.** FTR = archivos referenciados; DET = campos que cruzan el límite del sistema.

*Usuarios y acceso*

| Proceso elemental | Tipo | FTR | DET | Complejidad | PF |
|---|---|---|---|---|---|
| Registrar un usuario (administrador) | EI | 3 | 9 | Alta | 6 |
| Modificar un usuario o restablecer su clave | EI | 3 | 10 | Alta | 6 |
| Dar de baja un usuario | EI | 1 | 2 | Baja | 3 |
| Enviar una solicitud de registro (público) | EI | 1 | 7 | Baja | 3 |
| Aprobar o rechazar una solicitud | EI | 2 | 5 | Media | 4 |
| Iniciar sesión (correo o cédula) | EI | 1 | 3 | Baja | 3 |
| Consultar usuarios con filtros | EQ | 2 | 9 | Media | 4 |
| Ver un usuario | EQ | 2 | 10 | Media | 4 |
| Consultar solicitudes de registro | EQ | 1 | 8 | Baja | 3 |
| Consultar el registro de auditoría | EQ | 1 | 7 | Baja | 3 |

*Notificaciones*

| Proceso elemental | Tipo | FTR | DET | Complejidad | PF |
|---|---|---|---|---|---|
| Marcar una notificación como leída | EI | 1 | 2 | Baja | 3 |
| Marcar todas como leídas | EI | 1 | 1 | Baja | 3 |
| Consultar mis notificaciones | EQ | 1 | 6 | Baja | 3 |

*Contenedores*

| Proceso elemental | Tipo | FTR | DET | Complejidad | PF |
|---|---|---|---|---|---|
| Registrar un contenedor | EI | 3 | 9 | Alta | 6 |
| Modificar un contenedor | EI | 3 | 9 | Alta | 6 |
| Dar de baja un contenedor | EI | 2 | 3 | Baja | 3 |
| Consultar contenedores con filtros | EQ | 2 | 9 | Media | 4 |
| Ver la ficha de un contenedor | EQ | 2 | 10 | Media | 4 |
| Consultar el historial de un contenedor | EQ | 2 | 7 | Media | 4 |

*Centros de acopio*

| Proceso elemental | Tipo | FTR | DET | Complejidad | PF |
|---|---|---|---|---|---|
| Registrar un centro | EI | 2 | 6 | Media | 4 |
| Modificar un centro | EI | 2 | 6 | Media | 4 |
| Dar de baja un centro | EI | 1 | 2 | Baja | 3 |
| Consultar centros | EQ | 2 | 7 | Media | 4 |
| Ver un centro | EQ | 2 | 8 | Media | 4 |
| Ver el stock y los movimientos de un centro | EQ | 3 | 9 | Media | 4 |
| Retirar material del centro | EI | 2 | 5 | Media | 4 |
| Corregir un movimiento de stock | EI | 2 | 4 | Baja | 3 |
| Confirmar una recepción en el centro | EI | 3 | 6 | Alta | 6 |
| Consultar recepciones | EQ | 2 | 8 | Media | 4 |
| Ver el detalle de una recepción | EQ | 3 | 8 | Media | 4 |

*Maquinaria*

| Proceso elemental | Tipo | FTR | DET | Complejidad | PF |
|---|---|---|---|---|---|
| Registrar una máquina | EI | 2 | 6 | Media | 4 |
| Modificar una máquina | EI | 2 | 6 | Media | 4 |
| Dar de baja una máquina | EI | 1 | 2 | Baja | 3 |
| Cambiar el estado de una máquina (con nota) | EI | 2 | 4 | Baja | 3 |
| Consultar maquinaria | EQ | 2 | 7 | Media | 4 |
| Consultar el historial de una máquina | EQ | 2 | 6 | Media | 4 |

*Tablas de apoyo*

| Proceso elemental | Tipo | FTR | DET | Complejidad | PF |
|---|---|---|---|---|---|
| Registrar, modificar o dar de baja una zona (3 procesos) | EI | 1 | 3 | Baja | 9 |
| Consultar zonas | EQ | 1 | 4 | Baja | 3 |
| Registrar, modificar o dar de baja un tipo de residuo (3 procesos) | EI | 1 | 3 | Baja | 9 |
| Consultar tipos de residuo | EQ | 1 | 4 | Baja | 3 |

*Incidencias*

| Proceso elemental | Tipo | FTR | DET | Complejidad | PF |
|---|---|---|---|---|---|
| Informar un problema sobre un contenedor | EI | 3 | 7 | Alta | 6 |
| Asignar una incidencia a una cuadrilla | EI | 3 | 5 | Alta | 6 |
| Derivar una incidencia | EI | 2 | 4 | Baja | 3 |
| Registrar un avance (cuadrilla) | EI | 2 | 4 | Baja | 3 |
| Resolver una incidencia | EI | 2 | 5 | Media | 4 |
| Descartar una incidencia | EI | 2 | 4 | Baja | 3 |
| Cambiar la prioridad de una incidencia | EI | 1 | 2 | Baja | 3 |
| Consultar incidencias (administrador, cuadrilla y vecino) | EQ | 3 | 12 | Media | 4 |
| Ver el detalle y el historial de una incidencia | EQ | 3 | 14 | Media | 4 |

*Camiones y cuadrillas*

| Proceso elemental | Tipo | FTR | DET | Complejidad | PF |
|---|---|---|---|---|---|
| Registrar, modificar o dar de baja un camión (3 procesos) | EI | 1 | 5 | Baja | 9 |
| Consultar camiones | EQ | 1 | 6 | Baja | 3 |
| Registrar, modificar o dar de baja una cuadrilla (3 procesos) | EI | 2 | 5 | Media | 12 |
| Consultar cuadrillas | EQ | 1 | 6 | Baja | 3 |

*Rutas*

| Proceso elemental | Tipo | FTR | DET | Complejidad | PF |
|---|---|---|---|---|---|
| Registrar una ruta | EI | 2 | 8 | Media | 4 |
| Modificar una ruta | EI | 2 | 8 | Media | 4 |
| Dar de baja una ruta | EI | 1 | 2 | Baja | 3 |
| Definir los contenedores de una ruta | EI | 2 | 4 | Baja | 3 |
| Consultar rutas | EQ | 2 | 7 | Media | 4 |
| Ver el detalle de una ruta | EQ | 3 | 9 | Media | 4 |
| Consultar contenedores candidatos para una ruta | EQ | 2 | 6 | Media | 4 |
| Calcular el trazado de una ruta | EO | 2 | 8 | Media | 5 |

*Recolecciones*

| Proceso elemental | Tipo | FTR | DET | Complejidad | PF |
|---|---|---|---|---|---|
| Planificar una recolección (ruta, camión, cuadrilla, fecha) | EI | 4 | 8 | Alta | 6 |
| Iniciar una recolección | EI | 2 | 4 | Baja | 3 |
| Registrar un vaciado (contenedor, peso, volumen) | EI | 2 | 5 | Media | 4 |
| Finalizar una recolección | EI | 2 | 4 | Baja | 3 |
| Cancelar una recolección | EI | 2 | 3 | Baja | 3 |
| Consultar recolecciones | EQ | 3 | 10 | Media | 4 |
| Ver una recolección con sus vaciados | EQ | 3 | 12 | Media | 4 |
| Consultar las tareas asignadas | EQ | 3 | 9 | Media | 4 |
| Consultar mis rutas (cuadrilla) | EQ | 3 | 9 | Media | 4 |

*Mapas e informes*

| Proceso elemental | Tipo | FTR | DET | Complejidad | PF |
|---|---|---|---|---|---|
| Mapa interactivo (contenedores, centros e incidencias) | EQ | 3 | 9 | Media | 4 |
| Mapa público y cifras de la página de inicio | EQ | 5 | 9 | Alta | 6 |
| Panel por urgencias (nivel calculado y orden) | EO | 4 | 12 | Alta | 7 |
| Informe de incidencias por período y zona | EO | 3 | 10 | Media | 5 |
| Resumen de lo recolectado (kg y litros por tipo y zona) | EO | 4 | 8 | Alta | 7 |
| Descargar el informe de incidencias en CSV | EO | 3 | 10 | Media | 5 |
| Panel del centro del operario (ocupación y avisos) | EO | 3 | 8 | Media | 5 |

**Resultado.**

| Tipo | Procesos | PF |
|---|---|---|
| Entradas externas (EI) | 49 | 184 |
| Salidas externas (EO) | 6 | 34 |
| Consultas externas (EQ) | 30 | 115 |
| Archivos lógicos internos (ILF) | 14 | 98 |
| **Total sin ajustar** | | **431** |
| **Total ajustado (× 1,10)** | | **474** |

**Por qué no se parece al 393 de antes.** El conteo de la sección 15 venía de sumar a un conteo base de septiembre (24 procesos y 12 archivos) las partes que se fueron agregando, y después restar a ojo lo que se sacó. El de ahora cuenta cada operación de las APIs como un proceso, así que las altas, modificaciones y bajas aparecen por separado y los 431 PF no son comparables con los 393. No quiere decir que el sistema haya crecido: cambió el nivel de detalle con que se cuenta.

**Qué no se cuenta.** Las funciones retiradas (foto de la incidencia, consulta del próximo pasaje, calificación, puntos, predicción de llenado, ruta sugerida, impacto ambiental, guía, reporte en PDF, correo y recuperación de clave), la descarga CSV de usuarios y de contenedores, y los requerimientos no funcionales (modo oscuro, páginas de error, imagen para compartir). Tampoco se cuentan las funciones técnicas de las pruebas ni las migraciones.

**Factor de ajuste.** Se mantiene el 1,10 de los conteos anteriores; no se volvieron a evaluar las 14 características generales del sistema.

**Límite.** Los FTR y DET se tomaron de las columnas de las tablas y de lo que reciben y devuelven las rutas, no de revisar cada campo de cada pantalla. Por eso el total es una estimación con el criterio de quien la hizo, y puede variar unos puntos si otra persona cuenta distinto. Sigue valiendo la advertencia de siempre: no es productividad medida.
