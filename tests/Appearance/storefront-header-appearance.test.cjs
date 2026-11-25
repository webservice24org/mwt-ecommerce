const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { test } = require('node:test');
const assert = require('node:assert/strict');
const source = path => readFileSync(resolve(__dirname, '../../', path), 'utf8');

test('storefront appearance control lives in the existing header action group', () => {
    const header = source('resources/js/Components/Frontend/HeaderBuilder/MegaMenuHeader.tsx');
    const actions = header.slice(header.indexOf('function HeaderActions('), header.indexOf('function CartDrawer('));
    assert.match(actions, /config\.actions\.wishlist\.enabled/);
    assert.match(actions, /config\.actions\.cart\.enabled/);
    assert.match(actions, /config\.actions\.account\.enabled/);
    assert.equal((actions.match(/<AppearanceSwitcher\b/g) || []).length, 1);
    assert.ok(actions.indexOf('<AppearanceSwitcher') > actions.indexOf('<AccountMenu'));
});
test('storefront layout has no separate appearance bar or duplicate switcher', () => {
    const layout = source('resources/js/Layouts/Frontend/FrontendLayout.tsx');
    assert.doesNotMatch(layout, /mwt-storefront-appearance|AppearanceSwitcher/);
    assert.match(layout, /<StorefrontHeader/);
});
test('compact control retains native selection and accessible full-size target', () => {
    const control = source('resources/js/Components/Appearance/AppearanceSwitcher.tsx');
    const css = source('resources/css/appearance.css');
    assert.match(control, /<select aria-label="Color appearance"/);
    for (const mode of ['light', 'dark', 'system']) assert.ok(control.includes(`value="${mode}"`));
    assert.match(css, /\.mwt-header-appearance \.mwt-appearance-icon\s*\{\s*display: block;/);
    assert.match(css, /\.mwt-appearance-control\.mwt-header-appearance\s*\{[^}]*width: 2\.75rem;[^}]*height: 2\.75rem;/);
    assert.match(css, /\.mwt-header-appearance select\s*\{[^}]*width: 100%;[^}]*height: 100%;/);
});
