import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';

export default function NewPackMaterial({ auth, InputData, mattypes = [], subMattypes = [], uoms = [] }) {
  const { data, setData, patch, errors, processing } = useForm({
    bomId: InputData?.bomId || '',
    bomDesc: InputData?.bomDesc || '',
    mattype: InputData?.mattype || '',
    subMattype: InputData?.subMattype || '',
    componentId: '',
    searchDesc: '',
    fullDescEn: '',
    fullDescTh: '',
    uom: ''
  });

  const submit = (e) => {
    e.preventDefault();
    patch(route('packmaterial.create'));
  };

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">PACK MATERIAL - Create</h2>}
    >
      <Head title="PACK MATERIAL - Create" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-2">
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="bomId" value="New BOM ID" />

                  <TextInput
                    id="bomId"
                    className="mt-1 block w-full bg-gray-100"
                    disabled
                    defaultValue={data.bomId}
                  />
                </div>
                <div>
                  <InputLabel htmlFor="bomId" value="Description" />

                  <TextInput
                    id="bomId"
                    className="mt-1 block w-full bg-gray-100"
                    disabled
                    defaultValue={data.bomDesc}
                  />
                </div>
              </div>
            </div>
          </div>
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <form onSubmit={submit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="mattype" value="Mattype" />
                  <select
                    id="mattype"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${data?.mattype !== '' ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('mattype', e.target.value)}
                    defaultValue={data.mattype}
                    disabled={data?.mattype !== ''}
                  >
                    <option value="">---- Select Mattype ----</option>
                    {mattypes?.map(o => (
                      <option key={`mattype-code-${o.code}`} value={o.code}>{o.label}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.mattype} />
                </div>
                <div>
                  <InputLabel htmlFor="subMattype" value="Sub Mattype" />
                  <select
                    id="subMattype"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${data?.subMattype !== '' ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('subMattype', e.target.value)}
                    defaultValue={data.subMattype}
                    disabled={data?.subMattype !== ''}
                  >
                    <option value="">---- Select Sub Mattype ----</option>
                    {subMattypes?.map(o => (
                      <option key={`subMattype-code-${o.code}`} value={o.code}>{`${o.label}`}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.subMattype} />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="componentId" value="Component ID" />

                  <TextInput
                    id="componentId"
                    className="mt-1 block w-full bg-gray-100"
                    disabled
                  />
                </div>
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
                    {uoms?.map(o => (
                      <option key={`uom-code-${o.code}`} value={o.code}>{`${o.code} - ${o.label}`}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.uom} />
                </div>
              </div>
              <div className="flex items-center justify-center gap-4">
                <Link href={route('dashboard')}>
                  <SecondaryButton>
                    Back TO BOM
                  </SecondaryButton>
                </Link>
                <PrimaryButton disabled={processing}>Save</PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
