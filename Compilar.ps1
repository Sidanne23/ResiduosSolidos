$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$nodeDirectory = Get-ChildItem -LiteralPath (Join-Path $PSScriptRoot '.tools\node24') -Directory -ErrorAction SilentlyContinue | Select-Object -First 1
if ($nodeDirectory) {
    $env:PATH = $nodeDirectory.FullName + ';' + $env:PATH
    $npmProgram = Join-Path $nodeDirectory.FullName 'npm.cmd'
} else {
    $npmProgram = (Get-Command npm.cmd -ErrorAction Stop).Source
}
& $npmProgram run build
if ($LASTEXITCODE -ne 0) { throw 'Falló la compilación de recursos.' }
