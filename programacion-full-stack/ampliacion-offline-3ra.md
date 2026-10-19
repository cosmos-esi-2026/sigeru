---
materia: Programación Full Stack
entrega: 3ra (extras, parte 21: aplicación instalable y cuadrilla sin señal)
fecha: 2026-10-19
---

# Ampliación: aplicación instalable y cuadrilla sin señal

Parte 21 del plan de extras (la de más riesgo). Objetivo: que SiGeRU se pueda **instalar en el celular** y que la **Cuadrilla registre trabajo aunque se quede sin señal**. Sesión autónoma (Fornos pidió seguir sin responder): las decisiones marcadas «SIN Fornos» se pueden corregir.
**Migración 016** (una tabla de soporte, `clave_idempotencia`, fuera del modelo de clases: no entra en la huella de la base, que sigue siendo `d6c2eadfbc82fcdb`).

## 1. Qué se hizo

**Aplicación instalable (PWA)**
- `frontend-usuarios/manifest.webmanifest`: nombre, nombre corto «SiGeRU», inicio `login.html`, alcance `./`, modo `standalone`, colores y **tres íconos** (192, 512 y 512 «maskable»).
  Los íconos **se generan desde el logo vectorial** con `generadores/pwa_iconos.py` (lee los `<path>` del `logo.svg`, aplana las curvas y rellena con la regla par-impar; solo necesita Pillow). No se dibujó ni se generó nada con IA.
- `frontend-usuarios/sw.js` (service worker, alcance `/sigeru/frontend-usuarios/`): guarda en caché **solo archivos estáticos de la aplicación**, con una versión (`sigeru-estaticos-v1`: al cambiarla se borra lo anterior); páginas con «primero la red»
  y, si no hay red, la copia guardada o `sin-conexion.html`; estáticos con «lo guardado y se actualiza en segundo plano». **Nunca toca `/sigeru/api-*`**, ni pedidos que no sean GET, ni de otros orígenes (los mosaicos del mapa), y solo guarda respuestas
  del mismo origen: lo que viene de las APIs (personas, reclamos, sesión) **no se guarda por este medio**.
- `js/pwa.js` registra el service worker (en `localhost` o con HTTPS; todo en `try/catch`: si no se puede, la página anda igual). Todas las pantallas enlazan el manifiesto y llevan `theme-color`. `.htaccess`: tipo `application/manifest+json` y revalidación del manifiesto.

**Cuadrilla sin conexión — solo dos acciones: registrar un vaciado y registrar un avance de incidencia**
- `js/cola-offline.js`: cola en **IndexedDB**. Cada operación lleva una **clave única (UUID)** que viaja en el encabezado `Idempotency-Key`. Solo admite esas dos acciones, solo del rol Cuadrilla y solo hacia esas dos rutas de las APIs.
  En la cola hay únicamente lo que se escribió en el formulario (código del contenedor, kilos, texto del avance): ninguna credencial.
- `js/pendientes.js` (barra en las pantallas de la Cuadrilla): «**N pendientes de enviar**», botón «Enviar ahora», lista con «Reintentar» y «Descartar». Se envía **sola al volver la red** (evento `online`), al abrir una pantalla y cada minuto mientras haya algo.
- Los formularios de **vaciado** (`recoleccion-detalle.js`) y de **avance** (`incidencia-detalle.js`) mandan siempre su clave; si falla la red (y es la Cuadrilla), el dato queda en la cola y la pantalla lo dice («Sin conexión: el vaciado quedó guardado en este teléfono…»).
  Al enviarse desde la cola, la pantalla se refresca sola (evento `sigeru:cola-enviada`).
- **Casos de borde:** (a) la recolección se canceló mientras tanto → el servidor responde 409, la operación queda marcada **«rechazada»** con el motivo y **el dato intacto**, y la persona decide («Reintentar» o «Descartar»); no se reintenta sola.
  (b) La sesión venció (401) → todo queda guardado, la barra avisa «Tu sesión venció: volvé a entrar» y no se borra nada. (c) Servidor caído (5xx, 429) → sigue pendiente. (d) Lo guardado por otra persona en el mismo teléfono ni se envía ni se borra.
  (e) No hay contraseñas ni datos de la sesión en la cola ni en la caché.

**Servidor — las APIs ignoran los duplicados**
- Migración `016-clave-idempotencia.sql` (reflejada en `schema.sql`, `datos_prueba.sql` y `dump_estructura.sql`): tabla `clave_idempotencia` (clave, usuario, operación, estado, código y respuesta, fecha).
- `core/Idempotencia.php` (copia idéntica en `api-gestion` y `api-recoleccion`; la prueba falla si difieren). `Idempotencia::comenzar()` **reserva la clave** antes de hacer nada: si ya existía **terminada**, devuelve **la misma respuesta con `"repetida": true`** sin ejecutar de nuevo;
  si está **en proceso**, 409 (dos pedidos iguales simultáneos no corren a la vez); si es de **otra persona u otra operación**, 409; una clave que no es UUID, 400. `Response::json()` (idéntico en las tres APIs) llama a `Idempotencia::terminar()`:
  **guarda las respuestas exitosas (2xx)** y, si hubo error, **suelta la reserva** para poder corregir y reintentar. Una reserva «en proceso» de más de 2 minutos se considera abandonada. Las claves de más de 30 días se limpian solas.
- Se aplica solo en `POST /recolecciones/{id}/vaciados` y `POST /incidencias/{id}/avance`. **Sin el encabezado todo sigue como antes.**

## 2. Qué se pudo verificar y qué no

**Verificado (con evidencia):**
- **Unitarias (`tests/unitarias-offline.php`):** formato de clave (UUID válido y 8 formas inválidas, incluido un salto de línea: la prueba encontró que el `$` de la expresión lo aceptaba y se corrigió con la bandera `D`), copias idénticas de `Idempotencia.php`, manifiesto, íconos (PNG y medidas reales),
  el service worker (precarga solo lo que existe, no toca APIs/POST/otros orígenes, versión, borra lo viejo, `sin-conexion.html`), todas las pantallas enlazan el manifiesto y registran el SW, la barra solo está en pantallas de la Cuadrilla, la cola solo admite las dos acciones.
- **HTTP (`tests/correr-tests-idempotencia.php`):** el mismo vaciado con la misma clave **no se duplica** (queda 1 en la base y la segunda respuesta viene marcada «repetida»); con otro contenido sigue devolviendo lo de la primera vez; otra clave es otra operación; sin clave, el reenvío da 409 «ya se vació»
  (con clave devuelve el éxito original); un error no se recuerda; reserva reciente = 409 y abandonada = se libera; clave de otra persona o de otra operación = 409; el avance con la misma clave deja **un solo** registro en el historial.
- **Cola en el navegador (`tests/js/cola-offline.html`, 26 pruebas, 26 en verde):** lógica pura, IndexedDB real y `fetch` simulado: sin red no se envía y se corta; sesión vencida deja todo guardado; 503 cuenta el intento; al volver la red salen los 2 en orden con su clave; un segundo envío no manda nada;
  el reintento lleva **la misma clave**; una recolección cancelada queda «rechazada» con el motivo y el dato intacto y no se reenvía sola; lo de otra persona no se toca. **Escribir estas pruebas encontró un error real** (la cola no marcaba las rechazadas ni contaba los intentos) y se corrigió.
- **Lógica del service worker (`tests/js/sw.html`, 10 pruebas, 10 en verde):** se carga `sw.js` con `caches`, `fetch` y los eventos simulados: precarga los 26 archivos, borra versiones viejas, nunca atiende pedidos a las APIs (GET ni navegación), ni POST, ni otros orígenes; con red guarda la página; sin red la contesta desde la copia,
  y una página nunca guardada muestra `sin-conexion.html`; en todo el caché no hay ni una respuesta de las APIs.
- **De punta a punta en el navegador, como Cuadrilla:** con una recolección en curso, se «cortó la red» (el `fetch` de los pedidos POST a las APIs falla con `TypeError`) y se registró un vaciado de 25 kg: la pantalla dijo «Sin conexión: el vaciado quedó guardado en este teléfono…», la barra mostró **«1 pendiente de enviar»** y **la base seguía con 0 vaciados**.
  Al volver la señal el pedido **llegó al servidor pero se perdió la respuesta** (el peor caso); la cola lo dejó pendiente y, al reenviar con la **misma clave**, el servidor devolvió el resultado de la primera vez. Resultado en la base: **un solo vaciado de 25 kg** y la clave en estado «terminada». La barra desapareció y la lista de vaciados se actualizó sola. Los datos de la prueba se borraron (huella de la base sin cambios).

**No se pudo verificar:**
- **El service worker registrado de verdad.** El navegador integrado de esta sesión **no permite registrar ningún service worker** (ni uno vacío: «An unknown error occurred when fetching the script»), así que no se pudo ver el caché real, ni el comportamiento con la red caída, ni la página de «sin conexión» en vivo. Lo que sí se probó es su lógica (arriba, con entorno simulado) y que el archivo se sirve bien
  (`text/javascript`, 200, alcance correcto). **Hay que probarlo en un Chrome o en un celular real.**
- **La instalación en un celular real** (Android/iOS) ni el aviso «Agregar a la pantalla de inicio»: no hay un teléfono ni Lighthouse en esta sesión. El manifiesto (tipo `application/manifest+json`), los tres íconos y el alcance cumplen lo que el navegador pide, pero **falta probarlo con un celular**.
- **El modo avión real:** el corte de red se simuló haciendo fallar el `fetch`; no se cortó la conexión del equipo.
- **En el servidor `sigeru-srv` hace falta HTTPS** para que el celular instale la aplicación y registre el service worker (en `localhost` del pendrive funciona sin eso). Ver `administracion-sistemas-operativos/novedades-3ra-para-el-servidor.md`.

## 3. Decisiones «SIN Fornos»

1. **Solo dos acciones sin conexión** (vaciado y avance), como pedía el plan: son las que la cuadrilla hace en la calle; el resto (crear, asignar, resolver, cancelar) exige conexión para no generar conflictos.
2. **El dato se guarda al fallar el envío, no antes:** cuando hay señal el flujo es el de siempre; la cola solo entra si el pedido no pudo salir. La pantalla que registra el vaciado tiene que haberse abierto con señal (los datos de la recolección vienen de la API y no se guardan en el teléfono).
3. **Una operación rechazada no se descarta sola:** la decide la persona (no se pierde un dato por un error ajeno).
4. **Tabla de claves aparte, fuera del modelo:** es de soporte (como `auditoria` o `limite_peticion`), no cambia la huella de la base.
5. **Se guardan 30 días** las claves y sus respuestas; es más que cualquier tiempo razonable sin señal.
6. **Límite conocido:** si el servidor se cae exactamente entre el commit de la operación y la respuesta, la clave queda «en proceso» y a los 2 minutos se libera: un reenvío posterior podría duplicar. Es una ventana de milisegundos y se documenta, no se oculta.
7. **Se prefirió no cachear datos de la API** aunque eso limite el uso sin señal: así no quedan reclamos ni datos de personas en el teléfono por este medio. (IndexedDB solo guarda lo que la persona acaba de escribir y todavía no se envió.)
