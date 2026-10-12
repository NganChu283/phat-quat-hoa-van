$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$phpExecutable = 'C:\xampp\php\php.exe'
if (-not (Test-Path -LiteralPath $phpExecutable)) {
    $phpExecutable = (Get-Command php -ErrorAction Stop).Source
}
$sessionDirectory = Join-Path $PSScriptRoot '.local\sessions'
New-Item -ItemType Directory -Force -Path $sessionDirectory | Out-Null
$phpArguments = @('-d', "session.save_path=$sessionDirectory")
$modules = & $phpExecutable -m
if ($modules -notcontains 'gd' -and (Test-Path -LiteralPath (Join-Path (Split-Path $phpExecutable) 'ext\php_gd.dll'))) {
    $phpArguments += @('-d', 'extension=gd')
}
& $phpExecutable @phpArguments -S 127.0.0.1:8000 -t public app/router.php
