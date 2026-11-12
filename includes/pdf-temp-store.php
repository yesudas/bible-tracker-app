<?php

/**
 * Short-lived storage for the printable-plan PDFs (same concept as bible-quiz-app's pdf-temp): pdf-temp/ is a
 * gitignored, `Require all denied` folder that is never served directly.
 *
 * download.php builds the PDF in the browser, then POSTs the bytes to api/pdf-store.php so a real https:// URL
 * (api/pdf-download.php) can be handed to the browser instead of a blob: URL — blob: URLs can't be downloaded
 * inside the Kodular Android app's WebView, which can only resolve real http(s) URLs.
 *
 * Files are named "{timestamp}_{8 hex chars}.pdf" and cleaned up opportunistically (no cron on this host):
 * btPdfTempCleanup() runs on every store, btPdfTempCleanupBeforeToday() from api/pdf-cleanup.php.
 */

function btPdfTempDir(): string
{
    return __DIR__ . '/../pdf-temp';
}

/** Deletes any stored PDF whose mtime is before $cutoffTimestamp. */
function btPdfTempDeleteOlderThan(int $cutoffTimestamp): void
{
    $files = array_merge(@glob(btPdfTempDir() . '/*.pdf') ?: [], @glob(btPdfTempDir() . '/*.part') ?: []); // .part = unfinished chunked uploads
    foreach ($files as $file) {
        $mtime = @filemtime($file);
        if ($mtime !== false && $mtime < $cutoffTimestamp) {
            @unlink($file);
        }
    }
}

/** Deletes any stored PDF older than $maxAgeSeconds (called on every store). */
function btPdfTempCleanup(int $maxAgeSeconds = 3600): void
{
    btPdfTempDeleteOlderThan(time() - $maxAgeSeconds);
}

/** Deletes any stored PDF from before today (server date) — a coarser backstop sweep. */
function btPdfTempCleanupBeforeToday(): void
{
    btPdfTempDeleteOlderThan((int) strtotime('today'));
}

function btPdfTempCount(): int
{
    $files = @glob(btPdfTempDir() . '/*.pdf');
    return is_array($files) ? count($files) : 0;
}

/** Writes $bytes as a new uniquely-named PDF and returns its filename (not a path). */
function btPdfTempSave(string $bytes): string
{
    $dir = btPdfTempDir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
    file_put_contents($dir . '/' . $filename, $bytes);
    return $filename;
}

/**
 * Validates a client-supplied filename against the exact shape btPdfTempSave() generates and confirms the
 * resolved path is really inside pdf-temp/. Returns null on any mismatch or if the file doesn't exist.
 */
function btPdfTempResolve(string $filename): ?string
{
    if (!preg_match('/^\d+_[0-9a-f]{8}\.pdf$/', $filename)) {
        return null;
    }
    $dirReal = realpath(btPdfTempDir());
    if ($dirReal === false) {
        return null;
    }
    $fileReal = realpath($dirReal . '/' . $filename);
    if ($fileReal === false || !str_starts_with($fileReal, $dirReal . DIRECTORY_SEPARATOR)) {
        return null;
    }
    return $fileReal;
}

/** Path of the unfinished upload with this id (16 hex chars), or null for a bad id. */
function btPdfTempPartPath(string $id): ?string
{
    return preg_match('/^[0-9a-f]{16}$/', $id) ? btPdfTempDir() . '/' . $id . '.part' : null;
}
