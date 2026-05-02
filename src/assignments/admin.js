// 1. تعريف العناصر
const assignmentForm = document.getElementById('assignment-form');
const assignmentsTbody = document.getElementById('assignments-tbody');

let assignments = [];

// [JS-24, JS-25, JS-26] إنشاء الصف
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

// [JS-29, JS-30] إضافة واجب
async function handleAddAssignment(event) {
    if (event) event.preventDefault(); 
    const titleEl = document.querySelector('[name="title"]');
    const dateEl = document.querySelector('[name="due_date"]');
    const descEl = document.querySelector('[name="description"]');

    const newAssignment = {
        title: titleEl ? titleEl.value : '',
        due_date: dateEl ? dateEl.value : '',
        description: descEl ? descEl.value : '',
        files: [] 
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
            document.querySelector('[name="title"]').value = assignment.title;
            document.querySelector('[name="due_date"]').value = assignment.due_date;
            document.querySelector('[name="description"]').value = assignment.description;
        }
    }
}

// [JS-33, JS-34, JS-35] الدالة المطلوبة بالاسم
async function loadAndInitialize() {
    try {
        const response = await fetch('./api/index.php');
        const result = await response.json();
        if (result.success) {
            assignments = result.data;
            renderTable();
        }
        if (assignmentForm) assignmentForm.addEventListener('submit', handleAddAssignment);
        if (assignmentsTbody) assignmentsTbody.addEventListener('click', handleTableClick);
    } catch (error) { console.error(error); }
}

// تشغيل الدالة
loadAndInitialize();
