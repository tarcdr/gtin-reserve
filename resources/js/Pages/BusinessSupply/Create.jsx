import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { useState } from 'react';

export default function Request({ auth, InputData }) {
  const [subMatType] = useState([0, 1, 2, 3, 4]);
  const [isMore, setMore] = useState(false);
  const [isCreateMaterial, setIsCreateMaterial] = useState(false);
  const [materialList, setMaterialList] = useState([{
    code: '1',
    label: 'Item 1'
  }]);

  const { data, setData, patch, errors, processing, recentlySuccessful, transform } = useForm({
    bom: InputData?.bom || '',
    matType: '6'
  });

  transform(dataSet => ({
    ...dataSet,
    bom: InputData?.bom
  }));

  const toggleCreateMaterial = () => {
    setIsCreateMaterial(!isCreateMaterial);
  };

  const handleRemove = code => {
    const newList = materialList.filter(o => o.code !== code);
    setMaterialList(newList);
  };

  const submit = (e) => {
    e.preventDefault();

    patch(route('bns.create'));
  };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Business Supply - Create</h2>}
        >
            <Head title="Business Supply - Create" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                              <InputLabel htmlFor="fgCode" value="FG Code" />
                              <TextInput
                                  id="fgCode"
                                  className="mt-1 block w-full"
                                  defaultValue={data.fgCode}
                                  disabled
                              />
                            </div>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                              <InputLabel htmlFor="brand" value="Brand" />
                              <TextInput
                                  id="brand"
                                  className="mt-1 block w-full"
                                  defaultValue={data.brand}
                                  disabled
                              />
                            </div>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                              <InputLabel htmlFor="finishGoodType" value="Finish Good Type" />
                              <TextInput
                                  id="finishGoodType"
                                  className="mt-1 block w-full"
                                  defaultValue={data.finishGoodType}
                                  disabled
                              />
                            </div>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                              <InputLabel htmlFor="matType" value="Mat Type" />
                              <TextInput
                                  id="matType"
                                  className="mt-1 block w-full"
                                  defaultValue={data.matType}
                                  disabled
                              />
                            </div>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <InputLabel htmlFor="subMatType" value="Sub Mat Type" />
                                <select
                                    id="subMatType"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('subMatType', e.target.value)}
                                    defaultValue={data?.subMatType}
                                >
                                    <option value="">---- Select Brand ----</option>
                                    {subMatType?.map(o => (
                                        <option key={`subMatType-code-${o}`} value={o}>{o}</option>
                                    ))}
                                </select>

                                <InputError className="mt-2" message={errors.subMatType} />
                            </div>
                          </div>
                          {isMore && (
                            <>
                              <h5>Component (Spare Part / Sample / Premium / Premium(non-value))</h5>
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
                            </>
                          )}
                          <div className={`flex items-center justify-center gap-4${isMore ? ' hidden' : ''}`}>
                            <PrimaryButton type="button" onClick={() => setMore(true)}>Next</PrimaryButton>
                          </div>
                          <div className={`flex items-center justify-center gap-4${isMore ? '' : ' hidden'}`}>
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
                          <div className="flex items-center justify-center gap-4">
                            <Link href={route('rm.report')}>
                              <PrimaryButton type="button">Go To Templete</PrimaryButton>
                            </Link>
                          </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
