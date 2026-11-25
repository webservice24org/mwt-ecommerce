const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { runInNewContext } = require('node:vm');
const { test } = require('node:test');
const assert = require('node:assert/strict');

const source = readFileSync(resolve(__dirname, '../../resources/views/partials/appearance-bootstrap.blade.php'), 'utf8');
const script = source.match(/<script>([\s\S]*?)<\/script>/)[1];
const key = 'mwt.appearance.v1';

function boot({ saved, dark = false, blocked = false, legacy = false, noMedia = false, storage: persisted } = {}) {
    const storage = persisted ?? new Map(saved === undefined ? [] : [[key, saved]]);
    const attributes = new Map();
    const classes = new Set();
    const listeners = new Map();
    const mediaListeners = new Set();
    const media = { matches: dark };
    if (legacy) media.addListener = callback => mediaListeners.add(callback);
    else media.addEventListener = (name, callback) => { assert.equal(name, 'change'); mediaListeners.add(callback); };
    const root = {
        classList: { add: name => classes.add(name), remove: name => classes.delete(name) },
        setAttribute: (name, value) => attributes.set(name, value),
        style: {},
    };
    const localStorage = {
        getItem(name) { if (blocked) throw Error('blocked'); return storage.get(name) ?? null; },
        setItem(name, value) { if (blocked) throw Error('blocked'); storage.set(name, value); },
    };
    const window = {
        localStorage,
        addEventListener(name, callback) { if (!listeners.has(name)) listeners.set(name, []); listeners.get(name).push(callback); },
        dispatchEvent(event) { for (const listener of listeners.get(event.type) ?? []) listener(event); },
    };
    if (!noMedia) window.matchMedia = query => { assert.equal(query, '(prefers-color-scheme: dark)'); return media; };
    if (blocked) Object.defineProperty(window, 'localStorage', { get() { throw Error('blocked'); } });
    class Event { constructor(type) { this.type = type; } }
    runInNewContext(script, { document: { documentElement: root }, window, Event });
    return {
        get theme() { return attributes.get('data-mwt-theme'); },
        get preference() { return attributes.get('data-mwt-appearance'); },
        get dark() { return classes.has('dark'); },
        get colorScheme() { return root.style.colorScheme; },
        storage,
        localStorage,
        choose(value) { window.dispatchEvent({ type: 'mwt:appearance:set', detail: value }); },
        system(value) { media.matches = value; for (const listener of mediaListeners) listener({ matches: value }); },
        crossTab(value, area = localStorage, storageKey = key) { window.dispatchEvent({ type: 'storage', key: storageKey, newValue: value, storageArea: area }); },
    };
}

test('first visit follows system and updates root class and native controls', () => {
    const app = boot({ dark: true });
    assert.equal(app.preference, 'system'); assert.equal(app.theme, 'dark'); assert.equal(app.dark, true); assert.equal(app.colorScheme, 'dark');
    app.system(false); assert.equal(app.theme, 'light'); assert.equal(app.dark, false); assert.equal(app.colorScheme, 'light');
});
test('manual light overrides OS dark; manual dark overrides OS light', () => {
    const app = boot({ dark: true }); app.choose('light'); app.system(false); app.system(true);
    assert.equal(app.theme, 'light'); app.choose('dark'); app.system(false); assert.equal(app.theme, 'dark');
});
test('preference persists across fresh page loads', () => {
    const app = boot(); app.choose('dark'); const next = boot({ storage: app.storage }); assert.equal(next.preference, 'dark'); assert.equal(next.theme, 'dark');
});
test('switching back to system uses current OS preference', () => {
    const app = boot({ saved: 'dark' }); app.choose('system'); assert.equal(app.theme, 'light'); app.system(true); assert.equal(app.theme, 'dark');
});
test('legacy MediaQueryList.addListener remains functional', () => {
    const app = boot({ legacy: true }); app.system(true); assert.equal(app.theme, 'dark');
});
test('missing matchMedia safely falls back to light, manual dark still works', () => {
    const app = boot({ noMedia: true }); assert.equal(app.theme, 'light'); app.choose('dark'); assert.equal(app.theme, 'dark');
});
test('denied localStorage does not prevent appearance changes', () => {
    const app = boot({ blocked: true }); app.choose('dark'); assert.equal(app.theme, 'dark'); app.choose('light'); assert.equal(app.theme, 'light');
});
test('invalid stored preference and invalid events are ignored', () => {
    const app = boot({ saved: '<script>', dark: true }); assert.equal(app.preference, 'system'); app.choose('invalid'); assert.equal(app.preference, 'system'); assert.equal(app.theme, 'dark');
});
test('same-origin tab updates sync; unrelated or sessionStorage changes do not', () => {
    const app = boot(); app.crossTab('dark'); assert.equal(app.theme, 'dark'); app.crossTab('light', {}); assert.equal(app.theme, 'dark'); app.crossTab('light', app.localStorage, 'other-key'); assert.equal(app.theme, 'dark');
});
test('preference removal and storage clear restore system behavior', () => {
    const app = boot({ saved: 'dark' }); app.crossTab(null); assert.equal(app.preference, 'system'); assert.equal(app.theme, 'light'); app.crossTab('dark'); app.crossTab(null, app.localStorage, null); assert.equal(app.theme, 'light');
});
