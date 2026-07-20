import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import SecondaryButton from '@/Components/SecondaryButton';
import PrimaryButton from '@/Components/PrimaryButton';
import BusinessSupplyDetail from '@/Components/BusinessSupply/BusinessSupplyDetail';
import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { getAxiosErrorMessage } from '@/Utils/apiError';

export default function BusinessSupplyEdit({
  auth,
  InputData,
  uoms = [],
  selectedBusinessSupply = null,
}) {
  const pageId = '3EBS';
  const selectedBizsupId = InputData?.bizsupId || selectedBusinessSupply?.bizsupId || '';
  const detailValues = useMemo(() => ({
    bomBsId: selectedBusinessSupply?.record?.bomBsId || InputData?.bomBsId || '',
    bomBsDesc: selectedBusinessSupply?.record?.bomBsDesc || InputData?.bomBsDesc || '',
    bsId: selectedBusinessSupply?.record?.bsId || InputData?.bsId || selectedBizsupId,
    searchDesc: selectedBusinessSupply?.record?.searchDesc || InputData?.searchDesc || '',
    compDescEn: selectedBusinessSupply?.record?.compDescEn || InputData?.compDescEn || '',
    compDescTh: selectedBusinessSupply?.record?.compDescTh || InputData?.compDescTh || '',
    uom: selectedBusinessSupply?.record?.uom || InputData?.uom || '',
    sourceLabel: selectedBusinessSupply?.record?.sourceLabel || InputData?.sourceLabel || '',
  }), [InputData, selectedBusinessSupply, selectedBizsupId]);
  const normalizeUnderType = (value) => {
    const normalized = String(value || '').trim().toUpperCase();

    if (normalized === '1') return 'FG';
    if (normalized === '2') return 'BRAND';
    if (normalized === '3') return 'NOT ALL';

    return normalized || 'FG';
  };
  const businessSupplyType = normalizeUnderType(
    InputData?.underType || selectedBusinessSupply?.record?.productCat || InputData?.productCat
  );

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
      bizsupId: data.bomBsId || initialValues.bomBsId,
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
        bsId: data.bsId,
        bomBsDesc: data.bomBsDesc,
        underType: businessSupplyType,
        matType: InputData?.matType || selectedBusinessSupply?.record?.matType || '6',
        subMatType: InputData?.subMatType || selectedBusinessSupply?.record?.subMatType || '0',
        fgMaterialId: selectedBusinessSupply?.record?.fgMaterialId || InputData?.fgMaterialId || '',
        brand: selectedBusinessSupply?.record?.brand || InputData?.brand || '',
        site: selectedBusinessSupply?.record?.site || InputData?.site || '',
        searchDesc: data.searchDesc,
        compDescEn: data.compDescEn,
        compDescTh: data.compDescTh,
        uom: data.uom,
      }, {
        headers: { Accept: 'application/json' },
      });

      router.get(route('business-supply.existing', {
        bizsupId: data.bomBsId,
      }));
    } catch (error) {
      setError('save', getAxiosErrorMessage(error, 'Unable to update Business Supply.'));
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">EDIT BUSINESS SUPPLY</h2>}
      pageIdentity={{ pageId }}
    >
      <Head title="Edit Business Supply" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div className="space-y-6">
              <BusinessSupplyDetail
                mode="existing"
                values={data}
                errors={errors}
                uoms={uoms}
                businessSupplyType={businessSupplyType}
                isEditing
                showSourceField={false}
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
              {errors.save ? (
                <p className="text-center text-sm font-semibold text-red-600">
                  {errors.save}
                </p>
              ) : null}
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
