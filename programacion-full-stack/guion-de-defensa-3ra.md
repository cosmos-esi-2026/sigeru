---
materia: Programación Full Stack
entrega: 3ra — guion de defensa de la página (16-19/11/2026)
fecha: 2026-10-19
estado: BORRADOR hecho por la sesión autónoma (17/10, ampliado el 19/10 con las partes 12 a 22); ensayarlo y corregirlo con el equipo
---

# Guion de defensa de la página — 3ra entrega

Pensado para ~15 minutos de demostración y ~10 de preguntas. **No inventa nada**: cada cosa que se muestra está probada
en el repo (números al final). Usuario de prueba: `admin@sigeru.uy`, `cuadrilla@sigeru.uy`, `operario@sigeru.uy`,
`vecino@sigeru.uy`; clave de todos: **`sigeru2026`** (cambió el 17/10; antes era `123456`).

## 0. Antes de entrar (10 minutos antes, en la PC de la defensa)

1. Pendrive en `D:` (o la letra que toque). **Nunca** sacar el pendrive sin `detener-xampp.cmd` (exFAT sin journaling).
2. Arrancar con correo: `sigeru\iniciar-xampp-con-correo.cmd` (pide la contraseña de aplicación de Gmail; no se guarda).
   Si no hay internet o no hay clave de Gmail: `iniciar-xampp.cmd` **sirve para todo salvo crear usuarios** (el
   sistema pide correo configurado; ver «Si algo falla»).
3. `herramientas\xampp\restaurar-base-demo.cmd` si la base quedó con datos de ensayos (guarda copia antes).
4. Abrir `http://localhost/sigeru/landing/` y comprobar que el mapa muestra calles (necesita internet; sin red se
   ve el fondo liso con un aviso y los puntos igual).
5. Tener abiertas 4 ventanas de navegador (una por rol, en modo incógnito o perfiles distintos: la sesión es una cookie).

## 1. Demostración (en este orden)

| Min | Qué se muestra | Qué se dice |
|---|---|---|
| 0-2 | **Landing**: cifras reales, mapa público, «cómo informar» | «No hay datos inventados: las cifras salen de la base por una API pública que solo trae lugares públicos y totales, nunca personas.» Tocar un punto del mapa. |
| 2-4 | **Vecino** (`vecino@sigeru.uy`): «Informar un problema» sobre `CNT-002`, **con una foto** → «Mis reclamos» | «Solo un vecino registrado puede reportar (RF-217). El reclamo queda con número `INC-000N` y su historial.» |
| 4-8 | **Administrador**: inicio con **«Requiere atención»** (críticas, sin asignar, vencidas) → abrir la incidencia → **asignar a una cuadrilla** con prioridad | «El nivel de urgencia se calcula en el momento, sin tabla nueva: crítica = prioridad alta y más de 3 días.» Mostrar el historial de solo agregado (la base lo impide editar con triggers). |
| 8-10 | **Cuadrilla**: «Mis incidencias» → registrar avance → resolver; «Mis tareas» → iniciar recolección, registrar un vaciado con peso | «Cada rol ve solo lo suyo: la cuadrilla solo las incidencias y recolecciones de su cuadrilla.» |
| 10-12 | **Administrador**: **Reportes** (período, zona) → gráficos → **Exportar CSV** → Imprimir | «Las cifras del informe se contrastan en las pruebas contra las listas.» |
| 12-13 | **Mapa** (menú): capas por rol; el administrador ve incidencias abiertas | «Leaflet dentro del repo, sin CDN; sin internet no cae.» |
| 13-15 | **Usuarios** (admin): búsqueda, filtros, paginación, «Enviar acceso» | «La contraseña la crea la persona con un enlace de un solo uso, de una hora; el administrador nunca la conoce.» |

**Si sobra tiempo:** mostrar `tests` corriendo (`sigeru\tests\correr-tests.cmd`) y terminar con «Fallaron: 0».

## 1 bis. Demostraciones nuevas (partes 12 a 22) — elegir 3 o 4 según el tiempo

Orden sugerido **después** del recorrido de arriba (cada una ~2 minutos). Todo está en los datos de demostración; las que dependen de historial de vaciados necesitan `cargar-historial-demo.cmd` (en herramientas/xampp) (datos marcados como demostración, **no** son parte del seed).

| Qué se muestra | Qué se dice |
|---|---|
| **Vecino → «¿Dónde tiro esto?»** (también en la landing): buscar «botella», «pila», «pizza» | «Es una guía con 58 residuos del *Manual de residuos domiciliarios* de la Intendencia de Montevideo (2024); cada respuesta dice la página del manual. Funciona sin internet y sin API. Es la guía de Montevideo y lo avisa.» |
| **Vecino → su inicio**: elegir su zona («¿Cuándo pasa el camión?»), ver «Mi progreso», **calificar** un reclamo resuelto, campanita | «Calificar suma 5 puntos y que se resuelva suma 10; los puntos son de solo agregado (trigger). El ranking es por zona, no por persona.» |
| **Administrador → Contenedores → «Se llena en»** (con el historial de demostración) y **«Ruta sugerida»** (zona, 48 h, centro de destino) → mapa con el recorrido → «Crear recolección con esta ruta» (solo precarga) | «**No es inteligencia artificial**: la predicción es el intervalo medio entre vaciados y la ruta es vecino más cercano con 2-opt. Los supuestos (línea recta, 20 km/h, 5 min por parada) están en pantalla. No crea nada hasta que el administrador confirma.» |
| **Administrador → Reportes → «Descargar PDF»** (abrirlo) y la sección «Impacto ambiental» | «El PDF lo arma un generador propio, sin librerías, con las mismas cifras de la pantalla y la descarga queda en Auditoría. El CO2 evitado es una **estimación** con factores de la EPA (WARM v16) verificados contra el PDF oficial; orgánico y mixto no tienen factor y se informan solo en kilos.»|
| **Operario** (`operario@sigeru.uy`): inicio del centro → **Recepciones** → confirmar una → **Stock** (retiro y corrección) | «El operario pertenece a un centro; el stock es de solo agregado: una corrección es un movimiento inverso, nunca un cambio.» |
| **Cuadrilla en el celular o con la ventana angosta**: modo oscuro (botón redondo), y la **barra «N pendientes de enviar»** (se puede mostrar cortando la red del navegador) | «Sin señal solo se guardan dos acciones: registrar un vaciado y un avance. Cada una lleva una clave única y las APIs ignoran los duplicados.» **No** prometer la instalación en el celular: no se probó (ver preguntas). |

## 2. Preguntas probables y qué contestar

| Pregunta | Respuesta honesta |
|---|---|
| ¿Por qué PHP plano y no un framework? | Para entender y poder explicar cada línea; la letra permite elegir. El costo: más código repetido (se vigila con pruebas de «copias idénticas»). |
| ¿Cómo se organiza el sistema? | Cinco aplicaciones independientes que comparten una base MySQL: `landing`, `frontend-usuarios` (incluye el backoffice), `api-usuarios`, `api-gestion` y `api-recoleccion`. La letra propone seis: el backoffice **no es una aplicación aparte**, es la sección de administración de `frontend-usuarios` (decisión del 12/10, en la adenda de alcance). |
| ¿Cómo evitan que un vecino vea lo del administrador? | La API relee el rol de la base en **cada petición** y una tabla de permisos por recurso y método niega por defecto. El menú solo es cosmético; una prueba compara menú y API. Se prueba con los cuatro roles y sin sesión. |
| ¿Qué pasa con las fotos que sube un vecino? | Se lee el tipo del **contenido**, se rechaza lo que no sea JPG/PNG/WebP o pase de 2 MB, y se **vuelve a dibujar** como JPEG nuevo: así se pierden los datos de ubicación de la cámara y cualquier cosa escondida. Se guarda fuera de la carpeta pública con un nombre al azar y solo la ve quien la sube, la administración y la cuadrilla asignada. |
| ¿Dónde se guardan las contraseñas? | Con `password_hash` (bcrypt) y sal propia; el seed tiene un hash distinto por usuario. Nunca se comparan en texto plano. |
| ¿Y la recuperación de contraseña? | Enlace de 32 bytes aleatorios, solo se guarda su SHA-256, vence a la hora, se usa una vez y emitir uno nuevo invalida los anteriores. La respuesta es la misma exista o no la cuenta (no se puede enumerar usuarios). |
| ¿Hay inyección SQL / XSS? | Todas las consultas son preparadas (también la búsqueda, probada con `' OR 1=1 --` y con `%`). Todo lo que va a `innerHTML` se escapa; los CSV neutralizan fórmulas de planilla. |
| ¿Qué es el historial «de solo agregado»? | Dos triggers impiden `UPDATE` y `DELETE` en `historial_incidencia`: es evidencia. Se limpia solo por la cascada si se borra la incidencia entera (las pruebas lo usan). |
| ¿Cómo prueban? | 737 pruebas unitarias sin servidor (lógica pura: validaciones, niveles de urgencia, estadísticas, paginación) y 965 por HTTP contra el sistema real, con los cuatro roles. Además se hicieron **mutaciones**: se rompe el código a propósito y se comprueba que alguna prueba falle (todas las que se probaron en las partes 7 a 9 fueron detectadas; en la 7 una no, y se agregó la prueba que faltaba). |
| ¿Las pruebas son unitarias de verdad? | Las de HTTP son de integración, no unitarias: se dice tal cual. Las unitarias son las de las clases puras. No hay cobertura medida. |
| ¿El mapa depende de internet? | El fondo de calles sí (OpenStreetMap, no se guardan mosaicos: su política lo prohíbe). Sin red se ve fondo liso con aviso y los puntos siguen; la lista de abajo tiene las mismas direcciones. |
| ¿Mandan avisos por correo? | **Sí, desde la parte 13:** el vecino recibe un correo cuando cambia el estado de su reclamo (se puede apagar desde su inicio), y además hay campanita en todas las pantallas. Se probó el envío con un servidor de correo simulado (modo archivo); **el envío real contra Gmail no se probó** (Google bloquea la verificación en dos pasos de la cuenta nueva). |
| ¿Por qué el informe cuenta por fecha de reporte? | Simplicidad y trazabilidad; es una limitación documentada: una incidencia reportada en agosto y resuelta en octubre cuenta en agosto. |
| ¿Quién hizo qué? | Ver el historial de Git y las ampliaciones por parte: la mayor parte del código y la documentación la hizo Franco Fornos **con asistencia de IA (Claude)**, y eso se declara. Francisco Goycochea tiene a cargo la conexión de las APIs con la base. |
| ¿Se usó IA? | Sí. Se declara y no se esconde (**pendiente:** la declaración la tiene que escribir Fornos; el archivo `sigeru/declaracion-uso-ia.txt` todavía no existe). Lo que importa es que el equipo pueda explicar cada decisión (esta tabla es para eso). |

### Preguntas nuevas (partes 12 a 22)

| Pregunta | Respuesta honesta |
|---|---|
| ¿La predicción y la ruta usan inteligencia artificial? | **No.** La predicción es estadística simple (intervalo medio entre vaciados, hacen falta 3) y dice su confianza; la ruta es vecino más cercano más 2-opt con distancia en línea recta. En las pruebas, contra el óptimo por fuerza bruta en 7 puntos, el peor caso fue 1,2 veces el óptimo: es una propuesta razonable, no la mejor. Velocidad y minutos por parada son supuestos nuestros, declarados en pantalla. |
| ¿De dónde sale el CO2 evitado? | De los factores de la EPA de Estados Unidos (WARM v16, diciembre de 2023) leídos de las tablas del PDF oficial (plástico 1,047; vidrio 0,331; papel 3,990; metal 4,861 kg CO2e por kg). Son de otro país: se rotula «estimación». Orgánico y mixto no tienen factor y no se inventó ninguno. |
| ¿Por qué el PDF no usa FPDF? | El plan lo pedía y habría que descargarlo: se prefirió escribir un generador mínimo (Helvetica estándar, tablas con encabezado repetido, logo vectorial, «Página X de N»). Cero dependencias. Si se prefiere FPDF es reemplazar una clase. |
| ¿Qué pasa si la cuadrilla pierde la señal? | Puede registrar vaciados y avances: quedan en el teléfono (IndexedDB), se ve «N pendientes de enviar» y se mandan solos al volver la red con una clave única; si el servidor ya lo había recibido, devuelve lo mismo y **no se duplica** (probado). Si la recolección se canceló mientras tanto queda «rechazada» con el motivo y el dato no se pierde. |
| ¿Se puede instalar en el celular? | El manifiesto, los íconos y el service worker están hechos y su lógica probada, **pero no se pudo probar en un celular real ni registrar el service worker en el navegador de desarrollo**. En el servidor hace falta HTTPS. Se dice tal cual. |
| ¿El modo oscuro cumple accesibilidad? | El contraste de las dos paletas se **calcula en una prueba** con los colores reales (25 combinaciones por paleta, todas de 4,5:1 o más). El mapa sigue claro porque los mosaicos son de OpenStreetMap. Falta que alguien lo recorra a ojo. |
| ¿Por qué el modelo creció de 16 a 22 tablas? | Recepción, stock con movimientos, historial de maquinaria, notificación, calificación y puntos (partes 12 a 14). Los diagramas entregados describen el modelo de 16 clases: **se documentó por ampliación, no se reescribió lo entregado.** |
| ¿Qué decisiones de producto tomaron sin el equipo? | Las del Operario ligado a un centro, el correo de aviso activado por defecto, el sistema de puntos (10 y 5), la guía solo de Montevideo y el PDF propio. Están marcadas «SIN Fornos» en cada `ampliacion-*-3ra.md` y se pueden revertir. |
| ¿Cómo se midieron los puntos de función de lo nuevo? | IFPUG como en la adenda de maquinaria: 143 sin ajustar para las partes 12 a 22 (516 ajustados en total). Es una estimación **pendiente de revisión del equipo**; los requerimientos no funcionales (PWA, oscuro, errores) no suman. |

## 3. Si algo falla durante la defensa

- **El administrador no puede crear usuarios** («el correo no está configurado»): se arrancó sin
  `iniciar-xampp-con-correo`. Plan B: mostrar el alta con un usuario ya creado, o arrancar de nuevo con correo.
- **El mapa no muestra calles**: no hay internet. Es esperable y está avisado en pantalla.
- **MySQL no responde**: no matarlo nunca; `detener-xampp.cmd` desde una pestaña de terminal y volver a arrancar.
- **Datos raros en las listas** (ensayos anteriores): `restaurar-base-demo.cmd`.

## 4. Números exactos (19/10/2026)

22 tablas del modelo (+ 7 de soporte: `migracion`, `intento_login`, `enlace_clave`, `foto_incidencia`, `auditoria`, `limite_peticion` y `clave_idempotencia`) = 29 · 5 aplicaciones y la base · 4 roles ·
**737 pruebas unitarias** · **965 por HTTP** (con el correo en modo archivo; con el arranque común se omiten 2 bloques y el resumen lo dice) · **26 pruebas de la cola sin conexión y 10 de la lógica del service worker**
(se corren en el navegador, `tests/js/`) · base restaurable con huella **`d6c2eadfbc82fcdb`** (la de `60e98084441a8c3b` ya no vale: el modelo creció) · migraciones 001 a 016 · congelamiento 30/10 · entrega 06/11.
Puntos de función: 359 ajustados con lo de octubre y **516 con las partes 12 a 22** (estimación **pendiente de revisión del equipo**; ver la adenda de alcance).

## 5. Lo que NO decir

- «Es seguro»: decir «aplica estas defensas y tiene estas limitaciones» (con freno por IP en lo público (no es un WAF), sin CSRF
  por token —`SameSite=Lax` y JSON—, sin HTTPS en local).
- «Está terminado»: no se verificó a ojo el aspecto visual de todas las pantallas en modo oscuro, ni el envío real de correo, ni la instalación en MySQL 8 del servidor, ni la instalación en un celular; ver la adenda de alcance.
- «La predicción o la ruta usan IA» o «el CO2 es real»: son estadística simple y una estimación con factores de otro país.
- «Funciona sin señal» sin aclarar: solo dos acciones de la Cuadrilla, y la pantalla tiene que haberse abierto con señal.
- Cualquier cifra de rendimiento: no se midió.
