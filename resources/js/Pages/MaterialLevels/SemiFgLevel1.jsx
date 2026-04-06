import MaterialLevelForm from '@/Components/MaterialLevelForm';

export default function SemiFgLevel1(props) {
  return (
    <MaterialLevelForm
      {...props}
      title="Semi FG Lv.1"
      levelKey="semiFgLv1"
      levelRoute="material-levels.semi-fg-lv1.new"
      headerTitle={
        props?.InputData?.mode === 'view'
          ? 'SEMI FG LV.1 - Detail'
          : props?.InputData?.mode === 'edit'
            ? 'SEMI FG LV.1 - Edit'
            : 'SEMI FG LV.1 - Create'
      }
      submitRoute="material-levels.semi-fg-lv1.save"
      backRoute="product.view"
      createComponentRoute="material-levels.semi-fg-lv1.create-component"
    />
  );
}
