# GA4 — mise en production et réception du 30 octobre 2026

<a id="cloture-france-20261104"></a>

## Clôture du chantier France — 4 novembre 2026

**Livraison, activation, correctif du contact public, point administratif et réception dans les rapports standards : périmètre clôturé au 4 novembre 2026 (Europe/Paris).** Cette consignation s’appuie sur les sorties Git, les contrôles SSH/HTTP et les constats de captures transmis par l’utilisateur. Aucun accès distant, navigateur, inspection directe d’Analytics ou nouvel envoi n’a été réalisé pour cette mise à jour.

### Publications Git et état de production rapportés

| Dépôt | Références publiées avec succès, selon les sorties utilisateur | Commit |
| --- | --- | --- |
| StephaneBouret/appointment-sym | main | `98d572aff3d8e7836660178b745b6c694673c31a` |
| ftuncq/sym-appointment | main et livraison/ga4-france-20261029 | `1c2a93ee2b2ff2813d97292cae4f8f8e44f4c5c9` |

Selon les contrôles SSH et HTTP rapportés, la production France, dans **/home/u664242226/domains/francetuncq.fr/public_html**, est sur **main**, HEAD **1c2a93ee2b2ff2813d97292cae4f8f8e44f4c5c9**. Le fichier `templates/legal/privacy.html.twig` est conforme au contrôle ; le courriel public et le lien `mailto:` sont **contact@luniversdesnombres.com**. Analytics reste actif : **GA4_ENABLED=1**, destination **G-MWZX1GYXZE**. Aucun asset supplémentaire n’est nécessaire pour ce correctif.

Ces éléments confirment le résultat rapporté, sans documenter la méthode ni l’heure exacte du déploiement : aucune n’est déduite de la [procédure préparée](analytics-ga4-correctif-contact-publication.md). Les défauts locaux restent **GA4_ENABLED=0** dans les deux worktrees. Les archives, manifestes, récépissés, `release-sha.txt` et preuves historiques ne sont pas réécrits.

### Point administratif confirmé et clos

« Personnalité juridique » : **POTENTIEL CONSULTING** ; contact principal : **France Tuncq** ; courriel : **contact@luniversdesnombres.com** ; correspondance : **1B La Fendoire, 44770 La Plaine-sur-Mer**. La clôture a déjà été confirmée par l’utilisateur. La distinction avec le siège au **1 La Fendoire** est conservée.

Le rattachement du compte à l’organisation **Google Marketing Platform POTENTIEL CONSULTING** est distinct des **associations de produits de la propriété Analytics**, précédemment déclarées vides. Il ne modifie pas ce constat. La [rectification administrative](analytics-ga4-rectification-20261104.md) et ses réserves juridiques restent applicables ; cette clôture n’est pas une certification juridique globale.

### Rapports standards de production — constats de captures rapportés

L’utilisateur indique consulter **francetuncq.fr, propriété 559257505**. Les captures du rapport **« Événements : Nom de l’événement »** ont été examinées par son assistant conversationnel. Elles ne sont pas disponibles comme pièces jointes dans cette intervention : les observations ci-dessous sont **consignées comme rapportées**, sans prétendre à une lecture de ces images par cet agent ni à une inspection directe d’Analytics.

Pièces identifiées par l’utilisateur :

- `image(20261104-105455).png` : période du **30 octobre 2026**.
- `image(20261104-105523).png` : période du **30 octobre au 4 novembre 2026**.

Les périodes et les six lignes d’événements sont visibles selon ces constats. Les captures recadrées ne montrent pas le sélecteur de propriété : son identification repose aussi sur le contexte rapporté par l’utilisateur, et non sur les seules images. Les noms de pièces sont des références rapportées, pas des liens vers des fichiers locaux disponibles.

| Événement / indicateur | 30/10/2026 | 30/10 au 04/11/2026 |
| --- | ---: | ---: |
| page_view | 6 | 12 |
| discover_session_click | 2 | 2 |
| view_service | 2 | 2 |
| reserve_click | 1 | 1 |
| first_visit | 2 | 3 |
| session_start | 2 | 5 |
| **TOTAL événements** | **15** | **25** |
| **Nombre total utilisateurs** | **2** | **3** |

Totaux vérifiés : **6 + 2 + 2 + 1 + 2 + 2 = 15** et **12 + 2 + 2 + 1 + 3 + 5 = 25**. Les quatre événements applicatifs représentent respectivement **11** et **17** événements ; `first_visit` et `session_start` complètent les totaux avec **4** et **8** événements automatiques. Le nombre total d’utilisateurs est un indicateur distinct, sans addition aux événements.

**La présence des quatre événements applicatifs dans le rapport standard du 30 octobre est confirmée par ces preuves rapportées. Le contrôle précédemment « non effectué » / « non contrôlé » est désormais effectué.** Ce constat complète la réception Temps réel du 30 octobre et reste distinct des explorations de recette historiques.

Ces totaux agrégés ne démontrent **ni l’attribution individuelle au parcours de recette, ni la déduplication, ni une réservation finalisée**. `reserve_click` reste un clic, sans preuve de réservation ni de paiement. Le relevé jusqu’au **4 novembre est une photographie au moment de la consultation**, pas une garantie de journée complète ; aucune heure exacte de consultation n’est déduite des noms de fichiers.

### Réserves et portée de la clôture

L’absence historique des essais automatisés reste **inexpliquée**, sans responsabilité démontrée du mode headless. Le critère de retrait accepté reste inchangé : chaque transport couvert relit le consentement avant délégation ; une lecture non autorisante bloque définitivement ce contexte, détruit le cadre, nettoie les cookies dédiés et empêche le rejeu après une nouvelle acceptation. Ses limites restent conservées : **absence d’atomicité entre lecture et délégation, requêtes déjà confiées au navigateur, couverture limitée aux transports interceptés**, sans garantie universelle sur les versions futures de Google. Voir le [dossier de retrait](analytics-ga4-retrait-onglets.md).

Ces réserves ne créent pas de nouveau prérequis à la clôture du périmètre annoncé. Aucun changement applicatif, de configuration ou de test, aucune reconstruction d’assets, aucun commit, push ou déploiement n’est réalisé pour cette consignation. Le dépannage SSH GitHub reste dans son flux distinct.

**Historique conservé ci-dessous :** les constats du 30 octobre et de la préparation du correctif restent valables à leur date ; leurs mentions de rapports non contrôlés, de point administratif ouvert ou d’ancien HEAD ne décrivent plus le statut courant. Les preuves historiques ne sont pas modifiées.

## Rectification du 4 novembre 2026 — confirmations utilisateur du 30 octobre

**Statut documentaire actuel : « Personnalité juridique » clôturée sur confirmation rapportée.** Organisation Google Marketing Platform et personnalité juridique : **POTENTIEL CONSULTING** ; compte Analytics associé **France Tuncq, 412243474**. Contact principal **France Tuncq**, **contact@luniversdesnombres.com** ; correspondance **1B La Fendoire, 44770 La Plaine-sur-Mer, France**, siège **1 La Fendoire**. Le rattachement à une organisation est distinct des associations de produits Google, précédemment déclarées vides ; il ne contredit pas ce constat.

Voir la [rectification et sa provenance](analytics-ga4-rectification-20261104.md). Les mentions ci-dessous de champ vide/ouvert, d’absence de rattachement et l’ancien courriel sont des **constats historiques rectifiés, à ne pas reprendre comme coordonnées courantes**. Les preuves, manifestes et approbations historiques restent inchangés. Aucune certification juridique globale ni vérification des rapports standards de production n’en découle. La production reste active ; les défauts locaux restent désactivés.

**Déploiement, activation explicitement autorisée puis exécutée et réception des quatre événements applicatifs en Temps réel confirmés par l’utilisateur pour francetuncq.fr.** Les rapports standards de production ne sont pas encore contrôlés. La rubrique administrative « Personnalité juridique » du DPA reste ouverte. Ce compte rendu ne constitue pas une validation globale de conformité juridique.

## Provenance et portée

Les faits serveur et Analytics ci-dessous proviennent du compte rendu de l’utilisateur, qui confirme les opérations et les captures examinées avec son assistant. **Ces captures ne sont pas jointes au contexte de cette intervention : leurs observations sont consignées comme rapportées.** Cet agent n’a inspecté directement ni le serveur, ni Analytics, ni les captures de production, et n’a récupéré aucune preuve distante.

Date des opérations rapportées : **30 octobre 2026**, référentiel Europe/Paris (UTC+01:00). Aucune heure exacte n’a été fournie ; aucune chronologie à la seconde n’est reconstruite. La présente intervention est exclusivement documentaire : lectures et vérifications de fichiers/Git locaux, sans navigateur, SSH, requête au site, test de recette ou nouvel envoi Google.

Cette étape complète les preuves historiques ; elle ne transforme pas les vérifications locales antérieures en inspection de production. Les archives, manifestes, empreintes et récépissés restent inchangés. Les anciens statuts « non publié », « non activé » ou « Temps réel non confirmé » restent valables pour leur date et leur périmètre de recette ; le statut de production courant est celui consigné ici.

## Publication et déploiement rapportés

| Élément | Confirmation utilisateur |
| --- | --- |
| Dépôt France | https://github.com/ftuncq/sym-appointment.git |
| Références publiées | main et livraison/ga4-france-20261029 poussées au commit 29bcd686bc1dc5e7dfdccbfb937cead80c19c6bc |
| Commit déployé | 29bcd686bc1dc5e7dfdccbfb937cead80c19c6bc |
| Socle précédent | 360d7811c690940dc1fa67ed22f5f2f0873758c3 |
| Commit applicatif conservé | 7033249c538127371ef23f4e25670edd547c83df, suivi du commit documentaire de livraison |
| Racine Symfony | /home/u664242226/domains/francetuncq.fr/public_html |
| Artefact utilisé | 20261029-france.zip — lot de 18 sources et 86 fichiers AssetMapper, pas une application complète |
| SHA-256 de l’archive | 6e3bb812ffb307c422659f7974a51058ea1c90cdc489238077b397beb31006f3 |
| Vérifications de livraison | Sources et assets contrôlés ; validation Twig et opérations de cache réussies |
| État local du serveur | Modification préexistante de composer.json et fichiers locaux préservés selon le relevé ; aucune remise à zéro supposée |
| HTTP avant activation | Analytics désactivé, mentions approuvées présentes et CSS conforme |

Les fichiers locaux à préserver avaient été identifiés comme .htaccess, public/.htaccess, public/robots.txt, error_log, images dans public/images/about/ et public/images/avatars/, ainsi que la configuration privée .env.local. Leur préservation est rapportée ; leurs contenus et secrets ne sont pas lus ni copiés ici. Le statut des branches distantes n’est pas vérifié par un fetch ou une connexion GitHub dans cette intervention.

## Environnement et activation rapportés

- PHP CLI utilisé : **/opt/alt/php84/usr/bin/php**, version **8.4.27**.
- PHP web : **8.4.27**, UID **664242226**. Cette confirmation du processus web remplace la réserve historique ; elle ne découle pas du propriétaire de .env.local.
- OPcache activé, **validate_timestamps=1**, **revalidate_freq=2**. Ces valeurs sont rapportées pour le web ; aucune commande de redémarrage non observée n’est inventée.
- Activation **explicitement autorisée puis exécutée** : configuration persistante **GA4_ENABLED=1**, destination **G-MWZX1GYXZE** ; configuration servie par HTTP confirmée active avec cet identifiant.
- Propriété de production : **francetuncq.fr**, ID **559257505**. La propriété de recette **France Tuncq — Recette**, flux **G-7ZGZR4L2F8**, reste distincte.

L’activation effective du serveur ne modifie pas les valeurs par défaut locales : **GA4_ENABLED=0** dans la configuration d’exemple et les valeurs de repli du dépôt principal et du worktree de livraison. Aucun fichier d’environnement privé local n’est consulté pour en déduire une configuration effective, ni modifié. Le présent compte rendu ne donne pas d’autorisation pour une nouvelle opération serveur.

## Contrôle manuel et preuves de réception rapportés

L’utilisateur confirme le bandeau visible, une navigation utilisable après refus, la réouverture de « Gérer mes cookies », puis l’acceptation. Le parcours « Découvrir la séance → Réserver » a été effectué **sans soumission de formulaire**. Il ne constitue ni une réservation finalisée ni un paiement.

**Preuve réseau rapportée :** une capture montre un page_view de l’accueil vers **region1.google-analytics.com/g/collect**, destination **G-MWZX1GYXZE**, avec une réponse **HTTP 204**. Cette réponse est observée pour cette requête seulement ; aucune réponse HTTP des trois autres types n’est déduite de cette capture. Aucune file de commandes dataLayer ni trace réseau exhaustive de production n’a été examinée ici.

**Preuve d’interface rapportée :** la capture finale **Temps réel**, dans la propriété de production, affiche :

| Événement | Compteur affiché |
| --- | ---: |
| page_view | 2 |
| discover_session_click | 1 |
| view_service | 1 |
| reserve_click | 1 |
| **Total des quatre types rapportés** | **5** |

Les pages **« Accueil »** et **« Fiche prestation »** sont visibles. Ces observations confirment la **réception des quatre types d’événements en production**. La réponse réseau 204 et la présence dans Temps réel sont deux preuves distinctes ; un acquittement HTTP seul ne démontrerait pas l’affichage dans Analytics.

**Limite d’attribution :** une navigation supplémentaire vers l’accueil a été effectuée pour le diagnostic réseau. Les compteurs ne permettent pas d’attribuer individuellement chaque événement à une action, ni de démontrer une déduplication. Ils ne sont pas présentés comme un parcours strictement contrôlé. Aucun compteur first_visit/session_start ni constat DebugView de production n’est inventé. Les **rapports standards de production restent non contrôlés** ; les explorations acquises sur la propriété de recette ne valent pas vérification de ces rapports.

## Critère de retrait et limites conservés

Le critère technique explicitement accepté reste inchangé : **chaque transport couvert relit le consentement avant délégation ; une lecture non autorisante bloque définitivement ce contexte, avec destruction du cadre, nettoyage des cookies dédiés et absence de rejeu après une nouvelle acceptation**.

Les limites acceptées restent celles du [dossier de retrait](analytics-ga4-retrait-onglets.md) : absence d’atomicité entre lecture du consentement et délégation au navigateur, requêtes déjà confiées au navigateur avant retrait, couverture limitée aux transports interceptés sans garantie universelle sur les versions futures de Google. Le contrôle manuel de production rapporté n’est pas une nouvelle campagne exhaustive de retrait ou de déduplication et ne transforme pas les scénarios historiques réussis en garantie universelle.

Le sous-comptage lié au refus et aux bloqueurs demeure. L’absence historique des données des essais automatisés de recette reste inexpliquée ; aucune responsabilité du mode headless n’est déduite de cette réception en production. Aucun événement d’achat ni assimilation du clic Réserver à une vente n’est ajouté.

## Conservation distante des preuves — références seules

- Sauvegarde : **/home/u664242226/private-deployments/ga4-20261030/backup**.
- Preuves d’activation : **/home/u664242226/private-deployments/ga4-20261030/activation-uPGkU2S2**.

Ces emplacements sont fournis par l’utilisateur, hors racine web. Aucun contenu n’est récupéré, inventorié ou exposé ici. Les références ne constituent pas une vérification de leur contenu ou de leurs permissions par cet agent.

## Statut courant et suite documentaire

**Table historique au 30 octobre, remplacée pour le statut actuel par la [clôture du 4 novembre](#cloture-france-20261104).** Les suites alors prévues pour les rapports standards et le point administratif sont désormais effectuées / closes selon les preuves et confirmations rapportées ci-dessus.

| Critère | Statut au 30 octobre | Suite / responsable |
| --- | --- | --- |
| Déploiement du commit et de ses assets | **Confirmé par l’utilisateur** | Ne pas redemander la préparation ou rejouer la publication |
| Activation persistante et configuration HTTP | **Confirmées par l’utilisateur**, après autorisation explicite | Production active ; défauts locaux maintenus à 0 |
| Réception des quatre événements en production | **Confirmée dans Temps réel, observation rapportée** | Aucune nouvelle collecte demandée pour cette clôture |
| Mentions approuvées | **Présence HTTP confirmée**, approbation antérieure conservée | Pas de nouvelle approbation rédactionnelle requise |
| Rapports standards de production | **Non encore contrôlés** | Utilisateur disposant d’Analytics : consulter ultérieurement les données déjà reçues, sans nouveau parcours requis |
| « Personnalité juridique » du DPA | **Toujours ouverte** | France / administrateur Analytics : faire consigner l’entité par le moyen applicable ; ce déploiement ne clôt pas ce point |
| Conformité juridique globale | **Aucune certification déclarée** | Ne pas assimiler mise en production et audit juridique global |

La procédure SSH du worktree de livraison reste une pièce de préparation et de récupération historique, pas un journal exact des commandes exécutées. Ses exigences de maintien à 0 concernent la publication initialement désactivée et le retour arrière ; elles ne décrivent plus l’état actif rapporté après l’autorisation distincte. Aucune nouvelle action de désactivation ou de retour arrière n’est exécutée ici.

## Vérifications locales de cette intervention documentaire

Lecture des instructions applicables et des documents ; relevé local de Git ; contrôle de l’archive locale contre l’empreinte annoncée et du maintien des défauts locaux désactivés. Au début de cette étape, le dépôt principal est à 2653315d0f9ee2594079f8bfb34b357459aca955 avec ses travaux préexistants, la livraison à 29bcd686bc1dc5e7dfdccbfb937cead80c19c6bc et le worktree France historique à 360d7811c690940dc1fa67ed22f5f2f0873758c3. Ce relevé local ne vaut pas inspection des références distantes ou du serveur.

Vérification finale du périmètre Markdown, des liens internes ajoutés, du maintien du texte approuvé et des contenus historiques, des empreintes des manifestes/archives/récépissés, des fichiers hors documentation et des trois index Git, dont l’avatar préexistant. git diff --check est exécuté dans le dépôt principal et le worktree de livraison ; les nouveaux documents non suivis font également l’objet d’un contrôle d’espaces explicite. Les modifications documentaires restent **non commitées** ; préparation des commits et push réservée à une étape suivante. Aucun test Analytics, changement applicatif, configuration, asset, SSH, navigateur, commit, push, merge ou déploiement dans cette intervention.
