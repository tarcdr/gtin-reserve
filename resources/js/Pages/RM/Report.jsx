import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import QRCode from 'qrcode';
import JsBarcode from 'jsbarcode';

export default function Report({ auth, activeTab, columns = [], datas = [], labels = [] }) {
  const [confirmingActive, setConfirmingActive] = useState(false);
  const [confirmingDelete, setConfirmingDelete] = useState(false);
  const [dataLabels, setDataLabels] = useState({});
  const [showGoToBottom, setShowGoToBottom] = useState(true);
  const [showBackToTop, setShowBackToTop] = useState(false);
  const [showGtinModal, setShowGtinModal] = useState(false);
  const [activeGtin, setActiveGtin] = useState('');
  const qrCanvasRef = useRef(null);
  const barcodeCanvasRef = useRef(null);

  // เพิ่ม useState สำหรับ sidebar toggle
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);

  // ฟังก์ชัน toggle sidebar
  const toggleSidebar = () => setIsSidebarOpen(!isSidebarOpen);

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
  const gtinColumnNames = new Set(['gtin_number', 'gtin_number_desc']);
  const gtinColumnLabels = new Set(['GTIN_NUMBER_DESC']);

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

  const openGtinModal = (value) => {
    if (!value) return;
    setActiveGtin(String(value));
    setShowGtinModal(true);
  };

  const closeGtinModal = () => {
    setShowGtinModal(false);
    setActiveGtin('');
  };

  const downloadCanvas = (canvas, filename) => {
    if (!canvas) return;
    const link = document.createElement('a');
    link.href = canvas.toDataURL('image/png');
    link.download = filename;
    link.click();
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
    if (!showGtinModal || !activeGtin) return;

    let timeoutId;
    const renderCodes = () => {
      if (!qrCanvasRef.current || !barcodeCanvasRef.current) {
        timeoutId = setTimeout(renderCodes, 50);
        return;
      }

      QRCode.toCanvas(qrCanvasRef.current, activeGtin, {
        width: 220,
        margin: 1,
        errorCorrectionLevel: 'M',
      });

      JsBarcode(barcodeCanvasRef.current, activeGtin, {
        format: 'CODE128',
        width: 2,
        height: 80,
        margin: 0,
        displayValue: true,
        fontSize: 16,
      });
    };

    renderCodes();

    return () => {
      if (timeoutId) clearTimeout(timeoutId);
    };
  }, [showGtinModal, activeGtin]);

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

            <div className="flex flex-col md:flex-row min-h-screen">
              {/* Sidebar */}
              <div className={`bg-white border-r shadow-sm transition-all duration-300 ${isSidebarOpen ? 'w-full md:w-64' : 'w-full md:w-16'} shrink-0`}>
                <div className="flex justify-between items-center px-4 py-3 border-b">
                  <span className={`font-semibold text-gray-700 transition-opacity ${isSidebarOpen ? 'opacity-100 md:block' : 'opacity-0 hidden'}`}>Tabs</span>
                  <button
                    onClick={toggleSidebar}
                    className="text-gray-500 hover:text-gray-700 focus:outline-none ms-auto"
                  >
                    {isSidebarOpen ? '←' : '→'}
                  </button>
                </div>
                <nav className="flex flex-col text-sm">
                  {[
                    'AVAILABILITY', 'CUST_PART_NUM', 'FINANCIAL', 'GENERAL',
                    'GTINS', 'LOGISTICS', 'PLANNING', 'QTY_CONVERS',
                    'SALES_DATA', 'SUPP_PART_NUM', 'UOM_CHAR',
                  ].map((tab) => (
                    <button
                      key={`sidebar-tab-${tab}`}
                      onClick={() => toggleActiveTab(tab)}
                      className={`w-full ${isSidebarOpen ? 'text-left' : ''} px-4 py-3 border-b transition-colors duration-200 text-gray-600 hover:bg-gray-100 hover:text-blue-600 ${
                        activeTab === tab ? 'bg-blue-50 text-blue-700 font-semibold' : ''
                      }`}
                    >
                      {isSidebarOpen ? tab : tab.charAt(0)}
                    </button>
                  ))}
                </nav>
              </div>

              {/* Content Area */}
              <div className="flex flex-col flex-grow w-full px-4 py-6 overflow-hidden">
                {/* ปุ่ม Download */}
                <div className="flex justify-end mb-4">
                  <PrimaryButton onClick={() => window.open(route('rm.export'))}>Download</PrimaryButton>
                </div>

                {/* Scrollable Table */}
                <div className="bg-white shadow-sm sm:rounded-lg w-full overflow-x-auto">
                  <table className="min-w-[640px] w-full text-sm text-left rtl:text-right text-gray-800 dark:text-gray-600">
                    <thead className="text-xs bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
                      <tr>
                        <th scope="col" className="px-6 py-3">#</th>
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
                        <tr key={`${activeTab}-tr-${index}`} className="border-b">
                          <td scope="row" className="px-6 py-4">{index + 1}</td>
                          {columns.filter(column => !column?.hidden).map(column => (
                            <td scope="col" className="px-6 py-3" key={`${activeTab}-data-${column.name}`}>
                              {activeTab === 'GTINS' && (gtinColumnNames.has(column.name) || gtinColumnLabels.has(column.label) || gtinColumnLabels.has(dataLabels[column.label]))
                                ? (
                                  <button
                                    type="button"
                                    onClick={() => openGtinModal(o[column.name])}
                                    className="text-blue-600 hover:underline"
                                  >
                                    {o[column.name]}
                                  </button>
                                ) : (
                                  o[column.name]
                                )}
                            </td>
                          ))}
                          <td className="text-center px-6 py-3">
                            <div className="flex flex-wrap gap-1 items-center justify-center">
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

            <Modal show={confirmingActive} onClose={closeModal}>
              <div className="bg-white rounded-lg shadow-lg overflow-hidden max-w-3xl mx-auto">
                {/* Header */}
                <div className="bg-blue-100 border-b border-blue-300 px-6 py-4">
                    <h2 className="text-xl font-semibold text-blue-800">
                        {`Update data table ${activeTab}`}
                    </h2>
                </div>

                {/* Body */}
                <form onSubmit={setActive} className="p-6 max-h-[600px] overflow-x-auto">
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

                    {/* Footer */}
                    <div className="flex items-center justify-center gap-4 mt-6 border-t pt-4">
                      <SecondaryButton onClick={closeModal}>Cancel</SecondaryButton>
                      <PrimaryButton disabled={processing}>Confirm</PrimaryButton>
                    </div>
                </form>
              </div>
            </Modal>
            <Modal show={showGtinModal} onClose={closeGtinModal}>
              <div className="bg-white rounded-lg shadow-lg overflow-hidden max-w-md mx-auto">
                <div className="bg-slate-100 border-b border-slate-200 px-6 py-4">
                  <h2 className="text-lg font-semibold text-slate-800">
                    {activeGtin}
                  </h2>
                </div>
                <div className="p-6 space-y-6">
                  <div className="flex flex-col items-center gap-3">
                    <div className="text-sm text-slate-600">QR Code</div>
                    <canvas ref={qrCanvasRef} className="bg-white border border-slate-200 rounded p-2" />
                    <SecondaryButton onClick={() => downloadCanvas(qrCanvasRef.current, `gtin-${activeGtin}-qr.png`)}>
                      Download QR
                    </SecondaryButton>
                  </div>
                  <div className="flex flex-col items-center gap-3">
                    <div className="text-sm text-slate-600">Bar Code</div>
                    <canvas ref={barcodeCanvasRef} className="bg-white border border-slate-200 rounded p-2" />
                    <SecondaryButton onClick={() => downloadCanvas(barcodeCanvasRef.current, `gtin-${activeGtin}-barcode.png`)}>
                      Download Bar Code
                    </SecondaryButton>
                  </div>
                </div>
              </div>
            </Modal>
            <Modal show={confirmingDelete} onClose={closeModalDelete}>
              <div className="bg-white rounded-lg shadow-lg overflow-hidden max-w-3xl mx-auto">
                {/* Header */}
                <div className="bg-red-100 border-b border-red-300 px-6 py-4">
                  <h2 className="text-xl font-semibold text-red-800">
                      {`Delete data from table ${activeTab}`}
                  </h2>
                </div>

                {/* Body */}
                <form onSubmit={setDelete} className="p-6 max-h-[600px] overflow-x-auto">
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
                    <div className="flex items-center justify-center gap-4 mt-6 border-t pt-4">
                      <SecondaryButton onClick={closeModalDelete}>Cancel</SecondaryButton>
                      <DangerButton disabled={processing}>Delete</DangerButton>
                    </div>
                </form>
              </div>
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
