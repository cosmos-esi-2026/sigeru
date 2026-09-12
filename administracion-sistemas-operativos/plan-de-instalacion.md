# Plan de instalación del servidor SiGeRU (deuda de Administración de S.O.)

**Equipo COSMOS** — escrito el 12/09/2026 para el **primer sprint de la 3ra entrega**.

Este documento **no es el manual de algo que se hizo**: es el plan exacto de lo que falta
hacer para pagar los cuatro ítems de la materia que necesitan un servidor instalado y que
**no entran en la 2da entrega**:

| Ítem | Lo pide la | Estado al 12/09 |
|---|---|---|
| Manual de instalación del S.O. servidor | 1ra entrega (vencido desde julio) | No hecho: nunca se instaló la VM |
| Instalación y configuración de la red (estática) | 1ra entrega (vencido) | Ídem |
| Gestión y administración de paquetes | 1ra entrega (vencido) | Ídem |
| Configuración del servicio SSH | 2da entrega | Ídem |

Por qué no se hicieron este fin de semana está en el plan de cierre del 12/09: una primera
instalación con capturas, netplan y SSH son 4-5 horas para quien nunca la hizo, la ISO
correcta no está bajada, y el día que quedaba era para armar la entrega, no para
construir. Lo que sí está listo y **se va a instalar en ese servidor el primer día** son
los scripts de respaldo y de administración de la 2da entrega (`scripts/`), probados en
un contenedor Ubuntu.

Quien lo ejecute no debería tener que decidir nada: todas las decisiones están tomadas
abajo, con su porqué. **Dueño**: Axel Delgado (dueño de la materia para la 3ra entrega).
**Tiempo estimado**: 6 horas de trabajo efectivo, repartidas en una semana.

---

## 1. Hipervisor: dónde corre la máquina virtual

| Opción | Cuándo usarla | Cómo se activa |
|---|---|---|
| **Hyper-V** (recomendada en la PC de Fornos) | Windows 11 Pro/Education. En esa PC ya está la plataforma de virtualización activa porque WSL2 y Docker Desktop la usan; solo falta la consola de administración. | `Panel de control → Programas → Activar o desactivar características de Windows → Hyper-V` (las dos casillas), reiniciar. Necesita el usuario administrador local, que existe. |
| **VirtualBox** (la PC de Axel, o si Hyper-V no está disponible) | Windows Home, u otra PC sin Hyper-V. Es la herramienta que asumió el relevamiento de agosto. | Bajar el instalador de virtualbox.org (unos 100 MB), instalar con las opciones por defecto. |

No conviene tener las dos activas en la misma PC: VirtualBox sobre Hyper-V funciona pero
mucho más lento. Se elige una por máquina.

## 2. La imagen: Ubuntu Server 26.04 LTS

- Archivo: `ubuntu-26.04-live-server-amd64.iso` (2,7 GB), desde
  <https://ubuntu.com/download/server> (o <https://releases.ubuntu.com/26.04/>).
- Verificar la descarga con la suma SHA-256 que publica la misma página
  (`sha256sum` en Linux, `Get-FileHash` en PowerShell): una ISO cortada instala mal y se
  descubre tarde.
- Guardarla en `vm-ubuntu/` del pendrive **reemplazando** a
  `ubuntu-22.04-live-server-amd64.iso`, que es la versión que el relevamiento de julio
  había elegido y que el relevamiento de agosto (vigente) descartó: 26.04 tiene soporte
  hasta 2031 y hasta 2036 con Ubuntu Pro gratuito. La de 22.04 se borra: 1,8 GB de un
  sistema que no vamos a instalar.

## 3. La máquina virtual

| Parámetro | Valor | Por qué |
|---|---|---|
| Nombre | `sigeru-srv` | Igual que el hostname, para no confundirse |
| CPU | **2 vCPU** | El instalador y Apache + MySQL corren cómodos; con 1 la instalación tarda el doble |
| Memoria | **2 GB** | Lo que dimensionó el relevamiento para el servidor de aplicaciones en el ambiente de entrega. Ubuntu Server sin entorno gráfico usa ~300 MB; MySQL 8 ~400 MB |
| Disco | **20 GB**, dinámico | El sistema instalado ocupa ~4 GB, SiGeRU 11 MB, los respaldos de un año menos de 1 GB. (El relevamiento decía 25; con 20 alcanza y el disco virtual se agranda después sin reinstalar) |
| Red | **Puente** («External switch» en Hyper-V, «Adaptador puente» en VirtualBox) | La VM aparece en la red del laboratorio con su propia IP, como un servidor real: es lo que pide la letra («visible en la red») y lo que hace posible entrar por SSH desde otra PC |
| Firmware | Hyper-V: generación 2, con *Secure Boot* usando la plantilla «Microsoft UEFI Certificate Authority» (Ubuntu está firmado) · VirtualBox: EFI opcional | Generación 2 arranca más rápido y permite discos grandes |
| Unidad de DVD | La ISO de 26.04 | Solo para instalar; después se desconecta |

Si la red puente no funciona en una notebook con Wi-Fi (pasa), para la demostración
alcanza con el «Default Switch» de Hyper-V o «NAT» en VirtualBox: la VM sale a internet y
la PC anfitriona la ve, aunque el resto de la red no. Se anota en el manual como
limitación del laboratorio, no del servidor.

## 4. La instalación, pantalla por pantalla (y las capturas del manual)

El manual de instalación va a tener **una captura numerada por pantalla del instalador**,
tomada con la herramienta de captura del hipervisor (no con el celular), guardada en
`administracion-sistemas-operativos/capturas/NN-nombre.png`. Esta es la lista, con la
decisión que se toma en cada una:

| # | Pantalla del instalador | Qué se elige | Captura |
|---|---|---|---|
| 1 | Menú de arranque (GRUB) | «Try or Install Ubuntu Server» | `01-arranque.png` |
| 2 | Idioma | **English**. El sistema en inglés hace que los mensajes de error coincidan con la documentación y los foros; los documentos del equipo siguen en español | `02-idioma.png` |
| 3 | Actualización del instalador | «Update to the new installer» si aparece (trae correcciones) | `03-instalador.png` |
| 4 | Teclado | Spanish (Latin American) — o el del teclado físico que se use | `04-teclado.png` |
| 5 | Tipo de instalación | **Ubuntu Server** (no «minimized»: la minimizada no trae ni `nano` ni `man`) | `05-tipo.png` |
| 6 | Red | Dejar DHCP por ahora. La IP fija se configura después con netplan (sección 5), para documentarla como ítem propio | `06-red.png` |
| 7 | Proxy | Vacío | `07-proxy.png` |
| 8 | Espejo de paquetes | El que propone (`archive.ubuntu.com`) | `08-espejo.png` |
| 9 | Disco | «Use an entire disk», **sin** LVM ni cifrado (una VM de laboratorio; el cifrado exigiría escribir una clave en cada arranque y la letra no lo pide) | `09-disco.png` |
| 10 | Confirmación del particionado | Revisar que sea el disco de 20 GB y confirmar | `10-particiones.png` |
| 11 | Perfil | Nombre: `Equipo COSMOS` · servidor: **`sigeru-srv`** · usuario: **`sigeru`** · contraseña: la que fije el equipo, guardada en el gestor de contraseñas del grupo, **no en el repositorio** | `11-perfil.png` |
| 12 | Ubuntu Pro | «Skip for now» (se activa después con `pro attach`, cuando haya cuenta; no cambia nada de lo que sigue) | `12-pro.png` |
| 13 | SSH | **Marcar «Install OpenSSH server»**. Sin esto, el servidor solo se administra desde la consola del hipervisor. No importar claves de GitHub todavía | `13-ssh.png` |
| 14 | *Featured server snaps* | **Ninguno**. Apache y MySQL se instalan con `apt` (sección 6), que es lo que hay que documentar | `14-snaps.png` |
| 15 | Progreso e instalación | Esperar «Reboot Now», sacar la ISO, reiniciar | `15-instalando.png` |
| 16 | Primer inicio de sesión | `sigeru` en la consola; `hostname`, `ip a`, `lsb_release -a` como evidencia | `16-primer-login.png` |

Con eso el manual queda con 16 capturas y una tabla como esta. Tiempo: 45 minutos.

## 5. Red estática con netplan

Ubuntu Server configura la red con **netplan**: un archivo YAML en `/etc/netplan/` que
se «aplica» y genera la configuración real. Se hace después de la instalación, así:

1. Ver el nombre de la interfaz y la IP que dio DHCP: `ip -brief address` (en Hyper-V suele
   ser `eth0`; en VirtualBox `enp0s3`).
2. Elegir una IP libre de la red del laboratorio, fuera del rango que reparte el DHCP
   (preguntar al docente o mirar el router). El ejemplo usa la red `192.168.1.0/24`.
3. Escribir `/etc/netplan/01-sigeru.yaml` (el que trae el instalador, `50-cloud-init.yaml`,
   se deja como está o se borra; el número más bajo se aplica primero y el nuestro es el
   que define la interfaz):

   ```yaml
   # /etc/netplan/01-sigeru.yaml - IP fija del servidor SiGeRU
   network:
     version: 2
     ethernets:
       eth0:                      # el nombre que mostro "ip -brief address"
         dhcp4: false
         addresses:
           - 192.168.1.50/24      # IP fija del servidor + mascara
         routes:
           - to: default
             via: 192.168.1.1     # el router del laboratorio
         nameservers:
           addresses: [192.168.1.1, 1.1.1.1]
   ```

4. Permisos: `sudo chmod 600 /etc/netplan/01-sigeru.yaml` (netplan se queja si otros
   usuarios pueden leerlo).
5. Probar **sin quedar afuera**: `sudo netplan try`. Aplica la configuración y, si en 120
   segundos no se confirma con Enter (porque se perdió la conexión), la deshace sola.
   Recién después `sudo netplan apply`.
6. Verificar y capturar: `ip -brief address`, `ip route`, `resolvectl status`, `ping -c 3
   1.1.1.1` (sale a internet), `ping -c 3 ubuntu.com` (resuelve nombres), y desde la PC
   anfitriona `ping 192.168.1.50` (es visible en la red). Estas cinco salidas son la
   evidencia del ítem. El módulo `red` de `sigeru-admin.sh` las muestra todas juntas
   (`sudo sigeru-admin red resumen`).

Documento resultante: `red-estatica.md` con el YAML, las salidas y la captura
`17-red-estatica.png`. Tiempo: 30 minutos.

## 6. Gestión de paquetes: apt, dpkg y snap

Tres herramientas conviven en Ubuntu y conviene saber cuál es cuál:

- **`dpkg`** es la de más abajo: instala, quita y lista paquetes `.deb` uno por uno, **sin
  resolver dependencias**. Se usa para consultar (`dpkg -l`, `dpkg -L apache2` lista los
  archivos de un paquete) y muy rara vez para instalar un `.deb` bajado a mano (`dpkg -i`).
- **`apt`** es la que se usa todos los días: busca en los repositorios de Ubuntu, baja el
  paquete **y todo lo que necesita**, y lo instala con dpkg. Los repositorios son los que
  garantizan que el paquete esté firmado y con parches de seguridad hasta 2031.
- **`snap`** es el otro formato de Canonical: paquetes autocontenidos que se actualizan
  solos. En un servidor lo evitamos para lo que se administra a mano (Apache, MySQL),
  porque sus rutas y su configuración no son las de la documentación clásica; por eso en
  la instalación no se marcó ningún snap.

Comandos que van al documento, en el orden en que se ejecutan en el servidor nuevo:

```bash
# 1. Poner al dia la lista de paquetes y el sistema (siempre primero)
sudo apt update
sudo apt upgrade -y

# 2. Instalar lo que necesita SiGeRU: Apache, PHP 8 y MySQL 8 (mismo stack que XAMPP y Docker)
sudo apt install -y apache2 php libapache2-mod-php php-mysql php-curl mysql-server mysql-client
sudo a2enmod rewrite            # las APIs rutean con .htaccess (igual que en Docker)

# 3. Ver que quedo instalado y en que version
apt list --installed | grep -E 'apache2|php|mysql'
apache2 -v; php -v; mysql --version

# 4. Consultar: buscar un paquete, ver su descripcion, ver que archivos trajo
apt search htop
apt show htop
dpkg -L apache2 | head

# 5. Quitar un paquete y lo que quedo sin uso
sudo apt remove htop
sudo apt autoremove

# 6. Actualizaciones de seguridad automaticas (viene instalado; se confirma)
sudo dpkg-reconfigure -plow unattended-upgrades

# 7. Que hay de snap (no deberia haber nada nuestro)
snap list
```

Con Apache y MySQL instalados, se despliega SiGeRU en el servidor **igual que en el camino
3 del README del repositorio** (XAMPP común): clonar el repo en `/var/www/sigeru`, importar
`database/schema.sql` y `datos_prueba.sql`, y apuntar Apache con un `Alias /sigeru` +
`AllowOverride All` (el mismo `sigeru.conf` de XAMPP y del Dockerfile, adaptado). MySQL en
Ubuntu queda con `root` por socket: se crea un usuario `sigeru` con contraseña para las
APIs y para `backup.conf`. Los 47 tests corriendo en el servidor son la evidencia de que
el despliegue quedó bien.

Documento resultante: `gestion-de-paquetes.md` con los comandos, sus salidas y
`18-apt.png`. Tiempo: 1 hora con el despliegue de SiGeRU incluido.

## 7. SSH: entrar de forma remota y segura

OpenSSH ya quedó instalado en el paso 13 de la instalación; lo que falta es endurecerlo y
probarlo. Se edita `/etc/ssh/sshd_config` (con `sudo nano`) y se cambian estas líneas:

| Directiva | Valor | Por qué |
|---|---|---|
| `PermitRootLogin` | **`no`** | Es la primera cuenta que prueba cualquier robot que escanea internet: se sabe que existe y tiene todos los permisos. Con `no`, para administrar hay que entrar como `sigeru` y usar `sudo`, que además deja registrado quién hizo qué |
| `PasswordAuthentication` | `yes` al principio; **`no`** cuando cada integrante tenga su clave | Las claves SSH no se adivinan por fuerza bruta; una contraseña sí. Se deja la contraseña hasta que las claves de los cuatro estén cargadas en `~sigeru/.ssh/authorized_keys` (`ssh-copy-id` desde cada PC) |
| `MaxAuthTries` | `3` | Corta los intentos de adivinar contraseñas |
| `AllowUsers` | `sigeru` | Solo esa cuenta puede entrar por SSH, aunque después se creen otras (por ejemplo con `sigeru-admin usuarios alta`) |
| `X11Forwarding` | `no` | No hay entorno gráfico; menos superficie de ataque |

Después: `sudo sshd -t` (comprueba la sintaxis: un error acá deja el servidor sin acceso
remoto) y `sudo systemctl restart ssh`. Se prueba desde la PC anfitriona con
`ssh sigeru@192.168.1.50`, y se documenta también un intento fallido como `root`
(`ssh root@192.168.1.50` → «Permission denied»), que es la evidencia de que la
configuración se aplicó. `sudo journalctl -u ssh -n 20` muestra los dos intentos.

Documento resultante: `ssh.md` con la tabla, el `sshd_config` final en
`config/sshd_config`, y las capturas `19-ssh-ok.png` y `20-ssh-root-denegado.png`.
Tiempo: 30 minutos.

## 8. Cerrar el círculo: los scripts de la 2da entrega en el servidor real

El mismo día, con el servidor andando (30 minutos):

```bash
git clone https://github.com/cosmos-esi-2026/sigeru.git /tmp/sigeru
cd /tmp/sigeru/administracion-sistemas-operativos/scripts/respaldo
sudo ./instalar.sh                          # /opt/sigeru, /backups, cron de las 03:00
sudo nano /opt/sigeru/respaldo/backup.conf  # usuario y clave de MySQL del servidor
sudo /opt/sigeru/respaldo/backup.sh         # primer respaldo
sudo sigeru-admin                           # el menu, en el servidor
```

La salida de esos comandos en el servidor real se agrega a `pruebas/` como segunda
evidencia (la primera es la del contenedor del 12/09).

## 9. Dónde va cada cosa en el repositorio cuando esté

```
administracion-sistemas-operativos/
├── manual-de-instalacion.md        (sección 4)  + capturas/01..16
├── red-estatica.md                 (sección 5)  + capturas/17 + config/netplan/01-sigeru.yaml
├── gestion-de-paquetes.md          (sección 6)  + capturas/18
├── ssh.md                          (sección 7)  + capturas/19, 20 + config/sshd_config
├── pruebas/<fecha>-servidor-real.txt (sección 8)
└── Administracion_SO_3ra_Entrega_SiGeRU.pdf  (generado; doc15_admin_so.py se amplía
                                               o se hace doc16, sin tocar el PDF de la 2da)
```

Reglas que ya rigen y siguen rigiendo: los PDF se generan desde `generadores/`, no se
editan a mano; el `.gitattributes` fuerza LF en `*.sh`, `*.conf` y `cron/*`; nada de
contraseñas en el repositorio (el `.gitignore` ya bloquea `*credenciales*`).

## 10. Orden de ejecución y criterio de «listo»

| Día | Tarea | Listo cuando |
|---|---|---|
| 1 | Bajar y verificar la ISO; activar Hyper-V o instalar VirtualBox | La ISO está en `vm-ubuntu/` con SHA-256 correcta y el hipervisor abre |
| 1 | Crear la VM e instalar (sección 4) | Se entra como `sigeru` en la consola y hay 16 capturas en el repo |
| 2 | IP fija (sección 5) | `ping` desde la PC anfitriona responde; `red-estatica.md` en el repo |
| 2 | Paquetes + despliegue de SiGeRU (sección 6) | Los 47 tests pasan en el servidor; `gestion-de-paquetes.md` en el repo |
| 3 | SSH (sección 7) | `ssh sigeru@IP` entra, `ssh root@IP` es rechazado; `ssh.md` en el repo |
| 3 | Scripts de la 2da en el servidor (sección 8) | `/backups` tiene un `.tar.gz` y `sigeru-admin` corre |
| 4 | Regenerar el PDF de la materia y actualizar el checklist | PDF leído con `pypdf`, checklist con los 8 ítems en `[x]` |

Si el día 1 la VM no arranca después de dos intentos, se pide ayuda al docente de la
materia **ese mismo día**, no en la siguiente reunión: es el punto del que dependen los
otros seis.
