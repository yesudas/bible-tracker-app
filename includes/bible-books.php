<?php

/**
 * The 66 books of the Bible: [name, chapter count]. Keys are book numbers 1-66
 * (1-39 Old Testament, 40-66 New Testament).
 *
 * @return array<int, array{name:string, chapters:int}>
 */
function btBooks(): array
{
    static $books = null;
    if ($books !== null) {
        return $books;
    }
    $raw = [
        ['Genesis', 50], ['Exodus', 40], ['Leviticus', 27], ['Numbers', 36], ['Deuteronomy', 34],
        ['Joshua', 24], ['Judges', 21], ['Ruth', 4], ['1 Samuel', 31], ['2 Samuel', 24],
        ['1 Kings', 22], ['2 Kings', 25], ['1 Chronicles', 29], ['2 Chronicles', 36], ['Ezra', 10],
        ['Nehemiah', 13], ['Esther', 10], ['Job', 42], ['Psalms', 150], ['Proverbs', 31],
        ['Ecclesiastes', 12], ['Song of Solomon', 8], ['Isaiah', 66], ['Jeremiah', 52], ['Lamentations', 5],
        ['Ezekiel', 48], ['Daniel', 12], ['Hosea', 14], ['Joel', 3], ['Amos', 9],
        ['Obadiah', 1], ['Jonah', 4], ['Micah', 7], ['Nahum', 3], ['Habakkuk', 3],
        ['Zephaniah', 3], ['Haggai', 2], ['Zechariah', 14], ['Malachi', 4],
        ['Matthew', 28], ['Mark', 16], ['Luke', 24], ['John', 21], ['Acts', 28],
        ['Romans', 16], ['1 Corinthians', 16], ['2 Corinthians', 13], ['Galatians', 6], ['Ephesians', 6],
        ['Philippians', 4], ['Colossians', 4], ['1 Thessalonians', 5], ['2 Thessalonians', 3], ['1 Timothy', 6],
        ['2 Timothy', 4], ['Titus', 3], ['Philemon', 1], ['Hebrews', 13], ['James', 5],
        ['1 Peter', 5], ['2 Peter', 3], ['1 John', 5], ['2 John', 1], ['3 John', 1],
        ['Jude', 1], ['Revelation', 22],
    ];
    $books = [];
    foreach ($raw as $i => [$name, $chapters]) {
        $books[$i + 1] = ['name' => $name, 'chapters' => $chapters];
    }
    return $books;
}

/** @return list<array{0:int,1:int}> every [book, chapter] pair for the given book numbers, in Bible order */
function btChaptersOfBooks(array $bookNos): array
{
    $books = btBooks();
    $bookNos = array_values(array_unique(array_map('intval', $bookNos)));
    sort($bookNos);
    $out = [];
    foreach ($bookNos as $b) {
        if (!isset($books[$b])) {
            continue;
        }
        for ($c = 1; $c <= $books[$b]['chapters']; $c++) {
            $out[] = [$b, $c];
        }
    }
    return $out;
}

/** Supported languages: code => display name. Book names live in data/book-names.json (copied from bible-quiz-app's catalogs). */
const BT_LANGUAGES = ['en' => 'English', 'ta' => 'தமிழ்', 'hi' => 'हिंदी', 'kn' => 'ಕನ್ನಡ'];

function btLang(?string $code): string
{
    return isset(BT_LANGUAGES[(string) $code]) ? (string) $code : 'en';
}

/** @return array<int,string> book number (1-66) => name in $lang */
function btBookNames(string $lang = 'en'): array
{
    static $all = null;
    if ($all === null) {
        $f = __DIR__ . '/../data/book-names.json';
        $all = is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
    }
    $lang = btLang($lang);
    $names = [];
    foreach (btBooks() as $n => $b) {
        $names[$n] = (string) ($all[$lang][$n - 1] ?? $b['name']);
    }
    return $names;
}

function btBookName(int $n, string $lang = 'en'): string
{
    return btBookNames($lang)[$n] ?? ('Book ' . $n);
}

/**
 * Bible translations available for a language, from data/bible-translations.json.
 * Entries with "hide": true are never offered. The default is the first visible entry flagged
 * "isDefault" (if the flagged one is hidden, or none is flagged, the first visible entry).
 * @return array{list: list<array{abbr:string,name:string}>, default: string}
 */
function btTranslations(string $lang): array
{
    static $data = null, $cache = [];
    $lang = btLang($lang);
    if (isset($cache[$lang])) {
        return $cache[$lang];
    }
    if ($data === null) {
        $f = __DIR__ . '/../data/bible-translations.json';
        $data = is_file($f) ? (json_decode((string) file_get_contents($f), true)['biblesByLanguage'] ?? []) : [];
    }
    $list = [];
    $default = '';
    foreach ($data as $entry) {
        if (($entry['languageCode'] ?? '') !== $lang) {
            continue;
        }
        foreach ($entry['bibles'] ?? [] as $bible) {
            if (!empty($bible['hide']) || empty($bible['abbr'])) {
                continue;
            }
            $list[] = ['abbr' => (string) $bible['abbr'], 'name' => (string) ($bible['commonName'] ?? $bible['abbr'])];
            if ($default === '' && !empty($bible['isDefault'])) {
                $default = (string) $bible['abbr'];
            }
        }
    }
    if ($default === '' && $list) {
        $default = $list[0]['abbr'];
    }
    return $cache[$lang] = ['list' => $list, 'default' => $default];
}

/** $wanted if it's an offered (visible) translation for the language, else the language default ('' if none). */
function btPickTranslation(string $lang, ?string $wanted): string
{
    $t = btTranslations($lang);
    foreach ($t['list'] as $b) {
        if ($b['abbr'] === (string) $wanted) {
            return $b['abbr'];
        }
    }
    return $t['default'];
}

/**
 * Fetches a chapter's verses (same proxy approach as bible-quiz-app's a.php "bible_chapter" view).
 * @return array{success:bool, verses?:list<array{number:int,verse:string}>, bible?:string, error?:string}
 */
function btFetchChapter(string $lang, int $book, int $chapter, ?string $bible = null): array
{
    $lang = btLang($lang);
    $bibleId = btPickTranslation($lang, $bible);
    if ($bibleId === '') {
        return ['success' => false, 'error' => 'No Bible text is configured for this language.'];
    }
    if ($book < 1 || $book > 66 || $chapter < 1) {
        return ['success' => false, 'error' => 'Invalid book or chapter.'];
    }
    $query = 'api.php?action=getVerses&bible=' . rawurlencode($bibleId) . '&book=' . $book . '&chapter=' . $chapter;
    // Production endpoint, plus the sibling /bibles folder on the same host when we are on a known WordOfGod host.
    // (The Host header is client-controlled, so it is never used to build a URL for any other host.)
    $urls = ['https://www.wordofgod.in/bibles/' . $query];
    $hostOnly = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
    if (in_array($hostOnly, ['wordofgod.in', 'www.wordofgod.in'], true)) {
        // SCRIPT_NAME is <app folder>/api/bible.php: the sibling "bibles" folder sits next to the app folder
        $siblingBase = rtrim(dirname(dirname(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')))), '/');
        array_unshift($urls, 'https://' . $hostOnly . $siblingBase . '/bibles/' . $query);
    }
    foreach ($urls as $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 8, CURLOPT_FOLLOWLOCATION => true]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = is_string($body) ? json_decode($body, true) : null;
        if ($code === 200 && is_array($data) && !empty($data['success']) && !empty($data['verses'])) {
            return ['success' => true, 'bible' => $bibleId, 'verses' => $data['verses']];
        }
    }
    return ['success' => false, 'error' => 'Could not load the Bible chapter. Please try again.'];
}

/** Labels for the footer's "top scope / top reading option" metrics. */
const BT_FORM_SCOPES_LABELS = ['whole' => 'Whole Bible', 'ot' => 'Old Testament', 'nt' => 'New Testament', 'books' => 'Selected Books', 'chapters' => 'Selected Chapters', 'two-books' => 'Morning/Evening Books'];
const BT_MODE_LABELS = ['continuous' => 'Continuous', 'perday' => 'Chapters per Day', 'days' => 'Complete in X Days'];
