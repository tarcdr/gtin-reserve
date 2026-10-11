import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function RawMaterial({ auth, context }) {
    const title = context.action === 7 ? 'Raw Material Type4' : 'Raw Material Type4 Components';
    const fields = context.fields;
    return <AuthenticatedLayout user={auth.user} header={<h2 className="text-xl text-green-900">{title}</h2>}>
        <Head title={title} />
        <div className="mx-auto max-w-7xl p-6">
            <section className="space-y-6 rounded-lg border border-gray-200 bg-white p-6">
                <dl className="grid gap-4 sm:grid-cols-2">
                    {[['GTIN', context.header?.GTIN], ['BOM FG', fields.fgBomId], ['FG ID', fields.fgMaterialId], ['SEMI FG LV.2', fields.fgDetail?.semiFgLv2?.id], ['SEMI FG LV.1', fields.levelMaterialId], ['RAW MATERIAL TYPE4', fields.rawMaterialId]].map(([label, value]) => <div key={label}><dt className="text-sm text-[#4C5E86]">{label}</dt><dd className="mt-1 rounded-md bg-gray-50 p-3">{value || '—'}</dd></div>)}
                </dl>
                <p role="status" className="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm font-normal text-amber-900">รอ flow บันทึกจาก SA ฟอร์มนี้ยังไม่บันทึกข้อมูล และไม่มีการอัปเดต INDEX_SECTION</p>
                <div className="grid gap-4 sm:grid-cols-2">
                    {['Component / Material ID', 'Search Description', 'Full Description (EN)', 'Full Description (TH)', 'UOM'].map((label) => <label key={label} className="block text-sm text-[#4C5E86]">{label}<input className="mt-2 block w-full rounded-md border-gray-300 bg-gray-50" /></label>)}
                </div>
                <div className="flex gap-3"><Link href={context.returnUrl} className="rounded-md border border-gray-300 px-4 py-2 text-sm">Cancel</Link><button disabled title="รอ flow บันทึกจาก SA" className="rounded-md bg-[#5E9E6B] px-4 py-2 text-sm text-white opacity-40">Save</button></div>
            </section>
        </div>
    </AuthenticatedLayout>;
}
