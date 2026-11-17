param([int]$Puerto = 8000)
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$phpProgram = Join-Path $PSScriptRoot '.tools\php84\php.exe'
if (-not (Test-Path -LiteralPath $phpProgram)) {
    $phpProgram = (Get-Command php -ErrorAction Stop).Source
}
& $phpProgram -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);'
if ($LASTEXITCODE -ne 0) { throw 'Se requiere PHP 8.3 o superior. Consulte docs/07-manuales/programa-local.md.' }
Write-Host "Amarilis Ambiental: http://127.0.0.1:$Puerto" -ForegroundColor Green
& $phpProgram artisan serve --host=127.0.0.1 --port=$Puerto
