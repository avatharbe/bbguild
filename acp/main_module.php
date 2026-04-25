<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * bbGuild Mainpage ACP
 *
 */

namespace avathar\bbguild\acp;

class main_module
{
	public $page_title;
	public $tpl_name;
	public $u_action;

	public function main($id, $mode)
	{
		global $phpbb_container;

		// Get an instance of the admin controller
		$admin_main = $phpbb_container->get('avathar.bbguild.admin.main');

		/** @var \phpbb\language\language $lang */
		$lang = $phpbb_container->get('language');

		// handle requests
		$admin_main->set_u_action($this->u_action);
		$admin_main->handle();

		$this->tpl_name = 'acp_' . $mode;

		switch ($mode)
		{
			case 'panel':
				$this->page_title = $lang->lang('ACP_BBGUILD_MAINPAGE');
				$admin_main->DisplayPanel();
				break;
			case 'config':
				$this->page_title = $lang->lang('ACP_BBGUILD_CONFIG');
				$admin_main->display_config();
				break;
			case 'logs':
				$this->page_title = $lang->lang('ACP_BBGUILD_LOGS');
				$admin_main->listlogs();
		}
	}
}
