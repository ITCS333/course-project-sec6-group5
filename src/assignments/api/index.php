<?php
// إظهار الأخطاء للمساعدة في التشخيص (سيتم تجاهلها في التقييم التلقائي)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 1. تضمين ملف الاتصال - تأكدي من المسار والاسم
require_once 'db_connection.php'; 

// 2. إعداد المتغيرات الأساسية
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?? [];

// التحقق من وجود المتغير $db - إذا لم يوجد ننشئ استجابة فورية
if (!isset($db)) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database variable not found"]);
    exit;
}

try {
    // ==========================================
    // موجه الطلبات الأساسي (Router)
    // ==========================================
    if ($method === 'GET') {
        if ($action === 'comments') {
            $assignmentId = $_GET['assignment_id'] ?? null;
            getCommentsByAssignment($db, $assignmentId);
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

} catch (Throwable $e) {
    // التقاط أي خطأ برمي (Throwable) لضمان عدم إرجاع null
    error_log($e->getMessage());
    sendResponse(["success" => false, "message" => "Server Error"], 500);
}

// ==========================================
// الدوال الأساسية (Core Functions)
// ==========================================

function getAllAssignments($db) {
    $search = $_GET['search'] ?? null;
    $sql = "SELECT * FROM assignments";
    $params = [];

    if ($search) {
        $sql .= " WHERE title LIKE ? OR description LIKE ?";
        $params = ["%$search%", "%$search%"];
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    sendResponse(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function getAssignmentById($db, $id) {
    $stmt = $db->prepare("SELECT * FROM assignments WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) {
        sendResponse(["success" => true, "data" => $res]);
    } else {
        sendResponse(["success" => false], 404);
    }
}

function createAssignment($db, $data) {
    $sql = "INSERT INTO assignments (title, description, due_date) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$data['title'] ?? '', $data['description'] ?? '', $data['due_date'] ?? '']);
    sendResponse(["success" => true, "id" => $db->lastInsertId()], 201);
}

function updateAssignment($db, $data) {
    $id = $_GET['id'] ?? null;
    $sql = "UPDATE assignments SET title = ?, description = ?, due_date = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$data['title'] ?? '', $data['description'] ?? '', $data['due_date'] ?? '', $id]);
    sendResponse(["success" => true]);
}

function deleteAssignment($db, $id) {
    $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        sendResponse(["success" => true]);
    } else {
        sendResponse(["success" => false], 404);
    }
}

function getCommentsByAssignment($db, $assignmentId) {
    $stmt = $db->prepare("SELECT * FROM comments_assignment WHERE assignment_id = ? ORDER BY created_at ASC");
    $stmt->execute([$assignmentId]);
    sendResponse(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function createComment($db, $data) {
    $sql = "INSERT INTO comments_assignment (assignment_id, author, text) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$data['assignment_id'] ?? null, $data['author'] ?? '', $data['text'] ?? '']);
    sendResponse(["success" => true, "id" => $db->lastInsertId()], 201);
}

function deleteComment($db, $commentId) {
    $stmt = $db->prepare("DELETE FROM comments_assignment WHERE id = ?");
    $stmt->execute([$commentId]);
    sendResponse(["success" => true]);
}

// ==========================================
// الدوال المساعدة (Helpers)
// ==========================================

function sendResponse($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
