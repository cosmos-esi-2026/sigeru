---
materia: Programación Full Stack
entrega: 3ra (segunda simplificación, día del congelamiento)
fecha: 2026-10-30
---

# Ampliación: segunda simplificación, el día del congelamiento (30/10/2026)

## 1. Por qué

El profesor de Programación dijo que lo único que le importa es que **todo funcione**: no le importan las extras ni el diseño del frontend. El 30/10/2026 (congelamiento a las 18:00)
se analizó el sistema entero (visual, estructural y funcional; ver `analisis-programacion-2026-10-30.md`) y se hizo un plan por bloques (`plan-simplificacion-programacion.md`).
Esta ampliación documenta **lo que se hizo de ese plan**. Sigue el criterio de la ampliación del 25/10: sacar lo que no se va a defender y no tocar rutas, asignación a cuadrillas,
incidencias con prioridad, usuarios ni la seguridad.

Quedó **como estaba, a propósito**: el modo oscuro (se pidió el 25/10), el historial de maquinaria y todo el flujo del Operario (stock y recepciones).

## 2. Qué se hizo

### Para que no se trabe

| Cambio | Dónde | Por qué |
|---|---|---|
| El trazado de una ruta es **en línea recta** por defecto; `SIGERU_TRAZADO_MODO=calles` en Apache vuelve a pedirlo a Valhalla/OSRM | `api-recoleccion/models/Trazado.php`, `RutaController.php`, `ruta-armar.js/.html` | Guardar una ruta podía quedarse hasta 30 s esperando servicios públicos de internet. Las 40 rutas del seed conservan su trazado por calles guardado (`trazado_firma`); cualquier edición de la lista de paradas lo borra y pasa a recta |
| `apiPost`, `apiPut` y `apiDelete` **nunca lanzan** | `frontend-usuarios/js/api.js` | Nueve llamadas (botones de baja, solicitudes) quedaban mudas si se cortaba la red o Apache contestaba algo que no era JSON; ahora devuelven `{ ok:false, resultado:{ mensaje } }` y la pantalla lo muestra |
| «Asignación» arranca mostrando las rutas de **todos los días** | `js/asignacion.js` | Por defecto filtraba por mañana (sábado) y la pantalla aparecía vacía |
| Frase vieja de la auditoría («Envió un acceso por correo») | `js/auditoria.js` | El correo se sacó el 30/10 |

### Funciones que se sacaron

| Función | Qué desapareció |
|---|---|
| **Foto de la incidencia** | `ProcesadorFoto.php`, `FotoIncidencia.php`, `subirFoto` y `verFoto`, las rutas `/incidencias/{id}/foto`, el permiso `incidencias/foto`, el campo y la vista previa en «Informar un problema», la foto en el detalle de la incidencia, la tabla `foto_incidencia` y sus pruebas. La **migración 021** (`DROP TABLE IF EXISTS foto_incidencia`) lleva las bases ya creadas al estado nuevo; la 006 queda como historia |
| **«¿Cuándo pasa el camión por mi zona?»** | `PasajeController.php`, `ProximoPasaje.php`, la ruta `GET /proximo-pasaje`, `PUT /mi-zona`, la sección del Inicio del vecino y sus pruebas. La columna `usuario.id_zona` (migración 014) **se dejó**: no molesta y tocar la base más era riesgo sin ganancia |
| **«Ordenar por cercanía»** en Armar ruta | El botón y el cálculo (vecino más cercano y 2-opt) |
| **Exportación a CSV** | `descargarCsv` y compañía en `lista.js`, y los botones de Contenedores, Usuarios y Reportes |

## 3. Qué cambia en la base

La base pasa de 26 a **25 tablas** (21 del modelo y 4 de soporte: `migracion`, `intento_login`, `auditoria`, `limite_peticion`). `schema.sql` y `dump_estructura.sql` ya no
tienen `foto_incidencia`; `schema.sql` registra la migración 021; los dos seeds (`datos_prueba.sql`, `datos_defensa.sql`) ya no la vacían. Se corrigió además una línea del
encabezado de `dump_estructura.sql` que no tenía el `--` y rompía la importación. **La huella de la base cambia**: hay que recalcularla (`huella.sh`) después de restaurar.

## 4. Cómo se verificó

- Chequeo de sintaxis de todo lo tocado (`php -l`, `node --check`).
- Las pruebas unitarias corridas en una copia del repositorio: **605 pasan**; las 13 que no son porque esa copia no tenía todos los archivos (css, Leaflet, `.htaccess`, migraciones viejas), no por los cambios.
- Las pruebas contra la base y por HTTP **tienen que correrse en la máquina del equipo** (`restaurar-base-demo.cmd` y `correr-tests.cmd`, que tienen que terminar con `Fallaron: 0`), y después
  `restaurar-base-defensa.cmd` para dejar la base lista para la defensa. Ver `Checklist 3ra entrega.md`.

