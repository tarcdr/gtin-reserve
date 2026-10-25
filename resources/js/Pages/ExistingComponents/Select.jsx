import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import { getAxiosErrorMessage } from '@/Utils/apiError';

export default function SelectExistingComponent({ auth, scope, context, mode, selected, filters, backUrl }) {
  const isView = mode === 'view';
  const isLevel = scope === 'level1' || scope === 'level2';
  const hasFilters = scope !== 'raw4';
  const title = `${({ fg: 'FG BOM', semi1: 'Semi FG LV1', semi2: 'Semi FG LV2', level1: 'Semi FG LV1 BOM', level2: 'Semi FG LV2 BOM', raw4: 'Raw Material' })[scope]} - ${isView ? 'View' : 'Add'} ${isLevel ? 'Existing BOM' : 'Existing Component'}`;
  const [step, setStep] = useState(hasFilters && !isView ? 1 : 2);
  const [options, setOptions] = useState([]);
  const [reloadOptions, setReloadOptions] = useState(0);
  const [loading, setLoading] = useState(scope === 'raw4' && !isView);
  const [loadError, setLoadError] = useState('');
  const [confirmDelete, setConfirmDelete] = useState(false);
  const { data, setData, post, delete: destroy, errors, clearErrors, processing, reset } = useForm({
    fgMaterialId: context.fgMaterialId, fgBomId: context.fgBomId,
    semiFgLv1BomId: context.semiFgLv1BomId || '', masterMaterialId: context.masterMaterialId || '',
    mattype: '', subMattype: '', brand: '', productSubCat: '', semiFgLv2BomId: context.semiFgLv2BomId || '', componentId: selected?.code || '', no: selected?.no || '',
  });
  const option = options.find((item) => item.value === data.componentId);
  const description = isView ? selected?.label || '' : option?.description || '';
  const canDelete = context.canWrite && selected?.status === 'INS' && selected?.userCreate === auth.user.user_login;

  useEffect(() => {
    if (isView || step !== 2) return;
    const controller = new AbortController();
    setLoading(true);
    setLoadError('');
    axios.get(route('existing-components.options', { scope }), {
      params: { fgMaterialId: context.fgMaterialId, fgBomId: context.fgBomId,
        semiFgLv1BomId: context.semiFgLv1BomId || '', masterMaterialId: context.masterMaterialId || '',
        mattype: data.mattype, subMattype: data.subMattype, brand: data.brand, productSubCat: data.productSubCat, semiFgLv2BomId: context.semiFgLv2BomId || '' },
      signal: controller.signal,
    }).then(({ data: payload }) => {
      if (!controller.signal.aborted) setOptions(payload.options);
    }).catch((error) => {
      if (!controller.signal.aborted) setLoadError(getAxiosErrorMessage(error, 'Unable to load Material IDs.'));
    }).finally(() => { if (!controller.signal.aborted) setLoading(false); });
    return () => controller.abort();
  }, [scope, context.fgMaterialId, context.fgBomId, context.semiFgLv1BomId, context.masterMaterialId, data.mattype, data.subMattype, data.brand, data.productSubCat, isView, step, reloadOptions]);

  const resetForm = () => {
    reset(); clearErrors(); setOptions([]); setLoadError('');
    setStep(hasFilters ? 1 : 2);
    setReloadOptions((value) => value + 1);
    setLoading(scope === 'raw4');
  };
  const changeFilter = (field, value) => {
    setData((current) => ({ ...current, [field]: value, componentId: '', ...(['mattype', 'subMattype'].includes(field) ? { productSubCat: '' } : {}) }));
    setOptions([]); clearErrors(); setLoadError('');
  };
  const headerFields = [
    ['FG BOM ID', context.fgBomId], ['FG BOM Description', context.fgBomDesc],
    ...(context.semiFgLv2BomId ? [['SEMI FG LV2 BOM ID', context.semiFgLv2BomId], ['SEMI FG LV2 BOM Description', context.semiFgLv2BomDesc]] : []),
    ...(context.semiFgLv1BomId ? [['SEMI FG LV1 BOM ID', context.semiFgLv1BomId], ['SEMI FG LV1 BOM Description', context.semiFgLv1BomDesc]] : []),
    ...(scope === 'raw4' ? [
      ['MASTER MATERIAL ID M4', context.masterMaterialId], ['Master Material Description', context.masterDescription],
    ] : []),
  ];
  return (
    <AuthenticatedLayout user={auth.user} header={<h2 className="text-xl font-semibold text-gray-800">{title}</h2>}>
      <Head title={title} />
      <div className="mx-auto max-w-7xl space-y-6 px-4 py-12 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-6 rounded-lg bg-white p-6 shadow sm:grid-cols-2">
          {headerFields.map(([label, value], index) => <div key={label}>
            <InputLabel htmlFor={`parent-${index}`} value={label} />
            <TextInput id={`parent-${index}`} value={value || ''} disabled className="mt-1 block w-full bg-gray-100" />
          </div>)}
        </div>
        {!context.canWrite && <p role="status" className="text-sm text-gray-600">ข้อมูล Complete หรือส่งออกแล้ว ดูรายละเอียดได้เท่านั้น</p>}
        <form className="space-y-6 rounded-lg bg-white p-6 shadow" onSubmit={(event) => {
          event.preventDefault();
          if (!isView && step === 2 && option && context.canWrite && !loading) post(route('existing-components.save', { scope }));
        }}>
          {hasFilters && !isView && <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
            {[...(isLevel ? [['brand', 'Brand', filters?.brands]] : []), [ 'mattype', 'Mattype', filters?.mattypes ], [ 'subMattype', 'Sub Mattype', filters?.subMattypes ], ...(scope === 'semi2' ? [['productSubCat', 'Product SUB Category', filters?.productSubCats]] : [])].map(([field, label, choices]) => <div key={field}>
              <InputLabel htmlFor={field} value={label} />
              <select id={field} value={data[field]} disabled={step !== 1 || processing || !context.canWrite} onChange={(event) => changeFilter(field, event.target.value)} className="mt-1 block w-full rounded-md border-gray-300">
                <option value="">---- Select {label} ----</option>
                {(choices || []).filter((item) => field !== 'productSubCat' || item.value.startsWith(data.mattype + data.subMattype)).map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}
              </select>
              <InputError message={errors[field]} className="mt-2" />
            </div>)}
          </div>}
          {step === 2 && <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <InputLabel htmlFor="existingComponentId" value={isLevel ? `SEMI FG LV${scope.slice(-1)} ID EXISTING` : scope === 'fg' ? 'Component FG Material ID' : scope === 'semi2' ? 'Component SEMI FG LV2 Material ID' : 'Component M4 Material ID'} />
              {isView ? <TextInput id="existingComponentId" value={isLevel ? selected?.id || data.componentId : data.componentId} disabled className="mt-1 block w-full bg-gray-100" /> :
                <select id="existingComponentId" value={data.componentId} onChange={(event) => setData('componentId', event.target.value)} disabled={loading || processing || !context.canWrite} className="mt-1 block w-full rounded-md border-gray-300">
                  <option value="">{loading ? 'Loading...' : '---- Select Material ID ----'}</option>
                  {options.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}
                </select>}
              {!loading && !isView && !loadError && options.length === 0 && <p className="mt-2 text-sm text-gray-500">ไม่มี Material ID ที่ตรงกับเงื่อนไข</p>}
            </div>
            <div><InputLabel htmlFor="existingDescription" value="Search Description" /><TextInput id="existingDescription" disabled value={description} className="mt-1 block w-full bg-gray-100" /></div>
          </div>}
          {isLevel && (option || selected) && <div className="grid grid-cols-1 gap-6 sm:grid-cols-2"><div><InputLabel value="Selected BOM ID" /><TextInput disabled value={option?.bomId || selected?.bomId || ''} className="mt-1 block w-full bg-gray-100" /></div><div><InputLabel value="BOM Description" /><TextInput disabled value={option?.bomDescription || selected?.bomDesc || ''} className="mt-1 block w-full bg-gray-100" /></div></div>}
          {(loadError || errors.componentId || errors.no || errors.fgBomId || errors.masterMaterialId || errors.semiFgLv1BomId || errors.fgMaterialId) && <p role="alert" className="text-sm text-red-600">{loadError || errors.componentId || errors.no || errors.fgBomId || errors.masterMaterialId || errors.semiFgLv1BomId || errors.fgMaterialId}</p>}
          <div className="flex flex-wrap justify-center gap-3">
            <SecondaryButton type="button" disabled={processing} onClick={() => step === 2 && hasFilters && !isView ? (setStep(1), setData('componentId', '')) : router.get(backUrl)}>BACK</SecondaryButton>
            {!isView && <SecondaryButton type="button" disabled={processing} onClick={resetForm}>RESET</SecondaryButton>}
            {step === 1 ? <PrimaryButton type="button" disabled={!data.mattype || data.subMattype === '' || (isLevel && !data.brand) || (scope === 'semi2' && !data.productSubCat) || !context.canWrite} onClick={() => { setLoading(true); setStep(2); }}>NEXT</PrimaryButton> : !isView && <PrimaryButton disabled={processing || loading || !option || !context.canWrite}>SAVE</PrimaryButton>}
            {isView && <DangerButton type="button" disabled={!canDelete || processing} onClick={() => setConfirmDelete(true)}>DELETE</DangerButton>}
          </div>
        </form>
      </div>
      <Modal show={confirmDelete} onClose={() => setConfirmDelete(false)} maxWidth="lg">
        <div className="space-y-5 p-6"><h3 className="text-lg font-semibold">Delete {isLevel ? 'Existing BOM' : 'Existing Component'}</h3><p>ลบรายการ {data.componentId} ออกจาก BOM นี้?</p>
          <div className="flex justify-end gap-3"><SecondaryButton type="button" disabled={processing} onClick={() => setConfirmDelete(false)}>CANCEL</SecondaryButton><DangerButton type="button" disabled={processing} onClick={() => destroy(route('existing-components.delete', { scope }), { onSuccess: () => setConfirmDelete(false), onError: () => setConfirmDelete(false) })}>DELETE</DangerButton></div>
        </div>
      </Modal>
    </AuthenticatedLayout>
  );
}
