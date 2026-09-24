# bbGuild Family Roadmap (2.x)

*Updated 2026-09-24. Working copy: `ext/avathar/bbguild` (+ plugins). Reconciled with GitHub milestones.*

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
- **#389** roster images fell back to nothing when a plugin asset was missing — **shipped** 2026-09-24 (PR #390); the root-cause fix behind the whole icon cluster, see Game plugins below
- **#391** every game plugin is missing its `<game>_unknown.png` fallback icon — the last step of #389's chain, so it can currently almost never fire. Needs authoring per plugin, no upstream source

### 2.3.0 — integrations · due 2027-01-15
- **#370** expose a bbGuild API surface (phpBB events + read API) — the decoupling/integration layer for the whole family; revives the 2012 bbDKP-API idea; Discord/Gameworld are its first consumers
- **Discord integration** — new extension; **open question, not decided**
- **Gameworld#16** — port existing `avatharbe/Gameworld` repo from its old phpBB 3.0 MOD to a 3.3 extension (boss/zone progress); milestoned 2.3.0
- Battle.net API modernization
- **#286** spec build analysis

## Game plugins (milestone-locked)

All 9 plugins (wow, gw2, lotro, eq, eq2, ffxi, ffxiv, swtor, lineage2) release in lockstep with core and now carry the same milestones (2.0.0–2.3.0, same due dates) — locked to the milestone, not to identical RC numbers (within 2.0.0 stabilisation, core rc5 / WoW rc3 / rest rc2, each plugin hard-requiring core rc5). Per-plugin backlog is allocated as:

- **2.0.0 (stabilization)** — test suites (EPV / unit / functional / smoke / integration) in every plugin, plus roster/class/race **icon fixes** (eq #7, eq2 #7, ffxiv #4, gw2 #7, lineage2 #7/#3, lotro #3, swtor #3, wow #27) — deferred to 2.2.0 and **worked 2026-09-24**, see the icon-cluster subsection below.
- **2.1.0 (features/data)** — **seed specializations** for the 8 non-WoW games (eq #6, eq2 #6, ffxi #5, ffxiv #7, gw2 elite-spec icons #8, lineage2 #6, lotro #6, swtor #6); **LOTRO game-data** update (#7); **WoW** character features — scheduled Battle.net sync (#11, implements core #362) — **shipped**, activity feed (#10) — **shipped**, titles collection (#24) — **closed as won't-do** (compared against real Blizzard armory UI: titles never appear as a collapsible list anywhere, only as a single inline word above the character name — not worth building as originally scoped).
- **2.2.0** — WoW arena bracket ratings on the player page (#23); **GW2 API v2** roster sync (gw2 #9 — re-scoped to roster-only, name/rank/join-date; moved out of 2.1.0 since it assumed a working API this train never delivered, key-linking split off separately).

### Icon cluster — worked 2026-09-24

An audit of all 9 plugins' `class_images/`, `roster_classes/`, `race_images/` and `spec_icons/` against the `imagename`/`image_male`/`image_female` values their installers actually seed. Two findings reshaped the work:

1. **The tickets understated it.** Missing-asset counts were 1 (swtor), 3 (gw2, eq2), 9 (lotro), 12 (ffxiv, lineage2 class icons) and **110** (lineage2 roster art), plus `<game>_unknown.png` absent in 8 of 9 plugins and bbguildffxi missing its `roster_classes/` directory outright — that plugin had no icon ticket at all.
2. **Two tickets proposed the wrong fix.** eq2#7 and gw2#7 suggest copying the missing file from `class_images/`. `roster_classes/` is not an icon directory — it holds large character artwork (wow 184x184, eq 125x184, gw2 ~130x180, lineage2 ~100x100, swtor 100x100, lotro 80x80, eq2 ~80x85) — and those sources are 17-30px detail icons. Upscaling them would look wrong beside the rest of the grid.

So the root cause was fixed in core (**#389**, PR #390): every roster image now resolves against the filesystem and falls back roster art → detail icon → the game's unknown icon → omit the `<img>`. That is what makes lineage2's 110 missing portraits a quality gap rather than 110 broken images, and it is the only approach that scales.

**Shipped, merged and CI-green 2026-09-24:**

| Repo | PR | Scope | Issue |
|---|---|---|---|
| bbguild | #390 | fallback chain + template guards + 13 tests | #389 **closed** |
| bbguildgw2 | #11 | 27 elite-spec icons from the official GW2 render API, `spec_catalog()` wired | #8 **closed** |
| bbguildffxiv | #9 | 7 of 12 missing job icons, both sets | #8 partial |
| bbguildlotro | #8 | 9 unreachable filenames + sizes normalised to 48x48 | #3 partial |
| bbguildswtor | #7 | 4 race icons padded to the set's 44x44 | #3 partial |
| bbguildeq | #8 | Shadow Knight filename mismatch | #7 **closed** |
| bbguildeq2 | #8 | filename casing + a dead `eq2_Captain.png` | #7 partial |
| bbguildlineage2 | #8 | stray `.jpg` duplicate | #3 partial |

**Asset provenance, verified rather than assumed** — worth knowing before sourcing more:
- **GW2** elite-spec icons come from the official API (`/v2/specializations`), native 64x64, normalised to the 56x56 bbguildwow uses. Fully official, no licensing caveat. The API now returns **36** elite specs against the catalog's 27 (gw2#12).
- **FFXIV** icons are XIVAPI v1 (`/cj/1/<job>.png`) reduced with a **box** filter — measured mean per-pixel difference against the shipped icon at 24x24: box **11.1**, Lanczos 31.0, every raw game-icon range 41+. The `roster_classes/` plate is a horizontally uniform vertical gradient, reconstructable per row from any existing icon.
- **Undersized icons are padded onto a transparent canvas, not upscaled** — keeps the original pixels crisp and is reversible, accepting that the art still reads smaller than its neighbours.

**What remains is blocked on artwork, not effort** — no upstream source exists for any of it:
- **bbguildlineage2** #7 (110 roster portraits) and #3 (12 starter-class icons)
- **bbguildffxiv** #4 (6 race icons — the 6 wrong ones are dark full-body renders where the 10 correct ones are face crops on a light ground, so no compositing or reframing fixes them) and #8's last 4 jobs (Reaper, Sage, Viper, Pictomancer: absent from XIVAPI v1, and v2's raw range has them in a visibly darker tone that breaks the set)
- **bbguildlotro** #3 (Brawler, Mariner, River-hobbit, `lotro_unknown`, 9 classes with no roster art)
- **bbguildeq2** #7 (Beastlord, Channeler), **bbguildgw2** #7 (Revenant, Thief), **bbguildswtor** #3 (`swtor_unknown`), **bbguildffxi** #6 (whole `roster_classes/` directory + 3 class icons)
- **core #391** — `<game>_unknown.png` across all 9 plugins

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
- bbTips (`bonus=` attribute, #363) is a **separate extension** from the 9-plugin train. The `bonus=` support the 2.1.0 gear tooltips depend on ships in bbTips **2.0.0-rc3** (tagged `v2.0.0-rc3`, `BBTIPS_VERSION = '2.0.0-rc3'`) — released as an RC, not unreleased. The only gap is bookkeeping: no GitHub Release object exists for `v2.0.0-rc2`/`v2.0.0-rc3`, so the releases page still advertises `v1.0.7` (2015) as latest — the same state core's 2.0.0 tags were in until 2026-09-14. Publishing Release objects for the rc tags is the follow-up; cutting bbTips 2.0.0 stable is its own separate decision.

## Next steps
1. **2.1.0 is done and out** — tagged, Released on all 10 repos, milestone closed (0 open), CI green, forum posts + SEO published 2026-09-22. No follow-up work outstanding on this train.
2. **Icon cluster — code half done 2026-09-24**, 8 PRs merged across core + 7 plugins, 3 issues closed, CI green everywhere (see the icon-cluster subsection under Game plugins). Everything still open needs **artwork**: it is now a sourcing/commissioning task, not a coding one, and #389's fallback means none of it renders broken in the meantime. Five new tickets came out of the audit: core #391, bbguildffxiv#8, bbguildffxi#6, bbguildgw2#12, and core #389 (fixed).
3. **Decide how the remaining icon artwork gets sourced** — the blocker is that Lineage2 (110 + 12), FFXIV (6 race + 4 job), LOTRO (5), EQ2 (2), GW2 (2), SWTOR (1) and FFXI (23 + 3) have no upstream API to pull from, unlike GW2's specs and FFXIV's older jobs. Options are commissioning, extracting from game clients, or shipping authored placeholders.
4. Stand up epics/repos for the 2.2.0/2.3.0 new extensions (Events/RSVP first).
5. GW2 API v2 roster sync (gw2#9) moved to 2.2.0 — re-scoped to roster-only (name/rank/join date), key-linking split off separately; needs its own design pass before work starts.
