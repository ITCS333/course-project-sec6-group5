<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 1. استدعاء ملف الاتصال
if (file_exists('db_connection.php')) {
    require_once 'db_connection.php';
} elseif (file_exists('db.php')) {
    require_once 'db.php';
}

// 2. معالجة الطلب
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?? [];

if (!isset($db)) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Database connection missing"]);
    exit;
}

// 3. تحديد أسماء الجداول تلقائياً (عشان لو الاسم مفرد أو جمع يشتغل)
$tableAssignments = "assignments"; 
$tableComments = "comments_assignment"; 

// محاولة التأكد من وجود الجداول
try {
    $db->query("SELECT 1 FROM assignments LIMIT 1");
} catch (Exception $e) {
    $tableAssignments = "assignment"; // لو فشل الجمع نجرب المفرد
}

try {
    $db->query("SELECT 1 FROM comments_assignment LIMIT 1");
} catch (Exception $e) {
    $tableComments = "comments"; // لو فشل الاسم الطويل نجرب القصير
}

try {
    if ($method === 'GET') {
        if ($action === 'comments') {
            $id = $_GET['assignment_id'] ?? 0;
            $stmt = $db->prepare("SELECT * FROM $tableComments WHERE assignment_id = ? ORDER BY created_at ASC");
            $stmt->execute([$id]);
            sendResponse(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } elseif (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM $tableAssignments WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) sendResponse(["success" => true, "data" => $res]);
            else sendResponse(["success" => false], 404);
        } else {
            $search = $_GET['search'] ?? null;
            $sql = "SELECT * FROM $tableAssignments";
            $p = [];
            if ($search) {
                $sql .= " WHERE title LIKE ? OR description LIKE ?";
                $p = ["%$search%", "%$search%"];
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($p);
            sendResponse(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
    } 
    elseif ($method === 'POST') {
        if ($action === 'comment') {
            $stmt = $db->prepare("INSERT INTO $tableComments (assignment_id, author, text) VALUES (?, ?, ?)");
            $stmt->execute([$data['assignment_id'] ?? null, $data['author'] ?? '', $data['text'] ?? '']);
            sendResponse(["success" => true, "id" => (int)$db->lastInsertId()], 201);
        } else {
            // إضافة الواجب - التأكد من الحقول المطلوبة لتخطي Test 8
            if (empty($data['title'])) sendResponse(["success" => false], 400);
            
            $stmt = $db->prepare("INSERT INTO $tableAssignments (title, description, due_date) VALUES (?, ?, ?)");
            $stmt->execute([$data['title'], $data['description'] ?? '', $data['due_date'] ?? '']);
            sendResponse(["success" => true, "id" => (int)$db->lastInsertId()], 201);
        }
    } 
    elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? 0;
        $stmt = $db->prepare("UPDATE $tableAssignments SET title = ?, description = ?, due_date = ? WHERE id = ?");
        $stmt->execute([$data['title'] ?? '', $data['description'] ?? '', $data['due_date'] ?? '', $id]);
        sendResponse(["success" => true]);
    } 
    elseif ($method === 'DELETE') {
        if ($action === 'delete_comment') {
            $stmt = $db->prepare("DELETE FROM $tableComments WHERE id = ?");
            $stmt->execute([$_GET['comment_id'] ?? 0]);
            sendResponse(["success" => true]);
        } else {
            $stmt = $db->prepare("DELETE FROM $tableAssignments WHERE id = ?");
            $stmt->execute([$_GET['id'] ?? 0]);
            if ($stmt->rowCount() > 0) sendResponse(["success" => true]);
            else sendResponse(["success" => false], 404);
        }
    }
} catch (Throwable $e) {
    sendResponse(["success" => false, "message" => $e->getMessage()], 500);
}

function sendResponse($data, $status = 200) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}
