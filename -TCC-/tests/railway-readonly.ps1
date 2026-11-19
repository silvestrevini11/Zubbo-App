param(
    [string]$BaseUrl = 'https://zubbo-app-demo.up.railway.app',
    # Somente a origem de demo.02 conferida no painel Railway; nenhuma descoberta automática.
    [string]$ConfirmedDemo02Origin = ''
)
$ErrorActionPreference = 'Stop'
$allowedOrigins = @('https://zubbo-app-demo.up.railway.app')
if ($ConfirmedDemo02Origin -ne '') {
    $confirmed = $null
    if (-not [Uri]::TryCreate($ConfirmedDemo02Origin, [UriKind]::Absolute, [ref]$confirmed) -or
        $confirmed.Scheme -ne 'https' -or $confirmed.Host -notmatch '^[a-z0-9-]+\.up\.railway\.app$' -or
        $confirmed.UserInfo -ne '' -or -not $confirmed.IsDefaultPort -or
        $confirmed.AbsolutePath -ne '/' -or $confirmed.Query -ne '' -or $confirmed.Fragment -ne '') {
        throw 'ConfirmedDemo02Origin deve ser uma origem HTTPS Railway confirmada, sem caminho ou credenciais.'
    }
    $allowedOrigins += $confirmed.GetLeftPart([UriPartial]::Authority)
}
$BaseUrl = $BaseUrl.TrimEnd('/')
if ($BaseUrl -cnotin $allowedOrigins) {
    throw 'BaseUrl não autorizada. Informe o domínio de demo.02 também em ConfirmedDemo02Origin após conferi-lo no Railway.'
}
$checks = @(
    @('/',302), @('/health.php',200), @('/app/views/auth/login.php',200),
    @('/public/css/style.css',200), @('/sw.js',200), @('/public/manifest.php',200),
    @('/config/database.php',404), @('/database/scripts/criacaotables.sql',404),
    @('/vendor/autoload.php',404), @('/.git/config',404), @('/app/views/admin/_auth.php',404),
    @('/README.md',404), @('/docs/AMBIENTE_DEMO_02.md',404), @('/docs/PLANO_DEMO_02.md',404),
    @('/composer.json',404), @('/composer.lock',404), @('/.env',404),
    @('/tests/environment-readiness.php',404), @('/tests/railway-readonly.ps1',404)
)
$failures = 0
foreach ($check in $checks) {
    $status = & curl.exe --silent --show-error --proto '=https' --max-time 20 --output NUL --write-out '%{http_code}' "$BaseUrl$($check[0])"
    $ok = $LASTEXITCODE -eq 0 -and $status -eq [string]$check[1]
    if (!$ok) { $failures++ }
    [pscustomobject]@{Rota=$check[0]; Esperado=$check[1]; Recebido=$status; OK=$ok} | Format-Table -HideTableHeaders
}
Write-Output "$($checks.Count) consultas GET; $failures divergências. Nenhum formulário enviado."
Write-Output 'HTTP não comprova acesso autenticado, SHA, autodeploys, banco, volume ou configuração Railway.'
if ($failures -gt 0) { exit 1 }
exit 0
