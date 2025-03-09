import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Report({ auth, activeTab, columns = [], datas = [], labels = [] }) {
  const [confirmingActive, setConfirmingActive] = useState(false);
  const [confirmingDelete, setConfirmingDelete] = useState(false);
  const [filter, setFilter] = useState({
    status: ''
  });
  const [dataLabels, setDataLabels] = useState({});
  const [showGoToBottom, setShowGoToBottom] = useState(true);
  const [showBackToTop, setShowBackToTop] = useState(false);

  const { data, setData, processing, errors, patch, delete: actionDelete } = useForm({});

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

  const disabledColumn = ['status_row', 'user_create'];

  const toggleActiveTab = tabInput => {
    router.visit(`/rm/report/${tabInput}`, {
        method: "get",
        preserveState: true, // เก็บ state เดิม
        preserveScroll: true, // เก็บตำแหน่ง scroll
    });
  };

  const toggleModal = dataSet => {
    const newData = { ...data };
    Object.keys(data).forEach(o => {
      newData[o] = dataSet[o] || '';
    });
    setData(newData);
    setConfirmingActive(true);
  };

  const closeModal = () => {
      setConfirmingActive(false);
  };

  const toggleDelete = dataSet => {
    const newData = { ...data };
    Object.keys(data).forEach(o => {
      newData[o] = dataSet[o] || '';
    });
    setData(newData);
    setConfirmingDelete(true);
  };

  const closeModalDelete = () => {
    setConfirmingDelete(false);
  };

  const setActive = (e) => {
      e.preventDefault();

      patch(route('rm.confirm', { tab: activeTab }), {
          onSuccess: () => closeModal()
      });
  };

  const setDelete = (e) => {
      e.preventDefault();

      actionDelete(route('rm.delete', { tab: activeTab }), {
          onSuccess: () => closeModalDelete()
      });
  };

  // ฟังก์ชันเลื่อนขึ้นบนสุด
  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // ฟังก์ชันเลื่อนลงล่างสุด
  const scrollToBottom = () => {
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
  };

  useEffect(() => {
    const handleScroll = () => {
      const scrollY = window.scrollY;
      const scrollHeight = document.documentElement.scrollHeight;
      const windowHeight = window.innerHeight;

      // แสดงปุ่ม "Go to Bottom" ถ้าอยู่ด้านบนสุด
      setShowBackToTop(scrollY > 300);

      // แสดงปุ่ม "Back to Top" ถ้าอยู่ด้านล่างสุด
      setShowGoToBottom(scrollY + windowHeight < scrollHeight - 10);
    };

    window.addEventListener('scroll', handleScroll);
    return () => {
      window.removeEventListener('scroll', handleScroll);
    };
  }, []);

  useEffect(() => {
    const newData = {};
    columns.forEach(column => {
      newData[column.name] = '';
    });
    setData(newData);
  }, [columns]);

  useEffect(() => {
      const newLabels = {};
      labels?.forEach(label => {
          newLabels[label.label_col_name] = label.label_value;
      });
      setDataLabels(newLabels);
  }, [labels]);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Material Template</h2>}
        >
            <Head title="Material Template" />

            <div className="container mx-auto py-6">
                {/* Tabs Header */}
                <div className="flex items-center justify-end gap-4 mb-2">
                    <PrimaryButton onClick={() => window.open(route('rm.export'))}>Download</PrimaryButton>
                </div>
                <div className="flex flex-wrap border-b border-gray-200">
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'AVAILABILITY' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('AVAILABILITY')}
                    >
                        AVAILABILITY
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'CUST_PART_NUM' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('CUST_PART_NUM')}
                    >
                        CUST_PART_NUM
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'FINANCIAL' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('FINANCIAL')}
                    >
                        FINANCIAL
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'GENERAL' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('GENERAL')}
                    >
                        GENERAL
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'GTINS' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('GTINS')}
                    >
                        GTINS
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'LOGISTICS' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('LOGISTICS')}
                    >
                        LOGISTICS
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'PLANNING' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('PLANNING')}
                    >
                        PLANNING
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'QTY_CONVERS' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('QTY_CONVERS')}
                    >
                        QTY_CONVERS
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'SALES_DATA' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('SALES_DATA')}
                    >
                        SALES_DATA
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'SUPP_PART_NUM' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('SUPP_PART_NUM')}
                    >
                        SUPP_PART_NUM
                    </button>
                    <button
                        className={`px-4 py-2 -mb-px border-b-2 transition-colors duration-300 ${
                            activeTab === 'UOM_CHAR' ? 'border-blue-500 text-blue-500' : 'border-transparent text-gray-500 hover:text-blue-500'
                        }`}
                        onClick={() => toggleActiveTab('UOM_CHAR')}
                    >
                        UOM_CHAR
                    </button>
                </div>

                {/* Tabs Content */}
                <div className="mt-4">
                    <div className="w-full mx-auto">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-3 hidden">
                          <div>
                              <InputLabel htmlFor="status" value="Status" />
                              <select
                                  id="status"
                                  className="mt-1 block w-full"
                                  onChange={(e) => setFilter({ ...filter, status: e.target.value })}
                                  defaultValue={filter?.status}
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
                                    {columns.filter(column => !column?.hidden).map(column => (
                                      <th scope="col" className="px-6 py-3" key={`${activeTab}-column-${column.name}`}>
                                          {dataLabels[column.label] || column.label}
                                      </th>
                                    ))}
                                    <th className="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                              {datas.map((o, index) => (
                                <tr key={`${activeTab}-tr-${index}`}>
                                    <td scope="row" className="px-6 py-4">
                                        {index + 1}
                                    </td>
                                    {columns.filter(column => !column?.hidden).map(column => (
                                      <td scope="col" className="px-6 py-3" key={`${activeTab}-data-${column.name}`}>
                                          {o[column.name]}
                                      </td>
                                    ))}
                                    <td className="text-center px-6 py-3">
                                      <div className="flex gap-1 items-center justify-center">
                                        <SecondaryButton disabled={o?.status_row === 'ETS'} onClick={() => toggleModal(o)}>Edit</SecondaryButton>
                                        <DangerButton disabled={o?.status_row === 'ETS'} onClick={() => toggleDelete(o)}>Delete</DangerButton>
                                      </div>
                                    </td>
                                </tr>
                              ))}
                            </tbody>
                          </table>
                        </div>
                    </div>
                </div>
            </div>
            <Modal show={confirmingActive} onClose={closeModal}>
                <form onSubmit={setActive} className="p-6 max-h-[600px] overflow-x-auto">
                    <h2 className="text-lg font-medium text-gray-900">
                        {`Update data table ${activeTab}`}
                    </h2>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                      {columns.map(column => (
                        <div key={`form-input-${column.name}`} className={column?.hidden && 'hidden'}>
                          <InputLabel htmlFor={column.name} value={dataLabels[column.label] || column.label} />

                          <TextInput
                              id={column.name}
                              className={`mt-1 block w-full${disabledColumn.includes(column.name) ? ' opacity-25' : ''}`}
                              value={data[column.name]}
                              type={column?.hidden ? 'number' : 'text'}
                              maxLength="100"
                              onChange={(e) => setData(column.name, e.target.value)}
                              disabled={disabledColumn.includes(column.name)}
                          />

                          <InputError className="mt-2" message={errors[column.name]} />
                        </div>
                      ))}
                    </div>
                    <div className="flex items-center justify-center gap-4 mt-5">
                      <SecondaryButton onClick={closeModal}>Cancel</SecondaryButton>
                      <PrimaryButton disabled={processing}>Confirm</PrimaryButton>
                    </div>
                </form>
            </Modal>
            <Modal show={confirmingDelete} onClose={closeModalDelete}>
                <form onSubmit={setDelete} className="p-6 max-h-[600px] overflow-x-auto">
                    <h2 className="text-lg font-medium text-gray-900">
                        {`Delete data from table ${activeTab}`}
                    </h2>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                      {columns.map(column => (
                        <div key={`form-input-${column.name}`} className={column?.hidden && 'hidden'}>
                          <InputLabel htmlFor={column.name} value={dataLabels[column.label] || column.label} />

                          <TextInput
                              id={column.name}
                              className={`mt-1 block w-full opacity-25`}
                              value={data[column.name]}
                              type={column?.hidden ? 'number' : 'text'}
                              maxLength="100"
                              onChange={(e) => setData(column.name, e.target.value)}
                              disabled={true}
                          />

                          <InputError className="mt-2" message={errors[column.name]} />
                        </div>
                      ))}
                    </div>
                    <div className="flex items-center justify-center gap-4 mt-5">
                      <SecondaryButton onClick={closeModalDelete}>Cancel</SecondaryButton>
                      <DangerButton disabled={processing}>Delete</DangerButton>
                    </div>
                </form>
            </Modal>
            {/* ปุ่ม Go to Bottom (แสดงเฉพาะเมื่ออยู่ด้านบน) */}
            {showGoToBottom && (
              <div className="fixed top-10 right-5">
                <button
                  onClick={scrollToBottom}
                  className="bg-gray-800 text-white px-4 py-2 rounded shadow-md hover:bg-gray-700 transition opacity-75"
                >
                  ⬇ Go to Bottom
                </button>
              </div>
            )}

            {/* ปุ่ม Back to Top (แสดงเฉพาะเมื่ออยู่ด้านล่าง) */}
            {showBackToTop && (
              <div className="fixed bottom-10 right-5">
                <button
                  onClick={scrollToTop}
                  className="bg-gray-800 text-white px-4 py-2 rounded shadow-md hover:bg-gray-700 transition opacity-75"
                >
                  ⬆ Back to Top
                </button>
              </div>
            )}
        </AuthenticatedLayout>
    );
}
