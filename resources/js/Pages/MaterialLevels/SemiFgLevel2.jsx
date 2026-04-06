import MaterialLevelForm from '@/Components/MaterialLevelForm';

export default function SemiFgLevel2(props) {
  return (
    <MaterialLevelForm
      {...props}
      title="Semi FG Lv.2"
      levelKey="semiFgLv2"
      levelRoute="material-levels.semi-fg-lv2.new"
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
