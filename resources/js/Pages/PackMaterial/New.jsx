import BomMaterialForm from '@/Components/BomMaterialForm';

export default function NewPackMaterial({ auth, InputData, mattypes = [], subMattypes = [], uoms = [], productCategories = [], productSubCategories = [] }) {
  return (
    <BomMaterialForm
      auth={auth}
      headerTitle="PACK MATERIAL - Create"
      InputData={InputData}
      mattypes={mattypes}
      subMattypes={subMattypes}
      uoms={uoms}
      productCategories={productCategories}
      productSubCategories={productSubCategories}
      submitRoute="packmaterial.create"
    />
  );
}
