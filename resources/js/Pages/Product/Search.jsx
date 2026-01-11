import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import SecondaryButton from '@/Components/SecondaryButton';

export default function ProductSearch({ auth, brands = [], mattypes = [] }) {
  const { data, setData, patch, errors, processing } = useForm({
    brand: '',
    mattype: '',
    subMattype: ''
  });

  const submit = (e) => {
    e.preventDefault();

    patch(route('product.search'));
  };

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">FG Material - Search</h2>}
    >
      <Head title="FG Material" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <form onSubmit={submit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="brand" value="Brand" />
                  <select
                    id="brand"
                    className="mt-1 block w-full"
                    onChange={(e) => setData('brand', e.target.value)}
                    defaultValue={data?.brand}
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
                    className="mt-1 block w-full"
                    onChange={(e) => setData('mattype', e.target.value)}
                    defaultValue={data?.mattype}
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
                    className="mt-1 block w-full"
                    onChange={(e) => setData('subMattype', e.target.value)}
                    defaultValue={data?.subMattype}
                  >
                    <option value="">---- Select Sub Mattype ----</option>
                    {brands?.map(o => (
                      <option key={`subMattype-code-${o.code}`} value={o.code}>{`${o.code} - ${o?.label || o.code}`}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.subMattype} />
                </div>
              </div>
              <div className="flex items-center justify-center gap-4">
                <SecondaryButton type="button" onClick={() => window.history.back()}>
                  Back
                </SecondaryButton>
                <PrimaryButton disabled={processing}>Search FG</PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
