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
	'ACTIVE' => 'Actief',
	'ALL' => 'Alle',
	'ARMORY' => 'Armory',
	'GAME' => 'Spel',
	'INACTIVE' => 'Inactief',
	'PLUGIN_DISABLED' => 'Uitgeschakeld (plugin niet ingeschakeld)',
	'LINKED_USER' => 'Forumaccount',
	'NO_PLAYER' => 'Speler niet gevonden.',
	'ROLE' => 'Rol',
	'BBGUILDDISABLED' => 'bbGuild is momenteel uitgeschakeld.',
	'FOOTERBBGUILD' => 'bbGuild',

	// Portal Blocks
	'PORTAL' => 'Portaal',
	'REMEMBERME' => 'Onthoud mij',
	'INFORUM' => 'Geplaatst in',
	'BBGUILD' => 'Guild',
	'NEWS' => 'Nieuws',
	'COMMENT' => 'Reactie',
	'LIST_NEWS' => 'Nieuwsoverzicht',
	'NO_NEWS' => 'Geen nieuwsberichten gevonden.',
	'NEWS_PER_PAGE' => 'Nieuwsberichten per pagina',
	'ERROR_INVALID_NEWS_PROVIDED' => 'Er is geen geldig nieuws-ID opgegeven.',
	'BOSSPROGRESS' => 'Bossvoortgang',
	'WELCOME' => 'Welkom',
	'RECENT_LENGTH' => 'Aantal opgehaalde tekens',
	'NUMTOPICS' => 'Aantal opgehaalde onderwerpen',
	'SHOW_RT_BLOCK' => 'Toon recente onderwerpen',
	'RECENT_TOPICS_SETTING' => 'Instellingen recente onderwerpen',
	'RECENT_TOPICS' => 'Recente onderwerpen',
	'NO_RECENT_TOPICS' => 'Geen recente onderwerpen',
	'POSTED_BY_ON' => 'door %1$s op %2$s',
	'LATESTPLAYERS' => 'Nieuwste spelers',

	// Main Menu
	'MENU' => 'Navigatie',
	'MENU_WELCOME' => 'Welkom',
	'MENU_ROSTER' => 'Ledenlijst',
	'MENU_NEWS' => 'Nieuws',
	'MENU_RAIDS' => 'Raids',
	'MENU_STATS' => 'Statistieken',
	'MENU_PLAYER' => 'Speler',
	'MENU_ACHIEVEMENTS' => 'Prestaties',

	// Games
	'WOW'        => 'World of Warcraft',
	'EQ'         => 'EverQuest',
	'EQ2'        => 'EverQuest II',
	'FFXI'       => 'Final Fantasy XI',
	'LINEAGE2'   => 'Lineage 2',
	'LOTRO'      => 'Lord of the Rings Online',
	'SWTOR'      => 'Star Wars: The Old Republic',
	'FFXIV'      => 'Final Fantasy XIV',
	'PREINSTALLED' => 'Beschikbare spelplugins: %s',

	// Recruitment
	'RECRUITMENT_BLOCK' => 'Wervingsstatus',
	'RECRUIT_CLOSED' => 'Gesloten',
	'RECRUIT_OPEN' => 'Open',
	'TANK' => 'Tank',
	'DPS' => 'Dps',
	'HEAL' => 'Heal',
	'HEALER' => 'Healer',
	'RECRUIT_MESSAGE' => 'Wij zijn momenteel op zoek naar nieuwe spelers voor de volgende klassen:',

	// Roster
	'GUILDROSTER' => 'Guildledenlijst',
	'RANK'   => 'Rang',
	'CLASS'   => 'Klasse',
	'LVL'   => 'Niveau',
	'REALM'  => 'Realm',
	'REGION'  => 'Regio',
	'ACHIEV'  => 'Prestaties',
	'PROFFESSION' => 'Beroepen',

	// Player List
	'FILTER' => 'Filter',
	'LASTRAID' => 'Laatste raid',
	'LEVEL' => 'Niveau',
	'LISTPLAYERS_TITLE' => 'Ranglijst',
	'MNOTFOUND' => 'Kan spelerinformatie niet ophalen',
	'RNOTFOUND' => 'Kan raidinformatie niet ophalen',
	'EMPTYRAIDNAME' => 'Raidnaam niet gevonden',
	'NAME' => 'Naam',
	'SURNAME' => 'Achternaam/Titel',
	'LISTPLAYERS_FOOTCOUNT' => '... %d spelers gevonden',
	'LOGIN_TITLE' => 'Inloggen',
	'NOUCPACCESS' => 'U bent niet bevoegd om personages te claimen',
	'NOUCPADDCHARS' => 'U bent niet bevoegd om personages toe te voegen',
	'NOUCPUPDCHARS' => 'U bent niet bevoegd om uw personages bij te werken',
	'NOUCPDELCHARS' => 'U bent niet bevoegd om uw personages te verwijderen',

	// Common Labels
	'ACCOUNT' => 'Account',
	'ACTION' => 'Actie',
	'ACHIEVED' => 'heeft de prestatie behaald ',
	'ADD' => 'Toevoegen',
	'ADDED_BY' => 'Toegevoegd door %s',
	'ADMINISTRATION' => 'Beheer',
	'ADMINISTRATIVE_OPTIONS' => 'Beheeropties',
	'ADMIN_INDEX' => 'Beheerindex',
	'ATTENDED' => 'Aanwezig geweest',
	'ATTENDEES' => 'Deelnemers',
	'ATTENDANCE' => 'Aanwezigheid',
	'ATT' => 'Aanw.',
	'AVERAGE' => 'Gemiddeld',
	'BOSS' => 'Boss',
	'ARMOR' => 'Pantser',
	'STATS_SOCIAL' => '< 20% Aanwezigheid',
	'STATS_RAIDER' => '< 50% Aanwezigheid',
	'STATS_CORERAIDER' => '> 70% Aanwezigheid',

	// Armor Types
	'CLOTH' => 'Zeer licht / Stof',
	'ROBE' => 'Gewaden',
	'LEATHER' => 'Licht / Leer',
	'AUGMENTED' => 'Versterkt pak',
	'MAIL' =>  'Middel / Maliën',
	'HEAVY' => 'Zwaar pantser',
	'PLATE' => 'Zwaar / Plaat',

	// Class & Race Labels
	'CLASSID' => 'Klasse-ID',
	'CLASS_FACTOR' => 'Klassefactor',
	'CLASSARMOR' => 'Klassepantser',
	'CLASSIMAGE' => 'Afbeelding',
	'CLASSMIN' => 'Min. niveau',
	'CLASSMAX' => 'Max. niveau',
	'CLASS_DISTRIBUTION' => 'Klasseverdeling',
	'CLASS_SUMMARY' => 'Klasseoverzicht: %s tot %s',
	'CONFIGURATION' => 'Configuratie',
	'DATE' => 'Datum',
	'DELETE' => 'Verwijderen',
	'DELETE_CONFIRMATION' => 'Verwijderbevestiging',

	// Character Management
	'NO_CHARACTERS' => 'Geen personages in de database',
	'STATUS' => 'Status J/N',
	'CHARACTER' => 'Hier is een lijst van al uw personages. ',
	'CHARACTER_EXPLAIN' => 'Kies een niet-geclaimd personage om het te claimen en druk op verzenden.',
	'CHARACTERS_UPDATED' => 'De personagenaam %s is toegewezen aan uw account. ',
	'CLAIM_PLAYER' => 'Karakter claimen',
	'CLAIM' => 'Claimen',
	'UNCLAIM' => 'Vrijgeven',
	'CHARACTER_UNCLAIMED' => 'Het personage %s is van je account verwijderd.',
	'CHARACTER_UNCLAIM_FAILED' => 'Kon dit personage niet vrijgeven.',
	'CONFIRM_UNCLAIM_PLAYER' => 'Weet je zeker dat je %s wilt vrijgeven?',
	'MY_CHARACTERS' => 'Mijn Personages',
	'NO_PLAYERS_FOUND' => 'Geen karakters gevonden.',
	'NO_CHARACTERS_BOUND' => 'Geen personages gekoppeld aan uw account.',

	// Entity Labels
	'EVENT' => 'Evenement',
	'EVENTNAME' => 'Evenementnaam',
	'EVENTS' => 'Evenementen',
	'FACTION' => 'Factie',
	'FACTIONID' => 'Factie-ID',
	'FIRST' => 'Eerste',
	'HIGH' => 'Hoog',
	'JOINDATE' => 'Guild-toetredingsdatum',
	'LAST' => 'Laatste',
	'LAST_VISIT' => 'Laatste bezoek',
	'LAST_UPDATE' => 'Laatste update',
	'LOG_DATE_TIME' => 'Datum/Tijd van dit logboek',
	'LOW' => 'Laag',
	'MANAGE' => 'Beheren',
	'MEDIUM' => 'Middel',
	'MEMBERS' => 'Leden',
	'PLAYER' => 'Speler',
	'PLAYERS' => 'Spelers',
	'NA' => 'N.v.t.',
	'NO_DATA' => 'Geen gegevens',
	'MAX_CHARS_EXCEEDED' => 'U kunt maximaal %s personages koppelen aan uw phpBB-account.',
	'MISCELLANEOUS' => 'Diversen',
	'NEWEST' => 'Nieuwste raid',
	'NOTE' => 'Notitie',
	'OLDEST' => 'Oudste raid',
	'OPEN' => 'Open',
	'OPTIONS' => 'Opties',
	'OUTDATE' => 'Guild-vertrekdatum',
	'PERCENT' => 'Percentage',
	'PERMISSIONS' => 'Rechten',
	'PREFERENCES' => 'Voorkeuren',
	'QUOTE' => 'Citaat',
	'RACE' => 'Ras',
	'RACEID' => 'Ras-ID',
	'RAIDSTART' => 'Raid-start',
	'RAIDEND' => 'Raid-einde',
	'RAIDDURATION' => 'Duur',
	'RAID' => 'Raid',
	'RAIDCOUNT' => 'Raidaantal',
	'RAIDS' => 'Raids',
	'RAID_ID' => 'Raid-ID',
	'RANK_DISTRIBUTION' => 'Rangverdeling',
	'REASON' => 'Reden',
	'RESULT' => 'Resultaat',
	'SESSION_ID' => 'Sessie-ID',
	'SUMMARY_DATES' => 'Raidoverzicht: %s tot %s',
	'TIME' => 'Tijd',
	'TOTAL' => 'Totaal',
	'TYPE' => 'Type',
	'UPDATE' => 'Bijwerken',
	'UPDATED_BY' => 'Bijgewerkt door %s',
	'USER' => 'Gebruiker',
	'USERNAME' => 'Gebruikersnaam',
	'VALUE' => 'Waarde',
	'VIEW' => 'Bekijken',
	'VIEW_ACTION' => 'Actie bekijken',
	'VIEW_LOGS' => 'Logboek bekijken',
	'APPLICANTS' => 'Sollicitanten',
	'POSITIONS' => 'Posities',

	// Form Elements
	'ENDING_DATE' => 'Einddatum',
	'GUILD_TAG' => 'Guildtag',
	'LANGUAGE' => 'Taal',
	'STARTING_DATE' => 'Startdatum',
	'TO' => 'Tot',
	'ENTER_NEW' => 'Voer nieuwe naam in',

	// Pagination
	'NEXT_PAGE' => 'Volgende pagina',
	'PAGE' => 'Pagina',
	'PREVIOUS_PAGE' => 'Vorige pagina',

	// Permission Messages
	'NOAUTH_DEFAULT_TITLE' => 'Toegang geweigerd',
	'NOAUTH_U_PLAYER_LIST' => 'U heeft geen toestemming om spelersclassementen te bekijken.',
	'NOAUTH_U_PLAYER_VIEW' => 'U heeft geen toestemming om spelersgeschiedenis te bekijken.',
	'NOAUTH_U_RAID_LIST' => 'U heeft geen toestemming om raids te bekijken.',
	'NOAUTH_U_RAID_VIEW' => 'U heeft geen toestemming om raids te bekijken.',

	// Miscellaneous
	'DEACTIVATED_BY_USR' => 'Gedeactiveerd door gebruiker',
	'ADDED' => 'Toegevoegd',
	'CLOSED' => 'Gesloten',
	'DELETED' => 'Verwijderd',
	'FEMALE' => 'Vrouw',
	'GENDER' => 'Geslacht',
	'GUILD' => 'Guild',
	'LIST' => 'Lijst',
	'LIST_PLAYERS' => 'Spelers tonen',
	'MALE' => 'Man',
	'NOT_AVAILABLE' => 'Niet beschikbaar',
	'NORAIDS' => 'Geen raids',
	'OR' => 'of',
	'REQUIRED_FIELD_NOTE' => 'Velden gemarkeerd met een * zijn verplicht.',
	'UPDATED' => 'Bijgewerkt',
	'NOVIEW' => 'Onbekende weergavenaam %s',

	// About Page
	'ABOUT' => 'Over',
	'MAINIMG' => 'bbguild.png',
	'IMAGE_ALT' => 'Logo',
	'REPOSITORY_IMAGE' => 'Google.jpg',
	'TCOPYRIGHT' => 'Auteursrecht',
	'TCREDITS' => 'Credits',
	'TEAM' => 'Ontwikkelteam',
	'TSPONSORS' => 'Donateurs',
	'TPLUGINS' => 'Plugins',
	'CREATED' => 'Gemaakt door',
	'DEVELOPEDBY' => 'Ontwikkeld door',
	'DEVTEAM' => 'bbGuild-ontwikkelteam',
	'AUTHNAME' => 'Ippeh',
	'WEBNAME' =>'Website',
	'SVNNAME' => 'Repository',
	'SVNURL' => 'https://github.com/avatharbe/bbguild',
	'WEBURL' => 'http://www.avathar.be/bbdkp',
	'AUTHWEB' => 'http://www.avathar.be/bbdkp/',
	'LICENSE1' => 'bbGuild is vrije software: u mag het herdistribueren en/of wijzigen
   onder de voorwaarden van de GNU General Public License zoals gepubliceerd door
   de Free Software Foundation, ofwel versie 3 van de licentie, of
   (naar uw keuze) een latere versie.

   bbGuild wordt verspreid in de hoop dat het nuttig is,
   maar ZONDER ENIGE GARANTIE; zelfs zonder de impliciete garantie van
   VERKOOPBAARHEID of GESCHIKTHEID VOOR EEN BEPAALD DOEL. Zie de
   GNU General Public License voor meer informatie.

   U zou een kopie van de GNU General Public License
   samen met bbGuild ontvangen moeten hebben. Zo niet, zie http://www.gnu.org/licenses',
	'LICENSE2' => 'Aangedreven door bbDKP (c) 2009 The bbDKP Project Team. Als u deze software gebruikt en nuttig vindt, vragen wij u de onderstaande copyrightvermelding te behouden. Hoewel dit niet vereist is voor gratis gebruik, helpt het de interesse in het bbDKP-project te vergroten en is het <strong>vereist voor het verkrijgen van ondersteuning</strong>.',
	'COPYRIGHT3' => 'bbDKP (c) 2010 Sajaki, Malfate, Blazeflack <br />
bbDKP (c) 2008, 2009 Sajaki, Malfate, Kapli, Hroar',
	'COPYRIGHT2' => 'bbDKP (c) 2007 Ippeh, Teksonic, Monkeytech, DWKN',
	'COPYRIGHT1' => 'EQDkp (c) 2003 The EqDkp Project Team ',
	'PRODNAME' => 'Product',
	'VERSION' => 'Versie',
	'DEVELOPER' => 'Ontwikkelaar',
	'JOB' => 'Functie',
	'DEVLINK' => 'Link',
	'PROD' => 'bbGuild',
	'DEVELOPERS' => '<a href=mailto:sajaki@avathar.be>Sajaki</a>',
	'PHPBB' => 'phpBB',
	'PHPBBGR' => 'phpBB Group',
	'PHPBBLINK' => 'http://www.phpbb.com',
	'EQDKP' => 'Origineel EQDKP',
	'EQDKPVERS' => '1.3.2',
	'EQDKPDEV' => 'Tsigo',
	'EQDKPLINK' => 'http://www.eqdkp.com/',
	'PLUGINS' => 'Plugins',
	'PLUGINVERS' => 'Versie',
	'AUTHOR' => 'Auteur',
	'MAINT' => 'Beheerder',
	'DONATION' => 'Donatie',
	'DONA_NAME' => 'Naam',
	'ADDITIONS' => 'Code-toevoegingen',
	'CONTRIB' => 'Bijdragen',

	// is_enableable() error messages
	'BBGUILD_PHP_VERSION_FAIL'		=> 'Deze extensie vereist PHP %1$s of hoger. Je gebruikt PHP %2$s.',
	'BBGUILD_PHPBB_VERSION_FAIL'	=> 'Deze extensie vereist phpBB %1$s of hoger. Je gebruikt phpBB %2$s.',
	'BBGUILD_REQUIRES_GD'			=> 'Deze extensie vereist de GD PHP-extensie.',
	'BBGUILD_REQUIRES_CURL'			=> 'Deze extensie vereist de cURL PHP-extensie.',

	)
);
