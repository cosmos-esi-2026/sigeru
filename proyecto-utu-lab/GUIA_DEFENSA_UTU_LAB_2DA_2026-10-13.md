# Guía de defensa de Proyecto UTU LAB — segunda entrega de SiGeRU

**Para estudiar el 13/10/2026. Docente: Diego Pereira. Equipo COSMOS.**  
**Corte que se defiende:** la segunda entrega presentada el **14/09/2026**. El documento específico de UTU LAB está fechado el **03/09/2026**. Hay trabajo posterior de la tercera entrega; cuando aparezca en esta guía está señalado como posterior.

> **Idea central:** partimos de un problema de información dispersa en la gestión de residuos. En julio lo analizamos y dibujamos una primera maqueta. En la segunda entrega convertimos esa maqueta en ocho pantallas recorribles, revisamos el problema y el alcance, y preparamos una prueba de usabilidad. **La prueba con personas y el registro de su feedback no se hicieron antes de la entrega.** Saber decir esto con precisión es más importante que memorizar frases.

## Cómo estudiar esta guía

1. **Si tenés 15 minutos:** leé la «Hoja de último repaso» al final y ensayá el guion de 90 segundos.
2. **Si tenés una hora:** leé los capítulos 1 a 7 y hacé el recorrido de demostración.
3. **Si tenés más tiempo:** leé las preguntas, tapá las respuestas y contestá en voz alta. Después comprobá las respuestas.
4. **No memorices palabras que no entendés.** Volvé al ejemplo del contenedor de Ejido y Colonia: toda la materia puede explicarse con ese caso.

---

## 1. La historia entera en una página

SiGeRU significa **Sistema de Gestión de Residuos Urbanos**. La consigna general pide diseñar un sistema para administrar recursos de recolección, seguir incidencias y consultar información. UTU LAB mira el proyecto desde el **Pensamiento de Diseño**: entender a las personas y el problema, proponer una solución, construir un prototipo, probarlo e iterar.

Imaginá un contenedor desbordado en **Ejido y Colonia**. El aviso llega por teléfono; alguien busca en una planilla dónde está el contenedor, pregunta por otra vía si hay camión disponible y luego intenta averiguar si la cuadrilla fue. La hipótesis del equipo fue que **la información está dispersa y no hay un seguimiento común**. SiGeRU propone conectar aviso, contenedor, zona, prioridad, responsable, estado e historial en un mismo sistema. Su posible efecto social es responder mejor y rendir cuentas; **ese efecto todavía no fue medido en una intendencia**.

En la **primera entrega** se planteó el desafío, se hizo análisis FODA y de casos, se creó una *persona* ficticia (Mariana Pereira, supervisora municipal), se recorrieron E1 (explorar) y E2 (definir), se generaron ideas con *brainstorming*, se priorizaron módulos y se dibujaron ocho pantallas. Eran una figura y una descripción, todavía sin navegación real.

En la **segunda entrega** se hicieron ocho archivos HTML en escala de grises que permiten pasar de landing a login, panel, contenedores, registrar incidencia, detalle, camiones y reportes. Un selector permite ver distintos menús y paneles para administrador, cuadrilla, operario de acopio y vecino. Esta maqueta **simula el recorrido**; sus números son de ejemplo, no hay sesión real, base de datos ni acciones persistentes.

También se preparó una **prueba de usabilidad** con cuatro perfiles, cuatro tareas, guion y ficha de observación. La letra exigía **hacer** la validación con personas y registrar lo que dijeron e hicieron. Eso **quedó pendiente**. Los ajustes documentados provienen de construir el prototipo, revisar la coherencia y calcular el tamaño del sistema; **no son hallazgos de usuarios**.

Se revisó la solución: el problema de información dispersa se mantuvo como **hipótesis de trabajo**, se detectó que faltaban requisitos escritos de incidencias, y se distinguió entre sistema completo y alcance realizable. Se reflexionó sobre sostenibilidad y se hizo una retrospectiva franca: se cumplieron dos de cinco compromisos previos, uno a medias y dos no; el equipo concentró mucho trabajo cerca del plazo.

**Una respuesta de 20 segundos:** «En UTU LAB pasamos de definir el problema y dibujar pantallas a tener una maqueta navegable. Nos sirvió para detectar un error de navegación por rol. Diseñamos cuatro pruebas de usabilidad y cómo registrar sus resultados, pero no llegamos a ejecutarlas; por eso no atribuimos ningún cambio a usuarios. Sí revisamos el alcance con métricas y registramos lo que funcionó y lo que no en el proceso».

---

## 2. Qué pide exactamente la letra y qué hay para mostrar

La fuente principal es la **sección 9.2, «Proyecto UTU LAB», páginas 14-15** de la consigna general. La **sección 10.2, página 27**, vuelve a listar requisitos. En 9.2 hay **seis bloques** para la segunda entrega:

| Requisito de 9.2 | En palabras simples | Evidencia de septiembre | Estado honesto |
|---|---|---|---|
| 1. Prototipo funcional *lo-fi* | Poder recorrer una versión inicial de la experiencia de uso | Ocho HTML, CSS y JS; documento de UTU LAB §3, figura 6 | **Hecho como maqueta navegable.** No funciona como sistema de datos. |
| 2. Validación con usuarios reales o simulados **y feedback** | Sentar a personas frente al prototipo, observar tareas, registrar resultados y aprendizajes | Método, perfiles, cuatro escenarios, guion y ficha en §4 y anexos 10.2-10.3 | **Pendiente:** no se hicieron sesiones ni se obtuvo feedback. |
| 3. Revisar la definición del problema | Decir qué hipótesis se sostiene y qué cambia con lo aprendido | §5.1-5.2 | **Hecho como revisión documental y de diseño;** falta contraste con usuarios. |
| 4. Ajustar la propuesta después del testeo | Convertir aprendizajes en decisiones de solución | §5.3-5.4: ajuste por métricas y coherencia | **Parcial respecto de la letra:** hubo ajustes, pero no salieron de testeo con personas. |
| 5. Materiales y sostenibilidad | Pensar durabilidad e impacto ambiental de lo elegido | §6, cuatro planos | **Documentado.** Sus beneficios ambientales son posibilidades, no mediciones. |
| 6. Evidencias de iteración y segunda retrospectiva | Mostrar cambios, capturas/documentos y revisar el trabajo del equipo | §2, §7, figura 7, repositorio y prototipo | **Documentado en parte:** hay texto, figura y archivos; faltan resultados de validación y en ese PDF no hay capturas del prototipo ni del tablero. |

La consigna dice que el prototipo debe representar la experiencia de un «**cooperativista o administrativo**». SiGeRU es un proyecto de **gestión municipal de residuos**; el equipo tomó el perfil administrativo como **supervisora municipal** y añadió las perspectivas de cuadrilla, acopio y vecino. Si preguntan por esa palabra, no inventes una cooperativa cliente: explicá la adaptación al dominio de residuos y a los roles de la consigna general (§5).

La sección 10.2 resume la segunda entrega con menos detalle. Para estudiar, usá la lista más completa de 9.2; **no existe una rúbrica numérica específica de UTU LAB** en las páginas revisadas. La letra de la **tercera entrega** ya pide prototipo más cercano al producto final y **nueva** validación con comparación inicial/final. Eso hace más importante reconocer y resolver la falta de una línea base de usuarios.

### Qué se presentó y qué fecha tiene cada cosa

- El **documento específico** `Proyecto_UTU_LAB_2da_Entrega_SiGeRU.pdf` tiene **24 páginas** y fecha **03/09/2026**. Expone el prototipo, el plan de validación, la revisión del problema, sostenibilidad y retrospectiva.
- El **prototipo** está en `sigeru/proyecto-utu-lab/prototipo-lofi/` y contiene ocho páginas HTML, `lofi.css`, `lofi.js` y un LEEME.
- La entrega presencial del **14/09/2026** fue un **PDF integrador** de 319 páginas con varias materias; la copia congelada está en `entregas/2da-entrega-2026-09-14/`. Una versión de trabajo del integrador se rehízo después; **no atribuyas esas correcciones posteriores al 14/09**.
- La guía breve previa de UTU LAB y Emprendedurismo es útil como repaso, pero esta guía corrige un detalle esencial de la demostración: varios controles del prototipo **solo se ven; no ejecutan acciones**.

---

## 3. Desde cero: qué problema intentamos resolver

### 3.1 El contexto

Un servicio de residuos coordina **contenedores, camiones, cuadrillas, centros de acopio, zonas, incidencias y vecinos**. Cada pieza genera datos: el contenedor tiene ubicación y estado; el camión, disponibilidad; una incidencia tiene fecha, prioridad y responsable; una cuadrilla tiene tareas. Si todo está en planillas, llamadas y mensajes separados, nadie ve la historia completa al mismo tiempo.

La **formulación del equipo** fue: a los responsables municipales les falta una herramienta única para ver y actualizar recursos e incidencias y seguir cada reclamo desde que se informa hasta que se resuelve. Esa formulación está **basada en la consigna, escenarios y coherencia del diseño**. La entrevista intentada no dio datos utilizables y no hay resultados de usuarios externos: **es una hipótesis de análisis, todavía no una necesidad validada por una intendencia**.

### 3.2 Cadena causa → problema → efecto → impacto

```text
Registros dispersos, sin responsable ni historial común
                    ↓
No hay una vista actualizada y trazable de la operación
                    ↓
Demoras, tareas duplicadas, asignaciones mal informadas, reportes incompletos
                    ↓
Posibles efectos sobre limpieza, costo y confianza de la comunidad
```

**¿Qué puede hacer un software?** Registrar en un lugar común, relacionar los datos, hacer visible quién se ocupa, mostrar estados e historial, y permitir consultas. **¿Qué no puede prometer por sí solo?** Que la ciudad quedará limpia, que siempre habrá camiones o que bajarán los costos: esas consecuencias dependen de recursos y decisiones del organismo. Si te piden impacto, hablá de **impacto esperado o potencial**, nunca de un resultado demostrado.

### 3.3 Ejemplo que conecta todo

1. Un vecino ve desbordado el contenedor **CT-0231** en Ejido y Colonia.
2. Se registra una **incidencia**: tipo «desborde», contenedor, descripción, fecha y prioridad. Una incidencia es el expediente de un problema concreto; el contenedor es el bien físico.
3. La supervisora consulta qué pasó antes con ese contenedor y si hay cuadrilla y camión disponibles.
4. Se asigna un responsable. El estado pasa de «abierta» a «en curso» y luego a «resuelta» cuando se atiende.
5. El **historial** conserva los cambios y permite responder: quién hizo qué, cuándo y con qué resultado.
6. Un **reporte** puede agrupar incidencias por zona y período para ver si el problema se repite.

En el **prototipo de septiembre** solo se **representa visualmente** esa secuencia: escribir en un campo o hacer clic en «Guardar» no crea un registro real. Si se pregunta por el sistema de septiembre, separá con claridad maqueta, requisitos/modelo y aplicación real.

### 3.4 Roles: quién necesita qué

| Rol | Pregunta cotidiana | Vista propuesta |
|---|---|---|
| Administrador municipal | «¿Qué es urgente y quién se ocupa?» | Indicadores, incidencias generales, contenedores y recursos. |
| Cuadrilla | «¿Qué me toca y con qué camión?» | Sus tareas asignadas. |
| Operario de acopio | «¿Qué capacidad y recursos debo controlar?» | Información relevante del centro y contenedores. |
| Vecino | «¿Recibieron mi reclamo y en qué quedó?» | Sus propios reclamos y un acceso para informar. |

La distinción por rol responde a dos necesidades: **menos ruido** para cada persona y, en el sistema real, **permisos**. El prototipo solo cambia partes de la interfaz; **no implementa seguridad**. No hay autenticación verdadera y se pueden abrir archivos HTML por URL aunque no aparezcan en el menú.

---

## 4. Cómo llegamos hasta la segunda entrega: la primera, explicada fácil

La segunda entrega tiene sentido porque **continúa decisiones de julio**. No hace falta memorizar las 32 páginas de la primera, pero sí este camino:

### E1: explorar antes de resolver

Se estudiaron la consigna y el contexto con un **FODA**, un **estudio de casos** y un **mapa de actores**. El FODA separa fortalezas y debilidades internas de oportunidades y amenazas externas; no equivale a entrevistar usuarios. Los casos y el mapa ayudaron a listar quién aporta datos y qué necesita cada rol. El límite declarado fue que **no había validación directa aprovechable con usuarios**.

### Persona y recorrido

Una **persona** es un arquetipo ficticio para discutir decisiones concretas. La primera entrega creó a **Mariana Pereira**, 41 años, supervisora de operaciones, con experiencia administrativa y manejo digital intermedio. Su necesidad resumida: «ver en un solo lugar qué está pasando y quién se ocupa». **Mariana no es una entrevistada real.**

El *recorrido actual* de Mariana ante un contenedor desbordado tenía cinco pasos: recibir el aviso, verificar ubicación y antecedentes, asignar recursos, seguir la tarea y cerrar/reportar. En cada paso había una fricción; de allí salieron oportunidades de interfaz: un registro común, ficha con historial, disponibilidad de recursos, estados visibles y reportes con filtros.

### E2: definir

E2 convirtió la exploración en una frase de problema y una pregunta de diseño. La pregunta fue, en esencia: **«¿Cómo dar a los responsables municipales una vista centralizada, clara y actualizable de incidencias y recursos para mejorar la coordinación?»**. Se fijaron objetivos y criterios iniciales de éxito, como completar el flujo principal sin perderse y poder consultar estado, prioridad y responsable.

### Idear y priorizar

Se usó **brainstorming**: primero generar ideas sin juzgarlas, después agrupar y evaluar impacto y esfuerzo. Entraron registro de incidencias, asignación, panel, recursos y reportes. Se pospusieron QR, predicciones y optimización automática de rutas por esfuerzo. Esto muestra que la propuesta **no salió de elegir pantallas al azar**: cada módulo responde a una fricción del recorrido.

### Primera maqueta y tablero

Se dibujaron ocho pantallas y se describió cómo se navegarían. El tablero Trello «SiGeRU — COSMOS» se creó el **26/08**, más tarde de lo prometido, y el documento de septiembre registra **40 tarjetas** y cinco columnas: Ideas/Backlog, Pendiente, En proceso, En revisión y Terminado. Según la regla del equipo, una tarjeta necesitaba revisión de otra persona antes de darse por terminada. **Tener tablero no demuestra que la rutina de colaboración se haya sostenido**; la retrospectiva precisamente muestra esa dificultad.

### Puente entre entregas

```text
Julio: problema y usuario hipotético → ideas → ocho pantallas dibujadas
Septiembre: ocho pantallas HTML recorribles → error de rol detectado → prueba preparada
Pendiente: personas completando tareas → feedback real → cambios basados en ese feedback
```

---

## 5. El prototipo: qué se hizo, por qué y cómo contarlo

### 5.1 Qué significa «lo-fi funcional» aquí

*Lo-fi* significa **baja fidelidad**: pocos detalles de marca y acabado, suficiente forma para discutir estructura y recorrido. Se eligió una escala de grises, sin logo definitivo ni tipografía de marca, para centrar la conversación en «¿encuentra lo que busca?» antes de discutir estética.

«Funcional» en esta entrega significa **recorrible**: hay enlaces entre pantallas y una selección de rol que cambia menú y panel. **No** significa que los formularios procesen datos. El archivo `lofi.js` identifica la pantalla, dibuja una barra de aviso, guarda el rol elegido en `sessionStorage`, arma el menú y oculta algunos bloques con `data-roles`. No hace llamadas a API, no autentica y no escribe en base de datos. Muchos botones y selectores representan la acción futura pero todavía no hacen nada.

### 5.2 Por qué HTML en vez de Figma

La primera retrospectiva había prometido Figma. El equipo cambió a HTML porque: (1) el prototipo queda en el mismo repositorio y con historial; (2) abre sin depender de una cuenta o conexión; (3) puede mostrarse en cualquier navegador; (4) el equipo ya conoce HTML. **Se cambió la herramienta, no el objetivo de probar el recorrido.** Es una decisión defendible siempre que se diga que se cambió.

### 5.3 Las ocho pantallas, de memoria

| N.º | Archivo | Qué se intenta comprobar |
|---:|---|---|
| 1 | `index.html` | Entender para qué sirve el sistema y entrar. |
| 2 | `login.html` | Encontrar el ingreso y la solicitud de registro. Los campos no validan. |
| 3 | `panel.html` | Ver primero prioridades y tareas según rol. Es el centro de navegación. |
| 4 | `contenedores.html` | Ubicar un contenedor, ver estado e historial. Los filtros son visuales. |
| 5 | `incidencia-nueva.html` | Imaginar el registro de un problema en un formulario corto. «Guardar» solo lleva a otra pantalla. |
| 6 | `incidencia-detalle.html` | Ver prioridad, estado, responsable e historial; imaginar asignación y cierre. «Asignar» no ejecuta nada. |
| 7 | `camiones.html` | Consultar disponibilidad y mantenimiento de la flota. |
| 8 | `reportes.html` | Imaginar consulta por zona/período. «Generar» y «Exportar» no calculan ni descargan nada. |

Pantallas 1 y 2 son **conceptualmente públicas**; 3 a 8 representan una sesión. En el HTML de la maqueta **no existe control real de sesión**. La figura 6 del PDF de septiembre es el mapa de ese recorrido.

### 5.4 Lo que reveló construirlo

La primera versión dejaba que el **vecino** viera información general de toda la ciudad. Eso contradecía el criterio de la primera entrega: «mostrar a cada rol lo que le corresponde». Se añadió filtrado del **menú** y de bloques del **panel**; el vecino pasó a ver «Mis reclamos» y «Informar un problema». Es un **hallazgo de construcción y revisión interna**, no una observación de usuarios.

**Límite técnico que tenés que conocer:** el filtrado no cubre todas las pantallas ni protege las rutas. En `incidencia-detalle.html` los controles de asignación y cierre son visibles aunque se elija vecino; y los enlaces de pie también llevan a pantallas fuera del menú. Por eso no afirmes «los permisos están implementados» ni «cada rol no puede acceder a otras pantallas». Decí: «El selector demuestra la intención de navegación por rol en el menú y panel; al ser un lo-fi, el control de acceso se resuelve en el sistema real y el prototipo aún tiene huecos de navegación por rol».

### 5.5 Prototipo versus producto

| Pregunta | Maqueta de UTU LAB | Sistema real |
|---|---|---|
| ¿Qué se ve? | Pantallas y datos escritos de antemano | Datos que llegan de APIs/base |
| ¿Qué pasa al «Guardar»? | Se navega o se muestra una ilustración | Debería validar y persistir según el módulo |
| ¿El rol es seguro? | No; es un selector local para recorrer el diseño | Requiere autenticación y autorización de servidor |
| ¿Para qué sirve? | Preguntar si el flujo se entiende | Ejecutar operaciones de verdad |

**Corte histórico:** el 14/09 la aplicación real ya tenía varias pantallas y CRUD de recursos, pero **el flujo operativo de incidencias de la maqueta no estaba implementado**. En octubre se añadió una API de incidencias y un historial; las pantallas y reportes seguían planificados al 12/10. Si hoy mostrás código nuevo, identificá que es **avance de tercera entrega**.

---

## 6. La validación: lo más probable que pregunten

### 6.1 Qué es validar y qué no lo es

Una **prueba de usabilidad** pone a alguien delante de una tarea y observa si encuentra el camino, dónde duda, cuánto tarda y qué dice. No le preguntamos «¿te gusta?» como única medida. Tampoco basta con que un programador pruebe que un enlace devuelve HTTP 200. Eso comprueba un aspecto técnico; **no dice si una persona entiende la interfaz**.

Una persona **simulada** es alguien ajeno al proyecto que representa un rol a partir de una consigna. La letra permite docentes, compañeros u otros participantes. Un usuario simulado puede descubrir problemas de navegación, pero no reemplaza la validación de procesos municipales reales. La *persona* ficticia Mariana es un **artefacto de diseño**, no una participante de prueba.

### 6.2 Por qué se dejó la entrevista

La entrevista de diez preguntas prevista en julio se intentó. Según el registro del equipo, las respuestas fueron demasiado generales para traducirlas en decisiones y el instrumento se descartó el **26/08**. Se descartó la **herramienta**, no la **obligación de validar**. Con un prototipo recorrible, se eligió pasar de lo que alguien dice que haría a lo que efectivamente intenta hacer frente a una tarea.

### 6.3 El plan que sí quedó escrito

- **Participantes previstos:** cuatro personas externas al equipo, una representando cada rol; entre **20 y 30 minutos** por persona.
- **Equipo:** una persona modera, otra observa y anota. El moderador lee la tarea y no señala dónde hacer clic.
- **Material:** prototipo abierto, guion, ficha y reloj.
- **Medidas:** completó o no, tiempo, pasos en falso, punto de duda y frase textual. Después se escribe la decisión de diseño sugerida.
- **Cuatro tareas del PDF:** registrar un desborde de Ejido y Colonia con prioridad alta; asignar una cuadrilla disponible; cerrar y consultar la historia; consultar incidencias abiertas del Centro en el último mes.
- **Registro previsto:** una ficha por participante, llenada durante la sesión, con fecha, perfil, moderador, observador y resultados de cada escenario.

### 6.4 La frase correcta sobre el estado

«La validación **está diseñada, no ejecutada**. Tenemos perfiles, tareas, criterios, guion y ficha, pero no sesiones ni feedback. El documento lo declara en §4.7 y en el anexo 10.1. El error del vecino lo detectamos al construir y revisar la maqueta, no por observación de un participante».

**Ojo con la fecha:** el PDF del 03/09 dice que habría que hacer las sesiones **antes del 09/09**. El PDF se entregó el **14/09** conservando esa fecha pasada. No repitas «las haremos antes del 9 de septiembre» como si fuera un futuro. Decí: «Ese plazo no se cumplió; el documento quedó con un plan anterior a la entrega. Hoy la tarea sigue pendiente y hay que calendarizarla con fecha real antes de usar sus resultados».

### 6.5 Dos límites metodológicos adicionales

1. **Rol y tarea no están del todo alineados.** El plan propone una persona por cada rol y luego cuatro tareas centradas sobre todo en acciones administrativas. No sería correcto pedir a un vecino que asigne una cuadrilla. Al ejecutar la prueba habría que asignar tareas según el rol o comparar tareas comunes con permisos equivalentes.
2. **La maqueta no cambia datos.** El botón «Asignar» no actúa, «Generar» no filtra y «Guardar» no persiste. La prueba podría medir si la persona **encuentra el control esperado y entiende la secuencia**, pero no afirmar que completó una transacción real. El criterio de éxito del PDF «completa el registro» tendría que operacionalizarse como «encuentra la pantalla y los campos correctos sin ayuda» para este prototipo, o habría que hacer interactivos los controles antes de medirlo literalmente.

Reconocer esos límites muestra comprensión del método. No inventes participantes, tiempos, frases ni mejoras «obtenidas».

---

## 7. Qué revisamos del problema y por qué cambió el alcance

### 7.1 Qué se mantuvo

El documento de septiembre mantiene cuatro ideas de julio: (a) la información dispersa y falta de trazabilidad como **problema de trabajo**; (b) la posibilidad de un producto dedicado a residuos frente a software municipal más general; (c) el vecino como actor con una vista propia; (d) la decisión de actuar sobre causas organizativas y no prometer eliminar por software los impactos finales. **«Mantener» es una conclusión interna del equipo, no prueba con usuarios externos.**

### 7.2 Qué contradicción se detectó

La incidencia aparecía en la propuesta, los casos de uso y el prototipo, pero la **Especificación de Requerimientos de julio no tenía requisitos funcionales para ese módulo**. Eso rompe la **trazabilidad**: una pantalla o un caso de uso debería tener una necesidad escrita que lo justifique. El documento de UTU LAB propuso agregar requisitos de registrar, asignar, cambiar estado, consultar historial, cerrar y filtrar incidencias. La ampliación de requisitos se documentó en septiembre, pero eso **no demuestra que el módulo estuviera programado**.

**Definición simple:** un *requisito* dice «el sistema debe permitir X»; un *caso de uso* describe cómo un actor logra X; un *prototipo* muestra cómo podría verse; el *código* ejecuta X. Son niveles distintos y no deben mezclarse.

### 7.3 El cálculo de puntos de función, sin susto

Un **punto de función (PF)** mide el tamaño funcional del software desde lo que el usuario puede hacer y los datos que maneja. **No mide líneas de código, calidad ni avance completado.** En el documento usado por UTU LAB, el sistema descrito entonces sumaba **228 PF ajustados**. Con un supuesto de **10 horas por PF**, se estimaron **2.280 horas**. El equipo había presupuestado **580 horas** disponibles:

```text
228 PF × 10 h/PF = 2.280 h estimadas
580 h ÷ 10 h/PF = 58 PF equivalentes
58 ÷ 228 = 0,254 ≈ 25 % del tamaño estimado
2.280 ÷ 580 ≈ 3,93 veces las horas disponibles
```

El PDF explica que, según el escenario de productividad, la demanda podía ser entre **2,4 y 5,5 veces** la capacidad. **No digas que «solo se implementó el 25 %»**: era una comparación de estimaciones, no una medición de código terminado. Después se agregó maquinaria y el alcance documentado pasó a **252 PF ajustados**; el 228 es la foto usada por el texto de UTU LAB fechado el 03/09. La capacidad de 580 horas también era un **presupuesto del equipo**, no horas registradas una por una. El cálculo sirvió para obligar a priorizar; no autoriza a eliminar por cuenta propia requisitos obligatorios de otras secciones de la letra.

### 7.4 Qué se comprometió en la tabla del documento

| Bloque | Decisión escrita en UTU LAB §5.4 | Cómo interpretarla en la defensa |
|---|---|---|
| Acceso/usuarios, contenedores, incidencias, camiones | Construir completos | **Compromiso de alcance**, no afirmación de que todo estaba programado el 14/09. |
| Panel | Indicadores, sin gráficos | Recorte de presentación. Revisar siempre la obligación específica de la tercera entrega. |
| Reportes | Listados y filtros; sin predicciones | Recorte funcional propuesto; en septiembre la pantalla de reporte era maqueta. |
| Centros de acopio | Construir básico | Aunque julio los llamara recomendados, Full Stack los pidió para esta entrega. |
| Cuadrillas/rutas | Lo necesario para asignar incidencia | La planificación avanzada se pospone. |
| Mapa | Futuro según ese documento | La sección específica de Full Stack vuelve a pedir mapa para la tercera entrega; no lo presentes como requisito cancelado sin aval docente. |

**Respuesta clave:** «El cálculo nos hizo distinguir visión completa de compromiso por etapas. No podemos llamar ‘terminado’ al módulo de incidencias porque esté en una tabla o en una maqueta; tampoco podemos recortar una obligación de la letra sin acuerdo».

---
## 8. Materiales y sostenibilidad: la respuesta que tiene sentido para software

La letra pide reflexionar sobre **materiales, ambiente y durabilidad**. SiGeRU no fabrica un objeto físico. La respuesta del documento (§6) tiene cuatro planos:

1. **Durabilidad del software.** Se eligieron tecnologías abiertas y web (PHP, base de datos, Linux, HTML/CSS/JS), hardware modesto y versiones con soporte prolongado. La idea es que el organismo no dependa de una licencia que venza ni tenga que descartar equipos solo por usar la aplicación. El PDF menciona Ubuntu Pro como horizonte futuro; el servidor instalado en septiembre **no tenía Pro activado**. Evitá afirmar que se obtuvo un soporte extendido que no se activó.
2. **Bienes físicos gestionados.** Un historial por contenedor puede ayudar a decidir si repararlo o reemplazarlo; los datos de camiones pueden informar mantenimiento; el tipo de residuo y la capacidad de acopio pueden orientar decisiones. El software **hace visible información**; la reparación, las rutas y el tratamiento los decide y ejecuta el organismo.
3. **Huella propia.** El plan de inversiones estimó alrededor de **USD 159 al año** para servidor y dominio. Eso es una **estimación económica de infraestructura**, **no una medición de consumo eléctrico o emisiones**. No repitas como hecho cuantificado que «un camión en una jornada gasta más energía que el servidor en un año»: el documento no presenta una medición ambiental que pruebe esa comparación.
4. **Continuidad del proyecto.** Repositorio versionado, decisiones escritas y documentos generados desde código facilitan que otro equipo entienda y mantenga lo construido. El uso de herramientas portables ayudó a trabajar en distintas computadoras.

**Ejemplo para defender:** «Si vemos repetidas incidencias de rotura en el mismo contenedor, el historial puede justificar una reparación o un reemplazo. SiGeRU no decide ni prueba que ya se ahorró material; le da al responsable datos para decidir». Esta distinción entre **capacidad** y **resultado demostrado** sirve para casi todas las preguntas sobre sostenibilidad.

---

## 9. Iteración y segunda retrospectiva: qué aprendimos del trabajo

**Iterar** es hacer una versión, detectar un problema, cambiarla y dejar registro de por qué. La evidencia más concreta de iteración del prototipo es el cambio de navegación del vecino: la primera versión mostraba información general de la ciudad; después se filtraron menú y panel. No digas que fue una corrección solicitada por un usuario: salió de revisión interna mientras se construía.

La **retrospectiva** mira el **proceso de trabajo del equipo**, no evalúa si un botón es lindo. El documento comparó cinco acciones prometidas en la primera entrega:

| Acción prometida | Resultado registrado en septiembre | Lección |
|---|---|---|
| Aplicar entrevista y aprovecharla | Se intentó, pero no aportó datos utilizables; se descartó el instrumento | Había que preparar una validación más observacional y coordinar personas. |
| Crear y mantener Trello | Se creó el 26/08, con demora, y se configuró el 03/09 | Tener tablero ayuda, pero no reemplaza la rutina de actualizarlo. |
| Conectar ocho pantallas en Figma | Se hizo en HTML | Se puede cambiar de herramienta si se explica la razón y se cumple el objetivo. |
| Fijar fechas internas anteriores al plazo oficial | No se hizo | El trabajo volvió a acumularse cerca de la entrega. |
| Revisar coherencia entre documentos continuamente | Parcial; se hizo al final y encontró problemas | La revisión tiene que ocurrir antes de cerrar cada parte. |

El balance fue **dos cumplidas, una parcial y dos no cumplidas**. El documento registra **17 días sin aportes al repositorio entre el 31/07 y el 16/08**. También contiene un gráfico con porcentajes y días de actividad; la guía previa advierte que el conteo fino no pudo reproducirse exactamente con el historial local. Si te preguntan, apoyate en el período de inactividad y en el patrón, y tratá las cifras más finas como **datos del gráfico fechado el 03/09**, no como una auditoría actual.

La conclusión del equipo fue: **se completaban entregables concretos, pero costaba sostener hábitos**. Por eso la segunda retrospectiva no termina en «hay que organizarse mejor»; propuso acciones con un **disparador**: dar fecha y hora a validaciones, poner vencimientos propios en Trello, revisar el trabajo de otro integrante antes de «Terminado», actualizar el checklist después de traer cambios del repositorio. Que esas acciones estén escritas **no significa que todas se hayan cumplido**.

### Coherencia con otras materias

UTU LAB no está aislada:

```text
Necesidad / hipótesis de UTU LAB
        ↓
Requisitos y casos de uso (Ingeniería de Software)
        ↓
Prototipo de pantallas (UTU LAB)
        ↓
Datos, API e interfaz real (Full Stack)
        ↓
Pruebas con personas y nueva iteración (pendiente en este corte)
```

El control de coherencia encontró tres asuntos en el PDF de septiembre: faltaban requisitos escritos de incidencias; el registro interno decía que un relevamiento de sistema operativo no estaba subido, aunque sí estaba; y una corrección del router de la API figuraba pendiente cuando ya se había hecho. El hallazgo de UTU LAB más importante es **el primero** porque afecta la relación entre problema, requisito y solución. Los otros dos muestran que el checklist debía verificarse contra el repositorio.

---

## 10. Guion oral listo para ensayar

### 10.1 Versión de 90 segundos

> «SiGeRU nace de una dificultad de coordinación en la gestión de residuos: los datos de contenedores, camiones, cuadrillas y reclamos pueden quedar en lugares separados, y así es difícil saber qué problema hay, quién lo atiende y en qué estado quedó. Esa es la hipótesis de trabajo del equipo, tomada de la consigna y el análisis; todavía no está validada con usuarios municipales.  
> En la primera entrega exploramos actores y contexto, definimos el problema y dibujamos ocho pantallas. En la segunda convertimos ese diseño en una maqueta lo-fi de ocho páginas HTML que se puede recorrer. La hicimos deliberadamente simple para probar navegación; el selector permite mirar la experiencia como administrador, cuadrilla, operario de acopio o vecino. Mientras la construíamos vimos que el vecino podía ver información general de la ciudad y corregimos el menú y el panel.  
> Preparamos una prueba de usabilidad con cuatro perfiles, cuatro tareas, guion y fichas, pero las sesiones no se ejecutaron; por eso no tenemos feedback de usuarios ni afirmamos que el diseño fue validado. Revisamos además una falta de requisitos para incidencias y el tamaño del alcance: el cálculo que teníamos era 228 puntos de función, unas 2.280 horas frente a 580 previstas. Eso nos obligó a separar visión completa de etapas realizables. Cerramos con sostenibilidad y una retrospectiva que reconoce tanto lo hecho como la dificultad del equipo para sostener el ritmo». 

No hace falta decirlo idéntico; sí mantener **problema → primera entrega → prototipo → validación pendiente → revisión → aprendizaje**.

### 10.2 Versión de 4 minutos, por bloques

1. **Problema (35 s).** Usá el contenedor de Ejido y Colonia. Conectá aviso, ubicación, responsable, estado e historial. Decí que es la hipótesis del equipo.
2. **Primera entrega (35 s).** E1, E2, Mariana, ideas y ocho pantallas dibujadas. «La segunda no empezó de cero».
3. **Prototipo (80 s).** Mostrá landing, panel y cambio a vecino. Explicá por qué lo-fi y por qué HTML. Decí que la interfaz representa acciones pero no guarda datos.
4. **Validación (55 s).** Explicá participantes, tareas y mediciones; reconocé que faltan sesiones y feedback. Si el docente insiste, decí que el plazo del 09/09 no se cumplió.
5. **Revisión (45 s).** Faltaban requisitos de incidencias; PF frente a horas; recorte por etapas. Aclaración: los ajustes no salieron de testeo con personas.
6. **Sostenibilidad y retro (30 s).** Historial para decisiones de mantenimiento; dos de cinco compromisos cumplidos, uno parcial, dos no; acciones con disparador.

### 10.3 Transiciones naturales para sonar claro

- «Ahora separo lo que **diseñamos**, lo que **construimos** y lo que **probamos con personas**».
- «Este enlace me deja mostrar el recorrido; los datos de la pantalla son ejemplos».
- «Este cambio surgió al construir el prototipo, antes de la validación externa».
- «La estimación de PF nos ayudó a priorizar; no es una medición de funciones ya terminadas».
- «La evidencia disponible permite afirmar esto; para afirmar más necesitaríamos las fichas de sesión».

---

## 11. Demostración de 2 a 3 minutos, sin sorpresas

### Preparación

Desde la carpeta raíz `SIGERU 2026`, en PowerShell:

```powershell
& '.\herramientas\php\php.exe' -S localhost:8090 -t '.\sigeru\proyecto-utu-lab\prototipo-lofi'
```

Luego abrí `http://localhost:8090/` en el navegador. El servidor local se detiene con `Ctrl+C`. También se puede abrir `index.html` con doble clic, pero el cambio de rol usa `sessionStorage` y puede comportarse distinto con archivos locales. El puerto 8090 es solo para servir esta maqueta, no es la API del sistema.

### Recorrido seguro

| Paso | Hacé esto | Decí esto |
|---|---|---|
| 1 | Landing → «Ingresar» → en login, «Ingresar» → panel | «El ingreso es simulado; no se comprueba usuario ni contraseña». |
| 2 | Con rol administrador, señalá incidencias prioritarias y contenedores críticos | «La persona de julio necesitaba ver excepciones y responsables, no otra planilla plana». |
| 3 | Arriba, cambiá «Recorrer como» a **Vecino** | «El menú y el panel pasan a sus reclamos. El cambio nació de detectar que antes veía toda la ciudad». |
| 4 | Cambiá otra vez a **Administrador** → «Registrar incidencia» | «Esta es la información que necesitaría capturar el sistema; los campos y el código son de ejemplo». |
| 5 | Pulsá «Guardar» y señalá el detalle/historial | «El botón navega al detalle; no se guardó nada. En el producto real habría que persistir y validar». |
| 6 | Abrí «Reportes» y señalá zona/período | «Se ve cómo sería el filtro, pero Generar y Exportar no calculan nada en la maqueta». |

**No intentes demostrar** que «Asignar» cambia responsable, que el filtro modifica cifras ni que «Cerrar» cambia el historial. Esos controles son estáticos. Si alguien cambia a vecino y escribe directamente la URL de un detalle, verá controles que el menú no le ofrecía: eso confirma que **el prototipo no impone permisos**, y conviene responderlo de frente.

### Si no hay computadora

Usá la figura 6 del documento de septiembre y trazá con el dedo **landing → login → panel → incidencia nueva → detalle → reportes**. Señalá los cuatro roles en la tabla de §3.4. Después explicá el mismo límite: el mapa documenta navegación; no demuestra persistencia ni validación con usuarios.

---

## 12. Preguntas probables con respuestas que podés entender

### Conceptos y problema

**1. ¿Qué es UTU LAB en este proyecto?**  
Es la materia que examina cómo entendimos el desafío, a quién sirve la solución, cómo la prototipamos, la validamos y aprendimos del proceso. La programación es un medio; acá importa el vínculo **problema → usuario → diseño → prueba → iteración**.

**2. ¿Cuál es el problema central?**  
La hipótesis del equipo es que la información sobre recursos e incidencias está dispersa y carece de seguimiento común. Eso dificulta priorizar, asignar y explicar qué pasó con cada reclamo. Todavía falta contrastarla con usuarios reales.

**3. ¿Por qué es un problema social y no solo administrativo?**  
Porque demoras y falta de seguimiento pueden afectar limpieza, salud, uso de recursos y confianza de vecinos. Son **efectos esperados**, no impactos medidos por el proyecto.

**4. ¿Qué hace SiGeRU ante un contenedor desbordado?**  
La propuesta relaciona el aviso con un contenedor y una zona, asigna estado, prioridad y responsable, conserva historial y permite reportar casos repetidos. En septiembre el prototipo solo mostraba ese recorrido.

**5. ¿Qué diferencia hay entre actor, rol, persona y participante?**  
El **actor** es quien interactúa con el sistema; el **rol** define permisos y tareas (administrador, cuadrilla, acopio, vecino); la **persona** es un arquetipo ficticio, como Mariana; el **participante** es alguien concreto sentado en una prueba. Mariana no prueba que hubo una entrevista.

**6. ¿Qué fueron E1 y E2?**  
E1 exploró contexto, actores y supuestos. E2 formuló el problema, la pregunta de diseño, objetivos y criterios de éxito. Después se ideó, se prototipó y se preparó el test.

**7. ¿Qué es un FODA y qué les aportó?**  
Ordena fortalezas, oportunidades, debilidades y amenazas. Ayudó a pensar contexto, pero **no sustituye** hablar u observar usuarios.

**8. ¿Por qué eligieron a Mariana Pereira?**  
Representa a la supervisora que debe priorizar y asignar recursos, uno de los recorridos más amplios. Sirve para diseñar una vista clara. Es ficticia y no una funcionaria que haya dado aprobación.

**9. ¿Cuál fue la pregunta de diseño?**  
Cómo ofrecer a responsables municipales una visión centralizada y actualizable de incidencias y recursos para coordinar mejor y responder más rápido.

### Prototipo y decisiones

**10. ¿Qué significa *lo-fi*?**  
Baja fidelidad visual: suficiente para discutir recorrido y organización sin distraer con marca y acabado. En este caso son páginas grises sin logo definitivo.

**11. ¿Por qué llamarlo «funcional» si no guarda?**  
La letra admite prototipos analógicos o digitales. Aquí «funcional» se usó para la **navegación** y el selector de rol, no para operaciones de negocio. Si «funcional» se interpreta como procesar datos, hay que precisar que este prototipo no llega a eso.

**12. ¿Por qué HTML y no Figma?**  
Queda versionado con el código, funciona sin cuenta ni internet, abre en cualquier navegador y el equipo ya conocía HTML. El cambio respecto de lo prometido está documentado.

**13. ¿Qué muestran las ocho pantallas?**  
Entrada, login, panel, contenedores, nueva incidencia, detalle de incidencia, camiones y reportes. Recordá la secuencia con el caso del desborde.

**14. ¿Cuál fue el hallazgo de prototipar?**  
Al construir el recorrido por rol se vio que el vecino podía ver información general. Se filtraron menú y bloques del panel. Fue un hallazgo interno, **no feedback de una sesión**.

**15. ¿El vecino no puede acceder a datos municipales?**  
En la maqueta el menú y el panel se adaptan, pero no hay autenticación ni protección de rutas; algunas pantallas siguen visibles al abrir su URL. La restricción real debe aplicarse en el servidor. No vendemos el selector como seguridad.

**16. ¿Los números del panel y los reportes son reales?**  
No. Son datos de ejemplo escritos en HTML para representar densidad y jerarquía de la pantalla. No hay cálculo ni fuente de datos en el prototipo.

**17. ¿Qué diferencia hay entre prototipo y sistema real?**  
El prototipo permite entender el recorrido. El sistema real tiene que validar, autorizar, guardar, recuperar y actualizar datos. En septiembre existía aplicación real para varios recursos, pero el flujo de incidencias mostrado en la maqueta aún no estaba completo.

### Validación: las difíciles

**18. ¿Hicieron sesiones con usuarios?**  
No antes de la entrega. Estaban diseñadas, con cuatro perfiles, tareas, guion y ficha; no hay resultados ni feedback. Es una falta frente a la letra.

**19. Entonces, ¿cómo saben que a los usuarios les sirve?**  
Todavía **no lo sabemos**. Tenemos una hipótesis consistente con la consigna y un prototipo para ponerla a prueba. La revisión interna y las pruebas técnicas no sustituyen sesiones de usabilidad.

**20. ¿Por qué no valió la entrevista?**  
Se intentó, pero las respuestas registradas no fueron suficientemente concretas para derivar decisiones. Se dejó de usar ese instrumento y se diseñó una prueba basada en tareas observables.

**21. ¿Qué es «usuario simulado»?**  
Una persona externa al equipo que recibe un perfil y actúa en ese papel durante la prueba. La letra lo permite. Puede descubrir problemas del flujo, pero no representa por completo el trabajo de una intendencia.

**22. ¿Qué iban a medir?**  
Éxito de la tarea, tiempo, pasos erróneos, momento de duda y frase textual. Con eso se propondría una decisión de diseño. Sin fichas completadas no se puede afirmar un resultado.

**23. ¿Cómo evitarían sesgar la prueba?**  
Quien modera lee la consigna, pide pensar en voz alta y no indica dónde hacer clic. Otra persona observa. Se usa la misma forma de registro para comparar, y las tareas se adaptan a los permisos del rol.

**24. ¿Qué aprendieron del feedback?**  
No hubo feedback de participantes. Aprendimos al construir el prototipo que el vecino veía información que no correspondía; es **revisión interna**. Confundirlo con validación sería incorrecto.

**25. ¿Por qué el PDF dice «antes del 9/9» si lo entregaron el 14/9?**  
El documento quedó fechado el 03/09 y el plan no se ejecutó antes del plazo. Es una deuda de ejecución y de actualización del documento. No hay que presentar esa fecha vencida como plan vigente.

**26. ¿Qué harían para completar la validación de manera seria?**  
Definir fecha y participantes, asignar tareas compatibles con cada rol, decidir qué se puede medir en una maqueta estática, hacer las sesiones sin ayudar, guardar fichas fechadas y frases textuales, cambiar el diseño donde los datos lo justifiquen, y comparar el antes y el después en la tercera entrega.

### Revisión, alcance y sostenibilidad

**27. ¿Cambió el problema?**  
La formulación principal se mantuvo como hipótesis. Se detectó una brecha: incidencias estaba en casos de uso y prototipo, pero faltaba en requisitos funcionales de julio. También se redujo el compromiso por etapas según una estimación de esfuerzo.

**28. Si no testearon con personas, ¿de dónde salieron los ajustes?**  
De la construcción del prototipo, el control de coherencia entre documentos y el cálculo de puntos de función. No salieron de validación de usuarios; ese vínculo pedido por la letra quedó incompleto.

**29. ¿Qué es trazabilidad?**  
Poder seguir una necesidad desde su origen hasta el requisito, caso de uso, pantalla, código y prueba. Si una pantalla de incidencias no tiene requisito que la respalde, la cadena está cortada.

**30. ¿Qué es un punto de función y para qué lo usaron?**  
Es una medida estimada del tamaño funcional desde archivos y transacciones visibles al usuario. El cálculo de entonces: 228 PF ajustados, 2.280 h estimadas a 10 h/PF; el equipo planificaba 580 h. Sirvió para priorizar, no para afirmar cuánto estaba construido.

**31. ¿Por qué no hicieron todo si era «mínimo»?**  
Porque la estimación superaba varias veces las horas previstas, así que se propuso una secuencia. Pero un equipo no puede borrar obligaciones expresas de la letra por su cuenta: hay que mantenerlas en el plan o acordar formalmente cambios con el docente.

**32. ¿Qué se construyó de incidencias el 14/09?**  
Había propuesta, requisitos ampliados, casos de uso, representación en el prototipo y datos/modelo de apoyo; **no estaba completo el flujo operativo en la aplicación real**. La API de incidencias que existe en octubre es trabajo posterior de tercera entrega.

**33. ¿Cómo relacionaron software con materiales y sostenibilidad?**  
En durabilidad técnica, bienes físicos que el sistema gestiona, costo/huella estimada de infraestructura y continuidad del proyecto. El historial puede ayudar a reparar en vez de reemplazar; todavía no hay ahorro ambiental medido.

**34. ¿Qué fue lo más importante de la retrospectiva?**  
Dos acciones cumplidas, una a medias, dos no; hubo un período largo sin aportes. La causa recurrente fue no sostener rutinas. Por eso se propusieron disparadores concretos y revisión cruzada, además de nuevas fechas.

**35. ¿El Trello prueba que el equipo trabajó bien?**  
Prueba que se creó una estructura de tareas y responsables. No prueba por sí solo que se actualizó a tiempo o que todos colaboraron al mismo ritmo; la retrospectiva documenta problemas en eso.

**36. ¿Qué aportaste vos personalmente?**  
Respondé **solo lo que puedas sostener con tu experiencia y el historial**. Si no implementaste una parte, una respuesta honesta sería: «Esa sección la desarrolló el equipo; yo la estudié a partir del documento y del prototipo, y puedo explicar la decisión y sus límites». No te atribuyas entrevistas, pruebas ni código que no hiciste. Si preguntan por una función específica, describí lo que verificaste hoy y distinguí autoría de comprensión.

**37. ¿Se usó IA?**  
Si lo preguntan, declaralo con precisión según lo que efectivamente se usó y lo que puedas documentar. La existencia de un documento o código no sustituye la comprensión del equipo. Una respuesta segura es explicar **qué decisiones podés defender vos** y no atribuir a una persona una tarea que no hizo.

---

## 13. Glosario para no trabarte con las palabras

| Término | Significado simple | Ejemplo de SiGeRU |
|---|---|---|
| Pensamiento de Diseño | Proceso de entender usuarios, definir, idear, prototipar y probar | Partir del recorrido de Mariana antes de diseñar el panel. |
| Hipótesis | Explicación o necesidad razonable que falta comprobar | «La dispersión de datos es una causa de demora». |
| Actor | Tipo de persona o sistema que interactúa | Administrador municipal. |
| Rol | Conjunto de acciones y datos permitidos | Vecino ve sus reclamos. |
| Persona | Arquetipo ficticio para diseñar | Mariana Pereira. |
| Prototipo lo-fi | Versión simple para discutir recorrido | Ocho HTML grises. |
| Prototipo hi-fi | Versión visual cercana al producto final | Está pedido para la tercera entrega. |
| Usabilidad | Facilidad de entender y completar tareas | Encontrar «Registrar incidencia» sin ayuda. |
| Validación | Confrontar una hipótesis/diseño con personas o evidencia | Sesión con un participante y ficha; pendiente aquí. |
| Feedback | Lo que se observó y se recibió, registrado de forma comprobable | Frase literal y paso donde alguien se traba. |
| Iteración | Cambiar una versión por un hallazgo y justificarlo | Filtrar el panel del vecino. |
| Retrospectiva | Revisar cómo trabajó el equipo | Comparar cinco compromisos de julio con lo ocurrido. |
| Trazabilidad | Cadena de necesidad a requisito, diseño y prueba | Incidencia → requisito → caso de uso → pantalla. |
| Incidencia | Registro de un problema que requiere atención | Contenedor desbordado. |
| Estado | Etapa actual de un objeto o tarea | Abierta, en curso, resuelta. |
| Historial | Secuencia fechada de cambios | Cuándo se informó, asignó y resolvió. |
| Alcance | Qué funciones se promete entregar | Gestión básica frente a predicción de llenado. |
| Puntos de función | Estimación de tamaño funcional | 228 PF en el cálculo usado por el PDF de UTU LAB. |
| Kanban | Tablero de tareas por estado | Trello con Pendiente, En proceso, Revisión, Terminado. |
| FODA | Fortalezas, oportunidades, debilidades y amenazas | Herramienta de exploración, no test con usuarios. |
| API | Servicio al que una interfaz pide o envía datos | El prototipo no consulta ninguna. |
| Persistencia | Guardar datos para recuperarlos luego | «Guardar» en la maqueta **no** persiste. |

---

## 14. Prueba de comprensión: contestá sin mirar

1. Explicá el problema con el ejemplo de un contenedor desbordado en tres pasos.
2. ¿Qué partes de la definición del problema vienen de la consigna y cuáles de observación de usuarios reales?
3. ¿Qué diferencia a Mariana de una participante de prueba?
4. ¿Qué transformó la segunda entrega respecto de la primera?
5. ¿Qué hace de verdad el JavaScript del prototipo? Nombrá tres cosas.
6. Si hacés clic en «Guardar» una incidencia, ¿qué dato nuevo aparece en una base de datos?
7. ¿Cuál fue el error del vecino y cómo se descubrió?
8. ¿Qué pide la letra además de tener una ficha y un guion de validación?
9. ¿Qué tareas del plan no encajan con todos los roles?
10. ¿Por qué el 25 % de PF no significa «25 % del sistema terminado»?
11. ¿Qué se detectó al cruzar incidencias con la especificación de requisitos?
12. ¿Cómo dirías el impacto ambiental sin prometer resultados que no se midieron?
13. ¿Qué cinco compromisos de julio revisó la segunda retrospectiva?
14. ¿Qué diferencia hay entre un ajuste por revisar el prototipo y uno por feedback de usuarios?
15. Si el docente abre `incidencia-detalle.html` como vecino, ¿cómo explicás los controles que ve?

**Respuestas cortas:** (1) aviso disperso → no se ve responsable/estado → propuesta de registro e historial común; (2) hipótesis de consigna/análisis, sin validación municipal; (3) ficticia frente a persona real en sesión; (4) ocho pantallas dibujadas → ocho HTML navegables; (5) barra, menú, filtrado de algunos bloques y `sessionStorage`; (6) ninguno; (7) vecino veía información general, revisión interna; (8) ejecutar sesiones, guardar feedback y derivar aprendizajes; (9) asignar y cerrar son tareas de administración/cuadrilla, no del vecino; (10) es comparación de estimaciones de tamaño y horas; (11) faltaban RF de incidencias en la versión de julio; (12) hablar de decisiones que habilita, no de ahorro demostrado; (13) entrevista, tablero, Figma/prototipo, fechas internas, revisión de coherencia; (14) fuente de evidencia distinta; (15) la maqueta no implementa autorización y aún tiene huecos de rol.

---

## 15. Hoja de último repaso

### Siete frases que conviene saber decir

1. «El problema formulado es la **dispersión de información y la falta de trazabilidad**; todavía es una hipótesis sin validación municipal».
2. «La primera entrega hizo **E1, E2, persona, ideas y ocho pantallas dibujadas**; la segunda hizo una **maqueta navegable**».
3. «El prototipo **representa** el flujo; **no valida, no guarda, no calcula ni protege permisos**».
4. «La navegación por rol nos mostró un error del vecino y lo corregimos parcialmente en menú y panel; fue revisión interna».
5. «La validación con usuarios **quedó diseñada pero no ejecutada**; no hay feedback ni aprendizajes de participantes».
6. «El cálculo de **228 PF / 2.280 h frente a 580 h** ayudó a priorizar; es una estimación de alcance, no porcentaje de código terminado».
7. «La retrospectiva reconoció **2 compromisos cumplidos, 1 parcial, 2 incumplidos**, y propuso acciones con disparadores concretos».

### Tres errores que te harían quedar mal

- Decir que «probamos con cuatro usuarios» o citar comentarios de usuarios: **no ocurrió** en la entrega.
- Decir que «Guardar», «Asignar» o «Generar» realmente modifican datos en el lo-fi: **son controles de maqueta**.
- Presentar trabajo de **octubre** como si estuviera hecho en la entrega congelada de **septiembre**.

### Si la mente se queda en blanco

Volvé a este orden: **problema → persona → ocho pantallas → prueba pendiente → ajuste de alcance → retrospectiva**. Usá el caso de Ejido y Colonia para pasar de un punto a otro. Si no sabés un detalle, decí qué evidencia lo probaría y qué se puede afirmar con la evidencia actual.

---

## 16. Fuentes revisadas y dónde verificar cada afirmación

Esta guía está basada en archivos locales del proyecto; **no presupone que una sesión de usuario o un acuerdo con una intendencia hayan ocurrido**.

| Fuente local | Qué respalda |
|---|---|
| `vault/mind/raw/documentos/Proyecto_ESI_2026.pdf`, §5, §9.2 (pp. 14-15), §10.2 (p. 27) | Problema general, requisitos oficiales por entrega y materia. |
| `sigeru/proyecto-utu-lab/Proyecto_UTU_LAB_SiGeRU.pdf`, especialmente §§3-12 y 14 | Problema, Mariana, E1/E2, ideación, primera maqueta y compromisos de julio. |
| `sigeru/proyecto-utu-lab/Proyecto_UTU_LAB_2da_Entrega_SiGeRU.pdf`, §§2-10 | Lo que el equipo declaró el 03/09; la tabla de estado de la validación está en §4.7 y el anexo 10.1. |
| `sigeru/proyecto-utu-lab/prototipo-lofi/` | Ocho pantallas, comportamiento real de enlaces, selector, botones y datos de ejemplo. |
| `entregas/2da-entrega-2026-09-14/LEEME.md` y PDF integrador congelado | Forma y fecha de lo presentado presencialmente el 14/09. |
| `vault/mind/wiki/proyecto/Checklist 2da entrega.md`, bloque UTU LAB y cierre | Evidencia interna de qué estaba hecho, parcial o pendiente. |
| `sigeru/ingenieria-software/Alcance_vigente_y_decisiones_SiGeRU.md` | Revisión posterior que separa septiembre de octubre y advierte límites de evidencia. |
| `sigeru/programacion-full-stack/ampliacion-incidencias-3ra.md` | Avance de API e historial **posterior** a la segunda entrega. |

**Lectura más útil si querés profundizar:** primero §3 y §4 del PDF de segunda entrega; después §5 y §7; al final abrí la maqueta y verificá por vos mismo qué hace cada clic. Comprender un límite y poder mostrarlo vale más que repetir diez frases de memoria.

