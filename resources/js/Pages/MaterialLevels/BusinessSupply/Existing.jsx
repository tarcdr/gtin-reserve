import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import ReactSelect from 'react-select';
import SecondaryButton from '@/Components/SecondaryButton';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import InputError from '@/Components/InputError';
import DeleteDebugPanel from '@/Components/DeleteDebugPanel';
import { useMemo, useState } from 'react';
import BusinessSupplyDetail from '@/Components/BusinessSupply/BusinessSupplyDetail';
import BusinessSupplyComponents from '@/Components/BusinessSupply/BusinessSupplyComponents';
import SuccessButton from '@/Components/SuccessButton';

export default function BusinessSupplyExisting({
  auth,
  InputData,
  brands = [],
  fgMaterials = [],
  sites = [],
  businessSupplies = [],
  selectedBusinessSupply = null,
}) {
  const selectedBizsupId = InputData?.bizsupId || selectedBusinessSupply?.bizsupId || '';
  const selectedBizsupDesc = InputData?.bizsupDesc || selectedBusinessSupply?.bizsupDesc || '';
  const isLocked = Boolean(selectedBizsupId);
  const [confirmingDelete, setConfirmingDelete] = useState(false);
  const [deleteMessage, setDeleteMessage] = useState('');
  const pageId = `${isLocked ? 2 : 1}EBS`;
  const readOnlyClass = 'mt-1 block w-full border-gray-300 rounded-md bg-gray-100';

  const detailValues = useMemo(() => ({
    bomBsId: selectedBusinessSupply?.record?.bomBsId || InputData?.bomBsId || '',
    bomBsDesc: selectedBusinessSupply?.record?.bomBsDesc || InputData?.bomBsDesc || '',
    bsId: selectedBusinessSupply?.record?.bsId || InputData?.bsId || selectedBizsupId,
    searchDesc: selectedBusinessSupply?.record?.searchDesc || InputData?.searchDesc || '',
    compDescEn: selectedBusinessSupply?.record?.compDescEn || InputData?.compDescEn || '',
    compDescTh: selectedBusinessSupply?.record?.compDescTh || InputData?.compDescTh || '',
    uom: selectedBusinessSupply?.record?.uom || InputData?.uom || '',
    sourceLabel: selectedBusinessSupply?.record?.sourceLabel || selectedBizsupDesc || InputData?.sourceLabel || '',
    fgMaterialId: selectedBusinessSupply?.record?.fgMaterialId || InputData?.fgMaterialId || '',
    site: selectedBusinessSupply?.record?.site || InputData?.site || '',
    components: selectedBusinessSupply?.record?.components || InputData?.components || [],
  }), [InputData, selectedBusinessSupply, selectedBizsupDesc, selectedBizsupId]);

  const normalizeUnderType = (value) => {
    const normalized = String(value || '').trim().toUpperCase();

    if (normalized === '1') return 'FG';
    if (normalized === '2') return 'BRAND';
    if (normalized === '3') return 'NOT ALL';

    return normalized || 'FG';
  };

  const underType = normalizeUnderType(InputData?.underType || selectedBusinessSupply?.record?.productCat || InputData?.productCat);
  const businessSupplyType = underType;
  const selectedFgMaterial = fgMaterials.find((item) => item.value === detailValues.fgMaterialId) || null;
  const selectedSite = sites.find((item) => item.value === detailValues.site) || null;
  const selectedBrand = brands.find((item) => item.value === (selectedBusinessSupply?.record?.brand || InputData?.brand || '')) || null;

  const selectedBizsupOption = businessSupplies.find((item) => item.value === selectedBizsupId) || (
    selectedBizsupId
      ? { value: selectedBizsupId, label: selectedBizsupDesc ? `${selectedBizsupId} - ${selectedBizsupDesc}` : selectedBizsupId }
      : null
  );

  const handleCancel = () => {
    router.get(route('business-supply.existing'));
  };

  const handleDelete = () => {
    setDeleteMessage('');
    router.delete(route('business-supply.existing.delete'), {
      data: {
        bizsupId: detailValues.bomBsId || selectedBizsupId,
        bomBsId: detailValues.bomBsId,
      },
      preserveScroll: true,
      onError: (nextErrors) => {
        setDeleteMessage(nextErrors.delete || 'Business Supply delete prepared; procedure not mapped yet.');
      },
      onFinish: () => setConfirmingDelete(false),
    });
  };

  const renderSourceDisplay = () => {
    if (underType === 'FG') {
      return (
        <div>
          <InputLabel htmlFor="detailFgMaterialId" value="Material ID FG" />
          <TextInput
            id="detailFgMaterialId"
            className={readOnlyClass}
            value={selectedFgMaterial?.label || detailValues.fgMaterialId || ''}
            disabled
          />
        </div>
      );
    }

    if (underType === 'BRAND') {
      return (
        <div>
          <InputLabel htmlFor="detailBrand" value="Brand" />
          <TextInput
            id="detailBrand"
            className={readOnlyClass}
            value={selectedBrand?.label || selectedBusinessSupply?.record?.brand || InputData?.brand || ''}
            disabled
          />
        </div>
      );
    }

    return (
      <div>
        <InputLabel value="Business Supply Source" />
        <TextInput className={readOnlyClass} value="NOT ALL" disabled />
      </div>
    );
  };

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">EXISTING BUSINESS SUPPLY</h2>}
      pageIdentity={{ pageId }}
    >
      <Head title="Existing Business Supply" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <DeleteDebugPanel />
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div className="space-y-6">
              <div className="text-sm text-gray-500">
                Select a Business Supply ID to view the saved header and detail.
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="bizsupId" value="Business Supply BOM ID" />
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
                  <div>
                    <InputLabel value="Business Supply Under Type" />
                    <div className="mt-3 flex flex-wrap gap-6">
                      {['FG', 'BRAND', 'NOT ALL'].map((value) => (
                        <label key={value} className="inline-flex items-center gap-2 text-sm text-gray-700">
                          <input
                            type="radio"
                            name="underType"
                            value={value}
                            checked={underType === value}
                            disabled
                            className="border-gray-300 text-gray-900 focus:ring-gray-700"
                            readOnly
                          />
                          <span>{value}</span>
                        </label>
                      ))}
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>{renderSourceDisplay()}</div>
                    <div>
                      <InputLabel htmlFor="detailSite" value="Site" />
                      <TextInput
                        id="detailSite"
                        className={readOnlyClass}
                        value={selectedSite ? `${selectedSite.value} - ${selectedSite.label}` : detailValues.site || ''}
                        disabled
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                      <InputLabel htmlFor="detailMatType" value="Mattype" />
                      <TextInput id="detailMatType" className={readOnlyClass} value={InputData?.matType || ''} disabled />
                    </div>
                    <div>
                      <InputLabel htmlFor="detailSubMatType" value="Sub Mattype" />
                      <TextInput id="detailSubMatType" className={readOnlyClass} value={InputData?.subMatType || ''} disabled />
                    </div>
                  </div>
                </>
              ) : null}
            </div>
          </div>

          {selectedBizsupId ? (
            <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
              <BusinessSupplyDetail
                mode="existing"
                values={detailValues}
                sourceLabel={detailValues.sourceLabel}
                businessSupplyType={businessSupplyType}
                showSourceField={false}
                showActions={false}
              />

              <BusinessSupplyComponents
                components={detailValues.components}
                onAddMaterialId={() => router.get(route('business-supply.create-material-id', {
                  bizsupId: detailValues.bomBsId,
                }))}
                onAddComponent={() => router.get(route('business-supply.create-component', {
                  bizsupId: detailValues.bomBsId,
                }))}
                onEditComponent={(item) => {
                  if (item?.sourceType === 'matid') {
                    router.get(route('business-supply.create-material-id', {
                      bizsupId: detailValues.bomBsId,
                      actionMode: 'edit',
                      materialId: item.listMatId || item.componentId || '',
                      componentMaterialId: item.materialId || item.code || '',
                      brand: item.brand || '',
                      matType: item.matType || '',
                      subMatType: item.subMatType || '',
                    }));
                    return;
                  }

                  router.get(route('business-supply.edit-component', {
                    bizsupId: detailValues.bomBsId,
                    actionMode: 'edit',
                    componentId: item.code || item.componentId || '',
                  }));
                }}
                onDeleteComponent={(item) => {
                  if (item?.sourceType === 'matid') {
                    router.get(route('business-supply.create-material-id', {
                      bizsupId: detailValues.bomBsId,
                      actionMode: 'delete',
                      materialId: item.listMatId || item.componentId || '',
                      componentMaterialId: item.materialId || item.code || '',
                      brand: item.brand || '',
                      matType: item.matType || '',
                      subMatType: item.subMatType || '',
                    }));
                    return;
                  }

                  router.get(route('business-supply.edit-component', {
                    bizsupId: detailValues.bomBsId,
                    actionMode: 'delete',
                    componentId: item.code || item.componentId || '',
                  }));
                }}
              />

              <div className="flex flex-wrap justify-center gap-3 mt-8">
                <SecondaryButton type="button" onClick={handleCancel}>
                  Cancel
                </SecondaryButton>
                <PrimaryButton
                  type="button"
                  onClick={() => router.get(route('business-supply.edit', {
                    bizsupId: detailValues.bomBsId,
                  }))}
                >
                  Edit
                </PrimaryButton>
                <DangerButton type="button" onClick={() => setConfirmingDelete(true)}>
                  Delete
                </DangerButton>
                <SuccessButton type="button" onClick={() => {}}>
                  COMPLETE
                </SuccessButton>
              </div>
              <InputError className="text-center" message={deleteMessage} />
            </div>
          ) : null}
        </div>
      </div>

      <Modal show={confirmingDelete} maxWidth="lg" onClose={() => setConfirmingDelete(false)}>
        <div className="p-6 space-y-6">
          <h2 className="text-lg font-medium text-gray-900">Delete Business Supply</h2>
          <p className="text-sm text-gray-600">
            Are you sure you want to delete {detailValues.bomBsId || selectedBizsupId || 'this Business Supply'}?
          </p>
          <div className="flex justify-end gap-3">
            <SecondaryButton type="button" onClick={() => setConfirmingDelete(false)}>
              Cancel
            </SecondaryButton>
            <DangerButton type="button" onClick={handleDelete}>
              Delete
            </DangerButton>
          </div>
        </div>
      </Modal>
    </AuthenticatedLayout>
  );
}
