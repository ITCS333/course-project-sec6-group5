// 1. اختيار العناصر الأساسية من الصفحة
const assignmentForm = document.getElementById('assignment-form');
const assignmentsTbody = document.getElementById('assignments-tbody');

// مصفوفة لتخزين البيانات محلياً
let assignments = [];

/**
 * [JS-24, JS-25, JS-26] إنشاء صف في الجدول
 * يجب أن يحتوي على أربعة أعمدة وأزرار تعديل وحذف
 */
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

/**
 * [JS-27, JS-28] مسح الجدول وإعادة عرض البيانات
 */
function renderTable() {
    if (!assignmentsTbody) return;
    
    // مسح المحتوى القديم (JS-27)
    assignmentsTbody.innerHTML = ''; 
    
    // إضافة الصفوف الجديدة (JS-28)
    assignments.forEach(assignment => {
        const row = createAssignmentRow(assignment);
        assignmentsTbody.appendChild(row);
    });
}

/**
 * [JS-29, JS-30] معالجة إضافة واجب جديد
 */
async function handleAddAssignment(event) {
    // منع التحديث الافتراضي للصفحة (JS-29)
    event.preventDefault(); 

    const titleInput = document.querySelector('[name="title"]');
    const dateInput = document.querySelector('[name="due_date"]');
      const desInput = document.querySelector('[name="description"]');
      const filesInput = document.querySelector('[name="files"]');
    const newAssignment = {
        title: formData.get('title'),
        due_date: formData.get('due_date'),
        description: formData.get('description'),
        files: formData.get('files') ? formData.get('files').split(',') : []
    };

    try {
        // إرسال البيانات للسيرفر (JS-30)
        const response = await fetch('./api/index.php', {
            method: 'POST',
            body: JSON.stringify(newAssignment)
        });

        const result = await response.json();
        if (result.success) {
            assignments.push(result.data);
            renderTable();
            assignmentForm.reset(); // تصفير النموذج
        }
    } catch (error) {
        console.error("Error adding assignment:", error);
    }
}

/**
 */
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

// 2. ربط الأحداث وتشغيل الصفحة
if (assignmentForm) {
    assignmentForm.addEventListener('submit', handleAddAssignment);
}

loadAdminData();
