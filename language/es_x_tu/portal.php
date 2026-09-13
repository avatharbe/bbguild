<?php
/**
 * @package bbGuild Portal
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 *
 * Portal language keys (Spanish)
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
	'BBGUILD_PORTAL_MOTD'            => 'Mensaje del día',
	'BBGUILD_PORTAL_RECRUITMENT'     => 'Reclutamiento',
	'BBGUILD_PORTAL_ROSTER'          => 'Lista de miembros',

	// Module content
	'NO_RECRUITS'                    => 'No hay posiciones de reclutamiento abiertas.',
	'BBGUILD_NO_PORTAL_MODULES'      => 'No hay módulos de portal configurados para este gremio.',

	// ACP portal management
	'ACP_PORTAL_EXPLAIN'             => 'Gestiona los módulos del portal para cada gremio. Añade, elimina, reordena y activa/desactiva bloques.',
	'ACP_PORTAL_SELECT_GUILD'        => 'Seleccionar gremio',
	'ACP_PORTAL_MODULES'             => 'Módulos del portal',
	'ACP_PORTAL_MODULE_NAME'         => 'Módulo',
	'ACP_PORTAL_COLUMN'              => 'Columna',
	'ACP_PORTAL_ORDER'               => 'Orden',
	'ACP_PORTAL_COLUMN_TOP'          => 'Arriba',
	'ACP_PORTAL_COLUMN_CENTER'       => 'Centro',
	'ACP_PORTAL_COLUMN_RIGHT'        => 'Derecha',
	'ACP_PORTAL_ADD_MODULE'          => 'Añadir módulo',
	'ACP_PORTAL_NO_MODULES'          => 'No hay módulos del portal configurados para este gremio. Añade uno a continuación.',
	'ACP_PORTAL_MODULE_ADDED'        => 'Módulo del portal añadido.',
	'ACP_PORTAL_MODULE_ADD_FAILED'   => 'No se pudo añadir el módulo. Es posible que el módulo no esté permitido en la columna seleccionada.',
	'ACP_PORTAL_MODULE_DELETED'      => 'Módulo del portal eliminado.',
	'ACP_PORTAL_MOVE_LEFT'           => 'Mover a columna anterior',
	'ACP_PORTAL_MOVE_RIGHT'          => 'Mover a columna siguiente',
	'ACP_PORTAL_COLUMN_BOTTOM'       => 'Abajo',

	'ACP_PORTAL_TABS'                => 'Pestañas',
	'ACP_PORTAL_TAB'                 => 'Pestaña',
	'ACP_PORTAL_TAB_NAME'            => 'Nombre de la pestaña',
	'ACP_PORTAL_TAB_SLUG'            => 'Slug',
	'ACP_PORTAL_TAB_SLUG_EXPLAIN'    => 'Se usa en la URL de la página, p. ej. <samp>raids</samp>. Solo letras, números, guiones y guiones bajos; no puede empezar por un número.',
	'ACP_PORTAL_ADD_TAB'             => 'Añadir pestaña',
	'ACP_PORTAL_TAB_ADDED'           => 'La pestaña ha sido añadida.',
	'ACP_PORTAL_TAB_ADD_FAILED'      => 'No se pudo añadir la pestaña. Ya existe una pestaña con ese slug para este gremio.',
	'ACP_PORTAL_TAB_UPDATED'         => 'La pestaña ha sido actualizada.',
	'ACP_PORTAL_TAB_UPDATE_FAILED'   => 'No se pudo actualizar la pestaña. Ya existe una pestaña con ese slug para este gremio.',
	'ACP_PORTAL_TAB_DELETED'         => 'La pestaña ha sido eliminada.',
	'ACP_PORTAL_TAB_DELETE_FAILED'   => 'No se pudo eliminar la pestaña. Un gremio siempre debe tener al menos una pestaña.',

	// Module configuration
	'ACP_PORTAL_MODULE_CONFIG'        => 'Configuración del módulo',
	'ACP_PORTAL_MODULE_CONFIG_EXPLAIN' => 'Configura el nombre de visualización, el icono, la visibilidad y los ajustes específicos de este bloque del portal. Los cambios se aplican a la página de bienvenida del gremio.',
	'ACP_PORTAL_MODULE_SETTINGS'      => 'Ajustes generales',
	'ACP_PORTAL_MODULE_SETTINGS_EXPLAIN' => 'Establece el nombre de visualización y activa o desactiva este módulo. Los módulos desactivados se ocultan del portal pero mantienen su configuración.',
	'ACP_PORTAL_ICON_SETTINGS'        => 'Ajustes del icono',
	'ACP_PORTAL_ICON_SETTINGS_EXPLAIN' => 'Elige un icono que se muestra junto al título del módulo en el portal. Puedes usar un archivo de imagen o un icono de Font Awesome. Si ambos están configurados, el icono de Font Awesome tiene prioridad.',
	'ACP_PORTAL_IMAGE_SRC'            => 'Archivo de imagen',
	'ACP_PORTAL_IMAGE_SRC_EXPLAIN'    => 'Ruta relativa a un archivo de imagen, p. ej. <samp>ext/avathar/bbguild/images/icon.png</samp>.',
	'ACP_PORTAL_FA_ICON'              => 'Icono Font Awesome',
	'ACP_PORTAL_FA_ICON_EXPLAIN'      => 'Introduce una clase CSS de Font Awesome 4.7, p. ej. <samp>fa-star</samp>, <samp>fa-shield</samp>, <samp>fa-users</samp>. Tiene prioridad sobre el archivo de imagen. Déjalo vacío para usar la imagen.',
	'ACP_PORTAL_ICON_SIZE'            => 'Tamaño del icono',
	'ACP_PORTAL_ICON_SIZE_EXPLAIN'    => 'Tamaño del icono Font Awesome en píxeles (8–64). Solo se aplica cuando se ha configurado un icono Font Awesome.',
	'ACP_PORTAL_GROUP_ACCESS'         => 'Acceso por grupo',
	'ACP_PORTAL_GROUP_ACCESS_EXPLAIN' => 'Limita qué grupos de usuarios pueden ver este módulo en la página del portal.',
	'ACP_PORTAL_GROUP_IDS'            => 'Grupos permitidos',
	'ACP_PORTAL_GROUP_IDS_EXPLAIN'    => 'Selecciona uno o más grupos. Solo los miembros de los grupos seleccionados verán este módulo. Déjalo vacío para mostrar el módulo a todos los usuarios.',
	'ACP_PORTAL_MODULE_SPECIFIC'      => 'Ajustes del módulo',
	'ACP_PORTAL_MODULE_SPECIFIC_EXPLAIN' => 'Ajustes específicos para este tipo de módulo. Las opciones disponibles dependen del módulo (p. ej. contenido de bloque personalizado, límites de feed).',
	'ACP_PORTAL_MODULE_UPDATED'       => 'La configuración del módulo se ha guardado.',
]);
