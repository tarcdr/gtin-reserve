import MaterialLevelForm from '@/Components/MaterialLevelForm';

export default function SemiFgLevel2(props) {
  return (
    <MaterialLevelForm
      {...props}
      title="Semi FG Lv.2"
      levelKey="semiFgLv2"
      levelRoute="material-levels.semi-fg-lv2.new"
      fixedMattypeDisplayValue="2"
      subMattypeOptions={[
        { code: '0', label: '0' },
        { code: '1', label: '1' },
        { code: '2', label: '2' },
      ]}
      allowSubMattypeSelection
      enableSubMattypeGenerate
      generateRoute="material-levels.semi-fg-lv2.generate"
      showLevelBomFields
      levelBomIdLabel="SEMI FG LV2 BOM ID"
      levelBomDescLabel="SEMI FG LV2 BOM Description"
      levelIdLabel="SEMI FG LV2 ID"
      searchDescLabel="SEMI FG LV2 Search Description"
      fullDescEnLabel="SEMI FG LV2 Full Description (EN)"
      fullDescThLabel="SEMI FG LV2 Full Description (TH)"
      componentLegend="Semi FG Lv.2 Components"
      createComponentLabel="Create Component"
      showStorageTable={false}
      showComponentSectionWhenNotView
      headerTitle={
        props?.InputData?.mode === 'view'
          ? 'SEMI FG LV.2 - Detail'
          : props?.InputData?.mode === 'edit'
            ? 'SEMI FG LV.2 - Edit'
            : 'SEMI FG LV.2 - Create'
      }
      submitRoute="material-levels.semi-fg-lv2.save"
      backRoute="product.view"
      createComponentRoute="material-levels.semi-fg-lv2.create-component"
    />
  );
}
