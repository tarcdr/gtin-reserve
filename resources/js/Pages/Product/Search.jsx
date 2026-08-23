import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import SecondaryButton from '@/Components/SecondaryButton';
import DeleteDebugPanel from '@/Components/DeleteDebugPanel';
import { useState } from 'react';

export default function ProductSearch({ auth, InputData, brands = [], mattypes = [] }) {
  const initialBrand = InputData?.brand ?? '';
  const initialMattype = InputData?.mattype ?? '';
  const initialSubMattype = InputData?.subMattype ?? '';
  const initialOptions = InputData?.subMattypeOptions ?? [];
  const initialStep = InputData?.startStep ? Number(InputData.startStep) : (initialBrand && initialMattype ? 2 : 1);
  const [step, setStep] = useState(initialStep);
  const [subMattypeOptions, setSubMattypeOptions] = useState(initialOptions);
  const [subMattypeLoadError, setSubMattypeLoadError] = useState('');
  const { data, setData, patch, errors, processing, setError, clearErrors } = useForm({
    brand: initialBrand,
    mattype: initialMattype,
    subMattype: initialSubMattype,
    subMattypeOptions: initialOptions,
  });
  const pageId = `${Math.min(Math.max(step, 1), 3)}E`;

  const goBack = () => {
    if (step >= 2) {
      setSubMattypeLoadError('');
      clearErrors('subMattype');
      setStep(1);
      return;
    }

    window.history.back();
  };

  const loadSubMattypeOptions = async (mattypeValue) => {
    const response = await fetch(route('product.sub-mattypes', { mattype: mattypeValue }), {
      headers: {
        Accept: 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Unable to load Sub Mattype options.');
    }

    const payload = await response.json();
    const options = payload?.subMattypes || [];
    setSubMattypeOptions(options);
    setData('subMattypeOptions', options);
    return options;
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

      clearErrors('brand', 'mattype', 'subMattype');
      if (subMattypeOptions.length > 0) {
        setData('subMattypeOptions', subMattypeOptions);
        setStep(2);
        return;
      }

      setSubMattypeLoadError('');
      try {
        const options = await loadSubMattypeOptions(data.mattype);
        setData('subMattypeOptions', options);
        setStep(2);
      } catch (error) {
        setSubMattypeLoadError('Unable to load Sub Mattype options.');
        return;
      }

      return;
    }

    patch(route('product.search.find'));
  };

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">FG Material - Search</h2>}
      pageIdentity={{ pageId }}
    >
      <Head title="FG Material" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <DeleteDebugPanel />
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
                    onChange={(e) => {
                      const nextMattype = e.target.value;
                      setData('mattype', nextMattype);
                      setData('subMattype', '');
                      setData('subMattypeOptions', []);
                      setSubMattypeOptions([]);
                      setSubMattypeLoadError('');
                      clearErrors('subMattype');
                    }}
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
              {subMattypeLoadError && (
                <div className="text-sm text-red-600">
                  {subMattypeLoadError}
                </div>
              )}
              {step >= 2 && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div>
                    <InputLabel htmlFor="subMattype" value="Sub Mattype" />
                    <select
                      id="subMattype"
                      className="mt-1 block w-full border-gray-300 rounded-md"
                      onChange={(e) => {
                        const nextSubMattype = e.target.value;
                        setData('subMattype', nextSubMattype);
                      }}
                      value={data.subMattype}
                    >
                      <option value="">---- Select Sub Mattype ----</option>
                      {subMattypeOptions.map(option => (
                        <option key={`subMattype-code-${option.code}`} value={option.code}>{option.label}</option>
                      ))}
                    </select>

                    <InputError className="mt-2" message={errors.subMattype} />
                  </div>
                </div>
              )}
              <div className="flex items-center justify-center gap-4">
                <SecondaryButton type="button" onClick={goBack}>
                  Back
                </SecondaryButton>
                <PrimaryButton disabled={processing}>{step === 1 ? 'Next' : 'Search FG'}</PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
