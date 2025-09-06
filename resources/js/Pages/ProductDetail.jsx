import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';
import SuccessButton from '@/Components/SuccessButton';
import DangerButton from '@/Components/DangerButton';
import { useState } from 'react';
import { useEffect } from 'react';

export default function ProductDetail({ auth, InputData, isDisabled = true, brands = [], mattypes = [], sites = [], masterUom = [], finishGoods = [] }) {
  const [showSite, setShowSite] = useState(false);
  const [showBomId, setShowBomId] = useState(false);
  const { data, setData, patch, errors, processing } = useForm({
    brand: InputData?.brand || '',
    mattype: InputData?.mattype || '',
    subMattype: InputData?.subMattype || '',
    materialId: InputData?.materialId || '',
    bomId: InputData?.bomId || '',
    bomDesc: InputData?.bomDesc || '',
    uom: InputData?.uom || '',
    finishGoods: InputData?.finishGoods || '',
    fullDescEn: InputData?.fullDescEn || '',
    fullDescTh: InputData?.fullDescTh || '',
    searchDesc: InputData?.searchDesc || '',
    productGroup: InputData?.productGroup || '',
    site: InputData?.site || ''
  });

  const goToPackMaterial = () => {
    router.post('/packmaterial/new', { bomId: data.bomId, bomDesc: data.bomDesc });
  };

  const submit = (e) => {
    e.preventDefault();
    patch(route('product.create'));
  };
  
  useEffect(() => {
    let dispSite = false;
    const mattype = mattypes.find(mat => mat.code === data.mattype);
    if (mattype && mattype?.showSite) {
      dispSite = true;
    }
    let dispBomId = false;
    const mattype2 = mattypes.find(mat => mat.code === data.mattype);
    if (mattype2 && mattype2?.showBomId) {
      dispBomId = true;
    }
    setShowSite(dispSite);
    setShowBomId(dispBomId);
  }, [data.mattype]);

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">FG Material - Product Detail</h2>}
    >
      <Head title="FG Material - Product Detail" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <form onSubmit={submit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="brand" value="Brand" />
                  <select
                    id="brand"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('brand', e.target.value)}
                    defaultValue={data.brand}
                    disabled={isDisabled}
                  >
                    <option value="">---- Select Brand ----</option>
                    {brands?.map(o => (
                      <option key={`brand-code-${o.code}`} value={o.code}>{`${o.abb} - ${o.code}`}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.brand} />
                </div>
                <div>
                  <InputLabel htmlFor="mattype" value="Mattype" />
                  <select
                    id="mattype"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('mattype', e.target.value)}
                    defaultValue={data.mattype}
                    disabled={isDisabled}
                  >
                    <option value="">---- Select Mattype ----</option>
                    {mattypes?.map(o => (
                      <option key={`mattype-code-${o.code}`} value={o.code}>{o.label}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.mattype} />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="subMattype" value="Sub Mattype" />
                  <select
                    id="subMattype"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('subMattype', e.target.value)}
                    defaultValue={data.subMattype}
                    disabled={isDisabled}
                  >
                    <option value="">---- Select Sub Mattype ----</option>
                    {brands?.map(o => (
                      <option key={`subMattype-code-${o.code}`} value={o.code}>{`${o.abb} - ${o.code}`}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.subMattype} />
                </div>
                <div>
                  <InputLabel htmlFor="productGroup" value="Product Group" />
                  <select
                    id="productGroup"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('productGroup', e.target.value)}
                    defaultValue={data.productGroup}
                    disabled={isDisabled}
                  >
                    <option value="">---- Select Product Group ----</option>
                    {brands?.map(o => (
                      <option key={`productGroup-code-${o.code}`} value={o.code}>{`${o.abb} - ${o.code}`}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.productGroup} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="finishGoods" value="Finish Goods" />
                  <select
                    id="finishGoods"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('finishGoods', e.target.value)}
                    defaultValue={data.finishGoods}
                    disabled={isDisabled}
                  >
                    <option value="">---- Select Finish Goods ----</option>
                    {finishGoods?.map(o => (
                      <option key={`finishGoods-code-${o.code}`} value={o.code}>{`${o.code} - ${o.name}`}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.finishGoods} />
                </div>
                {showSite && (
                  <div>
                    <InputLabel htmlFor="site" value="Site" />
                    <select
                      id="site"
                      className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                      onChange={(e) => setData('site', e.target.value)}
                      defaultValue={data.site}
                      disabled={isDisabled}
                    >
                      <option value="">---- Select Site ----</option>
                      {sites?.map(o => (
                        <option key={`site-code-${o.value}`} value={o.value}>{`${o.value} - ${o.label}`}</option>
                      ))}
                    </select>

                    <InputError className="mt-2" message={errors.site} />
                  </div>
                )}
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="materialId" value="Suggest Material ID" />

                  <TextInput
                    id="materialId"
                    className="mt-1 block w-full bg-gray-100"
                    disabled
                  />
                </div>
              </div>
              {showBomId && (
                <>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                      <InputLabel htmlFor="bomId" value="BOM ID For FG" />

                      <TextInput
                        id="bomId"
                        className="mt-1 block w-full bg-gray-100"
                        disabled
                        defaultValue={data.bomId}
                      />
                    </div>
                    <div>
                      <InputLabel htmlFor="bomDesc" value="Description of BOM ID" />

                      <TextInput
                        id="bomDesc"
                        className="mt-1 block w-full"
                        value={data.bomDesc}
                        onChange={(e) => setData('bomDesc', e.target.value)}
                      />
                    </div>
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                      <InputLabel htmlFor="bomStatus" value="BOM Status" />

                      <TextInput
                        id="bomStatus"
                        className="mt-1 block w-full bg-gray-100"
                        disabled
                        defaultValue="INS"
                      />
                    </div>
                  </div>
                </>
              )}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="searchDesc" value="Search Description" />

                  <TextInput
                    id="searchDesc"
                    className="mt-1 block w-full border-gray-300 rounded-md"
                    value={data.searchDesc}
                    maxLength="40"
                    onChange={(e) => setData('searchDesc', e.target.value)}
                  />

                  <InputError className="mt-2" message={errors.searchDesc} />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="fullDescEn" value="Full Description (EN)" />

                  <TextInput
                    id="fullDescEn"
                    className="mt-1 block w-full border-gray-300 rounded-md"
                    value={data.fullDescEn}
                    maxLength="40"
                    onChange={(e) => setData('fullDescEn', e.target.value)}
                  />

                  <InputError className="mt-2" message={errors.fullDescEn} />
                </div>
                <div>
                  <InputLabel htmlFor="fullDescTh" value="Full Description (TH)" />

                  <TextInput
                    id="fullDescTh"
                    className="mt-1 block w-full border-gray-300 rounded-md"
                    value={data.fullDescTh}
                    maxLength="40"
                    onChange={(e) => setData('fullDescTh', e.target.value)}
                  />

                  <InputError className="mt-2" message={errors.fullDescTh} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="uom" value="UOM" />
                  <select
                    id="uom"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('uom', e.target.value)}
                    defaultValue={data.uom}
                    disabled={isDisabled}
                  >
                    <option value="">---- Select UOM ----</option>
                    {masterUom?.map(o => (
                      <option key={`uom-code-${o.value}`} value={o.value}>{o.label}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.uom} />
                </div>
              </div>
              <fieldset className="border border-gray-300 rounded-md p-4 mt-8">
                <legend className="px-2 text-gray-600">Components</legend>
                <div className="flex items-center justify-end gap-4 mb-2">
                    <SuccessButton type="button" onClick={goToPackMaterial}>Add Component</SuccessButton>
                </div>
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                  <table className="w-full text-sm text-left rtl:text-right text-gray-800 dark:text-gray-600">
                    <thead className="text-xs bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
                        <tr>
                            <th scope="col" className="px-6 py-3">
                                #
                            </th>
                            <th scope="col" className="px-6 py-3">
                                Component ID
                            </th>
                            <th scope="col" className="px-6 py-3">
                                Description
                            </th>
                            <th scope="col" className="px-6 py-3" width="100">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <th scope="row" className="px-6 py-4">
                          1
                        </th>
                        <th scope="row" className="px-6 py-4">
                          56000001
                        </th>
                        <th scope="row" className="px-6 py-4">
                          LLLLLLLL
                        </th>
                        <td className="px-6 py-4 flex gap-2">
                          <DangerButton type="button">DELETE</DangerButton>
                          <PrimaryButton type="button">Edit</PrimaryButton>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </fieldset>
              {data?.mattype === '1' && (
                <div className="space-y-6 border p-3 border-gray-300 sm:rounded-lg">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                      <InputLabel htmlFor="materialIdLv2" value="Material ID Semi FG Lv.2" />

                      <TextInput
                        id="materialIdLv2"
                        className="mt-1 block w-full bg-gray-100"
                        value="KEY_OF_FG_LV_2"
                        disabled
                      />
                    </div>
                    <div>
                      <InputLabel htmlFor="materialIdLv2Desc" value="Description of Lv.2" />

                      <TextInput
                        id="materialIdLv2Desc"
                        className="mt-1 block w-full bg-gray-100"
                        value="DESC_OF_FG_LV_2"
                        disabled
                      />
                    </div>
                  </div>
                  <div className="flex gap-4">
                    {/* <SuccessButton type="button">Add FG Lv.2</SuccessButton> */}
                    <PrimaryButton type="button">Edit FG Lv.2</PrimaryButton>
                  </div>
                </div>
              )}
              <div>
                <SuccessButton type="button">Create Semi FG Lelve 1</SuccessButton>
              </div>
              <div>
                <SuccessButton type="button">Create Business Supply</SuccessButton>
              </div>
              <div className="flex items-center justify-center gap-4">
                <Link href={route('dashboard')}>
                  <SecondaryButton>
                    Back
                  </SecondaryButton>
                </Link>
                <PrimaryButton disabled={processing}>Edit FG</PrimaryButton>
                <DangerButton type="button">DELETE FG</DangerButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
