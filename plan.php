<?php
require_once __DIR__ . '/counter.php';
require_once __DIR__ . '/partials.php';
require_once __DIR__ . '/version.php';

$user = btGoogleUser();
if (!$user) { header('Location: ./'); exit; }
$plan = btFindPlan(btLoadUserPlans($user), (string) ($_GET['id'] ?? ''));
if (!$plan) { header('Location: ./'); exit; }

$days = btPlanDays($plan);
$pr = btPlanProgress($plan);
$done = array_flip($plan['doneChapters'] ?? []);

renderBtHeader($plan['name'] . ' - Bible Tracker', $user, $version);
?>
<section class="page-head">
    <div><h1><?= btH($plan['name']) ?></h1><p class="muted"><?= btH(btPlanSummary($plan)) ?></p></div>
    <div class="head-actions"><a class="btn btn-primary" href="read.php?id=<?= btH($plan['id']) ?>"><i class="bi bi-book"></i> <?= btH(btT('plan.read')) ?></a> <a class="btn btn-outline" href="./"><i class="bi bi-arrow-left"></i> <?= btH(btT('tracker.plans')) ?></a></div>
</section>
<div class="progress big"><div class="progress-fill" id="progressFill" style="width:<?= $pr['percent'] ?>%"></div></div>
<p class="progress-text" id="progressLabel"><?= btH(btT('plan.progress', ['done' => $pr['done'], 'total' => $pr['total'], 'pct' => $pr['percent']])) ?></p>

<?php $byBook = btPlanChaptersByBook($plan); $names = btBookNames($plan['lang'] ?? 'en'); ?>
<div class="tracker" id="tracker" data-plan="<?= btH($plan['id']) ?>" data-csrf="<?= btH(btCsrf($user)) ?>">
<?php foreach ($byBook as $b => $chs): $allDone = true; foreach ($chs as $c) { if (!isset($done[$b . ':' . $c])) { $allDone = false; break; } } ?>
    <div class="book-row<?= $allDone ? ' complete' : '' ?>" data-book="<?= $b ?>">
        <div class="book-head">
            <span class="book-name"><?= btH($names[$b]) ?></span>
            <label class="select-all"><input type="checkbox" class="select-all-box"<?= $allDone ? ' checked' : '' ?>><span><?= btH(btT('tracker.selectAll')) ?></span></label>
        </div>
        <div class="chap-list">
        <?php foreach ($chs as $c): ?>
            <label class="chap"><input type="checkbox" data-key="<?= $b . ':' . $c ?>"<?= isset($done[$b . ':' . $c]) ? ' checked' : '' ?>><span><?= $c ?></span></label>
        <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<details class="schedule">
    <summary><?= btH(btT($plan['mode'] === 'continuous' ? 'tracker.scheduleReadings' : 'tracker.scheduleDays', ['n' => count($days)])) ?></summary>
    <ol class="day-list" id="dayList">
    <?php foreach ($days as $i => $sessions):
        $keys = []; $all = true;
        foreach ($sessions as $s) { foreach ($s['chapters'] as [$b, $c]) { $keys[] = $b . ':' . $c; if (!isset($done[$b . ':' . $c])) $all = false; } } ?>
        <li class="<?= $all ? 'done' : '' ?>" data-keys="<?= btH(implode(',', $keys)) ?>">
            <span class="day-no"><?= $plan['mode'] === 'continuous' ? '#' : btH(btT('tracker.day')) . ' ' ?><?= $i + 1 ?></span>
            <span class="day-reads">
            <?php foreach ($sessions as $s): ?>
                <span class="read"><?php if ($s['label']): ?><em><?= btH(btSessionLabel($s['label'])) ?>:</em> <?php endif; ?><?= btH(btFormatRefs($s['chapters'], $plan['lang'] ?? 'en')) ?></span>
            <?php endforeach; ?>
            </span>
        </li>
    <?php endforeach; ?>
    </ol>
</details>
<?php renderBtFooter($version);
