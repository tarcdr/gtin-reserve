import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import TextInput from '@/Components/TextInput';
import Modal from '@/Components/Modal';
import DeleteDebugPanel from '@/Components/DeleteDebugPanel';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';
import { getAxiosErrorMessage, getFirstErrorMessage } from '@/Utils/apiError';

export default function BusinessSupplyMaterialId({
  auth,
  InputData,
  brands = [],
  mattypes = [],
  subMattypes = [],
  materialOptions = [],
  selectedMaterial = null,
  selectedBusinessSupply = null,
}) {
  const pageId = '3EBS';
  const actionMode = InputData?.actionMode || InputData?.mode || 'create';
  const isDeleteMode = actionMode === 'delete';
  const isEditMode = actionMode === 'edit';
  const pageTitle = isDeleteMode
    ? 'BUSINESS SUPPLY BOM - DELETE MATERIAL ID'
    : isEditMode
      ? 'BUSINESS SUPPLY BOM - EDIT MATERIAL ID'
      : 'BUSINESS SUPPLY BOM - ADD MATERIAL ID';
  const bizsupId = InputData?.bizsupId || selectedBusinessSupply?.bizsupId || '';
  const defaultBrand = InputData?.brand || '';
  const defaultMatType = InputData?.matType || '';
  const defaultSubMatType = InputData?.subMatType || '';
  const [step, setStep] = useState(InputData?.componentId || selectedMaterial?.componentId ? 3 : 1);
  const [brand, setBrand] = useState(defaultBrand);
  const [matType, setMatType] = useState(defaultMatType);
  const [subMatType, setSubMatType] = useState(defaultSubMatType);
  const [availableMattypes, setAvailableMattypes] = useState(mattypes || []);
  const [availableSubMattypes, setAvailableSubMattypes] = useState(subMattypes || []);
  const [options, setOptions] = useState(materialOptions);
  const [componentId, setComponentId] = useState(InputData?.componentId || selectedMaterial?.componentId || '');
  const [materialDetail, setMaterialDetail] = useState(selectedMaterial);
  const [isLoadingOptions, setIsLoadingOptions] = useState(false);
  const [isLoadingRelatedOptions, setIsLoadingRelatedOptions] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [confirmingDelete, setConfirmingDelete] = useState(false);
  const [deleteDebug, setDeleteDebug] = useState(null);
  const [errors, setErrors] = useState({});
  const [saveError, setSaveError] = useState('');
  const hasLoadedOptionsRef = useRef(false);
  const relatedOptionsRequestRef = useRef(0);

  const clearErrors = () => setErrors({});

  const setFieldError = (field, message) => {
    setErrors((current) => ({
      ...current,
      [field]: message,
    }));
  };

  const setAxiosErrors = (error) => {
    const responseErrors = error?.response?.data?.errors;

    if (error?.response?.status === 422 && responseErrors && typeof responseErrors === 'object') {
      const nextErrors = Object.entries(responseErrors).reduce((acc, [field, messages]) => {
        acc[field] = Array.isArray(messages) ? messages[0] : messages;
        return acc;
      }, {});
      setErrors(nextErrors);
      return true;
    }

    return false;
  };

  useEffect(() => {
    setOptions(materialOptions || []);
  }, [materialOptions]);

  useEffect(() => {
    setAvailableMattypes(mattypes || []);
  }, [mattypes]);

  useEffect(() => {
    setAvailableSubMattypes(subMattypes || []);
  }, [subMattypes]);

  useEffect(() => {
    if (step !== 3) {
      return undefined;
    }

    let active = true;

    const loadOptions = async () => {
      if (!bizsupId) {
        setOptions([]);
        return;
      }

      setIsLoadingOptions(true);
      clearErrors();
      setSaveError('');

      if (hasLoadedOptionsRef.current) {
        setComponentId('');
        setMaterialDetail(null);
      }

      try {
        const { data: payload } = await axios.get(route('business-supply.create-material-id.options', {
          bizsupId,
          brand,
          matType,
          subMatType,
        }), {
          headers: { Accept: 'application/json' },
        });

        if (!active) {
          return;
        }

        setOptions(payload?.materialOptions || []);
      } catch (error) {
        if (active) {
          setOptions([]);
          setSaveError(getAxiosErrorMessage(error, 'Unable to load Component BD Material ID list.'));
        }
      } finally {
        if (active) {
          setIsLoadingOptions(false);
          hasLoadedOptionsRef.current = true;
        }
      }
    };

    loadOptions();

    return () => {
      active = false;
    };
  }, [bizsupId, brand, matType, subMatType, step]);

  useEffect(() => {
    if (!componentId) {
      setMaterialDetail(null);
      return;
    }

    const selectedOption = options.find((item) => item.value === componentId);
    setMaterialDetail(selectedOption || null);
  }, [componentId, options]);

  const resetSelection = () => {
    setComponentId('');
    setMaterialDetail(null);
    setOptions([]);
    hasLoadedOptionsRef.current = false;
    setSaveError('');
    clearErrors();
  };

  const loadRelatedOptions = async (nextBrand, nextMatType = '') => {
    const requestId = relatedOptionsRequestRef.current + 1;
    relatedOptionsRequestRef.current = requestId;

    if (!nextBrand) {
      setAvailableMattypes([]);
      setAvailableSubMattypes([]);
      setIsLoadingRelatedOptions(false);
      return;
    }

    setIsLoadingRelatedOptions(true);
    setSaveError('');

    try {
      const { data: payload } = await axios.get(route('business-supply.create-material-id.related-options', {
        brand: nextBrand,
        matType: nextMatType,
      }), {
        headers: { Accept: 'application/json' },
      });

      if (requestId !== relatedOptionsRequestRef.current) {
        return;
      }

      setAvailableMattypes(payload?.mattypes || []);
      setAvailableSubMattypes(payload?.subMattypes || []);
    } catch (error) {
      if (requestId !== relatedOptionsRequestRef.current) {
        return;
      }

      setAvailableMattypes([]);
      setAvailableSubMattypes([]);
      setSaveError(getAxiosErrorMessage(error, 'Unable to load related Mattype options.'));
    } finally {
      if (requestId === relatedOptionsRequestRef.current) {
        setIsLoadingRelatedOptions(false);
      }
    }
  };

  const handleReset = () => {
    hasLoadedOptionsRef.current = false;
    setBrand(defaultBrand);
    setMatType(defaultMatType);
    setSubMatType(defaultSubMatType);
    setAvailableMattypes(mattypes || []);
    setAvailableSubMattypes(subMattypes || []);
    setOptions(materialOptions || []);
    setComponentId(InputData?.componentId || selectedMaterial?.componentId || '');
    setMaterialDetail(selectedMaterial);
    setStep(InputData?.componentId || selectedMaterial?.componentId ? 3 : 1);
    setSaveError('');
    clearErrors();
  };

  const handleBrandChange = (nextBrand) => {
    setBrand(nextBrand);
    setMatType('');
    setSubMatType('');
    setAvailableMattypes([]);
    setAvailableSubMattypes([]);
    setStep(1);
    resetSelection();
    loadRelatedOptions(nextBrand);
  };

  const handleMatTypeChange = (nextMatType) => {
    setMatType(nextMatType);
    setSubMatType('');
    setAvailableSubMattypes([]);
    setStep(1);
    resetSelection();
    loadRelatedOptions(brand, nextMatType);
  };

  const handleSubMatTypeChange = (nextSubMatType) => {
    setSubMatType(nextSubMatType);
    setStep(nextSubMatType ? 3 : 2);
    resetSelection();
  };

  const handleNextStep = () => {
    if (step === 1) {
      if (!brand) {
        setFieldError('brand', 'The Brand field is required.');
        return;
      }

      if (!matType) {
        setFieldError('matType', 'The Mattype field is required.');
        return;
      }

      setErrors({});
      setStep(2);
      return;
    }

    if (step === 2) {
      if (!subMatType) {
        setFieldError('subMatType', 'The Sub Mattype field is required.');
        return;
      }

      setErrors({});
      setStep(3);
    }
  };

  const handleBack = () => {
    router.get(route('business-supply.existing', {
      bizsupId,
    }));
  };

  const handleSave = async () => {
    clearErrors();
    setSaveError('');

    let hasError = false;

    if (!bizsupId) {
      setSaveError('Business Supply BOM ID is required.');
      return;
    }

    if (!brand) {
      setFieldError('brand', 'The Brand field is required.');
      hasError = true;
    }

    if (!matType) {
      setFieldError('matType', 'The Mattype field is required.');
      hasError = true;
    }

    if (!subMatType) {
      setFieldError('subMatType', 'The Sub Mattype field is required.');
      hasError = true;
    }

    if (!componentId) {
      setFieldError('componentId', 'The Component BD Material ID field is required.');
      hasError = true;
    }

    if (componentId && !materialDetail) {
      setFieldError('componentId', 'Unable to load Component BD Material ID detail.');
      hasError = true;
    }

    if (hasError) {
      return;
    }

    setIsSaving(true);

    try {
      const response = await axios.patch(route('business-supply.create-material-id.save'), {
        bomBsId: bizsupId,
        matType,
        subMatType,
        componentId,
        materialId: materialDetail?.materialId || '',
        searchDesc: materialDetail?.searchDesc || '',
        fullDescEn: materialDetail?.fullDescEn || '',
        fullDescTh: materialDetail?.fullDescTh || '',
        brand,
        bizsupId,
        site: selectedBusinessSupply?.site || selectedBusinessSupply?.record?.site || '',
        actionMode,
      }, {
        headers: { Accept: 'application/json' },
      });

      if (isDeleteMode && response?.data?.program === null) {
        setSaveError(response?.data?.message || 'Unable to delete Business Supply Material ID.');
        setDeleteDebug(response?.data?.deleteDebug || null);
        setConfirmingDelete(false);
        return;
      }

      router.get(route('business-supply.existing', {
        bizsupId,
      }));
    } catch (error) {
      if (!setAxiosErrors(error)) {
        const payloadError = getFirstErrorMessage(error?.response?.data, '');
        setSaveError(payloadError || getAxiosErrorMessage(error, 'Unable to save Business Supply Material ID.'));
      }
    } finally {
      setIsSaving(false);
    }
  };

  const renderStep1 = () => (
    <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
          <InputLabel htmlFor="brand" value="Brand" />
          <select
            id="brand"
            className="mt-1 block w-full border-gray-300 rounded-md"
            value={brand}
            onChange={(e) => handleBrandChange(e.target.value)}
            disabled={isDeleteMode}
          >
            <option value="">---- Select Brand ----</option>
            {brands.map((item) => (
              <option key={`brand-${item.value}`} value={item.value}>
                {item.label}
              </option>
            ))}
          </select>
          <InputError className="mt-2" message={errors.brand} />
        </div>
        <div>
          <InputLabel htmlFor="matType" value="Mattype" />
          <select
            id="matType"
            className="mt-1 block w-full border-gray-300 rounded-md"
            value={matType}
            onChange={(e) => handleMatTypeChange(e.target.value)}
            disabled={isDeleteMode || !brand || isLoadingRelatedOptions}
          >
            <option value="">---- Select Mattype ----</option>
            {availableMattypes.map((item) => (
              <option key={`matType-${item.code}`} value={item.code}>
                {item.label}
              </option>
            ))}
          </select>
          <InputError className="mt-2" message={errors.matType} />
        </div>
      </div>

      <div className="flex flex-wrap justify-center gap-3 pt-6">
        <SecondaryButton type="button" onClick={handleBack}>
          BACK
        </SecondaryButton>
        {actionMode === 'create' && (
          <SecondaryButton type="button" onClick={handleReset}>
            RESET
          </SecondaryButton>
        )}
        <PrimaryButton type="button" onClick={handleNextStep} disabled={!brand || !matType || isLoadingRelatedOptions}>
          NEXT
        </PrimaryButton>
      </div>
    </div>
  );

  const renderStep2 = () => (
    <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
          <InputLabel htmlFor="brandStep2" value="Brand" />
          <TextInput id="brandStep2" className="mt-1 block w-full bg-gray-100" value={brand} disabled />
        </div>
        <div>
          <InputLabel htmlFor="matTypeStep2" value="Mattype" />
          <TextInput id="matTypeStep2" className="mt-1 block w-full bg-gray-100" value={matType} disabled />
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mt-6">
        <div>
          <InputLabel htmlFor="subMatType" value="Sub Mattype" />
          <select
            id="subMatType"
            className="mt-1 block w-full border-gray-300 rounded-md"
            value={subMatType}
            onChange={(e) => handleSubMatTypeChange(e.target.value)}
            disabled={isDeleteMode || !matType || isLoadingRelatedOptions}
          >
            <option value="">---- Select Sub Mattype ----</option>
            {availableSubMattypes.map((item) => (
              <option key={`subMatType-${item.code}`} value={item.code}>
                {item.label}
              </option>
            ))}
          </select>
          <InputError className="mt-2" message={errors.subMatType} />
        </div>
        <div />
      </div>

      <div className="flex flex-wrap justify-center gap-3 pt-6">
        <SecondaryButton type="button" onClick={handleBack}>
          BACK
        </SecondaryButton>
        {actionMode === 'create' && (
          <SecondaryButton type="button" onClick={handleReset}>
            RESET
          </SecondaryButton>
        )}
        <PrimaryButton type="button" onClick={handleNextStep} disabled={!subMatType || isLoadingRelatedOptions}>
          SEARCH FG
        </PrimaryButton>
      </div>
    </div>
  );

  const renderStep3 = () => (
    <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
      <div className="space-y-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <InputLabel htmlFor="brandStep3" value="Brand" />
            <TextInput id="brandStep3" className="mt-1 block w-full bg-gray-100" value={brand} disabled />
          </div>
          <div>
            <InputLabel htmlFor="matTypeStep3" value="Mattype" />
            <TextInput id="matTypeStep3" className="mt-1 block w-full bg-gray-100" value={matType} disabled />
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <InputLabel htmlFor="subMatTypeStep3" value="Sub Mattyp" />
            <TextInput id="subMatTypeStep3" className="mt-1 block w-full bg-gray-100" value={subMatType} disabled />
          </div>
          <div />
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <InputLabel htmlFor="componentId" value="Component BD Material ID" />
            <select
              id="componentId"
              className="mt-1 block w-full border-gray-300 rounded-md"
              value={componentId}
              onChange={(e) => setComponentId(e.target.value)}
              disabled={isLoadingOptions || isDeleteMode}
            >
              <option value="">---- Select Material ID ----</option>
              {options.map((item) => (
                <option key={`material-${item.value}`} value={item.value}>
                  {item.label}
                </option>
              ))}
            </select>
            <InputError className="mt-2" message={errors.componentId} />
          </div>
          <div>
            <InputLabel htmlFor="searchDesc" value="Search Description" />
            <TextInput
              id="searchDesc"
              className="mt-1 block w-full bg-gray-100"
              value={materialDetail?.searchDesc || ''}
              disabled
            />
            <InputError className="mt-2" message={errors.searchDesc} />
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <InputLabel htmlFor="fullDescEn" value="Full Description (EN)" />
            <TextInput
              id="fullDescEn"
              className="mt-1 block w-full bg-gray-100"
              value={materialDetail?.fullDescEn || ''}
              disabled
            />
            <InputError className="mt-2" message={errors.fullDescEn} />
          </div>
          <div>
            <InputLabel htmlFor="fullDescTh" value="Full Description (TH)" />
            <TextInput
              id="fullDescTh"
              className="mt-1 block w-full bg-gray-100"
              value={materialDetail?.fullDescTh || ''}
              disabled
            />
            <InputError className="mt-2" message={errors.fullDescTh} />
          </div>
        </div>

        <div className="flex flex-wrap justify-center gap-3 pt-2">
          <SecondaryButton type="button" onClick={handleBack} disabled={isSaving}>
            BACK
          </SecondaryButton>
          {actionMode === 'create' && (
            <SecondaryButton type="button" onClick={handleReset} disabled={isSaving}>
              RESET
            </SecondaryButton>
          )}
          {isDeleteMode ? (
            <DangerButton type="button" onClick={() => setConfirmingDelete(true)} disabled={isSaving || !componentId || !materialDetail}>
              DELETE
            </DangerButton>
          ) : (
            <PrimaryButton type="button" onClick={handleSave} disabled={isSaving || !componentId || !materialDetail}>
              SAVE
            </PrimaryButton>
          )}
        </div>
        {saveError ? (
          <p className="text-center text-sm font-semibold text-red-600">
            {saveError}
          </p>
        ) : null}
      </div>
    </div>
  );

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{pageTitle}</h2>}
      pageIdentity={{ pageId }}
    >
      <Head title="Business Supply Material ID" />

      <div className="py-12">
        <div className="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <DeleteDebugPanel debug={deleteDebug} />
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div className="grid grid-cols-1 gap-8">
              <div>
                <InputLabel htmlFor="bizsupId" value="Business Supply BOM ID" />
                <TextInput id="bizsupId" className="mt-1 block w-full bg-gray-100" value={bizsupId} disabled />
              </div>
            </div>
          </div>

          {step === 1 && renderStep1()}
          {step === 2 && renderStep2()}
          {step === 3 && renderStep3()}
        </div>
      </div>

      <Modal show={confirmingDelete} maxWidth="lg" onClose={() => setConfirmingDelete(false)}>
        <div className="p-6 space-y-6">
          <h2 className="text-lg font-medium text-gray-900">Delete Business Supply Material ID</h2>
          <p className="text-sm text-gray-600">
            Are you sure you want to delete {componentId || 'this material ID'}?
          </p>
          <div className="flex justify-end gap-3">
            <SecondaryButton type="button" onClick={() => setConfirmingDelete(false)} disabled={isSaving}>
              Cancel
            </SecondaryButton>
            <DangerButton type="button" onClick={handleSave} disabled={isSaving}>
              Delete
            </DangerButton>
          </div>
        </div>
      </Modal>
    </AuthenticatedLayout>
  );
}
