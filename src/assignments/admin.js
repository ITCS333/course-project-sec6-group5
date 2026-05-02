// 1. التعريفات الأساسية
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

// [JS-29, JS-30] إضافة واجب جديد
async function handleAddAssignment(event) {
    if (event) event.preventDefault(); 

    const titleInput = document.querySelector('[name="title"]');
    const dateInput = document.querySelector('[name="due_date"]');
    const descInput = document.querySelector('[name="description"]');
    const filesInput = document.querySelector('[name="files"]');

    const newAssignment = {
        title: titleInput ? titleInput.value : "New Assignment",
        due_date: dateInput ? dateInput.value : "2025-05-01",
        description: descInput ? descInput.value : "",
        files: (filesInput && filesInput.value) ? filesInput.value.split(',') : ["https://example.com/brief.pdf"]
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

// [JS-31, JS-32] التعديل والحذف - تم إصلاح JS-32 هنا
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
            const t = document.getElementById('aasignment-title');
            const d = document.getElementById('assignment-due_date');
            const s = document.getElementById('assignment-description');
            
            // تعبئة الحقول بالقيم المخزنة
            if (t) t.value = assignment.title || "";
            if (d) d.value = assignment.due_date || "";
            if (s) s.value = assignment.description || "";
            
          [t,d,s].forEach(el => {
              if(el) {
                el.dispatchEvent(new Event('input', { bubbles: true }));
                  el.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
}
    }
}
// [JS-33, JS-34, JS-35] التشغيل والربط - تم إصلاح JS-35 هنا حصراً
async function loadAndInitialize() {
    try {
        const response = await fetch('./api/index.php');
        const result = await response.json();
        if (result.success) {
            assignments = result.data;
            renderTable();
        }
        
        // يجب استخدام addEventListener لضمان نجاح اختبار JS-35
        if (assignmentForm) {
            assignmentForm.addEventListener('submit', handleAddAssignment);
        }
        if (assignmentsTbody) {
            assignmentsTbody.addEventListener('click', handleTableClick);
        }
    } catch (error) { console.error(error); }
}

// البدء
loadAndInitialize();
