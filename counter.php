<?php
// counter.php — included by every public page, before output.
require_once __DIR__ . '/includes/visitor-counter.php';

// Common bot keywords in the User-Agent (same list as bible-quiz-app).
$botKeywords = [
    'bot', 'crawl', 'slurp', 'spider', 'mediapartners', 'curl', 'python', 'wget', 'baiduspider', 'bingpreview', 'facebookexternalhit', 'pingdom'
];
$userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$isBot = false;
foreach ($botKeywords as $keyword) {
    if (strpos($userAgent, $keyword) !== false) {
        $isBot = true;
        break;
    }
}

// Only real visitors bump the visitor counter.
$visitors2 = $isBot ? btReadCounter('counter') : btIncrementCounter('counter');

// Read-only here: incremented by download.php / plan-save.
$downloads2 = btReadCounter('downloads');
$plansCreated2 = btReadCounter('plans-created');

// Usage metrics (read-only here; written by sign-in, plan-edit.php, api/progress.php and api/stats.php).
$booksRead2 = btReadCounter('books-read');
$chaptersRead2 = btReadCounter('chapters-read');
$quizzes2 = btReadCounter('quizzes-attempted');

require_once __DIR__ . '/includes/bible-books.php';
$btTop = static function (array $items, int $n = 1): array {
    return array_slice(array_keys(array_filter($items, static fn (int $c): bool => $c > 0)), 0, $n);
};
$topLanguage2 = ($t = $btTop(btReadMapCounter('plans-by-language'))) ? (BT_LANGUAGES[$t[0]] ?? $t[0]) : '';
$topScope2 = ($t = $btTop(btReadMapCounter('plans-by-scope'))) ? $t[0] : ''; // key; translated in footer-links.php
$topMode2 = ($t = $btTop(btReadMapCounter('plans-by-mode'))) ? $t[0] : '';
$topBooks2 = array_map(static fn (string $n): string => btBookName((int) $n, 'en'), $btTop(btReadMapCounter('chapters-read-by-book'), 3));
