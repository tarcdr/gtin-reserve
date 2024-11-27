import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';

export default function Report({ auth, activeTab, columns = [], datas = [] }) {
    const { data, setData, processing } = useForm({
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

    const toggleActiveTab = tabInput => {
      router.visit(`/rm/report/${tabInput}`, {
          method: "get",
          preserveState: true, // เก็บ state เดิม
          preserveScroll: true, // เก็บตำแหน่ง scroll
      });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Material Template</h2>}
        >
            <Head title="Material Template" />

            <div className="container mx-auto p-6">
                {/* Tabs Header */}
                <div className="flex flex-wrap border-b border-gray-200">
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'tab1' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('tab1')}
                    >
                        AVAILABILITY
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'tab2' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('tab2')}
                    >
                        CUST_PART_NUM
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'tab3' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('tab3')}
                    >
                        FINANCIAL
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'tab4' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('tab4')}
                    >
                        GENERAL
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'tab5' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('tab5')}
                    >
                        GTINS
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'tab6' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('tab6')}
                    >
                        LOGISTICS
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'tab7' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('tab7')}
                    >
                        PLANNING
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'tab8' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('tab8')}
                    >
                        QTY_CONVERS
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'tab9' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('tab9')}
                    >
                        SALES_DATA
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'tab10' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('tab10')}
                    >
                        SUPP_PART_NUM
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'tab11' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('tab11')}
                    >
                        UOM_CHAR
                    </button>
                </div>

                {/* Tabs Content */}
                <div className="mt-4">
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
                        <div className="bg-white overflow-x-auto shadow-sm sm:rounded-lg">
                          <table className="w-full text-sm text-left rtl:text-right text-gray-800 dark:text-gray-600">
                            <thead className="text-xs bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
                                <tr>
                                    <th scope="col" className="px-6 py-3">
                                        #
                                    </th>
                                    {columns.map(column => (
                                      <th scope="col" className="px-6 py-3" key={`${activeTab}-column-${column.name}`}>
                                          {column.label}
                                      </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                              {datas.map((o, index) => (
                                <tr key={`${activeTab}-tr-${index}`}>
                                    <td scope="row" className="px-6 py-4">
                                        {index + 1}
                                    </td>
                                    {columns.map(column => (
                                      <td scope="col" className="px-6 py-3" key={`${activeTab}-data-${column.name}`}>
                                          {o[column.name]}
                                      </td>
                                    ))}
                                </tr>
                              ))}
                            </tbody>
                          </table>
                        </div>
                        <div className="flex items-center justify-center mt-3">
                            <PrimaryButton>Save</PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
