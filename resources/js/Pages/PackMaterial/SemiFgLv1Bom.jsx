import RawMaterialExistingComponents from '@/Components/RawMaterialExistingComponents';
import BomMaterialForm from '@/Components/BomMaterialForm';
import useBomMaterialFormController from '@/Components/useBomMaterialFormController';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
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
    headerTitle = 'SEMI FG LV1 BOM - Detail';
  }

  return {
    pageId: isEditMode || isDeleteMode ? '2CSML1' : '1CSML1',
    headerTitle: headerTitle,
    submitRoute: 'packmaterial.semi-fg-lv1-bom.save',
    levelBomIdLabel: 'SEMI FG LV1 BOM ID',
    levelBomDescLabel: 'SEMI FG LV1 BOM Description',
  };
}

export default function SemiFgLv1Bom(props) {
  const data = props.InputData || {};
  return <SemiFgLv1BomForm key={`${data.actionMode}:${data.fgBomId}:${data.semiFgLvBomId}:${data.componentId}`} {...props} />;
}

function SemiFgLv1BomForm({ auth, InputData, subMattypes = [], uoms = [] }) {
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

  const openComponent = (actionMode) => router.get(route('packmaterial.semi-fg-lv1-bom.new'), {
    actionMode, fgMaterialId: form.data.fgMaterialId, referentMaterialId: form.data.fgMaterialId,
    fgBomId: form.data.fgBomId, levelMaterialId: form.data.levelMaterialId,
    semiFgLvBomId: form.data.semiFgLvBomId, semiFgLv2BomId: form.data.semiFgLv2BomId,
    componentId: form.data.componentId,
  });
  const handleBack = () => {
    if (['edit', 'delete'].includes(form.data.actionMode)) { openComponent('view'); return; }
    router.get(route('material-levels.semi-fg-lv1.new'), {
      mode: 'view',
      levelMaterialId: form.data.levelMaterialId || form.data.backMaterialId || form.data.materialId || '',
      fgMaterialId: form.data.fgMaterialId, referentMaterialId: form.data.fgMaterialId,
      fgBomId: form.data.fgBomId, levelBomId: form.data.semiFgLvBomId,
      parentLevelBomId: form.data.semiFgLv2BomId,
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
      detailActions={<><PrimaryButton type="button" disabled={!InputData?.canEdit} onClick={() => openComponent('edit')}>EDIT</PrimaryButton><DangerButton type="button" disabled={!InputData?.canDelete} onClick={() => openComponent('delete')}>DELETE</DangerButton></>}
      beforeActions={form.isViewMode && form.data.mattype === '4' && form.data.componentId ? <RawMaterialExistingComponents data={form.data} embedded /> : null}
      detailStatus={form.isViewMode ? InputData?.statusRow || '' : null}
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
      backLabel={['edit', 'delete'].includes(form.data.actionMode) ? 'Back to BOM Detail' : 'Back to Semi FG Lv.1 Detail'}
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
