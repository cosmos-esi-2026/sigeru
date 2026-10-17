---
materia: Programación Full Stack
entrega: 3ra (ampliación: mapa interactivo)
fecha: 2026-10-14
---

# Ampliación: mapa interactivo

Se hizo en la parte 6 de la mejora de la página de la 3ra entrega y cierra el hallazgo H7: la letra
recomienda un mapa en el nivel intermedio y los contenedores y centros de acopio ya tenían latitud y
longitud. **No cambian ni el modelo de datos ni las APIs:** el mapa usa lecturas que ya existían y respeta
los permisos de cada una.

## 1. Decisiones (Fornos, 14/10/2026)

- **Leaflet 1.9.4 dentro del repo** (`frontend-usuarios/vendor/leaflet/`, licencia BSD-2), descargado una vez
  y verificado contra los SHA-256 que publica leafletjs.com. La página no pide la librería a internet.
- **El fondo (las calles) viene de OpenStreetMap** con su atribución. **Sin red**, el mapa sigue funcionando
  sobre fondo liso, con un aviso, y la misma información está en una tabla. No se guardan mosaicos en el
  repo: la política de uso de OpenStreetMap prohíbe la descarga masiva.
- **Los cuatro roles ven el mapa**, cada uno con las capas que su permiso le deja leer.
- **La versión pública** (en la landing, sin sesión) queda para la parte 8, junto con el endpoint público
  acotado que necesita.

## 2. Qué muestra `mapa.html`

| Capa | Quién | Desde |
|---|---|---|
| Contenedores, coloreados por estado (filtros: estado, tipo de residuo, zona) | Los cuatro | `GET /contenedores` |
| Centros de acopio | Administrador, Operario | `GET /centros` |
| Incidencias abiertas (abierta o en curso) sobre su contenedor | Administrador (todas), Cuadrilla y Vecino (las suyas) | `GET /incidencias` |

Al tocar un contenedor se abre su ficha breve: el Administrador y la Cuadrilla tienen el enlace a
`contenedor.html` (con su historial), y el Vecino, **«Informar un problema acá»**, que abre
`informar-problema.html?contenedor=CODIGO` con el código ya cargado (RF-218: elegir el contenedor en el mapa).
El formulario del vecino tiene a su vez el enlace «buscalo en el mapa». El Operario solo mira.

**Accesibilidad:** el mapa no es el único camino a nada. Debajo está la tabla «Lo que se ve en el mapa» con
los mismos contenedores, sus enlaces y un botón «Ver en el mapa» que centra el mapa y abre la ficha. Los
centros y las incidencias son marcadores con nombre (`title` y `alt`). La rueda del mouse no hace zoom
(para no atrapar el desplazamiento de la página): se usan los botones + y −.

## 3. Pruebas

- **Unitarias (166):** el menú y la API coinciden también para el mapa; Leaflet está en el repo, con su
  licencia, y su `leaflet.js` tiene el hash verificado; `mapa.html` lo carga de la copia local; y el único
  pedido a internet de todo el front (la dirección de los mosaicos) está en un solo archivo, `js/mapa.js`.
- **Por HTTP (311):** `mapa.html` y los archivos de Leaflet se sirven a través de la lista blanca.
- En el navegador, los cuatro roles a 375, 768 y 1000 px, los filtros, las capas, la ficha, el camino
  formulario → mapa → formulario del vecino, y un corte simulado del fondo.

## 4. Limitaciones

- Sin red no hay calles: solo los puntos, que sin referencias se ubican peor. Es la consecuencia de no guardar
  mosaicos (política de OSM).
- Los contenedores sin latitud y longitud no aparecen en el mapa: la tabla los lista como «Sin ubicación».
  Hoy el formulario de contenedores no pide coordenadas (se podría sumar elegirlas en el mapa).
- Los mosaicos de OpenStreetMap son un servicio de uso razonable, no para producción con mucho tráfico: en una
  instalación real habría que usar un proveedor propio o con acuerdo.
- Los marcadores circulares de los contenedores no se enfocan con el teclado; se llega a ellos por la tabla.
