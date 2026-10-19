---
documento: Adenda de alcance — tercera entrega
sistema: SiGeRU
fecha: 2026-10-17
estado: BORRADOR para revisión del equipo (secciones 1 a 5: sesión autónoma del 17/10; secciones 6 a 10: sesión autónoma del 19/10, partes 12 a 22; no hay PDF todavía)
---

# Adenda de alcance — 3ra entrega

Esta adenda hace lo que dejó pendiente la parte 10 del plan: **medir en puntos de función lo que se construyó en
octubre y que no estaba en los 228 PF del 02/09** (ni en los 252 con maquinaria), y registrar **qué se hizo, qué se
hizo distinto a la especificación y qué no se hizo**. Sigue el método de la adenda de maquinaria (IFPUG, factor de
ajuste 1,10 sin cambios). **Es una estimación mía: conviene que la revise quien firma la medición.**

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
