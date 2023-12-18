import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Report({ auth, gtins = [] }) {
    const [confirmingActive, setConfirmingActive] = useState(false);
    const { setData, patch, processing } = useForm({
        gtin: ''
    });

    const confirmActiveGtin = gtin => {
        setData('gtin', gtin);
        setConfirmingActive(true);
    };

    const closeModal = () => {
        setConfirmingActive(false);
    };

    const setActive = (e) => {
        e.preventDefault();

        patch(route('report.confirm'), {
            onSuccess: () => closeModal()
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">GTIN_Confirm/Report</h2>}
        >
            <Head title="GTIN_Confirm/Report" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="flex items-center justify-end gap-4 mb-2">
                        <PrimaryButton onClick={() => window.open(route('export'))}>Download</PrimaryButton>
                    </div>
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                      <table className="w-full text-sm text-left rtl:text-right text-gray-800 dark:text-gray-600">
                        <thead className="text-xs bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
                            <tr>
                                <th scope="col" className="px-6 py-3">
                                    #
                                </th>
                                <th scope="col" className="px-6 py-3">
                                    Material ID
                                </th>
                                <th scope="col" className="px-6 py-3">
                                    Trading Unit
                                </th>
                                <th scope="col" className="px-6 py-3">
                                    Global Trade item number
                                </th>
                                <th scope="col" className="px-6 py-3">
                                    Last User Update
                                </th>
                                <th scope="col" className="px-6 py-3">
                                    Last Update
                                </th>
                                <th scope="col" className="px-6 py-3">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                          {gtins.map((o, index) => (
                            <tr key={`report-gtin-${o.global_trade_item_number}`}>
                                <th scope="row" className="px-6 py-4">
                                    {index + 1}
                                </th>
                                <th scope="row" className="px-6 py-4">
                                    {o.material_id}
                                </th>
                                <td className="px-6 py-4">
                                    {o.trading_unit}
                                </td>
                                <td className="px-6 py-4">
                                    {o.global_trade_item_number}
                                </td>
                                <td className="px-6 py-4">
                                    {o.user_last_update}
                                </td>
                                <td className="px-6 py-4">
                                    {o.last_update}
                                </td>
                                <td className="px-6 py-4">
                                    {o.status_gtin?.toLowerCase() === 'reserve' && o.user_last_update.toLowerCase() === auth?.user_login.toLowerCase() ? (
                                        <a href="#" className="font-medium text-blue-600 dark:text-blue-500 hover:underline" onClick={() => confirmActiveGtin(o.global_trade_item_number)}>{o.status_gtin}</a>
                                    ) : (
                                        o.status_gtin
                                    )}
                                </td>
                            </tr>
                          ))}
                        </tbody>
                    </table>

                    </div>
                </div>
            </div>

            <Modal show={confirmingActive} onClose={closeModal}>
                <form onSubmit={setActive} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        Are you sure you want to Active GTIN?
                    </h2>

                    <p className="mt-1 text-sm text-gray-600">
                        &nbsp;
                    </p>

                    <div className="mt-6 flex justify-end">
                        <SecondaryButton onClick={closeModal}>Cancel</SecondaryButton>

                        <PrimaryButton className="ml-3" disabled={processing}>
                            Confirm
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
