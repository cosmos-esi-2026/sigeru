---
materia: Programación Full Stack
entrega: 3ra (extras, parte 12: el Operario con trabajo real en el centro de acopio)
fecha: 2026-10-19
---

# Ampliación: Operario con trabajo real

Parte 12 del plan de extras (`plan-extras-pagina.md`). Hoy el Operario solo veía «Centros de acopio» y «Maquinaria»; el tribunal
va a preguntar para qué existe el rol. Ahora recibe lo que trae cada recolección, lleva el stock del centro y mantiene la maquinaria.

## 1. Decisiones (tomadas el 19/10 sin Fornos, que pidió seguir solo; se pueden corregir)

- **Cambia una decisión anterior.** El 12/10 se había decidido que «el Operario no está atado a un centro y no ve incidencias ni
  recolecciones». **Decisión nueva del 18-19/10:** el Operario queda **asociado a un centro** (lo asigna el Administrador en «Usuarios») y
  solo ve y mueve ese centro. Sigue sin ver incidencias, usuarios ni las recolecciones en sí: solo ve, en «Recepciones», las que llegan a su centro.
- **La recepción se registra por tipo de residuo** (no por total): el stock se lleva por tipo.
- **Un Operario sin centro asignado no puede operar:** recibe un 403 con el mensaje «Todavía no tenés un centro de acopio asignado».
- **Un centro ajeno no se distingue de uno que no existe:** el Operario recibe 404 (no 403) al pedir lo de otro centro.
- **Todo en `api-gestion`**, incluida la recepción, para hacerla en una sola transacción con el stock (la recolección vive en
  `api-recoleccion`, pero comparte la base).
- **El stock nunca se guarda:** es la suma de los movimientos (entradas menos salidas). Los movimientos son de **solo agregado**
  (triggers); un error se corrige con un **movimiento inverso** (una sola vez; una corrección no se corrige).
- **Aviso al pasar del 80 %** de la capacidad (con un decimal: 80,0 % no avisa, 80,1 % sí) y «lleno» desde el 100 %. Recibir por encima del
  100 % se permite (es un hecho: el camión ya descargó) pero queda como «lleno».
- **Datos de demostración:** el inventario inicial de los tres centros es inventado y está marcado «(demostración)» en cada movimiento;
  las dos recepciones del seed salen de los vaciados reales de las recolecciones 1 y 2. Ciudad Vieja queda en 81,3 % para que se vea el aviso.

## 2. Modelo de datos (migraciones 009 y 010)

| Cambio | Detalle |
|---|---|
| `usuario.id_centro` | Centro del Operario (nulo para los demás roles; se suelta solo si el usuario cambia de rol) |
| `recepcion` | Una por recolección (`UNIQUE`): centro, usuario, fecha y observación |
| `movimiento_stock` | Entrada o salida de un tipo de residuo en un centro: motivo (inventario inicial, recepción, venta, traslado, disposición, corrección), kg, recolección de origen, movimiento que corrige (`UNIQUE`), usuario y observación. Solo agregado (010) |
| `historial_maquinaria` | Cada cambio de estado de una máquina, con nota y usuario. Solo agregado (010) |

Las tres tablas nuevas son del modelo: **19 tablas** (eran 16). Las bajas en cascada (borrar el centro o la máquina) no disparan los
triggers: así las pruebas limpian lo suyo. **La huella de la base pasó a `17a811e39516dfd3`** (método de siempre, ahora con las 19
tablas del modelo; verificada contra una carga limpia del seed en otra base).

## 3. API (`api-gestion`)

| Endpoint | Quién | Qué hace |
|---|---|---|
| `GET /centros`, `GET /centros/{id}` | Admin, Operario (el suyo) | Ahora trae `stock_kg`, `ocupacion_pct` y `nivel` (y, en el detalle, los saldos por tipo) |
| `PUT /centros/{id}` | Admin, Operario (el suyo) | Sin cambios, pero acotado al centro propio |
| `GET /centros/{id}/stock` | Admin, Operario | Saldos por tipo, total, capacidad, ocupación y nivel |
| `GET /centros/{id}/movimientos` | Admin, Operario | Historial paginado; filtro `sentido=entrada` o `salida` |
| `POST /centros/{id}/movimientos` | Admin, Operario | Un retiro: tipo, kg, motivo (venta, traslado, disposición) y nota. Bloquea el centro mientras calcula: no se saca más de lo que hay (409) |
| `POST /centros/{id}/movimientos/{mid}/correccion` | Admin, Operario | Movimiento inverso, con el motivo (obligatorio) |
| `GET /recepciones?estado=` (`pendiente`, `recibida`, `todas`) | Admin, Operario | Recolecciones en curso o finalizadas que van al centro; el Administrador puede filtrar con `?centro=` |
| `GET /recepciones/{id_recoleccion}` | Admin, Operario | Detalle: lo declarado por los vaciados, por tipo, y lo recibido |
| `POST /recepciones/{id_recoleccion}` | Admin, Operario | Confirma la recepción: `lineas` (tipo y kg) y nota. Una sola vez (409). Devuelve un `aviso` si el centro pasó del 80 % |
| `POST /maquinaria/{id}/estado` | Admin, Operario (su centro) | Cambia el estado con una **nota obligatoria**; queda en el historial |
| `GET /maquinaria/{id}/historial` | Admin, Operario (su centro) | Asientos de la máquina, el más nuevo primero |
| `GET /estadisticas/centro` | Admin (`?centro=`), Operario | Recepciones pendientes, ocupación y máquinas en mantenimiento |

`GET /maquinaria` y `GET /maquinaria/{id}` también quedan acotados al centro del Operario. Las altas, ediciones y bajas de máquinas
(Administrador) ahora también dejan su asiento en el historial. En `api-usuarios`, `PUT /usuarios/{id}` y el alta aceptan `id_centro`
(solo para el rol Operario, y que el centro exista) y la lista trae el nombre del centro.

## 4. Pantallas

- **Inicio del Operario:** tres tarjetas: *Recepciones pendientes*, *Ocupación del centro* (con kg) y *Máquinas en mantenimiento*, y el aviso del 80 %.
- **Recepciones** (`recepciones.html`) y **Recepción** (`recepcion.html?id=`): lista con filtro y el formulario con lo declarado ya cargado.
- **Centro de acopio** (`centro.html?id=`): ocupación, stock por tipo, retiro, historial con paginación y corrección. «Centros de acopio» muestra la ocupación de cada uno.
- **Maquinaria:** botones *Estado* e *Historial* para el Operario; editar y dar de baja siguen siendo del Administrador.
- **Usuarios:** al elegir el rol Operario aparece «Centro de acopio».

## 5. Pruebas

Unitarias nuevas (85): saldos y ocupación, niveles, pesos válidos, retiros, correcciones, líneas de recepción, alcance por centro y matriz de
permisos. HTTP nuevas (115, `correr-tests-operario.php`): permisos 401/403, aislamiento entre centros, circuito completo (recibir, retirar,
corregir, aviso del 80 %, resumen), historial de maquinaria, asignación del centro y que los triggers bloqueen editar o borrar por fuera de la API.
Todo con registros propios `TOPE` que se borran al final (la cascada se lleva sus movimientos); el seed queda intacto.
