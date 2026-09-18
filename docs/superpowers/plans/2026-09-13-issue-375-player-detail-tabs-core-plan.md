# Player Detail Tabs — bbguild core Implementation Plan (#375)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a plugin-extensible player-detail sub-tab bar (Character built-in, others via a tagged-service registry) and redesign the Character-tab header to add a guild tag and a quick-stat pills slot.

**Architecture:** New `player_detail_tab_interface` + a `phpbb\di\service_collection` tagged `bbguild.player_detail_tab`, wrapped in a `player_detail_tab_registry` — the exact pattern already used for `bbguild.game_provider` (`game_registry`) and `bbguild.character_sync` (`character_sync_registry`). `view_controller::playerdetail()` gains an optional `$tab_slug` route param; it resolves the active tab against the registry and either includes that tab's returned template path, or falls through to the existing Character content (core fieldset + the pre-existing `avathar.bbguild.player_detail_display` event) unchanged. The event handler for that event gains one new block-var assignment point (`header_pills`) that plugins can populate — no new event needed.

**Tech Stack:** PHP 8.1+, phpBB 3.3 extension framework (Symfony DI `phpbb\di\service_collection`, Symfony routing), Twig.

**Spec:** `contrib/superpowers/plans/2026-09-13-issue-375-player-detail-tabs-design.md`

## Global Constraints

- PHP floor `>= 8.1.0`; phpBB floor `bbguild >= 3.3.0`.
- Code style: tabs, PHP long-form `array()` is NOT required here — this codebase's newer core files (e.g. `portal_renderer.php`) use short-array `[]` and typed properties; match that (newer) style since all files this plan touches are already in that style.
- The `bbguild.player_detail_tab` tag name, `player_tabs` template block-var name, and `S_PLAYER_TAB_TEMPLATE` var name are load-bearing for the bbguildwow plan (a separate plan/repo) — do not rename any of these three without updating this plan's own file-level impact notes.
- `loops.tabs` (guild-portal tabs, `#360`) and `loops.player_tabs` (this plan, player-detail sub-tabs) are two distinct, unrelated block-var loops that coexist on the same page — never conflate them.
- Commit after every task, reference `#375`.
- Edit-in-Sites-first: make each change in `/Users/Andreas/Sites/avathar/forum/ext/avathar/bbguild/...` first (or copy there immediately after editing the dev-repo file — both are currently byte-identical), then copy the finished file into `/Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/...` for commit. Purge phpBB cache (`php bin/phpbbcli.php cache:purge -n` from `/Users/Andreas/Sites/avathar/forum`) after every template/routing/services.yml change.

---

### Task 1: `player_detail_tab_interface`

**Files:**
- Create: `portal/player_detail_tab_interface.php`

**Interfaces:**
- Consumes: nothing (leaf interface).
- Produces: the contract every tab provider (core has none; bbguildwow's plan implements 3) must satisfy. Task 2's registry and Task 3's controller both depend on this exact method signature set.

- [ ] **Step 1: Write the interface**

```php
<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Player Detail Tab Interface
 * Implemented by game plugins to register an additional tab on the
 * player-detail page, alongside the built-in Character tab. Tagged
 * services implementing this interface are collected via
 * player_detail_tab_registry (#375).
 */

namespace avathar\bbguild\portal;

interface player_detail_tab_interface
{
	/**
	 * Lang key for the tab's label in the tab bar.
	 */
	public function get_tab_name(): string;

	/**
	 * URL segment for this tab, e.g. 'talents'. Must be unique across all
	 * registered tabs and must not be empty (empty is reserved for Character).
	 */
	public function get_tab_slug(): string;

	/**
	 * Position in the tab bar, ascending. Character is implicitly 0.
	 */
	public function get_tab_order(): int;

	/**
	 * Whether this tab applies to the given player/game. Lets a WoW-only
	 * tab hide itself for a non-WoW character, for example.
	 */
	public function is_available(int $player_id, string $game_id): bool;

	/**
	 * Template path to render this tab's content (e.g.
	 * '@avathar_bbguildwow/portal/talents_tab.html'), or null if there is
	 * nothing to show for this player.
	 */
	public function render(int $player_id): ?string;
}
```

- [ ] **Step 2: Verify**

Run: `php -l portal/player_detail_tab_interface.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Copy and commit**

```bash
cp /Users/Andreas/Sites/avathar/forum/ext/avathar/bbguild/portal/player_detail_tab_interface.php /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/portal/player_detail_tab_interface.php
cd /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild
git add portal/player_detail_tab_interface.php
git commit -m "feat: add player_detail_tab_interface (#375)"
```

(Write the file directly in the Sites path first, per Global Constraints, then copy to the dev repo as shown — do this same copy direction for every task below without it being repeated in each step.)

---

### Task 2: `player_detail_tab_registry` + DI wiring

**Files:**
- Create: `model/games/player_detail_tab_registry.php`
- Modify: `config/services.yml`
- Test: `tests/games/player_detail_tab_registry_test.php`

**Interfaces:**
- Consumes: `player_detail_tab_interface` (Task 1), an `iterable` of tagged services (empty in core — bbguildwow's plan adds real ones).
- Produces: `player_detail_tab_registry::get_available_tabs(int $player_id, string $game_id): player_detail_tab_interface[]` (sorted by `get_tab_order()`) and `::find(string $slug, int $player_id, string $game_id): ?player_detail_tab_interface` — Task 3's controller calls both.

- [ ] **Step 1: Write the failing test**

Create `tests/games/player_detail_tab_registry_test.php`:

```php
<?php
namespace avathar\bbguild\tests\games;

use avathar\bbguild\model\games\player_detail_tab_registry;
use avathar\bbguild\portal\player_detail_tab_interface;
use PHPUnit\Framework\TestCase;

class fake_tab implements player_detail_tab_interface
{
	public function __construct(
		private string $name,
		private string $slug,
		private int $order,
		private bool $available = true
	) {}

	public function get_tab_name(): string { return $this->name; }
	public function get_tab_slug(): string { return $this->slug; }
	public function get_tab_order(): int { return $this->order; }
	public function is_available(int $player_id, string $game_id): bool { return $this->available; }
	public function render(int $player_id): ?string { return '@ext/tab_' . $this->slug . '.html'; }
}

class player_detail_tab_registry_test extends TestCase
{
	public function test_get_available_tabs_sorts_by_order(): void
	{
		$registry = new player_detail_tab_registry([
			new fake_tab('B', 'b', 2),
			new fake_tab('A', 'a', 1),
		]);

		$tabs = $registry->get_available_tabs(1, 'wow');

		$this->assertCount(2, $tabs);
		$this->assertSame('a', $tabs[0]->get_tab_slug());
		$this->assertSame('b', $tabs[1]->get_tab_slug());
	}

	public function test_get_available_tabs_excludes_unavailable(): void
	{
		$registry = new player_detail_tab_registry([
			new fake_tab('A', 'a', 1, true),
			new fake_tab('B', 'b', 2, false),
		]);

		$tabs = $registry->get_available_tabs(1, 'wow');

		$this->assertCount(1, $tabs);
		$this->assertSame('a', $tabs[0]->get_tab_slug());
	}

	public function test_find_returns_matching_available_tab(): void
	{
		$registry = new player_detail_tab_registry([
			new fake_tab('A', 'a', 1, true),
		]);

		$tab = $registry->find('a', 1, 'wow');

		$this->assertNotNull($tab);
		$this->assertSame('a', $tab->get_tab_slug());
	}

	public function test_find_returns_null_for_unavailable_tab(): void
	{
		$registry = new player_detail_tab_registry([
			new fake_tab('A', 'a', 1, false),
		]);

		$this->assertNull($registry->find('a', 1, 'wow'));
	}

	public function test_find_returns_null_for_unknown_slug(): void
	{
		$registry = new player_detail_tab_registry([
			new fake_tab('A', 'a', 1, true),
		]);

		$this->assertNull($registry->find('nonexistent', 1, 'wow'));
	}
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /Users/Andreas/.local/share/phpbb-ext-test-harness && ./vendor/bin/phpunit -c phpunit-bbguild-360.xml --filter player_detail_tab_registry_test` (this repo's local harness config is named `phpunit-bbguild-360.xml`, pointed at bbguild core's `tests/` — reuse it rather than creating a new config file)
Expected: FAIL — class not found.

- [ ] **Step 3: Write `model/games/player_detail_tab_registry.php`**

```php
<?php
/**
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Player Detail Tab Registry
 * Collects all player_detail_tab_interface implementations registered via
 * tagged services. Game plugins register by tagging their service with
 * 'bbguild.player_detail_tab' (#375).
 */

namespace avathar\bbguild\model\games;

use avathar\bbguild\portal\player_detail_tab_interface;

class player_detail_tab_registry
{
	/** @var player_detail_tab_interface[] */
	private $tabs;

	/**
	 * @param iterable $tabs Tagged player_detail_tab_interface services
	 */
	public function __construct(iterable $tabs)
	{
		$this->tabs = is_array($tabs) ? $tabs : iterator_to_array($tabs);
	}

	/**
	 * All tabs available for this player/game, sorted by tab_order.
	 *
	 * @return player_detail_tab_interface[]
	 */
	public function get_available_tabs(int $player_id, string $game_id): array
	{
		$available = array_values(array_filter(
			$this->tabs,
			fn (player_detail_tab_interface $tab) => $tab->is_available($player_id, $game_id)
		));

		usort($available, fn ($a, $b) => $a->get_tab_order() <=> $b->get_tab_order());

		return $available;
	}

	/**
	 * Find a specific available tab by slug.
	 */
	public function find(string $slug, int $player_id, string $game_id): ?player_detail_tab_interface
	{
		foreach ($this->get_available_tabs($player_id, $game_id) as $tab)
		{
			if ($tab->get_tab_slug() === $slug)
			{
				return $tab;
			}
		}

		return null;
	}
}
```

- [ ] **Step 4: Wire the DI collection + registry**

In `config/services.yml`, add immediately after the existing `avathar.bbguild.character_sync_registry` block (find it via the existing `avathar.bbguild.character_sync_collection`/`avathar.bbguild.character_sync_registry` pair — match that exact two-service shape):

```yaml
    avathar.bbguild.player_detail_tab_collection:
        class: phpbb\di\service_collection
        arguments:
            - '@service_container'
        tags:
            - { name: service_collection, tag: bbguild.player_detail_tab }
    avathar.bbguild.player_detail_tab_registry:
        class: avathar\bbguild\model\games\player_detail_tab_registry
        arguments:
            - '@avathar.bbguild.player_detail_tab_collection'
```

- [ ] **Step 5: Run test to verify it passes**

Run: same command as Step 2.
Expected: PASS (5 tests).

- [ ] **Step 6: Copy, purge cache, commit**

```bash
cp /Users/Andreas/Sites/avathar/forum/ext/avathar/bbguild/model/games/player_detail_tab_registry.php /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/model/games/player_detail_tab_registry.php
cp /Users/Andreas/Sites/avathar/forum/ext/avathar/bbguild/tests/games/player_detail_tab_registry_test.php /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/tests/games/player_detail_tab_registry_test.php
cp /Users/Andreas/Sites/avathar/forum/ext/avathar/bbguild/config/services.yml /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/config/services.yml
cd /Users/Andreas/Sites/avathar/forum && php bin/phpbbcli.php cache:purge -n
cd /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild
git add model/games/player_detail_tab_registry.php tests/games/player_detail_tab_registry_test.php config/services.yml
git commit -m "feat: add player_detail_tab_registry + DI collection (#375)"
```

---

### Task 3: Wire the registry into `view_controller::playerdetail()` + routing

**Files:**
- Modify: `controller/view_controller.php`
- Modify: `config/routing.yml`
- Modify: `config/services.yml`
- Modify: `views/player_detail.php` (add a `get_game_id()` getter)

**Interfaces:**
- Consumes: `player_detail_tab_registry` (Task 2), `player_detail::get_game_id()` (new in this task).
- Produces: template vars `S_PLAYER_TAB_TEMPLATE` (string|null) and block-var `player_tabs` (`TAB_NAME`/`TAB_SLUG`/`TAB_ACTIVE`/`U_TAB`) — Task 4's template consumes both. Route `avathar_bbguild_player` gains an optional trailing `{tab_slug}` segment.

- [ ] **Step 1: Add `get_game_id()` to `player_detail`**

In `views/player_detail.php`, add a property next to the existing `protected $player_name;`-style properties (find it by searching for `$this->player_name = $p->getPlayerName();` inside `load()` — add the mirroring line right after it):

```php
	protected $game_id = '';
```

and in `load()`, immediately after `$this->player_name = $p->getPlayerName();`:

```php
		$this->game_id = $p->getGameId();
```

Then add a public getter near the existing `get_player_name()` method:

```php
	public function get_game_id(): string
	{
		return $this->game_id;
	}
```

- [ ] **Step 2: Extend the route**

In `config/routing.yml`, change the existing `avathar_bbguild_player` block from:

```yaml
avathar_bbguild_player:
    path: /guild/{guild_id}/player/{player_id}
    defaults: { _controller: avathar.bbguild.controller:playerdetail }
    requirements:
        guild_id: \d+
        player_id: \d+
```

to:

```yaml
avathar_bbguild_player:
    path: /guild/{guild_id}/player/{player_id}/{tab_slug}
    defaults: { _controller: avathar.bbguild.controller:playerdetail, tab_slug: '' }
    requirements:
        guild_id: \d+
        player_id: \d+
```

(A trailing optional segment with a route-level default is the same pattern already used for the guild-portal route's `{page}` segment — confirm by reading the `avathar_bbguild_00` route entry in this same file before editing, and match its exact requirements/defaults shape rather than guessing.)

- [ ] **Step 3: Inject the registry into `view_controller`**

In `controller/view_controller.php`, add the import and constructor param:

```php
use avathar\bbguild\model\games\player_detail_tab_registry;
```

Add a property:

```php
	/** @var player_detail_tab_registry */
	protected $player_detail_tab_registry;
```

Add `player_detail_tab_registry $player_detail_tab_registry` as the LAST constructor parameter (after `language $language`), and assign it in the constructor body (`$this->player_detail_tab_registry = $player_detail_tab_registry;`).

In `config/services.yml`, add `'@avathar.bbguild.player_detail_tab_registry'` as the last argument to the existing `avathar.bbguild.controller` service block (after the existing `'@language'` line).

- [ ] **Step 4: Update `playerdetail()`**

Replace the current method body:

```php
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
		$this->portal_renderer->render_tab_bar($guild_id);

		// Load player data
		if (!$this->player_detail->load($player_id, $this->template))
		{
			throw new \phpbb\exception\http_exception(404, 'NO_PLAYER');
		}
```

with (note the new `$tab_slug = ''` parameter and the tab-resolution block inserted after the player load, before the existing event-dispatch code which is unchanged below it):

```php
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

		foreach ($available_tabs as $tab)
		{
			$this->template->assign_block_vars('player_tabs', [
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

		$active_tab = $tab_slug !== '' ? $this->player_detail_tab_registry->find($tab_slug, $player_id, $game_id) : null;
		$this->template->assign_vars([
			'S_PLAYER_TAB_TEMPLATE' => $active_tab !== null ? $active_tab->render($player_id) : null,
		]);
```

Leave everything below this point (the `avathar.bbguild.player_detail_display` event dispatch and the final `return $this->helper->render(...)`) exactly as it is — do not modify it.

- [ ] **Step 5: Verify and test**

```bash
php -l views/player_detail.php
php -l controller/view_controller.php
```

Both must report no syntax errors. Then copy all four changed files (`views/player_detail.php`, `controller/view_controller.php`, `config/routing.yml`, `config/services.yml`) to the Sites install, purge cache, and confirm the existing player-detail page still loads without a fatal error (curl it as guest — expect the existing `403` permission page, NOT a 500/fatal, since this route is `u_bbguild`-gated and no test account is available in this environment): `curl -s -o /dev/null -w "%{http_code}\n" "http://localhost/avathar/forum/app.php/guild/2/player/4"`.

- [ ] **Step 6: Copy and commit**

```bash
cp /Users/Andreas/Sites/avathar/forum/ext/avathar/bbguild/views/player_detail.php /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/views/player_detail.php
cp /Users/Andreas/Sites/avathar/forum/ext/avathar/bbguild/controller/view_controller.php /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/controller/view_controller.php
cp /Users/Andreas/Sites/avathar/forum/ext/avathar/bbguild/config/routing.yml /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/config/routing.yml
cp /Users/Andreas/Sites/avathar/forum/ext/avathar/bbguild/config/services.yml /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/config/services.yml
cd /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild
git add views/player_detail.php controller/view_controller.php config/routing.yml config/services.yml
git commit -m "feat: resolve player-detail sub-tabs in view_controller, extend route (#375)"
```

---

### Task 4: Template — sub-tab bar, header guild-tag + pills slot, `{% include %}` branch

**Files:**
- Modify: `styles/all/template/player_detail.html`

**Interfaces:**
- Consumes: `loops.player_tabs` block (Task 3), `S_PLAYER_TAB_TEMPLATE` (Task 3), `header_pills` block (new — bbguildwow's plan populates it via the existing `player_detail_display` event; core just renders the loop, empty is fine).
- Produces: nothing new for other tasks — this is the last core task in this plan.

- [ ] **Step 1: Add the guild tag to the header title block**

In `styles/all/template/player_detail.html`, inside `<div class="player-detail-title-block">`, immediately after the closing `</h2>` of `player-detail-name` and before the `{% if PLAYER_TITLE %}` block, add:

```html
                    {% if PLAYER_GUILD_NAME %}<span class="player-detail-guild-tag">&lt;{{ PLAYER_GUILD_NAME }}&gt;</span>{% endif %}
```

- [ ] **Step 2: Add the pills slot**

Immediately after the closing `</div>` of `player-detail-subtitle` (still inside `player-detail-title-block`), add:

```html
                    {% if loops.header_pills %}
                    <div class="player-detail-pills">
                        {% for pill in loops.header_pills %}
                        <span class="player-detail-pill">{{ pill.PILL_LABEL }}</span>
                        {% endfor %}
                    </div>
                    {% endif %}
```

- [ ] **Step 3: Add the sub-tab bar**

Immediately after the existing guild-portal-tabs block (the `{% if loops.tabs|length > 1 %}...{% endif %}` block already in the file) and before `<!-- Player detail card -->`, add:

```html
        {% if loops.player_tabs %}
        <div class="player-detail-tabs">
            <ul>
                {% for tab in loops.player_tabs %}
                <li class="{% if tab.TAB_ACTIVE %}activetab{% endif %}">
                    <a href="{{ tab.U_TAB }}">{{ tab.TAB_NAME }}</a>
                </li>
                {% endfor %}
            </ul>
        </div>
        {% endif %}
```

- [ ] **Step 4: Wrap the existing body + event in the `{% if S_PLAYER_TAB_TEMPLATE %}` branch**

Change the existing:

```html
            <div class="player-detail-body">
                <fieldset>
                    ...
                </fieldset>
            </div>

            {% EVENT bbguild_player_detail_after_content %}
```

to:

```html
            {% if S_PLAYER_TAB_TEMPLATE %}
            {% include S_PLAYER_TAB_TEMPLATE %}
            {% else %}
            <div class="player-detail-body">
                <fieldset>
                    ...
                </fieldset>
            </div>

            {% EVENT bbguild_player_detail_after_content %}
            {% endif %}
```

(Keep the entire `<fieldset>...</fieldset>` content byte-for-byte identical — only the wrapping `{% if %}/{% else %}/{% endif %}` is new.)

- [ ] **Step 5: Copy, purge cache, verify no Twig break**

Copy the file to the Sites install, purge cache, and re-read the finished file once for balanced `{% if %}/{% endif %}` tags (this codebase has no local Twig lint tool — manual brace-balance re-read is the established verification method, per the `#364` plan's precedent). Then re-run the same guest curl smoke test from Task 3 Step 5 and confirm still no fatal/500.

- [ ] **Step 6: Copy and commit**

```bash
cp /Users/Andreas/Sites/avathar/forum/ext/avathar/bbguild/styles/all/template/player_detail.html /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild/styles/all/template/player_detail.html
cd /Users/Andreas/development/PHP/phpbb33_extensions/avathar/bbguild
git add styles/all/template/player_detail.html
git commit -m "feat: player-detail sub-tab bar, guild tag + pills slot in header (#375)"
```

---

## Out of scope (this plan)

- Anything bbguildwow-side (restyling the Character-tab body, the 3 new tab implementations, stat grouping) — a separate plan, depends on this one being merged first (Task 1's interface and Task 2's tag name must exist before bbguildwow can implement/tag against them).
- CSS for `.player-detail-guild-tag`/`.player-detail-pills`/`.player-detail-tabs` — bbguild core's `styles/all/theme/` CSS; add in this plan only if the existing prosilver defaults render it illegibly (check visually once merged; if it's usable unstyled, a follow-up CSS pass is fine, not blocking).
