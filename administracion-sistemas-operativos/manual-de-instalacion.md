# Manual de instalación del servidor sigeru-srv

**Hecho el 13/09/2026**, adelantado del primer sprint de la 3ra entrega (donde lo dejaba
`plan-de-instalacion.md`) porque la ficha de Falcon de esa fecha aclaró que la 2da entrega
también completa la calificación de la 1ra, y esta era una de sus tres deudas vencidas.

Hipervisor: **VirtualBox** (Windows 11 Pro, PC de Fornos, dominio Ceibal). Se instaló a
mano, pantalla por pantalla, con el asistente de VirtualBox y el instalador interactivo de
Ubuntu — a diferencia de lo que planteaba el commit del mismo día por la mañana
(`VBoxManage unattended install`, pensado para cuando nadie puede clickear la consola
gráfica): acá sí había alguien para eso, así que se volvió al camino manual que ya
describía `plan-de-instalacion.md`, sección 4.

## La máquina virtual

| Parámetro | Valor |
|---|---|
| Nombre | `sigeru-srv` |
| Carpeta de la VM | `C:\Users\franc\VirtualBox VMs\sigeru-srv` (en el disco local, no en el pendrive: es exFAT y por USB, y el disco de una VM ahí sería lento y sin journaling) |
| ISO | `vm-ubuntu\ubuntu-26.04.1-live-server-amd64.iso` |
| Tipo de SO invitado | Ubuntu detectado como "25.04 (Plucky Puffin)" — VirtualBox todavía no tiene una entrada específica para 26.04 en su lista interna; es cosmético, no afectó la instalación real |
| CPU | 2 vCPU |
| Memoria | 2048 MB |
| Disco | 20 GB, dinámico, sin EFI |
| Red | **NAT** (no puente: el dominio Ceibal puede bloquear el modo puente/promiscuo, como ya había anotado `plan-de-instalacion.md`) + reenvío de puerto `2222 (host) -> 22 (invitado)` para SSH |
| Instalación desatendida | **No** (`Proceed with Unattended Installation` destildado): se hizo el instalador interactivo a mano |

## Las 16 pantallas — qué se eligió y qué quedó distinto del plan

| # | Pantalla | Se planeaba | Se hizo |
|---|---|---|---|
| 1 | Arranque (GRUB) | Try or Install Ubuntu Server | Igual |
| 2 | Idioma | English | **Español** (desvío: se avanzó sin querer en español antes de poder volver atrás; se aceptó seguir así en vez de reinstalar) |
| 3 | Actualización del instalador | Aceptar si ofrece | (no se vio por separado, pasó junto con el idioma) |
| 4 | Teclado | Spanish (Latin American) | Igual, corrigiendo a mitad de camino: primero quedó "Spanish" (España) a secas, se cambió el *Variant* a Latin American |
| 5 | Tipo de instalación | Ubuntu Server (no minimized) | Igual |
| 6 | Red | DHCP por ahora | Igual — quedó `10.0.2.15/24` por DHCP dentro de la NAT, que es la misma IP que después se fija con netplan (ver `red-estatica.md`) |
| 7 | Proxy | Vacío | Igual |
| 8 | Espejo de paquetes | El que propone | Igual |
| 9 | Disco | Use an entire disk, sin LVM ni cifrado | Igual, con una corrección a mitad de camino: LVM venía **tildado** por defecto y se destildó a mano |
| 10 | Confirmación del particionado | Confirmar | Igual |
| 11 | Perfil | Nombre: Equipo COSMOS · servidor: `sigeru-srv` · usuario: `sigeru` | Nombre y hostname iguales; **usuario quedó `cosmos2026`**, no `sigeru` (desvío intencional, aceptado después de instalado: ver más abajo). Contraseña elegida por el equipo, no vive en el repositorio |
| 12 | Ubuntu Pro | Skip for now | Igual |
| 13 | SSH | Marcar "Install OpenSSH server", no importar claves | Igual, con una corrección a mitad de camino: la casilla de instalar OpenSSH venía **destildada** por defecto y se marcó a mano. "Permitir autenticación con contraseña por SSH" quedó tildado (coincide con el plan: se apaga recién cuando estén las claves de los cuatro cargadas) |
| 14 | Featured server snaps | Ninguno | Igual |
| 15 | Progreso e instalación | Esperar Reboot Now | Igual. Instaló Apache/PHP/MySQL... no, eso es aparte (sección de paquetes); acá solo el sistema base + OpenSSH |
| 16 | Primer inicio de sesión | `hostname`, `ip a`, `lsb_release -a` | Corridos por consola gráfica: `sigeru-srv`, `10.0.2.15/24` en `enp0s3`, `Ubuntu 26.04.1 LTS` |

### Por qué el usuario quedó `cosmos2026` y no `sigeru`

Fue un desvío de tipeo en la pantalla de perfil, detectado recién al loguear (el prompt
mostraba `cosmos2026@sigeru-srv`, no `sigeru@sigeru-srv`). Se evaluaron las dos opciones
—crear el usuario `sigeru` desde cero o seguir con `cosmos2026`— y se decidió **seguir con
`cosmos2026`**, adaptando la configuración ya escrita en vez de recrear la cuenta. Afecta
un solo archivo: `config/sshd_config.d/sigeru.conf` (`AllowUsers cosmos2026` en vez de
`AllowUsers sigeru`), ya corregido. No afecta a la base de datos, que sigue llamándose
`sigeru`, ni a `/opt/sigeru` ni a ningún otro path del proyecto.

## Capturas

Las 16 capturas numeradas (`capturas/01-arranque.png` … `16-primer-login.png`) que pide
este ítem **todavía no están guardadas como archivos** en el repositorio: la instalación se
hizo mirando la pantalla en vivo durante la sesión de trabajo del 13/09, pero guardar cada
captura con la herramienta de VirtualBox (**View → Take Screenshot**, no una foto de
celular) es un paso manual que queda pendiente para quien tenga la VM abierta. Antes de dar
este ítem por cerrado del todo, hay que:

1. Volver a abrir la VM (o usar las capturas que ya se tomaron durante la sesión, si se
   guardaron) y completar `capturas/` con los 16 archivos.
2. Referenciarlos desde este documento si se arma como PDF.

## Acceso al servidor ya instalado

```
ssh -p 2222 cosmos2026@127.0.0.1     # desde la PC que tiene la VM (reenvío de puerto NAT)
```

La clave pública de administración remota vive en `vm-ubuntu/ssh/` (fuera del repositorio,
igual criterio que `backup.conf` real y el `user-data` real del autoinstall).
