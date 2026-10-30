> **RETIRADA el 25/10/2026.** Esta funcionalidad se sacó del sistema antes del congelamiento para dejar la aplicación más simple y defendible (ver `../ampliacion-simplificacion-3ra.md`). El documento se conserva solo como registro de lo que se hizo; **nada de lo que describe existe ya en el código ni se va a defender.**

---
materia: Programación Full Stack
entrega: 3ra (extras, parte 18: impacto ambiental con fuente)
fecha: 2026-10-19
---

# Ampliación: impacto ambiental

Parte 18 del plan de extras. Objetivo: que el sistema muestre su impacto ambiental **con cifras que se puedan defender**. Sesión autónoma (Fornos pidió seguir sin responder): las decisiones
marcadas «SIN Fornos» se pueden corregir. **Los factores se buscaron en una fuente verificable, se citan en pantalla y acá, y se comprobaron contra el documento original.**

## 1. La fuente y cómo se verificó

- **Fuente:** modelo **WARM (Waste Reduction Model) de la EPA de Estados Unidos, versión 16 (diciembre de 2023)**, «Documentation for Greenhouse Gas Emission and Energy Factors Used in WARM: Containers, Packaging, and
  Non-Durable Good Materials Chapters», EPA-530-R-23-021.
  Enlace: <https://www.epa.gov/system/files/documents/2023-12/warm_containers_packaging_and_non-durable_goods_materials_v16_dec.pdf>
- **Verificación (19/10/2026):** se descargó el PDF oficial y se leyeron las tablas de emisiones netas por material de cada capítulo (Exhibit 1-4 vidrio, 2-7 metales, 3-7 papel y 5-3 plásticos); los valores de abajo
  son los de esas tablas, no de un resumen de terceros. La definición de «Mixed Metals» (35 % latas de aluminio y 65 % de acero) y de «Mixed Plastics» (40 % HDPE y 60 % PET) sale del documento general de WARM v16.
- **Qué mide el factor:** las emisiones **netas de reciclar** menos las **netas de enterrar** (relleno sanitario), en MTCO2E por tonelada corta; es decir, cuánto CO2 equivalente se **evita** al reciclar en vez de enterrar.
  Pasado a kg de CO2e por kg: × 1000 / 907,18474 (1 tonelada corta = 907,18474 kg; 1 MTCO2E = 1000 kg).

| Tipo de SiGeRU | Material de WARM | Reciclar | Enterrar | Evitado (MTCO2E/t corta) | Factor guardado (kg CO2e por kg) |
|---|---|---|---|---|---|
| Plástico | Mixed Plastics (Exhibit 5-3) | −0,93 | +0,02 | 0,95 | **1,047** |
| Vidrio | Glass (Exhibit 1-4) | −0,28 | +0,02 | 0,30 | **0,331** |
| Papel | Mixed Paper (general) (Exhibit 3-7) | −3,55 | +0,07 | 3,62 | **3,990** |
| Metal | Mixed Metals (Exhibit 2-7) | −4,39 | +0,02 | 4,41 | **4,861** |
| Orgánico | — | | | sin fuente verificada | *sin factor: solo kg* |
| Mixto | — | | | no reciclable | *sin factor: solo kg* |

**Si no se consigue una fuente confiable para un tipo, no se muestra CO2 para ese tipo: solo kg.** Es el caso del orgánico (su beneficio sería por compostaje, que no se verificó) y del mixto.

## 2. Decisiones (SIN Fornos)

- **La cifra de CO2 es siempre una «estimación»**, rotulada así en la landing, en Reportes y en la API, con un enlace a la fuente. Aclara que son factores de referencia de **otro país** (EE. UU.), no medidos en Uruguay.
- **Base del cálculo:** kilos **recolectados** (de `vaciado`) de cada tipo **reciclable con factor** × su factor. Se supone que lo reciclable se recicla en lugar de enterrarse. Además se informan los kilos
  **recibidos en los centros** (recepciones de la parte 12, descontadas sus correcciones) y el porcentaje reciclable.
- **En la landing se muestran las cifras agregadas (sin datos personales)** con freno por IP (120 pedidos por hora) y la caja «¿De dónde salen las cifras de CO2?» con las cuatro fuentes. En Reportes, por mes y exportable a CSV.
- **No se inventan equivalencias** (árboles, autos, etc.): no se verificaron.
- Los factores se editan solo por migración (no hay pantalla para tocarlos): cambiarlos exige cambiar también la fuente.

## 3. Modelo de datos (migración 015)

`tipo_residuo` suma `factor_co2_kg_por_kg` (decimal, nulo = sin fuente verificada), `fuente_factor` (texto con el exhibit y los números) y `url_fuente`. `reciclable` ya existía. No hay tablas nuevas: **la huella de la base cambia** (cambia el contenido de `tipo_residuo`).

## 4. API y pantallas

| Qué | Detalle |
|---|---|
| `GET /api-gestion/publico/impacto` | **Sin sesión**, con freno por IP. kg recolectados, kg y % reciclables, CO2 evitado estimado, kg recibidos en centros, detalle por tipo, fuentes, tipos sin factor y la nota de estimación. Solo agregados |
| `GET /api-gestion/estadisticas/impacto?desde=&hasta=` | Administrador. Lo mismo más `por_mes` (cada mes con su propio cálculo) |
| **Landing** | Sección «Impacto ambiental» con las cuatro cifras, el rótulo «estimación» y la caja de fuentes |
| **Reportes** | Sección «Impacto ambiental» con la tabla por mes, la nota, las fuentes y **Exportar (CSV)** |

## 5. Pruebas

Unitarias nuevas (16): el cálculo con números hechos a mano (1000 kg, 35 % reciclable, 465,2 kg de CO2), sin datos, tipos sin factor o no reciclables, el cálculo por mes y las fuentes, **los cuatro factores recalculados desde los números de WARM** y que la migración y el seed
lleven los mismos y con su enlace. HTTP nuevas (23, `correr-tests-impacto.php`): sin sesión y roles, el formato sin datos personales, que coincida con los kilos de Reportes, un camión, una ruta y dos contenedores propios `TIMP` (100 kg de vidrio suman 33,1 kg
de CO2 y 50 kg de mixto no suman nada), el reporte por mes y el **freno por IP** (429 al pasar el límite).
