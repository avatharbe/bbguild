# Contributing to bbGuild

Thanks for your interest in contributing! The full contribution policy is
posted here:

**https://www.avathar.be/forum/viewtopic.php?t=1854**

Read it before submitting anything non-trivial — the summary below hits
the key points, but the forum post is the source of truth.

Bugs or features specific to a particular game (WoW, GW2, LOTRO, etc.)
belong in that game's own plugin repository instead — see the README's
[Game Plugins](README.md#game-plugins) table.

## Before starting

- For anything larger than a bugfix, open an issue first so we can agree
  on the approach.
- Security issues: do not open a public issue or pull request — see
  [SECURITY.md](SECURITY.md).
- Be kind to each other. Repositories are moderated the same way as the
  forum.

## Via GitHub (preferred)

- Fork from https://github.com/avatharbe and branch from the appropriate
  phpBB version line.
- Follow phpBB Coding Standards and the surrounding code style.
- Keep the diff to the point — no unrelated reformatting, no whitespace
  churn.
- User-facing strings go in `language/en/` only.
- Database changes go through a migration; don't edit already-released
  migrations.
- Don't bump version numbers or edit `contrib/CHANGELOG.md` — that's
  handled separately at release time.
- One commit should do exactly one thing. Commit messages: a
  ~72-character summary line, a blank line, then the reasoning.
- Test against the supported phpBB/PHP versions and run the Extension
  Pre-Validator (EPV) before opening a PR — see
  [`contrib/architecture.md`](contrib/architecture.md) for the test
  layout and local harness notes.
- Sign off your commits (`git commit -s`).
- AI-assisted contributions are fine, but you remain the author: you're
  responsible for understanding, testing, and defending every line.
- Contributions are licensed under GPL-2.0-only.

## Manual fallback

No GitHub account? Post a unified diff to the User Modifications forum
with version information. The same standards apply.

## Documentation

- [`contrib/INSTALL.md`](contrib/INSTALL.md) — installation guide.
- [`contrib/architecture.md`](contrib/architecture.md) — system design,
  component boundaries, and the reasoning behind non-obvious decisions.
- [`contrib/database.md`](contrib/database.md) — full schema reference.
- [`contrib/Events.md`](contrib/Events.md) — PHP events other
  extensions can hook into.
- [`contrib/roadmap-2.x.md`](contrib/roadmap-2.x.md) — feature roadmap
  across the whole bbGuild family.

## Code of Conduct

This project follows the [Contributor Covenant](CODE_OF_CONDUCT.md).
