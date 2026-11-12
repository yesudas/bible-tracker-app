<?php
// POST the plan-form fields (lang, scope, books[], range_*, mode, per_day, days, morning_books[], evening_books[])
// -> JSON schedule for the printable PDF (assets/js/pdf-plan.js lays it out). Public: no sign-in needed.
require_once __DIR__ . '/../partials.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const BT_PDF_MAX_ROWS = 1500; // keeps the PDF (rendered in the browser) a sensible size

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

unset($_POST['morning_evening']); // not offered on this form
[$plan, $errors] = btPlanFromInput($_POST);
$lang = $plan['lang'];
if (!$errors) {
    $days = btPlanDays($plan);
    if (!$days) {
        $errors[] = btT('err.empty');
    } elseif (count($days) > BT_PDF_MAX_ROWS) {
        $errors[] = btT('pdf.tooMany', [], $lang);
    }
}
if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => implode(' ', $errors)], JSON_UNESCAPED_UNICODE);
    exit;
}

$rows = [];
foreach ($days as $i => $sessions) {
    $rows[] = [
        'n' => $i + 1,
        'sessions' => array_map(static fn (array $s): array => [
            'label' => $s['label'] !== '' ? btSessionLabel($s['label'], $lang) : '',
            'ref' => btFormatRefs($s['chapters'], $lang),
        ], $sessions),
    ];
}

echo json_encode([
    'ok' => true,
    'lang' => $lang,
    'summary' => btPlanSummary($plan, true, true),
    'continuous' => $plan['mode'] === 'continuous',
    'labels' => ['day' => btT('tracker.day', [], $lang), 'page' => btT('pdf.page', ['n' => '{n}'], $lang)],
    'rows' => $rows,
], JSON_UNESCAPED_UNICODE);
