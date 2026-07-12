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
          Add Material ID
        </SuccessButton>
        <SuccessButton type="button" onClick={onAddComponent} disabled={!onAddComponent}>
          Add Component
        </SuccessButton>
      </div>
      <div className="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table className="w-full text-left text-sm text-gray-800">
          <thead className="bg-gray-50 text-xs">
            <tr>
              <th scope="col" className="px-6 py-3">
                #
              </th>
              <th scope="col" className="px-6 py-3">
                Material ID/Component ID
              </th>
              <th scope="col" className="px-6 py-3">
                Description
              </th>
              <th scope="col" className="px-6 py-3">
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
              components.map((item, index) => (
                <tr key={`${item.code || item.componentId || index}`}>
                  <th scope="row" className="px-6 py-4">
                    {index + 1}
                  </th>
                  <th scope="row" className="px-6 py-4">
                    {item.code || item.componentId || '-'}
                  </th>
                  <td className="px-6 py-4">
                    {item.label || item.description || '-'}
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex flex-wrap gap-2">
                      <PrimaryButton
                        type="button"
                        onClick={() => onEditComponent?.(item)}
                        disabled={!onEditComponent || item.sourceType !== 'component'}
                      >
                        Edit
                      </PrimaryButton>
                      <DangerButton
                        type="button"
                        onClick={() => onDeleteComponent?.(item)}
                        disabled={!onDeleteComponent || item.sourceType !== 'component'}
                      >
                        Delete
                      </DangerButton>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </fieldset>
  );
}
