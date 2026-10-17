# Changelog

All notable changes to TI Voting are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.0] - 2026-10-18

### Added
- Thunder's Edge games are detected from the Milty draft (TE tiles or TE factions enabled). In those games, the Speaker can mark The Fracture as in play; from then on its planets (Cocytus, Lethe, Phlegethon and Styx) can be elected on planet agendas, including an agenda that is already open. Unmarking it removes them again, except planets that players already voted for.
- The Fracture planets can also be added manually through the custom tile numbers 125, 126 and 127.
- The current agenda is shown as a digital agenda card, styled like the printed cards, with its type (Law or Directive) on the ribbon. Custom agendas ask the Speaker to choose Law or Directive. Elect agendas show what is elected in bold, e.g. "Elect Player" or "Elect Cultural Planet".
- "Classified Document Leaks" now elects a scored secret objective instead of a For/Against vote.
- Before voting starts, the Speaker is prompted to ask for "When an agenda is revealed" action cards, and then for "After an agenda is revealed" cards. Voting stays locked until the Speaker marks both as done.
- The Speaker can discard an agenda before voting starts, for example when it is vetoed.
- The app version is shown at the bottom of each page.

### Fixed
- Mecatol Rex was missing from "Elect a Planet" agendas.
- "Colonial Redistribution" now correctly leaves Mecatol Rex out of its options.
- Thunder's Edge planets with two traits (Olergodt, Vira-Pics III, Lesab, Tinnes and Tarana) were missing from industrial, cultural and hazardous planet agendas.
- "Elect a Planet" agendas showed only non-home planets instead of all planets.
- Votes for an option that is not on the agenda are rejected.
- Voting twice on the same agenda shows an error instead of crashing.
- After voting ends, the results stay visible to the Speaker only, and the Speaker is asked to announce them.
- After ending voting, the Speaker can create a new agenda again; the Speaker controls no longer disappear.
- Only the Speaker can see and use the "Change Milty URL" button.
- Creating a game with a Milty URL that cannot be loaded shows an error instead of creating the game without draft data.
- Requests to the Milty API time out after 10 seconds.

### Removed
- The unused `/group/{group}` route.

## [1.2.0] - 2026-03-01

### Added
- The Speaker can change the Milty draft URL of a running game.
- Games older than 7 days are cleaned up automatically.
- README with setup and usage instructions.

### Fixed
- Milty draft data is saved correctly when the URL is changed.

## [1.1.0] - 2026-02-28

### Added
- Preset agendas: pick an agenda card from a library of TI4 agendas to fill in the title, description and vote options.
- Real-time updates over WebSockets (Laravel Reverb). Dashboards refresh when players join or vote.
- Agenda descriptions keep their line breaks.

### Fixed
- Loading Milty drafts after changes to the Milty API, including draft IDs that contain underscores.
- Planet data updated with the latest tiles.
- Planet agendas fall back to custom options when the game has no Milty draft.

## [1.0.0] - 2025-08-24

### Added
- Create and join games with a game code; no accounts required.
- Secret voting on agendas with influence tracking.
- Speaker controls: create agendas, end voting, transfer the Speaker role.
- Milty draft integration to load factions and planets.
- Planet agendas (any, industrial, cultural, hazardous, non-home), with extra tiles that can be added by tile number.

[1.3.0]: https://github.com/burnacid/ti-voting/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/burnacid/ti-voting/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/burnacid/ti-voting/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/burnacid/ti-voting/releases/tag/v1.0.0
