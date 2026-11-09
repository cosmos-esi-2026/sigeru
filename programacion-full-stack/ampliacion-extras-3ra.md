---
materia: Programación Full Stack
entrega: 3ra (extras, partes 12 a 22: resumen de todo lo agregado)
fecha: 2026-10-19
---

# Ampliación: las extras de la página (partes 12 a 22)

> **Actualización del 25/10/2026: este documento describe el sistema ANTES de simplificarlo.** El equipo decidió sacar lo que no iba a defender:
> predicción de llenado, ruta sugerida, reporte en PDF, aplicación instalable y cola sin señal, participación del vecino (calificar, puntos, insignias, ranking),
> impacto ambiental, guía «¿Dónde tiro esto?», calidad del servicio en Reportes y el correo de avisos. **Lo que vale hoy está en `ampliacion-simplificacion-3ra.md`**;
> las filas tachadas de la tabla de abajo ya no existen en el código y sus documentos pasaron a `retiradas/`. Las cifras de la sección 2 (22 tablas, 737 unitarias, 965 HTTP) son las del 19/10, no las de hoy.

Este documento **reúne en un solo lugar** lo que se agregó en las partes 12 a 22 del plan de extras (`plan-extras-pagina.md`). Cada parte tiene su propia ampliación con el detalle, las decisiones y lo que no se pudo verificar; acá está el mapa. Todo se hizo en una sesión autónoma
(Fornos pidió seguir sin responder el 19/10/2026): **las decisiones de producto están marcadas «SIN Fornos» en cada ampliación y el equipo puede confirmarlas o revertirlas.** Nada se subió a GitHub (el push lo decide Fornos).

## 1. Mapa de las partes

| Parte | Qué | Migración | Ampliación |
|---|---|---|---|
| 12 | Operario con trabajo real: recepciones, stock del centro (movimientos de solo agregado), estado de máquinas con historial | 009, 010 | `ampliacion-operario-3ra.md` |
| 13 | Notificaciones: campanita en todas las pantallas, avisos por rol y correo opcional al vecino | 011 | `ampliacion-notificaciones-3ra.md` |
| 14 | ~~Participación del vecino: calificar la atención, puntos de solo agregado, insignias, ranking por zona; calidad del servicio en Reportes~~ **RETIRADA 25/10** | 012, 013 | `retiradas/ampliacion-participacion-3ra.md` |
| 15 | «¿Cuándo pasa el camión por mi zona?» | 014 | `ampliacion-pasaje-3ra.md` |
| 16 | ~~Predicción de llenado de contenedores (estadística, no IA)~~ **RETIRADA 25/10** | — | `retiradas/ampliacion-prediccion-3ra.md` |
| 17 | ~~Ruta sugerida para el camión (vecino más cercano + 2-opt), con alta de recolección precargada~~ **RETIRADA 25/10** | — | `retiradas/ampliacion-ruta-3ra.md` |
| 18 | ~~Impacto ambiental con factores de la EPA (WARM v16) verificados~~ **RETIRADA 25/10** | 015 | `retiradas/ampliacion-impacto-3ra.md` |
| 19 | ~~Guía pública «¿Dónde tiro esto?» (Manual de la Intendencia de Montevideo)~~ **RETIRADA 25/10** | — | `retiradas/ampliacion-guia-3ra.md` |
| 20 | ~~Reporte de gestión en PDF con generador propio~~ **RETIRADA 25/10** | — | `retiradas/ampliacion-reporte-pdf-3ra.md` |
| 21 | ~~Aplicación instalable y Cuadrilla sin señal (cola y claves de operación)~~ **RETIRADA 25/10** | 016 | `retiradas/ampliacion-offline-3ra.md` |
| 22 | Pulido: modo oscuro, páginas de error, estados vacíos, imagen para compartir | — | `ampliacion-pulido-3ra.md` |

(La parte 11 fue de preparación, sin funciones nuevas, y la 23 es este cierre.)

## 2. Estado del sistema al cierre

- **Modelo:** 22 tablas del modelo (eran 16) y 29 físicas con las de soporte (`migracion`, `intento_login`, `enlace_clave`, `foto_incidencia`, `auditoria`, `limite_peticion`, `clave_idempotencia`). Migraciones 001 a 016, reflejadas en `schema.sql`, `datos_prueba.sql` y `dump_estructura.sql`.
  **Huella de la base: `d6c2eadfbc82fcdb`** (sha-256 de los `CHECKSUM TABLE` de las 22 tablas del modelo; verificada igual contra un seed limpio y contra la base viva después de las pruebas). La anterior, `60e98084441a8c3b`, ya no vale porque el modelo creció.
- **Pruebas (19/10/2026, todas en verde):** **737 unitarias** (sin base ni servidor) y **965 por HTTP** (contra Apache con `SIGERU_MAIL_MODE=archivo`, incluida la suite de seguridad), más **26 pruebas de la cola sin conexión y 10 de la lógica del service worker** que corren en el navegador (`tests/js/cola-offline.html` y `sw.html`).
  Las pruebas encontraron errores reales en el camino (la cola no marcaba las rechazadas; el botón de peligro quedaba ilegible en oscuro; un `$` de expresión regular aceptaba un salto de línea) y se corrigieron.
- **Permisos:** la matriz está actualizada hasta la parte 21 en `ciberseguridad/ampliacion-matriz-de-permisos-3ra.md` (secciones 18 a 22). Resumen de lo nuevo: predicción de llenado (Administrador y Cuadrilla), ruta sugerida y reporte PDF (solo Administrador), impacto público sin sesión con freno por IP, notificaciones y participación del vecino (cada usuario ve lo suyo), recepciones y stock (Operario de ese centro y Administrador).
- **Seguridad de lo nuevo:** todo con consultas preparadas; el service worker nunca toca las APIs; las claves de operación no se pueden usar entre personas ni entre operaciones; el PDF y las exportaciones solo para el Administrador; los datos ambientales públicos son solo agregados.

## 3. Decisiones de producto sin el equipo («SIN Fornos»)

1. **El Operario pertenece a un centro** (`usuario.id_centro`): reemplaza la decisión del 12/10. 2. **Correo de aviso al vecino activado por defecto** (se puede apagar), con campanita para todos. 3. **Puntos 10 al resolverse y 5 al calificar**, plazo de 30 días para calificar, ranking por zona y no por persona.
4. **Predicción y ruta: estadística simple y heurística, no IA**; supuestos de la ruta (línea recta, 20 km/h, 5 min por parada) inventados por el equipo y declarados en pantalla. 5. **Factores ambientales de la EPA** (otro país), rotulados «estimación». 6. **Guía solo de Montevideo**, con el aviso a la vista.
7. **PDF propio en vez de FPDF** (no se descargó nada sin permiso). 8. **Sin conexión solo dos acciones de la Cuadrilla.** 9. **Tema automático por defecto** y un único botón flotante. Todas con su justificación en cada ampliación.

## 4. Lo que no se pudo verificar y lo que sigue pendiente

- **Envío real de correo** (avisos al vecino y accesos): sigue sin probarse contra Gmail; el código y las pruebas cubren todo salvo la entrega.
- **Service worker registrado de verdad, instalación en un celular y modo avión real:** el navegador integrado de esta sesión no registra service workers; se probó la lógica con un entorno simulado y la cola de punta a punta con la red simulada. **Hay que probarlo en un Chrome y en un celular**, y en el servidor hace falta HTTPS.
- **Capturas de cada pantalla en modo oscuro** y revisión ortográfica completa: se verificó con pruebas automáticas (contraste de las dos paletas, desbordes a 375 y 768 px en 42 combinaciones de pantalla y rol) y con capturas de algunas; falta una recorrida humana.
- **`og:image` con la dirección completa:** hay que completarla cuando se sepa el dominio real.
- **Servidor `sigeru-srv`:** no tiene nada de esto (migraciones 009 a 016, GD, HTTPS, cabeceras). Ver `administracion-sistemas-operativos/novedades-3ra-para-el-servidor.md`.
- **Diagramas de clases y DER:** siguen siendo los del modelo de 16 clases (se documenta por ampliación). Si la docente los pide al día, hay que rehacerlos con los generadores.
- **Puntos de función de lo nuevo:** estimación (143 sin ajustar, 516 ajustados en total) **pendiente de revisión del equipo**, en `ingenieria-software/Adenda_Alcance_3ra_Entrega_SiGeRU.md`.

## 5. Cómo volver a verificarlo

```
herramientas/php/php.exe sigeru/tests/unitarias.php                                  # sin base ni servidor
SIGERU_MAIL_MODE=archivo herramientas/php/php.exe sigeru/tests/correr-tests.php      # contra Apache y MySQL (tarda unos 3 minutos)
bash generadores/extras-partes/huella.sh                                              # debe dar d6c2eadfbc82fcdb
```

Las pruebas del navegador (`tests/js/`) no las sirve Apache (la lista blanca lo impide a propósito): se copian un momento a `frontend-usuarios/pruebas-tmp/` ajustando la ruta del script, se abren y se borran. El detalle está en `continuar-mejora-pagina.md`.

## 6. Pruebas de mutación (19/10/2026)

Se rompió el código nuevo **a propósito** (20 mutantes: `generadores/extras-partes/mutar.py`, que corre las unitarias, no toca la base y restaura cada archivo) para ver si alguna prueba lo notaba.
**Resultado final: 19 de 20 mutantes los detectan las unitarias**; el 20.º (sacar la anotación de la descarga del PDF en la auditoría) lo cubre la prueba HTTP, pero **no se re-ejecutó ese mutante contra HTTP**. Al principio sobrevivieron 6, y **eso mostró cuatro pruebas que faltaban**, que se agregaron:

| Mutante que sobrevivió al principio | Qué faltaba | Prueba agregada |
|---|---|---|
| La ruta usaba `<` en vez de `<=` con el horizonte | El caso justo en el borde (llega al 90 % a las 24 h exactas) | `unitarias-ruta.php` |
| El PDF no escapaba el paréntesis de apertura | Un paréntesis sin pareja y mirar el contenido crudo (el lector de texto de la prueba perdonaba el error) | `unitarias-pdf.php` (dos pruebas) |
| El reporte PDF y la ruta sugerida «para todos los roles» | La tabla de permisos solo se probaba por HTTP | `unitarias-ruta.php` |
| El service worker dejaba de excluir las APIs | Solo se comprobaba que la función existiera, no que se usara | `unitarias-offline.php` |

Mutantes detectados desde el principio: tope de 25 y velocidad de la ruta, 2-opt al revés, haversine sin el 2, pie «Página X de N», codificación del PDF, tope del detalle, separadores de miles y umbral de horas, bandera `D` de la clave, colores ilegibles en oscuro, botón claro con texto blanco, hook de `Response` y el dibujo del estado vacío.
**Lo que esto no demuestra:** son 20 cambios elegidos por quien escribió el código; no es cobertura medida ni una herramienta automática de mutación.
