<?php
require_once __DIR__ . '/counter.php';
require_once __DIR__ . '/partials.php';
require_once __DIR__ . '/version.php';
require_once __DIR__ . '/includes/leaderboard.php';

$user = btGoogleUser();
$view = ($_GET['view'] ?? 'top10') === 'all' ? 'all' : 'top10';
$rows = btLbRows($view === 'all' ? null : 10);
$allRows = $view === 'all' ? $rows : btLbRows();

// Signed-in player's lifetime numbers (from their own file) and rank.
$myId = $user ? btLbId($user['email']) : '';
$data = $user ? btReadUserData($user) : [];
$stats = btStatsFromData($data);
$myName = $user ? btDisplayName($user, $data) : '';
$myRank = 0;
foreach ($allRows as $n => $r) { if ($r['id'] === $myId) { $myRank = $n + 1; break; } }
$tiers = btAchievementProgress($user ? $stats['chaptersRead'] : 0);

// Highest star earned, for the banner.
$best = null;
foreach ($tiers as $t) { foreach ($t['stars'] as $n => $lit) { if ($lit) $best = [btT('tier.' . $t['key']), $n + 1]; } }

$loginUrl = $user ? null : btGoogleLoginUrl(btCurrentUrl());
$logoutUrl = $user ? btGoogleLogoutUrl(preg_replace('#[^/]*$#', '', btCurrentUrl())) : null;

renderBtHeader(btT('lb.title') . ' - Bible Tracker', $user, $version);
?>
<section class="page-head">
    <h1>🏆 <?= btH(btT('lb.title')) ?><?= $view === 'all' ? '' : ' — ' . btH(btT('lb.top10')) ?></h1>
    <a class="btn btn-outline" href="./"><i class="bi bi-arrow-left"></i> <?= btH(btT('nav.home')) ?></a>
</section>

<?php if (!$rows): ?>
    <p class="empty-state muted"><?= btH(btT('lb.empty')) ?></p>
<?php else: ?>
    <table class="leaderboard-table">
        <thead><tr><th><?= btH(btT('lb.rank')) ?></th><th><?= btH(btT('lb.name')) ?></th><th><?= btH(btT('lb.chaptersRead')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $n => $row): $isMe = $row['id'] === $myId; ?>
            <tr<?= $isMe ? ' class="is-me"' : '' ?>>
                <td><?= $n + 1 ?></td>
                <td class="leaderboard-name-cell">
                    <?php if ($row['pictureUrl'] !== ''): ?><img class="player-avatar" src="<?= btH($row['pictureUrl']) ?>" alt="" width="28" height="28" referrerpolicy="no-referrer"><?php endif; ?>
                    <span><?= btH($row['displayName']) ?></span>
                    <?php if ($isMe): ?><span class="you-pill"><?= btH(btT('lb.you')) ?></span><?php endif; ?>
                </td>
                <td><?= number_format($row['score']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div class="leaderboard-actions">
    <?php if ($view === 'top10'): ?>
        <a href="leaderboard.php?view=all" class="btn btn-outline"><?= btH(btT('lb.showAll')) ?></a>
    <?php else: ?>
        <a href="leaderboard.php" class="btn btn-outline"><?= btH(btT('lb.top10')) ?></a>
    <?php endif; ?>
</div>

<div class="achievements-section card-panel" id="achievementsSection">
    <h2><?= btH(btT('ach.title')) ?></h2>
    <p class="achievements-intro"><?= btH(btT('ach.intro')) ?></p>

    <?php if ($user && $best): ?>
        <p class="achievements-badge-banner"><?= btT('ach.banner', ['name' => btH($myName), 'tier' => '<strong>' . btH($best[0]) . '</strong>', 'n' => (int) $best[1]]) ?></p>
    <?php endif; ?>

    <div class="achv-split">
        <div class="achv-sidebar">
            <?php if ($user): ?>
            <div class="achievements-identity">
                <div class="achv-avatar">
                    <?php if (!empty($user['picture'])): ?><img class="achv-avatar-img" src="<?= btH($user['picture']) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><i class="bi bi-person-circle"></i><?php endif; ?>
                </div>
                <strong class="achv-name" id="achvName"><?= btH($myName) ?></strong>
                <div class="achv-score"><span><?= number_format($stats['chaptersRead']) ?></span></div>
                <span class="achv-score-label"><?= btH(btT('lb.chaptersRead')) ?></span>
                <div class="achv-score achv-score-secondary"><span><?= number_format($stats['booksCompleted']) ?></span></div>
                <span class="achv-score-label"><?= btH(btT('ach.booksCompleted')) ?></span>
                <div class="achv-score achv-score-secondary"><span><?= $myRank ? '#' . $myRank : '—' ?></span></div>
                <span class="achv-score-label"><?= btH(btT('ach.rank')) ?></span>
                <div class="achv-actions">
                    <button type="button" class="achv-btn achv-btn-outline" id="editNameBtn"><i class="bi bi-pencil-square"></i> <?= btH(btT('ach.editName')) ?></button>
                    <?php if ($logoutUrl): ?><a href="<?= btH($logoutUrl) ?>" class="achv-btn achv-btn-outline-teal"><?= btH(btT('nav.signOut')) ?></a><?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="achievements-login">
                <div class="achv-avatar achv-avatar-placeholder"></div>
                <?php if ($loginUrl): ?><a href="<?= btH($loginUrl) ?>" class="achv-btn achv-btn-google"><i class="bi bi-google"></i> <?= btH(btT('home.signIn')) ?></a><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="achievement-tiers" id="achievementTiers">
        <?php foreach ($tiers as $tier): $complete = !in_array(false, $tier['stars'], true); ?>
            <div class="achievement-tier<?= $complete ? ' is-complete' : '' ?>" data-tier="<?= btH($tier['key']) ?>" data-complete-label="<?= btH(btT('ach.completed')) ?>" style="--tier-color: <?= btH($tier['color']) ?>">
                <div class="achievement-tier-icon"><?= $tier['icon'] ?></div>
                <div class="achievement-tier-body">
                    <span class="achievement-tier-label"><?= btH(btT('tier.' . $tier['key'])) ?></span>
                    <div class="achievement-tier-stars">
                    <?php foreach ($tier['stars'] as $n => $lit): $th = (int) $tier['thresholds'][$n]; ?>
                        <div class="achievement-star-wrap">
                            <span class="achievement-star<?= $lit ? ' is-lit' : '' ?>" style="--tier-color: <?= btH($tier['color']) ?>" title="<?= $th ?>+">★</span>
                            <span class="achievement-star-remaining"><?= $lit ? '' : btT('ach.toGo', ['n' => number_format(max(0, $th - ($user ? $stats['chaptersRead'] : 0)))]) ?></span>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>

    <?php if ($user): ?>
    <div id="editNameModal" class="modal-overlay" hidden role="dialog" aria-modal="true" aria-labelledby="editNameTitle">
        <div class="modal-box">
            <h2 id="editNameTitle"><?= btH(btT('ach.editName')) ?></h2>
            <p class="muted small"><?= btH(btT('ach.modalHelp')) ?></p>
            <label class="modal-field"><span><?= btH(btT('ach.displayName')) ?></span>
                <input type="text" id="editNameInput" maxlength="40" value="<?= btH($myName) ?>" data-csrf="<?= btH(btCsrf($user)) ?>"></label>
            <p class="modal-error" id="editNameError" hidden></p>
            <div class="modal-actions">
                <button type="button" class="btn btn-primary btn-sm" id="editNameSave"><?= btH(btT('common.save')) ?></button>
                <button type="button" class="btn btn-outline btn-sm" id="editNameCancel"><?= btH(btT('common.cancel')) ?></button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php renderBtFooter($version);
