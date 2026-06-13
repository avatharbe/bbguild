<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
 * DO NOT CHANGE
 */
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge(
	$lang, array(
	// General
	'ACTIVE' => 'Actif',
	'ALL' => 'Tous',
	'ARMORY' => 'Armurerie',
	'GAME' => 'Jeu',
	'INACTIVE' => 'Inactif',
	'PLUGIN_DISABLED' => 'Désactivé (plugin non activé)',
	'LINKED_USER' => 'Compte Forum',
	'NO_PLAYER' => 'Joueur introuvable.',
	'ROLE' => 'Rôle',
	'BBGUILDDISABLED' => 'bbGuild est actuellement désactivé.',
	'FOOTERBBGUILD' => 'bbGuild',

	//---- Portal blocks -----
	'PORTAL' => 'Portail',
	'REMEMBERME' => 'Me retenir',
	'INFORUM' => 'Publié dans',
	'BBGUILD' => 'Guilde',
	'NEWS' => 'Informations de la Guilde',
	'COMMENT' => 'Commentaire',
	'LIST_NEWS' => 'Liste des Entrées',
	'NO_NEWS' => 'Pas d\'entrées trouvés.',
	'NEWS_PER_PAGE' => 'Entrées par page',
	'ERROR_INVALID_NEWS_PROVIDED' => 'Cette information n\'a pas été trouvé',
	'BOSSPROGRESS' => 'Avancée des Boss',
	'WELCOME' => 'Bienvenu',
	'RECENT_LENGTH' => 'n° de caractères affichés',
	'NUMTOPICS' => 'n° de sujets affichés',
	'SHOW_RT_BLOCK' => 'Montrer Sujets récents sur Portail',
	'RECENT_TOPICS_SETTING' => 'Paramètres Sujets récents',
	'RECENT_TOPICS' => 'Sujets récents',
	'NO_RECENT_TOPICS' => 'Pas de Sujets récents',
	'POSTED_BY_ON' => 'par %1$s le %2$s',
	'LATESTPLAYERS' => 'Derniers membres',

	// Main Menu
	'MENU' => 'Navigation',
	'MENU_WELCOME' => 'Bienvenu',
	'MENU_ROSTER' => 'Armurerie',
	'MENU_NEWS' => 'Informations',
	'MENU_RAIDS' => 'Raids',
	'MENU_STATS' => 'Statistiques',
	'MENU_PLAYER' => 'Membre',
	'MENU_ACHIEVEMENTS' => 'Hauts faits',

	// Games
	'WOW'        => 'World of Warcraft',
	'EQ'         => 'EverQuest',
	'EQ2'        => 'EverQuest II',
	'FFXI'       => 'Final Fantasy XI',
	'LINEAGE2'   => 'Lineage 2',
	'LOTRO'      => 'Lord of the Rings Online',
	'SWTOR'      => 'Starwars : The old Republic',
	'FFXIV'      => 'Final Fantasy XIV',
	'PREINSTALLED' => 'Jeux préconfigurés: %s',

	// Recruitment
	'RECRUITMENT_BLOCK' => 'Statut de Recruitment',
	'RECRUIT_CLOSED' => 'Fermé !',
	'RECRUIT_OPEN' => 'Ouvert',
	'TANK' => 'Tank',
	'DPS' => 'Dps',
	'HEAL' => 'Heal',
	'HEALER' => 'Healer',
	'RECRUIT_MESSAGE' => 'Nous sommes actuellement à la recherche des classes suivantes:',

	// Roster
	'GUILDROSTER' => 'Armurerie',
	'RANK'   => 'Grade',
	'CLASS'   => 'Classe',
	'LVL'   => 'Niveau',
	'REALM'  => 'Realm',
	'REGION'  => 'Region',
	'ACHIEV'  => 'Hauts faits',
	'PROFFESSION' => 'Professions',

	// Player list
	'FILTER' => 'Filtre',
	'LASTRAID' => 'Dernier Raid',
	'LEVEL' => 'Niveau',
	'LISTPLAYERS_TITLE' => 'Tribune des DKP',
	'MNOTFOUND' => 'Membre introuvable',
	'RNOTFOUND' => 'Raid non trouvé',
	'EMPTYRAIDNAME' => 'Nom de Raid vide',
	'NAME' => 'Nom',
	'SURNAME' => 'Nom/Titre',
	'LISTPLAYERS_FOOTCOUNT' => '... trouvé %d membres',
	'LOGIN_TITLE' => 'Connexion',
	'NOUCPACCESS' => 'Vous ne pouvez pas ajouter des Caractères à votre compte.',
	'NOUCPADDCHARS' => 'Vous ne pouvez pas ajouter vos caractères',
	'NOUCPUPDCHARS' => 'Vous ne pouvez pas mettre à jour vos caractères',
	'NOUCPDELCHARS' => 'Vous ne pouvez pas supprimer vos caractères',

	// Various
	'ACCOUNT' => 'Compte',
	'ACTION' => 'Action',
	'ACHIEVED' => 'a obtenu le haut fait ',
	'ADD' => 'Ajouter',
	'ADDED_BY' => 'Ajouté par',
	'ADMINISTRATION' => 'Administration',
	'ADMINISTRATIVE_OPTIONS' => 'Options Administratives',
	'ADMIN_INDEX' => 'Index Admin',
	'ATTENDED' => 'Participé',
	'ATTENDEES' => 'Participants',
	'ATTENDANCE' => 'Participation',
	'ATT' => 'Part.',
	'AVERAGE' => 'Moyen',
	'BOSS' => 'Boss',
	'ARMOR' => 'Armure',
	'STATS_SOCIAL' => '< 20% participation',
	'STATS_RAIDER' => '< 50% participation',
	'STATS_CORERAIDER' => '> 70% participation',

	// Armor types
	'CLOTH' => 'Tissu',
	'ROBE' => 'Robes',
	'LEATHER' => 'Cuir',
	'AUGMENTED' => 'Costume augmenté',
	'MAIL' => 'Mailles',
	'HEAVY' => 'Armure Lourde',
	'PLATE' => 'Plate',

	'CLASSID' => 'id Classe',
	'CLASS_FACTOR' => 'Facteur de Classe',
	'CLASSARMOR' => 'Armure de la Classe',
	'CLASSIMAGE' => 'Image',
	'CLASSMIN' => 'Niveau Min',
	'CLASSMAX' => 'Niveau Max',
	'CLASS_DISTRIBUTION' => 'Distribution par Classe',
	'CLASS_SUMMARY' => 'Summaire par Classe: %s à %s',
	'CONFIGURATION' => 'Configuration',
	'DATE' => 'Date',
	'DELETE' => 'Supprimer',
	'DELETE_CONFIRMATION' => 'Confirmation de Suppression',

	'NO_CHARACTERS' => 'Pas de Caractères trouvés pour ajouter à votre compte phpbb.',
	'STATUS' => 'Statut Y/N',
	'CHARACTER' => 'Nom de Caractère',
	'CHARACTER_EXPLAIN' => 'Choisis ton nom de Caractere et confirme.',
	'CHARACTERS_UPDATED' => 'Le nom de Caractère %s est assigné à votre compte.',
	'CLAIM_PLAYER' => 'Revendiquer un personnage',
	'CLAIM' => 'Revendiquer',
	'UNCLAIM' => 'Libérer',
	'CHARACTER_UNCLAIMED' => 'Le personnage %s a été retiré de votre compte.',
	'CHARACTER_UNCLAIM_FAILED' => 'Impossible de libérer ce personnage.',
	'CONFIRM_UNCLAIM_PLAYER' => 'Êtes-vous sûr de vouloir libérer %s ?',
	'MY_CHARACTERS' => 'Mes Personnages',
	'NO_PLAYERS_FOUND' => 'Aucun personnage trouvé.',
	'NO_CHARACTERS_BOUND' => 'pas trouvé de charactères liés à votre compte.',

	'EVENT' => 'Evènement',
	'EVENTNAME' => 'Nom d\'évènement',
	'EVENTS' => 'Evènements',
	'FACTION' => 'Faction',
	'FACTIONID' => 'Faction ID',
	'FIRST' => 'Premier',
	'HIGH' => 'Urgent',
	'JOINDATE' => 'Date Recrutement',
	'LAST' => 'Dernier',
	'LAST_VISIT' => 'Dernière Visite',
	'LAST_UPDATE' => 'Dernière mise à jour',
	'LOG_DATE_TIME' => 'Temps/date de ce Log',
	'LOW' => 'Bas',
	'MANAGE' => 'Gérer',
	'MEDIUM' => 'Moyen',
	'MEMBERS' => 'Membres',
	'PLAYER' => 'Membre',
	'PLAYERS' => 'Membres',
	'NA' => 'P/A',
	'NO_DATA' => 'Pas de données',
	'MAX_CHARS_EXCEEDED' => 'Vous ne pouvez avoir que %s charactères liés à votre compte forum',
	'MISCELLANEOUS' => 'Divers',
	'NEWEST' => 'Dernier Raid',

	'NOTE' => 'Note',
	'OLDEST' => 'plus ancien Raid',
	'OPEN' => 'Ouvert',
	'OPTIONS' => 'Options',
	'OUTDATE' => 'Date de sortie',
	'PERCENT' => 'Pourcent',
	'PERMISSIONS' => 'Permissions',
	'PREFERENCES' => 'Préferences',
	'QUOTE' => 'Quote',
	'RACE' => 'Race',
	'RACEID' => 'Race ID',
	'RAIDSTART' => 'début de Raid',
	'RAIDEND' => 'fin de Raid',
	'RAIDDURATION' => 'Durée',
	'RAID' => 'Raid',
	'RAIDCOUNT' => 'Participation',
	'RAIDS' => 'Raids',
	'RAID_ID' => 'ID Raid',
	'RANK_DISTRIBUTION' => 'Distribution par Grade',
	'REASON' => 'Raison',
	'RESULT' => 'Resultat',

	'SESSION_ID' => 'ID de Session',

	'SUMMARY_DATES' => 'Sommaire du Raid: %s à %s',
	'TIME' => 'Temps',
	'TOTAL' => 'Total',
	'TYPE' => 'Type',
	'UPDATE' => 'M.a.j',
	'UPDATED_BY' => 'Mis à jour par',
	'USER' => 'Utilisateur',
	'USERNAME' => 'Nom d\'utilisateur',
	'VALUE' => 'Valeur',
	'VIEW' => 'Vue',
	'VIEW_ACTION' => 'Vue Action',
	'VIEW_LOGS' => 'Vue des Logs',
	'APPLICANTS' => 'Candidats',
	'POSITIONS' => 'Positions',

	// Form Elements
	'ENDING_DATE' => 'Date de fin',
	'GUILD_TAG' => 'Nom de Guilde',
	'LANGUAGE' => 'Langue',
	'STARTING_DATE' => 'Date de début',
	'TO' => 'vers',
	'ENTER_NEW' => 'Entres nouveau nom',

	// Pagination
	'NEXT_PAGE' => 'Page Suivante',
	'PAGE' => 'Page',
	'PREVIOUS_PAGE' => 'Page Précédente',

	// Permission Messages
	'NOAUTH_DEFAULT_TITLE' => 'Permission Denied',
	'NOAUTH_U_PLAYER_LIST' => 'Vous n\'avez pas la permission de voir la tribune des points DKP',
	'NOAUTH_U_PLAYER_VIEW' => 'Vous n\'avez pas la permission de voir l\'histoire de ce membre.',
	'NOAUTH_U_RAID_LIST' => 'Vous n\'avez pas la permission de voir la liste des Raids.',
	'NOAUTH_U_RAID_VIEW' => 'Vous n\'avez pas la permission de voir ce Raid.',

	// Miscellaneous
	'DEACTIVATED_BY_USR' => 'Désactivé par l\'utilisateur',

	'ADDED' => 'Ajouté',
	'CLOSED' => 'fermé',
	'DELETED' => 'Supprimé',
	'FEMALE' => 'féminin',
	'GENDER' => 'sexe',
	'GUILD' => 'Guilde',
	'LIST' => 'Liste',
	'LIST_PLAYERS' => 'Liste Membres',
	'MALE' => 'mâle',
	'NOT_AVAILABLE' => 'Pas disponible',
	'NORAIDS' => 'No Raids',
	'OR' => 'ou',
	'REQUIRED_FIELD_NOTE' => 'Champs marqué par un * sont requis.',
	'UPDATED' => 'Mis à jour',
	'NOVIEW' => 'Page inconnue: %s',

	//---- About ---
	//tabs
	'ABOUT' => 'Au sujet de',
	'MAINIMG' => 'bbguild.png',
	'IMAGE_ALT' => 'Logo',
	'REPOSITORY_IMAGE' => 'Google.jpg',
	'TCOPYRIGHT' => 'Copyright',
	'TCREDITS' => 'Crédits',
	'TEAM' => 'Equipe de développement',
	'TSPONSORS' => 'Donateurs',
	'TPLUGINS' => 'PlugIns',
	'CREATED' => 'Créé par',
	'DEVELOPEDBY' => 'Développé par',
	'DEVTEAM' => 'Equipe de développement bbGuild',
	'AUTHNAME' => 'Ippeh',
	'WEBNAME' => 'Site Web',
	'SVNNAME' => 'Repositoire',
	'SVNURL' => 'https://github.com/avatharbe/bbguild',
	'WEBURL' => 'http://www.avathar.be/bbdkp',
	'AUTHWEB' => 'http://www.avathar.be/bbdkp/',
	'LICENSE1' => 'bbGuild est un logiciel distribué sous la licence GPL v2. Vous pouvez copier et distribuer des copies conformes du code source du Programme, tel que
Vous l\'avez reçu, sur n\'importe quel support, à condition de placer sur chaque copie un copyright approprié et une restriction de garantie, de ne pas modifier
ou omettre toutes les stipulations se référant à la présente Licence et à la limitation de garantie, et de fournir avec toute copie de bbGuild un exemplaire de la Licence.
Parce que l\'utilisation de bbGuild est libre et gratuite, aucune garantie n\'est fournie, comme le permet la loi. Sauf mention écrite, les détenteurs du copyright et/ou
les tiers fournissent le Programme en l\'état, sans aucune sorte de garantie explicite ou implicite, y compris les garanties de commercialisation ou d\'adaptation
dans un but particulier. Vous assumez tous les risques quant à la qualité et aux effets du Programme. Si le Programme est défectueux, Vous assumez le coût
de tous les services, corrections ou réparations nécessaires. <br />
Vous devez avoir reçu une copie de la Licence Publique Générale GNU en même temps que ce programme ; si ce n\'est pas le cas, écrivez à la Free Software Foundation,
Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, États-Unis. http://www.gnu.org/licenses',
	'LICENSE2' => 'Powered by bbDKP (c) 2009 The bbDKP Project Team. Si vous utilisez ce logiciel et vous trouvez qu\'il vous plait,  nous vous demandons que vous retenez la marque de copyright au dessous.
Même si elle n\'est pas requise pour l\'usage gratuit, elle aide à soutenir l\'intérêt dans le projet bbDKP et est <strong>requis pour obtenir du support</strong>.',
	'COPYRIGHT3' => 'bbDKP (c) 2010 Sajaki, Malfate, Blazeflack <br /> bbDKP (c) 2008, 2009 Sajaki, Malfate, Kapli, Hroar',
	'COPYRIGHT2' => 'bbDKP (c) 2007 Ippeh, Teksonic, Monkeytech, DWKN',
	'COPYRIGHT1' => 'EQDkp (c) 2003 The EqDkp Project Team ',

	'PRODNAME' => 'Produit',
	'VERSION' => 'Version',
	'DEVELOPER' => 'Developpeur',
	'JOB' => 'Job',
	'DEVLINK' => 'Link',
	'PROD' => 'bbGuild',
	'DEVELOPERS' => '<a href=mailto:sajaki@avathar.be>Sajaki</a>',
	'PHPBB' => 'phpBB',
	'PHPBBGR' => 'phpBB Group',
	'PHPBBLINK' => 'http://www.phpbb.com',
	'EQDKP' => 'Original EQDKP',
	'EQDKPVERS' => '1.3.2',
	'EQDKPDEV' => 'Tsigo',
	'EQDKPLINK' => 'http://www.eqdkp.com/',

	'PLUGINS' => 'Plugins',
	'PLUGINVERS' => 'Version',
	'AUTHOR' => 'Author',
	'MAINT' => 'Maintainer',
	'DONATION' => 'Donation',
	'DONA_NAME' => 'Name',
	'ADDITIONS' => 'Code Additions',
	'CONTRIB' => 'Contributions',

	// is_enableable() error messages
	'BBGUILD_PHP_VERSION_FAIL'		=> 'Cette extension nécessite PHP %1$s ou supérieur. Vous utilisez PHP %2$s.',
	'BBGUILD_PHPBB_VERSION_FAIL'	=> 'Cette extension nécessite phpBB %1$s ou supérieur. Vous utilisez phpBB %2$s.',
	'BBGUILD_REQUIRES_GD'			=> 'Cette extension nécessite l\'extension PHP GD.',
	'BBGUILD_REQUIRES_CURL'			=> 'Cette extension nécessite l\'extension PHP cURL.',

	)
);
