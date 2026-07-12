import BusinessSupplyBom from '@/Pages/PackMaterial/BusinessSupplyBom';

export default function BusinessSupplyComponent(props) {
  const actionMode = props?.InputData?.actionMode || 'create';

  return (
    <BusinessSupplyBom
      {...props}
      submitRouteName={actionMode === 'edit' ? 'business-supply.edit-component.save' : 'business-supply.create-component.save'}
      defaultBackRoute="business-supply.existing"
    />
  );
}
