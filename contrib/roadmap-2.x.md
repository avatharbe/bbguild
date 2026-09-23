# bbGuild Family Roadmap (2.x)

*Updated 2026-09-23. Working copy: `ext/avathar/bbguild` (+ plugins). Reconciled with GitHub milestones.*

## North star

Feature parity, measured against four benchmarks at once:

1. **phpBB native** — profiles, permissions, PMs, notifications.
2. **Legacy bbDKP MOD (1.4)** — MOD features not yet re-implemented.
3. **The extension family** — bbAccounts, bbDKP v2, bbPoints, and planned events/Gameworld/Discord.
4. **Guild-hosting sites** — guildsofwow.com as the external bar.

Releases are a **coordinated train** across bbGuild core + all 9 game plugins — shipped together and **pairing-locked** (every plugin hard-requires the current core version). **2.0.0 shipped stable 2026-09-12, publicly released 2026-09-14** (core `v2.0.0`; bbguildwow's pairing bumped to `>=2.0.0` — its own version stays at `2.1.0-b1`, already mid-feature-work; other plugins' 2.0.0 status not yet re-verified as part of this pass). GitHub Releases for [`v2.0.0-rc5`](https://github.com/avatharbe/bbguild/releases/tag/v2.0.0-rc5) and [`v2.0.0`](https://github.com/avatharbe/bbguild/releases/tag/v2.0.0) were published 2026-09-14 (tagged 2026-07-26/2026-09-12 respectively, but sat without a Release object until then).

**2.1.0 shipped and was publicly released 2026-09-22** — core [`v2.1.0`](https://github.com/avatharbe/bbguild/releases/tag/v2.1.0) plus a `v2.1.0` Release on all 8 non-WoW plugins and [`v2.1.1`](https://github.com/avatharbe/bbguildwow/releases/tag/v2.1.1) on bbguildwow (a same-day patch for equipment-sync stalls found in manual verification; its own `v2.1.0` tag has no separate Release object, 2.1.1 supersedes it). All 10 repos: milestone closed, CI green on `main`, nothing unpushed. The author published the per-release forum posts and did an SEO pass the same day, which also closes out the 2.0.0 announcement that had been left outstanding. **New features are at 2.2.0.** Cadence ~6 weeks.

**Scope of this document:** the **bbGuild family** — core, game plugins, and guild-facing extensions (events/RSVP, Discord, Gameworld). The **DKP & accounting family** (bbAccounts, bbDKP, raid logging) is a **separate roadmap/topic** — summarized at the bottom for context only.

## Parity matrix

Status: ✅ have · ◑ partial · ⬜ gap. "Owner" = which repo delivers it.

| Feature area | phpBB | bbDKP 1.4 | GoW | Today | Owner | Target |
|---|---|---|---|---|---|---|
| Roster / members | ◑ | ✅ | ✅ | ✅ grid/list, filters | core+wow | shipped |
| Character page | ⬜ | ◑ | ✅ | ✅ redesigned, panes unified with guild page (#364/#380) | core+wow | shipped |
| Character auto-sync (scheduler) | ⬜ | ◑ manual | ✅ | ✅ #361/#362 shipped | core contract + wow | shipped |
| GW2 roster/character sync (API v2) | — | ⬜ | ✅ v2 | ⬜ | bbguildgw2 | 2.2.0 (gw2#9 — re-scoped to roster-only, moved out of 2.1.0) |
| Gear tooltips (bbTips + bonus IDs) | ⬜ | ⬜ | ✅ | ✅ shipped | wow + bbtips | shipped |
| Per-character achievements | ⬜ | ◑ points | ✅ | ✅ shipped (#365 tracking issue + bbguildwow#44) | wow | shipped |
| Guild achievement browser | ⬜ | ⬜ | ✅ | ✅ 3-level | wow | shipped |
| Guild statistics | ⬜ | ◑ class dist | ✅ | ✅ lists (#366 shipped) | core module | 2.1.0 (#366 shipped); 2.2.0 (#279 reopened — chart/build-list gap) |
| Portal — tabbed pages | ⬜ | ⬜ | ✅ | ✅ shipped | core | shipped |
| Recruitment | ⬜ | ✅ | ✅ | ✅ | core | shipped |
| Roster ↔ profile fields | ✅ | — | ✅ | ◑ UCP claim | core | 2.2.0 (#231) |
| Character-based forum avatars | ✅ | — | ✅ | ✅ shipped (#369) | wow + core hooks | shipped |
| Profile-field character info | ✅ | — | ✅ | ⬜ removed from pbwow | wow + core hooks | 2.2.0 (#368) |
| Professions | ⬜ | ⬜ | ✅ | ⬜ | core+wow | 2.2.0 (#230) |
| Player statistics | ⬜ | ✅(DKP) | ✅ | ⬜ | core | 2.2.0 (#289) |
| Events / RSVP calendar | ◑ | ⬜(Raidplanner) | ✅ | ⬜ | **new ext** | 2.2.0 |
| Discord integration | ⬜ | ⬜ | ✅ | ⬜ | **new ext** | 2.3.0 — open question, not decided |
| Boss / raid progress (Gameworld) | ⬜ | ◑(Gameworld MOD) | ✅ | ⬜ old phpBB 3.0 MOD | Gameworld (existing repo) | 2.3.0 (Gameworld#16 — port to 3.3 extension) |
| Battle.net API modernization | — | ◑ | — | ◑ working | wow | 2.3.0 |
| bbGuild API surface (events + read API) | — | ◑ bbDKP-api idea | ◑ | ◑ events shipped (#370 phase 1) | core | 2.1.0 (#370 phase 1, shipped); 2.3.0 (#370 phase 2 — read API, still open) |
| Spec build analysis | ⬜ | ⬜ | ◑ | ⬜ | wow | 2.3.0 (#286) |
| Plugin spec data (8 non-WoW) | — | — | — | ◑ WoW only (rest have researched data already, see #367) | plugins | **parked** (#367, inactive — scope undefined) |
| DKP / points | ⬜ | ✅ | ✅ | ◑ *separate track* | bbDKP/bbAccounts/bbPoints | see below |
| Raid logging + importer | ⬜ | ◑(Raidtracker) | ◑ | ⬜ | *DKP track* | see below |

## Release plan

### 2.0.0 — stable (core + bbguildwow) · due 2026-08-31 · **shipped 2026-09-12, released 2026-09-14**
**Stabilization only — no new features.**
- Closed the rc line; bug-bash across roster / UCP / ACP / portal / multi-guild.
- **#244** unit tests (gated stable) — closed 2026-09-12, scoped to essentials already shipped; remaining migration/CRUD coverage split into #372 (2.1.0).
- GitHub Releases for `v2.0.0-rc5` and `v2.0.0` published 2026-09-14 (tags existed since 2026-07-26/2026-09-12 but had no Release object until then). Forum announcement: covered by the 2026-09-22 release-post push (see 2.1.0). Note `v2.0.0`'s Release object is still flagged **Pre-release** on GitHub while `v2.1.0` is Latest — cosmetic, worth flipping next time the releases page is touched.
- Coordinated stable release tagged for core + bbguildwow (`avathar/bbguild >=2.0.0` pairing). Other 8 plugins' 2.0.0 status not re-verified in this pass.

### 2.1.0 — guild page overhaul · due 2026-10-15 · **shipped, released 2026-09-22**
Headline: tabbed portal + character experience + stats.
- **#360** page-level guild tabs (portal foundation) — **shipped** (merged ffeb450d, closed 2026-09-13)
- **#361** core character-sync scheduler contract + cron — **shipped**
- **#362** bbguildwow sync handler (armory equipment, incremental) — **shipped**
- **#363** gear tooltips via bbTips + capture bonus IDs — **shipped** (found fully implemented/committed under bbguildwow's `2.1.0-b1` line but undocumented; reconciled 2026-09-12)
- **#364** character page polish (layout + async stats) — **shipped**, closed 2026-09-18
- **#365** per-character achievements view — **shipped** as a tracking issue, closed 2026-09-18; concrete implementation shipped in bbguildwow#44 (also closed)
- **#369** character-based forum avatars (restores pbwowext#10, part 1 — rides on the synced renders) — **shipped** (bbguildwow 8124b41, closed)
- **#366** guild statistics portal module (numbers/percentage lists incl. class distribution) — **shipped** (5b04fe69, closed 2026-09-12)
- **#279** class distribution — reopened 2026-09-14, narrowed to the chart/graph visualization + optional build-list that #366 didn't cover; moved to milestone 2.2.0
- **#370** bbGuild event catalogue, phase 1 (in-process events only) — **shipped**, merged via PR #378 2026-09-14; `@since 2.1.0` since it ships this train rather than waiting for 2.3.0. Issue itself stays open/milestoned 2.3.0, since it also tracks phase 2 (the out-of-process read API)
- **#373** about page popup + footer credit — **shipped**, closed 2026-09-13
- **#375** player-detail sub-tab bar, core half — **shipped**, merged via PR #376 2026-09-13
- **#377** UCP character-add 503 fix — **shipped**, closed 2026-09-13
- **#367** complete plugin spec data for the 8 non-WoW games — **parked** 2026-09-13, labeled `inactive` (audited all 8 plugins; found real researched data already exists, remaining gap is thinner translation coverage vs. WoW, scope undefined)
- **#372** unit test coverage: migrations + remaining guild/player CRUD paths (split from #244) — **shipped**, closed 2026-09-18
- **#379** guild nav tab on the player-detail tab bar, linking back to the guild portal page — **shipped**, closed 2026-09-21
- **#380** unify guild portal-block panes with the player-detail pane style — **shipped**, closed 2026-09-21; shipped alongside a broader visual pass (hero pane redesign, realm-format fix, per-faction backgrounds, panes inheriting the active style's `.panel` look)
- **#381** migrate legacy free-text `player_spec` → `player_spec_id` (split out of #331's Phase 5) — **closed without a migration**; existing installs aren't a support concern (clean install is the path forward), so there's no real backlog to backfill in practice
- **#382** ACP "Linked user" dropdown could select newly-registered/banned accounts — **shipped**, closed 2026-09-21
- **#383** squash all 2.1.0 migrations into one, matching the 2.0.0 train's squash — **shipped**, closed 2026-09-22; 2.0.x migrations squashed the same way in the same pass

### 2.2.0 — events + roster depth · due 2026-11-30
- **Events / RSVP calendar** — new extension (top GoW gap; Raidplanner MOD prior art — Raidplanner's guild-specific raid signup/roster management, bbDKP raid-event integration, and portal blocks go beyond what a generic third-party calendar extension covers, so it's still being built)
- **#231** roster ↔ custom profile fields
- **#368** profile-field character info (restores pbwowext#10, part 2; companion to #231)
- **#230** professions
- **#289** player statistics page
- **#388** guild activity feed portal module (filed 2026-09-22, after the 2.1.0 release)

### 2.3.0 — integrations · due 2027-01-15
- **#370** expose a bbGuild API surface (phpBB events + read API) — the decoupling/integration layer for the whole family; revives the 2012 bbDKP-API idea; Discord/Gameworld are its first consumers
- **Discord integration** — new extension; **open question, not decided**
- **Gameworld#16** — port existing `avatharbe/Gameworld` repo from its old phpBB 3.0 MOD to a 3.3 extension (boss/zone progress); milestoned 2.3.0
- Battle.net API modernization
- **#286** spec build analysis

## Game plugins (milestone-locked)

All 9 plugins (wow, gw2, lotro, eq, eq2, ffxi, ffxiv, swtor, lineage2) release in lockstep with core and now carry the same milestones (2.0.0–2.3.0, same due dates) — locked to the milestone, not to identical RC numbers (within 2.0.0 stabilisation, core rc5 / WoW rc3 / rest rc2, each plugin hard-requiring core rc5). Per-plugin backlog is allocated as:

- **2.0.0 (stabilization)** — test suites (EPV / unit / functional / smoke / integration) in every plugin, plus roster/class/race **icon fixes** (eq #7, eq2 #7, ffxiv #4, gw2 #7, lineage2 #7/#3, lotro #3, swtor #3, wow #27).
- **2.1.0 (features/data)** — **seed specializations** for the 8 non-WoW games (eq #6, eq2 #6, ffxi #5, ffxiv #7, gw2 elite-spec icons #8, lineage2 #6, lotro #6, swtor #6); **LOTRO game-data** update (#7); **WoW** character features — scheduled Battle.net sync (#11, implements core #362) — **shipped**, activity feed (#10) — **shipped**, titles collection (#24) — **closed as won't-do** (compared against real Blizzard armory UI: titles never appear as a collapsible list anywhere, only as a single inline word above the character name — not worth building as originally scoped).
- **2.2.0** — WoW arena bracket ratings on the player page (#23); **GW2 API v2** roster sync (gw2 #9 — re-scoped to roster-only, name/rank/join-date; moved out of 2.1.0 since it assumed a working API this train never delivered, key-linking split off separately).

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
- New extensions (events, Discord) are **separate repos** — they need their own milestones/epics; tracked here only as roadmap line items. Discord is an **open question, not decided**.
- Gameworld is an **existing repo**, not a new one — still the old phpBB 3.0 MOD, needs porting to a 3.3 extension (`Gameworld#16`, milestoned 2.3.0).
- bbTips (`bonus=` attribute, #363) is a **separate extension** from the 9-plugin train. The `bonus=` support the 2.1.0 gear tooltips depend on is committed on bbTips `main` (working toward `2.0.0-rc3`), but bbTips' newest *published* GitHub Release is still `v1.0.7` (2015) — so 2.1.0's tooltips currently rely on an unreleased bbTips. Cutting a bbTips 2.0.0 release is a real follow-up, not just bookkeeping.

## Next steps
1. **2.1.0 is done and out** — tagged, Released on all 10 repos, milestone closed (0 open), CI green, forum posts + SEO published 2026-09-22. No follow-up work outstanding on this train.
2. **Icon-art cluster is now the oldest unclaimed work** — 9 plugin bugs, all milestoned 2.2.0, each small and independent: eq#7, eq2#7, ffxiv#4, gw2#7, gw2#8, lineage2#3, lineage2#7, lotro#3, swtor#3. They were queued as 2.0.0 stabilization work back on 2026-09-13, deferred to 2.2.0, and never picked up. Cheapest visible win available and parallelizes cleanly across repos.
3. Stand up epics/repos for the 2.2.0/2.3.0 new extensions (Events/RSVP first).
4. GW2 API v2 roster sync (gw2#9) moved to 2.2.0 — re-scoped to roster-only (name/rank/join date), key-linking split off separately; needs its own design pass before work starts.
