# Correctif du contact France — publication et déploiement préparés le 4 novembre 2026

## Clôture du correctif et du chantier France — 4 novembre 2026

**Publication et présence du correctif en production confirmées selon les sorties et contrôles rapportés par l’utilisateur.** Publications réussies : **StephaneBouret/appointment-sym, main : 98d572aff3d8e7836660178b745b6c694673c31a** ; **ftuncq/sym-appointment, main et livraison/ga4-france-20261029 : 1c2a93ee2b2ff2813d97292cae4f8f8e44f4c5c9**.

Production rapportée : **/home/u664242226/domains/francetuncq.fr/public_html**, branche **main**, HEAD **1c2a93ee2b2ff2813d97292cae4f8f8e44f4c5c9** ; `templates/legal/privacy.html.twig` conforme, courriel public et `mailto:` **contact@luniversdesnombres.com**. Analytics reste actif : **GA4_ENABLED=1 / G-MWZX1GYXZE**. Aucun asset supplémentaire nécessaire ; les défauts locaux restent à **GA4_ENABLED=0**.

Le périmètre livraison, activation, correctif du contact, point administratif et réception dans les rapports standards est **clôturé**. Voir le [bilan de clôture, les relevés et les réserves conservées](analytics-ga4-production-20261030.md#cloture-france-20261104). Le contrôle des rapports auparavant non effectué est désormais effectué sur les preuves rapportées ; aucune attribution individuelle, déduplication, réservation finalisée ou certification juridique globale n’en découle. L’absence historique des essais automatisés reste inexpliquée et les limites du retrait accepté restent applicables.

**Procédure de préparation conservée ci-dessous comme historique, à ne pas rejouer pour cette clôture.** Ses anciens HEAD, le SHA alors futur et les contrôles alors à faire décrivent la préparation. Elle n’est pas un journal d’exécution : aucune méthode ni heure de déploiement n’est déduite des commandes proposées. Aucune commande distante, publication ou opération de déploiement n’a été exécutée pour cette consignation ; le dépannage SSH GitHub reste suivi dans un autre flux.

**Commandes à exécuter seulement lors d’une intervention ultérieure autorisée. Rien ci-dessous n’a été exécuté sur GitHub ou sur le serveur pendant la préparation.** Voir la [rectification administrative et les contrôles locaux](analytics-ga4-rectification-20261104.md).

La production est déjà active. Conserver sa configuration persistante **GA4_ENABLED=1 / G-MWZX1GYXZE** ; aucun changement d’environnement, de consentement, de modules ou d’assets dans ce correctif. Ne pas rejouer la procédure initiale de livraison désactivée, ni installer l’ancien ZIP. Les défauts locaux restent à 0.

## Périmètre et état connu

- HEAD principal local et `origin/main` observé en lecture seule le 4 novembre : `c4ea32bc10d15c9aede05a700f8bc3cfe0f610c8`.
- HEAD de livraison et références `france/main` / `france/livraison/ga4-france-20261029` observées : `d78b3a6b06352414df0162be8165738a8c1a1d0c`.
- Dernier HEAD serveur rapporté déployé : `29bcd686bc1dc5e7dfdccbfb937cead80c19c6bc`. Le HEAD effectif doit être relu. `d78b3a6…` est également admis ci-dessous s’il a été déployé depuis : sa différence est uniquement documentaire. Tout autre état nécessite un rapprochement préalable.
- **Seul fichier applicatif à livrer : `templates/legal/privacy.html.twig`.** Les dix documents du commit France ci-dessous sont livrés par Git dans `docs/`. Aucun asset, archive historique, preuve brute, dépendance, fichier privé ou fichier de configuration à transférer. Aucun SCP nécessaire dans le chemin Git proposé.
- PHP CLI serveur confirmé par l’utilisateur : `/opt/alt/php84/usr/bin/php` (8.4.27). PHP web/OPcache ont été vérifiés le 30 octobre ; aucune nouvelle inspection directe ici.

## 1. Futur commit France et publication PowerShell

Exécuter seulement après revue des modifications présentes. Ces commandes créent un **nouveau** commit, sans amendement ni fusion du principal. L’index doit être vide ; aucune différence étrangère au lot n’est admise. Si une référence distante a évolué, arrêter sans forcer. Les changements du principal pourront faire l’objet d’un commit distinct sur `main` ; ne pas les fusionner dans France.

```powershell
$ErrorActionPreference = 'Stop'
$Repo = 'C:\Formations\appointment-sym\var\analytics-git\france-20261029'
$Base = 'd78b3a6b06352414df0162be8165738a8c1a1d0c'
$Files = @(
  'templates/legal/privacy.html.twig',
  'docs/analytics-ga4.md',
  'docs/analytics-ga4-controle-rapports.md',
  'docs/analytics-ga4-france-livraison-20261029.md',
  'docs/analytics-ga4-livraison-20261029.md',
  'docs/analytics-ga4-mentions-france-brouillon.md',
  'docs/analytics-ga4-production-20261030.md',
  'docs/analytics-ga4-publication-ssh.md',
  'docs/analytics-ga4-retrait-onglets.md',
  'docs/analytics-ga4-rectification-20261104.md',
  'docs/analytics-ga4-correctif-contact-publication.md'
)
function G { param([string[]]$A)
  $result = @(& git -C $Repo @A)
  if ($LASTEXITCODE -ne 0) { throw "Git a échoué : $($A -join ' ')" }
  return $result
}
function Assert-FranceRemote {
  $refs = @(G -A @('ls-remote','--exit-code','--refs','france','refs/heads/main','refs/heads/livraison/ga4-france-20261029'))
  $expected = @("$Base`trefs/heads/main", "$Base`trefs/heads/livraison/ga4-france-20261029")
  if (Compare-Object ($refs | Sort-Object) ($expected | Sort-Object)) { throw 'Branches distantes différentes : arrêt.' }
}
if ((G -A @('branch','--show-current')) -cne 'livraison/ga4-france-20261029') { throw 'Branche inattendue.' }
if ((G -A @('rev-parse','HEAD')) -cne $Base) { throw 'HEAD a évolué.' }
foreach ($argsUrl in @(@('remote','get-url','--all','france'), @('remote','get-url','--push','--all','france'))) {
  $urls = @(G -A $argsUrl)
  if ($urls.Count -ne 1 -or $urls[0] -cne 'https://github.com/ftuncq/sym-appointment.git') { throw 'Remote inattendu.' }
}
G -A @('diff','--cached','--quiet')
$changed = @(G -A @('diff','--name-only')) + @(G -A @('ls-files','--others','--exclude-standard'))
if (Compare-Object ($Files | Sort-Object) ($changed | Sort-Object)) { throw 'Périmètre local inattendu.' }
if ((Get-FileHash -Algorithm SHA256 -LiteralPath "$Repo\templates\legal\privacy.html.twig").Hash.ToLowerInvariant() -ne '3d8b25bc72ed76e21bdd80f95c747779017305d6cf611ef8ada1dc67fd8f88ac') { throw 'Template différent du correctif vérifié.' }
Assert-FranceRemote
G -A @('diff','--check')
G -A (@('add','--') + $Files)
$staged = @(G -A @('diff','--cached','--name-only'))
if (Compare-Object ($Files | Sort-Object) ($staged | Sort-Object)) { throw 'Index inattendu : arrêt sans reset.' }
G -A @('diff','--cached','--check')
G -A @('diff','--cached','--stat')
G -A @('commit','-m','fix: rectifier le contact France et clore le point administratif GA4')
$Release = [string](G -A @('rev-parse','HEAD'))
if ((G -A @('rev-parse',"$Release^")) -cne $Base) { throw 'Ascendance inattendue.' }
G -A @('merge-base','--is-ancestor',$Base,$Release)
Assert-FranceRemote
G -A @('push','--atomic','france',"${Release}:refs/heads/livraison/ga4-france-20261029","${Release}:refs/heads/main")
$published = @(G -A @('ls-remote','--exit-code','--refs','france','refs/heads/main','refs/heads/livraison/ga4-france-20261029'))
$expectedPublished = @("$Release`trefs/heads/main", "$Release`trefs/heads/livraison/ga4-france-20261029")
if (Compare-Object ($published | Sort-Object) ($expectedPublished | Sort-Object)) { throw 'Publication à vérifier : ne pas déployer.' }
Write-Host "SHA exact du correctif à reporter côté serveur : $Release"
```

La poussée normale refuse une évolution concurrente non compatible avec l’avance rapide ; les deux mises à jour France sont atomiques. Aucun repli forcé. Si le commit est créé mais la publication refusée, conserver le commit et rapprocher la référence distante : ne pas relancer aveuglément le bloc, qui attend le HEAD antérieur. Le SHA du correctif n’existe pas pendant cette préparation ; ne pas utiliser celui d’une ancienne livraison à sa place. Ne pas modifier les anciens `release-sha.txt` ou récépissés.

## 2. Connexion et contrôles serveur ultérieurs

Depuis PowerShell, uniquement après autorisation :

```powershell
ssh -p 65002 u664242226@147.93.93.52
```

Dans le shell Bash distant, remplacer la valeur `RELEASE` par le SHA de 40 caractères affiché après publication. Ne pas afficher les fichiers privés. Arrêter si les contrôles divergent ; ne pas modifier l’état local du serveur pour les faire passer.

```bash
set -euo pipefail
umask 077
APP=/home/u664242226/domains/francetuncq.fr/public_html
PHP_BIN=/opt/alt/php84/usr/bin/php
RELEASE=REMPLACER_PAR_LE_SHA_DU_NOUVEAU_COMMIT
[[ "$RELEASE" =~ ^[0-9a-f]{40}$ ]]
cd "$APP"
test "$(git rev-parse --show-toplevel)" = "$APP"
test "$(git branch --show-current)" = main
test "$(git remote get-url origin)" = https://github.com/ftuncq/sym-appointment.git
BEFORE=$(git rev-parse HEAD)
case "$BEFORE" in
  29bcd686bc1dc5e7dfdccbfb937cead80c19c6bc|d78b3a6b06352414df0162be8165738a8c1a1d0c) ;;
  *) echo 'HEAD serveur à rapprocher : arrêt.' >&2; exit 1 ;;
esac
git status --short --untracked-files=all
git diff --cached --quiet
git diff --quiet -- . ':!composer.json'
"$PHP_BIN" -v
"$PHP_BIN" <<'PHP'
<?php
$base = shell_exec('git show HEAD:composer.json');
if (!is_string($base) || !in_array(file_get_contents('composer.json'), [$base, $base."\n", $base."\r\n"], true)) {
    fwrite(STDERR, "Modification composer.json inattendue : arrêt.\n"); exit(1);
}
PHP
git fetch origin main
test "$(git rev-parse origin/main)" = "$RELEASE"
test "$(git rev-parse "$RELEASE^")" = d78b3a6b06352414df0162be8165738a8c1a1d0c
git merge-base --is-ancestor "$BEFORE" "$RELEASE"
test -z "$(git diff --name-only "$BEFORE" "$RELEASE" -- . ':!docs/**' ':!templates/legal/privacy.html.twig')"
git diff --stat "$BEFORE" "$RELEASE"
git diff "$BEFORE" "$RELEASE" -- templates/legal/privacy.html.twig
test -f .env.local
test -d /home/u664242226/private-deployments
STAGE=$(mktemp -d /home/u664242226/private-deployments/ga4-contact-20261104-XXXXXX)
chmod 700 "$STAGE"
printf '%s\n' "$BEFORE" > "$STAGE/before.txt"
printf '%s\n' "$RELEASE" > "$STAGE/release.txt"
printf '%s\n' "$STAGE"  # conserver ce chemin pour une récupération indépendante
cp templates/legal/privacy.html.twig "$STAGE/privacy.before.twig"
cp composer.json "$STAGE/composer.before.json"
cp .env.local "$STAGE/env.local.before"
sha256sum composer.json .env.local > "$STAGE/preserved.sha256"
if test -f .env.local.php; then
  cp .env.local.php "$STAGE/env.local.php.before"
  sha256sum .env.local.php >> "$STAGE/preserved.sha256"
fi
chmod 600 "$STAGE"/*
git show "$RELEASE:templates/legal/privacy.html.twig" > "$STAGE/privacy.candidate.twig"
printf '%s  %s\n' 3d8b25bc72ed76e21bdd80f95c747779017305d6cf611ef8ada1dc67fd8f88ac "$STAGE/privacy.candidate.twig" | sha256sum -c -
APP_ENV=prod APP_DEBUG=0 "$PHP_BIN" bin/console lint:twig "$STAGE/privacy.candidate.twig"
```

Les fichiers non suivis déjà connus (.htaccess, robots, journaux, images) restent en place. Examiner la liste, sans lire les journaux ni leur contenu personnel. Tout ajout inattendu ou collision avec un chemin du commit impose un arrêt ; Git ne doit pas être forcé. Les sauvegardes et la préparation restent privées (700/600), hors racine web.

## 3. Installation et vérification HTTP sans JavaScript

Exécuter à la suite des contrôles réussis, dans le même shell. Aucun `GA4_ENABLED` ou identifiant n’est exporté, aucune configuration privée n’est écrite. Aucune construction d’assets, installation Composer ou migration nécessaire.

```bash
cd "$APP"
test "$(git rev-parse HEAD)" = "$BEFORE"
git merge --ff-only "$RELEASE"
chmod 644 templates/legal/privacy.html.twig
printf '%s  %s\n' 3d8b25bc72ed76e21bdd80f95c747779017305d6cf611ef8ada1dc67fd8f88ac templates/legal/privacy.html.twig | sha256sum -c -
sha256sum -c "$STAGE/preserved.sha256"
APP_ENV=prod APP_DEBUG=0 "$PHP_BIN" bin/console lint:twig templates/legal/privacy.html.twig
APP_ENV=prod APP_DEBUG=0 "$PHP_BIN" bin/console cache:clear --env=prod --no-debug --no-warmup
APP_ENV=prod APP_DEBUG=0 "$PHP_BIN" bin/console cache:warmup --env=prod --no-debug
sleep 3  # délai supérieur au revalidate_freq=2 rapporté pour OPcache
curl --fail --silent --show-error --proto '=https' --max-time 30 \
  https://francetuncq.fr/politique-de-confidentialite -o "$STAGE/privacy.after.html"
export STAGE
"$PHP_BIN" <<'PHP'
<?php
$html = file_get_contents(getenv('STAGE').'/privacy.after.html');
if (!str_contains($html, 'href="mailto:contact@luniversdesnombres.com"')
    || !str_contains($html, '>contact@luniversdesnombres.com</a>')
    || str_contains($html, 'contact@universdesnombres.com')) {
    fwrite(STDERR, "Courriel HTTP non conforme : arrêter et diagnostiquer.\n"); exit(1);
}
echo "Courriel et lien HTTP conformes.\n";
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($html);
$nodes = (new DOMXPath($dom))->query('//*[@data-analytics-config]');
if ($nodes->length !== 1) { exit(1); }
$config = json_decode($nodes->item(0)->getAttribute('data-analytics-config'), true, 512, JSON_THROW_ON_ERROR);
if (($config['enabled'] ?? null) !== true || ($config['id'] ?? null) !== 'G-MWZX1GYXZE') {
    fwrite(STDERR, "Configuration HTTP Analytics différente : arrêt, ne pas la modifier automatiquement.\n"); exit(1);
}
echo "Configuration HTTP toujours active vers la production.\n";
PHP
sha256sum -c "$STAGE/preserved.sha256"
git status --short
```

`curl` télécharge seulement le HTML, n’exécute pas de JavaScript et ne contacte pas Google. Le contrôle PHP lit le courriel et `data-analytics-config` sans charger la balise. Il ne valide ni la délivrabilité du courriel ni les rapports Analytics ; ne pas accepter de consentement ni effectuer un parcours Analytics pour ce correctif. Si le rendu reste ancien malgré les caches renouvelés, diagnostiquer le cache HTTP/PHP de l’hébergeur avant toute manipulation supplémentaire ; ne pas inventer une commande de redémarrage.

## 4. Retour arrière selon l’étape atteinte

**Avant l’avance Git** : aucun template actif n’a été remplacé. Un échec du contrôle Twig préalable ne demande pas de restauration de fichier applicatif. Conserver la préparation privée pour diagnostic. Aucun fichier de configuration n’a été modifié.

**Après l’avance Git, y compris échec du cache ou du contrôle HTTP** : le bloc suivant peut être lancé dans un nouveau shell. Remplacer `STAGE` par le chemin privé effectivement affiché ; le HEAD antérieur et le SHA tenté sont lus dans cette sauvegarde, pas déduits du parent direct. La configuration active et les données ne sont jamais restaurées depuis une ancienne archive.

```bash
set -euo pipefail
umask 077
APP=/home/u664242226/domains/francetuncq.fr/public_html
PHP_BIN=/opt/alt/php84/usr/bin/php
STAGE=/home/u664242226/private-deployments/REMPLACER_PAR_LE_DOSSIER_CONSERVE
test -d "$STAGE"
BEFORE=$(cat "$STAGE/before.txt")
RELEASE=$(cat "$STAGE/release.txt")
[[ "$BEFORE" =~ ^[0-9a-f]{40}$ && "$RELEASE" =~ ^[0-9a-f]{40}$ ]]
cd "$APP"
CURRENT=$(git rev-parse HEAD)
if test "$CURRENT" = "$RELEASE"; then
  git diff --cached --quiet
  git diff --quiet -- . ':!composer.json'
  sha256sum -c "$STAGE/preserved.sha256"
  git switch --detach "$BEFORE"
elif test "$CURRENT" != "$BEFORE"; then
  echo 'HEAD différent des deux étapes connues : arrêt, ne pas forcer.' >&2
  exit 1
fi
cmp templates/legal/privacy.html.twig "$STAGE/privacy.before.twig"
sha256sum -c "$STAGE/preserved.sha256"
APP_ENV=prod APP_DEBUG=0 "$PHP_BIN" bin/console lint:twig templates/legal/privacy.html.twig
APP_ENV=prod APP_DEBUG=0 "$PHP_BIN" bin/console cache:clear --env=prod --no-debug --no-warmup
APP_ENV=prod APP_DEBUG=0 "$PHP_BIN" bin/console cache:warmup --env=prod --no-debug
git status --short
```

Le retour Git non forcé préserve la différence locale de `composer.json`, les fichiers non suivis et la configuration privée ; il s’arrête si une nouvelle modification suivie doit être rapprochée. Aucun asset n’ayant changé, aucun asset à restaurer. Le serveur est alors volontairement en **HEAD détachée sur sa version antérieure**, avec le courriel antérieur rétabli ; la branche `main` locale reste sur le correctif. Ne pas revenir aveuglément sur `main` : préparer ensuite, avec autorisation, la correction de l’incident et la réconciliation Git. Les références distantes ne sont pas réécrites. Si le renouvellement du cache échoue encore, conserver l’erreur et le chemin de sauvegarde, puis diagnostiquer ; ne pas annoncer un rétablissement HTTP sans le constater.

## Limites et contrôles de préparation

Les seules inconnues indispensables à l’exécution sont le **SHA du futur commit**, le **HEAD et les différences serveur au moment de l’intervention**, et les éventuelles évolutions distantes depuis la lecture du 4 novembre. Les blocs les contrôlent avant remplacement. La présence du contact corrigé en production ne sera acquise qu’après un futur contrôle HTTP ; aucune publication n’est présumée ici. Aucun point administratif à rouvrir sur « Personnalité juridique » et aucun nouveau test de réception requis.

Contrôles locaux du 4 novembre : les deux blocs PowerShell ont été analysés par le parseur PowerShell sans erreur ; les trois blocs Bash passent `bash -n` ; les deux fragments PHP passent `php -l`. Il s’agit de contrôles de syntaxe, pas d’une exécution des opérations ou d’une validation de l’état serveur. Le rendu Twig ciblé des deux versions est décrit dans le dossier de rectification. Les diffs passent `git diff --check` ; les index, l’avatar, les fichiers hors périmètre et les preuves historiques contrôlées sont inchangés. Les nouvelles pages documentaires ont également été contrôlées pour les espaces de fin de ligne et leurs liens internes.
