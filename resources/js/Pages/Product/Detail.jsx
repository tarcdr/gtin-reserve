import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';
import SuccessButton from '@/Components/SuccessButton';
import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import DeleteDebugPanel from '@/Components/DeleteDebugPanel';
import { useState } from 'react';
import { useEffect } from 'react';

const normalizeSemiFgForBomDisplay = (item) => {
  if (!item || String(item?.bomId || '').trim() === '') {
    return null;
  }

  return item;
};

export default function ProductDetail({ auth, InputData, isDisabled = true, isEditMode = false, brands = [], mattypes = [], sites = [], masterUom = [] }) {
  const [showSite, setShowSite] = useState(false);
  const [showBomId, setShowBomId] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleteError, setDeleteError] = useState('');
  const [isDeleting, setIsDeleting] = useState(false);
  const subMattypeOptions = ['0', '1', '2', '3'];
  const { data, setData, patch, errors, processing } = useForm({
    brand: InputData?.brand || '',
    mattype: InputData?.mattype || '',
    subMattype: InputData?.subMattype || '',
    materialId: InputData?.materialId || '',
    fgStatus: InputData?.fgStatus || 'INS',
    bomId: InputData?.bomId || '',
    bomDesc: InputData?.bomDesc || '',
    uom: InputData?.uom || '',
    finishGoods: InputData?.finishGoods || '',
    fullDescEn: InputData?.fullDescEn || '',
    fullDescTh: InputData?.fullDescTh || '',
    searchDesc: InputData?.searchDesc || '',
    site: InputData?.site || '',
    fgComponents: InputData?.fgComponents || [],
    semiFgLv2: normalizeSemiFgForBomDisplay(InputData?.semiFgLv2),
    semiFgLv1: normalizeSemiFgForBomDisplay(InputData?.semiFgLv1),
    businessSupply: InputData?.businessSupply || null,
  });
  const fgComponents = data.fgComponents || [];
  const semiFgLv2 = data.semiFgLv2 || null;
  const semiFgLv1 = data.semiFgLv1 || null;
  const hasSemiFgLv2 = String(semiFgLv2?.bomId || '').trim() !== '';
  const hasSemiFgLv1 = String(semiFgLv1?.bomId || '').trim() !== '';

  const getFinishGoodsValue = (mattype, subMattype) => {
    if (!mattype || subMattype === '') {
      return '';
    }

    return mattype === '1' && subMattype === '0'
      ? 'New Product'
      : 'Non New Product';
  };

  const goToPackMaterial = () => {
    router.get(route('packmaterial.fg-bom.new'), {
      ownerLevel: 'fg',
      backRoute: 'product.view',
      referentMaterialId: data.materialId,
      bomDesc: data.bomDesc,
      fgBomDesc: data.bomDesc,
      actionMode: 'create',
    });
  };

  const goToPackMaterialAction = (actionMode, item) => {
    router.get(route('packmaterial.fg-bom.new'), {
      ownerLevel: 'fg',
      backRoute: 'product.view',
      referentMaterialId: data.materialId,
      actionMode,
      ...(item?.code ? { componentId: item.code } : {}),
    });
  };

  const goToSemiFgLv2 = (levelData = {}) => {
    const mode = levelData.mode || (levelData.levelMaterialId ? 'view' : 'create');
    const payload = {
      mode,
      ...(levelData.levelMaterialId ? { levelMaterialId: levelData.levelMaterialId } : {}),
    };

    if (mode === 'create') {
      payload.referentMaterialId = data.materialId;
    }

    router.get(route('material-levels.semi-fg-lv2.new'), payload);
  };

  const goToSemiFgLv1 = (levelData = {}) => {
    const mode = levelData.mode || (levelData.levelMaterialId ? 'view' : 'create');
    const payload = {
      mode,
      ...(levelData.levelMaterialId ? { levelMaterialId: levelData.levelMaterialId } : {}),
    };

    if (mode === 'create') {
      payload.referentMaterialId = data.materialId;
    }

    router.get(route('material-levels.semi-fg-lv1.new'), payload);
  };

  const submit = (e) => {
    e.preventDefault();
    if (isDisabled) {
      return;
    }
    patch(route('product.update'));
  };

  const goToEdit = () => {
    router.get(route('product.edit'), {
      materialId: data.materialId,
    });
  };

  const goBackToFg = () => {
    if (!isEditMode) {
      router.get(route('dashboard'));
      return;
    }

    router.get(route('product.view'), {
      materialId: data.materialId,
    });
  };

  const openDeleteModal = (target) => {
    setDeleteError('');
    setDeleteTarget(target);
  };

  const closeDeleteModal = () => {
    setDeleteTarget(null);
  };

  const confirmDelete = () => {
    if (!deleteTarget) {
      return;
    }

    setDeleteError('');
    setIsDeleting(true);

    if (deleteTarget.type === 'semiFgLv2') {
      router.delete(route('packmaterial.semi-fg-lv2-bom.delete'), {
        data: {
          ownerLevel: 'semiFgLv2',
          actionMode: 'delete',
          deleteScope: 'level',
          backRoute: 'product.view',
          backMaterialId: data.materialId,
          referentMaterialId: data.materialId,
          fgMaterialId: data.materialId,
          fgBomId: data.bomId,
          bomId: deleteTarget.item?.bomId || '',
          semiFgLvBomId: deleteTarget.item?.bomId || '',
          levelMaterialId: deleteTarget.item?.id || '',
          componentId: '',
          materialIdM5: '',
        },
        preserveScroll: true,
        preserveState: false,
        onError: (nextErrors) => setDeleteError(nextErrors.delete || nextErrors.componentId || nextErrors.semiFgLvBomId || 'Unable to delete Semi FG Level 2.'),
        onSuccess: closeDeleteModal,
        onFinish: () => {
          setIsDeleting(false);
        },
      });
      return;
    }

    if (deleteTarget.type === 'semiFgLv1') {
      router.delete(route('packmaterial.semi-fg-lv1-bom.delete'), {
        data: {
          ownerLevel: 'semiFgLv1',
          actionMode: 'delete',
          deleteScope: 'level',
          backRoute: 'product.view',
          backMaterialId: data.materialId,
          referentMaterialId: data.materialId,
          fgMaterialId: data.materialId,
          fgBomId: data.bomId,
          bomId: deleteTarget.item?.bomId || '',
          semiFgLvBomId: deleteTarget.item?.bomId || '',
          semiFgLv2BomId: semiFgLv2?.bomId || '',
          levelMaterialId: deleteTarget.item?.id || '',
          componentId: '',
          materialIdM4: '',
        },
        preserveScroll: true,
        preserveState: false,
        onError: (nextErrors) => setDeleteError(nextErrors.delete || nextErrors.componentId || nextErrors.semiFgLvBomId || 'Unable to delete Semi FG Level 1.'),
        onSuccess: closeDeleteModal,
        onFinish: () => {
          setIsDeleting(false);
        },
      });
      return;
    }

    router.delete(route('product.delete'), {
      data: {
        materialId: data.materialId,
        fgMaterialId: data.materialId,
        bomId: data.bomId,
        fgBomId: data.bomId,
        brand: data.brand,
        mattype: data.mattype,
        subMattype: data.subMattype,
      },
      preserveScroll: true,
      preserveState: false,
      onError: (nextErrors) => setDeleteError(nextErrors.delete || nextErrors.materialId || nextErrors.bomId || 'Unable to delete FG Material.'),
      onSuccess: closeDeleteModal,
      onFinish: () => {
        setIsDeleting(false);
      },
    });
  };

  const handleComplete = () => {
    setData('fgStatus', 'COM');
  };

  const normalizeStatus = (value) => String(value || '').trim().toUpperCase();
  const hasBomId = (item) => String(item?.bomId || '').trim() !== '';
  const isCompleteStatus = (value) => normalizeStatus(value) === 'COM';
  const lockedIdentityFields = isEditMode || isDisabled;
  const normalizedFgStatus = normalizeStatus(data.fgStatus);
  const hasSemiFgLv2BomId = hasBomId(semiFgLv2);
  const hasSemiFgLv1BomId = hasBomId(semiFgLv1);
  const isSemiFgLv2Complete = isCompleteStatus(semiFgLv2?.statusRow);
  const isSemiFgLv1Complete = isCompleteStatus(semiFgLv1?.statusRow);
  const canCompleteBySemiFgStatus =
    (!hasSemiFgLv2BomId && !hasSemiFgLv1BomId) ||
    (hasSemiFgLv2BomId && isSemiFgLv2Complete && !hasSemiFgLv1BomId) ||
    (hasSemiFgLv2BomId && isSemiFgLv2Complete && hasSemiFgLv1BomId && isSemiFgLv1Complete);
  const isFgCompleteDisabled = normalizedFgStatus === 'COM' || !canCompleteBySemiFgStatus;

  useEffect(() => {
    if (!isDisabled || isEditMode) {
      return;
    }

    setData((currentData) => ({
      ...currentData,
      brand: InputData?.brand || '',
      mattype: InputData?.mattype || '',
      subMattype: InputData?.subMattype || '',
      materialId: InputData?.materialId || '',
      fgStatus: InputData?.fgStatus || 'INS',
      bomId: InputData?.bomId || '',
      bomDesc: InputData?.bomDesc || '',
      uom: InputData?.uom || '',
      finishGoods: InputData?.finishGoods || '',
      fullDescEn: InputData?.fullDescEn || '',
      fullDescTh: InputData?.fullDescTh || '',
      searchDesc: InputData?.searchDesc || '',
      site: InputData?.site || '',
      fgComponents: InputData?.fgComponents || [],
      semiFgLv2: normalizeSemiFgForBomDisplay(InputData?.semiFgLv2),
      semiFgLv1: normalizeSemiFgForBomDisplay(InputData?.semiFgLv1),
      businessSupply: InputData?.businessSupply || null,
    }));
  }, [InputData, isDisabled, isEditMode]);
  
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
  }, [data.mattype]);

  useEffect(() => {
    const nextFinishGoods = getFinishGoodsValue(data.mattype, data.subMattype);
    if (data.finishGoods !== nextFinishGoods) {
      setData('finishGoods', nextFinishGoods);
    }
  }, [data.mattype, data.subMattype, data.finishGoods]);

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">FG Material - Product Detail</h2>}
      pageIdentity={{
        pageId: '4E',
      }}
    >
      <Head title="FG Material - Product Detail" />

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
                    className={`mt-1 block w-full border-gray-300 rounded-md ${lockedIdentityFields ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('brand', e.target.value)}
                    value={data.brand}
                    disabled={lockedIdentityFields}
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
                    className={`mt-1 block w-full border-gray-300 rounded-md ${lockedIdentityFields ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('mattype', e.target.value)}
                    value={data.mattype}
                    disabled={lockedIdentityFields}
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
                    className={`mt-1 block w-full border-gray-300 rounded-md ${lockedIdentityFields ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('subMattype', e.target.value)}
                    value={data.subMattype}
                    disabled={lockedIdentityFields}
                  >
                    <option value="">---- Select Sub Mattype ----</option>
                    {subMattypeOptions.map(option => (
                      <option key={`subMattype-code-${option}`} value={option}>{option}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors.subMattype} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="materialId" value="Material ID FG" />

                  <TextInput
                    id="materialId"
                    className="mt-1 block w-full bg-gray-100"
                    value={data.materialId}
                    disabled
                  />
                </div>
              </div>

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
                      className={`mt-1 block w-full border-gray-300 rounded-md ${lockedIdentityFields ? 'bg-gray-100' : ''}`}
                      onChange={(e) => setData('site', e.target.value)}
                      value={data.site}
                      disabled={lockedIdentityFields}
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
              {showBomId && (
                <>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                      <InputLabel htmlFor="bomId" value="BOM ID For FG" />

                      <TextInput
                        id="bomId"
                        className="mt-1 block w-full bg-gray-100"
                        disabled
                        defaultValue={data.bomId}
                      />
                    </div>
                    <div>
                      <InputLabel htmlFor="bomDesc" value="Description of BOM ID" />

                      <TextInput
                        id="bomDesc"
                        className={`mt-1 block w-full ${isDisabled ? 'bg-gray-100' : ''}`}
                        value={data.bomDesc}
                        onChange={(e) => setData('bomDesc', e.target.value)}
                        disabled={isDisabled}
                      />
                    </div>
                  </div>
                </>
              )}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="searchDesc" value="Search Description" />

                  <TextInput
                    id="searchDesc"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                    value={data.searchDesc}
                    maxLength="40"
                    onChange={(e) => setData('searchDesc', e.target.value)}
                    disabled={isDisabled}
                  />

                  <InputError className="mt-2" message={errors.searchDesc} />
                </div>
                <div>
                  <InputLabel htmlFor="bomStatus" value="FG Status" />

                  <TextInput
                    id="bomStatus"
                    className="mt-1 block w-full bg-gray-100"
                    disabled
                    value={data.fgStatus}
                  />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="fullDescEn" value="Full Description (EN)" />

                  <TextInput
                    id="fullDescEn"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                    value={data.fullDescEn}
                    maxLength="40"
                    onChange={(e) => setData('fullDescEn', e.target.value)}
                    disabled={isDisabled}
                  />

                  <InputError className="mt-2" message={errors.fullDescEn} />
                </div>
                <div>
                  <InputLabel htmlFor="fullDescTh" value="Full Description (TH)" />

                  <TextInput
                    id="fullDescTh"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                    value={data.fullDescTh}
                    maxLength="40"
                    onChange={(e) => setData('fullDescTh', e.target.value)}
                    disabled={isDisabled}
                  />

                  <InputError className="mt-2" message={errors.fullDescTh} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="uom" value="UOM" />
                  <select
                    id="uom"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDisabled ? 'bg-gray-100' : ''}`}
                    onChange={(e) => setData('uom', e.target.value)}
                    defaultValue={data.uom}
                    disabled={isDisabled}
                  >
                    <option value="">---- Select UOM ----</option>
                    {masterUom?.map((o) => {
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
              {isDisabled && (
                <>
                  <fieldset className="border border-gray-300 rounded-md p-4 mt-8">
                    <legend className="px-2 text-gray-600">Components</legend>
                    <div className="flex items-center justify-end gap-4 mb-2">
                        <SuccessButton type="button" onClick={goToPackMaterial} disabled={!isDisabled}>Add Component</SuccessButton>
                    </div>
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                      <table className="w-full text-sm text-left rtl:text-right text-gray-800 dark:text-gray-600">
                        <thead className="text-xs bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
                            <tr>
                                <th scope="col" className="px-6 py-3">
                                    #
                                </th>
                                <th scope="col" className="px-6 py-3">
                                    Component ID
                                </th>
                                <th scope="col" className="px-6 py-3">
                                    Description
                                </th>
                                <th scope="col" className="px-6 py-3" width="100">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                          {fgComponents.length === 0 ? (
                            <tr>
                              <td colSpan="4" className="px-6 py-4 text-gray-500">No FG components yet.</td>
                            </tr>
                          ) : (
                            fgComponents.map((item, index) => (
                              <tr key={`fg-component-${item.code}`}>
                                <th scope="row" className="px-6 py-4">
                                  {index + 1}
                                </th>
                                <th scope="row" className="px-6 py-4">
                                  {item.code}
                                </th>
                                <th scope="row" className="px-6 py-4">
                                  {item.label}
                                </th>
                                <td className="px-6 py-4 flex gap-2">
                                  <PrimaryButton type="button" onClick={() => goToPackMaterialAction('edit', item)}>EDIT</PrimaryButton>
                                  <DangerButton type="button" onClick={() => goToPackMaterialAction('delete', item)}>DELETE</DangerButton>
                                </td>
                              </tr>
                            ))
                          )}
                        </tbody>
                      </table>
                    </div>
                  </fieldset>
                  {data?.mattype === '1' && (
                    <div className="space-y-6 border p-3 border-gray-300 sm:rounded-lg">
                      {hasSemiFgLv2 ? (
                        <div className="space-y-3">
                          <div className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                              <div>
                                <InputLabel htmlFor="semiFgLv2BomId" value="SEMI FG LV2 BOM ID" />
                                <TextInput
                                  id="semiFgLv2BomId"
                                  className="mt-1 block w-full bg-gray-100"
                                  value={semiFgLv2.bomId}
                                  disabled
                                />
                              </div>
                              <div>
                                <InputLabel htmlFor="semiFgLv2BomDesc" value="SEMI FG LV2 BOM Description" />
                                <TextInput
                                  id="semiFgLv2BomDesc"
                                  className="mt-1 block w-full bg-gray-100"
                                  value={semiFgLv2.bomDesc}
                                  disabled
                                />
                              </div>
                            </div>
                          </div>
                          <div className="flex flex-wrap items-center gap-3">
                            <DangerButton
                              type="button"
                              onClick={() => openDeleteModal({ type: 'semiFgLv2', item: semiFgLv2, label: 'Semi FG Level 2' })}
                              disabled={semiFgLv2.statusRow === 'ETS' || hasSemiFgLv1}
                            >
                              DELETE SEMI FG LV2
                            </DangerButton>
                            <PrimaryButton
                              type="button"
                              onClick={() => goToSemiFgLv2({
                                mode: 'view',
                                levelMaterialId: semiFgLv2.id,
                              })}
                              disabled={!isDisabled}
                            >
                              View Semi FG Level 2
                            </PrimaryButton>
                            <span className="text-sm font-medium text-gray-700">
                              STATUS: {semiFgLv2.statusRow || '-'}
                            </span>
                          </div>
                        </div>
                      ) : (
                        <SuccessButton type="button" onClick={() => goToSemiFgLv2()} disabled={!isDisabled}>
                          Create Semi FG Level 2
                        </SuccessButton>
                      )}
                    </div>
                  )}
                  {data?.mattype === '1' && (
                    <div className="space-y-6 border p-3 border-gray-300 sm:rounded-lg">
                      {hasSemiFgLv1 ? (
                        <div className="space-y-3">
                          <div className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                              <div>
                                <InputLabel htmlFor="semiFgLv1BomId" value="SEMI FG LV1 BOM ID" />
                                <TextInput
                                  id="semiFgLv1BomId"
                                  className="mt-1 block w-full bg-gray-100"
                                  value={semiFgLv1.bomId}
                                  disabled
                                />
                              </div>
                              <div>
                                <InputLabel htmlFor="semiFgLv1BomDesc" value="SEMI FG LV1 BOM Description" />
                                <TextInput
                                  id="semiFgLv1BomDesc"
                                  className="mt-1 block w-full bg-gray-100"
                                  value={semiFgLv1.bomDesc}
                                  disabled
                                />
                              </div>
                            </div>
                          </div>
                          <div className="flex flex-wrap items-center gap-3">
                            <DangerButton
                              type="button"
                              onClick={() => openDeleteModal({ type: 'semiFgLv1', item: semiFgLv1, label: 'Semi FG Level 1' })}
                              disabled={semiFgLv1.statusRow === 'ETS'}
                            >
                              DELETE SEMI FG LV1
                            </DangerButton>
                            <PrimaryButton
                              type="button"
                              onClick={() => goToSemiFgLv1({
                                mode: 'view',
                                levelMaterialId: semiFgLv1.id,
                              })}
                              disabled={!isDisabled}
                            >
                              View Semi FG Level 1
                            </PrimaryButton>
                            <span className="text-sm font-medium text-gray-700">
                              STATUS: {semiFgLv1.statusRow || '-'}
                            </span>
                          </div>
                        </div>
                      ) : (
                        <SuccessButton type="button" onClick={() => goToSemiFgLv1()} disabled={!isDisabled || !hasSemiFgLv2}>
                          Create Semi FG Level 1
                        </SuccessButton>
                      )}
                      {!hasSemiFgLv2 && (
                        <div className="text-sm text-gray-500">Create Semi FG Level 2 first.</div>
                      )}
                    </div>
                  )}
                </>
              )}
              <div className="flex items-center justify-center gap-4">
                <SecondaryButton type="button" onClick={goBackToFg}>
                  {isEditMode ? 'BACK TO FG' : 'BACK'}
                </SecondaryButton>
                {isDisabled ? (
                  <>
                    <PrimaryButton type="button" onClick={goToEdit}>Edit FG</PrimaryButton>
                    <DangerButton type="button" onClick={() => openDeleteModal({ type: 'fg', item: data, label: 'FG Material' })}>DELETE FG</DangerButton>
                    <SuccessButton type="button" onClick={handleComplete} disabled={isFgCompleteDisabled}>Complete</SuccessButton>
                  </>
                ) : (
                  <SuccessButton disabled={processing}>Save FG</SuccessButton>
                )}
              </div>
              <InputError className="text-center" message={deleteError || errors.delete} />
            </form>
          </div>
        </div>
      </div>

      <Modal show={Boolean(deleteTarget)} maxWidth="lg" onClose={closeDeleteModal}>
        <div className="p-6 space-y-6">
          <h2 className="text-lg font-medium text-gray-900">Delete {deleteTarget?.label}</h2>
          <p className="text-sm text-gray-600">
            Are you sure you want to delete {deleteTarget?.item?.bomId || deleteTarget?.item?.id || deleteTarget?.item?.materialId || data.materialId || 'this item'}?
          </p>
          <InputError className="mt-2" message={deleteError} />
          <div className="flex justify-end gap-3">
            <SecondaryButton type="button" onClick={closeDeleteModal} disabled={processing || isDeleting}>
              Cancel
            </SecondaryButton>
            <DangerButton type="button" onClick={confirmDelete} disabled={processing || isDeleting}>
              Delete
            </DangerButton>
          </div>
        </div>
      </Modal>
    </AuthenticatedLayout>
  );
}
