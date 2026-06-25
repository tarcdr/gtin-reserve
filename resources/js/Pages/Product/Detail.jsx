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
import { useState } from 'react';
import { useEffect } from 'react';

export default function ProductDetail({ auth, InputData, isDisabled = true, isEditMode = false, brands = [], mattypes = [], sites = [], masterUom = [] }) {
  const [showSite, setShowSite] = useState(false);
  const [showBomId, setShowBomId] = useState(false);
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
    semiFgLv2: InputData?.semiFgLv2 || null,
    semiFgLv1: InputData?.semiFgLv1 || null,
    businessSupply: InputData?.businessSupply || null,
  });
  const fgComponents = data.fgComponents || [];
  const semiFgLv2 = data.semiFgLv2 || null;
  const semiFgLv1 = data.semiFgLv1 || null;
  const hasSemiFgLv2 = !!(
    semiFgLv2?.id ||
    semiFgLv2?.searchDesc ||
    semiFgLv2?.fullDescEn ||
    semiFgLv2?.fullDescTh ||
    semiFgLv2?.uom ||
    semiFgLv2?.components?.length
  );
  const hasSemiFgLv1 = !!(
    semiFgLv1?.id ||
    semiFgLv1?.searchDesc ||
    semiFgLv1?.fullDescEn ||
    semiFgLv1?.fullDescTh ||
    semiFgLv1?.uom ||
    semiFgLv1?.components?.length
  );

  const getFinishGoodsValue = (mattype, subMattype) => {
    if (!mattype || subMattype === '') {
      return '';
    }

    return mattype === '1' && subMattype === '0'
      ? 'New Product'
      : 'Non New Product';
  };

  const goToPackMaterial = () => {
    router.get(route('packmaterial.new'), {
      ownerLevel: 'fg',
      backRoute: 'product.view',
      referentMaterialId: data.materialId,
      actionMode: 'create',
    });
  };

  const goToPackMaterialAction = (actionMode, item) => {
    router.get(route('packmaterial.new'), {
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

  const handleDelete = () => {
    if (!window.confirm('Delete this FG material?')) {
      return;
    }
    router.delete(route('product.delete'), {
      data: {
        materialId: data.materialId,
        brand: data.brand,
        mattype: data.mattype,
        subMattype: data.subMattype
      }
    });
  };

  const handleComplete = () => {
    setData('fgStatus', 'COM');
  };

  const lockedIdentityFields = isEditMode || isDisabled;
  
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
                    {masterUom?.map(o => (
                      <option key={`uom-code-${o.value}`} value={o.value}>{o.label}</option>
                    ))}
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
                          <div className="text-sm text-gray-600">Semi FG Level 2</div>
                          <div className="font-semibold">{semiFgLv2.id}</div>
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
                          <div className="text-sm text-gray-600">Semi FG Level 1</div>
                          <div className="font-semibold">{semiFgLv1.id}</div>
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
                    <DangerButton type="button" onClick={handleDelete}>DELETE FG</DangerButton>
                    <SuccessButton type="button" onClick={handleComplete} disabled={data.fgStatus === 'COM'}>Complete</SuccessButton>
                  </>
                ) : (
                  <SuccessButton disabled={processing}>Save FG</SuccessButton>
                )}
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
