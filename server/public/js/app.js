// Article Writer - small page helpers. No framework.
(function () {
    'use strict';
    const $ = (s, el) => (el || document).querySelector(s);
    const $$ = (s, el) => Array.from((el || document).querySelectorAll(s));
    const csrf = () => $('meta[name="csrf-token"]').content;

    // Confirm before submitting forms with data-confirm.
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
            e.preventDefault();
            return;
        }
        // Spin the icon of a busy button.
        const btn = form.querySelector('[data-busy]');
        if (btn) btn.classList.add('busy');
    });

    // Auto-submit filter selects.
    $$('[data-autosubmit]').forEach((el) => el.addEventListener('change', () => el.form.submit()));

    // Copy and show/hide secret fields.
    document.addEventListener('click', (e) => {
        const copy = e.target.closest('[data-copy]');
        if (copy) {
            const input = $(copy.dataset.copy);
            navigator.clipboard.writeText(input.value).then(() => {
                copy.classList.add('busy');
                setTimeout(() => copy.classList.remove('busy'), 600);
            });
        }
        const toggle = e.target.closest('[data-toggle-secret]');
        if (toggle) {
            const input = $(toggle.dataset.toggleSecret);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    });

    // Line counters for title textareas.
    const countLines = (ta, out) => {
        const update = () => { out.textContent = ta.value.split(/\r?\n/).filter((l) => l.trim() !== '').length; };
        ta.addEventListener('input', update);
        update();
    };
    $$('[data-count-lines]').forEach((ta) => countLines(ta, $(ta.dataset.countLines)));

    // Admin: provider driver -> default base URL.
    const driver = $('#driver[data-base-urls]');
    if (driver) {
        const urls = JSON.parse(driver.dataset.baseUrls);
        driver.addEventListener('change', () => {
            const base = $('#base_url');
            if (!base.value || Object.values(urls).includes(base.value)) base.value = urls[driver.value] || '';
        });
    }

    // Admin: wallet modal opened from the customer list.
    const walletModal = $('#walletModal');
    if (walletModal) {
        walletModal.addEventListener('show.bs.modal', (e) => {
            const btn = e.relatedTarget;
            if (btn && btn.dataset.walletUrl) {
                $('#walletForm').action = btn.dataset.walletUrl;
                $('[data-wallet-title]', walletModal).textContent = btn.dataset.walletName;
            }
        });
    }

    // ---------- title list table ----------
    const tbody = $('#titleRows');
    if (tbody) {
        const tableForm = $('#titleTableForm');
        const filter = $('#rowFilter');
        const totals = $('#rowTotals');
        const fmt = (n) => (Math.round(n * 10) / 10).toLocaleString(document.documentElement.lang);
        const weightOf = (tr) => Math.max(0, parseFloat($('[data-weight]', tr).value) || 0);

        const addRow = (row, focus) => {
            const tr = document.createElement('tr');
            if (row.id) tr.dataset.id = row.id;
            tr.innerHTML = `<td class="text-center text-muted small" data-n></td>
                <td><input class="form-control form-control-sm" data-title maxlength="500" dir="auto"></td>
                <td class="text-center"><input class="form-control form-control-sm aw-num mx-auto" type="number" data-weight min="0" max="1000" step="any"></td>
                <td class="text-center small" data-share></td>
                <td class="text-center small text-muted">${row.used || 0}</td>
                <td class="text-end"><button type="button" class="btn icon sm tone-red" data-del title="${tbody.dataset.delete}" aria-label="${tbody.dataset.delete}">
                    <svg class="ico" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg></button></td>`;
            const t = $('[data-title]', tr);
            t.value = row.title || '';
            t.placeholder = tbody.dataset.titlePh;
            $('[data-weight]', tr).value = row.weight ?? 1;
            tbody.appendChild(tr);
            if (focus) t.focus();
        };

        const refresh = () => {
            const rows = $$('tr', tbody);
            const sum = rows.reduce((s, tr) => s + weightOf(tr), 0);
            let active = 0;
            rows.forEach((tr, i) => {
                const w = weightOf(tr);
                if (w > 0) active++;
                $('[data-n]', tr).textContent = i + 1;
                const share = $('[data-share]', tr);
                share.textContent = w > 0 && sum > 0 ? fmt((w / sum) * 100) + '%' : tbody.dataset.paused;
                share.classList.toggle('text-muted', w === 0);
                tr.classList.toggle('aw-row-paused', w === 0);
            });
            $('#rowCount').textContent = rows.length;
            totals.textContent = totals.dataset.template.replace(':n', rows.length).replace(':active', active).replace(':sum', fmt(sum));
        };

        const applyFilter = () => {
            const q = filter.value.trim().toLowerCase();
            $$('tr', tbody).forEach((tr) => { tr.hidden = q !== '' && !$('[data-title]', tr).value.toLowerCase().includes(q); });
        };

        const existing = () => new Set($$('[data-title]', tbody).map((i) => i.value.trim().toLowerCase()));

        const start = JSON.parse(tbody.dataset.rows || '[]');
        start.forEach((r) => addRow(r));
        if (start.length === 0) addRow({ weight: 1 });
        refresh();

        tbody.addEventListener('input', refresh);
        tbody.addEventListener('click', (e) => {
            const del = e.target.closest('[data-del]');
            if (del) { del.closest('tr').remove(); refresh(); }
        });
        // Enter in the last title box adds a new row (handy when typing many titles).
        tbody.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.target.matches('[data-title]')) {
                e.preventDefault();
                const tr = e.target.closest('tr');
                if (tr === tbody.lastElementChild) { addRow({ weight: 1 }, true); refresh(); } else { $('[data-title]', tr.nextElementSibling).focus(); }
            }
        });
        filter.addEventListener('input', applyFilter);
        $('#addRow').addEventListener('click', () => { filter.value = ''; applyFilter(); addRow({ weight: 1 }, true); refresh(); });

        $('#pasteForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const seen = existing();
            const w = Math.max(0, parseFloat($('#pasteWeight').value) || 0);
            // Drop the empty starter row before adding pasted rows.
            $$('tr', tbody).forEach((tr) => { if (!tr.dataset.id && $('[data-title]', tr).value.trim() === '') tr.remove(); });
            $('#pasteText').value.split(/\r?\n/).map((l) => l.trim()).filter(Boolean).forEach((t) => {
                if (!seen.has(t.toLowerCase())) { seen.add(t.toLowerCase()); addRow({ title: t, weight: w }); }
            });
            $('#pasteText').value = '';
            refresh();
            bootstrap.Modal.getInstance($('#pasteModal')).hide();
        });

        $('#weightAllForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const w = $('#allWeight').value;
            $$('tr', tbody).filter((tr) => !tr.hidden).forEach((tr) => { $('[data-weight]', tr).value = w; });
            refresh();
            bootstrap.Modal.getInstance($('#weightAllModal')).hide();
        });

        tableForm.addEventListener('submit', () => {
            $('#rowsJson').value = JSON.stringify($$('tr', tbody)
                .map((tr) => ({ id: tr.dataset.id ? parseInt(tr.dataset.id, 10) : null, title: $('[data-title]', tr).value.trim(), weight: weightOf(tr) }))
                .filter((r) => r.title !== ''));
        });
    }

    // ---------- service form ----------
    const form = $('#serviceForm');
    if (!form) return;

    // Images: one size select per image.
    const rows = $('#imageRows');
    const count = $('#image_count');
    const sizes = JSON.parse(rows.dataset.sizes);
    let values = JSON.parse(rows.dataset.values);
    const renderImages = () => {
        const n = parseInt(count.value, 10) || 0;
        values = $$('select', rows).map((s) => parseInt(s.value, 10)).concat(values.slice($$('select', rows).length));
        rows.innerHTML = '';
        for (let i = 0; i < n; i++) {
            const wrap = document.createElement('div');
            wrap.className = 'input-group input-group-sm';
            const label = document.createElement('span');
            label.className = 'input-group-text';
            label.textContent = i === 0 ? rows.dataset.cover : rows.dataset.label.replace(':n', i + 1);
            const sel = document.createElement('select');
            sel.className = 'form-select';
            sel.name = 'images[]';
            let group = null;
            let parent = sel;
            sizes.forEach((s) => {
                if (s.group !== group) {
                    group = s.group;
                    parent = document.createElement('optgroup');
                    parent.label = group;
                    sel.appendChild(parent);
                }
                const o = document.createElement('option');
                o.value = s.id;
                o.textContent = s.label;
                parent.appendChild(o);
            });
            const v = values[i] || values[i - 1] || (sizes[0] && sizes[0].id);
            if (v) sel.value = v;
            wrap.append(label, sel);
            rows.appendChild(wrap);
        }
        $$('.aw-if-images', form).forEach((el) => { el.hidden = n === 0; });
    };
    count.addEventListener('change', renderImages);
    renderImages();

    // Times: add / remove.
    const times = $('#timeRows');
    $('#addTime').addEventListener('click', () => {
        const all = $$('.aw-time-row', times);
        const row = all[all.length - 1].cloneNode(true);
        $('input', row).value = '';
        times.insertBefore(row, $('#addTime'));
        $('input', row).focus();
    });
    times.addEventListener('click', (e) => {
        const del = e.target.closest('[data-remove-time]');
        if (del && $$('.aw-time-row', times).length > 1) del.closest('.aw-time-row').remove();
    });

    // Repeat type: show the right day picker; disable the hidden one so it is not sent.
    const showDays = () => {
        const type = $('input[name="schedule_type"]:checked', form).value;
        $$('.aw-days', form).forEach((box) => {
            const on = box.dataset.for === type;
            box.hidden = !on;
            $$('input', box).forEach((i) => { i.disabled = !on; });
        });
    };
    $$('input[name="schedule_type"]', form).forEach((r) => r.addEventListener('change', showDays));
    showDays();

    // "New title list" modal: save with fetch, then add and select it.
    const tlForm = $('#titleListForm');
    if (tlForm) {
        countLines($('#tl_titles'), $('[data-count]', tlForm));
        tlForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const err = $('[data-error]', tlForm);
            err.classList.add('d-none');
            const res = await fetch(tlForm.dataset.url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                body: new FormData(tlForm),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                err.textContent = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Error');
                err.classList.remove('d-none');
                return;
            }
            const sel = $('#title_list_id');
            const opt = new Option(`${data.name} (${data.count})`, data.id, true, true);
            sel.appendChild(opt);
            sel.value = data.id;
            tlForm.reset();
            bootstrap.Modal.getInstance($('#titleListModal')).hide();
        });
    }
})();
