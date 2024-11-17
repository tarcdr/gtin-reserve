import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';

export default function Request({ auth, InputData, brand = [] }) {

    const { data, setData, get, patch, errors, processing, recentlySuccessful, transform } = useForm({
        bom: InputData?.bom,
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

    const backToBom = () => {
      get(route('bom.new'));
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
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">RM - Material Create</h2>}
        >
            <Head title="RM - Material Create" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <InputLabel htmlFor="brand" value="Mattype" />
                                <select
                                    id="brand"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('brand', e.target.value)}
                                    defaultValue={data?.brand}
                                    disabled={isDisabled()}
                                >
                                    <option value="">---- Select Mattype ----</option>
                                    {brand?.map(o => (
                                        <option key={`brand-code-${o.code}`} value={o.code}>{`${o.abb} - ${o.code}`}</option>
                                    ))}
                                </select>

                                <InputError className="mt-2" message={errors.brand} />
                            </div>
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
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                              <InputLabel htmlFor="mattype" value="Material ID" />

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
                                <SecondaryButton type="button" onClick={backToBom}>
                                    Back To BOM
                                </SecondaryButton>
                                <PrimaryButton disabled={processing}>Save</PrimaryButton>
                                <DangerButton>Delete</DangerButton>
                            </div>
                          )}
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
