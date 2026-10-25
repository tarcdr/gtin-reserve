import { cellSpan, resultState } from './overviewTable';
import { useState } from 'react';
import Modal from '@/Components/Modal';
import { Dialog } from '@headlessui/react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

const display = (value) => value === null || value === undefined || value === '' ? '—' : String(value);
const buttonClass = 'inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-md border px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-[#4C5E86] focus:ring-offset-2';
const secondaryClass = `${buttonClass} border-gray-300 bg-white text-[#4C5E86] hover:bg-gray-50`;
const disabledClass = `${buttonClass} cursor-not-allowed border-gray-200 bg-gray-50 text-gray-400`;
const exportClass = `${buttonClass} border-transparent bg-[#5E9E6B] text-white hover:bg-[#518B5D] focus:ring-[#5E9E6B]`;
// Enable in the next phase when Overview creation actions are ready.
const showCreationActions = false;

function Result({ value, raw, checkbox }) {
    const checked = resultState(raw);
    if (checkbox && checked !== null) return <input type="checkbox" checked={checked} disabled aria-label={checked ? 'Yes' : 'No'} className="rounded border-gray-300 text-[#5E9E6B] disabled:opacity-100" />;
    const color = value === '1' ? 'text-green-800' : value === '0' ? 'text-[#964D55]' : 'text-gray-400';
    return <span className={`font-bold tabular-nums ${color}`}>{display(value)}</span>;
}

function Section({ section, number, products, plus }) {
    const idLabel = section.label === 'FG' ? 'FG ID' : section.label;
    const columns = [...section.parentLabels, idLabel, 'COMPONENTS', 'STATUS', 'ACTION'];
    return (
        <section className="overflow-hidden rounded-lg border border-gray-200" aria-label={section.label}>
            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-[#f3f7f4] px-4 py-3 sm:px-5">
                <h3 className="text-base font-bold text-green-900"><span className="mr-3 text-[#5E9E6B]">{number}.</span>{section.label}</h3>
                <span className="text-xs font-normal text-gray-500">{section.rows.length} รายการ</span>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full border-collapse text-left text-sm font-medium text-gray-800">
                    <caption className="sr-only">{section.label} components and action results</caption>
                    <thead className="bg-gray-50 text-xs font-semibold text-[#4C5E86]">
                        <tr>
                            {columns.map((column, index) => <th key={index} scope="col" rowSpan={2} className="border-b border-r border-gray-200 px-4 py-3 whitespace-nowrap">{column}{products && index === section.parentLabels.length && plus(number * 2 - 1)}{products && index === section.parentLabels.length + 1 && plus(number * 2)}</th>)}
                            <th scope="colgroup" colSpan={2} className="border-b border-gray-200 px-4 py-2 text-center">RESULT</th>
                        </tr>
                        <tr>
                            <th scope="col" className="min-w-20 border-b border-r border-gray-200 px-3 py-2 text-center">BUILT</th>
                            <th scope="col" className="min-w-24 border-b border-gray-200 px-3 py-2 text-center">COMPLETE</th>
                        </tr>
                    </thead>
                    <tbody>
                        {section.rows.length ? section.rows.map((row, index) => (
                            <tr key={index} className="border-b border-gray-100 last:border-b-0 even:bg-gray-50/60 hover:bg-green-50/50">
                                {[...row.parents, row.id, row.component].map((value, cell) => {
                                    const span = cell <= row.parents.length ? cellSpan(section.rows, index, cell) : 1;
                                    return span ? <td rowSpan={span} key={cell} className="border-r border-gray-100 px-4 py-3 whitespace-nowrap align-middle tabular-nums">{display(value)}</td> : null;
                                })}
                                <td className={`border-r border-gray-100 px-4 py-3 whitespace-nowrap align-middle font-semibold ${row.status === 'NEW' ? 'text-[#4C5E86]' : 'text-gray-600'}`}>{display(row.status)}</td>
                                <td className="min-w-44 border-r border-gray-100 px-4 py-3 align-middle whitespace-pre-wrap break-words">{display(row.action)}</td>
                                <td className="border-r border-gray-100 px-3 py-3 text-center align-middle"><Result value={row.built} raw={row.builtRaw} checkbox /></td>
                                <td className="px-3 py-3 text-center align-middle"><Result value={row.complete} raw={row.completeRaw} checkbox /></td>
                            </tr>
                        )) : <tr><td colSpan={columns.length + 2} className="px-4 py-6 text-center font-normal text-gray-500">ไม่มีข้อมูลในส่วนนี้</td></tr>}
                    </tbody>
                </table>
            </div>
        </section>
    );
}

const actionNames = ['New Product', 'FG Components', 'Semi FG LV.2', 'Semi FG LV.2 Components', 'Semi FG LV.1', 'Semi FG LV.1 Components', 'Raw Material Type4', 'Raw Material Type4 Components'];

export default function Overview({ auth, title, kind, records, page, total, error, notice, actions = {} }) {
    const [selection, setSelection] = useState(null);
    const [selectedPath, setSelectedPath] = useState('');
    const record = records[0];
    const pageRoute = `overview.${kind}`;
    const openForm = (action, path) => router.get(route('overview.products.add', { action }), { ...record?.identity, path });
    const plus = (action) => {
        if (!showCreationActions) return null;
        const options = actions[action] || [];
        const disabled = action !== 1 && options.length === 0;
        const reason = 'ไม่มี parent ที่พร้อมเพิ่มข้อมูล หรือเจ้าของข้อมูลเป็น COM / ข้อมูลไม่ครบ';
        return <button type="button" disabled={disabled} aria-label={`เพิ่ม ${actionNames[action - 1]}`} title={disabled ? reason : `เพิ่ม ${actionNames[action - 1]}`}
            className="ml-2 inline-flex h-6 w-6 items-center justify-center rounded bg-[#5E9E6B] text-lg text-white hover:bg-[#518B5D] focus:ring-2 focus:ring-[#4C5E86] disabled:cursor-not-allowed disabled:opacity-40"
            onClick={() => { if (action === 1 || options.length === 1) openForm(action, options[0]?.path || []); else { setSelectedPath(''); setSelection({ action, options }); } }}>+</button>;
    };

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="text-xl font-semibold text-green-900">{title}</h2>}>
            <Head title={title} />
            <main className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
                    <nav className="flex flex-wrap gap-2" aria-label="Overview pages">
                        {[['products', 'Products'], ['bs', 'Business Supply']].map(([key, label]) => (
                            <Link key={key} href={route(`overview.${key}`)} aria-current={kind === key ? 'page' : undefined}
                                className={kind === key ? `${buttonClass} border-transparent bg-[#4C5E86] text-white hover:bg-[#435477]` : secondaryClass}>{label}</Link>
                        ))}
                    </nav>
                    <div className="flex flex-wrap items-center justify-between gap-4 border-t border-gray-100 pt-4">
                        <nav className="flex items-center gap-2 sm:gap-3" aria-label="Overview pagination">
                            {page > 1 && !error ? <Link href={route(pageRoute, { page: page - 1 })} className={secondaryClass}>Previous</Link> : <button disabled className={disabledClass}>Previous</button>}
                            <span aria-live="polite" className="min-w-14 text-center text-sm font-semibold tabular-nums text-green-900">{page} / {total}</span>
                            {page < total && !error ? <Link href={route(pageRoute, { page: page + 1 })} className={secondaryClass}>Next</Link> : <button disabled className={disabledClass}>Next</button>}
                        </nav>
                        {total > 0 && !error ? <a href={route(`${pageRoute}.export`)} className={exportClass}>Export Excel ทุกรายการ</a> : <button disabled className={disabledClass}>Export Excel ทุกรายการ</button>}
                    </div>
                </div>
                {notice && <p role="status" className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900">{notice}</p>}
                {showCreationActions && kind === 'products' && !record && <div>New Product {plus(1)}</div>}
                {error ? (
                    <div role="alert" className="space-y-4 rounded-lg border border-red-200 bg-red-50 p-6 text-red-800">
                        <p>{error}</p>
                        <Link href={route(pageRoute, { page: Math.max(page, 1) })} className={secondaryClass}>ลองใหม่</Link>
                    </div>
                ) : record ? (
                    <div className="space-y-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                        <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                            {record.header.map((field) => <div key={field.label}><dt className="text-xs font-semibold text-[#4C5E86]">{field.label}</dt><dd className="mt-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 break-all text-sm font-semibold text-green-900">{display(field.value)}</dd></div>)}
                        </dl>
                        <p className="border-t border-gray-100 pt-4 text-xs font-normal text-gray-500">BUILT / COMPLETE: ☑ = Y, ☐ = N, — = ไม่มีค่า</p>
                        {showCreationActions && kind === 'products' && <p className="text-xs text-gray-500">ปุ่ม + ที่ปิดอยู่: ไม่มี parent ที่พร้อมเพิ่มข้อมูล เจ้าของข้อมูลเป็น COM หรือข้อมูล DML ไม่ครบ</p>}
                        {record.sections.map((section, index) => <Section key={section.label} section={section} number={index + 1} products={kind === 'products'} plus={plus} />)}
                    </div>
                ) : <p role="status" className="rounded-lg border border-gray-200 bg-white p-6 font-normal text-gray-600">ไม่มีรายการ Overview ที่เข้าเงื่อนไข</p>}
            </main>
            <Modal show={!!selection} onClose={() => setSelection(null)}>
                <div className="space-y-5 p-6">
                    <Dialog.Title as="h3" className="text-lg font-semibold text-green-900">เลือก parent สำหรับ {actionNames[(selection?.action || 1) - 1]}</Dialog.Title>
                    <p className="text-sm text-gray-600">เลือกเส้นทาง FG → LV.2 → LV.1 → Type4 ที่ต้องการ</p>
                    {selection?.options.map((option, index) => <label key={index} className="flex items-center gap-3 rounded border border-gray-200 p-3 text-sm"><input type="radio" name="parentPath" value={JSON.stringify(option.path)} checked={selectedPath === JSON.stringify(option.path)} onChange={(event) => setSelectedPath(event.target.value)} />{option.label}</label>)}
                    <div className="flex justify-end gap-3"><button className={secondaryClass} onClick={() => setSelection(null)}>Cancel</button><button disabled={!selectedPath} className={selectedPath ? exportClass : disabledClass} onClick={() => { openForm(selection.action, JSON.parse(selectedPath)); setSelection(null); }}>เปิดฟอร์ม</button></div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
