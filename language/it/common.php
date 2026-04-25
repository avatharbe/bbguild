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
	'ACTIVE' => 'Attivo',
	'ALL' => 'Tutti',
	'ARMORY' => 'Armeria',
	'GAME' => 'Gioco',
	'INACTIVE' => 'Inattivo',
	'PLUGIN_DISABLED' => 'Disabilitato (plugin non abilitato)',
	'LINKED_USER' => 'Account Forum',
	'NO_PLAYER' => 'Giocatore non trovato.',
	'ROLE' => 'Ruolo',
	'BBGUILDDISABLED' => 'bbGuild è attualmente disabilitato.',
	'FOOTERBBGUILD' => 'bbGuild',

	//---- Portal blocks -----
	'PORTAL' => 'Portale',
	'REMEMBERME' => 'Ricordami',
	'INFORUM' => 'Inserito',
	'BBGUILD' => 'Guild',
	'NEWS' => 'News',
	'COMMENT' => 'Commenti',
	'LIST_NEWS' => 'Lista News',
	'NO_NEWS' => 'Nessuna news trovata.',
	'NEWS_PER_PAGE' => 'News per pagina',
	'ERROR_INVALID_NEWS_PROVIDED' => 'Non è stato inserito un ID news valido.',
	'BOSSPROGRESS' => 'Bossprogress',
	'WELCOME' => 'Benvenuto',
	'RECENT_LENGTH' => 'Numero di personaggi associati',
	'NUMTOPICS' => 'Numero di discussioni associate',
	'SHOW_RT_BLOCK' => 'Mostra le ultime discussioni',
	'RECENT_TOPICS_SETTING' => 'Impostazioni Discussioni Recenti',
	'RECENT_TOPICS' => 'Discussioni Recenti',
	'NO_RECENT_TOPICS' => 'Nessuna Discussione Recente',
	'POSTED_BY_ON' => 'da %1$s il %2$s',
	'LATESTPLAYERS' => 'Ultimi Iscritti',

	// Main Menu
	'MENU' => 'Menù',
	'MENU_WELCOME' => 'Benvenuto',
	'MENU_ROSTER' => 'Roster',
	'MENU_NEWS' => 'News',
	'MENU_RAIDS' => 'Incursioni',
	'MENU_STATS' => 'Statistiche',
	'MENU_PLAYER' => 'Player',
	'MENU_ACHIEVEMENTS' => 'Achievements',

	// Games
	'WOW'        => 'World of Warcraft',
	'EQ'         => 'EverQuest',
	'EQ2'        => 'EverQuest II',
	'FFXI'       => 'Final Fantasy XI',
	'LINEAGE2'   => 'Lineage 2',
	'LOTRO'      => 'Lord of the Rings Online',
	'SWTOR'      => 'Starwars : The old Republic',
	'FFXIV'      => 'Final Fantasy XIV',
	'PREINSTALLED' => 'Preinstalled games: %s',

	// Recruitment
	'RECRUITMENT_BLOCK' => 'Reclutamento',
	'RECRUIT_CLOSED' => 'Chiusa !',
	'RECRUIT_OPEN' => 'Open',
	'TANK' => 'Difensore',
	'DPS' => 'Assaltatore',
	'HEAL' => 'Curatore',
	'HEALER' => 'Curatore',
	'RECRUIT_MESSAGE' => 'Attualmente stiamo cercando giocatori per le seguenti specializzazioni:',

	// Roster
	'GUILDROSTER' => 'Elenco personaggi',
	'RANK'   => 'Grado',
	'CLASS'   => 'Classe',
	'LVL'   => 'Level',
	'REALM'  => 'Reame',
	'REGION'  => 'Regione',
	'ACHIEV'  => 'Imprese',
	'PROFFESSION' => 'Professioni',

	// Player list
	'FILTER' => 'Filtro',
	'LASTRAID' => 'Ultima Incursione',
	'LEVEL' => 'Livello',
	'LISTPLAYERS_TITLE' => 'Classifiche Giocatori',
	'MNOTFOUND' => 'Informazioni Aggiuntive non disponibili',
	'RNOTFOUND' => 'Informazioni Incursione non disponibili',
	'EMPTYRAIDNAME' => 'Incursione Non Trovata',
	'NAME' => 'Nome',
	'SURNAME' => 'Nome/Titolo',
	'LISTPLAYERS_FOOTCOUNT' => '... trovati %d Personaggi',
	'LOGIN_TITLE' => 'Login',
	'NOUCPACCESS' => 'Non sei autorizzato ad associarti personaggi',
	'NOUCPADDCHARS' => 'Non sei autorizzato ad aggiungere personaggi',
	'NOUCPUPDCHARS' => 'Non sei autorizzato ad aggiornare personaggi',
	'NOUCPDELCHARS' => 'Non sei autorizzato a cancellari personaggi',

	// Various
	'ACCOUNT' => 'Account',
	'ACTION' => 'Azioni',
	'ACHIEVED' => 'earned the achievement ',
	'ADD' => 'Aggiungi',
	'ADDED_BY' => 'Aggiunto da %s',
	'ADMINISTRATION' => 'Amministrazione',
	'ADMINISTRATIVE_OPTIONS' => 'Opzioni Amministrazione',
	'ADMIN_INDEX' => 'Indice Amministrazione',
	'ATTENDED' => 'Partecipato',
	'ATTENDEES' => 'Partecipanti',
	'ATTENDANCE' => 'Presenza',
	'ATT' => 'Pre.',
	'AVERAGE' => 'Media',
	'BOSS' => 'Boss',
	'ARMOR' => 'Armatura',
	'STATS_SOCIAL' => '< 20% Presenza',
	'STATS_RAIDER' => '< 50% Presenza',
	'STATS_CORERAIDER' => '> 70% Presenza',

	// Armor types
	'CLOTH' => 'Molto Leggera / Stoffa',
	'ROBE' => 'Robes',
	'LEATHER' => 'Leggera / Pelle',
	'AUGMENTED' => 'Augmented Suit',
	'MAIL' =>  'Media / Maglia',
	'HEAVY' => 'Armatura Pesante',
	'PLATE' => 'Pesante / Piastre',

	'CLASSID' => 'ID Classe',
	'CLASS_FACTOR' => 'Fattore di Classe',
	'CLASSARMOR' => 'Tipo Armatura',
	'CLASSIMAGE' => 'Immagine',
	'CLASSMIN' => 'Livello Minimo',
	'CLASSMAX' => 'Livello Massimo',
	'CLASS_DISTRIBUTION' => 'Distribuzione per Classe',
	'CLASS_SUMMARY' => 'Sommario per Classe da %s a %s',
	'CONFIGURATION' => 'Configurazione',
	'DATE' => 'Data',
	'DELETE' => 'Cancella',
	'DELETE_CONFIRMATION' => 'Conferma Cancellazione',

	'NO_CHARACTERS' => 'Nessun Personaggio nel Database',
	'STATUS' => 'Stato S/N',
	'CHARACTER' => 'Questa è la lista di tutti i tuoi Personaggi. ',
	'CHARACTER_EXPLAIN' => 'Scegli un Personaggio che non è ancora stato associato.',
	'CHARACTERS_UPDATED' => 'Il Personaggio %s è stato assegnato al tuo account. ',
	'CLAIM_PLAYER' => 'Rivendica personaggio',
	'CLAIM' => 'Rivendica',
	'UNCLAIM' => 'Rilascia',
	'CHARACTER_UNCLAIMED' => 'Il personaggio %s è stato rimosso dal tuo account.',
	'CHARACTER_UNCLAIM_FAILED' => 'Impossibile rilasciare questo personaggio.',
	'CONFIRM_UNCLAIM_PLAYER' => 'Sei sicuro di voler rilasciare %s?',
	'MY_CHARACTERS' => 'I Miei Personaggi',
	'NO_PLAYERS_FOUND' => 'Nessun personaggio trovato.',
	'NO_CHARACTERS_BOUND' => 'Nessun Personaggio collegato al tuo account.',

	'EVENT' => 'Evento',
	'EVENTNAME' => 'Nome Evento',
	'EVENTS' => 'Eventi',
	'FACTION' => 'Fazione',
	'FACTIONID' => 'ID Fazione',
	'FIRST' => 'Primo',
	'HIGH' => 'Alto',
	'JOINDATE' => 'Data Ingresso in Gilda',
	'LAST' => 'Ultimo',
	'LAST_VISIT' => 'Ultima Visita',
	'LAST_UPDATE' => 'Last Aggiornamento',
	'LOG_DATE_TIME' => 'Data/Ora di questo Log',
	'LOW' => 'Basso',
	'MANAGE' => 'Gestisci',
	'MEDIUM' => 'Medio',
	'MEMBERS' => 'Members',
	'PLAYER' => 'Membro',
	'PLAYERS' => 'Membri',
	'NA' => 'N/A',
	'NO_DATA' => 'Nessun Dato',
	'MAX_CHARS_EXCEEDED' => 'Spiacente, puoi avere solamente %s Personaggi associati al tuo account del Forum.',
	'MISCELLANEOUS' => 'Varie',
	'NEWEST' => 'Ultime Incursioni',

	'NOTE' => 'Note',
	'OLDEST' => 'Vecchie incursioni',
	'OPEN' => 'Aperto',
	'OPTIONS' => 'Opzioni',
	'OUTDATE' => 'Data abbandoni gilda',
	'PERCENT' => 'Percentuale',
	'PERMISSIONS' => 'Permessi',
	'PREFERENCES' => 'Preferenze',
	'QUOTE' => 'Cita',
	'RACE' => 'Razza',
	'RACEID' => 'Race ID',
	'RAIDSTART' => 'Inizio Incursione',
	'RAIDEND' => 'Fine Incursione',
	'RAIDDURATION' => 'Durata',
	'RAID' => 'Incursione',
	'RAIDCOUNT' => 'Conteggio Incursione',
	'RAIDS' => 'Incursioni',
	'RAID_ID' => 'ID Incursione',
	'RANK_DISTRIBUTION' => 'Distribuzione Gradi',
	'REASON' => 'Motivazione',
	'RESULT' => 'Risultato',

	'SESSION_ID' => 'ID Sessione',

	'SUMMARY_DATES' => 'Sommario Incursione: %s a %s',
	'TIME' => 'Orario',
	'TOTAL' => 'Totale',
	'TYPE' => 'Tipo',
	'UPDATE' => 'Aggiornato',
	'UPDATED_BY' => 'Aggiornato da %s',
	'USER' => 'Utente',
	'USERNAME' => 'Username',
	'VALUE' => 'Valore',
	'VIEW' => 'Vedi',
	'VIEW_ACTION' => 'Vedi Azioni',
	'VIEW_LOGS' => 'Vedi Logs',
	'APPLICANTS' => 'Applicants',
	'POSITIONS' => 'Positions',

	// Form Elements
	'ENDING_DATE' => 'Data Fine',
	'GUILD_TAG' => 'Tag di Gilda',
	'LANGUAGE' => 'Lingua',
	'STARTING_DATE' => 'Data Inizio',
	'TO' => 'A',
	'ENTER_NEW' => 'inserisci nuovo nome',

	// Pagination
	'NEXT_PAGE' => 'Pagina Successiva',
	'PAGE' => 'Pagina',
	'PREVIOUS_PAGE' => 'Pagina Precedente',

	// Permission Messages
	'NOAUTH_DEFAULT_TITLE' => 'Permesso Negato',
	'NOAUTH_U_PLAYER_LIST' => 'Non hai il permesso di vedere le classifiche utenti.',
	'NOAUTH_U_PLAYER_VIEW' => 'Non hai il permesso di vedere lo storico dei personaggi.',
	'NOAUTH_U_RAID_LIST' => 'Non hai il permesso di visualizzare l elenco incursioni.',
	'NOAUTH_U_RAID_VIEW' => 'Non hai il permesso di visualizzare le incursioni.',

	// Miscellaneous
	'DEACTIVATED_BY_USR' => 'Disattivato da User',

	'ADDED' => 'Aggiunto',
	'CLOSED' => 'Chiuso',
	'DELETED' => 'Cancellato',
	'FEMALE' => 'Femmina',
	'GENDER' => 'Sesso',
	'GUILD' => 'Gilda',
	'LIST' => 'Elenco',
	'LIST_PLAYERS' => 'Elenco Membri',
	'MALE' => 'Maschio',
	'NOT_AVAILABLE' => 'Non Disponibile',
	'NORAIDS' => 'Nessuna Incursione',
	'OR' => 'o',
	'REQUIRED_FIELD_NOTE' => 'Oggetti marcati con un * sono campi obbligatori.',
	'UPDATED' => 'Aggiornato',
	'NOVIEW' => 'Nome Sconosciuto %s',

	//---- About ---
	//tabs
	'ABOUT' => 'About',
	'MAINIMG' => 'bbguild.png',
	'IMAGE_ALT' => 'Logo',
	'REPOSITORY_IMAGE' => 'Google.jpg',
	'TCOPYRIGHT' => 'Copyright',
	'TCREDITS' => 'Credits',
	'TEAM' => 'Dev Team',
	'TSPONSORS' => 'Donators',
	'TPLUGINS' => 'PlugIns',
	'CREATED' => 'Created by',
	'DEVELOPEDBY' => 'Developed by',
	'DEVTEAM' => 'bbGuild Development Team',
	'AUTHNAME' => 'Ippeh',
	'WEBNAME' =>'Website',
	'SVNNAME' => 'Repository',
	'SVNURL' => 'https://github.com/avatharbe/bbguild',
	'WEBURL' => 'http://www.avathar.be/bbdkp',
	'AUTHWEB' => 'http://www.avathar.be/bbdkp/',
	'LICENSE1' => 'bbGuild is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   bbGuild is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with bbGuild.  If not, see http://www.gnu.org/licenses',
	'LICENSE2' => 'Powered by bbDKP (c) 2009 The bbDKP Project Team. If you use this software and find it to be useful, we ask that you retain the copyright notice below. While not required for free use, it will help build interest in the bbDKP project and is <strong>required for obtaining support</strong>.',
	'COPYRIGHT3' => 'bbDKP (c) 2010 Sajaki, Malfate, Blazeflack <br />
bbDKP (c) 2008, 2009 Sajaki, Malfate, Kapli, Hroar',
	'COPYRIGHT2' => 'bbDKP (c) 2007 Ippeh, Teksonic, Monkeytech, DWKN',
	'COPYRIGHT1' => 'EQDkp (c) 2003 The EqDkp Project Team ',

	'PRODNAME' => 'Product',
	'VERSION' => 'Version',
	'DEVELOPER' => 'Developer',
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

	)
);
