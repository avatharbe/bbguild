# Issue #360 Design: Portal page-level guild tabs

**GitHub:** [avatharbe/bbguild#360](https://github.com/avatharbe/bbguild/issues/360), milestone `2.1.0` (due 2026-10-15).

**Goal:** Add a tabbing layer over the existing Board3-derived column portal, so a guild page can have multiple independent tabs (each with its own set of column-organized modules), reusing the currently-inert `{page}` route segment as the tab slug.

**Why now:** Roadmap-flagged as the 2.1.0 foundation piece — several other 2.1.0 items (character page, guild statistics module) are more naturally scoped as their own tab rather than more modules crammed into one page.

**Scope:** bbguild core only. No plugin changes.

## Architecture

### 1. Data model

- New `bb_portal_tabs` table: `tab_id` (PK, auto-increment), `guild_id`, `tab_name` (VCHAR_UNI:100), `tab_slug` (VCHAR:100, unique per guild — becomes the `{page}` route value), `tab_order` (USINT), `tab_status` (TINT:1, default 1 — mirrors `module_status`).
- New `module_tab` column on `bb_portal_modules` (UINT, references `bb_portal_tabs.tab_id`) — a plain row-level value like `module_column`, not a bitmask like modules' `get_allowed_columns()`, since modules today have zero tab-awareness and nothing about a module's *class* should constrain which tabs it can live on.
- **Backward compat (the migration's real work):** existing guilds have modules with no tab concept. The migration's `update_data()` auto-creates one default tab per existing `guild_id` present in `bb_portal_modules` (name "Overview", slug `welcome` — reusing today's actual default page value) and backfills every existing module row's `module_tab` to that guild's new default tab id. The `guild_id = 0` template-layout row (consumed by `database_handler::seed_guild_layout()` for new guilds) gets the same default-tab treatment, so new guilds still start with one tab. Net effect: zero visual change for any existing guild until someone adds a second tab in ACP.
- Index: fold `module_tab` into the existing `guild_col_order` composite index (`['guild_id', 'module_tab', 'module_column', 'module_order']`), since `database_handler::get_modules()`'s `WHERE`/`ORDER BY` now needs all three together.

### 2. Routing & controller flow

- `config/routing.yml`'s `avathar_bbguild_00` route requirement (`page: welcome|roster` — confirmed `roster` is dead weight, nothing generates a link with it, and `roster` became a portal module back in #345) widens to accept any slug shape. Unknown/empty slugs resolve to the guild's default tab at the controller/renderer level, not via a route-level whitelist.
- `controller/view_controller.php::handleview($guild_id, $page = 'welcome')` — `$page` is currently accepted but entirely unused. It now threads through: `$this->portal_renderer->render($this->guild_context->guild_id, $page)`.
- `portal_renderer::render(int $guild_id, string $tab_slug = '')`: resolves the tab — empty or unknown slug falls back to the guild's default tab (lowest `tab_order`); known slug uses that tab. Passes the resolved `tab_id` into `database_handler::get_modules(int $guild_id, int $tab_id)`, which adds `AND module_tab = ?` to its existing `WHERE guild_id = ?`.
- Tab bar: a new template loop (`tabs.TAB_NAME`/`TAB_SLUG`/`TAB_ACTIVE`/`U_TAB`) assigned by `portal_renderer` (it already owns `render()` and has `template`/`guild_context` access), rendered in `styles/all/template/main.html` between the closing `.guild-header` div (line 28) and the `view/welcome.html` include (line 35) — the exact seam the issue's "between the guild header and the portal grid" wording describes.

### 3. ACP

- New actions added to the existing `controller/admin_portal.php` (mirrors the module-CRUD action pattern already there — `add`/`delete`/`toggle`/`move_up`/`move_down` — rather than a separate controller, since this controller already owns per-guild portal configuration): `add_tab`, `edit_tab`, `delete_tab`, `move_tab_up`/`move_tab_down`.
- `build_module_list()`'s per-row `column_option` loop gets a sibling `tab_option` loop (all tabs for this guild); `build_add_module_form()`'s `column_options` loop gets a sibling `tab_options` loop. `acp_editguild_portal.html` gets a Tab `<select>` next to the existing Column `<select>` in both the module-list row and the add-module form, plus a new "Tabs" management fieldset (list + add/edit/delete/reorder), mirroring the existing module-list fieldset's shape.
- New `ACP_PORTAL_TAB_*` language keys, following the existing `ACP_PORTAL_COLUMN_*` naming precedent in `language/en/portal.php`.
- Deleting a tab that still has modules assigned: reassign those modules to the guild's default tab (same fallback semantics as an unknown route slug), rather than leaving orphaned `module_tab` values or blocking the delete — keeps the "always resolves to something" invariant from the routing layer consistent in the ACP layer too.

### 4. Testing

No existing test coverage touches `portal_renderer`, `database_handler`, `columns`, `module_helper`, or `admin_portal` directly — this establishes fresh precedent, following `tests/portal/modules/statistics_test.php`'s established style (mock `driver_interface` + `template`, capture `assign_block_vars`/`assign_vars` calls via `willReturnCallback`):

- Tab-resolution logic (slug → `tab_id`; empty/unknown slug → default tab, lowest `tab_order`).
- `database_handler::get_modules($guild_id, $tab_id)` — mocked-db test asserting the SQL filters on `module_tab`.
- `portal_renderer::render()` — asserts the resolved tab is passed through to `get_modules()`, and the `tabs` template loop's active-tab flag is correct.
- `admin_portal`'s new tab CRUD actions — request-handling/validation tests, matching the same ceiling every other `admin_*` controller has here (none are tested today either — no full phpBB session stack available locally).
- The migration's **data** backfill (existing modules → default tab) is the one piece more state-changing than a typical schema-only migration — verify specifically on the live board during final verification, not just trust the migration code in isolation.

## Out of scope / explicitly deferred

- Drag-and-drop reordering of modules *between* tabs (only up/down within a tab, matching the existing within-column reorder UX) — tabs aren't ordered/adjacent like columns, so this isn't a natural extension of the existing horizontal-move logic; a future issue if wanted.
- Per-tab permissions (all tabs visible to anyone who can see the guild page at all, same as today) — no request for tab-level ACLs in the issue body.
- Any change to `bb_portal_modules`'s existing `module_column`/column semantics — tabs are a layer *above* columns, not a replacement.
