import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Request({ auth, InputData, brands = [], mattypes = [], sites = [] }) {
  const [showSite, setShowSite] = useState(false);
  const [showBomId, setShowBomId] = useState(false);
  const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
    brand: '',
    mattype: '',
    subMattype: '',
    productGroup: '',
    finishGoods: '',
    site: '',
    materialId: '',
    bomId: '',
    searchDesc: '',
    fullDescEn: '',
    fullDescTh: '',
    uom: ''
  });

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

  useEffect(() => {
    if (recentlySuccessful && InputData?.success) {
      setTimeout(() => {
        window.open('/material/report', '_self');
      }, 500);
    }
  }, [recentlySuccessful]);

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">FG Material - Create New Product</h2>}
    >
      <Head title="FG Material - Create New Product" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <form onSubmit={submit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="brand" value="Brand" />
                  <select
                    id="brand"
                    className="mt-1 block w-full border-gray-300 rounded-md"
                    onChange={(e) => setData('brand', e.target.value)}
                    defaultValue={data.brand}
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
                    className="mt-1 block w-full border-gray-300 rounded-md"
                    onChange={(e) => setData('mattype', e.target.value)}
                    defaultValue={data.mattype}
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
                    className="mt-1 block w-full border-gray-300 rounded-md"
                    onChange={(e) => setData('subMattype', e.target.value)}
                    defaultValue={data.subMattype}
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
                    className="mt-1 block w-full border-gray-300 rounded-md"
                    onChange={(e) => setData('productGroup', e.target.value)}
                    defaultValue={data.productGroup}
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
                    className="mt-1 block w-full border-gray-300 rounded-md"
                    onChange={(e) => setData('finishGoods', e.target.value)}
                    defaultValue={data.finishGoods}
                  >
                    <option value="">---- Select Finish Goods ----</option>
                    {brands?.map(o => (
                      <option key={`finishGoods-code-${o.code}`} value={o.code}>{`${o.abb} - ${o.code}`}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.finishGoods} />
                </div>
                {showSite && (
                  <div>
                    <InputLabel htmlFor="site" value="Site" />
                    <select
                      id="site"
                      className="mt-1 block w-full border-gray-300 rounded-md"
                      onChange={(e) => setData('site', e.target.value)}
                      defaultValue={data.site}
                    >
                      <option value="">---- Select Site ----</option>
                      {sites?.map(o => (
                        <option key={`site-code-${o.code}`} value={o.code}>{o.label}</option>
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
                {showBomId && (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                      <InputLabel htmlFor="bomId" value="BOM ID For FG" />

                      <TextInput
                        id="bomId"
                        className="mt-1 block w-full bg-gray-100"
                        disabled
                      />
                    </div>
                    <div>
                      <InputLabel htmlFor="bomId" value="Description of BOM ID" />

                      <TextInput
                        id="bomId"
                        className="mt-1 block w-full bg-gray-100"
                        disabled
                      />
                    </div>
                  </div>
                )}
              </div>
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
                    className="mt-1 block w-full border-gray-300 rounded-md"
                    onChange={(e) => setData('uom', e.target.value)}
                    defaultValue={data.uom}
                  >
                    <option value="">---- Select UOM ----</option>
                    {brands?.map(o => (
                      <option key={`uom-code-${o.code}`} value={o.code}>{`${o.abb} - ${o.code}`}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.uom} />
                </div>
              </div>
              <div className="flex items-center justify-center gap-4">
                <Link href={route('dashboard')}>
                  <SecondaryButton>
                    Back
                  </SecondaryButton>
                </Link>
                <PrimaryButton disabled={processing}>Save FG</PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
