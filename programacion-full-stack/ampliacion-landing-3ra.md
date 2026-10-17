---
materia: Programación Full Stack
entrega: 3ra (ampliación: landing renovada con datos reales y mapa público)
fecha: 2026-10-17
---

# Ampliación: landing renovada

Parte 8 de la mejora de la página de la 3ra entrega. Cierra los hallazgos H4 (la landing mostraba una ficha
inventada y prometía cosas que el sistema no hacía) y H17 (promesas sin respaldo). Se hizo en la madrugada del
17/10 en la sesión autónoma; las decisiones marcadas «SIN Fornos» se pueden corregir.

## 1. Qué cambió

| Antes | Ahora |
|---|---|
| Ficha del hero con `CNT-018`, «62 %», «hoy 06:40», nivel de llenado: **nada de eso existe** (la base tiene `CNT-001` a `CNT-010` y no guarda el nivel de llenado) | Tarjeta **«El sistema hoy»** con cifras reales: contenedores en servicio, disponibles, llenos, zonas, centros de acopio, incidencias resueltas |
| Sin mapa | **Mapa público** (Leaflet) con los contenedores en servicio, coloreados por estado, y los centros de acopio activos; lista alternativa en una tabla |
| «rutas de recolección organizadas por turno», «canal para reportar» sin aclarar que hace falta cuenta | Textos que describen lo que hay: rutas por zona con día y hora, recolecciones con peso y volumen, incidencias para el **vecino registrado** |
| Sin explicar el flujo | Sección **«Cómo informar un problema»** en tres pasos, que aclara que **no se avisa por correo** (el vecino ve el avance en «Mis reclamos») |
| `js/main.js`: script de registro duplicado que ninguna página cargaba | Eliminado (el registro vive en `frontend-usuarios/registro.html`) |
| Tipos de residuo escritos a mano (5) | Se arman con los de la base (6, con «reciclable»); si la API falla queda la lista fija |

## 2. API pública

`GET /api-gestion/publico/mapa` — **sin sesión** (es el único recurso de las APIs que se lee sin iniciar sesión).
Devuelve `resumen` (cifras y tipos de residuo), `contenedores` (código, dirección, latitud, longitud, estado, tipo
de residuo y zona) y `centros` (nombre, dirección y ubicación de los activos). Reglas de privacidad, con pruebas:

- Solo **lugares públicos** (contenedores en la calle y centros de acopio) y **agregados**. Ninguna persona, ningún
  correo ni teléfono, ninguna incidencia individual, ningún id de usuario.
- Los contenedores **retirados** no se muestran.
- Es solo lectura: `POST` y demás responden 401 sin sesión y 403 con ella.
- Se implementó con una regla `ANONIMO` en `api-gestion/core/Permisos.php` (denegación por defecto para todo lo demás).

## 3. Independencia de la landing

La landing sigue sin cargar nada de `frontend-usuarios/` (js, css ni img): tiene su **propia copia idéntica de
Leaflet** (`landing/vendor/leaflet/`, la misma versión 1.9.4 verificada) y una prueba unitaria falla si difieren.
Habla con el sistema solo por HTTP (la API pública) y con enlaces a `login.html` y `registro.html`.

## 4. Pruebas (17/10/2026)

- **Unitarias:** la copia de Leaflet de la landing es idéntica; la landing carga todo de su carpeta; **no contiene**
  ninguna de las frases inventadas ni las promesas incumplidas (`CNT-018`, `62 %`, `06:40`, «Nivel de llenado»,
  «por turno», «te avisa», «en tiempo real»); no existe `main.js`; los únicos pedidos a internet son los mosaicos
  del fondo del mapa interno y del público.
- **HTTP:** el mapa público se lee sin sesión y con cualquier rol; cada contenedor trae **exactamente** los
  campos públicos; no aparece `@`, `telefono` ni `id_usuario`; los contenedores del mapa son los que el
  administrador ve en servicio; el resumen suma bien; los centros son los activos; las resueltas coinciden con el
  listado; `POST` y rutas inexistentes se rechazan; se sirven los archivos nuevos de la landing.
- **En el navegador:** a 375, 768 y 1100 px sin desborde horizontal; cifras `9, 6, 2, 5, 3, 1`; 9 puntos de
  contenedores y 3 centros; 12 filas en la lista; tipos de residuo completos; consola sin errores; y la **falla de
  la API** simulada muestra el mensaje de «no pudimos cargar los datos» sin romper la página.
- **No verificado:** el aspecto visual (capturas no disponibles con la ventana oculta) ni si el mapa muestra el
  **fondo de calles** (los mosaicos de OpenStreetMap dependen de internet y no se comprobó su carga).

## 5. Limitaciones

- El endpoint público no tiene límite de pedidos ni caché: es una lectura barata, pero un uso masivo la
  sobrecargaría. Para producción convendría una caché de 1 a 5 minutos delante.
- Las cifras se calculan al abrir la página (no se actualizan solas).
- «Zonas con contenedores» cuenta las zonas que tienen al menos un contenedor en servicio.
- Las coordenadas son las del seed (Montevideo); un contenedor sin latitud y longitud no aparece en el mapa pero sí en la lista.
