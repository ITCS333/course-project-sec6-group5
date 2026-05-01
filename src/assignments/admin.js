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

// [JS-29, JS-30] إضافة واجب جديد (تم التعديل لتجاوز خطأ FormData)
async function handleAddAssignment(event) {
    event.preventDefault(); 

    // جلب القيم يدوياً باستخدام id أو name لضمان عملها في بيئة الاختبار
    const title = document.querySelector('[name="title"]').value;
    const due_date = document.querySelector('[name="due_date"]').value;
    const description = document.querySelector('[name="description"]').value;
    const filesInput = document.querySelector('[name="files"]');
    const files = filesInput && filesInput.value ? filesInput.value.split(',') : [];

    const newAssignment = {
        title: title,
        due_date: due_date,
        description: description,
        files: files
    };

    try {
        const response = await fetch('./api/index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(newAssignment)
        });

        const result = await response.json();
        if (result.success) {
            assignments.push(result.data);
            renderTable();
            if (assignmentForm) assignmentForm.reset(); 
        }
    } catch (error) {
        console.error("Error adding assignment:", error);
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
        console.error("Error loading data:", error);
    }
}

// ربط الأحداث
if (assignmentForm) {
    assignmentForm.addEventListener('submit', handleAddAssignment);
}

loadAdminData();
