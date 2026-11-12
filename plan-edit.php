<?php
require_once __DIR__ . '/counter.php';
require_once __DIR__ . '/partials.php';
require_once __DIR__ . '/version.php';
require_once __DIR__ . '/includes/plan-form.php';

$user = btGoogleUser();
if (!$user) { header('Location: ./'); exit; }

$id = (string) ($_GET['id'] ?? $_POST['id'] ?? '');
$existing = $id !== '' ? btFindPlan(btLoadUserPlans($user), $id) : null;
if ($id !== '' && !$existing) { http_response_code(404); header('Location: ./'); exit; }

$books = btBooks();
$errors = [];
$p = $existing ?? [
    'name' => '', 'lang' => btUserSettings($user)['planLang'], 'scope' => 'whole', 'books' => [], 'ranges' => [['book' => 1, 'from' => 1, 'to' => 1]],
    'mode' => 'continuous', 'perDay' => 3, 'days' => 365, 'split' => 'none', 'morningBooks' => [], 'eveningBooks' => [],
];
$p['lang'] = btLang($p['lang'] ?? 'en');
$p['formScope'] = ($p['split'] ?? 'none') === 'two-books' ? 'two-books' : $p['scope'];
if (!$p['ranges']) $p['ranges'] = [['book' => 1, 'from' => 1, 'to' => 1]];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!btCheckCsrf($user, $_POST['csrf'] ?? null)) {
        $errors[] = btT('err.session');
    } else {
        [$new, $errors] = btPlanFromInput($_POST);
        if (!$errors && !btPlanDays($new)) $errors[] = btT('err.empty');
        if (!$errors) {
            $isNew = !$existing;
            btMutateUserPlans($user, function (array $plans) use ($new, $existing, $isNew): array {
                if ($existing) {
                    foreach ($plans as &$pl) {
                        if ($pl['id'] === $existing['id']) {
                            $scheduleChanged = array_diff_key($new, ['name' => 1, 'lang' => 1]) != array_intersect_key($pl, array_diff_key($new, ['name' => 1, 'lang' => 1]));
                            $pl = array_merge($pl, $new);
                            if ($scheduleChanged) $pl['doneChapters'] = []; // schedule changed: old ticks no longer map
                            $pl['updated'] = date('c');
                        }
                    }
                    unset($pl);
                } else {
                    $plans[] = $new + ['id' => bin2hex(random_bytes(6)), 'created' => date('c'), 'updated' => date('c'), 'doneChapters' => []];
                }
                return $plans;
            });
            // Remember the language for the next new plan's default.
            btMutateUserData($user, function (array $d) use ($new): array {
                $d['settings'] = (is_array($d['settings'] ?? null) ? $d['settings'] : []) + [];
                $d['settings']['planLang'] = $new['lang'];
                return $d;
            });
            if ($isNew) {
                btIncrementCounter('plans-created');
                btIncrementMapCounter('plans-by-language', $new['lang']);
                btIncrementMapCounter('plans-by-scope', $new['split'] === 'two-books' ? 'two-books' : $new['scope']);
                btIncrementMapCounter('plans-by-mode', $new['mode']);
            }
            header('Location: ./?msg=saved');
            exit;
        }
    }
    // Re-show what the user entered
    $p = array_merge($p, $new ?? []);
    $p['formScope'] = $p['split'] === 'two-books' ? 'two-books' : $p['scope'];
    if (empty($p['ranges'])) $p['ranges'] = [['book' => 1, 'from' => 1, 'to' => 1]];
}

renderBtHeader(btT($existing ? 'form.editPlan' : 'form.newPlan') . ' - Bible Tracker', $user, $version);
?>
<section class="page-head"><h1 data-i18n="<?= $existing ? 'form.editPlan' : 'form.newPlan' ?>"><?= btH(btT($existing ? 'form.editPlan' : 'form.newPlan')) ?></h1><a class="btn btn-outline" href="./" data-i18n="common.cancel"><?= btH(btT('common.cancel')) ?></a></section>
<?php foreach ($errors as $e): ?><div class="flash flash-error"><?= btH($e) ?></div><?php endforeach; ?>

<script>window.BT_NAMES = <?= json_encode(btAllNames(), JSON_UNESCAPED_UNICODE) ?>; window.BT_FORM_I18N = <?= json_encode(btFormLabelsAllLanguages(), JSON_UNESCAPED_UNICODE) ?>;</script>
<form method="post" id="planForm" class="form" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= btH(btCsrf($user)) ?>">
    <input type="hidden" name="id" value="<?= btH($id) ?>">

    <label class="field"><span class="field-label"><span data-i18n="form.planName"><?= btH(btT('form.planName')) ?></span> <small class="muted" data-i18n="form.optional"><?= btH(btT('form.optional')) ?></small></span>
        <input type="text" name="name" maxlength="80" value="<?= btH($p['name']) ?>" data-i18n="form.planNamePh" data-i18n-attr="placeholder" placeholder="<?= btH(btT('form.planNamePh')) ?>"></label>

    <?php btPlanFormFields($p, $books); ?>

    <?php if (($p['split'] ?? '') === 'morning-evening'): /* keep an existing plan's setting; no UI for it for now */ ?>
    <input type="hidden" name="morning_evening" value="1">
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <span data-i18n="<?= $existing ? 'form.saveChanges' : 'form.create' ?>"><?= btH(btT($existing ? 'form.saveChanges' : 'form.create')) ?></span></button>
    </div>
    <?php if ($existing): ?><p class="muted small" data-i18n="form.resetNote"><?= btH(btT('form.resetNote')) ?></p><?php endif; ?>
</form>
<?php renderBtFooter($version);
