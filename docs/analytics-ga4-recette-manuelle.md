# Diagnostic manuel de réception GA4 — 21, 28 et 29 octobre 2026

## Actualisation — production du 30 octobre 2026

**Déploiement, activation autorisée puis exécutée et réception des quatre événements dans Temps réel confirmés par l’utilisateur.** Voir le [compte rendu de mise en production](analytics-ga4-production-20261030.md). Les opérations et captures sont rapportées par l’utilisateur et son assistant ; aucune inspection directe du serveur, d’Analytics ou des captures de production par cet agent.

La nouvelle observation porte sur la production : Temps réel affiche page_view 2, discover_session_click 1, view_service 1 et reserve_click 1. Le retour supplémentaire à l’accueil interdit de tirer une preuve de déduplication de ces compteurs. Les recettes antérieures ci-dessous restent distinctes.

Les statuts de préparation et de recette qui suivent sont conservés comme **historique daté**, sans annuler les acquis du 30 octobre ni rouvrir les contrôles déjà confirmés. Aucune nouvelle opération serveur n’est autorisée par cette actualisation.

## Historique conservé

## Exploration confirmée et rapprochement des deux ouvertures — 29 octobre 2026

**Source : revue manuelle rapportée par l’utilisateur le 29 octobre 2026 à 08:19 Europe/Paris (UTC+01:00).** Exploration « **Recette GA4 — 28 octobre 2026** », propriété **559568920 / France Tuncq — Recette**, période fixe du **28 octobre**, tous les événements dans la colonne **9 h**. Aucun accès direct à GA4 ni nouvelle capture inspectée par l’agent à cette étape. Réception des quatre types applicatifs après correctif confirmée dans cette exploration, en complément de DebugView.

| Événement | Session principale : commandes et événements réseau | Ouverture interrompue : commandes et événements réseau | Exploration rapportée |
| --- | ---: | ---: | ---: |
| `page_view` | 7 | 1 | 8 |
| `view_service` | 6 | 0 | 6 |
| `discover_session_click` | 2 | 0 | 2 |
| `reserve_click` | 1 | 0 | 1 |
| **Total applicatif** | **16** | **1** | **17** |
| `first_visit` | Marqueur `_fv=1` sur la première collecte | Marqueur `_fv=1` | 2 |
| `session_start` | Marqueur `_ss=1` sur la première collecte | Marqueur `_ss=1` | 2 |
| **Total affiché** | — | — | **21** |

Les marqueurs automatiques ne sont pas des commandes applicatives ni des requêtes indépendantes. Leur présence dans les traces explique la compatibilité avec deux visites/sessions initiales ; leur affichage effectif est établi par le relevé manuel, pas par ces seuls marqueurs. Les 17 événements applicatifs des deux ouvertures sont transportés dans **huit POST au total**, exclusivement vers **`region1.google-analytics.com/g/collect`, `G-7ZGZR4L2F8`**, avec huit réponses 204 observées dans les entrées réseau disponibles.

### Ce que montre la première ouverture interrompue

- Session **`20261028-081422Z-e8fa67c9`** : une commande et une requête `page_view`, acquittée HTTP 204 dans le rapport conservé. L’entrée brute NetLog **602** débute à **09:14:39.654 Paris** et comporte une réponse **204 à 09:14:39.780**, avec `_fv=1` et `_ss=1`.
- Session principale **`20261028-083431Z-91281b44`** : sept requêtes finalisées et acquittées. Sa première collecte, source **846**, débute à **09:36:28.390**, réponse **204 à 09:36:28.493**, avec les mêmes deux marqueurs. Les six requêtes suivantes ne comportent pas ces marqueurs initiaux.
- Comparaison locale des valeurs brutes : **un identifiant navigateur et un identifiant session par ouverture**, avec des ensembles **distincts entre les deux ouvertures**. Leurs valeurs ne sont pas publiées. Les deux ouvertures appartiennent bien à la même tranche de 9 h du 28 octobre, UTC+01:00.

**Limite du NetLog interrompu :** ce fichier n’est pas un JSON finalisé. Son `report.json` conserve déjà `netlog_error: Expecting value…`. L’analyse du 29 lit seulement ses **26 312 entrées complètes**, ligne par ligne, sans modifier ni réparer le fichier d’origine. L’entrée de requête et celle de réponse citées sont complètes ; la complétude de la trace de cette ouverture n’est pas revendiquée. Le NetLog principal est, lui, un JSON valide et finalisé (**29 710 entrées**). Cette différence est conservée dans la pièce de rapprochement ; aucun résultat automatique historique n’est réécrit.

La première ouverture fournit donc **une explication cohérente de la page vue supplémentaire et des seconds marqueurs de visite/session**. La concordance est exacte au niveau des volumes disponibles : **7 + 1 pages vues**, 6/2/1 autres événements applicatifs et deux paires de marqueurs initiaux, compatibles avec les **21 affichés**. Cependant, l’exploration agrégée par heure ne relie pas individuellement ses événements aux identifiants locaux. On ne peut donc pas affirmer que telle ligne GA4 précise provient de telle ouverture sur la seule base de ce relevé.

### Réserve sur le parcours et statut de validation

L’utilisateur précise le **29 octobre** avoir probablement réalisé davantage de clics que demandé sur `localhost:8021`. Le parcours était **non strictement contrôlé**. Le regroupement de requêtes et les commandes horodatées prouvent ce qui a été instrumenté et transporté, pas le nombre exact de clics physiques ni l’intention de chaque action. La réception des quatre types est acquise ; les volumes seuls ne permettent de conclure ni à un doublon, ni à une correspondance un événement / une action physique. Aucun test supplémentaire n’est relancé ou demandé pour cette réserve. Les preuves des scénarios contrôlés historiques restent distinctes de ce parcours libre.

Le relevé **DebugView du 28 octobre vers 09:41** reste conservé à **17 affichés dont 5 `view_service`**. Le relevé du lendemain indique **6 `view_service`**, désormais concordants avec le total local principal. **Le défaut de concordance de volume n’est plus présent dans cette nouvelle observation** ; cela ne permet pas de qualifier rétroactivement la cause du précédent écart (retard, fenêtre d’affichage ou autre). Ne pas substituer 6 à 5 dans l’ancien relevé.

Le critère de réception après correctif dans une vue Analytics persistante est **satisfait selon la revue manuelle rapportée**. Les rapports prédéfinis et Temps réel ne sont pas pour autant déclarés inspectés ; leur consultation séparée n’est pas un prérequis supplémentaire pour démontrer cette réception. L’absence des essais automatisés des **19/21 octobre** reste inexpliquée, sans responsabilité démontrée du mode headless, du correctif ou de Google. Elle ne doit plus être formulée comme une absence générale de réception après correctif ni comme une demande de rejouer une campagne. La collecte habituelle reste désactivée et **la production n’est pas globalement validée**.

### Pièces conservées et vérifications de cette mise à jour

- `var/analytics-manual/20261028-081422Z-e8fa67c9/report.json` et `netlog-private.json` : ouverture interrompue, limite de finalisation indiquée ci-dessus ;
- `var/analytics-manual/20261028-083431Z-91281b44/report.json`, `netlog-private.json`, `module-fingerprints.json` et `closure-reconciliation.json` : session principale, preuves de clôture conservées ;
- **`var/analytics-manual/reconciliation-exploration-20261029.json`** : nouveau rapprochement hors ligne, provenance manuelle, compteurs, entrées réseau pertinentes, comparaison masquée des identifiants et SHA-256 des rapports/NetLog utilisés. Ne pas partager les NetLog bruts ni les profils Chrome.

Analyses uniquement sur les fichiers existants : recomptage des commandes/événements et réponses, lecture des marqueurs et comparaison locale des identifiants, comparaison des empreintes avec le manifeste du candidat et contrôle documentaire. Aucun lancement de Chrome, parcours, test applicatif, collecte ou changement de réglage. Le manifeste daté, les sources, les rapports historiques et l’index sont conservés. Les prochaines actions sont uniquement les [prérequis France réellement ouverts](analytics-ga4-controle-rapports.md#4-prérequis-france-restant-avant-une-activation-distinctement-autorisée), sans redemander les réglages déjà acquis.

## Clôture et réception après correctif confirmée — 28 octobre 2026

**Session `20261028-083431Z-91281b44` arrêtée proprement.** `python tests/Analytics/manual_recipe.py stop` a terminé avec le code 0 : `status: stopped`, `error: null`, `processes_stopped: true`. Le rapport final est daté de **09:42:25.127 Paris (08:42:25.127 UTC)**. Le superviseur PID 9400 n’est plus présent selon `Get-Process` ; `netstat -ano -p tcp` ne montre aucun port 8021/9233 en écoute (une connexion résiduelle `TIME_WAIT` vers 9233 ne constitue pas un serveur actif). Le lanceur a fermé et attendu ses propres processus Chrome/PHP ; aucun arrêt du navigateur habituel ni commande globale de suppression de processus. Le NetLog final est lisible et conservé. Aucun parcours ni envoi ajouté pour cette clôture : dernière collecte NetLog à **09:40:50.897**, avant la demande d’arrêt.

**Preuve d’interface : revue manuelle rapportée par l’utilisateur**, dans DebugView de **France Tuncq — Recette**, le **28 octobre vers 09:41 Paris**. Aucune nouvelle capture n’a été inspectée par l’agent à cette étape. Réception des **quatre noms d’événements applicatifs après correctif confirmée en mode manuel avec debug**, distincte des preuves réseau ci-dessous.

| Événement | Commandes locales | Événements dans les requêtes | Événements couverts par HTTP 204 final | DebugView rapporté |
| --- | ---: | ---: | ---: | ---: |
| `page_view` | 7 | 7 | 7 | 7 |
| `view_service` | 6 | 6 | 6 | 5 |
| `discover_session_click` | 2 | 2 | 2 | 2 |
| `reserve_click` | 1 | 1 | 1 | 1 |
| **Total applicatif** | **16** | **16** | **16** | **15** |
| `first_visit` | — | — | — | 1 |
| `session_start` | — | — | — | 1 |
| **Total affiché rapporté** | — | — | — | **17** |

Les 16 événements applicatifs sont transportés dans **sept requêtes POST**, individuelles ou groupées, toutes acquittées **HTTP 204** dans le NetLog final, vers **`region1.google-analytics.com/g/collect`, `G-7ZGZR4L2F8` exclusivement**. Destination recontrôlée dans les sept URLs brutes et dans chaque événement décodé du rapport. Ce ne sont pas 16 réponses indépendantes. Les deux événements automatiques n’ont pas de commande applicative ni de requête distincte à leur nom ; `_fv=1` et `_ss=1` sont présents sur la première collecte. Leur affichage est confirmé par la déclaration utilisateur, pas déduit des seuls marqueurs.

Le lot contenant `reserve_click` (source NetLog **1065**) est parti à **09:36:35.276** et sa réponse **204** est observée à **09:36:35.302**. La réponse CDP reste `null`, sans la remplacer : le complément vient du service réseau NetLog. Le même lot contient une `page_view` et un `view_service`. Les six autres requêtes ont des réponses 204 CDP et NetLog. Pas d’erreur JavaScript applicative enregistrée.

**Écart de volume observé à la clôture du 28 octobre, concordance obtenue dans le relevé ultérieur du 29 ci-dessus :** six `view_service` locaux contre cinq dans le relevé DebugView. Les commandes apparaissent à **09:36:33.436**, **09:36:41.697**, **09:40:06.260**, **09:40:28.507**, **09:40:39.394** et **09:40:45.339**. Les deux contextes sont `service_page` et `catalogue_ajax`, tous pour la prestation publique `1`. Le dernier est envoyé à 09:40:50.897 et acquitté à 09:40:50.922. Sans détail événement par événement de l’interface et heure exacte du relevé, on ne peut identifier lequel des six n’y est pas compté ni attribuer cet écart à un retard, un doublon ou une perte. La réception des quatre noms est confirmée ; **la concordance exhaustive des volumes ne l’est pas**.

### Les deux `discover_session_click` : commandes et lots distincts

Toutes les heures suivantes sont le **28 octobre 2026, Europe/Paris, UTC+01:00**, vérifié avec `TimeZoneInfo` Windows.

| Observation | Premier événement | Second événement |
| --- | --- | --- |
| Commande observée dans `dataLayer` | 09:40:06.044 | 09:40:39.205 |
| Requête observée CDP | 09:40:11.883 | 09:40:44.209 |
| Début réseau NetLog | 09:40:11.911 | 09:40:44.222 |
| Réponse NetLog | 09:40:11.975, HTTP 204 | 09:40:44.247, HTTP 204 |
| Source NetLog | 2374 | 2947 |
| Contenu du lot | 1 `discover_session_click`, 1 `page_view`, 1 `view_service` | 1 `discover_session_click`, 1 `page_view`, 1 `view_service` |

Les heures proches de **09:40:11 et 09:40:44 sont celles des envois groupés**, pas celles de clics physiques mesurés. Les deux commandes sont espacées de **33,161 secondes**, avec `service_id: "1"`, `display_context: "home_featured"`, page d’origine `/`, `debug_mode: true` et destination de recette. Une nouvelle commande `page_view` sur l’accueil est observée entre les deux, à **09:40:33.112** ; chaque commande de découverte précède une page vue et une vue de fiche. Cela décrit la séquence instrumentée, sans établir l’origine physique des actions.

Le code émet cet événement depuis son gestionnaire DOM `click`. L’observateur conserve les commandes et les transports mais **pas un journal indépendant des clics sur ce lien, de `event.isTrusted`, du dispositif d’entrée ou de l’intention utilisateur**. Il n’y a donc aucune preuve de deux clics physiques, ni de doublonnage d’un seul clic. Les traces ne montrent pas un simple rejeu réseau de la même commande : deux commandes distinctes et deux lots distincts sont enregistrés, avec une seule occurrence de découverte dans chacun. Aucun nouvel essai ni changement applicatif n’est effectué pour trancher au-delà de cette preuve.

### Portée, pièces et suite minimale au moment de la clôture du 28 octobre

La réception après correctif est acquise **dans DebugView, en manuel avec debug**, sur déclaration utilisateur. Les **rapports standards de cette session restent à vérifier**, ainsi que Temps réel si une preuve de cet écran est souhaitée ; aucun de ces affichages n’est inféré du HTTP 204. L’absence historique des essais automatisés des 19/21 octobre reste inexpliquée. Ce succès ne démontre aucune responsabilité du mode headless ; profil, date, debug et version de Chrome diffèrent aussi. Le critère de retrait accepté et ses limites sont conservés. **Aucune validation globale de production ni autorisation d’activation.**

Pièces conservées sous `var/analytics-manual/20261028-083431Z-91281b44/` :

- `report-live.json` : dernier état CDP, non réécrit pour lui attribuer les réponses manquantes ;
- `report.json` : rapport final complété par les correspondances NetLog ;
- `netlog-private.json` : trace brute finalisée, **ne pas partager** ;
- `module-fingerprints.json` : identification des modules au lancement ;
- `closure-reconciliation.json` : rapprochement hors ligne, horodatages, compteurs et SHA-256 des quatre pièces précédentes, sans valeurs d’identifiants navigateur/session.

Les champs génériques du rapport automatique (réception à confirmer / ancienne mention multi-onglets) ne sont pas réécrits pour simuler un résultat automatisé ; la confirmation manuelle et les limites actuelles sont consignées ici. Les 45 fichiers du candidat et les preuves historiques restent comparés au manifeste conservé, sans certification rétroactive supplémentaire.

**Vérification proposée le 28 octobre, désormais effectuée selon le relevé du 29 ci-dessus :** consulter les rapports standards/exploration du flux de recette sur la date fixe **28 octobre**, autour de **09:36–09:41 Paris**, et conserver propriété, fuseau, période et compteurs. Ne pas mélanger avec la première session interrompue de 09:14, qui a aussi émis une page vue. Dans un regroupement par heure, ces sessions peuvent se confondre ; un filtre à la minute si disponible aide, sans garantir une attribution par session. Documenter particulièrement `view_service` et les limites d’agrégation, sans supposer un résultat de 18 événements dans l’interface. Aucun besoin de refaire le parcours pour ce contrôle.

## Session visible sur le code corrigé — 28 octobre 2026

**Session désormais clôturée : `20261028-083431Z-91281b44`**, démarrée le **28 octobre à 09:34:32 Paris (08:34:32 UTC, UTC+01:00)**. Fuseau vérifié avec les règles Windows `Romance Standard Time` ; ne pas réutiliser le décalage UTC+02:00 des traces du 21 octobre. Chrome visible **156.0.8078.48**, profil isolé neuf, accueil **http://localhost:8021/**. Le superviseur et le serveur avaient été laissés ouverts pour la manipulation de l’utilisateur ; ils sont désormais arrêtés, comme indiqué dans la clôture ci-dessus.

Commande exécutée, sans `--self-check` :

```powershell
python tests/Analytics/manual_recipe.py start --authorize-real-ga4 G-7ZGZR4L2F8 --debug-mode
```

Destination exclusive : **France Tuncq — Recette, propriété 559568920, flux G-7ZGZR4L2F8**. Debug est demandé dans le harnais, mais décorera uniquement les commandes autorisées après acceptation. Les protections de consentement et de transport du code corrigé, les interceptions réseau et l’environnement synthétique restent ceux du candidat. Aucun parcours automatique, clic de consentement, soumission de formulaire ou envoi de préparation n’a été effectué par l’agent. La configuration habituelle reste désactivée ; l’activation est limitée au processus isolé.

**Contrôle avant remise de la main, 09:35:02 Paris :** accueil affiché, consentement absent, zéro cadre, zéro cookie, zéro bibliothèque Google, zéro commande d’événement, tentative de collecte ou réponse de collecte. Les ports 8021 et 9233 répondent. Les empreintes ci-dessous ont été relevées sans navigation, par lecture CDP des sources déjà chargées ; le débogueur de cette lecture a été désactivé et sa connexion détachée, sans toucher au superviseur.

| Module | SHA-256 | Preuve à cet instant |
| --- | --- | --- |
| `analytics.js` | `237c546ac32fc7270d4c4dce414e9de430ac035fc51333eb4f5f72b214ae2d57` | Source chargée dans Chrome via `Debugger.getScriptSource`, identique au disque |
| `analytics_policy.js` | `2c06777aa3ce6a5d3a64a581658d1fc41f48c6c651d0877b050c4c6d72e2bc47` | Source chargée dans Chrome, identique au disque |
| `analytics_frame.js` | `7dc88b50f4b03dd3f1a24676338563ce7ccd1ff38c6222e4e9a54312c4dd5765` | Fichier candidat ; exécution différée jusqu’au consentement, non revendiquée à cet instant |
| `analytics_transport.js` | `e9e4a04efb568b66529ae5486c14fcbb9d46744f394822fb690dcf831a8e9185` | Fichier candidat ; exécution différée jusqu’au consentement, non revendiquée à cet instant |

Pièce locale : `var/analytics-manual/20261028-083431Z-91281b44/module-fingerprints.json`. Les 45 sources/configurations/tests correspondent toujours au manifeste du 21 octobre. Cette vérification identifie le candidat présent ; elle ne certifie pas rétroactivement toutes les sources exécutées lors des anciennes recettes.

**Parcours autorisé pour cette session, une seule fois :** accepter sur l’accueil, attendre environ dix secondes, cliquer **Découvrir la séance**, attendre environ dix secondes, cliquer **Réserver**, puis rester sur la connexion sans saisir ni soumettre de formulaire. Ne pas exécuter l’étape catalogue/AJAX du parcours générique plus bas. Les quatre noms applicatifs attendus sont `page_view`, `discover_session_click`, `view_service`, `reserve_click` ; les volumes constatés seront lus dans cette session, sans reprendre ceux du 21 octobre.

Dans le navigateur habituel, ouvrir **DebugView** de la propriété de recette et **vérifier le sélecteur d’appareil après ce changement de profil**, même si plusieurs entrées s’appellent « Google ». Le troisième appareil historique n’est pas nécessairement le bon aujourd’hui. Conserver une capture avec propriété, date/heure, appareil sélectionné et les quatre noms/compteurs ; distinguer toute observation dans Temps réel ou les rapports standards. Une commande, une requête et sa réponse HTTP ne remplacent pas cette preuve dans l’interface. La réception était à confirmer à ce stade de préparation ; la confirmation ultérieure et le rapprochement figurent dans la clôture ci-dessus.

**Observation pendant la prise en main, 09:36:48 Paris :** le statut est toujours `running`. Le parcours a commencé pendant la finalisation documentaire : commandes et événements tentés `page_view: 4`, `view_service: 2`, `reserve_click: 1` ; aucun `discover_session_click` observé à cet instant. Les réponses HTTP du rapport actif couvrent alors `page_view: 3` et `view_service: 1`. Ces compteurs ne constituent ni une confirmation dans DebugView ni une réponse supposée aux requêtes restantes. Aucun parcours supplémentaire n’est lancé ou demandé sur cette seule observation.

Le succès désormais rapporté confirme la réception après correctif dans ce contexte ; il **ne démontre pas que headless explique l’absence historique**. Chrome a aussi changé de version (155 lors des recettes du 21, 156 ici). Le critère technique de retrait accepté et ses limites restent inchangés ; aucune validation globale ni activation production n’est accordée. Le champ statique `global_acceptance` du rapport manuel conserve une ancienne mention du défaut multi-onglets : ce libellé historique du harnais, non modifié ici, n’est pas une nouvelle reproduction ; se référer à la décision et aux preuves du document de retrait.

Arrêt exécuté à la demande de l’utilisateur pour cette session :

```powershell
python tests/Analytics/manual_recipe.py stop
```

### Première ouverture interrompue, distincte de la session clôturée ci-dessus

La session `20261028-081422Z-e8fa67c9`, démarrée à **09:14:22 Paris**, a enregistré une acceptation à **09:14:39.086**, pendant les vérifications en lecture de l’agent, sans clic ni modification de consentement par celui-ci. Une seule `page_view` apparaît : une commande, une requête POST vers `region1.google-analytics.com/g/collect` pour `G-7ZGZR4L2F8`, une réponse **HTTP 204** ; `ep.debug_mode=true` observé, sans preuve d’affichage GA4 à ce stade. Aucun clic métier dans le dernier rapport. Les quatre modules chargés avaient les mêmes empreintes que ci-dessus, y compris cadre et transport. Les valeurs d’identifiants ne sont pas publiées.

Le superviseur a ensuite signalé **`RuntimeError: CDP disconnected`** et a fermé ses processus (`processes_stopped: true`). Dernier instantané actif : **09:30:09 Paris** ; cet instant ne fixe pas à lui seul l’heure exacte ni la cause de la déconnexion. La vérification Windows suivante n’a trouvé ni ces processus ni les ports en écoute. Aucun lien causal avec le code applicatif n’est établi. Le rétablissement a créé le nouveau profil/session indiqué en tête ; aucun consentement n’a été recopié. Preuves distinctes : `var/analytics-manual/20261028-081422Z-e8fa67c9/report.json`, `report-live.json`, `module-fingerprints.json` et NetLog privé (ne pas partager).

La vérification initiale dans GA4 fournie par l'utilisateur le 21 octobre ne montrait aucun événement pour la période incluant le 19 octobre, et le flux affichait « Aucune donnée reçue ». Propriété « France Tuncq — Recette », ID `G-7ZGZR4L2F8`, mesures améliorées désactivées ; seul filtre « Internal Traffic » en état **Test**, aucun filtre actif. La réception de la session manuelle du 21 octobre est désormais confirmée dans DebugView, comme détaillé ci-dessous. L'absence des données des essais du 19 octobre reste inexpliquée. Aucun lien causal avec le mode headless n'est établi : le navigateur, le profil neuf, le moment et le mode debug diffèrent aussi dans ce diagnostic.

Cette étape concernait uniquement la réception. **À sa clôture, l'anomalie de retrait multi-onglets restait ouverte et la recette globale non validée.** L'intervention ultérieure est décrite séparément dans [le correctif des transports et ses vérifications](analytics-ga4-retrait-onglets.md). Elle ne rouvre pas le diagnostic de réception et ne valide pas la production.

## Réception confirmée et clôture — 21 octobre 2026

L'utilisateur confirme, captures DebugView jointes à l'échange du 21 octobre 2026 à l'appui, la réception dans la propriété **France Tuncq — Recette** après sélection du **troisième appareil nommé « Google »** parmi les quatre affichés. Les compteurs correspondent à la session `20261021-073956Z-940fdf46`, démarrée à **09:39:56, heure de Paris**. Les événements antérieurs de 09:28:32 ne sont pas attribués à cette session.

| Événement | Commandes applicatives | Tentatives transportées | Événements couverts par HTTP 204, après NetLog | Affichés dans DebugView |
| --- | ---: | ---: | ---: | ---: |
| `page_view` | 5 | 5 | 5 | 5 |
| `discover_session_click` | 2 | 2 | 2 | 2 |
| `view_service` | 2 | 2 | 2 | 2 |
| `reserve_click` | 2 | 2 | 2 | 2 |
| `first_visit` | — | — | — | 1 |
| `session_start` | — | — | — | 1 |

Soit **13 événements affichés**, dont les **11 événements applicatifs** attendus. `first_visit` et `session_start` sont des événements automatiques affichés par GA4, sans commandes applicatives ni requêtes distinctes de ces noms dans les captures. Les 11 événements applicatifs sont transportés dans **7 requêtes POST** : cinq individuelles et deux lots de trois événements. Il ne s'agit donc pas de 11 réponses HTTP indépendantes.

Les deux `reserve_click` n'avaient aucune réponse observée par CDP dans `report-live.json`. Après arrêt, le NetLog finalisé confirme séparément leurs réponses :

| `reserve_click` | Début réseau NetLog (Paris) | Réponse NetLog (Paris) | Statut |
| --- | --- | --- | --- |
| Premier parcours | 09:42:36.699 | 09:42:36.722 | HTTP 204 |
| Second parcours | 09:44:39.050 | 09:44:39.101 | HTTP 204 |

Ces heures sont celles du **21 octobre 2026, UTC+02:00**. Les transactions NetLog 1314 et 2103 ciblent `region1.google-analytics.com/g/collect`, exclusivement `G-7ZGZR4L2F8`. Le rapport final conserve `status: null` pour ces deux réponses CDP et ajoute `netlog_status: 204` ; aucune réponse manquante n'est supposée. Les sept requêtes de collecte ont désormais une réponse 204 observée. Les identifiants navigateur/session ne sont pas reproduits ici.

**La preuve réseau et la preuve dans l'interface sont distinctes** : le NetLog démontre les réponses HTTP ; les captures et la confirmation de l'utilisateur démontrent les compteurs dans DebugView, y compris les deux `reserve_click`. `ep.debug_mode=true` seul n'était pas une preuve suffisante. **La réception dans Temps réel et dans les rapports standards n'est pas confirmée par ces captures.** Les champs génériques du rapport automatique demandant une confirmation de l'interface restent inchangés ; la présente section consigne la confirmation manuelle postérieure.

Après chaque changement de profil Chrome, **vérifier à nouveau le sélecteur « Appareil de débogage »** : plusieurs appareils peuvent porter le même nom « Google ». Le troisième était le bon dans cette observation ; cette position n'est pas un identifiant stable ni une règle pour les prochaines recettes.

La commande `python tests/Analytics/manual_recipe.py stop` a terminé avec succès. Le rapport final est daté de **09:57:31, heure de Paris**, avec `status: stopped`, `error: null`, `processes_stopped: true`. Les vérifications Windows confirment l'absence du superviseur et des processus Chrome/PHP de cette session ; les ports 8021 et 9233 sont fermés. Aucun navigateur habituel n'a été arrêté. Le NetLog final contient les mêmes sept requêtes de collecte : aucun nouvel envoi Analytics n'a été déclenché pour cette clôture.

Pièces locales : `var/analytics-manual/20261021-073956Z-940fdf46/report.json` (expurgé) et `netlog-private.json` (brut, ne pas partager). Cette étape ne modifie que la documentation ; les paramètres habituels restent désactivés. **Le défaut d'envoi après retrait entre onglets reste ouvert, et la recette globale n'est pas validée pour la production.** La confirmation du 21 octobre ne résout pas l'absence des données du 19 octobre et ne démontre aucune responsabilité du mode headless.

## Lancer et arrêter

Depuis PowerShell :

```powershell
Set-Location C:\Formations\appointment-sym
python tests/Analytics/manual_recipe.py start --authorize-real-ga4 G-7ZGZR4L2F8 --debug-mode
```

Le lanceur ouvre **Chrome visible** sur **http://localhost:8021/** dans un profil vierge et distinct. Il retourne quand le statut est `running`. Un processus de supervision continue à servir les interceptions réseau ; le serveur reste disponible **sans limite de durée automatique**, même après fermeture du terminal de lancement. Aucun consentement n'est accepté automatiquement. Ne pas relancer si une session est déjà active.

État et compteurs :

```powershell
python tests/Analytics/manual_recipe.py status
```

Arrêt explicite, depuis un autre terminal si nécessaire :

```powershell
python tests/Analytics/manual_recipe.py stop
```

Cette commande demande au superviseur de fermer son Chrome et son serveur PHP, puis attend l'accusé d'arrêt et l'écriture du rapport. Elle ne tue aucun processus désigné par un PID ancien ni le navigateur habituel. Garder la fenêtre de recette ouverte : fermer Chrome, ou perdre la connexion de supervision DevTools, arrête également le serveur pour ne pas poursuivre une recette sans interception. Ne pas fermer de force le superviseur : il maintient les interceptions. En cas d'échec signalé, consulter `worker.log` dans le répertoire indiqué par `status`, puis relancer avec `start`.

Pour une seconde session **sans debug**, arrêter puis relancer la même commande sans `--debug-mode`. Chaque lancement crée un nouveau profil ; cela ne constitue pas, à lui seul, une comparaison contrôlée headless/visible.

## Parcours court

1. Dans le **navigateur habituel**, ouvrir la propriété de recette dans **Temps réel** et **DebugView**. Le navigateur isolé bloque volontairement les destinations autres que le site local et la collecte autorisée : ne pas y ouvrir l'administration Analytics ni s'y connecter à Google.
2. Sur l'accueil local, vérifier le bandeau, puis choisir **Accepter**. Avant ce choix, il ne doit y avoir ni bibliothèque Google ni commande Analytics. Attendre environ dix secondes après l'accord ; lancer `status` pour consulter commandes, tentatives réseau et réponses HTTP séparément.
3. Cliquer sur **Découvrir la séance**, attendre environ dix secondes sur la fiche, puis cliquer sur **Réserver** pour atteindre la connexion. Ne rien saisir ni soumettre. Événements attendus selon le parcours : `page_view`, `discover_session_click`, `view_service`, `reserve_click`.
4. Revenir à l'accueil, puis visiter `http://localhost:8021/rendez-vous/types` et ouvrir une fiche du catalogue AJAX si nécessaire. Garder une seule fenêtre de parcours pour ce diagnostic de réception.
5. Dans GA4, vérifier le sélecteur « Appareil de débogage » après chaque changement de profil Chrome, même si plusieurs entrées portent le même nom. Sélectionner l'appareil correspondant à la session courante et relever l'heure, le nom des événements, le flux et les paramètres affichés. Confirmer séparément ce qui est visible dans Temps réel et dans DebugView, ou leur absence. Aucune modification des filtres, associations ou réglages de propriété n'est effectuée par le harnais.
6. Une fois l'observation terminée, exécuter la commande `stop`. L'anomalie de retrait connue n'est pas réévaluée ni considérée corrigée par ce parcours.

## Debug réservé au harnais

`--debug-mode` est facultatif et désactivé par défaut. Un observateur CDP, présent uniquement dans ce profil de recette, complète **les commandes existantes** `config` et `event` avec `debug_mode: true`. Il ne crée ni commande, ni événement, ni choix de consentement.

Le complément ne s'applique que dans `/audience/frame` sur l'origine locale, avec l'intégration activée pour **l'ID exact de recette**, et après un accord accepté encore valide. Le validateur de consentement est repris du module applicatif, avec ses règles de date/version/expiration. Le code applicatif, les fichiers d'environnement habituels et la configuration de production restent inchangés. Les scripts automatisés ordinaires conservent leur observateur sans décoration debug ; le harnais Google simulé reste inchangé.

Google documente `debug_mode: true` dans la configuration ou sur les événements pour DebugView : [documentation officielle DebugView](https://support.google.com/analytics/answer/7201382). Cette option ne garantit pas à elle seule la réception visible.

## Protections conservées

- Même serveur `real_preview.php`, données synthétiques SQLite en mémoire, mailer nul, Stripe bloqué, routes autorisées et méthodes GET/HEAD seulement. Aucun formulaire métier n'est soumis.
- `GA4_ENABLED=1`, `GA4_MEASUREMENT_ID=G-7ZGZR4L2F8` et le verrou `GA4_REAL_RECIPE` sont injectés uniquement dans le sous-processus PHP. Aucun fichier `.env` privé n'est chargé ou modifié.
- Chaque requête des pages/cadres est interceptée. Bibliothèque permise : `www.googletagmanager.com/gtag/js` avec l'ID autorisé. Collecte permise : `/g/collect` sur les hôtes exacts `region1.google-analytics.com` et `www.google-analytics.com`, avec contrôle de chaque destination dans l'URL et le corps. L'ID production `G-MWZX1GYXZE` est refusé.
- Les nouveaux onglets sont attachés et protégés avant exécution. Les services annexes du navigateur sont restreints par les mêmes règles DNS que la recette précédente. Aucune protection navigateur n'est désactivée ; les CDN non nécessaires restent bloqués et Maps utilise sa réponse locale de remplacement.
- Aucun autre domaine Google n'est autorisé automatiquement. Les tentatives bloquées restent dans le rapport pour identifier précisément une éventuelle dépendance manquante.

## Lire les preuves

`status` indique le répertoire de session sous `var/analytics-manual/`, daté en UTC. Les deux rapports expurgés masquent les valeurs d'identifiants navigateur/session :

- `report-live.json` : mise à jour pendant le parcours, compteurs de commandes, tentatives autorisées, réponses HTTP connues de CDP, commandes avec debug et marqueurs debug observés dans les payloads ;
- `report.json` : rapport à l'arrêt, complété par les réponses du service réseau NetLog, notamment pour les clics dont le cadre a disparu avant la réponse.

Les niveaux de preuve restent distincts :

| Indicateur | Ce qu'il démontre |
| --- | --- |
| `queued_events`, `debug_commands` | Commandes effectivement ajoutées à `dataLayer`, avec leur configuration ; pas une livraison |
| `attempted_events`, `network` | Requêtes autorisées à continuer par l'interception ; une annulation ou l'absence de réponse ne prouve pas l'ingestion |
| `http_acknowledged_events`, `status` / `netlog_status` | Réponse HTTP 200/204 observée ; pas une apparition dans GA4 |
| Temps réel / DebugView | Réception visible à confirmer manuellement par l'utilisateur |

Les profils Chrome, `process.log`, `worker.log` et **`netlog-private.json` ne sont pas des pièces à partager** ; NetLog contient notamment des identifiants bruts. Le rapport garde toujours `validated=false` : ce diagnostic n'accorde aucune validation globale ni autorisation de production.

## Vérifications de préparation

```powershell
python tests/Analytics/manual_recipe.py start --authorize-real-ga4 G-7ZGZR4L2F8 --debug-mode --self-check
vendor\bin\phpunit --bootstrap tests/Prestations/bootstrap.php tests/Analytics/AnalyticsTest.php
php -l tests/Analytics/real_preview.php
git diff --check
```

Le contrôle `--self-check` ouvre Chrome visible, **bloque Google**, teste la décoration debug contre un stockage/DOM synthétiques, vérifie l'absence d'Analytics avant accord dans la page réelle et la protection d'un nouvel onglet, puis ferme ses processus. Résultat exécuté : **11 contrôles réussis**, aucun envoi Analytics réel pendant ce contrôle. PHP : **3 tests, 49 assertions réussies** ; syntaxe PHP/Python, garde de destination et argument d'autorisation obligatoire vérifiés.

Le lancement persistant a ensuite été vérifié : serveur local HTTP 200 après retour du lanceur, puis commande `stop` réussie avec fermeture des deux processus. Dans cette première fenêtre visible, une acceptation a été enregistrée le 21 octobre à **07:28:30 UTC** ; aucune acceptation n'est déclenchée par le lanceur. Une commande `page_view`, une tentative correspondante et une réponse HTTP 204 sont présentes, avec **`ep.debug_mode=true` dans le payload**. Référence expurgée : `var/analytics-manual/20261021-072822Z-ff37a230/report.json`. Ce champ effectivement observé ne prouve pas, à lui seul, que DebugView l'affiche ; ne pas le remplacer dans le compte rendu par un marqueur `_dbg` qui n'a pas été observé.

Une seconde session, `20261021-072930Z-335e638a`, a reçu une acceptation et émis une page vue avec debug et réponse HTTP 204, puis a subi une coupure de connexion DevTools (`WinError 10053`). Le superviseur a arrêté ses deux processus ; la cause de cette coupure n'est pas établie. Le lancement a été repris pour la vérification utilisateur. Le fichier `active.json` et la commande `status` sont la référence pour la session courante. Les heures des rapports sont UTC (ajouter deux heures pour Paris le 21 octobre).

Fichiers de cette adaptation : `tests/Analytics/manual_recipe.py` ajouté ; `tests/Analytics/real_driver.py` adapté avec un observateur facultatif (comportement ordinaire inchangé) ; présent document ajouté et liens/constats mis à jour dans `docs/analytics-ga4.md` et `docs/analytics-ga4-recette-reelle.md`. Aucun changement de la logique applicative, de la configuration habituelle ni de l'index Git.
