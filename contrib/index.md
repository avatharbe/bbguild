# bbGuild

A Guild Management System for [phpBB 3.3](https://www.phpbb.com/). Manage
your gaming guild's roster, recruitment, and news directly from your forum.

Originally forked as bbDKP from EQDKP to phpBB 3.0 in 2008, the 2.0 version
was renamed bbGuild and rebuilt for phpBB 3.3 and PHP 8.x.

**Current version:** 2.1.0 — see the [Changelog](CHANGELOG.md) for what's
new.

[Get it on GitHub](https://github.com/avatharbe/bbguild){ .md-button }
[Support forum](https://www.avathar.be/forum/viewforum.php?f=106){ .md-button }

## Features

- **Tabbed guild portal** — multiple pages per guild, each with its own
  block layout (Message of the Day, Roster, Recruitment, Guild Statistics)
- **Full guild roster** — sortable, filterable, with class/race/spec icons
- **Player profiles** — a tabbed detail page (Character, plus whatever
  tabs game plugins add — Talents, Achievements, PvP, etc.)
- **Specializations** — a subclass layer between class and role (e.g. Frost
  Mage vs. Fire Mage)
- **Recruitment board** — open positions by role and class
- **Multi-game support** — 9 game plugins (WoW, GW2, LOTRO, EQ, EQ2, FFXI,
  FFXIV, SWTOR, Lineage 2), plus a built-in Custom game
- **Character-sync scheduler** — a core cron task that keeps character
  data current via each game plugin's own sync handler
- **phpBB event catalogue** — sibling extensions can hook into guild
  lifecycle events without patching core files

See the full list in the [project README](https://github.com/avatharbe/bbguild#features).

## Where to start

- New to bbGuild? Start with **[Installation](INSTALL.md)**.
- Want to know how it's built? Read **[Architecture](architecture.md)**.
- Building an integration or a game plugin? See **[Events API](Events.md)**
  and the architecture doc's game-plugin contract section.
- Curious what's coming? Check the **[Roadmap](roadmap-2.x.md)**.
- Need the full schema? See **[Database Schema](database.md)**.

## Game Plugins

Game-specific support is provided by separate extensions:

| Plugin | Game |
|--------|------|
| [bbguildwow](https://github.com/avatharbe/bbguildwow) | World of Warcraft (Battle.net API) |
| [bbguildgw2](https://github.com/avatharbe/bbguildgw2) | Guild Wars 2 |
| [bbguildlotro](https://github.com/avatharbe/bbguildlotro) | Lord of the Rings Online |
| [bbguildeq](https://github.com/avatharbe/bbguildeq) | EverQuest |
| [bbguildeq2](https://github.com/avatharbe/bbguildeq2) | EverQuest 2 |
| [bbguildffxi](https://github.com/avatharbe/bbguildffxi) | Final Fantasy XI |
| [bbguildffxiv](https://github.com/avatharbe/bbguildffxiv) | Final Fantasy XIV |
| [bbguildswtor](https://github.com/avatharbe/bbguildswtor) | Star Wars: The Old Republic |
| [bbguildlineage2](https://github.com/avatharbe/bbguildlineage2) | Lineage 2 |

## Contributing

See [`CONTRIBUTING.md`](https://github.com/avatharbe/bbguild/blob/main/CONTRIBUTING.md)
for the full contribution policy, and
[`SECURITY.md`](https://github.com/avatharbe/bbguild/blob/main/SECURITY.md)
for reporting vulnerabilities.

## License

[GNU General Public License v2](http://opensource.org/licenses/gpl-2.0.php)
