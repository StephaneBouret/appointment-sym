# GA4 — premier lot, livré désactivé

## Actualisation — production du 30 octobre 2026

**Déploiement, activation autorisée puis exécutée et réception des quatre événements dans Temps réel confirmés par l’utilisateur.** Voir le [compte rendu de mise en production](analytics-ga4-production-20261030.md). Les opérations et captures sont rapportées par l’utilisateur et son assistant ; aucune inspection directe du serveur, d’Analytics ou des captures de production par cet agent.

La production est active sur G-MWZX1GYXZE ; les valeurs par défaut des deux dépôts locaux restent à GA4_ENABLED=0. Les rapports standards de production ne sont pas encore contrôlés et la rubrique DPA « Personnalité juridique » reste ouverte.

Les statuts de préparation et de recette qui suivent sont conservés comme **historique daté**, sans annuler les acquis du 30 octobre ni rouvrir les contrôles déjà confirmés. Aucune nouvelle opération serveur n’est autorisée par cette actualisation.

## Historique conservé

**Intégration locale approuvée par délégation — 29 octobre 2026 :** l’utilisateur déclare la délégation de France et approuve les mentions visiteurs avec la formulation « France Tuncq et le prestataire chargé du développement et de la maintenance du site », ainsi que les deux harmonisations. Texte et harmonisations intégrés localement ; [contrôles, livrable isolé et procédure](analytics-ga4-livraison-20261029.md). Cette décision n’est ni une certification juridique ni une autorisation de publication/activation. Collecte désactivée. La rubrique « Personnalité juridique » du DPA demeure **vide / point administratif ouvert**, sans rattachement Marketing Platform ajouté.

**Mentions préparées pour approbation, complément du 29 octobre :** [texte visiteur complet, non publié](analytics-ga4-mentions-france-brouillon.md) et [provenance/recherche officielle](analytics-ga4-controle-rapports.md#8-identité-accès-et-conditions-google--complément-du-29-octobre-2026). Exploitant POTENTIEL CONSULTING SASU, SIRET 91124890400029 ; siège **1 La Fendoire**, correspondance **1B La Fendoire**, 44770 La Plaine-sur-Mer. Contact DPA France Tuncq enregistré ; acceptation des conditions au 16 octobre ; seuls France/Stéphane utilisateurs, associations de produits toutes déclarées vides. Accès Google pour assistance/recommandations distincts ; deux autres options de partage désactivées. **La rubrique « Personnalité juridique » reste vide**, aucun rattachement Marketing Platform effectué. Les conditions publiques désignent Google Ireland Limited pour Analytics standard et décrivent les mécanismes de transfert ; elles ne certifient pas l’exécution propre au compte. Restent le renseignement de l’entité dans le DPA, l’approbation du texte par France, le futur livrable puis une décision d’activation séparée. Réception acquise et critère de retrait accepté inchangés ; aucun nouveau test, template modifié ou activation.

**État au 29 octobre 2026, relevé manuel à 08:19 Paris : réception après correctif acquise dans DebugView et dans l’exploration.** Dans « Recette GA4 — 28 octobre 2026 », propriété **559568920**, période fixe du **28 octobre**, l’utilisateur rapporte **21 événements, tous à 9 h** : `page_view` 8, `view_service` 6, `discover_session_click` 2, `reserve_click` 1, `first_visit` 2, `session_start` 2. Les traces des deux ouvertures manuelles du 28 concordent avec ces volumes : principale 7/6/2/1 et première ouverture interrompue 1 page vue ; chaque ouverture comporte `_fv=1` et `_ss=1`, avec des identifiants navigateur/session distincts comparés localement et masqués. **Concordance agrégée, pas attribution individuelle des lignes GA4.** Le relevé DebugView du 28 (5 `view_service`) est conservé comme observation datée ; le relevé ultérieur en affiche 6, sans démontrer la cause de cette évolution.

L’utilisateur précise avoir probablement effectué davantage de clics que demandé : **le parcours manuel n’était pas strictement contrôlé**. Ces volumes ne prouvent ni un doublon, ni l’absence de doublons, ni l’action physique précise à l’origine de chaque événement ; la réception des quatre types reste acquise. L’absence des essais automatisés des 19/21 octobre demeure un **constat historique inexpliqué**, sans absence générale de réception après correctif ni responsabilité démontrée de headless. Les rapports prédéfinis et Temps réel ne sont pas attestés par cette exploration, mais ne constituent pas un contrôle supplémentaire exigé pour ce critère de réception. Aucune relance demandée. Le critère de retrait accepté et ses limites restent inchangés ; **aucune validation globale ni activation de production**. Voir le [rapprochement du 29 octobre](analytics-ga4-recette-manuelle.md#exploration-confirmée-et-rapprochement-des-deux-ouvertures--29-octobre-2026) et les [seuls prérequis encore ouverts](analytics-ga4-controle-rapports.md#4-prérequis-france-restant-avant-une-activation-distinctement-autorisée).

**État historique à la clôture du 28 octobre 2026 (complété par le relevé du 29 ci-dessus) :** réception des **quatre événements applicatifs après correctif confirmée manuellement dans DebugView**, en mode manuel avec debug, pour `20261028-083431Z-91281b44`. Relevé utilisateur vers **09:41 Paris** : `page_view` 7, `view_service` 5, `discover_session_click` 2, `reserve_click` 1, `first_visit` 1, `session_start` 1 (**17 affichés**). Traces locales : **16 applicatifs** (7/6/2/1) dans **sept POST/204** finalisés, exclusivement `G-7ZGZR4L2F8` ; l’écart d’un `view_service` reste inexpliqué, sans présumer lequel manque dans l’interface. Les deux découvertes sont deux commandes dans deux lots, sans preuve de deux clics physiques ni de doublon. Session arrêtée proprement ; [rapprochement et preuves de clôture](analytics-ga4-recette-manuelle.md#clôture-et-réception-après-correctif-confirmée--28-octobre-2026). **Rapports standards de cette session encore à vérifier ; absence historique des essais automatisés inexpliquée ; aucune responsabilité de headless démontrée.** Critère de retrait accepté inchangé, production non validée, configuration habituelle désactivée. Aucun nouvel envoi pour cette clôture.

## État et périmètre

Implémentation du 19 octobre 2026 dans `C:\Formations\appointment-sym`, branche `main`, HEAD initial `2653315d0f9ee2594079f8bfb34b357459aca955`. Aucun AGENTS.md applicable trouvé. Index initial vide ; l’avatar non suivi `public/images/avatars/6ad0a39fe7f71.png` est préservé. Aucun commit, push ou déploiement. Aucun fichier d’environnement privé modifié.

La recherche dans `src`, `templates`, `assets` et `config` n’a trouvé aucune intégration Analytics/GTM/Ads ou gestionnaire de consentement antérieur. Les cookies de session, CSRF, double authentification et la préférence de thème sont indépendants. Le site comporte déjà d’autres services Google (avis/Maps) : ce lot ne constitue pas un audit global de ces services.

Le worktree France a été consulté uniquement en lecture, à `360d7811c690940dc1fa67ed22f5f2f0873758c3`. Le contrôleur d’accueil, `_featured_appointment.html.twig`, `type_detail.html.twig` et `type_page.html.twig` étaient identiques. France utilise « Réserver un accompagnement individuel » au lieu de « Prendre rendez-vous en accompagnement individuel » dans le titre du catalogue, corrige une apostrophe dans sa description et présente quelques différences éditoriales dans l’accueil (tiret du titre, ordre distance/présentiel). Aucun de ces contenus n’est importé. Aucun obstacle à la couverture des quatre événements n’a été identifié. Si la prestation mise en avant est désactivée ou introuvable, le bloc de repli n’a pas de bouton « Découvrir la séance » : cet événement est alors naturellement absent.

## Configuration

Les valeurs de repli sont dans `config/services.yaml`, l’exemple dans `.env.ga4.example`.

| Variable | Défaut | Déploiement France, après recette autorisée |
| --- | --- | --- |
| `GA4_ENABLED` | `0` | `1` pour activer explicitement |
| `GA4_MEASUREMENT_ID` | chaîne vide | `G-MWZX1GYXZE` |

Identification confirmée dans le bilan utilisateur du **21 octobre 2026** : production = propriété **francetuncq.fr**, flux **site France Tuncq**, URL **https://francetuncq.fr**, ID **G-MWZX1GYXZE**. La propriété **France Tuncq — Recette**, ID **G-7ZGZR4L2F8**, reste distincte. Les constats administratifs de production ne doivent pas lui être attribués automatiquement.

Seules les valeurs `1` et `true` (insensible à la casse) activent l’interrupteur. L’ID doit correspondre à `G-` suivi de dix lettres majuscules ou chiffres. Un ID absent, mal formé ou un interrupteur désactivé donne une intégration inactive, sans exception pour le visiteur. Cette validation syntaxique ne prouve pas l’existence ni l’appartenance du flux. Ni le domaine ni l’ID France ne figurent dans la logique applicative.

Le bandeau n’est pas demandé automatiquement quand la configuration est inactive. « Gérer mes cookies » reste disponible et explique alors que la mesure est désactivée. Les variables doivent être injectées séparément dans chaque déploiement ; ne pas activer les autres environnements avec le flux France.

## Choix et cycle de vie

Le bandeau est non modal, dans le flux de la page, avec deux boutons identiques « Accepter » et « Refuser ». Fermer/Escape n’enregistre aucun choix. Le bouton « Gérer mes cookies » reste dans la base des pages publiques et des écrans de compte/réservation. Aucun runtime Analytics n’est ajouté aux pages d’administration EasyAdmin.

La clé locale `audience-consent` contient uniquement `{version, choice, date, expires}` : version 1, `accepted`/`refused`, deux dates en millisecondes. L’échéance est à six mois calendaires UTC, avec ajustement au dernier jour du mois si nécessaire. Elle n’est pas prolongée par la navigation. Le choix doit être daté, non futur, non expiré, de bonne version et avec l’échéance exacte attendue. Tout autre cas équivaut à l’absence de consentement. Un stockage inaccessible laisse la collecte arrêtée. Aucun identifiant supplémentaire n’est créé pour le choix.

Le consentement est **basique** : aucun script Google ni ping Analytics avant accord, après refus ou sur un document privé. Aucun backlog des interactions. Un clic pendant le chargement de Google est abandonné ; seule la page encore affichée et sa fiche éventuelle peuvent être mesurées une fois la balise prête.

Une page exclue de la mesure ne vaut pas retrait du consentement. Si l’intégration est configurée et le choix accepté encore valide, le passage à la connexion ou à un formulaire privé détruit le contexte Analytics **sans supprimer ses cookies**. Leurs valeurs restent disponibles au retour sur une page publique, sans renouvellement du choix. Aucun cadre, chargement de balise ou événement n’est autorisé sur la page privée. Un refus/retrait, un choix absent, invalide ou expiré, ou une intégration désactivée/non configurée arrêtent au contraire la collecte **et nettoient les cookies dédiés**. L’expiration est surveillée également sur une page privée laissée ouverte, même en l’absence de cadre. Les transitions de navigation appliquent la même distinction.

La balise `gtag.js` s’exécute dans un iframe technique de même origine, `/audience/frame`, sans contenu utilisateur, titre privé, query string ni référent. Il est créé seulement après accord sur une page autorisée. La route refuse les query strings et renvoie 404 si la configuration est inactive. Un accès direct ne démarre aucune balise sans le dialogue avec le coordinateur parent et un choix accepté valide. Les messages vérifient origine, fenêtre émettrice, route, événement et paramètres.

Ce cadre sert à isoler **le cycle de vie** de la balise ; il n’est pas une frontière de sécurité contre un script tiers malveillant de même origine. Il évite qu’un événement automatique ordinaire de la balise lise le document contenant le formulaire privé. Sa suppression détruit également les timers, messages et chargements asynchrones du contexte. Elle est plus forte que le seul retrait d’un élément `<script>`.

Au retrait : opt-out `ga-disable-*` immédiat, file `dataLayer` vidée, cadre détruit, cookies propres à cette intégration supprimés. La suppression couvre le domaine hôte et ses suffixes, avec/sans point initial, la racine et les ancêtres du chemin courant/technique. Les cookies sont configurés **host-only**, chemin `/`, préfixe `audience_G_<ID>_ga`, `SameSite=Lax;Secure`, durée au plus égale au consentement restant, sans prolongation automatique. Les cookies étrangers ne sont pas supprimés. Les autres onglets réagissent au changement de stockage ; chaque envoi revalide le choix. Une vérification périodique traite aussi l’expiration sur une page laissée ouverte. Les données déjà envoyées ne sont pas effacées par un retrait.

Les consentements `ad_storage`, `ad_user_data`, `ad_personalization` restent `denied`. Le code fixe `allow_google_signals: false`, `allow_ad_personalization_signals: false`, `ads_data_redaction: true`, `url_passthrough: false` et `send_page_view: false`. Aucun User-ID, identifiant publicitaire, linker, événement d’achat, montant ou devise n’est ajouté. Google crée ses identifiants Analytics ordinaires seulement après accord.

**Correctif du retrait entre onglets, 21 octobre 2026 :** une protection des transports est installée dans le seul cadre, avant Google. Elle relit le consentement courant à chaque appel de `fetch`, `sendBeacon`, `XHR.send` et des accesseurs d'image couverts, et ferme définitivement l'ancien contexte si le choix n'est plus valide. Le contexte reste lié aux dates de son accord ; un nouvel accord recrée un cadre et mesure seulement l'affichage actuel, sans rejeu. Voir [la preuve avant/après et les limites de couverture](analytics-ga4-retrait-onglets.md). Les essais corrigent l'envoi différé reproduit ; ils ne démontrent pas une garantie atomique universelle entre processus, et la recette globale reste non validée pour la production.

## Routes mesurables

Seules les réponses publiques **GET** rendues avec ces métadonnées sont mesurées. La liste est centralisée dans `src/Analytics/AnalyticsConfig.php` et validée une seconde fois en JavaScript. Les chemins sont générés par le routeur Symfony, jamais copiés depuis la requête.

| Route | Chemin | Paramètre admis / titre fixe |
| --- | --- | --- |
| `app_home` | `/` | aucun ; Accueil |
| `app_about` | `/about` | aucun ; À propos |
| `app_accompagnement_individuel` | `/accompagnement-individuel` | aucun ; Accompagnement individuel |
| `app_contact` | `/contact` | aucun ; Contact ; aucun contenu du formulaire |
| `app_legal` | `/mentions-legales` | aucun ; Mentions légales |
| `app_privacy` | `/politique-de-confidentialite` | aucun ; Politique de confidentialité |
| `app_terms` | `/conditions-generales-de-vente` | aucun ; Conditions générales de vente |
| `app_appointment_types` | `/rendez-vous/types` | aucun ; Prestations |
| `app_appointment_type_detail` | `/rendez-vous/types/{id}` | identifiant public positif, 1 à 10 chiffres ; Fiche prestation |
| `seo_local_page` | `/{slug}` | cinq slugs ci-dessous ; Accompagnement local |

Slugs autorisés : `accompagnement-la-plaine-sur-mer`, `accompagnement-pornic`, `accompagnement-saint-brevin`, `accompagnement-saint-nazaire`, `accompagnement-nantes`.

Tout le reste est exclu par défaut, notamment administration, compte, connexion, inscription, 2FA, réinitialisation, calendrier privé, paiement, gestion des rendez-vous et le cadre Analytics lui-même.

## Événements et données

Tous les appels explicites utilisent `page_location = origine courante + chemin autorisé`, `page_title = titre fixe`, `page_referrer = ''` et `send_to = ID configuré`. Aucune query string, fragment, URL brute, valeur de champ, titre lu dans le DOM, nom de prestation administré, identifiant de rendez-vous/paiement ou donnée d’utilisateur n’est transmise. Le référent est supprimé également sur les chargements du cadre et du script. L’attribution marketing est volontairement abandonnée.

| Événement | Déclenchement | Paramètres métier |
| --- | --- | --- |
| `page_view` | document public réellement rendu, onglet visible | aucun |
| `discover_session_click` | activation du lien du bloc de prestation en accueil | `service_id`, `display_context=home_featured` |
| `view_service` | fiche rendue en accès direct ou après insertion AJAX réussie | `service_id`, `display_context=service_page` ou `catalogue_ajax` |
| `reserve_click` | activation du lien de la fiche, connecté ou anonyme | mêmes deux paramètres et contextes que `view_service` |

« Rendu » signifie présent dans le document, non masqué et dans un onglet visible ; ce n’est pas une mesure du temps passé ni de la visibilité dans le viewport. Les métriques techniques et événements automatiques internes de Google (par exemple session/démarrage/engagement) devront être observés lors de la recette réelle ; les quatre événements ci-dessus sont les seuls ajoutés par l’application.

## Turbo, AJAX et navigation

Un coordinateur JavaScript unique écoute les affichages et les clics. Son module est indépendant du graphe de l’application : une autre dépendance indisponible ne doit pas empêcher le choix de cookies. Aucun contrôleur Stimulus ne charge la balise.

Le document courant et les éléments de fiche affichés servent à dédupliquer `DOMContentLoaded`, `turbo:load`, reconnexions et signal d’insertion. Les prévisualisations Turbo ne sont pas mesurées. Une restauration réelle depuis le cache est un nouvel affichage, contrairement à plusieurs hooks sur le même corps de page. Il n’y a pas de déduplication permanente par URL. Le cadre persiste hors du corps remplacé par Turbo, et n’entre pas dans ses snapshots.

AJAX conserve l’URL et la page vue du catalogue. La requête précédente est annulée et un numéro de requête protège aussi les réponses déjà arrivées/annulées ; seul le résultat courant peut être inséré. Deux frames de rendu et un contrôle de présence/visibilité précèdent `view_service`. Une réponse échouée ou remplacée ne crée aucun événement ; le repli réseau peut aboutir à une vraie nouvelle consultation directe, alors mesurable. Aucun `page_view` artificiel pour une insertion de fiche.

Les frontières public/privé utilisent une navigation complète : `turbo:before-visit` pour les destinations exclues, `turbo-visit-control=reload` dans les réponses privées comme garde-fou des redirections, et `data-turbo=false` sur Réserver. L’ancien hook `showForm` utilise aussi la navigation complète. Le cadre est arrêté à la sortie du document. Les formulaires privés ne sont donc plus insérés sous la balise d’une fiche publique ; aucune logique métier de réservation/paiement n’a été modifiée.

Les clics sont observés sans `preventDefault`, délai, callback bloquant ou attente Google. La défaillance de la balise n’empêche pas d’ouvrir la fiche, la connexion ou la réservation. Un départ très rapide peut perdre un événement : la navigation prime.

## Vérifications locales et reproduction

Les tests PHP utilisent `tests/Prestations/bootstrap.php`, sans Dotenv : base SQLite **en mémoire**, mailer nul, doublures HTTP et Stripe bloqué. Aucun schéma de base existante n’est modifié. Le serveur navigateur n’accepte que GET/HEAD, des routes explicitement listées et les connexions loopback ; il crée des données synthétiques en mémoire. Les formulaires de réservation ne sont jamais soumis.

Commandes depuis le dépôt :

```powershell
vendor\bin\phpunit --bootstrap tests/Prestations/bootstrap.php tests/Analytics/AnalyticsTest.php
vendor\bin\phpunit --bootstrap tests/Prestations/bootstrap.php tests/Prestations
php -l src/Analytics/AnalyticsConfig.php
php -l src/Controller/AnalyticsController.php
php bin/console lint:twig templates/analytics templates/base.html.twig templates/legal/privacy.html.twig templates/appointment/type_detail.html.twig templates/partials/_featured_appointment.html.twig
php bin/console lint:yaml config/services.yaml config/packages/twig.yaml
php bin/console lint:container
git diff --check
```

Navigateur : démarrer `php -d xdebug.mode=off -S 127.0.0.1:8019 -t public tests/Analytics/preview.php`, puis Chrome headless avec `--remote-debugging-port=9231`, un **profil isolé** sous `var/analytics-tests/chrome`, `--disable-background-networking`, `--disable-component-update`, `--disable-sync`. Exécuter `python tests/Analytics/browser.py`. Ne jamais diriger ce test vers un site réel. Le seul ID permis par le harnais est `G-TEST123456` ; le script gtag est remplacé avant tout accès réseau et ses commandes sont capturées localement. Toutes les autres requêtes externes sont bloquées ; les requêtes de capture ne sont pas transmises au serveur.

Résultats : 3 tests Analytics / 49 assertions ; 35 tests prestations / 466 assertions, sans échec mais avec quatre dépréciations préexistantes de contraintes Symfony (`IsTrue`, `NotBlank`, `Length`, `Url`). Contrôles PHP, Twig, YAML, conteneur et diff valides. Les vérifications Chromium couvrent configuration inactive/invalide, absence de balise/cookies avant accord, refus conservé, fermeture sans accord, initialisation unique, absence de rejeu, dates invalides/expiration, retrait après et pendant chargement, suppression des cookies avec variantes de domaine/chemin, conservation des cookies étrangers, Turbo/cache, AJAX remplacé/échoué, nouvelle consultation, Réserver connecté/anonyme, frontière privée, stockage indisponible, panne Google, clavier, mobile et thèmes. Rapport et captures dans `var/analytics-tests/` (ignoré par Git).

**Portée des preuves** : ces tests exécutent réellement Symfony, Turbo, Stimulus et Chromium mais remplacent entièrement gtag. Ils prouvent les commandes, le filtrage et le cycle de vie du code local. Ils ne prouvent ni les requêtes propriétaires de la vraie bibliothèque, ni ses cookies effectifs, ni la configuration de la propriété Google. Aucune requête Analytics réelle et aucun événement vers `G-MWZX1GYXZE` n’ont été envoyés.

### Correctif de revue : conservation des cookies sur les pages privées

Le test ciblé a d’abord été exécuté contre le code précédent et a échoué à l’assertion `Private arrival erased accepted analytics cookies`, reproduisant le défaut à l’arrivée sur `/login`. Le correctif distingue dans `sync()` l’autorisation issue du consentement et de la configuration de l’éligibilité de la page. Il applique cette distinction aux arrêts de navigation et à la surveillance périodique, qui nettoie aussi les cookies après expiration sans iframe présent.

Le mock ne réécrit plus des valeurs fixes : chaque cookie manquant reçoit une valeur aléatoire propre au test ; un cookie existant reste inchangé. Les assertions comparent le nom, le domaine, le chemin et la valeur des deux cookies avant l’entrée privée, sur l’écran privé, puis après réinitialisation de la balise au retour public. Une suppression suivie d’une recréation ne peut donc plus être masquée par le mock.

Commande ciblée, avec le serveur et Chrome isolés décrits ci-dessus :

```powershell
python tests/Analytics/browser.py --cookie-lifecycle-only
```

Résultat exécuté après correction : **8 scénarios réussis**, aucune erreur JavaScript, aucun envoi Google réel. Les deux parcours connexion anonyme et formulaire authentifié vérifient une navigation complète réelle par le lien Réserver, l’absence de cadre et de commandes Analytics en privé (y compris après les hooks de navigation/visibilité), la conservation des valeurs des cookies au retour par le lien d’accueil, puis leur suppression lors d’un retrait depuis le privé. Les six autres scénarios vérifient le nettoyage en privé après absence du choix, choix invalide, expiration sur page ouverte, désactivation, ID absent et ID invalide. Rapport : `var/analytics-tests/cookie-lifecycle-results.json`.

La suite navigateur complète `python tests/Analytics/browser.py` a ensuite été réexécutée : **24 groupes de scénarios réussis**, incluant les 8 nouveaux, aucune erreur JavaScript et aucun envoi Google réel (501 requêtes externes bloquées). Rapport : `var/analytics-tests/results.json`.

Le contrôle PHP `vendor\bin\phpunit --bootstrap tests/Prestations/bootstrap.php tests/Analytics/AnalyticsTest.php` a également été réexécuté : **3 tests, 49 assertions, aucun échec**. `git diff --check` passe. Les autres vérifications du premier lot ci-dessus restent un historique distinct des contrôles de ce correctif. Le mock démontre le comportement de l’intégration locale ; la conservation effective des identifiants par la vraie balise reste à confirmer en recette autorisée.

## Recette ultérieure et activation France

**État historique de réception au 28 octobre 2026 à 08:15, avant la nouvelle session manuelle :** l'utilisateur rapporte à 08:15 Paris une exploration de la propriété de recette **559568920** sur le 21 octobre, inchangée depuis le 22 : **22 événements tous à 9 h** (8 pages vues, 2 de chaque événement métier, 4 first_visit et 4 session_start), aucun à 10 h. Ce total n'est pas celui des **22 événements applicatifs** de la passe finale. Le [diagnostic hors ligne du 28 octobre](analytics-ga4-diagnostic-reception-20261028.md) revérifie les 17 transactions brutes, toutes vers `G-7ZGZR4L2F8`, leurs réponses réseau HTTP 204 et la plage 10:21–10:24 Paris. Aucune mauvaise destination, réponse de collecte synthétique ou nouvelle anomalie applicative n'est démontrée ; la cause de l'écart reste inconnue. Complément utilisateur du 28 octobre : ces contrôles sont terminés, sans segment appliqué ni filtre, avec une pagination compatible ; seul Internal Traffic en Test, historique complet de 18 entrées le 19 octobre et création TESTING_FILTER_MODE. La cause historique reste inconnue. Un parcours manuel visible distinct sur le code corrigé est maintenant autorisé pour vérifier sa réception, sans parcours automatisé. Aucun nouvel envoi ni lancement de navigateur n'a été effectué pour ce diagnostic. **À cet instant, réception après correctif et validation globale encore ouvertes ; confirmation DebugView ultérieure consignée en tête, critère de retrait accepté conservé.**

**État après décision utilisateur du 21 octobre 2026 :** aucune anomalie applicative reproductible encore ouverte n'est identifiée dans les preuves examinées (54 contrôles réels finaux, 27 groupes simulés dont 20 assertions déterministes). L'utilisateur a [accepté explicitement le critère technique de retrait et ses limites](analytics-ga4-retrait-onglets.md#décision-utilisateur-du-21-octobre-2026--critère-technique-accepté) : contrôle avant chaque délégation couverte, fermeture définitive après lecture non autorisante, destruction/nettoyage et absence de rejeu. L'atomicité inter-processus et la couverture universelle ne sont pas garanties ; cette décision clôt leur arbitrage, sans autoriser l'activation. La preuve DebugView reste antérieure au correctif ; les rapports post-correctif et les prérequis France restent à confirmer. Le [dossier de contrôle sans nouvel envoi](analytics-ga4-controle-rapports.md) prépare cette vérification, les mentions à valider et le [manifeste SHA-256 du candidat](analytics-ga4-candidat-20261021.json). **La production reste non validée et la collecte désactivée.** Aucune recette ci-dessous n'a été réexécutée pour cette décision.

**Complément de revue manuelle des réglages, 21 octobre 2026 :** le [bilan détaillé de production](analytics-ga4-controle-rapports.md#7-revue-manuelle-des-réglages-de-production--21-octobre-2026) provient du compte rendu de captures examinées par l'utilisateur et son assistant ; cet agent n'a pas inspecté GA4 et ne dispose pas localement de ces nouvelles captures. Les réglages constatés, la correction visuelle de l'utilisation des données fournies par les utilisateurs et la sauvegarde du panneau **Gérer la transmission de données**, désormais confirmée après réouverture le 21 octobre selon le complément utilisateur du 28 octobre y sont distingués. Conservation événements/utilisateurs : **deux mois**, réinitialisation sur activité désactivée. La ligne **Pages vues** grisée, curseur à droite, **n'est pas documentée comme désactivée** ; `send_page_view: false` et les événements explicites restent nécessaires. La lecture du code ne révèle pas de contradiction avec les états rapportés, sans prouver leur exécution sur le flux France. Ces réglages de production ne sont pas couverts rétroactivement par les recettes de test. Le rapport de recette consulté le matin était vide : relecture prévue par l'utilisateur le **22 octobre** des données du **21 octobre**, sans nouvel envoi. Le manifeste daté reste inchangé.

**Diagnostic manuel du 21 octobre 2026 :** la réception est confirmée par l'utilisateur et ses captures dans DebugView, propriété « France Tuncq — Recette », pour la session `20261021-073956Z-940fdf46` : `page_view` 5, `discover_session_click` 2, `view_service` 2, `reserve_click` 2, plus `first_visit` 1 et `session_start` 1, soit 13 événements affichés dont 11 applicatifs. Ils sont devenus visibles après sélection du troisième appareil nommé « Google » ; vérifier à nouveau le sélecteur après chaque changement de profil Chrome, sans supposer que cette position restera identique. Le [compte rendu manuel](analytics-ga4-recette-manuelle.md) distingue cette preuve visuelle des sept réponses HTTP 204, dont celles des deux `reserve_click` retrouvées dans le NetLog finalisé après arrêt propre. Temps réel et rapports standards ne sont pas confirmés par ces captures. L'absence des données des essais du 19 octobre reste inexpliquée ; aucune responsabilité du mode headless n'est démontrée. Mesures améliorées désactivées, seul filtre Internal Traffic en état Test selon la vérification utilisateur. À la clôture du diagnostic de réception, le retrait multi-onglets restait à corriger. Le [suivi distinct du correctif](analytics-ga4-retrait-onglets.md) conserve la recette globale non validée pour la production.

La recette locale avec la vraie bibliothèque utilise désormais un lanceur **distinct et volontaire** :

```powershell
python tests/Analytics/real_recipe.py --authorize-real-ga4 G-7ZGZR4L2F8
```

Cette commande effectue de vrais envois vers le seul flux de recette `G-7ZGZR4L2F8` (France Tuncq — Recette / Recette locale — appointment-sym), expressément autorisé pour cette intervention. Elle ne doit pas être intégrée aux tests ordinaires ni exécutée pour une simple vérification de syntaxe. Sans l'argument explicite, elle quitte avant de démarrer les processus. Le serveur `real_preview.php` exige aussi trois variables de processus concordantes ; le lanceur les injecte uniquement dans son sous-processus PHP. Aucun fichier `.env` privé ou défaut du projet n'est changé. Le harnais Google simulé reste inchangé.

Le lanceur crée un profil Chrome séparé sous `var/analytics-real/`, un serveur GET/HEAD sur `127.0.0.1:8021`, et utilise le port DevTools `9233` ; il refuse des ports déjà occupés. Il réutilise les fixtures SQLite en mémoire, le mailer nul et l'interdiction Stripe. Aucun formulaire métier n'est soumis. Il ferme ses propres processus en fin d'exécution, y compris en cas d'échec. Le rapport JSON expurgé distingue les commandes, les tentatives réseau et les réponses HTTP, y compris les beacons dont les réponses ne sont visibles que dans NetLog après disparition du cadre. Les profils navigateur et `netlog-private.json` bruts ne sont pas des pièces à partager. Les options `--native-click-only` et `--withdrawal-only` permettent les diagnostics ciblés documentés dans le compte rendu.

Les requêtes de page sont interceptées **avant départ**. Seuls `https://www.googletagmanager.com/gtag/js?id=G-7ZGZR4L2F8` et `/g/collect` sur les hôtes exacts `www.google-analytics.com` et `region1.google-analytics.com` sont autorisés, avec contrôle de chaque ID dans l'URL et les lignes du corps. Les autres destinations sont bloquées. La dépendance applicative Maps est remplacée localement pour permettre le chargement du graphe JavaScript ; la bibliothèque Analytics, elle, est réelle. La réception HTTP ne prouve pas l'apparition des événements dans l'interface GA4.

Les connexions annexes internes à Chrome sont également restreintes par résolution DNS aux hôtes de recette explicitement autorisés, sans désactiver une protection navigateur. La [recette réelle du 19 octobre 2026](analytics-ga4-recette-reelle.md) conserve la preuve historique du lot différé envoyé après refus et acquitté HTTP 204. Le [correctif et sa recette du 21 octobre](analytics-ga4-retrait-onglets.md) bloquent ce transport dans les essais ciblés et détaillent la couverture, les résultats et les limites résiduelles désormais acceptées explicitement. **La recette globale demeure non validée pour la production** : la réception après correctif est désormais acquise dans DebugView et l’exploration ; seuls les prérequis d’activation actualisés dans le dossier France restent ouverts. Aucun critère ni résultat de la recette historique n'a été supprimé pour obtenir un succès ; le critère d'acceptation courant est distingué de ces assertions historiques.

1. Sur un environnement de recette autorisé, utiliser d’abord un flux de test distinct. Ne pas remplacer le mock local par le flux France. Rejouer les cas ci-dessus avec la vraie balise uniquement après autorisation explicite.
2. Conserver les réglages de production constatés dans le bilan du 21 octobre : flux/URL/ID France confirmés, **mesures améliorées désactivées** et six détections de la balise désactivées (historique du navigateur, défilements, clics sortants, formulaires, vidéos, téléchargements). Distinguer la première ligne **Pages vues**, grisée avec son curseur à droite, qui n'est pas prouvée désactivée. La maîtrise applicative reste `send_page_view: false` et les événements explicites. Il s'agit de constats utilisateur, pas d'une inspection du compte depuis le dépôt.
3. Google Signals, données fournies par l'utilisateur, personnalisation publicitaire, données précises de localisation/appareil, multidomaine et règles d'événements : états désormais documentés pour les vues de production inspectées. Aucun Ads affiché et une destination visible ne constituent pas un audit exhaustif de toutes les associations. **La sauvegarde du panneau de transmission, les associations de produits toutes vides et les accès France/Stéphane sont confirmés manuellement. Le prestataire et les mécanismes de transfert sont documentés dans le dossier France. Restent le renseignement de l’entité dans le DPA (rubrique vide) et l’approbation du brouillon complet, puis la préparation du livrable et l’autorisation distincte d’activation.** La durée de deux mois et l'absence de réinitialisation de la conservation utilisateurs sont acquises selon le bilan ; ne pas les confondre avec les six mois du choix/cookie. Aucune fonctionnalité publicitaire ou User-ID ne doit être ajoutée. Les brouillons restent non publiés, sans changement de template dans cette étape.
4. En navigateur propre, ouvrir Network et Application/Storage : zéro `gtag/js`, `g/collect` et cookie Analytics avant accord et après refus. Après accord, examiner **tous** les payloads, pas seulement le `dataLayer` : `dl`, `dr`, `dt`, paramètres métier et événements automatiques. Injecter des canaris synthétiques dans query/fragments, URL de réinitialisation et titres privés ; ils ne doivent apparaître dans aucune requête. Vérifier les cookies effectifs, leur préfixe, domaine, chemin et échéance.
5. Lors d'une nouvelle recette justifiée par un écart et autorisée, vérifier retrait pendant/après chargement, Turbo, session connectée/anonyme, AJAX, autre onglet et BFCache selon le périmètre concerné. Les parcours déjà couverts ne sont pas à répéter systématiquement. Vérifier conservation des cookies avec accord valide et nettoyage après retrait/expiration. Observer les envois différés/unload en distinguant lecture du choix, délégation native, observation réseau et réponse. **Ne pas activer si un canari privé est transmis ou si un transport couvert est délégué malgré une lecture non autorisante.** Appliquer le critère accepté et ses limites, sans assimiler une réponse tardive à un nouvel appel. Ne soumettre aucun paiement pour cette recette.
6. Si utile pour les rapports, déclarer `service_id` et `display_context` comme dimensions personnalisées de portée événement. Ne pas configurer `reserve_click` comme vente, `purchase` ou réservation payée. Les clics sont des intentions.
7. Après validation et autorisation de production, injecter `GA4_MEASUREMENT_ID=G-MWZX1GYXZE`, puis `GA4_ENABLED=1` via la configuration habituelle du déploiement. Construire les assets AssetMapper (les deux entrées Analytics doivent être incluses), renouveler le cache Symfony et les workers selon la procédure habituelle. Faire une vérification réseau limitée et autorisée en production, puis vérifier les événements/dimensions dans GA4. Rien de cela n’a été exécuté dans ce lot.

## Désactivation

Passer `GA4_ENABLED=0`, appliquer la configuration et renouveler les caches/processus de déploiement. L’ID peut rester présent ou être vidé. Vérifier une nouvelle page : pas de demande d’accord, aucun cadre/balise et aucune requête Analytics. Les cookies préfixés accessibles sont nettoyés ; la préférence locale peut rester mémorisée jusqu’à son échéance. Cette désactivation serveur prend effet au prochain document/navigation recevant la nouvelle configuration : elle ne pousse pas un ordre instantané aux onglets déjà ouverts. Pour un retrait individuel immédiat, utiliser « Gérer mes cookies » → « Refuser ». Un rollback des fichiers doit inclure les templates, modules, configuration et assets correspondants, sans toucher aux données métier.

## Limites

Refus, absence de réponse, bloqueurs, panne réseau, stockage indisponible, arrivée/départ avant chargement de la balise et navigation rapide causent un sous-comptage volontaire. Le cadre masqué peut influencer les métriques automatiques d’engagement de Google ; ce lot vise les événements explicites, pas la durée d’engagement. Le référent vide et l’absence de paramètres marketing empêchent une analyse d’attribution fiable. Les choix sont propres au navigateur/origine, sans synchronisation utilisateur. Les cookies HttpOnly ou d’un autre domaine sont inaccessibles au JavaScript ; cette intégration n’en crée pas. Aucun effacement des données historiques GA4 n’est effectué.

Le bouton de choix concerne la mesure d’audience de ce lot, pas l’audit ou la certification de tous les cookies et tiers du site. Aucun changement Stripe, migration, traitement de réservation/paiement ou envoi de courrier n’est livré. La conversion « réservation effectivement payée » reste hors périmètre.

## Fichiers

- `src/Analytics/AnalyticsConfig.php`, `src/Controller/AnalyticsController.php` : configuration, liste des routes et cadre technique.
- `config/services.yaml`, `config/packages/twig.yaml`, `.env.ga4.example`, `importmap.php` : valeurs de repli et câblage.
- `assets/modules/analytics.js`, `analytics_policy.js`, `analytics_frame.js`, `analytics_transport.js` : consentement, validation, runtime gtag et protection des transports du cadre.
- `assets/controllers/appointment_type_controller.js` : protection des requêtes AJAX remplacées et frontière du formulaire privé.
- `assets/styles/app.css`, `templates/base.html.twig`, `templates/analytics/_consent.html.twig`, `templates/analytics/frame.html.twig` : interface et chargement.
- `templates/partials/_featured_appointment.html.twig`, `templates/appointment/type_detail.html.twig` : points d’instrumentation.
- `templates/legal/privacy.html.twig` : remplacement des anciennes mentions de consentement implicite et de balises publicitaires.
- `tests/Analytics/AnalyticsTest.php`, `tests/Analytics/preview.php`, `tests/Analytics/browser.py` : tests isolés et harnais réseau.
- `docs/analytics-ga4.md` : présent dossier de livraison.

Références techniques consultées : [configuration GA4](https://developers.google.com/analytics/devguides/collection/ga4/reference/config) pour les paramètres de page, cookies et publicité ; [confidentialité et opt-out](https://developers.google.com/tag-platform/security/guides/privacy) pour `ga-disable-*` ; [consentement Google](https://developers.google.com/tag-platform/security/guides/consent) pour les états de consentement. Ces documents ne valident pas les réglages du compte France.
