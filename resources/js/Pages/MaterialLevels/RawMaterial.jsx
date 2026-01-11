import BomMaterialForm from '@/Components/BomMaterialForm';

export default function RawMaterial(props) {
  return (
    <BomMaterialForm
      auth={props.auth}
      headerTitle="RAW MATERIAL - Create"
      InputData={props.InputData}
      mattypes={props.mattypes}
      subMattypes={props.subMattypes}
      uoms={props.uoms}
      submitRoute="material-levels.raw.save"
    />
  );
}
