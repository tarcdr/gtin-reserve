import { test } from 'node:test';
import assert from 'node:assert/strict';
import { watchExportTabAccess } from '../../resources/js/Utils/watchExportTabAccess.js';

const flush = () => new Promise(resolve => setImmediate(resolve));
function fixture(fetchRequest, timers = {}) {
    const documentTarget = new EventTarget();
    documentTarget.visibilityState = 'visible';
    const windowTarget = new EventTarget();
    const access = [];
    const unauthorized = [];
    let tick;
    let stopped = false;
    const stop = watchExportTabAccess({
        url: '/export-to-sap/access', documentTarget, windowTarget, fetchRequest,
        onAccess: data => access.push(data), onUnauthorized: () => unauthorized.push(true),
        startInterval: (callback, milliseconds) => { assert.equal(milliseconds, 15000); tick = callback; return 7; },
        stopInterval: id => { assert.equal(id, 7); stopped = true; },
        ...timers,
    });
    return { documentTarget, windowTarget, access, unauthorized, tick: () => tick(), stop, stopped: () => stopped };
}
const response = data => ({ ok: true, status: 200, json: async () => data });

test('polls current state every 15 seconds and immediately on visible/focus; cleanup removes listeners', async () => {
    let enabled = true;
    const calls = [];
    const f = fixture(async (url, options) => { calls.push({ url, options }); return response({ enabled, canAccess: enabled }); });
    await flush();
    assert.deepEqual(f.access, [{ enabled: true, canAccess: true }]);
    assert.equal(calls[0].url, '/export-to-sap/access');
    assert.equal(calls[0].options.cache, 'no-store');
    assert.equal(calls[0].options.credentials, 'same-origin');
    enabled = false;
    await f.tick();
    assert.deepEqual(f.access.at(-1), { enabled: false, canAccess: false });
    f.documentTarget.visibilityState = 'hidden';
    f.documentTarget.dispatchEvent(new Event('visibilitychange'));
    await f.tick();
    assert.equal(calls.length, 2);
    f.documentTarget.visibilityState = 'visible';
    f.documentTarget.dispatchEvent(new Event('visibilitychange'));
    await flush();
    assert.equal(calls.length, 3);
    enabled = true;
    f.windowTarget.dispatchEvent(new Event('focus'));
    await flush();
    assert.deepEqual(f.access.at(-1), { enabled: true, canAccess: true });
    f.stop();
    f.windowTarget.dispatchEvent(new Event('focus'));
    f.documentTarget.dispatchEvent(new Event('visibilitychange'));
    await f.tick();
    assert.equal(calls.length, 4);
    assert.equal(f.stopped(), true);
});

test('network and malformed responses retry; expired session is reported; admin canAccess stays true when disabled', async () => {
    const replies = [() => { throw new Error('offline'); }, () => response({ enabled: false }),
        () => ({ status: 401, ok: false }), () => response({ enabled: false, canAccess: true })];
    const f = fixture(async () => replies.shift()());
    await flush();
    assert.deepEqual(f.access, []);
    await f.tick();
    assert.deepEqual(f.access, []);
    await f.tick();
    assert.equal(f.unauthorized.length, 1);
    await f.tick();
    assert.deepEqual(f.access, [{ enabled: false, canAccess: true }]);
    f.stop();
});

test('no overlapping polls; cleanup aborts a pending request and ignores its late result', async () => {
    let resolve;
    let signal;
    let calls = 0;
    const f = fixture((url, options) => { calls++; signal = options.signal; return new Promise(done => { resolve = done; }); });
    await f.tick();
    assert.equal(calls, 1);
    f.stop();
    assert.equal(signal.aborted, true);
    resolve(response({ enabled: false, canAccess: false }));
    await flush();
    assert.deepEqual(f.access, []);
});

test('hiding tab aborts request; stale response cannot overwrite the newest state', async () => {
    let oldResolve;
    let calls = 0;
    const f = fixture(() => ++calls === 1 ? new Promise(resolve => { oldResolve = resolve; }) : Promise.resolve(response({ enabled: false, canAccess: false })));
    f.documentTarget.visibilityState = 'hidden';
    f.documentTarget.dispatchEvent(new Event('visibilitychange'));
    f.documentTarget.visibilityState = 'visible';
    f.documentTarget.dispatchEvent(new Event('visibilitychange'));
    await flush();
    oldResolve(response({ enabled: true, canAccess: true }));
    await flush();
    assert.deepEqual(f.access, [{ enabled: false, canAccess: false }]);
    f.stop();
});


test('a hung status request times out and can retry on the next interval', async () => {
    let timeout;
    let signal;
    let oldResolve;
    let calls = 0;
    const f = fixture((url, options) => {
        signal = options.signal;
        return ++calls === 1 ? new Promise(resolve => { oldResolve = resolve; }) : Promise.resolve(response({ enabled: false, canAccess: false }));
    }, {
        startTimeout: (callback, milliseconds) => { assert.equal(milliseconds, 10000); timeout = callback; return 1; },
        stopTimeout: () => {},
    });
    timeout();
    assert.equal(signal.aborted, true);
    await f.tick();
    assert.equal(calls, 2);
    oldResolve(response({ enabled: true, canAccess: true }));
    await flush();
    assert.deepEqual(f.access, [{ enabled: false, canAccess: false }]);
    f.stop();
});
