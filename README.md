# Bible Tracker

**Demo:** https://wordofgod.in/bible-tracker/

A mobile-friendly PHP web app (installable PWA) for creating Bible reading plans, reading the Bible, and tracking progress. Part of [WordOfGod.in](https://wordofgod.in/).

## Features
- **Reading plans** — create, edit and delete plans; tick off each chapter and see progress per plan.
- **Reader** — read the plan's next chapter in-app, choose the Bible translation, resize the text, mark chapters read, and open the Bible Quiz for the chapter.
- **Printable PDF plans** — on the Download page (the **PDF** link in the header), anyone (no sign-in) can create an A4 PDF reading plan with their own header and sub header, and download it as `…-from-wordofgod.in.pdf`.
- **Leaderboard & achievements** — lifetime chapters read, star tiers (Silver to Diamond), editable display name.
- **Languages** — English, தமிழ், हिंदी, ಕನ್ನಡ (interface, book names and PDFs). The brand name "Bible Tracker" and the tagline stay in English everywhere.
- **Light / dark theme**, font size and last-used language are saved per user.
- **Admin screen** — metrics, users, leaderboard moderation, name-change history, achievement thresholds.
- **PWA** — installable, works as a web app on Android/iOS (and inside a Kodular WebView).

## Requirements
- PHP 8.1+ with the `curl` extension (no database, no Composer)
- Apache/Nginx, or `php -S` for local development
- Sign-in uses the shared Google auth service from the sibling `auth/` folder (`../auth/session.php`, same as [bible-quiz-app](../bible-quiz-app)). Without it the "Sign in" button is disabled.

## Configuration (`config.php`, gitignored)
Copy `config.example.php` to `config.php`. All keys are optional:

| Key | Meaning |
|---|---|
| `test_mode` | `true` = behave as if `test_user` is signed in. Only honoured on `localhost` / `127.0.0.1`. |
| `test_user` | The fake signed-in user (default: Yesudas Solomon, <YOUR-EMAIL>@gmail.com). |
| `admins` | Extra admin emails. `<YOUR-EMAIL>@gmail.com` is always an admin. |

## Local development
```bash
cp config.example.php config.php   # then set 'test_mode' => true
php -S localhost:8080
```
Open `http://localhost:8080/`.

### Testing mode
With `'test_mode' => true` in `config.php` **and** a `localhost`/`127.0.0.1` host, the app behaves as if *Yesudas Solomon (<YOUR-EMAIL>@gmail.com)* is already signed in (a "TEST MODE" badge shows in the header). The flag is ignored on any other host, so it can't be left on by accident in production. Edit `test_user` in `config.php` to use a different identity.

## Languages and Bible text
- The interface, book names and PDFs are available in **English, தமிழ், हिंदी and ಕನ್ನಡ**. The language follows the user's last choice (the language icon in the header, or the Language field on a plan) and is saved in their user file; signed-out visitors get a cookie. Translations live in `includes/i18n.php`; book names in `data/book-names.json`.
- The reader loads chapter text from the WordOfGod bibles API (the sibling `../bibles` folder if present, otherwise wordofgod.in). The translations offered per language come from `data/bible-translations.json`: entries with `"hide": true` are not shown, and the entry with `"isDefault": true` is selected first. The user's last choice per language is remembered.

## Creating a plan
| Scope | How to read |
|---|---|
| Whole Bible · Old Testament · New Testament · Selected Books · Selected Chapters · Morning one book, Evening a different book | Continuous · Read X chapters per day · Complete in X days |

Everything for a user (plans, progress, theme, font size, language, preferred Bible translations) is saved in one file in `users/` (filename = hash of the Google email). A plan stores only its settings and ticked chapters; the day-by-day schedule is regenerated from them. To avoid false filling, at most 66 chapters (Psalms excluded) can be newly marked read per day.

## Printable PDF plans (`download.php`)
Choose the language, scope (book lists have **Select all**, also per Testament) and how to read (optionally add a header and sub header under **Optional**), then press **Create PDF**. A progress bar shows while the PDF is built, and a **Download PDF** link appears when it is ready.
- The PDF is **A4** with the plan in **2 columns**; **continuous** plans use **3 columns** to save pages. The footer has the page number on the left and a clickable `Bible Tracker` link (https://wordofgod.in/bible-tracker/) on the right.
- It is built in the browser (so Tamil, Hindi and Kannada are shaped correctly) with jsPDF + html2canvas from cdnjs, uploaded to `pdf-temp/` in one request (in small chunks only if the server's upload limit refuses a single file), and offered as a normal download link (`api/pdf-download.php`), which also works inside the Kodular Android WebView.
- Files in `pdf-temp/` are deleted automatically (after 1 hour on each new PDF, plus a before-today sweep when **Create PDF** is pressed). The PDF is made of page images, so text can't be selected.

## Testing
Run the app locally in testing mode (above) and click through every page. Before deploying, copy the project to a clean folder without `config.php`, `bt-secret.php` and the data in `users/`, `counters/`, `logs/`, run it with `php -d display_errors=1 -d error_reporting=-1 -S localhost:8080`, and check that no page or API call produces a PHP warning. More detail is in [AGENTS.md](AGENTS.md).

## Admin
`a.php` (linked as "Admin" in the menu for admins): metrics, users (edit / delete), leaderboard moderation with name-change history, and achievement thresholds. `<YOUR-EMAIL>@gmail.com` is an admin by default; add others with `'admins' => ['x@y.com']` in `config.php`.

## Folder structure
```
index.php          home: landing (signed out) / My Plans (signed in) + leaderboard widget
plan-edit.php      create + edit plan form (fields shared via includes/plan-form.php)
plan.php           track a plan (tick chapters)
read.php           in-app Bible reader for a plan
download.php       custom printable PDF plans
leaderboard.php    leaderboard + achievements
a.php              admin screen
bot.php            honeypot (shared with bible-quiz-app)
api/               progress, settings, profile, stats, bible, plan-schedule, pdf-store / pdf-download / pdf-cleanup
includes/          bible-books, i18n, plans, leaderboard, admin, plan-form, pdf-temp-store, google-auth-client, app-config, visitor-counter
assets/{css,js,images}
data/              book-names.json, bible-translations.json
users/  counters/  logs/  pdf-temp/   (runtime data, gitignored; .htaccess guards)
counter.php  bot.php  footer-links.php  google-analytics.php  menu-links.php  version.php   (shared with bible-quiz-app)
manifest.json  sw.js   (PWA)
```

## Security
Secrets and runtime data are kept out of git (`.gitignore`) and out of the web (`.htaccess` files in the project root and in `includes/`, `users/`, `counters/`, `logs/`, `pdf-temp/`, `data/`). Signed-in actions are CSRF-protected, uploads are limited to PDFs, outbound requests only go to wordofgod.in, and third-party scripts are pinned with integrity hashes. After deploying, confirm that `/config.php`, `/bt-secret.php`, `/includes/plans.php`, `/users/` and `/counters/counter.txt` are **not** reachable. Details: [AGENTS.md](AGENTS.md#security).

## Deploying
Upload the files (not `config.php`, `bt-secret.php`, `users/*`, `counters/*`, `logs/*`, `pdf-temp/*`). Copy `google-analytics.example.php` to `google-analytics.php` and put your own Measurement ID in it (the app works without the file). Make `users/`, `counters/`, `logs/` and `pdf-temp/` writable by the web server. Bump `version.php` on each release (cache-busting for CSS/JS/manifest).
