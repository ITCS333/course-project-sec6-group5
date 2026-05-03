<?php
// 1. منع ظهور أي مخرجات غير الـ JSON
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 2. استدعاء ملف الاتصال - جربي الأسماء الأكثر احتمالاً
if (file_exists('db_connection.php')) {
    require_once 'db_connection.php';
} elseif (file_exists('db.php')) {
    require_once 'db.php';
}

// 3. تعريف المتغيرات الأساسية
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?? [];

// 4. التأكد من وجود $db
if (!isset($db) || !($db instanceof PDO)) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "PDO Connection Missing"]);
    exit;
}

try {
    if ($method === 'GET') {
        if ($action === 'comments') {
            getCommentsByAssignment($db, $_GET['assignment_id'] ?? 0);
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
            deleteComment($db, $_GET['comment_id'] ?? 0);
        } else {
            deleteAssignment($db, $_GET['id'] ?? 0);
        }
    } 
    else {
        sendResponse(["success" => false], 405);
    }
} catch (Throwable $e) {
    sendResponse(["success" => false, "error" => $e->getMessage()], 500);
}

// ==========================================
// الدوال المصممة لتخطي اختبارات image_104.jpg
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
    // إرجاع المصفوفة دائماً حتى لو فارغة (لحل Test 1 & 2)
    sendResponse(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function getAssignmentById($db, $id) {
    $stmt = $db->prepare("SELECT * FROM assignments WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($res) {
        sendResponse(["success" => true, "data" => $res]);
    } else {
        // حل خطأ Test 4 (توقع 404 بدل 200)
        sendResponse(["success" => false], 404);
    }
}

function createAssignment($db, $data) {
    if (empty($data['title']) || empty($data['due_date'])) {
        sendResponse(["success" => false], 400);
    }
    
    $stmt = $db->prepare("INSERT INTO assignments (title, description, due_date) VALUES (?, ?, ?)");
    $stmt->execute([$data['title'], $data['description'] ?? '', $data['due_date']]);
    
    // حل خطأ Test 6 & 7 (توقع 201 والمعرف الجديد)
    sendResponse([
        "success" => true, 
        "id" => (int)$db->lastInsertId(),
        "message" => "Created Successfully"
    ], 201);
}

function updateAssignment($db, $data) {
    $id = $_GET['id'] ?? 0;
    $stmt = $db->prepare("UPDATE assignments SET title = ?, description = ?, due_date = ? WHERE id = ?");
    $stmt->execute([$data['title'], $data['description'], $data['due_date'], $id]);
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
    $stmt->execute([$data['assignment_id'], $data['author'], $data['text']]);
    sendResponse(["success" => true, "id" => (int)$db->lastInsertId()], 201);
}

function deleteComment($db, $commentId) {
    $stmt = $db->prepare("DELETE FROM comments_assignment WHERE id = ?");
    $stmt->execute([$commentId]);
    sendResponse(["success" => true]);
}

function sendResponse($data, $status = 200) {
    // مسح أي مخرجات نصية سبقت الـ JSON
    ob_clean();
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}
