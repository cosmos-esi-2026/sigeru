# Política de respaldo y alta disponibilidad de SiGeRU

**Materia**: Administración de Sistemas Operativos — 2da entrega, ítem «Definición de
medios de respaldo a largo plazo y alta disponibilidad».
**Equipo COSMOS** — 12/09/2026.

Este documento define **qué** se respalda, **cada cuánto**, **dónde** se guarda, **cuánto
tiempo** y **cómo se comprueba** que sirve. Los scripts que ejecutan la parte diaria están
en `scripts/respaldo/` y su prueba en `pruebas/`. La segunda parte explica qué es la alta
disponibilidad, por qué hoy no la tenemos y qué plan realista proponemos para una
intendencia chica.

---

## 1. Qué se protege

Un respaldo es la copia de lo que no se puede volver a fabricar. En SiGeRU son tres cosas,
en este orden de importancia:

| Qué | Dónde vive en el servidor | Por qué importa | Cuánto pesa hoy |
|---|---|---|---|
| **La base de datos `sigeru`** (MySQL, 15 tablas) | `/var/lib/mysql/sigeru/` | Es el trabajo de la intendencia: contenedores, incidencias, recolecciones, usuarios. Si se pierde, no hay forma de reconstruirla. | Menos de 1 MB con los datos de prueba; crece con el uso. |
| **El código del sistema** (las dos APIs y los frontends) | `/var/www/sigeru/` | Está en GitHub, así que en teoría se recupera; pero el servidor puede tener ajustes locales y GitHub puede no estar accesible el día que haga falta. | ~11 MB. |
| **Los archivos de configuración del servidor** | `/etc/netplan/`, `/etc/ssh/sshd_config`, `/etc/apache2/sites-available/`, `/etc/mysql/mysql.conf.d/`, `/etc/cron.d/sigeru-backup`, `/opt/sigeru/respaldo/backup.conf` | Son horas de trabajo de instalación. Con ellos a mano, reinstalar el servidor es seguir el manual; sin ellos es empezar de cero. | Unos KB. |

**No se respalda** el sistema operativo entero (se reinstala desde la ISO siguiendo
`plan-de-instalacion.md`), ni los logs de Apache y MySQL (no son datos del sistema), ni los
respaldos mismos (eso lo resuelve la regla 3-2-1, no un respaldo del respaldo).

## 2. Cuánto se puede perder y cuánto se puede tardar: RPO y RTO

Son las dos preguntas que hay que responder **antes** de elegir cada cuánto respaldar,
porque el calendario sale de ahí y no al revés.

- **RPO** (*Recovery Point Objective*, «punto de recuperación»): **cuánto trabajo, medido en
  tiempo, se acepta perder**. Si el último respaldo es de las 03:00 y el disco se rompe a
  las 15:00, se pierden 12 horas de registros. Nuestro RPO es **24 horas**: como mucho se
  pierde el trabajo de un día. Lo elegimos porque SiGeRU registra recolecciones e
  incidencias que quedan también en papel y en la memoria de la gente que las cargó ese
  día; un día se puede recargar a mano, una semana no. Un RPO menor (respaldar cada hora)
  es posible con el mismo script, pero llena el disco y hoy nadie lo pidió.
- **RTO** (*Recovery Time Objective*, «tiempo de recuperación»): **cuánto tiempo se acepta
  tener el sistema caído** hasta que vuelve a funcionar. Nuestro RTO es **1 hora** si el
  servidor está sano y solo se perdieron datos (correr `restaurar.sh` lleva minutos, el
  resto es avisar y verificar), y **4 horas** si hay que reinstalar el servidor desde la
  ISO (instalación siguiendo el manual + restauración). La intendencia puede seguir
  recolectando sin el sistema medio día; lo que se atrasa es la carga, no el servicio.

Los dos números se cumplen con **un respaldo diario, verificado y restaurable**. Todo lo
demás de este documento existe para que ese respaldo esté siempre y sirva.

## 3. La regla 3-2-1

Es la regla más simple y más conocida para que un respaldo sobreviva a lo que sea:

- **3** copias de los datos (la que está en uso + 2 respaldos),
- en **2** medios distintos (que no fallen por la misma causa: un disco y otro disco no
  alcanza si están en la misma máquina y se quema la fuente),
- **1** de ellas **fuera del edificio** (un incendio, un robo o una inundación se llevan
  todo lo que esté en la misma sala).

Cómo la cumple SiGeRU:

| Copia | Medio | Dónde |
|---|---|---|
| 1 — los datos en uso | Disco del servidor de base de datos | Sala de servidores de la intendencia |
| 2 — respaldo diario | `/backups` en el servidor de respaldos (tercer servidor de la letra) | Misma sala, otra máquina y otro disco |
| 3 — respaldo mensual | Disco externo USB | Otro edificio de la intendencia (una oficina con llave) |

Mientras no exista el tercer servidor (se genera en la 3ra entrega), la copia 2 va a un
**disco aparte** del mismo servidor, montado en `/backups`. Es peor que la meta y lo
decimos: protege contra un `DROP DATABASE` o un error de una actualización, pero no
contra la rotura de la máquina. Por eso la copia mensual externa no es opcional.

## 4. Calendario

| Cuándo | Qué | Cómo | Dónde queda | Quién |
|---|---|---|---|---|
| **Todos los días a las 03:00** | Base + código + configuración, todo en un `.tar.gz` con su huella SHA-256 | `backup.sh`, disparado por `/etc/cron.d/sigeru-backup` | `/backups/sigeru-AAAAMMDD-HHMM.tar.gz` | Automático |
| **Semanal — domingos, después del diario** | Copia completa del respaldo del domingo | Copia al servidor de respaldos (`rsync` o `scp`, que vienen con Ubuntu). Se automatiza en la 3ra entrega, cuando exista ese servidor; hasta entonces la hace el responsable a mano | Servidor de respaldos, carpeta `semanal/` | Responsable de sistemas |
| **Mensual — primer domingo de cada mes** | Copia completa del respaldo de ese día a un medio externo | Copiar el `.tar.gz` y su `.sha256` al disco USB, verificar con `sha256sum -c`, y llevar el disco al otro edificio | Disco externo, fuera del edificio | Responsable de sistemas, 10 minutos, con lista de control |
| **Mensual — mismo día** | **Prueba de restauración** (sección 7) | `restaurar.sh` en una máquina de prueba + los 47 tests | Registro en la tabla de la sección 7 | Responsable de sistemas |

Por qué a las 03:00: es la hora de menos uso (la recolección arranca de mañana) y deja
margen para que termine antes del primer turno. Por qué el diario ya es «completo» y no
incremental: la base pesa menos de 1 MB y el código 11 MB, así que un respaldo completo
tarda segundos y ocupa nada; un esquema incremental (guardar solo lo que cambió) ahorra
espacio pero complica la restauración (hay que encadenar varias copias) y hoy no hay nada
que ahorrar. Si la base creciera a cientos de MB, se revisa.

## 5. Retención: cuánto tiempo se guarda cada copia

| Tipo | Se guardan | Cubre | Quién borra |
|---|---|---|---|
| Diarios | **14** (dos semanas) | «Ayer borré algo sin querer» — el caso más común | `backup.sh` borra solo los de más de 14 días |
| Semanales | **8** (dos meses) | Un error que nadie notó en dos semanas | A mano, al copiar el siguiente |
| Mensuales | **12** (un año) | Reconstruir un estado viejo (una auditoría, un reclamo) | A mano, al copiar el siguiente |

Son 34 copias como máximo. Con el tamaño actual (unos 3 MB comprimido cada una) ocupan
100 MB en total; aunque la base creciera cien veces, entran en un disco de 20 GB. Los
números salen de responder «¿cuánto tiempo puede pasar hasta que alguien note que falta
algo?»: dos semanas para lo cotidiano, dos meses para lo que se ve en un cierre de mes, un
año para lo que aparece en una auditoría.

## 6. Medios de respaldo a largo plazo

| Medio | Para qué se usa | A favor | En contra |
|---|---|---|---|
| **Disco aparte en el servidor** (`/backups`) | Los 14 diarios | Rápido, automático, no depende de nadie | Se pierde con la máquina |
| **Servidor de respaldos** (tercer servidor de la letra: 1 núcleo, 1 GB, 50 GB según el relevamiento) | Los 8 semanales y copia de los diarios | Otra máquina: sobrevive a la rotura del servidor principal. Es el que pide la letra y el que dimensionó el relevamiento de agosto | Misma sala: no sobrevive a un incendio ni a un robo |
| **Disco externo USB** | Los 12 mensuales | Barato, se guarda fuera del edificio, cumple el «1» de la regla | Depende de que alguien lo lleve; hay que verificarlo al copiar |
| **Nube** (opcional) | Copia de los mensuales | Fuera del edificio sin mover nada físico | Cuesta dinero por mes, necesita internet, y los datos personales de vecinos salen del edificio: habría que cifrarlos antes de subirlos. Por eso queda como opcional, no como parte de esta política |

Lo que **descartamos**: cintas (caras y sin lector en la intendencia), DVD (poca capacidad
y se degradan) y «el respaldo en la PC de alguien» (no es un medio, es un riesgo).

## 7. La prueba de restauración mensual es obligatoria

Un respaldo que nunca se restauró no se sabe si sirve. Es la parte que más se saltea y la
única que importa: el día que se necesita, no hay margen para descubrir que el archivo
estaba vacío o que el script tenía un error.

**Procedimiento** (el primer domingo de cada mes, 20 minutos):

1. Tomar el respaldo del día (`/backups/sigeru-<fecha>.tar.gz`) y su `.sha256`.
2. En una máquina de prueba (una VM o un contenedor Ubuntu con `mysql-client`, nunca el
   servidor en producción), correr `restaurar.sh <archivo>`.
3. Correr los 47 tests del proyecto (`tests/correr-tests.php`) contra la base restaurada.
4. Anotar el resultado en esta tabla. Si falló, es prioridad 1 hasta que pase.

| Fecha | Respaldo probado | Dónde | Resultado | Quién |
|---|---|---|---|---|
| 12/09/2026 | `sigeru-20260912-*.tar.gz`, hecho contra la base del Docker del proyecto | Contenedor Ubuntu en la red del Docker | Ver `pruebas/2026-09-12-prueba-respaldo.txt`: la base se borró con `DROP DATABASE`, se restauró y los tests pasaron | Franco Fornos |

## 8. Alta disponibilidad

### 8.1 Qué es

Un sistema es de **alta disponibilidad** cuando sigue funcionando aunque se rompa una de
sus partes: si se cae un servidor, otro toma su lugar sin que los usuarios lo noten. Se
mide en porcentaje de tiempo en servicio: 99 % es estar caído hasta 3,6 días por año;
99,9 % son 8,7 horas por año. Cada «9» que se agrega cuesta más o menos el doble.

Es distinto de tener respaldos: **el respaldo protege los datos; la alta disponibilidad
protege el servicio**. Se pueden tener respaldos perfectos y estar caídos tres días, o
tener dos servidores en espejo y perder los datos igual (si alguien borra una tabla, el
espejo la borra también en el mismo segundo). Se necesitan las dos cosas.

### 8.2 Cómo estamos hoy

Hoy hay **un solo servidor** (y en rigor todavía no está instalado: el sistema corre en
XAMPP desde el pendrive y en Docker). Todo —Apache, PHP, MySQL— vive en la misma máquina.
Si se rompe, SiGeRU no funciona hasta que se reinstale y se restaure: son las 4 horas del
RTO. Para el uso real que va a tener (una oficina de la intendencia, en horario de
trabajo, con decenas de usuarios) eso es **aceptable como punto de partida**, y lo
decimos así para no vender lo que no hay.

### 8.3 El plan realista para una intendencia chica

La letra pide tres servidores como mínimo (aplicaciones, base de datos y respaldos). Con
esos tres se puede llegar a una disponibilidad razonable sin comprar nada raro, en dos
pasos:

**Paso 1 — separar y replicar la base de datos.**
MySQL queda en el servidor de base de datos, y en el servidor de respaldos corre una
**réplica** (*replication* de MySQL: el servidor principal manda cada cambio a la réplica,
que lo aplica en segundos). Si el servidor de base de datos se rompe, se apunta la
aplicación a la réplica cambiando una variable de entorno (`SIGERU_DB_HOST`, que ya existe
para eso) y el sistema vuelve en minutos, no en horas. Cuesta cero: es una función de
MySQL, y el servidor de respaldos ya tiene que existir.
Ojo: la réplica **no reemplaza al respaldo**, porque copia también los errores. Sí permite
hacer los respaldos desde la réplica, sin tocar el servidor principal.

**Paso 2 — segundo servidor web detrás de un balanceador.**
Las APIs de SiGeRU no guardan datos en el disco del servidor web (todo está en MySQL), así
que se pueden correr dos copias iguales. Lo único que hoy vive en el servidor web es la
sesión de PHP (un archivo por usuario conectado): para tener dos servidores hay que
guardarla en la base —PHP lo permite con un cambio de configuración— o hacer que el
balanceador mande siempre al mismo servidor a cada usuario («sesión pegajosa»). Un
**balanceador** (Apache con `mod_proxy_balancer`, que viene con el mismo paquete `apache2`;
o HAProxy, que está en los repositorios de Ubuntu) reparte las peticiones entre las dos y
deja de mandar a la que no responde. El segundo servidor web puede ser el mismo servidor de
respaldos, que tiene capacidad de sobra: su trabajo es guardar archivos de noche.

Con los dos pasos:

| Falla | Hoy | Con el plan |
|---|---|---|
| Se rompe el servidor web | Sistema caído hasta reinstalar (horas) | El balanceador manda todo al otro; nadie lo nota |
| Se rompe el servidor de base de datos | Sistema caído + se pierde el trabajo desde las 03:00 | Se apunta a la réplica; se pierden segundos, no horas |
| Alguien borra una tabla | Se restaura el respaldo de las 03:00 | Igual: la réplica también la borró. **Por eso está el respaldo** |
| Se corta la luz de la sala | Todo caído | Todo caído: los tres servidores están en la misma sala. Para eso haría falta un segundo sitio, que excede a una intendencia chica |

Lo que **no** proponemos: clústeres con conmutación automática de MySQL (Galera, InnoDB
Cluster), Kubernetes, ni un segundo centro de datos. Son las herramientas correctas para
un banco, no para una oficina de recolección; agregan complejidad que el equipo que va a
administrar esto no puede sostener, y el costo de un día caído no lo justifica.

### 8.4 Qué se entrega y qué queda para después

- **2da entrega (este documento)**: la política completa, los scripts del respaldo diario
  probados con una restauración real, y el plan de alta disponibilidad fundamentado.
- **3ra entrega** (la letra pide «generación del servidor de respaldo»): el tercer
  servidor instalado, la copia semanal automatizada hacia él, y —si el tiempo alcanza— la
  réplica de MySQL del paso 1. El paso 2 (balanceador) queda propuesto para la puesta en
  producción real, después de la defensa.

## 9. Responsabilidades

| Tarea | Frecuencia | Responsable |
|---|---|---|
| Mirar el log `/var/log/sigeru-backup.log` y que exista el `.tar.gz` de hoy | Semanal (lunes) | Responsable de sistemas de la intendencia; hasta la entrega, el dueño de la materia en el equipo (propuesto para la 3ra: Axel Delgado) |
| Copia semanal al servidor de respaldos | Domingos | Ídem |
| Copia mensual al disco externo + prueba de restauración | Primer domingo del mes | Ídem |
| Revisar esta política | Una vez por año, o cuando cambie el tamaño de la base o la cantidad de usuarios | Equipo |

## 10. Fuentes

- Letra del Proyecto de Pasaje de Grado 2026, sección 9.3 (Administración de Sistemas
  Operativos): «definición de medios de respaldo a largo plazo y alta disponibilidad»,
  «rutinas de backup», y el mínimo de 3 servidores.
- `Relevamiento_Sistema_Operativo_SiGeRU.pdf` (21/08/2026), secciones 3.1 (los tres
  servidores), 9.6 (ventanas de mantenimiento), 10 (dimensionamiento) y 11 (riesgos: «pérdida
  de datos por falla de hardware → respaldos diarios al tercer servidor y verificación
  mensual de restauración»). Esta política es la que cumple esa contingencia.
- Documentación de MySQL 8: `mysqldump` (opción `--single-transaction`) y *Replication*.
- Documentación de Ubuntu Server: `cron`, `tar`, `sha256sum`.
