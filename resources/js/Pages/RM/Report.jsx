import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

export default function Report({ auth, InputData, materials = [] }) {
    const [activeTab, setActiveTab] = useState('tab1');
    const [data, setData] = useState({
      status: ''
    });
    const status = [{
      code: 'IMP',
      label: 'Inprocess'
    }, {
      code: 'CP',
      label: 'Complete'
    }, {
      code: 'ETS',
      label: 'Export to SAP'
    }];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Material Template</h2>}
        >
            <Head title="Material Template" />

            <div className="container mx-auto p-6">
                {/* Tabs Header */}
                <div className="flex border-b border-gray-200">
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'tab1' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => setActiveTab('tab1')}
                    >
                        Table 1
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'tab2' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => setActiveTab('tab2')}
                    >
                        Table 2
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'tab3' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => setActiveTab('tab3')}
                    >
                        Table 3
                    </button>
                </div>

                {/* Tabs Content */}
                <div className="mt-4">
                    {activeTab === 'tab1' && (
                      <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-3">
                            <div>
                                <InputLabel htmlFor="status" value="Status" />
                                <select
                                    id="status"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('status', e.target.value)}
                                    defaultValue={data?.status}
                                >
                                    <option value="">---- Select Status ----</option>
                                    {status?.map(o => (
                                        <option key={`status-code-${o.code}`} value={o.code}>{o.label}</option>
                                    ))}
                                </select>
                            </div>
                          </div>
                          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <table className="w-full text-sm text-left rtl:text-right text-gray-800 dark:text-gray-600">
                              <thead className="text-xs bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
                                  <tr>
                                      <th scope="col" className="px-6 py-3">
                                          #
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Material ID
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Material Desc
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Brand
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Mat Type
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Last User Update
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Last Update
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Action
                                      </th>
                                  </tr>
                              </thead>
                              <tbody>
                                {materials.map((o, index) => (
                                  <tr key={`report-gtin-${o.material_id}`}>
                                      <th scope="row" className="px-6 py-4">
                                          {index + 1}
                                      </th>
                                      <th scope="row" className="px-6 py-4">
                                          {o.material_id}
                                      </th>
                                      <td className="px-6 py-4">
                                          {o.material_desc}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.brand}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.mattype}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.last_user}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.last_update}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.status?.toLowerCase() === 'reserve' && o.last_user === auth.user.user_login ? (
                                            <a href="#" className="font-medium text-blue-600 dark:text-blue-500 hover:underline">{o.status}</a>
                                          ) : (
                                            o.status
                                          )}
                                      </td>
                                  </tr>
                                ))}
                              </tbody>
                            </table>
                          </div>
                          <div className="flex items-center justify-center mt-3">
                              <PrimaryButton>Save</PrimaryButton>
                          </div>
                      </div>
                    )}
                    {activeTab === 'tab2' && (
                      <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-3">
                            <div>
                                <InputLabel htmlFor="status" value="Status" />
                                <select
                                    id="status"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('status', e.target.value)}
                                    defaultValue={data?.status}
                                >
                                    <option value="">---- Select Status ----</option>
                                    {status?.map(o => (
                                        <option key={`status-code-${o.code}`} value={o.code}>{o.label}</option>
                                    ))}
                                </select>
                            </div>
                          </div>
                          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <table className="w-full text-sm text-left rtl:text-right text-gray-800 dark:text-gray-600">
                              <thead className="text-xs bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
                                  <tr>
                                      <th scope="col" className="px-6 py-3">
                                          #
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Material ID
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Material Desc
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Brand
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Mat Type
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Last User Update
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Last Update
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Action
                                      </th>
                                  </tr>
                              </thead>
                              <tbody>
                                {materials.map((o, index) => (
                                  <tr key={`report-gtin-${o.material_id}`}>
                                      <th scope="row" className="px-6 py-4">
                                          {index + 1}
                                      </th>
                                      <th scope="row" className="px-6 py-4">
                                          {o.material_id}
                                      </th>
                                      <td className="px-6 py-4">
                                          {o.material_desc}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.brand}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.mattype}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.last_user}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.last_update}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.status?.toLowerCase() === 'reserve' && o.last_user === auth.user.user_login ? (
                                            <a href="#" className="font-medium text-blue-600 dark:text-blue-500 hover:underline">{o.status}</a>
                                          ) : (
                                            o.status
                                          )}
                                      </td>
                                  </tr>
                                ))}
                              </tbody>
                            </table>
                          </div>
                          <div className="flex items-center justify-center mt-3">
                              <PrimaryButton>Save</PrimaryButton>
                          </div>
                      </div>
                    )}
                    {activeTab === 'tab3' && (
                      <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-3">
                            <div>
                                <InputLabel htmlFor="status" value="Status" />
                                <select
                                    id="status"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('status', e.target.value)}
                                    defaultValue={data?.status}
                                >
                                    <option value="">---- Select Status ----</option>
                                    {status?.map(o => (
                                        <option key={`status-code-${o.code}`} value={o.code}>{o.label}</option>
                                    ))}
                                </select>
                            </div>
                          </div>
                          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <table className="w-full text-sm text-left rtl:text-right text-gray-800 dark:text-gray-600">
                              <thead className="text-xs bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
                                  <tr>
                                      <th scope="col" className="px-6 py-3">
                                          #
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Material ID
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Material Desc
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Brand
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Mat Type
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Last User Update
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Last Update
                                      </th>
                                      <th scope="col" className="px-6 py-3">
                                          Action
                                      </th>
                                  </tr>
                              </thead>
                              <tbody>
                                {materials.map((o, index) => (
                                  <tr key={`report-gtin-${o.material_id}`}>
                                      <th scope="row" className="px-6 py-4">
                                          {index + 1}
                                      </th>
                                      <th scope="row" className="px-6 py-4">
                                          {o.material_id}
                                      </th>
                                      <td className="px-6 py-4">
                                          {o.material_desc}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.brand}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.mattype}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.last_user}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.last_update}
                                      </td>
                                      <td className="px-6 py-4">
                                          {o.status?.toLowerCase() === 'reserve' && o.last_user === auth.user.user_login ? (
                                            <a href="#" className="font-medium text-blue-600 dark:text-blue-500 hover:underline">{o.status}</a>
                                          ) : (
                                            o.status
                                          )}
                                      </td>
                                  </tr>
                                ))}
                              </tbody>
                            </table>
                          </div>
                          <div className="flex items-center justify-center mt-3">
                              <PrimaryButton>Save</PrimaryButton>
                          </div>
                      </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
