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
	'ACTIVE' => 'Activo',
	'ALL' => 'Todos',
	'ARMORY' => 'Armería',
	'GAME' => 'Juego',
	'INACTIVE' => 'Inactivo',
	'PLUGIN_DISABLED' => 'Deshabilitado (plugin no habilitado)',
	'LINKED_USER' => 'Cuenta del Foro',
	'NO_PLAYER' => 'Jugador no encontrado.',
	'ROLE' => 'Rol',
	'BBGUILDDISABLED' => 'bbGuild está actualmente deshabilitado.',
	'FOOTERBBGUILD' => 'bbGuild',

	// Portal Blocks
	'PORTAL' => 'Portal',
	'REMEMBERME' => 'Recuérdame',
	'INFORUM' => 'Publicado en',
	'BBGUILD' => 'Hermandad',
	'NEWS' => 'Noticias',
	'COMMENT' => 'Comentario',
	'LIST_NEWS' => 'Listar noticias',
	'NO_NEWS' => 'No se encontraron noticias.',
	'NEWS_PER_PAGE' => 'Noticias por página',
	'ERROR_INVALID_NEWS_PROVIDED' => 'No se proporcionó un ID de noticia válido.',
	'BOSSPROGRESS' => 'Progreso de jefes',
	'WELCOME' => 'Bienvenido',
	'RECENT_LENGTH' => 'Número de caracteres obtenidos',
	'NUMTOPICS' => 'Número de temas obtenidos',
	'SHOW_RT_BLOCK' => 'Mostrar temas recientes',
	'RECENT_TOPICS_SETTING' => 'Ajustes de temas recientes',
	'RECENT_TOPICS' => 'Temas recientes',
	'NO_RECENT_TOPICS' => 'No hay temas recientes',
	'POSTED_BY_ON' => 'por %1$s el %2$s',
	'LATESTPLAYERS' => 'Últimos jugadores',

	// Main Menu
	'MENU' => 'Navegación',
	'MENU_WELCOME' => 'Bienvenida',
	'MENU_ROSTER' => 'Plantilla',
	'MENU_NEWS' => 'Noticias',
	'MENU_RAIDS' => 'Bandas',
	'MENU_STATS' => 'Estadísticas',
	'MENU_PLAYER' => 'Jugador',
	'MENU_ACHIEVEMENTS' => 'Logros',

	// Games
	'WOW'        => 'World of Warcraft',
	'EQ'         => 'EverQuest',
	'EQ2'        => 'EverQuest II',
	'FFXI'       => 'Final Fantasy XI',
	'LINEAGE2'   => 'Lineage 2',
	'LOTRO'      => 'Lord of the Rings Online',
	'SWTOR'      => 'Starwars : The old Republic',
	'FFXIV'      => 'Final Fantasy XIV',
	'PREINSTALLED' => 'Plugins de juegos disponibles: %s',

	// Recruitment
	'RECRUITMENT_BLOCK' => 'Estado de reclutamiento',
	'RECRUIT_CLOSED' => 'Cerrado',
	'RECRUIT_OPEN' => 'Abierto',
	'TANK' => 'Tanque',
	'DPS' => 'DPS',
	'HEAL' => 'Sanación',
	'HEALER' => 'Sanador',
	'RECRUIT_MESSAGE' => 'Actualmente estamos buscando nuevos jugadores para las siguientes clases:',

	// Roster
	'GUILDROSTER' => 'Plantilla de la hermandad',
	'RANK'   => 'Rango',
	'CLASS'   => 'Clase',
	'LVL'   => 'Nivel',
	'REALM'  => 'Reino',
	'REGION'  => 'Región',
	'ACHIEV'  => 'Logros',
	'PROFFESSION' => 'Profesiones',

	// Player List
	'FILTER' => 'Filtro',
	'LASTRAID' => 'Última banda',
	'LEVEL' => 'Nivel',
	'LISTPLAYERS_TITLE' => 'Clasificación',
	'MNOTFOUND' => 'No se pudo obtener la información del jugador',
	'RNOTFOUND' => 'No se pudo obtener la información de la banda',
	'EMPTYRAIDNAME' => 'Nombre de banda no encontrado',
	'NAME' => 'Nombre',
	'SURNAME' => 'Apellido/Título',
	'LISTPLAYERS_FOOTCOUNT' => '... se encontraron %d jugadores',
	'LOGIN_TITLE' => 'Iniciar sesión',
	'NOUCPACCESS' => 'No tienes autorización para reclamar personajes',
	'NOUCPADDCHARS' => 'No tienes autorización para añadir personajes',
	'NOUCPUPDCHARS' => 'No tienes autorización para actualizar tus personajes',
	'NOUCPDELCHARS' => 'No tienes autorización para eliminar tus personajes',

	// Common Labels
	'ACCOUNT' => 'Cuenta',
	'ACTION' => 'Acción',
	'ACHIEVED' => 'obtuvo el logro ',
	'ADD' => 'Añadir',
	'ADDED_BY' => 'Añadido por %s',
	'ADMINISTRATION' => 'Administración',
	'ADMINISTRATIVE_OPTIONS' => 'Opciones administrativas',
	'ADMIN_INDEX' => 'Índice de administración',
	'ATTENDED' => 'Asistió',
	'ATTENDEES' => 'Asistentes',
	'ATTENDANCE' => 'Asistencia',
	'ATT' => 'Asist.',
	'AVERAGE' => 'Promedio',
	'BOSS' => 'Jefe',
	'ARMOR' => 'Armadura',
	'STATS_SOCIAL' => '< 20% Asistencia',
	'STATS_RAIDER' => '< 50% Asistencia',
	'STATS_CORERAIDER' => '> 70% Asistencia',

	// Armor Types
	'CLOTH' => 'Muy ligera / Tela',
	'ROBE' => 'Túnicas',
	'LEATHER' => 'Ligera / Cuero',
	'AUGMENTED' => 'Traje reforzado',
	'MAIL' =>  'Media / Malla',
	'HEAVY' => 'Armadura pesada',
	'PLATE' => 'Pesada / Placas',

	// Class & Race Labels
	'CLASSID' => 'ID de clase',
	'CLASS_FACTOR' => 'Factor de clase',
	'CLASSARMOR' => 'Armadura de clase',
	'CLASSIMAGE' => 'Imagen',
	'CLASSMIN' => 'Nivel mínimo',
	'CLASSMAX' => 'Nivel máximo',
	'CLASS_DISTRIBUTION' => 'Distribución de clases',
	'CLASS_SUMMARY' => 'Resumen de clases: %s a %s',
	'CONFIGURATION' => 'Configuración',
	'DATE' => 'Fecha',
	'DELETE' => 'Eliminar',
	'DELETE_CONFIRMATION' => 'Confirmación de eliminación',

	// Character Management
	'NO_CHARACTERS' => 'No hay personajes en la base de datos',
	'STATUS' => 'Estado S/N',
	'CHARACTER' => 'Aquí tienes una lista de todos tus personajes. ',
	'CHARACTER_EXPLAIN' => 'Elige un personaje no reclamado para reclamarlo y pulsa enviar.',
	'CHARACTERS_UPDATED' => 'El personaje %s fue asignado a tu cuenta. ',
	'CLAIM_PLAYER' => 'Reclamar personaje',
	'CLAIM' => 'Reclamar',
	'UNCLAIM' => 'Liberar',
	'CHARACTER_UNCLAIMED' => 'El personaje %s ha sido eliminado de tu cuenta.',
	'CHARACTER_UNCLAIM_FAILED' => 'No se pudo liberar este personaje.',
	'CONFIRM_UNCLAIM_PLAYER' => '¿Estás seguro de que quieres liberar %s?',
	'MY_CHARACTERS' => 'Mis Personajes',
	'NO_PLAYERS_FOUND' => 'No se encontraron personajes.',
	'NO_CHARACTERS_BOUND' => 'No hay personajes vinculados a tu cuenta.',

	// Entity Labels
	'EVENT' => 'Evento',
	'EVENTNAME' => 'Nombre del evento',
	'EVENTS' => 'Eventos',
	'FACTION' => 'Facción',
	'FACTIONID' => 'ID de facción',
	'FIRST' => 'Primero',
	'HIGH' => 'Alto',
	'JOINDATE' => 'Fecha de ingreso a la hermandad',
	'LAST' => 'Último',
	'LAST_VISIT' => 'Última visita',
	'LAST_UPDATE' => 'Última actualización',
	'LOG_DATE_TIME' => 'Fecha/Hora de este registro',
	'LOW' => 'Bajo',
	'MANAGE' => 'Gestionar',
	'MEDIUM' => 'Medio',
	'MEMBERS' => 'Miembros',
	'PLAYER' => 'Jugador',
	'PLAYERS' => 'Jugadores',
	'NA' => 'N/D',
	'NO_DATA' => 'Sin datos',
	'MAX_CHARS_EXCEEDED' => 'Lo siento, solo puedes tener %s personajes vinculados a tu cuenta de phpBB.',
	'MISCELLANEOUS' => 'Varios',
	'NEWEST' => 'Banda más reciente',
	'NOTE' => 'Nota',
	'OLDEST' => 'Banda más antigua',
	'OPEN' => 'Abierto',
	'OPTIONS' => 'Opciones',
	'OUTDATE' => 'Fecha de salida de la hermandad',
	'PERCENT' => 'Porcentaje',
	'PERMISSIONS' => 'Permisos',
	'PREFERENCES' => 'Preferencias',
	'QUOTE' => 'Cita',
	'RACE' => 'Raza',
	'RACEID' => 'ID de raza',
	'RAIDSTART' => 'Inicio de banda',
	'RAIDEND' => 'Fin de banda',
	'RAIDDURATION' => 'Duración',
	'RAID' => 'Banda',
	'RAIDCOUNT' => 'Número de bandas',
	'RAIDS' => 'Bandas',
	'RAID_ID' => 'ID de banda',
	'RANK_DISTRIBUTION' => 'Distribución de rangos',
	'REASON' => 'Motivo',
	'RESULT' => 'Resultado',
	'SESSION_ID' => 'ID de sesión',
	'SUMMARY_DATES' => 'Resumen de bandas: %s a %s',
	'TIME' => 'Hora',
	'TOTAL' => 'Total',
	'TYPE' => 'Tipo',
	'UPDATE' => 'Actualizar',
	'UPDATED_BY' => 'Actualizado por %s',
	'USER' => 'Usuario',
	'USERNAME' => 'Nombre de usuario',
	'VALUE' => 'Valor',
	'VIEW' => 'Ver',
	'VIEW_ACTION' => 'Ver acción',
	'VIEW_LOGS' => 'Ver registros',
	'APPLICANTS' => 'Solicitantes',
	'POSITIONS' => 'Puestos',

	// Form Elements
	'ENDING_DATE' => 'Fecha de fin',
	'GUILD_TAG' => 'Etiqueta de hermandad',
	'LANGUAGE' => 'Idioma',
	'STARTING_DATE' => 'Fecha de inicio',
	'TO' => 'Hasta',
	'ENTER_NEW' => 'Introduce un nuevo nombre',

	// Pagination
	'NEXT_PAGE' => 'Página siguiente',
	'PAGE' => 'Página',
	'PREVIOUS_PAGE' => 'Página anterior',

	// Permission Messages
	'NOAUTH_DEFAULT_TITLE' => 'Permiso denegado',
	'NOAUTH_U_PLAYER_LIST' => 'No tienes permiso para ver la clasificación de jugadores.',
	'NOAUTH_U_PLAYER_VIEW' => 'No tienes permiso para ver el historial de jugadores.',
	'NOAUTH_U_RAID_LIST' => 'No tienes permiso para listar las bandas.',
	'NOAUTH_U_RAID_VIEW' => 'No tienes permiso para ver las bandas.',

	// Miscellaneous
	'DEACTIVATED_BY_USR' => 'Desactivado por el usuario',
	'ADDED' => 'Añadido',
	'CLOSED' => 'Cerrado',
	'DELETED' => 'Eliminado',
	'FEMALE' => 'Femenino',
	'GENDER' => 'Género',
	'GUILD' => 'Hermandad',
	'LIST' => 'Lista',
	'LIST_PLAYERS' => 'Listar jugadores',
	'MALE' => 'Masculino',
	'NOT_AVAILABLE' => 'No disponible',
	'NORAIDS' => 'Sin bandas',
	'OR' => 'o',
	'REQUIRED_FIELD_NOTE' => 'Los campos marcados con * son obligatorios.',
	'UPDATED' => 'Actualizado',
	'NOVIEW' => 'Nombre de vista desconocido %s',

	// About Page
	'ABOUT' => 'Acerca de',
	'MAINIMG' => 'bbguild.png',
	'IMAGE_ALT' => 'Logo',
	'REPOSITORY_IMAGE' => 'Google.jpg',
	'TCOPYRIGHT' => 'Copyright',
	'TCREDITS' => 'Créditos',
	'TEAM' => 'Equipo de desarrollo',
	'TSPONSORS' => 'Donantes',
	'TPLUGINS' => 'Plugins',
	'CREATED' => 'Creado por',
	'DEVELOPEDBY' => 'Desarrollado por',
	'DEVTEAM' => 'Equipo de desarrollo de bbGuild',
	'AUTHNAME' => 'Ippeh',
	'WEBNAME' =>'Sitio web',
	'SVNNAME' => 'Repositorio',
	'SVNURL' => 'https://github.com/avatharbe/bbguild',
	'WEBURL' => 'http://www.avathar.be/bbdkp',
	'AUTHWEB' => 'http://www.avathar.be/bbdkp/',
	'LICENSE1' => 'bbGuild es software libre: puedes redistribuirlo y/o modificarlo
   bajo los términos de la Licencia Pública General de GNU publicada por
   la Free Software Foundation, ya sea la versión 3 de la Licencia, o
   (a tu elección) cualquier versión posterior.

   bbGuild se distribuye con la esperanza de que sea útil,
   pero SIN NINGUNA GARANTÍA; sin siquiera la garantía implícita de
   COMERCIABILIDAD o IDONEIDAD PARA UN PROPÓSITO PARTICULAR. Consulta la
   Licencia Pública General de GNU para más detalles.

   Deberías haber recibido una copia de la Licencia Pública General de GNU
   junto con bbGuild. Si no es así, consulta http://www.gnu.org/licenses',
	'LICENSE2' => 'Funciona con bbDKP (c) 2009 The bbDKP Project Team. Si utilizas este software y lo encuentras útil, te pedimos que conserves el aviso de copyright que aparece a continuación. Aunque no es obligatorio para el uso gratuito, ayudará a generar interés en el proyecto bbDKP y es <strong>necesario para obtener soporte</strong>.',
	'COPYRIGHT3' => 'bbDKP (c) 2010 Sajaki, Malfate, Blazeflack <br />
bbDKP (c) 2008, 2009 Sajaki, Malfate, Kapli, Hroar',
	'COPYRIGHT2' => 'bbDKP (c) 2007 Ippeh, Teksonic, Monkeytech, DWKN',
	'COPYRIGHT1' => 'EQDkp (c) 2003 The EqDkp Project Team ',
	'PRODNAME' => 'Producto',
	'VERSION' => 'Versión',
	'DEVELOPER' => 'Desarrollador',
	'JOB' => 'Función',
	'DEVLINK' => 'Enlace',
	'PROD' => 'bbGuild',
	'DEVELOPERS' => '<a href=mailto:sajaki@avathar.be>Sajaki</a>',
	'PHPBB' => 'phpBB',
	'PHPBBGR' => 'phpBB Group',
	'PHPBBLINK' => 'http://www.phpbb.com',
	'EQDKP' => 'EQDKP original',
	'EQDKPVERS' => '1.3.2',
	'EQDKPDEV' => 'Tsigo',
	'EQDKPLINK' => 'http://www.eqdkp.com/',
	'PLUGINS' => 'Plugins',
	'PLUGINVERS' => 'Versión',
	'AUTHOR' => 'Autor',
	'MAINT' => 'Mantenedor',
	'DONATION' => 'Donación',
	'DONA_NAME' => 'Nombre',
	'ADDITIONS' => 'Adiciones de código',
	'CONTRIB' => 'Contribuciones',

	// is_enableable() error messages
	'BBGUILD_PHP_VERSION_FAIL'		=> 'Esta extensión requiere PHP %1$s o superior. Estás ejecutando PHP %2$s.',
	'BBGUILD_PHPBB_VERSION_FAIL'	=> 'Esta extensión requiere phpBB %1$s o superior. Estás ejecutando phpBB %2$s.',
	'BBGUILD_REQUIRES_GD'			=> 'Esta extensión requiere que la extensión PHP GD esté cargada.',
	'BBGUILD_REQUIRES_CURL'			=> 'Esta extensión requiere que la extensión PHP cURL esté cargada.',

	)
);
