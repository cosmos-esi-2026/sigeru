# Red estática del servidor sigeru-srv

**Hecho el 13/09/2026.** Aplicado por SSH desde la PC anfitriona, sobre el servidor recién
instalado (ver `manual-de-instalacion.md`).

## Por qué NAT y no puente

`plan-de-instalacion.md` (12/09) ya prevenía este caso: la PC donde se instaló está en el
dominio Ceibal, y una interfaz en modo puente/promiscuo puede estar bloqueada por política
de red del laboratorio. Se usó **NAT** directamente (la VM sale a internet y la PC
anfitriona la ve por el reenvío de puerto configurado; el resto de la red no la ve), con la
consecuencia de que el "ping desde la PC anfitriona" que pedía el plan original no aplica
igual bajo NAT (ver más abajo).

## El archivo

`config/netplan/01-sigeru.yaml`, copiado a `/etc/netplan/01-sigeru.yaml` en el servidor
(dueño root, permisos 600):

```yaml
network:
  version: 2
  ethernets:
    enp0s3:
      dhcp4: false
      addresses:
        - 10.0.2.15/24
      routes:
        - to: default
          via: 10.0.2.2
      nameservers:
        addresses: [10.0.2.3, 1.1.1.1]
```

`10.0.2.15` es la misma IP que la VM ya tenía por DHCP dentro de la red NAT que arma
VirtualBox (`10.0.2.0/24`), así que fijarla con netplan no cambió nada en caliente: no hubo
riesgo de perder la conexión SSH al aplicar. `10.0.2.2` es siempre el gateway (la PC
anfitriona) y `10.0.2.3` el servidor DNS que arma VirtualBox en ese modo.

## Aplicación

```bash
sudo cp 01-sigeru.yaml /etc/netplan/01-sigeru.yaml
sudo chown root:root /etc/netplan/01-sigeru.yaml
sudo chmod 600 /etc/netplan/01-sigeru.yaml
sudo netplan apply
```

## Evidencia (transcripción completa en `pruebas/2026-09-13-prueba-red-estatica.txt`)

```
$ ip -brief address
enp0s3   UP   10.0.2.15/24 ...

$ ip route
default via 10.0.2.2 dev enp0s3 proto static
10.0.2.0/24 dev enp0s3 proto kernel scope link src 10.0.2.15

$ resolvectl status
DNS Servers: 10.0.2.3 1.1.1.1 ...

$ ping -c 3 1.1.1.1        -> 0% de pérdida
$ ping -c 3 ubuntu.com     -> 0% de pérdida (resuelve nombres y sale a internet)
```

### Sobre el "ping desde la PC anfitriona"

El plan original (pensado para red puente) pedía probar `ping <ip-servidor>` desde la PC
anfitriona como evidencia de que la VM es visible en la red. Bajo NAT eso no funciona: el
host no tiene ruta directa a la IP interna de la VM (`10.0.2.15`), solo a lo que tenga
reenvío de puerto explícito. La prueba equivalente bajo NAT es que **el acceso SSH ya
funciona** desde la PC anfitriona contra `127.0.0.1:2222` (reenvío de puerto configurado en
la VM), que es justamente el canal que se usó para aplicar y verificar todo esto.
