import test from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { renderStudents } from '../../resources/js/scanner-table.js';
import * as scannerTable from '../../resources/js/scanner-table.js';

test('student names and NISN are literal text, including executable HTML', () => {
    const dom = new JSDOM('<table><tbody></tbody></table>');
    const body = dom.window.document.querySelector('tbody');
    const name = '<img src=x onerror="globalThis.compromised=true">';
    const nisn = '"][data-id="other"]';
    const selects = renderStudents(body, [{ id: 4, name, nisn, status: 'alpa' }], () => {});
    assert.equal(body.querySelector('img'), null);
    assert.ok(body.textContent.includes(name));
    assert.ok(body.textContent.includes(nisn));
    assert.equal(selects.get(nisn).value, 'alpa');
    assert.equal(selects.get(nisn).getAttribute('aria-label'), `Status kehadiran ${name}`);
});

test('saving sends only changed students together with their previous saved status', () => {
    const dom = new JSDOM('<table><tbody></tbody></table>');
    const selects = renderStudents(dom.window.document.querySelector('tbody'), [
        { id: 1, name: 'Ayu', nisn: '001', status: 'alpa' },
        { id: 2, name: 'Budi', nisn: '002', status: 'hadir' },
        { id: 3, name: 'Cici', nisn: '003', status: '' },
    ], () => {});
    selects.get('001').value = 'izin';
    selects.get('003').value = 'hadir';
    assert.equal(typeof scannerTable.attendanceChanges, 'function');
    assert.deepEqual(scannerTable.attendanceChanges(selects), [
        { student_id: '1', status: 'izin', expected_status: 'alpa' },
        { student_id: '3', status: 'hadir', expected_status: null },
    ]);
    selects.get('001').value = 'alpa';
    selects.get('003').value = '';
    assert.deepEqual(scannerTable.attendanceChanges(selects), []);
});

test('manual changes identify the student and trigger the unsaved-state callback', () => {
    const dom = new JSDOM('<table><tbody></tbody></table>');
    const body = dom.window.document.querySelector('tbody');
    const changed = [];
    const selects = renderStudents(body, [{ id: 9, name: 'Ayu', nisn: '0000000009', status: '' }], select => changed.push(select.dataset.id));
    const select = selects.get('0000000009');
    select.value = 'hadir';
    select.dispatchEvent(new dom.window.Event('change'));
    assert.deepEqual(changed, ['9']);
});
