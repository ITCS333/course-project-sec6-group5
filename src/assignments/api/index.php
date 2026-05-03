<?php
// إيقاف أي رسائل خطأ قد تخرب الـ JSON
error_reporting(0);
ini_set('display_errors', 0);

// 1. استدعاء ملفك الأصلي
require_once 'db_connection.php'; 

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);

header('Content-Type: application/json');

try {
    // GET Assignments
    if ($method === 'GET' && empty($action)) {
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM assignments WHERE id = ?");
            $stmt->execute([$id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) echo json_encode(["success" => true, "data" => $res]);
            else { http_response_code(404); echo json_encode(["success" => false]); }
        } else {
            $stmt = $db->query("SELECT * FROM assignments");
            echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
    }
    // POST Assignment
    elseif ($method === 'POST' && empty($action)) {
        if (empty($input['title'])) { http_response_code(400); echo json_encode(["success" => false]); exit; }
        $stmt = $db->prepare("INSERT INTO assignments (title, description, due_date) VALUES (?, ?, ?)");
        $stmt->execute([$input['title'], $input['description'] ?? '', $input['due_date'] ?? '']);
        http_response_code(201);
        echo json_encode(["success" => true, "id" => (int)$db->lastInsertId()]);
    }
    // PUT Assignment
    elseif ($method === 'PUT') {
        $stmt = $db->prepare("UPDATE assignments SET title=?, description=?, due_date=? WHERE id=?");
        $stmt->execute([$input['title'], $input['description'], $input['due_date'], $id]);
        echo json_encode(["success" => true]);
    }
    // DELETE Assignment
    elseif ($method === 'DELETE' && empty($action)) {
        $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) echo json_encode(["success" => true]);
        else { http_response_code(404); echo json_encode(["success" => false]); }
    }
    // Comments Logic
    elseif ($action === 'comments' || $action === 'comment') {
        if ($method === 'GET') {
            $stmt = $db->prepare("SELECT * FROM comments_assignment WHERE assignment_id = ?");
            $stmt->execute([$_GET['assignment_id']]);
            echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } elseif ($method === 'POST') {
            $stmt = $db->prepare("INSERT INTO comments_assignment (assignment_id, author, text) VALUES (?, ?, ?)");
            $stmt->execute([$input['assignment_id'], $input['author'], $input['text']]);
            http_response_code(201);
            echo json_encode(["success" => true, "id" => (int)$db->lastInsertId()]);
        }
    }
} catch (Exception $e) {
    echo json_encode(["success" => false]);
}
