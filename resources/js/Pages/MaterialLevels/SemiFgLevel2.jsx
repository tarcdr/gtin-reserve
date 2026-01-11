import MaterialLevelForm from '@/Components/MaterialLevelForm';

export default function SemiFgLevel2(props) {
  return (
    <MaterialLevelForm
      {...props}
      title="Semi FG Lv.2"
      headerTitle="SEMI FG LV.2 - Create"
      submitRoute="material-levels.semi-fg-lv2.save"
      backRoute="product.view"
      createComponentRoute="material-levels.semi-fg-lv2.create-component"
    />
  );
}
