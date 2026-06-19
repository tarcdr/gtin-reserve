import BomMaterialForm from '@/Components/BomMaterialForm';

export default function NewPackMaterial({ auth, InputData, mattypes = [], subMattypes = [], uoms = [], productCategories = [], productSubCategories = [] }) {
  const modePrefix = InputData?.actionMode === 'create' ? '1' : '2';
  const ownerSuffixMap = {
    fg: 'CFG',
    semiFgLv1: 'SML1',
    semiFgLv2: 'SML2',
    businessSupply: 'BNS',
  };
  const pageId = `${modePrefix}${ownerSuffixMap[InputData?.ownerLevel] || 'PM'}`;
  const submitRoute = InputData?.actionMode === 'edit' ? 'packmaterial.update' : 'packmaterial.create';
  const headerTitle = InputData?.actionMode === 'edit' ? 'PACK MATERIAL - Edit' : 'PACK MATERIAL - Create';

  return (
    <BomMaterialForm
      auth={auth}
      headerTitle={headerTitle}
      pageIdentity={{ pageId }}
      InputData={InputData}
      mattypes={mattypes}
      subMattypes={subMattypes}
      uoms={uoms}
      productCategories={productCategories}
      productSubCategories={productSubCategories}
      submitRoute={submitRoute}
    />
  );
}
