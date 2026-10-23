# Etiquetas QR para los contenedores (Proyecto UTU LAB, 3ra entrega)

Es la parte **analógica + digital** que pide la letra de la 3ra: una etiqueta impresa que se pega en el contenedor físico. El
vecino la escanea con el celular y se abre «Informar un problema» con ese contenedor ya elegido. Si no había iniciado sesión,
el login lo devuelve a esa misma pantalla (`?volver=`, arreglado el 23/10/2026 en `frontend-usuarios/js/api.js`).

## Cómo se generan

Desde la carpeta `generadores/` del pendrive:

```
..\herramientas\python\python.exe utulab3_qr.py                                    (contenedores de la ruta 1, localhost)
..\herramientas\python\python.exe utulab3_qr.py --ruta 6 --base http://192.168.1.127/sigeru
..\herramientas\python\python.exe utulab3_qr.py --codigos MVD-172712,MVD-101602
```

- `--ruta N` lee de la base los contenedores de esa ruta, en orden (la base tiene que estar levantada).
- `--base` es la dirección que queda **impresa en el QR**. Con `localhost` el QR solo abre en la propia PC: la hoja lo avisa en rojo.

## Para que abra en un celular

1. Arrancar con `herramientas\xampp\iniciar-xampp.cmd red` (sin `red`, Apache solo atiende a la misma PC). Muestra la IP de la PC.
2. Si Windows pregunta por el firewall de Apache: permitir solo en **red privada**.
3. Generar las etiquetas con esa IP: `--base http://LA-IP/sigeru`. La IP cambia según la red (casa, liceo): se regeneran el mismo día.
4. Celular en la **misma wifi** que la PC. Escanear con la cámara.
5. Al terminar, volver a arrancar sin `red`.

En el servidor del proyecto (`sigeru-srv`) la base sería la dirección del servidor, y no hace falta nada de lo anterior.

## Qué hay en la carpeta

| Archivo | Qué es |
|---|---|
| `Etiquetas_QR_ruta_1.pdf` | Los 20 contenedores de «Municipio A - Ruta 1», con `localhost` (muestra, no sirve para un celular) |
| `Etiquetas_QR_elegidos.pdf` | Los dos contenedores de las tareas V1 y V2 del kit de validación |

## Lo que no se verificó

- **El escaneo con un celular real**: en la PC no hay lector de QR. Se verificó que el enlace que va dentro del QR abre la pantalla
  con el contenedor elegido, con sesión y sin sesión (pasando por el login).
- La foto de una etiqueta pegada en un contenedor (o en la maqueta) la saca el equipo: es evidencia y no se genera.
