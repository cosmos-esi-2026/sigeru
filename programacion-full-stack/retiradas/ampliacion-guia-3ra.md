> **RETIRADA el 25/10/2026.** Esta funcionalidad se sacó del sistema antes del congelamiento para dejar la aplicación más simple y defendible (ver `../ampliacion-simplificacion-3ra.md`). El documento se conserva solo como registro de lo que se hizo; **nada de lo que describe existe ya en el código ni se va a defender.**

---
materia: Programación Full Stack
entrega: 3ra (extras, parte 19: guía pública «¿Dónde tiro esto?»)
fecha: 2026-10-19
---

# Ampliación: guía «¿Dónde tiro esto?»

Parte 19 del plan de extras. Objetivo: un servicio útil **sin necesidad de cuenta**: el vecino escribe qué quiere tirar y la guía le dice en qué contenedor va, cómo prepararlo y de qué fuente sale el dato.
Sesión autónoma (Fornos pidió seguir sin responder): las decisiones marcadas «SIN Fornos» se pueden corregir. **No hay migración ni tablas nuevas** (la huella de la base no cambia).

## 1. La fuente

- **Manual de residuos domiciliarios** de la Intendencia de Montevideo, Departamento de Desarrollo Ambiental (2024):
  <https://montevideo.gub.uy/sites/default/files/biblioteca/manualderesiduosdomiciliarios.pdf>
- Se leyó el PDF completo (páginas 11, 17-19, 21-27, 29 y 38 son las que alimentan los ítems). **Cada ítem guarda la página del manual de donde sale** y se muestra en pantalla («Fuente: … página N»).
- Los enlaces que cita el propio manual (ubicación de contenedores, ecocentros, compostaje) se muestran rotulados «indicado en el manual»; no se visitaron todos, por eso no se afirma nada más que eso.
- **Limitación declarada en la página:** las reglas cambian y **varían según el departamento**; la guía es la de Montevideo. Es el aviso que se ve junto a la fuente.

## 2. Qué se hizo

- `landing/data/residuos.json`: **58 residuos** con `nombre`, `sinonimos`, `tipo`, `contenedor`, `consejo`, `fuente` y `pagina`. Tipos: los seis del sistema (Orgánico, Plástico, Vidrio, Papel, Metal, Mixto) más
  **«Especial»** (pilas, medicamentos, aceite usado, electrónicos, lámparas, escombro, muebles, neumáticos, baterías de auto, ropa y textiles, agroquímicos).
  **«Especial» no es un `tipo_residuo` del sistema**: son cosas que no van al contenedor de la calle y tienen un lugar de entrega propio (ecocentro, farmacias, comercios). La página lo aclara cuando aparece.
- `landing/donde-tiro.html` + `landing/js/donde-tiro.js`: buscador con etiqueta, filtro por tipo, resultados en una región `aria-live="polite"` («3 resultados.»), sin tildes ni mayúsculas (se normaliza), todas las
  palabras escritas tienen que aparecer en el nombre, los sinónimos o el tipo. «botella» devuelve plástico y vidrio; «pila», Especial; `?q=` precarga la búsqueda.
- **Funciona sin API ni base de datos**: es un JSON estático que se pide con `fetch`; si falla, la página dice «No se pudo cargar la guía».
- Enlaces: en el menú y en «Servicios» de la landing y en «Informar un problema» (el vecino que duda en qué contenedor va algo, la encuentra ahí).

## 3. Decisiones «SIN Fornos»

1. **Solo Montevideo**, con el aviso a la vista (es la única fuente oficial completa que se pudo leer entera).
2. **«Especial» como categoría extra**, fuera de `tipo_residuo`, para no tocar el modelo de datos.
3. **Datos en JSON estático** y no en la base: son contenido editorial con fuente, no datos operativos; así el sistema no cambia y funciona sin internet una vez cargada la página.
4. No se verificó cada uno de los 58 contra una segunda fuente: **la fuente es el manual** y cada ítem dice la página.

## 4. Pruebas

- **Unitarias (`tests/unitarias-guia.php`):** JSON válido, al menos 55 ítems, cada uno con nombre, tipo válido, contenedor, consejo, fuente existente y página; sin nombres repetidos; están los siete tipos; fuentes en https; el aviso;
  la misma búsqueda que hace el navegador (botella, pila, PIZZA en mayúsculas, bateria sin tilde, algo inexistente); la página tiene etiqueta, `aria-live` y fuente; la landing y «Informar un problema» la enlazan.
  La prueba de accesibilidad estructural ahora incluye `donde-tiro.html`.
- **HTTP (`tests/correr-tests-guia.php`):** la página y el JSON se sirven sin sesión y el JSON es idéntico al del repo; el JS no llama a ninguna API.
- **Navegador:** «botella» → 5 resultados (plástico y vidrio), «pila» → «Pilas» con la nota de «Especial»; sin desborde horizontal a 375 px.

## 5. Para el equipo

- Para agregar o corregir un ítem se edita `landing/data/residuos.json` (mismo formato) y se corre `herramientas/php/php.exe sigeru/tests/unitarias.php`: la prueba avisa si falta la fuente o la página.
- Si se quiere otra ciudad, hay que **buscar su manual** y citarlo: no copiar las reglas de Montevideo.
