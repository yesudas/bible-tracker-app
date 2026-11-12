<?php
// POST csrf + any of: theme (light|dark), font_size (13-33|reset), bible_lang + bible (preferred translation) -> saved in the user's file.
require_once __DIR__ . '/../partials.php';
header('Content-Type: application/json; charset=utf-8');

$user = btGoogleUser();
if (!$user) { http_response_code(401); echo json_encode(['ok' => false, 'error' => 'Not signed in']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !btCheckCsrf($user, $_POST['csrf'] ?? null)) {
    http_response_code(403); echo json_encode(['ok' => false, 'error' => 'Bad request']); exit;
}

btMutateUserData($user, function (array $d): array {
    $s = is_array($d['settings'] ?? null) ? $d['settings'] : [];
    if (isset($_POST['theme'])) {
        $s['theme'] = $_POST['theme'] === 'dark' ? 'dark' : 'light';
    }
    if (isset($_POST['font_size'])) {
        if ($_POST['font_size'] === 'reset') {
            unset($s['fontSize']);
        } else {
            $s['fontSize'] = max(BT_FONT_MIN, min(BT_FONT_MAX, (int) $_POST['font_size']));
        }
    }
    if (isset($_POST['bible_lang'], $_POST['bible'])) {
        $bl = btLang($_POST['bible_lang']);
        $pick = btPickTranslation($bl, (string) $_POST['bible']);
        $s['bibles'] = is_array($s['bibles'] ?? null) ? $s['bibles'] : [];
        if ($pick !== '' && $pick === (string) $_POST['bible']) {
            $s['bibles'][$bl] = $pick;
        }
    }
    if (isset($_POST['plan_lang'])) {
        $s['planLang'] = btLang($_POST['plan_lang']); // the UI language = most recent language choice
    }
    $d['settings'] = $s;
    return $d;
});
echo json_encode(['ok' => true] + btUserSettings($user));
