# Changelog

All notable changes to TI Voting are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- The admin games list shows how each game is set up: where its map comes from (Milty draft, TI4 Lab draft, map string or no map), its expansions (Prophecy of Kings, Thunder's Edge with whether the Fracture is in play, Discordant Stars), whether an agenda is being voted on, and the options that are on, such as Absol's agendas, managed influence or Hidden Agenda off. Each game also shows its number of votes. The game's admin page shows the same.
- The admin games list can be filtered by map, expansion and option, and sorted by newest, last agenda or most players. The filters are kept in the URL.
- An admin can open a game as one of its players from the game's admin page, for example to help a player or fix a game. A banner shows whose game it is, with a Stop button. It ends when the admin stops or logs out, never lands in the admin's list of games, and is logged with the admin's name, as is everything done meanwhile.
- The admin pages show the logo.

## [1.10.1] - 2026-10-25

### Added
- A privacy policy, linked in the footer. It explains what the app stores, for how long, and how to ask about your data. The retention periods it shows come from the app's settings, so self-hosted installs show their own.

### Changed
- The fonts of the agenda cards are served by the app itself instead of by Google Fonts, so visitors' browsers no longer contact Google.

### Fixed
- `laravel-echo` and `pusher-js` are listed as dependencies, so a fresh `npm install` builds again.

## [1.10.0] - 2026-10-25

### Added
- Feedback: anyone can send an idea, a bug or other feedback with the Feedback link in the footer, optionally with a name or contact for a reply. Feedback sent from a game remembers the game. It can be sent five times an hour. Feedback is only on the public site: self-hosted installs don't have it unless `FEEDBACK_ENABLED=true` is set.
- The admin panel has a Feedback page with the feedback, which can be filtered, marked as read or unread and deleted. The menu shows how many are unread, and the dashboard shows the most recent feedback.
- Absol's agendas: the Speaker can switch the agenda list to the agendas of Absol's Lab, which replace the official agendas. Absol's agendas have their own card style, so players see the difference.
- Agenda cards show the deck they come from, e.g. Prophecy of Kings or Absol's Lab.
- Show results to all players: with this Speaker setting on, every player sees the results once voting ends, instead of the Speaker announcing them. Breaking a tie and showing who is affected stay with the Speaker. It is off by default.
- Hidden Agenda, as the event card: votes are secret and only the totals are revealed. It is on by default. With it off, every player sees how each player voted, in the player list and the results, and players are told their vote is public. The Speaker can only change it between agendas, and it only applies to agendas revealed after the change, so it cannot be used to see how players voted on an open or ended agenda.
- Managed influence: with this Speaker setting on, the Speaker has a Start New Agenda Phase button, confirmed in a dialog, that asks every player for their available influence in a dialog they cannot skip. A vote can't spend more influence than the player has, and the votes are taken from it automatically. Abstaining and votes under Representative Government cost nothing. A player with no influence left can only abstain. The Speaker sees who still has to enter their influence, and the setting can only be changed between agendas. With Hidden Agenda on, the setting warns that players seeing each other's influence go down can hint at who voted for what. It is off by default.

### Changed
- Speaker Settings is split into Voting, Agendas and Map sections, and its settings are on/off switches instead of checkboxes. It no longer has a yellow background.
- Speaker Settings is the last tab, and everyone, also the Speaker, starts on the Players tab.
- A player with 0 influence has Abstain selected when voting opens, except under Representative Government.

### Removed
- The Speaker's unused option to view results during voting.

### Security
- A map draft URL is only accepted from milty.shenanigans.be or tidraft.com, and the game stores a clean link made from the draft id. Before, a crafted URL could run a script for anyone who opened the map draft link, the admin included. Existing games only show the link when it is a https URL.
- The draft data of a new game can no longer be changed from the browser, and an agenda's deck must be a known deck.
- Loading a draft is limited to 20 times per 10 minutes per visitor, so the draft sites cannot be flooded through the app.
- A failed Speaker transfer no longer shows the technical error to the player.

### Fixed
- When the last two votes come in at the same moment, voting ends once instead of twice.

## [1.9.0] - 2026-10-24

### Added
- Predictions: before voting opens, a player who predicts an outcome aloud, with a Rider (Construction, Diplomacy, Imperial, Leadership, Politics, Technology, Trade or Warfare) or The Nekro Virus's Galactic Threat ability, records it in the app. Every player sees who predicted which outcome, with which card and what they get if they are right. The Speaker can record a prediction for another player, and a prediction made by mistake can be removed until voting opens.
- A player who predicted cannot vote on that agenda, as the cards say, and does not keep voting open. The player list shows them as Predicted.
- After voting, the Speaker sees which predictions are correct. On a tie the Speaker breaks the tie first.
- When The Nekro Virus predicted correctly, the Nekro player sees which players voted for that outcome, with their factions, to take a technology from. The Speaker sees them with the prediction too. It is shown until the next agenda is revealed.
- Available influence: every player enters how much influence they have by tapping the blue hexagon next to their name at the top of the game page, and it is shown to them as a reminder when voting. Every player sees it on each player's card in the Players tab, as a blue hexagon like the influence value on a TI4 planet card.

## [1.8.0] - 2026-10-18

### Added
- Invite players: every player can open a QR code of the game's join link from the game header, for others to scan with their phone camera. The dialog also shows the game code and can copy or share the join link. The QR code is made by the app itself, so the link is not sent to an outside service.
- Agendas that affect players by how they voted, such as Mutiny, Unconventional Measures or Conventions of War (an effect with "each player who voted For" or "Against"): after voting ends, the Speaker sees which outcome won and can show who is affected, i.e. the winning outcome's effect and the players who voted the way it names. On a tie, also when no votes were cast, the Speaker first picks the winning outcome.

### Changed
- Voting ends by itself once every player who may vote has voted, so the Speaker no longer has to press End Voting. End Voting is still there to end voting early.
- The game screen is less cluttered: Speaker Settings, the players and the Star Chart are tabs below the current agenda, which stays visible. Create New Agenda and its form stay above the tabs. The Speaker starts on the Speaker tab, other players on Players, and the chosen tab is remembered on that device for each game. Players who only have the players list see no tabs.
- The tabs have icons, and the Speaker's tab is called Speaker Settings.
- A cleaner player list: compact rows with an avatar, Speaker and You badges, and a Voted, Waiting or Cannot vote status for each player. The header shows how many players voted. Make Speaker and Remove from game are in a menu on each player, so they are not tapped by accident.
- A tidier game header: tap the game code to copy it, the map draft is a button, and a card shows who you play as, with your faction and whether you are the Speaker.
- Change Map moved from the header to Speaker Settings and is called Change Map Data. The Speaker's screen updates right after changing it.
- The Speaker's "When" and "After" action card steps (and The Argent Flight step) are shown next to the agenda card instead of in Speaker Settings, with Discard Agenda right beside the Done button.
- When Representative Government ties, the Speaker breaks the tie in the results; if For wins, the law is put in effect automatically.

### Fixed
- A faction another player already chose can no longer be picked when joining. The faction list updates while the join page is open, a faction taken in the meantime is cleared with a message, and taken factions are shown as unavailable with who took them. Two players joining at the same moment can no longer get the same faction.
- The Change Map Data dialog dims the page behind it again instead of covering it in black.

## [1.7.0] - 2026-10-18

### Added
- Besides a Milty draft URL, a game can get its map from a TI4 Lab draft URL (tidraft.com), with its players, factions and slices, or from a map string as other draft tools export it: tile numbers separated by spaces or commas. Factions come from the home systems on the map. Planet agendas, faction choice and Star Chart work with all three. Use the same field when creating a game, or Change Map on the game page.

## [1.6.0] - 2026-10-18

### Added
- A phone can play in several games: the homepage lists every game it is in, most recent first, with whether an agenda is open. A game can be removed from that list; the player stays in the game.
- Star Chart, from the separate TI4 Star Chart Randomizer: in games with a Milty draft, any player can draw a random blue system tile that is not on the map yet, from the expansions in the draft. Every player sees the drawn tile with its planets, tiles are never drawn twice, and their planets can be elected on planet agendas. The Speaker can undo a draw.
- A Home link on the game page, also handy in the installed app, which has no back button.

### Changed
- Players no longer type a faction. In a game with a Milty draft they choose the faction they drafted from a list, which shows the drafter's name and leaves out factions other players already chose. Games without a Milty draft have no factions. Players without a drafted faction, for example after the Speaker added the Milty draft later, are asked to choose one on the game screen.
- The Milty draft loads as soon as the URL is entered when creating a game, and the field is labelled correctly.

### Fixed
- Planet agendas no longer fail when the Milty draft is not finished and some players have not picked a slice yet.
- Players no longer lose access to their game after 2 hours without activity, for example between agenda phases. The games a browser plays in are kept in their own cookie for 30 days, renewed on every visit, instead of in the session.
- The app icons did not load on Apache servers, which reserve the `/icons/` path for their own icons. They are now served from `/app-icons/`.

## [1.5.0] - 2026-10-18

### Added
- A logo: an agenda card going into a ballot slot on a hex tile. It is shown on the homepage and in the README.
- The site can be installed as a web app: "Add to Home Screen" on iPhone or "Install app" on Android opens it full screen with its own icon. The homepage and the game screen show how to install it; the hint can be dismissed.
- The homepage offers "Continue your game" when this phone is already playing a game, so the installed app takes you straight back.
- A friendly offline page when a page cannot load without a connection.

### Changed
- The favicon, browser tab icon and iPhone home screen icon use the new logo instead of the Laravel logo.

## [1.4.2] - 2026-10-18

### Added
- Rejoining: a player who lost access (cleared browser, other phone), including the Speaker, can ask to rejoin under their name through the game's rejoin link or by joining with their taken name. Another player in the game confirms it is them, and they are let back in. Every player can copy the rejoin link from the game screen.
- On a game's admin page, an admin can make any player the Speaker and remove players.
- When The Argent Flight is in play (in the Milty draft or entered as a player's faction), the Speaker gets an extra step before voting opens: The Argent Flight votes first and announces their votes publicly.
- Representative Government: when this law passes For, from then on each player casts 1 vote on each agenda instead of spending influence. A banner on every player's game screen shows that the law is in effect. The Speaker can also turn it on or off by hand, for example after a tie or when the law is repealed.
- `GAME_RETENTION_DAYS` (default 7) and `LOG_DATABASE_DAYS` (default 30) in `.env` set how long games and log entries are kept.
- Creating games is limited to 10 per hour and joining to 30 attempts per 10 minutes per IP address.

### Changed
- The preset agenda picker is a searchable dropdown: type to filter by name or effect text, use the arrow keys and Enter, or open the full list with the arrow button.
- The nightly cleanup deletes games that have had no activity for the retention period, instead of games created before it, so long games are no longer deleted while being played.
- The Twilight Imperium Features block is removed from the homepage.

### Fixed
- A removed player is taken off an open "Elect Player" agenda, unless someone already voted for them. Removing the Speaker passes the Speaker token on.
- The "Change Milty URL" control no longer errors for a player who was removed from the game.

## [1.4.1] - 2026-10-18

### Added
- The URL path of the admin dashboard can be set with `ADMIN_PATH` in `.env`, e.g. `ADMIN_PATH=beheer` serves it at `/beheer` instead of `/admin`.

## [1.4.0] - 2026-10-18

### Added
- The Speaker can remove a player from the game. Their votes are removed too, and the removed player is sent back to the join page.
- While checking "When" and "After" action cards, the Speaker can block a player from voting on the current agenda, for example after Assassinate Representative. Blocked players cannot vote and are not counted in the voting progress.
- Admin dashboard at `/admin`, behind a login. Accounts are created with `php artisan admin:create`. It shows stats (games, players, agendas, votes, games per day, most played agendas, recent errors), lets admins search, inspect and delete games, run the cleanup of old games, and review logs.
- Logs are stored in the database and shown in the admin dashboard, with filters for level, game and text. Entries older than 30 days are deleted every night.
- Game actions are logged with the game code and player, such as games created, players joining, agendas revealed, votes cast and voting ended.
- The footer links to the GitHub repository and shows the copyright.

### Changed
- The Speaker controls are redesigned for phones, with full-width buttons, larger tap targets and voting types as selectable cards.
- Logs are written to the database and to daily log files kept for 14 days, instead of one `laravel.log` file. Set `LOG_STACK=database,daily` in `.env` and run the migrations.

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

[1.10.1]: https://github.com/burnacid/ti-voting/compare/v1.10.0...v1.10.1
[1.10.0]: https://github.com/burnacid/ti-voting/compare/v1.9.0...v1.10.0
[1.9.0]: https://github.com/burnacid/ti-voting/compare/v1.8.0...v1.9.0
[1.8.0]: https://github.com/burnacid/ti-voting/compare/v1.7.0...v1.8.0
[1.7.0]: https://github.com/burnacid/ti-voting/compare/v1.6.0...v1.7.0
[1.6.0]: https://github.com/burnacid/ti-voting/compare/v1.5.0...v1.6.0
[1.5.0]: https://github.com/burnacid/ti-voting/compare/v1.4.2...v1.5.0
[1.4.2]: https://github.com/burnacid/ti-voting/compare/v1.4.1...v1.4.2
[1.4.1]: https://github.com/burnacid/ti-voting/compare/v1.4.0...v1.4.1
[1.4.0]: https://github.com/burnacid/ti-voting/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/burnacid/ti-voting/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/burnacid/ti-voting/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/burnacid/ti-voting/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/burnacid/ti-voting/releases/tag/v1.0.0
