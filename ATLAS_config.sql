CREATE TABLE `ATLAS_config` (
  `ATLAS_CONF_id` int NOT NULL,
  `ATLAS_CONF_evtCode` int NOT NULL,
  `ATLAS_CONF_evtSubCode` int NOT NULL,
  `ATLAS_CONF_evtCodeAndSubCode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ATLAS_CONF_evtCategory` int NOT NULL,
  `ATLAS_CONF_infoCode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ATLAS_CONF_infoSubCode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ATLAS_CONF_infoCodeAndSubCode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ATLAS_CONF_infoCategory` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `API_ATLAS_config` (`ATLAS_CONF_id`, `ATLAS_CONF_evtCode`, `ATLAS_CONF_evtSubCode`, `ATLAS_CONF_evtCodeAndSubCode`, `ATLAS_CONF_evtCategory`, `ATLAS_CONF_infoCode`, `ATLAS_CONF_infoSubCode`, `ATLAS_CONF_infoCodeAndSubCode`, `ATLAS_CONF_infoCategory`) VALUES
(1, 8, 0, '8.0', 1, 'Connexion réussie', NULL, 'Connexion réussie', 'Systeme'),
(3, 212, 0, '212.0', 1, 'Connexion infructueuse', 'Mot de passe incorrect', 'Connexion infructueuse (Mot de passe incorrect)', 'Systeme'),
(4, 128, 0, '128.0', 3, 'Horaire inactif', NULL, 'Horaire inactif', 'Systeme'),
(5, 48, 0, '48.0', 2, 'Accès autorisé', 'Carte', 'Accès autorisé (Carte)', 'Accès autorisé'),
(6, 49, 14, '49.14', 2, 'Accès refusé', 'Numéro de carte inconnu', 'Accès refusé (Numéro de carte inconnu) (Carte)', 'Accès refusé'),
(7, 49, 11, '49.11', 2, 'Accès refusé', 'En dehors de l\'horaire', 'Accès refusé (En dehors de l\'horaire) (Carte)', 'Accès refusé'),
(8, 49, 2, '49.2', 2, 'Accès refusé', 'Badge désactivé', 'Accès refusé (Badge désactivé) (Carte)', 'Accès refusé'),
(9, 49, 0, '49.0', 2, 'Accès refusé', NULL, 'Accès refusé (général)', 'Accès refusé'),
(10, 50, 0, '50.0', 2, 'Accès refusé', 'Porte verrouillée', 'Accès refusé (Porte verrouillée)', 'Accès refusé'),
(11, 51, 0, '51.0', 2, 'Accès refusé', 'Alarme active', 'Accès refusé (Alarme active)', 'Accès refusé'),
(12, 52, 0, '52.0', 3, 'Alarme activée', NULL, 'Alarme activée', 'Alarme'),
(13, 53, 0, '53.0', 3, 'Alarme désactivée', NULL, 'Alarme désactivée', 'Alarme'),
(14, 54, 0, '54.0', 1, 'Connexion', 'Portail web', 'Connexion (portail web)', 'Système'),
(15, 55, 0, '55.0', 1, 'Déconnexion', 'Portail web', 'Déconnexion (portail web)', 'Système'),
(16, 56, 0, '56.0', 2, 'Tentative d’accès', 'Carte non valide', 'Tentative d’accès (carte non valide)', 'Accès refusé'),
(55, 49, 1, '49.1', 2, 'Accès refusé', 'Carte expirée', 'Accès refusé (Carte expirée)', 'Accès refusé'),
(56, 49, 3, '49.3', 2, 'Accès refusé', 'Tentative d\'usurpation', 'Accès refusé (Tentative usurpation)', 'Accès refusé'),
(57, 49, 12, '49.12', 2, 'Accès refusé', 'Verrouillage manuel', 'Accès refusé (Verrouillage manuel)', 'Accès refusé'),
(58, 49, 13, '49.13', 2, 'Accès refusé', 'Limite d\'entrée dépassée', 'Accès refusé (Limite d\'entrée dépassée)', 'Accès refusé'),
(59, 6, 0, '6.0', 1, 'Connexion', 'Application', 'Connexion via application', 'Système'),
(60, 7, 0, '7.0', 1, 'Déconnexion', NULL, 'Déconnexion', 'Système');

ALTER TABLE `API_ATLAS_config`
  ADD PRIMARY KEY (`ATLAS_CONF_id`),
  ADD UNIQUE KEY `unique_evtCode_evtSubCode` (`ATLAS_CONF_evtCode`,`ATLAS_CONF_evtSubCode`);
