<?php
/**
 * @package bbGuild Portal
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * Portal language keys (German)
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
	'BBGUILD_PORTAL_MOTD'            => 'Nachricht des Tages',
	'BBGUILD_PORTAL_RECRUITMENT'     => 'Rekrutierung',
	'BBGUILD_PORTAL_ROSTER'          => 'Gildenaufstellung',
	'BBGUILD_PORTAL_STATISTICS'      => 'Gildenstatistik',

	// Module content
	'NO_RECRUITS'                    => 'Keine offenen Rekrutierungspositionen.',
	'BBGUILD_NO_PORTAL_MODULES'      => 'Keine Portalmodule für diese Gilde konfiguriert.',

	// Statistics module
	'STAT_CLASS_DISTRIBUTION'        => 'Klassen',
	'STAT_RACE_DISTRIBUTION'         => 'Rassen',
	'STAT_LEVEL_DISTRIBUTION'        => 'Stufen',
	'STAT_RANK_DISTRIBUTION'         => 'Ränge',
	'STAT_TOTAL_MEMBERS'             => 'Aktive Mitglieder insgesamt: %d',
	'STAT_RECENT_JOINS'              => 'Kürzlich beigetreten',
	'STAT_RECENT_DEPARTURES'         => 'Kürzlich ausgetreten',
	'STAT_NO_DATA'                   => 'Noch keine Daten.',

	// ACP portal management
	'ACP_PORTAL_EXPLAIN'             => 'Portalmodule pro Gilde verwalten. Blöcke hinzufügen, entfernen, sortieren und aktivieren/deaktivieren.',
	'ACP_PORTAL_SELECT_GUILD'        => 'Gilde auswählen',
	'ACP_PORTAL_MODULES'             => 'Portalmodule',
	'ACP_PORTAL_MODULE_NAME'         => 'Modul',
	'ACP_PORTAL_COLUMN'              => 'Spalte',
	'ACP_PORTAL_ORDER'               => 'Reihenfolge',
	'ACP_PORTAL_COLUMN_TOP'          => 'Oben',
	'ACP_PORTAL_COLUMN_CENTER'       => 'Mitte',
	'ACP_PORTAL_COLUMN_RIGHT'        => 'Rechts',
	'ACP_PORTAL_ADD_MODULE'          => 'Modul hinzufügen',
	'ACP_PORTAL_NO_MODULES'          => 'Keine Portalmodule für diese Gilde konfiguriert. Fügen Sie unten eines hinzu.',
	'ACP_PORTAL_MODULE_ADDED'        => 'Portalmodul wurde hinzugefügt.',
	'ACP_PORTAL_MODULE_ADD_FAILED'   => 'Portalmodul konnte nicht hinzugefügt werden. Das Modul ist in der gewählten Spalte möglicherweise nicht erlaubt.',
	'ACP_PORTAL_MODULE_DELETED'      => 'Portalmodul wurde entfernt.',
	'ACP_PORTAL_MOVE_LEFT'           => 'In vorherige Spalte verschieben',
	'ACP_PORTAL_MOVE_RIGHT'          => 'In nächste Spalte verschieben',
	'ACP_PORTAL_COLUMN_BOTTOM'       => 'Unten',

	'ACP_PORTAL_TABS'                => 'Reiter',
	'ACP_PORTAL_TAB'                 => 'Reiter',
	'ACP_PORTAL_TAB_NAME'            => 'Reitername',
	'ACP_PORTAL_TAB_SLUG'            => 'Slug',
	'ACP_PORTAL_TAB_SLUG_EXPLAIN'    => 'Wird in der Seiten-URL verwendet, z.B. <samp>raids</samp>. Nur Buchstaben, Zahlen, Bindestriche und Unterstriche; darf nicht mit einer Zahl beginnen.',
	'ACP_PORTAL_ADD_TAB'             => 'Reiter hinzufügen',
	'ACP_PORTAL_TAB_ADDED'           => 'Reiter wurde hinzugefügt.',
	'ACP_PORTAL_TAB_ADD_FAILED'      => 'Reiter konnte nicht hinzugefügt werden. Ein Reiter mit diesem Slug existiert bereits für diese Gilde.',
	'ACP_PORTAL_TAB_UPDATED'         => 'Reiter wurde aktualisiert.',
	'ACP_PORTAL_TAB_UPDATE_FAILED'   => 'Reiter konnte nicht aktualisiert werden. Ein Reiter mit diesem Slug existiert bereits für diese Gilde.',
	'ACP_PORTAL_TAB_DELETED'         => 'Reiter wurde entfernt.',
	'ACP_PORTAL_TAB_DELETE_FAILED'   => 'Reiter konnte nicht entfernt werden. Eine Gilde muss immer mindestens einen Reiter haben.',

	// Module configuration
	'ACP_PORTAL_MODULE_CONFIG'        => 'Modulkonfiguration',
	'ACP_PORTAL_MODULE_CONFIG_EXPLAIN' => 'Konfiguriere den Anzeigenamen, das Symbol, die Sichtbarkeit und modulspezifische Einstellungen für diesen Portalblock. Änderungen gelten für die Gildenwillkommensseite.',
	'ACP_PORTAL_MODULE_SETTINGS'      => 'Allgemeine Einstellungen',
	'ACP_PORTAL_MODULE_SETTINGS_EXPLAIN' => 'Lege den Anzeigenamen fest und aktiviere oder deaktiviere dieses Modul. Deaktivierte Module werden im Portal ausgeblendet, behalten aber ihre Konfiguration.',
	'ACP_PORTAL_ICON_SETTINGS'        => 'Symbol-Einstellungen',
	'ACP_PORTAL_ICON_SETTINGS_EXPLAIN' => 'Wähle ein Symbol, das neben dem Modultitel im Portal angezeigt wird. Du kannst entweder eine Bilddatei oder ein Font Awesome-Symbol verwenden. Wenn beides gesetzt ist, hat das Font Awesome-Symbol Vorrang.',
	'ACP_PORTAL_IMAGE_SRC'            => 'Bilddatei',
	'ACP_PORTAL_IMAGE_SRC_EXPLAIN'    => 'Relativer Pfad zu einer Bilddatei, z.B. <samp>ext/avathar/bbguild/images/icon.png</samp>.',
	'ACP_PORTAL_FA_ICON'              => 'Font Awesome-Symbol',
	'ACP_PORTAL_FA_ICON_EXPLAIN'      => 'Gib eine Font Awesome 4.7 CSS-Klasse ein, z.B. <samp>fa-star</samp>, <samp>fa-shield</samp>, <samp>fa-users</samp>. Hat Vorrang vor der Bilddatei. Leer lassen, um stattdessen das Bild zu verwenden.',
	'ACP_PORTAL_ICON_SIZE'            => 'Symbolgröße',
	'ACP_PORTAL_ICON_SIZE_EXPLAIN'    => 'Größe des Font Awesome-Symbols in Pixeln (8–64). Gilt nur wenn ein Font Awesome-Symbol gesetzt ist.',
	'ACP_PORTAL_GROUP_ACCESS'         => 'Gruppenzugriff',
	'ACP_PORTAL_GROUP_ACCESS_EXPLAIN' => 'Lege fest, welche Benutzergruppen dieses Modul auf der Portalseite sehen können.',
	'ACP_PORTAL_GROUP_IDS'            => 'Erlaubte Gruppen',
	'ACP_PORTAL_GROUP_IDS_EXPLAIN'    => 'Wähle eine oder mehrere Gruppen. Nur Mitglieder der ausgewählten Gruppen sehen dieses Modul. Leer lassen, um das Modul allen Benutzern zu zeigen.',
	'ACP_PORTAL_MODULE_SPECIFIC'      => 'Moduleinstellungen',
	'ACP_PORTAL_MODULE_SPECIFIC_EXPLAIN' => 'Einstellungen speziell für diesen Modultyp. Verfügbare Optionen hängen vom Modul ab (z.B. benutzerdefinierter Blockinhalt, Feed-Limits).',
	'ACP_PORTAL_MODULE_UPDATED'       => 'Modulkonfiguration wurde gespeichert.',
]);
