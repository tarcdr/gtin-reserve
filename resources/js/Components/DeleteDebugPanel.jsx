import { usePage } from '@inertiajs/react';

export default function DeleteDebugPanel({ debug: explicitDebug = null, className = '' }) {
  const { flash } = usePage().props;
  const debug = explicitDebug || flash?.deleteDebug || null;

  if (!debug) {
    return null;
  }

  const bindings = debug.bindings || {};
  const context = debug.context || {};

  return (
    <div className={`rounded-md border border-amber-300 bg-amber-50 p-4 text-sm font-medium text-amber-950 ${className}`}>
      <div className="font-bold">Delete Procedure Debug</div>
      <div className="mt-2 grid grid-cols-1 gap-1 md:grid-cols-2">
        <div>Procedure: {debug.program || 'not mapped'}</div>
        <div>P_CNT_ROW: {debug.countRow ?? '-'}</div>
        <div>P_ERROR: {debug.rawError || '-'}</div>
        <div>Resolved Error: {debug.resolvedError || '-'}</div>
      </div>
      {Object.keys(bindings).length > 0 ? (
        <div className="mt-3">
          <div className="font-bold">Parameters</div>
          <dl className="mt-1 grid grid-cols-1 gap-1 md:grid-cols-2">
            {Object.entries(bindings).map(([key, value]) => (
              <div key={`delete-debug-binding-${key}`}>
                <dt className="inline">{key}: </dt>
                <dd className="inline break-all">{value === '' || value === null || value === undefined ? '-' : String(value)}</dd>
              </div>
            ))}
          </dl>
        </div>
      ) : null}
      {Object.keys(context).length > 0 ? (
        <div className="mt-3">
          <div className="font-bold">Context</div>
          <dl className="mt-1 grid grid-cols-1 gap-1 md:grid-cols-2">
            {Object.entries(context).map(([key, value]) => (
              <div key={`delete-debug-context-${key}`}>
                <dt className="inline">{key}: </dt>
                <dd className="inline break-all">{value === '' || value === null || value === undefined ? '-' : String(value)}</dd>
              </div>
            ))}
          </dl>
        </div>
      ) : null}
    </div>
  );
}
