<?php
// POST multipart field "pdf" -> {"file": "<name>"} (same flow as bible-quiz-app's api/pdf-store.php).
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/pdf-temp-store.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

const BT_PDF_MAX_BYTES = 12 * 1024 * 1024; // sanity cap on the finished PDF

// Uploads are multipart (not a raw application/pdf body): some hosts' WAF rules block an unusual raw-body
// Content-Type on a POST to a .php file, whereas a normal file-upload field is universally allowed.
// The browser sends the PDF in small chunks (id, index, total) so the size isn't limited by upload_max_filesize /
// post_max_size; a plain single upload (no id) is still accepted.
function btStoreFail(int $code, string $msg): never
{
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['pdf']['error'] ?? UPLOAD_ERR_NO_FILE;
    btStoreFail(400, in_array($err, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
        ? 'Upload too large for this server (upload_max_filesize ' . ini_get('upload_max_filesize') . ')'
        : 'Empty or invalid upload');
}
$bytes = file_get_contents($_FILES['pdf']['tmp_name']);
if ($bytes === false || $bytes === '') {
    btStoreFail(400, 'Empty upload');
}

$id = (string) ($_POST['id'] ?? '');
if ($id === '') {
    // Single-request upload
    if (strlen($bytes) > BT_PDF_MAX_BYTES) { btStoreFail(413, 'File too large'); }
    if (strncmp($bytes, '%PDF-', 5) !== 0) { btStoreFail(400, 'Not a PDF'); } // this folder is served as application/pdf
    btPdfTempCleanup();
    if (btPdfTempCount() >= 150) { btStoreFail(429, 'Too many files right now, please try again shortly'); } // flood guard
    echo json_encode(['file' => btPdfTempSave($bytes)]);
    exit;
}

// Chunked upload: chunk `index` of `total`, appended in order to pdf-temp/<id>.part, then finalised.
$part = btPdfTempPartPath($id);
$index = (int) ($_POST['index'] ?? -1);
$total = (int) ($_POST['total'] ?? 0);
if ($part === null || $total < 1 || $total > 200 || $index < 0 || $index >= $total) {
    btStoreFail(400, 'Bad chunk');
}
if (!is_dir(btPdfTempDir())) { @mkdir(btPdfTempDir(), 0755, true); }
if ($index === 0) {
    if (strncmp($bytes, '%PDF-', 5) !== 0) { btStoreFail(400, 'Not a PDF'); }
    btPdfTempCleanup();
    if (btPdfTempCount() >= 150) { btStoreFail(429, 'Too many files right now, please try again shortly'); }
    file_put_contents($part, $bytes, LOCK_EX); // starts (or restarts) this upload
} else {
    if (!is_file($part)) { btStoreFail(400, 'Unknown upload'); }
    if (filesize($part) + strlen($bytes) > BT_PDF_MAX_BYTES) { @unlink($part); btStoreFail(413, 'File too large'); }
    file_put_contents($part, $bytes, FILE_APPEND | LOCK_EX);
}
if ($index < $total - 1) {
    echo json_encode(['ok' => true, 'next' => $index + 1]);
    exit;
}
$filename = time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
if (!@rename($part, btPdfTempDir() . '/' . $filename)) { @unlink($part); btStoreFail(500, 'Could not store the file'); }
echo json_encode(['file' => $filename]);
