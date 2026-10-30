# GA4 — mise en production et réception du 30 octobre 2026

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
