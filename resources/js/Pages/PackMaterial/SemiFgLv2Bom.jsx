import BomMaterialForm from '@/Components/BomMaterialForm';
import useBomMaterialFormController from '@/Components/useBomMaterialFormController';
import { router } from '@inertiajs/react';

export default function SemiFgLv2Bom({ auth, InputData, subMattypes = [], uoms = [] }) {
  const ownerLevel = 'semiFgLv2';
  const isSemiFgOwner = true;
  const defaultBackRoute = 'material-levels.semi-fg-lv2.new';
  const pageId = InputData?.actionMode === 'create' ? '1CSML2' : '2CSML2';
  const form = useBomMaterialFormController({
    InputData: { ...(InputData || {}), ownerLevel },
    submitRoute: 'packmaterial.semi-fg-lv2-bom.save',
    ownerLevel,
    isSemiFgOwner,
    defaultBackRoute,
  });

  const handleBack = () => {
    router.get(route(defaultBackRoute), {
      mode: 'view',
      levelMaterialId: form.data.backMaterialId || form.data.levelMaterialId || form.data.materialId || '',
      semiFgLvBomId: form.data.semiFgLvBomId || form.data.bomId || '',
      semiFgLvBomDesc: form.data.semiFgLvBomDesc || form.data.bomDesc || '',
      bomId: form.data.semiFgLvBomId || form.data.bomId || '',
      bomDesc: form.data.semiFgLvBomDesc || form.data.bomDesc || '',
    });
  };

  const handleDelete = () => {
    router.delete(route('packmaterial.semi-fg-lv2-bom.delete'), {
      data: form.data,
      preserveScroll: true,
    });
  };

  return (
    <BomMaterialForm
      auth={auth}
      headerTitle={`SEMI FG LV2 BOM - ${InputData?.actionMode === 'edit' ? 'Edit' : (InputData?.actionMode === 'delete' ? 'Delete' : 'Create')}`}
      pageIdentity={{ pageId }}
      InputData={{ ...(InputData || {}), ownerLevel }}
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
      levelBomIdLabel="SEMI FG LV2 BOM ID"
      levelBomDescLabel="SEMI FG LV2 BOM Description"
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
