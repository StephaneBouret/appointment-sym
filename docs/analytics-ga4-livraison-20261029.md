# GA4 — intégration locale et livrable du 29 octobre 2026

**Préparation locale terminée, collecte désactivée. Aucun déploiement ni activation.** L’utilisateur déclare que France Tuncq lui a délégué la validation et approuve les mentions avec « France Tuncq et le prestataire chargé du développement et de la maintenance du site », les deux harmonisations et cette préparation. Cette approbation rédactionnelle n’est ni une certification juridique ni une autorisation de publication ou d’activation.

La rubrique **« Personnalité juridique » du DPA reste vide / point administratif ouvert**, distinct de la préparation technique. Aucun rattachement Marketing Platform ajouté. Réception acquise, réserve sur le parcours manuel libre et critère de retrait accepté inchangés. Aucune campagne Analytics rejouée.

## Intégration et portée

Seul fichier applicatif modifié aujourd’hui : **templates/legal/privacy.html.twig**. Présentation et wrappers existants conservés.

- Section 11 : les **12 paragraphes approuvés**, avec siège au 1 et correspondance au 1B, six mois du choix/cookies et deux mois du réglage Google. Aucune note interne publiée.
- Section 9 : remplacement du paragraphe contradictoire sur les transmissions aux tiers, y compris sa restriction au seul rachat, par le renvoi approuvé vers la section 11, avec ancre locale.
- Section 8 : remplacement de la copie de pièce d’identité systématique par la vérification complémentaire en cas de doute raisonnable.

La comparaison avec le template copié avant édition démontre **trois zones de changement** seulement. Les autres modifications préexistantes, l’index et le worktree France sont préservés. Les 44 autres sources du manifeste historique sont identiques ; seul le template de confidentialité diffère parmi ses 45 sources. Les sept rapports historiques et le manifeste daté restent inchangés.

## Livrable identifié

Répertoire : **var/analytics-delivery/20261029-mentions/**.

| Répertoire | Contenu |
| --- | --- |
| payload/ | 18 fichiers source du lot GA4 modifiés/non suivis par rapport au socle, plus 82 assets et cinq métadonnées AssetMapper : **105 fichiers**. Ce ne sont pas 18 fichiers modifiés aujourd’hui |
| sources/ | 128 fichiers d’entrée identifiés : candidat actualisé, assets locaux et des dépendances, métadonnées Composer installées. Copie pour revue, pas pour la racine web |
| support/ | Lanceurs isolés de construction et de contrôle, hors application publiée |
| evidence/ | HTML synthétique, quatre captures, résultats, texte approuvé, comparaison ciblée du template et diff du candidat contre HEAD |
| documentation/ | Documents GA4 et nouveau manifeste |

Archive : **var/analytics-delivery/20261029-mentions.zip**, empreinte dans le fichier **.zip.sha256** voisin.

Nouveau manifeste : [analytics-ga4-livrable-20261029.json](analytics-ga4-livrable-20261029.json). Il contient tailles et SHA-256 des sources, fichiers livrés, supports/preuves et documents. Socle : branche main, HEAD **2653315d0f9ee2594079f8bfb34b357459aca955**. Il référence le [manifeste historique](analytics-ga4-candidat-20261021.json) sans le remplacer, ainsi que le texte approuvé et les écarts connus.

Il s’agit d’un **lot à intégrer au dépôt principal**, pas d’une application autonome avec ses dépendances et sa configuration de déploiement. Aucun environnement privé, profil Chrome, cache Symfony, base de données, avatar non suivi préexistant ou secret de production n’est livré. Les métadonnées Composer ne remplacent pas les dépendances d’exécution. Les empreintes ne certifient pas rétroactivement les sources des recettes anciennes.

## Construction et contrôles exécutés

Le lanceur console réutilise **asset-map:compile** et le graphe AssetMapper du dépôt, avec le noyau applicatif en **prod, debug désactivé**. Caches et sorties sont redirigés vers le répertoire isolé ; aucun public/assets habituel écrasé. Le bootstrap synthétique évite Dotenv et les environnements privés : SQLite en mémoire, mailer nul, Stripe bloqué, client HTTP simulé, GA4_ENABLED=0 et identifiant vide dans les processus de préparation.

Commandes exécutées depuis le dépôt :

~~~powershell
php -d xdebug.mode=off var/analytics-delivery/20261029-mentions/support/console.php lint:twig templates/legal/privacy.html.twig
php -d xdebug.mode=off var/analytics-delivery/20261029-mentions/support/console.php asset-map:compile
php -d xdebug.mode=off var/analytics-delivery/20261029-mentions/support/console.php debug:asset-map --full --no-ansi
php -d xdebug.mode=off var/analytics-delivery/20261029-mentions/support/render.php
python var/analytics-delivery/20261029-mentions/support/render_check.py
git diff --check
~~~

| Contrôle | Résultat |
| --- | --- |
| Twig | Un fichier valide, code 0 |
| AssetMapper | 82 assets, entrées analytics, analytics-frame et app présentes, code 0 |
| Modules Analytics construits | Octets identiques aux quatre modules sources |
| Rendu Symfony | HTTP 200 avec données synthétiques en mémoire |
| Texte | 12 paragraphes identiques au texte approuvé après normalisation des espaces/balisage |
| Liens | Sept destinations conformes, HTTPS ou mailto ; ancre section 9 → 11 présente. Aucun lien externe ouvert, disponibilité distante non testée |
| Harmonisations et notes | Deux harmonisations présentes ; copie systématique et notes internes absentes |
| Ordinateur/mobile | 1365 × 900 et 390 × 844, clair/sombre, aucun débordement horizontal. Captures ordinateur clair et mobile sombre inspectées visuellement |
| Analytics/réseau | Zéro tentative de balise ou collecte Analytics, zéro cadre/cookie Analytics, aucune connexion externe autorisée |
| JavaScript | Aucune exception applicative observée sur cette page |
| Arrêt | Chrome headless isolé et serveur statique fermés ; navigateur habituel non touché |

Une seule page de confidentialité est ouverte, sans consentement ni parcours métier. Les ressources distantes FullCalendar sont bloquées ; le module Maps commun reçoit un substitut local refusant son utilisation. Ces services ne sont pas validés. Le premier cadrage des captures avait rencontré le défilement animé existant : seul le contrôle visuel hors ligne a été repris avec un défilement instantané dans le navigateur de contrôle. Aucun CSS applicatif changé.

**Dépréciation préexistante signalée :** SendMailService::sendMail(), paramètre facultatif $from avant $context requis, src/Service/SendMailService.php:13. Elle n’empêche pas syntaxe, construction ou rendu ; aucun courriel envoyé ni correctif métier ajouté.

Comparaisons des sources/index/rapports et contrôles d’empreintes effectués hors ligne. Aucune réception GA4 retestée, migration, soumission de formulaire, réservation ou paiement.

## Procédure de déploiement — autorisation distincte requise, non exécutée

1. Avant publication, faire consigner l’entité juridique dans le dossier DPA par le moyen applicable au compte ; ce point n’est pas résolu ici. Obtenir la décision de publication du livrable. Ne pas redemander l’approbation rédactionnelle déjà acquise.
2. Vérifier l’empreinte de l’archive, extraire dans une préparation neuve et vérifier les fichiers contre le manifeste. Les supports de reproduction supposent leur emplacement d’origine sous var/analytics-delivery/20261029-mentions/ dans le dépôt.
3. Comparer le socle et les fichiers de la cible. **Ne pas copier aveuglément le lot principal dans le déploiement France** : préserver ses contenus, données société, configuration privée et évolutions. Utiliser le diff contre HEAD et les sources. Le worktree France n’a pas été consulté/modifié dans cette préparation. Si l’adaptation change les sources/assets, construire le candidat adapté et produire un nouveau manifeste ; les empreintes présentes ne le couvrent pas.
4. Préparer une version de déploiement séparée avec les dépendances correspondant à composer.lock. Maintenir **GA4_ENABLED=0 dans la configuration effective**, même si un ID France valide existe déjà. Aucun environnement privé du poste local, support de recette, profil, cache ou preuve dans la racine publiée.
5. Pour un socle compatible inchangé, intégrer les fichiers applicatifs/configuration examinés de payload/ et ses assets dans la nouvelle version. Fusionner la configuration en préservant les particularités de la cible. Inclure les métadonnées AssetMapper ; conserver les anciens assets nécessaires aux pages ouvertes selon la procédure de versions de l’hébergeur. Ne pas supprimer récursivement le public existant.
6. Sur la version préparée, avec son environnement normal désactivé, vérifier Twig et préparer les caches, puis basculer/recharger PHP selon la procédure de l’hébergement :

~~~powershell
# Exemple PowerShell, uniquement dans la version de déploiement autorisée.
$env:GA4_ENABLED = '0'
$env:APP_ENV = 'prod'
$env:APP_DEBUG = '0'
php bin/console lint:twig templates/legal/privacy.html.twig
php bin/console cache:clear --env=prod --no-debug --no-warmup
php bin/console cache:warmup --env=prod --no-debug
~~~

Ces variables ne modifient que le processus : **la configuration persistante doit elle aussi rester à 0**. Ne pas réutiliser les caches synthétiques. Pour reconstruire après une adaptation autorisée, la commande normale reste **php bin/console asset-map:compile --env=prod --no-debug**, dans la version isolée, suivie d’un nouveau manifeste.

7. Vérifier la politique publiée, les harmonisations et l’absence d’Analytics tant que l’interrupteur reste désactivé. Ne pas rejouer une campagne de réception pour ce seul changement rédactionnel et ne déclencher aucune opération métier.
8. **Activation ultérieure distincte, non autorisée ici :** seulement après décision explicite, appliquer GA4_MEASUREMENT_ID=G-MWZX1GYXZE et GA4_ENABLED=1, conserver les réglages GA4 acquis et préparer une observation limitée séparément autorisée. Ne jamais utiliser les harnais de recette avec l’ID de production.

**Désactivation/retour arrière :** conserver ou remettre GA4_ENABLED=0 dans la configuration effective, renouveler caches/processus et, si nécessaire, revenir à la version applicative/assets précédente selon la procédure de l’hébergeur. Aucun retour arrière de base n’est nécessaire. La désactivation serveur prend effet quand les pages reçoivent la nouvelle configuration ; elle n’est pas un ordre instantané aux onglets déjà ouverts. Les limites du retrait individuel restent celles déjà acceptées.
