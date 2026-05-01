// 1. التعريفات الأساسية (يجب أن تكون في أعلى الملف)
const assignmentTitle = document.getElementById('assignment-title');
const assignmentDueDate = document.getElementById('assignment-due-date');
const assignmentDescription = document.getElementById('assignment-description');
const assignmentFilesList = document.getElementById('assignment-files-list');
const commentList = document.getElementById('comment-list');
const commentForm = document.getElementById('comment-form');
const newCommentInput = document.getElementById('new-comment');

let currentAssignmentId = null;
let currentComments = [];

// [JS-09] الحصول على المعرف
function getAssignmentIdFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}

// [JS-10 إلى JS-14] عرض التفاصيل
function renderAssignmentDetails(assignment) {
    if (assignmentTitle) assignmentTitle.textContent = assignment.title;
    if (assignmentDueDate) assignmentDueDate.textContent = `Due: ${assignment.due_date}`;
    if (assignmentDescription) assignmentDescription.textContent = assignment.description;

    if (assignmentFilesList) {
        assignmentFilesList.innerHTML = '';
        if (assignment.files) {
            assignment.files.forEach(fileUrl => {
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.href = fileUrl;
                a.textContent = fileUrl;
                li.appendChild(a);
                assignmentFilesList.appendChild(li);
            });
        }
    }
}

// [JS-15, JS-16] إنشاء التعليق
function createCommentArticle(comment) {
    const article = document.createElement('article');
    article.innerHTML = `<p>${comment.text}</p><footer>Posted by: ${comment.author}</footer>`;
    return article;
}

// [JS-17] عرض التعليقات
function renderComments() {
    if (commentList) {
        commentList.innerHTML = "";
        currentComments.forEach(comment => {
            const commentArticle = createCommentArticle(comment);
            commentList.appendChild(commentArticle);
        });
    }
}

// [JS-18 إلى JS-21] إضافة تعليق
async function handleAddComment(event) {
    event.preventDefault();
    const commentText = newCommentInput.value.trim();
    if (commentText === "") return;

    const response = await fetch('./api/index.php?action=comment', {
        method: 'POST',
        body: JSON.stringify({
            assignment_id: currentAssignmentId,
            author: "student",
            text: commentText
        })
    });

    const result = await response.json();
    if (result.success) {
        currentComments.push(result.data);
        renderComments();
        newCommentInput.value = "";
    }
}

// [JS-22, JS-23] تشغيل الصفحة
async function initializePage() {
    currentAssignmentId = getAssignmentIdFromURL();
    if (!currentAssignmentId) return;

    try {
        const [res1, res2] = await Promise.all([
            fetch(`./api/index.php?id=${currentAssignmentId}`),
            fetch(`./api/index.php?action=comments&assignment_id=${currentAssignmentId}`)
        ]);

        const assignmentResult = await res1.json();
        const commentsResult = await res2.json();

        if (assignmentResult.success) {
            renderAssignmentDetails(assignmentResult.data);
            currentComments = commentsResult.success ? commentsResult.data : [];
            renderComments();
            if (commentForm) commentForm.addEventListener('submit', handleAddComment);
        }
    } catch (error) {
        console.error("Error:", error);
    }
}

// البدء
initializePage();
