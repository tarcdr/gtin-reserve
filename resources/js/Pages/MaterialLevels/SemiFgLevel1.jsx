import MaterialLevelForm from '@/Components/MaterialLevelForm';

export default function SemiFgLevel1(props) {
  const pageId = props?.InputData?.mode === 'create' ? '1SML1' : '2SML1';

  return (
    <MaterialLevelForm
      {...props}
      title="Semi FG Lv.1"
      levelKey="semiFgLv1"
      levelRoute="material-levels.semi-fg-lv1.new"
      pageIdentity={{ pageId }}
      disableSubMattypeOnEdit={true}
      fixedMattypeDisplayValue="3"
      allowSubMattypeSelection
      enableSubMattypeGenerate
      generateRoute="material-levels.semi-fg-lv1.generate"
      showLevelBomFields
      levelBomIdLabel="SEMI FG LV1 BOM ID"
      levelBomDescLabel="SEMI FG LV1 BOM Description"
      showParentLevelBomFields
      parentLevelBomIdLabel="SEMI FG LV2 BOM ID"
      parentLevelBomDescLabel="SEMI FG LV2 BOM Description"
      levelIdLabel="SEMI FG LV1 ID"
      searchDescLabel="SEMI FG LV1 Search Description"
      fullDescEnLabel="SEMI FG LV1 Full Description (EN)"
      fullDescThLabel="SEMI FG LV1 Full Description (TH)"
      componentLegend="Semi FG Lv.1 Components"
      createComponentLabel="Create Component"
      showStorageTable={false}
      headerTitle={
        props?.InputData?.mode === 'view'
          ? 'SEMI FG LV.1 - Detail'
          : props?.InputData?.mode === 'edit'
            ? 'SEMI FG LV.1 - Edit'
            : 'SEMI FG LV.1 - Create'
      }
      submitRoute="material-levels.semi-fg-lv1.save"
      completeRoute="material-levels.semi-fg-lv1.complete"
      backRoute="product.view"
      createComponentRoute="material-levels.semi-fg-lv1.create-component"
    />
  );
}
