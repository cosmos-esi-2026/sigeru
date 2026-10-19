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
(Operativa, En mantenimiento, Fuera de servicio); **Guardar máquina**.

## 8. Reportes

**Reportes:** período (Últimos 30 días, Este mes, Todo el historial o Desde/Hasta) y Zona; **Generar informe**, **Imprimir**,
**Exportar (CSV)** y **Exportar detalle (CSV)**.

## 9. Auditoría

**Auditoría** registra cada operación de escritura, hecha o negada por permisos. Filtros: Resultado (Hecho, Negado por permisos),
Operación (POST, PUT, DELETE), Desde, Hasta.

## 10. Preguntas frecuentes

- **Un usuario no puede entrar:** revisá que no esté dado de baja o pendiente; usá **Enviar acceso**.
- **No encuentro una incidencia cerrada para editarla:** no se puede, por diseño.
- **Cambié algo y no sé quién fue:** Auditoría.
