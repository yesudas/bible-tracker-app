<?php
// GET book, chapter, lang, bible(optional abbr) -> {success, bible, verses:[{number,verse}]} (signed-in users only).
require_once __DIR__ . '/../partials.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=3600');

if (!btGoogleUser()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not signed in']);
    exit;
}
echo json_encode(
    btFetchChapter((string) ($_GET['lang'] ?? 'en'), (int) ($_GET['book'] ?? 0), (int) ($_GET['chapter'] ?? 0), isset($_GET['bible']) ? (string) $_GET['bible'] : null),
    JSON_UNESCAPED_UNICODE
);
