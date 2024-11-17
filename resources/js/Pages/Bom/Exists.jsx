import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Request({ auth, InputData, boms = [], subBoms = {} }) {
    const [materialList, setMaterialList] = useState([]);

    const { data, setData, patch, errors, processing, recentlySuccessful, transform } = useForm({
        bom: InputData?.bom || ''
    });

    transform(dataSet => ({
      ...dataSet,
      bom: InputData?.bom,
    }));

    const isDisabled = () => InputData?.bom;

    const submit = (e) => {
        e.preventDefault();

        patch(route('material.update'));
    };
    // ฟังก์ชันในการลบ Sub BOM จากรายการ
    const handleRemove = (code) => {
        setSubBoms((prevSubBoms) => ({
            ...prevSubBoms,
            [selectedBom]: prevSubBoms[selectedBom].filter((item) => item.code !== code),
        }));
    };

    useEffect(() => {
      if (recentlySuccessful && InputData?.success) {
        setTimeout(() => {
          window.open('/material/report', '_self');
        }, 500);
      }
    }, [recentlySuccessful, data.materialChoose]);

    useEffect(() => {
      if (data?.bom && subBoms[data.bom]) {
        setMaterialList(subBoms[data.bom]);
      }
    }, [data, subBoms]);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{`BOM - ${data.bom}`}</h2>}
        >
            <Head title="BOM - Exists" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <InputLabel htmlFor="bom" value="BOM ID" />
                                <select
                                    id="bom"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('bom', e.target.value)}
                                    defaultValue={data?.bom}
                                    disabled={isDisabled()}
                                >
                                    <option value="">---- Select BOM ID ----</option>
                                    {boms?.map(o => (
                                        <option key={`bom-code-${o.code}`} value={o.code}>{`${o.code} - ${o.label}`}</option>
                                    ))}
                                </select>

                                <InputError className="mt-2" message={errors.bom} />
                            </div>
                          </div>
                          {/* Sub BOM Items List */}
                          {materialList?.length > 0 && (
                          <ul className="border border-gray-200 rounded-lg divide-y divide-gray-200">
                              {materialList.map((item, index) => (
                                  <li key={item.code} className="flex justify-between items-center p-3 bg-white hover:bg-gray-50">
                                      <span>{`${index + 1}. ${item.label}`}</span>
                                  </li>
                              ))}
                          </ul>
                          )}
                          {(InputData?.bom) && (
                            <div className="flex items-center justify-center gap-4">
                                <Link href={route('rm.component_request')}>
                                    <SecondaryButton>
                                        Back
                                    </SecondaryButton>
                                </Link>
                                <PrimaryButton disabled={processing}>Finnish RM Go to Templete</PrimaryButton>
                            </div>
                          )}
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
