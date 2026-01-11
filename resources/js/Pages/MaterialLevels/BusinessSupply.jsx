import MaterialLevelForm from '@/Components/MaterialLevelForm';

export default function BusinessSupply(props) {
  return (
    <MaterialLevelForm
      {...props}
      title="Business Supply"
      headerTitle="BUSINESS SUPPLY - Create"
      submitRoute="material-levels.business-supply.save"
      backRoute="product.view"
      createComponentRoute="material-levels.business-supply.create-component"
    />
  );
}
