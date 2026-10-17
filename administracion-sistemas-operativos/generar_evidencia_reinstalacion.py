"""PDF de la reinstalación documentada de SiGeRU en la VM Ubuntu."""

from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas


BASE = Path(__file__).resolve().parent
IMAGES = BASE / "capturas"
OUTPUT = BASE / "Evidencia_VM_Ubuntu_SiGeRU_2026-10-12.pdf"
W, H = 841.89, 595.28  # A4 apaisado
GREEN = colors.HexColor("#10674D")
DARK = colors.HexColor("#193129")
MUTED = colors.HexColor("#566B61")
PALE = colors.HexColor("#F1F6F1")
LINE = colors.HexColor("#D7E3D9")
GOLD = colors.HexColor("#DFB529")

pdfmetrics.registerFont(TTFont("Arial", r"C:\Windows\Fonts\arial.ttf"))
pdfmetrics.registerFont(TTFont("Arial-Bold", r"C:\Windows\Fonts\arialbd.ttf"))


def write(c, x, y, content, size=10, color=DARK, bold=False):
    c.setFillColor(color)
    c.setFont("Arial-Bold" if bold else "Arial", size)
    c.drawString(x, y, content)


def lines(c, x, y, content, size=10, leading=16, color=MUTED):
    for line in content:
        write(c, x, y, line, size, color)
        y -= leading


def shell(c, page_no, label):
    c.setFillColor(GREEN)
    c.rect(0, H - 9, W, 9, fill=1, stroke=0)
    write(c, 48, H - 39, "SiGeRU  /  Equipo COSMOS", 14, GREEN, True)
    write(c, 48, H - 59, label.upper(), 8.8, MUTED, True)
    c.setStrokeColor(LINE)
    c.line(48, 47, W - 48, 47)
    write(c, 48, 29, "Administración de Sistemas Operativos · Ubuntu Server 26.04.1 LTS · 12/10/2026", 8.2, MUTED)
    write(c, W - 80, 29, f"{page_no} / 9", 8.2, MUTED)


def framed_top(c, path, crop_height, x=60, y=82, max_w=722, max_h=359):
    """Encuadra la parte útil de un PNG real, sin modificar el archivo."""
    image = ImageReader(str(path))
    iw, ih = image.getSize()
    crop_height = min(crop_height, ih)
    scale = min(max_w / iw, max_h / crop_height)
    dw, dh = iw * scale, crop_height * scale
    ix, iy = x + (max_w - dw) / 2, y + (max_h - dh) / 2
    c.setFillColor(colors.white)
    c.setStrokeColor(LINE)
    c.roundRect(ix - 4, iy - 4, dw + 8, dh + 8, 5, fill=1, stroke=1)
    c.saveState()
    clip = c.beginPath()
    clip.rect(ix, iy, dw, dh)
    c.clipPath(clip, stroke=0, fill=0)
    c.drawImage(image, ix, iy + dh - ih * scale, iw * scale, ih * scale)
    c.restoreState()


def framed_center(c, path, scale, x=60, y=82, max_w=722, max_h=359):
    image = ImageReader(str(path))
    iw, ih = image.getSize()
    dw, dh = iw * scale, ih * scale
    c.setFillColor(colors.white)
    c.setStrokeColor(LINE)
    c.roundRect(x - 4, y - 4, max_w + 8, max_h + 8, 5, fill=1, stroke=1)
    c.saveState()
    clip = c.beginPath()
    clip.rect(x, y, max_w, max_h)
    c.clipPath(clip, stroke=0, fill=0)
    c.drawImage(image, x + (max_w - dw) / 2, y + (max_h - dh) / 2, dw, dh)
    c.restoreState()


def step_page(c, page_no, step, title, subtitle, caption, filename, crop_height):
    path = IMAGES / filename
    if not path.is_file():
        raise FileNotFoundError(path)
    shell(c, page_no, f"Paso {step} de 8")
    write(c, 48, 488, title, 21.5, DARK, True)
    write(c, 48, 463, subtitle, 10.5, MUTED)
    framed_top(c, path, crop_height)
    write(c, 61, 63, caption, 8.8, MUTED)
    c.showPage()


def cover(c):
    shell(c, 1, "Evidencia de despliegue")
    write(c, 48, 478, "Reinstalación paso a paso de SiGeRU", 28, DARK, True)
    write(c, 48, 447, "Capturas reales de la consola de Ubuntu y de la aplicación en funcionamiento", 12, MUTED)

    c.setFillColor(PALE)
    c.roundRect(48, 347, 746, 75, 10, fill=1, stroke=0)
    for x, heading, value in (
        (68, "MÁQUINA VIRTUAL", "sigeru-srv"),
        (306, "SISTEMA", "Ubuntu 26.04.1 LTS"),
        (594, "RESULTADO", "4 rutas: 200 OK"),
    ):
        write(c, x, 393, heading, 8.5, MUTED, True)
        write(c, x, 367, value, 13, GREEN, True)

    write(c, 48, 308, "Qué se hizo", 16, DARK, True)
    lines(c, 48, 284, [
        "1. Se identificó la VM y se comprobaron sus paquetes y servicios.",
        "2. Se exportó la versión que ya funcionaba y se transfirió por la PC anfitriona.",
        "3. Se guardó un respaldo, se extrajo el paquete y se reinstalaron los archivos.",
        "4. Se recargó Apache y se comprobaron la base, las rutas y las pantallas web.",
    ], 10.6, 23, DARK)

    c.setFillColor(colors.HexColor("#FFF8DE"))
    c.roundRect(48, 82, 746, 85, 8, fill=1, stroke=0)
    write(c, 64, 143, "ALCANCE", 9, DARK, True)
    lines(c, 64, 124, [
        "Se reinstalaron los archivos del proyecto sobre el Ubuntu ya instalado. Se conservaron",
        "el sistema operativo, la base MySQL y la configuración existente del servidor.",
        "La copia local de desarrollo tiene cambios sin confirmar y no fue sobrescrita.",
    ], 9.6, 15, DARK)
    c.showPage()


def main():
    c = canvas.Canvas(str(OUTPUT), pagesize=(W, H))
    c.setTitle("Reinstalación paso a paso de SiGeRU en Ubuntu Server")
    c.setAuthor("Equipo COSMOS")
    c.setSubject("Evidencias reales de VirtualBox y comprobación del despliegue")

    cover(c)
    step_page(c, 2, 1, "Identificar el servidor Ubuntu", "La VM se llama sigeru-srv y usa Ubuntu Server 26.04.1 LTS.",
              "Figura 1. Consola de VirtualBox: sistema, IP local y espacio disponible.",
              "04-paso-1-ubuntu.png", 520)
    step_page(c, 3, 2, "Comprobar los paquetes necesarios", "Apache, PHP, MySQL y SSH están instalados y sus servicios responden.",
              "Figura 2. Versiones reales de paquetes y servicios activos en Ubuntu.",
              "05-paso-2-paquetes-servicios.png", 720)
    step_page(c, 4, 3, "Recibir el paquete del proyecto", "El archivo pasó por la PC anfitriona y volvió a la VM con el mismo SHA-256.",
              "Figura 3. Archivo recibido, suma SHA-256 y contenido del paquete.",
              "06-paso-3-paquete-recibido.png", 795)
    step_page(c, 5, 4, "Respaldar y preparar la reinstalación", "Se guardó la instalación anterior y se extrajo una copia nueva del proyecto.",
              "Figura 4. Respaldo y extracción; ambos directorios ocupan 8,8 MB.",
              "07-paso-4-respaldo-extraccion.png", 650)
    step_page(c, 6, 5, "Reinstalar los archivos y recargar Apache", "La configuración pasó la prueba de sintaxis antes de recargar el servicio.",
              "Figura 5. Sincronización, permisos, Syntax OK y recarga de Apache.",
              "08-paso-5-reinstalacion-apache.png", 590)
    step_page(c, 7, 6, "Comprobar el resultado desde Ubuntu", "La base conserva 15 tablas y las cuatro rutas principales responden 200.",
              "Figura 6. MySQL, servicios activos y respuestas HTTP desde la propia VM.",
              "09-paso-6-verificacion-final.png", 710)
    step_page(c, 8, 7, "Abrir la portada desde el anfitrión", "http://127.0.0.1:8080/sigeru/landing/  ·  respuesta HTTP 200",
              "Figura 7. Portada de SiGeRU servida por la VM después de reinstalar.",
              "10-paso-7-portada-tras-reinstalar.png", 760)

    login = IMAGES / "11-paso-8-login-tras-reinstalar.png"
    if not login.is_file():
        raise FileNotFoundError(login)
    shell(c, 9, "Paso 8 de 8")
    write(c, 48, 488, "Abrir la pantalla de acceso", 21.5, DARK, True)
    write(c, 48, 463, "http://127.0.0.1:8080/sigeru/frontend-usuarios/login.html  ·  respuesta HTTP 200", 10.2, MUTED)
    framed_center(c, login, 0.72)
    write(c, 61, 63, "Figura 8. Acceso servido por la VM; no se realizó un inicio de sesión.", 8.8, MUTED)
    c.showPage()

    c.save()
    print(OUTPUT)


if __name__ == "__main__":
    main()
