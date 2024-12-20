import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Report({ auth, InputData, materials = [] }) {
    const [confirmingActive, setConfirmingActive] = useState(false);
    const { data, setData: setData2, patch: patch2, errors, processing: processing2, recentlySuccessful } = useForm({
        search: InputData?.search || '',
    });
    const { setData, patch, processing } = useForm({
        material_id: ''
    });

    const confirmActiveGtin = material_id => {
        setData('material_id', material_id);
        setConfirmingActive(true);
    };

    const closeModal = () => {
        setConfirmingActive(false);
    };

    const setActive = (e) => {
        e.preventDefault();

        patch(route('material.confirm'), {
            onSuccess: () => closeModal()
        });
    };

    const submit = (e) => {
        e.preventDefault();

        patch2(route('material.search'));
    };

    const trigger = value => {
      setData2('search', value);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Material_Confirm/Report</h2>}
        >
            <Head title="Material_Confirm/Report" />

            <div className="pt-12 pb-4">
              <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                  <form onSubmit={submit} className="space-y-6">
                    <div className="grid grid-cols-1">
                      <div>
                          <InputLabel htmlFor="search" value="Material ID" />

                          <TextInput
                              id="search"
                              className="mt-1 block w-full"
                              value={data.search}
                              onChange={(e) => trigger(e.target.value)}
                              isFocused
                          />

                          <InputError className="mt-2" message={errors.search} />
                      </div>
                    </div>
                    <div className="flex items-center justify-center gap-4">
                        <PrimaryButton disabled={processing2}>Search</PrimaryButton>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <div className="pb-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="flex items-center justify-end gap-4 mb-2">
                        <PrimaryButton onClick={() => window.open(route('material.export'))}>Download</PrimaryButton>
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
                                    Material Desc
                                </th>
                                <th scope="col" className="px-6 py-3">
                                    Brand
                                </th>
                                <th scope="col" className="px-6 py-3">
                                    Mat Type
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
                          {materials.map((o, index) => (
                            <tr key={`report-gtin-${o.material_id}`}>
                                <th scope="row" className="px-6 py-4">
                                    {index + 1}
                                </th>
                                <th scope="row" className="px-6 py-4">
                                    {o.material_id}
                                </th>
                                <td className="px-6 py-4">
                                    {o.material_desc}
                                </td>
                                <td className="px-6 py-4">
                                    {o.brand}
                                </td>
                                <td className="px-6 py-4">
                                    {o.mattype}
                                </td>
                                <td className="px-6 py-4">
                                    {o.last_user}
                                </td>
                                <td className="px-6 py-4">
                                    {o.last_update}
                                </td>
                                <td className="px-6 py-4">
                                    {o.status?.toLowerCase() === 'reserve' && o.last_user === auth.user.user_login ? (
                                      <a href="#" className="font-medium text-blue-600 dark:text-blue-500 hover:underline" onClick={() => confirmActiveGtin(o.material_id)}>{o.status}</a>
                                    ) : (
                                      o.status
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
                        Are you sure you want to Active Material ID?
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
