import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { getAxiosErrorMessage, getFirstErrorMessage, getResponseErrorMessage } from '@/Utils/apiError';
import {
  buildBomMaterialInitialData,
  buildGenerateComponentIdParams,
  buildProductCategoriesRequestParams,
  getBomMaterialOptionLabel,
  normalizeProductSubCategoryOptions,
} from './useBomMaterialFormController.utils';

export default function useBomMaterialFormController({
  InputData,
  submitRoute,
  ownerLevel,
  isSemiFgOwner,
  defaultBackRoute,
}) {
  const [isGeneratingComponentId, setIsGeneratingComponentId] = useState(false);
  const [productCategoryOptions, setProductCategoryOptions] = useState([]);
  const [productSubCategoryOptions, setProductSubCategoryOptions] = useState([]);
  const { data, setData, patch, errors, processing, setError, clearErrors } = useForm(
    buildBomMaterialInitialData({
      InputData,
      ownerLevel,
      defaultBackRoute,
    })
  );
  const isFgCreateMode = data.ownerLevel === 'fg' && data.actionMode === 'create';
  const isSemiFgCreateMode = Boolean(isSemiFgOwner) && data.actionMode === 'create';
  const isViewMode = data.actionMode === 'view';
  const isEditMode = data.actionMode === 'edit';
  const isDeleteMode = data.actionMode === 'delete';
  const isExistingReadOnlyMode = isEditMode || isDeleteMode || isViewMode;
  const shouldSkipProductCategoryLookup = isExistingReadOnlyMode && Boolean(data.productSubCat);
  const isSubMattypeLocked = isExistingReadOnlyMode;
  const isProductSubCatLocked = isExistingReadOnlyMode;

  const submit = (e) => {
    e.preventDefault();
    patch(route(submitRoute));
  };

  const filteredProductCategories = productCategoryOptions;
  const filteredProductSubCategories = productSubCategoryOptions;
  const currentMatType = data.matType || data.mattype || '';
  const currentSubMattype = data.subMatType || data.subMattype || '';
  const selectedProductCategory = filteredProductCategories.find(
    (option) => option.code === data.productCat
  ) || null;

  const getOptionLabel = getBomMaterialOptionLabel;

  useEffect(() => {
    if (shouldSkipProductCategoryLookup) {
      if (data.productCat) {
        setProductCategoryOptions([{
          code: data.productCat,
          description: '',
        }]);
      }

      setProductSubCategoryOptions([{
        code: data.productSubCat,
        description: '',
      }]);

      return;
    }

    if (!currentSubMattype) {
      setProductCategoryOptions([]);
      setProductSubCategoryOptions([]);
      if (data.productCat && !isEditMode) {
        setData('productCat', '');
      }
      if (data.productSubCat && !isEditMode) {
        setData('productSubCat', '');
      }
      return;
    }

    const controller = new AbortController();
    fetch(route('packmaterial.product-categories', buildProductCategoriesRequestParams({
      subMattype: currentSubMattype,
      ownerLevel: data.ownerLevel,
      mattype: currentMatType,
    })), {
      headers: {
        Accept: 'application/json',
      },
      signal: controller.signal,
      })
      .then((response) => response.ok ? response.json() : null)
      .then((payload) => {
        const options = payload?.productCategories || [];
        const subOptions = payload?.productSubCategories || [];
        setProductCategoryOptions(options);
        const nextProductSubCat = data.productSubCat;
        const normalizedSubOptions = normalizeProductSubCategoryOptions(
          subOptions,
          nextProductSubCat,
          isExistingReadOnlyMode
        );
        setProductSubCategoryOptions(normalizedSubOptions);

        const nextProductCat = payload?.productCat || options[0]?.code || '';
        if (data.productCat !== nextProductCat) {
          setData('productCat', nextProductCat);
        }

        if (!isExistingReadOnlyMode && data.productSubCat) {
          setData('productSubCat', '');
        }
      })
      .catch(() => {});

    return () => controller.abort();
  }, [currentSubMattype]);

  const handleProductSubCategoryChange = async (nextProductSubCat) => {
    setData('productSubCat', nextProductSubCat);

    if (data.ownerLevel === 'businessSupply' && data.actionMode === 'create') {
      setData('componentId', '');
      clearErrors('componentId');

      if (!nextProductSubCat) {
        return;
      }

      setIsGeneratingComponentId(true);

      try {
        const response = await fetch(route('business-supply.generate-component-id', buildGenerateComponentIdParams({
          productSubCat: nextProductSubCat,
          ownerLevel: data.ownerLevel,
        })), {
          headers: {
            Accept: 'application/json',
          },
        });

        if (!response.ok) {
          throw new Error(await getResponseErrorMessage(response, 'Unable to generate Component ID.'));
        }

        const payload = await response.json();
        const payloadError = getFirstErrorMessage(payload, '');
        if (payloadError) {
          throw new Error(payloadError);
        }
        const nextComponentId = payload?.componentId || '';

        if (!nextComponentId) {
          throw new Error('Empty Component ID.');
        }

        setData('componentId', nextComponentId);
        clearErrors('componentId');
      } catch (error) {
        setData('componentId', '');
        setError('componentId', getAxiosErrorMessage(error, 'Unable to generate Component ID.'));
      } finally {
        setIsGeneratingComponentId(false);
      }

      return;
    }

    if (!isFgCreateMode && !isSemiFgCreateMode) {
      return;
    }

    setData('componentId', '');
    clearErrors('componentId');

    if (!nextProductSubCat) {
      return;
    }

    setIsGeneratingComponentId(true);

    try {
      const response = await fetch(route('packmaterial.generate-component-id', buildGenerateComponentIdParams({
        productSubCat: nextProductSubCat,
        ownerLevel: data.ownerLevel,
      })), {
        headers: {
          Accept: 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(await getResponseErrorMessage(response, 'Unable to generate Component ID.'));
      }

      const payload = await response.json();
      const payloadError = getFirstErrorMessage(payload, '');
      if (payloadError) {
        throw new Error(payloadError);
      }
      const nextComponentId = payload?.componentId || '';

      if (!nextComponentId) {
        throw new Error('Empty Component ID.');
      }

      setData('componentId', nextComponentId);
      clearErrors('componentId');
    } catch (error) {
      setData('componentId', '');
      setError('componentId', getAxiosErrorMessage(error, 'Unable to generate Component ID.'));
    } finally {
      setIsGeneratingComponentId(false);
    }
  };

  const handleSubMattypeChange = (nextSubMattype) => {
    setData('subMattype', nextSubMattype);
    setData('subMatType', nextSubMattype);
    setData('productCat', '');
    setData('productSubCat', '');
    setData('componentId', '');
    clearErrors('productCat', 'productSubCat');

    if (isFgCreateMode) {
      setData('componentId', '');
      clearErrors('componentId');
    } else if (data.ownerLevel === 'businessSupply' && data.actionMode === 'create') {
      clearErrors('componentId');
    }
  };

  useEffect(() => {
    if (!currentSubMattype || !data.productCat) {
      if (isExistingReadOnlyMode) {
        return;
      }

      setData('productSubCat', '');
      if (isFgCreateMode || (data.ownerLevel === 'businessSupply' && data.actionMode === 'create')) {
        setData('componentId', '');
      }
    }
  }, [currentSubMattype, data.productCat, data.productSubCat]);

  const primaryActionLabel = data.actionMode === 'edit' ? 'Update' : 'Save';
  const backLabel = data.ownerLevel === 'fg'
    ? 'Back to FG BOM'
    : data.ownerLevel === 'semiFgLv1'
      ? 'Back to Semi FG Lv.1 BOM'
      : data.ownerLevel === 'businessSupply'
        ? 'Back to Business Supply'
        : 'Back to Semi FG Lv.2 BOM';

  return {
    data,
    errors,
    processing,
    isGeneratingComponentId,
    productCategoryOptions,
    productSubCategoryOptions: filteredProductSubCategories,
    selectedProductCategory,
    getOptionLabel,
    isSemiFgOwner: Boolean(isSemiFgOwner),
    isSubMattypeLocked,
    isProductSubCatLocked,
    isDeleteMode,
    isViewMode,
    primaryActionLabel,
    backLabel,
    handleSubmit: submit,
    handleSubMattypeChange,
    handleProductSubCategoryChange,
    handleSearchDescChange: (value) => setData('searchDesc', value),
    handleFullDescEnChange: (value) => setData('fullDescEn', value),
    handleFullDescThChange: (value) => setData('fullDescTh', value),
    handleUomChange: (value) => setData('uom', value),
    setData,
  };
}
