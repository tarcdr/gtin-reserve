import BomMaterialForm from '@/Components/BomMaterialForm';
import useBomMaterialFormController from '@/Components/useBomMaterialFormController';
import { router } from '@inertiajs/react';

export default function RawNew({ auth, InputData, subMattypes, uoms, backUrl }) {
  const form = useBomMaterialFormController({ InputData, submitRoute: 'raw-child.save', ownerLevel: 'semiFgLv1', isSemiFgOwner: true, defaultBackRoute: 'packmaterial.semi-fg-lv1-bom.new' });
  return <BomMaterialForm auth={auth} headerTitle={`Raw Material Component - ${InputData.actionMode}`} data={form.data}
    errors={form.errors} processing={form.processing || !InputData.canEdit} isGeneratingComponentId={form.isGeneratingComponentId}
    subMattypes={subMattypes} uoms={uoms} selectedProductCategory={form.selectedProductCategory} productSubCategoryOptions={form.productSubCategoryOptions} getOptionLabel={form.getOptionLabel}
    isSemiFgOwner isSubMattypeLocked={form.isSubMattypeLocked} isProductSubCatLocked={form.isProductSubCatLocked} isDeleteMode={form.isDeleteMode} isViewMode={form.isViewMode || !InputData.canEdit}
    levelBomIdLabel="SEMI FG LV1 BOM ID" levelBomDescLabel="SEMI FG LV1 BOM Description" primaryActionLabel="SAVE" backLabel="BACK TO RAW MATERIAL"
    onSubmit={form.handleSubmit} onBack={() => router.get(backUrl)}
    onDelete={() => router.delete(route('raw-child.delete'), { data: { ...form.data, childId: form.data.componentId } })}
    onSubMattypeChange={form.handleSubMattypeChange} onProductSubCategoryChange={form.handleProductSubCategoryChange}
    onSearchDescChange={form.handleSearchDescChange} onFullDescEnChange={form.handleFullDescEnChange} onFullDescThChange={form.handleFullDescThChange} onUomChange={form.handleUomChange} />;
}
