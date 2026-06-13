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
	'ACTIVE' => 'Aktiv',
	'ALL' => 'Alle',
	'ARMORY' => 'Armory',
	'GAME' => 'Spiel',
	'INACTIVE' => 'Inaktiv',
	'PLUGIN_DISABLED' => 'Deaktiviert (Plugin nicht aktiviert)',
	'LINKED_USER' => 'Forum-Konto',
	'NO_PLAYER' => 'Spieler nicht gefunden.',
	'ROLE' => 'Rolle',
	'BBGUILDDISABLED' => 'bbGuild is zurzeit nicht erreichbar.',
	'FOOTERBBGUILD' => 'bbGuild',

	//---- Portal blocks -----
	'PORTAL' => 'Portal',
	'REMEMBERME' => 'Login merken',
	'INFORUM' => 'in',
	'BBGUILD' => 'Gilde',
	'NEWS' => 'Nachrichten',
	'COMMENT' => 'Kommentare',
	'LIST_NEWS' => 'Nachrichtenliste',
	'NO_NEWS' => 'Keine Nachrichten gefunden.',
	'NEWS_PER_PAGE' => 'Nachrichten pro Seite',
	'ERROR_INVALID_NEWS_PROVIDED' => 'Ungültige Nachricht',
	'BOSSPROGRESS' => 'Instanz-Fortschritt',
	'WELCOME' => 'Willkommen',
	'RECENT_LENGTH' => 'Anzahl angezeigte Buchstaben',
	'NUMTOPICS' => 'Anzahl angezeigte Beiträge',
	'SHOW_RT_BLOCK' => 'neue Beiträge anzeigen',
	'RECENT_TOPICS_SETTING' => 'Einstellungen neue Beiträge',
	'RECENT_TOPICS' => 'Neue Beiträge',
	'NO_RECENT_TOPICS' => 'Keine neue Beiträge',
	'POSTED_BY_ON' => '%2$s von %1$s',
	'LATESTPLAYERS' => 'Neueste Mitglieder',

	// Main Menu
	'MENU' => 'Navigation',
	'MENU_WELCOME' => 'Willkommen',
	'MENU_ROSTER' => 'Mitgliedsbuch',
	'MENU_NEWS' => 'Nachrichten',
	'MENU_RAIDS' => 'Raids',
	'MENU_STATS' => 'Statistiken',
	'MENU_PLAYER' => 'Mitglied',
	'MENU_ACHIEVEMENTS' => 'Erfolge',

	// Games
	'WOW'        => 'World of Warcraft',
	'EQ'         => 'EverQuest',
	'EQ2'        => 'EverQuest II',
	'FFXI'       => 'Final Fantasy XI',
	'LINEAGE2'   => 'Lineage 2',
	'LOTRO'      => 'Lord of the Rings Online',
	'SWTOR'      => 'Starwars : The old Republic',
	'FFXIV'      => 'Final Fantasy XIV',
	'PREINSTALLED' => 'Vorinstallierte Spiele: %s',

	// Recruitment
	'RECRUITMENT_BLOCK' => 'Rekrutierungs Status',
	'RECRUIT_CLOSED' => 'Geschlossen!',
	'RECRUIT_OPEN' => 'Offen',
	'TANK' => 'Schutz',
	'DPS' => 'Schaden',
	'HEAL' => 'Heilung',
	'HEALER' => 'Heiler',
	'RECRUIT_MESSAGE' => 'Wir sind zurzeit auf der Suche nach neuen Mitstreitern folgender Klassen und Spezialisierungen:',

	// Roster
	'GUILDROSTER' => 'Mitgliedsliste',
	'RANK'   => 'Rang',
	'CLASS'   => 'Klasse',
	'LVL'   => 'Niveau',
	'REALM'  => 'Realm',
	'REGION'  => 'Region',
	'ACHIEV'  => 'Erfolge',
	'PROFFESSION' => 'Berufe',

	// Player list
	'FILTER' => 'Filter',
	'LASTRAID' => 'Letzter Raid',
	'LEVEL' => 'Level',
	'LISTPLAYERS_TITLE' => 'Mitglieder Statistik',
	'MNOTFOUND' => 'Mitglied nicht gefunden',
	'RNOTFOUND' => 'Raid icht gefunden',
	'EMPTYRAIDNAME' => 'Raidname nicht gefunden',
	'NAME' => 'Name',
	'SURNAME' => 'Nachname/Titel',
	'LISTPLAYERS_FOOTCOUNT' => '... %d Mitglieder gefunden',
	'LOGIN_TITLE' => 'Login',
	'NOUCPACCESS' => 'Du kannst keine Charaktere einstellen.',
	'NOUCPADDCHARS' => 'Du kannst keine Charaktere hinzufügen.',
	'NOUCPUPDCHARS' => 'Du kannst deine Charaktere nicht bearbeiten.',
	'NOUCPDELCHARS' => 'Du kannst deine Charaktere nicht löschen',

	// Various
	'ACCOUNT' => 'Konto',
	'ACTION' => 'Aktion',
	'ACHIEVED' => 'earned the achievement ',
	'ADD' => 'Hinzufügen',
	'ADDED_BY' => 'Hinzugefügt von',
	'ADMINISTRATION' => 'Administration',
	'ADMINISTRATIVE_OPTIONS' => 'Administrative Einstellungen',
	'ADMIN_INDEX' => 'Admin Index',
	'ATTENDED' => 'Teilgenommen',
	'ATTENDEES' => 'Teilnehmer',
	'ATTENDANCE' => 'Teilnahme',
	'ATT' => 'Teiln.',
	'AVERAGE' => 'Durchschnitt',
	'BOSS' => 'Boss',
	'ARMOR' => 'Rüstung',
	'STATS_SOCIAL' => '< 20% Teilnahme',
	'STATS_RAIDER' => '< 50% Teilnahme',
	'STATS_CORERAIDER' => '> 70% Teilnahme',

	// Armor types
	'CLOTH' => 'Stoff',
	'ROBE' => 'Robe',
	'LEATHER' => 'Leder',
	'AUGMENTED' => 'Erweiterter Anzug',
	'MAIL' =>  'Mittel / Kettenhemd',
	'HEAVY' => 'Schwere Rüstung',
	'PLATE' => 'Schwer / Platte',

	'CLASSID' => 'Klassen ID',
	'CLASS_FACTOR' => 'Klassen Faktor',
	'CLASSARMOR' => 'Klasserüstung',
	'CLASSIMAGE' => 'Bild',
	'CLASSMIN' => 'Min Level',
	'CLASSMAX' => 'Max Level',
	'CLASS_DISTRIBUTION' => 'Klassenverteilung',
	'CLASS_SUMMARY' => 'Klassen Übersicht: %s zu %s',
	'CONFIGURATION' => 'Konfiguration',
	'DATE' => 'Datum',
	'DELETE' => 'Löschen',
	'DELETE_CONFIRMATION' => 'Bestätige Löschung',

	'NO_CHARACTERS' => 'Keine Charaktere gefunden.',
	'STATUS' => 'Status Y/N',
	'CHARACTER' => 'Charaktername',
	'CHARACTER_EXPLAIN' => 'Wähle dein Charakternamen and bestätige.',
	'CHARACTERS_UPDATED' => 'Der Charaktername %s wurde an dein Forumkonto gekoppelt. ',
	'CLAIM_PLAYER' => 'Charakter beanspruchen',
	'CLAIM' => 'Beanspruchen',
	'UNCLAIM' => 'Freigeben',
	'CHARACTER_UNCLAIMED' => 'Der Charakter %s wurde von deinem Konto entfernt.',
	'CHARACTER_UNCLAIM_FAILED' => 'Der Charakter konnte nicht freigegeben werden.',
	'CONFIRM_UNCLAIM_PLAYER' => 'Bist du sicher, dass du %s freigeben möchtest?',
	'MY_CHARACTERS' => 'Meine Charaktere',
	'NO_PLAYERS_FOUND' => 'Keine Charaktere gefunden.',
	'NO_CHARACTERS_BOUND' => 'Keine Charaktere an sind an Ihr Account verbunden.',

	'EVENT' => 'Ereignis',
	'EVENTNAME' => 'Ereignisname',
	'EVENTS' => 'Ereignisse',
	'FACTION' => 'Fraktion',
	'FACTIONID' => 'Fraktions ID',
	'FIRST' => 'Erster',
	'HIGH' => 'Hoch',
	'JOINDATE' => 'Gilden Eintrittsdatum',
	'LAST' => 'Letzter',
	'LAST_VISIT' => 'Letzter Besuch',
	'LAST_UPDATE' => 'Letzte Aktualisierung',
	'LOG_DATE_TIME' => 'Datum/Zeit dieses Logs',
	'LOW' => 'Niedrig',
	'MANAGE' => 'Verwalten',
	'MEDIUM' => 'Mittel',
	'MEMBERS' => 'Mitglieder',
	'PLAYER' => 'Mitglied',
	'PLAYERS' => 'Mitglieder',
	'NA' => 'n.v.',
	'NO_DATA' => 'Keine Daten',
	'MAX_CHARS_EXCEEDED' => 'Du kannst höchstens %s Charaktere an dein Forumkonto koppeln.',
	'MISCELLANEOUS' => 'Verschiedenes',
	'NEWEST' => 'Neuester Raid',

	'NOTE' => 'Notiz',
	'OLDEST' => 'Ältester Raid',
	'OPEN' => 'Offen',
	'OPTIONS' => 'Einstellungen',
	'OUTDATE' => 'Gilden Austrittsdatum',
	'PERCENT' => 'Prozent',
	'PERMISSIONS' => 'Berechtigungen',
	'PREFERENCES' => 'Voreinstellungen',
	'QUOTE' => 'Zitat',
	'RACE' => 'Rasse',
	'RACEID' => 'Rassen ID',
	'RAIDSTART' => 'Raidanfang',
	'RAIDEND' => 'Raidende',
	'RAIDDURATION' => 'Dauer',
	'RAID' => 'Raid',
	'RAIDCOUNT' => 'Teilnehmerzahl',
	'RAIDS' => 'Raids',
	'RAID_ID' => 'Raid ID',
	'RANK_DISTRIBUTION' => 'Rang-Verteilung',
	'REASON' => 'Grund',
	'RESULT' => 'Ergebnis',

	'SESSION_ID' => 'Session ID',

	'SUMMARY_DATES' => 'Raid Zusammenfassung: %s bis %s',
	'TIME' => 'Zeit',
	'TOTAL' => 'Gesamt',
	'TYPE' => 'Typ',
	'UPDATE' => 'Aktualisieren',
	'UPDATED_BY' => 'Aktualisiert By',
	'USER' => 'Benutzer',
	'USERNAME' => 'Benutzername',
	'VALUE' => 'Wert',
	'VIEW' => 'Ansehen',
	'VIEW_ACTION' => 'Aktion ansehen',
	'VIEW_LOGS' => 'Logs ansehen',
	'APPLICANTS' => 'Bewerber',
	'POSITIONS' => 'Positionen',

	// Form Elements
	'ENDING_DATE' => 'Enddatum',
	'GUILD_TAG' => 'Gildenname',
	'LANGUAGE' => 'Sprache',
	'STARTING_DATE' => 'Startdatum',
	'TO' => 'Bis',
	'ENTER_NEW' => 'Neu eingeben',

	// Pagination
	'NEXT_PAGE' => 'Nächste Seite',
	'PAGE' => 'Seite',
	'PREVIOUS_PAGE' => 'Vorherige Seite',

	// Permission Messages
	'NOAUTH_DEFAULT_TITLE' => 'Zugriff verweigert',
	'NOAUTH_U_PLAYER_LIST' => 'Du hast keine Berechtigung Mitgliederstände zu sehen.',
	'NOAUTH_U_PLAYER_VIEW' => 'Du hast keine Berechtigung Historien der Mitglieder zu sehen.',
	'NOAUTH_U_RAID_LIST' => 'Du hast keine Berechtigung Raids aufzulisten.',
	'NOAUTH_U_RAID_VIEW' => 'Du hast keine Berechtigung Raids zu sehen.',

	// Miscellaneous
	'DEACTIVATED_BY_USR' => 'Gebraucher Deaktivierung',

	'ADDED' => 'Hinzugefügt',
	'CLOSED' => 'Geschlossen',
	'DELETED' => 'Gelöscht',
	'FEMALE' => 'weiblich',
	'GENDER' => 'Geschlecht',
	'GUILD' => 'Gilde',
	'LIST' => 'Liste',
	'LIST_PLAYERS' => 'Mitglieder zeigen',
	'MALE' => 'männlich',
	'NOT_AVAILABLE' => 'Nicht vorhanden',
	'NORAIDS' => 'Keine Raids',
	'OR' => 'oder',
	'REQUIRED_FIELD_NOTE' => 'Mit * gekennzeichnete Felder sind Pflichtfelder.',
	'UPDATED' => 'Aktualisiert',
	'NOVIEW' => 'Seite %s kann nicht geladen werden.',

	//---- About ---
	//tabs
	'ABOUT' => 'Über',
	'MAINIMG' => 'bbguild.png',
	'IMAGE_ALT' => 'Logo',
	'REPOSITORY_IMAGE' => 'Google.jpg',
	'TCOPYRIGHT' => 'Copyright',
	'TCREDITS' => 'Credits',
	'TEAM' => 'Entwickler Team',
	'TSPONSORS' => 'Spender',
	'TPLUGINS' => 'Plugins',
	'CREATED' => 'Urheber',
	'DEVELOPEDBY' => 'weiter entwickelt durch',
	'DEVTEAM' => 'bbGuild Entwicklungs Team',
	'AUTHNAME' => 'Ippeh',
	'WEBNAME' =>'Website',
	'SVNNAME' => 'Repository',
	'SVNURL' => 'https://github.com/avatharbe/bbguild',
	'WEBURL' => 'http://www.avathar.be/bbdkp',
	'AUTHWEB' => 'http://www.avathar.be/bbdkp/',
	'LICENSE1' => 'bbGuild ist freie Software. Du kannst es unter den Bedingungen
 der GNU General Public License, wie von der Free Software Foundation
 veröffentlicht, weitergeben und/oder modifizieren, entweder gemäß
 Version 2 der Lizenz oder (nach Ihrer Wahl) jeder späteren Version.

 Die Veröffentlichung bbGuild erfolgt in der Hoffnung,
 daß es dir von Nutzen sein wird, aber OHNE IRGENDEINE
 GARANTIE, sogar ohne die implizite Garantie der MARKTREIFE
 oder der VERWENDBARKEIT FÜR EINEN BESTIMMTEN ZWECK.
 Details finden Sie in der GNU General Public License.

 Du solltest ein Exemplar der GNU General Public License
 zusammen mit diesem Programm erhalten haben.
 Falls nicht schau unter http://www.gnu.org/licenses',
	'LICENSE2' => 'Powered by bbDkp (c) 2009 The bbDkp Project Team. Wenn Sie diese Software benutzen und hilfreich finden, möchten wir Sie bitten den Copyright-Hinweis zu erhalten. Auch wenn er für die freie Verwendung nicht notwendig ist, so hilft er Interesse am Projekt bbGuild zu wecken und <strong>ist Voraussetzung um Support zu erhalten</strong>.',
	'COPYRIGHT3' => 'bbGuild (c) 2010 Sajaki, Malfate, Blazeflack <br /> bbGuild (c) 2008, 2009 Sajaki, Malfate, Kapli, Hroar',
	'COPYRIGHT2' => 'bbGuild (c) 2007 Ippeh, Teksonic, Monkeytech, DWKN',
	'COPYRIGHT1' => 'EQDkp (c) 2003 The EqDkp Project Team ',

	'PRODNAME' => 'Produkt',
	'VERSION' => 'Version',
	'DEVELOPER' => 'Entwickler',
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
	'DONATION' => 'Spenden',
	'DONA_NAME' => 'Name',
	'ADDITIONS' => 'Code Hilfen',
	'CONTRIB' => 'Mitwirkungen',

	// is_enableable() error messages
	'BBGUILD_PHP_VERSION_FAIL'		=> 'Diese Erweiterung benötigt PHP %1$s oder höher. Du verwendest PHP %2$s.',
	'BBGUILD_PHPBB_VERSION_FAIL'	=> 'Diese Erweiterung benötigt phpBB %1$s oder höher. Du verwendest phpBB %2$s.',
	'BBGUILD_REQUIRES_GD'			=> 'Diese Erweiterung benötigt die GD PHP-Erweiterung.',
	'BBGUILD_REQUIRES_CURL'			=> 'Diese Erweiterung benötigt die cURL PHP-Erweiterung.',

	)
);
