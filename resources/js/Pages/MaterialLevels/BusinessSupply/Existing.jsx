import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import ReactSelect from 'react-select';
import SecondaryButton from '@/Components/SecondaryButton';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import { useMemo } from 'react';
import BusinessSupplyDetail from '@/Components/BusinessSupply/BusinessSupplyDetail';
import BusinessSupplyComponents from '@/Components/BusinessSupply/BusinessSupplyComponents';

export default function BusinessSupplyExisting({
  auth,
  InputData,
  businessSupplies = [],
  selectedBusinessSupply = null,
}) {
  const selectedBizsupId = InputData?.bizsupId || selectedBusinessSupply?.bizsupId || '';
  const selectedBizsupDesc = InputData?.bizsupDesc || selectedBusinessSupply?.bizsupDesc || '';
  const isLocked = Boolean(selectedBizsupId);

  const detailValues = useMemo(() => ({
    bomBsId: selectedBusinessSupply?.record?.bomBsId || InputData?.bomBsId || '',
    bsId: selectedBusinessSupply?.record?.bsId || InputData?.bsId || selectedBizsupId,
    searchDesc: selectedBusinessSupply?.record?.searchDesc || InputData?.searchDesc || '',
    compDescEn: selectedBusinessSupply?.record?.compDescEn || InputData?.compDescEn || '',
    compDescTh: selectedBusinessSupply?.record?.compDescTh || InputData?.compDescTh || '',
    uom: selectedBusinessSupply?.record?.uom || InputData?.uom || '',
    sourceLabel: selectedBusinessSupply?.record?.sourceLabel || selectedBizsupDesc || InputData?.sourceLabel || '',
    fgMaterialId: InputData?.fgMaterialId || '',
    site: InputData?.site || '',
  }), [InputData, selectedBusinessSupply, selectedBizsupDesc, selectedBizsupId]);

  const components = selectedBusinessSupply?.record?.components || InputData?.components || [];

  const selectedBizsupOption = businessSupplies.find((item) => item.value === selectedBizsupId) || (
    selectedBizsupId
      ? { value: selectedBizsupId, label: selectedBizsupDesc ? `${selectedBizsupId} - ${selectedBizsupDesc}` : selectedBizsupId }
      : null
  );

  const handleCancel = () => {
    router.get(route('business-supply.existing'));
  };

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">EXISTING BUSINESS SUPPLY</h2>}
    >
      <Head title="Existing Business Supply" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div className="space-y-6">
              <div className="text-sm text-gray-500">
                Select a Business Supply ID to view the saved record and component list.
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="bizsupId" value="Business Supply Id" />
                  <ReactSelect
                    inputId="bizsupId"
                    options={businessSupplies}
                    isSearchable
                    placeholder="---- Select Business Supply Id ----"
                    value={selectedBizsupOption}
                    isDisabled={isLocked}
                    onChange={(option) => {
                      router.get(route('business-supply.existing', {
                        bizsupId: option?.value || '',
                      }));
                    }}
                    getOptionLabel={(option) => option.label}
                    getOptionValue={(option) => option.value}
                    classNames={{
                      control: () => 'mt-1 block w-full',
                    }}
                  />
                </div>
                <div />
              </div>

              {selectedBizsupId ? (
                <>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                      <InputLabel htmlFor="detailFgMaterialId" value="Material ID FG" />
                      <TextInput id="detailFgMaterialId" className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100" value={InputData?.fgMaterialId || ''} disabled />
                    </div>
                    <div>
                      <InputLabel htmlFor="detailSite" value="Site" />
                      <TextInput id="detailSite" className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100" value={InputData?.site || ''} disabled />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                      <InputLabel htmlFor="detailMatType" value="Mattype" />
                      <TextInput id="detailMatType" className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100" value={InputData?.matType || ''} disabled />
                    </div>
                    <div>
                      <InputLabel htmlFor="detailSubMatType" value="Sub Mattype" />
                      <TextInput id="detailSubMatType" className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100" value={InputData?.subMatType || ''} disabled />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                      <InputLabel htmlFor="detailBrand" value="Brand" />
                      <TextInput id="detailBrand" className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100" value={InputData?.brand || ''} disabled />
                    </div>
                    <div />
                  </div>

                  <BusinessSupplyDetail
                    mode="existing"
                    values={detailValues}
                    sourceLabel={detailValues.sourceLabel}
                    showActions={false}
                  />

                  <BusinessSupplyComponents
                    components={components}
                    onAddComponent={() => {}}
                  />

                  <div className="flex flex-wrap justify-center gap-3 mt-8">
                    <SecondaryButton type="button" onClick={handleCancel}>
                      Cancel
                    </SecondaryButton>
                    <PrimaryButton
                      type="button"
                      onClick={() => router.get(route('business-supply.edit', {
                        bizsupId: selectedBizsupId,
                      }))}
                    >
                      Edit
                    </PrimaryButton>
                    <DangerButton type="button" onClick={() => {}}>
                      Delete
                    </DangerButton>
                  </div>
                </>
              ) : null}
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
