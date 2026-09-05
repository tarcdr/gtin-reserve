import BomMaterialForm from '@/Components/BomMaterialForm';
import useBomMaterialFormController from '@/Components/useBomMaterialFormController';
import { router } from '@inertiajs/react';

export default function BusinessSupplyBom({
  auth,
  InputData,
  subMattypes = [],
  uoms = [],
  submitRouteName = null,
  defaultBackRoute = 'business-supply.new',
}) {
  const ownerLevel = InputData?.ownerLevel || 'businessSupply';
  const isSemiFgOwner = false;
  const resolvedBackRoute = InputData?.backRoute || defaultBackRoute;
  const pageId = InputData?.actionMode === 'create' ? '1CBNS' : '2CBNS';
  const pageConfig = {
    headerLabel: 'BUSINESS SUPPLY BOM',
    levelBomIdLabel: 'BUSINESS SUPPLY BOM ID',
    levelBomDescLabel: 'BUSINESS SUPPLY BOM Description',
  };
  const submitRoute = submitRouteName || (InputData?.actionMode === 'edit' ? 'packmaterial.update' : 'packmaterial.create');
  let actionLabel = 'Create';
  if (InputData?.actionMode === 'delete') {
    actionLabel = 'Delete';
  } else if (InputData?.actionMode === 'edit') {
    actionLabel = 'Edit';
  } else if (InputData?.actionMode === 'view') {
    actionLabel = 'View';
  }
  const headerTitle = `${pageConfig.headerLabel} - ${actionLabel}`;
  const initialSubMattype = InputData?.subMattype || '0';
  const form = useBomMaterialFormController({
    InputData: { ...(InputData || {}), ownerLevel, subMattype: initialSubMattype },
    submitRoute,
    ownerLevel,
    isSemiFgOwner,
    defaultBackRoute,
  });

  const handleBack = () => {
    router.get(route(resolvedBackRoute), {
      bizsupId: form.data.ownerDetail?.id || InputData?.bizsupId || '',
    });
  };

  const handleDelete = () => {
    router.patch(route(submitRoute), form.data, {
      preserveScroll: true,
    });
  };

  return (
    <BomMaterialForm
      auth={auth}
      headerTitle={headerTitle}
      pageIdentity={{ pageId }}
      InputData={{ ...(InputData || {}), ownerLevel, subMattype: initialSubMattype }}
      subMattypes={subMattypes}
      uoms={uoms}
      data={form.data}
      errors={form.errors}
      processing={form.processing}
      isGeneratingComponentId={form.isGeneratingComponentId}
      selectedProductCategory={form.selectedProductCategory}
      productSubCategoryOptions={form.productSubCategoryOptions}
      getOptionLabel={form.getOptionLabel}
      isSemiFgOwner={isSemiFgOwner}
      isSubMattypeLocked={form.isSubMattypeLocked}
      isProductSubCatLocked={form.isProductSubCatLocked}
      isDeleteMode={form.isDeleteMode}
      isViewMode={form.isViewMode}
      levelBomIdLabel={pageConfig.levelBomIdLabel}
      levelBomDescLabel={pageConfig.levelBomDescLabel}
      primaryActionLabel={form.primaryActionLabel}
      backLabel={form.backLabel}
      onSubmit={form.handleSubmit}
      onBack={handleBack}
      onDelete={handleDelete}
      onSubMattypeChange={form.handleSubMattypeChange}
      onProductSubCategoryChange={form.handleProductSubCategoryChange}
      onSearchDescChange={form.handleSearchDescChange}
      onFullDescEnChange={form.handleFullDescEnChange}
      onFullDescThChange={form.handleFullDescThChange}
      onUomChange={form.handleUomChange}
    />
  );
}
