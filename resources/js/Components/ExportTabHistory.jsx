import { useEffect, useState } from 'react';
import SecondaryButton from './SecondaryButton';

const stateLabel = value => value === '1' ? 'Enabled' : value === '0' ? 'Disabled' : value ?? '—';
export const formatAuditTime = value => {
    if (!value) return '—';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '—' : new Intl.DateTimeFormat('th-TH-u-ca-gregory', {
        timeZone: 'Asia/Bangkok', dateStyle: 'short', timeStyle: 'medium',
    }).format(date);
};

export default function ExportTabHistory() {
    const [page, setPage] = useState(1);
    const [retry, setRetry] = useState(0);
    const [result, setResult] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    // A successful setting change remounts this component via its key in UserManagement.
    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        setError('');
        (async () => {
            try {
                const response = await fetch(route('admin.settings.export-to-sap-tab.audit', { page }), {
                    credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
                    headers: { Accept: 'application/json' },
                });
                const body = await response.json();
                if (!response.ok) throw new Error(body.message || 'ไม่สามารถอ่านประวัติได้');
                if (!Array.isArray(body.data)) throw new Error('รูปแบบข้อมูลประวัติไม่ถูกต้อง');
                if (!controller.signal.aborted) setResult(body);
            } catch (exception) {
                if (!controller.signal.aborted) setError(exception.message || 'ไม่สามารถอ่านประวัติได้');
            } finally {
                if (!controller.signal.aborted) setLoading(false);
            }
        })();
        return () => controller.abort();
    }, [page, retry]);

    return <ExportTabHistoryContent loading={loading} error={error} result={result} page={page}
        onRetry={() => setRetry(value => value + 1)} onPage={setPage} />;
}

export function ExportTabHistoryContent({ loading, error, result, page = 1, onRetry, onPage }) {
    return <section className="space-y-4 rounded-lg bg-white p-6 shadow-sm" aria-label="ประวัติการเปิดปิด Export-to-SAP">
        <h3 className="text-lg font-medium text-gray-900">ประวัติการเปิด/ปิด Export-to-SAP</h3>
        {loading ? <p role="status" className="text-sm font-normal text-gray-500">กำลังโหลดประวัติ…</p> : error ? <div role="alert" className="space-y-3 text-sm font-normal text-red-700"><p>{error}</p><SecondaryButton onClick={onRetry}>ลองใหม่</SecondaryButton></div> : <>
            {result?.data.length ? <div className="overflow-x-auto"><table className="w-full text-left text-sm font-normal text-gray-700">
                <thead className="bg-gray-50 text-[#4C5E86]"><tr>{['เวลา (ประเทศไทย)', 'ผู้แก้ไข', 'ค่าเดิม', 'ค่าใหม่'].map(label => <th key={label} className="whitespace-nowrap px-3 py-3">{label}</th>)}</tr></thead>
                <tbody>{result.data.map(entry => <tr key={entry.id} className="border-b border-gray-100">
                    <td className="whitespace-nowrap px-3 py-3">{formatAuditTime(entry.changed_at)}</td>
                    <td className="px-3 py-3 break-all">{entry.changed_by || '—'}</td>
                    <td className="px-3 py-3">{entry.previous_value === null ? 'ข้อมูลเดิมก่อนเริ่มเก็บประวัติ' : stateLabel(entry.previous_value)}</td>
                    <td className="px-3 py-3">{stateLabel(entry.value)}</td>
                </tr>)}</tbody>
            </table></div> : <p role="status" className="text-sm font-normal text-gray-500">ยังไม่มีประวัติ</p>}
            <div className="flex flex-wrap items-center gap-3 text-sm font-normal">
                <SecondaryButton disabled={page <= 1} onClick={() => onPage(page - 1)}>Previous</SecondaryButton>
                <span>หน้า {result?.current_page ?? 1} / {result?.last_page ?? 1} · {result?.total ?? 0} รายการ</span>
                <SecondaryButton disabled={page >= (result?.last_page ?? 1)} onClick={() => onPage(page + 1)}>Next</SecondaryButton>
            </div>
        </>}
    </section>;
}
