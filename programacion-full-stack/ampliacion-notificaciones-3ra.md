---
materia: Programación Full Stack
entrega: 3ra (extras, parte 13: avisos dentro del sistema y por correo)
fecha: 2026-10-19
---

> **Actualización del 25/10/2026:** se sacó el **correo de aviso al vecino** y su preferencia (`GET/PUT /preferencias`, la casilla del Inicio, la columna `usuario.avisos_por_correo` y `Correo::enviarAviso`). **Queda la campanita** con todos los avisos de este documento. Lo que abajo dice del correo ya no existe (ver `ampliacion-simplificacion-3ra.md`).

# Ampliación: notificaciones

Parte 13 del plan de extras. Objetivo: que cada persona se entere de lo que le importa **sin tener que entrar a mirar**. Hecha en sesión
autónoma (Fornos pidió seguir sin responder): las decisiones marcadas «SIN Fornos» se pueden corregir.

## 1. Decisiones (SIN Fornos)

- **Quién recibe qué:**

| Evento | Campanita | Correo |
|---|---|---|
| Una incidencia pasa a *en curso* (se asigna una cuadrilla estando abierta), *resuelta* o *descartada* | El vecino que la informó | Sí (si lo tiene activado) |
| Se asigna una incidencia a una cuadrilla (también al reasignar) | Las cuentas de esa cuadrilla | No |
| Se planifica una recolección | Las cuentas de la cuadrilla asignada | No |
| Entra una incidencia con prioridad **alta** | Todos los administradores | No |
| Un centro **pasa** del 80 % (o llega al 100 %) | Los administradores y el Operario de ese centro | No |

- **«Crítica» = prioridad alta al entrar.** Subir la prioridad después no avisa (no hay un evento «entró»).
- **El aviso del centro salta al cruzar el umbral**, no en cada recepción que sigue por encima (se compara el nivel antes y después).
- **El mail es solo para el vecino** (lo pide la letra del plan) y solo si tiene activada la casilla «Recibir avisos por correo» (por defecto sí).
  El personal se entera por la campanita.
- **Un fallo de correo o de aviso nunca rompe la operación:** todo error se anota en el log y se sigue. Sin correo configurado queda solo la campanita.
- **Los avisos no llevan datos de otra persona:** solo el número del reclamo, la dirección del contenedor (a la cuadrilla que ya la ve), la ruta
  y la fecha, o el nombre del centro. El aviso al vecino no nombra a la cuadrilla ni al personal.
- **Los enlaces son solo a pantallas del sistema** (`^[a-z0-9-]+\.html(\?[a-z0-9=&_-]*)?$`): el correo y la campanita descartan cualquier otro.

## 2. Modelo de datos (migración 011)

- `notificacion` (usuario, tipo, título ≤120, mensaje ≤300, enlace, leída, fecha). Borrar un usuario borra sus avisos. **20 tablas del modelo.**
- `usuario.avisos_por_correo` (por defecto 1).

## 3. API

Una sola función de aviso, `Notificador` (clase con copia idéntica en `api-gestion` y `api-recoleccion`, que verifica una prueba), con los textos de
cada evento, la búsqueda de destinatarios y el envío. El correo usa el motor de `api-usuarios/core/Correo.php`, al que se agregó `enviarAviso`
(texto + HTML, enlace a la pantalla, pie con cómo desactivarlo; en modo archivo deja `…-aviso.txt`, sin pisar los de recuperación y bienvenida).

| Endpoint (`api-usuarios`, cualquier persona con sesión) | Qué hace |
|---|---|
| `GET /notificaciones?limite=&solo_no_leidas=1` | Los avisos propios, del más nuevo al más viejo (límite 1 a 50, 20 por omisión) y `no_leidas` |
| `POST /notificaciones/{id}/leida` | Marca uno propio como leído (404 si es de otra persona o no existe) |
| `POST /notificaciones/leer-todas` | Marca todos los propios |
| `GET /preferencias`, `PUT /preferencias` | Lee y guarda `avisos_por_correo` (verdadero o falso) |

Los avisos se escriben desde `IncidenciaController` (crear, asignar, resolver, descartar), `RecoleccionController` (planificar) y `RecepcionController` (cruce del 80 %).

## 4. Pantallas

- **Campanita** en el menú superior de todas las pantallas (`shell.js`): contador de no leídas, panel accesible por teclado (Esc cierra y devuelve el foco; tabular
  fuera lo cierra), «Marcar como leído» por aviso y «Marcar todos como leídos»; sondeo cada 60 s que se pausa con la pestaña oculta y se actualiza al volver.
  Un aviso con enlace lo lleva a la pantalla y lo marca como leído. A 375 px el panel ocupa el ancho de la pantalla.
- **Inicio del Vecino:** casilla «Recibir avisos por correo cuando cambie el estado de mis reclamos».
- La landing ya no dice «No enviamos avisos por correo» (hallazgo H11-5).

## 5. Pruebas

Unitarias nuevas (22): textos de cada evento, límites, que el aviso al vecino no nombre al personal, el correo en modo archivo (enlace, pie, sin token, destino con salto
de línea rechazado, enlaces raros descartados) y la copia idéntica de `Notificador`. HTTP nuevas (53, `correr-tests-notificaciones.php`): 401 sin sesión, preferencias,
que un vecino no pueda leer ni marcar los avisos de otro, que cada evento dispare el aviso correcto a la persona correcta (y a nadie más), correo en modo archivo,
correo apagado, correo inválido sin romper la operación, textos largos cortados y el cruce del 80 % una sola vez. Los avisos de la corrida se borran por fecha.
