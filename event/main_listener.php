<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace avathar\bbguild\event;

use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\db\driver\driver_interface;
use phpbb\template\template;
use phpbb\user;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class main_listener implements EventSubscriberInterface
{
	/* @var helper */
	protected $helper;

	/* @var template */
	protected $template;

	/* @var user */
	protected $user;

	/* @var config */
	protected $config;

	/* @var driver_interface */
	protected $db;

	/* @var string */
	protected $guild_table;

	/**
	 * main_listener constructor.
	 */
	public function __construct(helper $helper,
		template $template,
		user $user,
		config $config,
		driver_interface $db,
		string $guild_table
	)
	{
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->config = $config;
		$this->db = $db;
		$this->guild_table = $guild_table;
	}


	/**
	 * Assign functions defined in this class to event listeners in the core
	 *
	 * @return array
	 */
	static public function getSubscribedEvents()
	{
		return array(
			// for all defined events, write a function below
			'core.common'                           => 'global_calls',
			'core.user_setup'                        => 'load_language_on_setup',
			'core.page_header'                        => 'add_page_header_link',
			'core.permissions'                        => 'add_permission_cat',
		);
	}
	/**
	 * core.common
	 * Handles logic that needs to be called on every page.
	 *
	 * @param array $event Array containing situational data.
	 */
	public function global_calls($event)
	{
		// Assign global template vars.
		$this->template->assign_vars(
			array(
			'S_BBGUILD_ENABLED'   => true,
			)
		);
	}

	/**
	 * core.user_setup
	 *
	 * @param $event
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'avathar/bbguild',
			'lang_set' => array('common', 'admin', 'portal'),
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}


	/**
	 * core.page_header
	 *
	 * @param $event
	 */
	public function add_page_header_link($event)
	{
		$sql = 'SELECT id, name FROM ' . $this->guild_table . ' WHERE id > 0 ORDER BY name ASC';
		$result = $this->db->sql_query($sql);

		$guilds = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$guilds[] = $row;
		}
		$this->db->sql_freeresult($result);

		// First guild is the default link
		$first_guild_id = !empty($guilds) ? (int) $guilds[0]['id'] : 1;

		$this->template->assign_vars([
			'U_GUILD'           => $this->helper->route('avathar_bbguild_00', [
				'guild_id' => $first_guild_id,
				'page'     => 'welcome',
			]),
			'S_MULTI_GUILD'     => count($guilds) > 1,
		]);

		foreach ($guilds as $row)
		{
			$this->template->assign_block_vars('guild_nav', [
				'NAME'  => $row['name'],
				'U_GUILD' => $this->helper->route('avathar_bbguild_00', [
					'guild_id' => (int) $row['id'],
					'page'     => 'welcome',
				]),
			]);
		}
	}


	/**
	 * bbGuild permission category
	 *
	 * @param $event
	 */
	public function add_permission_cat($event)
	{
		$perm_cat = $event['categories'];
		$perm_cat['bbguild'] = 'ACP_BBGUILD';
		$event['categories'] = $perm_cat;

		$permission = $event['permissions'];
		$permission['a_bbguild']    = array('lang' => 'ACL_A_BBGUILD',        'cat' => 'bbguild');
		$permission['u_bbguild']    = array('lang' => 'ACL_U_BBGUILD',        'cat' => 'bbguild');
		$permission['u_charclaim']    = array('lang' => 'ACL_U_CHARCLAIM',    'cat' => 'bbguild');
		$permission['u_charadd']    = array('lang' => 'ACL_U_CHARADD',        'cat' => 'bbguild');
		$permission['u_chardelete']    = array('lang' => 'ACL_U_CHARDELETE',    'cat' => 'bbguild');
		$permission['u_charupdate']    = array('lang' => 'ACL_U_CHARUPDATE',    'cat' => 'bbguild');
		$event['permissions'] = $permission;
	}

}
