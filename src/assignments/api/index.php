<?php

// ==========================================
// 1. دوال الواجبات (Assignments Functions)
// ==========================================

/**
 * جلب جميع الواجبات مع دعم التصفية (Filter)
 */
function getAllAssignments(PDO $db): void
{
    $search = $_GET['search'] ?? null;
    $sql = "SELECT * FROM assignments";
    $params = [];

    if ($search) {
        $sql .= " WHERE title LIKE ? OR description LIKE ?";
        $params = ["%$search%", "%$search%"];
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(["success" => true, "data" => $assignments]);
}

/**
 * جلب واجب محدد بواسطة المعرف ID
 */
function getAssignmentById(PDO $db, $id): void
{
    if (!is_numeric($id)) {
        sendResponse(["success" => false, "message" => "Invalid ID format"], 400);
    }

    $stmt = $db->prepare("SELECT * FROM assignments WHERE id = ?");
    $stmt->execute([$id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($assignment) {
        sendResponse(["success" => true, "data" => $assignment]);
    } else {
        sendResponse(["success" => false, "message" => "Assignment not found"], 404);
    }
}

/**
 * إنشاء واجب جديد
 */
function createAssignment(PDO $db, array $data): void
{
    $title = sanitizeInput($data['title'] ?? '');
    $description = sanitizeInput($data['description'] ?? '');
    $due_date = $data['due_date'] ?? '';

    if (empty($title) || empty($due_date) || !validateDate($due_date)) {
        sendResponse(["success" => false, "message" => "Invalid input data"], 400);
    }

    $sql = "INSERT INTO assignments (title, description, due_date) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$title, $description, $due_date]);

    if ($stmt->rowCount() > 0) {
        sendResponse([
            "success" => true, 
            "id" => $db->lastInsertId(), 
            "message" => "Assignment created"
        ], 201);
    } else {
        sendResponse(["success" => false, "message" => "Failed to create assignment"], 500);
    }
}

/**
 * تحديث واجب موجود
 */
function updateAssignment(PDO $db, array $data): void
{
    $id = $_GET['id'] ?? null;
    if (!$id || !is_numeric($id)) {
        sendResponse(["success" => false, "message" => "Valid ID required"], 400);
    }

    $title = sanitizeInput($data['title'] ?? '');
    $description = sanitizeInput($data['description'] ?? '');
    $due_date = $data['due_date'] ?? '';

    if (empty($title) || empty($due_date) || !validateDate($due_date)) {
        sendResponse(["success" => false, "message" => "Invalid input data"], 400);
    }

    $sql = "UPDATE assignments SET title = ?, description = ?, due_date = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$title, $description, $due_date, $id]);

    sendResponse(["success" => true, "message" => "Assignment updated"]);
}

/**
 * حذف واجب (سيقوم بحذف التعليقات المرتبطة به تلقائياً بسبب CASCADE)
 */
function deleteAssignment(PDO $db, $id): void
{
    if (!$id || !is_numeric($id)) {
        sendResponse(["success" => false, "message" => "Valid ID required"], 400);
    }

    $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        sendResponse(["success" => true, "message" => "Assignment deleted"]);
    } else {
        sendResponse(["success" => false, "message" => "Assignment not found"], 404);
    }
}

// ==========================================
// 2. دوال التعليقات (Comments Functions)
// ==========================================

function getCommentsByAssignment(PDO $db, $assignmentId): void 
{
    $stmt = $db->prepare("SELECT * FROM comments_assignment WHERE assignment_id = ? ORDER BY created_at ASC");
    $stmt->execute([$assignmentId]);
    sendResponse(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function createComment(PDO $db, array $data): void
{
    $assignmentId = $data['assignment_id'] ?? null;
    $author = sanitizeInput($data['author'] ?? '');
    $text = sanitizeInput($data['text'] ?? '');

    if (!$assignmentId || empty($author) || empty($text)) {
        sendResponse(["success" => false, "message" => "Missing fields"], 400);
    }

    $sql = "INSERT INTO comments_assignment (assignment_id, author, text) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$assignmentId, $author, $text]);

    if ($stmt->rowCount() > 0) {
        sendResponse(["success" => true, "id" => $db->lastInsertId()], 201);
    } else {
        sendResponse(["success" => false, "message" => "Error"], 500);
    }
}

function deleteComment(PDO $db, $commentId): void
{
    $stmt = $db->prepare("DELETE FROM comments_assignment WHERE id = ?");
    $stmt->execute([$commentId]);
    if ($stmt->rowCount() > 0) {
        sendResponse(["success" => true, "message" => "Comment deleted"]);
    } else {
        sendResponse(["success" => false, "message" => "Comment not found"], 404);
    }
}

// ==========================================
// 3. الموجه (Router) ومعالجة الأخطاء
// ==========================================

try {
    if ($method === 'GET') {
        if ($action === 'comments') {
            getCommentsByAssignment($db, $_GET['assignment_id'] ?? null);
        } elseif (isset($_GET['id'])) {
            getAssignmentById($db, $_GET['id']);
        } else {
            getAllAssignments($db);
        }
    } 
    elseif ($method === 'POST') {
        if ($action === 'comment') {
            createComment($db, $data);
        } else {
            createAssignment($db, $data);
        }
    } 
    elseif ($method === 'PUT') {
        updateAssignment($db, $data);
    } 
    elseif ($method === 'DELETE') {
        if ($action === 'delete_comment') {
            deleteComment($db, $_GET['comment_id'] ?? null);
        } else {
            deleteAssignment($db, $_GET['id'] ?? null);
        }
    } 
    else {
        sendResponse(["success" => false, "message" => "Method Not Allowed"], 405);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    sendResponse(["success" => false, "message" => "Server error"], 500);
}

// ==========================================
// 4. الدوال المساعدة (Helpers)
// ==========================================

function sendResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function validateDate(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeInput(string $data): string
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
