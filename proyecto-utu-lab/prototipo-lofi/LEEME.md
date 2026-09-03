# Maqueta Low-Fi navegable de SiGeRU

Prototipo funcional de baja fidelidad correspondiente a la **2da entrega de Proyecto
UTU LAB**. Reproduce las ocho pantallas de la Figura 5 del documento de la 1ra entrega
y las conecta entre sí para poder recorrerlas.

## Cómo abrirlo

**Opción 1 — doble clic.** Abrir `index.html` con cualquier navegador. No necesita
servidor, ni internet, ni instalar nada.

**Opción 2 — servido (recomendada para la sesión de validación).** Desde esta carpeta:

    ..\..\..\herramientas\php\php.exe -S localhost:8090

y abrir `http://localhost:8090`. Es la forma recomendada porque el selector de rol usa
`sessionStorage`, que algunos navegadores restringen cuando la página se abre como
archivo local.

## Qué es y qué no es

Es una **maqueta de baja fidelidad**: está deliberadamente en escala de grises, sin
logo, sin tipografía de marca y sin colores. Un prototipo lo-fi se pone a prueba por su
recorrido, no por su estética; si tuviera identidad visual definitiva, la persona que lo
valida terminaría opinando sobre el color en lugar de opinar sobre la navegación.

**No es el sistema.** No valida datos, no calcula, no guarda nada y no se conecta a
ninguna API. Los datos que se ven son fijos y están escritos en el HTML. El sistema real
—que sí funciona contra las APIs— vive en `frontend-usuarios/` y `landing/`, en la raíz
del repositorio.

El único código de la maqueta es `lofi.js`, y hace dos cosas: rotula cada pantalla en la
barra superior y muestra en el menú solo las opciones del rol elegido.

## Las ocho pantallas

| # | Archivo | Pantalla | Acceso |
|---|---|---|---|
| 1 | `index.html` | Landing | pública |
| 2 | `login.html` | Inicio de sesión y solicitud de registro | pública |
| 3 | `panel.html` | Panel principal | con sesión, distinto por rol |
| 4 | `contenedores.html` | Listado y ficha de contenedor | con sesión |
| 5 | `incidencia-nueva.html` | Registrar incidencia | con sesión |
| 6 | `incidencia-detalle.html` | Detalle, asignación e historial | con sesión |
| 7 | `camiones.html` | Flota y ficha de camión | con sesión |
| 8 | `reportes.html` | Reportes por zona y período | con sesión |

## Recorrer con distintos roles

El selector **«Recorrer como»** de la barra superior cambia el perfil sin necesidad de
usuarios ni contraseñas. Cambia dos cosas: las opciones del menú lateral y el contenido
del panel principal.

| Rol | Ve en el menú | Ve en el panel |
|---|---|---|
| Administrador municipal | Panel, contenedores, incidencias, camiones, reportes | Indicadores, incidencias de toda la ciudad, contenedores críticos |
| Cuadrilla de recolección | Panel, mis incidencias | Solo las tareas asignadas a su cuadrilla |
| Operario de centro de acopio | Panel, centro de acopio | Indicadores y contenedores críticos |
| Vecino | Mis reclamos, informar un problema | Solo los reclamos que informó él mismo |

Esto sostiene el criterio de navegabilidad definido en la 1ra entrega: *lo que un
usuario no puede hacer, no se muestra*.

## Para qué se usa

Es el material de la **sesión de validación con usuarios simulados** descrita en el
documento de la 2da entrega. Los cuatro escenarios que se le piden al participante
(registrar una incidencia crítica, asignar una cuadrilla, hacer seguimiento y filtrar un
reporte) se pueden completar de punta a punta recorriendo estas pantallas.
