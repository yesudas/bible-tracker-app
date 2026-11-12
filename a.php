<?php
/**
 * Admin screen — Google-signed-in admins only (see btAdmins() in includes/app-config.php).
 * Views: ?view=metrics (default) | users | leaderboard (incl. name-change history) | achievements
 */
require_once __DIR__ . '/partials.php';
require_once __DIR__ . '/version.php';
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/visitor-counter.php';

$user = btGoogleUser();
if (!btIsAdmin($user)) {
    http_response_code(403);
    renderBtHeader('Admin - Bible Tracker', $user, $version);
    echo '<section class="empty"><i class="bi bi-shield-lock"></i><p>' . ($user ? 'You do not have access to this page.' : 'Please sign in with an admin account.') . '</p><a class="btn btn-outline" href="./">Home</a></section>';
    renderBtFooter($version);
    exit;
}

$views = ['metrics' => ['📊 Metrics', 'bi-bar-chart'], 'users' => ['👥 Users', 'bi-people'], 'leaderboard' => ['🏆 Leaderboard', 'bi-trophy'], 'achievements' => ['🏅 Achievements', 'bi-award']];
$view = isset($views[$_GET['view'] ?? '']) ? $_GET['view'] : 'metrics';
$csrf = btCsrf($user);
$self = 'a.php?view=' . $view;
$go = static function (string $to, string $msg = '', bool $err = false): never {
    header('Location: a.php?view=' . $to . ($msg !== '' ? '&' . ($err ? 'err' : 'msg') . '=' . urlencode($msg) : ''));
    exit;
};

// ── POST actions (all CSRF-checked) ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!btCheckCsrf($user, $_POST['csrf'] ?? null)) {
        $go($view, 'Session expired — please try again.', true);
    }
    $action = (string) ($_POST['action'] ?? '');
    $target = btFindUserById((string) ($_POST['uid'] ?? ''));

    switch ($action) {
        case 'save_thresholds':
            $in = [];
            foreach (array_keys(btAchievementDefaultThresholds()) as $tier) {
                $in[$tier] = (array) ($_POST['th'][$tier] ?? []);
            }
            $ok = btAchievementSetThresholds($in);
            $go('achievements', $ok ? 'Thresholds saved.' : 'Could not save thresholds.', !$ok);
        case 'reset_thresholds':
            $go('achievements', btAchievementSetThresholds(null) ? 'Thresholds reset to defaults.' : 'Could not reset.');

        case 'lb_edit':
            if (!$target) { $go('leaderboard', 'User not found.', true); }
            $newName = mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($_POST['display_name'] ?? '')))), 0, 40);
            $newScore = max(0, (int) ($_POST['score'] ?? 0));
            $oldName = $target['displayName'];
            if ($newScore !== $target['stats']['chaptersRead']) {
                $tu = ['email' => $target['email'], 'name' => $target['name'], 'picture' => $target['picture']];
                btMutateUserData($tu, function (array $d) use ($newScore, $target): array {
                    $d['stats'] = ($d['stats'] ?? btBackfillStats($d['plans'])) ;
                    $d['stats']['chaptersRead'] = $newScore;
                    return $d;
                });
            }
            btSetDisplayName($target, $newName);
            $fresh = btFindUserById($target['id']);
            btNameLogAdd($target['email'], $oldName, $fresh['displayName'] ?? $oldName, 'admin');
            if (isset($_POST['listed_present'])) { // users screen: the "Listed on leaderboard" tick box
                $want = !empty($_POST['listed']);
                $tu = ['email' => $target['email'], 'name' => $target['name'], 'picture' => $target['picture']];
                btMutateUserData($tu, function (array $d) use ($want): array {
                    if ($want) { unset($d['leaderboardHidden']); } else { $d['leaderboardHidden'] = true; }
                    return $d;
                });
                btLbUpdate($tu);
            }
            $go(($_POST['back'] ?? '') === 'users' ? 'users' : 'leaderboard', 'Entry updated.');
        case 'lb_hide':
        case 'lb_restore':
            if (!$target) { $go('leaderboard', 'User not found.', true); }
            $tu = ['email' => $target['email'], 'name' => $target['name'], 'picture' => $target['picture']];
            btMutateUserData($tu, function (array $d) use ($action): array {
                if ($action === 'lb_hide') { $d['leaderboardHidden'] = true; } else { unset($d['leaderboardHidden']); }
                return $d;
            });
            btLbUpdate($tu);
            $go(($_POST['back'] ?? '') === 'users' ? 'users' : 'leaderboard', $action === 'lb_hide' ? 'Removed from the leaderboard.' : 'Restored to the leaderboard.');
        case 'name_revert':
            $go('leaderboard', btNameLogRevert((string) ($_POST['log_id'] ?? '')) ? 'Name reverted.' : 'Nothing to revert.');
        case 'user_delete':
            if (!$target) { $go('users', 'User not found.', true); }
            if (strtolower($target['email']) === strtolower($user['email'])) { $go('users', 'You cannot delete your own account here.', true); }
            $ok = btDeleteUser($target);
            $go('users', $ok ? 'User deleted.' : 'Could not delete the user.', !$ok);
    }
    $go($view);
}

$msg = (string) ($_GET['msg'] ?? '');
$err = (string) ($_GET['err'] ?? '');
renderBtHeader('Admin - Bible Tracker', $user, $version, 'admin-page');
?>
<section class="page-head"><h1>Admin</h1><a class="btn btn-outline" href="./"><i class="bi bi-arrow-left"></i> Site</a></section>
<nav class="admin-tabs" aria-label="Admin sections">
<?php foreach ($views as $k => [$label]): ?>
    <a class="chip<?= $k === $view ? ' active' : '' ?>" href="a.php?view=<?= $k ?>"><?= btH($label) ?></a>
<?php endforeach; ?>
</nav>
<?php if ($msg): ?><div class="flash"><?= btH($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="flash flash-error"><?= btH($err) ?></div><?php endif; ?>
<?php
$hidden = fn (string $action, string $uid = "", array $extra = []): string =>
    '<input type="hidden" name="action" value="' . btH($action) . '">'
    . '<input type="hidden" name="csrf" value="' . btH($csrf) . '">'
    . ($uid !== '' ? '<input type="hidden" name="uid" value="' . btH($uid) . '">' : '')
    . implode('', array_map(static fn ($k, $v) => '<input type="hidden" name="' . btH($k) . '" value="' . btH($v) . '">', array_keys($extra), $extra));

// ═══ Metrics ═══════════════════════════════════════════════════════════════
if ($view === 'metrics'):
    $users = btAllUsers();
    $plansNow = array_sum(array_map(fn ($u) => count($u['plans']), $users));
    $onBoard = count(btLbRows());
    $cards = [
        ['Visitors', btReadCounter('counter')], ['Downloads', btReadCounter('downloads')],
        ['Signed-in users', btReadCounter('signed-in-users')], ['Users now', count($users)],
        ['Plans created (all-time)', btReadCounter('plans-created')], ['Plans now', $plansNow],
        ['Chapters read', btReadCounter('chapters-read')], ['Books read', btReadCounter('books-read')],
        ['Quizzes attempted', btReadCounter('quizzes-attempted')], ['On leaderboard', $onBoard],
    ];
    $maps = [
        'Plans by language' => [btReadMapCounter('plans-by-language'), BT_LANGUAGES],
        'Plans by scope' => [btReadMapCounter('plans-by-scope'), BT_FORM_SCOPES_LABELS],
        'Plans by reading option' => [btReadMapCounter('plans-by-mode'), BT_MODE_LABELS],
    ];
    $byBook = btReadMapCounter('chapters-read-by-book');
?>
<div class="metric-grid">
<?php foreach ($cards as [$label, $n]): ?>
    <div class="metric"><span class="metric-n"><?= number_format((int) $n) ?></span><span class="metric-l"><?= btH($label) ?></span></div>
<?php endforeach; ?>
</div>
<div class="admin-cols">
<?php foreach ($maps as $title => [$items, $labels]): ?>
    <section class="card-panel"><h2 class="section-title"><?= btH($title) ?></h2>
    <?php if (!$items): ?><p class="muted small">No data yet.</p><?php else: ?>
        <table class="admin-table"><tbody>
        <?php foreach ($items as $k => $n): ?><tr><td><?= btH((string) ($labels[$k] ?? $k)) ?></td><td class="num"><?= number_format($n) ?></td></tr><?php endforeach; ?>
        </tbody></table>
    <?php endif; ?></section>
<?php endforeach; ?>
    <section class="card-panel"><h2 class="section-title">Top books (chapters read)</h2>
    <?php if (!$byBook): ?><p class="muted small">No data yet.</p><?php else: ?>
        <div class="table-scroll"><table class="admin-table"><tbody>
        <?php foreach (array_slice($byBook, 0, 15, true) as $b => $n): ?><tr><td><?= btH(btBookName((int) $b, 'en')) ?></td><td class="num"><?= number_format($n) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?></section>
</div>

<?php
// ═══ Users ═════════════════════════════════════════════════════════════════
elseif ($view === 'users'):
    $users = btAllUsers();
    $q = trim((string) ($_GET['q'] ?? ''));
    if ($q !== '') {
        $users = array_values(array_filter($users, fn ($u) => stripos($u['email'] . ' ' . $u['name'] . ' ' . $u['displayName'], $q) !== false));
    }
    $sel = isset($_GET['u']) ? btFindUserById((string) $_GET['u']) : null;
?>
<form method="get" class="admin-search"><input type="hidden" name="view" value="users">
    <input type="search" name="q" value="<?= btH($q) ?>" placeholder="Search name or email"><button class="btn btn-outline btn-sm" type="submit">Search</button>
    <span class="muted small"><?= count($users) ?> user<?= count($users) === 1 ? '' : 's' ?></span></form>

<?php if ($sel): ?>
<section class="card-panel" id="userDetail">
    <div class="user-head">
        <?php if ($sel['picture'] !== ''): ?><img class="player-avatar" src="<?= btH($sel['picture']) ?>" alt="" width="40" height="40" referrerpolicy="no-referrer"><?php endif; ?>
        <div><strong><?= btH($sel['name'] ?: $sel['displayName']) ?></strong><br><span class="muted small"><?= btH($sel['email']) ?></span></div>
        <a class="btn btn-sm btn-outline" href="a.php?view=users<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>">Close</a>
    </div>
    <p class="small">Leaderboard name: <strong><?= btH($sel['displayName']) ?></strong> · <?= number_format($sel['stats']['chaptersRead']) ?> chapters · <?= $sel['stats']['booksCompleted'] ?> books · joined <?= btH($sel['created'] !== '' ? substr($sel['created'], 0, 10) : '—') ?> · last active <?= btH(date('Y-m-d H:i', $sel['modified'])) ?><?= $sel['hidden'] ? ' · <em>hidden from leaderboard</em>' : '' ?></p>
    <h3 class="small">Plans (<?= count($sel['plans']) ?>)</h3>
    <?php if (!$sel['plans']): ?><p class="muted small">No plans.</p><?php else: ?>
    <div class="table-scroll"><table class="admin-table"><thead><tr><th>Plan</th><th>Setup</th><th>Progress</th></tr></thead><tbody>
    <?php foreach ($sel['plans'] as $p): $pr = btPlanProgress($p); ?>
        <tr><td><?= btH($p['name']) ?></td><td class="small"><?= btH(btPlanSummary($p)) ?> · <?= btH(BT_LANGUAGES[$p['lang'] ?? 'en'] ?? '') ?></td><td><?= $pr['done'] ?>/<?= $pr['total'] ?> (<?= $pr['percent'] ?>%)</td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
    <div class="admin-actions">
        <form method="post"><?= $hidden($sel['hidden'] ? 'lb_restore' : 'lb_hide', $sel['id'], ['back' => 'users']) ?><button class="btn btn-sm btn-outline" type="submit"><?= $sel['hidden'] ? 'Restore to leaderboard' : 'Remove from leaderboard' ?></button></form>
        <?php if (strtolower($sel['email']) !== strtolower($user['email'])): ?>
        <form method="post" data-confirm="Permanently delete this user, all their plans and progress?"><?= $hidden('user_delete', $sel['id']) ?><button class="btn btn-sm btn-danger" type="submit"><i class="bi bi-trash"></i> Delete user</button></form>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!$users): ?><p class="empty-state muted">No users found.</p><?php else: ?>
<div class="table-wide"><table class="admin-table editable-rows">
    <thead><tr><th>User</th><th>Email</th><th>Leaderboard name</th><th class="num">Plans</th><th class="num">Chapters</th><th class="num">Books</th><th>Joined</th><th>Last active</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): $formId = 'u' . $u['id']; $isSelf = strtolower($u['email']) === strtolower($user['email']); ?>
        <tr<?= $sel && $sel['id'] === $u['id'] ? ' class="row-sel"' : '' ?>>
            <td class="leaderboard-name-cell" data-label="User"><?php if ($u['picture'] !== ''): ?><img class="player-avatar" src="<?= btH($u['picture']) ?>" alt="" width="28" height="28" referrerpolicy="no-referrer"><?php endif; ?>
                <a href="a.php?view=users&amp;u=<?= btH($u['id']) ?><?= $q !== '' ? '&amp;q=' . urlencode($q) : '' ?>#userDetail"><?= btH($u['name'] ?: $u['displayName']) ?></a><?= btIsAdmin(['email' => $u['email']]) ? ' <span class="you-pill">ADMIN</span>' : '' ?></td>
            <td class="small" data-label="Email"><?= btH($u['email']) ?></td>
            <td data-label="Leaderboard name"><span class="view-only"><?= btH($u['displayName']) ?><?= $u['hidden'] ? ' <em class="muted small">(hidden)</em>' : '' ?></span>
                <span class="edit-only"><input form="<?= $formId ?>" type="text" name="display_name" maxlength="40" value="<?= btH($u['displayName']) ?>" aria-label="Leaderboard name">
                <label class="small"><input form="<?= $formId ?>" type="checkbox" name="listed" value="1"<?= $u['hidden'] ? '' : ' checked' ?>> Listed</label></span></td>
            <td class="num" data-label="Plans"><?= count($u['plans']) ?></td>
            <td class="num" data-label="Chapters"><span class="view-only"><?= number_format($u['stats']['chaptersRead']) ?></span><input class="edit-only" form="<?= $formId ?>" type="number" name="score" min="0" value="<?= (int) $u['stats']['chaptersRead'] ?>" aria-label="Chapters read"></td>
            <td class="num" data-label="Books"><?= $u['stats']['booksCompleted'] ?></td>
            <td class="small" data-label="Joined"><?= btH($u['created'] !== '' ? substr($u['created'], 0, 10) : '—') ?></td><td class="small" data-label="Last active"><?= btH(date('Y-m-d H:i', $u['modified'])) ?></td>
            <td class="row-actions">
                <button type="button" class="icon-act view-only row-edit" title="Edit" aria-label="Edit <?= btH($u['email']) ?>"><i class="bi bi-pencil"></i></button>
                <form id="<?= $formId ?>" method="post" class="edit-only"><?= $hidden('lb_edit', $u['id'], ['back' => 'users', 'listed_present' => '1']) ?><button class="btn btn-sm btn-primary" type="submit">Save</button> <button type="button" class="btn btn-sm btn-outline row-cancel">Cancel</button></form>
                <?php if (!$isSelf): ?>
                <form method="post" class="view-only" data-confirm="Permanently delete <?= btH($u['email']) ?>, all their plans and progress?"><?= $hidden('user_delete', $u['id']) ?><button class="icon-act icon-danger" type="submit" title="Delete user" aria-label="Delete <?= btH($u['email']) ?>"><i class="bi bi-trash"></i></button></form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?></tbody></table></div>
<?php endif; ?>

<?php
// ═══ Leaderboard (+ name-change history) ═══════════════════════════════════
elseif ($view === 'leaderboard'):
    $users = array_values(array_filter(btAllUsers(), fn ($u) => $u['stats']['chaptersRead'] > 0 || $u['hidden']));
    usort($users, fn ($a, $b) => [$b['stats']['chaptersRead'], $b['stats']['booksCompleted'], $a['displayName']] <=> [$a['stats']['chaptersRead'], $a['stats']['booksCompleted'], $b['displayName']]);
    $totalChapters = 1189;
    $nameLog = btNameLogRead();
    $byId = [];
    foreach (btAllUsers() as $u) { $byId[$u['id']] = $u; }
    $rank = 0;
?>
<h2 class="section-title">🏆 Leaderboard entries <span class="muted small">(<?= count($users) ?>)</span></h2>
<?php if (!$users): ?><p class="empty-state muted">No entries yet.</p><?php else: ?>
<div class="table-wide"><table class="admin-table lb-admin editable-rows">
    <thead><tr><th>Rank</th><th>Name</th><th>Email</th><th class="num">Chapters</th><th class="num">Books</th><th class="num">% of Bible</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): $shown = !$u['hidden']; if ($shown) $rank++; $formId = 'f' . $u['id']; ?>
        <tr>
            <td data-label="Rank"><?= $shown ? $rank : '—' ?></td>
            <td data-label="Name"><span class="view-only"><?= btH($u['displayName']) ?></span><input class="edit-only" form="<?= $formId ?>" type="text" name="display_name" maxlength="40" value="<?= btH($u['displayName']) ?>" aria-label="Display name"></td>
            <td class="small" data-label="Email"><?= btH($u['email']) ?></td>
            <td class="num" data-label="Chapters"><span class="view-only"><?= number_format($u['stats']['chaptersRead']) ?></span><input class="edit-only" form="<?= $formId ?>" type="number" name="score" min="0" value="<?= (int) $u['stats']['chaptersRead'] ?>" aria-label="Chapters read"></td>
            <td class="num" data-label="Books"><?= $u['stats']['booksCompleted'] ?></td>
            <td class="num" data-label="% of Bible"><?= round($u['stats']['chaptersRead'] * 100 / $totalChapters, 1) ?>%</td>
            <td class="small" data-label="Status"><?= $shown ? 'Listed' : '<em>Hidden</em>' ?></td>
            <td class="row-actions">
                <button type="button" class="icon-act view-only row-edit" title="Edit" aria-label="Edit <?= btH($u['displayName']) ?>"><i class="bi bi-pencil"></i></button>
                <form id="<?= $formId ?>" method="post" class="edit-only"><?= $hidden('lb_edit', $u['id'], ['back' => 'leaderboard']) ?><button class="btn btn-sm btn-primary" type="submit">Save</button> <button type="button" class="btn btn-sm btn-outline row-cancel">Cancel</button></form>
                <form method="post" class="view-only" data-confirm="<?= $shown ? 'Remove this person from the leaderboard?' : 'Restore this person to the leaderboard?' ?>"><?= $hidden($shown ? 'lb_hide' : 'lb_restore', $u['id'], ['back' => 'leaderboard']) ?><button class="icon-act" type="submit" title="<?= $shown ? 'Remove from leaderboard' : 'Restore to leaderboard' ?>" aria-label="<?= $shown ? 'Remove from leaderboard' : 'Restore to leaderboard' ?>"><i class="bi <?= $shown ? 'bi-trash' : 'bi-arrow-counterclockwise' ?>"></i></button></form>
            </td>
        </tr>
    <?php endforeach; ?></tbody></table></div>
<p class="muted small">Score is the user's lifetime chapters read. Editing it changes their stored total; removing hides them from the public leaderboard (their account and plans are untouched).</p>
<?php endif; ?>

<h2 class="section-title">📝 Name change history <span class="muted small">(<?= count($nameLog) ?>)</span></h2>
<?php if (!$nameLog): ?><p class="empty-state muted">No display-name changes have been logged yet.</p><?php else: ?>
<div class="table-scroll"><table class="admin-table">
    <thead><tr><th>When</th><th>Email</th><th>From</th><th>To</th><th>By</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($nameLog as $e): $owner = $byId[$e['userId'] ?? ''] ?? null; ?>
        <tr><td class="small"><?= btH($e['at'] ?? '') ?></td><td class="small"><?= btH($e['email'] ?? '—') ?></td><td><?= btH($e['from'] ?? '') ?></td><td><?= btH($e['to'] ?? '') ?></td><td class="small"><?= btH($e['by'] ?? '') ?></td>
            <td>
            <?php if (!empty($e['reverted'])): ?><span class="muted small">↩️ Reverted<?= isset($e['revertedAt']) ? ' ' . btH($e['revertedAt']) : '' ?></span>
            <?php elseif (!$owner): ?><span class="muted small">User removed</span>
            <?php else: ?><form method="post" data-confirm="Revert this name back to “<?= btH($e['from'] ?? '') ?>”?"><?= $hidden('name_revert', '', ['log_id' => (string) $e['id']]) ?><button class="btn btn-sm btn-outline" type="submit">↩️ Revert</button></form><?php endif; ?>
            </td></tr>
    <?php endforeach; ?></tbody></table></div>
<?php endif; ?>

<?php
// ═══ Achievements configuration ════════════════════════════════════════════
else:
    $tiers = btAchievementTiers();
    $defaults = btAchievementDefaultThresholds();
?>
<p class="muted">Chapters-read thresholds for each star on the leaderboard's Achievements section. Five ascending values per tier; changes apply immediately to everyone.</p>
<form method="post" class="card-panel"><?= $hidden('save_thresholds') ?>
    <div class="table-scroll"><table class="admin-table thresholds">
        <thead><tr><th>Tier</th><?php for ($i = 1; $i <= 5; $i++): ?><th>★ <?= $i ?></th><?php endfor; ?></tr></thead>
        <tbody>
        <?php foreach ($tiers as $key => $t): ?>
            <tr><td><?= $t['icon'] ?> <strong><?= btH($t['label']) ?></strong></td>
            <?php foreach ($t['thresholds'] as $i => $th): ?>
                <td><input type="number" name="th[<?= btH($key) ?>][]" min="0" value="<?= (int) $th ?>" aria-label="<?= btH($t['label']) ?> star <?= $i + 1 ?>" title="default <?= (int) $defaults[$key][$i] ?>"></td>
            <?php endforeach; ?></tr>
        <?php endforeach; ?></tbody></table></div>
    <div class="admin-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Save thresholds</button></div>
</form>
<form method="post" data-confirm="Reset all thresholds to the defaults?"><?= $hidden('reset_thresholds') ?><button class="btn btn-outline btn-sm" type="submit">Reset to defaults</button></form>
<p class="muted small">The whole Bible has 1,189 chapters. Values within a tier are sorted ascending when saved.</p>
<?php endif; ?>
<?php renderBtFooter($version);
