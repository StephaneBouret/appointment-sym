# Recette GA4 avec la bibliothèque réelle — 19 octobre 2026

**Complément du 21 octobre 2026 :** après un constat initial « Aucune donnée reçue », l'utilisateur confirme par captures la réception dans DebugView de la session manuelle `20261021-073956Z-940fdf46`, dans « France Tuncq — Recette » : `page_view` 5, `discover_session_click` 2, `view_service` 2, `reserve_click` 2, `first_visit` 1 et `session_start` 1. Les 13 événements, dont 11 applicatifs, sont devenus visibles après sélection du troisième appareil nommé « Google ». Le [compte rendu manuel](analytics-ga4-recette-manuelle.md) documente la vérification du sélecteur après changement de profil, l'arrêt propre et les HTTP 204 des deux `reserve_click` complétés par le NetLog final. Les captures ne confirment ni Temps réel ni les rapports standards. L'absence des données des essais du 19 octobre reste inexpliquée ; aucune responsabilité du mode headless n'est démontrée. Les mesures améliorées restent désactivées et seul Internal Traffic existe en état Test, sans filtre actif, selon la vérification utilisateur.

**Résultat historique : la recette du 19 octobre n'est pas validée pour activation en production, un envoi différé du second onglet recevant HTTP 204 après retrait effectif.** Le diagnostic de réception n'avait pas corrigé ce défaut. Une intervention distincte du 21 octobre ajoute ensuite [la protection applicative des transports et sa preuve avant/après](analytics-ga4-retrait-onglets.md) ; ses limites conservent la recette globale non validée pour la production. Les résultats détaillés ci-dessous concernent les essais du 19 octobre et distinguent commandes, tentatives réseau et réponses HTTP. Leur réception dans l'interface reste non confirmée ; la preuve DebugView du 21 octobre concerne uniquement la session manuelle indiquée ci-dessus.

## Périmètre et environnement

- Dépôt principal `C:\Formations\appointment-sym`, branche `main`, HEAD `2653315d0f9ee2594079f8bfb34b357459aca955`. Modifications préexistantes conservées, index Git inchangé.
- Destination expressément autorisée : **France Tuncq — Recette**, flux **Recette locale — appointment-sym**, `G-7ZGZR4L2F8`. Aucun envoi de test vers `G-MWZX1GYXZE`.
- Mesures améliorées du flux de recette désactivées : capture vérifiée par l'utilisateur. Les autres réglages du compte Google ne sont pas certifiés par cette recette.
- Vrai Symfony/Turbo/Stimulus, données synthétiques uniquement, SQLite en mémoire, mailer nul, Stripe bloqué. Serveur local limité aux GET/HEAD et aux routes de recette. Aucune soumission de formulaire métier.
- Chrome headless `155.0.8059.80`, profil vierge séparé à chaque exécution ; origine `http://localhost:8021`. Les deux cookies Secure sont effectivement créés sur localhost. `isSecureContext=true` ; aucune option désactivant la sécurité, aucun certificat ignoré, aucune adaptation HTTPS nécessaire pour **ce navigateur et cette origine**. Cela ne prouve rien pour une adresse IP/LAN ou un autre navigateur.
- `GA4_ENABLED=1` et `GA4_MEASUREMENT_ID=G-7ZGZR4L2F8` injectés uniquement dans le processus PHP isolé, avec le verrou supplémentaire `GA4_REAL_RECIPE`. Les fichiers d'environnement privés ne sont ni chargés par le bootstrap de test ni modifiés.

Le lanceur autonome `tests/Analytics/real_recipe.py` exige `--authorize-real-ga4 G-7ZGZR4L2F8`. Le harnais Google simulé `browser.py` et son serveur `preview.php` restent inchangés. Les rapports JSON remplacent les valeurs des identifiants navigateur/session et des cookies par des alias ; ne pas partager les profils Chrome ni les journaux bruts.

## Méthode de mesure

Le harnais observe les commandes ajoutées à `dataLayer`, sans en ajouter ni changer les états de consentement. Il intercepte les requêtes de page avant leur départ, en vérifiant la destination et les identifiants dans l'URL **et** chaque ligne du corps. Il observe ensuite les réponses HTTP et les annulations signalées par Chrome.

Google regroupe plusieurs événements dans une requête ; chaque ligne du corps est analysée avec ses paramètres communs. Une attente de dix secondes entre les étapes ordinaires évite d'attribuer le lot de la page précédente à la page suivante. Un clic de réservation conserve sa navigation immédiate : aucune attente Analytics n'est ajoutée à ce parcours.

Trois niveaux de preuve doivent rester séparés :

1. **Commande en file** : l'intégration a appelé `gtag`/`dataLayer.push`. Ce n'est pas une livraison.
2. **Tentative réseau autorisée** : Chrome a présenté une requête au filtre puis le harnais l'a laissée continuer. Une annulation ou l'absence de réponse laisse sa réception incertaine. Pour certains beacons de sortie de page, CDP ne fournit pas de `networkId` ; leur heure d'interception ne prouve pas l'heure précise d'initiation.
3. **Réponse HTTP 204** : le collecteur Google a répondu. Cela ne prouve ni l'ingestion dans les rapports ni le traitement métier de l'événement.

Les chargements de bibliothèque servis depuis la mémoire du navigateur sont identifiés séparément ; ils ne constituent pas un nouveau contact réseau. Le cache est temporairement désactivé pour les seuls scénarios de chargement ralenti et de panne, afin de réellement provoquer ces situations. Les tests de retour arrière conservent le cache et vérifient `pageshow.persisted`.

## Données observées

Les pages envoyées utilisent uniquement l'origine locale et ces chemins : `/`, `/rendez-vous/types`, `/rendez-vous/types/1`. Les titres sont respectivement `Accueil`, `Prestations`, `Fiche prestation`. Aucun `dl` avec query string, fragment, connexion ou formulaire privé. `dr` est absent/vide ; aucun en-tête HTTP `Referer` vers Google dans les captures de recette. Les paramètres métier observés sont `ep.service_id=1` et `ep.display_context=home_featured`, `service_page` ou `catalogue_ajax`.

Les marqueurs `GA4_RECIPE_CANARY_*` placés dans les query strings et fragments publics, puis dans le titre, le DOM, les paramètres et fragments privés, n'apparaissent dans aucune requête Google autorisée. Aucune valeur de formulaire ou donnée utilisateur n'est présente dans les paramètres inspectés. Aucun `uid`/User-ID, événement d'achat ou identifiant de paiement/rendez-vous n'est ajouté.

Les paramètres techniques de la bibliothèque comprennent notamment `cid`, `sid`, `sct`, `seg`, des informations navigateur/langue/écran, `gcs`, `gcd`, `npa`, et des marqueurs internes. Les valeurs de `cid`/`sid` sont masquées dans les pièces partagées. Les débuts de navigateur/session apparaissent aussi sous forme de drapeaux `_fv=1` et `_ss=1` sur des événements explicites : l'absence d'un événement séparé nommé `first_visit`/`session_start` dans la capture ne démontre pas leur absence dans les rapports GA4. Les consentements publicitaires refusés sont observés dans les commandes ; les payloads portent notamment `gcs=G101` et `npa=1`. Aucun décodage exhaustif des champs propriétaires Google n'est prétendu.

## Cookies réels

| Nom | Rôle observé | Attributs |
| --- | --- | --- |
| `audience_G_7ZGZR4L2F8_ga` | Identifiant navigateur | domaine `localhost`, chemin `/`, Secure, SameSite=Lax, non-HttpOnly, persistant |
| `audience_G_7ZGZR4L2F8_ga_7ZGZR4L2F8` | État de session du flux | mêmes attributs |

L'échéance observée est le 19 avril 2027, six mois après le choix de recette. Chrome rapporte `sourceScheme=NonSecure` pour l'origine HTTP locale, tout en confirmant `secure=true` sur les cookies. Aucun cookie `_ga` non préfixé, `_gid`, `_gat` ou `_gcl` n'est observé dans cette exécution.

Les deux parcours public → privé → public comparent la valeur du cookie navigateur et les paramètres réseau `cid`/`sid`. La présence du cookie de session est vérifiée, mais sa valeur complète peut évoluer normalement : elle n'est pas exigée identique. Le retrait efface les deux cookies dédiés.

## Destinations

| Destination | Observation et traitement |
| --- | --- |
| `www.googletagmanager.com/gtag/js?id=G-7ZGZR4L2F8` | Vraie bibliothèque, HTTP 200 puis cache mémoire ; réponse retenue pendant le scénario ralenti |
| `region1.google-analytics.com/g/collect` | Collecte réelle du seul ID autorisé, réponses HTTP 204 et tentatives sans réponse détaillées dans les résultats |
| `www.google-analytics.com/g/collect` | Hôte exact prévu par le filtre ; ne pas confondre autorisation et contact effectivement observé |
| `cdn.jsdelivr.net/npm/@fullcalendar/...` | Dépendances existantes sans rapport avec Analytics, bloquées avant départ |
| `unpkg.com/@googlemaps/extended-component-library@0.6` | Réponse JavaScript locale de remplacement ; aucun accès au CDN ni à Maps |

Aucune dépendance Google supplémentaire nécessaire n'a été identifiée. Aucun domaine Google générique n'a été autorisé. Le relevé CDP concerne les pages et cadres protégés, pas une capture réseau de l'ensemble du système d'exploitation.

Le diagnostic NetLog a révélé que les options `--disable-background-networking` et apparentées ne suppriment pas toutes les connexions **internes de Chrome**, hors interception des pages : horloge (`clients2.google.com`), compte (`accounts.google.com`), notifications (`android.clients.google.com`), mises à jour (`update.googleapis.com`), modèles (`optimizationguide-pa.googleapis.com`), aide au remplissage (`content-autofill.googleapis.com`), ainsi que DNS sécurisé (`dns10.quad9.net`). Ces connexions ont existé pendant la première tentative de diagnostic NetLog, qui n'avait pas initialisé Analytics. Ce ne sont pas des dépendances ajoutées à GA4 ni des domaines autorisés par le filtre applicatif. Aucun profil personnel n'a été utilisé.

Le lanceur a été renforcé avec des règles DNS restrictives : seuls localhost et les trois hôtes exacts déjà autorisés pour Analytics sont résolubles. Cette restriction n'affaiblit aucune protection navigateur. Dans le diagnostic final, NetLog confirme que les requêtes HTTP externes ayant reçu une réponse se limitent à la bibliothèque (200) et au collecteur `region1` (204). Les services annexes, dont `www.gstatic.com` et `www.google.com`, échouent à la résolution (`-105`, parfois accompagné d'une autre erreur) ; ils n'ont pas été ajoutés à la liste autorisée. Résumé expurgé : `var/analytics-real/20261019-125630/network-service-destinations.json`.

## Résultats et anomalies

La passe principale de référence est `var/analytics-real/20261019-124804/`, du **19 octobre 2026, 10:48:06 à 10:52:17 UTC**. Le rapport brut expurgé contient 44 contrôles exécutés, dont 42 réussis et deux échecs correspondant au même retrait multi-onglets. Le rapport `reviewed-report.json` ajoute trois contrôles d'inspection hors ligne : métadonnées conformes, deux livraisons de clics natifs alors non confirmées par CDP. Ce rapport est conservé tel quel : le diagnostic ultérieur ne réécrit pas la réception des événements de cette passe.

| Contrôle | Résultat observé |
| --- | --- |
| Avant choix et après refus, navigations/rechargements compris | Aucun chargement Analytics, cookie dédié ou envoi de collecte |
| Acceptation | Vraie bibliothèque HTTP 200, deux cookies effectifs, page vue émise |
| Turbo, hooks répétés, retour à l'accueil, accès direct à la fiche | Comptes attendus, aucun doublon d'affichage observé ; la nouvelle visite est comptée |
| AJAX | Un `view_service` pour la fiche affichée ; aucun `page_view` supplémentaire |
| Connexion anonyme et formulaire synthétique authentifié | Navigation complète, cadre détruit ; aucune collecte pendant l'attente sur l'écran privé ; URL/titre privés absents des payloads |
| Retour public après privé | Même cookie navigateur, même `cid`, même `sid` dans chacun des deux parcours courts ; cookie de session autorisé à évoluer |
| Retrait depuis le privé, puis retour public | Cookies supprimés, aucun nouveau cadre ni envoi sans nouvel accord |
| Retours navigateur avec BFCache | `pageshow.persisted=true` réellement observé sur `/login`, le formulaire, le catalogue et la fiche ; les retours publics après retrait restent sans cadre/cookies/collecte |
| Retrait après chargement, onglet courant | Cadre et cookies supprimés, aucune nouvelle requête observée après le clic de refus |
| Retrait pendant chargement | Réponse réelle de la bibliothèque retenue avant exécution ; retrait puis libération de la réponse : aucune résurrection du cadre/cookies et aucun envoi |
| Deux onglets | Deux cadres détruits et cookies supprimés, mais **requête initiée après retrait effectif et acquittée HTTP 204**, critère strict non satisfait |
| Panne Google | Chargement réellement bloqué par le filtre, puis navigation jusqu'au formulaire privé fonctionnelle |
| Nettoyage final du diagnostic ciblé | Retrait depuis le formulaire privé réussi, aucun cadre/cookie restant |

La passe principale s'est arrêtée lors de son ultime clic de nettoyage : le harnais avait attendu le changement d'URL du formulaire mais pas encore la disponibilité de son DOM (`RuntimeError: Uncaught`, élément de bouton absent). Le lanceur a néanmoins fermé ses processus. L'attente du DOM privé a été corrigée ; elle est exercée avec succès dans le diagnostic ciblé, ainsi que son retrait final. **La suite complète n'a pas été relancée après ce dernier correctif du harnais.** Cela ne masque pas l'échec multi-onglets précédent.

### Commandes, requêtes et réponses

Passe principale, décompte des événements après séparation des lots Google :

| Événement | Commandes en file | Tentatives réseau | Événements dans une requête avec HTTP 204 observé par CDP |
| --- | ---: | ---: | ---: |
| `page_view` | 13 | 13 | 13 |
| `discover_session_click` | 1 | 1 | 1 |
| `view_service` | 6 | 6 | 5 |
| `reserve_click` | 3 | 3 | 1 |

Cela représente **18 requêtes de collecte**, dont 15 avec HTTP 204 et trois sans réponse rattachée par CDP. Le `reserve_click` acquitté dans cette passe provient d'un **contrôle diagnostique** où le lien est temporairement empêché de naviguer dans le seul navigateur de test ; il ne valide pas un clic natif. Les deux autres proviennent bien de navigations natives, sans attente ni interception de leur destination. La tentative `view_service` sans réponse est celle du retrait multi-onglets.

Le diagnostic ciblé final, `var/analytics-real/20261019-125630/`, **10:56:31 à 10:57:07 UTC**, a rejoué uniquement les deux clics natifs, avec NetLog et restriction DNS. Les commandes et les tentatives correspondent à **2 `page_view`, 2 `view_service`, 2 `reserve_click`**. NetLog confirme **cinq requêtes HTTP 204** contenant ces six événements, dont **une réponse 204 pour chacun des deux clics Réserver, anonyme et connecté**. Les 6 contrôles de ce diagnostic et de son audit hors ligne passent. Son `validated=true` s'applique seulement à ce périmètre ciblé, pas à la recette globale.

Les premières conclusions de perte de clics étaient donc une insuffisance de l'observation CDP : certains beacons de sortie de page sont présentés au filtre sans `networkId`, puis leurs réponses ne sont plus rattachées au cadre détruit. Le harnais conserve désormais ces requêtes séparément et rapproche leurs URL, hors identifiants masqués, des transactions NetLog du service réseau. Il ne considère jamais une simple continuation du filtre comme un HTTP 204. Les commandes de recette ordinaires disposent désormais aussi de ce rapprochement.

Aucun autre nom d'événement automatique n'a été observé dans les captures de ces parcours courts. Les marqueurs internes `_fv`/`_ss` restent présents comme expliqué plus haut ; le comportement sur de longues sessions et le traitement côté GA4 ne sont pas démontrés.

### Anomalie identifiée le 19 octobre : retrait multi-onglets

Reproduction, observée lors de deux passes : accepter sur l'accueil dans l'onglet A, ouvrir la fiche `/rendez-vous/types/1` dans un onglet B, laisser initialiser la bibliothèque et la page vue, puis revenir rapidement dans A et choisir « Gérer mes cookies » → « Refuser » alors que Google conserve encore le `view_service` de B en attente.

Dans la passe de référence, le `view_service` de B déclenche `Network.requestWillBeSent` **2,337 ms après** le marqueur du clic de refus dans A. Le filtre le laisse continuer vers le seul flux autorisé. Chrome signale ensuite `net::ERR_ABORTED` environ **55,629 ms après le clic**, sans réponse HTTP rattachée dans CDP. Il s'agit d'une nouvelle tentative après retrait, pas simplement de la réponse tardive d'une requête initiée avant. Son payload contient encore la fiche publique propre, sans canari privé.

Ce premier horodatage utilisait le clic, sans encore observer la fin de l'écriture en stockage. Il ne suffisait pas à dater précisément le retrait effectif. **Nous n'avons pas conclu que Google n'avait pas reçu cette tentative :** des requêtes acquittées 204 portent aussi `ERR_ABORTED` dans CDP, et cette passe ne disposait pas encore de NetLog.

Un diagnostic ciblé supplémentaire a donc été exécuté, avec le même blocage DNS et NetLog :

```powershell
python tests/Analytics/real_recipe.py --authorize-real-ga4 G-7ZGZR4L2F8 --withdrawal-only
```

Référence : `var/analytics-real/20261019-130332/reviewed-report.json`. Le harnais horodate désormais le **retour réussi de `localStorage.setItem('audience-consent', ...)` avec le choix `refused`**, via un observateur installé avant l'application. La requête suivante est initiée **2,654 ms après cette écriture effective**, selon `Network.requestWillBeSent`. Elle contient le lot public `page_view` + `view_service` de l'onglet B. NetLog rattache à cette transaction une **réponse HTTP 204** (source réseau 718), alors que CDP signale encore une annulation sans réponse. Ce n'est donc plus une simple incertitude de l'outil ou une réponse arrivée après un envoi antérieur au refus.

Les contrôles séparés confirment que **les deux cadres sont détruits et les cookies supprimés**, et que les métadonnées du lot restent publiques et filtrées. Trois contrôles sur quatre passent ; celui interdisant toute émission après le refus effectif échoue. La suppression des cookies et des cadres ne suffit donc pas à neutraliser ce lot différé de la vraie bibliothèque.

Hypothèse issue du code et du timing : la bibliothèque conserve une file interne qui n'est pas celle de `dataLayer`, et peut émettre pendant l'arrêt/destruction du cadre ou avant le traitement asynchrone du `storage` de l'autre onglet. Vider `dataLayer`, poser `ga-disable-*` puis retirer l'iframe ne constitue pas, à lui seul, une preuve de neutralisation de tous ses transports.

**Proposition à l’issue de la recette du 19 octobre (implémentation ultérieure décrite dans le compte rendu du 21 octobre) :** ajouter dans le cadre dédié une barrière de sortie installée *avant* le chargement de Google, qui relit synchroniquement le consentement courant au moment d'une émission et refuse les transports lorsque l'accord n'est plus valide. Elle doit couvrir les transports réellement utilisés (`fetch` avec maintien après navigation, `sendBeacon`, et tout repli XHR/image identifié), neutraliser les tâches en attente avant destruction, et être testée avec les mêmes courses multi-onglets/BFCache et NetLog. Une protection limitée à un seul transport ne serait pas suffisante. Conserver la destruction du cadre, les frontières de navigation complètes et l'absence de délai sur les liens. À cette date, cette proposition nécessitait encore une implémentation et une nouvelle recette ; elle n'était pas une garantie acquise ni une invitation à conserver Google sur les écrans privés.

### Historique des corrections du harnais

Les premières passes ont permis de corriger : attachement CDP d'un onglet explicite ; distinction cache mémoire/réseau ; attente des lots Google ; comparaison du seul identifiant navigateur plutôt que de toute la valeur du cookie de session ; séparation des beacons sans `networkId` ; vraie panne forcée malgré le cache ; précondition explicite d'initialisation du consentement dans le diagnostic ciblé ; horodatage du retrait après l'écriture effective. La tentative NetLog `20261019-125355` n'avait pas activé Analytics et n'est pas utilisée comme preuve de clics réels. Les rapports intermédiaires restent dans `var/analytics-real/`, avec leurs échecs, et ne remplacent pas les références détaillées ci-dessus. Des événements de recette ont aussi été émis pendant les premières passes réelles : les compteurs des tableaux ne sont pas un total de la propriété GA4.

## Vérifications locales et état final

```powershell
python tests/Analytics/real_recipe.py --authorize-real-ga4 G-7ZGZR4L2F8
python tests/Analytics/real_recipe.py --authorize-real-ga4 G-7ZGZR4L2F8 --native-click-only
python tests/Analytics/real_recipe.py --authorize-real-ga4 G-7ZGZR4L2F8 --withdrawal-only
vendor\bin\phpunit --bootstrap tests/Prestations/bootstrap.php tests/Analytics/AnalyticsTest.php
php -l tests/Analytics/real_preview.php
git diff --check
```

PHPUnit : **3 tests, 49 assertions, aucun échec**. Syntaxe PHP valide. Les modules Python ont été compilés en mémoire, sans lancement de navigateur ; les assertions du filtre de destination passent, notamment le rejet de l'ID production, des IDs mixtes, d'un hôte non autorisé et de HTTP. Le lanceur sans argument retourne bien le code 2 avant tout démarrage.

Les fonctions hors ligne `attach_netlog(report, path)` et `audit_report(report)` ont été exécutées sur les pièces enregistrées pour produire les `reviewed-report.json`, sans nouvel envoi. Ces rapports sont expurgés ; les fichiers `netlog-private.json` contiennent des identifiants bruts et ne doivent pas être partagés.

Codes de sortie réellement observés : la passe complète a retourné **1** (retrait multi-onglets et arrêt final du harnais décrit plus haut) ; le diagnostic natif a d'abord retourné **1**, avant ajout de la corrélation NetLog, puis son audit hors ligne a confirmé **6/6** contrôles ; le diagnostic précis de retrait a retourné **1**, avec **3/4** contrôles réussis et la requête tardive confirmée HTTP 204. Les contrôles PHP, syntaxe et diff ont retourné **0**. La dernière version du lanceur intègre les audits hors ligne ; aucune nouvelle passe complète n'est prétendue après ces adaptations.

Les vérifications de refus/expiration/désactivation avec Google simulé restent celles documentées dans `analytics-ga4.md` ; cette intervention ne les présente pas comme de nouveaux tests réels. Les tests ordinaires ne lancent pas cette recette réseau.

Les défauts de configuration du projet restent `GA4_ENABLED=0`, `GA4_MEASUREMENT_ID=` dans `.env.ga4.example` et `config/services.yaml`. Aucun changement du code applicatif durant cette recette. Aucun accès au worktree France, aucune migration, réservation, paiement, émission de courriel, opération en production, commit, push ou déploiement.

Tous les lanceurs ont arrêté leurs processus. Les contrôles finaux confirment les ports **8021 et 9233 libres**, aucun processus PHP/Chrome restant avec les arguments du harnais de recette, et aucune variable `GA4_ENABLED`, `GA4_MEASUREMENT_ID` ou `GA4_REAL_RECIPE` injectée dans le processus parent. `git diff --check` passe et l'index reste vide, comme au départ.

Fichiers ajoutés ou adaptés pour cette intervention uniquement :

- `tests/Analytics/real_recipe.py` : lancement explicite, scénarios, NetLog, audit et expurgation ;
- `tests/Analytics/real_driver.py` : observation CDP et filtre de destinations ;
- `tests/Analytics/real_preview.php` : serveur isolé verrouillé sur le flux autorisé ;
- `docs/analytics-ga4.md` : procédure distincte et réserve d'activation ;
- `docs/analytics-ga4-recette-reelle.md` : présent compte rendu.

La confirmation manuelle GA4 doit porter sur le flux de recette, les plages horaires et les événements indiqués dans les rapports expurgés. Ne pas créer de vente/conversion à partir de `reserve_click`. Une nouvelle recette des anomalies est nécessaire avant toute demande d'activation en production.
