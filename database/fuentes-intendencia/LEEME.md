# Datos de la Intendencia de Montevideo (fuente de los contenedores de la demostración)

Estos dos archivos se descargaron el **22/10/2026** del Catálogo de Datos Abiertos:
«Contenedores de residuos domiciliarios - Ubicación, circuitos y frecuencia de recolección»
https://catalogodatos.gub.uy/dataset/contenedores-de-residuos-domiciliarios-ubicacion-circuitos-y-frecuencia-de-recoleccion

- **Autor:** División Limpieza de la Intendencia de Montevideo. **Licencia:** «Licencia de DAG de Uruguay» (así figura en el catálogo; texto en
  https://www.gub.uy/agencia-gobierno-electronico-sociedad-informacion-conocimiento/sites/agencia-gobierno-electronico-sociedad-informacion-conocimiento/files/documentos/publicaciones/licencia_de_datos_abiertos_0.pdf).
  Hay que citar la fuente. Los datos se regeneran todos los días; esta es una copia congelada de esa fecha.
- Descargados desde `https://datos-abiertos.montevideo.gub.uy/` (Contenedores_domiciliarios.zip y Circuitos_recoleccion.zip).

| Archivo | Bytes | SHA-256 |
|---|---|---|
| `Contenedores_domiciliarios.zip` (trae `Contenedores_domiciliarios.csv`, 11.035 filas) | 348.564 | `22aedf04d9624879629d5559759464f88782aeac05f6d0939009057c4f328878` |
| `Circuitos_recoleccion.zip` (trae `Circuitos_recoleccion.csv`, 137 filas) | 34.597 | `cc5447f7bad47c6941186dcb888fe1706fa7708275697336b45d27b7f9c0d063` |

**Campos.** Contenedores: `GID; COD_CIRCUITO; TURNO_HORARIO; X; Y; MOTIVO` (MOTIVO vacío = activo; «Mantenimiento» o «Sin instalar» = inactivo).
Circuitos: `GID; COD_CIRCUITO; MUNICIPIO (A, B, C, CH, D, E, F, G); VERTICES` (polígono WKT). Un circuito puede tener varios polígonos.
Las coordenadas están en **SIRGAS2000 / UTM huso 21 sur (EPSG:31981)**: `generadores/utm.py` las pasa a latitud y longitud.

**Cómo se usan.** `generadores/importar_contenedores_intendencia.py` elige 100 contenedores activos por municipio y arma `database/datos_defensa.sql`
(ver `plan-rutas-defensa.md`). **Es una muestra para la demostración, no un inventario real.** Si el sistema se llevara a producción, la Intendencia
cargaría sus propios contenedores. Citar siempre esta fuente donde se muestren estos datos.
