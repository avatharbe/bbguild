<?php
/**
 * @package bbGuild Portal
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * Portal language keys (French)
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'ACP_BBGUILD_PORTAL'             => 'Portail',

	// Module names
	'BBGUILD_PORTAL_MOTD'            => 'Message du jour',
	'BBGUILD_PORTAL_RECRUITMENT'     => 'Recrutement',
	'BBGUILD_PORTAL_ROSTER'          => 'Liste des membres',
	'BBGUILD_PORTAL_STATISTICS'      => 'Statistiques de la guilde',

	// Module content
	'NO_RECRUITS'                    => 'Aucune position de recrutement ouverte.',
	'BBGUILD_NO_PORTAL_MODULES'      => 'Aucun module de portail configuré pour cette guilde.',

	// Statistics module
	'STAT_CLASS_DISTRIBUTION'        => 'Classes',
	'STAT_RACE_DISTRIBUTION'         => 'Races',
	'STAT_LEVEL_DISTRIBUTION'        => 'Niveaux',
	'STAT_RANK_DISTRIBUTION'         => 'Rangs',
	'STAT_TOTAL_MEMBERS'             => 'Membres actifs totaux : %d',
	'STAT_RECENT_JOINS'              => 'Arrivées récentes',
	'STAT_RECENT_DEPARTURES'         => 'Départs récents',
	'STAT_NO_DATA'                   => 'Aucune donnée pour le moment.',

	// ACP portal management
	'ACP_PORTAL_EXPLAIN'             => 'Gérer les modules du portail pour chaque guilde. Ajouter, supprimer, réorganiser et activer/désactiver les blocs.',
	'ACP_PORTAL_SELECT_GUILD'        => 'Sélectionner la guilde',
	'ACP_PORTAL_MODULES'             => 'Modules du portail',
	'ACP_PORTAL_MODULE_NAME'         => 'Module',
	'ACP_PORTAL_COLUMN'              => 'Colonne',
	'ACP_PORTAL_ORDER'               => 'Ordre',
	'ACP_PORTAL_COLUMN_TOP'          => 'Haut',
	'ACP_PORTAL_COLUMN_CENTER'       => 'Centre',
	'ACP_PORTAL_COLUMN_RIGHT'        => 'Droite',
	'ACP_PORTAL_ADD_MODULE'          => 'Ajouter un module',
	'ACP_PORTAL_NO_MODULES'          => 'Aucun module de portail configuré pour cette guilde. Ajoutez-en un ci-dessous.',
	'ACP_PORTAL_MODULE_ADDED'        => 'Le module du portail a été ajouté.',
	'ACP_PORTAL_MODULE_ADD_FAILED'   => 'Impossible d\'ajouter le module. Le module n\'est peut-être pas autorisé dans la colonne sélectionnée.',
	'ACP_PORTAL_MODULE_DELETED'      => 'Le module du portail a été supprimé.',
	'ACP_PORTAL_MOVE_LEFT'           => 'Déplacer vers la colonne précédente',
	'ACP_PORTAL_MOVE_RIGHT'          => 'Déplacer vers la colonne suivante',
	'ACP_PORTAL_COLUMN_BOTTOM'       => 'Bas',

	'ACP_PORTAL_TABS'                => 'Onglets',
	'ACP_PORTAL_TAB'                 => 'Onglet',
	'ACP_PORTAL_TAB_NAME'            => 'Nom de l\'onglet',
	'ACP_PORTAL_TAB_SLUG'            => 'Slug',
	'ACP_PORTAL_TAB_SLUG_EXPLAIN'    => 'Utilisé dans l\'URL de la page, par ex. <samp>raids</samp>. Lettres, chiffres, tirets et underscores uniquement ; ne peut pas commencer par un chiffre.',
	'ACP_PORTAL_ADD_TAB'             => 'Ajouter un onglet',
	'ACP_PORTAL_TAB_ADDED'           => 'L\'onglet a été ajouté.',
	'ACP_PORTAL_TAB_ADD_FAILED'      => 'Impossible d\'ajouter l\'onglet. Un onglet avec ce slug existe déjà pour cette guilde.',
	'ACP_PORTAL_TAB_UPDATED'         => 'L\'onglet a été mis à jour.',
	'ACP_PORTAL_TAB_UPDATE_FAILED'   => 'Impossible de mettre à jour l\'onglet. Un onglet avec ce slug existe déjà pour cette guilde.',
	'ACP_PORTAL_TAB_DELETED'         => 'L\'onglet a été supprimé.',
	'ACP_PORTAL_TAB_DELETE_FAILED'   => 'Impossible de supprimer l\'onglet. Une guilde doit toujours avoir au moins un onglet.',

	// Module configuration
	'ACP_PORTAL_MODULE_CONFIG'        => 'Configuration du module',
	'ACP_PORTAL_MODULE_CONFIG_EXPLAIN' => 'Configurez le nom d\'affichage, l\'icône, la visibilité et les paramètres spécifiques de ce bloc portail. Les modifications s\'appliquent à la page d\'accueil de la guilde.',
	'ACP_PORTAL_MODULE_SETTINGS'      => 'Paramètres généraux',
	'ACP_PORTAL_MODULE_SETTINGS_EXPLAIN' => 'Définissez le nom d\'affichage et activez ou désactivez ce module. Les modules désactivés sont masqués du portail mais conservent leur configuration.',
	'ACP_PORTAL_ICON_SETTINGS'        => 'Paramètres de l\'icône',
	'ACP_PORTAL_ICON_SETTINGS_EXPLAIN' => 'Choisissez une icône affichée à côté du titre du module sur le portail. Vous pouvez utiliser un fichier image ou une icône Font Awesome. Si les deux sont définis, l\'icône Font Awesome a la priorité.',
	'ACP_PORTAL_IMAGE_SRC'            => 'Fichier image',
	'ACP_PORTAL_IMAGE_SRC_EXPLAIN'    => 'Chemin relatif vers un fichier image, par ex. <samp>ext/avathar/bbguild/images/icon.png</samp>.',
	'ACP_PORTAL_FA_ICON'              => 'Icône Font Awesome',
	'ACP_PORTAL_FA_ICON_EXPLAIN'      => 'Entrez une classe CSS Font Awesome 4.7, par ex. <samp>fa-star</samp>, <samp>fa-shield</samp>, <samp>fa-users</samp>. A la priorité sur le fichier image. Laissez vide pour utiliser l\'image.',
	'ACP_PORTAL_ICON_SIZE'            => 'Taille de l\'icône',
	'ACP_PORTAL_ICON_SIZE_EXPLAIN'    => 'Taille de l\'icône Font Awesome en pixels (8–64). S\'applique uniquement lorsqu\'une icône Font Awesome est définie.',
	'ACP_PORTAL_GROUP_ACCESS'         => 'Accès par groupe',
	'ACP_PORTAL_GROUP_ACCESS_EXPLAIN' => 'Limitez les groupes d\'utilisateurs pouvant voir ce module sur la page du portail.',
	'ACP_PORTAL_GROUP_IDS'            => 'Groupes autorisés',
	'ACP_PORTAL_GROUP_IDS_EXPLAIN'    => 'Sélectionnez un ou plusieurs groupes. Seuls les membres des groupes sélectionnés verront ce module. Laissez vide pour afficher le module à tous les utilisateurs.',
	'ACP_PORTAL_MODULE_SPECIFIC'      => 'Paramètres du module',
	'ACP_PORTAL_MODULE_SPECIFIC_EXPLAIN' => 'Paramètres spécifiques à ce type de module. Les options disponibles dépendent du module (par ex. contenu de bloc personnalisé, limites de flux).',
	'ACP_PORTAL_MODULE_UPDATED'       => 'La configuration du module a été sauvegardée.',
]);
