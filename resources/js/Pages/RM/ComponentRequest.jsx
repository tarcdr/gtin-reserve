import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function ComponentRequest({ auth, boms = [] }) {
  const [isOpen, setOpen] = useState(false);

  const { data, setData, get, errors } = useForm({
    bom: ''
  });

  const confirmActive = () => {
      setOpen(true);
  };

  const closeModal = () => {
      setOpen(false);
  };

  const setActive = (e) => {
      e.preventDefault();

      get(route('bom.exists', { id: data.bom }));
  };

  return (
    <AuthenticatedLayout
        user={auth.user}
        header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">RM - Component Request</h2>}
    >
        <Head title="RM - Component Request" />

        <div className="py-12">
            <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="my-12 flex justify-center">
                        <Link href={route('bom.new')}>
                            <PrimaryButton className="ml-3 font-bold py-4 px-8">
                              <span className="text-3xl">
                                New BOM
                              </span>
                            </PrimaryButton>
                        </Link>
                        <PrimaryButton className="ml-3 font-bold py-4 px-8" onClick={confirmActive}>
                          <span className="text-3xl">
                            Existing BOM
                          </span>
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>

        <Modal show={isOpen} onClose={closeModal}>
          <form onSubmit={setActive} className="p-6">
            <div>
              <InputLabel htmlFor="bom" value="BOM ID" />
              <select
                  id="bom"
                  className="mt-1 block w-full"
                  onChange={(e) => setData('bom', e.target.value)}
                  defaultValue={data?.bom}
              >
                  <option value="">---- Select BOM ID ----</option>
                  {boms?.map(o => (
                      <option key={`bom-code-${o.code}`} value={o.code}>{`${o.code} - ${o.label}`}</option>
                  ))}
              </select>

              <InputError className="mt-2" message={errors.bom} />
            </div>

            <div className="mt-6 flex justify-end">
                <SecondaryButton onClick={closeModal}>Cancel</SecondaryButton>

                <PrimaryButton className="ml-3">
                    Confirm
                </PrimaryButton>
            </div>
          </form>
        </Modal>
    </AuthenticatedLayout>
  );
}
