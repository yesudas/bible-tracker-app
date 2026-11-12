<?php
// POST event=quiz, csrf -> counts a quiz attempt (the "Attend Quiz" button opened the embedded quiz).
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../includes/visitor-counter.php';
header('Content-Type: application/json; charset=utf-8');

$user = btGoogleUser();
if (!$user || $_SERVER['REQUEST_METHOD'] !== 'POST' || !btCheckCsrf($user, $_POST['csrf'] ?? null)) {
    http_response_code(403); echo json_encode(['ok' => false]); exit;
}
if (($_POST['event'] ?? '') === 'quiz') {
    btIncrementCounter('quizzes-attempted');
}
echo json_encode(['ok' => true]);
