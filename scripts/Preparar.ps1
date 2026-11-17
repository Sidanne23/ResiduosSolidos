# Preparación local reproducible. No sustituye PHP/Node/MySQL de Laragon.
$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
New-Item -ItemType Directory -Force '.tools' | Out-Null
if (-not (Test-Path '.tools\php84\php.exe')) {
    $release = (Invoke-RestMethod 'https://windows.php.net/downloads/releases/releases.json').'8.4'.'ts-vs17-x64'.zip
    Invoke-WebRequest ('https://windows.php.net/downloads/releases/' + $release.path) -OutFile '.tools\php.zip'
    if ((Get-FileHash '.tools\php.zip' -Algorithm SHA256).Hash.ToLower() -ne $release.sha256) { throw 'Huella PHP inválida' }
    Expand-Archive '.tools\php.zip' -DestinationPath '.tools\php84' -Force
    Copy-Item '.tools\php84\php.ini-development' '.tools\php84\php.ini'
    $ini = Get-Content '.tools\php84\php.ini' -Raw
    foreach ($extension in @('curl','fileinfo','gd','intl','mbstring','openssl','pdo_mysql','pdo_sqlite','sqlite3','zip')) { $ini = $ini.Replace(';extension='+$extension, 'extension='+$extension) }
    $ini = $ini.Replace(';extension_dir = "ext"', 'extension_dir = "'+(Resolve-Path '.tools\php84\ext').Path+'"')
    $ini += "`nzend.assertions=1`ndate.timezone=UTC`nmemory_limit=512M`n"
    Set-Content '.tools\php84\php.ini' $ini
}
$phpProgram = Join-Path (Get-Location) '.tools\php84\php.exe'
if (-not (Test-Path '.tools\php84\cacert.pem')) {
    Invoke-WebRequest 'https://curl.se/ca/cacert.pem' -OutFile '.tools\php84\cacert.pem'
    $response = Invoke-WebRequest 'https://curl.se/ca/cacert.pem.sha256'
    $hashText = if ($response.Content -is [byte[]]) { [Text.Encoding]::UTF8.GetString($response.Content) } else { [string]$response.Content }
    $expected = ($hashText.Trim() -split '\s+')[0]
    if ((Get-FileHash '.tools\php84\cacert.pem' -Algorithm SHA256).Hash.ToLower() -ne $expected) { throw 'Huella de certificados CA inválida' }
    $caPath = (Resolve-Path '.tools\php84\cacert.pem').Path
    Add-Content '.tools\php84\php.ini' "`ncurl.cainfo=`"$caPath`"`nopenssl.cafile=`"$caPath`""
}
if (-not (Test-Path '.tools\composer.phar')) {
    Invoke-WebRequest 'https://getcomposer.org/installer' -OutFile '.tools\composer-setup.php'
    $response = Invoke-WebRequest 'https://composer.github.io/installer.sig'
    $signature = if ($response.Content -is [byte[]]) { [Text.Encoding]::UTF8.GetString($response.Content).Trim() } else { ([string]$response.Content).Trim() }
    if ((Get-FileHash '.tools\composer-setup.php' -Algorithm SHA384).Hash.ToLower() -ne $signature) { throw 'Firma Composer inválida' }
    & $phpProgram '.tools\composer-setup.php' --install-dir=.tools --filename=composer.phar
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo instalar Composer.' }
}
& $phpProgram '.tools\composer.phar' install --no-interaction
if ($LASTEXITCODE -ne 0) { throw 'Falló composer install.' }
& $phpProgram scripts/setup-local.php
if ($LASTEXITCODE -ne 0) { throw 'Revise MySQL en Laragon y las credenciales locales de .env.' }
& $phpProgram artisan config:clear
& $phpProgram artisan migrate --force
if ($LASTEXITCODE -ne 0) { throw 'Fallaron las migraciones.' }
if (-not (Test-Path '.tools\node24')) {
    $response = Invoke-WebRequest 'https://nodejs.org/dist/latest-v24.x/SHASUMS256.txt'
    $checksums = if ($response.Content -is [byte[]]) { [Text.Encoding]::UTF8.GetString($response.Content) } else { [string]$response.Content }
    $entry = ($checksums -split "`n" | Where-Object { $_ -match 'node-v24\.[\d.]+-win-x64\.zip$' } | Select-Object -First 1).Trim() -split '\s+'
    if ($entry.Count -ne 2) { throw 'No se pudo obtener la huella de Node 24.' }
    Invoke-WebRequest ('https://nodejs.org/dist/latest-v24.x/'+$entry[1]) -OutFile '.tools\node24.zip'
    if ((Get-FileHash '.tools\node24.zip' -Algorithm SHA256).Hash.ToLower() -ne $entry[0]) { throw 'Huella Node inválida' }
    Expand-Archive '.tools\node24.zip' -DestinationPath '.tools\node24' -Force
}
$nodeDirectory = Get-ChildItem '.tools\node24' -Directory | Select-Object -First 1
$env:PATH = $nodeDirectory.FullName + ';' + $env:PATH
& (Join-Path $nodeDirectory.FullName 'npm.cmd') ci --no-fund
if ($LASTEXITCODE -ne 0) { throw 'Falló npm ci.' }
& '.\Compilar.ps1'
Write-Host 'Preparación terminada. Continúe con evidence:load y app:admin según programa-local.md.' -ForegroundColor Green
