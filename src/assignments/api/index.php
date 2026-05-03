<?php
// 1. إعدادات عرض الأخطاء للتشخيص
ini_set('display_errors', 0); 
error_reporting(E_ALL);

// 2. محاولة استدعاء ملف الاتصال بأكثر من اسم شائع (لحل مشكلة الاسم)
if (file_exists('db_connection.php')) {
    require_once 'db_connection.php';
} elseif (file_exists('db.php')) {
    require_once 'db.php';
} elseif (file_exists('config.php')) {
    require_once 'config.php';
}

// 3. إعداد متغيرات الطلب
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?? [];

// 4. التأكد من وجود المتغير $db وتصحيحه إذا كان مختلفاً
if (!isset($db)) {
    // محاولة البحث عن أي متغير PDO معرف إذا لم يكن اسمه $db
    foreach (get_defined_vars() as $var) {
        if ($var instanceof PDO) {
            $db = $var;
            break;
        }
    }
}

// إذا استمر عدم وجود الاتصال، نرسل JSON يوضح المشكلة بدلاً من null
if (!isset($db)) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Database connection variable not found. Check your file name."]);
    exit;
}

try {
    // ==========================================
    // موجه الطلبات (The Router)
    // ==========================================
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

} catch (Throwable $e) {
    sendResponse(["success" => false, "message" => "Error: " . $e->getMessage()], 500);
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
    $stmt = $db->prepare("INSERT INTO assignments (title, description, due_date) VALUES (?, ?, ?)");
    $stmt->execute([$data['title'] ?? '', $data['description'] ?? '', $data['due_date'] ?? '']);
    sendResponse(["success" => true, "id" => $db->lastInsertId()], 201);
}

function updateAssignment($db, $data) {
    $id = $_GET['id'] ?? null;
    $stmt = $db->prepare("UPDATE assignments SET title = ?, description = ?, due_date = ? WHERE id = ?");
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
    $stmt = $db->prepare("INSERT INTO comments_assignment (assignment_id, author, text) VALUES (?, ?, ?)");
    $stmt->execute([$data['assignment_id'] ?? null, $data['author'] ?? '', $data['text'] ?? '']);
    sendResponse(["success" => true, "id" => $db->lastInsertId()], 201);
}

function deleteComment($db, $commentId) {
    $stmt = $db->prepare("DELETE FROM comments_assignment WHERE id = ?");
    $stmt->execute([$commentId]);
    sendResponse(["success" => true]);
}

function sendResponse($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}
