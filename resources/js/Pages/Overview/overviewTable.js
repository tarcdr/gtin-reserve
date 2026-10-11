export function cellSpan(rows, index, column) {
    const prefix = (row) => [...row.parents, row.id].slice(0, column + 1);
    const current = prefix(rows[index]);
    if (current.some((value) => value === null || value === undefined || value === '')) return 1;
    const matches = (row) => JSON.stringify(prefix(row)) === JSON.stringify(current);
    if (index > 0 && matches(rows[index - 1])) return 0;
    let count = 1;
    while (index + count < rows.length && matches(rows[index + count])) count++;
    return count;
}

export function resultState(raw) {
    const value = String(raw ?? '').trim().toUpperCase();
    return value === 'Y' ? true : value === 'N' ? false : null;
}
