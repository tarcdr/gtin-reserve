import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';

export default function ProductNew({ auth, brands = [], mattypes = [], sites = [], masterUom = [] }) {
  const [showSite, setShowSite] = useState(false);
  const [showBomId, setShowBomId] = useState(false);
  const [step, setStep] = useState(1);
  const [isGeneratingMaterialId, setIsGeneratingMaterialId] = useState(false);
  const subMattypeOptions = ['0', '1', '2', '3'];
  const { data, setData, patch, errors, processing, setError, clearErrors } = useForm({
    brand: '',
    mattype: '',
    subMattype: '',
    finishGoods: '',
    site: '',
    materialId: '',
    bomId: '',
    searchDesc: '',
    fullDescEn: '',
    fullDescTh: '',
    uom: '',
  });

  const getFinishGoodsValue = (mattype, subMattype) => {
    if (!mattype || subMattype === '') {
      return '';
    }

    return mattype === '1' && subMattype === '0'
      ? 'New Product'
      : 'Non New Product';
  };

  const resetGeneratedFields = () => {
    setData('finishGoods', '');
    setData('site', '');
    setData('materialId', '');
    setData('bomId', '');
    setData('searchDesc', '');
    setData('fullDescEn', '');
    setData('fullDescTh', '');
    setData('uom', '');
  };

  const resetBrandMattype = () => {
    setData('brand', '');
    setData('mattype', '');
    setData('subMattype', '');
    resetGeneratedFields();
    clearErrors();
    setStep(1);
  };

  const resetSubMattype = () => {
    setData('subMattype', '');
    resetGeneratedFields();
    clearErrors();
    setStep(2);
  };

  const submit = async (e) => {
    e.preventDefault();

    if (step === 1) {
      let hasError = false;

      if (!data.brand) {
        setError('brand', 'The Brand field is required.');
        hasError = true;
      }

      if (!data.mattype) {
        setError('mattype', 'The Mattype field is required.');
        hasError = true;
      }

      if (hasError) {
        return;
      }

      clearErrors('brand', 'mattype');
      setStep(2);
      return;
    }

    if (step === 2) {
      if (!data.subMattype) {
        setError('subMattype', 'The Sub Mattype field is required.');
        return;
      }

      clearErrors('subMattype', 'materialId');
      setIsGeneratingMaterialId(true);

      try {
        const response = await fetch(route('product.generate-material-id', {
          brand: data.brand,
          mattype: data.mattype,
          subMattype: data.subMattype,
        }), {
          headers: {
            Accept: 'application/json',
          },
        });

        if (!response.ok) {
          throw new Error('Unable to generate Suggest Material ID.');
        }

        const payload = await response.json();
        setData('materialId', payload?.materialId || '');
        setStep(3);
      } catch (error) {
        setError('materialId', 'Unable to generate Suggest Material ID.');
      } finally {
        setIsGeneratingMaterialId(false);
      }

      return;
    }

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
  }, [data.mattype, mattypes]);

  useEffect(() => {
    const nextFinishGoods = getFinishGoodsValue(data.mattype, data.subMattype);
    if (data.finishGoods !== nextFinishGoods) {
      setData('finishGoods', nextFinishGoods);
    }
  }, [data.mattype, data.subMattype, data.finishGoods]);

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">FG Material - Create New Product</h2>}
    >
      <Head title="FG Material - Create New Product" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <form onSubmit={submit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="brand" value="Brand" />
                  <select
                    id="brand"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${step >= 2 ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('brand', e.target.value)}
                    value={data.brand}
                    disabled={step >= 2}
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
                    className={`mt-1 block w-full border-gray-300 rounded-md ${step >= 2 ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('mattype', e.target.value)}
                    value={data.mattype}
                    disabled={step >= 2}
                  >
                    <option value="">---- Select Mattype ----</option>
                    {mattypes?.map(o => (
                      <option key={`mattype-code-${o.code}`} value={o.code}>{o.label}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.mattype} />
                </div>
              </div>

              {step >= 2 && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div>
                    <InputLabel htmlFor="subMattype" value="Sub Mattype" />
                    <select
                      id="subMattype"
                      className={`mt-1 block w-full border-gray-300 rounded-md ${step >= 3 ? 'bg-gray-100' : ''}`}
                      onChange={(e) => setData('subMattype', e.target.value)}
                      value={data.subMattype}
                      disabled={step >= 3}
                    >
                      <option value="">---- Select Sub Mattype ----</option>
                      {subMattypeOptions.map(option => (
                        <option key={`subMattype-code-${option}`} value={option}>{option}</option>
                      ))}
                    </select>

                    <InputError className="mt-2" message={errors.subMattype} />
                    <InputError className="mt-2" message={errors.materialId} />
                  </div>
                </div>
              )}

              {step >= 3 && (
                <>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                      <InputLabel htmlFor="finishGoods" value="Finish Goods" />
                      <TextInput
                        id="finishGoods"
                        className="mt-1 block w-full bg-gray-100"
                        value={data.finishGoods}
                        readOnly
                      />

                      <InputError className="mt-2" message={errors.finishGoods} />
                    </div>
                    {showSite && (
                      <div>
                        <InputLabel htmlFor="site" value="Site" />
                        <select
                          id="site"
                          className="mt-1 block w-full border-gray-300 rounded-md"
                          onChange={(e) => setData('site', e.target.value)}
                          value={data.site}
                        >
                          <option value="">---- Select Site ----</option>
                          {sites?.map(o => (
                            <option key={`site-code-${o.value}`} value={o.value}>{`${o.value} - ${o.label}`}</option>
                          ))}
                        </select>

                        <InputError className="mt-2" message={errors.site} />
                      </div>
                    )}
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                      <InputLabel htmlFor="materialId" value="Suggest Material ID" />

                      <TextInput
                        id="materialId"
                        className="mt-1 block w-full bg-gray-100"
                        value={data.materialId}
                        disabled
                      />
                    </div>
                    {showBomId && (
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
                          <InputLabel htmlFor="bomId" value="Description of BOM ID" />

                          <TextInput
                            id="bomId"
                            className="mt-1 block w-full bg-gray-100"
                            disabled
                          />
                        </div>
                      </div>
                    )}
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
                        value={data.uom}
                      >
                        <option value="">---- Select UOM ----</option>
                        {masterUom?.map(o => (
                          <option key={`uom-code-${o.value}`} value={o.value}>{o.label}</option>
                        ))}
                      </select>

                      <InputError className="mt-2" message={errors.uom} />
                    </div>
                  </div>
                </>
              )}

              <div className="flex items-center justify-center gap-4">
                {step >= 2 && (
                  <SecondaryButton type="button" onClick={resetBrandMattype}>
                    Change Brand / Mattype
                  </SecondaryButton>
                )}
                {step >= 3 && (
                  <SecondaryButton type="button" onClick={resetSubMattype}>
                    Change Sub Mattype
                  </SecondaryButton>
                )}
                <SecondaryButton type="button" onClick={() => window.history.back()}>
                  Back
                </SecondaryButton>
                <PrimaryButton disabled={processing || isGeneratingMaterialId}>
                  {step === 1 ? 'Submit' : step === 2 ? 'Generate Suggest Material ID' : 'Save FG'}
                </PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
