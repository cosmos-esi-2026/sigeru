# Inicia XAMPP portable con Gmail sin guardar la contrasena en archivos.
$ErrorActionPreference = 'Stop'
$xampp = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\herramientas\xampp')).Path
$proyecto = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$plantilla = Join-Path $xampp '..\plantillas\sigeru.conf.plantilla'
$configApache = Join-Path $xampp 'apache\conf\extra\sigeru.conf'
$apacheExe = Join-Path $xampp 'apache\bin\httpd.exe'
$mysqlExe = Join-Path $xampp 'mysql\bin\mysqld.exe'
$mysqlIni = Join-Path $xampp 'mysql\bin\my.ini'

foreach ($ruta in @($plantilla, $apacheExe, $mysqlExe, $mysqlIni)) {
    if (-not (Test-Path -LiteralPath $ruta)) {
        throw "No se encontro el archivo necesario: $ruta"
    }
}

$activos = Get-Process -Name httpd, mysqld -ErrorAction SilentlyContinue |
    Where-Object { $_.Path -like "$xampp\*" }
if ($activos) {
    throw 'XAMPP ya esta activo. Ejecuta herramientas\xampp\detener-xampp.cmd antes de iniciar el correo.'
}

$segura = Read-Host 'Contrasena de aplicacion de Gmail (no se guarda)' -AsSecureString
$credencial = [PSCredential]::new('sigeruesi@gmail.com', $segura)
$env:SIGERU_SMTP_URL = 'smtps://smtp.gmail.com:465'
$env:SIGERU_SMTP_USER = 'sigeruesi@gmail.com'
$env:SIGERU_MAIL_FROM = 'sigeruesi@gmail.com'
$env:SIGERU_PUBLIC_URL = 'http://localhost/sigeru'
$env:SIGERU_SMTP_PASS = $credencial.GetNetworkCredential().Password

try {
    $rutaApache = $proyecto.Replace('\', '/')
    $contenido = (Get-Content -LiteralPath $plantilla -Raw) -replace '__RUTA__', $rutaApache
    Set-Content -LiteralPath $configApache -Value $contenido -NoNewline

    $apache = Start-Process -FilePath $apacheExe -ArgumentList ('-d "' + (Join-Path $xampp 'apache') + '"') -WorkingDirectory $xampp -WindowStyle Hidden -PassThru
    $mysql = Start-Process -FilePath $mysqlExe -ArgumentList ('--defaults-file="' + $mysqlIni + '" --standalone') -WorkingDirectory $xampp -WindowStyle Hidden -PassThru
    Start-Sleep -Seconds 2
    $apache.Refresh()
    $mysql.Refresh()
    if ($apache.HasExited -or $mysql.HasExited) {
        throw "XAMPP no quedo activo. Apache activo: $(-not $apache.HasExited); MySQL activo: $(-not $mysql.HasExited)."
    }
    Write-Host 'XAMPP iniciado con correo. Pagina: http://localhost/sigeru/landing/'
} finally {
    foreach ($nombre in @('SIGERU_SMTP_URL', 'SIGERU_SMTP_USER', 'SIGERU_MAIL_FROM',
                          'SIGERU_PUBLIC_URL', 'SIGERU_SMTP_PASS')) {
        Remove-Item -Path "Env:$nombre" -ErrorAction SilentlyContinue
    }
}
