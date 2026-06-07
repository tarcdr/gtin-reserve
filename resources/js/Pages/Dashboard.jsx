import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import parse from 'html-react-parser';

const summaryFields = [
    { key: 'DATA_YEAR', labelNo: 1, className: 'w-24' },
    { key: 'DATA_MONTH', labelNo: 2, className: 'w-28' },
    { key: 'NPD_AMT', labelNo: 4, className: 'w-28 text-center' },
    { key: 'GTIN_PCS_AMT', labelNo: 6, className: 'w-28 text-center' },
    { key: 'GTIN_BOX_AMT', labelNo: 9, className: 'w-28 text-center' },
    { key: 'GTIN_CARTON_AMT', labelNo: 10, className: 'w-28 text-center' },
    { key: 'GTIN_PACKAGE_AMT', labelNo: 11, className: 'w-28 text-center' },
    { key: 'GTIN_PAIR_AMT', labelNo: 12, className: 'w-28 text-center' },
    { key: 'GTIN_SET_AMT', labelNo: 13, className: 'w-28 text-center' },
];

function headLabel(summaryHeadMap, no) {
    return parse(summaryHeadMap?.[no] || '-');
}

export default function Dashboard({ auth, message = '', summaryHead = [], summaryRows = [] }) {
    const summaryHeadMap = summaryHead.reduce((acc, item) => {
        acc[item.no] = item.head_label;
        return acc;
    }, {});

    const dashboardButtonBase =
        'relative inline-flex flex-none w-[240px] sm:w-[320px] items-center justify-center overflow-hidden rounded-full px-7 py-5 text-xl sm:text-3xl font-black tracking-[0.08em] uppercase text-white transition-transform duration-300 ease-out focus:outline-none focus:ring-4 focus:ring-offset-4 focus:ring-offset-white hover:-translate-y-1';

    const buttonGloss =
        'before:pointer-events-none before:absolute before:inset-x-4 before:top-3 before:h-1/2 before:rounded-full before:bg-white/35 before:blur-2xl before:content-[""] after:pointer-events-none after:absolute after:inset-0 after:rounded-full after:bg-gradient-to-b after:from-white/25 after:via-transparent after:to-transparent after:content-[""]';

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12 space-y-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="my-12 flex flex-col items-center justify-center gap-5 px-4 sm:flex-row sm:gap-6">
                            <Link
                                href={route('product.new')}
                                className={`${dashboardButtonBase} ${buttonGloss} border border-cyan-200/60 bg-[linear-gradient(180deg,_#9fe7f6_0%,_#55b6ee_45%,_#68d9e7_100%)] shadow-[0_0_0_8px_rgba(142,228,247,0.35),_0_18px_45px_rgba(59,130,246,0.35),_inset_0_2px_10px_rgba(255,255,255,0.55)] focus:ring-cyan-300`}
                            >
                                <span className="relative z-10 drop-shadow-[0_2px_2px_rgba(0,0,0,0.15)]">
                                    NPD
                                </span>
                            </Link>
                            <Link
                                href={route('product.search')}
                                className={`${dashboardButtonBase} ${buttonGloss} border border-blue-300/50 bg-[linear-gradient(180deg,_#1d4ed8_0%,_#0b2aa8_52%,_#0a1b87_100%)] shadow-[0_0_0_8px_rgba(59,130,246,0.28),_0_20px_50px_rgba(29,78,216,0.45),_inset_0_2px_12px_rgba(255,255,255,0.18)] focus:ring-blue-400`}
                            >
                                <span className="relative z-10 drop-shadow-[0_2px_2px_rgba(0,0,0,0.2)]">
                                    Existing
                                </span>
                            </Link>
                        </div>

                        <div className="p-6 text-gray-900">{parse(message)}</div>
                    </div>
                </div>

                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left rtl:text-right text-gray-800 dark:text-gray-600">
                                <thead className="text-xs bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
                                    <tr>
                                        <th rowSpan={4} className="border border-gray-200 px-6 py-3 text-center text-xs font-semibold uppercase align-middle">
                                            {headLabel(summaryHeadMap, 1)}
                                        </th>
                                        <th rowSpan={4} className="border border-gray-200 px-6 py-3 text-center text-xs font-semibold uppercase align-middle">
                                            {headLabel(summaryHeadMap, 2)}
                                        </th>
                                        <th colSpan={7} className="border border-gray-200 px-6 py-3 text-center text-xs font-semibold uppercase">
                                            {headLabel(summaryHeadMap, 3)}
                                        </th>
                                    </tr>
                                    <tr>
                                        <th rowSpan={3} className="border border-gray-200 px-6 py-3 text-center text-xs font-semibold uppercase align-middle">
                                            {headLabel(summaryHeadMap, 4)}
                                        </th>
                                        <th colSpan={6} className="border border-gray-200 px-6 py-3 text-center text-xs font-semibold uppercase">
                                            {headLabel(summaryHeadMap, 5)}
                                        </th>
                                    </tr>
                                    <tr>
                                        <th rowSpan={2} className="border border-gray-200 px-6 py-3 text-center text-xs font-semibold uppercase align-middle">
                                            {headLabel(summaryHeadMap, 6)}
                                        </th>
                                        <th colSpan={5} className="border border-gray-200 px-6 py-3 text-center text-xs font-semibold uppercase">
                                            {headLabel(summaryHeadMap, 8)}
                                        </th>
                                    </tr>
                                    <tr>
                                        <th className="border border-gray-200 px-6 py-3 text-center text-xs font-semibold uppercase">{headLabel(summaryHeadMap, 9)}</th>
                                        <th className="border border-gray-200 px-6 py-3 text-center text-xs font-semibold uppercase">{headLabel(summaryHeadMap, 10)}</th>
                                        <th className="border border-gray-200 px-6 py-3 text-center text-xs font-semibold uppercase">{headLabel(summaryHeadMap, 11)}</th>
                                        <th className="border border-gray-200 px-6 py-3 text-center text-xs font-semibold uppercase">{headLabel(summaryHeadMap, 12)}</th>
                                        <th className="border border-gray-200 px-6 py-3 text-center text-xs font-semibold uppercase">{headLabel(summaryHeadMap, 13)}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {summaryRows.map((row, index) => (
                                        <tr key={`dashboard-summary-${index}`}>
                                            {summaryFields.map((field) => (
                                                <td key={field.key} className={`border border-gray-200 px-6 py-4 text-center ${field.className}`}>
                                                    {row?.[field.key] ?? '-'}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
