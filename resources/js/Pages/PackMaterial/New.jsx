import BomMaterialForm from '@/Components/BomMaterialForm';

export default function NewPackMaterial({ auth, InputData, subMattypes = [], uoms = [] }) {
  const ownerLevel = InputData?.ownerLevel || 'fg';
  const modePrefix = InputData?.actionMode === 'create' ? '1' : '2';
  const ownerSuffixMap = {
    fg: 'CFG',
    semiFgLv1: 'SML1',
    semiFgLv2: 'SML2',
    businessSupply: 'BNS',
  };
  const ownerSuffix = ownerSuffixMap[ownerLevel] || 'PM';
  const pageId = ownerLevel && ownerLevel !== 'fg'
    ? `${modePrefix}C${ownerSuffix}`
    : `${modePrefix}${ownerSuffix}`;
  const initialSubMattype = InputData?.subMattype || '0';
  const submitRoute = InputData?.actionMode === 'edit' ? 'packmaterial.update' : 'packmaterial.create';
  let headerLabel = 'PACK';
  if (ownerLevel === 'semiFgLv1') {
    headerLabel = 'RAW';
  }
  const headerTitle = InputData?.actionMode === 'edit' ? `${headerLabel} MATERIAL - Edit` : `${headerLabel} MATERIAL - Create`;

  return (
    <BomMaterialForm
      auth={auth}
      headerTitle={headerTitle}
      pageIdentity={{ pageId }}
      InputData={{ ...(InputData || {}), ownerLevel, subMattype: initialSubMattype }}
      subMattypes={subMattypes}
      uoms={uoms}
      submitRoute={submitRoute}
    />
  );
}
