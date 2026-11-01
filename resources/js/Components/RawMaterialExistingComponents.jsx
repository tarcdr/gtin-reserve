import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';
import SuccessButton from '@/Components/SuccessButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import { getAxiosErrorMessage } from '@/Utils/apiError';

export default function RawMaterialExistingComponents({ data, embedded = false }) {
  const user = usePage().props.auth.user;
  const [payload, setPayload] = useState(null);
  const [error, setError] = useState('');
  const context = {
    fgMaterialId: data.fgMaterialId, fgBomId: data.fgBomId,
    semiFgLv1BomId: data.semiFgLvBomId, masterMaterialId: data.componentId,
  };
  useEffect(() => {
    const controller = new AbortController();
    setPayload(null); setError('');
    axios.get(route('existing-components.options', { scope: 'raw4' }), {
      params: { fgMaterialId: data.fgMaterialId, fgBomId: data.fgBomId, semiFgLv1BomId: data.semiFgLvBomId, masterMaterialId: data.componentId },
      signal: controller.signal,
    }).then(({ data: result }) => { if (!controller.signal.aborted) setPayload(result); })
      .catch((failure) => { if (!controller.signal.aborted) setError(getAxiosErrorMessage(failure, 'ยังไม่พร้อมใช้งาน กรุณาติดต่อผู้ดูแลระบบ')); });
    return () => controller.abort();
  }, [data.fgMaterialId, data.fgBomId, data.semiFgLvBomId, data.componentId]);
  const openNew = (row, actionMode = 'create') => router.get(route('raw-child.form'), { ...context, actionMode, ...(row ? { childId: row.code } : {}) });
  const open = (row, mode = 'view') => router.get(route('existing-components.index', { scope: 'raw4' }), { ...context, ...(row ? { mode, no: row.no, componentId: row.code } : {}) });
  return <div className="space-y-6">
    <section aria-label="Raw Material Components" className={embedded ? 'space-y-5 rounded-lg border border-gray-200 p-4' : 'space-y-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6'}>
      <header><h3 className="text-base font-semibold text-gray-800">Raw Material Components</h3><p className="mt-1 text-xs font-normal text-gray-500">Materials used to build this component</p></header>
    <div>
      <div className="mb-3 flex flex-wrap items-center justify-between gap-3"><h4 className="text-sm font-semibold">From existing</h4><SuccessButton type="button" disabled={!payload?.context.canWrite} onClick={() => open()}>ADD MATERIAL ID</SuccessButton></div>
      {error ? <p role="alert" className="text-sm text-red-600">{error}</p> : !payload ? <p role="status" className="text-sm text-gray-500">Loading...</p> :
        <div className="overflow-x-auto"><table className="w-full min-w-[640px] table-fixed text-left text-sm"><colgroup>{[6, 24, 44, 26].map((width) => <col key={width} style={{ width: `${width}%` }} />)}</colgroup>
          {payload.rows.length > 0 && <thead className="bg-gray-50"><tr>{['#', 'Material ID/Component ID', 'Description', 'Action'].map((label) => <th key={label} scope="col" className="px-4 py-3">{label}</th>)}</tr></thead>}
          <tbody>{payload.rows.length ? payload.rows.map((row, index) => <tr key={row.no}><td className="px-4 py-3">{index + 1}</td><td className="px-4 py-3">{row.code}</td><td className="px-4 py-3">{row.label || payload.options.find((item) => item.value === row.code)?.description || '—'}</td><td className="px-4 py-3"><div className="flex gap-2"><SecondaryButton type="button" onClick={() => open(row)}>VIEW</SecondaryButton><DangerButton type="button" disabled={!payload.context.canWrite || row.status !== 'INS' || row.userCreate !== user.user_login} onClick={() => open(row, 'delete')}>DELETE</DangerButton></div></td></tr>) : <tr><td colSpan="4" className="px-4 py-4 text-gray-500">No existing components yet.</td></tr>}</tbody>
        </table></div>}
    </div>
    <div className="border-t border-gray-100 pt-5">
    <div>
      <div className="mb-3 flex flex-wrap items-center justify-between gap-3"><h4 className="text-sm font-semibold">New</h4><SuccessButton type="button" disabled={!payload?.context.canWrite} onClick={() => openNew()}>ADD COMPONENT NEW</SuccessButton></div>
      <div className="mt-3 overflow-x-auto"><table className="w-full min-w-[640px] table-fixed text-left text-sm"><colgroup>{[6, 24, 44, 26].map((width) => <col key={width} style={{ width: `${width}%` }} />)}</colgroup>
        {payload?.newRows?.length > 0 && <thead className="bg-gray-50"><tr>{['#', 'Material ID/Component ID', 'Description', 'Action'].map((label) => <th key={label} scope="col" className="px-4 py-3">{label}</th>)}</tr></thead>}
        <tbody>{payload?.newRows?.length ? payload.newRows.map((row, index) => <tr key={`${row.code}-${index}`}><td className="px-4 py-3">{index + 1}</td><td className="px-4 py-3">{row.code}</td><td className="px-4 py-3">{row.label || '—'}</td><td className="px-4 py-3"><div className="flex gap-2"><SecondaryButton type="button" onClick={() => openNew(row, 'view')}>VIEW</SecondaryButton><DangerButton type="button" disabled={!payload?.context.canWrite || row.status !== 'INS' || row.userCreate !== user.user_login} onClick={() => openNew(row, 'delete')}>DELETE</DangerButton></div></td></tr>) : <tr><td colSpan="4" className="px-4 py-4 text-gray-500">{payload ? 'No new components yet.' : error ? 'ยังแสดงรายการไม่ได้' : 'Loading...'}</td></tr>}</tbody>
      </table></div>
    </div>
    </div>
    </section>
  </div>;
}
