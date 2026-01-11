import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SuccessButton from '@/Components/SuccessButton';
import TextInput from '@/Components/TextInput';

export default function MaterialLevelForm({
  auth,
  title,
  headerTitle,
  InputData,
  mattypes = [],
  subMattypes = [],
  uoms = [],
  components = [],
  submitRoute,
  backRoute,
  createComponentRoute
}) {
  const { data, setData, patch, processing, errors } = useForm({
    fgMaterialId: InputData?.fgMaterialId || '',
    fgBomId: InputData?.fgBomId || '',
    fgBomDesc: InputData?.fgBomDesc || '',
    mattype: InputData?.mattype || '',
    subMattype: InputData?.subMattype || '',
    materialId: InputData?.materialId || '',
    searchDesc: InputData?.searchDesc || '',
    fullDescEn: InputData?.fullDescEn || '',
    fullDescTh: InputData?.fullDescTh || '',
    uom: InputData?.uom || ''
  });

  const submit = (e) => {
    e.preventDefault();
    patch(route(submitRoute));
  };

  const goCreateComponent = () => {
    if (!createComponentRoute) {
      return;
    }
    window.open(route(createComponentRoute, {
      fgMaterialId: data.fgMaterialId,
      fgBomId: data.fgBomId,
      fgBomDesc: data.fgBomDesc
    }), '_self');
  };

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{headerTitle}</h2>}
    >
      <Head title={headerTitle} />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-2">
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="fgMaterialId" value="FG Material ID" />
                  <TextInput
                    id="fgMaterialId"
                    className="mt-1 block w-full bg-gray-100"
                    value={data.fgMaterialId}
                    disabled
                  />
                </div>
                <div>
                  <InputLabel htmlFor="fgBomId" value="FG BOM ID" />
                  <TextInput
                    id="fgBomId"
                    className="mt-1 block w-full bg-gray-100"
                    value={data.fgBomId}
                    disabled
                  />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="fgBomDesc" value="FG BOM Description" />
                  <TextInput
                    id="fgBomDesc"
                    className="mt-1 block w-full bg-gray-100"
                    value={data.fgBomDesc}
                    disabled
                  />
                </div>
                <div>
                  <InputLabel htmlFor="storageTable" value="Storage Table" />
                  <TextInput
                    id="storageTable"
                    className="mt-1 block w-full bg-gray-100"
                    value={InputData?.storageTable || ''}
                    disabled
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
                    className={`mt-1 block w-full border-gray-300 rounded-md ${data.mattype ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('mattype', e.target.value)}
                    defaultValue={data.mattype}
                    disabled={!!data.mattype}
                  >
                    <option value="">---- Select Mattype ----</option>
                    {mattypes?.map((o) => (
                      <option key={`mattype-code-${o.code}`} value={o.code}>{o.label}</option>
                    ))}
                  </select>
                  <InputError className="mt-2" message={errors.mattype} />
                </div>
                <div>
                  <InputLabel htmlFor="subMattype" value="Sub Mattype" />
                  <select
                    id="subMattype"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${data.subMattype ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('subMattype', e.target.value)}
                    defaultValue={data.subMattype}
                    disabled={!!data.subMattype}
                  >
                    <option value="">---- Select Sub Mattype ----</option>
                    {subMattypes?.map((o) => (
                      <option key={`subMattype-code-${o.code}`} value={o.code}>{o.label}</option>
                    ))}
                  </select>
                  <InputError className="mt-2" message={errors.subMattype} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="materialId" value={`${title} ID`} />
                  <TextInput
                    id="materialId"
                    className="mt-1 block w-full bg-gray-100"
                    value={data.materialId}
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
                    {uoms?.map((o) => {
                      const value = o.value ?? o.code;
                      const label = o.label ?? o.description_uom ?? value;
                      return (
                        <option key={`uom-code-${value}`} value={value}>{`${value} - ${label}`}</option>
                      );
                    })}
                  </select>
                  <InputError className="mt-2" message={errors.uom} />
                </div>
              </div>

              <fieldset className="border border-gray-300 rounded-md p-4">
                <legend className="px-2 text-gray-600">{title} Components</legend>
                <div className="flex items-center justify-end gap-4 mb-2">
                  <SuccessButton type="button" onClick={goCreateComponent} disabled={!createComponentRoute}>
                    Create Component
                  </SuccessButton>
                </div>
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                  <table className="w-full text-sm text-left rtl:text-right text-gray-800 dark:text-gray-600">
                    <thead className="text-xs bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
                      <tr>
                        <th scope="col" className="px-6 py-3">#</th>
                        <th scope="col" className="px-6 py-3">Component ID</th>
                        <th scope="col" className="px-6 py-3">Description</th>
                        <th scope="col" className="px-6 py-3" width="120">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {components.length === 0 ? (
                        <tr>
                          <td colSpan="4" className="px-6 py-4 text-gray-500">No components yet. Create one to map with FG.</td>
                        </tr>
                      ) : (
                        components.map((item, index) => (
                          <tr key={`${title}-component-${item.code}`}>
                            <th scope="row" className="px-6 py-4">{index + 1}</th>
                            <td className="px-6 py-4">{item.code}</td>
                            <td className="px-6 py-4">{item.label}</td>
                            <td className="px-6 py-4">{item.status}</td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </fieldset>

              <div className="flex items-center justify-center gap-4">
                <SecondaryButton type="button" onClick={() => window.history.back()}>
                  Back
                </SecondaryButton>
                <PrimaryButton disabled={processing}>Save</PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
