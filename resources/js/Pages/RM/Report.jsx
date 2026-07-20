import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableCreatableInput from '@/Components/SearchableCreatableInput';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';

export default function Report({ auth, activeTab, columns = [], datas = [], labels = [], fieldOptions = {} }) {
  const [confirmingActive, setConfirmingActive] = useState(false);
  const [confirmingDelete, setConfirmingDelete] = useState(false);
  const [dataLabels, setDataLabels] = useState({});
  const [formErrors, setFormErrors] = useState({});
  const [confirmingExportSap, setConfirmingExportSap] = useState(false);
  const [exportSapConfirm, setExportSapConfirm] = useState('');
  const [exportSapError, setExportSapError] = useState('');
  const [isExportingSap, setIsExportingSap] = useState(false);
  const [showGoToBottom, setShowGoToBottom] = useState(true);
  const [showBackToTop, setShowBackToTop] = useState(false);

  // เพิ่ม useState สำหรับ sidebar toggle
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);

  // ฟังก์ชัน toggle sidebar
  const toggleSidebar = () => setIsSidebarOpen(!isSidebarOpen);
  const isAdmin = auth?.user?.role === 'admin';

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

  const getOptionsFromData = (columnName) => (
    [...new Set(datas.map((item) => item?.[columnName]).filter(Boolean))].map((value) => ({
      value,
      label: value,
      code: value,
    }))
  );

  const getFieldOptions = (columnName) => {
    const masterOptions = fieldOptions?.[activeTab]?.[columnName];

    if (Array.isArray(masterOptions) && masterOptions.length > 0) {
      return masterOptions;
    }

    return getOptionsFromData(columnName);
  };

  const tabFieldConfigs = {
    AVAILABILITY: {
      material_id: { type: 'display', required: true },
      planning_area_id: { type: 'list', required: true, options: getFieldOptions('planning_area_id') },
      status: { type: 'list', required: true, options: getFieldOptions('status') },
      availability_check_scope: { type: 'text', required: false },
      status_row: { type: 'display', required: false },
      user_create: { type: 'display', required: false },
    },
    BOM_GENERAL: {
      bom_id: { type: 'display', required: true },
      variant_id: { type: 'display', required: true },
      language: { type: 'display', required: true },
      variant_desc: { type: 'text', required: true, maxLength: 255 },
      long_text: { type: 'text', required: true },
      status_row: { type: 'display', required: false },
      user_create: { type: 'display', required: false },
      user_role: { type: 'display', required: false, hidden: true },
      create_date: { type: 'display', required: false, hidden: true },
      user_update: { type: 'display', required: false, hidden: true },
      update_date: { type: 'display', required: false, hidden: true },
    },
    CUST_PART_NUM: {
      material_id: { type: 'display', required: true },
      customer_id: { type: 'combo', required: true, options: getFieldOptions('customer_id') },
      customer_part_number: { type: 'combo', required: true, options: getFieldOptions('customer_part_number') },
      status_row: { type: 'display', required: false },
      user_create: { type: 'display', required: false },
    },
    FINANCIAL: {
      material_id: { type: 'display', required: true },
      company_id: { type: 'combo', required: true, options: getFieldOptions('company_id') },
      business_residence_id: { type: 'combo', required: true, options: getFieldOptions('business_residence_id') },
      status_row: { type: 'display', required: false },
      user_create: { type: 'display', required: false },
    },
    GENERAL: {
      material_id: { type: 'display', required: true },
      material_desc: { type: 'display', required: true, maxLength: 40 },
      full_material_desc: { type: 'display', required: true },
      meterial_desc_th: { type: 'display', required: true },
      product_category_id: { type: 'list', required: true, options: getFieldOptions('product_category_id') },
      mat_type: { type: 'display', required: true },
      sub_type: { type: 'display', required: true, maxLength: 30 },
      brand: { type: 'display', required: true },
      base_uom: { type: 'display', required: true },
      inv_valuation_uom: { type: 'text', required: false },
      pillar: { type: 'list', required: true, options: getFieldOptions('pillar') },
      division: { type: 'combo', required: true, options: getFieldOptions('division') },
      department: { type: 'list', required: true, options: getFieldOptions('department') },
      sub_department: { type: 'list', required: false, options: getFieldOptions('sub_department') },
      class: { type: 'combo', required: true, maxLength: 30, options: getFieldOptions('class') },
      sub_class: { type: 'combo', required: true, maxLength: 30, options: getFieldOptions('sub_class') },
      section: { type: 'combo', required: true, maxLength: 30, options: getFieldOptions('section') },
      series: { type: 'combo', required: true, maxLength: 30, options: getFieldOptions('series') },
      attribute_1: { type: 'list', required: true, options: getFieldOptions('attribute_1') },
      register_off: { type: 'text', required: false, maxLength: 30 },
      shelf_life: { type: 'text', required: true },
      hs_code: { type: 'combo', required: true, options: getFieldOptions('hs_code') },
      country: { type: 'combo', required: true, maxLength: 30, options: getFieldOptions('country') },
      old_product_id: { type: 'text', required: false },
      identified_stock_type: { type: 'text', required: false },
      serial_number_profile: { type: 'text', required: false },
      retail_sales_price: { type: 'text', required: false },
      product_core: { type: 'text', required: false },
      attribute_2: { type: 'combo', required: true, options: getFieldOptions('attribute_2') },
      detail_name: { type: 'display', required: false },
      status_row: { type: 'display', required: false },
      user_create: { type: 'display', required: false },
    },
    GTINS: {
      material_id: { type: 'display', required: true },
      trading_unit: { type: 'list', required: true, options: getFieldOptions('trading_unit') },
      gtin_number: { type: 'display', required: true },
      status_row: { type: 'display', required: false },
      user_create: { type: 'display', required: false },
    },
    INPUT_PRODUCTS: {
      bom_id: { type: 'display', required: true },
      variant_id: { type: 'text', required: true, numericOnly: true },
      line_item_grp_id: { type: 'text', required: true, numericOnly: true },
      line_item_bom: { type: 'text', required: true, numericOnly: true },
      input_prod_id: { type: 'display', required: true },
      quantity: { type: 'text', required: true, numericOnly: true },
      quantity_uom: { type: 'list', required: true, options: getFieldOptions('quantity_uom') },
      engr_chg_order_id: { type: 'text', required: true },
      fixed_qty_indi: { type: 'text', required: true },
      deleted: { type: 'text', required: true },
      status_row: { type: 'display', required: false },
      user_create: { type: 'display', required: false },
      user_role: { type: 'display', required: false, hidden: true },
      create_date: { type: 'display', required: false, hidden: true },
      user_update: { type: 'display', required: false, hidden: true },
      update_date: { type: 'display', required: false, hidden: true },
    },
    LOGISTICS: {
      material_id: { type: 'display', required: true },
      site_id: { type: 'display', required: true },
      status: { type: 'list', required: true, options: getFieldOptions('status') },
      storage_group_id: { type: 'combo', required: true, options: getFieldOptions('storage_group_id') },
      status_row: { type: 'display', required: false },
      user_create: { type: 'display', required: false },
    },
    PLANNING: {
      material_id: { type: 'display', required: true },
      planning_area_id: { type: 'combo', required: true, options: getFieldOptions('planning_area_id') },
      status: { type: 'list', required: true, options: getFieldOptions('status') },
      planning_uom: { type: 'list', required: true, options: getFieldOptions('planning_uom') },
      demand_manage_procedure: { type: 'combo', required: true, options: getFieldOptions('demand_manage_procedure') },
      procurement_type: { type: 'combo', required: true, options: getFieldOptions('procurement_type') },
      planning_procedure: { type: 'combo', required: true, options: getFieldOptions('planning_procedure') },
      lot_sizing_method: { type: 'combo', required: true, options: getFieldOptions('lot_sizing_method') },
      status_row: { type: 'display', required: false },
      user_create: { type: 'display', required: false },
    },
    QTY_CONVERS: {
      material_id: { type: 'display', required: true },
      quantity: { type: 'text', required: true },
      quantity_uom: { type: 'list', required: true, options: getFieldOptions('quantity_uom') },
      corres_qty: { type: 'text', required: true },
      corres_qty_uom: { type: 'list', required: true, options: getFieldOptions('corres_qty_uom') },
      status_row: { type: 'display', required: false },
      user_create: { type: 'display', required: false },
    },
    SALES_DATA: {
      material_id: { type: 'display', required: true },
      sales_org_id: { type: 'list', required: true, options: getFieldOptions('sales_org_id') },
      distribution_channel: { type: 'combo', required: true, options: getFieldOptions('distribution_channel') },
      status: { type: 'list', required: true, options: getFieldOptions('status') },
      sales_uom: { type: 'list', required: true, options: getFieldOptions('sales_uom') },
      item_group: { type: 'combo', required: true, options: getFieldOptions('item_group') },
      status_row: { type: 'display', required: false },
      user_create: { type: 'display', required: false },
    },
    SUPP_PART_NUM: {
      material_id: { type: 'display', required: true },
      supplier_id: { type: 'combo', required: true, options: getFieldOptions('supplier_id') },
      supplier_part_number: { type: 'combo', required: true, options: getFieldOptions('supplier_part_number') },
      supplier_lead_time: { type: 'text', required: false },
      status_row: { type: 'display', required: false },
      user_create: { type: 'display', required: false },
    },
    UOM_CHAR: {
      material_id: { type: 'display', required: true },
      unit_of_measure: { type: 'display', required: true },
      net_weight: { type: 'text', required: false },
      uom_net_weight: { type: 'text', required: false },
      gross_weight: { type: 'text', required: false },
      uom_gross_weight: { type: 'text', required: false },
      net_volume: { type: 'text', required: false },
      uom_net_volume: { type: 'text', required: false },
      gross_volume: { type: 'text', required: false },
      uom_gross_volume: { type: 'text', required: false },
      lengths: { type: 'text', required: false },
      uom_length: { type: 'text', required: false },
      width: { type: 'text', required: false },
      uom_width: { type: 'text', required: false },
      height: { type: 'text', required: false },
      uom_height: { type: 'text', required: false },
      quantity: { type: 'text', required: false },
      quantity_uom: { type: 'list', required: false, options: getFieldOptions('quantity_uom') },
      quantity_type_char: { type: 'text', required: false },
      status_row: { type: 'display', required: false },
      user_create: { type: 'display', required: false },
    },
  };
  const getFieldConfig = (columnName) => {
    return tabFieldConfigs[activeTab]?.[columnName] || { type: 'text', required: false };
  };
  const renderEditField = (column) => {
    const fieldConfig = getFieldConfig(column.name);
    const handleChange = (nextValue) => {
      const value = fieldConfig.numericOnly ? String(nextValue ?? '').replace(/\D+/g, '') : nextValue;
      setData(column.name, value);
    };

    if (fieldConfig.type === 'list') {
      return (
        <select
          id={column.name}
          className="mt-1 block w-full border-gray-300 rounded-md"
          value={data[column.name] ?? ''}
          required={fieldConfig.required}
          maxLength={fieldConfig.maxLength}
          onChange={(e) => setData(column.name, e.target.value)}
        >
          <option value="">---- Select ----</option>
          {fieldConfig.options?.map((option) => (
            <option key={`${column.name}-${option.value}`} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      );
    }

    if (fieldConfig.type === 'combo') {
      return (
        <SearchableCreatableInput
          id={column.name}
          className="mt-1 block w-full"
          value={data[column.name] ?? ''}
          options={fieldConfig.options || []}
          required={fieldConfig.required}
          maxLength={fieldConfig.maxLength}
          onChange={handleChange}
        />
      );
    }

    return (
      <TextInput
        id={column.name}
        className={`mt-1 block w-full${fieldConfig.type === 'display' ? ' opacity-25' : ''}`}
        value={data[column.name] ?? ''}
        type={column?.hidden ? 'number' : 'text'}
        maxLength={fieldConfig.maxLength || 100}
        inputMode={fieldConfig.numericOnly ? 'numeric' : undefined}
        pattern={fieldConfig.numericOnly ? '[0-9]*' : undefined}
        required={fieldConfig.required}
        onChange={(e) => handleChange(e.target.value)}
        disabled={fieldConfig.type === 'display'}
      />
    );
  };

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
      newData[o] = dataSet[o] ?? '';
    });
    setData(newData);
    setConfirmingActive(true);
  };

  const closeModal = () => {
      setFormErrors({});
      setConfirmingActive(false);
  };

  const toggleDelete = dataSet => {
    const newData = { ...data };
    Object.keys(data).forEach(o => {
      newData[o] = dataSet[o] ?? '';
    });
    setData(newData);
    setConfirmingDelete(true);
  };

  const closeModalDelete = () => {
    setConfirmingDelete(false);
  };

  const setActive = (e) => {
      e.preventDefault();

      const nextErrors = {};
      columns
        .filter(column => !column?.hidden)
        .forEach((column) => {
          const fieldConfig = getFieldConfig(column.name);
          const value = data[column.name];

          if (fieldConfig.required && !String(value ?? '').trim()) {
            nextErrors[column.name] = `${dataLabels[column.label] || column.label} is required.`;
            return;
          }

          if (fieldConfig.maxLength && String(value ?? '').length > fieldConfig.maxLength) {
            nextErrors[column.name] = `${dataLabels[column.label] || column.label} must be at most ${fieldConfig.maxLength} characters.`;
          }
        });

      setFormErrors(nextErrors);

      if (Object.keys(nextErrors).length > 0) {
        return;
      }

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

  const openExportSapModal = () => {
    setExportSapConfirm('');
    setExportSapError('');
    setConfirmingExportSap(true);
  };

  const closeExportSapModal = () => {
    if (isExportingSap) {
      return;
    }

    setConfirmingExportSap(false);
  };

  const getExportSapFilename = (contentDisposition) => {
    const fallback = `export_excel_${new Date().toISOString().replace(/[-:T]/g, '').slice(0, 14)}.xml`;

    if (!contentDisposition) {
      return fallback;
    }

    const encodedMatch = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (encodedMatch?.[1]) {
      return decodeURIComponent(encodedMatch[1].replace(/"/g, ''));
    }

    const match = contentDisposition.match(/filename="?([^"]+)"?/i);
    return match?.[1] || fallback;
  };

  const getExportSapErrorMessage = async (error) => {
    const fallback = 'Unable to export to SAP.';
    const payload = error?.response?.data;

    if (payload instanceof Blob) {
      try {
        const text = await payload.text();
        const json = JSON.parse(text);
        const errorsPayload = json?.errors;

        if (errorsPayload && typeof errorsPayload === 'object') {
          const firstError = Object.values(errorsPayload).flat().find(Boolean);
          if (firstError) {
            return firstError;
          }
        }

        return json?.message || json?.error || fallback;
      } catch {
        return fallback;
      }
    }

    return error?.response?.data?.message || error?.response?.data?.error || fallback;
  };

  const submitExportSap = async (e) => {
    e.preventDefault();
    setExportSapError('');
    setIsExportingSap(true);

    try {
      const response = await axios.post(route('rm.export-sap'), {
        p_confirm: exportSapConfirm,
      }, {
        responseType: 'blob',
        headers: { Accept: 'application/json, application/xml, application/octet-stream' },
      });
      const blobUrl = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');

      link.href = blobUrl;
      link.download = getExportSapFilename(response.headers?.['content-disposition']);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(blobUrl);

      setConfirmingExportSap(false);
      setExportSapConfirm('');

      router.visit(`/rm/report/${activeTab}`, {
        method: 'get',
        preserveState: false,
        preserveScroll: false,
        replace: true,
      });
    } catch (error) {
      setExportSapError(await getExportSapErrorMessage(error));
    } finally {
      setIsExportingSap(false);
    }
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
                    'SALES_DATA', 'SUPP_PART_NUM', 'UOM_CHAR', 'BOM_GENERAL', 'INPUT_PRODUCTS',
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
                <div className="flex justify-end gap-2 mb-4">
                  <PrimaryButton
                    onClick={openExportSapModal}
                    disabled={!isAdmin}
                    title={!isAdmin ? 'Admin only' : undefined}
                  >
                    Export to SAP
                  </PrimaryButton>
                  <PrimaryButton onClick={() => window.open(route('rm.export'))}>Download</PrimaryButton>
                </div>
                {/* Scrollable Table */}
                <div className="bg-white shadow-sm sm:rounded-lg w-full">
                  <div className="max-h-[calc(100vh-240px)] overflow-auto">
                    <table className="min-w-[640px] w-full text-sm text-left rtl:text-right text-gray-800 dark:text-gray-600">
                      <thead className="text-xs bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
                        <tr>
                          <th scope="col" className="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3">#</th>
                          {columns.filter(column => !column?.hidden).map(column => (
                            <th
                              scope="col"
                              className="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3"
                              key={`${activeTab}-column-${column.name}`}
                            >
                              {dataLabels[column.label] || column.label}
                            </th>
                          ))}
                          <th className="sticky top-0 z-10 bg-gray-50 dark:bg-gray-700 text-center px-6 py-3 w-[160px]">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        {datas.map((o, index) => (
                          <tr key={`${activeTab}-tr-${index}`} className={`border-b ${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}`}>
                            <td scope="row" className="px-6 py-4">{index + 1}</td>
                            {columns.filter(column => !column?.hidden).map(column => (
                              <td scope="col" className="px-6 py-3" key={`${activeTab}-data-${column.name}`}>
                                {o[column.name]}
                              </td>
                            ))}
                            <td className="text-center px-6 py-3 w-[160px]">
                              <div className="flex flex-nowrap gap-1 items-center justify-center">
                                <SecondaryButton disabled={o?.status_row === 'ETS'} onClick={() => toggleModal(o)}>Edit</SecondaryButton>
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

                          {renderEditField(column)}

                          <InputError className="mt-2" message={formErrors[column.name] || errors[column.name]} />
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
            <Modal show={confirmingExportSap} onClose={closeExportSapModal}>
              <div className="bg-white rounded-lg shadow-lg overflow-hidden max-w-2xl mx-auto">
                <div className="bg-blue-100 border-b border-blue-300 px-6 py-4">
                  <h2 className="text-xl font-semibold text-blue-800">
                    Export to SAP
                  </h2>
                </div>

                <form onSubmit={submitExportSap} className="p-6">
                  <div>
                    <InputLabel htmlFor="export_sap_confirm" value="Confirm" />
                    <textarea
                      id="export_sap_confirm"
                      className="mt-1 block w-full min-h-[160px] rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      value={exportSapConfirm}
                      maxLength={4000}
                      required
                      disabled={isExportingSap}
                      onChange={(e) => setExportSapConfirm(e.target.value)}
                    />
                    <InputError className="mt-2" message={exportSapError} />
                  </div>

                  <div className="flex items-center justify-center gap-4 mt-6 border-t pt-4">
                    <SecondaryButton type="button" onClick={closeExportSapModal} disabled={isExportingSap}>Cancel</SecondaryButton>
                    <PrimaryButton disabled={isExportingSap}>
                      {isExportingSap ? 'Processing...' : 'Confirm'}
                    </PrimaryButton>
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
                  ▼ Go to Bottom
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
                  ▲ Back to Top
                </button>
              </div>
            )}
        </AuthenticatedLayout>
    );
}
