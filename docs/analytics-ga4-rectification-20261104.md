# GA4 — rectification des coordonnées et clôture administrative, 4 novembre 2026

## Provenance et statut courant

**Confirmations du 30 octobre 2026 rapportées par l’utilisateur, consignées le 4 novembre 2026 (Europe/Paris).** Aucune inspection de l’interface Google, capture administrative ou connexion au serveur n’est réalisée pendant cette intervention.

| Élément | Confirmation rapportée |
| --- | --- |
| Organisation Google Marketing Platform | POTENTIEL CONSULTING |
| Personnalité juridique enregistrée | POTENTIEL CONSULTING |
| Compte Analytics associé | France Tuncq, **412243474** (identifiant de compte, pas de propriété ni de flux) |
| Contact principal | France Tuncq |
| Courriel correct | **contact@luniversdesnombres.com** |
| Adresse de correspondance | **1B La Fendoire, 44770 La Plaine-sur-Mer, France** |
| Siège | **1 La Fendoire, 44770 La Plaine-sur-Mer** ; distinction volontaire conservée |

Le point documentaire **« Personnalité juridique » est clos sur cette confirmation utilisateur**. Les anciens constats de champ vide et d’absence de rattachement sont conservés comme historiques, mais ne décrivent plus le statut courant. L’association du **compte** à une **organisation Marketing Platform** est distincte des **associations de produits de la propriété Analytics** : les confirmations antérieures de liens de produits vides ne sont ni annulées ni remplacées par cette information.

Cette clôture ne vaut ni certification juridique globale, ni nouvelle vérification des transferts, ni validation des rapports standards. Les quatre types d’événements reçus en production dans Temps réel restent acquis selon les preuves rapportées le 30 octobre ; les **rapports standards de production restent non contrôlés** sur les éléments disponibles. Le critère de retrait accepté et ses limites techniques sont inchangés.

Production rapportée active : **G-MWZX1GYXZE**, propriété **559257505**, configuration persistante **GA4_ENABLED=1**. Aucun changement de cette configuration n’est exécuté ni prescrit par le correctif. La destination de recette reste distincte. Les exemples et défauts locaux conservent **GA4_ENABLED=0**.

## Correction publique et texte rectifié

Dans les deux worktrees, seul changement applicatif : les **deux occurrences du courriel**, texte visible et lien `mailto:`, dans `templates/legal/privacy.html.twig`, section 11. Aucun changement du contenu métier, des modules Analytics, des règles de consentement, des assets ou des autres mentions.

Le paragraphe courant, rectifiant le courriel du texte approuvé par délégation le 29 octobre, est :

> **Contact et droits.** Pour toute question sur cette mesure ou pour exercer vos droits d’accès, de rectification, d’effacement, de limitation et de portabilité dans les conditions applicables, contactez **France Tuncq pour POTENTIEL CONSULTING**, à [contact@luniversdesnombres.com](mailto:contact@luniversdesnombres.com), ou par courrier à **POTENTIEL CONSULTING — à l’attention de France Tuncq, 1B La Fendoire, 44770 La Plaine-sur-Mer**. Cette adresse de correspondance est distincte du siège. Vous pouvez également adresser une réclamation à la [CNIL](https://www.cnil.fr/fr/plaintes). Pour en savoir plus sur le traitement par Google : [informations relatives aux sites utilisant ses services](https://policies.google.com/technologies/partner-sites?hl=fr).

La formulation « France Tuncq et le prestataire chargé du développement et de la maintenance du site » reste identique. Les notes administratives de ce dossier ne sont pas ajoutées aux mentions visiteurs.

L’ancien courriel reste uniquement dans le paragraphe historique approuvé et dans le relevé administratif historique du dossier de contrôle, chacun précédé d’une rectification explicite. Les copies anciennes dans les archives, preuves et manifestes ne sont pas corrigées rétroactivement. Il faut utiliser le template courant ou le paragraphe ci-dessus pour toute nouvelle publication, et non recopier ces anciennes pièces.

## Rattachement local et contrôles ciblés

| Worktree | Branche et HEAD avant correction |
| --- | --- |
| Principal | main — c4ea32bc10d15c9aede05a700f8bc3cfe0f610c8 |
| Livraison France | livraison/ga4-france-20261029 — d78b3a6b06352414df0162be8165738a8c1a1d0c |

Index initialement vides. Avatar préexistant `public/images/avatars/6ad0a39fe7f71.png` non suivi dans le principal, préservé. Lecture seule GitHub le 4 novembre : `origin/main` correspond au HEAD principal ; `france/main` et `france/livraison/ga4-france-20261029` correspondent au HEAD de livraison. Ce relevé distant ne dit pas quel HEAD est effectivement déployé aujourd’hui ; le dernier déploiement rapporté reste **29bcd686bc1dc5e7dfdccbfb937cead80c19c6bc**. Les références devront être relues avant publication.

Les deux templates corrigés ont la même empreinte SHA-256 :

`3d8b25bc72ed76e21bdd80f95c747779017305d6cf611ef8ada1dc67fd8f88ac`

Cette empreinte identifie le correctif présent ; elle ne remplace aucun manifeste historique. Les anciennes archives ne contiennent pas ce correctif et ne doivent pas être réinstallées pour le livrer.

Commande locale exécutée :

```powershell
php -d xdebug.mode=off var/analytics-contact-20261104/check.php
```

Compilation Twig et rendu du corps de chacun des deux templates réussis ; un lien `mailto:contact@luniversdesnombres.com` avec texte identique, ancien courriel absent du HTML, adresses du siège et de correspondance présentes. Le contrôle utilise Twig installé, une société synthétique et un layout/footer neutres, sans noyau Symfony, environnement privé, base, navigateur, JavaScript ou réseau. Il vérifie la syntaxe du template et le courriel rendu, **pas un nouveau contrôle visuel de la page complète ni un envoi de courriel**. Aucun lien externe ouvert, aucune balise Google chargée et aucune campagne Analytics rejouée.

Les résultats, HTML synthétiques et empreintes sont conservés localement sous `var/analytics-contact-20261104/` (hors commit/livraison). Le contrôle final vérifie les diffs, les index, les fichiers hors périmètre et les preuves protégées ; `git diff --check` est exécuté dans les deux worktrees. Aucun nouveau commit, push, déploiement ou activation n’est réalisé.

La [procédure du correctif France](analytics-ga4-correctif-contact-publication.md) prépare uniquement une intervention future autorisée : publication des sources, remplacement du template et renouvellement du cache, avec retour arrière ciblé. Aucun nouvel asset à construire ou transférer.
