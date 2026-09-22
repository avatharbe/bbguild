# Installation Guide

## Prerequisites

1. **phpBB 3.3.0+** installed and working
2. **PHP 8.1+** with the GD and cURL extensions
3. A **game plugin** (optional) if you want a supported game's data pre-built
   in — see [Game Plugins](#game-plugins) below. Without one, bbGuild's
   built-in **Custom** game still works for a manually-configured roster.

## Step 1: Install the Extension

1. Download or clone `bbguild` into your phpBB extensions directory:
   ```
   ext/avathar/bbguild/
   ```
2. Verify the file structure — you should have:
   ```
   ext/avathar/bbguild/
   ├── composer.json
   ├── ext.php
   ├── config/services.yml
   ├── migrations/
   └── acp/
   ```
3. Navigate to **ACP > Customise > Manage extensions**.
4. Find **bbGuild** under Disabled Extensions and click **Enable**.

If the extension fails to enable, check the **Requirements** section of the
README — `is_enableable()` will list the specific PHP version or missing
`gd`/`curl` extension it's rejecting on.

## Step 2: Review Permissions

bbGuild adds one ACP category — `a_bbguild` — and five user permissions:
`u_bbguild` (view guild pages), `u_charclaim`, `u_charadd`, `u_chardelete`,
and `u_charupdate` (character management via UCP). The migration grants
sensible defaults automatically (role-based **and** direct per-group
grants, so installs managing permissions either way are covered — see
`contrib/database.md` for the full matrix), but two defaults are worth
knowing about before you go live:

- **`u_bbguild` is granted to GUESTS by default**, so guild pages are
  publicly visible out of the box. Revoke it from GUESTS (**ACP >
  Permissions > Group permissions**) to make guild pages members-only.
- **`bbguild_maxchars`** (**ACP > bbGuild > General Settings > Settings**)
  caps how many characters each user can own — adjust it for your
  community's needs.

## Step 3: Install a Game

1. Navigate to **ACP > bbGuild > Game settings > Game List**.
2. If you installed a game plugin (Step 1 above, in that plugin's own
   `ext/avathar/bbguild<game>/`), its game now appears in the installable
   list — click **Install** next to it. This seeds factions, classes,
   races, roles, and (where the plugin defines them) specializations.
3. Without a plugin, use **Custom** instead — configure your own factions,
   classes, races, and roles directly under **Edit Game**.

## Step 4: Create a Guild

1. Navigate to **ACP > bbGuild > Guild and Player management > Add Guild**.
2. Fill in the guild name, realm, region, and game.
3. Save. A default portal layout (Message of the Day, Roster, Recruitment)
   is created automatically for every new guild.

## Step 5: Set Up the Portal (Optional)

Each guild's front page can have multiple tabs (e.g. "Overview", "Roster",
"Rules"), each with its own independently laid-out set of modules across
four columns (top, center, right, bottom).

1. Navigate to **ACP > bbGuild > Guild and Player management > Guild
   List**, then **Edit** your guild.
2. Open the **Portal** tab to add/reorder tabs and modules, or toggle
   built-in modules (Message of the Day, Roster, Recruitment, Guild
   Statistics) on and off.

The default layout from Step 4 already works without any changes here —
this step is only needed to customize it.

## Step 6: Add Characters

Characters can be added two ways:

- **ACP**: **ACP > bbGuild > Guild and Player management > Add player**.
- **UCP** (if `u_charadd` is granted): logged-in users can add and claim
  their own characters from **UCP > bbGuild**.

## Game Plugins

Game-specific data (real class/race/faction rosters instead of Custom) and,
for some games, API-driven sync come from separate plugin extensions,
installed the same way as core:

| Plugin | Directory | Notes |
|--------|-----------|-------|
| [bbguildwow](https://github.com/avatharbe/bbguildwow) | `ext/avathar/bbguildwow/` | Battle.net API sync — see its own [docs/INSTALL.md](https://github.com/avatharbe/bbguildwow/blob/main/docs/INSTALL.md) |
| bbguildgw2 | `ext/avathar/bbguildgw2/` | Guild Wars 2 |
| bbguildeq | `ext/avathar/bbguildeq/` | EverQuest |
| bbguildeq2 | `ext/avathar/bbguildeq2/` | EverQuest 2 |
| bbguildffxi | `ext/avathar/bbguildffxi/` | Final Fantasy XI |
| bbguildffxiv | `ext/avathar/bbguildffxiv/` | Final Fantasy XIV |
| bbguildlotro | `ext/avathar/bbguildlotro/` | Lord of the Rings Online |
| bbguildswtor | `ext/avathar/bbguildswtor/` | Star Wars: The Old Republic |
| bbguildlineage2 | `ext/avathar/bbguildlineage2/` | Lineage 2 |

Every plugin hard-requires a minimum bbGuild core version via its own
`ext.php::is_enableable()` — if a plugin won't enable, update core first.

## Verify

1. Visit your forum's bbGuild page (`/guild/{guild_id}`, e.g. `/guild/1`
   for the first guild you created).
2. You should see the guild header and its portal modules (Roster, MOTD,
   etc.) on the default "Overview" tab.
3. Add a character (Step 6) and confirm it appears in the roster.

## Troubleshooting

### Extension does not appear in ACP
- Check that files are in the correct directory: `ext/avathar/bbguild/composer.json` must exist.
- Clear the phpBB cache: **ACP > General > Purge the cache**.

### A game plugin won't enable
- Enable bbGuild core first — every plugin hard-requires it.
- Check the plugin's own minimum-core-version requirement (in its
  `ext.php`) against the core version installed (**ACP > bbGuild > General
  Settings > Dashboard**).

### Guild page renders empty (no roster, no MOTD)
- Confirm the guild has at least one portal tab with modules attached
  (Step 5) — a guild created directly via SQL/import rather than the ACP
  **Add Guild** flow won't get the automatic default layout.
- Purge the phpBB cache and reload.

### UCP "bbGuild" tab is missing for a user
- Check that the user's group has at least one of `u_charclaim` /
  `u_charadd` granted — the tab is hidden entirely if none apply, even
  when `u_bbguild` (view-only) is granted.

## Uninstall

1. In the ACP, go to **Customise > Manage extensions**.
2. Disable any enabled game plugins first — disabling core drops shared DI
   parameters their `services.yml` files reference.
3. Find **bbGuild** under Enabled Extensions and click **Disable**.
4. To permanently remove, click **Delete Data** and then delete
   `ext/avathar/bbguild/`.
