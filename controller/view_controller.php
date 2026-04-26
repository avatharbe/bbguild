<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace avathar\bbguild\controller;

use avathar\bbguild\portal\guild_context;
use avathar\bbguild\portal\portal_renderer;
use avathar\bbguild\views\player_detail;

/**
 * Front-end controller for the guild portal and player detail pages.
 */
class view_controller
{
	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var guild_context */
	protected $guild_context;

	/** @var portal_renderer */
	protected $portal_renderer;

	/** @var player_detail */
	protected $player_detail;

	/** @var \phpbb\event\dispatcher_interface */
	protected $dispatcher;

	public function __construct(
		\phpbb\controller\helper $helper,
		\phpbb\template\template $template,
		\phpbb\auth\auth $auth,
		guild_context $guild_context,
		portal_renderer $portal_renderer,
		player_detail $player_detail,
		\phpbb\event\dispatcher_interface $dispatcher
	)
	{
		$this->helper = $helper;
		$this->template = $template;
		$this->auth = $auth;
		$this->guild_context = $guild_context;
		$this->portal_renderer = $portal_renderer;
		$this->player_detail = $player_detail;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Main view handler — builds guild context and renders portal.
	 *
	 * @param  int    $guild_id
	 * @param  string $page  Kept for route compatibility (unused)
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function handleview($guild_id, $page = 'welcome')
	{
		if (!$this->auth->acl_get('u_bbguild'))
		{
			throw new \phpbb\exception\http_exception(403, 'NOT_AUTHORISED');
		}

		$this->guild_context->init((int) $guild_id);
		$this->portal_renderer->render($this->guild_context->guild_id);
		$this->template->assign_vars(['S_DISPLAY_WELCOME' => true]);

		return $this->helper->render('main.html', $this->guild_context->guild->getName());
	}

	/**
	 * Individual player detail page.
	 *
	 * @param  int $guild_id
	 * @param  int $player_id
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function playerdetail($guild_id, $player_id)
	{
		if (!$this->auth->acl_get('u_bbguild'))
		{
			throw new \phpbb\exception\http_exception(403, 'NOT_AUTHORISED');
		}

		$guild_id = (int) $guild_id;
		$player_id = (int) $player_id;

		// Build guild context (header, guild dropdown)
		$this->guild_context->init($guild_id);

		// Load player data
		if (!$this->player_detail->load($player_id, $this->template))
		{
			throw new \phpbb\exception\http_exception(404, 'NO_PLAYER');
		}

		/**
		 * Event dispatched when the individual player detail page is rendered.
		 * Allows game plugins (e.g. bbguildwow) to inject API-specific content
		 * such as gear, talents, achievements or pet collections.
		 *
		 * @event avathar.bbguild.player_detail_display
		 * @var int player_id The player being displayed
		 * @var int guild_id  The guild the player belongs to
		 * @since 2.0.0-b2
		 */
		$vars = ['player_id', 'guild_id'];
		extract($this->dispatcher->trigger_event('avathar.bbguild.player_detail_display', compact($vars)));

		return $this->helper->render('player_detail.html', $this->player_detail->get_player_name());
	}
}
