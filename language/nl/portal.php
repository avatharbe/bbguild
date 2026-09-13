<?php
/**
 * @package bbGuild Portal
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * Portal language keys (Dutch)
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
	'ACP_BBGUILD_PORTAL'             => 'Portaal',

	// Module names
	'BBGUILD_PORTAL_MOTD'            => 'Bericht van de dag',
	'BBGUILD_PORTAL_RECRUITMENT'     => 'Rekrutering',
	'BBGUILD_PORTAL_ROSTER'          => 'Gildeleden',

	// Module content
	'NO_RECRUITS'                    => 'Geen open rekruteringsposities.',
	'BBGUILD_NO_PORTAL_MODULES'      => 'Geen portaalmodules geconfigureerd voor deze gilde.',

	// ACP portal management
	'ACP_PORTAL_EXPLAIN'             => 'Beheer portaalmodules per gilde. Voeg toe, verwijder, herorden en schakel blokken in/uit.',
	'ACP_PORTAL_SELECT_GUILD'        => 'Selecteer gilde',
	'ACP_PORTAL_MODULES'             => 'Portaalmodules',
	'ACP_PORTAL_MODULE_NAME'         => 'Module',
	'ACP_PORTAL_COLUMN'              => 'Kolom',
	'ACP_PORTAL_ORDER'               => 'Volgorde',
	'ACP_PORTAL_COLUMN_TOP'          => 'Boven',
	'ACP_PORTAL_COLUMN_CENTER'       => 'Midden',
	'ACP_PORTAL_COLUMN_RIGHT'        => 'Rechts',
	'ACP_PORTAL_ADD_MODULE'          => 'Module toevoegen',
	'ACP_PORTAL_NO_MODULES'          => 'Geen portaalmodules geconfigureerd voor deze gilde. Voeg er hieronder een toe.',
	'ACP_PORTAL_MODULE_ADDED'        => 'Portaalmodule is toegevoegd.',
	'ACP_PORTAL_MODULE_ADD_FAILED'   => 'Portaalmodule kon niet worden toegevoegd. De module is mogelijk niet toegestaan in de geselecteerde kolom.',
	'ACP_PORTAL_MODULE_DELETED'      => 'Portaalmodule is verwijderd.',
	'ACP_PORTAL_MOVE_LEFT'           => 'Verplaats naar vorige kolom',
	'ACP_PORTAL_MOVE_RIGHT'          => 'Verplaats naar volgende kolom',
	'ACP_PORTAL_COLUMN_BOTTOM'       => 'Onder',

	'ACP_PORTAL_TABS'                => 'Tabbladen',
	'ACP_PORTAL_TAB'                 => 'Tabblad',
	'ACP_PORTAL_TAB_NAME'            => 'Tabbladnaam',
	'ACP_PORTAL_TAB_SLUG'            => 'Slug',
	'ACP_PORTAL_TAB_SLUG_EXPLAIN'    => 'Wordt gebruikt in de pagina-URL, bijv. <samp>raids</samp>. Alleen letters, cijfers, koppeltekens en underscores.',
	'ACP_PORTAL_ADD_TAB'             => 'Tabblad toevoegen',
	'ACP_PORTAL_TAB_ADDED'           => 'Tabblad is toegevoegd.',
	'ACP_PORTAL_TAB_ADD_FAILED'      => 'Tabblad kon niet worden toegevoegd. Er bestaat al een tabblad met deze slug voor deze gilde.',
	'ACP_PORTAL_TAB_UPDATED'         => 'Tabblad is bijgewerkt.',
	'ACP_PORTAL_TAB_UPDATE_FAILED'   => 'Tabblad kon niet worden bijgewerkt. Er bestaat al een tabblad met deze slug voor deze gilde.',
	'ACP_PORTAL_TAB_DELETED'         => 'Tabblad is verwijderd.',
	'ACP_PORTAL_TAB_DELETE_FAILED'   => 'Tabblad kon niet worden verwijderd. Een gilde moet altijd minstens één tabblad hebben.',

	// Module configuration
	'ACP_PORTAL_MODULE_CONFIG'        => 'Moduleconfiguratie',
	'ACP_PORTAL_MODULE_CONFIG_EXPLAIN' => 'Configureer de weergavenaam, het icoon, de zichtbaarheid en modulespecifieke instellingen voor dit portaalblok. Wijzigingen zijn van toepassing op de gildewelkomstpagina.',
	'ACP_PORTAL_MODULE_SETTINGS'      => 'Algemene instellingen',
	'ACP_PORTAL_MODULE_SETTINGS_EXPLAIN' => 'Stel de weergavenaam in en schakel deze module in of uit. Uitgeschakelde modules zijn verborgen op het portaal maar behouden hun configuratie.',
	'ACP_PORTAL_ICON_SETTINGS'        => 'Icooninstellingen',
	'ACP_PORTAL_ICON_SETTINGS_EXPLAIN' => 'Kies een icoon dat naast de moduletitel op het portaal wordt weergegeven. Je kunt een afbeelding of een Font Awesome-icoon gebruiken. Als beide zijn ingesteld, heeft het Font Awesome-icoon voorrang.',
	'ACP_PORTAL_IMAGE_SRC'            => 'Afbeeldingsbestand',
	'ACP_PORTAL_IMAGE_SRC_EXPLAIN'    => 'Relatief pad naar een afbeeldingsbestand, bijv. <samp>ext/avathar/bbguild/images/icon.png</samp>.',
	'ACP_PORTAL_FA_ICON'              => 'Font Awesome-icoon',
	'ACP_PORTAL_FA_ICON_EXPLAIN'      => 'Voer een Font Awesome 4.7 CSS-klasse in, bijv. <samp>fa-star</samp>, <samp>fa-shield</samp>, <samp>fa-users</samp>. Heeft voorrang boven het afbeeldingsbestand. Laat leeg om het afbeeldingsbestand te gebruiken.',
	'ACP_PORTAL_ICON_SIZE'            => 'Icoongrootte',
	'ACP_PORTAL_ICON_SIZE_EXPLAIN'    => 'Grootte van het Font Awesome-icoon in pixels (8–64). Alleen van toepassing wanneer een Font Awesome-icoon is ingesteld.',
	'ACP_PORTAL_GROUP_ACCESS'         => 'Groepstoegang',
	'ACP_PORTAL_GROUP_ACCESS_EXPLAIN' => 'Beperk welke gebruikersgroepen deze module op de portaalpagina kunnen zien.',
	'ACP_PORTAL_GROUP_IDS'            => 'Toegestane groepen',
	'ACP_PORTAL_GROUP_IDS_EXPLAIN'    => 'Selecteer een of meer groepen. Alleen leden van de geselecteerde groepen zien deze module. Laat leeg om de module aan alle gebruikers te tonen.',
	'ACP_PORTAL_MODULE_SPECIFIC'      => 'Module-instellingen',
	'ACP_PORTAL_MODULE_SPECIFIC_EXPLAIN' => 'Instellingen specifiek voor dit moduletype. Beschikbare opties zijn afhankelijk van de module (bijv. aangepaste blokinhoud, feedlimieten).',
	'ACP_PORTAL_MODULE_UPDATED'       => 'Moduleconfiguratie is opgeslagen.',
]);
