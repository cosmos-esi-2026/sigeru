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
| `frontend-usuarios/` | Interfaz de gestión: login, panel, usuarios, contenedores, camiones | Funcionando |
| `api-usuarios/` | API de usuarios, login y solicitudes de registro | Funcionando, sin persistencia |
| `api-gestion/` | API de contenedores y camiones | Funcionando, sin persistencia |
| `database/` | Modelo físico: DDL de creación y volcado de la estructura | 7 tablas |

Las dos APIs guardan los datos **en memoria**: se pierden al reiniciar. La persistencia
en base de datos es el entregable central de la 2da entrega.

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

Usuarios de prueba: `admin@sigeru.uy`, `cuadrilla@sigeru.uy` y `vecino@sigeru.uy`.

El prototipo Low-Fi de UTU LAB se abre aparte, con doble clic en
`proyecto-utu-lab/prototipo-lofi/index.html`.

## Equipo

Franco Fornos (coordinador) · Franco Astorino (subcoordinador) · Axel Delgado ·
Francisco Goycochea
