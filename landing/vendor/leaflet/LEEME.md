# Leaflet 1.9.4 (copia local)

Librería de mapas usada por `mapa.html` (parte 6 de la mejora de la 3ra entrega). **Esta carpeta es una copia
idéntica de `frontend-usuarios/vendor/leaflet/`** para el mapa público de la landing (parte 8): la landing no puede cargar
archivos de otra aplicación. Una prueba unitaria falla si las dos copias difieren. Se guarda dentro del
repo para que la página no pida nada a internet en tiempo de ejecución (la defensa puede ser sin red).

- Versión: 1.9.4 (mayo de 2023). Licencia: BSD 2-Clause (`LICENSE`, de este mismo directorio).
- Descargada el 14/10/2026, con autorización de Franco Fornos, desde
  `https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/` (la licencia, del paquete npm `leaflet@1.9.4`).
- Verificación: los SHA-256 coinciden con los que publica https://leafletjs.com/download.html
  - `leaflet.js`  `sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=`
  - `leaflet.css` `sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=`
- No se modifica. Para actualizarla, se reemplazan los archivos y se anotan los hashes nuevos.

El **fondo del mapa** (las calles) sí viene de internet: los mosaicos de OpenStreetMap, con su
atribución. Sin red, el mapa sigue funcionando sobre fondo liso, con un aviso. No se guardan mosaicos en el
repo: la política de uso de OpenStreetMap prohíbe la descarga masiva.
