import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Request({ auth, InputData, brand = [] }) {

    const { data, setData, patch, errors, processing, recentlySuccessful, transform } = useForm({
        brand: InputData?.brand || '',
        mattype: 1,
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

    useEffect(() => {
      if (recentlySuccessful && InputData?.success) {
        setTimeout(() => {
          window.open('/material/report', '_self');
        }, 500);
      }
    }, [recentlySuccessful, data.materialChoose]);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">NPD</h2>}
        >
            <Head title="NPD" />

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
                              <InputLabel htmlFor="mattype" value="Mattype" />

                              <TextInput
                                  id="mattype"
                                  className="mt-1 block w-full bg-gray-100"
                                  value={data?.mattype}
                                  disabled
                              />
                            </div>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <InputLabel htmlFor="brand" value="Sub Mattype" />
                                <select
                                    id="brand"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('brand', e.target.value)}
                                    defaultValue={data?.brand}
                                    disabled={isDisabled()}
                                >
                                    <option value="">---- Select Sub Mattype ----</option>
                                    {brand?.map(o => (
                                        <option key={`brand-code-${o.code}`} value={o.code}>{`${o.abb} - ${o.code}`}</option>
                                    ))}
                                </select>

                                <InputError className="mt-2" message={errors.brand} />
                            </div>
                            <div>
                                <InputLabel htmlFor="brand" value="Product Group" />
                                <select
                                    id="brand"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('brand', e.target.value)}
                                    defaultValue={data?.brand}
                                    disabled={isDisabled()}
                                >
                                    <option value="">---- Select Product Group ----</option>
                                    {brand?.map(o => (
                                        <option key={`brand-code-${o.code}`} value={o.code}>{`${o.abb} - ${o.code}`}</option>
                                    ))}
                                </select>

                                <InputError className="mt-2" message={errors.brand} />
                            </div>
                          </div>

                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                              <div>
                                <InputLabel htmlFor="brand" value="Finish Goods" />
                                <select
                                    id="brand"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('brand', e.target.value)}
                                    defaultValue={data?.brand}
                                    disabled={isDisabled()}
                                >
                                    <option value="">---- Select Finish Goods ----</option>
                                    {brand?.map(o => (
                                        <option key={`brand-code-${o.code}`} value={o.code}>{`${o.abb} - ${o.code}`}</option>
                                    ))}
                                </select>

                                <InputError className="mt-2" message={errors.brand} />
                              </div>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                              <InputLabel htmlFor="mattype" value="Suggest Material ID" />

                              <TextInput
                                  id="mattype"
                                  className="mt-1 block w-full bg-gray-100"
                                  disabled
                              />
                            </div>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                              <InputLabel htmlFor="material_desc" value="Search Description" />

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
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                              <InputLabel htmlFor="material_desc" value="Full Description (EN)" />

                              <TextInput
                                  id="material_desc"
                                  className="mt-1 block w-full"
                                  value={data.material_desc}
                                  maxLength="40"
                                  onChange={(e) => setData('material_desc', e.target.value)}
                              />

                              <InputError className="mt-2" message={errors.material_desc} />
                            </div>
                            <div>
                              <InputLabel htmlFor="material_desc" value="Full Description (TH)" />

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

                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                              <div>
                                <InputLabel htmlFor="brand" value="UOM" />
                                <select
                                    id="brand"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('brand', e.target.value)}
                                    defaultValue={data?.brand}
                                    disabled={isDisabled()}
                                >
                                    <option value="">---- Select UOM ----</option>
                                    {brand?.map(o => (
                                        <option key={`brand-code-${o.code}`} value={o.code}>{`${o.abb} - ${o.code}`}</option>
                                    ))}
                                </select>

                                <InputError className="mt-2" message={errors.brand} />
                              </div>
                          </div>
                          {(!InputData?.brand || !InputData?.mattype) && (
                            <div className="flex items-center justify-center gap-4">
                                <Link href={route('dashboard')}>
                                    <SecondaryButton>
                                        Back
                                    </SecondaryButton>
                                </Link>
                                <PrimaryButton disabled={processing}>Save FG</PrimaryButton>
                            </div>
                          )}
                        </form>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mt-3">
                            <div className="border-2 border-gray-400 rounded-lg p-3">
                                <h1 className="text-xl font-bold text-gray-600 mb-2">
                                    Component Request
                                </h1>
                                <div className="flex items-center gap-4">
                                    <Link href={route('rm.component_request')}>
                                        <PrimaryButton>
                                            <span className="text-xl">
                                                RM
                                            </span>
                                        </PrimaryButton>
                                    </Link>
                                    <PrimaryButton>
                                        <span className="text-xl">
                                            PM
                                        </span>
                                    </PrimaryButton>
                                </div>
                            </div>
                            <div className="border-2 border-gray-400 rounded-lg p-3">
                                <div>
                                    <PrimaryButton className="ml-3 font-bold py-4 px-8">
                                        <span className="text-xl">
                                            Businewss Supply
                                        </span>
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mt-3">
                            <div className="flex items-center gap-4">
                                <PrimaryButton>
                                    <span className="text-md">
                                        View ภาพรวม
                                    </span>
                                </PrimaryButton>
                                <PrimaryButton>
                                    <span className="text-md">
                                        Delete
                                    </span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
