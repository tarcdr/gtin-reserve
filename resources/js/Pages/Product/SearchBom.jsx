import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';
import ReactSelect from 'react-select';
import { useEffect } from 'react';
import { useState } from 'react';

const FLOW_KEY = 'product.search.flow';

const readFlowState = () => {
  if (typeof window === 'undefined') {
    return {};
  }

  try {
    return JSON.parse(window.sessionStorage.getItem(FLOW_KEY) || '{}') || {};
  } catch {
    return {};
  }
};

const writeFlowState = (state) => {
  if (typeof window === 'undefined') {
    return;
  }

  window.sessionStorage.setItem(FLOW_KEY, JSON.stringify(state));
};

export default function ProductSearchBom({ auth, InputData, isDisabled = true, brands = [], mattypes = [], materials = [] }) {
  const storedFlow = readFlowState();
  const initialOptions = storedFlow.subMattypeOptions ?? InputData?.subMattypeOptions ?? [];
  const [subMattypeOptions, setSubMattypeOptions] = useState(initialOptions);
  const { data, setData, patch, errors, processing } = useForm({
    brand: storedFlow.brand ?? InputData?.brand ?? '',
    mattype: storedFlow.mattype ?? InputData?.mattype ?? '',
    subMattype: storedFlow.subMattype ?? InputData?.subMattype ?? '',
    materialId: InputData?.materialId || '',
    status: InputData?.status || ''
  });

  const selectedMaterial = materials.find(item => item.code === data.materialId) || null;

  const submit = (e) => {
    e.preventDefault();
    patch(route('product.search.bom'));
  };

  useEffect(() => {
    if (!data.mattype) {
      setSubMattypeOptions([]);
      return;
    }

    if (subMattypeOptions.length > 0) {
      return;
    }

    const controller = new AbortController();
    fetch(route('product.sub-mattypes', { mattype: data.mattype }), {
      headers: {
        Accept: 'application/json'
      },
      signal: controller.signal
    })
      .then((response) => response.ok ? response.json() : null)
      .then((payload) => {
        setSubMattypeOptions(payload?.subMattypes || []);
      })
      .catch(() => {});

    return () => controller.abort();
  }, [data.mattype]);

  useEffect(() => {
    if (!data.materialId) {
      if (data.status) {
        setData('status', '');
      }
      return;
    }
    const controller = new AbortController();
    const url = route('product.material-status', { materialId: data.materialId });
    fetch(url, {
      headers: {
        Accept: 'application/json'
      },
      signal: controller.signal
    })
      .then((response) => response.ok ? response.json() : null)
      .then((payload) => {
        if (!payload) {
          return;
        }
        setData('status', payload.status || '');
      })
      .catch(() => {});

    return () => controller.abort();
  }, [data.materialId]);

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">FG Material - Product Search</h2>}
    >
      <Head title="FG Material - Product Search" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <form onSubmit={submit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="brand" value="Brand" />
                  <select
                    id="brand"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('brand', e.target.value)}
                    value={data.brand}
                    disabled={isDisabled}
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
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('mattype', e.target.value)}
                    value={data.mattype}
                    disabled={isDisabled}
                  >
                    <option value="">---- Select Mattype ----</option>
                    {mattypes?.map(o => (
                      <option key={`mattype-code-${o.code}`} value={o.code}>{o.label}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.mattype} />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="subMattype" value="Sub Mattype" />
                  <select
                    id="subMattype"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('subMattype', e.target.value)}
                    value={data.subMattype}
                    disabled={isDisabled}
                  >
                    <option value="">---- Select Sub Mattype ----</option>
                    {subMattypeOptions.map(option => (
                      <option key={`subMattype-code-${option.code}`} value={option.code}>{option.label}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.subMattype} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="materialId" value="Material ID FG" />

                  <ReactSelect
                    options={materials}
                    isSearchable={true}
                    placeholder="---- Select Material ID ----"
                    value={selectedMaterial}
                    onChange={(option) => {
                      setData('materialId', option?.code || '');
                      setData('status', option?.status || '');
                    }}
                    getOptionLabel={(option) => option.label}
                    getOptionValue={(option) => option.code}
                    classNames={{
                      control: () => 'mt-1 block w-full'
                    }}
                  />
                  <InputError className="mt-2" message={errors?.materialId} />
                </div>
                <div>
                  <InputLabel htmlFor="status" value="FG Status" />

                  <TextInput
                    id="status"
                    className="mt-1 block w-full bg-gray-100"
                    disabled
                    value={data.status}
                  />
                </div>
              </div>
              <div className="flex items-center justify-center gap-4">
                <SecondaryButton
                  type="button"
                  onClick={() => {
                    writeFlowState({
                      brand: data.brand,
                      mattype: data.mattype,
                      subMattype: data.subMattype,
                      subMattypeOptions,
                      step: 2,
                    });
                    window.history.back();
                  }}
                >
                  Back
                </SecondaryButton>
                <SecondaryButton
                  type="button"
                  onClick={() => {
                    writeFlowState({
                      brand: data.brand,
                      mattype: data.mattype,
                      subMattype: data.subMattype,
                      subMattypeOptions,
                      step: 1,
                    });
                    window.history.back();
                  }}
                >
                  Change Brand / Mattype
                </SecondaryButton>
                <SecondaryButton type="button" onClick={() => window.history.back()}>
                  Cancel
                </SecondaryButton>
                <PrimaryButton disabled={processing}>Submit</PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
