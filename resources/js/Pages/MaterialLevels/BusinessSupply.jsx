import MaterialLevelForm from '@/Components/MaterialLevelForm';

export default function BusinessSupply(props) {
  return (
    <MaterialLevelForm
      {...props}
      title="Business Supply"
      levelKey="businessSupply"
      levelRoute="material-levels.business-supply.new"
      headerTitle={
        props?.InputData?.mode === 'view'
          ? 'BUSINESS SUPPLY - Detail'
          : props?.InputData?.mode === 'edit'
            ? 'BUSINESS SUPPLY - Edit'
            : 'BUSINESS SUPPLY - Create'
      }
      submitRoute="material-levels.business-supply.save"
      backRoute="product.view"
      createComponentRoute="material-levels.business-supply.create-component"
    />
  );
}
