import BomMaterialForm from '@/Components/BomMaterialForm';
import useBomMaterialFormController from '@/Components/useBomMaterialFormController';
import { router } from '@inertiajs/react';

export default function BusinessSupplyBom({ auth, InputData, subMattypes = [], uoms = [] }) {
  const ownerLevel = InputData?.ownerLevel || 'businessSupply';
  const isSemiFgOwner = false;
  const defaultBackRoute = 'business-supply.new';
  const pageId = InputData?.actionMode === 'create' ? '1CBNS' : '2CBNS';
  const pageConfig = {
    headerLabel: 'BUSINESS SUPPLY BOM',
    levelBomIdLabel: 'BUSINESS SUPPLY BOM ID',
    levelBomDescLabel: 'BUSINESS SUPPLY BOM Description',
  };
  const submitRoute = InputData?.actionMode === 'edit' ? 'packmaterial.update' : 'packmaterial.create';
  const actionLabel = InputData?.actionMode === 'edit' ? 'Edit' : 'Create';
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
    router.get(route(defaultBackRoute), {
      mode: 'view',
      fgDetail: form.data.fgDetail,
      materialId: form.data.fgDetail?.materialId,
      semiFgLvBomId: form.data.semiFgLvBomId || form.data.bomId || '',
      semiFgLvBomDesc: form.data.semiFgLvBomDesc || form.data.bomDesc || '',
      bomId: form.data.semiFgLvBomId || form.data.bomId || '',
      bomDesc: form.data.semiFgLvBomDesc || form.data.bomDesc || '',
      levelMaterialId: form.data.ownerDetail?.id,
      searchDesc: form.data.ownerDetail?.searchDesc,
      fullDescEn: form.data.ownerDetail?.fullDescEn,
      fullDescTh: form.data.ownerDetail?.fullDescTh,
      uom: form.data.ownerDetail?.uom,
      components: form.data.components,
    });
  };

  const handleDelete = handleBack;

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
