import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { Transition } from '@headlessui/react';
import Checkbox from '@/Components/Checkbox';

export default function Request({ auth, InputData, brand, mattype, company, exists }) {

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        brand: InputData?.brand || '',
        brand_abb: InputData?.brand_abb || '',
        mattype: InputData?.mattype || '',
        gtinExist: InputData?.gtinExist || undefined,
        company: InputData?.company || '',
        gtinCode: InputData?.gtinCode || '',
        gtinForPcs: InputData?.gtinForPcs || '',
        gtinForInnerOrPack: InputData?.gtinForInnerOrPack || '',
        l_product_code: InputData?.l_product_code || '',
        s_product_code: InputData?.s_product_code || ''
    });

    const handdleChange = (name, value) => {
        if (name === 'brand') {
            const newBrand = brand?.find(b => b.code === value);
            console.log(name, value, newBrand, brand);
            if (newBrand?.abb) {
                setData('brand_abb', newBrand.abb);
            }
        }
        setData(name, value);
    };

    const submit = (e) => {
        e.preventDefault();

        patch(route('request.update'));
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
                                    onChange={(e) => handdleChange('brand', e.target.value)}
                                    defaultValue={data?.brand}
                                >
                                    <option>---- Select Brand ----</option>
                                    {brand?.map(o => (
                                        <option key={`brand-code-${o.code}`} value={o.code}>{`${o.abb} - ${o.name}`}</option>
                                    ))}
                                </select>

                                <InputError className="mt-2" message={errors.brand} />
                            </div>
                            <div>
                                <InputLabel htmlFor="mattype" value="MATTYPE" />
                                <select
                                    id="mattype"
                                    className="mt-1 block w-full"
                                    onChange={(e) => handdleChange('mattype', e.target.value)}
                                    defaultValue={data?.mattype}
                                >
                                    <option>---- Select MATTYPE ----</option>
                                    {mattype?.map(o => (
                                        <option key={`mattype-code-${o.code}`} value={o.code}>{`${o.code} - ${o.name}`}</option>
                                    ))}
                                </select>

                                <InputError className="mt-2" message={errors.mattype} />
                            </div>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <InputLabel htmlFor="prefixCode" value="Product Code Prefix" />

                                <TextInput
                                    id="prefixCode"
                                    className="mt-1 block w-full bg-gray-100"
                                    value={`${data?.brand_abb}${data?.mattype}`}
                                    disabled
                                />
                            </div>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <InputLabel htmlFor="latestProductCode" value="Latest Product code" />

                                <TextInput
                                    id="latestProductCode"
                                    className="mt-1 block w-full bg-gray-100"
                                    value={`${data?.l_product_code}`}
                                    disabled
                                />
                            </div>
                            <div>
                                <InputLabel htmlFor="suggestProductCode" value="Suggest Product code" />

                                <TextInput
                                    id="suggestProductCode"
                                    className="mt-1 block w-full bg-gray-100"
                                    value={`${data?.s_product_code}`}
                                    disabled
                                />
                            </div>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <label className="flex items-center">
                                <Checkbox
                                    name="gtinExist"
                                    checked={data?.gtinExist}
                                    value="Y"
                                    onChange={(e) => setData('gtinExist', e.target.checked)}
                                />
                                <span className="ml-2">GTIN Existing</span>
                            </label>
                            {data?.gtinExist && (
                                <div>
                                    <InputLabel htmlFor="gtinCode" value="GTIN Code" />

                                    <TextInput
                                        id="gtinCode"
                                        className="mt-1 block w-full"
                                        value={data.gtinCode}
                                        onChange={(e) => setData('gtinCode', e.target.value)}
                                        isFocused
                                    />

                                    <InputError className="mt-2" message={errors.gtinCode} />
                                </div>
                            )}
                          </div>
                          {typeof data?.gtinExist === 'boolean' && !data?.gtinExist && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div>
                                    <InputLabel htmlFor="company" value="Company Code" />
                                    <select
                                        id="company"
                                        className="mt-1 block w-full"
                                        onChange={(e) => setData('company', e.target.value)}
                                        defaultValue={data?.company}
                                    >
                                        <option>---- Select Company ----</option>
                                        {company?.map(o => (
                                            <option key={`company-code-${o.code}`} value={o.code}>{`${o.code} - ${o.name}`}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <InputLabel htmlFor="gtinPrefix" value="GTIN Prefix" />

                                    <TextInput
                                        id="gtinPrefix"
                                        className="mt-1 block w-full bg-gray-100"
                                        value={data.company}
                                        disabled
                                    />
                                </div>
                            </div>
                          )}
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <InputLabel htmlFor="gtinForPcs" value="GTIN For Pcs" />
                                <select
                                    id="gtinForPcs"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('gtinForPcs', e.target.value)}
                                    defaultValue={data?.gtinForPcs}
                                >
                                    <option>---- Select Choice ----</option>
                                    {exists?.map(o => (
                                        <option key={`exists-code-${o.code}`} value={o.code}>{o.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <InputLabel htmlFor="gtinForInnerOrPack" value="GTIN For Inner / Pack" />
                                <select
                                    id="gtinForInnerOrPack"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('gtinForInnerOrPack', e.target.value)}
                                    defaultValue={data?.gtinForInnerOrPack}
                                >
                                    <option>---- Select Choice ----</option>
                                    {exists?.map(o => (
                                        <option key={`exists-code-${o.code}`} value={o.code}>{o.name}</option>
                                    ))}
                                </select>
                            </div>
                          </div>
                          {data?.gtinForPcs === 'Y' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                              <div>
                                  <InputLabel htmlFor="latestGTINCodeForPcs" value="Latest GTIN Code for Pcs" />

                                  <TextInput
                                      id="latestGTINCodeForPcs"
                                      className="mt-1 block w-full bg-gray-100"
                                      value={`${data?.company}XXXXXX`}
                                      disabled
                                  />
                              </div>
                              <div>
                                  <InputLabel htmlFor="suggestGTINCodeForPcs" value="Suggest GTIN Code for Pcs" />

                                  <TextInput
                                      id="suggestGTINCodeForPcs"
                                      className="mt-1 block w-full bg-gray-100"
                                      value={`${data?.company}XXXXXX`}
                                      disabled
                                  />
                              </div>
                            </div>
                          )}
                          {data?.gtinForInnerOrPack === 'Y' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                              <div>
                                  <InputLabel htmlFor="latestGTINCodeForInnerOrPack" value="Latest GTIN Code for Inner / Pack" />

                                  <TextInput
                                      id="latestGTINCodeForInnerOrPack"
                                      className="mt-1 block w-full bg-gray-100"
                                      value={`${data?.company}XXXXXX`}
                                      disabled
                                  />
                              </div>
                              <div>
                                  <InputLabel htmlFor="suggestGTINCodeForInnerOrPack" value="Suggest GTIN Code for Inner / Pack" />

                                  <TextInput
                                      id="suggestGTINCodeForInnerOrPack"
                                      className="mt-1 block w-full bg-gray-100"
                                      value={`${data?.company}XXXXXX`}
                                      disabled
                                  />
                              </div>
                            </div>
                          )}

                            <div className="flex items-center justify-center gap-4">
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
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
