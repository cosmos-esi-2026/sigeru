> **RETIRADA el 25/10/2026.** Esta funcionalidad se sacó del sistema antes del congelamiento para dejar la aplicación más simple y defendible (ver `../ampliacion-simplificacion-3ra.md`). El documento se conserva solo como registro de lo que se hizo; **nada de lo que describe existe ya en el código ni se va a defender.**

---
materia: Programación Full Stack
entrega: 3ra (extras, parte 16: predicción de llenado de contenedores)
fecha: 2026-10-19
---

# Ampliación: predicción de llenado

Parte 16 del plan de extras. Objetivo: estimar cuándo se llena cada contenedor con un método simple que el equipo pueda explicar en la defensa.
**No es inteligencia artificial ni aprendizaje automático: es una estimación estadística y así se presenta** (en la API y en cada pantalla).
Sesión autónoma (Fornos pidió seguir sin responder): las decisiones marcadas «SIN Fornos» se pueden corregir.

## 1. Cuánto historial real hay (medido al empezar)

En el seed hay **6 vaciados, uno por contenedor** (`vaciado`), y el historial de estados (`estado_contenedor`) no dice cuándo se vació nada. Con menos de 3 vaciados por contenedor el método no puede estimar,
así que **sin historial de demostración la predicción diría «sin datos suficientes» para todos**. Por eso se generó historial de demostración (ver 4).

## 2. El método (la cuenta que se explica en la defensa)

1. Se miden los **intervalos entre vaciados** consecutivos del contenedor y se calcula el **intervalo medio** (μ).
2. Se mide cuánto pasó **desde el último vaciado** (e).
3. Suponiendo que se llena parejo, el nivel estimado hoy es e / μ (como porcentaje, hasta 100 %), y llega al **90 %** cuando e = 0,9 × μ.
   **Horas hasta el 90 % = 0,9 × μ − e** (nunca menos de 0).
4. **Menos de 3 vaciados → «sin datos suficientes»** (no se inventa un número). Un contenedor que ya figura **lleno** devuelve «lleno, 0 horas»; los retirados y en mantenimiento no se estiman.
5. **Confianza:** *alta* con 8 o más vaciados y ritmo parejo (el desvío de los intervalos hasta el 25 % de su media); *media* con 5 o más y hasta 50 %; *baja* en los demás casos.

Ejemplo hecho a mano (es una de las pruebas): 6 vaciados cada 48 h y el último hace 6 h → μ = 48 h, e = 6 h → 0,9 × 48 − 6 = **37,2 h** (nivel de hoy 13 %), confianza media.

## 3. API y pantallas

| Qué | Detalle |
|---|---|
| `GET /api-gestion/contenedores/prediccion?dentro_de=N` | Administrador y Cuadrilla. Una fila por contenedor (sin los retirados), ordenada por «se llena antes»; con `dentro_de` (1 a 720 horas) solo los que se llenan en ese plazo. Trae el estado, las horas, el nivel estimado, el intervalo medio, los vaciados, la confianza y un texto («en 1 día y 13 h») |
| **Contenedores** | Columna «Se llena en», filtro «Se llena pronto» (24 h, 48 h o 7 días) y una caja plegable «¿Cómo se calcula?» con la explicación en lenguaje simple |
| **Ficha del contenedor** | Panel «Cuándo se llena» con el nivel estimado y el intervalo medio, y la misma caja «¿Cómo se calcula?» |
| **Inicio del Administrador** | Panel «Se llenan en las próximas 48 horas» con los más cercanos y un enlace a la lista filtrada |

La lógica es la clase pura `Prediccion` (con pruebas unitarias); el controlador solo junta las fechas de los vaciados.

## 4. Historial de demostración

- **`sigeru/database/demo_historial.sql`**, generado por `generadores/demo_historial.py` (semilla fija): unos 60 días de vaciados de los contenedores con ritmos distintos (cada 2 a 7 días, uno irregular), **marcado como datos de demostración** en el
  archivo, en las recolecciones que crea (`observaciones` empieza con `DEMO-HISTORIAL`) y en esta documentación.
- **Las fechas son relativas a `NOW()`**: se carga el día de la demo y el último vaciado de cada contenedor queda «hace pocas horas». Se carga **encima del seed** con `herramientas/xampp/cargar-historial-demo.cmd`
  y se puede repetir (borra sus propias filas antes de insertar; los vaciados se van en cascada y los triggers de solo agregado no estorban). **No forma parte del seed** (para que la huella de la base y las pruebas sigan siendo estables).
- Con él cargado, los contenedores de la demo quedan (valores del día de la carga): CNT-004 en unas 10 h, CNT-001 en 16 h, CNT-008 (ritmo irregular, confianza media) en 29 h, CNT-003 en 31 h, CNT-006 en unos 3,4 días, CNT-009 en
  4,5 días, y CNT-002 y CNT-007 ya llenos (los dos figuran como llenos en el seed).
  Para volver al seed limpio: `restaurar-base-demo.cmd`.

## 5. Pruebas

Unitarias nuevas (35): ritmo constante con la cuenta a mano (37,2 h), sin datos (0, 1 y 2 vaciados) y justo 3, contenedor ya lleno, retirado y en mantenimiento, nivel ya superado, datos irregulares, fechas desordenadas, repetidas, futuras y vacías, los
umbrales de confianza y los textos. HTTP nuevas (21, `correr-tests-prediccion.php`): 401 y 403, validación de `dentro_de`, el formato, un contenedor propio `TPRED` con seis vaciados de fechas controladas (los resultados coinciden con la cuenta a mano), el filtro por
plazo, que pase a lleno y que desaparezca al retirarse. Todo se borra al final.
