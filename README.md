# SiGeRU — Sistema de Gestión de Residuos Urbanos

Proyecto de Pasaje de Grado 2026 — Escuela Superior de Informática (ESI), CETP/UTU.
Equipo **COSMOS**, grupo 3°MG.

Sistema de gestión de residuos urbanos para una intendencia: registro centralizado de
contenedores, camiones e incidencias, con seguimiento de estado, asignación de
responsables y reportes.

## Aplicaciones

| Carpeta | Qué es | Estado |
| --- | --- | --- |
| `landing/` | Página pública y formulario de solicitud de registro | Funcionando |
| `frontend-usuarios/` | Interfaz de gestión: login, panel, usuarios, contenedores, camiones, centros de acopio, maquinaria y solicitudes | Funcionando |
| `api-usuarios/` | API de usuarios, login, sesión y solicitudes de registro | Funcionando, sobre MySQL |
| `api-gestion/` | API de contenedores, camiones, centros de acopio, maquinaria, tipos de residuo y zonas | Funcionando, sobre MySQL |
| `database/` | Modelo físico: DDL de creación, datos de prueba y volcado de la estructura | 15 tablas |
| `tests/` | Pruebas de las dos APIs por HTTP, con lanzador propio | 47 pruebas |

Los datos **se persisten en MySQL** desde el 09/09/2026: las dos APIs abren su conexión
por PDO y todas las consultas van preparadas. La sesión es de servidor (cookie `httponly`)
y el rol se relee de la base en cada petición.

## Documentación por materia

| Carpeta | Unidad curricular |
| --- | --- |
| `ingenieria-software/` | Ingeniería de Software |
| `proyecto-utu-lab/` | Proyecto UTU LAB (incluye el prototipo Low-Fi navegable) |
| `Emprendedurismo y Gestion/` | Emprendedurismo y Gestión |
| `administracion-sistemas-operativos/` | Administración de Sistemas Operativos |
| `docs/` | Notas técnicas de decisiones de implementación |

## Cómo levantarlo

El frontend espera las APIs bajo `/sigeru/`, así que se sirve con Apache (XAMPP) con el
repositorio publicado en esa ruta. Para probar una API sola alcanza con PHP:

    php -S localhost:8081 index.php     # desde api-usuarios/
    php -S localhost:8082 index.php     # desde api-gestion/

Antes de la primera vez hay que importar la base: `database/schema.sql` crea las 15
tablas y `database/datos_prueba.sql` las llena con datos de Montevideo.

Usuarios de prueba: `admin@sigeru.uy`, `cuadrilla@sigeru.uy` y `vecino@sigeru.uy`, todos
con contraseña `123456`.

Las pruebas se corren con `tests/correr-tests.cmd` (necesitan Apache y MySQL levantados);
se limpian solas al terminar.

El prototipo Low-Fi de UTU LAB se abre aparte, con doble clic en
`proyecto-utu-lab/prototipo-lofi/index.html`.

## Equipo

Franco Fornos (coordinador) · Franco Astorino (subcoordinador) · Axel Delgado ·
Francisco Goycochea
