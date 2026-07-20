import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { getAxiosErrorMessage, getFirstErrorMessage } from '@/Utils/apiError';
import axios from 'axios';
import ReactSelect from 'react-select';
import { useState } from 'react';
import BusinessSupplyDetail from '@/Components/BusinessSupply/BusinessSupplyDetail';

const DEFAULT_MATTYPE = '6';
const DEFAULT_SUB_MATTYPE = '0';

export default function BusinessSupplyNew({ auth, InputData, brands = [], fgMaterials = [], sites = [], subMattypes = [], uoms = [] }) {
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
  const pageId = `${step}NBS`;
  const { errors, setError, clearErrors } = useForm({
    underType: InputData?.underType || 'FG',
    fgMaterialId: InputData?.fgMaterialId || '',
    brand: InputData?.brand || '',
    site: InputData?.site || '',
    searchDesc: InputData?.searchDesc || '',
    compDescEn: InputData?.compDescEn || '',
    compDescTh: InputData?.compDescTh || '',
    uom: InputData?.uom || '',
  });

  const selectedFgMaterial = fgMaterials.find((item) => item.value === fgMaterialId) || null;
  const isStepOneLocked = step === 2;

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

  const setAxiosValidationErrors = (error) => {
    const responseErrors = error?.response?.data?.errors;

    if (error?.response?.status !== 422 || !responseErrors || typeof responseErrors !== 'object') {
      return false;
    }

    const normalizedErrors = Object.entries(responseErrors).reduce((nextErrors, [field, messages]) => {
      const message = Array.isArray(messages) ? messages[0] : messages;

      nextErrors[field === 'componentId' ? 'bsId' : field] = message;

      return nextErrors;
    }, {});

    setError(normalizedErrors);
    return true;
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

  const handleGenerate = async () => {
    setGenerationError('');
    setSaveError('');
    setSuccessMessage('');
    clearErrors();

    let hasError = false;

    if (!underType) {
      setError('underType', 'The Business Supply Under Type field is required.');
      hasError = true;
    }

    if (underType === 'FG' && !fgMaterialId) {
      setError('fgMaterialId', 'The Material ID FG field is required.');
      hasError = true;
    }

    if (underType === 'BRAND' && !brand) {
      setError('brand', 'The Brand field is required.');
      hasError = true;
    }

    if (!site) {
      setError('site', 'The Site field is required.');
      hasError = true;
    }

    if (!matType) {
      setError('matType', 'The Mattype field is required.');
      hasError = true;
    }

    if (!subMatType) {
      setError('subMatType', 'The Sub Mattype field is required.');
      hasError = true;
    }

    if (hasError) {
      return;
    }

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
        headers: { Accept: 'application/json' },
      });

      const payloadError = getFirstErrorMessage(payload, '');
      if (payloadError) {
        throw new Error(payloadError);
      }

      const nextBsId = (payload?.bsId || '').trim();
      const nextBomBsId = (payload?.bomBsId || '').trim();

      if (!nextBsId || !nextBomBsId) {
        setBsId('');
        setBomBsId('');
        setStep(1);
        throw new Error('Procedure did not return Business Supply IDs.');
      }

      setBsId(nextBsId);
      setBomBsId(nextBomBsId);
      setStep(2);
    } catch (error) {
      if (!setAxiosValidationErrors(error)) {
        setGenerationError(getAxiosErrorMessage(error, 'Unable to generate Business Supply ID.'));
      }
    } finally {
      setIsGenerating(false);
    }
  };

  const handleSave = async () => {
    setSaveError('');
    setSuccessMessage('');
    clearErrors();

    if (!bomBsId || !bsId) {
      if (!bomBsId) {
        setError('bomBsId', 'Generate BOM ID for Business Supply first.');
      }
      if (!bsId) {
        setError('bsId', 'Generate Business Supply ID first.');
      }
      setSaveError('Generate Business Supply IDs first.');
      return;
    }

    let hasError = false;
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
        bsId,
        underType,
        matType,
        subMatType,
        fgMaterialId,
        brand,
        site,
        searchDesc,
        compDescEn,
        compDescTh,
        uom,
      }, {
        headers: { Accept: 'application/json' },
      });

      const nextBsId = (payload?.bsId || bsId || '').trim();
      const nextBomBsId = (payload?.bomBsId || bomBsId || '').trim();

      setBsId(nextBsId);
      setBomBsId(nextBomBsId);
      setSuccessMessage(payload?.message || 'Business Supply saved.');

      router.get(route('business-supply.existing', {
        bizsupId: nextBomBsId,
      }));
    } catch (error) {
      if (!setAxiosValidationErrors(error)) {
        setSaveError(getAxiosErrorMessage(error, 'Unable to save Business Supply.'));
      }
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
            isSearchable
            placeholder="---- Select Material ID ----"
            value={selectedFgMaterial}
            onChange={(option) => {
              setFgMaterialId(option?.value || '');
              clearGeneratedOutput();
            }}
            isDisabled={step === 2}
            getOptionLabel={(item) => item.label}
            getOptionValue={(item) => item.value}
            classNames={{ control: () => `mt-1 block w-full ${step === 2 ? 'bg-gray-100' : ''}` }}
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

  const detailValues = {
    bomBsId,
    bomBsDesc,
    bsId,
    searchDesc,
    compDescEn,
    compDescTh,
    uom,
  };

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">NEW BUSINESS SUPPLY</h2>}
      pageIdentity={{ pageId }}
    >
      <Head title="New Business Supply" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <div className={`p-4 sm:p-8 bg-white shadow sm:rounded-lg ${isStepOneLocked ? 'opacity-95' : ''}`}>
            <form onSubmit={(e) => { e.preventDefault(); handleGenerate(); }} className="space-y-6">
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
                        disabled={isStepOneLocked}
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
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isStepOneLocked ? 'bg-gray-100' : ''}`}
                    value={site}
                    disabled={isStepOneLocked}
                    onChange={(e) => {
                      setSite(e.target.value);
                      clearGeneratedOutput();
                    }}
                  >
                    <option value="">---- Select Site ----</option>
                    {sites.map((item) => (
                      <option key={`site-${item.value}`} value={item.label}>
                        {`${item.value} - ${item.label}`}
                      </option>
                    ))}
                  </select>
                  <InputError className="mt-2" message={errors.site} />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="matType" value="Mattype" />
                  <TextInput id="matType" className="mt-1 block w-full border-gray-300 rounded-md bg-gray-100" value={matType} readOnly disabled={isStepOneLocked} />
                  <InputError className="mt-2" message={errors.matType} />
                </div>
                <div>
                  <InputLabel htmlFor="subMatType" value="Sub Mattype" />
                  <select
                    id="subMatType"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isStepOneLocked ? 'bg-gray-100' : ''}`}
                    value={subMatType}
                    disabled={isStepOneLocked}
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
                <SecondaryButton type="button" onClick={resetStepOne} disabled={isStepOneLocked}>
                  Reset
                </SecondaryButton>
                <PrimaryButton type="submit" disabled={isGenerating || isStepOneLocked}>
                  {isGenerating ? 'Generating...' : 'GENERATE BUSINESS ID'}
                </PrimaryButton>
              </div>
            </form>
          </div>

          {step === 2 ? (
            <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
              <BusinessSupplyDetail
                mode="create"
                values={detailValues}
                errors={errors}
                uoms={uoms}
                businessSupplyType={underType}
                showSourceField={false}
                onChange={(field, value) => {
                  if (field === 'bomBsDesc') setBomBsDesc(value);
                  if (field === 'searchDesc') setSearchDesc(value);
                  if (field === 'compDescEn') setCompDescEn(value);
                  if (field === 'compDescTh') setCompDescTh(value);
                  if (field === 'uom') setUom(value);
                }}
                onCancel={resetStepOne}
                onSave={handleSave}
                saveError={saveError}
              />
              {successMessage && (
                <div className="mt-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                  {successMessage}
                </div>
              )}
            </div>
          ) : null}
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
