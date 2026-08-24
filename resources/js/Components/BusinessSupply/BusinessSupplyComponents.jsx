import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SuccessButton from '@/Components/SuccessButton';

export default function BusinessSupplyComponents({
  components = [],
  onAddMaterialId,
  onAddComponent,
  onEditComponent,
  onDeleteComponent,
}) {
  return (
    <fieldset className="mt-8 rounded-md border border-gray-300 p-4">
      <legend className="px-2 text-gray-700">Business Supply Components</legend>
      <div className="mb-3 flex items-center justify-end gap-3">
        <SuccessButton type="button" onClick={onAddMaterialId} disabled={!onAddMaterialId}>
          ADD MATERIAL ID
        </SuccessButton>
        <SuccessButton type="button" onClick={onAddComponent} disabled={!onAddComponent}>
          ADD COMPONENT
        </SuccessButton>
      </div>
      <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <table className="w-full text-sm text-left rtl:text-right text-gray-800">
          <thead className="text-xs bg-gray-50">
              <tr>
                  <th scope="col" className="px-6 py-3" width="50">
                      #
                  </th>
                  <th scope="col" className="px-6 py-3" width="220">
                      Material ID/Component ID
                  </th>
                  <th scope="col" className="px-6 py-3">
                      Description
                  </th>
                  <th scope="col" className="px-6 py-3" width="220">
                      Action
                  </th>
              </tr>
          </thead>
          <tbody>
            {components.length === 0 ? (
              <tr>
                <td colSpan="4" className="px-6 py-4 text-gray-500">
                  No components yet. Select a business supply to view details.
                </td>
              </tr>
            ) : (
              components.map((item, index) => {
                const description = item.sourceType === 'matid'
                  ? item.searchDesc || item.description || item.label || '-'
                  : item.label || item.description || '-';

                return (
                  <tr key={`${item.code || item.componentId || index}`}>
                    <th scope="row" className="px-6 py-4">
                      {index + 1}
                    </th>
                    <th scope="row" className="px-6 py-4">
                      {item.code || item.componentId || '-'}
                    </th>
                    <th scope="row" className="px-6 py-4">
                      {description}
                    </th>
                    <td className="px-6 py-4 flex gap-2">
                      <div className="flex flex-wrap gap-2">
                        <PrimaryButton
                          type="button"
                          onClick={() => onEditComponent?.(item)}
                          disabled={!onEditComponent}
                        >
                          Edit
                        </PrimaryButton>
                        <DangerButton
                          type="button"
                          onClick={() => onDeleteComponent?.(item)}
                          disabled={!onDeleteComponent}
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
    </fieldset>
  );
}
