const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const ts = require('typescript')
const root = path.resolve(__dirname, '../..')
const source = file => fs.readFileSync(path.join(root, file), 'utf8')
const compiled = ts.transpileModule(source('resources/js/lib/adminBranding.ts'), { compilerOptions: { module: ts.ModuleKind.CommonJS } }).outputText
const exportsObject = {}
new Function('exports', compiled)(exportsObject)
const { normalizeAdminBranding, adminLoginBackground } = exportsObject
const image = key => `/storage/branding/admin/${key}/${'a'.repeat(40)}.png`

test('defaults preserve the existing adaptive background', () => {
    assert.deepEqual(normalizeAdminBranding([]), {})
    assert.deepEqual(adminLoginBackground({ background_mode: 'default', background_color: '#123456' }), {})
    assert.deepEqual(adminLoginBackground({ background_mode: 'image' }), {})
})
test('solid color and image modes are independent and safe', () => {
    assert.deepEqual(adminLoginBackground({ background_mode: 'color', background_color: '#ABCDEF', background_image: image('background_image') }), { backgroundColor: '#abcdef' })
    assert.equal(adminLoginBackground({ background_mode: 'image', background_image: image('background_image') }).backgroundImage, `url("${image('background_image')}")`)
    assert.equal(adminLoginBackground({ background_mode: 'image', background_image: image('background_image') }).backgroundSize, 'cover')
    assert.deepEqual(normalizeAdminBranding({ login_logo: image('login_logo'), sidebar_logo: image('sidebar_logo') }), { login_logo: image('login_logo'), sidebar_logo: image('sidebar_logo') })
})
test('unsafe paths and CSS values never reach rendering', () => {
    assert.deepEqual(normalizeAdminBranding({ login_logo: image('sidebar_logo'), sidebar_logo: 'https://external.invalid/logo.png', background_color: 'url(x)', background_image: '/storage/branding/admin/background_image/../a.png', custom_css: 'body{}' }), {})
    assert.deepEqual(normalizeAdminBranding({ login_logo: image('login_logo') + '\n' }), {})
})
test('branding is wired to the existing login, sidebar and fixed admin credit', () => {
    const login = source('resources/js/Pages/Admin/Auth/Login.tsx')
    assert.match(login, /style=\{adminLoginBackground\(branding\)\}/)
    assert.ok(login.indexOf('url={branding.login_logo}') < login.indexOf('>Admin Login</h1>'))
    assert.match(login, /shadow-sm/)
    assert.match(login, /admin\.login\.store/)
    assert.match(source('resources/js/Components/Admin/AdminSidebar.tsx'), /url=\{branding\.sidebar_logo\}/)
    const layout = source('resources/js/Layouts/Admin/AdminLayout.tsx')
    assert.match(layout, /Website Developed by/)
    assert.match(layout, /href="https:\/\/webservicebd\.org"/)
    assert.match(layout, /MicroWeb Technology/)
    assert.match(source('resources/js/Pages/Admin/WebsiteSettings/ThemeSettings/Edit.tsx'), /Admin Branding/)
})
