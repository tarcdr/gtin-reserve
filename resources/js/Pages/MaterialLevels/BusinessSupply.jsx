import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import TextInput from '@/Components/TextInput';
import Modal from '@/Components/Modal';
import DeleteDebugPanel from '@/Components/DeleteDebugPanel';
import { router, useForm } from '@inertiajs/react';
import axios from 'axios';
import ReactSelect from 'react-select';
import { useMemo, useState } from 'react';
import { getAxiosErrorMessage, getFirstErrorMessage } from '@/Utils/apiError';

const DEFAULT_MATTYPE = '6';
const DEFAULT_SUB_MATTYPE = '0';

export default function BusinessSupply({
  auth,
  InputData,
  brands = [],
  fgMaterials = [],
  sites = [],
  subMattypes = [],
  uoms = [],
  businessSupplies = [],
  selectedBusinessSupply = null,
}) {
  const pageMode = InputData?.mode === 'existing' ? 'Existing' : 'New';
  const isExistingMode = pageMode === 'Existing';
  const [step, setStep] = useState(InputData?.bsId || InputData?.bomBsId ? 2 : 1);
  const [underType, setUnderType] = useState(InputData?.underType || 'FG');
  const [fgMaterialId, setFgMaterialId] = useState(InputData?.fgMaterialId || '');
  const [brand, setBrand] = useState(InputData?.brand || '');
  const [site, setSite] = useState(InputData?.site || '');
  const [matType, setMatType] = useState(InputData?.matType || DEFAULT_MATTYPE);
  const [subMatType, setSubMatType] = useState(InputData?.subMatType || DEFAULT_SUB_MATTYPE);
  const [bsId, setBsId] = useState(InputData?.bsId || '');
  const [bomBsId, setBomBsId] = useState(InputData?.bomBsId || '');
  const [bomBsDesc, setBomBsDesc] = useState(InputData?.bomBsDesc || '');
  const [searchDesc, setSearchDesc] = useState(InputData?.searchDesc || '');
  const [compDescEn, setCompDescEn] = useState(InputData?.compDescEn || '');
  const [compDescTh, setCompDescTh] = useState(InputData?.compDescTh || '');
  const [uom, setUom] = useState(InputData?.uom || '');
  const [generationError, setGenerationError] = useState('');
  const [saveError, setSaveError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  const [isGenerating, setIsGenerating] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [confirmingDelete, setConfirmingDelete] = useState(false);
  const { errors, setError, clearErrors } = useForm({
    underType: InputData?.underType || 'FG',
    fgMaterialId: InputData?.fgMaterialId || '',
    brand: InputData?.brand || '',
    site: InputData?.site || '',
    bomBsDesc: InputData?.bomBsDesc || '',
    searchDesc: InputData?.searchDesc || '',
    compDescEn: InputData?.compDescEn || '',
    compDescTh: InputData?.compDescTh || '',
    uom: InputData?.uom || '',
  });

  const sourceValue = useMemo(() => {
    if (underType === 'FG') {
      return fgMaterialId;
    }

    if (underType === 'BRAND') {
      return brand;
    }

    return '';
  }, [underType, fgMaterialId, brand]);

  const handleDeletePrepared = () => {
    setSaveError('');
    router.delete(route('business-supply.existing.delete'), {
      data: {
        bizsupId: bomBsId || bsId,
        bomBsId,
      },
      preserveScroll: true,
      onError: (nextErrors) => {
        setSaveError(nextErrors.delete || 'Business Supply delete prepared; procedure not mapped yet.');
      },
      onFinish: () => setConfirmingDelete(false),
    });
  };

  const selectedFgMaterial = fgMaterials.find((item) => item.value === fgMaterialId) || null;

  const sourceLabel = underType === 'FG'
    ? 'Material ID FG'
    : underType === 'BRAND'
      ? 'Brand'
      : 'NOT ALL';
  const selectedBizsupId = InputData?.bizsupId || selectedBusinessSupply?.bizsupId || '';
  const selectedBizsupDesc = InputData?.bizsupDesc || selectedBusinessSupply?.bizsupDesc || '';
  const selectedBizsupOption = businessSupplies.find((item) => item.value === selectedBizsupId) || (
    selectedBizsupId
      ? { value: selectedBizsupId, label: selectedBizsupDesc ? `${selectedBizsupId} - ${selectedBizsupDesc}` : selectedBizsupId }
      : null
  );
  const selectedBizsupRecord = selectedBusinessSupply?.record || null;

  const clearGeneratedOutput = () => {
    setBsId('');
    setBomBsId('');
    setBomBsDesc('');
    setSearchDesc('');
    setCompDescEn('');
    setCompDescTh('');
    setUom('');
    setGenerationError('');
    setSaveError('');
    setSuccessMessage('');
    setStep(1);
    clearErrors();
  };

  const resetStepOne = () => {
    setUnderType('FG');
    setFgMaterialId('');
    setBrand('');
    setSite('');
    setMatType(DEFAULT_MATTYPE);
    setSubMatType(DEFAULT_SUB_MATTYPE);
    clearGeneratedOutput();
    setGenerationError('');
  };

  const cancelToStepOne = () => {
    clearGeneratedOutput();
    setGenerationError('');
  };

  const handleGenerate = async () => {
    setGenerationError('');
    setSaveError('');
    setSuccessMessage('');
    clearErrors();

    setIsGenerating(true);

    try {
      const { data: payload } = await axios.get(route('business-supply.generate', {
        matType,
        subMatType,
        underType,
        site,
        fgMaterialId,
        brand,
      }), {
        headers: {
          Accept: 'application/json',
        },
      });

      const payloadError = getFirstErrorMessage(payload, '');
      if (payloadError) {
        throw new Error(payloadError);
      }

      setBsId(payload?.bsId || '');
      setBomBsId(payload?.bomBsId || '');
      setStep(2);

      if (!payload?.bsId || !payload?.bomBsId) {
        setGenerationError('Procedure returned empty Business Supply IDs.');
      }
    } catch (error) {
      setGenerationError(getAxiosErrorMessage(error, 'Unable to generate Business Supply ID.'));
    } finally {
      setIsGenerating(false);
    }
  };

  const handleSave = async () => {
    setSaveError('');
    setSuccessMessage('');
    clearErrors();

    if (!bomBsId || !bsId) {
      setSaveError('Generate Business Supply IDs first.');
      return;
    }

    let hasError = false;
    if (!bomBsDesc) {
      setError('bomBsDesc', 'Description of BOM ID Business Supply is required.');
      hasError = true;
    }
    if (!searchDesc) {
      setError('searchDesc', 'Search Description is required.');
      hasError = true;
    }
    if (!compDescEn) {
      setError('compDescEn', 'Full Description (EN) is required.');
      hasError = true;
    }
    if (!compDescTh) {
      setError('compDescTh', 'Full Description (TH) is required.');
      hasError = true;
    }
    if (!uom) {
      setError('uom', 'UOM is required.');
      hasError = true;
    }

    if (hasError) {
      return;
    }

    setIsSaving(true);

    try {
      const { data: payload } = await axios.patch(route('business-supply.save'), {
        bomBsId,
        bomBsDesc,
        matType,
        subMatType,
        productCat: underType,
        prodSubCat: sourceValue || underType,
        componentId: bsId,
        searchDesc,
        compDescEn,
        compDescTh,
        uom,
      }, {
        headers: {
          Accept: 'application/json',
        },
      });

      if (payload?.message) {
        setSuccessMessage(payload.message);
      } else {
        setSuccessMessage('Business Supply saved.');
      }

      router.get(route('business-supply.existing', {
        bizsupId: bsId,
        bizsupDesc: selectedBizsupDesc || sourceLabel,
        bomBsId,
        bomBsDesc,
        matType,
        subMatType,
        underType,
        fgMaterialId,
        brand,
        site,
        bsId,
        searchDesc,
        compDescEn,
        compDescTh,
        uom,
        productCat: underType,
        prodSubCat: sourceValue || underType,
        componentId: bsId,
      }));
    } catch (error) {
      setSaveError(getAxiosErrorMessage(error, 'Unable to save Business Supply.'));
    } finally {
      setIsSaving(false);
    }
  };

  const renderSourceSelector = () => {
    if (underType === 'FG') {
      return (
        <div>
          <InputLabel htmlFor="fgMaterialId" value="Material ID FG" />
          <ReactSelect
            inputId="fgMaterialId"
            options={fgMaterials}
            isSearchable={true}
            placeholder="---- Select Material ID ----"
            value={selectedFgMaterial}
            onChange={(e) => {
              setFgMaterialId(e?.value || '');
              clearGeneratedOutput();
            }}
            isDisabled={step === 2}
            getOptionLabel={(item) => item.label}
            getOptionValue={(item) => item.value}
            classNames={{
              control: () => `mt-1 block w-full ${step === 2 ? 'bg-gray-100' : ''}`,
            }}
          />
          <InputError className="mt-2" message={errors.fgMaterialId} />
        </div>
      );
    }

    if (underType === 'BRAND') {
      return (
        <div>
          <InputLabel htmlFor="brand" value="Brand" />
          <select
            id="brand"
            className={`mt-1 block w-full border-gray-300 rounded-md ${step >= 2 ? 'bg-gray-100' : ''}`}
            value={brand}
            disabled={step === 2}
            onChange={(e) => {
              setBrand(e.target.value);
              clearGeneratedOutput();
            }}
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
      );
    }

    return (
      <div className="space-y-2">
        <InputLabel value="Business Supply Source" />
        <div className="flex min-h-[42px] items-center rounded-md border border-gray-300 bg-gray-100 px-3 text-sm text-gray-900">
          NOT ALL
        </div>
      </div>
    );
  };

  const renderBusinessSupplyComponents = () => {
    const components = selectedBizsupRecord?.components || InputData?.components || [];

    return (
      <div className="rounded-lg border border-gray-200 bg-white">
        <div className="flex items-center justify-between border-b border-gray-100 px-4 py-3">
          <h4 className="text-sm font-semibold text-gray-800">Business Supply Components</h4>
          <SecondaryButton type="button" disabled>
            Add Component
          </SecondaryButton>
        </div>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200 text-sm">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                <th className="px-4 py-3 text-left font-semibold text-gray-600">Component ID</th>
                <th className="px-4 py-3 text-left font-semibold text-gray-600">Description</th>
                <th className="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                <th className="px-4 py-3 text-left font-semibold text-gray-600">Action</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 bg-white">
              {components.length > 0 ? (
                components.map((item, index) => (
                  <tr key={`${item.code || item.componentId || index}`}>
                    <td className="px-4 py-3 text-gray-700">{index + 1}</td>
                    <td className="px-4 py-3 text-gray-700">{item.code || item.componentId || '-'}</td>
                    <td className="px-4 py-3 text-gray-700">{item.label || item.description || '-'}</td>
                    <td className="px-4 py-3 text-gray-700">{item.status || '-'}</td>
                    <td className="px-4 py-3 text-gray-700">-</td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td className="px-4 py-6 text-gray-500" colSpan={5}>
                    No components yet. Select a business supply to view details.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    );
  };

  const renderExistingPage = () => (
    <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
      <div className="space-y-6">
        <div className="text-sm text-gray-500">
          Select a Business Supply ID to view details. The detail section is prepared for edit/delete flows.
        </div>

        <div>
          <InputLabel htmlFor="bizsupId" value="Business Supply Id" />
          <ReactSelect
            inputId="bizsupId"
            options={businessSupplies}
            isSearchable={true}
            placeholder="---- Select Business Supply Id ----"
            value={selectedBizsupOption}
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
          <InputError className="mt-2" message={errors.bizsupId} />
        </div>

        {selectedBizsupId && (
          <>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div>
                <InputLabel htmlFor="detailMatType" value="Mattype" />
                <TextInput
                  id="detailMatType"
                  className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                  value={InputData?.matType || ''}
                  disabled
                />
              </div>
              <div>
                <InputLabel htmlFor="detailSubMatType" value="Sub Mattype" />
                <TextInput
                  id="detailSubMatType"
                  className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                  value={InputData?.subMatType || ''}
                  disabled
                />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div>
                <InputLabel htmlFor="detailBrand" value="Brand" />
                <TextInput
                  id="detailBrand"
                  className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                  value={InputData?.brand || ''}
                  disabled
                />
              </div>
              <div>
                <InputLabel htmlFor="detailSite" value="Site" />
                <TextInput
                  id="detailSite"
                  className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                  value={InputData?.site || ''}
                  disabled
                />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div>
                <InputLabel htmlFor="bomBsId" value="BOM ID for Business Supply" />
                <TextInput
                  id="bomBsId"
                  className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                  value={InputData?.bomBsId || ''}
                  disabled
                />
              </div>
              <div>
                <InputLabel htmlFor="bomBsDesc" value="Description of BOM ID Business Supply" />
                <TextInput
                  id="bomBsDesc"
                  className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                  value={InputData?.bomBsDesc || ''}
                  disabled
                />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div>
                <InputLabel htmlFor="bsId" value="Business Supply ID" />
                <TextInput
                  id="bsId"
                  className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                  value={InputData?.bsId || selectedBizsupId}
                  disabled
                />
              </div>
              <div>
                <InputLabel htmlFor="searchDesc" value="Search Description" />
                <TextInput
                  id="searchDesc"
                  className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                  value={InputData?.searchDesc || ''}
                  disabled
                />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div>
                <InputLabel htmlFor="compDescEn" value="Full Description (EN)" />
                <TextInput
                  id="compDescEn"
                  className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                  value={InputData?.compDescEn || ''}
                  disabled
                />
              </div>
              <div>
                <InputLabel htmlFor="compDescTh" value="Full Description (TH)" />
                <TextInput
                  id="compDescTh"
                  className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                  value={InputData?.compDescTh || ''}
                  disabled
                />
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div>
                <InputLabel htmlFor="uom" value="UOM" />
                <TextInput
                  id="uom"
                  className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                  value={InputData?.uom || ''}
                  disabled
                />
              </div>
              <div>
                <InputLabel value="Business Supply Source" />
                <TextInput
                  className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                  value={selectedBizsupDesc || sourceLabel}
                  disabled
                />
              </div>
            </div>

            {renderBusinessSupplyComponents()}

            <div className="flex flex-wrap justify-center gap-3">
              <SecondaryButton
                type="button"
                onClick={() => {
                  router.get(route('business-supply.existing'));
                }}
              >
                Cancel
              </SecondaryButton>
              <PrimaryButton type="button" disabled>
                Edit
              </PrimaryButton>
              <DangerButton type="button" onClick={() => setConfirmingDelete(true)} disabled={!bomBsId && !bsId}>
                Delete
              </DangerButton>
            </div>
            <InputError className="text-center" message={saveError} />
          </>
        )}
      </div>
    </div>
  );

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{`${pageMode.toUpperCase()} BUSINESS SUPPLY`}</h2>}
    >
      <Head title={`${pageMode} Business Supply`} />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <DeleteDebugPanel />
          {isExistingMode ? renderExistingPage() : null}
          {!isExistingMode ? (
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <form
              onSubmit={(e) => {
                e.preventDefault();
                handleGenerate();
              }}
              className="space-y-6"
            >
              <div className="text-sm text-gray-500">
                Step 1 generates Business Supply IDs. Step 2 finalizes the component save.
              </div>

              {generationError && (
                <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                  {generationError}
                </div>
              )}

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
                        disabled={step === 2}
                        onChange={(e) => {
                          setUnderType(e.target.value);
                          setFgMaterialId('');
                          setBrand('');
                          clearGeneratedOutput();
                        }}
                        className="border-gray-300 text-gray-900 focus:ring-gray-700"
                      />
                      <span>{value}</span>
                    </label>
                  ))}
                </div>
                <InputError className="mt-2" message={errors.underType} />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>{renderSourceSelector()}</div>
                <div>
                  <InputLabel htmlFor="site" value="Site" />
                  <select
                    id="site"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${step === 2 ? 'bg-gray-100' : ''}`}
                    value={site}
                    disabled={step === 2}
                    onChange={(e) => {
                      setSite(e.target.value);
                      clearGeneratedOutput();
                    }}
                  >
                    <option value="">---- Select Site ----</option>
                    {sites.map((item) => (
                      <option key={`site-${item.value}`} value={item.value}>
                        {item.label}
                      </option>
                    ))}
                  </select>
                  <InputError className="mt-2" message={errors.site} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="matType" value="Mattype" />
                  <TextInput
                    id="matType"
                    className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                    value={matType}
                    readOnly
                    disabled={step === 2}
                  />
                  <InputError className="mt-2" message={errors.matType} />
                </div>
                <div>
                  <InputLabel htmlFor="subMatType" value="Sub Mattype" />
                  <select
                    id="subMatType"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${step === 2 ? 'bg-gray-100' : ''}`}
                    value={subMatType}
                    disabled={step === 2}
                    onChange={(e) => {
                      setSubMatType(e.target.value);
                      clearGeneratedOutput();
                    }}
                  >
                    <option value="">---- Select Sub Mattype ----</option>
                    {subMattypes.map((item) => (
                      <option key={`subMatType-${item.code}`} value={item.code}>
                        {item.label}
                      </option>
                    ))}
                  </select>
                  <InputError className="mt-2" message={errors.subMatType} />
                </div>
              </div>

              <div className="flex flex-wrap justify-end gap-3">
                <SecondaryButton type="button" onClick={resetStepOne} disabled={step === 2}>
                  Reset
                </SecondaryButton>
                <PrimaryButton type="submit" disabled={isGenerating || step === 2}>
                  {isGenerating ? 'Generating...' : 'GENERATE BUSINESS ID'}
                </PrimaryButton>
              </div>
            </form>
          </div>
          ) : null}
          {!isExistingMode && step === 2 && (
            <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
              <form className="space-y-6">
                {successMessage && (
                  <div className="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {successMessage}
                  </div>
                )}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div>
                    <InputLabel htmlFor="bomBsId" value="BOM ID for Business Supply" />
                    <TextInput
                      id="bomBsId"
                      className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                      value={bomBsId}
                      disabled
                    />
                    <InputError className="mt-2" message={errors.bomBsId} />
                  </div>
                  <div>
                    <InputLabel htmlFor="bomBsDesc" value="Description of BOM ID Business Supply" />
                    <TextInput
                      id="bomBsDesc"
                      className="mt-1 block w-full border-gray-300 rounded-md"
                      value={bomBsDesc}
                      onChange={(e) => setBomBsDesc(e.target.value)}
                    />
                    <InputError className="mt-2" message={errors.bomBsDesc} />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div>
                    <InputLabel htmlFor="bsId" value="Business Supply ID" />
                    <TextInput
                      id="bsId"
                      className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                      value={bsId}
                      disabled
                    />
                    <InputError className="mt-2" message={errors.bsId} />
                  </div>
                  <div>
                    <InputLabel htmlFor="searchDesc" value="Search Description" />
                    <TextInput
                      id="searchDesc"
                      className="mt-1 block w-full border-gray-300 rounded-md"
                      value={searchDesc}
                      onChange={(e) => setSearchDesc(e.target.value)}
                    />
                    <InputError className="mt-2" message={errors.searchDesc} />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div>
                    <InputLabel htmlFor="compDescEn" value="Full Description (EN)" />
                    <TextInput
                      id="compDescEn"
                      className="mt-1 block w-full border-gray-300 rounded-md"
                      value={compDescEn}
                      onChange={(e) => setCompDescEn(e.target.value)}
                    />
                    <InputError className="mt-2" message={errors.compDescEn} />
                  </div>
                  <div>
                    <InputLabel htmlFor="compDescTh" value="Full Description (TH)" />
                    <TextInput
                      id="compDescTh"
                      className="mt-1 block w-full border-gray-300 rounded-md"
                      value={compDescTh}
                      onChange={(e) => setCompDescTh(e.target.value)}
                    />
                    <InputError className="mt-2" message={errors.compDescTh} />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div>
                    <InputLabel htmlFor="uom" value="UOM" />
                    <select
                      id="uom"
                      className="mt-1 block w-full border-gray-300 rounded-md"
                      value={uom}
                      onChange={(e) => setUom(e.target.value)}
                    >
                      <option value="">---- Select UOM ----</option>
                      {uoms.map((item) => (
                        <option key={`uom-${item.value}`} value={item.value}>
                          {item.label}
                        </option>
                      ))}
                    </select>
                    <InputError className="mt-2" message={errors.uom} />
                  </div>
                  <div>
                    <InputLabel value="Business Supply Source" />
                    <TextInput
                      className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100"
                      value={sourceLabel}
                      disabled
                    />
                  </div>
                </div>

                <div className="flex flex-wrap justify-end gap-3">
                  <SecondaryButton type="button" onClick={cancelToStepOne}>
                    Cancel
                  </SecondaryButton>
                  <PrimaryButton type="button" onClick={handleSave} disabled={isSaving}>
                    {isSaving ? 'Saving...' : 'SAVE BS'}
                  </PrimaryButton>
                </div>
                {saveError ? (
                  <p className="text-center text-sm font-semibold text-red-600">
                    {saveError}
                  </p>
                ) : null}
              </form>
            </div>
          )}
        </div>
      </div>
      <Modal show={confirmingDelete} maxWidth="lg" onClose={() => setConfirmingDelete(false)}>
        <div className="p-6 space-y-6">
          <h2 className="text-lg font-medium text-gray-900">Delete Business Supply</h2>
          <p className="text-sm text-gray-600">
            Are you sure you want to delete {bomBsId || bsId || 'this Business Supply'}?
          </p>
          <div className="flex justify-end gap-3">
            <SecondaryButton type="button" onClick={() => setConfirmingDelete(false)}>
              Cancel
            </SecondaryButton>
            <DangerButton type="button" onClick={handleDeletePrepared}>
              Delete
            </DangerButton>
          </div>
        </div>
      </Modal>
    </AuthenticatedLayout>
  );
}
