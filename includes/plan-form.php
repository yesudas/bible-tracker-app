<?php

require_once __DIR__ . '/plans.php';

/** Shared plan-form pieces: used by plan-edit.php (saved plans) and download.php (printable PDF plans). */

function btBookOptions(array $books, array $selected = [], string $lang = 'en'): string
{
    $names = btBookNames($lang);
    $html = '';
    foreach ($books as $n => $b) {
        if ($n === 1) $html .= '<optgroup data-i18n="testament.ot" data-i18n-attr="label" label="' . btH(btT('testament.ot')) . '">';
        if ($n === 40) $html .= '</optgroup><optgroup data-i18n="testament.nt" data-i18n-attr="label" label="' . btH(btT('testament.nt')) . '">';
        $html .= '<option value="' . $n . '" data-book="' . $n . '" data-ch="' . $b['chapters'] . '"' . (in_array($n, $selected, true) ? ' selected' : '') . '>' . btH($names[$n]) . '</option>';
    }
    return $html . '</optgroup>';
}
function btBookChecks(array $books, string $name, array $selected, string $lang = 'en'): string
{
    $names = btBookNames($lang);
    $all = static fn (string $scope): string => '<label class="bc-selall"><input type="checkbox" class="bc-all" data-scope="' . $scope . '"><span data-i18n="tracker.selectAll">' . btH(btT('tracker.selectAll')) . '</span></label>';
    $html = '<div class="book-checks"><div class="bc-top">' . $all('all') . '</div>';
    foreach ($books as $n => $b) {
        if ($n === 1) $html .= '<div class="bc-head-row"><h3 class="bc-head" data-i18n="testament.ot">' . btH(btT('testament.ot')) . '</h3>' . $all('ot') . '</div><div class="bc-grid">';
        if ($n === 40) $html .= '</div><div class="bc-head-row"><h3 class="bc-head" data-i18n="testament.nt">' . btH(btT('testament.nt')) . '</h3>' . $all('nt') . '</div><div class="bc-grid">';
        $html .= '<label class="bc"><input type="checkbox" name="' . $name . '[]" value="' . $n . '"' . (in_array($n, $selected, true) ? ' checked' : '') . '><span data-book="' . $n . '">' . btH($names[$n]) . '</span></label>';
    }
    return $html . '</div></div>';
}
function btAllNames(): array
{
    $o = [];
    foreach (array_keys(BT_LANGUAGES) as $c) { $o[$c] = array_values(btBookNames($c)); }
    return $o;
}
/** Radio list. With $i18nPrefix the labels are translated (and tagged so the form can switch language live). */
function btRadio(string $name, array $opts, string $cur, string $i18nPrefix = ''): void
{
    foreach ($opts as $k => $label) {
        $text = $i18nPrefix !== '' ? btT($i18nPrefix . '.' . $k) : $label;
        $tag = $i18nPrefix !== '' ? ' data-i18n="' . btH($i18nPrefix . '.' . $k) . '"' : '';
        echo '<label class="opt"><input type="radio" name="' . $name . '" value="' . btH($k) . '"' . ($k === $cur ? ' checked' : '') . '><span' . $tag . '>' . btH($text) . '</span></label>';
    }
}

/**
 * The Language / Scope / "How to read?" fieldsets. $p holds the current values (lang, formScope, books, ranges, mode,
 * perDay, days, morningBooks, eveningBooks). The page must contain <form id="planForm"> so assets/js/app.js wires it up.
 */
function btPlanFormFields(array $p, array $books): void
{
    ?>
    <fieldset>
        <legend data-i18n="form.language"><?= btH(btT('form.language')) ?></legend>
        <p class="muted small" data-i18n="form.languageHelp"><?= btH(btT('form.languageHelp')) ?></p>
        <div class="opts opts-row"><?php btRadio('lang', BT_LANGUAGES, $p['lang']); ?></div>
    </fieldset>

    <fieldset id="scopeFieldset">
        <legend data-i18n="form.scope"><?= btH(btT('form.scope')) ?></legend>
        <div class="opts"><?php btRadio('scope', BT_FORM_SCOPES, $p['formScope'], 'scope'); ?></div>

        <div id="scopeBooks" hidden>
            <p class="muted small" data-i18n="form.tickBooks"><?= btH(btT('form.tickBooks')) ?></p>
            <?= btBookChecks($books, 'books', $p['books'], $p['lang']) ?>
        </div>

        <div id="scopeChapters" hidden>
            <p class="muted small" data-i18n="form.addRanges"><?= btH(btT('form.addRanges')) ?></p>
            <div id="rangeRows">
            <?php foreach ($p['ranges'] as $r): ?>
                <div class="range-row">
                    <select name="range_book[]"><?= btBookOptions($books, [(int) $r['book']], $p['lang']) ?></select>
                    <input type="number" name="range_from[]" min="1" value="<?= (int) $r['from'] ?>" aria-label="From chapter">
                    <span data-i18n="form.to"><?= btH(btT('form.to')) ?></span>
                    <input type="number" name="range_to[]" min="1" value="<?= (int) $r['to'] ?>" aria-label="To chapter">
                    <button type="button" class="icon-btn rm" aria-label="Remove range"><i class="bi bi-x-lg"></i></button>
                </div>
            <?php endforeach; ?>
            </div>
            <button type="button" id="addRange" class="btn btn-sm btn-outline"><i class="bi bi-plus-lg"></i> <span data-i18n="form.addRange"><?= btH(btT('form.addRange')) ?></span></button>
            <template id="rangeTpl">
                <div class="range-row">
                    <select name="range_book[]"><?= btBookOptions($books, [1], $p['lang']) ?></select>
                    <input type="number" name="range_from[]" min="1" value="1" aria-label="From chapter">
                    <span data-i18n="form.to"><?= btH(btT('form.to')) ?></span>
                    <input type="number" name="range_to[]" min="1" value="1" aria-label="To chapter">
                    <button type="button" class="icon-btn rm" aria-label="Remove range"><i class="bi bi-x-lg"></i></button>
                </div>
            </template>
        </div>

        <div id="twoBooks" hidden class="two-books">
            <p class="muted small" data-i18n="form.pickSession"><?= btH(btT('form.pickSession')) ?></p>
            <label class="field"><span class="field-label" data-i18n="form.morningBooks"><?= btH(btT('form.morningBooks')) ?></span>
                </label><?= btBookChecks($books, 'morning_books', $p['morningBooks'], $p['lang']) ?>
            <label class="field"><span class="field-label" data-i18n="form.eveningBooks"><?= btH(btT('form.eveningBooks')) ?></span>
                </label><?= btBookChecks($books, 'evening_books', $p['eveningBooks'], $p['lang']) ?>
        </div>
    </fieldset>

    <fieldset>
        <legend data-i18n="form.howToRead"><?= btH(btT('form.howToRead')) ?></legend>
        <div class="opts"><?php btRadio('mode', BT_MODES, $p['mode'], 'mode'); ?></div>
        <label class="field inline" id="perDayBox" hidden><span data-i18n="form.perDay"><?= btH(btT('form.perDay')) ?></span>
            <input type="number" name="per_day" min="1" max="100" value="<?= (int) $p['perDay'] ?>"></label>
        <label class="field inline" id="daysBox" hidden><span data-i18n="form.numDays"><?= btH(btT('form.numDays')) ?></span>
            <input type="number" name="days" min="1" max="3650" value="<?= (int) $p['days'] ?>"></label>
    </fieldset>
    <?php
}
