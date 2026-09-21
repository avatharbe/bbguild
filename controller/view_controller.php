<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace avathar\bbguild\controller;

use avathar\bbguild\ext;
use avathar\bbguild\model\games\player_detail_tab_registry;
use avathar\bbguild\portal\guild_context;
use avathar\bbguild\portal\portal_renderer;
use avathar\bbguild\views\player_detail;
use phpbb\language\language;

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

	/** @var language */
	protected $language;

	/** @var player_detail_tab_registry */
	protected $player_detail_tab_registry;

	public function __construct(
		\phpbb\controller\helper $helper,
		\phpbb\template\template $template,
		\phpbb\auth\auth $auth,
		guild_context $guild_context,
		portal_renderer $portal_renderer,
		player_detail $player_detail,
		\phpbb\event\dispatcher_interface $dispatcher,
		language $language,
		player_detail_tab_registry $player_detail_tab_registry
	)
	{
		$this->helper = $helper;
		$this->template = $template;
		$this->auth = $auth;
		$this->guild_context = $guild_context;
		$this->portal_renderer = $portal_renderer;
		$this->player_detail = $player_detail;
		$this->dispatcher = $dispatcher;
		$this->language = $language;
		$this->player_detail_tab_registry = $player_detail_tab_registry;
	}

	/**
	 * Main view handler — builds guild context and renders portal.
	 *
	 * @param  int    $guild_id
	 * @param  string $page  Tab slug to render (falls back to the guild's default tab)
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function handleview($guild_id, $page = 'welcome')
	{
		if (!$this->auth->acl_get('u_bbguild'))
		{
			throw new \phpbb\exception\http_exception(403, 'NOT_AUTHORISED');
		}

		$this->guild_context->init((int) $guild_id);
		$this->portal_renderer->render($this->guild_context->guild_id, (string) $page);
		$this->template->assign_vars(['S_DISPLAY_WELCOME' => true]);

		// Same top-of-page nav bar as the player detail page (bbguild#379),
		// with "Guild" as the (only, always active) entry here -- the
		// Character/Talents/etc. entries are resolved per-player and have
		// no single character to point to from this page.
		$this->template->assign_block_vars('nav_tabs', [
			'TAB_NAME'   => $this->language->lang('PLAYER_TAB_GUILD'),
			'TAB_SLUG'   => '',
			'TAB_ACTIVE' => true,
			'U_TAB'      => $this->helper->route('avathar_bbguild_guild', [
				'guild_id' => $this->guild_context->guild_id,
			]),
		]);

		return $this->helper->render('main.html', $this->guild_context->guild->getName());
	}

	/**
	 * Individual player detail page.
	 *
	 * @param  int    $guild_id
	 * @param  int    $player_id
	 * @param  string $tab_slug Tab slug to render (empty resolves to the built-in Character tab)
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function playerdetail($guild_id, $player_id, $tab_slug = '')
	{
		if (!$this->auth->acl_get('u_bbguild'))
		{
			throw new \phpbb\exception\http_exception(403, 'NOT_AUTHORISED');
		}

		$guild_id = (int) $guild_id;
		$player_id = (int) $player_id;
		$tab_slug = (string) $tab_slug;

		// Build guild context (header, guild dropdown)
		$this->guild_context->init($guild_id);
		$this->portal_renderer->render_tab_bar($guild_id);

		// Load player data
		if (!$this->player_detail->load($player_id, $this->template))
		{
			throw new \phpbb\exception\http_exception(404, 'NO_PLAYER');
		}

		// Resolve player-detail sub-tabs (Character is core's built-in
		// default; anything else comes from a registered tab provider)
		$game_id = $this->player_detail->get_game_id();
		$available_tabs = $this->player_detail_tab_registry->get_available_tabs($player_id, $game_id);
		$active_tab = $tab_slug !== '' ? $this->player_detail_tab_registry->find($tab_slug, $player_id, $game_id) : null;

		// Not a player-detail sub-tab like the ones below -- a plain nav
		// link back to the guild's own portal page (bbguild#379), so it's
		// hardcoded here rather than going through player_detail_tab_interface
		// (that system is for game-plugin content rendered inline via
		// render()/S_PLAYER_TAB_TEMPLATE, not external navigation) and is
		// never itself the "active" tab on this page.
		$this->template->assign_block_vars('nav_tabs', [
			'TAB_NAME'   => $this->language->lang('PLAYER_TAB_GUILD'),
			'TAB_SLUG'   => '',
			'TAB_ACTIVE' => false,
			'U_TAB'      => $this->helper->route('avathar_bbguild_guild', [
				'guild_id' => $guild_id,
			]),
		]);

		$this->template->assign_block_vars('nav_tabs', [
			'TAB_NAME'   => $this->language->lang('PLAYER_TAB_CHARACTER'),
			'TAB_SLUG'   => '',
			'TAB_ACTIVE' => $active_tab === null,
			'U_TAB'      => $this->helper->route('avathar_bbguild_player', [
				'guild_id'  => $guild_id,
				'player_id' => $player_id,
			]),
		]);

		foreach ($available_tabs as $tab)
		{
			$this->template->assign_block_vars('nav_tabs', [
				'TAB_NAME'   => $this->language->lang($tab->get_tab_name()),
				'TAB_SLUG'   => $tab->get_tab_slug(),
				'TAB_ACTIVE' => $tab->get_tab_slug() === $tab_slug,
				'U_TAB'      => $this->helper->route('avathar_bbguild_player', [
					'guild_id'  => $guild_id,
					'player_id' => $player_id,
					'tab_slug'  => $tab->get_tab_slug(),
				]),
			]);
		}
		$this->template->assign_vars([
			'S_PLAYER_TAB_TEMPLATE' => $active_tab !== null ? $active_tab->render($player_id) : null,
		]);

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

	/**
	 * About page — bbGuild version, license and credits. Opened as a popup
	 * from the footer copyright line.
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function about()
	{
		$this->template->assign_vars([
			'BBGUILD_VERSION' => ext::BBGUILD_VERSION,
			'BBGUILD_YEAR'    => date('Y'),
		]);

		return $this->helper->render('about.html', $this->language->lang('BBGUILD_ABOUT'));
	}
}
