import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';

export default function Request({ auth, InputData }) {

    const { data, setData, patch, errors, processing, recentlySuccessful, transform } = useForm({
        bom: InputData?.bom || '',
        description: ''
    });

    transform(dataSet => ({
      ...dataSet,
      bom: InputData?.bom
    }));

    const submit = (e) => {
        e.preventDefault();

        patch(route('bom.create.process'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">BOM - Create</h2>}
        >
            <Head title="BOM - Create" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                              <InputLabel htmlFor="bom" value="New BOM ID" />
                              <TextInput
                                  id="bom"
                                  className="mt-1 block w-full"
                                  defaultValue={data.bom}
                                  disabled
                              />
                            </div>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                              <InputLabel htmlFor="description" value="Description" />

                              <TextInput
                                  id="description"
                                  className="mt-1 block w-full"
                                  value={data.description}
                                  maxLength="40"
                                  onChange={(e) => setData('description', e.target.value)}
                              />

                              <InputError className="mt-2" message={errors.description} />
                            </div>
                          </div>
                          <div className="flex items-center justify-center gap-4">
                            <PrimaryButton disabled={processing}>Save</PrimaryButton>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-gray-600">Created.</p>
                            </Transition>
                          </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
