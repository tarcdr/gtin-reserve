import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';
import ReactSelect from 'react-select';
import SuccessButton from '@/Components/SuccessButton';
import DangerButton from '@/Components/DangerButton';

export default function ProductDetail({ auth, InputData, isDisabled = true, brands = [], mattypes = [], sites = [], materials = [] }) {
  const [showSite, setShowSite] = useState(false);
  const [showBomId, setShowBomId] = useState(false);
  const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
    brand: InputData?.brand || '',
    mattype: InputData?.mattype || '',
    subMattype: InputData?.subMattype || '',
    productGroup: InputData?.productGroup || '',
    finishGoods: InputData?.finishGoods || '',
    site: InputData?.site || '',
    materialId: InputData?.materialId || '',
    bomId: InputData?.bomId || '',
    materialDesc: InputData?.materialDesc || '',
    fullDescEn: InputData?.fullDescEn || '',
    fullDescTh: InputData?.fullDescTh || '',
    uom: InputData?.uom || ''
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
                      <option key={`subMattype-code-${o.code}`} value={o.code}>{`${o.code} - ${o?.label || o.code}`}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.subMattype} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="materialId" value="Material ID FG" />
                  <select
                    id="materialId"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('materialId', e.target.value)}
                    defaultValue={data.materialId}
                    disabled={isDisabled}
                  >
                    <option value="">---- Select Material ID FG ----</option>
                    {materials?.map(o => (
                      <option key={`materialId-code-${o.code}`} value={o.code}>{`${o.code} - ${o.label}`}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.materialId} />
                </div>
                <div>
                  <InputLabel htmlFor="bomId" value="Material Status" />

                  <TextInput
                    id="bomId"
                    className="mt-1 block w-full bg-gray-100"
                    disabled
                    defaultValue="INS"
                  />
                </div>
              </div>
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
                  <InputLabel htmlFor="bomDesc" value="Description of BOM ID" />

                  <TextInput
                    id="bomDesc"
                    className="mt-1 block w-full"
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
                    {brands?.map(o => (
                      <option key={`uom-code-${o.code}`} value={o.code}>{`${o.abb} - ${o.code}`}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.uom} />
                </div>
              </div>
              {data?.mattype === '1' && (
                <div className="space-y-6 border p-3 border-gray-300 sm:rounded-lg">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                      <InputLabel htmlFor="materialIdLv2" value="Material ID Semi FG Lv.2" />

                      <TextInput
                        id="materialIdLv2"
                        className="mt-1 block w-full bg-gray-100"
                        disabled
                      />
                    </div>
                    <div>
                      <InputLabel htmlFor="materialIdLv2Desc" value="Description of Lv.2" />

                      <TextInput
                        id="materialIdLv2Desc"
                        className="mt-1 block w-full"
                      />
                    </div>
                  </div>
                  <div className="flex gap-4">
                    <SuccessButton type="button">Add FG Lv.2</SuccessButton>
                    <PrimaryButton type="button">Edit FG Lv.2</PrimaryButton>
                  </div>
                </div>
              )}
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
