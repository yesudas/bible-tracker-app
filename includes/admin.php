<?php

require_once __DIR__ . '/leaderboard.php';

/**
 * Admin helpers for a.php: the user list (read from users/*.json — the single source of truth), leaderboard
 * moderation and the name-change log (logs/leaderboard-name-changes.json, blocked from the web by logs/.htaccess).
 */

/**
 * Every user, newest activity first.
 * @return list<array{id:string,email:string,name:string,picture:string,displayName:string,hidden:bool,stats:array{chaptersRead:int,booksCompleted:int},plans:list<array>,created:string,modified:int,file:string}>
 */
function btAllUsers(): array
{
    $out = [];
    foreach (glob(__DIR__ . '/../users/*.json') ?: [] as $file) {
        $d = json_decode((string) file_get_contents($file), true);
        if (!is_array($d) || empty($d['email'])) {
            continue;
        }
        $user = ['email' => (string) $d['email'], 'name' => (string) ($d['name'] ?? ''), 'picture' => (string) ($d['picture'] ?? '')];
        $plans = is_array($d['plans'] ?? null) ? array_map('btNormalizePlan', array_values($d['plans'])) : [];
        $out[] = [
            'id' => btLbId($user['email']),
            'email' => $user['email'],
            'name' => $user['name'],
            'picture' => $user['picture'],
            'displayName' => btDisplayName($user, $d),
            'hidden' => !empty($d['leaderboardHidden']),
            'stats' => isset($d['stats']) ? btStatsFromData($d) : btBackfillStats($plans),
            'plans' => $plans,
            'created' => (string) ($d['createdAt'] ?? ''),
            'modified' => (int) filemtime($file),
            'file' => $file,
        ];
    }
    usort($out, static fn (array $a, array $b): int => $b['modified'] <=> $a['modified']);
    return $out;
}

function btFindUserById(string $id): ?array
{
    foreach (btAllUsers() as $u) {
        if ($u['id'] === $id) {
            return $u;
        }
    }
    return null;
}

function btNameLogFile(): string
{
    return __DIR__ . '/../logs/leaderboard-name-changes.json';
}

/** @return list<array{id:string,userId:string,email:string,from:string,to:string,by:string,at:string,reverted:bool}> newest first */
function btNameLogRead(): array
{
    $f = btNameLogFile();
    $d = is_file($f) ? json_decode((string) file_get_contents($f), true) : [];
    return is_array($d) ? array_reverse(array_values($d)) : [];
}

/** Appends a name-change entry (no-op if nothing changed). */
function btNameLogAdd(string $email, string $from, string $to, string $by): void
{
    if ($from === $to) {
        return;
    }
    btNameLogMutate(function (array $log) use ($email, $from, $to, $by): array {
        $log[] = [
            'id' => bin2hex(random_bytes(6)), 'userId' => btLbId($email), 'email' => $email,
            'from' => $from, 'to' => $to, 'by' => $by, 'at' => date('Y-m-d H:i:s'), 'reverted' => false,
        ];
        return $log;
    });
}

function btNameLogMutate(callable $fn): void
{
    $f = btNameLogFile();
    if (!is_dir(dirname($f))) {
        mkdir(dirname($f), 0775, true);
    }
    $fh = fopen($f, 'c+');
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $log = $raw ? json_decode($raw, true) : [];
    $log = $fn(is_array($log) ? array_values($log) : []);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode(array_values($log), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
}

/** Sets (or, with '' , clears) a user's display name and refreshes their leaderboard row. */
function btSetDisplayName(array $u, string $name): void
{
    $user = ['email' => $u['email'], 'name' => $u['name'], 'picture' => $u['picture']];
    btMutateUserData($user, function (array $d) use ($name): array {
        if ($name === '') { unset($d['displayName']); } else { $d['displayName'] = $name; }
        return $d;
    });
    btLbUpdate($user);
}

/** Restores the name from before a logged change; returns false if already reverted / the user is gone. */
function btNameLogRevert(string $logId): bool
{
    $done = false;
    $entry = null;
    foreach (btNameLogRead() as $e) {
        if ($e['id'] === $logId) { $entry = $e; break; }
    }
    if (!$entry || !empty($entry['reverted']) || !($u = btFindUserById($entry['userId']))) {
        return false;
    }
    btSetDisplayName($u, $entry['from']);
    btNameLogMutate(function (array $log) use ($logId, &$done): array {
        foreach ($log as &$e) {
            if (($e['id'] ?? '') === $logId) {
                $e['reverted'] = true;
                $e['revertedAt'] = date('Y-m-d H:i:s');
                $done = true;
            }
        }
        return $log;
    });
    return $done;
}

/** Removes a user's file and leaderboard row. */
function btDeleteUser(array $u): bool
{
    $ok = @unlink($u['file']);
    $f = btLbMasterFile();
    if (is_file($f)) {
        $fh = fopen($f, 'c+');
        flock($fh, LOCK_EX);
        $rows = json_decode((string) stream_get_contents($fh), true);
        $rows = is_array($rows) ? $rows : [];
        unset($rows[$u['id']]);
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($rows, JSON_UNESCAPED_UNICODE));
        fflush($fh);
        flock($fh, LOCK_UN);
        fclose($fh);
    }
    return $ok;
}
