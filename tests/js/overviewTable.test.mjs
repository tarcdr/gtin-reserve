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

test('Business Supply merges consecutive BS IDs while retaining every component row', () => {
    const rows = [
        { parents: [], id: '60LS00370', component: '501012063' },
        { parents: [], id: '60LS00370', component: '510001122' },
        { parents: [], id: '60LS00370', component: '510005689' },
        { parents: [], id: '60LS00371', component: '510005689' },
        { parents: [], id: '60LS00370', component: '501012063' },
    ];
    assert.deepEqual(rows.map((_, index) => cellSpan(rows, index, 0)), [3, 0, 0, 1, 1]);
    assert.equal(rows.length, 5);
    assert.equal(cellSpan([{ parents: [], id: '' }, { parents: [], id: '' }], 0, 0), 1);
});
