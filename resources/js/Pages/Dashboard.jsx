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

function MessageIcon({ variant }) {
    if (variant === 'warning') {
        return (
            <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path
                    fillRule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.5a.75.75 0 00-1.5 0v5a.75.75 0 001.5 0v-5zM10 14.75a1 1 0 100-2 1 1 0 000 2z"
                    clipRule="evenodd"
                />
            </svg>
        );
    }

    return (
        <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path
                fillRule="evenodd"
                d="M18 10A8 8 0 112 10a8 8 0 0116 0zM9.25 8.75a.75.75 0 011.5 0v5a.75.75 0 01-1.5 0v-5zM10 7a1 1 0 100-2 1 1 0 000 2z"
                clipRule="evenodd"
            />
        </svg>
    );
}

function MessageBlock({ children, label, variant = 'information' }) {
    if (!children) {
        return null;
    }

    const styles = {
        warning: {
            container: 'border-red-200 bg-red-50',
            label: 'text-red-700',
            content: 'text-red-950',
        },
        information: {
            container: 'border-blue-200 bg-blue-50',
            label: 'text-blue-700',
            content: 'text-blue-950',
        },
    };
    const variantStyles = styles[variant] || styles.information;

    return (
        <div className={`rounded-lg border px-4 py-4 shadow-sm ${variantStyles.container}`}>
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start">
                <div className={`flex min-w-[112px] shrink-0 items-center gap-2 text-sm font-black ${variantStyles.label}`}>
                    <MessageIcon variant={variant} />
                    <span>{label}</span>
                </div>
                <div className={`dashboard-message-content min-w-0 flex-1 text-sm font-extrabold ${variantStyles.content}`}>
                    {parse(children)}
                </div>
            </div>
        </div>
    );
}

export default function Dashboard({ auth, message = '', messageProj12 = '', summaryHead = [], summaryRows = [] }) {
    const summaryHeadMap = summaryHead.reduce((acc, item) => {
        acc[item.no] = item.head_label;
        return acc;
    }, {});

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>}
            pageIdentity={{
                pageId: '1D',
            }}
        >
            <Head title="Dashboard" />

            <div className="py-12 space-y-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="px-4 pb-5 pt-8 sm:px-8 sm:pb-8 sm:pt-10">
                            <div className="mx-auto flex w-full max-w-3xl rounded-full bg-[#cfe6f8] p-2 shadow-[inset_0_2px_10px_rgba(255,255,255,0.7),_0_18px_45px_rgba(30,64,175,0.18)]">
                                <Link
                                    href={route('product.new')}
                                    className="inline-flex min-h-[54px] flex-1 items-center justify-center rounded-full bg-[linear-gradient(180deg,_#2f80d8_0%,_#0d3f94_100%)] px-5 text-lg font-black uppercase tracking-[0.08em] text-white shadow-[0_8px_20px_rgba(15,61,145,0.45),_inset_0_2px_8px_rgba(255,255,255,0.28)] transition hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-blue-300 sm:text-2xl"
                                >
                                    NPD
                                </Link>
                                <Link
                                    href={route('product.search')}
                                    className="inline-flex min-h-[54px] flex-1 items-center justify-center rounded-full px-5 text-lg font-black uppercase tracking-[0.08em] text-[#4d93cc] transition hover:bg-white/35 focus:outline-none focus:ring-4 focus:ring-blue-200 sm:text-2xl"
                                >
                                    Existing
                                </Link>
                            </div>
                        </div>

                        {(message || messageProj12) && (
                            <div className="px-4 pb-6 sm:px-8">
                                <section className="rounded-2xl border border-gray-200 bg-white p-5 shadow-[0_16px_40px_rgba(15,23,42,0.12)]">
                                    <h3 className="mb-4 text-lg font-black text-gray-900">
                                        ประกาศสำคัญและการแจ้งเตือน
                                    </h3>
                                    <div className="space-y-3">
                                        <MessageBlock variant="warning" label="Warning">{message}</MessageBlock>
                                        <MessageBlock variant="information" label="Information">{messageProj12}</MessageBlock>
                                    </div>
                                </section>
                            </div>
                        )}
                    </div>
                </div>

                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left rtl:text-right text-gray-800">
                                <thead className="text-xs bg-gray-50">
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
                                        <tr
                                            key={`dashboard-summary-${index}`}
                                            className={index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}
                                        >
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
