<?php
// GET file=<name from pdf-store>&name=<download filename> -> streams the stored PDF as a download.
require_once __DIR__ . '/../includes/pdf-temp-store.php';
require_once __DIR__ . '/../includes/visitor-counter.php';

$file = (string) ($_GET['file'] ?? '');
$path = btPdfTempResolve($file);
if ($path === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    exit;
}

$name = (string) ($_GET['name'] ?? '');
$name = preg_replace('/[^A-Za-z0-9 ._-]/', '', $name);
if ($name === '' || !str_ends_with(strtolower($name), '.pdf')) {
    $name = 'bible-reading-plan-from-wordofgod.in.pdf';
}

// Count real downloads (not HEAD probes / resumed ranges), like the other download counter.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !isset($_SERVER['HTTP_RANGE'])
    && !preg_match('/bot|crawl|spider|curl|wget|python/i', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''))) {
    btIncrementCounter('downloads');
}

// Same header set as bible-quiz-app's api/pdf-download.php (proven to work in Kodular's WebView): the plain quoted
// filename alone isn't enough there, so it also sends filename*= (RFC 6266) and the legacy File-Transfer headers.
$contentDisposition = 'attachment;'
    . ' filename="' . addslashes($name) . '";'
    . ' filename*=UTF-8\'\'' . rawurlencode($name);

header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: ' . $contentDisposition);
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . (string) filesize($path));
header('X-Content-Type-Options: nosniff');

if (ob_get_level() > 0) {
    ob_clean();
}
flush();
readfile($path);
