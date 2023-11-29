import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Report({ auth, gtins = [] }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Report</h2>}
        >
            <Head title="Report" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="flex items-center justify-end gap-4 mb-2">
                        <PrimaryButton onClick={() => window.open(route('export'))}>Download</PrimaryButton>
                    </div>
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                      <table class="w-full text-sm text-left rtl:text-right text-gray-800 dark:text-gray-600">
                        <thead class="text-xs bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
                            <tr>
                                <th scope="col" class="px-6 py-3">
                                    #
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Material ID
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Trading Unit
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Global Trade item number
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Last User Update
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Last Update
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                          {gtins.map((o, index) => (
                            <tr key={`report-gtin-${o.global_trade_item_number}`}>
                                <th scope="row" class="px-6 py-4">
                                    {index + 1}
                                </th>
                                <th scope="row" class="px-6 py-4">
                                    {o.material_id}
                                </th>
                                <td class="px-6 py-4">
                                    {o.trading_unit}
                                </td>
                                <td class="px-6 py-4">
                                    {o.global_trade_item_number}
                                </td>
                                <td class="px-6 py-4">
                                    {o.user_last_update}
                                </td>
                                <td class="px-6 py-4">
                                    {o.last_update}
                                </td>
                                <td class="px-6 py-4">
                                    <a href="#" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">{o.status_gtin}</a>
                                </td>
                            </tr>
                          ))}
                        </tbody>
                    </table>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
