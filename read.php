<?php
require_once __DIR__ . '/counter.php';
require_once __DIR__ . '/partials.php';
require_once __DIR__ . '/version.php';

$user = btGoogleUser();
if (!$user) { header('Location: ./'); exit; }
$plan = btFindPlan(btLoadUserPlans($user), (string) ($_GET['id'] ?? ''));
if (!$plan) { header('Location: ./'); exit; }

$lang = btLang($plan['lang'] ?? 'en');
$names = btBookNames($lang);
$settings = btUserSettings($user);
$trans = btTranslations($lang);
$bible = btPickTranslation($lang, (string) ($_GET['bible'] ?? ($settings['bibles'][$lang] ?? '')));
$done = array_flip($plan['doneChapters'] ?? []);

// The plan's chapters in reading order.
$seq = [];
foreach (btPlanChaptersByBook($plan) as $b => $chs) {
    foreach ($chs as $c) { $seq[] = [$b, $c]; }
}
if (!$seq) { header('Location: ./'); exit; }

// Current chapter: ?b=&c= if it belongs to the plan, else the first unread one (or the first).
$cur = null;
foreach ($seq as $i => [$b, $c]) {
    if ($b === (int) ($_GET['b'] ?? 0) && $c === (int) ($_GET['c'] ?? 0)) { $cur = $i; break; }
}
if ($cur === null) {
    $cur = 0;
    foreach ($seq as $i => [$b, $c]) { if (!isset($done[$b . ':' . $c])) { $cur = $i; break; } }
}
[$b, $c] = $seq[$cur];
$prev = $seq[$cur - 1] ?? null;
$next = $seq[$cur + 1] ?? null;
// Bible Quiz embed for this chapter (bible-quiz-app language folder names).
$quizLang = ['en' => 'English', 'ta' => 'தமிழ்', 'hi' => 'हिंदी', 'kn' => 'ಕನ್ನಡ'][$lang] ?? 'English';
$quizUrl = 'https://wordofgod.in/bible-quiz/embed-quiz.php?lang=' . rawurlencode($quizLang)
    . '&type=chapter&books=' . sprintf('%02d', $b) . '&chapters=' . sprintf('%02d-%03d', $b, $c);
$url = fn (array $t) => 'read.php?id=' . urlencode($plan['id']) . '&b=' . $t[0] . '&c=' . $t[1] . ($bible !== '' ? '&bible=' . urlencode($bible) : '');
$label = fn (array $t) => $names[$t[0]] . ' ' . $t[1];

renderBtHeader($label([$b, $c]) . ' - Bible Tracker', $user, $version);
?>
<section class="page-head">
    <div><h1><?= btH($label([$b, $c])) ?></h1><p class="muted"><?= btH($plan['name']) ?> &middot; <?= $cur + 1 ?> / <?= count($seq) ?></p></div>
    <a class="btn btn-outline" href="plan.php?id=<?= btH($plan['id']) ?>"><i class="bi bi-check2-square"></i> <?= btH(btT('plan.track')) ?></a>
</section>

<?php if (count($trans['list']) > 0): ?>
<label class="bible-pick"><span class="muted small"><?= btH(btT('reader.translation')) ?></span>
    <select id="bibleSelect" aria-label="<?= btH(btT('reader.translation')) ?>">
    <?php foreach ($trans['list'] as $t): ?>
        <option value="<?= btH($t['abbr']) ?>"<?= $t['abbr'] === $bible ? ' selected' : '' ?>><?= btH($t['name']) ?></option>
    <?php endforeach; ?>
    </select>
</label>
<?php endif; ?>
<div class="reader-tools" role="group" aria-label="Text size">
    <button type="button" class="icon-btn" id="fsDown" aria-label="<?= btH(btT('reader.smaller')) ?>" title="<?= btH(btT('reader.smaller')) ?>"><i class="bi bi-dash-lg"></i></button>
    <span class="fs-val" id="fsVal"><?= (int) $settings['fontSize'] ?>px</span>
    <button type="button" class="icon-btn" id="fsUp" aria-label="<?= btH(btT('reader.larger')) ?>" title="<?= btH(btT('reader.larger')) ?>"><i class="bi bi-plus-lg"></i></button>
    <button type="button" class="icon-btn" id="fsReset" aria-label="<?= btH(btT('reader.reset')) ?>" title="<?= btH(btT('reader.reset')) ?>"><i class="bi bi-arrow-counterclockwise"></i></button>
</div>
<article class="reader" id="reader" style="--reader-fs:<?= (int) $settings['fontSize'] ?>px" lang="<?= btH($lang) ?>"
    data-lang="<?= btH($lang) ?>" data-bible="<?= btH($bible) ?>" data-book="<?= $b ?>" data-chapter="<?= $c ?>"
    data-plan="<?= btH($plan['id']) ?>" data-csrf="<?= btH(btCsrf($user)) ?>"
    data-next="<?= $next ? btH($url($next)) : '' ?>">
    <p class="muted" id="readerStatus"><?= btH(btT('reader.loading')) ?></p>
    <div id="verses"></div>
</article>

<div id="readerFooter">
<div class="reader-actions" id="readerActions" hidden>
    <?php if ($prev): ?><a class="btn btn-outline" href="<?= btH($url($prev)) ?>">&lsaquo; <?= btH($label($prev)) ?></a><?php endif; ?>
    <button type="button" class="btn btn-primary" id="markRead"<?= isset($done[$b . ':' . $c]) ? ' data-already="1"' : '' ?>><i class="bi bi-check-lg"></i> <span><?= btH(btT(isset($done[$b . ':' . $c]) ? 'reader.read' : 'reader.markRead')) ?></span></button>
    <button type="button" class="btn btn-outline" id="quizBtn" data-url="<?= btH($quizUrl) ?>" aria-expanded="false"><i class="bi bi-patch-question"></i> <span><?= btH(btT('reader.quiz')) ?></span></button>
    <?php if ($next): ?>
        <a class="btn btn-primary" id="nextBtn" href="<?= btH($url($next)) ?>"<?= isset($done[$b . ':' . $c]) ? '' : ' hidden' ?>><?= btH(btT('reader.next', ['ref' => $label($next)])) ?> &rsaquo;</a>
    <?php else: ?>
        <a class="btn btn-primary" id="nextBtn" href="plan.php?id=<?= btH($plan['id']) ?>"<?= isset($done[$b . ':' . $c]) ? '' : ' hidden' ?>><?= btH(btT('reader.planComplete')) ?></a>
    <?php endif; ?>
</div>
<div class="quiz-wrap" id="quizWrap" hidden></div>
</div>
<?php renderBtFooter($version);
