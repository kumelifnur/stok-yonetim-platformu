$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot

$phpExe = (Get-Command php -ErrorAction Stop).Source
$phpDirectory = Split-Path $phpExe
$phpExt = Join-Path $phpDirectory 'ext'
$baseIni = Join-Path $phpDirectory 'php.ini'
$localIni = Join-Path $PSScriptRoot '.php-local.ini'

if (-not (Test-Path -LiteralPath $baseIni)) {
    throw "PHP configuration file was not found: $baseIni"
}

Copy-Item -LiteralPath $baseIni -Destination $localIni -Force
@(
    "extension_dir=`"$phpExt`""
    'extension=php_pdo_sqlite.dll'
    'extension=php_sqlite3.dll'
) | Add-Content -LiteralPath $localIni -Encoding Ascii
$env:PHPRC = $localIni

if (-not (Test-Path -LiteralPath (Join-Path $PSScriptRoot '.env'))) {
    Copy-Item -LiteralPath (Join-Path $PSScriptRoot '.env.example') -Destination (Join-Path $PSScriptRoot '.env')
}

$envFile = Get-Content -LiteralPath (Join-Path $PSScriptRoot '.env') -Raw
if ($envFile -notmatch '(?m)^APP_KEY=.+$') {
    & $phpExe artisan key:generate
}

$databaseFile = Join-Path $PSScriptRoot 'database\database.sqlite'
if (-not (Test-Path -LiteralPath $databaseFile)) {
    New-Item -ItemType File -Path $databaseFile | Out-Null
}

& $phpExe artisan migrate --force
if ($LASTEXITCODE -ne 0) { throw 'Database migration failed.' }

& $phpExe artisan serve --host=127.0.0.1 --port=8000
