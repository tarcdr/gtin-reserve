import SuccessButton from '@/Components/SuccessButton';

export default function BusinessSupplyComponents({ components = [], onAddComponent }) {
  return (
    <fieldset className="border border-gray-300 rounded-md p-4 mt-8">
      <legend className="px-2 text-gray-600">Business Supply Components</legend>
      <div className="flex items-center justify-end gap-4 mb-2">
        <SuccessButton type="button" onClick={onAddComponent} disabled={!onAddComponent}>
          Add Component
        </SuccessButton>
      </div>
      <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <table className="w-full text-sm text-left rtl:text-right text-gray-800 dark:text-gray-600">
          <thead className="text-xs bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
            <tr>
              <th scope="col" className="px-6 py-3">
                #
              </th>
              <th scope="col" className="px-6 py-3">
                Component ID
              </th>
              <th scope="col" className="px-6 py-3">
                Description
              </th>
              <th scope="col" className="px-6 py-3">
                Status
              </th>
              <th scope="col" className="px-6 py-3" width="100">
                Action
              </th>
            </tr>
          </thead>
          <tbody>
            {components.length === 0 ? (
              <tr>
                <td colSpan="5" className="px-6 py-4 text-gray-500">
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
                  <th scope="row" className="px-6 py-4">
                    {item.label || item.description || '-'}
                  </th>
                  <td className="px-6 py-4">{item.status || '-'}</td>
                  <td className="px-6 py-4">-</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </fieldset>
  );
}
