---
materia: Programación Full Stack
entrega: 3ra (extras, parte 22: pulido)
fecha: 2026-10-19
---

> **Actualización del 25/10/2026:** el service worker, el manifiesto y la guía «¿Dónde tiro esto?» que se mencionan abajo se sacaron del sistema (ver `ampliacion-simplificacion-3ra.md`). **El modo oscuro, las páginas de error, los estados vacíos y la imagen para compartir siguen.**

# Ampliación: pulido (modo oscuro, errores amables, estados vacíos, imagen para compartir)

Parte 22 del plan de extras. Objetivo: el acabado que hace que se vea como un producto. Sesión autónoma (Fornos pidió seguir sin responder): las decisiones marcadas «SIN Fornos» se pueden corregir. **Sin migración ni tablas nuevas** (la huella de la base no cambia).

## 1. Modo oscuro

- **Tokens de color en `css/base.css`** (copia idéntica en `landing/` y `frontend-usuarios/`; la prueba falla si difieren). Se agregaron los tokens que faltaban para poder cambiar de paleta sin tocar cada regla:
  `--superficie` (fondo de tarjetas, tablas y campos; antes se usaba el blanco fijo), `--accion` y `--accion-hover` (fondo de botones principales, con texto blanco), `--peligro`, `--barra` (menú lateral y escena del login), `--pie`, `--borde-fuerte`,
  `--azul-*`, `--mapa-fondo`, `--cabecera-fondo`, `--resalte`. Los estilos que tenían colores fijos pasaron a usarlos.
- **Cómo se elige:** automático (sigue al sistema con `prefers-color-scheme`), oscuro o claro. El **botón redondo** de abajo a la derecha (en todas las pantallas, incluidas la landing, el login y las páginas de error) cambia entre los tres estados; la elección se guarda con `localStorage` **dentro de `try/catch`**:
  si el navegador no lo permite (ventana privada, datos bloqueados) la página anda igual y solo no se recuerda. `js/tema.js` (copia idéntica en las dos aplicaciones) se carga en el `<head>` para aplicar el tema antes de pintar, sin destello. El botón tiene nombre accesible y no se imprime.
- **Contraste AA verificado con los colores reales** (`tests/unitarias-tema.php`): en cada paleta, **25 combinaciones** de texto y fondo (texto normal, suave y tenue sobre hueso y superficie, enlaces verdes, blanco sobre botones, cada par de aviso/ok/error/neutro/azul, menú lateral…) con **contraste de al menos 4,5:1** calculado con la fórmula de WCAG.
  Esa prueba encontró un caso real: el botón de peligro usaba el color del texto de error como fondo, y en oscuro habría quedado blanco sobre rosa; por eso existe `--peligro`. También comprueba que **todos los `var(--x)` usados estén definidos** y que **ningún fondo use el blanco fijo**.
- **Al imprimir siempre sale blanco** aunque el tema sea oscuro (regla de impresión de Reportes).
- **Límites declarados:** el **fondo del mapa (mosaicos de OpenStreetMap) sigue claro** en modo oscuro (son imágenes de un servicio externo); las fotos de la landing no cambian; y el contraste se calculó sobre los tokens, no sobre textos puestos encima de fotos.

## 2. Páginas de error amables

- `landing/errores/403.html`, `404.html` y `500.html`: con dibujo propio (un contenedor con un signo de pregunta, un candado y una llave), el código, una explicación en español y los botones «Volver al inicio» e «Ingresar». **No muestran la versión del servidor, rutas internas ni nada técnico**, llevan `noindex` y usan rutas absolutas (se muestran bajo cualquier dirección).
- `.htaccess` de la raíz: `ErrorDocument 403/404/500` hacia esas páginas (dentro de `landing/`, que la lista blanca deja pasar). **Solo se usan cuando el error lo genera Apache**: las APIs siguen contestando sus errores en JSON (comprobado). La lista blanca (403 para `tests/`, `database/`, etc.) ahora muestra la 403 propia y sigue sin servir el contenido.

## 3. Estados vacíos con ilustración propia

`tabla.js` tiene ahora `ilustracionVacia(tipo)`: **dos dibujos SVG hechos en el repo** (no generados con IA), con los colores del tema: un **contenedor con la tapa abierta** («todavía no hay nada») y el mismo contenedor con una **lupa** («nada coincide con la búsqueda o los filtros», se elige solo según el texto del estado vacío).
Los usa `filaVacia`, que es la que usan las tablas de todas las pantallas (contenedores, incidencias, recolecciones, centros, camiones, maquinaria, auditoría, solicitudes, usuarios, reportes, dashboard…). Los mensajes de cada lista ya eran claros y dicen qué hacer; se revisaron y se dejaron.

## 4. Imagen para compartir y Open Graph

- `landing/img/compartir.png` (1200 × 630) generada por `generadores/compartir_imagen.py` con el logo vectorial del repo y la tipografía Bitstream Vera (que viene con reportlab y tiene licencia libre). **Sin imágenes de IA.**
- Metaetiquetas `og:*` y `twitter:*` en la landing, la guía «¿Dónde tiro esto?» y el login. **Pendiente del equipo:** `og:image` lleva la ruta `/sigeru/landing/img/compartir.png` y los sitios que arman la vista previa piden la **dirección completa** (`https://dominio/...`); hay que completarla cuando se sepa el dominio real del servidor.

## 5. Revisión a 375, 768 y 1366 px

- **Auditoría de desbordes** (se carga cada pantalla en un marco de 375 y de 768 px y se mide si la página se ensancha): **administrador** (19 pantallas), **vecino** (5), **cuadrilla** (7), **operario** (5) y las **públicas** (landing, guía, login, registro, recuperar contraseña, error 404). Encontró **un problema real**, en Maquinaria: a 375 px los títulos ocultos de la tabla y los botones de acción de cada tarjeta empujaban la página 27 px a la derecha, y a 768 px
  los textos para lectores de pantalla (posición absoluta) ensanchaban la página hasta 857 px. Se corrigió en el CSS común (títulos sin ancho, acciones que pasan de renglón, `position: relative` en el contenedor de las tablas) y **todas las pantallas quedaron sin desborde**. A 1366 px no se encontraron desbordes en las revisiones anteriores.
- **Hallazgos de la parte 11 resueltos:** **H11-1** (la impresión de Reportes se cortaba: hoja apaisada, tabla de ancho fijo, botones de ordenar sin dibujo, siempre sobre blanco) y **H11-3** (el texto de las búsquedas se cortaba: placeholders de 34 caracteres o menos y campo más ancho; en Auditoría el texto era confuso y se reescribió).
- Con el menú de la landing se ajustó el tamaño entre 900 y 1100 px, donde el botón «Ingresar» bajaba de renglón.
- El service worker (parte 21) subió a la **versión `sigeru-estaticos-v2`** y precarga `tema.js`.

## 6. Qué no se pudo verificar / decisiones «SIN Fornos»

- **Capturas de cada pantalla en cada tema:** el panel del navegador de esta sesión muestra solo unos 800 px de ancho, así que se miraron con capturas la landing, el 404, el panel de control y Contenedores (con la lupa) en **oscuro**, y el resto con las pruebas automáticas (contraste, desbordes, tokens). Conviene que alguien del equipo recorra las pantallas en oscuro con ojos humanos.
- **Revisión de textos:** se leyeron los textos de las pantallas nuevas y los de los estados vacíos; no se hizo una corrección ortográfica de todo el sistema.
- Decisiones: (1) tema **automático por defecto** (respeta al sistema y se puede forzar); (2) **un solo botón flotante** en vez de uno por pantalla (menos código, mismo lugar siempre); (3) el mapa **no** se oscurece (otro servicio); (4) las páginas de error **no** usan el menú ni el estado de la sesión: son estáticas para que nunca fallen.
- **Pruebas:** `tests/unitarias-tema.php` (contraste, tokens, interruptor, `tema.js` en el `<head>` de cada pantalla), `tests/unitarias-pulido.php` (errores, Open Graph y tamaño de la imagen, estados vacíos, impresión, placeholders) y `tests/correr-tests-errores.php` (HTTP: 404 y 403 propias, APIs en JSON, imagen servida).
