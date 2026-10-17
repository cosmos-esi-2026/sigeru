---
materia: Programación Full Stack
entrega: 3ra (ampliación: foto de la incidencia y pantalla de rutas)
fecha: 2026-10-17
---

# Ampliación: foto de la incidencia (P4b) y pantalla de rutas (P5b)

Las dos partes «si sobra» del plan, hechas en la madrugada del 17/10 en la sesión autónoma.

## 1. Foto de la incidencia (RF-222, RF-223)

**Qué hace.** Al informar un problema el vecino puede elegir una foto (JPG, PNG o WebP de hasta 2 MB), ve una vista
previa y puede quitarla. El reporte se registra primero y la foto se sube enseguida; si la foto falla, el reporte queda
registrado igual y se le avisa. La ven el vecino que informó, la administración y la cuadrilla a la que se asigne; en la
ficha de la incidencia aparece debajo de la descripción.

**Decisiones (SIN Fornos):** una foto por incidencia y sin reemplazo (en el espíritu del historial de solo agregado); solo
la sube quien informó y solo mientras la incidencia sigue abierta; se guarda siempre como JPEG de hasta 1600 px.

**Qué se hizo para que sea seguro** (con una prueba para cada punto):

| Riesgo | Defensa |
|---|---|
| Un archivo que no es una foto con nombre `.jpg` | El tipo se lee del **contenido** (`finfo` sobre los primeros bytes), no del nombre ni de lo que declara el navegador |
| Código escondido en la imagen o pegado al final | La imagen se **vuelve a dibujar** con GD y se guarda un JPEG nuevo: lo que venga después del final del archivo desaparece |
| Datos de ubicación de la cámara (EXIF/GPS) | Se pierden al volver a dibujar. Si el celular marcó la orientación, se aplica antes de descartar el EXIF |
| «Bomba de descompresión» (pocos bytes, millones de píxeles) | Se rechaza **antes de decodificar** si pasa de 6000 px de lado o de 24 millones de píxeles |
| Archivos enormes | Tope de 2 MB, comprobado contra el tamaño informado **y** el real del disco (413) |
| Subir un script y ejecutarlo | El archivo vive en `almacen/incidencias/` **fuera de lo que sirve Apache** (la lista blanca del `.htaccess` no lo incluye: pedirlo por la web da 403), con un nombre de 32 hex al azar que nunca sale del cliente, y se guarda como `.jpg` |
| Que otro vea la foto | `GET /incidencias/{id}/foto` aplica la misma regla de alcance que la incidencia: ajena → 404; operario → 403; sin sesión → 401 |
| Que el navegador interprete la imagen como otra cosa | `Content-Type: image/jpeg`, `X-Content-Type-Options: nosniff`, `Content-Security-Policy: default-src 'none'; sandbox`, `Cache-Control: private` |
| Que quede un archivo huérfano si falla la base | Si la ficha no se puede insertar, el archivo se borra; dos subidas simultáneas: la segunda recibe 409 (índice único) |

**Datos.** Tabla nueva `foto_incidencia` (migración `006`): una fila por incidencia con el nombre del archivo, el peso y las
medidas; se va en cascada con la incidencia. Es parte de `Incidencia` y no una clase aparte (modelo conceptual sin cambios).
La huella de la base no cambia (la tabla no entra en esa cuenta).

**Requisito del servidor:** la extensión **GD** de PHP. En el XAMPP portable se prendió (`extension=gd` en
`herramientas/xampp/php/php.ini`); en Docker el `Dockerfile` instala `libjpeg`, `libpng` y `libwebp` y la compila, y
`SIGERU_ALMACEN` apunta a una carpeta que Apache puede escribir. **Sin GD** el servidor responde 503 «no puede procesar
fotos todavía» y las pruebas lo dicen como «omitidas».

**Límites:** no se puede reemplazar ni borrar una foto; no hay galería (una sola); la limpieza de archivos de una
incidencia que se borre a mano de la base no es automática (en el sistema no hay API para borrar incidencias); el
almacén hay que sumarlo a la copia de seguridad (es dato de la instalación y no entra en Git).

## 2. Pantalla de rutas (P5b)

`rutas.html` (menú «Rutas», Administrador): la API de rutas ya existía y estaba probada; faltaba la pantalla. Lista
(nombre, día, hora, zona, estado), alta y edición en una ventana, y baja lógica («inactiva», con confirmación) como
camiones. Probado en el navegador de punta a punta (crear, editar la hora, dar de baja) y el dato de prueba se borró.
Sin cambios en APIs ni en el modelo.

## 3. Pruebas

- **Unitarias (22 nuevas):** formatos válidos, PNG transparente, reducción, texto/GIF/SVG/«PHP disfrazado»/JPEG cortado
  rechazados, 413, imagen ancha y «bomba» rechazadas sin decodificar, **EXIF/GPS y marca del teléfono ausentes**, cola
  de código eliminada, nombres del almacén.
- **HTTP (29 nuevas):** quién puede subir y ver (cinco roles/sesiones), cada rechazo con su código, lo guardado (nombre,
  carpeta, peso en la ficha), `403` al pedir el almacén por la web y `409` en una incidencia cerrada o con foto.
- **Mutaciones: 6 de 6** (se deja de volver a dibujar la imagen, se admite GIF, se quita la revisión de medidas, se
  abre el permiso al administrador, se quita el control de alcance al ver, se permite subir a una cerrada).
- **En el navegador:** elegir una foto (vista previa), elegir un `.txt` (error en español y sin vista previa), enviar el
  reporte con foto («Foto: Adjuntada»), y la ficha muestra la imagen (800 × 600). Se borraron los datos de prueba.
- **No verificado:** el aspecto visual; fotos reales de celular con orientación EXIF (el PHP de las pruebas no
  tiene la extensión `exif`); WebP en el Apache de Docker; la construcción de la imagen Docker con GD (la verifica GitHub al pushear).
