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

const state = {
    dir: elDir.value || '',
    selected: [], // [{path}]
};

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

function escapeHtml(s) {
    return s.replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
}

async function apiGet(url, params) {
    const u = new URL(url, window.location.origin);
    for (const [k, v] of Object.entries(params || {})) {
        u.searchParams.set(k, v);
    }
    const res = await fetch(u.toString(), {headers: {'Accept': 'application/json'}});
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || 'Request failed');
    return data;
}

function renderEntries(entries, currentDir) {
    elList.innerHTML = '';

    const cur = normalizeDir(currentDir || '');

    const finalEntries = Array.isArray(entries) ? [...entries] : [];

    // Inject ".." if not at root
    if (cur) {
        finalEntries.unshift({
            path: parentDir(cur),
            name: '..',
            isDir: true,
            size: null,
            mtime: null
        });
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
        row.innerHTML = `<span class="me-2">${icon}</span><span class="small text-break">${escapeHtml(e.name)}</span>
                     <div class="small text-secondary">${escapeHtml(e.path)}</div>`;

        row.addEventListener('click', async () => {
            if (e.isDir) {
                await loadDir(e.path);
                return;
            }
            await previewFile(e.path);
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

    const data = await apiGet(window.SENTINEL_FS.listUrl, {dir: state.dir});
    renderEntries(data.entries, state.dir);
}

async function search(q) {
    elPreview.textContent = '';
    elList.innerHTML = `<div class="text-secondary small p-2">Searching…</div>`;

    const data = await apiGet(window.SENTINEL_FS.searchUrl, {q: q || '', dir: state.dir});
    renderEntries(data.results);
}

async function previewFile(path) {
    elPreview.textContent = 'Loading preview…';
    try {
        const data = await apiGet(window.SENTINEL_FS.previewUrl, {path});
        elPreview.textContent = data.content || '';
    } catch (e) {
        elPreview.textContent = `Preview error: ${e.message}`;
    }
}

btnLoad?.addEventListener('click', async () => {
    await loadDir(elDir.value);
});

btnSearch?.addEventListener('click', async () => {
    await search(elSearch.value);
});

elSearch?.addEventListener('keydown', async (ev) => {
    if (ev.key === 'Enter') {
        ev.preventDefault();
        await search(elSearch.value);
    }
});

btnClear?.addEventListener('click', () => {
    setSelected([]);
});

(async function init() {
    renderSelected();
    await loadDir(elDir.value || '');
})();
