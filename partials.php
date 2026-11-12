<?php
require_once __DIR__ . '/includes/google-auth-client.php';
require_once __DIR__ . '/includes/plans.php';
require_once __DIR__ . '/includes/i18n.php';

function btH(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** The language-change icon + dropdown shown in the header (next to the profile icon). */
function btLangMenu(?array $user): void
{
    $cur = btUiLang();
    ?><div class="lang-menu">
            <button class="icon-btn" id="langToggle" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="langPop" aria-label="<?= btH(btT('nav.language')) ?>" title="<?= btH(btT('nav.language')) ?>" data-csrf="<?= $user ? btH(btCsrf($user)) : '' ?>"><i class="bi bi-translate"></i><span class="lang-label"><?= btH(BT_LANGUAGES[$cur]) ?></span></button>
            <ul class="lang-pop" id="langPop" role="menu" hidden>
            <?php foreach (BT_LANGUAGES as $code => $label): ?>
                <li role="none"><button type="button" role="menuitemradio" aria-checked="<?= $code === $cur ? 'true' : 'false' ?>" data-lang="<?= $code ?>" class="<?= $code === $cur ? 'active' : '' ?>"><?= btH($label) ?></button></li>
            <?php endforeach; ?>
            </ul>
        </div>
<?php
}

/** Renders <head> + opening <body> + top bar. $user may be null (signed out). */
function renderBtHeader(string $title, ?array $user, string $version, string $bodyClass = ''): void
{
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Frame-Options: SAMEORIGIN'); // no clickjacking of the app / admin screen
    }
    if ($user) {
        btSyncProfile($user);
    }
    $settings = btUserSettings($user);
    // Sign-out link (shown in the menu); none in testing mode or when the auth service isn't installed.
    $logout = $user ? btGoogleLogoutUrl(preg_replace('#[^/]*$#', '', btCurrentUrl())) : null;
    ?><!DOCTYPE html>
<html lang="<?= btH(btUiLang()) ?>" data-theme="<?= btH($settings['theme']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= btH($title) ?></title>
    <meta name="description" content="Bible Tracker - create Bible reading plans and track your progress. From WordOfGod.in">
    <meta name="theme-color" content="<?= $settings['theme'] === 'dark' ? '#12301f' : '#1f8a5b' ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Bible Tracker">
    <link rel="manifest" href="manifest.json?v=<?= btH($version) ?>">
    <link rel="icon" type="image/png" href="assets/images/icon-192.png">
    <link rel="apple-touch-icon" href="assets/images/icon-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-4LISF5TTJX/fLmGSxO53rV4miRxdg84mZsxmO8Rx5jGtp/LbrixFETvWa5a6sESd" crossorigin="anonymous">
    <link href="assets/css/style.css?v=<?= btH($version) ?>" rel="stylesheet">
    <?php if (is_file(__DIR__ . '/google-analytics.php')) { include __DIR__ . '/google-analytics.php'; } /* optional: skipped if not uploaded */ ?>
</head>
<body class="<?= btH($bodyClass) ?>">
<header class="topbar">
    <div class="brand-block">
        <a class="brand" href="./"><span><?= btH(btT('site.brand')) ?></span></a>
        <p class="header-tagline"><?= btH(btT('site.tagline')) ?></p>
    </div>
    <div class="topbar-right">
        <a class="home-link<?= $user ? '' : ' solo' ?>" href="./" title="<?= btH(btT('nav.home')) ?>"><i class="bi bi-house-door"></i><span><?= btH(btT('nav.home')) ?></span></a>
        <?php if ($user): ?>
            <a class="user-chip" href="leaderboard.php#achievementsSection" title="<?= btH(btT('nav.myAchievements')) ?>" aria-label="<?= btH(btT('nav.myAchievements')) ?>"><?php if (!empty($user['picture'])): ?><img src="<?= btH($user['picture']) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><i class="bi bi-person-circle"></i><?php endif; ?><span class="user-name"><?= btH($user['name'] ?? $user['email']) ?></span></a>
            <a class="pdf-link" href="download.php" title="<?= btH(btT('dl.title')) ?>" aria-label="<?= btH(btT('dl.title')) ?>"><i class="bi bi-download"></i><span>PDF</span></a>
            <?php btLangMenu($user); ?>
            <button class="icon-btn" id="themeToggle" type="button" data-csrf="<?= btH(btCsrf($user)) ?>" aria-label="<?= btH(btT('nav.theme')) ?>" title="<?= btH(btT('nav.theme')) ?>"><i class="bi <?= $settings['theme'] === 'dark' ? 'bi-sun' : 'bi-moon-stars' ?>"></i></button>
            <?php if (btTestMode()): ?><span class="badge-test" title="Testing mode: signed in automatically">TEST MODE</span><?php endif; ?>
        <?php else: ?>
            <a class="pdf-link" href="download.php" title="<?= btH(btT('dl.title')) ?>" aria-label="<?= btH(btT('dl.title')) ?>"><i class="bi bi-download"></i><span>PDF</span></a>
            <?php btLangMenu(null); endif; ?>
        <button class="icon-btn" id="navToggle" type="button" aria-label="<?= btH(btT('nav.menu')) ?>" aria-controls="siteNav"><i class="bi bi-list"></i></button>
    </div>
</header>
<?php if (is_file(__DIR__ . '/menu-links.php')) { include __DIR__ . '/menu-links.php'; } /* optional: skipped if not uploaded */ ?>
<main class="container">
<?php
}

function renderBtFooter(string $version): void
{
    global $visitors2, $downloads2, $plansCreated2, $booksRead2, $chaptersRead2, $quizzes2, $topLanguage2, $topScope2, $topMode2, $topBooks2;
    ?>
</main>
<footer class="site-footer">
    <p class="footer-verse"><?= btH(btT('site.verse')) ?></p>
    <?php if (is_file(__DIR__ . '/footer-links.php')) { include __DIR__ . '/footer-links.php'; } /* optional: skipped if not uploaded */ ?>
    <p class="footer-install-wrap"><button type="button" class="install-app-btn btn btn-outline btn-sm" hidden><i class="bi bi-box-arrow-in-down"></i> <?= btH(btT('nav.installApp')) ?></button></p>
</footer>
<script>window.BT_T = <?= json_encode(btJsLabels(), JSON_UNESCAPED_UNICODE) ?>; window.BT_LANG = <?= json_encode(btUiLang()) ?>;</script>
<script src="assets/js/app.js?v=<?= btH($version) ?>"></script>
</body>
</html>
<?php
}
