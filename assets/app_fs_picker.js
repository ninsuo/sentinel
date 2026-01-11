function initFsPicker(root) {
    const SENTINEL_FS = {
        listUrl: root.dataset.listUrl,
        searchUrl: root.dataset.searchUrl,
        previewUrl: root.dataset.previewUrl,
    };

    function qs(id) {
        return document.getElementById(id);
    }

    const elDir = qs('fs-dir');
    const elList = qs('fs-list');
    const elPreview = qs('fs-preview');
    const elSearch = qs('fs-search');

    const elSelected = qs('selected-files');
    const elSelectedJson = qs('selected-files-json');
    const btnLoad = qs('fs-load');
    const btnSearch = qs('fs-search-btn');
    const btnClear = qs('clear-selection');

    const required = {elDir, elList, elPreview, elSelected, elSelectedJson};
    for (const [k, v] of Object.entries(required)) {
        if (!v) {
            console.error(`[Sentinel FS] Missing ${k}. Picker cannot start.`);
            return;
        }
    }

    if (!SENTINEL_FS.listUrl || !SENTINEL_FS.searchUrl || !SENTINEL_FS.previewUrl) {
        console.error('[Sentinel FS] FS config missing on #fs-picker dataset.', SENTINEL_FS);
        elList.innerHTML = `<div class="text-danger small p-2">FS config missing (dataset).</div>`;
        return;
    }

    const state = {
        dir: elDir.value || '',
        selected: [],
    };

    // --- keep your helper funcs (normalizeDir, parentDir, escapeHtml, apiGet, etc.) ---
    // (paste your existing implementations here, unchanged, but using SENTINEL_FS and state)

    function normalizeDir(dir) {
        dir = (dir || '').trim().replace(/\\/g, '/').replace(/^\/+/, '').replace(/\/+$/, '');
        return dir;
    }

    function parentDir(dir) {
        dir = normalizeDir(dir);
        if (!dir) return '';
        const parts = dir.split('/');
        parts.pop();
        return parts.join('/');
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[c]));
    }

    function renderSelected() {
        elSelected.innerHTML = '';
        if (state.selected.length === 0) {
            elSelected.innerHTML = `<div class="text-secondary small p-2">No files selected.</div>`;
            return;
        }

        for (const item of state.selected) {
            const row = document.createElement('div');
            row.className = 'list-group-item d-flex justify-content-between align-items-center';
            row.innerHTML = `
        <span class="small text-break">${escapeHtml(item.path)}</span>
        <button type="button" class="btn btn-sm btn-outline-danger">×</button>
      `;
            row.querySelector('button').addEventListener('click', () => removeSelected(item.path));
            elSelected.appendChild(row);
        }
    }

    function setSelected(next) {
        state.selected = next;
        elSelectedJson.value = JSON.stringify(state.selected);
        renderSelected();
    }

    function addSelected(path) {
        if (state.selected.some(x => x.path === path)) return;
        setSelected([...state.selected, {path}]);
    }

    function removeSelected(path) {
        setSelected(state.selected.filter(x => x.path !== path));
    }

    async function apiGet(url, params) {
        const u = new URL(url, window.location.origin);
        for (const [k, v] of Object.entries(params || {})) u.searchParams.set(k, v);

        const res = await fetch(u.toString(), {headers: {'Accept': 'application/json'}});

        let data;
        try {
            data = await res.json();
        } catch {
            throw new Error(`Invalid JSON response (HTTP ${res.status}).`);
        }

        if (!res.ok) throw new Error(data?.error || `HTTP ${res.status}`);
        return data;
    }

    function renderEntries(entries, currentDir) {
        elList.innerHTML = '';

        const cur = normalizeDir(currentDir || '');
        const finalEntries = Array.isArray(entries) ? [...entries] : [];

        if (cur) {
            finalEntries.unshift({path: parentDir(cur), name: '..', isDir: true, size: null, mtime: null});
        }

        if (finalEntries.length === 0) {
            elList.innerHTML = `<div class="text-secondary small p-2">Empty.</div>`;
            return;
        }

        for (const e of finalEntries) {
            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'list-group-item list-group-item-action';

            const icon = e.isDir ? '📁' : '📄';
            const isSelected = !e.isDir && state.selected.some(x => x.path === e.path);
            const check = isSelected ? `<span class="ms-2 small">✓</span>` : '';

            row.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <span class="me-2">${icon}</span><span class="small text-break">${escapeHtml(e.name)}</span>
          </div>
          ${check}
        </div>
        <div class="small text-secondary">${escapeHtml(e.path)}</div>
      `;

            row.addEventListener('click', async () => {
                if (e.isDir) return loadDir(e.path);
                return previewFile(e.path);
            });

            row.addEventListener('dblclick', () => {
                if (!e.isDir) addSelected(e.path);
            });

            elList.appendChild(row);
        }
    }

    async function loadDir(dir) {
        state.dir = normalizeDir(dir || '');
        elDir.value = state.dir;
        elPreview.textContent = '';
        elList.innerHTML = `<div class="text-secondary small p-2">Loading…</div>`;

        const data = await apiGet(SENTINEL_FS.listUrl, {dir: state.dir});
        renderEntries(data.entries, state.dir);
    }

    async function search(q) {
        elPreview.textContent = '';
        elList.innerHTML = `<div class="text-secondary small p-2">Searching…</div>`;

        const data = await apiGet(SENTINEL_FS.searchUrl, {q: q || '', dir: state.dir});
        renderEntries(data.results, state.dir);
    }

    async function previewFile(path) {
        elPreview.textContent = 'Loading preview…';
        try {
            const data = await apiGet(SENTINEL_FS.previewUrl, {path});
            elPreview.textContent = data.content || '';
        } catch (e) {
            elPreview.textContent = `Preview error: ${e.message}`;
        }
    }

    function initSelectedFromHiddenInput() {
        try {
            const raw = elSelectedJson.value || '[]';
            const parsed = JSON.parse(raw);

            if (Array.isArray(parsed)) {
                const normalized = parsed
                    .filter(x => x && typeof x === 'object' && typeof x.path === 'string')
                    .map(x => ({path: x.path}));

                setSelected(normalized);
                return;
            }
        } catch {
        }
        setSelected([]);
    }

    // Bind events
    btnLoad?.addEventListener('click', () => loadDir(elDir.value));
    btnSearch?.addEventListener('click', () => search(elSearch?.value || ''));
    elSearch?.addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter') {
            ev.preventDefault();
            search(elSearch.value);
        }
    });
    btnClear?.addEventListener('click', () => setSelected([]));

    // Boot this instance
    initSelectedFromHiddenInput();
    loadDir(elDir.value || '').catch(err => {
        console.error('[Sentinel FS] initial loadDir failed', err);
        elList.innerHTML = `<div class="text-danger small p-2">Failed to load: ${escapeHtml(err.message)}</div>`;
    });
}

function bootFsPicker() {
    const root = document.getElementById('fs-picker');
    if (!root) return;

    // Prevent double init on Turbo renders
    if (root.dataset.initialized === '1') return;
    root.dataset.initialized = '1';

    initFsPicker(root);
}

// Turbo (Symfony UX Turbo)
document.addEventListener('turbo:load', bootFsPicker);
document.addEventListener('turbo:render', bootFsPicker);

// Classic navigation
document.addEventListener('DOMContentLoaded', bootFsPicker);

// If the script loads after the page is already ready
bootFsPicker();
