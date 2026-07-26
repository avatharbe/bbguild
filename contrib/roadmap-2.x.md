# bbGuild Family Roadmap (2.x)

*Updated 2026-07-26. Working copy: `ext/avathar/bbguild` (+ plugins). Reconciled with GitHub milestones.*

## North star

Feature parity, measured against four benchmarks at once:

1. **phpBB native** — profiles, permissions, PMs, notifications.
2. **Legacy bbDKP MOD (1.4)** — MOD features not yet re-implemented.
3. **The extension family** — bbAccounts, bbDKP v2, bbPoints, and planned events/Gameworld/Discord.
4. **Guild-hosting sites** — guildsofwow.com as the external bar.

Releases are a **coordinated train** across bbGuild core + all 9 game plugins (version-locked). **2.0.0 ships stable first (stabilization only); new features start at 2.1.0.** Cadence ~6 weeks.

**Scope of this document:** the **bbGuild family** — core, game plugins, and guild-facing extensions (events/RSVP, Discord, Gameworld). The **DKP & accounting family** (bbAccounts, bbDKP, raid logging) is a **separate roadmap/topic** — summarized at the bottom for context only.

## Parity matrix

Status: ✅ have · ◑ partial · ⬜ gap. "Owner" = which repo delivers it.

| Feature area | phpBB | bbDKP 1.4 | GoW | Today | Owner | Target |
|---|---|---|---|---|---|---|
| Roster / members | ◑ | ✅ | ✅ | ✅ grid/list, filters | core+wow | shipped |
| Character page | ⬜ | ◑ | ✅ | ◑ basic, rough | core+wow | 2.1.0 |
| Character auto-sync (scheduler) | ⬜ | ◑ manual | ✅ | ⬜ manual only | core contract + wow | 2.1.0 (#360/#361/#362) |
| GW2 roster/character sync (API v2) | — | ⬜ | ✅ v2 | ⬜ | bbguildgw2 | 2.1.0 (gw2#9) |
| Gear tooltips (bbTips + bonus IDs) | ⬜ | ⬜ | ✅ | ◑ inert | wow + bbtips | 2.1.0 (#363) |
| Per-character achievements | ⬜ | ◑ points | ✅ | ⬜ model exists | wow | 2.1.0 (#365) |
| Guild achievement browser | ⬜ | ⬜ | ✅ | ✅ 3-level | wow | shipped |
| Guild statistics | ⬜ | ◑ class dist | ✅ | ⬜ | core module | 2.1.0 (#366, #279) |
| Portal — tabbed pages | ⬜ | ⬜ | ✅ | ⬜ columns only | core | 2.1.0 (#360) |
| Recruitment | ⬜ | ✅ | ✅ | ✅ | core | shipped |
| Roster ↔ profile fields | ✅ | — | ✅ | ◑ UCP claim | core | 2.2.0 (#231) |
| Character-based forum avatars | ✅ | — | ✅ | ⬜ removed from pbwow | wow + core hooks | 2.1.0 (#369) |
| Profile-field character info | ✅ | — | ✅ | ⬜ removed from pbwow | wow + core hooks | 2.2.0 (#368) |
| Professions | ⬜ | ⬜ | ✅ | ⬜ | core+wow | 2.2.0 (#230) |
| Player statistics | ⬜ | ✅(DKP) | ✅ | ⬜ | core | 2.2.0 (#289) |
| Events / RSVP calendar | ◑ | ⬜(Raidplanner) | ✅ | ⬜ | **new ext** | 2.2.0 |
| Discord integration | ⬜ | ⬜ | ✅ | ⬜ | **new ext** | 2.3.0 |
| Boss / raid progress (Gameworld) | ⬜ | ◑(Gameworld MOD) | ✅ | ⬜ stub | **new ext** | 2.3.0 |
| Battle.net API modernization | — | ◑ | — | ◑ working | wow | 2.3.0 |
| bbGuild API surface (events + read API) | — | ◑ bbDKP-api idea | ◑ | ⬜ | core | 2.3.0 (#370) |
| Spec build analysis | ⬜ | ⬜ | ◑ | ⬜ | wow | 2.3.0 (#286) |
| Plugin spec data (8 non-WoW) | — | — | — | ◑ WoW only | plugins | 2.1.0 (#367) |
| DKP / points | ⬜ | ✅ | ✅ | ◑ *separate track* | bbDKP/bbAccounts/bbPoints | see below |
| Raid logging + importer | ⬜ | ◑(Raidtracker) | ◑ | ⬜ | *DKP track* | see below |

## Release plan

### 2.0.0 — stable (core + 9 plugins) · due 2026-08-31
**Stabilization only — no new features.**
- Close the rc line; bug-bash across roster / UCP / ACP / portal / multi-guild.
- **#244** unit tests (gates stable).
- Tag the coordinated stable release.

### 2.1.0 — guild page overhaul · due 2026-10-15
Headline: tabbed portal + character experience + stats.
- **#360** page-level guild tabs (portal foundation)
- **#361** core character-sync scheduler contract + cron
- **#362** bbguildwow sync handler (armory equipment, incremental)
- **#363** gear tooltips via bbTips + capture bonus IDs
- **#364** character page polish (layout + async stats)
- **#365** per-character achievements view
- **#369** character-based forum avatars (restores pbwowext#10, part 1 — rides on the synced renders)
- **#366** guild statistics portal module (+ **#279** class distribution)
- **#367** complete plugin spec data for the 8 non-WoW games

### 2.2.0 — events + roster depth · due 2026-11-30
- **Events / RSVP calendar** — new extension (top GoW gap; Raidplanner MOD prior art)
- **#231** roster ↔ custom profile fields
- **#368** profile-field character info (restores pbwowext#10, part 2; companion to #231)
- **#230** professions
- **#289** player statistics page

### 2.3.0 — integrations · due 2027-01-15
- **#370** expose a bbGuild API surface (phpBB events + read API) — the decoupling/integration layer for the whole family; revives the 2012 bbDKP-API idea; Discord/Gameworld are its first consumers
- **Discord integration** — new extension
- **Gameworld** — new extension (boss/zone progress)
- Battle.net API modernization
- **#286** spec build analysis

## Game plugins (version-locked)

All 9 plugins (wow, gw2, lotro, eq, eq2, ffxi, ffxiv, swtor, lineage2) release in lockstep with core and now carry the same milestones (2.0.0–2.3.0, same due dates). Per-plugin backlog is allocated as:

- **2.0.0 (stabilization)** — test suites (EPV / unit / functional / smoke / integration) in every plugin, plus roster/class/race **icon fixes** (eq #7, eq2 #7, ffxiv #4, gw2 #7, lineage2 #7/#3, lotro #3, swtor #3, wow #27).
- **2.1.0 (features/data)** — **seed specializations** for the 8 non-WoW games (eq #6, eq2 #6, ffxi #5, ffxiv #7, gw2 elite-spec icons #8, lineage2 #6, lotro #6, swtor #6); **LOTRO game-data** update (#7); **GW2 API v2** roster/character sync (gw2 #9 — implements the core sync contract for Guild Wars 2); **WoW** character features — scheduled Battle.net sync (#11, implements core #362), titles collection (#24), activity feed (#10).
- **2.2.0** — WoW arena bracket ratings on the player page (#23).

This mirrors core: tests + display bugs stabilize 2.0.0; spec/data + character features land 2.1.0. WoW's scheduled-sync ticket (#11) is the plugin half of the core sync-scheduler contract (#361/#362).

## Related but separate: DKP & Accounting track
Its own roadmap/topic. bbGuild-side integration points land **2.1.0+**. Status:
- **bbAccounts** — almost **RC ready** (double-entry ledger / canonical store). Most mature.
- **bbDKP v2** — **~0%** as a real product beyond scaffolding.
- **Raid logging + importer** — **0%** (Raidtracker MOD prior art; companion bbDKP Lua mod).
- **bbPoints v2** — rebuild on bbAccounts.
Gets its own parity matrix + release plan in a separate document.

## Open decisions
- Sync trigger: phpBB cron default + documented system-cron option *(recommended)*.
- Item data depth: base ID **+ bonus IDs** minimum *(recommended)*.

## Cross-repo notes
- New extensions (events, Discord, Gameworld) are **separate repos** — they need their own milestones/epics; tracked here only as roadmap line items.
- bbTips (`bonus=` attribute, #363) is a **separate extension** from the 9-plugin train — coordinate its release with 2.1.0.

## Next steps
1. First implementation spec: **tab foundation + character cluster** (#360–#365) — own design → plan.
2. Follow-on spec: guild-statistics module (#366).
3. Stand up epics/repos for the 2.2.0/2.3.0 new extensions.
