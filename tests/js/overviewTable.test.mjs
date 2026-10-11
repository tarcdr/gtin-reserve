import { test } from 'node:test';
import assert from 'node:assert/strict';
import { cellSpan, resultState } from '../../resources/js/Pages/Overview/overviewTable.js';

test('rowSpan preserves duplicate components and separates complete parent paths', () => {
    const rows = [
        { parents: ['L2a', 'L1'], id: 'R4', component: 'C' },
        { parents: ['L2a', 'L1'], id: 'R4', component: 'C' },
        { parents: ['L2b', 'L1'], id: 'R4', component: 'C' },
    ];
    assert.deepEqual([0, 1, 2].map(column => cellSpan(rows, 0, column)), [2, 2, 2]);
    assert.equal(cellSpan(rows, 1, 2), 0);
    assert.equal(cellSpan(rows, 2, 2), 1);
    assert.equal(rows.length, 3);
    assert.equal(cellSpan([{parents: [null], id: 'R4'}, {parents: [null], id: 'R4'}], 0, 1), 1);
});

test('only Y/N are checkboxes; missing and unknown values remain text', () => {
    assert.equal(resultState('Y'), true);
    assert.equal(resultState(' n '), false);
    for (const value of [null, '', '—', 'unexpected', '1', '0']) assert.equal(resultState(value), null);
});
