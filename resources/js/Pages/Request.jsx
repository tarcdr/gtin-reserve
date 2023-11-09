import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { Transition } from '@headlessui/react';
import Checkbox from '@/Components/Checkbox';
import Radio from '@/Components/Radio';

export default function Request({ auth, InputData, brand, mattype, company }) {

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        brand: InputData?.brand || '',
        mattype: InputData?.mattype || '',
        gtinExistPcs: InputData?.gtinExistPcs === 1 || false,
        gtinExistPack: InputData?.gtinExistPack === 1 || false,
        company: InputData?.company || '',
        gtinCode: InputData?.gtinCode || '',
        gtinForPcs: InputData?.gtinForPcs || '',
        gtinForInnerOrPack: InputData?.gtinForInnerOrPack || '',
        product_code_choose: 'l'
    });

    const handdleChange = (name, value) => {
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
                                    value={`${brand.filter(b => b.code === data?.brand).map(b => b.abb).join('')}${data?.mattype}`}
                                    disabled
                                />
                            </div>
                          </div>
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div className="grid grid-cols-4 gap-2">
                                <label className="flex items-center justify-center">
                                    <Radio
                                        name="product_code_choose"
                                        checked={data?.product_code_choose === 'l'}
                                        value="l"
                                        onChange={(e) => handdleChange('product_code_choose', e.target.value)}
                                    />
                                    <span className="ml-2">Choose</span>
                                </label>
                                <div className="col-span-3">
                                    <InputLabel htmlFor="l_product_code" value="Latest Product code" />

                                    <TextInput
                                        id="l_product_code"
                                        className="mt-1 block w-full bg-gray-100"
                                        value={`${InputData?.l_product_code}`}
                                        disabled
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-4 gap-2">
                                <label className="flex items-center justify-center">
                                    <Radio
                                        name="product_code_choose"
                                        checked={data?.product_code_choose === 's'}
                                        value="s"
                                        onChange={(e) => handdleChange('product_code_choose', e.target.value)}
                                    />
                                    <span className="ml-2">Choose</span>
                                </label>
                                <div className="col-span-3">
                                    <InputLabel htmlFor="s_product_code" value="Suggest Product code" />

                                    <TextInput
                                        id="s_product_code"
                                        className="mt-1 block w-full bg-gray-100"
                                        value={`${InputData?.s_product_code}`}
                                        disabled
                                    />
                                </div>
                            </div>
                          </div>
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
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div className="grid grid-cols-1 gap-4">
                              <label className="flex items-center">
                                  <Checkbox
                                      name="gtinExistPcs"
                                      checked={data?.gtinExistPcs}
                                      onChange={(e) => setData('gtinExistPcs', e.target.checked)}
                                  />
                                  <span className="ml-2">GTIN Pcs Existing</span>
                              </label>
                              {data?.gtinExistPcs && (
                                  <div>
                                      <InputLabel htmlFor="gtinPcsCode" value="GTIN Pcs Code" />

                                      <TextInput
                                          id="gtinPcsCode"
                                          className="mt-1 block w-full"
                                          value={data.gtinPcsCode}
                                          onChange={(e) => setData('gtinPcsCode', e.target.value)}
                                          isFocused
                                      />

                                      <InputError className="mt-2" message={errors.gtinPcsCode} />
                                  </div>
                              )}
                              <div className="grid grid-cols-4 gap-2">
                                <label className="flex items-center">
                                    <Radio
                                        name="gtinPcsChoose"
                                        checked={data?.gtinPcsChoose === 'l'}
                                        value="l"
                                        onChange={(e) => handdleChange('gtinPcsChoose', e.target.value)}
                                    />
                                    <span className="ml-2">Choose</span>
                                </label>
                                <div className="col-span-3">
                                    <InputLabel htmlFor="latestGTINCodeForPcs" value="Latest GTIN Code for Pcs" />

                                    <TextInput
                                        id="latestGTINCodeForPcs"
                                        className="mt-1 block w-full bg-gray-100"
                                        value={`${data?.company}XXXXXX`}
                                        disabled
                                    />
                                </div>
                              </div>
                              <div className="grid grid-cols-4 gap-2">
                                <label className="flex items-center">
                                    <Radio
                                        name="gtinPcsChoose"
                                        checked={data?.gtinPcsChoose === 's'}
                                        value="s"
                                        onChange={(e) => handdleChange('gtinPcsChoose', e.target.value)}
                                    />
                                    <span className="ml-2">Choose</span>
                                </label>
                                <div className="col-span-3">
                                    <InputLabel htmlFor="suggestGTINCodeForPcs" value="Suggest GTIN Code for Pcs" />

                                    <TextInput
                                        id="suggestGTINCodeForPcs"
                                        className="mt-1 block w-full bg-gray-100"
                                        value={`${data?.company}XXXXXX`}
                                        disabled
                                    />
                                </div>
                              </div>
                            </div>
                            <div className="grid grid-cols-1 gap-4">
                              <label className="flex items-center">
                                  <Checkbox
                                      name="gtinExistPack"
                                      checked={data?.gtinExistPack}
                                      onChange={(e) => setData('gtinExistPack', e.target.checked)}
                                  />
                                  <span className="ml-2">GTIN Pack Existing</span>
                              </label>
                              {data?.gtinExistPack && (
                                  <div>
                                      <InputLabel htmlFor="gtinPackCode" value="GTIN Pack Code" />

                                      <TextInput
                                          id="gtinPackCode"
                                          className="mt-1 block w-full"
                                          value={data.gtinPackCode}
                                          onChange={(e) => setData('gtinPackCode', e.target.value)}
                                          isFocused
                                      />

                                      <InputError className="mt-2" message={errors.gtinPackCode} />
                                  </div>
                              )}
                              <div className="grid grid-cols-4 gap-2">
                                <label className="flex items-center">
                                    <Radio
                                        name="gtinPackChoose"
                                        checked={data?.gtinPackChoose === 'l'}
                                        value="l"
                                        onChange={(e) => handdleChange('gtinPackChoose', e.target.value)}
                                    />
                                    <span className="ml-2">Choose</span>
                                </label>
                                <div className="col-span-3">
                                    <InputLabel htmlFor="latestGTINCodeForInnerOrPack" value="Latest GTIN Code for Inner / Pack" />

                                    <TextInput
                                        id="latestGTINCodeForInnerOrPack"
                                        className="mt-1 block w-full bg-gray-100"
                                        value={`${data?.company}XXXXXX`}
                                        disabled
                                    />
                                </div>
                              </div>
                              <div className="grid grid-cols-4 gap-2">
                                <label className="flex items-center">
                                    <Radio
                                        name="gtinPackChoose"
                                        checked={data?.gtinPackChoose === 's'}
                                        value="s"
                                        onChange={(e) => handdleChange('gtinPackChoose', e.target.value)}
                                    />
                                    <span className="ml-2">Choose</span>
                                </label>
                                <div className="col-span-3">
                                  <InputLabel htmlFor="suggestGTINCodeForInnerOrPack" value="Suggest GTIN Code for Inner / Pack" />

                                  <TextInput
                                      id="suggestGTINCodeForInnerOrPack"
                                      className="mt-1 block w-full bg-gray-100"
                                      value={`${data?.company}XXXXXX`}
                                      disabled
                                  />
                                </div>
                              </div>
                            </div>
                          </div>

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
