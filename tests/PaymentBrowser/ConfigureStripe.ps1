param([switch]$ReuseTestKeys)
$ErrorActionPreference = 'Stop'
$state = Join-Path ([Environment]::GetFolderPath('LocalApplicationData')) 'Temp\appointment-payment-browser-20261007'
if (!(Test-Path -LiteralPath $state)) { New-Item -ItemType Directory -Path $state | Out-Null }
$privatePath = Join-Path $state 'stripe-private.json'
# Private input stays on this machine, outside Git and command-line arguments.
if ($ReuseTestKeys) {
    $savedKeys = Get-Content -LiteralPath $privatePath -Raw | ConvertFrom-Json
    $public = $savedKeys.public
    $secret = $savedKeys.secret
    $savedKeys = $null
} else {
    Write-Host 'Dans Stripe Dashboard, sélectionner le bac à sable / mode TEST du compte de recette.'
    Write-Host 'Copier ses deux clés API ci-dessous. Ne jamais utiliser les clés live.'
    $public = [Net.NetworkCredential]::new('', (Read-Host 'Clé publique pk_test_ (saisie masquée)' -AsSecureString)).Password
    $secret = [Net.NetworkCredential]::new('', (Read-Host 'Clé secrète sk_test_ (saisie masquée)' -AsSecureString)).Password
}
if ($public -notmatch '^pk_test_[A-Za-z0-9]+$' -or $secret -notmatch '^sk_test_[A-Za-z0-9]+$') { throw 'Clés TEST attendues ; aucune connexion effectuée.' }
$values = @{ public=$public; secret=$secret; webhook='' }
if (!$ReuseTestKeys) {
    [IO.File]::WriteAllText($privatePath, ($values | ConvertTo-Json), [Text.UTF8Encoding]::new($false))
    $acl = Get-Acl -LiteralPath $privatePath
    $acl.SetAccessRuleProtection($true, $false)
    $acl.SetAccessRule([Security.AccessControl.FileSystemAccessRule]::new([Security.Principal.WindowsIdentity]::GetCurrent().Name, 'FullControl', 'Allow'))
    Set-Acl -LiteralPath $privatePath -AclObject $acl
}
$previousKey = $env:STRIPE_API_KEY
try {
    $env:STRIPE_API_KEY = $secret
    Write-Host 'Listener TEST vers http://127.0.0.1:8097/stripe/webhook. Laisser cette fenêtre ouverte.'
    # No --live, no configured remote endpoint. Same TEST key as the HTTP app.
    & stripe --config (Join-Path $state 'stripe.toml') --project-name appointment-payment-recette listen --skip-update --events payment_intent.succeeded --forward-to http://127.0.0.1:8097/stripe/webhook 2>&1 | ForEach-Object {
        $line = $_.ToString()
        if ($line -match 'whsec_[A-Za-z0-9]+') {
            $values.webhook = $Matches[0]
            [IO.File]::WriteAllText($privatePath, ($values | ConvertTo-Json), [Text.UTF8Encoding]::new($false))
            Write-Host 'Secret du listener enregistré sans affichage. Application prête pour Stripe TEST.'
        } else {
            Write-Host ([regex]::Replace($line, '(?:[spr]k_(?:test|live)_|whsec_)[A-Za-z0-9]+', '[masqué]'))
        }
    }
} finally { $env:STRIPE_API_KEY = $previousKey; $secret = $null; $values = $null }
