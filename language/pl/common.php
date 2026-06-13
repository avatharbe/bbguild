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
	'ACTIVE' => 'Aktywny',
	'ALL' => 'Wszystko',
	'ARMORY' => 'Zbrojownia',
	'GAME' => 'Gra',
	'INACTIVE' => 'Nieaktywny',
	'PLUGIN_DISABLED' => 'Wyłączony (plugin nie jest włączony)',
	'LINKED_USER' => 'Konto na forum',
	'NO_PLAYER' => 'Nie znaleziono gracza.',
	'ROLE' => 'Rola',
	'BBGUILDDISABLED' => 'bbGuild jest obecnie wyłączony.',
	'FOOTERBBGUILD' => 'bbGuild',

	// Portal Blocks
	'PORTAL' => 'Portal',
	'REMEMBERME' => 'Zapamiętaj mnie',
	'INFORUM' => 'Opublikowano w',
	'BBGUILD' => 'Gildia',
	'NEWS' => 'Aktualności',
	'COMMENT' => 'Komentarz',
	'LIST_NEWS' => 'Lista aktualności',
	'NO_NEWS' => 'Nie znaleziono wpisów z aktualnościami.',
	'NEWS_PER_PAGE' => 'Wpisów aktualności na stronę',
	'ERROR_INVALID_NEWS_PROVIDED' => 'Nie podano prawidłowego identyfikatora aktualności.',
	'BOSSPROGRESS' => 'Postęp bossów',
	'WELCOME' => 'Witaj',
	'RECENT_LENGTH' => 'Liczba pobieranych znaków',
	'NUMTOPICS' => 'Liczba pobieranych tematów',
	'SHOW_RT_BLOCK' => 'Pokaż ostatnie tematy',
	'RECENT_TOPICS_SETTING' => 'Ustawienia ostatnich tematów',
	'RECENT_TOPICS' => 'Ostatnie tematy',
	'NO_RECENT_TOPICS' => 'Brak ostatnich tematów',
	'POSTED_BY_ON' => 'przez %1$s dnia %2$s',
	'LATESTPLAYERS' => 'Najnowsi gracze',

	// Main Menu
	'MENU' => 'Nawigacja',
	'MENU_WELCOME' => 'Witaj',
	'MENU_ROSTER' => 'Spis gildii',
	'MENU_NEWS' => 'Aktualności',
	'MENU_RAIDS' => 'Rajdy',
	'MENU_STATS' => 'Statystyki',
	'MENU_PLAYER' => 'Gracz',
	'MENU_ACHIEVEMENTS' => 'Osiągnięcia',

	// Games
	'WOW'        => 'World of Warcraft',
	'EQ'         => 'EverQuest',
	'EQ2'        => 'EverQuest II',
	'FFXI'       => 'Final Fantasy XI',
	'LINEAGE2'   => 'Lineage 2',
	'LOTRO'      => 'Lord of the Rings Online',
	'SWTOR'      => 'Starwars : The old Republic',
	'FFXIV'      => 'Final Fantasy XIV',
	'PREINSTALLED' => 'Dostępne wtyczki gier: %s',

	// Recruitment
	'RECRUITMENT_BLOCK' => 'Status rekrutacji',
	'RECRUIT_CLOSED' => 'Zamknięta',
	'RECRUIT_OPEN' => 'Otwarta',
	'TANK' => 'Tank',
	'DPS' => 'DPS',
	'HEAL' => 'Leczenie',
	'HEALER' => 'Uzdrowiciel',
	'RECRUIT_MESSAGE' => 'Aktualnie poszukujemy nowych graczy na następujące klasy:',

	// Roster
	'GUILDROSTER' => 'Spis gildii',
	'RANK'   => 'Ranga',
	'CLASS'   => 'Klasa',
	'LVL'   => 'Poziom',
	'REALM'  => 'Realm',
	'REGION'  => 'Region',
	'ACHIEV'  => 'Osiągnięcia',
	'PROFFESSION' => 'Profesje',

	// Player List
	'FILTER' => 'Filtr',
	'LASTRAID' => 'Ostatni rajd',
	'LEVEL' => 'Poziom',
	'LISTPLAYERS_TITLE' => 'Tabela wyników',
	'MNOTFOUND' => 'Nie udało się pobrać informacji o graczu',
	'RNOTFOUND' => 'Nie udało się pobrać informacji o rajdzie',
	'EMPTYRAIDNAME' => 'Nie znaleziono nazwy rajdu',
	'NAME' => 'Nazwa',
	'SURNAME' => 'Przydomek/Tytuł',
	'LISTPLAYERS_FOOTCOUNT' => '... znaleziono %d graczy',
	'LOGIN_TITLE' => 'Logowanie',
	'NOUCPACCESS' => 'Nie masz uprawnień do przypisywania postaci',
	'NOUCPADDCHARS' => 'Nie masz uprawnień do dodawania postaci',
	'NOUCPUPDCHARS' => 'Nie masz uprawnień do aktualizowania swoich postaci',
	'NOUCPDELCHARS' => 'Nie masz uprawnień do usuwania swoich postaci',

	// Common Labels
	'ACCOUNT' => 'Konto',
	'ACTION' => 'Akcja',
	'ACHIEVED' => 'zdobył osiągnięcie ',
	'ADD' => 'Dodaj',
	'ADDED_BY' => 'Dodane przez %s',
	'ADMINISTRATION' => 'Administracja',
	'ADMINISTRATIVE_OPTIONS' => 'Opcje administracyjne',
	'ADMIN_INDEX' => 'Indeks administracji',
	'ATTENDED' => 'Uczestniczył',
	'ATTENDEES' => 'Uczestnicy',
	'ATTENDANCE' => 'Frekwencja',
	'ATT' => 'Frekw.',
	'AVERAGE' => 'Średnia',
	'BOSS' => 'Boss',
	'ARMOR' => 'Zbroja',
	'STATS_SOCIAL' => '< 20% Frekwencja',
	'STATS_RAIDER' => '< 50% Frekwencja',
	'STATS_CORERAIDER' => '> 70% Frekwencja',

	// Armor Types
	'CLOTH' => 'Bardzo lekka / Tkanina',
	'ROBE' => 'Szaty',
	'LEATHER' => 'Lekka / Skóra',
	'AUGMENTED' => 'Ulepszony kombinezon',
	'MAIL' =>  'Średnia / Kolczuga',
	'HEAVY' => 'Ciężka zbroja',
	'PLATE' => 'Ciężka / Płytowa',

	// Class & Race Labels
	'CLASSID' => 'ID klasy',
	'CLASS_FACTOR' => 'Współczynnik klasy',
	'CLASSARMOR' => 'Zbroja klasy',
	'CLASSIMAGE' => 'Obrazek',
	'CLASSMIN' => 'Min. poziom',
	'CLASSMAX' => 'Maks. poziom',
	'CLASS_DISTRIBUTION' => 'Rozkład klas',
	'CLASS_SUMMARY' => 'Podsumowanie klas: %s do %s',
	'CONFIGURATION' => 'Konfiguracja',
	'DATE' => 'Data',
	'DELETE' => 'Usuń',
	'DELETE_CONFIRMATION' => 'Potwierdzenie usunięcia',

	// Character Management
	'NO_CHARACTERS' => 'Brak postaci w bazie danych',
	'STATUS' => 'Status T/N',
	'CHARACTER' => 'Oto lista wszystkich Twoich postaci. ',
	'CHARACTER_EXPLAIN' => 'Wybierz nieprzypisaną postać, aby ją przypisać, i naciśnij Zatwierdź.',
	'CHARACTERS_UPDATED' => 'Postać %s została przypisana do Twojego konta. ',
	'CLAIM_PLAYER' => 'Przypisz postać',
	'CLAIM' => 'Przypisz',
	'UNCLAIM' => 'Zwolnij',
	'CHARACTER_UNCLAIMED' => 'Postać %s została usunięta z twojego konta.',
	'CHARACTER_UNCLAIM_FAILED' => 'Nie udało się zwolnić tej postaci.',
	'CONFIRM_UNCLAIM_PLAYER' => 'Czy na pewno chcesz zwolnić %s?',
	'MY_CHARACTERS' => 'Moje Postacie',
	'NO_PLAYERS_FOUND' => 'Nie znaleziono postaci.',
	'NO_CHARACTERS_BOUND' => 'Brak postaci przypisanych do Twojego konta.',

	// Entity Labels
	'EVENT' => 'Wydarzenie',
	'EVENTNAME' => 'Nazwa wydarzenia',
	'EVENTS' => 'Wydarzenia',
	'FACTION' => 'Frakcja',
	'FACTIONID' => 'ID frakcji',
	'FIRST' => 'Pierwszy',
	'HIGH' => 'Wysoki',
	'JOINDATE' => 'Data dołączenia do gildii',
	'LAST' => 'Ostatni',
	'LAST_VISIT' => 'Ostatnia wizyta',
	'LAST_UPDATE' => 'Ostatnia aktualizacja',
	'LOG_DATE_TIME' => 'Data/Czas tego wpisu',
	'LOW' => 'Niski',
	'MANAGE' => 'Zarządzaj',
	'MEDIUM' => 'Średni',
	'MEMBERS' => 'Członkowie',
	'PLAYER' => 'Gracz',
	'PLAYERS' => 'Gracze',
	'NA' => 'N/D',
	'NO_DATA' => 'Brak danych',
	'MAX_CHARS_EXCEEDED' => 'Przepraszamy, możesz mieć tylko %s postaci przypisanych do swojego konta phpBB.',
	'MISCELLANEOUS' => 'Różne',
	'NEWEST' => 'Najnowszy rajd',
	'NOTE' => 'Uwaga',
	'OLDEST' => 'Najstarszy rajd',
	'OPEN' => 'Otwarte',
	'OPTIONS' => 'Opcje',
	'OUTDATE' => 'Data opuszczenia gildii',
	'PERCENT' => 'Procent',
	'PERMISSIONS' => 'Uprawnienia',
	'PREFERENCES' => 'Preferencje',
	'QUOTE' => 'Cytat',
	'RACE' => 'Rasa',
	'RACEID' => 'ID rasy',
	'RAIDSTART' => 'Początek rajdu',
	'RAIDEND' => 'Koniec rajdu',
	'RAIDDURATION' => 'Czas trwania',
	'RAID' => 'Rajd',
	'RAIDCOUNT' => 'Liczba rajdów',
	'RAIDS' => 'Rajdy',
	'RAID_ID' => 'ID rajdu',
	'RANK_DISTRIBUTION' => 'Rozkład rang',
	'REASON' => 'Powód',
	'RESULT' => 'Wynik',
	'SESSION_ID' => 'ID sesji',
	'SUMMARY_DATES' => 'Podsumowanie rajdów: %s do %s',
	'TIME' => 'Czas',
	'TOTAL' => 'Łącznie',
	'TYPE' => 'Typ',
	'UPDATE' => 'Aktualizuj',
	'UPDATED_BY' => 'Zaktualizowane przez %s',
	'USER' => 'Użytkownik',
	'USERNAME' => 'Nazwa użytkownika',
	'VALUE' => 'Wartość',
	'VIEW' => 'Podgląd',
	'VIEW_ACTION' => 'Podgląd akcji',
	'VIEW_LOGS' => 'Podgląd logów',
	'APPLICANTS' => 'Kandydaci',
	'POSITIONS' => 'Stanowiska',

	// Form Elements
	'ENDING_DATE' => 'Data końcowa',
	'GUILD_TAG' => 'Tag gildii',
	'LANGUAGE' => 'Język',
	'STARTING_DATE' => 'Data początkowa',
	'TO' => 'Do',
	'ENTER_NEW' => 'Wprowadź nową nazwę',

	// Pagination
	'NEXT_PAGE' => 'Następna strona',
	'PAGE' => 'Strona',
	'PREVIOUS_PAGE' => 'Poprzednia strona',

	// Permission Messages
	'NOAUTH_DEFAULT_TITLE' => 'Brak uprawnień',
	'NOAUTH_U_PLAYER_LIST' => 'Nie masz uprawnień do przeglądania listy graczy.',
	'NOAUTH_U_PLAYER_VIEW' => 'Nie masz uprawnień do przeglądania historii gracza.',
	'NOAUTH_U_RAID_LIST' => 'Nie masz uprawnień do wyświetlania listy rajdów.',
	'NOAUTH_U_RAID_VIEW' => 'Nie masz uprawnień do przeglądania rajdów.',

	// Miscellaneous
	'DEACTIVATED_BY_USR' => 'Dezaktywowane przez użytkownika',
	'ADDED' => 'Dodano',
	'CLOSED' => 'Zamknięte',
	'DELETED' => 'Usunięte',
	'FEMALE' => 'Kobieta',
	'GENDER' => 'Płeć',
	'GUILD' => 'Gildia',
	'LIST' => 'Lista',
	'LIST_PLAYERS' => 'Lista graczy',
	'MALE' => 'Mężczyzna',
	'NOT_AVAILABLE' => 'Niedostępne',
	'NORAIDS' => 'Brak rajdów',
	'OR' => 'lub',
	'REQUIRED_FIELD_NOTE' => 'Pola oznaczone * są wymagane.',
	'UPDATED' => 'Zaktualizowano',
	'NOVIEW' => 'Nieznana nazwa widoku %s',

	// About Page
	'ABOUT' => 'O nas',
	'MAINIMG' => 'bbguild.png',
	'IMAGE_ALT' => 'Logo',
	'REPOSITORY_IMAGE' => 'Google.jpg',
	'TCOPYRIGHT' => 'Prawa autorskie',
	'TCREDITS' => 'Podziękowania',
	'TEAM' => 'Zespół deweloperów',
	'TSPONSORS' => 'Darczyńcy',
	'TPLUGINS' => 'Wtyczki',
	'CREATED' => 'Utworzył',
	'DEVELOPEDBY' => 'Rozwijane przez',
	'DEVTEAM' => 'Zespół deweloperów bbGuild',
	'AUTHNAME' => 'Ippeh',
	'WEBNAME' =>'Strona internetowa',
	'SVNNAME' => 'Repozytorium',
	'SVNURL' => 'https://github.com/avatharbe/bbguild',
	'WEBURL' => 'http://www.avathar.be/bbdkp',
	'AUTHWEB' => 'http://www.avathar.be/bbdkp/',
	'LICENSE1' => 'bbGuild jest wolnym oprogramowaniem: możesz je rozpowszechniać i/lub modyfikować
   na warunkach Powszechnej Licencji Publicznej GNU opublikowanej przez
   Free Software Foundation, w wersji 3 Licencji lub
   (według własnego uznania) dowolnej późniejszej wersji.

   bbGuild jest rozpowszechniany w nadziei, że będzie użyteczny,
   ale BEZ JAKIEJKOLWIEK GWARANCJI; nawet bez dorozumianej gwarancji
   PRZYDATNOŚCI HANDLOWEJ lub PRZYDATNOŚCI DO OKREŚLONEGO CELU. Zobacz
   Powszechną Licencję Publiczną GNU, aby uzyskać więcej szczegółów.

   Powinieneś otrzymać kopię Powszechnej Licencji Publicznej GNU
   wraz z bbGuild. Jeśli nie, zobacz http://www.gnu.org/licenses',
	'LICENSE2' => 'Oparty na bbDKP (c) 2009 The bbDKP Project Team. Jeśli korzystasz z tego oprogramowania i uważasz je za przydatne, prosimy o zachowanie poniższej informacji o prawach autorskich. Choć nie jest to wymagane do bezpłatnego użytkowania, pomoże to wzbudzić zainteresowanie projektem bbDKP i jest <strong>wymagane w celu uzyskania wsparcia</strong>.',
	'COPYRIGHT3' => 'bbDKP (c) 2010 Sajaki, Malfate, Blazeflack <br />
bbDKP (c) 2008, 2009 Sajaki, Malfate, Kapli, Hroar',
	'COPYRIGHT2' => 'bbDKP (c) 2007 Ippeh, Teksonic, Monkeytech, DWKN',
	'COPYRIGHT1' => 'EQDkp (c) 2003 The EqDkp Project Team ',
	'PRODNAME' => 'Produkt',
	'VERSION' => 'Wersja',
	'DEVELOPER' => 'Deweloper',
	'JOB' => 'Zadanie',
	'DEVLINK' => 'Odnośnik',
	'PROD' => 'bbGuild',
	'DEVELOPERS' => '<a href=mailto:sajaki@avathar.be>Sajaki</a>',
	'PHPBB' => 'phpBB',
	'PHPBBGR' => 'phpBB Group',
	'PHPBBLINK' => 'http://www.phpbb.com',
	'EQDKP' => 'Oryginalny EQDKP',
	'EQDKPVERS' => '1.3.2',
	'EQDKPDEV' => 'Tsigo',
	'EQDKPLINK' => 'http://www.eqdkp.com/',
	'PLUGINS' => 'Wtyczki',
	'PLUGINVERS' => 'Wersja',
	'AUTHOR' => 'Autor',
	'MAINT' => 'Opiekun',
	'DONATION' => 'Darowizna',
	'DONA_NAME' => 'Nazwa',
	'ADDITIONS' => 'Dodatkowy kod',
	'CONTRIB' => 'Wkład',

	// is_enableable() error messages
	'BBGUILD_PHP_VERSION_FAIL'		=> 'To rozszerzenie wymaga PHP %1$s lub wyższego. Używasz PHP %2$s.',
	'BBGUILD_PHPBB_VERSION_FAIL'	=> 'To rozszerzenie wymaga phpBB %1$s lub wyższego. Używasz phpBB %2$s.',
	'BBGUILD_REQUIRES_GD'			=> 'To rozszerzenie wymaga załadowanego rozszerzenia PHP GD.',
	'BBGUILD_REQUIRES_CURL'			=> 'To rozszerzenie wymaga załadowanego rozszerzenia PHP cURL.',

	)
);
