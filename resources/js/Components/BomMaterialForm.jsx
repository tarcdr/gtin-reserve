import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useEffect, useState } from 'react';
import DangerButton from './DangerButton';

export default function BomMaterialForm({
  auth,
  headerTitle,
  pageIdentity = null,
  InputData,
  subMattypes = [],
  uoms = [],
  submitRoute
}) {
  const [isGeneratingComponentId, setIsGeneratingComponentId] = useState(false);
  const [productCategoryOptions, setProductCategoryOptions] = useState([]);
  const [productSubCategoryOptions, setProductSubCategoryOptions] = useState([]);
  const ownerLevel = InputData?.ownerLevel || 'fg';
  const defaultBackRoute = ownerLevel === 'semiFgLv2'
    ? 'material-levels.semi-fg-lv2.new'
    : ownerLevel === 'semiFgLv1'
      ? 'material-levels.semi-fg-lv1.new'
    : 'product.view';
  const { data, setData, patch, errors, processing, setError, clearErrors } = useForm({
    actionMode: InputData?.actionMode || 'create',
    ownerLevel,
    backRoute: InputData?.backRoute || defaultBackRoute,
    backMaterialId: InputData?.backMaterialId || InputData?.referentMaterialId || InputData?.materialId || InputData?.fgMaterialId || '',
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
    site: InputData?.site || InputData?.fgDetail?.semiFgLv2?.site || InputData?.ownerDetail?.site || '',
    productCat: InputData?.productCat || '',
    productSubCat: InputData?.productSubCat || '',
    componentId: InputData?.componentId || '',
    searchDesc: InputData?.searchDesc || '',
    fullDescEn: InputData?.fullDescEn || '',
    fullDescTh: InputData?.fullDescTh || '',
    uom: InputData?.uom || ''
  });
  const isFgCreateMode = data.ownerLevel === 'fg' && data.actionMode === 'create';
  const isSemiFgCreateMode = ['semiFgLv1', 'semiFgLv2'].includes(data.ownerLevel) && data.actionMode === 'create';
  const isEditMode = data.actionMode === 'edit';
  const isDeleteMode = data.actionMode === 'delete';
  const isSubMattypeLocked = isEditMode;
  const isProductSubCatLocked = isEditMode;

  const submit = (e) => {
    e.preventDefault();
    patch(route(submitRoute));
  };

  const filteredProductCategories = productCategoryOptions;
  const filteredProductSubCategories = productSubCategoryOptions;
  const selectedProductCategory = filteredProductCategories.find(
    (option) => option.code === data.productCat
  ) || null;

  const getOptionLabel = (option) => `${option.code}${option.description ? ` - ${option.description}` : ''}`;

  useEffect(() => {
    if (!data.subMattype) {
      setProductCategoryOptions([]);
      setProductSubCategoryOptions([]);
      if (data.productCat) {
        setData('productCat', '');
      }
      if (data.productSubCat) {
        setData('productSubCat', '');
      }
      return;
    }

    const controller = new AbortController();
    fetch(route('packmaterial.product-categories', {
      subMattype: data.subMattype,
      ownerLevel: data.ownerLevel,
      mattype: data.mattype || '5',
    }), {
      headers: {
        Accept: 'application/json',
      },
      signal: controller.signal,
      })
      .then((response) => response.ok ? response.json() : null)
      .then((payload) => {
        const options = payload?.productCategories || [];
        const subOptions = payload?.productSubCategories || [];
        setProductCategoryOptions(options);
        setProductSubCategoryOptions(subOptions);

        const nextProductCat = payload?.productCat || options[0]?.code || '';
        if (data.productCat !== nextProductCat) {
          setData('productCat', nextProductCat);
        }

        if (!isEditMode && data.productSubCat) {
          setData('productSubCat', '');
        }
      })
      .catch(() => {});

    return () => controller.abort();
  }, [data.subMattype]);

  const handleProductSubCategoryChange = async (nextProductSubCat) => {
    setData('productSubCat', nextProductSubCat);

    if (!isFgCreateMode && !isSemiFgCreateMode) {
      return;
    }

    setData('componentId', '');
    clearErrors('componentId');

    if (!nextProductSubCat) {
      return;
    }

    setIsGeneratingComponentId(true);

    try {
      const response = await fetch(route('packmaterial.generate-component-id', {
        productSubCat: nextProductSubCat,
        ownerLevel: data.ownerLevel,
      }), {
        headers: {
          Accept: 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error('Unable to generate Component ID.');
      }

      const payload = await response.json();
      const payloadError = (payload?.error || '').trim();
      if (payloadError) {
        throw new Error(payloadError);
      }
      const nextComponentId = payload?.componentId || '';

      if (!nextComponentId) {
        throw new Error('Empty Component ID.');
      }

      setData('componentId', nextComponentId);
      clearErrors('componentId');
    } catch (error) {
      setData('componentId', '');
      setError('componentId', 'Unable to generate Component ID.');
    } finally {
      setIsGeneratingComponentId(false);
    }
  };

  const handleSubMattypeChange = (nextSubMattype) => {
    setData('subMattype', nextSubMattype);
    setData('productCat', '');
    setData('productSubCat', '');
    clearErrors('productCat', 'productSubCat');

    if (isFgCreateMode) {
      setData('componentId', '');
      clearErrors('componentId');
    }
  };

  useEffect(() => {
    if (!data.subMattype || !data.productCat) {
      setData('productSubCat', '');
      if (isFgCreateMode) {
        setData('componentId', '');
      }
    }
  }, [data.subMattype, data.productCat, data.productSubCat]);

  const backToSemiFgLv2 = () => {
    if (data.ownerLevel === 'fg') {
      router.get(route(data.backRoute || 'product.view'), {
        materialId: data.backMaterialId || data.fgMaterialId || data.materialId || data.backMaterialId,
      });
      return;
    }

    if (data.ownerLevel === 'semiFgLv2') {
      router.get(route(data.backRoute || 'material-levels.semi-fg-lv2.new'), {
        mode: 'view',
        levelMaterialId: data.levelMaterialId || data.materialId || '',
      });
      return;
    }

    if (data.ownerLevel === 'semiFgLv1') {
      router.get(route(data.backRoute || 'material-levels.semi-fg-lv1.new'), {
        mode: 'view',
        levelMaterialId: data.levelMaterialId || data.materialId || '',
      });
      return;
    }

    const levelRoute = data.ownerLevel === 'semiFgLv1'
      ? 'material-levels.semi-fg-lv1.new'
      : data.ownerLevel === 'businessSupply'
        ? 'business-supply.new'
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
    if (data.ownerLevel === 'fg') {
      router.get(route(data.backRoute || 'product.view'), {
        materialId: data.backMaterialId || data.fgMaterialId || data.materialId || data.backMaterialId,
      });
      return;
    }

    if (data.ownerLevel === 'semiFgLv2') {
      router.get(route(data.backRoute || 'material-levels.semi-fg-lv2.new'), {
        mode: 'view',
        levelMaterialId: data.levelMaterialId || data.materialId || '',
      });
      return;
    }

    if (data.ownerLevel === 'semiFgLv1') {
      router.get(route(data.backRoute || 'material-levels.semi-fg-lv1.new'), {
        mode: 'view',
        levelMaterialId: data.levelMaterialId || data.materialId || '',
      });
      return;
    }

    const levelRoute = data.ownerLevel === 'semiFgLv1'
      ? 'material-levels.semi-fg-lv1.new'
      : data.ownerLevel === 'businessSupply'
        ? 'business-supply.new'
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
      pageIdentity={pageIdentity}
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
                  <TextInput
                    id="mattype"
                    className={`mt-1 block w-full bg-gray-100 ${!data.subMattype ? 'opacity-60' : ''}`}
                    disabled
                    value={data.mattype}
                  />

                  <InputError className="mt-2" message={errors.mattype} />
                </div>
                <div>
                  <InputLabel htmlFor="subMattype" value="Sub Mattype" />
                  <select
                    id="subMattype"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isSubMattypeLocked ? 'bg-gray-100 text-gray-500' : ''}`}
                    onChange={(e) => handleSubMattypeChange(e.target.value)}
                    value={data.subMattype}
                    disabled={isSubMattypeLocked || isDeleteMode}
                  >
                    {subMattypes?.map((o) => (
                      <option key={`subMattype-code-${o.code}`} value={o.code}>{getOptionLabel(o)}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.subMattype} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="productCat" value="Product Category" />
                  <TextInput
                    id="productCat"
                    className={`mt-1 block w-full bg-gray-100 ${!data.subMattype ? 'opacity-60' : ''}`}
                    disabled
                    value={selectedProductCategory ? getOptionLabel(selectedProductCategory) : ''}
                  />

                  <InputError className="mt-2" message={errors.productCat} />
                </div>
                <div>
                  <InputLabel htmlFor="productSubCat" value="Product SUB Category" />
                  <select
                    id="productSubCat"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${!data.productCat || isProductSubCatLocked ? 'bg-gray-100 text-gray-500' : ''}`}
                    onChange={(e) => handleProductSubCategoryChange(e.target.value)}
                    value={data.productSubCat}
                    disabled={!data.productCat || isProductSubCatLocked || isDeleteMode}
                  >
                    <option value="">---- Select Product SUB Category ----</option>
                    {filteredProductSubCategories.map((option) => (
                      <option key={`productSubCat-code-${option.code}`} value={option.code}>
                        {getOptionLabel(option)}
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

                  <InputError className="mt-2" message={errors.componentId} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="searchDesc" value="Search Description" />

                  <TextInput
                    id="searchDesc"
                    className={`mt-1 block w-full ${isDeleteMode ? 'bg-gray-100 text-gray-500' : ''}`}
                    value={data.searchDesc}
                    maxLength="40"
                    onChange={(e) => setData('searchDesc', e.target.value)}
                    disabled={isDeleteMode}
                  />

                  <InputError className="mt-2" message={errors.searchDesc} />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="fullDescEn" value="Description (EN)" />

                  <TextInput
                    id="fullDescEn"
                    className={`mt-1 block w-full ${isDeleteMode ? 'bg-gray-100 text-gray-500' : ''}`}
                    value={data.fullDescEn}
                    maxLength="40"
                    onChange={(e) => setData('fullDescEn', e.target.value)}
                    disabled={isDeleteMode}
                  />

                  <InputError className="mt-2" message={errors.fullDescEn} />
                </div>
                <div>
                  <InputLabel htmlFor="fullDescTh" value="Description (TH)" />

                  <TextInput
                    id="fullDescTh"
                    className={`mt-1 block w-full ${isDeleteMode ? 'bg-gray-100 text-gray-500' : ''}`}
                    value={data.fullDescTh}
                    maxLength="40"
                    onChange={(e) => setData('fullDescTh', e.target.value)}
                    disabled={isDeleteMode}
                  />

                  <InputError className="mt-2" message={errors.fullDescTh} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="uom" value="UOM" />
                  <select
                    id="uom"
                    className={`mt-1 block w-full ${isDeleteMode ? 'bg-gray-100 text-gray-500' : ''}`}
                    onChange={(e) => setData('uom', e.target.value)}
                    defaultValue={data.uom}
                    disabled={isDeleteMode}
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
                  <PrimaryButton disabled={processing || isGeneratingComponentId}>{primaryActionLabel}</PrimaryButton>
                )}
                {data.actionMode === 'delete' && (
                  <DangerButton type="button" onClick={deleteFromSemiFgLv2}>
                    Delete
                  </DangerButton>
                )}
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
