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
  let levelBomIdLabel = 'New BOM ID';
  let levelBomDescLabel = 'New BOM Description';
  if (ownerLevel === 'semiFgLv1') {
    headerLabel = 'RAW';
    levelBomIdLabel = 'SEMI FG LV1 BOM ID';
    levelBomDescLabel = 'SEMI FG LV1 BOM Description';
  } else if (ownerLevel === 'semiFgLv2') {
    headerLabel = 'SEMI';
    levelBomIdLabel = 'SEMI FG LV2 BOM ID';
    levelBomDescLabel = 'SEMI FG LV2 BOM Description';
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
      levelBomIdLabel={levelBomIdLabel}
      levelBomDescLabel={levelBomDescLabel}
    />
  );
}
