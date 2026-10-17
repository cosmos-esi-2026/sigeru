# SiGeRU — alcance vigente y registro de decisiones

**Fecha de revisión:** 12/10/2026  
**Etapa:** paso 2 del plan de corrección de Ingeniería de Software  
**Estado:** análisis documentado; las decisiones señaladas como pendientes requieren evidencia o definición del equipo.

Las rutas de fuentes indicadas abajo se interpretan desde la carpeta `SIGERU 2026`.

## 1. Dos fechas que no deben mezclarse

- **14/09/2026 — segunda entrega:** describe qué exigía la consigna y qué se había construido o documentado hasta ese día. Su copia en `entregas/2da-entrega-2026-09-14/` permanece congelada.
- **12/10/2026 — estado de trabajo:** incluye ampliaciones de la tercera entrega, en especial permisos por rol e incidencias en la API. Estas mejoras no se atribuyen retroactivamente a la segunda entrega.

La versión de trabajo del integrador, en `sigeru/SiGeRU_Documento_Integrador_2da_Entrega.pdf`, reordena la documentación de septiembre. Este registro sirve para corregir sus afirmaciones de alcance y para alimentar la ERS del paso 3; no reemplaza un acuerdo con usuarios ni una aprobación docente.

## 2. Evidencia disponible y sus límites

| Evidencia | Qué permite afirmar | Qué no permite afirmar |
|---|---|---|
| `vault/mind/raw/documentos/Proyecto_ESI_2026.pdf`, secciones 5, 6, 9.1 y 9.4 | La consigna define funcionalidades y entregables por fecha y materia. | Que una intendencia concreta haya confirmado esos procesos o prioridades. |
| `vault/mind/wiki/fuentes/Entrevista_Para_Entrega1_UTU_SiGeRU.md` y `sigeru/proyecto-utu-lab/Proyecto_UTU_LAB_2da_Entrega_SiGeRU.pdf`, sección 4.1 | La entrevista se intentó y el equipo descartó sus respuestas por no aportar información utilizable. | Citar esa entrevista como fuente de necesidades, métricas o validación. La plantilla conservada tiene respuestas vacías. |
| Prototipo Low-Fi en `sigeru/proyecto-utu-lab/prototipo-lofi/` y documento de UTU LAB, secciones 3, 4.7 y anexo 10.1 | Existen ocho pantallas navegables, cuatro escenarios, guion y ficha para validación. | Afirmar que se hicieron las cuatro sesiones, que hubo feedback o que usuarios externos aprobaron el diseño. El documento las marca pendientes. |
| Integrador de segunda entrega, sección 3.3; código, esquema y pruebas del repositorio | Había interfaces, dos APIs, persistencia y CRUD de los recursos indicados en la sección. | Que el módulo de incidencias completo estuviera implementado el 14/09; entonces había datos y diseño, no su flujo operativo. |
| `vault/mind/wiki/proyecto/Checklist 3ra entrega.md` y `sigeru/programacion-full-stack/ampliacion-incidencias-3ra.md` | Al 12/10 existen API e historial de incidencias, con pruebas registradas; las pantallas y los reportes siguen planificados. | Presentar la ampliación de octubre como parte de la entrega de septiembre. |

**Problema de trabajo:** la consigna plantea administrar, seguir y analizar información de residuos urbanos. Los documentos del equipo formulan como problema la dispersión de información y la falta de trazabilidad de contenedores e incidencias. Esa formulación es una **hipótesis de análisis y diseño del proyecto**, apoyada en la consigna y en la coherencia interna del modelo; todavía no está validada con observación municipal ni con resultados de usuarios.

**Usuarios previstos:** administrador municipal, cuadrilla, operario de centro de acopio y vecino. Son perfiles de la consigna y del diseño del equipo. Las personas que ocupen esos puestos en un organismo real, sus permisos y sus tareas diarias requieren validación. El prototipo no sustituye esa evidencia.

**Cliente:** el integrador llama a la Intendencia de Montevideo «cliente piloto identificado». No se encontró en los materiales revisados una carta, convenio, entrevista utilizable o aceptación que confirme esa relación. Hasta que exista constancia, usar **«organismo piloto propuesto»** y tratar los beneficios calculados como escenario, no como resultado observado.

## 3. Alcance por obligación y por fecha

La sección 5 de la consigna define un **nivel mínimo general**: ABM de usuarios, contenedores y camiones; registro y seguimiento de incidencias; inventario básico de camiones y contenedores de repuesto; e informe simple. La sección 9.4 agrega entregables específicos por avance. La etiqueta «recomendado» del nivel general no anula un elemento que la sección de una materia enumera para una entrega.

| Bloque | Alcance exigido o propuesto | Corte temporal y estado conocido |
|---|---|---|
| Acceso, usuarios y roles | Registro, autenticación, CRUD de usuarios y separación de permisos. | Exigido en el segundo avance de Full Stack. Implementado en septiembre; permisos de lectura reforzados en octubre. |
| Contenedores | CRUD, ubicación, estado y tipo de residuo. | CRUD exigido en el segundo avance. Historial modelado en septiembre; reglas y protección del historial ampliadas en octubre. |
| Camiones e inventario | CRUD de camiones; inventario básico de camiones y contenedores de repuesto. | CRUD exigido en el segundo avance; inventario en el mínimo general. Verificar en el paso 3 la cobertura específica del stock de repuestos. |
| Centros de acopio y maquinaria básica | CRUD y endpoints. | Aunque el nivel general los llama intermedios, Full Stack los exige expresamente en el segundo avance. Construidos y documentados en septiembre. |
| Incidencias | Registrar, asignar, seguir estado e historial, consultar y cerrar. | Mínimo general; Full Stack las enumera para el tercer avance. En septiembre había requisitos, casos de uso y tabla; al 12/10 existe la API, pero faltan pantallas por rol. |
| Informe simple | Como mínimo, listado de incidencias abiertas y cerradas. | Mínimo general y tercer avance de Full Stack. Al 12/10 está pendiente el reporte de incidencias. |
| API de recolección y rutas | Gestión móvil de flota, rutas y cuadrillas según sección 6.5; rutas al menos a nivel conceptual en la sección 5. | API de recolección enumerada para el tercer avance; al 12/10 sigue planificada. La planificación avanzada de recorridos excede el mínimo general. |
| Mapa, panel y alertas | La sección 5 llama intermedios al mapa y al panel, y opcionales a las alertas; la sección 9.4 los enumera en el tercer avance de Full Stack. | **Son compromisos de la tercera entrega.** Fornos confirmó el 12/10 que no hay autorización docente escrita para rebajarlos a deseables. El plan técnico P0-P10 debe revisarse en su propia sesión antes del cierre. |
| Arquitectura y calidad | Landing, frontend, APIs, PHP 8, MySQL 8, Linux/Docker, Git, pruebas y documentación. | Requisitos transversales de las secciones 6 a 8 y de cada avance. Deben verificarse por evidencia propia, no por la existencia del archivo o del plan. |

## 4. Producto mínimo funcional para medir en el paso 6

Este es el **recorte funcional de referencia** tomado del nivel mínimo general. Es un inventario de funciones, todavía **no** un conteo de puntos de función ni una promesa de que entre en 580 horas.

1. Acceso por los cuatro roles previstos y administración de usuarios.
2. Alta, consulta, modificación y baja lógica de contenedores, con ubicación, tipo y estado.
3. Alta, consulta, modificación y baja lógica de camiones, más consulta de su inventario.
4. Registro y consulta del stock básico de contenedores de repuesto.
5. Registro de incidencias por un usuario autorizado, asignación a una cuadrilla, seguimiento e historial hasta resolución o descarte.
6. Listado simple de incidencias abiertas y cerradas, filtrable por un criterio acordado.
7. Soporte mínimo de datos para zona, tipo de residuo y cuadrilla cuando las funciones anteriores lo requieran.

La landing, las APIs, la base de datos, el control de acceso, las pruebas y el despliegue son condiciones de realización de estas funciones. El conteo IFPUG deberá delimitar la frontera del sistema y decidir qué operaciones son procesos elementales; esta lista no equivale automáticamente a siete funciones contables.

**Fuera de este mínimo general, con tratamiento separado:** análisis estadístico avanzado, predicciones de llenado, trazabilidad completa de reciclaje, planificación optimizada de recorridos y mantenimiento detallado de flota. Centros y maquinaria se mantienen porque fueron exigidos para el segundo avance. Mapa, panel, API de recolección y alertas no se eliminan de la tercera entrega por esta lista; su obligación específica se resuelve en el registro de decisiones.

## 5. Registro de decisiones

| ID | Decisión o regla | Estado | Fundamento / próxima comprobación |
|---|---|---|---|
| A-01 | Mantener separados el corte documental del 14/09 y el estado técnico del 12/10. | Regla de esta revisión | Evita atribuir a la segunda entrega el trabajo posterior. |
| A-02 | Usar la consigna como fuente de requisitos institucionales, no como prueba de procesos reales de una intendencia. | Regla de esta revisión | La entrevista aplicada no produjo datos utilizables. |
| A-03 | Nombrar a la Intendencia de Montevideo como organismo piloto **propuesto** hasta hallar aceptación verificable. | Pendiente de evidencia | Buscar convenio, correo o acta; sin ello, corregir las afirmaciones del integrador y costo-beneficio. |
| A-04 | Considerar las 580 horas como capacidad estimada del equipo, no como esfuerzo demostrado para el mínimo. | Regla de esta revisión | Medir el recorte en puntos de función y confrontarlo con horas reales en el paso 6. |
| A-05 | Mantener «solo vecino registrado» para informar una incidencia. | Decisión previa del equipo | `Checklist 3ra entrega.md`; verificar su reflejo en RF, CU e interfaz en pasos 3 y 5. |
| A-06 | Distinguir mínimo general de entregables expresos de Full Stack: centros y maquinaria eran exigidos para el segundo avance. | Interpretación directa de la consigna | Secciones 5 y 9.4. |
| A-07 | No presentar las sesiones de usuarios como ejecutadas ni inferir feedback. | Hecho documentado | Documento de UTU LAB, sección 4.7 y anexo 10.1; registrar resultados cuando existan. |
| A-08 | Mapa, panel, alertas y API de recolección siguen como compromisos de la sección 9.4 del tercer avance. | Confirmado en esta revisión | Fornos confirmó el 12/10 que no hay autorización docente escrita para reducirlos. El plan P0-P10 clasifica varios como deseables y requiere ajuste de prioridades y capacidad antes de cerrar la tercera entrega. |
| A-09 | Las alertas calculadas en el panel son la solución técnica elegida por el equipo, sin tabla ni correo automático. | Decisión previa del equipo | Verificar que satisfaga el alcance que el docente espera para «notificaciones o alertas». |
| A-10 | La validación municipal y de usuarios es una actividad pendiente, no una condición que se pueda dar por cumplida con diagramas o tests. | Pendiente de evidencia | Guardar fichas, fechas, perfiles, observaciones y decisiones derivadas si se ejecuta. |

## 6. Datos que faltan para cerrar el alcance

1. Una planificación realizable para mapa, panel y alertas dentro del tercer avance, o una autorización docente escrita si se acuerda reducir ese compromiso. Hoy no existe esa autorización.
2. Evidencia de relación con un organismo piloto, si existe.
3. Resultados de validación con usuarios o decisión expresa de mantener el diseño como hipótesis sin validar.
4. Desglose de procesos y archivos del mínimo general para comprobar si cabe en las horas disponibles.
5. Verificación funcional del stock de contenedores de repuesto y de los informes simples, sin inferir cobertura por la mera presencia de tablas.

## 7. Entrega al paso 3

La ERS corregida debe tener una columna **fuente** (`consigna`, `decisión del equipo`, `necesidad validada` o `hipótesis`) y una columna **horizonte** (`2.º avance`, `3.º avance`, `futuro`). Ningún RF se vuelve obligatorio o se elimina solo porque haya código escrito o porque no haya tiempo: cada cambio de prioridad requiere fundamento y versión. Las discrepancias A-03, A-08 y A-10 deben quedar visibles hasta resolverse.
