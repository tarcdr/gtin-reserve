import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useEffect } from 'react';

export default function BomMaterialForm({
  auth,
  headerTitle,
  InputData,
  mattypes = [],
  subMattypes = [],
  uoms = [],
  productCategories = [],
  productSubCategories = [],
  submitRoute
}) {
  const { data, setData, patch, errors, processing } = useForm({
    actionMode: InputData?.actionMode || 'create',
    ownerLevel: InputData?.ownerLevel || 'fg',
    bomId: InputData?.bomId || '',
    bomDesc: InputData?.bomDesc || '',
    mattype: InputData?.mattype || '',
    subMattype: InputData?.subMattype || '',
    fgDetail: InputData?.fgDetail || {},
    ownerDetail: InputData?.ownerDetail || {},
    components: InputData?.components || [],
    fgMaterialId: InputData?.fgMaterialId || '',
    fgBomId: InputData?.fgBomId || '',
    fgBomDesc: InputData?.fgBomDesc || '',
    levelMaterialId: InputData?.levelMaterialId || '',
    levelSearchDesc: InputData?.levelSearchDesc || '',
    levelFullDescEn: InputData?.levelFullDescEn || '',
    levelFullDescTh: InputData?.levelFullDescTh || '',
    levelUom: InputData?.levelUom || '',
    productCat: InputData?.productCat || '',
    productSubCat: InputData?.productSubCat || '',
    componentId: InputData?.componentId || '',
    searchDesc: InputData?.searchDesc || '',
    fullDescEn: InputData?.fullDescEn || '',
    fullDescTh: InputData?.fullDescTh || '',
    uom: InputData?.uom || ''
  });

  const submit = (e) => {
    e.preventDefault();
    patch(route(submitRoute));
  };

  const filteredProductSubCategories = productSubCategories.filter(
    (option) => option.productCatCode === data.productCat
  );

  useEffect(() => {
    if (!data.productCat && data.productSubCat) {
      setData('productSubCat', '');
      return;
    }

    const hasSelectedSubCategory = filteredProductSubCategories.some(
      (option) => option.code === data.productSubCat
    );

    if (data.productSubCat && !hasSelectedSubCategory) {
      setData('productSubCat', '');
    }
  }, [data.productCat, data.productSubCat]);

  const backToSemiFgLv2 = () => {
    if (data.ownerLevel === 'fg') {
      router.get(route('product.view'), data.fgDetail);
      return;
    }

    const levelRoute = data.ownerLevel === 'semiFgLv1'
      ? 'material-levels.semi-fg-lv1.new'
      : data.ownerLevel === 'businessSupply'
        ? 'material-levels.business-supply.new'
        : 'material-levels.semi-fg-lv2.new';

    router.get(route(levelRoute), {
      mode: 'view',
      fgDetail: data.fgDetail,
      materialId: data.fgDetail?.materialId,
      bomId: data.fgDetail?.bomId,
      bomDesc: data.fgDetail?.bomDesc,
      levelMaterialId: data.ownerDetail?.id,
      searchDesc: data.ownerDetail?.searchDesc,
      fullDescEn: data.ownerDetail?.fullDescEn,
      fullDescTh: data.ownerDetail?.fullDescTh,
      uom: data.ownerDetail?.uom,
      components: data.components,
    });
  };

  const deleteFromSemiFgLv2 = () => {
    const filteredComponents = data.components.filter((item) => item.code !== data.componentId);

    if (data.ownerLevel === 'fg') {
      router.get(route('product.view'), {
        ...data.fgDetail,
        fgComponents: filteredComponents,
      });
      return;
    }

    const levelRoute = data.ownerLevel === 'semiFgLv1'
      ? 'material-levels.semi-fg-lv1.new'
      : data.ownerLevel === 'businessSupply'
        ? 'material-levels.business-supply.new'
        : 'material-levels.semi-fg-lv2.new';

    router.get(route(levelRoute), {
      mode: 'view',
      fgDetail: data.fgDetail,
      materialId: data.fgDetail?.materialId,
      bomId: data.fgDetail?.bomId,
      bomDesc: data.fgDetail?.bomDesc,
      levelMaterialId: data.ownerDetail?.id,
      searchDesc: data.ownerDetail?.searchDesc,
      fullDescEn: data.ownerDetail?.fullDescEn,
      fullDescTh: data.ownerDetail?.fullDescTh,
      uom: data.ownerDetail?.uom,
      components: filteredComponents,
    });
  };

  const primaryActionLabel = data.actionMode === 'edit' ? 'Update' : 'Save';
  const backLabel = data.ownerLevel === 'fg'
    ? 'Back to FG'
    : data.ownerLevel === 'semiFgLv1'
      ? 'Back to Semi FG Lv.1'
      : data.ownerLevel === 'businessSupply'
        ? 'Back to Business Supply'
        : 'Back to Semi FG Lv.2';

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">{headerTitle}</h2>}
    >
      <Head title={headerTitle} />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-2">
          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="bomId" value="New BOM ID" />
                  <TextInput
                    id="bomId"
                    className="mt-1 block w-full bg-gray-100"
                    disabled
                    value={data.bomId}
                  />
                </div>
              </div>
            </div>
          </div>

          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <form onSubmit={submit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="mattype" value="Mattype" />
                  <select
                    id="mattype"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${data?.mattype !== '' ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('mattype', e.target.value)}
                    defaultValue={data.mattype}
                    disabled={data?.mattype !== ''}
                  >
                    <option value="">---- Select Mattype ----</option>
                    {mattypes?.map((o) => (
                      <option key={`mattype-code-${o.code}`} value={o.code}>{o.label}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.mattype} />
                </div>
                <div>
                  <InputLabel htmlFor="subMattype" value="Sub Mattype" />
                  <select
                    id="subMattype"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${data?.subMattype !== '' ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('subMattype', e.target.value)}
                    defaultValue={data.subMattype}
                    disabled={data?.subMattype !== ''}
                  >
                    <option value="">---- Select Sub Mattype ----</option>
                    {subMattypes?.map((o) => (
                      <option key={`subMattype-code-${o.code}`} value={o.code}>{`${o.label}`}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.subMattype} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="productCat" value="Product Category" />
                  <select
                    id="productCat"
                    className="mt-1 block w-full border-gray-300 rounded-md"
                    onChange={(e) => setData('productCat', e.target.value)}
                    value={data.productCat}
                  >
                    <option value="">---- Select Product Category ----</option>
                    {productCategories?.map((option) => (
                      <option key={`productCat-code-${option.code}`} value={option.code}>
                        {`${option.code} - ${option.label}`}
                      </option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.productCat} />
                </div>
                <div>
                  <InputLabel htmlFor="productSubCat" value="Product SUB Category" />
                  <select
                    id="productSubCat"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${!data.productCat ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('productSubCat', e.target.value)}
                    value={data.productSubCat}
                    disabled={!data.productCat}
                  >
                    <option value="">---- Select Product SUB Category ----</option>
                    {filteredProductSubCategories.map((option) => (
                      <option key={`productSubCat-code-${option.code}`} value={option.code}>
                        {`${option.code} - ${option.label}`}
                      </option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.productSubCat} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="componentId" value="Component ID" />

                  <TextInput
                    id="componentId"
                    className="mt-1 block w-full bg-gray-100"
                    disabled
                    value={data.componentId}
                  />
                </div>
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
                  <InputLabel htmlFor="fullDescEn" value="Description (EN)" />

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
                  <InputLabel htmlFor="fullDescTh" value="Description (TH)" />

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
                    defaultValue={data.uom}
                  >
                    <option value="">---- Select UOM ----</option>
                    {uoms?.map((o) => {
                      const value = o.value ?? o.code;
                      const label = o.label ?? value;
                      return (
                        <option key={`uom-code-${value}`} value={value}>{`${value} - ${label}`}</option>
                      );
                    })}
                  </select>

                  <InputError className="mt-2" message={errors.uom} />
                </div>
              </div>
              <div className="flex items-center justify-center gap-4">
                <SecondaryButton type="button" onClick={backToSemiFgLv2}>
                  {backLabel}
                </SecondaryButton>
                {data.actionMode !== 'delete' && (
                  <PrimaryButton disabled={processing}>{primaryActionLabel}</PrimaryButton>
                )}
                {data.actionMode !== 'create' && (
                  <SecondaryButton type="button" onClick={deleteFromSemiFgLv2}>
                    Delete
                  </SecondaryButton>
                )}
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
