// 1. اختيار العناصر الأساسية
const assignmentForm = document.getElementById('assignment-form');
const assignmentsTbody = document.getElementById('assignments-tbody');

let assignments = [];

// [JS-24, JS-25, JS-26] إنشاء صف في الجدول
function createAssignmentRow(assignment) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>${assignment.title}</td>
        <td>${assignment.due_date}</td>
        <td>${assignment.description}</td>
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
        const row = createAssignmentRow(assignment);
        assignmentsTbody.appendChild(row);
    });
}

// [JS-29, JS-30] إضافة واجب جديد
async function handleAddAssignment(event) {
    if (event) event.preventDefault(); 

    // اختيار المدخلات بطريقة آمنة لتجنب خطأ الـ null
    const titleEl = document.querySelector('[name="title"]');
    const dateEl = document.querySelector('[name="due_date"]');
    const descEl = document.querySelector('[name="description"]');
    const filesEl = document.querySelector('[name="files"]');

    const newAssignment = {
        title: titleEl ? titleEl.value : '',
        due_date: dateEl ? dateEl.value : '',
        description: descEl ? descEl.value : '',
        files: (filesEl && filesEl.value) ? filesEl.value.split(',') : []
    };

    try {
        const response = await fetch('./api/index.php', {
            method: 'POST',
            body: JSON.stringify(newAssignment)
        });

        const result = await response.json();
        if (result.success) {
            assignments.push(result.data);
            renderTable();
            if (assignmentForm) assignmentForm.reset(); 
        }
    } catch (error) {
        console.error("Error:", error);
    }
}

// جلب البيانات الأولية
async function loadAdminData() {
    try {
        const response = await fetch('./api/index.php');
        const result = await response.json();
        if (result.success) {
            assignments = result.data;
            renderTable();
        }
    } catch (error) {
        console.error("Error:", error);
    }
}

// ربط الأحداث
if (assignmentForm) {
    assignmentForm.addEventListener('submit', handleAddAssignment);
}

loadAdminData();
