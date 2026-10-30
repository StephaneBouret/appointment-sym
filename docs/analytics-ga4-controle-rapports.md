# GA4 — contrôle des données existantes et préparation France

## Actualisation — production du 30 octobre 2026

**Déploiement, activation autorisée puis exécutée et réception des quatre événements dans Temps réel confirmés par l’utilisateur.** Voir le [compte rendu de mise en production](analytics-ga4-production-20261030.md). Les opérations et captures sont rapportées par l’utilisateur et son assistant ; aucune inspection directe du serveur, d’Analytics ou des captures de production par cet agent.

La réception dans Temps réel est maintenant acquise pour la production (propriété 559257505), distincte des recettes. Le statut courant des suites figure aussi en section 4 ci-dessous : rapports standards de production non contrôlés et « Personnalité juridique » toujours ouverte.

Les statuts de préparation et de recette qui suivent sont conservés comme **historique daté**, sans annuler les acquis du 30 octobre ni rouvrir les contrôles déjà confirmés. Aucune nouvelle opération serveur n’est autorisée par cette actualisation.

## Historique conservé

**Intégration locale approuvée par délégation — 29 octobre 2026 :** l’utilisateur déclare la délégation de France et approuve les mentions visiteurs avec la formulation « France Tuncq et le prestataire chargé du développement et de la maintenance du site », ainsi que les deux harmonisations. Texte et harmonisations intégrés localement ; [contrôles, livrable isolé et procédure](analytics-ga4-livraison-20261029.md). Cette décision n’est ni une certification juridique ni une autorisation de publication/activation. Collecte désactivée. La rubrique « Personnalité juridique » du DPA demeure **vide / point administratif ouvert**, sans rattachement Marketing Platform ajouté.

**État au 29 octobre 2026, relevé manuel à 08:19 Paris : réception après correctif acquise dans DebugView et dans l’exploration.** Dans « Recette GA4 — 28 octobre 2026 », propriété **559568920**, période fixe du **28 octobre**, l’utilisateur rapporte **21 événements, tous à 9 h** : `page_view` 8, `view_service` 6, `discover_session_click` 2, `reserve_click` 1, `first_visit` 2, `session_start` 2. Les traces des deux ouvertures manuelles du 28 concordent avec ces volumes : principale 7/6/2/1 et première ouverture interrompue 1 page vue ; chaque ouverture comporte `_fv=1` et `_ss=1`, avec des identifiants navigateur/session distincts comparés localement et masqués. **Concordance agrégée, pas attribution individuelle des lignes GA4.** Le relevé DebugView du 28 (5 `view_service`) est conservé comme observation datée ; le relevé ultérieur en affiche 6, sans démontrer la cause de cette évolution.

L’utilisateur précise avoir probablement effectué davantage de clics que demandé : **le parcours manuel n’était pas strictement contrôlé**. Ces volumes ne prouvent ni un doublon, ni l’absence de doublons, ni l’action physique précise à l’origine de chaque événement ; la réception des quatre types reste acquise. L’absence des essais automatisés des 19/21 octobre demeure un **constat historique inexpliqué**, sans absence générale de réception après correctif ni responsabilité démontrée de headless. Les rapports prédéfinis et Temps réel ne sont pas attestés par cette exploration, mais ne constituent pas un contrôle supplémentaire exigé pour ce critère de réception. Aucune relance demandée. Le critère de retrait accepté et ses limites restent inchangés ; **aucune validation globale ni activation de production**. Voir le [rapprochement du 29 octobre](analytics-ga4-recette-manuelle.md#exploration-confirmée-et-rapprochement-des-deux-ouvertures--29-octobre-2026) et les [seuls prérequis encore ouverts](analytics-ga4-controle-rapports.md#4-prérequis-france-restant-avant-une-activation-distinctement-autorisée).

**État historique à la clôture du 28 octobre 2026 (complété par le relevé du 29 ci-dessus) :** réception des **quatre événements applicatifs après correctif confirmée manuellement dans DebugView**, en mode manuel avec debug, pour `20261028-083431Z-91281b44`. Relevé utilisateur vers **09:41 Paris** : `page_view` 7, `view_service` 5, `discover_session_click` 2, `reserve_click` 1, `first_visit` 1, `session_start` 1 (**17 affichés**). Traces locales : **16 applicatifs** (7/6/2/1) dans **sept POST/204** finalisés, exclusivement `G-7ZGZR4L2F8` ; l’écart d’un `view_service` reste inexpliqué, sans présumer lequel manque dans l’interface. Les deux découvertes sont deux commandes dans deux lots, sans preuve de deux clics physiques ni de doublon. Session arrêtée proprement ; [rapprochement et preuves de clôture](analytics-ga4-recette-manuelle.md#clôture-et-réception-après-correctif-confirmée--28-octobre-2026). **Rapports standards de cette session encore à vérifier ; absence historique des essais automatisés inexpliquée ; aucune responsabilité de headless démontrée.** Critère de retrait accepté inchangé, production non validée, configuration habituelle désactivée. Aucun nouvel envoi pour cette clôture.

Préparé le 21 octobre 2026 après acceptation explicite du critère technique de retrait. Aucun lancement de Chrome de recette, aucune activation, aucun nouvel envoi Analytics et aucune modification de propriété. La consultation de la documentation publique Google pour préparer les indications d'interface n'est pas une nouvelle collecte vers le flux. La validation globale et la production restent non acquises.

**Actualisation du 28 octobre 2026, constat utilisateur à 08:15 Paris :** dans la propriété de recette **559568920**, l'exploration du 21 octobre affiche toujours uniquement à **9 h** : `page_view` 8, `first_visit` 4, `session_start` 4 et chacun des trois événements métier 2. Aucun événement à 10 h ; résultats inchangés depuis le 22. Ces **22 affichés = 14 applicatifs + 8 automatiques**, pas les 22 applicatifs de la passe finale. Le [diagnostic hors ligne du 28 octobre](analytics-ga4-diagnostic-reception-20261028.md) confirme dans les NetLog bruts 17 POST/204 vers le seul flux autorisé et la conversion 10:21–10:24 Paris, sans identifier la cause de l'écart d'affichage. Ces contrôles ont depuis été terminés : voir le complément manuel du 28 octobre ci-dessous. La session manuelle sur le code corrigé a depuis confirmé les quatre noms dans DebugView ; voir la clôture en tête. Le critère de retrait accepté reste inchangé ; rapports standards de cette nouvelle session et validation globale restent ouverts.

**Actualisation du 21 octobre 2026 :** le bilan de la revue manuelle des réglages de **production** est consigné en section 7, sur la base du compte rendu transmis par l'utilisateur. Il ne qualifie pas rétroactivement la propriété de recette. Le rapport consulté le matin même était vide ; la réception après correctif demeure **non confirmée**, avec relecture des données du 21 octobre prévue par l'utilisateur le **22 octobre**, sans nouvel envoi. Le critère de retrait accepté et ses limites restent inchangés.

## 1. Passe à rechercher et fuseaux

- Propriété : **France Tuncq — Recette**.
- Flux : **Recette locale — appointment-sym**, identifiant de mesure **`G-7ZGZR4L2F8`** (ce n'est pas l'identifiant numérique de propriété ou de flux).
- Rapport source : `var/analytics-real/20261021-102059/report.json`, passe complète **après correctif**, 54 contrôles réussis, `production_validated=false`.
- Configuration observée : vraie bibliothèque, Chrome isolé, origine `http://localhost:8021`, sans décoration `debug_mode`. Aucun événement n'est attendu dans DebugView sur la seule base de cette configuration.

Les heures ci-dessous sont recalculées à partir des secondes Unix des champs `network[].time` et `netlog_requests[].start_time/response_time`, pas déduites du nom du répertoire. La conversion a été vérifiée avec `TimeZoneInfo` Windows, zone **`Romance Standard Time` (Paris)**, heure d'été active le **21 octobre 2026**, décalage **UTC+02:00**. Le fuseau local du poste est également cette zone. **L'utilisateur confirme désormais France, GMT+2 au 21 octobre 2026**, ce qui concorde avec la conversion locale. Cette confirmation est rapportée, pas issue d'un accès GA4 par cet agent. Le bilan administratif détaillé ci-dessous porte sur la production ; conserver l'identification de la propriété et le libellé du fuseau avec la preuve de consultation de la recette, sans lui attribuer automatiquement les réglages de production.

| Repère du 21 octobre 2026 | UTC | Paris, UTC+02:00 |
| --- | --- | --- |
| Première commande d'événement effectivement transporté | 08:21:08.896500 | 10:21:08.896500 |
| Première observation de collecte CDP | 08:21:08.903840 | 10:21:08.903840 |
| Dernière observation de collecte CDP | 08:24:46.890746 | 10:24:46.890746 |
| Premier début de transaction NetLog | 08:21:08.934 | 10:21:08.934 |
| Dernier début de transaction NetLog | 08:24:46.937 | 10:24:46.937 |
| Dernière réponse NetLog | 08:24:46.961 | 10:24:46.961 |

Fenêtre de recherche pratique : **21/10/2026, 10:21:00 à 10:24:59 Paris**, soit **08:21:00 à 08:24:59 UTC**. Si la propriété utilise un autre fuseau, convertir ces instants UTC dans ce fuseau et conserver son libellé dans la preuve ; ne pas modifier le réglage pour ce contrôle. Les heures réseau ne sont pas nécessairement les heures d'événement affichées par GA4 : le lot peut être envoyé quelques secondes après la commande. Exemple : la page du catalogue est commandée à 10:21:58.343800 et transportée à 10:22:03.350622. Ne pas exiger une égalité artificielle des compteurs minute par minute avec les départs réseau.

Méthode de vérification locale employée, en lecture seule :

```powershell
$zoneParis = [TimeZoneInfo]::FindSystemTimeZoneById('Romance Standard Time')
$instantRecette = [DateTimeOffset]::FromUnixTimeMilliseconds(1792570868903)
[TimeZoneInfo]::ConvertTime($instantRecette, $zoneParis).ToString('o')
$zoneParis.IsDaylightSavingTime($instantRecette)
```

Résultats : `2026-10-21T10:21:08.9030000+02:00`, `True`. Le contrôle a également été fait pour le dernier instant de collecte. La commande ci-dessus tronque aux millisecondes uniquement pour l'affichage .NET ; le tableau garde la précision disponible dans le JSON.

## 2. Événements et volumes issus des traces

| Événement | Commandes mises dans la file | Événements transportés | Événements couverts par une réponse HTTP 204 |
| --- | ---: | ---: | ---: |
| `page_view` | 14 | **13** | 13 |
| `discover_session_click` | 1 | **1** | 1 |
| `view_service` | 6 | **5** | 5 |
| `reserve_click` | 3 | **3** | 3 |
| **Total applicatif** | **24** | **22** | **22** |

Les deux commandes en plus (`page_view` et `view_service`, second onglet à 10:24:57.987500/10:24:57.992100) ont été mises en file avant le retrait, puis bloquées au transport. **Ne pas les rechercher comme des événements livrés.** Les 22 événements sont regroupés dans **17 POST**, avec **17 réponses HTTP 204 dans NetLog**, dont 15 aussi observées par CDP. Ce ne sont pas 22 réponses HTTP distinctes. La réception dans les rapports demeure à confirmer par l'utilisateur.

Repères fonctionnels :

| Donnée transportée | Volume / contexte |
| --- | --- |
| `page_view` | Accueil `/` : 8 ; fiche `/rendez-vous/types/1` : 4 ; catalogue `/rendez-vous/types` : 1 |
| `discover_session_click` | 1 depuis `/`, `service_id=1`, `display_context=home_featured` |
| `view_service` | 4 sur fiche avec `service_page`, 1 dans le catalogue avec `catalogue_ajax` ; `service_id=1` |
| `reserve_click` | 1 depuis le catalogue avec `catalogue_ajax`, 2 depuis la fiche avec `service_page` ; `service_id=1` |

Les clics Réserver ont été observés sur le réseau à **10:22:20.348109** (anonyme), **10:23:12.200509** (connecté) et **10:24:08.836621** (diagnostic maintenant la fiche ouverte). Ce dernier reste inclus dans les trois événements techniques : ce n'est ni un troisième parcours natif complet, ni une réservation ni une vente. Aucune soumission métier n'a eu lieu.

Quatre pages vues transportées portent `_fv=1` et `_ss=1`, après les créations/recréations de contexte et de cookies. Ces marqueurs peuvent accompagner la génération des événements automatiques `first_visit`/`session_start` par Google ; ce ne sont pas des commandes applicatives ni des requêtes séparées de ces noms. **Relever les événements automatiques réellement visibles sans imposer un total global de 22 ou inventer leurs compteurs dans les rapports.** Les cookies ont été effacés puis recréés au cours des scénarios : la passe ne correspond pas nécessairement à un seul utilisateur ni à une seule session GA4.

## 3. Distinguer cette passe et conserver une preuve utile

Le meilleur repère disponible est la fenêtre horaire, complétée par le flux, les quatre noms d'événement, l'hôte `localhost`, les pages et les contextes. Aucun identifiant de recette spécifique n'a été envoyé à Google ; le nom local `20261021-102059` et les noms de phases du harnais ne sont pas des dimensions GA4. Les identifiants navigateur/session ne sont pas publiés dans ce dossier.

| Pièce antérieure | Plage de collecte observée, Paris le 21 octobre | Risque de confusion |
| --- | --- | --- |
| Session manuelle `20261021-073956Z-940fdf46` | Session démarrée à 09:39:56 ; parcours avant le correctif | DebugView confirmé : 5/2/2/2 applicatifs. Le troisième appareil « Google » n'identifie pas la passe automatisée ultérieure. |
| Passe réelle incomplète `20261021-101435` | 10:14:44.550131–10:18:22.473008 | **Mêmes volumes 13/1/5/3 que la passe finale** ; les comptes seuls ne départagent pas les deux. |
| Trois essais ciblés `20261021-101945` | 10:19:51.172688–10:20:41.530160 | 4 pages vues et 1 fiche ; même flux/hôte, immédiatement avant la passe finale. |
| Passe finale recherchée `20261021-102059` | **10:21:08.903840–10:24:46.890746** | 13/1/5/3 transportés après correctif, sans décoration debug. |

Un total journalier ou même horaire peut mélanger ces essais. Une capture où les quatre noms figurent confirme leur présence dans les rapports sur cette période, mais **pas à elle seule leur attribution à la passe finale**. L'hôte, les routes et `service_id=1` sont partagés avec d'autres essais. Les paramètres métier ne sont exploitables dans les rapports que si les dimensions correspondantes étaient disponibles ; ne pas créer maintenant de dimension pour tenter de prouver rétroactivement cette passe. [Documentation Google sur les paramètres et dimensions](https://developers.google.com/analytics/devguides/collection/ga4/event-parameters).

### Manipulation manuelle, sans nouvelle collecte

1. Dans votre navigateur habituel, sélectionner **France Tuncq — Recette**. Dans **Administration → Propriété → Détails de la propriété**, relever le **fuseau horaire des rapports**, sans enregistrer de modification. Dans les flux de données, vérifier que **Recette locale — appointment-sym** porte bien `G-7ZGZR4L2F8`. L'identifiant numérique de flux éventuellement utilisé comme filtre doit être relevé dans cette fiche, pas remplacé par l'ID `G-…`. [Emplacement du fuseau documenté par Google](https://support.google.com/analytics/answer/9304776?hl=fr).
2. Ouvrir le rapport **Événements** dans **Rapports** (généralement **Engagement → Événements**, le classement dépend de la collection affichée). Régler la période sur **le 21 octobre 2026 uniquement**. Chercher les quatre noms du tableau et relever **Nombre d'événements**, pas le nombre d'utilisateurs, d'événements clés ou les revenus. Ne rien marquer comme événement clé/vente et ne pas modifier les filtres de données. [Rapport Événements](https://support.google.com/analytics/answer/12926615?hl=fr).
3. Si une vue d'analyse déjà accessible propose une ventilation temporelle à la minute, utiliser le nom d'événement, le nombre d'événements, le flux, et **10:21–10:24 inclus** si le fuseau est Paris (08:21–08:24 en UTC). Filtrer sur `localhost` si la dimension d'hôte est disponible. Les dimensions temporelles `dateHour` et `dateHourMinute` existent dans le [schéma de reporting Google](https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema), ce qui ne garantit pas leur disponibilité dans chaque écran standard. Ne pas créer de configuration, d'export BigQuery ou de nouvelle collecte pour obtenir cette ventilation.
4. Si l'écran ne permet qu'un total de journée/heure, conserver cette preuve **avec la limite d'attribution**, sans déclarer la passe finale isolée. Noter les filtres/comparaisons appliqués, les éventuels avertissements de qualité/seuil/traitement et l'heure de consultation. L'absence immédiate n'est pas à interpréter comme une perte : Google annonce un délai pouvant atteindre 24 heures pour ce rapport. Reconsulter les mêmes données ultérieurement si nécessaire, sans rejouer le parcours. [Délai documenté du rapport](https://support.google.com/analytics/answer/12926615?hl=fr).

Preuves à conserver : capture du nom de propriété et de la fiche du flux/ID ; capture du fuseau ; capture du tableau avec date, noms d'événement, métrique, ventilation temporelle et filtres visibles, éventuellement export des données déjà affichées. Ajouter la date/heure de consultation et une note « passe isolée » ou « données agrégées, attribution non démontrée ». Masquer les valeurs d'identifiants navigateur/session si un autre écran les expose. Une capture Temps réel ou DebugView historique ne remplace pas ce contrôle des rapports après correctif.

**Historique du contrôle prévu le 22 octobre :** la consultation a ensuite été rapportée par l'utilisateur le 28 octobre. Les données du 21 restent limitées à 9 h, sans événement affiché à 10 h ; le panneau du 22 indiquait 100 % des données disponibles, non échantillonnées, source intrajournalière. Le rapport consulté le matin du 21 était vide ; ces constats datés sont distincts. La passe recherchée reste 10:21:08–10:24:46 Paris, 13/1/5/3 événements applicatifs. **L'étape actuelle consiste à inspecter les paramètres de sélection de l'exploration**, selon le diagnostic du 28, plutôt qu'à seulement attendre ou rejouer les parcours. Aucune cause n'est attribuée au mode headless, au correctif ou à Google ; l'absence du 19 octobre reste inexpliquée.

## 4. Prérequis France restant avant une activation distinctement autorisée

### Statut courant — 30 octobre 2026

La liste d’avant activation est conservée plus bas pour la traçabilité. Elle ne décrit plus les actions à réaliser : publication, déploiement, autorisation d’activation, activation effective et réception des quatre types dans Temps réel sont **confirmés par l’utilisateur**. Le [compte rendu de production](analytics-ga4-production-20261030.md) distingue cette confirmation rapportée des contrôles locaux.

| Point | État courant / responsable |
| --- | --- |
| Mentions, livrable, publication et activation | Acquis rapportés ; ne pas redemander l’approbation, la sauvegarde du panneau de transmission ou une nouvelle recette |
| PHP web et OPcache | PHP 8.4.27, UID 664242226, OPcache activé, validate_timestamps=1 et revalidate_freq=2 rapportés ; réserve d’identité web clôturée sur cette base |
| Réception de production | Quatre types dans Temps réel acquis ; compteurs sans attribution individuelle ni preuve de déduplication |
| Rapports standards de production | Non encore contrôlés ; utilisateur disposant d’Analytics, consultation des données déjà reçues sans nouvelle collecte requise |
| « Personnalité juridique » du DPA | Toujours ouverte ; France / administrateur Analytics |

Les défauts locaux restent désactivés ; la configuration persistante de production est rapportée active. Critère de retrait et limites acceptés inchangés, aucune certification juridique globale.

### Liste historique au 29 octobre — conservée, remplacée pour le statut courant

**Liste actualisée le 29 octobre : seuls les éléments ci-dessous restent ouverts.** Les lignes de réception et de configuration inactive qui figuraient dans l’ancienne liste sont acquises, pas des contrôles à redemander.

| Responsable | Prérequis restant | Action minimale, sans activation ici |
| --- | --- | --- |
| France / administrateur du compte | Renseignement de l’entité juridique dans le DPA | POTENTIEL CONSULTING est confirmé, mais « Personnalité juridique » reste vide. Faire consigner l’entité par le moyen applicable, sans imposer un rattachement Marketing Platform. Contact et acceptation du 16 octobre déjà confirmés ; voir section 8. |
| Utilisateur mandaté par France | Approbation des mentions **acquise le 29 octobre par délégation** | Texte et deux harmonisations approuvés puis intégrés localement. Aucun nouvel accord rédactionnel demandé ; pas de certification juridique ni d’activation. |
| Responsable du livrable / développement | Préparation locale **effectuée**, adaptation à la cible lors d’un déploiement autorisé | Assets compilés isolément et nouveau manifeste ; voir le dossier de livraison. Ne pas écraser les particularités du déploiement France à partir du dépôt principal ; aucun worktree France modifié. |
| Responsable du site / de la mise en production | Décision explicite de validation finale et d’activation | Examiner le livrable final et les prérequis ci-dessus, puis autoriser distinctement l’activation France et son calendrier. La procédure de désactivation par `GA4_ENABLED=0` est déjà documentée ; aucun paramètre privé ni de production n’est modifié ici. |

**Acquis, à ne pas rouvrir sans nouvel élément :** réception des quatre types après correctif dans DebugView et l’exploration ; concordance agrégée des volumes du 28 octobre ; critère technique de retrait et limites acceptés ; réglages Google dans les vues inspectées, dont sauvegarde du panneau de transmission ; toutes les associations de produits déclarées vides le 29 octobre, accès France/Stéphane et partage de compte confirmés ; configuration habituelle désactivée et procédure de retour à `0`. Aucun nouvel envoi requis pour la réception.

**Limites conservées, pas prérequis supplémentaires :** parcours manuel non strictement contrôlé, attribution individuelle impossible sur les seuls agrégats, absence historique des essais automatisés inexpliquée, absence d’inspection des rapports prédéfinis et de Temps réel. La réserve sur le parcours libre ne transforme pas les volumes en preuve de doublon et ne remplace pas les scénarios contrôlés déjà documentés. Les dimensions personnalisées `service_id` / `display_context` restent optionnelles selon les analyses souhaitées ; aucun `purchase` ni assimilation de `reserve_click` à une vente. Pas de certification globale de conformité ni d’activation autorisée.

## 5. Version complète à approuver et historique des brouillons

**Version courante pour approbation : [mentions complètes France du 29 octobre](analytics-ga4-mentions-france-brouillon.md).** Elle remplace les propositions partielles ci-dessous ; leurs champs entre crochets et leur jargon sont conservés uniquement comme historique de préparation, pas comme informations encore manquantes ni comme texte à publier. La section 8 donne la provenance et les sources officielles.

### Propositions historiques antérieures aux confirmations du 29 octobre

Ces propositions sont destinées à remplacer les formulations trop absolues de la section 11 de `templates/legal/privacy.html.twig` après validation. **Le template reste inchangé dans cette intervention.** Elles décrivent le mécanisme accepté et ne constituent pas un avis de conformité juridique globale.

### Information sur l'accord

> Lorsque la mesure d'audience est activée sur ce site, elle nécessite votre clic sur « Accepter ». Sans choix accepté et valide, le site n'initialise pas Google Analytics. Fermer le bandeau, défiler ou continuer à naviguer ne vaut pas accord. Votre choix est mémorisé pendant six mois dans votre navigateur ; si le choix est absent, expiré, invalide ou inaccessible, la mesure reste désactivée. Le refus ne bloque ni la navigation ni la réservation.

### Retrait et limites du mécanisme

> Vous pouvez revenir sur votre choix avec « Gérer mes cookies ». Le retrait ferme le contexte de mesure d'audience et supprime les cookies Analytics accessibles de cette intégration. Chaque canal d'envoi pris en charge vérifie le choix avant de remettre une requête au navigateur : une vérification qui n'autorise plus la mesure bloque définitivement les envois de ce contexte. Une nouvelle acceptation ne rejoue pas les interactions de l'ancien accord ; la page alors affichée peut être mesurée une fois si elle est autorisée.
>
> Une requête déjà remise au navigateur peut terminer après le retrait. Si le retrait intervient dans un autre onglet entre la dernière vérification du choix et la remise de la requête, cette requête peut encore partir. Le mécanisme couvre les canaux d'envoi interceptés par l'intégration ; il ne garantit pas l'interception de tout canal qu'une future version de Google pourrait utiliser. Le bouton ne supprime pas les données déjà envoyées à Google.

### Conservation, destinataires et transferts — durée renseignée, autres éléments à valider

> Le choix local est conservé pendant six mois. Les cookies Analytics de cette intégration sont configurés pour expirer au plus tard à l'échéance de ce choix, sans prolongation automatique. Cette durée est distincte de la conservation des données par Google. Dans notre propriété Analytics de production, la conservation des données utilisateur et d'événement concernées par ce réglage est fixée à **deux mois**, sans réinitialisation de la conservation des utilisateurs lors d'une nouvelle activité. Ce réglage ne fixe pas une durée de deux mois pour les rapports agrégés standards. Les personnes habilitées à consulter ces données sont **[rôles ou destinataires effectivement autorisés]**.
>
> Le service est fourni dans le cadre de **[identité du prestataire contractuel et référence des conditions applicables à vérifier]**. Les modalités de traitement et, le cas échéant, de transfert hors de l'Espace économique européen sont **[modalités et garanties effectivement vérifiées à compléter]**. Vous pouvez consulter la [politique de confidentialité de Google](https://policies.google.com/privacy).

La durée renseignée provient du bilan utilisateur du 21 octobre, pour **la production seulement**. La portée du réglage est expliquée dans la [documentation Google sur la conservation](https://support.google.com/analytics/answer/7667196?hl=fr) : elle ne limite pas la conservation des rapports agrégés standards. Les champs entre crochets ne sont pas publiables tels quels. Ne pas présenter la durée du choix/cookie comme la durée de conservation de toutes les données Google, ni ajouter une promesse de localisation européenne ou d'effacement rétroactif non vérifiée. Les mentions existantes sur les quatre événements, les pages exclues, les données filtrées et les consentements publicitaires refusés restent fondées sur le code ; le responsable doit approuver le texte final avant toute publication.

## 6. Manifeste et écarts depuis les vérifications

Le fichier [`analytics-ga4-candidat-20261021.json`](analytics-ga4-candidat-20261021.json) contient les chemins relatifs, catégories, tailles et empreintes SHA-256 des octets présents : fichiers applicatifs GA4, tous les fichiers suivis modifiés, configuration publique nécessaire, tests Analytics et infrastructure/non-régression Prestations. Le HEAD précise le socle du dépôt ; les verrous de dépendances sont inclus. Le manifeste décrit des sources, pas un artefact AssetMapper compilé, le contenu installé de `vendor/`, la bibliothèque distante Google ou une configuration privée de déploiement.

Inventaire : **45 fichiers** (16 applicatifs, 10 de configuration/verrous, 9 tests/harnais Analytics, 10 supports/tests Prestations) et empreintes de **7 rapports existants**. Empreinte de l'inventaire canonique, dont le format est décrit dans le JSON : `550a4c98db00871ec131aa578bcb9d96f23dec6382bb259584fc97b7522f124d`. Aucun fichier source du manifeste n'a une date de modification postérieure au rapport réel final ; ce constat de dates est un indice de continuité, pas une preuve rétroactive d'identité.

Il identifie **l'état présent**. Il **ne prouve pas rétroactivement l'identité exacte des sources servies pendant les recettes antérieures**. Les rapports n'archivaient pas de manifeste à ce moment-là. Les empreintes des rapports expurgés utilisés sont conservées séparément dans le manifeste pour identifier ces pièces, sans y copier leurs données ni les identifiants navigateur/session. Les NetLog bruts et profils Chrome sont exclus, de même que les fichiers d'environnement privés et l'avatar non suivi préexistant.

Écarts connus :

- Aucun changement applicatif/configuration/tests détecté par comparaison SHA-256 avec le relevé de l'analyse précédente conservé dans cette session. Aucun changement de ces fichiers effectué ici. Les derniers rapports réels/simulés restent les preuves disponibles ; aucune nouvelle exécution n'est revendiquée.
- La première passe de six temporisations du 21 octobre englobe la conservation des constructeurs dans `analytics_transport.js`. Les trois temporisations finales et la passe complète de 54 contrôles sont postérieures ; ce décalage historique est conservé, pas réécrit.
- Depuis les recettes finales, les écarts documentés portent sur la documentation : confirmation de réception historique, correction du comptage 17 requêtes/15 appels natifs observés, analyse des limites, décision acceptée, présent dossier et manifeste. Ils ne justifient pas une campagne applicative identique.
- Les quatre dépréciations Symfony préexistantes de la suite Prestations et l'absence de preuve d'un artefact de production construit restent documentées. Le manifeste n'efface pas le code de sortie historique de cette suite et ne certifie pas un pipeline de déploiement.

Vérifications de cette préparation : lecture/recomptage des JSON existants, conversion explicite des fuseaux, comparaison d'empreintes, contrôle des liens locaux, du JSON et de `git diff --check`. Aucune recette, aucun test applicatif, aucun envoi Google, aucune modification de propriété ou de template visiteur.

**Actualisation documentaire après revue des réglages :** le manifeste daté n'est pas réécrit. Son empreinte de fichier reste `d764abb7038bde4d174d32f4419cf6f1df270f7c2a8beac5a954a7daacffe730`. Les 45 empreintes de sources/configuration/tests et les 7 empreintes de rapports correspondent toujours aux pièces présentes. Les changements concernent uniquement `analytics-ga4.md`, `analytics-ga4-retrait-onglets.md` et le présent document ; leurs empreintes documentaires changent, mais ils sont explicitement exclus du manifeste historique. Aucune identité rétroactive des sources ni aucun réglage Google n'est certifié par ce manifeste. L'identification locale du candidat reste acquise ; l'artefact construit et les futures mentions approuvées devront être identifiés séparément lors d'une préparation autorisée.

## 7. Revue manuelle des réglages de production — 21 octobre 2026

### Origine et périmètre de la preuve

Source : **bilan textuel transmis par l'utilisateur, issu de la revue manuelle de captures par l'utilisateur et son assistant le 21 octobre 2026**. L'agent chargé de cette mise à jour n'a ni ouvert GA4 ni inspecté lui-même la propriété. Aucune nouvelle capture de ces panneaux n'a été jointe localement à cette intervention ; aucun fichier de capture absent n'est inventé comme pièce. La documentation publique consultée ci-dessous explique des réglages ; elle ne prouve pas leur état dans le compte.

| Environnement | Propriété / flux / destination | Portée |
| --- | --- | --- |
| **Production** | Propriété **francetuncq.fr** ; flux **site France Tuncq** ; URL **https://francetuncq.fr** ; ID **G-MWZX1GYXZE** | Tous les réglages détaillés ci-dessous concernent cette propriété et sa balise. |
| **Recette** | **France Tuncq — Recette**, ID **G-7ZGZR4L2F8** | Destination des recettes historiques et du contrôle des rapports attendu le 22 octobre. Ne pas lui attribuer automatiquement les réglages de production. |

Fuseau confirmé par l'utilisateur : **France, GMT+2 au 21 octobre 2026**. Il s'agit d'un décalage à cette date, pas d'un GMT+2 permanent toute l'année. Les heures de recette ci-dessus restent celles vérifiées à partir des horodatages Unix.

### États constatés au niveau de la propriété de production

« Constaté » signifie état visuel rapporté dans le bilan utilisateur. Lorsqu'aucun état antérieur n'est fourni, ce tableau n'invente ni changement réalisé ni heure de sauvegarde.

| Réglage | Constat rapporté | Statut documentaire |
| --- | --- | --- |
| Mesures améliorées | Désactivées | Constaté ; ne pas confondre avec la ligne grisée Pages vues de la balise. |
| Google Signals | Non activé | Constaté. |
| Collecte des données fournies par les utilisateurs | Non activée | Constaté au niveau propriété. |
| Personnalisation publicitaire | Désactivée pour toutes les régions, affichage **0/307** | Constaté. |
| Données précises de localisation et d'appareil | Collecte désactivée | Constaté ; ne signifie pas absence de toute information technique réseau. |
| Conservation événements et utilisateurs | **Deux mois** | Constaté ; distinct des six mois du choix local et des cookies. |
| Réinitialisation de la conservation utilisateurs sur nouvelle activité | Désactivée | Constaté. |
| Associations Google Ads | Aucune association affichée | Constaté dans la vue inspectée ; ne certifie pas toutes les associations de produits. |

### États constatés et correction visuelle au niveau de la balise de production

| Réglage | Constat rapporté | Statut / limite |
| --- | --- | --- |
| Destinations, vue d'ensemble | Une seule destination visible : **site France Tuncq** | Constaté dans cette vue ; pas un audit exhaustif des associations de produits. |
| Multidomaine | Aucune condition configurée | Constaté. |
| Utilisation des données fournies par les utilisateurs | Désactivée après correction | **Modification confirmée visuellement selon le bilan** : auparavant l'option et la détection automatique des e-mails, téléphones, noms et adresses étaient cochées. Cet état antérieur ne prouve pas une collecte effective de ces données. Ne pas le confondre avec le réglage propriété déjà non activé. |
| Six détections automatiques | Historique du navigateur, défilements, clics sortants, interactions avec les formulaires, vidéos, téléchargements : désactivés | États constatés ; aucune chronologie supplémentaire inventée. |
| Première ligne **Pages vues** | Grisée, curseur à droite | **Ne pas documenter comme désactivée.** Le code maîtrise également les pages vues par `send_page_view: false` et des événements explicites. |
| Événements Universal Analytics | Collecte désactivée | Constaté. |
| Transaction ID, Value, Currency | Aucun mode ni emplacement d'extraction renseigné | Constaté ; ne pas en déduire une recette de paiement, hors périmètre. |
| Marquer automatiquement les données comme consenties | **Non** | Constaté. |
| Remplacer les valeurs par défaut du mode Consentement | Annonces et Analytics : désactivés | Constaté dans ce panneau ; ne pas traduire ces interrupteurs en preuve d'un `analytics_storage=denied` permanent dans l'application. |
| Remplacer les paramètres des cookies | Décoché | Constaté ; cohérent avec les paramètres fournis par le code. |
| Modification et création d'événements | Aucune règle dans les deux listes inspectées | Constaté, limité à ces listes. |

### Transmission de données : sauvegarde confirmée après réouverture

Dans **Gérer la transmission de données**, les choix suivants ont été sélectionnés et une instruction d'enregistrer a été donnée :

- limitation publicitaire activée, option **Bloquer la transmission des données publicitaires** ;
- blocage des données d'analyse du comportement ;
- blocage des données de diagnostic.

**Correction documentaire, complément utilisateur du 28 octobre : la sauvegarde a été confirmée après réouverture le 21 octobre.** La réserve initiale reposait sur la seule capture avant enregistrement ; elle est désormais clôturée sur la base de cette revue manuelle rapportée, sans inspection directe par cet agent ni nouvelle capture locale. Statut : **persistance confirmée manuellement**. Cela ne constitue pas une recette du comportement effectif en production.

Selon la [documentation Google des paramètres de transmission](https://support.google.com/tagmanager/answer/16054531?hl=fr), le blocage de l'analyse du comportement est conditionné au refus de `analytics_storage` ; le blocage publicitaire et celui des diagnostics sont également liés au consentement correspondant. Ce n'est donc pas, sur la seule base de ces libellés, une interdiction de toute mesure après acceptation. Ces options sont gérées dans l'interface, non par une commande gtag équivalente. La sauvegarde est confirmée par la revue manuelle ci-dessus ; cette explication ne démontre pas leur comportement effectif sur le flux France.

### Cohérence vérifiée par lecture du code, sans exécution de balise

| Sujet | Code actuel | Conclusion limitée à la lecture |
| --- | --- | --- |
| Accord Analytics | `analytics.js` exige configuration active, accord valide et page publique avant création du cadre. `analytics_frame.js` vérifie à nouveau l'accord ; les commandes précèdent le chargement Google. Il envoie `consent/default` avec `analytics_storage: granted` **uniquement après cet accord**. | Cohérent avec le consentement basique et le marquage automatique Non. Avant accord, absence de balise plutôt qu'envoi d'un ping de refus. L'interrupteur distant de remplacement désactivé n'est pas une contradiction démontrée avec ce consentement explicite. |
| Publicité et données utilisateur | `ad_storage`, `ad_user_data`, `ad_personalization` restent `denied`. `allow_google_signals` et `allow_ad_personalization_signals` valent `false`, `ads_data_redaction: true`, `url_passthrough: false`. Aucun `user_id`/`user_data`, montant ou devise ajouté par cette intégration. | Cohérent avec les réglages rapportés. Ne prouve pas à lui seul toutes les associations ou extractions distantes. Le panneau de transmission, dont la sauvegarde est maintenant confirmée manuellement, complète ces protections ; il ne remplace pas le garde applicatif. |
| Pages vues | `send_page_view: false` dans `config`, puis `event page_view` explicite avec métadonnées autorisées ; six automatismes rapportés désactivés | Pas de contradiction de configuration identifiée avec la ligne grisée ; elle **n'est pas prouvée désactivée**. L'absence de doublons a été testée sur le flux de recette, pas sur ces nouveaux réglages de production. |
| Cookies | Préfixe dédié, `cookie_domain: none`, `cookie_path: /`, `cookie_flags: SameSite=Lax;Secure`, `cookie_update: false`, échéance au plus égale au choix restant ; nettoyage dédié dans `analytics_policy.js` | Cohérent avec le remplacement des paramètres décoché. Les valeurs effectives en production ne sont pas observées dans cette étape. |
| Durées | Choix validé pendant six mois calendaires, cookie borné par ce choix ; Google réglé sur deux mois pour les données concernées | **Aucune contradiction** : persistance locale du choix/cookie et conservation serveur sont deux mécanismes distincts. Les deux désactivations de renouvellement ont également des portées différentes. |
| Retrait | Relecture avant les transports couverts, fermeture définitive du contexte après lecture non autorisante, destruction/nettoyage et absence de rejeu | Critère déjà accepté inchangé, avec ses trois limites : concurrence lecture/délégation, requêtes déjà remises au navigateur et transports couverts seulement. Les réglages distants ne suppriment pas ces limites. |

**Aucune contradiction applicative démontrée n'a été identifiée par cette lecture.** La contradiction documentaire potentielle était d'assimiler « mesures améliorées désactivées » à « première ligne Pages vues désactivée » ; cette assimilation est explicitement écartée. La désactivation de la localisation/appareil précis ne supprime pas les informations nécessaires aux communications réseau, notamment l'IP, déjà mentionnées dans le brouillon existant. L'état ancien de détection des coordonnées ne prouve pas un envoi effectif et n'explique pas, sans autre preuve, le rapport de recette vide.

Ces constats Google portent sur la **production**, au 21 octobre, après les recettes historiques effectuées exclusivement sur `G-7ZGZR4L2F8`. Aucun de ces nouveaux réglages ne devient rétroactivement couvert par ces essais. La réserve de sauvegarde est désormais clôturée ; la consultation des données existantes a été complétée le 28 octobre. Un seul nouveau parcours manuel est explicitement autorisé pour vérifier la réception après correctif, sans nouvelle campagne complète. Un éventuel écart concret ultérieur devra être décrit avant de proposer une vérification ciblée et distinctement autorisée.

### Actions restant à clôturer et responsables

La [liste de référence actualisée en section 4](#4-prérequis-france-restant-avant-une-activation-distinctement-autorisée) remplace l’ancienne demande de relecture des rapports. La réception dans l’exploration est confirmée par le relevé du 29 octobre ; la sauvegarde du panneau de transmission était déjà confirmée. Les associations et accès sont désormais confirmés. Restent le renseignement de l’entité dans le DPA, l’approbation des mentions, le futur livrable identifié et la décision explicite d’activation, avec leurs responsables indiqués dans cette liste.

## Complément manuel rapporté — 28 octobre 2026

Source : déclaration de l’utilisateur dans l’échange, sans accès direct de cet agent à GA4 ni nouvelle capture locale. Dans la propriété de recette **France Tuncq — Recette, 559568920 / G-7ZGZR4L2F8**, l’exploration ne comporte **aucun segment appliqué ni filtre** ; la pagination permet d’afficher les **six événements**. Le seul filtre de données est **Internal Traffic**, actuellement **Test**. L’historique complet affiché comporte **18 entrées, toutes le 19 octobre, aucune modification ultérieure** ; le détail de création indique **TESTING_FILTER_MODE**. Les contrôles demandés de sélection et de filtre/historique sont donc clôturés dans ce périmètre rapporté ; ils ne démontrent pas la cause de l’absence des événements de 10 h et ne prouvent pas une ingestion.

Séparément, pour la **production**, l’utilisateur confirme la persistance du panneau de transmission après sa réouverture le **21 octobre**. Les autres prérequis France et l’approbation des mentions restent distincts. Le critère technique de retrait accepté est conservé ; aucune activation de production n’est autorisée.

## 8. Identité, accès et conditions Google — complément du 29 octobre 2026

### Provenance et état effectivement rapporté pour le compte

**Source : déclaration explicite de l’utilisateur le 29 octobre 2026**, fondée sur ses contrôles du compte et ses confirmations d’identité/adresses. L’agent n’a ni ouvert le compte Analytics ni inspecté de nouvelles captures à cette étape. Les constats de production ne sont pas réattribués à la propriété de recette. Aucune modification de compte n’est effectuée.

| Élément | Constat confirmé / limite |
| --- | --- |
| Exploitant | **POTENTIEL CONSULTING, SASU**, SIRET **91124890400029** |
| Siège | **1 La Fendoire, 44770 La Plaine-sur-Mer**, confirmé par l’utilisateur |
| Correspondance du contact DPA | **1B La Fendoire, 44770 La Plaine-sur-Mer** ; différence avec le siège volontaire, ne pas uniformiser |
| Contact principal DPA | **France Tuncq**, `contact@universdesnombres.com` ; enregistrement confirmé visuellement selon l’utilisateur. Ce statut n’est pas une désignation de DPO |
| Acceptation des conditions de traitement | **16 octobre 2026**, selon l’écran du compte rapporté ; aucun export du texte contractuel exact accepté n’est fourni ici |
| Utilisateurs Analytics | **France et Stéphane uniquement**, selon l’utilisateur ; aucun rôle détaillé ni statut professionnel de Stéphane n’est inventé |
| Associations de produits en production | **Toutes contrôlées et déclarées vides** par l’utilisateur ; réserve antérieure sur les vues non examinées clôturée |
| Partage « Produits et services Google » | Décoché |
| Partage « Contributions à la modélisation et insights métier » | Décoché |
| Partage « Assistance technique » | Coché |
| Partage « Recommandations pour votre entreprise » | Coché |
| « Personnalité juridique » du DPA | **Vide**, non déclarée renseignée |
| Organisation Marketing Platform | **Aucun rattachement effectué** ; aucun rattachement n’est réalisé ni imposé par cette intervention |

La sauvegarde du panneau de transmission après réouverture le 21 octobre demeure acquise. Les réglages de partage ci-dessus, les associations de produits et les comptes utilisateurs constituent trois périmètres différents : deux utilisateurs nommés ne signifient pas absence d’accès des personnels Google habilités. L’état des cases n’atteste pas qu’un agent Google a effectivement consulté les données.

### Recherche officielle du 29 octobre — dispositions publiques et portée

**Prestataire retenu : Google Ireland Limited.** Les [conditions françaises de Google Analytics standard](https://marketingplatform.google.com/about/analytics/terms/fr/) désignent cette société et incorporent les conditions de traitement (§7). Cette identification découle des conditions publiques applicables au service standard présenté dans ce dossier ; elle n’est pas un constat d’un contrat nominatif du compte. L’[aide DPA Analytics](https://support.google.com/analytics/answer/3379636?hl=fr) indique leur inclusion pour les entreprises de l’EEE. La date affichée du 16 octobre est une preuve d’acceptation rapportée, pas une copie vérifiée de la version exacte du contrat. Ne pas confondre le prestataire irlandais avec Google LLC, destinataire américain possible.

Google Analytics figure dans les [services concernés par les conditions de traitement](https://business.safety.google/adsservices/). Le titre « Google Ads » du DPA n’implique donc ni activation publicitaire ni association Google Ads pour ce compte.

Les [conditions de traitement](https://business.safety.google/adsprocessorterms/) décrivent Google comme sous-traitant (§5), prévoient le traitement dans les pays où Google ou ses prestataires disposent d’installations (§10), et encadrent leurs interventions (§11). Leur annexe 3A organise les garanties de transfert et les clauses applicables selon les situations (§7) ; elle demande aussi des informations exactes sur l’entité cliente (§9). Ce sont des engagements contractuels publics, pas une vérification indépendante de leur exécution pour cette propriété.

Pour les transferts vers les États-Unis, la [page Google dédiée aux produits publicitaires et Analytics](https://business.safety.google/adsdatatransfers/) indique le recours au **cadre UE–États-Unis (DPF)** pour les transferts concernés ; des clauses contractuelles types sont prévues dans les autres situations applicables. La [page générale des mécanismes de transfert](https://policies.google.com/privacy/frameworks) décrit aussi ces dispositifs. Le lien du registre DPF a été consulté, mais n’a pas fourni de contenu exploitable avec l’outil : la certification est ici **rapportée d’après les publications Google**, pas présentée comme un contrôle indépendant du registre. Aucun chemin, pays de stockage exclusif ou mécanisme précis par événement du compte n’est démontré par ces pages ou par les traces réseau.

La [liste des sous-traitants ultérieurs](https://business.safety.google/adssubprocessors/) mentionne notamment des entités d’assistance pour Analytics dans plusieurs pays. Elle indique des prestataires possibles du service, pas lesquels ont effectivement traité ce compte. Le brouillon informe donc de traitements possibles hors EEE, y compris aux États-Unis, sans promettre un hébergement exclusivement européen. Une collecte initiale en Europe n’équivaut pas à une localisation exclusive de tout traitement ; voir la [documentation GA4 UE](https://support.google.com/analytics/answer/12017362?hl=fr).

Les [paramètres de partage](https://support.google.com/analytics/answer/1011397?hl=en) distinguent l’accès de l’assistance et l’utilisation de données du compte pour les recommandations. Désactiver les deux autres cases n’équivaut donc pas à « Google n’accède jamais aux données ». La qualité de responsable distinct décrite pour l’option « Produits et services Google » activée n’est pas attribuée à ce compte où elle est désactivée. Les données de compte et de configuration ne sont pas assimilées aux noms/courriels des visiteurs que l’intégration n’envoie pas.

La [conservation GA4](https://support.google.com/analytics/answer/7667196?hl=fr) explique la portée des deux mois et les cycles mensuels de suppression ; ce réglage ne limite pas les rapports agrégés standards. Le réglage effectif de production et l’absence de réinitialisation sur activité proviennent de la revue manuelle déjà acquise. Le code conserve séparément le choix pendant six mois et borne l’échéance des cookies à celle du choix. Aucun délai global de suppression de toutes les données Google n’est inventé.

### Information restant à consigner et approbation

**Pas de coordonnée manquante pour rédiger les mentions.** Le seul point administratif explicitement incomplet est l’identification de l’entité dans le DPA : l’[aide officielle, « Fournir des coordonnées »](https://support.google.com/analytics/answer/3379636?hl=fr), demande le nom de l’entité juridique. POTENTIEL CONSULTING est connu mais son renseignement dans cette rubrique n’est pas acquis. France / l’administrateur doit faire consigner l’entité par le moyen applicable au compte, ou demander à Google la procédure si l’interface ne le permet pas. Ce constat n’annule pas l’acceptation rapportée et ne rend pas obligatoire, sur les preuves disponibles, une organisation Marketing Platform. Aucune modification n’est faite ici.

Le [texte intégral à approuver](analytics-ga4-mentions-france-brouillon.md) est complet, avec destinataires, transferts et contact. L’approbation par l’utilisateur mandaté par France et l’intégration locale sont désormais consignées dans le dossier de livraison ; elles n’autorisent pas l’activation. Les sources publiques suffisent à exposer les mécanismes Google sans inventer une garantie d’exécution ; aucune demande de nouvelle recette ou de réexamen des réglages acquis n’est ajoutée. La validation des mentions n’est ni une certification globale de conformité ni une autorisation de production.

Le texte visiteur évite le jargon du garde de transport et des cadres. Les limites techniques acceptées restent dans `analytics-ga4-retrait-onglets.md` ; leur garantie n’est ni renforcée ni réduite. La politique actuelle contient des formulations générales à harmoniser lors de l’intégration (communication à des tiers, justificatif d’identité systématique) : propositions ciblées dans les notes du brouillon, **sans modification du template actuel**. Pour l’information et les droits, référence de rédaction : [RGPD, articles 7, 12, 13 et 15 à 20](https://eur-lex.europa.eu/eli/reg/2016/679/oj?locale=fr). Aucun DPO n’est inventé à partir du seul contact principal DPA.

