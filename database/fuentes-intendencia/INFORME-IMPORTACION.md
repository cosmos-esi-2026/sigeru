# Informe de la importacion (generado por `generadores/importar_contenedores_intendencia.py`)

## Validacion de las coordenadas

- Contenedores en el CSV: **11035** (activos: **10457**; en mantenimiento: 517; sin instalar: 61).
- Fuera de la caja de Montevideo (lat -35.0 a -34.7, lon -56.5 a -55.9): **0**.
- Con un circuito que no figura en el archivo de circuitos: **0**.
- Dentro del poligono de su propio circuito: **10960 de 11035 (99.3 %)**. (Los poligonos de la Intendencia son aproximados; el resto queda cerca del borde.)
- Conversion UTM 21S a latitud/longitud: `generadores/utm.py` (autoprueba: ida y vuelta de 0,3 mm, arco de meridiano integrado a mano y factor de escala).

## Los 10 contenedores del seed de pruebas (se conservan: mismo codigo, estado y tipo, ubicacion real)

| Codigo | Estado | Ubicacion original | Municipio | Distancia al contenedor real |
|---|---|---|---|---|
| CNT-001 | disponible | Av. Italia esq. Propios | D | 88 m |
| CNT-002 | lleno | Plaza Independencia 750 | B | 110 m |
| CNT-003 | disponible | Bvar. Artigas 2200 | B | 50 m |
| CNT-004 | disponible | Sarandi 620 | B | 19 m |
| CNT-005 | mantenimiento | Perez Castellano 1520 | B | 146 m |
| CNT-006 | disponible | 21 de Setiembre 2680 | CH | 64 m |
| CNT-007 | lleno | Av. Brasil 3040 | CH | 33 m |
| CNT-008 | disponible | Ellauri 350 | CH | 54 m |
| CNT-009 | disponible | Cno. Castro 1480 | G | 93 m |
| CNT-010 | retirado | Av. Millan 4200 | C | 46 m |

## Municipios, muestra y rutas

| Municipio | Activos en la Intendencia | En la muestra | Centro de acopio | Rutas (paradas, largo en linea recta) |
|---|---|---|---|---|
| A | 1264 | 100 | 2 | R1: 20, 9.6 km; R2: 20, 10.3 km; R3: 20, 7.6 km; R4: 20, 10.6 km; R5: 20, 7.3 km |
| B | 1388 | 100 | 3 | R1: 20, 5.6 km; R2: 20, 6.7 km; R3: 20, 5.4 km; R4: 20, 5.6 km; R5: 20, 5.5 km |
| C | 1535 | 100 | 2 | R1: 20, 9.9 km; R2: 20, 7.4 km; R3: 20, 7.8 km; R4: 20, 7.2 km; R5: 20, 6.5 km |
| CH | 1159 | 100 | 3 | R1: 20, 6.6 km; R2: 20, 6.2 km; R3: 20, 6.4 km; R4: 20, 4.8 km; R5: 20, 5.2 km |
| D | 1248 | 100 | 2 | R1: 20, 12.0 km; R2: 20, 8.7 km; R3: 20, 8.2 km; R4: 20, 10.2 km; R5: 20, 6.5 km |
| E | 1247 | 100 | 1 | R1: 20, 14.9 km; R2: 20, 7.0 km; R3: 20, 10.4 km; R4: 20, 8.1 km; R5: 20, 8.7 km |
| F | 1299 | 100 | 1 | R1: 20, 14.3 km; R2: 20, 9.2 km; R3: 20, 9.5 km; R4: 20, 11.6 km; R5: 20, 8.7 km |
| G | 1317 | 100 | 2 | R1: 20, 10.2 km; R2: 20, 8.9 km; R3: 20, 11.0 km; R4: 20, 9.5 km; R5: 20, 11.6 km |

## Salidas

- `database/datos_defensa.sql`: 801 contenedores (800 en rutas + CNT-010 retirado), 8 municipios, 8 cuadrillas, 40 rutas, 800 paradas, 40 trazados por calles ya incluidos.
- `database/rutas_defensa.json`: las paradas de cada ruta (lo lee `generadores/precalcular_trazados.py`).
- Largo total en linea recta de las 40 rutas: 341 km; promedio 8.5 km por ruta.
