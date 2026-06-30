import BomMaterialForm from '@/Components/BomMaterialForm';
import useBomMaterialFormController from '@/Components/useBomMaterialFormController';
import { router } from '@inertiajs/react';

export default function FgBom({ auth, InputData, subMattypes = [], uoms = [] }) {
  const ownerLevel = 'fg';
  const isSemiFgOwner = false;
  const defaultBackRoute = 'product.view';
  const pageId = InputData?.actionMode === 'create' ? '1CFG' : '2CFG';
  const form = useBomMaterialFormController({
    InputData: { ...(InputData || {}), ownerLevel },
    submitRoute: 'packmaterial.fg-bom.save',
    ownerLevel,
    isSemiFgOwner,
    defaultBackRoute,
  });

  const handleBack = () => {
    router.get(route('product.view'), {
      materialId: form.data.backMaterialId || form.data.fgMaterialId || form.data.materialId || '',
    });
  };

  const handleDelete = handleBack;

  return (
    <BomMaterialForm
      auth={auth}
      headerTitle={`FG BOM - ${InputData?.actionMode === 'edit' ? 'Edit' : 'Create'}`}
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
      levelBomIdLabel="FG BOM ID"
      levelBomDescLabel="FG BOM Description"
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
