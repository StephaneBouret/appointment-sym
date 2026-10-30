# GA4 — candidat adapté à France, 29 octobre 2026

## Actualisation — production du 30 octobre 2026

**Déploiement, activation autorisée puis exécutée et réception des quatre événements dans Temps réel confirmés par l’utilisateur.** Voir le [compte rendu de mise en production](analytics-ga4-production-20261030.md). Les opérations et captures sont rapportées par l’utilisateur et son assistant ; aucune inspection directe du serveur, d’Analytics ou des captures de production par cet agent.

Le commit 29bcd686bc1dc5e7dfdccbfb937cead80c19c6bc et le ZIP identifié ci-dessous sont rapportés déployés, puis l’activation autorisée a été effectuée. Le relevé du serveur complète la cible locale connue ; il n’est pas une inspection directe par cet agent. Les défauts locaux et les manifestes sont inchangés.

Les statuts de préparation et de recette qui suivent sont conservés comme **historique daté**, sans annuler les acquis du 30 octobre ni rouvrir les contrôles déjà confirmés. Aucune nouvelle opération serveur n’est autorisée par cette actualisation.

## Historique conservé

**Préparation locale terminée, collecte désactivée.** Ce rapprochement ne vaut ni publication, ni activation, ni validation globale de production. Aucun appel Google, parcours de recette réelle, migration ou opération métier exécuté. La réception après correctif et les limites acceptées du retrait restent acquises dans leur périmètre historique ; elles ne sont pas rejouées.

## Cible effectivement examinée

Le worktree **C:/Formations/appointment-sym-france** est propre, sans modification suivie ni fichier non suivi non ignoré, sur **deploiement/accueil-cap-france**, HEAD **360d7811c690940dc1fa67ed22f5f2f0873758c3**. Ce commit décrit ici la cible locale constatée, sans être imposé au dépôt principal. **Aucune correspondance avec les fichiers actuellement déployés n'est établie.** Les fichiers privés d'environnement et les données du site sont exclus de la préparation ; leur contenu n'est ni repris ni publié. Les dépendances ignorées pertinentes ont aussi été examinées : vendor Composer présent ; assets/vendor absent dans France.

Dépôt principal : main, HEAD **2653315d0f9ee2594079f8bfb34b357459aca955**, modifications GA4 préexistantes et avatar non suivi conservés. Aucun AGENTS.md applicable trouvé aux sources concernées. L'exception safe.directory utilisée pour les lectures Git France était limitée aux commandes, sans modifier la configuration Git. Les deux index sont vérifiés par empreinte avant/après.

Livrable d'entrée : **var/analytics-delivery/20261029-mentions.zip**, SHA-256 **81e8b5be9d08f23054514fb088c3bbc4b4be137ad20cbd8b3c1ab95e26c983e9**. CRC du ZIP et empreintes des 105 fichiers payload contrôlés contre le manifeste du 29 octobre. Les 18 sources locales correspondantes coïncident avec ce livrable. Archive et manifestes historiques conservés sans modification.

## Différences utiles et traitement

| Zone | Différence constatée | Traitement du candidat |
| --- | --- | --- |
| CSS | France possède ses couleurs, présentations, commentaires et bouton de retour en haut | Insertion du seul bloc de consentement ; suppression de ce bloc redonne exactement le CSS France initial |
| Accueil, catalogue, pied de page, navigation, maintenance | Contenus France, dont « Réserver un accompagnement individuel », ordre des modalités et images propres | Tous conservés ; composants fiche et « Découvrir la séance » identiques au socle du lot, instrumentation seule ajoutée |
| Base et services | Différences de fin de fichier seulement | Ajouts Analytics appliqués en conservant la fin de fichier France ; paramètres 0 / identifiant vide |
| Politique | Même socle que le dépôt principal avant le lot | Section 11 approuvée, renvoi section 9 et vérification d'identité en cas de doute raisonnable ; aucune note interne publiée |
| Routes, contrôleurs métier et entités | Quelques différences de fin de fichier, aucune incompatibilité de route identifiée | Sources France conservées ; ajout du contrôleur du cadre et de la configuration Analytics seulement |
| Composer | composer.lock identique ; composer.json diffère uniquement par sa fin de fichier | Aucun changement de dépendance ; versions et références des paquets installés vérifiées contre le verrou, identiques entre les deux worktrees |
| JavaScript téléchargé | assets/vendor absent de France | Copies locales du dépôt principal identifiées et utilisées dans le candidat, sans téléchargement ; importmap de base identique |
| Assets construits | Trois images du socle principal absentes de France, deux images propres ajoutées | Construction depuis les sources France : 81 assets + cinq métadonnées, et non reprise des 82 anciens assets |
| Environnements et données | Configuration de déploiement non établie ici | Aucun environnement privé, base, migration ou donnée métier repris dans le lot à intégrer |

Les trois anciennes images sont img/france-tuncq-pict.jpg, img/not-found copy.svg et img/satisfait-remboursement.png ; les deux images France sont img/Ensemble formes.jpg et img/France Tuncq 2023.png. Parmi les chemins communs du manifeste AssetMapper, seul styles/app.css change d'empreinte construite. Aucune suppression d'asset ancien n'est prescrite sur un site existant.

Les **quatre modules Analytics et le contrôleur Stimulus instrumenté restent identiques au lot d'entrée**. Les quatre événements, la liste des routes autorisées, le filtrage des paramètres, les navigations complètes aux frontières privées, le traitement AJAX et les protections de transport n'ont pas été réécrits. Le libellé « France Tuncq et le prestataire chargé du développement et de la maintenance du site » reste celui approuvé par délégation. Aucun choix métier ou changement du texte approuvé n'a été nécessaire.

**Critère de retrait accepté inchangé :** chaque transport couvert relit le consentement avant délégation ; une lecture non autorisante bloque définitivement ce contexte, détruit le cadre, nettoie les cookies dédiés et empêche le rejeu après nouvelle acceptation. Absence d'atomicité lecture/délégation, requêtes déjà déléguées et couverture limitée aux transports interceptés restent les limites expressément acceptées. Voir le dossier historique de retrait, non remplacé par cette préparation.

## Candidat et archive

Préparation isolée : **var/analytics-delivery/20261029-france/**. L'arborescence candidate/ contient les sources France nécessaires et une copie locale des dépendances pour les contrôles ; elle n'est pas une copie des environnements ou des données de production.

Archive : **var/analytics-delivery/20261029-france.zip** ; empreinte exacte dans **20261029-france.zip.sha256**. Manifeste : [analytics-ga4-france-livrable-20261029.json](analytics-ga4-france-livrable-20261029.json), également manifest.json dans l'archive.

**Il s'agit d'un lot de fichiers à intégrer, pas d'une version complète autonome.** Seul payload/ est destiné à l'intégration : **18 sources et 86 fichiers AssetMapper, soit 104 fichiers**. Il contient la configuration d'exemple désactivée, pas de .env privé. Ne pas remplacer la racine du site par l'archive.

- sources/ : copie de revue des entrées applicatives, de configuration, de templates, d'assets et de tests du candidat, hors dépendances PHP complètes et fichiers d'environnement privés.
- build-inputs/ : ressources des paquets utilisées par AssetMapper et métadonnées des dépendances installées, identifiées séparément.
- evidence/ : comparaison ciblée, inventaire initial, résultats, HTML synthétiques et captures ; aucune trace de collecte réelle ni profil navigateur.
- support/ : vérification du paquet et supports locaux de construction/contrôle, jamais à publier.
- documentation/ : ce dossier, les explications Analytics et les mentions approuvées, hors racine web.

Le manifeste identifie les octets du candidat effectivement vérifié, les sources France de départ, les sources/outputs construits, les supports et preuves. Il **ne prouve pas rétroactivement** l'identité des sources lors des recettes antérieures. Les quatre modules sont néanmoins comparés octet pour octet au lot d'entrée, et leurs sorties construites aux sources.

## Contrôles exécutés sur ce candidat

Bootstrap synthétique sans Dotenv : SQLite strictement en mémoire, mailer nul, Stripe bloqué, client HTTP Symfony simulé ; aucun accès aux services réels. Construction en prod sans debug avec GA4_ENABLED=0 et ID vide, caches et sorties isolés. Les tests PHP de contrat utilisent uniquement un identifiant fictif sans navigateur ou transport Google.

| Contrôle | Résultat et preuve dans evidence/ |
| --- | --- |
| YAML / Twig | 34 YAML et 55 templates valides ; check-1.log / check-2.log |
| AssetMapper | 81 assets, trois entrées app / analytics / analytics-frame ; check-3.log / check-4.log |
| Contrats PHP de configuration et intégration | 3 tests, 49 assertions réussies ; anonymat/connexion, métadonnées filtrées, fiche, frontières privées ; check-6.log |
| Rendu ciblé | HTTP 200 accueil/catalogue/fiche/connexion/confidentialité, titre France conservé, marqueurs découverte/réservation présents, fragment AJAX sans document complet, cadre désactivé HTTP 404 ; integration-render.json |
| Mentions | 12 paragraphes et sept liens identiques à l'approuvé, ancre section 9 et harmonisation d'identité présentes, pas de notes internes ; render-check.json |
| Visuel | 1365 × 900 et 390 × 844, clair/sombre, aucun débordement ; quatre captures, ordinateur clair et mobile sombre inspectés |
| Collecte désactivée | Sur les cinq pages, aucun cadre ni cookie Analytics ; aucune tentative gtag/collect ; aucun accès externe autorisé ; render-check.json |
| Intégrité | Sources France hors lot inchangées, quatre modules source/construits identiques, paquet vérifié par manifeste ; integrity.json |
| Arrêt | Chrome headless isolé et serveur statique fermés, processus PHP ponctuels terminés ; navigateur habituel non touché |

Commandes depuis candidate/ pour les contrôles console/PHP :

~~~powershell
php -d xdebug.mode=off ../support/console.php lint:yaml config
php -d xdebug.mode=off ../support/console.php lint:twig templates
php -d xdebug.mode=off ../support/console.php asset-map:compile
php -d xdebug.mode=off ../support/console.php debug:asset-map --full --no-ansi
php -d xdebug.mode=off ../support/render.php
php -d xdebug.mode=off vendor/phpunit/phpunit/phpunit --no-configuration --bootstrap tests/Prestations/bootstrap.php --do-not-cache-result tests/Analytics/AnalyticsTest.php
python ../support/render_check.py
~~~

Ces supports se rapportent à la préparation locale avec candidate/ et ses dépendances ; l'archive n'embarque pas l'environnement de travail entier. Le vérificateur de paquet, lui, fonctionne directement après extraction de l'archive.

Le contrôle AJAX a d'abord rencontré une assertion incorrecte du support : DomCrawler ajoute html/body aux fragments. L'assertion a été corrigée pour examiner la réponse brute, puis le rendu ciblé a réussi ; **aucune source applicative changée** pour ce contrôle. Une dépréciation préexistante de SendMailService ($from facultatif avant $context requis) reste signalée, comme dans la préparation principale ; aucun courriel envoyé ni correction métier.

Le navigateur ne teste que les pages synthétiques désactivées : FullCalendar distant bloqué, import Maps remplacé par un substitut local qui interdit son utilisation. Les liens externes sont comparés au texte approuvé sans les ouvrir. Les nouvelles vérifications portent sur la fusion CSS, les templates/configuration et le graphe construit ; aucune modification de logique Analytics ne justifiait de rejouer les 27 groupes simulés ou la recette réelle. Les tests PHP ciblés vérifient l'intégration des hooks et frontières dans la base France, pas une nouvelle preuve de réception GA4.

## Intégration ultérieure — non exécutée, autorisation distincte requise

1. Identifier la **vraie version déployée** et son processus de publication. Conserver une sauvegarde cohérente des sources, templates, importmap, configuration et assets précédents. Le présent worktree ne suffit pas à certifier cette version. Comparer cette base à la cible connue avant toute copie.
2. Contrôler le SHA-256 du ZIP, extraire hors racine web puis exécuter le vérificateur. L'option target compare les sources applicatives pertinentes au socle France connu, y compris l'absence attendue des nouveaux fichiers. Elle ne lit pas les environnements privés, ne déploie rien et refuse les divergences :

~~~powershell
Get-FileHash ./20261029-france.zip -Algorithm SHA256
# Dans le répertoire extrait :
python support/verify_package.py
python support/verify_package.py --target C:/chemin/vers/la/version-source-a-integrer
~~~

3. En cas de divergence, conserver les changements de la cible et adapter dans une nouvelle préparation, avec nouveau manifeste si les octets changent. Si le socle est compatible, intégrer les **18 sources de payload/** dans une copie isolée de la version cible ; ne pas appliquer sources/, evidence/, support/ ou les fichiers du candidat de test au site. Aucun script d'intégration automatique n'est exécuté ici.
4. Conserver les dépendances de composer.lock, inchangé. Préparer vendor selon le processus existant sans importer le vendor de contrôle. Maintenir **GA4_ENABLED=0 dans la configuration persistante et effective**, ID vide ou ID de production déjà autorisé, jamais l'ID de recette. Ne pas copier les environnements privés du poste ni le bootstrap synthétique. Aucun changement métier ni migration requis.
5. Intégrer le **répertoire complet payload/public/assets/** dans la nouvelle version avec ses cinq métadonnées. Conserver les assets précédents encore nécessaires aux documents ouverts ; ne pas supprimer récursivement le public existant. Les sources France, styles et images de ce build doivent rester associés à ces assets. Une reconstruction différente impose un nouveau manifeste.
6. Dans cette version préparée et désactivée, vérifier Twig et renouveler le cache de production selon l'hébergement. Exemples de commandes habituelles (non exécutées sur la cible) :

~~~powershell
$env:GA4_ENABLED = '0'
php bin/console lint:twig templates
php bin/console cache:clear --env=prod --no-debug --no-warmup
php bin/console cache:warmup --env=prod --no-debug
~~~

La variable PowerShell n'est pas une configuration persistante. Ne jamais livrer les caches synthétiques. Après autorisation de publication, basculer vers la version complète préparée selon le procédé de l'hébergeur et recharger PHP. Vérifier les mentions, liens, contenus France et l'absence d'Analytics sur un nouveau document désactivé. Aucune publication n'est effectuée ici.

**Activation distincte :** après décision explicite seulement, destination de production G-MWZX1GYXZE ; ne jamais reprendre G-7ZGZR4L2F8. Les réglages Analytics déjà confirmés et le critère de retrait accepté ne sont pas rouverts. Ce document n'autorise pas GA4_ENABLED=1 ni de nouvel envoi.

## Retour arrière et points ouverts

Maintenir/remettre GA4_ENABLED=0 dans l'environnement effectif, rétablir la version applicative/configuration/AssetMapper précédente sauvegardée, renouveler ses caches et recharger les processus PHP. Privilégier la bascule de version prévue par l'hébergement ; ne pas restaurer seulement le CSS ou le template en laissant un importmap incompatible. Les nouveaux fichiers Analytics peuvent rester dans la version retirée ; aucune suppression récursive, restauration de base ou migration nécessaire. La désactivation serveur concerne les nouveaux documents recevant sa configuration, pas un ordre instantané à tous les onglets ouverts.

**Restent avant publication :** identifier et comparer le socle réellement déployé, préparer la bascule/retour arrière chez l'hébergeur, puis obtenir l'autorisation de publication. Aucun arbitrage métier ni reprise des mentions n'est nécessaire pour cette cible locale. La rubrique administrative **« Personnalité juridique » du DPA reste ouverte**, à traiter par France / l'administrateur Analytics ; elle n'a pas bloqué cette préparation locale et n'est pas déclarée renseignée. L'activation demeure une décision séparée. Les anciennes absences de réception automatisée restent inexpliquées, sans invalider la réception manuelle après correctif ni attribuer une responsabilité à headless.
