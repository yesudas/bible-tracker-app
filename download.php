<?php
require_once __DIR__ . '/counter.php';
require_once __DIR__ . '/partials.php';
require_once __DIR__ . '/version.php';
require_once __DIR__ . '/includes/plan-form.php';

$user = btGoogleUser();

renderBtHeader(btT('dl.title') . ' - Bible Tracker', $user, $version);

// Defaults for the custom-plan form (same fields as the plan editor).
$p = [
    'lang' => btUiLang(), 'scope' => 'whole', 'formScope' => 'whole', 'books' => [], 'ranges' => [['book' => 1, 'from' => 1, 'to' => 1]],
    'mode' => 'days', 'perDay' => 3, 'days' => 365, 'split' => 'none', 'morningBooks' => [], 'eveningBooks' => [],
];
$books = btBooks();
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;600;700&amp;family=Noto+Sans+Tamil:wght@400;600;700&amp;family=Noto+Sans+Devanagari:wght@400;600;700&amp;family=Noto+Sans+Kannada:wght@400;600;700&amp;display=swap">
<section class="page-head"><div><h1><?= btH(btT('dl.title')) ?></h1></div>
    <a class="btn btn-outline" href="./"><i class="bi bi-arrow-left"></i> <?= btH(btT('nav.home')) ?></a></section>

<section class="card-panel pdf-maker" id="pdfMaker">
    <h2 class="section-title" data-i18n="dl.customTitle"><?= btH(btT('dl.customTitle')) ?></h2>
    <p class="muted small" data-i18n="dl.customDesc"><?= btH(btT('dl.customDesc')) ?></p>
    <script>window.BT_NAMES = <?= json_encode(btAllNames(), JSON_UNESCAPED_UNICODE) ?>; window.BT_FORM_I18N = <?= json_encode(btFormLabelsAllLanguages(), JSON_UNESCAPED_UNICODE) ?>;</script>
    <form id="planForm" class="form" autocomplete="off" action="#" onsubmit="return false">
        <?php btPlanFormFields($p, $books); ?>

        <fieldset>
            <legend data-i18n="pdf.optionalSection"><?= btH(btT('pdf.optionalSection')) ?></legend>
            <label class="field"><span class="field-label" data-i18n="pdf.header"><?= btH(btT('pdf.header')) ?></span>
                <input type="text" id="pdfHeader" maxlength="80" data-i18n="pdf.headerPh" data-i18n-attr="placeholder" placeholder="<?= btH(btT('pdf.headerPh')) ?>"></label>
            <label class="field"><span class="field-label" data-i18n="pdf.subheader"><?= btH(btT('pdf.subheader')) ?></span>
                <input type="text" id="pdfSubheader" maxlength="140" data-i18n="pdf.subheaderPh" data-i18n-attr="placeholder" placeholder="<?= btH(btT('pdf.subheaderPh')) ?>"></label>
        </fieldset>

        <div class="form-actions">
            <button type="button" class="btn btn-primary" id="createPdfBtn"><i class="bi bi-file-earmark-pdf"></i> <span data-i18n="pdf.create"><?= btH(btT('pdf.create')) ?></span></button>
        </div>
        <div class="pdf-progress" id="pdfProgress" hidden>
            <div class="progress big" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="<?= btH(btT('pdf.creating')) ?>"><div class="progress-fill" id="pdfProgressFill" style="width:0%"></div></div>
            <p class="progress-text" id="pdfStatus" role="status"></p>
        </div>
        <p class="flash flash-error" id="pdfError" role="alert" hidden></p>
        <div class="pdf-download-ready" id="pdfResult" hidden>
            <p><strong><?= btH(btT('pdf.ready')) ?></strong></p>
            <a href="#" id="pdfLink" class="btn btn-primary"><i class="bi bi-download"></i> <?= btH(btT('pdf.download')) ?></a>
        </div>
    </form>
</section>

<?php if (!$user): $loginUrl = btGoogleLoginUrl(btCurrentUrl()); if ($loginUrl): ?>
    <p class="cta"><?= btH(btT('dl.cta')) ?> <a class="btn btn-google" href="<?= btH($loginUrl) ?>"><i class="bi bi-google"></i> <?= btH(btT('home.signIn')) ?></a></p>
<?php endif; endif; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" integrity="sha384-JcnsjUPPylna1s1fvi1u12X5qjY5OL56iySh75FdtrwhO/SWXgMjoVqcKyIIWOLk" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" integrity="sha384-ZZ1pncU3bQe8y31yfZdMFdSpttDoPmOZg2wguVK9almUodir1PghgT0eY7Mrty8H" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="assets/js/pdf-plan.js?v=<?= btH($version) ?>"></script>
<?php renderBtFooter($version);
