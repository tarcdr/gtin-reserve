import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SuccessButton from '@/Components/SuccessButton';
import TextInput from '@/Components/TextInput';
import DangerButton from '@/Components/DangerButton';
import { getAxiosErrorMessage, getResponseErrorMessage } from '@/Utils/apiError';
import { useEffect, useState } from 'react';

export default function MaterialLevelForm({
  auth,
  title,
  headerTitle,
  pageIdentity = null,
  InputData,
  mattypes = [],
  subMattypes = [],
  uoms = [],
  components = [],
  submitRoute,
  backRoute,
  createComponentRoute,
  levelKey,
  levelRoute,
  fixedMattypeDisplayValue = '',
  subMattypeOptions = null,
  allowSubMattypeSelection = false,
  enableSubMattypeGenerate = false,
  generateRoute = '',
  showLevelBomFields = false,
  levelBomIdLabel = 'Level BOM ID',
  levelBomDescLabel = 'Level BOM Description',
  levelIdLabel = '',
  searchDescLabel = 'Search Description',
  fullDescEnLabel = 'Full Description (EN)',
  fullDescThLabel = 'Full Description (TH)',
  componentLegend = '',
  createComponentLabel = 'Add Component',
  showStorageTable = true,
  showComponentSectionWhenNotView = false,
  hideSubMattypeOnView = false,
  disableSubMattypeOnEdit = false,
  semiFgLv2 = null
}) {
  const [componentRows, setComponentRows] = useState(components);
  const [isGeneratingLevelData, setIsGeneratingLevelData] = useState(false);
  const mode = InputData?.mode || (InputData?.materialId ? 'view' : 'create');
  const isViewMode = mode === 'view';
  const isEditMode = mode === 'edit';
  const isCreateMode = mode === 'create';
  const isSubMattypeReadOnly = isViewMode || (disableSubMattypeOnEdit && isEditMode);
  const { data, setData, patch, processing, errors, setError, clearErrors } = useForm({
    mode,
    fgDetail: InputData?.fgDetail || {},
    fgMaterialId: InputData?.fgMaterialId || InputData?.referentMaterialId || '',
    referentMaterialId: InputData?.referentMaterialId || InputData?.fgMaterialId || '',
    fgBomId: InputData?.fgBomId || '',
    fgBomDesc: InputData?.fgBomDesc || '',
    parentMattype: InputData?.parentMattype || '',
    parentSubMattype: InputData?.parentSubMattype || '',
    mattype: InputData?.mattype || '',
    subMattype: InputData?.subMattype || '',
    levelBomId: InputData?.levelBomId || InputData?.bomId || '',
    levelBomDesc: InputData?.levelBomDesc || '',
    materialId: InputData?.materialId || '',
    searchDesc: InputData?.searchDesc || '',
    fullDescEn: InputData?.fullDescEn || '',
    fullDescTh: InputData?.fullDescTh || '',
    uom: InputData?.uom || '',
    components: components,
    fg_bom_id: semiFgLv2?.fg_bom_id || '',
    semi_fg_lv2_id: semiFgLv2?.semi_fg_lv2_id || '',
  });
  const effectiveSubMattypeOptions = subMattypeOptions || subMattypes;
  const subMattypeDisplayValue = effectiveSubMattypeOptions?.find((item) => String(item.code) === String(data.subMattype))?.label || data.subMattype || '';

  const submit = (e) => {
    e.preventDefault();
    patch(route(submitRoute));
  };

  const handleSubMattypeChange = async (nextSubMattype) => {
    setData('subMattype', nextSubMattype);

    if (!enableSubMattypeGenerate || !generateRoute || isViewMode) {
      return;
    }

    setData('levelBomId', '');
    setData('materialId', '');
    clearErrors('subMattype', 'levelBomId', 'materialId');

    if (!nextSubMattype) {
      return;
    }

    setIsGeneratingLevelData(true);

    try {
      const response = await fetch(route(generateRoute, {
        fgMaterialId: data.fgMaterialId,
        fgBomId: data.fgBomId,
        mattype: data.mattype || fixedMattypeDisplayValue || '',
        subMattype: nextSubMattype,
      }), {
        headers: {
          Accept: 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(await getResponseErrorMessage(response, 'Unable to generate Semi FG Lv data.'));
      }

      const payload = await response.json();
      const nextLevelBomId = payload?.levelBomId || '';
      const nextMaterialId = payload?.materialId || '';

      setData('levelBomId', nextLevelBomId);
      setData('materialId', nextMaterialId);
      clearErrors('levelBomId', 'materialId');
    } catch (error) {
      setData('levelBomId', '');
      setData('materialId', '');
      setError('subMattype', getAxiosErrorMessage(error, 'Unable to generate Semi FG Lv data.'));
    } finally {
      setIsGeneratingLevelData(false);
    }
  };

  useEffect(() => {
    setComponentRows(components);
  }, [components]);

  useEffect(() => {
    setData('components', componentRows);
  }, [componentRows]);

  const buildNextFgDetail = () => {
    const nextFgDetail = {
      ...data.fgDetail,
    };

    const hasLevelDetail = !!(
      data.levelBomId ||
      data.levelBomDesc ||
      data.materialId ||
      data.searchDesc ||
      data.fullDescEn ||
      data.fullDescTh ||
      data.uom ||
      componentRows.length
    );

    if (levelKey && hasLevelDetail) {
      nextFgDetail[levelKey] = {
        bomId: data.levelBomId,
        bomDesc: data.levelBomDesc,
        id: data.materialId,
        desc: data.searchDesc,
        searchDesc: data.searchDesc,
        fullDescEn: data.fullDescEn,
        fullDescTh: data.fullDescTh,
        uom: data.uom,
        components: componentRows,
      };
    }

    return nextFgDetail;
  };

  const buildLevelLookupPayload = (nextMode = mode) => ({
    mode: nextMode,
    levelMaterialId: data.materialId || '',
  });

  const buildSemiFgLv2ComponentPayload = (actionMode, item = {}) => ({
    ownerLevel: 'semiFgLv2',
    actionMode,
    backRoute: levelRoute,
    levelMaterialId: data.materialId || data.levelMaterialId || '',
    backMaterialId: data.levelMaterialId || data.materialId || '',
    referentMaterialId: data.fgMaterialId || data.referentMaterialId || '',
    ...(actionMode !== 'create' && item?.code ? { componentId: item.code } : {}),
  });

  const buildSemiFgLv1ComponentPayload = (actionMode, item = {}) => ({
    ownerLevel: 'semiFgLv1',
    actionMode,
    backRoute: levelRoute,
    levelMaterialId: data.materialId || data.levelMaterialId || '',
    backMaterialId: data.levelMaterialId || data.materialId || '',
    referentMaterialId: data.fgMaterialId || data.referentMaterialId || '',
    ...(actionMode !== 'create' && item?.code ? { componentId: item.code } : {}),
  });

  const goCreateComponent = () => {
    if (levelKey === 'semiFgLv2') {
      router.get(route('packmaterial.new'), buildSemiFgLv2ComponentPayload('create'));
      return;
    }

    if (levelKey === 'semiFgLv1') {
      router.get(route('packmaterial.new'), buildSemiFgLv1ComponentPayload('create'));
      return;
    }

    if (!createComponentRoute) {
      return;
    }

    const createPayload = {
      fgMaterialId: data.fgMaterialId,
      fgBomId: data.fgBomId,
      fgBomDesc: data.fgBomDesc,
      actionMode: 'create',
    };

    Object.assign(createPayload, {
      fgDetail: buildNextFgDetail(),
      components: componentRows,
    });

    if (!isCreateMode) {
      Object.assign(createPayload, {
        levelBomId: data.levelBomId,
        levelBomDesc: data.levelBomDesc,
        materialId: data.materialId,
        searchDesc: data.searchDesc,
        fullDescEn: data.fullDescEn,
        fullDescTh: data.fullDescTh,
        uom: data.uom,
        ownerDetail: {
          bomId: data.levelBomId,
          bomDesc: data.levelBomDesc,
          id: data.materialId,
          desc: data.searchDesc,
          searchDesc: data.searchDesc,
          fullDescEn: data.fullDescEn,
          fullDescTh: data.fullDescTh,
          uom: data.uom,
        },
      });
    }

    router.get(route(createComponentRoute), createPayload);
  };

  const goComponentAction = (actionMode, item = {}) => {
    if (levelKey === 'semiFgLv2') {
      router.get(route('packmaterial.new'), buildSemiFgLv2ComponentPayload(actionMode, item));
      return;
    }

    if (levelKey === 'semiFgLv1') {
      router.get(route('packmaterial.new'), buildSemiFgLv1ComponentPayload(actionMode, item));
      return;
    }

    if (!createComponentRoute) {
      return;
    }
    router.get(route(createComponentRoute), {
      fgMaterialId: data.fgMaterialId,
      fgBomId: data.fgBomId,
      fgBomDesc: data.fgBomDesc,
      levelBomId: data.levelBomId,
      levelBomDesc: data.levelBomDesc,
      materialId: data.materialId,
      fgDetail: buildNextFgDetail(),
      components: componentRows,
      ownerDetail: {
        bomId: data.levelBomId,
        bomDesc: data.levelBomDesc,
        id: data.materialId,
        desc: data.searchDesc,
        searchDesc: data.searchDesc,
        fullDescEn: data.fullDescEn,
        fullDescTh: data.fullDescTh,
        uom: data.uom,
      },
      actionMode,
      componentId: item.code || '',
      searchDesc: item.searchDesc || item.label || '',
      fullDescEn: item.fullDescEn || item.label || '',
      fullDescTh: item.fullDescTh || item.label || '',
      uom: item.uom || '',
      productCat: item.productCat || data.productCat || (item.code ? item.code.slice(0, 2) : ''),
      productSubCat: item.productSubCat || data.productSubCat || (item.code ? item.code.slice(0, 4) : ''),
    });
  };

  const goBack = () => {
    if (isEditMode && levelRoute) {
      router.get(route(levelRoute), buildLevelLookupPayload('view'));
      return;
    }

    if (backRoute === 'product.view') {
      const backMaterialId = data.fgMaterialId || data.referentMaterialId || data.fgDetail?.materialId || data.materialId;
      router.get(route(backRoute), {
        materialId: backMaterialId,
      });
      return;
    }

    window.history.back();
  };

  const goToEdit = () => {
    if (!levelRoute) {
      return;
    }

    router.get(route(levelRoute), buildLevelLookupPayload('edit'));
  };

  const backLabel = isEditMode
    ? `Back to ${title}`
    : backRoute === 'product.view'
      ? 'Back to FG'
      : 'Back';

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
                  <InputLabel htmlFor="fgMaterialId" value="FG Material ID" />
                  <TextInput
                    id="fgMaterialId"
                    className="mt-1 block w-full bg-gray-100"
                    value={data.fgMaterialId}
                    disabled
                  />
                </div>
                <div>
                  <InputLabel htmlFor="fgBomId" value="FG BOM ID" />
                  <TextInput
                    id="fgBomId"
                    className="mt-1 block w-full bg-gray-100"
                    value={data.fgBomId}
                    disabled
                  />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="fgBomDesc" value="FG BOM Description" />
                  <TextInput
                    id="fgBomDesc"
                    className="mt-1 block w-full bg-gray-100"
                    value={data.fgBomDesc}
                    disabled
                  />
                </div>
                {showStorageTable && (
                  <div>
                    <InputLabel htmlFor="storageTable" value="Storage Table" />
                    <TextInput
                      id="storageTable"
                      className="mt-1 block w-full bg-gray-100"
                      value={InputData?.storageTable || ''}
                      disabled
                    />
                  </div>
                )}
              </div>
            </div>
          </div>

          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <form onSubmit={submit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="mattype" value="Mattype" />
                  {fixedMattypeDisplayValue ? (
                    <TextInput
                      id="mattype"
                      className="mt-1 block w-full bg-gray-100"
                      value={fixedMattypeDisplayValue}
                      disabled
                    />
                  ) : (
                    <select
                      id="mattype"
                      className={`mt-1 block w-full border-gray-300 rounded-md ${data.mattype ? 'bg-gray-100' : ''}`}
                      onChange={(e) => setData('mattype', e.target.value)}
                      defaultValue={data.mattype}
                      disabled
                    >
                      <option value="">---- Select Mattype ----</option>
                      {mattypes?.map((o) => (
                        <option key={`mattype-code-${o.code}`} value={o.code}>{o.label}</option>
                      ))}
                    </select>
                  )}
                  <InputError className="mt-2" message={errors.mattype} />
                </div>
                {!(hideSubMattypeOnView && isViewMode) && (
                  <div>
                    <InputLabel htmlFor="subMattype" value="Sub Mattype" />
                    {isSubMattypeReadOnly ? (
                      <TextInput
                        id="subMattype"
                        className="mt-1 block w-full bg-gray-100 text-gray-500 cursor-not-allowed"
                        value={subMattypeDisplayValue}
                        disabled
                      />
                    ) : (
                      <select
                        id="subMattype"
                        className="mt-1 block w-full border-gray-300 rounded-md"
                        onChange={(e) => handleSubMattypeChange(e.target.value)}
                        defaultValue={data.subMattype}
                        disabled={!allowSubMattypeSelection || isGeneratingLevelData}
                      >
                        <option value="">---- Select Sub Mattype ----</option>
                        {effectiveSubMattypeOptions?.map((o) => (
                          <option key={`subMattype-code-${o.code}`} value={o.code}>{o.label}</option>
                        ))}
                      </select>
                    )}
                    <InputError className="mt-2" message={errors.subMattype} />
                  </div>
                )}
              </div>

              {showLevelBomFields && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div>
                    <InputLabel htmlFor="levelBomId" value={levelBomIdLabel} />
                    <TextInput
                      id="levelBomId"
                      className="mt-1 block w-full bg-gray-100"
                      value={data.levelBomId}
                      disabled
                    />
                    <InputError className="mt-2" message={errors.levelBomId} />
                  </div>
                  <div>
                    <InputLabel htmlFor="levelBomDesc" value={levelBomDescLabel} />
                    <TextInput
                      id="levelBomDesc"
                      className={`mt-1 block w-full border-gray-300 rounded-md ${isViewMode ? 'bg-gray-100' : ''}`}
                      value={data.levelBomDesc}
                      maxLength="100"
                      onChange={(e) => setData('levelBomDesc', e.target.value)}
                      disabled={isViewMode}
                    />
                    <InputError className="mt-2" message={errors.levelBomDesc} />
                  </div>
                </div>
              )}

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="materialId" value={levelIdLabel || `${title} ID`} />
                  <TextInput
                    id="materialId"
                    className="mt-1 block w-full bg-gray-100"
                    value={data.materialId}
                    disabled
                  />
                </div>
                <div>
                  <InputLabel htmlFor="searchDesc" value={searchDescLabel} />
                  <TextInput
                    id="searchDesc"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isViewMode ? 'bg-gray-100' : ''}`}
                    value={data.searchDesc}
                    maxLength="40"
                    onChange={(e) => setData('searchDesc', e.target.value)}
                    disabled={isViewMode}
                  />
                  <InputError className="mt-2" message={errors.searchDesc} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="fullDescEn" value={fullDescEnLabel} />
                  <TextInput
                    id="fullDescEn"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isViewMode ? 'bg-gray-100' : ''}`}
                    value={data.fullDescEn}
                    maxLength="40"
                    onChange={(e) => setData('fullDescEn', e.target.value)}
                    disabled={isViewMode}
                  />
                  <InputError className="mt-2" message={errors.fullDescEn} />
                </div>
                <div>
                  <InputLabel htmlFor="fullDescTh" value={fullDescThLabel} />
                  <TextInput
                    id="fullDescTh"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isViewMode ? 'bg-gray-100' : ''}`}
                    value={data.fullDescTh}
                    maxLength="40"
                    onChange={(e) => setData('fullDescTh', e.target.value)}
                    disabled={isViewMode}
                  />
                  <InputError className="mt-2" message={errors.fullDescTh} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="uom" value="UOM" />
                  <select
                    id="uom"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isViewMode ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('uom', e.target.value)}
                    defaultValue={data.uom}
                    disabled={isViewMode}
                  >
                    <option value="">---- Select UOM ----</option>
                    {uoms?.map((o) => {
                      const value = o.value ?? o.code;
                      const label = o.label ?? o.description_uom ?? value;
                      return (
                        <option key={`uom-code-${value}`} value={value}>{`${value} - ${label}`}</option>
                      );
                    })}
                  </select>
                  <InputError className="mt-2" message={errors.uom} />
                </div>
              </div>

              {(isViewMode || (showComponentSectionWhenNotView && !isCreateMode)) && (
                <fieldset className="border border-gray-300 rounded-md p-4">
                  <legend className="px-2 text-gray-600">{componentLegend || `${title} Components`}</legend>
                  {isViewMode && (
                    <div className="flex items-center justify-end gap-4 mb-2">
                      <SuccessButton type="button" onClick={goCreateComponent} disabled={!createComponentRoute}>
                        {createComponentLabel}
                      </SuccessButton>
                    </div>
                  )}
                  <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table className="w-full text-sm text-left rtl:text-right text-gray-800 dark:text-gray-600">
                      <thead className="text-xs bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
                        <tr>
                          <th scope="col" className="px-6 py-3">#</th>
                          <th scope="col" className="px-6 py-3">Component ID</th>
                          <th scope="col" className="px-6 py-3">Description</th>
                          <th scope="col" className="px-6 py-3" width="120">Status</th>
                          <th scope="col" className="px-6 py-3" width="180">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        {componentRows.length === 0 ? (
                          <tr>
                            <td colSpan="5" className="px-6 py-4 text-gray-500">No components yet. Create one to map with FG.</td>
                          </tr>
                        ) : (
                          componentRows.map((item, index) => (
                            <tr key={`${title}-component-${item.code}`}>
                              <th scope="row" className="px-6 py-4">{index + 1}</th>
                              <td className="px-6 py-4">{item.code}</td>
                              <td className="px-6 py-4">{item.label}</td>
                              <td className="px-6 py-4">{item.status}</td>
                              <td className="px-6 py-4">
                                {isViewMode ? (
                                  <div className="flex items-center gap-2">
                                    <PrimaryButton type="button" onClick={() => goComponentAction('edit', item)}>EDIT</PrimaryButton>
                                    <DangerButton type="button" onClick={() => goComponentAction('delete', item)}>DELETE</DangerButton>
                                  </div>
                                ) : (
                                  <span className="text-gray-400">View Only</span>
                                )}
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </fieldset>
              )}

              <div className="flex items-center justify-center gap-4">
                <SecondaryButton type="button" onClick={goBack}>
                  {backLabel}
                </SecondaryButton>
                {isViewMode ? (
                  <PrimaryButton type="button" onClick={goToEdit}>Edit</PrimaryButton>
                ) : (
                  <SuccessButton disabled={processing || isGeneratingLevelData}>Save</SuccessButton>
                )}
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
