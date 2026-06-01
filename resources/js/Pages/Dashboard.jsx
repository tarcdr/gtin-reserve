import PrimaryButton from '@/Components/PrimaryButton';
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

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12 space-y-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="my-12 flex justify-center">
                            <Link href={route('product.new')}>
                                <PrimaryButton className="ml-3 font-bold py-4 px-8">
                                  <span className="text-3xl">
                                    NPD
                                  </span>
                                </PrimaryButton>
                            </Link>
                            <Link href={route('product.search')}>
                                <PrimaryButton className="ml-3 font-bold py-4 px-8">
                                  <span className="text-3xl">
                                    Existing
                                  </span>
                                </PrimaryButton>
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
