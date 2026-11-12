<?php

/**
 * Leaderboard + achievements.
 *
 * Score = a user's lifetime "chapters read" (every chapter newly ticked as read; never lowered by unticking).
 * Each user's lifetime numbers live in their own file (users/*.json → "stats": {chaptersRead, booksCompleted});
 * the leaderboard reads one private master file, logs/leaderboard-data.json (logs/.htaccess blocks web access),
 * keyed by an opaque id derived from the email (HMAC) so no email is ever exposed on a public page:
 *   {"<id>": {"displayName": "...", "pictureUrl": "...", "score": N, "books": N, "updated": "..."}}
 */
require_once __DIR__ . '/plans.php';

function btLbMasterFile(): string
{
    return __DIR__ . '/../logs/leaderboard-data.json';
}

/** Opaque, stable per-user id (matches a signed-in user to their own row without exposing the email). */
function btLbId(string $email): string
{
    return substr(hash_hmac('sha256', 'lb|' . strtolower(trim($email)), btSecret()), 0, 16);
}

/** Default display name: the Google profile name exactly as given (email's local part if there is none). */
function btDefaultDisplayName(string $name, string $email): string
{
    $name = trim(preg_replace('/\s+/u', ' ', $name));
    return $name !== '' ? mb_substr($name, 0, 40) : mb_substr(explode('@', $email)[0], 0, 40);
}

function btDisplayName(array $user, array $data): string
{
    $custom = trim((string) ($data['displayName'] ?? ''));
    return $custom !== '' ? $custom : btDefaultDisplayName((string) ($user['name'] ?? ''), (string) $user['email']);
}

/** Lifetime numbers for a user's file data. @return array{chaptersRead:int,booksCompleted:int} */
function btStatsFromData(array $data): array
{
    $s = is_array($data['stats'] ?? null) ? $data['stats'] : [];
    return ['chaptersRead' => (int) ($s['chaptersRead'] ?? 0), 'booksCompleted' => (int) ($s['booksCompleted'] ?? 0)];
}

/**
 * Starting stats for a user who ticked chapters before the leaderboard existed:
 * what's already ticked across their plans.
 * @return array{chaptersRead:int,booksCompleted:int}
 */
function btBackfillStats(array $plans): array
{
    $chapters = 0;
    $books = 0;
    foreach ($plans as $p) {
        $set = array_flip($p['doneChapters'] ?? []);
        $chapters += count($set);
        foreach (btPlanChaptersByBook($p) as $b => $chs) {
            foreach ($chs as $c) {
                if (!isset($set[$b . ':' . $c])) {
                    continue 2;
                }
            }
            $books++;
        }
    }
    return ['chaptersRead' => $chapters, 'booksCompleted' => $books];
}

/** Writes/refreshes this user's row in the master file from their user file. */
function btLbUpdate(array $user): void
{
    $data = btReadUserData($user);
    $stats = btStatsFromData($data);
    $id = btLbId($user['email']);
    $file = btLbMasterFile();
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0775, true);
    }
    $fh = fopen($file, 'c+');
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $rows = $raw ? json_decode($raw, true) : [];
    $rows = is_array($rows) ? $rows : [];
    if ($stats['chaptersRead'] > 0 && empty($data['leaderboardHidden'])) {
        $rows[$id] = [
            'displayName' => btDisplayName($user, $data),
            'pictureUrl' => (string) ($user['picture'] ?? ''),
            'score' => $stats['chaptersRead'],
            'books' => $stats['booksCompleted'],
            'updated' => date('c'),
        ];
    } else {
        unset($rows[$id]); // no chapters yet, or removed from the leaderboard by an admin
    }
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($rows, JSON_UNESCAPED_UNICODE));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
}

/** All rows, best first (score, then books, then name). @return list<array{id:string,displayName:string,pictureUrl:string,score:int,books:int}> */
function btLbRows(?int $limit = null): array
{
    $file = btLbMasterFile();
    $raw = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
    $out = [];
    foreach (is_array($raw) ? $raw : [] as $id => $r) {
        if ((int) ($r['score'] ?? 0) <= 0) {
            continue;
        }
        $out[] = [
            'id' => (string) $id,
            'displayName' => (string) ($r['displayName'] ?? ''),
            'pictureUrl' => (string) ($r['pictureUrl'] ?? ''),
            'score' => (int) $r['score'],
            'books' => (int) ($r['books'] ?? 0),
        ];
    }
    usort($out, static fn (array $a, array $b): int => [$b['score'], $b['books'], $a['displayName']] <=> [$a['score'], $a['books'], $b['displayName']]);
    return $limit ? array_slice($out, 0, $limit) : $out;
}

/** Star-tier ladder, in display order (thresholds default here; admins can change them — see btAchievementThresholds()). */
function btAchievementTiers(): array
{
    $th = btAchievementThresholds();
    $tiers = [
        'silver' => ['label' => 'Silver', 'color' => '#b9c2cc', 'icon' => '🥈'],
        'bronze' => ['label' => 'Bronze', 'color' => '#c27a3f', 'icon' => '🥉'],
        'gold' => ['label' => 'Gold', 'color' => '#e8b923', 'icon' => '🥇'],
        'platinum' => ['label' => 'Platinum', 'color' => '#7fa8b8', 'icon' => '💎'],
        'diamond' => ['label' => 'Diamond', 'color' => '#58c7e0', 'icon' => '👑'],
    ];
    foreach ($tiers as $key => &$t) {
        $t['thresholds'] = $th[$key];
    }
    unset($t);
    return $tiers;
}

function btAchievementDefaultThresholds(): array
{
    return [
        'silver' => [10, 25, 50, 75, 100],
        'bronze' => [150, 200, 250, 300, 400],
        'gold' => [500, 600, 700, 800, 900],
        'platinum' => [1000, 1189, 1500, 2000, 2500],
        'diamond' => [3000, 4000, 5000, 6000, 7500],
    ];
}

function btAchievementThresholdsFile(): string
{
    return __DIR__ . '/../counters/achievement-thresholds.txt';
}

/** Admin-configured thresholds (counters/achievement-thresholds.txt), per tier falling back to the defaults. @return array<string,list<int>> */
function btAchievementThresholds(): array
{
    $out = btAchievementDefaultThresholds();
    $f = btAchievementThresholdsFile();
    $saved = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
    foreach ($out as $tier => $defaults) {
        $v = is_array($saved) ? ($saved[$tier] ?? null) : null;
        if (is_array($v) && count($v) === 5) {
            $v = array_map(static fn ($n): int => max(0, (int) $n), array_values($v));
            sort($v);
            $out[$tier] = $v;
        }
    }
    return $out;
}

/** @param array<string,list<int|string>> $thresholds tier => 5 values; null resets to defaults */
function btAchievementSetThresholds(?array $thresholds): bool
{
    $f = btAchievementThresholdsFile();
    if ($thresholds === null) {
        return !is_file($f) || @unlink($f);
    }
    $cur = btAchievementThresholds();
    foreach (array_keys($cur) as $tier) {
        $v = $thresholds[$tier] ?? null;
        if (is_array($v) && count($v) === 5) {
            $v = array_map(static fn ($n): int => max(0, (int) $n), array_values($v));
            sort($v);
            $cur[$tier] = $v;
        }
    }
    if (!is_dir(dirname($f))) {
        mkdir(dirname($f), 0775, true);
    }
    return @file_put_contents($f, json_encode($cur), LOCK_EX) !== false;
}

/** Lit stars for a lifetime chapters-read count. @return list<array{key:string,label:string,color:string,icon:string,stars:list<bool>,thresholds:list<int>}> */
function btAchievementProgress(int $chaptersRead): array
{
    $out = [];
    foreach (btAchievementTiers() as $key => $t) {
        $out[] = [
            'key' => $key, 'label' => $t['label'], 'color' => $t['color'], 'icon' => $t['icon'],
            'stars' => array_map(static fn (int $th): bool => $chaptersRead >= $th, $t['thresholds']),
            'thresholds' => $t['thresholds'],
        ];
    }
    return $out;
}
