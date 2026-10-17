---
materia: Programación Full Stack
entrega: 3ra (ampliación: listas con filtros y paginación en el servidor, y datos validados)
fecha: 2026-10-17
---

# Ampliación: listas y robustez

Parte 9 de la mejora de la página de la 3ra entrega. Cierra H9 (listas sin filtros, sin paginación y sin
exportar) y H11b (valores por omisión silenciosos y datos sin validar). Hecha en la madrugada del 17/10 en la
sesión autónoma; las decisiones marcadas «SIN Fornos» se pueden corregir.

## 1. Decisiones SIN Fornos

- **Dónde se paginó:** solo en las dos listas que crecen sin límite, **contenedores** y **usuarios**. Camiones,
  centros, maquinaria, solicitudes, incidencias y recolecciones siguen cargando todo (son decenas de filas, y las
  incidencias y recolecciones ya se filtran por estado, zona y fechas en el servidor).
- **Compatible hacia atrás:** sin el parámetro `pagina` las APIs devuelven **todo**, como siempre (lo usan el mapa,
  los formularios y los reportes). Con `pagina` devuelven un tramo y un bloque `meta`.
- **Contenedores sin zona o sin tipo ya no se aceptan:** antes el servidor ponía «la primera zona» y el tipo
  «Mixto» sin avisar. La pantalla ya mandaba ambos; solo cambió para quien usaba la API a mano y para las pruebas.

## 2. API

| Endpoint | Parámetros nuevos |
|---|---|
| `GET /api-gestion/contenedores` | `q` (código, dirección, zona o tipo), `estado`, `zona`, `tipo`, `pagina`, `por_pagina` |
| `GET /api-usuarios/usuarios` (solo Administrador) | `q` (nombre, apellido o correo), `rol`, `estado` (incluye `pendiente`), `pagina`, `por_pagina` |

- `pagina` desde 1; `por_pagina` de 1 a 100 (25 si no se indica). Respuesta: `datos` y
  `meta = {total, pagina, por_pagina, paginas, desde, hasta}`. Una página que no existe devuelve `datos` vacío y
  `desde = 0`.
- `q` tiene hasta 60 caracteres; `%` y `_` se tratan como texto (no son comodines) y todo va con consultas
  preparadas (probado con una inyección SQL en la búsqueda).
- Cualquier parámetro inválido responde 400 con el motivo.

## 3. Validaciones (H11b)

| Recurso | Reglas nuevas |
|---|---|
| Contenedor | código 1-30 (letras, números, `.`, `-`, `_`); dirección 1-200; capacidad entera de 1 a 100 000 L; latitud y longitud **juntas**, en rango; **zona y tipo de residuo obligatorios y existentes** (alta) o existentes (edición); estado válido. Antes un tipo desconocido se ignoraba en silencio al editar |
| Centro de acopio | nombre 1-120; dirección ≤ 200; capacidad entera ≥ 1; ubicación en pares; **estado válido** (antes uno inventado se cambiaba por «activo» sin avisar) |
| Maquinaria | código 1-30 con caracteres válidos; marca y modelo ≤ 60 (el tipo y el estado ya se validaban) |
| Camión | matrícula 1-20 (letras, números, espacio, guion); marca y modelo ≤ 60; capacidad entera de 1 a 100 000 kg; estado válido |

Las reglas viven en clases **puras** (`ValidacionContenedor`, `ValidacionCentro`, `ValidacionMaquinaria`,
`ValidacionCamion`, `Campos`, `Paginacion`, `FiltrosUsuario`) que no tocan la base; lo que necesita la base (que la
zona exista) lo comprueba el controlador. `Paginacion.php` y `Campos.php` son copias idénticas entre APIs y una
prueba unitaria vigila que no se separen.

## 4. Pantallas

- **Contenedores** y **Usuarios**: búsqueda con espera de 350 ms (pide al servidor, no filtra en el navegador),
  filtros (estado, zona y tipo; rol y estado), paginación (10, 25 o 50 por página, «Anterior» y «Siguiente»,
  «Mostrando 1–10 de 37») y **Exportar CSV** de **todo lo que coincide con los filtros** (no solo la página). La
  exportación de usuarios no lleva contraseñas. «Limpiar» borra también la búsqueda.
- Los usuarios `pendiente` (esperan su enlace de correo) se ven en naranja.
- `js/lista.js` junta lo común (paginador, espera, CSV con protección contra fórmulas de planilla); `reportes.js` lo
  reutiliza en lugar de tener su propia copia.
- Al ordenar por columna se ordena **solo la página que se ve** (el orden en el servidor queda para una próxima parte).

## 5. Pruebas (17/10/2026)

- **Unitarias: 236 → 334.** Paginación (bordes, meta, búsqueda, escape de comodines), filtros, cada regla de cada
  validador con su caso malo y su caso bueno, copias idénticas, y las pantallas (cargan `lista.js`, el CSV está en
  un solo lugar, no usan el buscador local).
- **HTTP: 415 → 484.** Doce altas inválidas de contenedor rechazadas sin dejar rastro; ediciones inválidas que
  no tocan el dato; recorrer todas las páginas devuelve **todos los contenedores una vez cada uno**; cada filtro da
  lo mismo que filtrar la lista entera; búsqueda con `%` y con inyección SQL; usuarios (rol, estado, correo, 403
  para vecino, 401 sin sesión); y las altas inválidas de centros, maquinaria y camiones.
- **Mutaciones: 7 de 7** (escape de comodines, tope de capacidad, zona obligatoria, cuenta de la última página,
  filtro de estado de contenedores, filtro de rol de usuarios, existencia del tipo de residuo).
- **En el navegador:** páginas de 4 (1–4, 5–8, 9–10, «Siguiente» deshabilitada al final), por página, filtro de
  estado, limpiar, búsqueda con resultados y sin ellos, el CSV descargado (10 filas, valores correctos) y la lista de
  usuarios con rol y búsqueda por correo; consola sin errores. Se arreglaron las pruebas viejas que creaban
  contenedores sin zona ni tipo.
- **No verificado:** el aspecto visual de los controles de paginación (capturas no disponibles).

## 6. Limitaciones

- El orden por columna es solo dentro de la página.
- Las otras listas no se paginan (ver decisiones).
- `por_pagina` admite hasta 100; para exportar todo el CSV pide la lista sin paginar (con miles de filas habría que
  exportar desde el servidor).
