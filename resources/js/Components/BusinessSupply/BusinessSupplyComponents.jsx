import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SuccessButton from '@/Components/SuccessButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function BusinessSupplyComponents({
  components = [],
  onAddMaterialId,
  onAddComponent,
  onEditComponent,
  onDeleteComponent,
  onViewComponent,
  isCompleted = false,
}) {
  const componentItems = components.filter((item) => item.sourceType === 'component');
  const materialIdItems = components.filter((item) => item.sourceType === 'matid');

  const renderTable = ({ items, emptyMessage, allowEdit, allowView }) => (
    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
      <table className="w-full text-sm text-left rtl:text-right text-gray-800">
        <thead className="text-xs bg-gray-50">
          <tr>
            <th scope="col" className="px-6 py-3" width="50">#</th>
            <th scope="col" className="px-6 py-3" width="220">Material ID/Component ID</th>
            <th scope="col" className="px-6 py-3">Description</th>
            <th scope="col" className="px-6 py-3 text-center" width="220">Action</th>
          </tr>
        </thead>
        <tbody>
          {items.length === 0 ? (
            <tr>
              <td colSpan="4" className="px-6 py-4 text-gray-500">{emptyMessage}</td>
            </tr>
          ) : (
            items.map((item, index) => {
              const description = item.sourceType === 'matid'
                ? item.searchDesc || item.description || item.label || '-'
                : item.label || item.description || '-';

              return (
                <tr key={`${item.code || item.componentId || index}`}>
                  <th scope="row" className="px-6 py-4">{index + 1}</th>
                  <th scope="row" className="px-6 py-4">{item.code || item.componentId || '-'}</th>
                  <th scope="row" className="px-6 py-4">{description}</th>
                  <td className="px-6 py-4">
                    <div className="flex flex-wrap justify-end gap-2">
                      {allowEdit ? (
                        <PrimaryButton
                          type="button"
                          onClick={() => onEditComponent?.(item)}
                          disabled={!onEditComponent}
                        >
                          {isCompleted ? 'View' : 'Edit'}
                        </PrimaryButton>
                      ) : null}
                      {allowView ? (
                        <SecondaryButton
                          type="button"
                          onClick={() => onViewComponent?.(item)}
                          disabled={!onViewComponent}
                        >
                          View
                        </SecondaryButton>
                      ) : null}
                      <DangerButton
                        type="button"
                        onClick={() => onDeleteComponent?.(item)}
                        disabled={!onDeleteComponent || isCompleted}
                      >
                        Delete
                      </DangerButton>
                    </div>
                  </td>
                </tr>
              );
            })
          )}
        </tbody>
      </table>
    </div>
  );

  return (
    <div className="mt-8 space-y-6">
      <fieldset className="rounded-md border border-gray-300 p-4">
        <legend className="px-2 text-gray-700">Business Supply Components</legend>
        <div className="mb-3 flex items-center justify-end">
          <SuccessButton type="button" onClick={onAddComponent} disabled={!onAddComponent || isCompleted}>
            ADD COMPONENT
          </SuccessButton>
        </div>
        {renderTable({
          items: componentItems,
          emptyMessage: 'No components yet. Select a business supply to view details.',
          allowEdit: !isCompleted,
          allowView: true,
        })}
      </fieldset>

      <fieldset className="rounded-md border border-gray-300 p-4">
        <legend className="px-2 text-gray-700">Business Supply Components From Material ID</legend>
        <div className="mb-3 flex items-center justify-end">
          <SuccessButton type="button" onClick={onAddMaterialId} disabled={!onAddMaterialId || isCompleted}>
            ADD MATERIAL ID
          </SuccessButton>
        </div>
        {renderTable({
          items: materialIdItems,
          emptyMessage: 'No Material ID components yet. Select a business supply to view details.',
          allowEdit: false,
          allowView: true,
        })}
      </fieldset>
    </div>
  );
}
