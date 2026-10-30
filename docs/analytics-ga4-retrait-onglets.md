# Retrait GA4 entre onglets — correctif et vérifications du 21 octobre 2026

## Actualisation — production du 30 octobre 2026

**Déploiement, activation autorisée puis exécutée et réception des quatre événements dans Temps réel confirmés par l’utilisateur.** Voir le [compte rendu de mise en production](analytics-ga4-production-20261030.md). Les opérations et captures sont rapportées par l’utilisateur et son assistant ; aucune inspection directe du serveur, d’Analytics ou des captures de production par cet agent.

Le critère technique de retrait accepté et ses limites sont inchangés. La réception en production ne constitue ni une nouvelle campagne de retrait ni une garantie universelle ; les rapports standards de production restent non contrôlés et « Personnalité juridique » ouverte.

Les statuts de préparation et de recette qui suivent sont conservés comme **historique daté**, sans annuler les acquis du 30 octobre ni rouvrir les contrôles déjà confirmés. Aucune nouvelle opération serveur n’est autorisée par cette actualisation.

## Historique conservé

**Intégration locale approuvée par délégation — 29 octobre 2026 :** l’utilisateur déclare la délégation de France et approuve les mentions visiteurs avec la formulation « France Tuncq et le prestataire chargé du développement et de la maintenance du site », ainsi que les deux harmonisations. Texte et harmonisations intégrés localement ; [contrôles, livrable isolé et procédure](analytics-ga4-livraison-20261029.md). Cette décision n’est ni une certification juridique ni une autorisation de publication/activation. Collecte désactivée. La rubrique « Personnalité juridique » du DPA demeure **vide / point administratif ouvert**, sans rattachement Marketing Platform ajouté.

**Mentions préparées pour approbation, complément du 29 octobre :** [texte visiteur complet, non publié](analytics-ga4-mentions-france-brouillon.md) et [provenance/recherche officielle](analytics-ga4-controle-rapports.md#8-identité-accès-et-conditions-google--complément-du-29-octobre-2026). Exploitant POTENTIEL CONSULTING SASU, SIRET 91124890400029 ; siège **1 La Fendoire**, correspondance **1B La Fendoire**, 44770 La Plaine-sur-Mer. Contact DPA France Tuncq enregistré ; acceptation des conditions au 16 octobre ; seuls France/Stéphane utilisateurs, associations de produits toutes déclarées vides. Accès Google pour assistance/recommandations distincts ; deux autres options de partage désactivées. **La rubrique « Personnalité juridique » reste vide**, aucun rattachement Marketing Platform effectué. Les conditions publiques désignent Google Ireland Limited pour Analytics standard et décrivent les mécanismes de transfert ; elles ne certifient pas l’exécution propre au compte. Restent le renseignement de l’entité dans le DPA, l’approbation du texte par France, le futur livrable puis une décision d’activation séparée. Réception acquise et critère de retrait accepté inchangés ; aucun nouveau test, template modifié ou activation.

**État au 29 octobre 2026, relevé manuel à 08:19 Paris : réception après correctif acquise dans DebugView et dans l’exploration.** Dans « Recette GA4 — 28 octobre 2026 », propriété **559568920**, période fixe du **28 octobre**, l’utilisateur rapporte **21 événements, tous à 9 h** : `page_view` 8, `view_service` 6, `discover_session_click` 2, `reserve_click` 1, `first_visit` 2, `session_start` 2. Les traces des deux ouvertures manuelles du 28 concordent avec ces volumes : principale 7/6/2/1 et première ouverture interrompue 1 page vue ; chaque ouverture comporte `_fv=1` et `_ss=1`, avec des identifiants navigateur/session distincts comparés localement et masqués. **Concordance agrégée, pas attribution individuelle des lignes GA4.** Le relevé DebugView du 28 (5 `view_service`) est conservé comme observation datée ; le relevé ultérieur en affiche 6, sans démontrer la cause de cette évolution.

L’utilisateur précise avoir probablement effectué davantage de clics que demandé : **le parcours manuel n’était pas strictement contrôlé**. Ces volumes ne prouvent ni un doublon, ni l’absence de doublons, ni l’action physique précise à l’origine de chaque événement ; la réception des quatre types reste acquise. L’absence des essais automatisés des 19/21 octobre demeure un **constat historique inexpliqué**, sans absence générale de réception après correctif ni responsabilité démontrée de headless. Les rapports prédéfinis et Temps réel ne sont pas attestés par cette exploration, mais ne constituent pas un contrôle supplémentaire exigé pour ce critère de réception. Aucune relance demandée. Le critère de retrait accepté et ses limites restent inchangés ; **aucune validation globale ni activation de production**. Voir le [rapprochement du 29 octobre](analytics-ga4-recette-manuelle.md#exploration-confirmée-et-rapprochement-des-deux-ouvertures--29-octobre-2026) et les [seuls prérequis encore ouverts](analytics-ga4-controle-rapports.md#4-prérequis-france-restant-avant-une-activation-distinctement-autorisée).

**État historique à la clôture du 28 octobre 2026 (complété par le relevé du 29 ci-dessus) :** réception des **quatre événements applicatifs après correctif confirmée manuellement dans DebugView**, en mode manuel avec debug, pour `20261028-083431Z-91281b44`. Relevé utilisateur vers **09:41 Paris** : `page_view` 7, `view_service` 5, `discover_session_click` 2, `reserve_click` 1, `first_visit` 1, `session_start` 1 (**17 affichés**). Traces locales : **16 applicatifs** (7/6/2/1) dans **sept POST/204** finalisés, exclusivement `G-7ZGZR4L2F8` ; l’écart d’un `view_service` reste inexpliqué, sans présumer lequel manque dans l’interface. Les deux découvertes sont deux commandes dans deux lots, sans preuve de deux clics physiques ni de doublon. Session arrêtée proprement ; [rapprochement et preuves de clôture](analytics-ga4-recette-manuelle.md#clôture-et-réception-après-correctif-confirmée--28-octobre-2026). **Rapports standards de cette session encore à vérifier ; absence historique des essais automatisés inexpliquée ; aucune responsabilité de headless démontrée.** Critère de retrait accepté inchangé, production non validée, configuration habituelle désactivée. Aucun nouvel envoi pour cette clôture.

Le défaut reproduit est corrigé sur les parcours exercés : la barrière applicative bloque les transports différés avant leur délégation au navigateur. **La recette globale reste non validée pour la production.** Le 21 octobre 2026, après l'analyse ci-dessous, l'utilisateur a explicitement accepté le critère technique limité et ses risques résiduels : voir la décision en fin de document. Le critère réseau initial et ses assertions historiques sont conservés ; aucun code ni rapport de test n'a été réécrit pour transformer un échec en succès.

La réception DebugView des quatre événements, confirmée manuellement pour `20261021-073956Z-940fdf46`, reste acquise et distincte. Aucun nouveau diagnostic manuel de réception ni réglage GA4 n'a été demandé. L'absence des données du 19 octobre reste inexpliquée et aucune responsabilité du mode headless n'est démontrée.

## Reproduction avant correction

Commande : `python tests/Analytics/real_recipe.py --authorize-real-ga4 G-7ZGZR4L2F8 --withdrawal-only`.

Rapport conservé : `var/analytics-real/20261021-100407/report.json`. Le code applicatif n'était pas encore corrigé. L'observateur du harnais avait seulement été complété pour dater les appels natifs, sans décider de leur autorisation.

- Marqueur de retrait conservé **après le retour réussi** de `localStorage.setItem` avec `choice=refused`.
- Appel natif `fetch` pour le lot de l'autre onglet : **+2,600 ms** après ce marqueur.
- Observation `Network.requestWillBeSent` : **+3,200 ms**.
- Réponse **HTTP 204** confirmée dans le NetLog finalisé, transaction 720.
- Cadres détruits et cookies supprimés ; le contrôle interdisant le nouvel envoi échoue. Code de sortie 1, processus arrêtés.

Cette reproduction montre un nouvel appel de transport après le retrait, et non seulement une réponse tardive ou une notification réseau tardive d'un appel antérieur. Google conserve du travail différé en dehors de la file applicative `dataLayer` ; l'opt-out et la destruction du cadre ne suffisaient pas. Le détail de la file interne de Google n'est pas accessible : son fonctionnement exact demeure une hypothèse, tandis que l'appel natif tardif est observé.

## Correction applicative

`analytics_frame.js` installe `analytics_transport.js` **avant le chargement de Google**, uniquement dans le cadre technique. Les fonctions de transport de la page métier ne sont pas modifiées.

Chaque appel couvert relit le consentement avec le validateur existant (`readConsent`) et vérifie la configuration. Absence, refus, date/version/expiration incorrecte, stockage illisible ou configuration inactive ferment la barrière. Aucun accord accepté mémorisé dans une simple variable ni notification `storage` ne suffit à autoriser un envoi.

Le contexte est également lié aux dates de l'accord qui l'a créé. Un autre accord, même valide, ne peut autoriser sa file ancienne. Dès un refus de la barrière, sa fermeture est définitive. Le coordinateur ferme explicitement les transports avant destruction lors d'un retrait et recrée un contexte pour un nouvel accord, en remettant à zéro la déduplication de l'affichage courant. Aucun événement antérieur n'est rejoué ; la page actuelle peut être mesurée une fois.

| API du cadre couverte | Accord valide | Collecte non autorisée |
| --- | --- | --- |
| `navigator.sendBeacon` | Délégation avec récepteur/arguments et retour natifs | `false`, sans délégation |
| `fetch`, y compris `keepalive` | Même Promise native, mêmes arguments/options | Promise rejetée `AbortError`, sans délégation |
| `XMLHttpRequest.send` | Délégation native inchangée | `abort()` et retour `undefined`, sans `send` natif |
| `HTMLImageElement.src` et `srcset` | Accesseurs natifs conservés | Pas d'affectation réseau ; sources supprimées |
| `setAttribute` / `setAttributeNS` sur `src` / `srcset` d'une image | Délégation native inchangée | Pas d'affectation réseau ; sources supprimées |

Les constructeurs nécessaires à la réponse de refus sont conservés avant destruction du cadre : un appel à une ancienne fonction conservée reçoit encore `AbortError`, sans tenter de relire les constructeurs d'une fenêtre détruite.

**Transport réellement observé dans les essais ciblés : `fetch` avec `keepalive: true`.** Les replis beacon, XHR et image sont couverts par les tests déterministes ; ils n'ont pas été observés comme transports de collecte dans ces essais réels. Ce constat n'est pas extrapolé à toutes les versions futures de Google.

Le nettoyage des cookies, la destruction du cadre et les navigations complètes vers le privé restent actifs. Avec un accord valide, la sortie publique vers le privé conserve les cookies ; elle permet la fin d'un transport de métadonnées publiques avant destruction, sans charger de cadre sur le document privé. Aucun délai, callback d'attente ou dépendance métier n'est ajouté à « Réserver ».

## Preuves temporelles et absence de masquage par le harnais

L'observateur distingue les appels entrant dans la protection, leur délégation au transport natif, les observations CDP et les transactions NetLog. L'horodatage natif est pris immédiatement avant `Reflect.apply` ; l'observation est notifiée après délégation, sans lecture de consentement ni décision réseau par cet observateur.

Le filtre de destinations reste indépendant du consentement : une émission tardive autorisée vers le flux de recette continuerait à passer. Ce filtre ne corrige donc pas le défaut à la place de l'application. Les requêtes vers toute autre destination restent interdites.

Les rapports distinguent :

1. Appel refusé par la barrière : aucune délégation native ; pas de requête attendue.
2. Appel natif après le marqueur de retrait : échec, même sans réponse HTTP.
3. Appel natif avant retrait, observation CDP ensuite : la remise au navigateur est antérieure ; l'observation tardive est conservée et le critère réseau initial n'est pas transformé silencieusement en succès.
4. Réponse après retrait à un appel antérieur : réponse tardive, pas nouvel appel. Une assertion déterministe exerce cette distinction avec une Promise native différée.

Le rapport rapproche les URL et sessions CDP des appels natifs, conserve les délais et les cas sans corrélation. NetLog ajoute ses heures de début/réponse et ses statuts ; une réponse CDP absente ne vaut jamais preuve de non-réception. Les valeurs d'identifiants sont masquées dans les rapports partagés. Les NetLog bruts et profils restent privés sous `var/`.

## Essais ciblés après correction

```powershell
python tests/Analytics/real_recipe.py --authorize-real-ga4 G-7ZGZR4L2F8 --withdrawal-only --withdrawal-delays 0,0.1,1,3,4.8,5.2
python tests/Analytics/real_recipe.py --authorize-real-ga4 G-7ZGZR4L2F8 --withdrawal-only --withdrawal-delays 0.05,4.95,5.05
```

Les temporisations partent de la commande `view_service` observée dans le second onglet. Le rapport conserve aussi le délai réellement obtenu, incluant l'ordonnancement et le passage au premier onglet.

| Rapport sous `var/analytics-real/` | Délais réels file → refus, ms | Appels Google bloqués après retrait | Résultat |
| --- | --- | ---: | --- |
| `20261021-101215/report.json` | 46,100 ; 121,000 ; 1038,900 ; 3062,300 ; 4833,700 ; 5245,300 | 5 `fetch` | 31 contrôles réussis |
| `20261021-101945/report.json` | 99,800 ; 4986,200 ; 5088,600 | 2 `fetch` | 16 contrôles réussis |

Dans les neuf essais : **zéro appel natif, zéro observation de nouvelle requête CDP et zéro début de transaction de collecte NetLog après le refus**, cadres détruits, cookies supprimés. Le contrôle réseau original est conservé en plus du contrôle des appels natifs. Aucune exception JavaScript. Les deux derniers délais au-delà de cinq secondes exercent un lot déjà parti avant retrait ; ils ne sont pas présentés comme des blocages.

Exemple de la dernière passe : appel natif **78,300 ms avant** le refus, observation réseau **77,840 ms avant**, réponse CDP **8,602 ms avant**. Il n'y a pas de réponse réseau tardive observée à inventer dans ce cas. Les deux passes ciblées terminent avec code 0 et processus arrêtés. `validated=true` dans ces rapports signifie réussite des contrôles exécutés, jamais validation de production (`production_validated=false`).

## Tests simulés et contrôles locaux

```powershell
python tests/Analytics/mock_recipe.py --transport-only
python tests/Analytics/mock_recipe.py --cookie-lifecycle-only
python tests/Analytics/mock_recipe.py --agreement-only
python tests/Analytics/mock_recipe.py
vendor\bin\phpunit --bootstrap tests/Prestations/bootstrap.php tests/Analytics/AnalyticsTest.php
vendor\bin\phpunit --bootstrap tests/Prestations/bootstrap.php tests/Prestations
php bin/console lint:container
php bin/console lint:yaml config/services.yaml config/packages/twig.yaml
php bin/console lint:twig templates/analytics
php bin/console debug:asset-map analytics_transport
git diff --check
```

La suite simulée finale réussit **27 groupes**, incluant **20 assertions déterministes** : récepteurs/arguments/retours natifs, Promise conservée, rejet/abort, image, cinq états de stockage non autorisés, fermeture définitive, réponse à un appel antérieur, nouvel accord. Un test dans le vrai cadre vérifie aussi qu'une file de l'ancien accord ne passe pas avec un autre accord valide, avant la notification asynchrone. Le test d'une fonction conservée d'un cadre détruit a d'abord révélé un `TypeError` ; la conservation des constructeurs corrige ce comportement et la passe finale reçoit bien `AbortError` sans collecte.

Les parcours simulés couvrent également retrait pendant chargement, retrait privé, expiration, refus, configuration absente/invalide/désactivée, stockage indisponible, non-rejeu, Turbo/cache, AJAX échoué/remplacé, conservation des cookies public/privé/public, clics connecté/anonyme, panne Google, clavier/mobile/thèmes. Rapport : `var/analytics-tests/results.json`, 525 requêtes externes bloquées, **zéro envoi Google réel et zéro erreur JavaScript**. Le nouveau lanceur ferme ses processus même en cas d'échec ; il est distinct de la recette réelle.

PHP Analytics : **3 tests, 49 assertions, code 0**. Prestations : **35 tests, 466 assertions, aucun échec d'assertion**, mais code **1** en raison de quatre dépréciations Symfony préexistantes (`IsTrue`, `NotBlank`, `Length`, `Url`). Les contrôles de conteneur, YAML et Twig passent ; AssetMapper trouve `modules/analytics_transport.js`. Les fichiers Python ont été compilés en mémoire sans exécution de recette. Aucun test n'utilise une base existante ou un mailer réel.

Les passes intermédiaires `20261021-100855` et `20261021-100944` échouaient sur une syntaxe de l'observateur, et `20261021-101012` avant l'initialisation du choix : elles ne prouvent pas le retrait. Le harnais attend maintenant un statut de consentement initialisé, et non simplement la présence du bouton. La première passe réelle complète `20261021-101435` a réussi ses **47 contrôles exécutés**, mais a terminé code 1 sur son dernier clic de refus avant initialisation du gestionnaire privé. Elle reste conservée comme passe incomplète ; l'attente du statut a été ajoutée au helper de retrait.

## Passe réelle complète finale

Commande exécutée après correction de cette attente :

```powershell
python tests/Analytics/real_recipe.py --authorize-real-ga4 G-7ZGZR4L2F8
```

Rapport : `var/analytics-real/20261021-102059/report.json`. **54 contrôles réussis, code 0, aucune erreur JavaScript**, processus arrêtés. Cette passe exécute les parcours de non-régression ; elle ne recommence pas le diagnostic de réception manuelle dans GA4.

| Vérification avec la vraie bibliothèque | Résultat |
| --- | --- |
| Avant accord et après refus | Ni bibliothèque, ni collecte, ni cookie Analytics |
| Accueil, fiche directe, Turbo et hooks répétés | Émissions attendues, nouvelle visite comptée, pas de doublon d'affichage |
| Fiche AJAX | Un `view_service`, pas de `page_view` artificiel |
| Réserver anonyme et connecté | Navigation native complète sans attente ; deux clics réels confirmés HTTP 204 par NetLog |
| Public → privé → public | Identifiant navigateur et identifiant de session conservés dans les deux parcours courts ; aucune collecte privée |
| Retrait courant, privé et pendant chargement ralenti | Cadre/cookies supprimés, aucun nouvel envoi observé ; libération de la réponse retenue sans résurrection |
| Retrait dans l'autre onglet | Un `fetch` de Google refusé par l'application avant délégation ; aucune nouvelle requête après refus |
| Historique/BFCache | Quatre restaurations `pageshow.persisted=true` observées ; retour après retrait sans cadre/cookies/collecte |
| Panne Google | Échec du chargement réellement injecté, lien Réserver et entrée privée fonctionnels |
| Métadonnées et destinations | Aucun canari privé transmis ; aucune destination non autorisée poursuivie |

Les **17 requêtes POST de collecte**, toutes acquittées HTTP 204 dans le NetLog, couvrent `page_view` **13**, `discover_session_click` **1**, `view_service` **5**, `reserve_click` **3** : **22 événements applicatifs**, certains regroupés. CDP observe 15 réponses ; les deux réponses des clics natifs Réserver sont complétées par NetLog. L'observateur de transports a enregistré **15 appels natifs**, tous `fetch` avec `keepalive: true` ; il n'a pas enregistré ceux des deux clics quittant la fiche. Ne pas confondre ce compteur d'appels observés avec les 17 requêtes réseau, ni attribuer un transport natif non observé aux deux restantes. Le troisième `reserve_click` est le contrôle diagnostique existant maintenant temporairement la fiche ouverte, et non un troisième parcours natif. Aucune vente ni réservation payée n'est mesurée. Seuls `www.googletagmanager.com` (bibliothèque) et `region1.google-analytics.com` (collecte) ont été autorisés vers Google, exclusivement avec `G-7ZGZR4L2F8`.

Expiration et stockage illisible sont exercés par les tests déterministes/simulés ; ils ne sont pas présentés comme des scénarios supplémentaires avec Google réel. Aucune nouvelle confirmation de réception dans l'interface Analytics n'est revendiquée pour cette passe.

## Limites et statut

- La vérification du stockage puis l'appel natif ne forment pas une transaction atomique avec une écriture simultanée dans un autre processus. Le standard recommande de ne pas supposer de verrou inter-clusters pour `localStorage` : [HTML, Web storage](https://html.spec.whatwg.org/multipage/webstorage.html). Les neuf temporisations ne prouvent pas l'absence de toute course à cette frontière. C'est une limite de garantie, pas un échec observé masqué par une tolérance temporelle.
- Une requête déjà confiée au navigateur avant retrait, notamment avec `keepalive`, n'est pas rappelable par cette barrière ; sa réponse peut arriver ensuite. Les observations sont classées sans inventer leur livraison.
- L'interception couvre les API listées et les usages observés. Elle n'intercepte pas des images créées par analyse de HTML (`innerHTML`, `document.write`), `Attr.value`/`setAttributeNode`, CSS, WebSocket, worker, ou une API native obtenue d'un autre contexte. Le cadre de même origine n'est pas une enceinte contre un script tiers hostile. Ces voies ne sont pas utilisées par la collecte observée ; elles restent hors de la garantie, à réexaminer si la bibliothèque évolue.
- Les replis sont testés avec des espions natifs déterministes, pas forcés dans Google en production. Seuls Chrome et la version de bibliothèque effectivement servis ont été exercés.

**Le défaut différé reproduit est corrigé et vérifié dans ce périmètre ; l'exigence stricte universelle reste non démontrée. La recette globale demeure non validée pour la production.** Aucun réglage Google n'a été modifié, ni aucun événement d'achat ajouté. La collecte habituelle reste désactivée ; aucune activation n'est autorisée par ce rapport.

## Fichiers et clôture

Fichiers de cette intervention uniquement (les autres changements préexistants sont conservés) :

- Application : `assets/modules/analytics_transport.js` ajouté ; `assets/modules/analytics_frame.js` et `assets/modules/analytics.js` adaptés.
- Tests : `tests/Analytics/transport_checks.js` et `tests/Analytics/mock_recipe.py` ajoutés ; `tests/Analytics/browser.py`, `tests/Analytics/real_driver.py`, `tests/Analytics/real_recipe.py` adaptés.
- Documentation : présent fichier ajouté ; `docs/analytics-ga4.md`, `docs/analytics-ga4-recette-reelle.md`, `docs/analytics-ga4-recette-manuelle.md` actualisés en conservant les preuves historiques.

Contrôle final : aucun processus Chrome/PHP/superviseur du harnais restant, ports **8019, 8021, 9231, 9233 fermés**, aucune variable d'activation de recette dans le processus parent. `.env.ga4.example` et les valeurs de repli de `config/services.yaml` restent `GA4_ENABLED=0`, ID vide. Aucun fichier d'environnement privé ni le worktree France n'a été modifié. Index Git inchangé, vérifié par SHA-256 ; `git diff --check` passe, y compris un contrôle explicite des espaces des nouveaux fichiers non suivis. Aucun commit, push, déploiement, migration, paiement ou courriel réel. Les bases utilisées sont les fixtures SQLite en mémoire.

## Analyse des critères encore ouverts — 21 octobre 2026

Cette analyse relit le code et les pièces existantes, sans exécuter de recette, ouvrir Chrome, activer GA4 ou envoyer de nouvelles données. **Aucune anomalie applicative reproductible encore ouverte n'est identifiée dans les pièces examinées.** Le défaut différé historique est corrigé sur les scénarios exécutés. La limite de garantie universelle, la confirmation des rapports et les prérequis de déploiement restent distincts. Lors de cette analyse préalable, aucune reformulation n'était adoptée. La décision utilisateur postérieure est consignée en fin de document ; les statuts du tableau ont été actualisés en conséquence, sans validation globale.

### Correspondance entre code et preuves

Le code actuel installe la protection avant Google dans `analytics_frame.js`, relit le choix à chaque transport couvert et rattache le contexte aux dates de l'accord initial. `analytics_transport.js` ferme définitivement la protection après un refus et conserve les constructeurs utilisables après destruction du cadre. `analytics.js` ferme cette protection avant destruction lors du retrait ; une page privée avec accord valide conserve les cookies. Les lectures du consentement, le filtrage des pages et les données envoyées restent dans `analytics_policy.js` et `src/Analytics/AnalyticsConfig.php`.

Références utilisées dans le tableau, chemins relatifs au dépôt :

| Référence | Pièce | Portée temporelle |
| --- | --- | --- |
| A | `var/analytics-real/20261021-100407/report.json` | Reproduction **avant** protection : appel natif +2,600 ms, CDP +3,200 ms, HTTP 204. Ce n'est pas l'état actuel. |
| B | `var/analytics-real/20261021-101215/report.json` | Six temporisations, 31 contrôles, cinq appels bloqués. La dernière modification du module de transport (conservation des constructeurs, 10:13:49 Paris) intervient pendant cette passe : elle ne certifie pas les six essais sur un fichier final identique. |
| C | `var/analytics-real/20261021-101945/report.json` | Trois temporisations après cette modification, 16 contrôles, deux appels bloqués ; observateur final avec corrélation native/réseau. |
| D | `var/analytics-real/20261021-102059/report.json` | Passe complète après les dernières modifications applicatives et du harnais : 54 contrôles réussis, `production_validated=false`. Collecte observée de 10:21:08 à 10:24:46 Paris le 21 octobre, sans décoration debug. |
| E | `var/analytics-tests/results.json` ; `var/analytics-tests/transport-results.json` | Dernière passe simulée : 27 groupes dont 20 assertions déterministes, zéro envoi réel. Ces fichiers sont réutilisés par le harnais : les anciens résultats de 24 groupes cités historiquement dans la documentation ne sont plus leur contenu actuel. |
| F | `var/analytics-manual/20261021-073956Z-940fdf46/report.json` ; [confirmation et captures utilisateur](analytics-ga4-recette-manuelle.md#réception-confirmée-et-clôture--21-octobre-2026) | Réception DebugView **avant** le correctif des transports : 5/2/2/2 événements applicatifs et deux événements automatiques, 13 au total. Les captures sont celles fournies dans l'échange, pas des fichiers de preuve supplémentaires inventés dans le dépôt. |
| G | [Livraison et procédure France](analytics-ga4.md#recette-ultérieure-et-activation-france) ; [contrôles PHP locaux](#tests-simulés-et-contrôles-locaux) | Prérequis de déploiement et comptes rendus des commandes antérieures ; pas une preuve des réglages effectifs de production. |

Le HEAD `2653315d0f9ee2594079f8bfb34b357459aca955` ne désigne pas à lui seul ce candidat : les ajouts/modifications GA4 sont non commités. L'ordre des modifications et des résultats est cohérent avec le code relu ; les rapports ne contiennent toutefois **ni copie des sources, ni manifeste d'empreintes au moment de l'essai**. Les dates de fichiers ne remplacent pas ce manifeste. D et E constituent les preuves finales disponibles ; B reste une preuve de développement intermédiaire. Il n'est pas nécessaire de rejouer les neuf temporisations pour cette seule raison. Lors du gel du candidat à déployer, associer son inventaire/empreinte aux pièces et vérifier tout écart intervenu depuis ; une empreinte calculée aujourd'hui ne certifierait pas rétroactivement les octets servis hier.

La relecture corrige également ci-dessus une erreur documentaire : **17 requêtes réseau**, et non 15 ; **15 observations natives**. Les 54 contrôles et les compteurs d'événements restent inchangés.

### Tableau de validation

« Satisfait » signifie satisfait dans le périmètre décrit, jamais démontré pour tout navigateur et toute concurrence. Les prérequis proposés pour clôturer la validation fonctionnelle sont explicités ; ils ne sont pas de nouvelles anomalies.

| Critère et comportement attendu | Preuve disponible | Statut | Bloquant production ? Pourquoi ? | Action minimale utile |
| --- | --- | --- | --- | --- |
| Interrupteur explicite, ID valide et accord nécessaires ; défaut inactif, pas de bandeau sans objet | E ; `.env.ga4.example`, `config/services.yaml`, `AnalyticsConfig.php` | Satisfait | Non : garde testée et défaut toujours à 0/ID vide | Aucune nouvelle recette ; préserver ces défauts dans le candidat. |
| Avant accord/après refus : aucun chargement ni cookie ; choix six mois, invalidité/expiration/stockage indisponible refusés ; interface accessible | D pour avant accord/refus ; E pour dates, stockage, clavier, mobile et thèmes | Satisfait | Non dans le périmètre Chrome testé ; une certification générale d'accessibilité n'est pas revendiquée | Aucune répétition générale ; élargir uniquement si une cible navigateur supplémentaire est exigée. |
| Routes publiques explicites, métadonnées filtrées, publicité refusée et aucun User-ID ; privé sans cadre ni métadonnées privées | D, E ; `AnalyticsConfig.php`, `analytics_policy.js`, `analytics_frame.js` ; tests PHP consignés en G | Satisfait | Non pour le code/parcours testés ; réglages externes traités séparément ci-dessous | Aucun nouvel envoi pour ces scénarios. |
| Quatre événements, absence de doublons initial/Turbo/historique/AJAX ; aucun événement pour une fiche remplacée/échouée | D pour les parcours réels ; E pour AJAX remplacé/échoué et hooks | Satisfait | Non : quatre événements réseau acquittés après correctif ; une commande en file ne vaut pas une livraison | Conserver les preuves ; pas de campagne identique. |
| Cookies conservés public → privé → public avec accord valide ; identifiants navigateur/session continus sur parcours court ; refus/retrait nettoient | D pour parcours réels et retrait privé ; E pour expiration, invalidité et désactivation | Satisfait | Non : conservation réelle des identifiants, nettoyage exercé ; pas d'exigence que tout le cookie de session reste figé | Aucune action supplémentaire sur les parcours exercés. |
| Critère désormais accepté : relecture avant chaque délégation couverte, blocage définitif si lecture non autorisante ; destruction, nettoyage et absence de rejeu | C, D, E ; A est la reproduction antérieure | Satisfait dans le périmètre vérifié | Non : neuf temporisations réussies, sept tentatives bloquées ; contrôles finaux réel/simulé réussis. Les limites suivantes sont acceptées explicitement | Poursuivre la validation de réception et les prérequis France ; pas de nouvelle recette identique. |
| Critère initial conservé pour mémoire : zéro nouvel appel natif et zéro départ réseau après refus, pour **toute** concurrence | A montre l'ancien défaut ; B/C/D réussissent dans leurs fenêtres ; lecture puis appel séparés dans le code | Limite technique acceptée | **Non au titre du nouveau critère** ; la garantie universelle reste impossible à revendiquer avec cette architecture | Aucune action architecturale demandée ; réouvrir si cette garantie devient à nouveau exigée. |
| Couverture limitée aux transports interceptés ; aucune garantie sur toutes les versions futures de Google | C/D : `fetch keepalive` observé ; E : beacon, fetch, XHR, accesseurs/attributs image ; exclusions ci-dessous | Limite technique acceptée | Non au titre de la décision du 21 octobre ; aucun transport non couvert émettant réellement n'est identifié dans les captures | Réexaminer toute évolution ou anomalie concrète sortant du périmètre ; ne pas présenter la protection comme universelle. |
| Refus, retrait et panne Google ne bloquent pas liens/connexion/entrée de réservation | D et E, navigation réelle connecté/anonyme sans soumission métier | Satisfait | Non : aucun délai Analytics ajouté à la navigation | Aucune recette métier ni paiement nécessaire. |
| Réception des quatre événements dans DebugView | F : 13 événements visibles, dont 11 applicatifs, avant correctif | Satisfait, historiquement | Non pour démontrer que le flux a déjà reçu les quatre événements ; cette preuve ne certifie pas à elle seule l'ingestion après correctif | Pas besoin de refaire DebugView si la réception post-correctif est confirmée par les rapports. Vérifier le sélecteur d'appareil lors d'un futur profil. |
| Réception post-correctif dans une vue Analytics persistante | Exploration du 28 octobre, revue manuelle rapportée le 29 à 08:19 Paris : quatre types applicatifs, 21 événements affichés ; concordance avec les deux ouvertures manuelles et leurs marqueurs initiaux | **Satisfait pour la réception et la concordance agrégée** ; attribution individuelle non démontrée | **Non bloquant pour ce critère** : l’absence historique des essais automatisés reste inexpliquée, sans absence générale de réception après correctif | Conserver le rapprochement du 29 et les relevés datés. Aucun nouveau parcours ni inspection obligatoire d’un autre écran pour redémontrer la réception |
| Réception des quatre noms après correctif dans DebugView, manuel avec debug | Revue manuelle du 28 vers 09:41 : 17 affichés, dont 5 view_service ; sept POST/204 du parcours principal. Exploration rapportée le 29 : 6 view_service, concordants avec les traces | **Satisfait pour les quatre noms** ; ancien écart daté conservé, concordance obtenue dans l’observation ultérieure | Ne vaut pas validation globale ni attribution individuelle des événements ; parcours déclaré non strictement contrôlé | Conserver les deux observations sans réécrire les compteurs du 28 ni attribuer la différence à une cause non prouvée ; aucun nouveau test demandé |
| Affichage Temps réel | Aucune confirmation conservée ; F concerne DebugView | Non vérifié | **Non bloquant en soi**, sauf exigence opérationnelle explicite de cet écran ; ce n'est pas un second transport applicatif à valider | Ne pas relancer pour cet écran seul. Une ancienne fenêtre Temps réel ne se reconstitue pas en rejouant un rapport ; le noter lors d'une future observation autorisée si utile. |
| Réglages France dans les vues inspectées : flux, mesures améliorées, publicité, cookies et conservation | [Revue de captures rapportée par l'utilisateur le 21 octobre](analytics-ga4-controle-rapports.md#7-revue-manuelle-des-réglages-de-production--21-octobre-2026) ; cohérence avec le code relu | Constaté selon le bilan utilisateur | Non pour les états acquis ; pas une recette effective de cette configuration de production | Maintenir la distinction avec la recette. Pages vues grisée non déclarée désactivée ; deux mois côté Google distincts des six mois du choix/cookie. |
| Entité juridique DPA et approbation des mentions France | Associations toutes vides, accès France/Stéphane et contact DPA confirmés le 29 ; conditions publiques étudiées, texte complet préparé ; rubrique Personnalité juridique encore vide | Partiel : constats administratifs acquis, entité à consigner et texte à approuver | Oui pour les éléments restant avant activation ; ne pas redemander associations, accès ni sauvegarde acquise | France : faire consigner POTENTIEL CONSULTING dans le DPA et approuver le brouillon ; aucune nouvelle collecte |
| Candidat présent identifié, assets à préparer pour le déploiement | [Manifeste SHA-256](analytics-ga4-candidat-20261021.json) ; D/E ; contrôles PHP historiques G | Satisfait pour l'inventaire présent ; identité rétroactive et artefact compilé non vérifiés | Le manifeste clôt l'identification locale, pas la construction/vérification du futur livrable. Les quatre dépréciations historiques ne démontrent pas de régression GA4 | Comparer le futur candidat au manifeste et vérifier les assets lors de la préparation autorisée ; ne pas attribuer rétroactivement ces empreintes aux recettes. |

La désactivation serveur reste un changement reçu au prochain document/configuration : elle ne diffuse pas une commande instantanée à tous les onglets ouverts. Cette limite opérationnelle est déjà annoncée dans la procédure de désactivation et ne doit pas être confondue avec le retrait individuel stocké entre onglets. Sous-comptage par refus/bloqueurs/navigation rapide et métriques automatiques influencées par le cadre restent des limites documentées, pas des anomalies nouvelles.

### Retrait : distinguer les instants et la portée de la garantie

On note **W** le retour réussi de l'écriture du refus (le marqueur de recette est pris juste après), **R** la dernière lecture par la protection, **N** l'entrée dans le transport natif, **Q** l'observation CDP/début NetLog et **H** la réponse. Les sondes horodatent au plus près de ces opérations, sans constituer une transaction atomique ni une horloge instrumentant physiquement l'émission de chaque paquet.

- **W < N** : nouvel appel natif après le refus, même si aucune réponse ne suit. C'est le défaut reproduit avant correction en A. Il n'est plus observé dans C/D et les autres essais corrigés.
- **N < W < Q** : délégation antérieure, observation réseau postérieure. Elle peut venir de l'ordonnancement ou d'un départ différé dans le navigateur ; CDP seul ne tranche pas. Il faut conserver la corrélation native/NetLog. Ce cas est conceptuellement distinct et ne doit pas être déclaré rencontré dans les essais si la trace ne le contient pas. Le critère réseau initial, conservé dans les assertions, le refuse s'il apparaît ; le critère désormais accepté classe séparément cette délégation antérieure et son observation ultérieure.
- **N < W < H** : réponse à un envoi antérieur. Elle ne prouve pas un nouvel appel après retrait. Une Promise différée l'exerce dans les tests déterministes ; les réussites ne prouvent pas que toutes les réponses réelles arrivent avant W.
- **R < W < N** : fenêtre de concurrence résiduelle. Même une lecture valide immédiatement avant `Reflect.apply` ne verrouille pas l'écriture d'un autre onglet/processus. Cette fenêtre n'est pas une anomalie reproduite dans les pièces, mais suffit à interdire une garantie universelle. L'événement `storage`, la destruction du cadre et les sondes ne rendent pas les deux opérations atomiques.

La protection couvre `sendBeacon`, `fetch` (dont `keepalive`), `XMLHttpRequest.send`, `HTMLImageElement.src/srcset` et `setAttribute/setAttributeNS` pour ces sources d'image, dans le cadre Analytics. **Seul `fetch keepalive` a été observé nativement dans les essais réels instrumentés** ; les autres chemins sont vérifiés avec espions déterministes. HTML analysé, `Attr.value`/`setAttributeNode`, CSS, workers, WebSocket et API issues d'un autre contexte restent hors couverture. Aucun de ces contournements n'est identifié comme émission réelle dans les captures. La bibliothèque distante n'est pas figée par cette architecture.

Les neuf essais ciblés attendent environ six secondes après chaque refus ; les navigations/cache et chargements ralentis sont exercés en plus dans D/E. Les succès sont des observations finies sous Chrome 155.0.8059.86, avec instrumentation. Celle-ci ajoute du travail et peut influencer l'ordonnancement, même si elle ne bloque pas selon le consentement. Ni ces sondes ni les 54 contrôles ne prouvent l'absence de toutes les courses futures.

### Proposition historique — non adoptée lors de l'analyse, acceptée ensuite

| Élément | Formulation |
| --- | --- |
| Critère initial | Dès l'écriture effective du refus, aucun nouvel appel natif et aucune nouvelle requête de collecte observée CDP/NetLog, quels que soient l'onglet et l'ordonnancement. Les réponses seules sont distinguées des nouveaux envois. |
| Critère proposé | Pour chaque tentative via un transport couvert, relire et valider le consentement du même accord avant délégation. Si cette lecture n'autorise pas la collecte, ne jamais appeler le transport natif et fermer définitivement ce contexte. Fermer/détruire les cadres lors de la propagation du retrait, nettoyer les cookies dédiés et interdire le rejeu après réacceptation. Classer séparément les délégations antérieures, leurs observations et leurs réponses ultérieures. |
| Différence de garantie | Passage d'une interdiction globale fondée sur l'instant W à une obligation de contrôle et de refus à chaque délégation couverte. Le critère proposé **ne garantit pas N < W** lorsqu'un autre processus écrit entre R et N ; il ne rappelle pas une requête déjà déléguée. |
| Risque résiduel | Une délégation concurrente peut encore survenir dans cette fenêtre ; un envoi antérieurement délégué peut démarrer/finir après W ; une évolution de Google utilisant un transport non couvert peut échapper à la barrière. Aucune probabilité ou borne de retard n'est démontrée. |

**L'architecture actuelle ne garantit pas le critère strict universel.** L'analyse préalable concluait que son maintien nécessiterait une solution architecturale démontrable. La décision de l'utilisateur ci-dessous accepte désormais la garantie limitée, sans modifier les résultats historiques. Les assertions réseau plus strictes restent dans le harnais : elles conservent leur valeur diagnostique et leur résultat doit être interprété avec les instants natifs/réseau. Les formulations visiteur à aligner sont préparées en brouillon dans le dossier de contrôle, sans modification des templates.

### Réception et prochaine action minimale

DebugView démontre déjà la réception et l'affichage des quatre événements métier de la session F dans le bon flux, avec les compteurs confirmés et les événements automatiques `first_visit`/`session_start`. Il ne prouve ni la présence dans les rapports standards, ni la configuration de production, ni la réception de D. La décoration `debug_mode` n'est pas utilisée par D : l'absence d'une confirmation DebugView pour D n'est donc pas un test de réception échoué. Les HTTP 204 de D constituent une preuve réseau post-correctif, distincte de l'ingestion visible.

**Ordre recommandé, sans relance de collecte :**

1. Décision désormais acquise : l'utilisateur accepte le périmètre limité décrit ci-dessous. Ce point ne bloque plus la poursuite de la validation technique ; les limites ne sont pas supprimées.
2. Consulter les rapports standards existants du flux de recette pour la passe post-correctif D (21 octobre, 10:21–10:25 Paris), en tenant compte du fuseau de la propriété, de la période, du flux et des filtres. Documenter ce qui peut réellement être rattaché à cette passe. Une confirmation dans ces rapports évite de refaire un parcours DebugView uniquement pour disposer d'une preuve d'ingestion post-correctif. En cas d'absence, diagnostiquer la visibilité/agrégation/configuration avant de proposer un seul nouvel essai répondant à une question précise, avec autorisation distincte. Temps réel n'impose pas une campagne supplémentaire.
3. Avant toute activation, fermer les prérequis France déjà documentés et figer le candidat/les assets associés aux preuves. Aucune nouvelle recette complète identique n'est justifiée par cette analyse seule.

L'absence des données du 19 octobre reste un constat historique inexpliqué ; elle ne démontre pas une perte actuelle après correctif et n'impose pas, à elle seule, une nouvelle campagne. Aucun lien causal avec le mode headless n'est établi. Cette analyse ne valide ni la production ni la conformité globale du site.

Vérifications de l'analyse préalable : lectures de code/JSON uniquement, recomptage hors ligne des événements/requêtes/réponses, comparaison des dates des pièces, vérification de branche/HEAD/index et des valeurs publiques par défaut. Aucun test navigateur/PHP ni commande de recette n'a été relancé. Les fichiers applicatifs et de tests/configuration ont été conservés ; cette analyse préalable avait seulement mis à jour le présent document et le lien de synthèse dans `analytics-ga4.md`. La préparation postérieure à la décision ajoute le dossier de contrôle et le manifeste ci-dessous.

## Décision utilisateur du 21 octobre 2026 — critère technique accepté

Après l'analyse précédente, l'utilisateur accepte explicitement le critère suivant :

> Chaque transport couvert relit le consentement avant délégation. Une lecture non autorisante bloque définitivement ce contexte, avec destruction du cadre, nettoyage des cookies dédiés et absence de rejeu après une nouvelle acceptation.

L'utilisateur accepte également l'absence d'atomicité entre lecture et délégation, les requêtes déjà confiées au navigateur avant retrait et la couverture limitée aux transports interceptés, sans garantie universelle sur les versions futures de Google. Cette acceptation **permet de poursuivre la validation technique ; elle n'autorise aucune activation ni mise en production**.

Statut : critère accepté satisfait sur les vérifications disponibles C/D/E ; limites techniques acceptées et documentées, non assimilées à des anomalies reproduites. La réception des quatre événements dans DebugView reste une preuve antérieure au correctif. Les rapports après correctif, les prérequis France et la validation des mentions restent à clôturer. La recette globale demeure non validée pour la production, la collecte habituelle reste désactivée.

Le [contrôle manuel des données existantes et les brouillons France](analytics-ga4-controle-rapports.md) donne les heures vérifiées, volumes attendus et preuves à conserver. Le [manifeste du candidat présent](analytics-ga4-candidat-20261021.json) identifie les fichiers locaux par SHA-256, sans certification rétroactive des sources exécutées. Aucun changement applicatif, de test, de configuration ou de propriété GA4 n'accompagne cette décision ; aucune campagne ni collecte supplémentaire n'a été lancée.

**Suivi documentaire de la revue Google du 21 octobre :** les réglages rapportés concernent exclusivement la propriété de production **francetuncq.fr / G-MWZX1GYXZE**, pas la propriété de recette **France Tuncq — Recette / G-7ZGZR4L2F8**. Ils ne sont pas rétroactivement validés par C/D/E/F. Leur origine et les limites de preuve sont consignées dans le dossier de contrôle : bilan utilisateur d'une revue de captures avec son assistant, sans inspection directe par cet agent ni nouvelles captures locales. La décision de retrait reste acquise et inchangée ; les autres réserves administratives restent ouvertes. Correction selon le complément utilisateur du 28 octobre : la sauvegarde du panneau de transmission avait été confirmée après réouverture le 21 octobre ; cette réserve est clôturée. Le rapport de recette vide le matin du 21 octobre sera reconsulté par l'utilisateur le 22, sans nouvel envoi. Le manifeste source daté, les rapports historiques et l'index Git sont conservés ; seuls trois documents évoluent, sans changement applicatif ni validation globale.

**Suivi du 28 octobre 2026 :** le constat utilisateur de 08:15 Paris confirme la persistance de l'écart dans l'exploration du 21 : 22 événements à 9 h, aucun à 10 h, inchangés depuis le 22. Le [diagnostic réseau/réception dédié](analytics-ga4-diagnostic-reception-20261028.md) conserve les preuves HTTP de D, les compare aux sessions manuelles reçues et identifie les limites des captures. Il ne rouvre pas le défaut de retrait corrigé et n'attribue pas la non-visibilité au correctif, à headless ou à Google. Cette action a depuis été terminée selon le complément manuel rapporté dans le dossier de contrôle. Une session visible distincte sur le code corrigé est autorisée pour vérifier la réception, sans automatisation du parcours ni changement du critère accepté. Les critères acceptés, le manifeste et les rapports historiques restent inchangés ; collecte toujours désactivée, production non validée.

## Actualisation de la validation — 29 octobre 2026

Le critère de réception après correctif est désormais satisfait dans **DebugView et l’exploration**, selon les revues manuelles rapportées. La table ci-dessus est actualisée sans réécrire les résultats des essais D/E ni les décisions historiques. L’absence de réception visible des essais automatisés reste inexpliquée ; elle ne constitue plus une réserve générale sur la réception du code corrigé. Le parcours manuel du 28 a pu comporter davantage de clics que demandé : ses volumes ne prouvent ni doublon ni attribution à une action physique, et ne remplacent pas les tests contrôlés. Aucune anomalie applicative reproductible nouvelle n’est démontrée par ce rapprochement.

Les seuls prérequis avant activation sont ceux de la [section 4 du dossier France](analytics-ga4-controle-rapports.md#4-prérequis-france-restant-avant-une-activation-distinctement-autorisée). Les mentions à compléter et le futur livrable ne sont pas approuvés par ce rapprochement ; aucune validation globale de production. Le critère technique de retrait accepté est conservé, avec ses limites, sans nouvelle campagne.
