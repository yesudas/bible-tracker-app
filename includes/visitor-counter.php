<?php

/**
 * Simple JSON counters in counters/ (gitignored, blocked by counters/.htaccess).
 * One file per counter, each {"total": N}, updated under an exclusive lock.
 */
function btCounterFile(string $name): string
{
    return __DIR__ . '/../counters/' . $name . '.txt';
}

function btReadCounter(string $name): int
{
    $f = btCounterFile($name);
    $d = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
    return is_array($d) ? (int) ($d['total'] ?? 0) : (is_numeric($d) ? (int) $d : 0);
}

function btIncrementCounter(string $name): int
{
    $f = btCounterFile($name);
    if (!is_dir(dirname($f))) {
        mkdir(dirname($f), 0775, true);
    }
    $fh = fopen($f, 'c+');
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $d = $raw !== '' ? json_decode($raw, true) : null;
    $total = (is_array($d) ? (int) ($d['total'] ?? 0) : (int) $d) + 1;
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode(['total' => $total]));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $total;
}

/**
 * Keyed counters ({"items": {"key": N}}) in counters/{name}.txt — e.g. plans per language.
 */
function btIncrementMapCounter(string $name, string $key, int $by = 1): void
{
    $f = btCounterFile($name);
    if (!is_dir(dirname($f))) {
        mkdir(dirname($f), 0775, true);
    }
    $fh = fopen($f, 'c+');
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $d = $raw !== '' ? json_decode($raw, true) : null;
    $items = is_array($d['items'] ?? null) ? $d['items'] : [];
    $items[$key] = (int) ($items[$key] ?? 0) + $by;
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode(['items' => $items], JSON_UNESCAPED_UNICODE));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
}

/** @return array<string,int> */
function btReadMapCounter(string $name): array
{
    $f = btCounterFile($name);
    $d = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
    $items = is_array($d['items'] ?? null) ? array_map('intval', $d['items']) : [];
    arsort($items);
    return $items;
}
