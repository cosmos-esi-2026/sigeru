---
documento: Manual de uso — Cuadrilla de recolección
sistema: SiGeRU 2026
version: 1.0 (borrador, 17/10/2026)
estado: Escrito leyendo el sistema y probando los recorridos. Faltan las capturas de pantalla y la revisión por alguien de una cuadrilla real.
---

# Manual de la Cuadrilla

**Para qué sirve.** La cuadrilla ve **solo lo que le asignaron**: las incidencias que la administración le encargó y las recolecciones
de su cuadrilla. Desde acá registra el avance de cada trabajo, lo cierra, y anota cada contenedor que vacía con su peso.

## 1. Entrar

Desde la página de SiGeRU, **Ingresar** con el correo y la contraseña que te dio la administración. Si no tenés contraseña, la
administración te manda un enlace de acceso por correo (dura una hora y sirve una sola vez): ábrelo, escribí la **Nueva contraseña**
dos veces y **Guardar contraseña**. Tu cuenta tiene que estar vinculada a una cuadrilla (lo hace la administración); si no lo está, no vas a ver incidencias ni recolecciones.
Menú: **Inicio**, **Mapa**, **Contenedores**, **Mis incidencias** y **Mis tareas**. La sesión se cierra sola tras 2 horas sin uso.

## 2. Mis incidencias

**Mis incidencias** lista lo asignado a tu cuadrilla, con número, contenedor, dirección, tipo, prioridad, estado y fecha. Podés
buscar y filtrar por estado.

1. Tocá el número (por ejemplo `INC-0003`) para abrir el detalle: datos, descripción, **foto** (si el vecino adjuntó una) e **historial**.
2. **Registrar un avance:** escribí **Qué se hizo hasta ahora** (por ejemplo, «se pidió una tapa al depósito») y tocá **Registrar el avance**.
   El texto es obligatorio (hasta 300 caracteres) y queda firmado con tu nombre y la hora.
3. **Cerrar como resuelta:** cuando el problema está solucionado, escribí **Qué se hizo** (opcional) y tocá **Marcar como resuelta**.
   El contenedor vuelve a «disponible» si no quedan otros problemas abiertos en él.

Lo que **no** podés hacer: asignar a otra cuadrilla, cambiar la prioridad ni descartar (es de la administración). No ves quién
informó el problema, solo que lo informó un vecino.

## 3. Mis tareas (recolecciones)

**Mis tareas** muestra las recolecciones de tu cuadrilla, con estado: **Planificada**, **En curso**, **Finalizada**, **Cancelada**.
Tocá una para abrirla: ruta, camión, cuadrilla, centro de destino, inicio previsto y la tabla de **Vaciados**.

1. **Salir a recolectar:** tocá **Iniciar la recolección**. El camión pasa a «en ruta».
2. **Registrar un vaciado,** por cada contenedor que vaciás: **Contenedor** (el código, por ejemplo `CNT-004`), **Peso (kg)**
   (obligatorio, mayor que 0 y hasta 5000) y **Volumen (L)** (opcional, no puede superar la capacidad del contenedor). Tocá
   **Registrar el vaciado**. Cada vaciado queda con su hora.
3. **Terminar el recorrido:** tocá **Finalizar la recolección** (podés agregar **Observaciones**). Se registra la hora de fin, el camión vuelve
   a «operativo» y ya no se pueden agregar vaciados.

Cancelar una recolección lo hace la administración. Si abrís una recolección de otra cuadrilla, el sistema dice que no la encuentra.

## 4. Mapa y contenedores

**Mapa** muestra los contenedores y las incidencias abiertas que te tocan. **Contenedores** lista los contenedores; al abrir uno se ve
su **ficha** con el historial de sus estados (disponible, lleno, mantenimiento, retirado).

## Cuándo se llena cada contenedor

En **Contenedores**, la columna **Se llena en** estima cuánto falta para que cada contenedor llegue al 90 % (con su confianza), y el filtro **Se llena pronto** muestra los que se llenan en 24 h, 48 h o 7 días.
Es una estimación según cada cuánto se vació (no es inteligencia artificial): la caja **¿Cómo se calcula?** la explica.

## Calificaciones de tus incidencias

Cuando un vecino califica la atención de una incidencia que resolvió tu cuadrilla, la calificación (de 1 a 5 y su comentario, sin el nombre del vecino) aparece en el detalle de esa incidencia.
Solo ves las de tu cuadrilla. La administración mide la satisfacción por cuadrilla en **Reportes**.

## Cuando te quedás sin señal

Podés **instalar SiGeRU en el celular** («Agregar a la pantalla de inicio» o «Instalar aplicación» en el menú del navegador) si el sistema se abre con HTTPS.
Si perdés la señal **con la pantalla de la recolección o de la incidencia ya abierta**, igual podés **registrar un vaciado** o **registrar un avance**: el dato queda guardado en el teléfono, la pantalla lo avisa y arriba aparece **«N pendientes de enviar»**.
Cuando vuelve la señal se envían solos (también podés tocar **«Enviar ahora»**) y cada uno **llega una sola vez**, aunque se corte la conexión a mitad del envío.
Si el sistema rechaza alguno (por ejemplo, la recolección se canceló mientras tanto), queda marcado como **rechazado** con el motivo: **no se pierde**. Podés **Reintentar** o **Descartar**. Si la sesión venció, la barra te pide volver a entrar y lo guardado sigue ahí.
Solo esas dos acciones funcionan sin señal; para todo lo demás hace falta conexión.

## Avisos (la campanita)

La **campanita** de arriba a la derecha te muestra, con un número, lo que todavía no leíste: cuando el administrador **les asigna una incidencia** y cuando
**planifica una recolección** para tu cuadrilla. Tocá un aviso para ir directo a la incidencia o a la recolección; **Marcar todos como leídos** limpia el contador.
Se actualiza sola cada minuto. Cada persona de la cuadrilla recibe los avisos de su cuadrilla.

## 5. Problemas frecuentes

- **«Esa recolección no es de tu cuadrilla»:** abrila desde **Mis tareas**; solo ves las propias.
- **El peso no se acepta:** tiene que ser un número mayor que 0 y de hasta 5000 kg; usá punto o coma decimal según tu teclado.
- **No puedo iniciar:** la recolección tiene que estar «Planificada» y el camión disponible; si el camión está en mantenimiento, avisá a la administración.
