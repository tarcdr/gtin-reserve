import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';

export default function Request({ auth, InputData, boms = [], subBoms = {}, brand = [] }) {
    const [materialList, setMaterialList] = useState([]);
    const [isCreateMaterial, setIsCreateMaterial] = useState(false);

    const { data, setData, patch, errors, processing, recentlySuccessful, transform } = useForm({
        bom: InputData?.bom || '',
        description: InputData?.description || '',
        bom_refer: InputData?.bom_refer || '',
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

    const isDisabled = () => InputData?.bom_refer;

    const submit = (e) => {
        e.preventDefault();

        patch(route('material.update'));
    };

    const handleRemove = code => {
      const newList = materialList.filter(o => o.code !== code);
      setMaterialList(newList);
    };

    const toggleCreateMaterial = () => {
        setIsCreateMaterial(!isCreateMaterial);
    };

    useEffect(() => {
      if (data?.bom_refer && subBoms[data.bom_refer]) {
        setMaterialList(subBoms[data.bom_refer]);
      }
    }, [data, subBoms]);

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
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">BOM - Create</h2>}
        >
            <Head title="BOM - Create" />

            <div className="pt-12 mb-2">
              <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                  <form className="space-y-6">
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
                      <div>
                        <InputLabel htmlFor="description" value="Description" />
                        <TextInput
                            id="description"
                            className="mt-1 block w-full"
                            defaultValue={data.description}
                            disabled
                        />
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <div className={`pb-12${(isCreateMaterial && ' hidden') || ''}`}>
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <InputLabel htmlFor="bom_refer" value="BOM ID (Referrance)" />
                                <select
                                    id="bom_refer"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('bom_refer', e.target.value)}
                                    defaultValue={data?.bom_refer}
                                    disabled={isDisabled()}
                                >
                                    <option value="">---- Select BOM ID ----</option>
                                    {boms?.map(o => (
                                        <option key={`bom-code-${o.code}`} value={o.code}>{`${o.code} - ${o.label}`}</option>
                                    ))}
                                </select>

                                <InputError className="mt-2" message={errors.bom_refer} />
                            </div>
                          </div>
                          {/* Sub BOM Items List */}
                          {materialList?.length > 0 && (
                          <ul className="border border-gray-200 rounded-lg divide-y divide-gray-200">
                              {materialList.map((item, index) => (
                                  <li key={item.code} className="flex justify-between items-center p-3 bg-white hover:bg-gray-50">
                                      <span>{`${index + 1}. ${item.label}`}</span>
                                      <button
                                          className="text-red-500 hover:text-red-700 focus:outline-none"
                                          onClick={() => handleRemove(item.code)}
                                      >
                                          ลบ
                                      </button>
                                  </li>
                              ))}
                          </ul>
                          )}
                          <div className="grid grid-cols-1 lg:grid-cols-4 md:grid-cols-3 gap-8">
                            <PrimaryButton onClick={toggleCreateMaterial} type="button">
                                Add New Material
                            </PrimaryButton>
                          </div>
                          {(!InputData?.bom_refer) && (
                            <div className="flex items-center justify-center gap-4">
                                <Link href={route('rm.component_request')}>
                                    <SecondaryButton>
                                        Back
                                    </SecondaryButton>
                                </Link>
                                <Link href={route('rm.report')}>
                                    <PrimaryButton disabled={processing}>Finnish RM Go to Templete</PrimaryButton>
                                </Link>
                            </div>
                          )}
                        </form>
                    </div>
                </div>
            </div>
            <div className={`pb-12${(!isCreateMaterial && ' hidden') || ''}`}>
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
                                <SecondaryButton type="button" onClick={toggleCreateMaterial}>
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
