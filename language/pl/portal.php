<?php
/**
 * @package bbGuild Portal
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * Portal language keys (Polish)
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
	'ACP_BBGUILD_PORTAL'             => 'Portal',

	// Module names
	'BBGUILD_PORTAL_MOTD'            => 'Wiadomość dnia',
	'BBGUILD_PORTAL_RECRUITMENT'     => 'Rekrutacja',
	'BBGUILD_PORTAL_ROSTER'          => 'Lista członków',

	// Module content
	'NO_RECRUITS'                    => 'Brak otwartych pozycji rekrutacyjnych.',
	'BBGUILD_NO_PORTAL_MODULES'      => 'Nie skonfigurowano modułów portalu dla tej gildii.',

	// ACP portal management
	'ACP_PORTAL_EXPLAIN'             => 'Zarządzaj modułami portalu dla każdej gildii. Dodawaj, usuwaj, zmieniaj kolejność i włączaj/wyłączaj bloki.',
	'ACP_PORTAL_SELECT_GUILD'        => 'Wybierz gildię',
	'ACP_PORTAL_MODULES'             => 'Moduły portalu',
	'ACP_PORTAL_MODULE_NAME'         => 'Moduł',
	'ACP_PORTAL_COLUMN'              => 'Kolumna',
	'ACP_PORTAL_ORDER'               => 'Kolejność',
	'ACP_PORTAL_COLUMN_TOP'          => 'Góra',
	'ACP_PORTAL_COLUMN_CENTER'       => 'Środek',
	'ACP_PORTAL_COLUMN_RIGHT'        => 'Prawa',
	'ACP_PORTAL_ADD_MODULE'          => 'Dodaj moduł',
	'ACP_PORTAL_NO_MODULES'          => 'Brak modułów portalu skonfigurowanych dla tej gildii. Dodaj jeden poniżej.',
	'ACP_PORTAL_MODULE_ADDED'        => 'Moduł portalu został dodany.',
	'ACP_PORTAL_MODULE_ADD_FAILED'   => 'Nie można dodać modułu portalu. Moduł może nie być dozwolony w wybranej kolumnie.',
	'ACP_PORTAL_MODULE_DELETED'      => 'Moduł portalu został usunięty.',
	'ACP_PORTAL_MOVE_LEFT'           => 'Przenieś do poprzedniej kolumny',
	'ACP_PORTAL_MOVE_RIGHT'          => 'Przenieś do następnej kolumny',
	'ACP_PORTAL_COLUMN_BOTTOM'       => 'Dół',

	'ACP_PORTAL_TABS'                => 'Zakładki',
	'ACP_PORTAL_TAB'                 => 'Zakładka',
	'ACP_PORTAL_TAB_NAME'            => 'Nazwa zakładki',
	'ACP_PORTAL_TAB_SLUG'            => 'Slug',
	'ACP_PORTAL_TAB_SLUG_EXPLAIN'    => 'Używany w adresie URL strony, np. <samp>raids</samp>. Tylko litery, cyfry, myślniki i podkreślenia.',
	'ACP_PORTAL_ADD_TAB'             => 'Dodaj zakładkę',
	'ACP_PORTAL_TAB_ADDED'           => 'Zakładka została dodana.',
	'ACP_PORTAL_TAB_ADD_FAILED'      => 'Nie można dodać zakładki. Zakładka z tym slugiem już istnieje dla tej gildii.',
	'ACP_PORTAL_TAB_UPDATED'         => 'Zakładka została zaktualizowana.',
	'ACP_PORTAL_TAB_UPDATE_FAILED'   => 'Nie można zaktualizować zakładki. Zakładka z tym slugiem już istnieje dla tej gildii.',
	'ACP_PORTAL_TAB_DELETED'         => 'Zakładka została usunięta.',
	'ACP_PORTAL_TAB_DELETE_FAILED'   => 'Nie można usunąć zakładki. Gildia musi zawsze mieć co najmniej jedną zakładkę.',

	// Module configuration
	'ACP_PORTAL_MODULE_CONFIG'        => 'Konfiguracja modułu',
	'ACP_PORTAL_MODULE_CONFIG_EXPLAIN' => 'Skonfiguruj nazwę wyświetlaną, ikonę, widoczność i ustawienia specyficzne dla tego bloku portalu. Zmiany dotyczą strony powitalnej gildii.',
	'ACP_PORTAL_MODULE_SETTINGS'      => 'Ustawienia ogólne',
	'ACP_PORTAL_MODULE_SETTINGS_EXPLAIN' => 'Ustaw nazwę wyświetlaną oraz włącz lub wyłącz ten moduł. Wyłączone moduły są ukryte na portalu, ale zachowują swoją konfigurację.',
	'ACP_PORTAL_ICON_SETTINGS'        => 'Ustawienia ikony',
	'ACP_PORTAL_ICON_SETTINGS_EXPLAIN' => 'Wybierz ikonę wyświetlaną obok tytułu modułu na portalu. Możesz użyć pliku obrazu lub ikony Font Awesome. Jeśli oba są ustawione, ikona Font Awesome ma priorytet.',
	'ACP_PORTAL_IMAGE_SRC'            => 'Plik obrazu',
	'ACP_PORTAL_IMAGE_SRC_EXPLAIN'    => 'Ścieżka względna do pliku obrazu, np. <samp>ext/avathar/bbguild/images/icon.png</samp>.',
	'ACP_PORTAL_FA_ICON'              => 'Ikona Font Awesome',
	'ACP_PORTAL_FA_ICON_EXPLAIN'      => 'Wprowadź klasę CSS Font Awesome 4.7, np. <samp>fa-star</samp>, <samp>fa-shield</samp>, <samp>fa-users</samp>. Ma priorytet nad plikiem obrazu. Pozostaw puste, aby użyć obrazu.',
	'ACP_PORTAL_ICON_SIZE'            => 'Rozmiar ikony',
	'ACP_PORTAL_ICON_SIZE_EXPLAIN'    => 'Rozmiar ikony Font Awesome w pikselach (8–64). Dotyczy tylko gdy ustawiona jest ikona Font Awesome.',
	'ACP_PORTAL_GROUP_ACCESS'         => 'Dostęp grup',
	'ACP_PORTAL_GROUP_ACCESS_EXPLAIN' => 'Ogranicz, które grupy użytkowników mogą widzieć ten moduł na stronie portalu.',
	'ACP_PORTAL_GROUP_IDS'            => 'Dozwolone grupy',
	'ACP_PORTAL_GROUP_IDS_EXPLAIN'    => 'Wybierz jedną lub więcej grup. Tylko członkowie wybranych grup zobaczą ten moduł. Pozostaw puste, aby pokazać moduł wszystkim użytkownikom.',
	'ACP_PORTAL_MODULE_SPECIFIC'      => 'Ustawienia modułu',
	'ACP_PORTAL_MODULE_SPECIFIC_EXPLAIN' => 'Ustawienia specyficzne dla tego typu modułu. Dostępne opcje zależą od modułu (np. zawartość bloku niestandardowego, limity kanału).',
	'ACP_PORTAL_MODULE_UPDATED'       => 'Konfiguracja modułu została zapisana.',
]);
