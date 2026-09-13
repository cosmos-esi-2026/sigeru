# Configuración del servicio SSH en sigeru-srv

**Hecho el 13/09/2026.** Aplicado y probado por SSH desde la PC anfitriona.

## El archivo

`config/sshd_config.d/sigeru.conf`, copiado a `/etc/ssh/sshd_config.d/sigeru.conf` (Ubuntu
Server moderno ya incluye `Include /etc/ssh/sshd_config.d/*.conf` al final de
`sshd_config`, así que alcanza con copiar el archivo sin tocar el original):

| Directiva | Valor | Por qué |
|---|---|---|
| `PermitRootLogin` | `no` | Es la primera cuenta que prueba cualquier robot que escanea internet. Con `no`, para administrar hay que entrar con el usuario normal y `sudo`, que además deja registrado quién hizo qué |
| `PasswordAuthentication` | `yes` (por ahora) | Se deja hasta que las claves de los cuatro integrantes estén cargadas en `~cosmos2026/.ssh/authorized_keys`; recién entonces se pasa a `no`. **Sigue pendiente**: por ahora solo está cargada la clave de administración remota, no la de cada integrante |
| `MaxAuthTries` | `3` | Corta los intentos de adivinar contraseña |
| `AllowUsers` | `cosmos2026` | Solo esa cuenta entra por SSH. Dice `cosmos2026` y no `sigeru` porque ese quedó siendo el usuario del sistema operativo en esta instalación (ver `manual-de-instalacion.md`, "por qué el usuario quedó cosmos2026") |
| `X11Forwarding` | `no` | No hay entorno gráfico en el servidor: menos superficie de ataque |

## Aplicación

```bash
sudo cp sigeru.conf /etc/ssh/sshd_config.d/sigeru.conf
sudo chown root:root /etc/ssh/sshd_config.d/sigeru.conf
sudo chmod 644 /etc/ssh/sshd_config.d/sigeru.conf
sudo sshd -t              # valida la sintaxis antes de aplicar
sudo systemctl reload ssh
```

`sshd -t` antes de recargar es la diferencia entre un error de tipeo que se corrige al
toque y quedarse sin acceso remoto al servidor.

## Prueba: acceso permitido y acceso rechazado

```
$ ssh -p 2222 cosmos2026@127.0.0.1 "whoami"
cosmos2026

$ ssh -p 2222 root@127.0.0.1 "whoami"
root@127.0.0.1: Permission denied (publickey,password).
```

Log del servidor (`journalctl -u ssh`):

```
sshd-session: User root from 10.0.2.2 not allowed because not listed in AllowUsers
sshd-session: Connection closed by invalid user root 10.0.2.2 port ... [preauth]
sshd-session: Accepted publickey for cosmos2026 from 10.0.2.2 port ...
```

`10.0.2.2` es la PC anfitriona vista desde la red NAT de VirtualBox, no una IP externa.
Transcripción completa en `pruebas/2026-09-13-prueba-ssh.txt`.

## Autenticación por clave

Se cargó una clave ED25519 dedicada a la administración remota (generada en la PC
anfitriona, en `vm-ubuntu/ssh/`, fuera del repositorio) en
`~cosmos2026/.ssh/authorized_keys`. Con eso el acceso administrativo no depende de la
contraseña de la cuenta.

## Lo que queda pendiente

- Cargar la clave pública de cada uno de los cuatro integrantes en
  `~cosmos2026/.ssh/authorized_keys` (con `ssh-copy-id` desde cada PC).
- Recién con las cuatro cargadas, pasar `PasswordAuthentication` a `no` en
  `sigeru.conf` y volver a aplicar (`sudo systemctl reload ssh`).

Este segundo paso no bloquea el ítem de la letra (el servicio SSH está configurado,
endurecido y probado), pero conviene cerrarlo antes de la instalación final de
noviembre.
