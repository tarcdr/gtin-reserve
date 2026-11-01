import BomMaterialForm from '@/Components/BomMaterialForm';
import useBomMaterialFormController from '@/Components/useBomMaterialFormController';
import PrimaryButton from '@/Components/PrimaryButton';
import { router } from '@inertiajs/react';

export default function RawNew(props) {
  const data = props.InputData || {};
  return <RawNewForm key={`${data.actionMode}:${data.fgBomId}:${data.semiFgLv1BomId}:${data.masterMaterialId}:${data.componentId}`} {...props} />;
}

function RawNewForm({ auth, InputData, subMattypes, uoms, backUrl }) {
  const form = useBomMaterialFormController({ InputData, submitRoute: 'raw-child.save', ownerLevel: 'semiFgLv1', isSemiFgOwner: true, defaultBackRoute: 'packmaterial.semi-fg-lv1-bom.new' });
  const openMode = (actionMode) => router.get(route('raw-child.form'), {
    fgMaterialId: form.data.fgMaterialId, fgBomId: form.data.fgBomId,
    semiFgLv1BomId: form.data.semiFgLv1BomId, masterMaterialId: form.data.masterMaterialId,
    childId: form.data.componentId, actionMode,
  });
  return <BomMaterialForm auth={auth} headerTitle={`Raw Material Component - ${form.isViewMode ? 'Detail' : InputData.actionMode}`} data={form.data}
    detailActions={<PrimaryButton type="button" disabled={!InputData.canEdit} onClick={() => openMode('edit')}>EDIT</PrimaryButton>}
    errors={form.errors} processing={form.processing || !InputData.canEdit} isGeneratingComponentId={form.isGeneratingComponentId}
    subMattypes={subMattypes} uoms={uoms} selectedProductCategory={form.selectedProductCategory} productSubCategoryOptions={form.productSubCategoryOptions} getOptionLabel={form.getOptionLabel}
    isSemiFgOwner isSubMattypeLocked={form.isSubMattypeLocked} isProductSubCatLocked={form.isProductSubCatLocked} isDeleteMode={form.isDeleteMode} isViewMode={form.isViewMode || !InputData.canEdit}
    levelBomIdLabel="SEMI FG LV1 BOM ID" levelBomDescLabel="SEMI FG LV1 BOM Description" primaryActionLabel={form.primaryActionLabel} backLabel={['edit', 'delete'].includes(form.data.actionMode) ? 'BACK TO DETAIL' : 'BACK TO RAW MATERIAL'}
    onSubmit={form.handleSubmit} onBack={() => ['edit', 'delete'].includes(form.data.actionMode) ? openMode('view') : router.get(backUrl)}
    onDelete={() => router.delete(route('raw-child.delete'), { data: { ...form.data, childId: form.data.componentId } })}
    onSubMattypeChange={form.handleSubMattypeChange} onProductSubCategoryChange={form.handleProductSubCategoryChange}
    onSearchDescChange={form.handleSearchDescChange} onFullDescEnChange={form.handleFullDescEnChange} onFullDescThChange={form.handleFullDescThChange} onUomChange={form.handleUomChange} />;
}
