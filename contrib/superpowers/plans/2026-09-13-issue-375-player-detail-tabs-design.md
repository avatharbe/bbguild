# Player Detail Tabs — Design (#375)

**Status:** approved by project owner in conversation 2026-09-13, ready for implementation planning.

## Problem

The character page (`player_detail.html` + bbguildwow's injected content) has grown crowded: a plain core `<dl>` fieldset, followed by bbguildwow's hero render, two equipment columns, a flat stats list, professions, Mythic+, and PvP — all stacked in one vertical column with no grouping. The target is classic-armory.org's Character tab layout (`bbguildwow/contrib/classic-armory screenshots/Screen Shot 2026-09-13 at 13.59.01.png`): a compact header, two dense gear columns, a grouped Base/Melee/Spell stat sidebar, and a sub-tab bar (Character/Talents/Raid Progression/PVP).

bbguild core is game-agnostic (9 game plugins). "Talents"/"Raid Progression"/"PVP" are WoW-specific concepts, so the sub-tab bar needs a plugin-extensibility mechanism, not a hardcoded list — the same shape of problem `#360` solved for guild-level portal tabs and `#361` solved for the character-sync scheduler.

## Decisions

### 1. Tab ownership split

**Character is not a registered tab — it stays core's built-in, always-first tab**, rendered via the *existing* mechanism unchanged: core's header + fieldset, then whatever the existing `avathar.bbguild.player_detail_display` event injects (bbguildwow's equipment/stats/professions/M+/PvP content keeps landing there the same way it does today, just restyled — see Decision 3). Only *additional* tabs beyond Character go through the new registry.

Rationale: Character's content mechanism already works and is data-source-agnostic per plugin; only the *bar itself* and *additional tabs* need a new extensibility point.

### 2. Tab interface + collection

New interface in bbguild core, `avathar\bbguild\portal\player_detail_tab_interface`, naming and shape modeled on the existing `modules\module_interface` (portal modules) and `character_sync_interface` (#361) conventions already in this codebase:

```php
namespace avathar\bbguild\portal;

interface player_detail_tab_interface
{
	/** Lang key for the tab label. */
	public function get_tab_name(): string;

	/** URL segment, e.g. 'talents'. */
	public function get_tab_slug(): string;

	/** Position in the bar; Character is implicitly 0. */
	public function get_tab_order(): int;

	/** Whether this tab applies to this player/game (e.g. WoW-only tabs hide for non-WoW characters). */
	public function is_available(int $player_id, string $game_id): bool;

	/** Template path to render this tab's content, or null if nothing to show. */
	public function render(int $player_id): ?string;
}
```

Collected via a `phpbb\di\service_collection` tagged `bbguild.player_detail_tab` — identical mechanism to `bbguild.portal.module` and `bbguild.character_sync`, so a new plugin just tags its service, no core registration step.

### 3. Routing + controller flow

Route: extend the existing player-detail route with an optional trailing tab-slug segment: `/guild/{guild_id}/player/{player_id}/{tab_slug}` (default `''` → Character). Each tab is a **full page load** via its own URL — not client-side tab-switching — matching `#360`'s guild-portal tab pattern. A tab with expensive data can use the same async-panel-after-page-paint pattern `#364` already established for Character's stats; no new SPA-style mechanism is needed.

`view_controller::playerdetail($guild_id, $player_id, $tab_slug = '')`:
1. Load player (existing), get `game_id`.
2. Query the `bbguild.player_detail_tab` collection for all tabs where `is_available($player_id, $game_id)` is true; assign as a `player_tabs` block var (tab bar), same shape as the existing guild-tab bar (`TAB_NAME`/`TAB_SLUG`/`TAB_ACTIVE`/`U_TAB`).
3. Resolve active tab: empty/unmatched slug → Character (core-built-in); matched slug → the corresponding provider.
4. If the active tab is a registered one, call its `render($player_id)` and assign the returned template path to `S_PLAYER_TAB_TEMPLATE`. `player_detail.html` does:
   ```twig
   {% if S_PLAYER_TAB_TEMPLATE %}
   {% include S_PLAYER_TAB_TEMPLATE %}
   {% else %}
   {# existing Character content: core fieldset + event %}
   {% endif %}
   ```

### 4. Character tab visual redesign

**Header (core, `player_detail.html`)** — replaces the current plain `<dl>` fieldset:
- Portrait thumbnail (reuses existing `player_render_url`/class-icon fallback logic already in the template).
- Class-colored name + guild-tag link.
- A level / race-icon / race / spec / class(colored) / realm line.
- A **quick-stat pills row**: core renders an empty slot (`{% for pill in header_pills %}`); plugins populate it via the *existing* `avathar.bbguild.player_detail_display` event (add `header_pills` block-var assignment alongside what bbguildwow already injects there — no new event needed).

**Body (bbguildwow's `bbguild_player_detail_after_content.html`, restyled — no core changes)**:
- Drop the hero render entirely (the reference has none; the portrait now lives in the header).
- Two equipment columns sit directly side-by-side (reuse `#364`'s existing 8/8 split, quality-color rows — layout only, no data change).
- **New third column**: the stats sidebar, regrouped from today's flat list into three named sub-panels — **Base** (Health/Mana/Stamina/Strength/Agility/Intellect/Spirit), **Melee** (Damage/Speed/Attack Power/Crit/Haste), **Spell** (Bonus Healing/Bonus Damage/Penetration/Mana Regen/Combat Regen/Crit/Haste) — matching the reference. This un-defers the grouping `#364`'s plan explicitly left as a fast-follow.
- Professions/Mythic+/PvP panels stay below, unchanged in mechanism (still async-loaded from the existing `character-stats` endpoint).

Stat grouping happens in `character_stats_controller::build_stats()` (bbguildwow) — group each stat under `base`/`melee`/`spell` instead of a flat array; the inline-script JS renderer in the template groups accordingly.

### 5. New tabs: content sources

- **PVP**: real content now — honor level (`fetch_pvp_summary()`, already fetched by `#364`). Bracket ratings (2v2/3v3/RBG) are `#23` (already open, milestone 2.2.0) — that ticket fills in this same tab slot later; no tab-mechanism changes needed when it lands.
- **Talents**: no current Blizzard API integration for a full talent tree (`fetch_character_stats()` only pulls spec, not individual talent choices). Ships as a **placeholder tab** — `is_available()` true, `render()` returns a minimal "coming soon" template. Real implementation is a separate future ticket.
- **Raid Progression**: no data exists anywhere in the system (`bb_bosstable`/`bb_zonetable` are roadmap-only, targeted at the future Gameworld extension, 2.3.0). Ships as a **placeholder tab** for the same reason as Talents.

Both placeholder tabs use the same minimal template (e.g. `styles/all/template/portal/player_detail_tab_placeholder.html`, shared by both bbguildwow tab registrations) — a single "Coming soon" message, no per-tab custom markup needed until real content exists.

## Out of scope

- Building real Talents or Raid Progression data — tracked as future tickets once their prerequisites (a talent-tree API integration; the Gameworld extension) exist.
- `#23` (PvP bracket ratings) itself — this design only ensures the PVP tab slot exists for it to fill in later.
- Any change to the guild-level portal tab bar (`#360`) — a different, unrelated tab concept, confirmed to stay as-is above the redesigned player-detail header.
- Non-WoW game plugins registering their own player-detail tabs — the mechanism supports it, but no other plugin is in scope for this ticket.

## File-level impact (for the implementation plan)

**bbguild core:**
- New: `portal/player_detail_tab_interface.php`
- Modify: `controller/view_controller.php` (`playerdetail()` — tab collection, resolution, template hand-off)
- Modify: `config/routing.yml` (optional tab-slug segment on the player route)
- Modify: `config/services.yml` (register the `bbguild.player_detail_tab` tagged collection, mirroring the existing `bbguild.portal.module` collection registration)
- Modify: `styles/all/template/player_detail.html` (header redesign, tab bar, `{% include S_PLAYER_TAB_TEMPLATE %}` branch)

**bbguildwow:**
- Modify: `event/listener.php` (add `header_pills` block-var assignment to the existing `player_detail_display` event handler)
- Modify: `controller/character_stats_controller.php` (group `build_stats()` output into base/melee/spell)
- Modify: `styles/all/template/event/bbguild_player_detail_after_content.html` (drop hero render, 3-column layout, grouped stats rendering in the inline script)
- New: two tagged services (Talents, Raid Progression placeholder tabs) implementing `player_detail_tab_interface`, plus a PVP tab implementation surfacing existing `fetch_pvp_summary()` data
- New: `styles/all/template/portal/player_detail_tab_placeholder.html` (shared "coming soon" template)
- Modify: `config/services.yml` (tag the three new tab services)
