> **RETIRADA el 25/10/2026.** Esta funcionalidad se sacó del sistema antes del congelamiento para dejar la aplicación más simple y defendible (ver `../ampliacion-simplificacion-3ra.md`). El documento se conserva solo como registro de lo que se hizo; **nada de lo que describe existe ya en el código ni se va a defender.**

---
materia: Programación Full Stack
entrega: 3ra (extras, parte 17: ruta sugerida automática)
fecha: 2026-10-19
---

# Ampliación: ruta sugerida

Parte 17 del plan de extras. Objetivo: que el administrador reciba **una ruta propuesta solo con los contenedores que la necesitan**, dibujada en el mapa, y pueda usarla para planificar una recolección.
Sesión autónoma (Fornos pidió seguir sin responder): las decisiones marcadas «SIN Fornos» se pueden corregir. **No hay migración ni tablas nuevas** (la huella de la base no cambia).
**No es inteligencia artificial**: es una heurística clásica, explicable en una defensa.

## 1. Cómo funciona

1. **Qué contenedores entran** (usa la predicción de la parte 16): los que están **llenos** y los que, según la estimación estadística, llegan al **90 %** dentro del horizonte elegido (**24 o 48 h**). No entran los retirados, los
   en mantenimiento, los que no tienen ubicación ni los que no tienen historial suficiente (**no se inventa una predicción**). Si pueden ser más de **25** (el tope), se quedan los más urgentes (primero los llenos, después por horas
   hasta el 90 %, y por código si hay empate) y la pantalla avisa cuántos quedaron afuera.
2. **Orden por vecino más cercano:** se arranca por el contenedor **más lejos del centro de destino** (si no se elige un centro, por el más al norte) y siempre se va al más cercano de los que faltan. La distancia es **haversine**:
   el arco sobre una esfera de 6371,0088 km de radio, **en línea recta, no por calles**.
3. **Mejora con 2-opt:** se prueba invertir cada tramo del recorrido y se queda con la inversión si lo acorta; se repite hasta que ninguna inversión mejore. Si hay centro de destino, **el recorrido termina ahí** (el centro es un extremo fijo).
4. **Kilómetros y tiempo:** suma de los tramos; tiempo = km ÷ **20 km/h** + **5 minutos por parada**. **Son supuestos fijos, no medidos, y la pantalla lo dice.**
5. Todo es **determinista**: con los mismos datos da siempre la misma ruta (los empates se resuelven por el orden de entrada).

## 2. Qué se hizo

- `api-gestion/models/RutaSugerida.php`: clase **pura** (sin base ni servidor): `distanciaKm`, `seleccionar`, `ordenar` (vecino más cercano + 2-opt), `largoKm` y `sugerir`.
- `GET /api-gestion/contenedores/ruta-sugerida?horizonte=24|48&id_zona=&id_centro=` — **solo Administrador** (la matriz de permisos lo dice; cuadrilla, operario y vecino reciben 403). Valida horizonte, zona y centro (400). **Solo lee: no crea nada.**
- `frontend-usuarios/ruta-sugerida.html` + `js/ruta-sugerida.js` (entrada «Ruta sugerida» del menú del administrador y botón «Sugerir ruta» en Recolecciones): zona, horizonte, centro de destino y camión; muestra paradas, distancia, tiempo y los supuestos;
  dibuja el recorrido con Leaflet (círculos numerados, rojo si está lleno, el centro como cuadrado, línea verde) y una **tabla con el mismo orden** (el mapa no es la única forma de leerlo). Sin internet, el recorrido se dibuja sobre fondo liso.
- **«Crear recolección con esta ruta» solo precarga:** guarda la propuesta en la pestaña (`sessionStorage`) y abre Recolecciones con el alta ya abierta: ruta de esa zona (si hay una activa), camión, centro y en las observaciones el orden de los
  contenedores («Ruta sugerida (N paradas, X km aprox.): CNT-001 > CNT-004 …», máximo 300 caracteres). **No se crea nada hasta que el administrador toca «Planificar»**; el modelo de datos no guarda las paradas (la cuadrilla las registra
  como vaciados a medida que las hace), por eso el orden viaja en las observaciones.

## 3. Decisiones «SIN Fornos»

1. **Heurística simple (vecino más cercano + 2-opt)** y no un solver: para 25 puntos alcanza y se puede explicar. En las pruebas, contra la solución óptima calculada por fuerza bruta en 12 casos de 7 puntos, el peor caso quedó a **1,20 veces el óptimo**:
   es una propuesta razonable, no la mejor posible, y la pantalla lo dice.
2. **Sin depósito de salida:** la ruta no tiene punto de partida fijo (el sistema no tiene cargadas las cocheras): empieza en el contenedor más lejano del destino. Si se quiere agregar una cochera, es un punto más al principio.
3. **Velocidad 20 km/h y 5 minutos por parada**: valores de planificación urbana razonables pero **inventados por el equipo**; por eso están declarados en pantalla, en la respuesta de la API y acá. Si la cuadrilla mide tiempos reales, hay que cambiarlos en `RutaSugerida`.
4. **El camión se elige pero no entra en el cálculo** (el sistema no guarda la capacidad de carga de los camiones): solo se precarga en el alta.
5. **Solo el administrador** la ve (es quien planifica recolecciones).

## 4. Pruebas

- **Unitarias (`tests/unitarias-ruta.php`):** haversine (1° de latitud = 111,195 km, media vuelta al ecuador = π·R, simétrica); caso a mano con 3 contenedores sobre un meridiano (2,22 km y 22 min sin destino; 3,34 km y 25 min con destino);
  0 y 1 contenedor; selección (llenos primero, horizontes de 24 y 48 h, excluye retirados, en mantenimiento, sin ubicación y sin predicción); tope de 25 con aviso de los 5 que quedan afuera y desempate por código; **12 casos de 7 puntos
  contra fuerza bruta** (permutación válida, determinista, nunca mejor que el óptimo, a menos del 25 % del óptimo); un cuadrado (3 lados = 6,67 km); las constantes declaradas.
  También se actualizaron las pruebas del menú (24 pantallas), de los pedidos a internet (solo los mosaicos del mapa) y de la matriz de permisos.
- **HTTP (`tests/correr-tests-ruta.php`):** permisos (401/403), horizonte, zona y centro inválidos (400), un caso con una zona propia de 4 contenedores llenos y la cuenta a mano por la API (3,34 km, 30 min), con centro de destino, que los retirados, en mantenimiento y
  sin historial no entran, propuesta vacía sin error, y que **pedir la ruta no crea ninguna recolección**.

## 5. Para el equipo

- La ruta es una **propuesta**: el administrador puede cambiar el orden, la ruta, el camión y la fecha antes de planificar.
- Para que haya contenedores «por llenarse» hace falta **historial de vaciados** (3 o más por contenedor): con el seed solo aparecen los ya llenos. Para una demostración se puede cargar el historial de la parte 16 con `herramientas/xampp/cargar-historial-demo.cmd` (datos marcados como demostración; no es parte del seed).
