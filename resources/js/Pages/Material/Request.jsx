import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { Transition } from '@headlessui/react';
import Radio from '@/Components/Radio';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Request({ auth, InputData, brand = [], mattype = [] }) {

    const { data, setData, patch, errors, processing, recentlySuccessful, transform } = useForm({
        brand: InputData?.brand || '',
        mattype: InputData?.mattype || '',
        material_desc: InputData?.material_desc || '',
        p_last_id: InputData?.p_last_id || '',
        p_suggest_id: InputData?.p_suggest_id || '',
        materialChoose: InputData?.materialChoose,
    });

    transform(dataSet => ({
      ...dataSet,
      p_last_id: InputData?.p_last_id,
      p_suggest_id: InputData?.p_suggest_id,
    }));

    const isDisabled = () => InputData?.brand && InputData?.mattype;

    const submit = (e) => {
        e.preventDefault();

        patch(route('material.update'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Request</h2>}
        >
            <Head title="Request" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <InputLabel htmlFor="brand" value="Brand" />
                                <select
                                    id="brand"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('brand', e.target.value)}
                                    defaultValue={data?.brand}
                                    disabled={isDisabled()}
                                >
                                    <option value="">---- Select Brand ----</option>
                                    {brand?.map(o => (
                                        <option key={`brand-code-${o.code}`} value={o.code}>{`${o.abb} - ${o.code}`}</option>
                                    ))}
                                </select>

                                <InputError className="mt-2" message={errors.brand} />
                            </div>
                            <div>
                                <InputLabel htmlFor="mattype" value="MATTYPE" />
                                <select
                                    id="mattype"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('mattype', e.target.value)}
                                    defaultValue={data?.mattype}
                                    disabled={isDisabled()}
                                >
                                    <option value="">---- Select MATTYPE ----</option>
                                    {mattype?.map(o => (
                                        <option key={`mattype-code-${o.code}`} value={o.code}>{o.name}</option>
                                    ))}
                                </select>

                                <InputError className="mt-2" message={errors.mattype} />
                            </div>
                          </div>
                          {(!InputData?.brand || !InputData?.mattype) && (
                            <div className="flex items-center justify-center gap-4">
                                <PrimaryButton disabled={processing}>Submit</PrimaryButton>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-gray-600">Saved.</p>
                                </Transition>
                            </div>
                          )}
                          {InputData?.brand && InputData?.mattype && (
                            <>
                              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div className="grid grid-cols-1 gap-4">
                                  <div className="grid grid-cols-4 gap-2">
                                    <label className="flex items-center">
                                        <Radio
                                            name="materialChoose"
                                            checked={data?.materialChoose === 'l'}
                                            value="l"
                                            onChange={(e) => setData('materialChoose', e.target.value)}
                                        />
                                        <span className="ml-2">Choose</span>
                                    </label>
                                    <div className="col-span-3">
                                        <InputLabel htmlFor="latestMaterialId" value="Latest Material ID" />

                                        <TextInput
                                            id="latestMaterialId"
                                            className="mt-1 block w-full bg-gray-100"
                                            value={InputData?.p_last_id || ''}
                                            disabled
                                        />
                                    </div>
                                  </div>
                                </div>
                                <div className="grid grid-cols-1 gap-4">
                                  <div className="grid grid-cols-4 gap-2">
                                    <label className="flex items-center">
                                        <Radio
                                            name="materialChoose"
                                            checked={data?.materialChoose === 's'}
                                            value="s"
                                            onChange={(e) => setData('materialChoose', e.target.value)}
                                        />
                                        <span className="ml-2">Choose</span>
                                    </label>
                                    <div className="col-span-3">
                                        <InputLabel htmlFor="suggestMaterialId" value="Suggest Material ID" />

                                        <TextInput
                                            id="suggestMaterialId"
                                            className="mt-1 block w-full bg-gray-100"
                                            value={InputData?.p_suggest_id || ''}
                                            disabled
                                        />
                                    </div>
                                  </div>
                                </div>
                              </div>

                              <div className="grid grid-cols-1">
                                <div className="col-span-3">
                                    <InputLabel htmlFor="material_desc" value="Material Description" />

                                    <TextInput
                                        id="material_desc"
                                        className="mt-1 block w-full"
                                        value={data.material_desc}
                                        maxLength="40"
                                        onChange={(e) => setData('material_desc', e.target.value)}
                                    />

                                    <InputError className="mt-2" message={errors.material_desc} />
                                </div>
                              </div>

                              <div className="flex items-center justify-center gap-4">
                                  <SecondaryButton onClick={() => window.open('/material/request', '_self')}>Reset</SecondaryButton>
                                  <PrimaryButton disabled={processing}>Reserve</PrimaryButton>

                                  <Transition
                                      show={recentlySuccessful}
                                      enter="transition ease-in-out"
                                      enterFrom="opacity-0"
                                      leave="transition ease-in-out"
                                      leaveTo="opacity-0"
                                  >
                                      <p className="text-sm text-gray-600">Saved.</p>
                                  </Transition>
                              </div>
                            </>
                          )}
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
