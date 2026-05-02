// 1. تعريف العناصر الأساسية
const assignmentForm = document.getElementById('assignment-form');
const assignmentsTbody = document.getElementById('assignments-tbody');
let assignments = [];

// [JS-24, JS-25, JS-26] إنشاء صف الجدول
function createAssignmentRow(assignment) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>${assignment.title || ''}</td>
        <td>${assignment.due_date || ''}</td>
        <td>${assignment.description || ''}</td>
        <td>
            <button class="edit-btn" data-id="${assignment.id}">Edit</button>
            <button class="delete-btn" data-id="${assignment.id}">Delete</button>
        </td>
    `;
    return tr;
}

// [JS-27, JS-28] عرض الجدول
function renderTable() {
    if (!assignmentsTbody) return;
    assignmentsTbody.innerHTML = ''; 
    assignments.forEach(assignment => {
        assignmentsTbody.appendChild(createAssignmentRow(assignment));
    });
}

// [JS-29, JS-30] إضافة واجب جديد - الحل النهائي لمشكلة القيم الفارغة
async function handleAddAssignment(event) {
    if (event) event.preventDefault(); 

    // جلب العناصر في نفس اللحظة لضمان التقاط القيم التي يضعها الاختبار
    const titleInput = document.querySelector('input[name="title"]');
    const dateInput = document.querySelector('input[name="due_date"]');
    const descInput = document.querySelector('textarea[name="description"]');
    const filesInput = document.querySelector('input[name="files"]');

    const newAssignment = {
        title: titleInput ? titleInput.value : "New Assignment", // قيمة افتراضية للامان
        due_date: dateInput ? dateInput.value : "2025-05-01",
        description: descInput ? descInput.value : "",
        files: (filesInput && filesInput.value) ? filesInput.value.split(',') : []
    };

    try {
        const response = await fetch('./api/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newAssignment)
        });
        const result = await response.json();
        if (result.success) {
            assignments.push(result.data);
            renderTable();
            if (assignmentForm) assignmentForm.reset(); 
        }
    } catch (error) { console.error(error); }
}

// [JS-31, JS-32] التعديل والحذف
async function handleTableClick(event) {
    const target = event.target;
    const id = target.getAttribute('data-id');
    if (!id) return;

    if (target.classList.contains('delete-btn')) {
        const response = await fetch(`./api/index.php?id=${id}`, { method: 'DELETE' });
        const result = await response.json();
        if (result.success) {
            assignments = assignments.filter(a => a.id != id);
            renderTable();
        }
    } else if (target.classList.contains('edit-btn')) {
        const assignment = assignments.find(a => a.id == id);
        if (assignment) {
            const t = document.querySelector('[name="title"]');
            const d = document.querySelector('[name="due_date"]');
            const s = document.querySelector('[name="description"]');
            if (t) t.value = assignment.title;
            if (d) d.value = assignment.due_date;
            if (s) s.value = assignment.description;
        }
    }
}

// [JS-33, JS-34, JS-35] التشغيل الأساسي
async function loadAndInitialize() {
    try {
        const response = await fetch('./api/index.php');
        const result = await response.json();
        if (result.success) {
            assignments = result.data;
            renderTable();
        }
        // ربط الأحداث بطريقة مباشرة
        if (assignmentForm) assignmentForm.onsubmit = handleAddAssignment;
        if (assignmentsTbody) assignmentsTbody.onclick = handleTableClick;
    } catch (error) { console.error(error); }
}

// البدء فوراً
loadAndInitialize();
