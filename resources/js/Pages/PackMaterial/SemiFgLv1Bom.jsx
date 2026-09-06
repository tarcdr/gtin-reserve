import BomMaterialForm from '@/Components/BomMaterialForm';
import useBomMaterialFormController from '@/Components/useBomMaterialFormController';
import { router } from '@inertiajs/react';

function buildSemiFgLv1BomConfig(actionMode = 'create') {
  const isEditMode = actionMode === 'edit';
  const isViewMode = actionMode === 'view';
  const isDeleteMode = actionMode === 'delete';
  let headerTitle = 'SEMI FG LV1 BOM - Create';
  if (isEditMode) {
    headerTitle = 'SEMI FG LV1 BOM - Edit';
  } else if (isDeleteMode) {
    headerTitle = 'SEMI FG LV1 BOM - Delete';
  } else if (isViewMode) {
    headerTitle = 'SEMI FG LV1 BOM - View';
  }

  return {
    pageId: isEditMode || isDeleteMode ? '2CSML1' : '1CSML1',
    headerTitle: headerTitle,
    submitRoute: 'packmaterial.semi-fg-lv1-bom.save',
    levelBomIdLabel: 'SEMI FG LV1 BOM ID',
    levelBomDescLabel: 'SEMI FG LV1 BOM Description',
  };
}

export default function SemiFgLv1Bom({ auth, InputData, subMattypes = [], uoms = [] }) {
  const ownerLevel = 'semiFgLv1';
  const isSemiFgOwner = true;
  const config = buildSemiFgLv1BomConfig(InputData?.actionMode);
  const form = useBomMaterialFormController({
    InputData: { ...(InputData || {}), ownerLevel },
    submitRoute: config.submitRoute,
    ownerLevel,
    isSemiFgOwner,
    defaultBackRoute: 'material-levels.semi-fg-lv1.new',
  });

  const handleBack = () => {
    router.get(route('material-levels.semi-fg-lv1.new'), {
      mode: 'view',
      levelMaterialId: form.data.levelMaterialId || form.data.backMaterialId || form.data.materialId || '',
    });
  };

  const handleDelete = () => {
    router.delete(route('packmaterial.semi-fg-lv1-bom.component.delete'), {
      data: form.data,
      preserveScroll: true,
    });
  };

  return (
    <BomMaterialForm
      auth={auth}
      headerTitle={config.headerTitle}
      pageIdentity={{ pageId: config.pageId }}
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
      isViewMode={form.isViewMode}
      levelBomIdLabel={config.levelBomIdLabel}
      levelBomDescLabel={config.levelBomDescLabel}
      primaryActionLabel={form.primaryActionLabel}
      backLabel="Back to Semi FG Lv.1 Detail"
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
