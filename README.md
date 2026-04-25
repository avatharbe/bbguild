[![bbGuild](https://www.avathar.be/forum/images/site_logo.png)](https://www.avathar.be/forum)

# bbGuild

A Guild Management System for [phpBB 3.3](https://www.phpbb.com/). Manage your gaming guild's roster, recruitment, and news directly from your forum.

Originally forked as bbDKP from EQDKP to phpBB 3.0 in 2008, the 2.0 version has been renamed bbGuild and rebuilt for phpBB 3.3 and PHP 8.x.

**Current version:** 2.0.0-b3 (beta)

## Features

### Guild Portal
- **Block-based welcome page** — configurable portal with 3-column layout (top, center, right)
- **Built-in modules** — Message of the Day, Roster, Recruitment
- **Guild-scoped** — each guild gets its own portal layout, independently configured
- **Module plugin system** — extensible via tagged services; add custom portal modules from other extensions
- **ACP management** — add, remove, reorder, move between columns, and enable/disable modules per guild
- **Default layout template** — new guilds inherit a default module configuration

### Guild Management
- **Multi-guild support** — manage multiple guilds from a single phpBB installation
- **Guild profiles** — name, realm, region, faction, game, emblem, and description
- **Guild news** — message of the day and news feed

### Roster
- **Full guild roster** — sortable player list with class, race, rank, and level
- **Player profiles** — detailed character view with gear, stats, and guild history
- **Character management** — add, edit, delete, and claim characters via ACP and UCP
- **Rank management** — define custom guild ranks with display preferences

### Recruitment
- **Recruitment board** — post open positions by role and class
- **Status tracking** — open/closed status, number of positions, applicant count
- **Role-based filtering** — recruit by game role (tank, healer, DPS, etc.)

### Multi-Game Support
- **Plugin architecture** — game support provided via separate `bbguild_<game>` extensions
- **Available plugins:** World of Warcraft (with Battle.net API), Guild Wars 2, LOTRO, EverQuest, EverQuest 2, FFXI, FFXIV, SWTOR, Lineage 2
- **Custom game support** — define your own game with custom classes, races, factions, and roles
- **Per-game data** — classes, races, factions, and roles are fully configurable per game

### Administration (ACP)
- **Portal management** — configure portal modules per guild (add, remove, reorder, toggle)
- **Game management** — install, configure, and remove game definitions
- **Guild management** — create and edit guilds, assign games and realms
- **Player management** — full CRUD for characters, batch operations, rank sorting
- **Recruitment management** — create and manage recruitment postings
- **Activity logs** — track all administrative actions

### User Control Panel (UCP)
- **Character claiming** — users can link forum accounts to guild characters
- **Character editing** — update your own character details
- **Permission-based access** — granular permissions for claim, add, edit, and delete

### Localization
- English, German, French, Italian, Dutch, Spanish, Polish

## Requirements

- phpBB >= 3.3.0
- PHP >= 7.4
- PHP GD extension (`php_gd2`)
- PHP cURL extension (`php_curl`)

## Installation

1. Download the [latest release](https://github.com/avatharbe/bbguild/releases).
2. Create the directory `ext/avathar/` in your phpBB installation (if it doesn't exist).
3. Extract `bbguild` into `ext/avathar/bbguild/`.
4. In the ACP, go to **Customise > Manage extensions**.
5. Find **bbGuild** under Disabled Extensions and click **Enable**.

### Game Plugins

Game support is provided by separate extensions. Install them the same way:

| Plugin | Directory | Notes |
|--------|-----------|-------|
| [bbguild_wow](https://github.com/avatharbe/bbguild_wow) | `ext/avathar/bbguild_wow/` | Includes Battle.net API integration |
| bbguild_gw2 | `ext/avathar/bbguild_gw2/` | Guild Wars 2 |
| bbguild_eq | `ext/avathar/bbguild_eq/` | EverQuest |
| bbguild_eq2 | `ext/avathar/bbguild_eq2/` | EverQuest 2 |
| bbguild_ffxi | `ext/avathar/bbguild_ffxi/` | Final Fantasy XI |
| bbguild_ffxiv | `ext/avathar/bbguild_ffxiv/` | Final Fantasy XIV |
| bbguild_lotro | `ext/avathar/bbguild_lotro/` | Lord of the Rings Online |
| bbguild_swtor | `ext/avathar/bbguild_swtor/` | Star Wars: The Old Republic |
| bbguild_lineage2 | `ext/avathar/bbguild_lineage2/` | Lineage 2 |

## Uninstall

1. In the ACP, go to **Customise > Manage extensions**.
2. Find **bbGuild** under Enabled Extensions and click **Disable**.
3. To permanently remove, click **Delete Data** and then delete `ext/avathar/bbguild/`.

## Community

- Support forum: [https://www.avathar.be/forum](https://www.avathar.be/forum)
- phpBB topic: [Extension development](https://www.phpbb.com/community/viewtopic.php?f=456&t=2258141)

## Contributing

See the [contributors](https://github.com/avatharbe/bbguild/graphs/contributors) who have helped build this project.

1. [Create an issue](https://github.com/avatharbe/bbguild/issues) (unless one already exists).
2. Submit a pull request.

See `contrib/CHANGELOG.md` for version history.

## License

[GNU General Public License v2](http://opensource.org/licenses/gpl-2.0.php)
