/**
 * Printable reading-plan PDF (download.php).
 *
 * 1. POST the form to api/plan-schedule.php -> the day-by-day schedule in the chosen language.
 * 2. Lay it out as A4 pages with two columns (three for continuous plans) in the browser (the browser shapes Tamil / Hindi / Kannada text
 *    correctly), render each page with html2canvas and assemble the PDF with jsPDF.
 * 3. Upload the PDF to api/pdf-store.php in one request (chunked only if the server refuses it) (stored in pdf-temp/) and show a normal https:// link to
 *    api/pdf-download.php — a blob: URL can't be downloaded in the Kodular Android WebView (same approach as
 *    bible-quiz-app).
 */
(function () {
    'use strict';

    var A4W = 794, A4H = 1123;            // A4 at 96 dpi (CSS px)
    var PAD_X = 36, PAD_TOP = 34, PAD_BOTTOM = 56, GAP = 26;
    var SITE_URL = 'https://wordofgod.in/bible-tracker/';
    var SITE_LABEL = 'Bible Tracker';
    var IMG_DEFAULT = { scale: 2, quality: 0.8 }; // page image resolution/quality
    var FONT = '"Noto Sans","Noto Sans Tamil","Noto Sans Devanagari","Noto Sans Kannada",system-ui,-apple-system,"Segoe UI",sans-serif';

    function css() { return '' +
        '.pdf-stage{position:fixed;left:-20000px;top:0;width:' + A4W + 'px;font-family:' + FONT + ';color:#1b1b1b}' +
        '.pdf-stage *{box-sizing:border-box;margin:0;padding:0}' +
        '.pdf-page{position:relative;width:' + A4W + 'px;height:' + A4H + 'px;background:#fff;overflow:hidden}' +
        '.pdf-head{text-align:center;padding-bottom:10px;border-bottom:2px solid #1f8a5b}' +
        '.pdf-head h1{font-size:26px;line-height:1.3;font-weight:700;color:#14532d}' +
        '.pdf-head .sub{margin-top:4px;font-size:14px;line-height:1.45;color:#333}' +
        '.pdf-head .sum{margin-top:4px;font-size:11.5px;color:#666}' +
        '.pdf-cols{position:absolute;display:flex;gap:' + GAP + 'px;left:' + PAD_X + 'px;width:' + (A4W - 2 * PAD_X) + 'px}' +
                '.pr{display:flex;gap:8px;padding:5px 0;border-bottom:1px solid #e2e6e3;font-size:12.5px;line-height:1.4}' +
        '.pd{flex:0 0 var(--pdw,50px);font-weight:700;color:#14532d;white-space:nowrap}' +
        '.pl{flex:1;min-width:0}' +
        '.ps{display:flex;gap:6px;align-items:flex-start}' +
        '.cb{flex:0 0 13px;width:13px;height:13px;border:1.5px solid #14532d;border-radius:2px;margin-top:2px}' +
        '.pm{flex:0 0 auto;color:#666;font-size:11px;margin-top:1px}' +
        '.pt{flex:1;min-width:0;overflow-wrap:anywhere}' +
        '.pdf-foot{position:absolute;left:' + PAD_X + 'px;right:' + PAD_X + 'px;bottom:22px;display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#555;border-top:1px solid #cfd5d1;padding-top:7px}' +
        '.pdf-foot a{color:#14532d;text-decoration:underline;font-weight:600}'; }

    function emptyCols(n) { var a = []; for (var i = 0; i < n; i++) { a.push([]); } return a; }
    function t(key) { try { return (typeof BT === 'function') ? BT(key) : key; } catch (e) { return key; } }
    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    function slug(s) { var x = String(s || '').replace(/[^A-Za-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); return x || 'bible-reading-plan'; }

    function rowHtml(row, data) {
        var label = data.continuous ? String(row.n) : data.labels.day + ' ' + row.n;
        var lines = row.sessions.map(function (s) {
            return '<div class="ps"><i class="cb"></i>' + (s.label ? '<span class="pm">' + esc(s.label) + '</span>' : '') +
                '<span class="pt">' + esc(s.ref) + '</span></div>';
        }).join('');
        return '<div class="pr"><div class="pd">' + esc(label) + '</div><div class="pl">' + lines + '</div></div>';
    }

    // Wait for layout/paint; a timer backs up requestAnimationFrame, which pauses in background tabs
    function nextFrame() {
        return new Promise(function (resolve) {
            var done = false, finish = function () { if (!done) { done = true; resolve(); } };
            if (window.requestAnimationFrame) { requestAnimationFrame(function () { requestAnimationFrame(finish); }); }
            setTimeout(finish, 120);
        });
    }

    async function buildPdf(data, header, subheader, onProgress, imgOpts) {
        var IMG = Object.assign({}, IMG_DEFAULT, window.BT_PDF_OPTS || {}, imgOpts || {});
        // Continuous plans are one short reading per row, so they use 3 columns to save pages; the rest use 2
        var COLS = data.continuous ? 3 : 2;
        var COL_W = (A4W - 2 * PAD_X - GAP * (COLS - 1)) / COLS;
        var stage = document.createElement('div');
        stage.className = 'pdf-stage';
        stage.innerHTML = '<style>' + css() + '</style>';
        document.body.appendChild(stage);
        try {
            // Header block (first page only) + a probe holding all text, so the web fonts load before measuring
            var headHtml = '<div class="pdf-head"><h1>' + esc(header || data.summary) + '</h1>' +
                (subheader ? '<div class="sub">' + esc(subheader) + '</div>' : '') +
                (header ? '<div class="sum">' + esc(data.summary) + '</div>' : '') + '</div>';
            // Pass 1: load the web fonts and measure the widest "Day N" label so every row's label column is the same width
            var lastRow = data.rows[data.rows.length - 1];
            var probe = document.createElement('div');
            probe.style.width = (A4W - 2 * PAD_X) + 'px';
            probe.innerHTML = headHtml + '<div class="pr"><div class="pd" id="pdProbe" style="flex:none;--pdw:auto">' + esc(data.continuous ? String(lastRow.n) : data.labels.day + ' ' + lastRow.n) + '</div></div>' +
                '<div class="pdf-col" style="width:' + COL_W + 'px">' + data.rows.slice(0, 40).map(function (r) { return rowHtml(r, data); }).join('') + '</div>';
            stage.appendChild(probe);
            if (document.fonts && document.fonts.ready) { await Promise.race([document.fonts.ready, new Promise(function (r) { setTimeout(r, 4000); })]); }
            await nextFrame();
            var labelW = Math.ceil(probe.querySelector('#pdProbe').getBoundingClientRect().width) + 4;
            stage.removeChild(probe);
            stage.style.setProperty('--pdw', Math.max(data.continuous ? 24 : 44, labelW) + 'px');

            // Pass 2: measure the header and every row at the real column width
            var rowsHtml = data.rows.map(function (r) { return rowHtml(r, data); });
            probe = document.createElement('div');
            probe.style.width = (A4W - 2 * PAD_X) + 'px';
            probe.innerHTML = headHtml + '<div class="pdf-col" style="width:' + COL_W + 'px">' + rowsHtml.join('') + '</div>';
            stage.appendChild(probe);
            await nextFrame();
            var headH = probe.querySelector('.pdf-head').getBoundingClientRect().height;
            var heights = Array.prototype.map.call(probe.querySelectorAll('.pdf-col > .pr'), function (el) { return el.getBoundingClientRect().height; });
            stage.removeChild(probe);

            // Paginate: fill column 1, then column 2, then the next page
            var topFirst = PAD_TOP + headH + 14, topNext = PAD_TOP;
            var bottom = A4H - PAD_BOTTOM;
            var pages = [], page = { cols: emptyCols(COLS), top: topFirst }, col = 0, used = 0;
            for (var i = 0; i < heights.length; i++) {
                var avail = bottom - page.top;
                if (used + heights[i] > avail && page.cols[col].length) {
                    col++; used = 0;
                    if (col === COLS) { pages.push(page); page = { cols: emptyCols(COLS), top: topNext }; col = 0; }
                }
                page.cols[col].push(i);
                used += heights[i];
            }
            pages.push(page);

            onProgress(0, pages.length);
            var jsPDF = window.jspdf.jsPDF;
            var pdf = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait', compress: true });
            pdf.setProperties({ title: header || data.summary });
            var mm = 210 / A4W;

            for (var p = 0; p < pages.length; p++) {
                var pg = pages[p];
                var el = document.createElement('div');
                el.className = 'pdf-page';
                el.innerHTML = (p === 0 ? '<div style="position:absolute;left:' + PAD_X + 'px;top:' + PAD_TOP + 'px;width:' + (A4W - 2 * PAD_X) + 'px">' + headHtml + '</div>' : '') +
                    '<div class="pdf-cols" style="top:' + pg.top + 'px">' +
                    pg.cols.map(function (c) { return '<div class="pdf-col" style="width:' + COL_W + 'px">' + c.map(function (idx) { return rowsHtml[idx]; }).join('') + '</div>'; }).join('') +
                    '</div>' +
                    '<div class="pdf-foot"><span>' + esc(data.labels.page.replace('{n}', p + 1)) + '</span>' +
                    '<a href="' + SITE_URL + '">' + SITE_LABEL + '</a></div>';
                stage.appendChild(el);
                await nextFrame();

                var canvas = await html2canvas(el, { scale: IMG.scale, backgroundColor: '#ffffff', useCORS: true, logging: false, width: A4W, height: A4H });
                if (p > 0) { pdf.addPage('a4', 'portrait'); }
                pdf.addImage(canvas.toDataURL('image/jpeg', IMG.quality), 'JPEG', 0, 0, 210, 297, undefined, 'FAST');

                // Make the "Bible Tracker" footer text a clickable link
                var a = el.querySelector('.pdf-foot a').getBoundingClientRect(), pr = el.getBoundingClientRect();
                pdf.link((a.left - pr.left) * mm, (a.top - pr.top) * mm, a.width * mm, a.height * mm, { url: SITE_URL });

                stage.removeChild(el);
                canvas.width = canvas.height = 0; // free memory early (phones)
                onProgress(p + 1, pages.length);
            }
            var out = pdf.output('blob');
            window.BT_LAST_PDF_BYTES = out.size;
            return out;
        } finally {
            if (stage.parentNode) { stage.parentNode.removeChild(stage); }
        }
    }

    // Upload the PDF in ONE request (fastest: a single write on the server). Only if the server refuses it because
    // of its upload limits (upload_max_filesize / post_max_size, 400 or 413) fall back to small chunks.
    async function uploadPdf(blob, onChunk) {
        var one = new FormData();
        one.append('pdf', blob, 'plan.pdf');
        try {
            var r1 = await fetch('api/pdf-store.php', { method: 'POST', body: one });
            var b1 = await r1.json().catch(function () { return {}; });
            if (r1.ok && b1.file) {
                if (onChunk) { onChunk(1, 1); }
                return b1;
            }
            if (r1.status !== 400 && r1.status !== 413) { throw new Error(b1.error || 'store'); }
        } catch (e) {
            if (!(e instanceof TypeError)) { throw e; } // a network error here is retried below as chunks
        }
        return uploadChunks(blob, onChunk);
    }

    // Small chunks so the PDF size never hits the server's upload limits
    async function uploadChunks(blob, onChunk) {
        var CHUNK = 600 * 1024;
        var total = Math.max(1, Math.ceil(blob.size / CHUNK));
        var id = Array.prototype.map.call(crypto.getRandomValues(new Uint8Array(8)), function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
        for (var i = 0; i < total; i++) {
            var fd = new FormData();
            fd.append('pdf', blob.slice(i * CHUNK, Math.min(blob.size, (i + 1) * CHUNK)), 'part.bin');
            fd.append('id', id); fd.append('index', String(i)); fd.append('total', String(total));
            var res = await fetch('api/pdf-store.php', { method: 'POST', body: fd });
            var body = await res.json().catch(function () { return {}; });
            if (!res.ok) { throw new Error(body.error || 'store'); }
            if (onChunk) { onChunk(i + 1, total); }
            if (i === total - 1) { if (!body.file) { throw new Error('store'); } return body; }
        }
    }

    function init() {
        var form = document.getElementById('planForm');
        var btn = document.getElementById('createPdfBtn');
        if (!form || !btn) { return; }
        var status = document.getElementById('pdfStatus');
        var bar = document.getElementById('pdfProgress');
        var fill = document.getElementById('pdfProgressFill');
        var error = document.getElementById('pdfError');
        var result = document.getElementById('pdfResult');
        var link = document.getElementById('pdfLink');

        function show(el, text) { el.textContent = text || ''; el.hidden = !text; }
        // Progress bar: 0-5% schedule, 5-10% preparing, 10-88% rendering pages, 88-100% uploading
        function progress(pct, text) {
            pct = Math.max(0, Math.min(100, Math.round(pct)));
            bar.hidden = false;
            fill.style.width = pct + '%';
            bar.firstElementChild.setAttribute('aria-valuenow', String(pct));
            status.textContent = text + ' ' + pct + '%';
        }

        // Any change to the form makes the finished PDF out of date, so hide its "ready" box and download link
        function hideReady() { if (!btn.disabled) { result.hidden = true; } }
        form.addEventListener('change', hideReady);
        form.addEventListener('input', hideReady);

        btn.addEventListener('click', async function () {
            show(error, ''); result.hidden = true;
            btn.disabled = true;
            progress(2, t('pdf.creating'));
            fetch('api/pdf-cleanup.php', { method: 'POST' }).catch(function () {}); // sweep old files (fire and forget)
            try {
                if (!window.jspdf || !window.html2canvas) { throw new Error('libs'); }
                var res = await fetch('api/plan-schedule.php', { method: 'POST', body: new FormData(form) });
                var data = await res.json();
                if (!res.ok || !data.ok) { var e = new Error(data.error || 'schedule'); e.userMessage = data.error; throw e; }

                progress(6, t('pdf.creating'));
                var header = document.getElementById('pdfHeader').value.trim();
                var sub = document.getElementById('pdfSubheader').value.trim();
                var blob = await buildPdf(data, header, sub, function (n, total) { progress(10 + (n / total) * 78, t('pdf.creating')); });
                progress(88, t('pdf.creating'));
                var stored = await uploadPdf(blob, function (n, total) { progress(88 + (n / total) * 11, t('pdf.creating')); });
                progress(100, t('pdf.creating'));

                link.href = 'api/pdf-download.php?file=' + encodeURIComponent(stored.file) + '&name=' + encodeURIComponent(slug(header) + '-from-wordofgod.in.pdf');
                bar.hidden = true;
                result.hidden = false;
                if (result.scrollIntoView) { result.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }
            } catch (err) {
                bar.hidden = true;
                show(error, (err && err.userMessage) ? err.userMessage : t('pdf.error'));
            } finally {
                btn.disabled = false;
            }
        });
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
