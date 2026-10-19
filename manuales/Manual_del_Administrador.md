---
documento: Manual de uso — Administrador
sistema: SiGeRU 2026
version: 1.0 (borrador, 17/10/2026)
estado: Escrito leyendo las pantallas y el codigo. Faltan capturas, la revision por una persona y completar el detalle de Camiones y Rutas.
---

# Manual del Administrador

**Para qué sirve.** El administrador tiene acceso a todo el sistema: usuarios, contenedores, incidencias, flota, rutas, recolecciones,
centros, maquinaria, reportes y auditoría.

## 1. Entrar y panel

**Ingresar** con correo y contraseña. **Inicio** (Panel de control) muestra el bloque **Requiere atención**, con las incidencias urgentes
calculadas al momento. Tras 2 horas sin actividad la sesión se cierra.

## 2. Usuarios

**Usuarios** lista las cuentas. Podés buscar y filtrar por **Rol** (Administrador, Operario, Cuadrilla, Vecino) y **Estado** (Activo,
Pendiente sin contraseña, Dado de baja); **Limpiar** borra los filtros y **Exportar CSV** baja el listado.

- **Alta:** botón de nuevo usuario, completar Nombre, Apellido, Email y rol, **Guardar usuario**. La cuenta queda *pendiente*.
- **Enviar acceso:** manda al correo un enlace para crear la contraseña (vence en una hora, un solo uso).
- **Centro del Operario:** al elegir el rol Operario aparece **Centro de acopio**. Un Operario solo ve y mueve el centro que le asignes; sin
  centro no puede operar (la lista muestra «Operario · nombre del centro»).
- **Editar:** lápiz de la fila, **Guardar cambios**. **Dar de baja:** pide confirmación.

## 3. Solicitudes de acceso

**Solicitudes de acceso** tiene las pestañas Pendientes, Todas, Aprobadas y Rechazadas. **Aprobar** pide el **Rol con el que entra**
(Vecino, Operario, Cuadrilla o Administrador) y **Aprobar y crear la cuenta**. **Rechazar** cierra la solicitud.

## 4. Contenedores

**Contenedores**: alta y edición con Código, Dirección, Zona, Tipo de residuo, Capacidad (litros) y Estado. Cada contenedor tiene una
ficha con su historial. Dar de baja pide confirmación.

## 5. Incidencias

**Incidencias** lista con filtros Estado (Abierta, En curso, Resuelta, Descartada), Zona, Desde y Hasta. Al abrir una:

- **Asignar una cuadrilla** (o **Reasignar**): el selector muestra turno y carga de cada cuadrilla.
- **Nueva prioridad:** cambia la prioridad.
- **Marcar como resuelta:** cierra la incidencia; si el contenedor estaba lleno o en mantenimiento por esto, vuelve a disponible.
- **Descartar:** cierra con motivo. Una incidencia cerrada ya no admite cambios.
- El historial es de solo agregado: nada se borra ni se edita.

## 6. Flota y recolección

- **Camiones:** Matrícula, Marca, Modelo, Capacidad (kg), Estado (Operativo, En ruta, En mantenimiento, Fuera de servicio).
  El estado pasa solo a *En ruta* al iniciar una recolección.
- **Rutas:** Nombre, Día de la semana, Hora de inicio, Zona y Estado (Activa o Inactiva); **Guardar ruta**.
- **Recolecciones:** filtros Estado (Planificada, En curso, Finalizada, Cancelada), Desde, Hasta. **Planificar** pide Ruta, Fecha y
  hora de inicio, Camión, Cuadrilla, Centro de acopio de destino (opcional) y Observaciones.

## 7. Centros de acopio y Maquinaria

**Centros de acopio:** Nombre, Dirección, Capacidad (kg), Estado (Activo, Inactivo); **Guardar centro**. **Maquinaria:** Código, Tipo
(compactadora, pala cargadora, cinta de clasificación, retroexcavadora, montacargas, otra), Marca, Modelo, Centro de acopio y Estado
(Operativa, En mantenimiento, Fuera de servicio); **Guardar máquina**. Cada cambio de estado queda en el **Historial** de la máquina.

**Stock y recepciones (parte de las extras):** cada centro tiene su ficha (tocá el nombre): ocupación, stock por tipo, retiros y movimientos.
**Recepciones** lista lo que llega a los centros; el administrador puede recibir en cualquiera (filtrando por centro). Un error de stock se
corrige con un movimiento inverso: no se borra nada. Cuando un centro pasa del 80 % de su capacidad, la lista de centros lo marca.

## 8. Reportes

**Reportes:** período (Últimos 30 días, Este mes, Todo el historial o Desde/Hasta) y Zona; **Generar informe**, **Imprimir**,
**Exportar (CSV)** y **Exportar detalle (CSV)**.

## Reporte en PDF

En **Reportes**, elegí el período (y la zona si querés) y tocá **«Descargar PDF»**. El archivo (`sigeru-reporte-AAAA-MM-DD.pdf`) trae portada con el período, resumen, urgencias, incidencias por zona y tipo, satisfacción, impacto ambiental (marcado como estimación, con sus fuentes) y el detalle de las incidencias.
Las cifras son las mismas de la pantalla. Para que no sea interminable muestra hasta 100 incidencias y 25 contenedores llenos, y lo avisa; para ver todas usá «Exportar (CSV)». **Cada descarga queda anotada en Auditoría.**

## Impacto ambiental

En **Reportes**, la sección **Impacto ambiental** muestra por mes los kilos recolectados, cuántos son de tipos reciclables, el porcentaje y el **CO2 evitado estimado**, con **Exportar (CSV)**. **Es una estimación**: kilos de cada tipo reciclable por un factor de la
EPA de Estados Unidos (modelo WARM v16), con la fuente a la vista; orgánico y mixto no tienen factor verificado y se informan solo en kilos. La landing pública muestra las cifras agregadas del total, también rotuladas como estimación.

## 9. Auditoría

**Auditoría** registra cada operación de escritura, hecha o negada por permisos. Filtros: Resultado (Hecho, Negado por permisos),
Operación (POST, PUT, DELETE), Desde, Hasta.

## Cuándo se llena cada contenedor

**Contenedores** tiene la columna **Se llena en** (por ejemplo «en 1 día y 13 h (confianza media)») y el filtro **Se llena pronto** (24 h, 48 h o 7 días). La **ficha** de cada contenedor y tu **Inicio** (panel
«Se llenan en las próximas 48 horas») muestran lo mismo. **No es inteligencia artificial**: se calcula con el ritmo de vaciado del contenedor (la caja **¿Cómo se calcula?** lo explica). Con menos de 3 vaciados dice
«Sin datos suficientes»; es una guía para planificar rutas, no una certeza.

## Ruta sugerida

Menú **«Ruta sugerida»** (o el botón «Sugerir ruta» de Recolecciones). Elegí zona, horizonte (24 o 48 horas), centro de destino y camión, y tocá **«Sugerir ruta»**. Entran los contenedores ya llenos y los que, según la predicción de llenado, llegan al 90 % dentro del horizonte (hasta 25, los más urgentes).
Ves el recorrido en el mapa (círculos numerados; rojo si ya está lleno), la tabla con el mismo orden, la distancia y el tiempo estimados. **Son estimaciones:** distancia en línea recta (no por calles), 20 km/h y 5 minutos por parada, y el orden es una propuesta (vecino más cercano mejorado), no el recorrido óptimo.
**«Crear recolección con esta ruta» no crea nada:** abre el alta de Recolecciones con el camión, el centro y el orden de los contenedores (en las observaciones) ya cargados; elegís la ruta, la fecha y la cuadrilla y confirmás con «Planificar». Hace falta historial de vaciados (3 o más por contenedor) para que aparezcan los que están por llenarse.

## Calidad del servicio (reportes)

En **Reportes**, la sección **Calidad del servicio** muestra por zona y por cuadrilla: incidencias, resueltas, cuántas calificaciones dejaron los vecinos, la **satisfacción promedio** (de 1 a 5)
y el **tiempo medio de resolución**, del período y la zona que elijas. **Exportar (CSV)** baja las dos tablas. Una zona o cuadrilla sin calificaciones muestra «Sin calificaciones» (no un cero).
Los vecinos califican solo un reclamo resuelto, una vez y dentro de los 30 días; el detalle de la incidencia muestra la calificación.

## Avisos (la campanita)

La **campanita** de arriba a la derecha avisa, con un número, lo que pide tu atención: cuando **entra una incidencia de prioridad alta** y cuando un **centro de acopio
pasa del 80 % de su capacidad** (el aviso salta al cruzar el umbral, no en cada recepción). Tocá un aviso para ir a la incidencia o al centro. Los vecinos y las
cuadrillas reciben sus propios avisos cuando asignás, resolvés o descartás una incidencia y cuando planificás una recolección.

## 10. Preguntas frecuentes

- **Un usuario no puede entrar:** revisá que no esté dado de baja o pendiente; usá **Enviar acceso**.
- **No encuentro una incidencia cerrada para editarla:** no se puede, por diseño.
- **Cambié algo y no sé quién fue:** Auditoría.
