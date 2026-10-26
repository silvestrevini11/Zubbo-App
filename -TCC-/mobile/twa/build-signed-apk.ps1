param(
    [string] $SdkPath = $env:ANDROID_HOME,
    [string] $JdkPath = $env:JAVA_HOME
)

$ErrorActionPreference = 'Stop'
$here = $PSScriptRoot
$android = Join-Path $here 'android'
$keystore = Join-Path $here '.local/zubbo-release.keystore'
$passwordFile = Join-Path $here '.local/signing-password.txt'
if (-not $SdkPath -or -not $JdkPath) { throw 'Defina ANDROID_HOME e JAVA_HOME ou informe -SdkPath e -JdkPath.' }
if (-not (Test-Path -LiteralPath $keystore) -or -not (Test-Path -LiteralPath $passwordFile)) {
    throw 'Chave ou senha local ausente. Restaure o backup protegido em twa/.local/.'
}

$tools = Get-ChildItem -LiteralPath (Join-Path $SdkPath 'build-tools') -Directory |
    Where-Object { (Test-Path (Join-Path $_.FullName 'zipalign.exe')) -and
                   (Test-Path (Join-Path $_.FullName 'lib/apksigner.jar')) } |
    Sort-Object { [version] $_.Name } -Descending |
    Select-Object -First 1
if (-not $tools) { throw 'Android SDK Build Tools com zipalign e apksigner não encontrado.' }

$manifest = Get-Content -Raw -LiteralPath (Join-Path $android 'twa-manifest.json') | ConvertFrom-Json
$aligned = Join-Path $android 'app-release-unsigned-aligned.apk'
$unsigned = Join-Path $android 'app/build/outputs/apk/release/app-release-unsigned.apk'
$apk = Join-Path $android ("Zubbo-v{0}.apk" -f $manifest.appVersionCode)
$java = Join-Path $JdkPath 'bin/java.exe'
$gradle = Join-Path $android 'gradlew.bat'

Push-Location $android
try {
    $env:JAVA_HOME = $JdkPath
    $env:ANDROID_HOME = $SdkPath
    & $gradle assembleRelease --no-daemon --console=plain
    if ($LASTEXITCODE -ne 0) { throw 'Compilação Gradle falhou.' }
    & (Join-Path $tools.FullName 'zipalign.exe') -f -p 4 $unsigned $aligned
    if ($LASTEXITCODE -ne 0) { throw 'Alinhamento do APK falhou.' }

    $env:ZUBBO_SIGN_PASSWORD = (Get-Content -Raw -LiteralPath $passwordFile).Trim()
    try {
        & $java -jar (Join-Path $tools.FullName 'lib/apksigner.jar') sign `
            --ks $keystore --ks-key-alias zubbo `
            --ks-pass env:ZUBBO_SIGN_PASSWORD --key-pass env:ZUBBO_SIGN_PASSWORD `
            --out $apk $aligned
        if ($LASTEXITCODE -ne 0) { throw 'Assinatura do APK falhou.' }
    } finally {
        Remove-Item Env:ZUBBO_SIGN_PASSWORD -ErrorAction SilentlyContinue
    }
    & $java -jar (Join-Path $tools.FullName 'lib/apksigner.jar') verify --print-certs $apk
    if ($LASTEXITCODE -ne 0) { throw 'Verificação da assinatura falhou.' }
    Write-Output "APK pronto: $apk"
} finally {
    Pop-Location
}
