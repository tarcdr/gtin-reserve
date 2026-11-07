export const EXPORT_TAB_CLOSED_NOTICE = 'ผู้ใช้อื่นไม่เห็นเมนู Export-to-SAP และไม่สามารถเข้าใช้งานได้';

// Poll only the small access endpoint, never the report data. Dependencies are injectable for lifecycle tests.
export function watchExportTabAccess({ url, onAccess, onUnauthorized, fetchRequest = fetch,
    documentTarget = document, windowTarget = window,
    startInterval = setInterval, stopInterval = clearInterval, startTimeout = setTimeout, stopTimeout = clearTimeout }) {
    let disposed = false;
    let pending = null;
    let pendingTimeout = null;
    const check = async () => {
        if (disposed || documentTarget.visibilityState === 'hidden' || pending) return;
        const controller = new AbortController();
        pending = controller;
        const timeout = startTimeout(() => {
            controller.abort();
            if (pending === controller) { pending = null; pendingTimeout = null; }
        }, 10000);
        pendingTimeout = timeout;
        try {
            const response = await fetchRequest(url, {
                credentials: 'same-origin', cache: 'no-store',
                headers: { Accept: 'application/json' }, signal: controller.signal,
            });
            if (disposed || controller.signal.aborted) return;
            if (response.status === 401) { onUnauthorized?.(); return; }
            if (!response.ok) return;
            const data = await response.json();
            if (!disposed && !controller.signal.aborted && typeof data.enabled === 'boolean' && typeof data.canAccess === 'boolean') onAccess(data);
        } catch {
            // Offline or aborted: retry on the next interval/focus. The server still guards every action.
        } finally {
            stopTimeout(timeout);
            if (pending === controller) { pending = null; pendingTimeout = null; }
        }
    };
    const onVisibility = () => {
        if (documentTarget.visibilityState === 'hidden') {
            pending?.abort();
            stopTimeout(pendingTimeout);
            pendingTimeout = null;
            pending = null;
        } else check();
    };
    const interval = startInterval(check, 15000);
    documentTarget.addEventListener('visibilitychange', onVisibility);
    windowTarget.addEventListener('focus', check);
    check();
    return () => {
        disposed = true;
        pending?.abort();
        stopTimeout(pendingTimeout);
        stopInterval(interval);
        documentTarget.removeEventListener('visibilitychange', onVisibility);
        windowTarget.removeEventListener('focus', check);
    };
}
