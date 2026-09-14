---
materia: Ciberseguridad
entrega: 2da (registro de implementaciones de seguridad para servidores: IDS, IPS, firewall, fail2ban)
fecha: 2026-09-14
---

# Firewall y fail2ban en el servidor real — SiGeRU

**Hecho, instalado y probado el 14/09/2026 contra `sigeru-srv`** (la misma VM
que Administración de Sistemas Operativos instaló el 13/09), por SSH desde la
PC anfitriona, con evidencia real — no un plan en papel.

## 1. Qué había antes

```
$ sudo ufw status verbose
Status: inactive

$ dpkg -l | grep fail2ban
(nada instalado)
```

`ufw` estaba en el sistema pero apagado, y `fail2ban` no estaba instalado.
MySQL ya escuchaba solo en `127.0.0.1` (mitigación anterior, ver
`amenazas-y-vulnerabilidades.md`), pero ningún firewall de perímetro cubría
el resto: SSH (22) y Apache (80) eran alcanzables desde toda la red sin
ningún filtro.

## 2. fail2ban — detección y bloqueo de fuerza bruta contra SSH

Instalado (`apt-get install fail2ban`, versión 1.1.0-9). Ubuntu lo trae con
el jail de `sshd` ya activo por defecto (`/etc/fail2ban/jail.d/defaults-debian.conf`,
`banaction = nftables`). Se agregó una configuración propia,
`config/fail2ban/sigeru.conf` (copiar a `/etc/fail2ban/jail.d/sigeru.conf`):

```ini
[DEFAULT]
ignoreip = 127.0.0.1/8 ::1 10.0.2.2

[sshd]
enabled  = true
maxretry = 3
findtime = 10m
bantime  = 1h
```

`10.0.2.2` es el gateway NAT de VirtualBox = la PC anfitriona, el único canal
de administración remota que existe hoy (ver `red-estatica.md`). Se puso en
la lista blanca **con motivo**, no como comodín: durante la propia prueba de
esta entrega, el equipo se baneó a sí mismo por error (ver la sección 3, es
la evidencia de por qué la lista blanca hace falta), y el objetivo es que
`fail2ban` siga protegiendo contra cualquier otro origen sin arriesgar que el
equipo quede afuera de su propio servidor por un error de tipeo.

## 3. Evidencia: un ataque real, no simulado

Mientras se depuraba la conexión SSH para esta misma entrega, el jail de
`sshd` (con su configuración por defecto, antes de escribir la lista blanca)
detectó los fallos de autenticación reales del propio equipo y **baneó solo**
al origen de administración:

```
2026-09-14 01:23:29 [sshd] Ban 10.0.2.2
2026-09-14 01:32:12 [sshd] Unban 10.0.2.2   (bantime por defecto, 600 s)
```

Es la prueba más honesta posible de que `fail2ban` lee el log de `sshd` y
actúa de verdad: no fue un ataque preparado para la demostración, fue el
sistema reaccionando a intentos fallidos reales. Transcripción completa en
`pruebas/2026-09-14-prueba-firewall-fail2ban.txt`.

Con la lista blanca ya puesta, se hizo además una prueba manual y controlada
de ban/desban sobre una IP sintética (`198.51.100.23`, bloque reservado para
documentación, RFC 5737 — no es una IP real de nadie), para comprobar sin
riesgo que el mecanismo sigue funcionando para cualquier origen que no sea el
de administración:

```
$ sudo fail2ban-client set sshd banip 198.51.100.23
$ sudo nft list set inet f2b-table addr-set-sshd
    elements = { 198.51.100.23 }        <- regla real en el kernel, no solo un contador
$ sudo fail2ban-client set sshd unbanip 198.51.100.23
$ sudo nft list set inet f2b-table addr-set-sshd
    (vacío)
```

## 4. ufw — firewall de perímetro

```bash
sudo ufw allow 22/tcp comment 'SSH administracion'
sudo ufw allow 80/tcp comment 'SiGeRU HTTP'
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw --force enable
```

Se permitieron los dos puertos **antes** de activar la política de rechazo
por defecto, para no cortar el propio acceso SSH al aplicar los cambios
(mismo cuidado que ya usó Administración de S.O. con `sshd -t` antes de
recargar SSH). Estado final:

```
Status: active
Default: deny (incoming), allow (outgoing)
22/tcp   ALLOW IN  Anywhere   # SSH administracion
80/tcp   ALLOW IN  Anywhere   # SiGeRU HTTP
(+ las mismas dos reglas para IPv6)
```

Verificado justo después de activarlo, sobre la misma sesión SSH ya abierta,
que el acceso administrativo y el sitio seguían respondiendo:

```
$ echo ssh-sigue-vivo          -> ssh-sigue-vivo
$ curl http://localhost/sigeru/ -> HTTP 200
```

## 5. Sobre IDS/IPS de red

Se evaluó instalar un IDS/IPS de red completo (Snort o Suricata) y se decidió
**no hacerlo en esta entrega**: `sigeru-srv` vive detrás de NAT sin IP
pública, así que no hay tráfico de internet llegando directo a inspeccionar,
y afinar las reglas de un IDS de red sin generar ruido de falsos positivos es
un trabajo en sí mismo que no se justifica todavía con esa exposición. En su
lugar, `fail2ban` cumple el rol de **IDS/IPS de host**: detecta (lee el log
real de `sshd`) y actúa (bloquea con una regla de `nftables`) sobre la
amenaza que sí es realista hoy — fuerza bruta contra el único servicio de
administración remota expuesto. Queda anotado como decisión de alcance para
la 3ra entrega, si el servidor pasa a tener exposición real a internet.

## 6. Estado final, verificado

```
$ systemctl is-active ufw fail2ban ssh apache2 mysql
active active active active active

$ sudo ss -tulnp
127.0.0.1:3306   mysqld    <- nunca expuesto (ni por bind, ni por firewall)
0.0.0.0:22       sshd      <- permitido por ufw, endurecido (ver ssh.md)
*:80             apache2   <- permitido por ufw
```

Transcripción completa, sin editar, de toda la sesión:
`pruebas/2026-09-14-prueba-firewall-fail2ban.txt`.
