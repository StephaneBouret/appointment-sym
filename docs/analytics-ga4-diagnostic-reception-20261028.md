# GA4 — écart réseau / exploration, diagnostic du 28 octobre 2026

**État au 29 octobre 2026, relevé manuel à 08:19 Paris : réception après correctif acquise dans DebugView et dans l’exploration.** Dans « Recette GA4 — 28 octobre 2026 », propriété **559568920**, période fixe du **28 octobre**, l’utilisateur rapporte **21 événements, tous à 9 h** : `page_view` 8, `view_service` 6, `discover_session_click` 2, `reserve_click` 1, `first_visit` 2, `session_start` 2. Les traces des deux ouvertures manuelles du 28 concordent avec ces volumes : principale 7/6/2/1 et première ouverture interrompue 1 page vue ; chaque ouverture comporte `_fv=1` et `_ss=1`, avec des identifiants navigateur/session distincts comparés localement et masqués. **Concordance agrégée, pas attribution individuelle des lignes GA4.** Le relevé DebugView du 28 (5 `view_service`) est conservé comme observation datée ; le relevé ultérieur en affiche 6, sans démontrer la cause de cette évolution.

L’utilisateur précise avoir probablement effectué davantage de clics que demandé : **le parcours manuel n’était pas strictement contrôlé**. Ces volumes ne prouvent ni un doublon, ni l’absence de doublons, ni l’action physique précise à l’origine de chaque événement ; la réception des quatre types reste acquise. L’absence des essais automatisés des 19/21 octobre demeure un **constat historique inexpliqué**, sans absence générale de réception après correctif ni responsabilité démontrée de headless. Les rapports prédéfinis et Temps réel ne sont pas attestés par cette exploration, mais ne constituent pas un contrôle supplémentaire exigé pour ce critère de réception. Aucune relance demandée. Le critère de retrait accepté et ses limites restent inchangés ; **aucune validation globale ni activation de production**. Voir le [rapprochement du 29 octobre](analytics-ga4-recette-manuelle.md#exploration-confirmée-et-rapprochement-des-deux-ouvertures--29-octobre-2026) et les [seuls prérequis encore ouverts](analytics-ga4-controle-rapports.md#4-prérequis-france-restant-avant-une-activation-distinctement-autorisée).

**État historique à la clôture du 28 octobre 2026 (complété par le relevé du 29 ci-dessus) :** réception des **quatre événements applicatifs après correctif confirmée manuellement dans DebugView**, en mode manuel avec debug, pour `20261028-083431Z-91281b44`. Relevé utilisateur vers **09:41 Paris** : `page_view` 7, `view_service` 5, `discover_session_click` 2, `reserve_click` 1, `first_visit` 1, `session_start` 1 (**17 affichés**). Traces locales : **16 applicatifs** (7/6/2/1) dans **sept POST/204** finalisés, exclusivement `G-7ZGZR4L2F8` ; l’écart d’un `view_service` reste inexpliqué, sans présumer lequel manque dans l’interface. Les deux découvertes sont deux commandes dans deux lots, sans preuve de deux clics physiques ni de doublon. Session arrêtée proprement ; [rapprochement et preuves de clôture](analytics-ga4-recette-manuelle.md#clôture-et-réception-après-correctif-confirmée--28-octobre-2026). **Rapports standards de cette session encore à vérifier ; absence historique des essais automatisés inexpliquée ; aucune responsabilité de headless démontrée.** Critère de retrait accepté inchangé, production non validée, configuration habituelle désactivée. Aucun nouvel envoi pour cette clôture.

**Conclusion du diagnostic initial, portant sur la passe automatisée du 21 octobre :** la passe finale du 21 octobre a bien produit 17 transactions de collecte vers le flux de recette, avec 17 réponses HTTP 204 observées dans le service réseau de Chrome. La destination et la conversion horaire ont été recontrôlées dans les NetLog bruts. La réception de cette passe automatisée dans l'exploration du 21 reste non démontrée : l'utilisateur n'y voit aucun événement à 10 h, même le 28 octobre. Ce constat est distinct de la réception des parcours manuels après correctif des 28/29 octobre, désormais confirmée en tête de document. Aucune mauvaise destination, réponse 204 fabriquée par le harnais ou nouvelle anomalie applicative n'est démontrée. La cause de l'écart reste ouverte ; les vérifications de sélection et de filtre/historique ont depuis été clôturées par la revue manuelle rapportée ci-dessous. La session manuelle distincte autorisée a depuis confirmé les quatre noms applicatifs dans DebugView, comme indiqué dans la clôture en tête ; cela ne résout pas l’absence historique dans cette exploration.

Aucun navigateur, serveur ou test de collecte n'a été lancé pour cette analyse. Aucun changement applicatif ni de configuration. Le critère de retrait accepté le 21 octobre et ses limites ne sont pas remis en cause. La production demeure non validée et la collecte habituelle désactivée.

## Constat manuel et distinction des deux totaux de 22

Source : compte rendu de l'utilisateur du **28 octobre 2026 à 08:15, Europe/Paris**, propriété **France Tuncq — Recette**, ID numérique **559568920**, exploration sur la date fixe **21 octobre 2026**. L'agent n'a pas ouvert GA4 et n'a pas reçu de nouvelle capture locale de cet écran dans cette intervention.

| Événement | Exploration rapportée : tous à 9 h | Passe finale à 10 h : transporté / acquitté HTTP |
| --- | ---: | ---: |
| `page_view` | 8 | 13 |
| `discover_session_click` | 2 | 1 |
| `view_service` | 2 | 5 |
| `reserve_click` | 2 | 3 |
| **Sous-total applicatif** | **14** | **22** |
| `first_visit` | 4 | Pas de commande applicative séparée de ce nom |
| `session_start` | 4 | Pas de commande applicative séparée de ce nom |
| **Total affiché rapporté** | **22** | Réception GA4 non confirmée |

Aucun événement affiché à 10 h selon l'utilisateur ; résultats inchangés depuis le **22 octobre**. À cette dernière date, le panneau indiquait **100 % des données disponibles**, **non échantillonnées**, source **intrajournalière**. Ne pas attribuer automatiquement cette dernière étiquette à l'écran du 28, ni transformer le total de 22 affiché en preuve des 22 événements applicatifs attendus.

« 100 % » porte sur les données disponibles pour la vue et ses dimensions/métriques ; cela ne certifie pas que tous les POST acquittés sont ingérés ni que la vue n'a aucun filtre/segment. Voir [indicateur de qualité](https://support.google.com/analytics/answer/12856703?hl=fr) et [segments/filtres des explorations](https://support.google.com/analytics/answer/9328518?hl=fr). La persistance sur plusieurs jours ne justifie plus de proposer seulement une nouvelle attente sans question diagnostique précise.

## Pièces existantes et portée

- Finale après correctif : `var/analytics-real/20261021-102059/report.json` et `netlog-private.json` dans le même répertoire.
- Manuelle reçue dans DebugView : `var/analytics-manual/20261021-073956Z-940fdf46/report.json` et `netlog-private.json` ; confirmation historique dans [le compte rendu manuel](analytics-ga4-recette-manuelle.md).
- Préparations manuelles : rapports/NetLog sous `var/analytics-manual/20261021-072822Z-ff37a230`, `20261021-072930Z-335e638a`, `20261021-073210Z-caa5fac7`.
- Code relu : `tests/Analytics/real_driver.py`, `real_recipe.py`, `manual_recipe.py`, `real_preview.php`, modules Analytics et configuration publique déjà identifiés par le manifeste.

Les NetLog bruts sont des pièces privées : leurs identifiants navigateur/session ne sont pas reproduits dans ce document. Empreintes SHA-256 des deux pièces principales, conservées sans modification :

- Finale, NetLog : `bb4b01948dee771b38eadfd46787325f49b20c1182218852899900b31294ac48`.
- Manuelle, NetLog : `d9117ca9cebb30b868b3f5832eabc1eb287499f7114c13e331a3468a40ab7246`.

## Destination, horaires et origine des 204

Pour **chacune** des 17 transactions finales, vérification de `URL_REQUEST_START_JOB`, des en-têtes d'envoi HTTP/2 ou QUIC (`:authority`, `:path`) et de `HTTP_TRANSACTION_READ_RESPONSE_HEADERS`, regroupés par source NetLog :

- schéma HTTPS, hôte **region1.google-analytics.com**, chemin **/g/collect**, méthode POST ;
- `tid=G-7ZGZR4L2F8` dans chaque URL brute et dans le chemin réellement transmis ; aucun autre ID de mesure dans les URL démarrées du NetLog ;
- vérification des paramètres communs et de chaque ligne des corps conservés par CDP : aucune destination concurrente ni substitution de `tid` ;
- 17 signatures de requête distinctes après retrait des identifiants sensibles ; correspondance des paramètres non sensibles entre chaque URL brute et son entrée de rapport ; pas de corrélation ambiguë détectée ici ;
- réponse 204 lue dans le service réseau pour les 17 sources, `server: Golfe2`, `cache-control: no-cache, no-store, must-revalidate`, `content-length: 0`. Les dates serveur sont le 21 octobre, 08:21–08:24 GMT, cohérentes avec les temps locaux.

| Source NetLog | Début UTC le 21 octobre | Début Paris (UTC+02) | Événements applicatifs du POST | Réponse |
| --- | --- | --- | --- | --- |
| 667 | 08:21:08.934 | 10:21:08.934 | `page_view` | 204 |
| 787 | 08:21:23.939 | 10:21:23.939 | `discover_session_click`, `page_view`, `view_service` | 204 |
| 842 | 08:21:41.779 | 10:21:41.779 | `page_view` | 204 |
| 967 | 08:21:52.062 | 10:21:52.062 | `page_view`, `view_service` | 204 |
| 1091 | 08:22:03.361 | 10:22:03.361 | `page_view` | 204 |
| 1242 | 08:22:14.408 | 10:22:14.408 | `view_service` | 204 |
| 1269 | 08:22:20.348 | 10:22:20.348 | `reserve_click` anonyme | 204 |
| 1484 | 08:22:31.063 | 10:22:31.063 | `page_view` | 204 |
| 1571 | 08:22:50.058 | 10:22:50.058 | `page_view` | 204 |
| 1763 | 08:23:06.012 | 10:23:06.012 | `page_view`, `view_service` | 204 |
| 2049 | 08:23:12.200 | 10:23:12.200 | `reserve_click` connecté | 204 |
| 2255 | 08:23:23.171 | 10:23:23.171 | `page_view` | 204 |
| 2399 | 08:23:42.061 | 10:23:42.061 | `page_view` | 204 |
| 2552 | 08:23:57.985 | 10:23:57.985 | `page_view`, `view_service` | 204 |
| 2776 | 08:24:08.883 | 10:24:08.883 | `reserve_click` diagnostique, fiche maintenue ouverte | 204 |
| 2902 | 08:24:20.104 | 10:24:20.104 | `page_view` | 204 |
| 3034 | 08:24:46.937 | 10:24:46.937 | `page_view` | 204 |

Les sources 667 et 787 utilisent HTTP/2 ; les 15 suivantes utilisent QUIC/HTTP/3. La ligne normalisée `HTTP/1.1 204` de `HTTP_TRANSACTION_READ_RESPONSE_HEADERS` ne signifie pas que ces connexions étaient en HTTP/1.1. Les sources 1269 et 2049 complètent les réponses Réserver non observées par CDP ; elles ne sont pas des réponses supposées.

Calcul NetLog : `(event.time + constants.timeTickOffset) / 1000` donne des secondes Unix. Conversion recontrôlée via `TimeZoneInfo`, zone Windows **Romance Standard Time** : le **21 octobre 2026**, Paris est à **UTC+02**, heure d'été active. Les observations CDP encadrent la collecte à **08:21:08.903840–08:24:46.890746 UTC**, soit **10:21:08.903840–10:24:46.890746 Paris**. La dernière réponse NetLog arrive à **08:24:46.961 UTC**. Les dates serveur confirment cet ordre de grandeur, sans décalage d'une heure repéré.

Le constat utilisateur du **28 octobre à 08:15 Paris** est, lui, à **UTC+01**, soit **07:15 UTC**. Ne pas appliquer ce décalage d'hiver aux événements historiques du 21. Le fuseau et la sélection effective de l'exploration ne sont pas inspectables à partir du NetLog ; la conversion locale n'a toutefois pas placé par erreur cette passe à 9 h.

### Ce que fait le harnais et ce que prouvent les réponses

`Driver.event()` autorise `/g/collect` avec `Fetch.continueRequest`, sans modifier son URL, son corps, ses en-têtes ou sa méthode. L'interception de réponse n'est demandée que pour la bibliothèque ; son ralentissement retient puis libère une réponse distante avec `Fetch.continueResponse`. La seule réponse synthétique dans ce gestionnaire est le **200 de la doublure Maps**, sans rapport avec la collecte. Aucun chemin produisant un 204 Analytics synthétique n'est présent dans les lanceurs réels relus.

`status` vient de `Network.responseReceived` : **15 réponses finales observées par CDP**. `netlog_status` est ajouté après fermeture par `attach_netlog()` en lisant les en-têtes de réponse des sources NetLog : **17 réponses**, y compris les deux départs Réserver. L'audit hors ligne a revérifié les sources brutes, pas seulement les compteurs ou les assertions `passed`. Aucun cache de collecte n'est signalé ; les événements bruts d'envoi HTTP/2/QUIC et de lecture des réponses sont présents. La bibliothèque, elle, a aussi été réutilisée depuis le cache lors de navigations ; ce n'est pas un rejeu de collecte.

**Conclusion réseau : réponses distantes observées par Chrome, pas réponses de collecte fabriquées par l'interception ni simples commandes mises en file.** Cela ne constitue ni un accusé d'ingestion dans les rapports ni une preuve de traitement de chaque événement par GA4.

Limites des captures : `--net-log-capture-mode=Default` garde les en-têtes et les tailles, pas les octets complets des corps uploadés. Le rapport conserve les corps observés par CDP puis expurgés/re-sérialisés. Par exemple, les lots finaux ont des tailles brutes 224/89/89/89 octets et des représentations dans le rapport de 222/88/88/88 octets. L'écart d'un octet par séparation est compatible avec la normalisation des lignes par `splitlines()` puis `join('\n')` ; le mode manuel a le même écart (281→279 et 275→273). On ne peut pas prouver la séquence exacte de séparateurs d'origine sans octets archivés. **Ce n'est pas une modification du corps avant délégation démontrée** : la re-sérialisation intervient pour écrire le rapport. Les paramètres décodés ont été comparés, pas prétendument une empreinte complète du corps réellement reçu par Google.

Enfin, 15 requêtes finales et 5 manuelles ont aussi un signalement CDP `net::ERR_ABORTED`, avec une réponse 204 déjà consignée et des horodatages de callbacks égaux à la résolution enregistrée. Cette coexistence est commune aux deux modes. Elle ne permet pas d'annuler la preuve des réponses NetLog ni d'expliquer sélectivement l'absence finale ; sa cause interne n'est pas établie ici.

## Comparaison avec la session manuelle reçue

| Point | Manuelle `073956Z-940fdf46` | Finale `102059` | Portée |
| --- | --- | --- | --- |
| Destination | `G-7ZGZR4L2F8`, même collecteur | Identique | Pas de dérive de destination détectée. |
| Environnement | Chrome visible 155.0.8059.86, Windows, profil isolé, localhost:8021 | Même version/OS/origine, autre profil isolé ; `--headless=new`, `--disable-gpu` | Plusieurs variables changent à la fois ; pas de comparaison contrôlée. |
| User-Agent brut de collecte | `Chrome/155.0.0.0` | **`HeadlessChrome/155.0.0.0`** | Différence effectivement transmise, pas une cause démontrée. Les client hints de version restent identiques. |
| Taille d'écran envoyée | `1707x1067` | `800x600` | Différence de contexte. |
| Transport HTTP | 7 POST : 2 HTTP/2, 5 QUIC ; 7 réponses 204 | 17 POST : 2 HTTP/2, 15 QUIC ; 17 réponses 204 | Les deux utilisent les mêmes protocoles réseau. |
| API native JavaScript | Pas de sonde native dans les observations historiques | 15 appels de collecte capturés, `fetch keepalive`; 2 non capturés à la sortie de fiche | Ne pas inventer un transport JS observé côté manuel. La forme POST/no-cors ne prouve pas à elle seule l'API utilisée. |
| En-têtes | Origin localhost:8021, `no-cors`, `cross-site`, `text/plain;charset=UTF-8` pour les lots | Identiques | Pas d'écart de format d'en-tête identifié, hors User-Agent. |
| Ordre initial des commandes | consent/default → set → js → config → set → event/page_view | Identique | L'observateur ne rajoute pas un second `config` initial pour le debug. |
| Consentement | Analytics granted après accord ; ad_storage/ad_user_data/ad_personalization denied | Identique | Commandes et paramètres réseau concordants. |
| Marqueurs observés | `gcs=G101`, `gcd=13p3t3p2p7l1`, `npa=1`, `pscdl=denied`, `frm=1` | Identiques | Égalité des valeurs constatée, sans décodage spéculatif de champs propriétaires. |
| Configuration/cookies | send_page_view false ; signaux/personnalisation false ; préfixe dédié, host-only, `/`, Secure/Lax, cookie_update false | Identique | Rien dans les configurations capturées ne montre Analytics refusé après acceptation en finale. |
| Debug | Booléen `debug_mode: true` ajouté aux commandes config/event existantes, après accord ; `ep.debug_mode=true` transmis | Pas de décoration ni paramètre debug transmis | Différence établie ; l'absence de debug n'est pas une preuve de rejet des événements ordinaires. `_dbg` n'est pas observé ni forcé. |
| Paramètres métier | `service_id` et `display_context` chaînes ; service 1, home_featured/service_page | Mêmes types ; catalogue_ajax ajouté pour le parcours AJAX | Contexte attendu, sans donnée privée ni destination supplémentaire identifiée. |
| Cycle de vie | 1 couple navigateur/session sur cette session ; 3 configurations de cadres | 4 couples après retraits/réacceptations ; 14 configurations incluant scénarios d'arrêt/échec | Les couples ont été comparés localement, sans valeurs publiées. Aucune identité commune entre ces deux passes. |
| Bibliothèque | URL gtag/js avec le même ID ; réponse zstd, taille compressée 163241 ; marqueur gtm commun | Mêmes URL, taille et marqueur `45je6aj1v9267611814za200zd9267611814xf1` | Indices communs, pas une preuve d'identité du code Google : aucun corps/empreinte de bibliothèque archivé. `tag_exp`, `rcb` et `tfd` diffèrent sans interprétation causale établie. |
| Observateur et application | Observation de dataLayer + décoration debug ; avant protection des transports | Observation dataLayer sans décoration, sondes natives/entrée de garde ; protection applicative en place | Les sondes peuvent influencer l'ordonnancement. Pas de copie historique intégrale des scripts servis pour certifier chaque octet du mode manuel. |

Le code actuel de l'observateur appelle le `push` d'origine via `Reflect.apply`, conserve son retour et utilise un `WeakSet` pour ne pas observer/décorer deux fois le même objet. En manuel, la décoration remplace l'objet de paramètres de la commande existante pour y ajouter le booléen debug ; elle n'ajoute pas d'événement. Dans la passe finale, les sondes de transport observent l'entrée et la délégation sans décision de consentement ; cette décision appartient au garde applicatif. Les traces confirment ces différences de commandes et d'observations, mais ne constituent pas une expérience isolant l'effet de chaque enveloppe sur la bibliothèque.

Les deux modes conservent les protections DNS/destinations, la doublure Maps, les fixtures synthétiques, le mailer nul et Stripe bloqué. Aucune option `--ignore-certificate-errors` ou `--disable-web-security` n'apparaît dans leurs lignes de commande brutes. Aucun changement de consentement/configuration propre aux nouvelles options de **production** n'est déduit de ces traces de **recette**.

### Correspondance avec les événements actuellement visibles à 9 h

Les rapports manuels de préparation `072822Z-ff37a230`, `072930Z-335e638a` et `073210Z-caa5fac7` comportent chacun une page vue acquittée. Ajoutés à la session reçue `073956Z-940fdf46` (5/2/2/2), ils donnent **8/2/2/2**, exactement les **14 événements applicatifs** de l'exploration rapportée. Leurs NetLog totalisent quatre couples navigateur/session et quatre marqueurs `_fv=1`/`_ss=1`, cohérents avec les 4 `first_visit` et 4 `session_start` visibles. Les deux préparations sans collecte n'ajoutent rien.

Cette concordance renforce l'interprétation « données manuelles visibles, passe finale non visible ». Elle n'attribue pas formellement chaque ligne GA4 à un identifiant local : l'interface et ses identifiants n'ont pas été rapprochés directement. Les événements de 9 h ne sont en aucun cas reclassés comme preuve de la passe de 10 h.

## Hypothèses et contrôles proposés avant le complément manuel, sans modification

| Piste | Ce qui est établi / ce qui manque | Contrôle minimal |
| --- | --- | --- |
| Sélection de l'exploration | Date et résultats rapportés, mais configuration complète des segments/filtres/limitations de lignes non fournie. Non échantillonné n'exclut pas une sélection. | **Premier contrôle :** consulter les panneaux de l'exploration ci-dessous, sans les modifier. |
| Filtre de collecte côté propriété de recette | Ancien constat : seul Internal Traffic en Test. Aucun état historique complet autour de 09 h–10 h ni preuve d'un changement depuis. | Si la vue n'explique pas l'écart : inspecter Filtres de données et l'historique de cette propriété, pas ceux de production. |
| Traitement des navigateurs automatisés | User-Agent HeadlessChrome démontré ; Google documente une exclusion automatique de robots connus, sans fournir ici de décision individuelle. | Hypothèse plausible, **non démontrée pour cette passe**. Aucune option à désactiver ni falsification d'User-Agent proposée. Ne pas conclure « headless responsable ». |
| Debug, bibliothèque ou observateur | Différences simultanées réelles ; absence d'expérience contrôlée, de source Google archivée et de logs d'ingestion. | Garder ces facteurs ouverts après contrôle de la vue/des filtres. Aucun nouvel essai autorisé ici. |
| Correctif de retrait | Réponses réseau après correctif effectives ; pas de paramètre malformé/consentement refusé détecté dans les événements transportés. | Pas de preuve que le correctif explique l'absence. Ne pas le retirer ni réouvrir automatiquement le défaut déjà corrigé. |
| Fuseau ou mauvaise destination | URLs/en-têtes/horodatages serveur cohérents ; réception manuelle antérieure sur le même ID. | Pistes locales non corroborées. Vérifier le libellé du fuseau de la recette avec les captures de vue, sans modifier le réglage. |

Google indique que les [robots connus sont exclus automatiquement](https://support.google.com/analytics/answer/9888366?hl=fr), sans écran donnant le volume exclu ni option de désactivation. Cela établit l'existence du mécanisme, **pas le classement de ce Chrome dans cette catégorie**. Les données disponibles ne départagent pas debug, automation, observateur et autres décisions d'ingestion. Aucune responsabilité de Google ni du mode headless n'est prouvée ; l'absence historique du 19 octobre reste également inexpliquée.

### Action initialement proposée dans Analytics — terminée selon le complément ci-dessous

**Dans la propriété de recette 559568920, ouvrir l'exploration existante sur le 21 octobre et conserver une capture de ses panneaux Variables et Paramètres, sans changer de sélection.** Montrer précisément :

- segments **appliqués** dans « Comparaisons de segments » (la simple liste de segments disponibles ne suffit pas) et leurs conditions en consultation ;
- bloc **Filtres** en bas du panneau Paramètres : toutes les conditions, notamment heure, flux, nom du filtre de données test, debug, appareil ou audience si présentes ;
- lignes/colonnes, dimension **Heure**, métrique **Nombre d'événements**, nombre de lignes/ligne de début ou pagination ;
- date fixe, nom/ID de propriété, panneau de qualité. Le libellé du fuseau de la propriété peut être relevé dans Administration → Détails de la propriété, sans modification.

**Pourquoi :** cette seule inspection permet de vérifier si la vue sélectionne un sous-ensemble lié au debug/profil/heure, ce que le panneau « 100 % » ne tranche pas. Les [filtres et segments d'exploration](https://support.google.com/analytics/answer/9328518?hl=fr) agissent sur les données affichées. Il n'est demandé ni de les enlever ni de créer une nouvelle exploration.

Seulement si ces paramètres ne restreignent pas les événements attendus : consulter **Administration → Collecte et modification des données → Filtres de données** dans **la recette**, relever type, opération, valeur et état de chaque filtre. Puis **Administration → Propriété → Historique des modifications de la propriété** autour du 21 octobre, notamment avant et après 10 h, pour savoir si l'état actuel était déjà celui des essais. L'historique utilise le fuseau du système d'exploitation : conserver le libellé des heures. Un filtre Internal Traffic en **Test** marque des données mais ne les exclut pas ; un filtre actif doit être évalué selon sa règle. Un simple filtre d'exclusion du trafic développeur viserait les événements debug et n'expliquerait pas à lui seul la disparition des seuls événements sans debug. [Trafic interne](https://support.google.com/analytics/answer/10104470?hl=fr), [trafic développeur](https://support.google.com/analytics/answer/13296662?hl=fr), [historique](https://support.google.com/analytics/answer/9305465?hl=fr).

Masquer les courriels des auteurs de modifications et les identifiants navigateur/session dans les preuves partagées. Aucun changement des filtres, du fuseau, du consentement, des propriétés ou des protections n'est demandé. Si la vue et les filtres sont innocentés, la question restante sera un traitement à l'ingestion ou une différence de contexte ; un éventuel essai comparatif devra alors être défini et autorisé séparément, avec un seul facteur changé, pas une nouvelle campagne complète.

## Vérifications locales du diagnostic initial, avant la nouvelle session manuelle

Lecture du code et des JSON uniquement ; analyse des événements NetLog et des en-têtes envoyés/reçus ; rapprochement des 17 sources finales et des 7 manuelles avec les rapports ; comparaison des paramètres décodés, types, consentements et identifiants en local sans publication de leurs valeurs ; recomptage des quatre sessions manuelles ; conversion de fuseaux avec les règles Windows ; vérification des empreintes du manifeste, des rapports et de l'index ; contrôle documentaire et `git diff --check`. Aucun module de recette n'a été exécuté/importé pour lancer une session, aucun test applicatif relancé.

Les 45 sources/configurations/tests et les 7 rapports du manifeste daté correspondent toujours à leurs empreintes. Le manifeste, les NetLog et l'index sont conservés ; les modifications se limitent à ce diagnostic documentaire et aux liens/statuts dans les documents GA4 existants. `GA4_ENABLED=0` reste le défaut. **État à la fin de ce diagnostic initial : écart de visibilité persistant selon la provenance manuelle, cause non identifiée, réception après correctif et validation globale encore ouvertes. La confirmation DebugView ultérieure du nouveau parcours manuel est consignée en tête et ne réécrit pas ce résultat historique.**

## Complément utilisateur et essai ciblé autorisé — 28 octobre 2026

**Revue manuelle rapportée**, sans inspection directe par cet agent : exploration sans segment appliqué ni filtre ; pagination compatible avec les six événements affichés ; seul filtre de données **Internal Traffic, Test** ; historique complet affiché de **18 entrées, toutes le 19 octobre**, aucune modification ultérieure ; création du filtre en **TESTING_FILTER_MODE**. Ces pièces rapportées ne corroborent ni une restriction de l’exploration ni un passage du filtre en exclusion active dans l’historique examiné. Les contrôles initialement demandés ci-dessus sont terminés ; la cause de l’écart demeure inconnue.

L’utilisateur autorise maintenant **un seul parcours manuel** dans un nouveau Chrome visible isolé, avec **le code corrigé actuel**, `debug_mode` après acceptation uniquement et destination exclusive `G-7ZGZR4L2F8`. Question précise : les quatre événements applicatifs de ce parcours après correctif deviennent-ils visibles dans DebugView ? Aucun parcours automatisé, consentement automatique ni collecte de préparation. Voir le [journal de recette manuelle](analytics-ga4-recette-manuelle.md) pour la session et ses empreintes.

Un succès constituerait une preuve de réception après correctif dans ce contexte. Il ne démontrerait **pas** que le mode headless explique les absences des 19/21 octobre : plusieurs facteurs diffèrent de la passe historique (debug, profil, moment, contexte navigateur). Les résultats historiques, leurs limites et le critère technique de retrait accepté restent inchangés. Cette réception a depuis été confirmée manuellement dans DebugView (clôture en tête). Les rapports standards, l’écart d’un view_service et la validation globale restent distincts et ouverts.
