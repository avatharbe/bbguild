<?php
/**
 * @package bbGuild Portal
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * Portal language keys (Italian)
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
	'ACP_BBGUILD_PORTAL'             => 'Portale',

	// Module names
	'BBGUILD_PORTAL_MOTD'            => 'Messaggio del giorno',
	'BBGUILD_PORTAL_RECRUITMENT'     => 'Reclutamento',
	'BBGUILD_PORTAL_ROSTER'          => 'Elenco membri',

	// Module content
	'NO_RECRUITS'                    => 'Nessuna posizione di reclutamento aperta.',
	'BBGUILD_NO_PORTAL_MODULES'      => 'Nessun modulo portale configurato per questa gilda.',

	// ACP portal management
	'ACP_PORTAL_EXPLAIN'             => 'Gestisci i moduli del portale per ogni gilda. Aggiungi, rimuovi, riordina e attiva/disattiva i blocchi.',
	'ACP_PORTAL_SELECT_GUILD'        => 'Seleziona gilda',
	'ACP_PORTAL_MODULES'             => 'Moduli del portale',
	'ACP_PORTAL_MODULE_NAME'         => 'Modulo',
	'ACP_PORTAL_COLUMN'              => 'Colonna',
	'ACP_PORTAL_ORDER'               => 'Ordine',
	'ACP_PORTAL_COLUMN_TOP'          => 'Alto',
	'ACP_PORTAL_COLUMN_CENTER'       => 'Centro',
	'ACP_PORTAL_COLUMN_RIGHT'        => 'Destra',
	'ACP_PORTAL_ADD_MODULE'          => 'Aggiungi modulo',
	'ACP_PORTAL_NO_MODULES'          => 'Nessun modulo del portale configurato per questa gilda. Aggiungine uno qui sotto.',
	'ACP_PORTAL_MODULE_ADDED'        => 'Modulo del portale aggiunto.',
	'ACP_PORTAL_MODULE_ADD_FAILED'   => 'Impossibile aggiungere il modulo. Il modulo potrebbe non essere consentito nella colonna selezionata.',
	'ACP_PORTAL_MODULE_DELETED'      => 'Modulo del portale rimosso.',
	'ACP_PORTAL_MOVE_LEFT'           => 'Sposta nella colonna precedente',
	'ACP_PORTAL_MOVE_RIGHT'          => 'Sposta nella colonna successiva',
	'ACP_PORTAL_COLUMN_BOTTOM'       => 'In basso',

	'ACP_PORTAL_TABS'                => 'Schede',
	'ACP_PORTAL_TAB'                 => 'Scheda',
	'ACP_PORTAL_TAB_NAME'            => 'Nome scheda',
	'ACP_PORTAL_TAB_SLUG'            => 'Slug',
	'ACP_PORTAL_TAB_SLUG_EXPLAIN'    => 'Utilizzato nell\'URL della pagina, es. <samp>raids</samp>. Solo lettere, numeri, trattini e underscore.',
	'ACP_PORTAL_ADD_TAB'             => 'Aggiungi scheda',
	'ACP_PORTAL_TAB_ADDED'           => 'La scheda è stata aggiunta.',
	'ACP_PORTAL_TAB_ADD_FAILED'      => 'Impossibile aggiungere la scheda. Esiste già una scheda con questo slug per questa gilda.',
	'ACP_PORTAL_TAB_UPDATED'         => 'La scheda è stata aggiornata.',
	'ACP_PORTAL_TAB_UPDATE_FAILED'   => 'Impossibile aggiornare la scheda. Esiste già una scheda con questo slug per questa gilda.',
	'ACP_PORTAL_TAB_DELETED'         => 'La scheda è stata rimossa.',
	'ACP_PORTAL_TAB_DELETE_FAILED'   => 'Impossibile rimuovere la scheda. Una gilda deve avere sempre almeno una scheda.',

	// Module configuration
	'ACP_PORTAL_MODULE_CONFIG'        => 'Configurazione modulo',
	'ACP_PORTAL_MODULE_CONFIG_EXPLAIN' => 'Configura il nome visualizzato, l\'icona, la visibilità e le impostazioni specifiche per questo blocco del portale. Le modifiche si applicano alla pagina di benvenuto della gilda.',
	'ACP_PORTAL_MODULE_SETTINGS'      => 'Impostazioni generali',
	'ACP_PORTAL_MODULE_SETTINGS_EXPLAIN' => 'Imposta il nome visualizzato e abilita o disabilita questo modulo. I moduli disabilitati sono nascosti dal portale ma mantengono la loro configurazione.',
	'ACP_PORTAL_ICON_SETTINGS'        => 'Impostazioni icona',
	'ACP_PORTAL_ICON_SETTINGS_EXPLAIN' => 'Scegli un\'icona visualizzata accanto al titolo del modulo nel portale. Puoi usare un file immagine o un\'icona Font Awesome. Se entrambi sono impostati, l\'icona Font Awesome ha la priorità.',
	'ACP_PORTAL_IMAGE_SRC'            => 'File immagine',
	'ACP_PORTAL_IMAGE_SRC_EXPLAIN'    => 'Percorso relativo a un file immagine, es. <samp>ext/avathar/bbguild/images/icon.png</samp>.',
	'ACP_PORTAL_FA_ICON'              => 'Icona Font Awesome',
	'ACP_PORTAL_FA_ICON_EXPLAIN'      => 'Inserisci una classe CSS Font Awesome 4.7, es. <samp>fa-star</samp>, <samp>fa-shield</samp>, <samp>fa-users</samp>. Ha la priorità sul file immagine. Lascia vuoto per usare l\'immagine.',
	'ACP_PORTAL_ICON_SIZE'            => 'Dimensione icona',
	'ACP_PORTAL_ICON_SIZE_EXPLAIN'    => 'Dimensione dell\'icona Font Awesome in pixel (8–64). Si applica solo quando è impostata un\'icona Font Awesome.',
	'ACP_PORTAL_GROUP_ACCESS'         => 'Accesso per gruppo',
	'ACP_PORTAL_GROUP_ACCESS_EXPLAIN' => 'Limita quali gruppi di utenti possono vedere questo modulo nella pagina del portale.',
	'ACP_PORTAL_GROUP_IDS'            => 'Gruppi autorizzati',
	'ACP_PORTAL_GROUP_IDS_EXPLAIN'    => 'Seleziona uno o più gruppi. Solo i membri dei gruppi selezionati vedranno questo modulo. Lascia vuoto per mostrare il modulo a tutti gli utenti.',
	'ACP_PORTAL_MODULE_SPECIFIC'      => 'Impostazioni modulo',
	'ACP_PORTAL_MODULE_SPECIFIC_EXPLAIN' => 'Impostazioni specifiche per questo tipo di modulo. Le opzioni disponibili dipendono dal modulo (es. contenuto blocco personalizzato, limiti feed).',
	'ACP_PORTAL_MODULE_UPDATED'       => 'La configurazione del modulo è stata salvata.',
]);
