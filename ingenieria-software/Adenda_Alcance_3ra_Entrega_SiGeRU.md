---
documento: Adenda de alcance — tercera entrega
sistema: SiGeRU
fecha: 2026-10-17
estado: BORRADOR para revisión de Fornos (hecho por la sesión autónoma de la madrugada del 17/10; no hay PDF todavía)
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
