<?php
require_once __DIR__ . '/counter.php';
require_once __DIR__ . '/partials.php';
require_once __DIR__ . '/version.php';
require_once __DIR__ . '/includes/leaderboard.php';

$user = btGoogleUser();
$flash = '';

// Delete a plan (POST)
if ($user && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (btCheckCsrf($user, $_POST['csrf'] ?? null)) {
        $delId = (string) ($_POST['id'] ?? '');
        btMutateUserPlans($user, fn (array $plans) => array_values(array_filter($plans, fn ($p) => ($p['id'] ?? '') !== $delId)));
        header('Location: ./?msg=deleted');
        exit;
    }
}
if (($_GET['msg'] ?? '') === 'deleted') $flash = btT('home.planDeleted');
if (($_GET['msg'] ?? '') === 'saved') $flash = btT('home.planSaved');

renderBtHeader('Bible Tracker - Bible Reading Plans', $user, $version);

if (!$user):
    $loginUrl = btGoogleLoginUrl(btCurrentUrl());
?>
<section class="hero">
    <img src="assets/images/icon-512.png" alt="Bible Tracker" class="hero-logo" width="120" height="120">
    <h1>Bible Tracker</h1>
    <p class="lead"><?= btH(btT('home.lead')) ?></p>
</section>

<section class="choice-grid">
    <div class="choice-card">
        <i class="bi bi-calendar2-check"></i>
        <h2><?= btH(btT('home.createTitle')) ?></h2>
        <p><?= btH(btT('home.createDesc')) ?></p>
        <?php if ($loginUrl): ?>
            <a class="btn btn-google" href="<?= btH($loginUrl) ?>"><i class="bi bi-google"></i> <?= btH(btT('home.signIn')) ?></a>
        <?php else: ?>
            <span class="btn btn-google disabled" aria-disabled="true"><i class="bi bi-google"></i> <?= btH(btT('home.signInUnavailable')) ?></span>
            <small class="muted"><?= btH(btT('home.signInUnavailableNote')) ?></small>
        <?php endif; ?>
    </div>
    <a class="choice-card" href="download.php">
        <i class="bi bi-download"></i>
        <h2><?= btH(btT('home.downloadTitle')) ?></h2>
        <p><?= btH(btT('home.downloadDesc')) ?></p>
        <span class="btn btn-outline"><?= btH(btT('home.downloadTitle')) ?></span>
    </a>
</section>
<?php else:
    $plans = btLoadUserPlans($user);
    $csrf = btCsrf($user);
?>
<section class="page-head">
    <div>
        <h1><?= btH(btT('home.myPlans')) ?></h1>
        <p class="muted"><?= btH(btT('home.welcome', ['name' => explode(' ', trim($user['name'] ?? ''))[0] ?: $user['email']])) ?></p>
    </div>
    <a class="btn btn-primary" href="plan-edit.php"><i class="bi bi-plus-lg"></i> <?= btH(btT('home.newPlan')) ?></a>
</section>
<?php if ($flash): ?><div class="flash"><?= btH($flash) ?></div><?php endif; ?>

<?php if (!$plans): ?>
    <div class="empty">
        <i class="bi bi-journal-plus"></i>
        <p><?= btH(btT('home.noPlans')) ?></p>
        <a class="btn btn-primary" href="plan-edit.php"><?= btH(btT('home.createFirst')) ?></a>
        <a class="btn btn-outline" href="download.php"><?= btH(btT('home.downloadReady')) ?></a>
    </div>
<?php else: ?>
    <ul class="plan-list">
    <?php foreach ($plans as $p): $pr = btPlanProgress($p); ?>
        <li class="plan-card">
            <a class="plan-main" href="plan.php?id=<?= btH($p['id']) ?>">
                <h2><?= btH($p['name']) ?></h2>
                <p class="muted"><?= btH(btPlanSummary($p)) ?></p>
                <div class="progress"><div class="progress-fill" style="width:<?= $pr['percent'] ?>%"></div></div>
                <p class="progress-text"><?= btH(btT('plan.progress', ['done' => $pr['done'], 'total' => $pr['total'], 'pct' => $pr['percent']])) ?><?= $pr['total'] && $pr['done'] >= $pr['total'] ? ' &middot; ' . btH(btT('plan.completed')) : '' ?></p>
            </a>
            <div class="plan-actions">
                <a class="btn btn-sm btn-primary" href="read.php?id=<?= btH($p['id']) ?>"><i class="bi bi-book"></i> <?= btH(btT('plan.read')) ?></a>
                <a class="btn btn-sm btn-outline" href="plan.php?id=<?= btH($p['id']) ?>"><i class="bi bi-check2-square"></i> <?= btH(btT('plan.track')) ?></a>
                <a class="btn btn-sm btn-outline" href="plan-edit.php?id=<?= btH($p['id']) ?>"><i class="bi bi-pencil"></i> <?= btH(btT('plan.edit')) ?></a>
                <form method="post" data-confirm="<?= btH(btT('plan.deleteConfirm')) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= btH($p['id']) ?>">
                    <input type="hidden" name="csrf" value="<?= btH($csrf) ?>">
                    <button class="btn btn-sm btn-danger" type="submit"><i class="bi bi-trash"></i> <?= btH(btT('plan.delete')) ?></button>
                </form>
            </div>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; endif; ?>

<?php
$lbTop = btLbRows(10);
$lbMe = $user ? btLbId($user['email']) : '';
$lbPodium = array_slice($lbTop, 0, 3);
$lbRest = array_slice($lbTop, 3, 7);
if ($lbTop):
    $podiumOrder = [1 => 'silver', 0 => 'gold', 2 => 'bronze'];
?>
<section class="leaderboard-widget card-panel" id="leaderboardWidget" aria-labelledby="leaderboard-widget-title">
    <h2 id="leaderboard-widget-title" class="section-title">🏆 <?= btH(btT('lb.title')) ?> <small class="muted">— <?= btH(btT('lb.subtitle')) ?></small></h2>
    <div class="podium">
        <?php foreach ($podiumOrder as $n => $tier): if (!isset($lbPodium[$n])) continue; $row = $lbPodium[$n]; $isMe = $row['id'] === $lbMe; ?>
            <div class="podium-step <?= $tier ?><?= $isMe ? ' is-me' : '' ?>">
                <span class="ord-tag"><?= $n + 1 ?></span>
                <span class="p-name"><?= btH($row['displayName']) ?><?php if ($isMe): ?> <span class="you-pill"><?= btH(btT('lb.you')) ?></span><?php endif; ?></span>
                <span class="p-score"><?= number_format($row['score']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($lbRest): ?>
        <div class="rest-list">
            <?php foreach ($lbRest as $n => $row): $isMe = $row['id'] === $lbMe; ?>
                <div class="rest-row<?= $isMe ? ' is-me' : '' ?>">
                    <span class="r-rank"><?= $n + 4 ?></span>
                    <span class="r-name"><?= btH($row['displayName']) ?><?php if ($isMe): ?> <span class="you-pill"><?= btH(btT('lb.you')) ?></span><?php endif; ?></span>
                    <span class="r-score"><?= number_format($row['score']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <a class="leaderboard-widget-more" href="leaderboard.php?view=all"><?= btH(btT('lb.showAll')) ?> →</a>
</section>
<?php endif; ?>

<?php renderBtFooter($version);
