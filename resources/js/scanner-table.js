const statusClasses = {
    hadir: 'bg-emerald-50 text-emerald-800 border-emerald-300',
    sakit: 'bg-blue-50 text-blue-800 border-blue-300',
    izin: 'bg-yellow-50 text-yellow-900 border-yellow-400',
    alpa: 'bg-red-50 text-red-800 border-red-300',
};

export function colorStatus(select) {
    select.className = `status-dropdown w-full min-h-11 text-xs font-bold rounded-lg px-2 py-2 border focus:ring-2 focus:ring-blue-500 ${statusClasses[select.value] || 'bg-white text-gray-700 border-gray-400'}`;
}

export function attendanceChanges(selects) {
    return Array.from(selects.values())
        .filter(select => select.value && select.value !== select.dataset.savedStatus)
        .map(select => ({ student_id: select.dataset.id, status: select.value, expected_status: select.dataset.savedStatus || null }));
}

// The server remains authoritative; this only spares the teacher a rejected save.
export function reasonRequirement(attendances, reason, lateAfter, now) {
    if (reason.length >= 10) return null;
    const late = Boolean(lateAfter) && now >= Date.parse(lateAfter);
    if (late) return { title: 'Alasan pencatatan susulan diperlukan', text: 'Batas waktu pencatatan sudah lewat. Isi alasan minimal 10 karakter untuk menyimpan presensi.' };
    if (attendances.some(row => row.expected_status !== null)) return { title: 'Alasan koreksi diperlukan', text: 'Isi alasan minimal 10 karakter untuk mengubah status yang sudah tersimpan.' };
    return null;
}

export function renderStudents(tbody, students, onChange) {
    const document = tbody.ownerDocument;
    const selects = new Map();
    tbody.replaceChildren();
    students.forEach((student, index) => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-blue-50 student-row';
        row.id = `student-row-${student.id}`;
        const number = document.createElement('td');
        number.className = 'px-4 py-4 text-center';
        number.textContent = String(index + 1);
        const details = document.createElement('td');
        details.className = 'px-4 py-4';
        const name = document.createElement('div');
        name.className = 'font-bold text-gray-800 student-name-text max-w-xs';
        name.style.overflowWrap = 'anywhere';
        name.textContent = student.name;
        const nisn = document.createElement('div');
        nisn.className = 'text-xs text-gray-600 font-mono';
        nisn.textContent = `NISN: ${student.nisn}`;
        details.append(name, nisn);
        const status = document.createElement('td');
        status.className = 'px-4 py-4 text-center';
        const select = document.createElement('select');
        select.dataset.id = String(student.id);
        select.dataset.savedStatus = student.status || '';
        select.setAttribute('aria-label', `Status kehadiran ${student.name}`);
        for (const [value, label] of [['', '-- BELUM ABSEN --'], ['hadir', 'HADIR'], ['sakit', 'SAKIT'], ['izin', 'IZIN'], ['alpa', 'ALPA']]) {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label;
            option.disabled = value === '' && Boolean(student.status);
            select.append(option);
        }
        select.value = student.status || '';
        colorStatus(select);
        select.addEventListener('change', () => onChange(select));
        status.append(select);
        row.append(number, details, status);
        tbody.append(row);
        selects.set(String(student.nisn), select);
    });
    return selects;
}
