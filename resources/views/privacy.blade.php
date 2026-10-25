<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy - TI4 Hidden Agenda</title>
    @include('partials.icons')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    @php($issues = 'https://github.com/burnacid/ti-voting/issues')
    @php($link = 'text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <a href="{{ route('home') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    ← Back to Home
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mt-4">Privacy Policy</h1>
                <p class="text-gray-600 dark:text-gray-300 mt-2">Last updated 25 October 2026</p>
            </div>

            {{-- Retention periods come from the config, so a self-hosted install shows its own settings --}}
            <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 sm:p-8 space-y-8 text-gray-700 dark:text-gray-300 leading-relaxed">
                <section class="space-y-3">
                    <p>
                        TI4 Hidden Agenda helps groups play the agenda phase of Twilight Imperium 4 on their phones.
                        It is made to need as little personal data as possible: there are no accounts for players,
                        no advertising, no analytics and no tracking.
                    </p>
                    <p>
                        This site is run by Stefan Lenders, who decides what data is used and why
                        (the "controller" under the General Data Protection Regulation, the GDPR).
                        The data is stored on servers in the European Union.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">What is stored when you play</h2>
                    <ul class="list-disc pl-5 space-y-2">
                        <li><strong class="text-gray-900 dark:text-white">The game:</strong> its name, its code, and the map you load from a Milty draft, a TI4 Lab draft or a map string. A draft can contain the names of the players in that draft.</li>
                        <li><strong class="text-gray-900 dark:text-white">You as a player:</strong> the name you enter, your faction, the influence you enter, and whether you are the Speaker. You can use a nickname; it doesn't have to be your real name.</li>
                        <li><strong class="text-gray-900 dark:text-white">The agendas:</strong> the agendas, your votes and the influence you spent, your predictions, and the Star Chart tiles you draw.</li>
                    </ul>
                    <p>
                        Other players in your game see your name, faction and influence, and, depending on the game's settings, the results and how you voted.
                        With Hidden Agenda on, which is the default, other players don't see how you voted, only the totals.
                    </p>
                    <p>
                        A game is deleted automatically, with its players, agendas and votes,
                        after {{ \App\Models\Game::retentionDays() }} {{ Str::plural('day', \App\Models\Game::retentionDays()) }} without any activity.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Cookies and storage on your device</h2>
                    <p>Only the cookies and storage the site needs to work are used. They are never used to track you or for advertising.</p>
                    <ul class="list-disc pl-5 space-y-2">
                        <li><strong class="text-gray-900 dark:text-white">Session cookie:</strong> keeps you connected while you use the site and protects forms against misuse. It expires {{ config('session.lifetime') }} minutes after your last visit.</li>
                        <li><strong class="text-gray-900 dark:text-white">Games cookie:</strong> remembers which games this browser plays in, so you get back into your game. It is encrypted, kept for 30 days and renewed when you open a game. You can remove a game from the list on the homepage.</li>
                        <li><strong class="text-gray-900 dark:text-white">Browser storage:</strong> remembers the tab you had open in a game and whether you closed the "install the app" hint. This never leaves your device.</li>
                        <li><strong class="text-gray-900 dark:text-white">Offline page:</strong> if you install the site as an app, it keeps a page to show when your phone is offline.</li>
                    </ul>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Technical data</h2>
                    <p>
                        To run the site and protect it against abuse, your IP address and browser type are stored with your session,
                        and your IP address is used to limit how often games can be created, joined or loaded from a draft.
                        Sessions are deleted after they expire, and the limits are forgotten within an hour.
                    </p>
                    <p>
                        The site keeps a log of what happens in games, such as who joined and how they voted, to fix problems.
                        The log is deleted after {{ \App\Models\LogEntry::retentionDays() }} days.
                        Only the admin of this site can see the log and the games, including votes, and only looks at them to fix problems.
                        To help a player or fix a game, the admin can open a game as one of its players; this is logged.
                    </p>
                </section>

                @if(config('app.feedback'))
                    <section class="space-y-3">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Feedback</h2>
                        <p>
                            When you send feedback, your message, the name or contact you choose to add, the page and game you sent it from,
                            and your browser type are stored. It is only read by the admin of this site to improve it,
                            and deleted when it is no longer needed.
                        </p>
                    </section>
                @endif

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Other services</h2>
                    <p>
                        When a game loads a Milty draft (milty.shenanigans.be) or a TI4 Lab draft (tidraft.com), this server fetches the draft from that site.
                        Your browser doesn't contact those sites unless you open the draft link yourself; then their own privacy policy applies.
                        Fonts and all other files are served by this site itself. Your data is not sold or shared with anyone else.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Why this data may be used</h2>
                    <p>
                        The data is used because it is needed to provide the game you ask for, and because of the legitimate interest
                        in keeping the site working and safe (Article 6(1)(b) and (f) GDPR). Feedback is only stored because you choose to send it.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Your rights</h2>
                    <p>
                        You have the right to see the data about you, to have it corrected or deleted, to object to its use,
                        and to receive it in a usable format. As there are no accounts, it helps to tell us the game code and your player name.
                        Most data is deleted automatically within days, and the Speaker can remove a player from a game at any time.
                    </p>
                    <p>
                        To use your rights or ask a question, open an issue on <a href="{{ $issues }}" target="_blank" rel="noopener noreferrer" class="{{ $link }}">GitHub</a>
                        asking to be contacted about privacy. Issues are public, so don't post personal details in them; you'll be contacted to continue privately.
                    </p>
                    <p>
                        You can also file a complaint with your data protection authority. In the Netherlands, that is the
                        <a href="https://autoriteitpersoonsgegevens.nl" target="_blank" rel="noopener noreferrer" class="{{ $link }}">Autoriteit Persoonsgegevens</a>.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Changes</h2>
                    <p>
                        This policy may change when the site changes. The date at the top shows when it was last updated.
                        The source code of the site, including this page, is public on
                        <a href="https://github.com/burnacid/ti-voting" target="_blank" rel="noopener noreferrer" class="{{ $link }}">GitHub</a>.
                    </p>
                </section>
            </article>
        </div>
    </div>

    @include('partials.footer')
</body>
</html>
