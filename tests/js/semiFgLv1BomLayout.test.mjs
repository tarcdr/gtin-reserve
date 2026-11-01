import { test } from 'node:test';
import assert from 'node:assert/strict';
import { build } from 'esbuild';
import { createRequire } from 'node:module';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

const root = fileURLToPath(new URL('../../', import.meta.url));
const require = createRequire(import.meta.url);
const result = await build({
    stdin: {
        contents: "export { default as Page } from './resources/js/Pages/PackMaterial/SemiFgLv1Bom.jsx'; export { default as Form } from './resources/js/Components/BomMaterialForm.jsx'; export { default as RawPage } from './resources/js/Pages/ExistingComponents/RawNew.jsx'; export { default as ExistingPage } from './resources/js/Pages/ExistingComponents/Select.jsx';",
        resolveDir: root,
    },
    bundle: true, write: false, platform: 'node', format: 'cjs', packages: 'external', jsx: 'automatic',
    define: { 'import.meta.env.DEV': 'false' },
    plugins: [{
        name: 'render-without-browser-or-network',
        setup(builder) {
            builder.onResolve({ filter: /^react(\/.*)?$/ }, args => ({ path: args.path, external: true }));
            builder.onResolve({ filter: /^@inertiajs\/react$/ }, () => ({ path: 'inertia', namespace: 'fixture' }));
            builder.onResolve({ filter: /^@\/Layouts\/AuthenticatedLayout$/ }, () => ({ path: 'layout', namespace: 'fixture' }));
            builder.onResolve({ filter: /^@\/Components\/Modal$/ }, () => ({ path: 'modal', namespace: 'fixture' }));
            builder.onResolve({ filter: /^@\// }, args => builder.resolve(path.join(root, 'resources/js', args.path.slice(2)), { kind: args.kind, resolveDir: root }));
            builder.onLoad({ filter: /.*/, namespace: 'fixture' }, args => ({
                loader: 'jsx',
                contents: args.path === 'inertia' ? `
                    import React from 'react';
                    export const Head = () => null;
                    export const router = { get() {}, delete() {} };
                    export const usePage = () => ({ props: { auth: { user: { user_login: 'tester' } }, errors: {} } });
                    export const useForm = initial => {
                        const [data] = React.useState(initial);
                        return { data, errors: {}, processing: false, setData() {}, setError() {}, clearErrors() {}, patch() {} };
                    };
                ` : args.path === 'layout' ? `import React from 'react'; export default function Layout({ header, children }) { return <main>{header}{children}</main>; }` : 'export default function Modal() { return null; }',
            }));
        },
    }],
});
const module = { exports: {} };
new Function('require', 'module', 'exports', result.outputFiles[0].text)(require, module, module.exports);
const { Page, Form, RawPage, ExistingPage } = module.exports;
const input = {
    fgMaterialId: 'FG1', fgBomId: 'BFG1', semiFgLv2BomId: 'BL2', semiFgLvBomId: 'BL1', levelMaterialId: 'L1',
    componentId: '400200004', mattype: '4', subMattype: '0', productCat: '40', productSubCat: '4002',
    searchDesc: 'Component', fullDescEn: 'English', fullDescTh: 'Thai', uom: 'DAY', statusRow: 'INS', canEdit: true, canDelete: true,
};
const render = (mode, overrides = {}) => renderToStaticMarkup(React.createElement(Page, {
    auth: { user: { user_login: 'tester' } }, InputData: { ...input, actionMode: mode, ...overrides },
    subMattypes: [{ code: '0' }], uoms: [{ code: 'DAY' }],
}));
const field = (html, id) => {
    const tag = html.match(new RegExp(`<(?:input|select)[^>]*id="${id}"[^>]*>`));
    assert.ok(tag, `missing ${id}`);
    return tag[0];
};

test('Detail renders read-only fields, Raw Material in the form, then footer actions', () => {
    const html = render('view');
    assert.match(html, /SEMI FG LV1 BOM - Detail/);
    const raw = html.indexOf('aria-label="Raw Material Components"');
    const footer = html.indexOf('Back to Semi FG Lv.1 Detail');
    assert.ok(html.indexOf('id="componentStatus"') < raw);
    assert.ok(raw < footer && footer < html.indexOf('</form>'));
    assert.ok(html.indexOf('From existing', raw) < html.indexOf('>New</h4>', raw));
    assert.match(field(html, 'componentStatus'), /value="INS"/);
    for (const id of ['searchDesc', 'fullDescEn', 'fullDescTh', 'uom', 'mattype', 'productSubCat', 'componentId']) {
        assert.match(field(html, id), /disabled/);
    }
    assert.match(html.slice(footer), />EDIT<\/button>/);
    assert.match(html.slice(footer), />DELETE<\/button>/);
});

test('Edit contains only main fields, leaves category locked and shows Update', () => {
    const html = render('edit');
    assert.doesNotMatch(html, /Raw Material Components|id="componentStatus"/);
    for (const id of ['searchDesc', 'fullDescEn', 'fullDescTh', 'uom']) assert.doesNotMatch(field(html, id), /disabled/);
    for (const id of ['mattype', 'subMattype', 'productSubCat', 'componentId']) assert.match(field(html, id), /disabled/);
    assert.match(html, /Back to BOM Detail/);
    assert.match(html, />Update<\/button>/);
});

test('Create and Delete do not mount Raw Material management', () => {
    assert.doesNotMatch(render('create'), /Raw Material Components/);
    const html = render('delete');
    assert.doesNotMatch(html, /Raw Material Components/);
    assert.match(field(html, 'searchDesc'), /disabled/);
});

test('completed or foreign-owned Detail keeps Edit and Delete disabled', () => {
    const html = render('view', { canEdit: false, canDelete: false });
    assert.match(html, /<button[^>]*disabled[^>]*>EDIT<\/button>/);
    assert.match(html, /<button[^>]*disabled[^>]*>DELETE<\/button>/);
});

test('shared form does not add LV1 sections or status to other BOM pages', () => {
    const html = renderToStaticMarkup(React.createElement(Form, { auth: { user: {} }, data: { actionMode: 'view' }, isViewMode: true }));
    assert.doesNotMatch(html, /Raw Material Components|id="componentStatus"|>EDIT<\/button>/);
});

test('Raw Material child Detail exposes Edit inside, with read-only fields', () => {
    const html = renderToStaticMarkup(React.createElement(RawPage, {
        auth: { user: { user_login: 'tester' } }, InputData: { ...input, actionMode: 'view', masterMaterialId: 'MASTER4', semiFgLv1BomId: 'BL1' },
        subMattypes: [{ code: '0' }], uoms: [{ code: 'DAY' }], backUrl: '/parent-detail',
    }));
    assert.match(html, /Raw Material Component - Detail/);
    assert.match(html, />EDIT<\/button>/);
    assert.doesNotMatch(html, />DELETE<\/button>/);
    assert.match(field(html, 'searchDesc'), /disabled/);
    assert.match(field(html, 'uom'), /disabled/);
});

test('Raw Material deletion remains a separate read-only process', () => {
    const html = renderToStaticMarkup(React.createElement(RawPage, {
        auth: { user: { user_login: 'tester' } }, InputData: { ...input, actionMode: 'delete', masterMaterialId: 'MASTER4', semiFgLv1BomId: 'BL1' },
        subMattypes: [{ code: '0' }], uoms: [{ code: 'DAY' }], backUrl: '/parent-detail',
    }));
    assert.match(html, /Raw Material Component - delete/);
    assert.match(html, />Delete<\/button>/);
    assert.doesNotMatch(html, />EDIT<\/button>/);
    assert.match(field(html, 'searchDesc'), /disabled/);
});

test('Raw Material Existing View has no Delete; its separate delete process does', () => {
    const renderExisting = (mode, scope = 'raw4') => renderToStaticMarkup(React.createElement(ExistingPage, {
        auth: { user: { user_login: 'tester' } }, scope, mode,
        context: { ...input, canWrite: true, masterMaterialId: 'MASTER4', semiFgLv1BomId: 'BL1' },
        selected: { code: 'RAW4', no: '1', label: 'Existing raw', status: 'INS', userCreate: 'tester' }, backUrl: '/parent-detail',
    }));
    const view = renderExisting('view');
    assert.doesNotMatch(view, />DELETE<\/button>/);
    assert.match(field(view, 'existingComponentId'), /disabled/);
    const remove = renderExisting('delete');
    assert.match(remove, /Raw Material - Delete Existing Component/);
    assert.match(remove, />DELETE<\/button>/);
    assert.match(field(remove, 'existingComponentId'), /disabled/);
    assert.match(renderExisting('view', 'fg'), />DELETE<\/button>/);
});
