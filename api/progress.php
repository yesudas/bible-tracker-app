<?php
// POST id, keys ("book:chapter,book:chapter"), done(1|0), csrf -> ticks/unticks chapters in the signed-in user's plan.
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../includes/visitor-counter.php';
require_once __DIR__ . '/../includes/leaderboard.php';
header('Content-Type: application/json; charset=utf-8');

$user = btGoogleUser();
if (!$user) { http_response_code(401); echo json_encode(['ok' => false, 'error' => 'Not signed in']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !btCheckCsrf($user, $_POST['csrf'] ?? null)) {
    http_response_code(403); echo json_encode(['ok' => false, 'error' => 'Bad request']); exit;
}

$id = (string) ($_POST['id'] ?? '');
$keys = array_filter(explode(',', (string) ($_POST['keys'] ?? '')), fn ($k) => preg_match('/^\d{1,2}:\d{1,3}$/', $k));
$done = ($_POST['done'] ?? '') === '1';
$result = null;
$blocked = false;
$events = []; // metric increments, fired only if the change is accepted

// Books of the plan whose every chapter is ticked in $set.
$booksDone = function (array $plan, array $set): array {
    $out = [];
    foreach (btPlanChaptersByBook($plan) as $b => $chs) {
        foreach ($chs as $c) { if (!isset($set[$b . ':' . $c])) continue 2; }
        $out[] = $b;
    }
    return $out;
};

btMutateUserData($user, function (array $data) use ($id, $keys, $done, &$result, &$blocked, &$events, $booksDone): array {
    // Anti false-filling: at most BT_DAILY_CHAPTER_LIMIT chapters can be newly ticked per day (Psalms excluded).
    // $daily = chapters ticked today ("planId|book:chapter", non-Psalms only); unticking one gives it back.
    // Lifetime stats for the leaderboard; users who ticked before it existed start from what's already ticked.
    if (!isset($data['stats'])) {
        $data['stats'] = btBackfillStats($data['plans']);
    }
    $today = date('Y-m-d');
    $daily = (($data['daily']['date'] ?? '') === $today && is_array($data['daily']['keys'] ?? null)) ? $data['daily']['keys'] : [];
    $daily = array_flip($daily);

    foreach ($data['plans'] as &$p) {
        if (($p['id'] ?? '') !== $id) continue;
        $valid = [];
        foreach (btPlanChaptersByBook($p) as $b => $chs) { foreach ($chs as $c) { $valid[$b . ':' . $c] = true; } }
        $set = array_flip($p['doneChapters'] ?? []);
        $booksBefore = $booksDone($p, $set);

        $newKeys = [];
        foreach ($keys as $k) {
            if (isset($valid[$k]) && $done && !isset($set[$k])) $newKeys[] = $k;
        }
        $counted = array_filter($newKeys, fn ($k) => (int) explode(':', $k)[0] !== BT_PSALMS);
        if (count($daily) + count($counted) > BT_DAILY_CHAPTER_LIMIT) {
            $blocked = true;
            break;
        }

        foreach ($keys as $k) {
            if (!isset($valid[$k])) continue;
            $book = (int) explode(':', $k)[0];
            if ($done) {
                if (!isset($set[$k])) {
                    $set[$k] = true;
                    $events[] = ['chapter', $book];
                    if ($book !== BT_PSALMS) $daily[$id . '|' . $k] = true;
                }
            } else {
                unset($set[$k], $daily[$id . '|' . $k]);
            }
        }
        foreach (array_diff($booksDone($p, $set), $booksBefore) as $_) {
            $events[] = ['book', 0];
        }
        $p['doneChapters'] = array_keys($set);
        $p['updated'] = date('c');
        $result = btPlanProgress($p);
    }
    unset($p);
    if (!$blocked) {
        foreach ($events as [$type]) {
            $data['stats'][$type === 'chapter' ? 'chaptersRead' : 'booksCompleted']++;
        }
        $data['daily'] = ['date' => $today, 'keys' => array_keys($daily)];
    }
    return $data;
});

if ($blocked) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'blocked' => true, 'error' => btT('err.dailyLimit', ['n' => BT_DAILY_CHAPTER_LIMIT])]);
    exit;
}
if ($events) {
    btLbUpdate($user);
}
foreach ($events as [$type, $book]) {
    if ($type === 'chapter') {
        btIncrementCounter('chapters-read');
        btIncrementMapCounter('chapters-read-by-book', (string) $book);
    } else {
        btIncrementCounter('books-read');
    }
}

if (!$result) { http_response_code(404); echo json_encode(['ok' => false, 'error' => 'Plan not found']); exit; }
echo json_encode(['ok' => true] + $result);
