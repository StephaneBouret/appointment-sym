-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 19 fév. 2026 à 10:24
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS=0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `appointment_sym`
--

--
-- Déchargement des données de la table `about`
--

INSERT INTO `about` (`id`, `firstname`, `lastname`, `description`, `image_name`, `updated_at`) VALUES
(1, 'France', 'Tuncq', '<div>Les Nombres, dans leur dimension symbolique : une passion profonde et transformatrice dans ma vie.</div><div>Ils ne sont pas que des chiffres : ils sont des symboles, des énergies, des clés pour explorer notre essence, nos aspirations et les dynamiques qui façonnent notre existence.</div><div>Pendant 30 ans, j’ai été informaticienne, occupant divers postes dans plusieurs entreprises, dont certaines de grande envergure spécialisées dans l’imagerie, présentes dans de nombreux pays. J’ai eu la chance de diriger des projets ambitieux avec des budgets de plusieurs millions d’euros.</div><div>&nbsp;</div><div>Le jour de mes 40 ans, à la question : vas-tu mener la 2nde moitié de ta vie comme la 1ère, la réponse a fusé : NON !</div><div>Ce refus a marqué le début d’un voyage intérieur. J’ai entrepris une psychothérapie de 7 ans, exploré des outils de connaissance de soi dont l’ennéagramme. J’ai obtenu une licence en psychologie à distance et me suis formée à l’analyse transactionnelle. Je suis actuellement dans un cursus de formation en coaching. Ces étapes m’ont aidée dans ma recherche de sens, mais il me manquait encore l’outil me permettant de répondre pleinement à mes aspirations.</div><div>&nbsp;</div><div>La découverte des Nombres dans leur dimension symbolique a été cette rencontre transformatrice ! Certifiée en Numérologie Stratégique®, j’ai co-fondé l’ICERNS® (Institut de Conseil, d’Enseignement, de Recherche en Numérologie Stratégique®), une expérience intense et structurante. Aujourd’hui, je poursuis ma propre voie : j’élargis mes sources, en étudiant d’autres courants de la numérologie, en me plongeant dans l’histoire des Nombres, la géométrie et en cherchant à tisser des liens pour comprendre les Nombres dans toutes leurs dimensions. Mon objectif ? Comprendre et partager comment ces outils puissants organisent notre réalité et nous guident vers l’épanouissement. Les Nombres sont plus que des supports mathématiques : ils sont des alliés pour révéler qui nous sommes et nous aider à manifester une existence alignée sur nos aspirations les plus profondes.</div><div>&nbsp;</div><div>Aujourd\'hui...</div><div>Je propose des consultations individuelles, des ateliers de découverte en petits groupes, et de la supervision auprès de coachs utilisant les Nombres dans leur pratique.</div><div>Je continue à approfondir l’étude des Nombres, notamment avec une numérologue qui a 30 ans de pratique et me transmet sa compréhension des nombres, son expertise et la richesse de sa pratique.<br><br></div><div>Mon aspiration ne s’arrête pas là : j’ai de nombreux projets en tête, tous centrés sur les Nombres et leur énergie pour aider à comprendre qui nous sommes, nous relier aux autres et construire un monde où l’humain peut s’épanouir en harmonie avec tous les êtres vivants dans le respect de notre planète et des lois universelles.&nbsp;</div>', 'france-tuncq-pict-697768a6b5bd2967120232.jpg', '2026-01-26 13:14:14');

--
-- Déchargement des données de la table `appointment_type`
--

INSERT INTO `appointment_type` (`id`, `name`, `duration`, `min_age`, `max_age`, `price`, `participants`, `is_pack`, `description`, `introduction`, `slug`, `prerequisite_id`, `image_name`, `updated_at`) VALUES
(1, 'Jeune adulte', 60, 18, 23, 7000, 1, 0, '<div><strong>Bénéfices :</strong></div><ul><li>Éclairer ses ressources et ses potentiels pour informer ses choix de vie,</li><li>Ressentir ses aspirations profondes et prendre conscience des possibles défis à dépasser,</li><li>Prendre des décisions alignées avec votre vraie nature dans votre orientation personnelle ou professionnelle.</li></ul><div><strong>Pré-requis :</strong></div><ul><li>Aucune préparation nécessaire, juste la date de naissance et les prénoms.</li></ul><div><strong>Infos pratiques sur le déroulement :</strong></div><ul><li>Format : Séance en visio</li><li>Déroulement : Analyse des nombres, explication de leur signification et échanges sur leur impact sur la vie quotidienne.</li></ul>', 'Lors de cette séance, nous explorerons les nombres associés à votre identité (prénoms, nom et date de naissance) pour comprendre vos ressources et vos besoins essentiels. Nous mettrons en lumière les aspects clés de votre identité pour vous accompagner dans vos choix de vie, en particulier pour votre professionnelle ou personnelle.', 'jeune-adulte', NULL, 'jeune-adulte-698b03d59dfec249198756.png', '2026-02-10 10:09:25'),
(2, 'Enfant', 60, 0, 17, 7000, 1, 0, '<div><strong>Bénéfices :</strong></div><ul><li>Mieux comprendre la personnalité, les besoins et les potentiels de votre enfant,</li><li>Identifier les leviers pour l’accompagner dans son développement et son épanouissement,</li><li>Explorer des pistes pour prévenir les tensions ou blocages dans son parcours.</li></ul><div><strong>Pré-requis :</strong></div><ul><li>Prénoms, nom de naissance et date de naissance de l\'enfant.</li></ul><div><strong>Infos pratiques sur le déroulement :</strong></div><ul><li>Format : Séance en visio</li><li>Déroulement : Analyse des nombres liés à l’enfant, explication détaillée de leur signification et pistes pratiques pour accompagner ses besoins et favoriser l’expression de ses ressources au quotidien.</li></ul>', 'Cette séance est destinée aux parents qui souhaitent mieux comprendre l\'identité de leur enfant à travers la numérologie. En étudiant les prénoms et la date de naissance de l’enfant, nous identifierons ses ressources, ses talents naturels, et les défis spécifiques à son développement. Cela vous permettra de mieux l’accompagner dans son éducation et ses choix futurs.', 'enfant', NULL, 'enfant-698b04c2413b7974772695.png', '2026-02-10 10:13:22'),
(3, 'Analyse identité Adulte', 75, 24, NULL, 9000, 1, 0, '<div><strong>Bénéfices :</strong></div><ul><li>Explorer ses ressources et son potentiel grâce à son Odyssée des Nombres,</li><li>Éclairer ses aspirations profondes et identifier ses leviers pour atteindre ses objectifs,</li><li>Prendre des décisions alignées avec son aspiration profonde afin de mieux orienter sa vie personnelle et professionnelle.</li></ul><div><strong>Pré-requis :</strong></div><ul><li>Prénoms, nom de naissance, nom du conjoint ou professionnel et date de naissance.</li></ul><div><strong>Infos pratiques sur le déroulement :</strong></div><ul><li>Format : Séance en visio</li><li>Déroulement : Analyse détaillée des nombres, explication des énergies associées et conseils personnalisés pour le développement personnel et professionnel.</li></ul>', 'Dans cette séance, nous décryptons votre Odyssée des Nombres à partir de vos identités de naissance et sociale, afin de mieux comprendre vos ressources, vos besoins, vos motivations et ce qui vous inspire. Vous recevez des clés pour comprendre certaines ambivalences ou tensions, ainsi que des conseils pour développer un chemin de vie plus épanouissant et mieux aligné.', 'analyse-identite-adulte', NULL, 'analyse-identite-adulte-698b0556a0578521962801.png', '2026-02-10 10:15:50'),
(4, 'Analyse des potentiels', 75, 24, NULL, 9000, 1, 0, '<div><strong>Bénéfices :</strong></div><ul><li>Identifiez vos ressources et talents profonds dans des domaines clés de votre vie,</li><li>Comprenez vos forces et comment les exploiter pour avancer vers vos objectifs,</li><li>Découvrez les leviers pour mieux gérer vos relations, votre travail, et votre évolution.</li></ul><div><strong>Pré-requis :</strong></div><ul><li>Aucune préparation nécessaire, juste la date de naissance et les prénoms.</li></ul><div><strong>Infos pratiques sur le déroulement :</strong></div><ul><li>Format : Séance en visio ou en personne.</li><li>Déroulement : Identification de vos talents naturels et des zones de développement, conseils pratiques pour libérer votre potentiel et avancer sereinement.</li></ul>', 'Cette séance est conçue pour explorer vos potentiels à travers la numérologie. Nous analyserons des aspects spécifiques tels que votre autonomie, votre rapport aux autres, votre engagement professionnel et personnel, et d\'autres domaines essentiels. Cette analyse vous aidera à mieux comprendre comment maximiser vos forces et surmonter vos freins.', 'analyse-des-potentiels', 3, 'analyse-potentiel-698b05f2aa99d768242954.png', '2026-02-10 10:18:26'),
(5, 'Analyse des cycles temporels', 75, 24, NULL, 9000, 1, 0, '<div><strong>Bénéfices :</strong></div><ul><li>Comprenez les cycles énergétiques qui influencent votre vie,</li><li>Apprenez à prendre des décisions éclairées en fonction des énergies des cycles,</li><li>Anticipez les moments favorables pour agir et les périodes de révision ou de retrait.</li></ul><div><strong>Pré-requis :</strong></div><ul><li>Prénoms, nom de naissance, nom social et date de naissance.</li><li>L\'<strong>Analyse Identité Adulte</strong> doit être effectuée avant de choisir cette séance.</li></ul><div><strong>Infos pratiques sur le déroulement :</strong></div><ul><li>Format : Séance en visio.</li><li>Déroulement : Décryptage des cycles temporels en cours, explication des énergies associées et recommandations pratiques pour mieux les utiliser dans votre vie.</li></ul>', 'Les cycles temporels sont des périodes spécifiques qui influencent votre vie, votre énergie et vos choix. Dans cette séance, nous étudierons vos cycles personnels pour identifier les périodes où vous êtes le plus à même d’agir, ainsi que celles où vous devez prendre du recul pour mieux vous préparer.', 'analyse-des-cycles-temporels', 3, 'analyse-cycles-temporels-698b06962267c138924976.png', '2026-02-10 10:21:10'),
(6, 'Analyse couple', 120, 18, NULL, 14000, 2, 0, '<div><strong>Bénéfices :</strong></div><ul><li>Découvrez les dynamiques énergétiques qui influencent votre relation de couple,</li><li>Mieux comprendre les forces et les défis de votre union,</li><li>Améliorez la communication et l’harmonie dans votre relation.</li></ul><div><strong>Pré-requis :</strong></div><ul><li>Prénoms, nom de naissance, nom social et date de naissance de chaque partenaire.</li></ul><div><strong>Infos pratiques sur le déroulement :</strong></div><ul><li>Format : Séance en visio</li><li>Déroulement : Analyse des énergies personnelles et relationnelles, discussion sur les dynamiques du couple et des conseils pratiques pour améliorer la relation.</li></ul>', 'Cette séance est idéale pour les couples qui souhaitent explorer la dynamique de leur relation à travers la numérologie. En étudiant les nombres de chaque partenaire et de la relation elle-même, nous pourrons identifier les points forts et les challenges de votre relation et proposer des solutions concrètes pour renforcer l’harmonie et la compréhension mutuelle.', 'analyse-couple', NULL, 'analyse-couple-698b072996ae4104913592.png', '2026-02-10 10:23:37'),
(7, 'Pack Analyse globale', 235, 24, NULL, 25000, 1, 1, '<div><strong>Bénéfices :</strong></div><ul><li>Profitez d’un accompagnement complet pour comprendre vos identités, vos potentiels et les cycles temporels qui influencent votre vie,</li><li>Bénéficiez d’une vision globale et intégrée pour agir en harmonie avec vos énergies et vos objectifs,</li><li>Prenez des décisions éclairées pour avancer sereinement et efficacement dans tous les domaines de votre vie.</li></ul><div><strong>Pré-requis :</strong></div><ul><li>Prénoms, nom de naissance, nom social et date de naissance.</li></ul><div><strong>Infos pratiques sur le déroulement :</strong></div><ul><li>Format : Séance en visio</li><li>Déroulement : 1ère heure : Analyse de l\'identité. 2ème heure : Exploration des potentiels. 3ème heure : Décryptage des cycles temporels. Un accompagnement complet et personnalisé pour vous aider à prendre des décisions alignées avec vos besoins profonds.</li></ul>', 'Ce pack combine trois séances pour vous offrir une analyse complète de vos ressources, de vos cycles temporels et de votre identité profonde. Ce format vous permettra de bénéficier d’un accompagnement holistique et personnalisé sur une période prolongée. C’est l’offre idéale pour ceux qui souhaitent un suivi approfondi et structuré.', 'pack-analyse-globale', NULL, 'pack-analyse-globale-698b079de9e4a186576606.png', '2026-02-10 10:25:33');

--
-- Déchargement des données de la table `avatar`
--

INSERT INTO `avatar` (`id`, `image_name`, `updated_at`, `user_id`) VALUES
(1, '69789d7dce5de.png', '2026-01-27 11:11:57', 1),
(2, '69734f9d1eb9f.png', '2026-01-23 10:38:21', 2),
(3, '69734f9d77adc.png', '2026-01-23 10:38:21', 3),
(4, '69734f9dce65d.png', '2026-01-23 10:38:21', 4),
(5, '69734f9e3357f.png', '2026-01-23 10:38:22', 5),
(7, '697c8c8cbfa25.png', '2026-01-30 10:48:44', 7);

--
-- Déchargement des données de la table `company`
--

INSERT INTO `company` (`id`, `name`, `slug`, `adress`, `postal_code`, `city`, `email`, `phone`, `type`, `siren`, `url`, `manager`) VALUES
(1, 'Potentiel Consulting', 'potentiel-consulting', '1B, La Fendoire', '44770', 'La Plaine sur Mer', 'contact@luniversdesnombres.com', '+33634560579', 'SASU', '911 248 904 00029', 'https://www.luniversdesnombres.com', 'France Tuncq');

--
-- Déchargement des données de la table `contact`
--

INSERT INTO `contact` (`id`, `firstname`, `lastname`, `email`, `phone`, `content`, `send_at`) VALUES
(1, 'Stéphane', 'BOURET', 'admin@gmail.com', '+33752424235', 'test test test', '2026-01-26 14:21:10'),
(2, 'Stéphane', 'BOURET', 'assodcod@gmail.com', '+33752424235', 'e(dtrfyguhijo', '2026-02-17 14:13:12');

--
-- Déchargement des données de la table `link`
--

INSERT INTO `link` (`id`, `url`, `type`, `image_name`, `updated_at`, `about_id`) VALUES
(1, 'https://www.linkedin.com/in/france-tuncq-%F0%9F%A7%AD-01a2b47/?locale=fr_FR', 'Linkedin', 'linkedin-69776a8dcfa0e941073749.svg', '2026-01-26 13:22:21', 1);

--
-- Déchargement des données de la table `schedule_setting`
--

INSERT INTO `schedule_setting` (`id`, `setting_key`, `value`) VALUES
(1, 'morning_start', '09:00'),
(2, 'morning_end', '12:00'),
(3, 'afternoon_start', '14:00'),
(4, 'afternoon_end', '18:00'),
(5, 'open_days', '1,2,3,4,5'),
(6, 'slot_buffer_minutes', '15'),
(7, 'fixed_slots', '1'),
(8, 'opening_delay_hours', '48'),
(9, 'reschedule_min_notice_hours', '24');

--
-- Déchargement des données de la table `setting`
--

INSERT INTO `setting` (`id`, `setting_key`, `value`) VALUES
(1, 'maintenance', 0);

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `email`, `roles`, `password`, `firstname`, `lastname`, `adress`, `postal_code`, `city`, `phone`, `is_verified`, `reset_token`, `created_token_at`, `auth_code`, `trusted_version`) VALUES
(1, 'admin@gmail.com', '[\"ROLE_ADMIN\"]', '$2y$13$Qpa9Dz6RjlK1hKEg2cNaw.qS1LKAcGRZX6ygFvSZ8XVYGEAYC.MO2', 'Admin', 'Admin', '58, rue Ledoux', '30328', 'Marie-sur-Blanc', '+33605139902', 1, NULL, NULL, '701904', 0),
(2, 'user0@gmail.com', '[]', '$2y$13$r0ggm78W2ACpMpNpkVEV5OWcoV0KLsmhG6LsWtyHBJtFtdEvbtp7q', 'Dominique', 'LEFEBVRE', '35, place Pruvost', '24431', 'Le Roux', '+33699919264', 1, NULL, NULL, '138037', 0),
(3, 'user1@gmail.com', '[]', '$2y$13$y7GURu8pinFD427cFkwesO8sHq1g0JQgfrRcoHWY6HQ2goK1S8h9S', 'Margaret', 'Renaud', '34, boulevard Paul', '45904', 'Foucher', '+33634757236', 1, NULL, NULL, '322337', 0),
(4, 'user2@gmail.com', '[]', '$2y$13$3bz4J53TPGmEWDUGtidP0.rQ4CI7T/U17RX8yzinyFTvBZmE.pJx2', 'René', 'Olivier', '22, impasse Arthur Aubry', '59808', 'Lesage', '+33698120062', 1, NULL, NULL, NULL, 0),
(5, 'user3@gmail.com', '[]', '$2y$13$CEKkGAR/MYyvlnakQKTQguqmuxFufX7XKzP/pvR5CKCb7Hvegqjvq', 'Marine', 'Paris', '68, place de Nicolas', '42574', 'Hamel', '+33637212180', 1, NULL, NULL, NULL, 0),
(7, 'user4@gmail.com', '[]', '$2y$13$bC38akU37OF5w.qwy78kyeUzIdWfbqLiogJJkYw7N/URCjWpZIksy', 'Albert', 'AUBRY', '5 Boulevard Haussmann', '75009', 'Paris', '+33101010101', 1, NULL, NULL, NULL, 0);
COMMIT;
SET FOREIGN_KEY_CHECKS=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
