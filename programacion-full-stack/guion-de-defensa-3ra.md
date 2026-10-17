---
materia: Programación Full Stack
entrega: 3ra — guion de defensa de la página (16-19/11/2026)
fecha: 2026-10-17
estado: BORRADOR hecho por la sesión autónoma; ensayarlo y corregirlo con el equipo
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

## 2. Preguntas probables y qué contestar

| Pregunta | Respuesta honesta |
|---|---|
| ¿Por qué PHP plano y no un framework? | Para entender y poder explicar cada línea; la letra permite elegir. El costo: más código repetido (se vigila con pruebas de «copias idénticas»). |
| ¿Cómo se organiza el sistema? | Seis aplicaciones independientes que comparten la base: `landing`, `frontend-usuarios` (incluye el backoffice), `api-usuarios`, `api-gestion`, `api-recoleccion` y la base MySQL. El backoffice **no es una aplicación aparte**: es la sección de administración (decisión de diseño documentada). |
| ¿Cómo evitan que un vecino vea lo del administrador? | La API relee el rol de la base en **cada petición** y una tabla de permisos por recurso y método niega por defecto. El menú solo es cosmético; una prueba compara menú y API. Se prueba con los cuatro roles y sin sesión. |
| ¿Qué pasa con las fotos que sube un vecino? | Se lee el tipo del **contenido**, se rechaza lo que no sea JPG/PNG/WebP o pase de 2 MB, y se **vuelve a dibujar** como JPEG nuevo: así se pierden los datos de ubicación de la cámara y cualquier cosa escondida. Se guarda fuera de la carpeta pública con un nombre al azar y solo la ve quien la sube, la administración y la cuadrilla asignada. |
| ¿Dónde se guardan las contraseñas? | Con `password_hash` (bcrypt) y sal propia; el seed tiene un hash distinto por usuario. Nunca se comparan en texto plano. |
| ¿Y la recuperación de contraseña? | Enlace de 32 bytes aleatorios, solo se guarda su SHA-256, vence a la hora, se usa una vez y emitir uno nuevo invalida los anteriores. La respuesta es la misma exista o no la cuenta (no se puede enumerar usuarios). |
| ¿Hay inyección SQL / XSS? | Todas las consultas son preparadas (también la búsqueda, probada con `' OR 1=1 --` y con `%`). Todo lo que va a `innerHTML` se escapa; los CSV neutralizan fórmulas de planilla. |
| ¿Qué es el historial «de solo agregado»? | Dos triggers impiden `UPDATE` y `DELETE` en `historial_incidencia`: es evidencia. Se limpia solo por la cascada si se borra la incidencia entera (las pruebas lo usan). |
| ¿Cómo prueban? | 410 pruebas unitarias sin servidor (lógica pura: validaciones, niveles de urgencia, estadísticas, paginación) y 590 por HTTP contra el sistema real, con los cuatro roles. Además se hicieron **mutaciones**: se rompe el código a propósito y se comprueba que alguna prueba falle (todas las que se probaron en las partes 7 a 9 fueron detectadas; en la 7 una no, y se agregó la prueba que faltaba). |
| ¿Las pruebas son unitarias de verdad? | Las de HTTP son de integración, no unitarias: se dice tal cual. Las unitarias son las de las clases puras. No hay cobertura medida. |
| ¿El mapa depende de internet? | El fondo de calles sí (OpenStreetMap, no se guardan mosaicos: su política lo prohíbe). Sin red se ve fondo liso con aviso y los puntos siguen; la lista de abajo tiene las mismas direcciones. |
| ¿Mandan avisos por correo? | No. Las alertas son visibles en el panel. El único correo es el de acceso (crear o recuperar contraseña). |
| ¿Por qué el informe cuenta por fecha de reporte? | Simplicidad y trazabilidad; es una limitación documentada: una incidencia reportada en agosto y resuelta en octubre cuenta en agosto. |
| ¿Quién hizo qué? | Ver el historial de Git y las ampliaciones por parte: la mayor parte del código y la documentación la hizo Franco Fornos **con asistencia de IA (Claude)**, y eso se declara. Francisco Goycochea tiene a cargo la conexión de las APIs con la base. |
| ¿Se usó IA? | Sí. Se declara y no se esconde (**pendiente:** la declaración la tiene que escribir Fornos; el archivo `sigeru/declaracion-uso-ia.txt` todavía no existe). Lo que importa es que el equipo pueda explicar cada decisión (esta tabla es para eso). |

## 3. Si algo falla durante la defensa

- **El administrador no puede crear usuarios** («el correo no está configurado»): se arrancó sin
  `iniciar-xampp-con-correo`. Plan B: mostrar el alta con un usuario ya creado, o arrancar de nuevo con correo.
- **El mapa no muestra calles**: no hay internet. Es esperable y está avisado en pantalla.
- **MySQL no responde**: no matarlo nunca; `detener-xampp.cmd` desde una pestaña de terminal y volver a arrancar.
- **Datos raros en las listas** (ensayos anteriores): `restaurar-base-demo.cmd`.

## 4. Números exactos (17/10/2026)

16 tablas del modelo (+ `enlace_clave`, `intento_login`, `migracion` y `foto_incidencia` de soporte) · 6 aplicaciones · 4 roles · 410 pruebas
unitarias · 590 por HTTP (con el correo en modo archivo; con el arranque común se omiten 2 bloques y el resumen lo dice) · base
restaurable con huella `60e98084441a8c3b` · congelamiento 30/10 · entrega 06/11.

## 5. Lo que NO decir

- «Es seguro»: decir «aplica estas defensas y tiene estas limitaciones» (sin límite de pedidos en lo público, sin CSRF
  por token —`SameSite=Lax` y JSON—, sin HTTPS en local).
- «Está terminado»: no se verificó a ojo el aspecto visual, ni el envío real de correo, ni la instalación en Docker/MySQL 8; ver la adenda de alcance.
- Cualquier cifra de rendimiento: no se midió.
