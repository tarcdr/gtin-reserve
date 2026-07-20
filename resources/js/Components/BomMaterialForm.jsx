import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import SuccessButton from '@/Components/SuccessButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import DangerButton from './DangerButton';

export default function BomMaterialForm({
  auth,
  headerTitle,
  pageIdentity = null,
  data,
  errors = {},
  processing = false,
  isGeneratingComponentId = false,
  subMattypes = [],
  uoms = [],
  selectedProductCategory = null,
  productSubCategoryOptions = [],
  getOptionLabel = (option) => `${option.code}${option.description ? ` - ${option.description}` : ''}`,
  isSemiFgOwner = false,
  isSubMattypeLocked = false,
  isProductSubCatLocked = false,
  isDeleteMode = false,
  levelBomIdLabel = 'New BOM ID',
  levelBomDescLabel = 'New BOM Description',
  primaryActionLabel = 'Save',
  backLabel = 'Back',
  onSubmit,
  onBack,
  onDelete,
  onSubMattypeChange,
  onProductSubCategoryChange,
  onSearchDescChange,
  onFullDescEnChange,
  onFullDescThChange,
  onUomChange,
}) {
  const saveError = data?.actionMode !== 'delete' ? (errors?.save || errors?.componentId || '') : '';

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
                  <InputLabel htmlFor={isSemiFgOwner ? 'semiFgLvBomId' : 'bomId'} value={levelBomIdLabel} />
                  <TextInput
                    id={isSemiFgOwner ? 'semiFgLvBomId' : 'bomId'}
                    className="mt-1 block w-full bg-gray-100"
                    disabled
                    value={isSemiFgOwner ? data?.semiFgLvBomId : data?.bomId}
                  />
                </div>
                <div>
                  <InputLabel htmlFor={isSemiFgOwner ? 'semiFgLvBomDesc' : 'bomDesc'} value={levelBomDescLabel} />
                  <TextInput
                    id={isSemiFgOwner ? 'semiFgLvBomDesc' : 'bomDesc'}
                    className="mt-1 block w-full bg-gray-100"
                    disabled
                    value={isSemiFgOwner ? data?.semiFgLvBomDesc : data?.bomDesc}
                  />
                </div>
              </div>
            </div>
          </div>

          <div className="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <form onSubmit={onSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="mattype" value="Mattype" />
                  <TextInput
                    id="mattype"
                    className={`mt-1 block w-full bg-gray-100 ${!data?.subMattype ? 'opacity-60' : ''}`}
                    disabled
                    value={data?.mattype}
                  />

                  <InputError className="mt-2" message={errors?.mattype} />
                </div>
                <div>
                  <InputLabel htmlFor="subMattype" value="Sub Mattype" />
                  <select
                    id="subMattype"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isSubMattypeLocked ? 'bg-gray-100 text-gray-500' : ''}`}
                    onChange={(e) => onSubMattypeChange?.(e.target.value)}
                    value={data?.subMattype}
                    disabled={isSubMattypeLocked || isDeleteMode}
                  >
                    <option value="">---- Select Sub Mattype ----</option>
                    {subMattypes?.map((o) => (
                      <option key={`subMattype-code-${o.code}`} value={o.code}>{getOptionLabel(o)}</option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors?.subMattype} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="productCat" value="Product Category" />
                  <TextInput
                    id="productCat"
                    className={`mt-1 block w-full bg-gray-100 ${!data?.subMattype ? 'opacity-60' : ''}`}
                    disabled
                    value={selectedProductCategory ? getOptionLabel(selectedProductCategory) : ''}
                  />

                  <InputError className="mt-2" message={errors?.productCat} />
                </div>
                <div>
                  <InputLabel htmlFor="productSubCat" value="Product SUB Category" />
                  <select
                    id="productSubCat"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${!data?.productCat || isProductSubCatLocked ? 'bg-gray-100 text-gray-500' : ''}`}
                    onChange={(e) => onProductSubCategoryChange?.(e.target.value)}
                    value={data?.productSubCat}
                    disabled={!data?.productCat || isProductSubCatLocked || isDeleteMode}
                  >
                    <option value="">---- Select Product SUB Category ----</option>
                    {productSubCategoryOptions?.map((option) => (
                      <option key={`productSubCat-code-${option.code}`} value={option.code}>
                        {getOptionLabel(option)}
                      </option>
                    ))}
                  </select>

                  <InputError className="mt-2" message={errors?.productSubCat} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="componentId" value="Component ID" />

                  <TextInput
                    id="componentId"
                    className="mt-1 block w-full bg-gray-100"
                    disabled
                    value={data?.componentId}
                  />

                  <InputError className="mt-2" message={saveError === errors?.componentId ? '' : errors?.componentId} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="searchDesc" value="Search Description" />

                  <TextInput
                    id="searchDesc"
                    className={`mt-1 block w-full ${isDeleteMode ? 'bg-gray-100 text-gray-500' : ''}`}
                    value={data?.searchDesc}
                    maxLength="40"
                    onChange={(e) => onSearchDescChange?.(e.target.value)}
                    disabled={isDeleteMode}
                  />

                  <InputError className="mt-2" message={errors?.searchDesc} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="fullDescEn" value="Description (EN)" />

                  <TextInput
                    id="fullDescEn"
                    className={`mt-1 block w-full ${isDeleteMode ? 'bg-gray-100 text-gray-500' : ''}`}
                    value={data?.fullDescEn}
                    maxLength="40"
                    onChange={(e) => onFullDescEnChange?.(e.target.value)}
                    disabled={isDeleteMode}
                  />

                  <InputError className="mt-2" message={errors?.fullDescEn} />
                </div>
                <div>
                  <InputLabel htmlFor="fullDescTh" value="Description (TH)" />

                  <TextInput
                    id="fullDescTh"
                    className={`mt-1 block w-full ${isDeleteMode ? 'bg-gray-100 text-gray-500' : ''}`}
                    value={data?.fullDescTh}
                    maxLength="40"
                    onChange={(e) => onFullDescThChange?.(e.target.value)}
                    disabled={isDeleteMode}
                  />

                  <InputError className="mt-2" message={errors?.fullDescTh} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                  <InputLabel htmlFor="uom" value="UOM" />
                  <select
                    id="uom"
                    className={`mt-1 block w-full border-gray-300 rounded-md ${isDeleteMode ? 'bg-gray-100 text-gray-500' : ''}`}
                    onChange={(e) => onUomChange?.(e.target.value)}
                    value={data?.uom}
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

                  <InputError className="mt-2" message={errors?.uom} />
                </div>
              </div>

              <div className="flex flex-col items-stretch justify-center gap-3 md:flex-row md:items-center">
                <SecondaryButton type="button" onClick={onBack}>
                  {backLabel}
                </SecondaryButton>
                {data?.actionMode !== 'delete' && (
                  <SuccessButton disabled={processing || isGeneratingComponentId}>{primaryActionLabel}</SuccessButton>
                )}
                {data?.actionMode === 'delete' && (
                  <DangerButton type="button" onClick={onDelete}>
                    Delete
                  </DangerButton>
                )}
              </div>
              {saveError ? (
                <p className="text-center text-sm font-semibold text-red-600">
                  {saveError}
                </p>
              ) : null}
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
