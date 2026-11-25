{{-- Run before Vite CSS/JS: no flash of the opposite saved appearance. --}}
<script>
(function () {
    'use strict';
    var root = document.documentElement;
    var key = 'mwt.appearance.v1';
    var preference = 'system';
    var query = null;
    function valid(value) {
        return value === 'light' || value === 'dark' || value === 'system';
    }
    try {
        var saved = window.localStorage.getItem(key);
        if (valid(saved)) preference = saved;
    } catch (error) { /* Storage can be unavailable in private/restricted browsing. */ }
    try {
        if (typeof window.matchMedia === 'function') {
            query = window.matchMedia('(prefers-color-scheme: dark)');
        }
    } catch (error) { /* Unsupported system preference falls back to light. */ }
    function apply() {
        var dark = preference === 'dark' || (preference === 'system' && !!(query && query.matches));
        if (dark) root.classList.add('dark');
        else root.classList.remove('dark');
        root.setAttribute('data-mwt-appearance', preference);
        root.setAttribute('data-mwt-theme', dark ? 'dark' : 'light');
        root.style.colorScheme = dark ? 'dark' : 'light';
        window.dispatchEvent(new Event('mwt:appearance:changed'));
    }
    function systemChanged() {
        if (preference === 'system') apply();
    }
    if (query) {
        if (typeof query.addEventListener === 'function') query.addEventListener('change', systemChanged);
        else if (typeof query.addListener === 'function') query.addListener(systemChanged);
    }
    window.addEventListener('mwt:appearance:set', function (event) {
        if (!valid(event.detail)) return;
        preference = event.detail;
        try { window.localStorage.setItem(key, preference); } catch (error) { /* In-memory preference still works. */ }
        apply();
    });
    window.addEventListener('storage', function (event) {
        if (event.key !== key && event.key !== null) return;
        try { if (event.storageArea !== window.localStorage) return; } catch (error) { return; }
        preference = valid(event.newValue) ? event.newValue : 'system';
        apply();
    });
    apply();
}());
</script>
