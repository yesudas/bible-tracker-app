// PWA service worker + shared UI
const BT = (k, vars) => {
    let t = (window.BT_T || {})[k] || k;
    Object.keys(vars || {}).forEach((n) => { t = t.split('{' + n + '}').join(vars[n]); });
    return t;
};

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('./sw.js').catch(() => {});
}

// PWA install (same approach as bible-quiz-app): buttons stay hidden until the browser says the app is installable.
(function () {
    const buttons = document.querySelectorAll('.install-app-btn');
    if (!buttons.length) return;
    let deferred = null;
    const standalone = (() => {
        try {
            return new URLSearchParams(location.search).get('f') === 'app'
                || matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
        } catch (e) { return false; }
    })();
    const setHidden = (h) => buttons.forEach((b) => { b.hidden = h; });
    setHidden(true);
    if (standalone) return;
    const ios = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
    if (ios) setHidden(false); // iOS has no install prompt: show instructions instead
    window.addEventListener('beforeinstallprompt', (e) => { e.preventDefault(); deferred = e; setHidden(false); });
    window.addEventListener('appinstalled', () => { deferred = null; setHidden(true); });
    buttons.forEach((b) => b.addEventListener('click', async () => {
        if (deferred) {
            deferred.prompt();
            await deferred.userChoice;
            deferred = null;
        } else if (ios) {
            alert(BT('install.ios'));
        }
    }));
})();

(function () {
    const nav = document.getElementById('siteNav');
    const btn = document.getElementById('navToggle');
    if (nav && btn) {
        btn.addEventListener('click', () => { nav.hidden = !nav.hidden; });
    }

    // Language icon (header): saved to the user's file when signed in, else to a cookie; then reload in that language
    const langToggle = document.getElementById('langToggle');
    const langPop = document.getElementById('langPop');
    if (langToggle && langPop) {
        const closePop = () => { langPop.hidden = true; langToggle.setAttribute('aria-expanded', 'false'); };
        langToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            langPop.hidden = !langPop.hidden;
            langToggle.setAttribute('aria-expanded', String(!langPop.hidden));
        });
        document.addEventListener('click', (e) => { if (!e.target.closest('.lang-menu')) closePop(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePop(); });
        langPop.addEventListener('click', (e) => {
            const b = e.target.closest('button[data-lang]');
            if (!b) return;
            const code = b.dataset.lang;
            document.cookie = 'bt_lang=' + code + ';path=/;max-age=31536000;SameSite=Lax';
            const done = () => location.reload();
            if (langToggle.dataset.csrf) {
                fetch('api/settings.php', { method: 'POST', body: new URLSearchParams({ plan_lang: code, csrf: langToggle.dataset.csrf }) }).then(done, done);
            } else {
                done();
            }
        });
    }

    // Saved per-user settings (theme, reader font size) -> api/settings.php
    const csrfEl = document.getElementById('themeToggle');
    const saveSetting = (data) => {
        if (!csrfEl) return Promise.resolve();
        return fetch('api/settings.php', { method: 'POST', body: new URLSearchParams({ ...data, csrf: csrfEl.dataset.csrf }) }).catch(() => {});
    };
    if (csrfEl) {
        csrfEl.addEventListener('click', () => {
            const root = document.documentElement;
            const dark = root.dataset.theme !== 'dark';
            root.dataset.theme = dark ? 'dark' : 'light';
            csrfEl.querySelector('i').className = 'bi ' + (dark ? 'bi-sun' : 'bi-moon-stars');
            const m = document.querySelector('meta[name="theme-color"]');
            if (m) m.content = dark ? '#12301f' : '#1f8a5b';
            saveSetting({ theme: dark ? 'dark' : 'light' });
        });
    }

    // Plan form: show/hide sections for the chosen options.
    const form = document.getElementById('planForm');
    if (form) {
        const val = (n) => (form.querySelector(`[name="${n}"]:checked`) || {}).value;
        const show = (id, on) => { const el = document.getElementById(id); if (el) el.hidden = !on; };
        const refresh = () => {
            const scope = val('scope'), mode = val('mode');
            const two = scope === 'two-books';
            show('scopeBooks', !two && scope === 'books');
            show('scopeChapters', !two && scope === 'chapters');
            show('twoBooks', two);
            show('perDayBox', mode === 'perday');
            show('daysBox', mode === 'days');
        };
        const relabel = () => {
            // Switch the form's own text to the chosen language (labels from window.BT_FORM_I18N)
            const tr = (window.BT_FORM_I18N || {})[val('lang')];
            if (tr) {
                form.closest('main').querySelectorAll('[data-i18n]').forEach((el) => {
                    const t = tr[el.dataset.i18n];
                    if (t === undefined) return;
                    if (el.dataset.i18nAttr) el.setAttribute(el.dataset.i18nAttr, t); else el.textContent = t;
                });
                document.documentElement.lang = val('lang');
            }
            const names = (window.BT_NAMES || {})[val('lang')];
            if (!names) return;
            form.querySelectorAll('option[data-book]').forEach((o) => { o.textContent = names[o.dataset.book - 1]; });
            form.querySelectorAll('span[data-book]').forEach((s) => { s.textContent = names[s.dataset.book - 1]; });
        };
        form.addEventListener('change', (e) => { if (e.target.name === 'lang') relabel(); refresh(); });
        refresh();
        relabel();

        // Book checklists: "Select all" (whole list), and "Select all" under Old / New Testament
        const syncBooks = (box) => {
            const cbs = [...box.querySelectorAll('.bc input')];
            const state = (list, scope) => {
                const t = box.querySelector('.bc-all[data-scope="' + scope + '"]');
                if (!t) return;
                const n = list.filter((c) => c.checked).length;
                t.checked = list.length > 0 && n === list.length;
                t.indeterminate = n > 0 && n < list.length;
            };
            state(cbs, 'all');
            state(cbs.filter((c) => +c.value <= 39), 'ot');
            state(cbs.filter((c) => +c.value >= 40), 'nt');
        };
        form.querySelectorAll('.book-checks').forEach(syncBooks);
        form.addEventListener('change', (e) => {
            const t = e.target;
            const box = t.closest && t.closest('.book-checks');
            if (!box) return;
            if (t.classList.contains('bc-all')) {
                const scope = t.dataset.scope;
                box.querySelectorAll('.bc input').forEach((c) => {
                    const n = +c.value;
                    if (scope === 'all' || (scope === 'ot' && n <= 39) || (scope === 'nt' && n >= 40)) c.checked = t.checked;
                });
            }
            syncBooks(box);
        });

        // Chapter ranges: add / remove rows
        const rows = document.getElementById('rangeRows');
        const tpl = document.getElementById('rangeTpl');
        const addBtn = document.getElementById('addRange');
        if (rows && tpl && addBtn) {
            const bind = (row) => {
                const sel = row.querySelector('select');
                const setMax = () => {
                    const max = parseInt(sel.selectedOptions[0].dataset.ch, 10);
                    row.querySelectorAll('input[type=number]').forEach((i) => {
                        i.max = max;
                        if (+i.value > max) i.value = max;
                    });
                };
                sel.addEventListener('change', setMax);
                row.querySelector('.rm').addEventListener('click', () => { if (rows.children.length > 1) row.remove(); });
                setMax();
            };
            rows.querySelectorAll('.range-row').forEach(bind);
            addBtn.addEventListener('click', () => {
                const row = tpl.content.firstElementChild.cloneNode(true);
                rows.appendChild(row);
                bind(row);
                relabel();
            });
        }
    }

    // Plan tracker: tick chapters (saved via api/progress.php)
    const tracker = document.getElementById('tracker');
    if (tracker) {
        const bar = document.getElementById('progressFill');
        const label = document.getElementById('progressLabel');
        const checked = (k) => { const cb = tracker.querySelector(`input[data-key="${k}"]`); return cb && cb.checked; };
        const refreshView = () => {
            tracker.querySelectorAll('.book-row').forEach((row) => {
                const boxes = [...row.querySelectorAll('.chap input')];
                const n = boxes.filter((b) => b.checked).length;
                row.classList.toggle('complete', n === boxes.length);
                const all = row.querySelector('.select-all-box');
                all.checked = n === boxes.length;
                all.indeterminate = n > 0 && n < boxes.length;
            });
            document.querySelectorAll('#dayList li').forEach((li) => {
                li.classList.toggle('done', li.dataset.keys.split(',').every(checked));
            });
        };
        refreshView();
        const save = (boxes, state) => {
            const keys = boxes.map((b) => b.dataset.key).join(',');
            const body = new URLSearchParams({ id: tracker.dataset.plan, keys, done: state ? '1' : '0', csrf: tracker.dataset.csrf });
            boxes.forEach((b) => { b.checked = state; });
            refreshView();
            return fetch('api/progress.php', { method: 'POST', body })
                .then((r) => r.json())
                .then((j) => {
                    if (!j.ok) { const err = new Error(j.error || 'failed'); err.blocked = !!j.blocked; throw err; }
                    bar.style.width = j.percent + '%';
                    label.textContent = BT('plan.progress', { done: j.done, total: j.total, pct: j.percent });
                })
                .catch((err) => {
                    boxes.forEach((b) => { b.checked = !state; });
                    refreshView();
                    alert(err && err.blocked ? err.message : BT('reader.saveError'));
                });
        };
        tracker.addEventListener('change', (e) => {
            const t = e.target;
            if (t.matches('.select-all-box')) {
                save([...t.closest('.book-row').querySelectorAll('.chap input')], t.checked);
            } else if (t.matches('.chap input')) {
                save([t], t.checked);
            }
        });
    }

    // Bible reader
    const reader = document.getElementById('reader');
    if (reader) {
        const status = document.getElementById('readerStatus');
        const box = document.getElementById('verses');
        const actions = document.getElementById('readerActions');
        const FS = { def: 17, min: 13, max: 33, step: 2 };
        const fsVal = document.getElementById('fsVal');
        let size = parseInt(getComputedStyle(reader).fontSize, 10) || FS.def;
        const applySize = (n, persist) => {
            size = Math.max(FS.min, Math.min(FS.max, n));
            reader.style.setProperty('--reader-fs', size + 'px');
            fsVal.textContent = size + 'px';
            if (persist) saveSetting({ font_size: size === FS.def ? 'reset' : size });
        };
        document.getElementById('fsUp').addEventListener('click', () => applySize(size + FS.step, true));
        document.getElementById('fsDown').addEventListener('click', () => applySize(size - FS.step, true));
        document.getElementById('fsReset').addEventListener('click', () => applySize(FS.def, true));
        const loadText = () => {
            const q = new URLSearchParams({ lang: reader.dataset.lang, book: reader.dataset.book, chapter: reader.dataset.chapter });
            if (reader.dataset.bible) q.set('bible', reader.dataset.bible);
            status.textContent = BT('reader.loading');
            status.hidden = false;
            box.textContent = '';
            return fetch('api/bible.php?' + q)
                .then((r) => r.json())
                .then((j) => {
                    if (!j.success) throw new Error(j.error || 'Could not load');
                    status.hidden = true;
                    j.verses.forEach((v) => {
                        const p = document.createElement('p');
                        p.className = 'verse';
                        const n = document.createElement('sup');
                        n.textContent = v.number;
                        p.append(n, ' ', document.createTextNode(String(v.verse).replace(/<[^>]*>/g, '')));
                        box.appendChild(p);
                    });
                })
                .catch((e) => { status.textContent = e.message + ' — ' + BT('reader.loadError'); })
                .finally(() => { actions.hidden = false; });
        };
        loadText();

        const bibleSel = document.getElementById('bibleSelect');
        if (bibleSel) {
            bibleSel.addEventListener('change', () => {
                reader.dataset.bible = bibleSel.value;
                // Keep prev/next links on the chosen translation
                document.querySelectorAll('#readerActions a[href^="read.php"]').forEach((a) => {
                    const u = new URL(a.getAttribute('href'), location.href);
                    u.searchParams.set('bible', bibleSel.value);
                    a.setAttribute('href', u.pathname.split('/').pop() + u.search);
                });
                saveSetting({ bible_lang: reader.dataset.lang, bible: bibleSel.value });
                loadText();
            });
        }

        const quizBtn = document.getElementById('quizBtn');
        const quizWrap = document.getElementById('quizWrap');
        quizBtn.addEventListener('click', () => {
            const open = quizWrap.hidden;
            if (open && !quizWrap.firstChild) {
                const f = document.createElement('iframe');
                f.src = quizBtn.dataset.url;
                f.width = '100%';
                f.height = '1100';
                f.style.cssText = 'border:0;max-width:600px;';
                f.loading = 'lazy';
                f.title = 'Bible Quiz';
                quizWrap.appendChild(f);
            }
            if (open && !quizBtn.dataset.counted) {
                quizBtn.dataset.counted = '1'; // count once per page view
                fetch('api/stats.php', { method: 'POST', body: new URLSearchParams({ event: 'quiz', csrf: reader.dataset.csrf }) }).catch(() => {});
            }
            quizWrap.hidden = !open;
            document.getElementById('readerFooter').classList.toggle('quiz-open', open);
            quizBtn.setAttribute('aria-expanded', String(open));
            quizBtn.querySelector('span').textContent = open ? BT('reader.hideQuiz') : BT('reader.quiz');
            if (open) quizWrap.scrollIntoView({ block: 'start', behavior: 'smooth' });
        });

        const mark = document.getElementById('markRead');
        const next = document.getElementById('nextBtn');
        mark.addEventListener('click', () => {
            if (mark.dataset.already) return;
            mark.disabled = true;
            const body = new URLSearchParams({
                id: reader.dataset.plan, keys: reader.dataset.book + ':' + reader.dataset.chapter, done: '1', csrf: reader.dataset.csrf,
            });
            fetch('api/progress.php', { method: 'POST', body })
                .then((r) => r.json())
                .then((j) => {
                    if (!j.ok) { const err = new Error(j.error); err.blocked = !!j.blocked; throw err; }
                    mark.dataset.already = '1';
                    mark.querySelector('span').textContent = BT('reader.read');
                    next.hidden = false;
                    next.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                })
                .catch((err) => alert(err && err.blocked ? err.message : BT('reader.saveError')))
                .finally(() => { mark.disabled = false; });
        });
    }

    // Leaderboard: edit display name
    const editBtn = document.getElementById('editNameBtn');
    if (editBtn) {
        const modal = document.getElementById('editNameModal');
        const input = document.getElementById('editNameInput');
        const err = document.getElementById('editNameError');
        const close = () => { modal.hidden = true; };
        editBtn.addEventListener('click', () => { err.hidden = true; modal.hidden = false; input.focus(); input.select(); });
        document.getElementById('editNameCancel').addEventListener('click', close);
        modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
        document.getElementById('editNameSave').addEventListener('click', () => {
            fetch('api/profile.php', { method: 'POST', body: new URLSearchParams({ display_name: input.value, csrf: input.dataset.csrf }) })
                .then((r) => r.json())
                .then((j) => { if (!j.ok) throw new Error(); location.reload(); })
                .catch(() => { err.textContent = BT('ach.saveError'); err.hidden = false; });
        });
    }

    // Admin tables: pencil switches a row into edit mode, Cancel switches back
    document.querySelectorAll('.editable-rows').forEach((t) => {
        t.addEventListener('click', (e) => {
            const row = e.target.closest('tr');
            if (!row) return;
            if (e.target.closest('.row-edit')) { row.classList.add('editing'); const i = row.querySelector('input.edit-only, .edit-only input'); if (i) i.focus(); }
            if (e.target.closest('.row-cancel')) { row.classList.remove('editing'); row.querySelectorAll('form.edit-only').forEach((f) => f.reset()); }
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach((f) => {
        f.addEventListener('submit', (e) => { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
    });
})();
