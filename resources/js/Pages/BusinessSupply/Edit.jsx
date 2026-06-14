import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import SecondaryButton from '@/Components/SecondaryButton';
import PrimaryButton from '@/Components/PrimaryButton';
import BusinessSupplyDetail from '@/Components/BusinessSupply/BusinessSupplyDetail';
import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';

export default function BusinessSupplyEdit({
  auth,
  InputData,
  uoms = [],
  selectedBusinessSupply = null,
}) {
  const selectedBizsupId = InputData?.bizsupId || selectedBusinessSupply?.bizsupId || '';
  const selectedBizsupDesc = InputData?.bizsupDesc || selectedBusinessSupply?.bizsupDesc || '';
  const detailValues = useMemo(() => ({
    bomBsId: selectedBusinessSupply?.record?.bomBsId || InputData?.bomBsId || '',
    bsId: selectedBusinessSupply?.record?.bsId || InputData?.bsId || selectedBizsupId,
    searchDesc: selectedBusinessSupply?.record?.searchDesc || InputData?.searchDesc || '',
    compDescEn: selectedBusinessSupply?.record?.compDescEn || InputData?.compDescEn || '',
    compDescTh: selectedBusinessSupply?.record?.compDescTh || InputData?.compDescTh || '',
    uom: selectedBusinessSupply?.record?.uom || InputData?.uom || '',
    sourceLabel: selectedBusinessSupply?.record?.sourceLabel || InputData?.sourceLabel || '',
  }), [InputData, selectedBusinessSupply, selectedBizsupId]);

  const [initialValues, setInitialValues] = useState(detailValues);
  const [isSaving, setIsSaving] = useState(false);
  const { data, setData, errors, setError, clearErrors } = useForm(detailValues);

  useEffect(() => {
    setInitialValues(detailValues);
    setData(detailValues);
    clearErrors();
  }, [detailValues, setData, clearErrors]);

  const handleCancel = () => {
    setData(initialValues);
    router.get(route('business-supply.existing', {
      bizsupId: selectedBizsupId,
    }));
  };

  const handleSave = async () => {
    clearErrors();

    let hasError = false;
    if (!data.searchDesc) {
      setError('searchDesc', 'Search Description is required.');
      hasError = true;
    }
    if (!data.compDescEn) {
      setError('compDescEn', 'Full Description (EN) is required.');
      hasError = true;
    }
    if (!data.compDescTh) {
      setError('compDescTh', 'Full Description (TH) is required.');
      hasError = true;
    }
    if (!data.uom) {
      setError('uom', 'UOM is required.');
      hasError = true;
    }

    if (hasError) {
      return;
    }

    setIsSaving(true);

    try {
      await axios.patch(route('business-supply.existing.update'), {
        bomBsId: data.bomBsId,
        searchDesc: data.searchDesc,
        compDescEn: data.compDescEn,
        compDescTh: data.compDescTh,
        uom: data.uom,
      }, {
        headers: { Accept: 'application/json' },
      });

      router.get(route('business-supply.existing', {
        bizsupId: selectedBizsupId,
      }));
    } catch (error) {
      const responseErrors = error?.response?.data?.errors || {};
      if (error?.response?.status === 422 && Object.keys(responseErrors).length > 0) {
        Object.entries(responseErrors).forEach(([field, messages]) => {
          if (Array.isArray(messages) && messages[0]) {
            setError(field, messages[0]);
          }
        });
        return;
      }

      setError('save', error?.response?.data?.error || error?.message || 'Unable to update Business Supply.');
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">EDIT BUSINESS SUPPLY</h2>}
    >
      <Head title="Edit Business Supply" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <label className="block text-sm font-medium text-gray-700">Material ID FG</label>
                  <div className="mt-1 block w-full rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-gray-700">
                    {InputData?.fgMaterialId || '-'}
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700">Site</label>
                  <div className="mt-1 block w-full rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-gray-700">
                    {InputData?.site || '-'}
                  </div>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <label className="block text-sm font-medium text-gray-700">Business Supply Id</label>
                  <div className="mt-1 block w-full rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-gray-700">
                    {selectedBizsupId || '-'}
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700">Business Supply Description</label>
                  <div className="mt-1 block w-full rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-gray-700">
                    {selectedBizsupDesc || '-'}
                  </div>
                </div>
              </div>

              <BusinessSupplyDetail
                mode="existing"
                values={data}
                errors={errors}
                uoms={uoms}
                sourceLabel={data.sourceLabel}
                isEditing
                showActions={false}
                onChange={(field, value) => setData(field, value)}
              />

              <div className="flex flex-wrap justify-center gap-3 mt-8">
                <SecondaryButton type="button" onClick={handleCancel} disabled={isSaving}>
                  Cancel
                </SecondaryButton>
                <PrimaryButton type="button" onClick={handleSave} disabled={isSaving}>
                  Save Edit
                </PrimaryButton>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
