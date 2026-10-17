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
