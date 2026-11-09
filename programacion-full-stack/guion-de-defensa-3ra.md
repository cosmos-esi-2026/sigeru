---
materia: Programación Full Stack
entrega: 3ra — guion de defensa de la página (16-19/11/2026)
fecha: 2026-10-24
estado: BORRADOR hecho por la sesión autónoma (17/10, ampliado el 19/10 con las partes 12 a 22 y el 24/10 con el plan de rutas, partes 24 a 26); ensayarlo y corregirlo con el equipo
---

# Guion de defensa de la página — 3ra entrega

Pensado para ~15 minutos de demostración y ~10 de preguntas. **No inventa nada**: cada cosa que se muestra está probada
en el repo (números al final). Usuario de prueba: `admin@sigeru.uy`, `cuadrilla@sigeru.uy`, `operario@sigeru.uy`,
`vecino@sigeru.uy`; clave de todos: **`sigeru2026`** (cambió el 17/10; antes era `123456`).

## 0. Antes de entrar (10 minutos antes, en la PC de la defensa)

1. Pendrive en `D:` (o la letra que toque). **Nunca** sacar el pendrive sin `detener-xampp.cmd` (exFAT sin journaling).
2. Arrancar con `iniciar-xampp.cmd`. No hace falta internet ni correo: el sistema no envía correo.
3. `herramientas\xampp\restaurar-base-defensa.cmd` (unos 3 minutos; guarda copia antes): deja los **800 contenedores reales, las 40 rutas y las 8 cuadrillas por municipio** que usa la demo de rutas
   (sección 1 ter). `restaurar-base-demo.cmd` es el seed chico de las pruebas: sirve para el recorrido de la sección 1, **no** para el de rutas.
4. Abrir `http://localhost/sigeru/landing/` y comprobar que el mapa muestra calles (necesita internet; sin red se
   ve el fondo liso con un aviso y los puntos igual).
5. Tener abiertas 4 ventanas de navegador (una por rol, en modo incógnito o perfiles distintos: la sesión es una cookie).

## 1. Demostración (en este orden)

| Min | Qué se muestra | Qué se dice |
|---|---|---|
| 0-2 | **Landing**: cifras reales, mapa público, «cómo informar» | «No hay datos inventados: las cifras salen de la base por una API pública que solo trae lugares públicos y totales, nunca personas.» Tocar un punto del mapa. |
| 2-4 | **Vecino** (`vecino@sigeru.uy`): «Informar un problema» sobre `CNT-002` → «Mis reclamos» | «Solo un vecino registrado puede reportar (RF-217). El reclamo queda con número `INC-000N` y su historial.» |
| 4-8 | **Administrador**: inicio con **«Requiere atención»** (críticas, sin asignar, vencidas) → abrir la incidencia → **asignar a una cuadrilla** con prioridad | «El nivel de urgencia se calcula en el momento, sin tabla nueva: crítica = prioridad alta y más de 3 días.» Mostrar el historial de solo agregado (la base lo impide editar con triggers). |
| 8-10 | **Cuadrilla**: «Mis incidencias» → registrar avance → resolver; «Mis tareas» → iniciar recolección, registrar un vaciado con peso | «Cada rol ve solo lo suyo: la cuadrilla solo las incidencias y recolecciones de su cuadrilla.» |
| 10-12 | **Administrador**: **Reportes** (período, zona) → gráficos → Imprimir | «Las cifras del informe se contrastan en las pruebas contra las listas.» |
| 12-13 | **Mapa** (menú): capas por rol; el administrador ve incidencias abiertas | «Leaflet dentro del repo, sin CDN; sin internet no cae.» |
| 13-15 | **Usuarios** (admin): búsqueda, filtros, paginación, «Enviar acceso» | «La contraseña la crea la persona con un enlace de un solo uso, de una hora; el administrador nunca la conoce.» |

**Si sobra tiempo:** mostrar `tests` corriendo (`sigeru\tests\correr-tests.cmd`) y terminar con «Fallaron: 0».

## 1 bis. Demostraciones nuevas (partes 12 a 15) — elegir 2 o 3 según el tiempo

Orden sugerido **después** del recorrido de arriba (cada una ~2 minutos). Todo está en los datos de demostración.

| Qué se muestra | Qué se dice |
|---|---|
| **Vecino → su inicio**: elegir su zona («¿Cuándo pasa el camión?») y la campanita | «Los avisos son de la campanita: cuando cambia el estado de su reclamo el vecino lo ve ahí; no mandamos correo para eso.» |
| **Operario** (`operario@sigeru.uy`): inicio del centro → **Recepciones** → confirmar una → **Stock** (retiro y corrección) | «El operario pertenece a un centro; el stock es de solo agregado: una corrección es un movimiento inverso, nunca un cambio.» |
| **Cualquier pantalla con el modo oscuro** (botón redondo abajo a la derecha) | «El contraste de las dos paletas se calcula en una prueba con los colores reales. El mapa sigue claro porque los mosaicos son de OpenStreetMap.» |

## 1 ter. Demo del plan de rutas (la nota de 9 puntos del profe de Programación) — 10 minutos, con la base de defensa

**Un nombre por bloque** (completar en el ensayo): cada bloque lo muestra una persona distinta y las otras tres pueden contestar sobre él.
Datos: 8 municipios (A, B, C, CH, D, E, F, G), 100 contenedores reales de la Intendencia de Montevideo en cada uno, 5 rutas de 20 por municipio (40 rutas, de lunes a viernes), una cuadrilla por municipio.
**Cuentas** (clave `sigeru2026`): `admin@sigeru.uy` · `vecino@sigeru.uy` · cuadrillas: `cuadrilla@sigeru.uy` (Municipio A), `carla.nunez@sigeru.uy` (B), `cuadrilla.c@sigeru.uy`, `cuadrilla.ch@sigeru.uy`, `cuadrilla.d@sigeru.uy`, `cuadrilla.e@sigeru.uy`, `cuadrilla.f@sigeru.uy`, `cuadrilla.g@sigeru.uy`.
**Ojo con la fecha:** la pantalla «Asignación» arranca en *mañana*; sábado y domingo no hay rutas (dice «No hay rutas para mostrar»). Elegir un día de lunes a viernes.

| Bloque | Responsable | Qué se muestra | Qué se dice |
|---|---|---|---|
| **1. Armar una ruta** (puntos 1 y 2) | | Administrador → **Rutas** → «Armar» en *Municipio A - Ruta 1* → mapa con sus 20 contenedores → quitar uno y agregar otro tocando el mapa → guardar → la ruta se dibuja **en línea recta entre sus paradas** | «Solo el administrador arma rutas. El tope es de 25 contenedores. Los contenedores son reales (datos abiertos de la Intendencia, con la fuente citada). Desde el 30/10 el trazado es en línea recta (no depende de internet); las 40 rutas del seed conservan el trazado por calles que ya tenían guardado, hasta que se edita su lista de paradas.» |
| **2. Prioridad en incidencias** (punto 3) | | Vecino: informar un problema de tipo **desbordado** (prioridad alta) y otro **lleno** (media), *sin escribir descripción*. Administrador: abrir uno **roto** → «derivar a mantenimiento» (anota a quién llamó) | «La prioridad sale del tipo: desbordado alta, lleno media. Lo roto no lo recolecta el camión: se deriva a mantenimiento y el contenedor vuelve a disponible al cerrar. La descripción es opcional salvo en “otro”.» |
| **3. Asignar rutas a cuadrillas** (puntos 4 y 6) | | Administrador → **Asignación** → un día de semana → ver las rutas ordenadas por prioridad → **«Sugerir»** → cada ruta propone **la cuadrilla de su municipio** → cambiar una a mano (la del mismo municipio aparece primera; una de otro municipio muestra el aviso «Fuera de su municipio») → **«Asignar todas»** | «El sistema sugiere y el administrador confirma: nada se crea solo. Si sobran cuadrillas, quedan libres para otras rutas; si faltan, se avisa cuántas rutas quedan sin cuadrilla. El turno solo avisa, no impide.» |
| **4. La cuadrilla ve solo lo suyo** (punto 5) | | Entrar como `carla.nunez@sigeru.uy` → **Mis tareas** (la recolección recién asignada; las vencidas salen marcadas) → **Mapa** («Mis rutas»: solo sus contenedores) → iniciar y registrar un vaciado | «El servidor le manda 40 contenedores de 800: no es un filtro de la pantalla. Una cuadrilla no crea ni edita rutas (403) y el vaciado solo acepta contenedores de esa ruta.» |
| **5. Cuadrillas y usuarios** (puntos 7, 8 y 9) | | Administrador → **Cuadrillas** (municipio y turno de cada una; no se da de baja con trabajo abierto) → **Usuarios** (campo «Cuadrilla» para el rol Cuadrilla) → en el login, «¿Sos de una cuadrilla? **Pedí acceso**» → **Solicitudes** → aprobar *eligiendo la cuadrilla real* | «Era un error real: la API de usuarios nunca guardaba la cuadrilla de una persona. Quien pide acceso como cuadrilla no se hace cuadrilla solo: el administrador elige a cuál.» |
| **6. Que no se trabe** (punto 9) | | Mapa del administrador con los 800 contenedores y cambiar los filtros | «Se midió: portada 286 ms con 803 marcadores, ninguna respuesta pasa de 70 KB comprimida y una consulta de rutas pasó de 0,4 s a 0,04 s. Son mediciones de **una** PC; no se probó en la más lenta ni en un celular.» |

## 2. Preguntas probables y qué contestar

| Pregunta | Respuesta honesta |
|---|---|
| ¿Por qué PHP plano y no un framework? | Para entender y poder explicar cada línea; la letra permite elegir. El costo: más código repetido (se vigila con pruebas de «copias idénticas»). |
| ¿Cómo se organiza el sistema? | Cinco aplicaciones independientes que comparten una base MySQL: `landing`, `frontend-usuarios` (incluye el backoffice), `api-usuarios`, `api-gestion` y `api-recoleccion`. La letra propone seis: el backoffice **no es una aplicación aparte**, es la sección de administración de `frontend-usuarios` (decisión del 12/10, en la adenda de alcance). |
| ¿Cómo evitan que un vecino vea lo del administrador? | La API relee el rol de la base en **cada petición** y una tabla de permisos por recurso y método niega por defecto. El menú solo es cosmético; una prueba compara menú y API. Se prueba con los cuatro roles y sin sesión. |
| ¿Dónde se guardan las contraseñas? | Con `password_hash` (bcrypt) y sal propia; el seed tiene un hash distinto por usuario. Nunca se comparan en texto plano. |
| ¿Y la recuperación de contraseña? | Enlace de 32 bytes aleatorios, solo se guarda su SHA-256, vence a la hora, se usa una vez y emitir uno nuevo invalida los anteriores. La respuesta es la misma exista o no la cuenta (no se puede enumerar usuarios). |
| ¿Hay inyección SQL / XSS? | Todas las consultas son preparadas (también la búsqueda, probada con `' OR 1=1 --` y con `%`). Todo lo que va a `innerHTML` se escapa. |
| ¿Qué es el historial «de solo agregado»? | Dos triggers impiden `UPDATE` y `DELETE` en `historial_incidencia`: es evidencia. Se limpia solo por la cascada si se borra la incidencia entera (las pruebas lo usan). |
| ¿Cómo prueban? | 737 pruebas unitarias sin servidor (lógica pura: validaciones, niveles de urgencia, estadísticas, paginación) y 965 por HTTP contra el sistema real, con los cuatro roles. Además se hicieron **mutaciones**: se rompe el código a propósito y se comprueba que alguna prueba falle (todas las que se probaron en las partes 7 a 9 fueron detectadas; en la 7 una no, y se agregó la prueba que faltaba). |
| ¿Las pruebas son unitarias de verdad? | Las de HTTP son de integración, no unitarias: se dice tal cual. Las unitarias son las de las clases puras. No hay cobertura medida. |
| ¿El mapa depende de internet? | El fondo de calles sí (OpenStreetMap, no se guardan mosaicos: su política lo prohíbe). Sin red se ve fondo liso con aviso y los puntos siguen; la lista de abajo tiene las mismas direcciones. |
| ¿Mandan avisos por correo? | **No para los cambios de estado.** Los avisos son la campanita de todas las pantallas. **El sistema no manda correo de ningún tipo** (se sacó el 30/10): el administrador define la contraseña al crear la cuenta y la restablece editando el usuario; no hay «Olvidé mi contraseña». Es una decisión de simplicidad: se evita depender de un servidor de correo. |
| ¿Por qué el informe cuenta por fecha de reporte? | Simplicidad y trazabilidad; es una limitación documentada: una incidencia reportada en agosto y resuelta en octubre cuenta en agosto. |
| ¿Quién hizo qué? | Ver el historial de Git y las ampliaciones por parte: la mayor parte del código y la documentación la hizo Franco Fornos **con asistencia de IA (Claude)**, y eso se declara. Francisco Goycochea tiene a cargo la conexión de las APIs con la base. |
| ¿Se usó IA? | Sí. Se declara y no se esconde (**pendiente:** la declaración la tiene que escribir Fornos; el archivo `sigeru/declaracion-uso-ia.txt` todavía no existe). Lo que importa es que el equipo pueda explicar cada decisión (esta tabla es para eso). |

### Preguntas nuevas (partes 12 a 22)

*(El 25/10 se sacó lo que no se iba a defender: predicción, ruta sugerida, PDF, sin conexión, calificaciones, impacto y guía; ver `ampliacion-simplificacion-3ra.md`. Si preguntan por eso: «lo evaluamos y decidimos no entregarlo para dejar el sistema más simple y bien probado».)*

| Pregunta | Respuesta honesta |
|---|---|
| ¿El modo oscuro cumple accesibilidad? | El contraste de las dos paletas se **calcula en una prueba** con los colores reales (25 combinaciones por paleta, todas de 4,5:1 o más). El mapa sigue claro porque los mosaicos son de OpenStreetMap. Falta que alguien lo recorra a ojo. |
| ¿Por qué el modelo creció de 16 a 21 tablas? | Recepción, stock con movimientos, historial de maquinaria y notificación (partes 12 y 13) y la zona de la cuadrilla (columna). Los diagramas entregados describen el modelo de 16 clases: **se documentó por ampliación, no se reescribió lo entregado.** |
| ¿Qué decisiones de producto tomaron sin el equipo? | Las del Operario ligado a un centro y la zona de la cuadrilla para sugerir la asignación. Están marcadas «SIN Fornos» en cada `ampliacion-*-3ra.md` y se pueden revertir. |
| ¿Cómo se midieron los puntos de función de lo nuevo? | IFPUG como en la adenda de maquinaria: 73 sin ajustar para lo que quedó de las partes 12 a 22 (439 ajustados en total, ver la sección 11 de la adenda). Es una estimación **pendiente de revisión del equipo**; los requerimientos no funcionales (oscuro, errores) no suman. |

### Preguntas del plan de rutas (partes 24 a 26)

| Pregunta | Respuesta honesta |
|---|---|
| ¿Por qué 5 rutas de 20 y no 4 de 25? | Decisión nuestra (marcada «SIN Fornos» en `ampliacion-rutas-defensa.md`): una ruta por día de lunes a viernes. El tope de 25 queda como límite de edición. Se puede cambiar. |
| ¿La sugerencia de asignación decide sola? | No. Recorre las rutas por prioridad y le busca a cada una **la cuadrilla libre de su municipio**; si no hay, una sin municipio; las que sobran de otros municipios se ofrecen a las rutas que quedaron sin cuadrilla, **marcadas**. El administrador revisa y confirma. El turno solo avisa. |
| ¿Cómo sabe de qué municipio es una cuadrilla? | Desde la migración 020 la cuadrilla tiene un municipio opcional. Antes no lo tenía y la sugerencia emparejaba por posición (se corría un lugar: lo detectó la evaluación heurística, hallazgo H-04). La migración vincula sola las del seed por el nombre. |
| ¿Dónde está la calle de cada contenedor? | **No la tenemos.** Los datos abiertos de la Intendencia traen el circuito y las coordenadas, no la dirección. En lugar de una calle inventada, la ficha tiene «Ver en el mapa». No se geocodificó: consultar 800 puntos a un servicio público va contra su política. |
| ¿Qué pasa con un contenedor roto? | No lo recolecta el camión: no cuenta en la prioridad de la ruta y no se puede asignar a una cuadrilla; el administrador lo deriva a mantenimiento. |
| ¿Y si no hay internet para el trazado? | La ruta se dibuja en línea recta y la pantalla lo dice. Los 40 trazados del seed ya están guardados. |
| ¿Por qué una descripción opcional al informar? | Un aviso desde el QR de un contenedor no debería frenarse por no escribir nada (hallazgo H-06). Si no escribe, se guarda el nombre del tipo. En “otro” sigue siendo obligatoria. |
| ¿Funciona en el servidor de la instalación? | **No se probó todavía.** El código se escribió para PHP 8.0 o superior (una prueba lo vigila) y la hora de Uruguay la fija el código, no el servidor. Falta ejecutarlo en la VM Rocky con el PHP que tenga. Si ya se hizo, decir la fecha y mostrar la captura. |

## 3. Si algo falla durante la defensa

- **El mapa no muestra calles**: no hay internet. Es esperable y está avisado en pantalla.
- **MySQL no responde**: no matarlo nunca; `detener-xampp.cmd` desde una pestaña de terminal y volver a arrancar.
- **Datos raros en las listas** (ensayos anteriores): `restaurar-base-demo.cmd`.

## 4. Números exactos (30/10/2026, después de sacar el correo)

21 tablas del modelo (+ 5 de soporte: `migracion`, `intento_login`, `auditoria` y `limite_peticion`) = 25 · 5 aplicaciones y la base · 4 roles ·
**654 pruebas unitarias** · **995 por HTTP** (1 se omite si Apache no arranca con el trazado en modo recta) ·
base restaurable con huella **`232ba1b2758a7a3e`** con el seed de pruebas y **`2b25a0d53cf71e5d`** con el de defensa · migraciones 001 a 003, 005 a 011, 014 y 017 a 020 · congelamiento 30/10 · entrega 06/11.
Puntos de función: 359 ajustados con lo de octubre y **439 con lo que quedó de las partes 12 a 22** (estimación **pendiente de revisión del equipo**; ver la sección 11 de la adenda de alcance).

## 5. Lo que NO decir

- «Es seguro»: decir «aplica estas defensas y tiene estas limitaciones» (con freno por IP en lo público (no es un WAF), sin CSRF
  por token —`SameSite=Lax` y JSON—, sin HTTPS en local).
- «Está terminado»: no se verificó a ojo el aspecto visual de todas las pantallas en modo oscuro, ni la instalación en MySQL 8 del servidor, ni la instalación en un celular; ver la adenda de alcance.
- Cualquier cifra de rendimiento más allá de las tres mediciones de una sola PC: no se midió en la PC más lenta ni en un celular.
- «Los contenedores tienen su dirección»: tienen circuito y coordenadas, **no calle**. «Ver en el mapa» es lo que reemplaza a la calle.
- «La cuadrilla funciona en el celular» o «anda en el servidor Rocky»: no se probó (si se probó antes de la defensa, decir cuándo y mostrar la captura).
