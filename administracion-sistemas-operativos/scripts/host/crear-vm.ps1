# SiGeRU - Crea e instala de forma desatendida la VM sigeru-srv (Administracion de S.O.)
#
# Corre en la PC anfitriona (Windows), no en el servidor. Usa VBoxManage para crear la
# maquina con los parametros del plan (administracion-sistemas-operativos/plan-de-instalacion.md,
# seccion 3) e instala Ubuntu Server sin intervencion manual: en vez de las 16 capturas del
# instalador interactivo, el "manual de instalacion" documenta el autoinstall (user-data)
# como archivo de respuesta, que es el equivalente real para una instalacion desatendida.
#
# Autoinstall (cloud-init/Subiquity) se sirve por HTTP local al puerto 8080 porque
# "VBoxManage unattended install" necesita inyectar el parametro de arranque
# "ds=nocloud-net;s=http://..." y el datasource nocloud-net de Ubuntu lo lee por red;
# 10.0.2.2 es la IP fija con la que cualquier VM en modo NAT de VirtualBox ve a su anfitrion.
#
# Uso: abrir PowerShell en esta carpeta y correr .\crear-vm.ps1
# Requiere: VirtualBox instalado, la ISO ya descargada y verificada en vm-ubuntu/,
# y el user-data real (con el hash de la contrasena) en la carpeta indicada abajo.

$ErrorActionPreference = "Stop"

$VBoxManage = "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe"
$PendriveRoot = Resolve-Path (Join-Path $PSScriptRoot "..\..\..\..")
$Iso = Join-Path $PendriveRoot "vm-ubuntu\ubuntu-26.04.1-live-server-amd64.iso"
# Carpeta con el user-data REAL (con el hash de la contrasena real, no la .ejemplo del repo)
$AutoinstallDir = "C:\Users\57734545\AppData\Local\Temp\claude\D--SIGERU-2026\befc5fac-232f-4722-a9e5-09545d645850\scratchpad\vbox"
$VmName = "sigeru-srv"
$VmFolder = Join-Path $PendriveRoot "vm-ubuntu\vm-sigeru-srv"
$DiskPath = Join-Path $VmFolder "sigeru-srv.vdi"

if (-not (Test-Path $VBoxManage)) { throw "VirtualBox no esta instalado (no se encuentra VBoxManage.exe)" }
if (-not (Test-Path $Iso)) { throw "No esta la ISO en $Iso" }
if (-not (Test-Path (Join-Path $AutoinstallDir "user-data"))) { throw "Falta el user-data real en $AutoinstallDir" }

New-Item -ItemType Directory -Force -Path $VmFolder | Out-Null

Write-Output "1/7 - Sirviendo autoinstall por HTTP en :8080 (para que la VM lo lea via 10.0.2.2)"
$PythonExe = Join-Path $PendriveRoot "herramientas\python\python.exe"
if (-not (Test-Path $PythonExe)) { throw "No esta el Python portable en $PythonExe" }
$httpProc = Start-Process -FilePath $PythonExe -ArgumentList "-m","http.server","8080","--bind","0.0.0.0","--directory",$AutoinstallDir -WindowStyle Hidden -PassThru
Start-Sleep -Seconds 2
Write-Output "   Servidor HTTP arriba (PID $($httpProc.Id)). Detenerlo despues con: Stop-Process -Id $($httpProc.Id)"

Write-Output "2/7 - Creando la VM $VmName"
& $VBoxManage createvm --name $VmName --ostype "Ubuntu_64" --basefolder $VmFolder --register

Write-Output "3/7 - CPU, memoria y red (NAT + reenvio de puerto SSH 2222->22)"
& $VBoxManage modifyvm $VmName --cpus 2 --memory 2048 --vram 16 --nic1 nat --natpf1 "sigeru-ssh,tcp,,2222,,22" --boot1 dvd --boot2 disk --audio-driver none

Write-Output "4/7 - Disco de 20 GB dinamico"
& $VBoxManage createmedium disk --filename $DiskPath --size 20000 --variant Standard
& $VBoxManage storagectl $VmName --name "SATA" --add sata --bootable on --hostiocache off
& $VBoxManage storageattach $VmName --storagectl "SATA" --port 0 --device 0 --type hdd --medium $DiskPath

Write-Output "5/7 - Adjuntando la ISO de Ubuntu Server 26.04.1"
& $VBoxManage storagectl $VmName --name "IDE" --add ide
& $VBoxManage storageattach $VmName --storagectl "IDE" --port 0 --device 0 --type dvddrive --medium $Iso

Write-Output "6/7 - Instalacion desatendida (autoinstall via HTTP). Esto arranca la VM headless."
& $VBoxManage unattended install $VmName `
    --iso=$Iso `
    --ostype="Ubuntu_64" `
    --extra-install-kernel-parameters="autoinstall ds=nocloud-net;s=http://10.0.2.2:8080/" `
    --start-vm=headless

Write-Output "7/7 - Listo. La instalacion sigue en marcha dentro de la VM (15-30 min)."
Write-Output "Revisar avance con: VBoxManage controlvm $VmName screenshotpng captura.png"
Write-Output "Cuando termine y reinicie, probar: ssh -p 2222 sigeru@127.0.0.1"
