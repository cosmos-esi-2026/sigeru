---
materia: Programación Full Stack
entrega: 3ra (extras, parte 15: «¿Cuándo pasa el camión por mi zona?»)
fecha: 2026-10-19
---

# Ampliación: cuándo pasa el camión por mi zona

Parte 15 del plan de extras. Objetivo: que el vecino sepa cuándo pasa la recolección por su zona, con datos reales de las recolecciones planificadas.
Sesión autónoma (Fornos pidió seguir sin responder): las decisiones marcadas «SIN Fornos» se pueden corregir.

## 1. Decisiones (SIN Fornos)

- **No hay consulta pública en la landing** (recomendación del plan): el pasaje se ve solo con sesión, en el Inicio del Vecino.
- **La zona se guarda en el usuario** (`usuario.id_zona`, migración 014): la elige el vecino la primera vez y la puede cambiar o quitar.
- **El pasaje sale de las recolecciones planificadas** de las rutas de esa zona. Solo cuentan las de ahora en adelante: una planificada vieja que nadie cerró no es un próximo pasaje.
- **Estados honestos:** *sin zona elegida* («Elegí tu zona…»), *sin pasajes planificados* («Todavía no hay recolecciones planificadas para tu zona»), *el camión está pasando hoy*
  (hay una recolección en curso) y *programado* (el día y la hora). Si se **canceló** una recolección futura anterior al próximo pasaje, el vecino ve la siguiente **y el aviso** de la cancelada.
- **Privacidad:** el endpoint devuelve solo `estado`, `zona`, `id_zona`, `proximo` y `cancelada`: **nunca camión, cuadrilla ni ruta interna**.
- **Datos de demostración:** el seed lleva cinco recolecciones planificadas con **fechas fijas de la semana de la defensa** (16 al 23/11/2026): Ciudad Vieja y Centro el lunes 16, Pocitos el miércoles 18, Cordón
  el viernes 20 y Centro otra vez el lunes 23 (para mostrar «cancelo una, ve la siguiente»). Pasada esa semana el vecino verá «todavía no hay recolecciones planificadas». Aires Puros no tiene ninguna a propósito.

## 2. API

| Endpoint | Quién | Qué hace |
|---|---|---|
| `PUT /api-usuarios/mi-zona` | Vecino | `{"id_zona": n}` elige o cambia la zona; `null` la quita. 400 si no es un número válido, 404 si la zona no existe |
| `GET /api-recoleccion/proximo-pasaje` | Vecino | La zona elegida, el estado y el día y la hora del próximo pasaje |

El cálculo es una clase pura (`ProximoPasaje::calcular`) con pruebas unitarias; la consulta mira solo estado y fecha de las recolecciones de la zona.

## 3. Pantalla

**Inicio del Vecino:** panel «¿Cuándo pasa el camión por mi zona?» con el texto («Próximo pasaje en Centro: lunes 16/11 a las 06:30.») y el selector de zona
(«Guardar mi zona» la primera vez, «Cambiar mi zona» después).

## 4. Pruebas

Unitarias nuevas (11): varias planificadas desordenadas, ninguna, zona sin rutas, una pasada, el límite exacto de ahora, una cancelada que se avisa y una que no, todas canceladas, en curso y que el resultado no traiga
nada interno. HTTP nuevas (25, `correr-tests-pasaje.php`): 401 y 403, validación de la zona, los estados, el cálculo con una ruta propia `TPAS` en Aires Puros (fechas relativas a hoy), que el otro vecino vea lo mismo en su
misma zona, que el administrador cancele y se vea la siguiente y que ningún vecino del seed quede con zona.
