<?php
// POST csrf + display_name -> the name shown on the leaderboard (empty = back to the Google name).
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../includes/leaderboard.php';
require_once __DIR__ . '/../includes/admin.php';
header('Content-Type: application/json; charset=utf-8');

$user = btGoogleUser();
if (!$user || $_SERVER['REQUEST_METHOD'] !== 'POST' || !btCheckCsrf($user, $_POST['csrf'] ?? null)) {
    http_response_code(403); echo json_encode(['ok' => false, 'error' => 'Bad request']); exit;
}
$name = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($_POST['display_name'] ?? ''))));
$name = mb_substr($name, 0, 40);

$before = btDisplayName($user, btReadUserData($user));
btMutateUserData($user, function (array $d) use ($name): array {
    if ($name === '') { unset($d['displayName']); } else { $d['displayName'] = $name; }
    return $d;
});
btLbUpdate($user);
$after = btDisplayName($user, btReadUserData($user));
btNameLogAdd($user['email'], $before, $after, 'user request (IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ')');
echo json_encode(['ok' => true, 'displayName' => $after], JSON_UNESCAPED_UNICODE);
