<?php

require_once __DIR__ . '/bible-books.php';
require_once __DIR__ . '/i18n.php';

/**
 * User storage: ONE JSON file per user in users/, named from a hash of the
 * Google email (so no address appears in a filename):
 *   {"email": "...", "name": "...", "picture": "<Google profile photo URL>", "plans": [ {plan}, ... ],
 *    "settings": {"theme": "light|dark", "fontSize": 17}}
 *
 * A plan stores only its settings + which chapters are ticked off ("doneChapters": ["book:chapter", ...]);
 * the day-by-day schedule is regenerated on demand by btPlanDays().
 */

const BT_SCOPES = ['whole' => 'Whole Bible', 'ot' => 'Old Testament', 'nt' => 'New Testament', 'books' => 'Selected Books', 'chapters' => 'Selected Chapters'];
const BT_MODES = ['continuous' => 'Continuous', 'perday' => 'Read X Chapters per Day', 'days' => 'Complete in X No of Days'];
/** Scope choices shown on the form: the saved scopes plus the form-only 'two-books' (stored as split = two-books). */
const BT_FORM_SCOPES = BT_SCOPES + ['two-books' => 'Morning one book, Evening different book'];

function btUserFile(string $email): string
{
    return __DIR__ . '/../users/' . substr(hash('sha256', strtolower(trim($email))), 0, 32) . '.json';
}

function btReadUserData(array $user): array
{
    $file = btUserFile($user['email']);
    $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    return is_array($data) ? $data : [];
}

function btLoadUserPlans(array $user): array
{
    $data = btReadUserData($user);
    return is_array($data['plans'] ?? null) ? array_map('btNormalizePlan', array_values($data['plans'])) : [];
}

/**
 * Called on every signed-in page load. First sight of a user creates their (empty) file and counts them in the
 * "signed-in users" metric; afterwards it only rewrites the file when Google's name/picture have changed.
 */
function btSyncProfile(array $user): void
{
    $new = !is_file(btUserFile($user['email']));
    if (!$new) {
        $d = btReadUserData($user);
        if (($d['name'] ?? '') === ($user['name'] ?? '') && ($d['picture'] ?? '') === ($user['picture'] ?? '')) {
            return;
        }
    }
    btMutateUserData($user, function (array $x) use ($new): array {
        if ($new && !isset($x['email'])) {
            $x['createdAt'] = date('c');
            require_once __DIR__ . '/visitor-counter.php';
            btIncrementCounter('signed-in-users');
        }
        return $x;
    });
}

/** Max chapters that may be newly marked read per day (Psalms excluded) — stops false filling. */
const BT_DAILY_CHAPTER_LIMIT = 66;
const BT_PSALMS = 19;

const BT_FONT_DEFAULT = 17;
const BT_FONT_MIN = 13;
const BT_FONT_MAX = 33;

/** @return array{theme:string,fontSize:int,bibles:array<string,string>,planLang:string} */
function btUserSettings(?array $user): array
{
    $s = $user ? (btReadUserData($user)['settings'] ?? []) : [];
    $theme = ($s['theme'] ?? '') === 'dark' ? 'dark' : 'light';
    $font = max(BT_FONT_MIN, min(BT_FONT_MAX, (int) ($s['fontSize'] ?? BT_FONT_DEFAULT)));
    $bibles = is_array($s['bibles'] ?? null) ? $s['bibles'] : [];
    return ['theme' => $theme, 'fontSize' => $font, 'bibles' => $bibles, 'planLang' => btLang($s['planLang'] ?? 'en')];
}

/** Atomically rewrites the user's whole file with $mutator(data) => data, under an exclusive lock. */
function btMutateUserData(array $user, callable $mutator): array
{
    $file = btUserFile($user['email']);
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0775, true);
    }
    $fh = fopen($file, 'c+');
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $data = $raw ? json_decode($raw, true) : null;
    $data = is_array($data) ? $data : [];
    $data['plans'] = is_array($data['plans'] ?? null) ? array_map('btNormalizePlan', array_values($data['plans'])) : [];
    $data = $mutator($data);
    $data = ['email' => $user['email'], 'name' => $user['name'] ?? '', 'picture' => $user['picture'] ?? ''] + $data;
    $data['plans'] = array_values($data['plans']);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $data;
}

/** Same, but the mutator only sees/returns the plans list (settings are preserved). */
function btMutateUserPlans(array $user, callable $mutator): array
{
    $data = btMutateUserData($user, function (array $d) use ($mutator): array {
        $d['plans'] = $mutator($d['plans']);
        return $d;
    });
    return $data['plans'];
}

function btFindPlan(array $plans, string $id): ?array
{
    foreach ($plans as $p) {
        if (($p['id'] ?? '') === $id) {
            return $p;
        }
    }
    return null;
}

/** @return list<int> */
function btIntList(mixed $v): array
{
    $books = btBooks();
    $out = [];
    foreach ((array) $v as $n) {
        $n = (int) $n;
        if (isset($books[$n])) {
            $out[$n] = $n;
        }
    }
    ksort($out);
    return array_values($out);
}

/**
 * Validates form input into a plan array.
 * @return array{0: array, 1: list<string>} [plan, errors]
 */
function btPlanFromInput(array $in): array
{
    $books = btBooks();
    $errors = [];
    $plan = [
        'name' => trim(mb_substr((string) ($in['name'] ?? ''), 0, 80)),
        'lang' => btLang($in['lang'] ?? 'en'),
        'scope' => (string) ($in['scope'] ?? 'whole'),
        'books' => [],
        'ranges' => [],
        'mode' => (string) ($in['mode'] ?? 'continuous'),
        'perDay' => max(1, min(100, (int) ($in['per_day'] ?? 3))),
        'days' => max(1, min(3650, (int) ($in['days'] ?? 365))),
        'split' => !empty($in['morning_evening']) ? 'morning-evening' : 'none',
        'morningBooks' => [],
        'eveningBooks' => [],
    ];
    if ($plan['scope'] === 'two-books') {
        $plan['scope'] = 'whole'; // scope is ignored for this option
        $plan['split'] = 'two-books';
    }
    if (!isset(BT_SCOPES[$plan['scope']])) {
        $plan['scope'] = 'whole';
    }
    if (!isset(BT_MODES[$plan['mode']])) {
        $plan['mode'] = 'continuous';
    }
    if ($plan['mode'] === 'continuous' && $plan['split'] === 'morning-evening') {
        $plan['split'] = 'none'; // no daily schedule to split
    }

    if ($plan['split'] === 'two-books') {
        $plan['morningBooks'] = btIntList($in['morning_books'] ?? []);
        $plan['eveningBooks'] = btIntList($in['evening_books'] ?? []);
        if (!$plan['morningBooks'] || !$plan['eveningBooks']) {
            $errors[] = btT('err.twoBooks');
        }
    } elseif ($plan['scope'] === 'books') {
        $plan['books'] = btIntList($in['books'] ?? []);
        if (!$plan['books']) {
            $errors[] = btT('err.selectBook');
        }
    } elseif ($plan['scope'] === 'chapters') {
        $rb = (array) ($in['range_book'] ?? []);
        $rf = (array) ($in['range_from'] ?? []);
        $rt = (array) ($in['range_to'] ?? []);
        foreach ($rb as $i => $b) {
            $b = (int) $b;
            if (!isset($books[$b])) {
                continue;
            }
            $max = $books[$b]['chapters'];
            $from = max(1, min($max, (int) ($rf[$i] ?? 1)));
            $to = max(1, min($max, (int) ($rt[$i] ?? $from)));
            if ($to < $from) {
                [$from, $to] = [$to, $from];
            }
            $plan['ranges'][] = ['book' => $b, 'from' => $from, 'to' => $to];
        }
        if (!$plan['ranges']) {
            $errors[] = btT('err.addRange');
        }
    }

    if ($plan['name'] === '') {
        $plan['name'] = btDefaultName($plan);
    }
    return [$plan, $errors];
}

function btDefaultName(array $plan): string
{
    $lang = $plan['lang'] ?? 'en';
    if ($plan['split'] === 'two-books') {
        return btT('scope.two-books', [], $lang);
    }
    $base = btT('scope.' . $plan['scope'], [], $lang);
    if ($plan['scope'] === 'books' && count($plan['books']) === 1) {
        $base = btBookName($plan['books'][0], $lang);
    }
    return $base . ' - ' . match ($plan['mode']) {
        'perday' => btT($plan['perDay'] > 1 ? 'sum.perDayN' : 'sum.perDay1', ['n' => $plan['perDay']], $lang),
        'days' => btT('sum.inDays', ['n' => $plan['days']], $lang),
        default => btT('mode.continuous', [], $lang),
    };
}

/** @return list<array{0:int,1:int}> */
function btScopeChapters(array $plan, string $which = 'main'): array
{
    if ($which === 'morning') {
        return btChaptersOfBooks($plan['morningBooks'] ?? []);
    }
    if ($which === 'evening') {
        return btChaptersOfBooks($plan['eveningBooks'] ?? []);
    }
    switch ($plan['scope']) {
        case 'ot':
            return btChaptersOfBooks(range(1, 39));
        case 'nt':
            return btChaptersOfBooks(range(40, 66));
        case 'books':
            return btChaptersOfBooks($plan['books'] ?? []);
        case 'chapters':
            $out = [];
            foreach ($plan['ranges'] ?? [] as $r) {
                for ($c = $r['from']; $c <= $r['to']; $c++) {
                    $out[] = [(int) $r['book'], $c];
                }
            }
            return $out;
        default:
            return btChaptersOfBooks(range(1, 66));
    }
}

/** Splits $list into $n near-equal consecutive parts (no empty parts). */
function btSplitEven(array $list, int $n): array
{
    $total = count($list);
    $n = max(1, min($n, $total));
    $parts = [];
    for ($i = 0; $i < $n; $i++) {
        $start = intdiv($i * $total, $n);
        $end = intdiv(($i + 1) * $total, $n);
        $parts[] = array_slice($list, $start, $end - $start);
    }
    return $parts;
}

/** Cuts a chapter list into day-sized chunks for the plan's reading mode. */
function btChunkForMode(array $list, array $plan): array
{
    if (!$list) {
        return [];
    }
    return match ($plan['mode']) {
        'days' => btSplitEven($list, (int) $plan['days']),
        'perday' => array_chunk($list, max(1, (int) $plan['perDay'])),
        default => array_chunk($list, 1),
    };
}

/**
 * Day-by-day schedule.
 * @return list<list<array{label:string,chapters:list<array{0:int,1:int}>}>> days → sessions
 */
function btPlanDays(array $plan): array
{
    $days = [];
    if (($plan['split'] ?? 'none') === 'two-books') {
        $m = btChunkForMode(btScopeChapters($plan, 'morning'), $plan);
        $e = btChunkForMode(btScopeChapters($plan, 'evening'), $plan);
        $count = max(count($m), count($e));
        for ($i = 0; $i < $count; $i++) {
            $sessions = [];
            if (isset($m[$i])) {
                $sessions[] = ['label' => 'Morning', 'chapters' => $m[$i]];
            }
            if (isset($e[$i])) {
                $sessions[] = ['label' => 'Evening', 'chapters' => $e[$i]];
            }
            $days[] = $sessions;
        }
        return $days;
    }
    $chunks = btChunkForMode(btScopeChapters($plan), $plan);
    foreach ($chunks as $chunk) {
        if (($plan['split'] ?? 'none') === 'morning-evening' && count($chunk) > 1) {
            $half = (int) ceil(count($chunk) / 2);
            $days[] = [
                ['label' => 'Morning', 'chapters' => array_slice($chunk, 0, $half)],
                ['label' => 'Evening', 'chapters' => array_slice($chunk, $half)],
            ];
        } elseif (($plan['split'] ?? 'none') === 'morning-evening') {
            $days[] = [['label' => 'Morning', 'chapters' => $chunk]];
        } else {
            $days[] = [['label' => '', 'chapters' => $chunk]];
        }
    }
    return $days;
}

/** "Genesis 1-3; Exodus 1" from a chapter list. */
function btFormatRefs(array $chapters, string $lang = 'en'): string
{
    $books = btBooks();
    $names = btBookNames($lang);
    $groups = [];
    foreach ($chapters as [$b, $c]) {
        $last = count($groups) - 1;
        if ($last >= 0 && $groups[$last]['b'] === $b && $groups[$last]['to'] === $c - 1) {
            $groups[$last]['to'] = $c;
        } else {
            $groups[] = ['b' => $b, 'from' => $c, 'to' => $c];
        }
    }
    $parts = [];
    foreach ($groups as $g) {
        $name = $names[$g['b']];
        $whole = $books[$g['b']]['chapters'] === 1;
        if ($whole) {
            $parts[] = $name;
        } else {
            $parts[] = $name . ' ' . ($g['from'] === $g['to'] ? $g['from'] : $g['from'] . '-' . $g['to']);
        }
    }
    return implode('; ', $parts);
}

/** Upgrades older plans that tracked whole days ("doneDays") to per-chapter ticks ("doneChapters"). */
function btNormalizePlan(array $plan): array
{
    if (!isset($plan['doneChapters'])) {
        $keys = [];
        if (!empty($plan['doneDays'])) {
            $days = btPlanDays($plan);
            foreach ($plan['doneDays'] as $d) {
                foreach ($days[(int) $d] ?? [] as $s) {
                    foreach ($s['chapters'] as [$b, $c]) {
                        $keys[$b . ':' . $c] = true;
                    }
                }
            }
        }
        $plan['doneChapters'] = array_keys($keys);
    }
    unset($plan['doneDays']);
    return $plan;
}

/** Every distinct chapter in the plan, grouped by book, in Bible order. @return array<int,list<int>> book => chapters */
function btPlanChaptersByBook(array $plan): array
{
    $by = [];
    foreach (btPlanDays($plan) as $sessions) {
        foreach ($sessions as $s) {
            foreach ($s['chapters'] as [$b, $c]) {
                $by[$b][$c] = $c;
            }
        }
    }
    ksort($by);
    foreach ($by as &$list) {
        sort($list);
        $list = array_values($list);
    }
    return $by;
}

/** @return array{done:int,total:int,percent:int} chapter counts */
function btPlanProgress(array $plan): array
{
    $total = 0;
    $done = 0;
    $set = array_flip($plan['doneChapters'] ?? []);
    foreach (btPlanChaptersByBook($plan) as $b => $chs) {
        foreach ($chs as $c) {
            $total++;
            if (isset($set[$b . ':' . $c])) {
                $done++;
            }
        }
    }
    return ['done' => $done, 'total' => $total, 'percent' => $total ? (int) floor($done * 100 / $total) : 0];
}

/** $short: for the PDF header — a two-books plan reads "Morning & evening · in 365 days" instead of listing every book. */
function btPlanSummary(array $plan, bool $inPlanLanguage = false, bool $short = false): string
{
    $lang = $plan['lang'] ?? 'en';
    $tl = $inPlanLanguage ? $lang : null; // null = the UI language
    $names = static fn (array $nos): string => implode(', ', array_map(fn ($n) => btBookName($n, $lang), $nos));
    $me = btT('sum.me', [], $tl);
    $scope = ($short && $plan['split'] === 'two-books')
        ? mb_strtoupper(mb_substr($me, 0, 1)) . mb_substr($me, 1)
        : ($plan['split'] === 'two-books'
        ? btT('sum.morning', [], $tl) . ': ' . $names($plan['morningBooks']) . ' · ' . btT('sum.evening', [], $tl) . ': ' . $names($plan['eveningBooks'])
        : btT('scope.' . $plan['scope'], [], $tl));
    $mode = match ($plan['mode']) {
        'perday' => btT($plan['perDay'] > 1 ? 'sum.perDayN' : 'sum.perDay1', ['n' => $plan['perDay']], $tl),
        'days' => btT('sum.inDays', ['n' => $plan['days']], $tl),
        default => btT('sum.continuous', [], $tl),
    };
    $extra = $plan['split'] === 'morning-evening' ? ' · ' . btT('sum.me', [], $tl) : '';
    return $scope . ' · ' . $mode . $extra;
}

/** Localised "Morning"/"Evening" session label (the schedule data itself stores the English words). */
function btSessionLabel(string $label, ?string $lang = null): string
{
    return match ($label) {
        'Morning' => btT('sum.morning', [], $lang),
        'Evening' => btT('sum.evening', [], $lang),
        default => $label,
    };
}

/** Stateless CSRF token tied to the signed-in email. */
function btSecret(): string
{
    $file = __DIR__ . '/../bt-secret.php';
    if (!is_file($file)) {
        // If the folder isn't writable, fall back to a key derived from this install so pages still work.
        if (@file_put_contents($file, "<?php\nreturn " . var_export(bin2hex(random_bytes(32)), true) . ";\n", LOCK_EX) === false) {
            return hash('sha256', 'bt-fallback|' . __DIR__ . '|' . php_uname('n'));
        }
    }
    return (string) require $file;
}

function btCsrf(array $user): string
{
    return hash_hmac('sha256', 'csrf|' . strtolower($user['email']), btSecret());
}

function btCheckCsrf(array $user, ?string $token): bool
{
    return is_string($token) && hash_equals(btCsrf($user), $token);
}
