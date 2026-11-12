# Agent guide — Bible Tracker

Plain PHP (8.1+), no framework, no database, no Composer. Mobile-first PWA; one JSON file per user. Part of WordOfGod.in and modelled on `../bible-quiz-app` (same footer/menu/analytics/bot/version files, same Google sign-in service, same `pdf-temp` concept).

## Pages and endpoints

**Pages** (each: `require counter.php` → `partials.php` → `version.php`, then `renderBtHeader()` … `renderBtFooter()`):

| File | What it does |
|---|---|
| `index.php` | Landing (signed out: a "Create & track your own plans" card + a "Download plans" card that opens the PDF maker) / **My Plans** (signed in: cards with Read / Track / Edit / Delete) + leaderboard widget (podium + ranks 4-10). Delete is a POST to itself. |
| `plan-edit.php` | Create / edit a plan (POST to itself). Fields come from `includes/plan-form.php`. |
| `plan.php` | Tracker: book rows with chapter tick boxes ("Select all" per book), collapsible daily schedule. |
| `read.php` | In-app reader for a plan: Bible translation picker, text size, Mark as read, Attend Quiz (embeds the bible-quiz embed for the chapter), previous/next. |
| `download.php` | **Printable PDF plans only** (the ready-made presets and CSV export were removed). |
| `leaderboard.php` | Leaderboard table (`?view=all`) + the Achievements section (`#achievementsSection`; the header profile picture links here). |
| `a.php` | Admin screen (see Admin). |
| `bot.php` | Honeypot (shared with the quiz app; link hidden in `footer-links.php`). |

**API** (`api/`; every mutation checks `btCheckCsrf()` unless noted):

| File | Purpose |
|---|---|
| `progress.php` | POST `id, keys ("book:chapter,…"), done, csrf` → tick/untick chapters; enforces the daily limit; updates stats, counters, leaderboard. |
| `settings.php` | POST `theme`, `font_size` (13-33 or `reset`), `bible_lang`+`bible`, `plan_lang` (the UI language). |
| `profile.php` | POST `display_name` (leaderboard name; logged in the name-change history). |
| `stats.php` | POST `event=quiz` → quizzes-attempted counter. |
| `bible.php` | GET `book, chapter, lang, bible` → chapter text proxy (signed-in only). |
| `plan-schedule.php` | POST plan-form fields → schedule JSON for the PDF (public, no CSRF; read-only). |
| `pdf-store.php` / `pdf-download.php` / `pdf-cleanup.php` | Chunked PDF upload into `pdf-temp/`, download, cleanup (see PDF). Public. |

## Data model

- **Auth:** `includes/google-auth-client.php` bridges to the sibling `../auth/` service (`btGoogleUser/LoginUrl/LogoutUrl`). **Testing mode:** `config.php` (`test_mode`, `test_user`, `admins`; gitignored; template `config.example.php`) is honoured only when `btIsLocalhost()`; then `btGoogleUser()` returns the fixed user. Loader is `includes/app-config.php` — never name a shared include `config.php` (deploy filters that skip `config.php` would drop it).
- **User file:** `users/{sha256(email)[0:32]}.json` = `{email, name, picture, createdAt, plans[], settings{theme, fontSize, planLang, bibles{lang:abbr}}, stats{chaptersRead, booksCompleted}, daily{date, keys[]}, displayName?, leaderboardHidden?}`. Written only through `btMutateUserData()` / `btMutateUserPlans()` (exclusive lock; the plans-only wrapper preserves everything else). `btSyncProfile()` creates the file on first sight of a user and keeps name/picture current. `users/` has `.htaccess` (Require all denied) and a 403 `index.php` so it can never be listed.
- **Plan:** `{id, name, lang, scope, books, ranges, mode, perDay, days, split, morningBooks, eveningBooks, doneChapters["book:chapter"], created, updated}`. Only settings + ticks are stored; the schedule comes from `btPlanDays()` — never persist it. Scopes: whole/ot/nt/books/chapters, plus the form-only `two-books` (stored as `split: 'two-books'`; it ignores `scope`). Modes: continuous / perday / days. `split: 'morning-evening'` is kept for old plans but has no UI. Changing schedule-affecting fields in `plan-edit.php` resets `doneChapters` (name/lang changes don't). `btNormalizePlan()` upgrades old `doneDays` plans.
- **Daily limit (anti false-filling):** at most `BT_DAILY_CHAPTER_LIMIT` (66) chapters can be *newly* ticked per day across all plans, Psalms (`BT_PSALMS` = 19) excluded; unticking a chapter ticked today gives the slot back. Tracked in `daily`. Over the limit → HTTP 429 with a translated message.
- **Books & Bible text:** `includes/bible-books.php` — 66 books + chapter counts; names per language from `data/book-names.json`; `btFetchChapter()` proxies the WordOfGod bibles API (sibling `../bibles` first, else wordofgod.in; localhost goes straight to production). Translations per language come from `data/bible-translations.json` via `btTranslations()`: entries with `"hide": true` are never offered; the default is the first visible `isDefault`, else the first visible. A user's last choice per language is saved in `settings.bibles`.
- **Counters:** `includes/visitor-counter.php` (`btReadCounter/btIncrementCounter`, keyed `btReadMapCounter/btIncrementMapCounter`; files `counters/{name}.txt`). All-time, never decreased: `counter` (visitors, non-bots, in `counter.php`), `downloads` (bumped in `api/pdf-download.php`), `signed-in-users` (shown only on the admin Metrics screen, not in the site footer), `plans-created` + `plans-by-language|scope|mode`, `chapters-read` / `chapters-read-by-book` / `books-read` (in `api/progress.php`), `quizzes-attempted` (`api/stats.php`, once per reader page view). `counter.php` reads them into `$…2` variables; `renderBtFooter()` must `global` every variable `footer-links.php` uses. The site footer shows Visitors, Downloads, Plans Created, Books Read, Chapters Read, Quizzes Attempted and the top language / 3 books / scope / reading option; its labels are intentionally English in every language. New counter = new file name, no schema change.
- **Leaderboard & achievements:** `includes/leaderboard.php`. Score = lifetime `stats.chaptersRead` (backfilled from existing ticks the first time). Ranking reads one private master file `logs/leaderboard-data.json` keyed by an HMAC id (no emails on public pages); `btLbUpdate($user)` refreshes a row (after progress and after name edits). Default display name = full Google name; users can edit it. Star tiers: `btAchievementTiers()`; thresholds default in code and are admin-editable (`counters/achievement-thresholds.txt`).
- **Secrets:** `bt-secret.php` (auto-generated, gitignored) is the HMAC key for CSRF tokens and leaderboard ids; falls back to a derived key if the folder isn't writable.

## Admin (`a.php`)
Google-signed-in admins only — `btIsAdmin()` / `btAdmins()` (`<YOUR-EMAIL>@gmail.com` is always admin; add more via `config.php` `'admins'`). Non-admins get 403 and never see the "Admin" menu item. Views: **metrics** (counters + keyed maps, read-only), **users** (search, detail, edit via pencil, delete via trash, listed/hidden), **leaderboard** (edit name/score via pencil, remove/restore, **name-change history** with Revert), **achievements** (thresholds + reset). Tables are `.editable-rows` (pencil toggles inline edit; phones render each row as a card). All POSTs are CSRF-checked; helpers in `includes/admin.php`. `a.php` doesn't include `counter.php`, so admin visits don't inflate visitor counts. English only.

## i18n
`includes/i18n.php` (same pattern as the quiz app's `quiz-i18n.php`): a label table per language (`en`, `ta`, `hi`, `kn`), English fallback, `btT('key', ['name' => …], $lang?)`. UI language = the user's most recent language choice: signed in → `settings.planLang`; signed out → `bt_lang` cookie. Set by saving a plan or by the header language icon (`btLangMenu()`, next to the profile icon, via `api/settings.php` `plan_lang`). The plan/PDF forms relabel live when their Language radio changes (elements carry `data-i18n`; strings in `window.BT_FORM_I18N`, which only includes the key prefixes allowed in `btFormLabelsAllLanguages()`); other scripts use `window.BT_T` / `BT()` (`btJsLabels()`). **New UI text: add the key to every language** (English first). Some Tamil/Hindi/Kannada words are intentionally left in English (the footer metric labels, `site.brand` = "Bible Tracker" and `site.tagline` = "Read the Bible. Track Your Progress. Grow in God's Word." are English in every language) — don't "fix" them. Ta/Hi/Kn strings were machine-drafted and need native review.

## Printable plan PDFs (`download.php`)
The form reuses the plan editor's fields (`includes/plan-form.php`) plus an **Optional** fieldset (Header, Sub header) below "How to read?". **Create PDF** → `api/plan-schedule.php` (schedule JSON in the chosen language; a two-books plan's summary is the short "Morning & evening · in 365 days") → `assets/js/pdf-plan.js` lays out **A4** pages in the browser — **2 columns, 3 for continuous plans** — because the browser shapes Tamil/Hindi/Kannada (PHP PDF libraries can't); each page is rendered with html2canvas and assembled with jsPDF (both from cdnjs); Noto fonts come from Google Fonts. Footer = `Page N` left + clickable `Bible Tracker` (https://wordofgod.in/bible-tracker/) right. A progress bar tracks the stages (schedule → layout → pages → upload; the pause near 88% is PDF assembly plus the single upload, which gives no byte progress). Any change to the form hides the "ready" box. The download is named `<header-slug>-from-wordofgod.in.pdf` (default `bible-reading-plan-from-wordofgod.in.pdf`). The plan form's book lists (Selected Books, Morning/Evening books) have **Select all** for the whole list and under Old / New Testament (`syncBooks()` in `app.js`; the boxes are unnamed so they're never submitted).

Upload/download (the `pdf-temp` concept from the quiz app): the PDF is uploaded **in one request** (`uploadPdf()` → `api/pdf-store.php`, a single write on the server); only if the server refuses it because of its upload limits (HTTP 400/413) does it fall back to **600 KB chunks** (`uploadChunks()`, `id/index/total`, assembled in `pdf-temp/<id>.part`). Files are stored in `pdf-temp/` (`includes/pdf-temp-store.php`; files and stale `.part`s are deleted after 1 hour on every store, plus a before-today sweep from `api/pdf-cleanup.php`, called when Create PDF is pressed), then served by `api/pdf-download.php` over a normal https link with Kodular-WebView-friendly headers (blob: URLs can't be downloaded there). Only real PDFs are accepted (`%PDF-` check); max 300 files / 24 MB. The PDF is made of page images, so text isn't selectable. `sw.js` must never resolve a fetch with `undefined` (it returns `Response.error()` when offline and uncached).

## Front end
- **Theme:** the look is **Fresh Green** (decided); light/dark via `<html data-theme>` set server-side from `settings.theme`; tokens are CSS variables at the top of `assets/css/style.css`, dark overrides near the end.
- **Header:** brand ("Bible Tracker" in every language, Baloo 2, 1.4rem / 500; 32px on phones) + tagline (English in every language), then Home, profile chip (name hidden on phones; picture/icon only), **PDF** link to `download.php`, language icon, theme toggle, menu. On desktop the header content is centred in a 1200px-wide area (the green bar still spans the full width); on phones the header is two lines and not sticky. Footer/nav text is 14.4px.
- **Mobile:** a compact scale at the end of `style.css` (`@media(max-width:600px)`: 14px root, 36px buttons, 32px chapter chips) so more fits on screen. The header's two-line layout switches at 720px.
- **Install App** (PWA) buttons in the footer and menu appear only when the browser reports the app installable (iOS shows instructions).
- **JS:** `assets/js/app.js` (all pages) and `assets/js/pdf-plan.js` (download page only). Escape all output with `btH()`.
- **Optional templates:** `google-analytics.php` (gitignored; copy `google-analytics.example.php`), `menu-links.php`, `footer-links.php` are included only if present, so a missing upload never breaks a page.

## Security
- **Web-server rules:** root `.htaccess` denies `config.php`, `bt-secret.php`, `*.log`, `error_log`, `*.psd`; `includes/`, `users/`, `counters/`, `logs/`, `pdf-temp/` and `data/` each have `Require all denied`. These rely on Apache reading `.htaccess` (`php -S` ignores them) — after deploying, check that `/config.php`, `/bt-secret.php`, `/includes/plans.php`, `/users/`, `/counters/counter.txt`, `/logs/…` and `/bot.log` return 403/404.
- **CSRF:** every state-changing endpoint for signed-in users checks `btCheckCsrf()` (HMAC of the user's email with `bt-secret.php`; not session-bound). The anonymous PDF endpoints (`plan-schedule`, `pdf-store`, `pdf-cleanup`) change no user data.
- **Test mode** requires the connection itself to come from loopback *and* a localhost Host header (the Host header alone is client-controlled).
- **Outbound requests:** `btFetchChapter()` only calls wordofgod.in; the Host header is never used to build a URL for another host. Book/chapter/translation are validated first.
- **Uploads:** `api/pdf-store.php` accepts only `%PDF-` content (12 MB, 150 files max, deleted after 1 hour), stores under generated names and is served back as an attachment with `nosniff`.
- **Output:** all HTML output is escaped with `btH()`; `pdf-plan.js` escapes user text with `esc()` before `innerHTML`.
- **Headers:** `renderBtHeader()` sends `X-Content-Type-Options`, `Referrer-Policy` and `X-Frame-Options: SAMEORIGIN`.
- **Third-party scripts** (jsPDF, html2canvas, bootstrap-icons) are version-pinned with Subresource Integrity hashes — **recompute the `integrity` value whenever a version changes** (`curl -sL URL | openssl dgst -sha384 -binary | openssl base64 -A`). Google Fonts can't be SRI-pinned.
- **Privacy:** per-user data (email, name, picture) is in `users/`; the name-change log in `logs/` and `bot.log` contain IP addresses. None of these folders are committed.

## Conventions
- Runtime data dirs (`users/ counters/ logs/ pdf-temp/`) are gitignored except their `.htaccess` (`Require all denied`). Never commit user files, `config.php`, `bt-secret.php`.
- Bump `version.php` on every release (cache-busting for CSS/JS/manifest).
- Shared with bible-quiz-app: `bot.php`, `footer-links.php`, `menu-links.php`, `google-analytics.php`, `version.php`.

## Testing
Run the app with `cp config.example.php config.php` (set `test_mode` true) → `php -S localhost:8080` (`php -S` ignores `.htaccess`).

Before handing over, **simulate a fresh server**: copy the project into a subfolder **without** `config.php`, `bt-secret.php`, `users/*.json`, `counters/*.txt`, `logs/*.json` (and even without `google-analytics.php`), run `php -d display_errors=1 -d error_reporting=-1 -S …`, hit every page and API signed out and in test mode, and require zero PHP warnings/notices. Cover: create (all scopes/modes/two-books), edit, delete, tick/untick, the daily limit, settings, profile/leaderboard, admin actions (as admin, as non-admin → 403, bad CSRF), every language, and the PDF flow (plan-schedule errors, chunked store/download, cleanup). The PDF itself can be exercised end to end in headless Chrome (open `download.php`, fill the form, click Create PDF, wait for the link). Screenshot phone widths (390px) when changing CSS.
