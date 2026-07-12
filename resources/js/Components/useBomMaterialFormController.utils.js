export function buildBomMaterialInitialData({
  InputData = {},
  ownerLevel = 'fg',
  defaultBackRoute = 'product.view',
}) {
  return {
    actionMode: InputData?.actionMode || 'create',
    ownerLevel: ownerLevel || InputData?.ownerLevel || 'fg',
    backRoute: InputData?.backRoute || defaultBackRoute,
    backMaterialId: InputData?.backMaterialId || InputData?.referentMaterialId || InputData?.materialId || InputData?.fgMaterialId || '',
    bomId: InputData?.bomId || '',
    bomBsId: InputData?.bomBsId || InputData?.bomId || '',
    bomDesc: InputData?.bomDesc || '',
    bomBsDesc: InputData?.bomBsDesc || InputData?.bomDesc || '',
    semiFgLvBomId: InputData?.semiFgLvBomId || InputData?.bomId || '',
    semiFgLvBomDesc: InputData?.semiFgLvBomDesc || InputData?.bomDesc || '',
    mattype: InputData?.mattype || '',
    matType: InputData?.matType || InputData?.mattype || '',
    subMattype: InputData?.subMattype || '',
    subMatType: InputData?.subMatType || InputData?.subMattype || '',
    fgDetail: InputData?.fgDetail || {},
    ownerDetail: InputData?.ownerDetail || {},
    components: InputData?.components || [],
    fgMaterialId: InputData?.fgMaterialId || '',
    fgBomId: InputData?.fgBomId || '',
    fgBomDesc: InputData?.fgBomDesc || '',
    levelMaterialId: InputData?.levelMaterialId || '',
    levelSearchDesc: InputData?.levelSearchDesc || '',
    levelFullDescEn: InputData?.levelFullDescEn || '',
    levelFullDescTh: InputData?.levelFullDescTh || '',
    levelUom: InputData?.levelUom || '',
    site: InputData?.site || InputData?.fgDetail?.semiFgLv2?.site || InputData?.ownerDetail?.site || '',
    productCat: InputData?.productCat || '',
    productSubCat: InputData?.productSubCat || '',
    componentId: InputData?.componentId || '',
    searchDesc: InputData?.searchDesc || '',
    fullDescEn: InputData?.fullDescEn || '',
    fullDescTh: InputData?.fullDescTh || '',
    uom: InputData?.uom || '',
  };
}

export function getBomMaterialOptionLabel(option) {
  return `${option.code}${option.description ? ` - ${option.description}` : ''}`;
}

export function normalizeProductSubCategoryOptions(options = [], currentProductSubCat = '', shouldPreserve = false) {
  if (!shouldPreserve || !currentProductSubCat) {
    return options;
  }

  if (options.some((option) => option.code === currentProductSubCat)) {
    return options;
  }

  return [...options, { code: currentProductSubCat, description: '' }];
}

export function buildProductCategoriesRequestParams({
  subMattype,
  ownerLevel,
  mattype,
}) {
  return {
    subMattype,
    ownerLevel,
    mattype: mattype || '5',
  };
}

export function buildGenerateComponentIdParams({
  productSubCat,
  ownerLevel,
}) {
  return {
    productSubCat,
    ownerLevel,
  };
}
