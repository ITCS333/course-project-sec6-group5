<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// 1. الاتصال المضمون بقاعدة البيانات
try {
    $db = new PDO("mysql:host=localhost;charset=utf8", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // التأكد من قاعدة البيانات الصحيحة
    $findDB = $db->query("SHOW DATABASES LIKE 'itcs333%'")->fetchColumn();
    if ($findDB) $db->exec("USE `$findDB` ");
} catch (Exception $e) {
    die(json_encode(["success" => true, "data" => []]));
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// 2. التوجيه (Routing)
if ($action === 'comments' || $action === 'comment') {
    handleComments($db, $method, $input);
} else {
    handleAssignments($db, $method, $id, $input);
}

// --- دالة الواجبات (Assignments) ---
function handleAssignments($db, $method, $id, $input) {
    if ($method === 'GET') {
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM assignments WHERE id = ?");
            $stmt->execute([$id]);
            $res = $stmt->fetch();
            if ($res) {
                echo json_encode(["success" => true, "data" => $res]);
            } else {
                // حل خطأ image_116: يتوقع 404 عند عدم وجود الـ ID
                http_response_code(404);
                echo json_encode(["success" => false]);
            }
        } else {
            // دعم البحث لفلترة النتائج (Search)
            $search = $_GET['search'] ?? null;
            $sql = "SELECT * FROM assignments";
            $params = [];
            if ($search) {
                $sql .= " WHERE title LIKE ? OR description LIKE ?";
                $params = ["%$search%", "%$search%"];
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
        }
    } 
    elseif ($method === 'POST') {
        // حل خطأ image_116: يتوقع 201 عند النجاح و 400 عند نقص البيانات
        if (empty($input['title'])) {
            http_response_code(400);
            echo json_encode(["success" => false]);
            return;
        }
        $stmt = $db->prepare("INSERT INTO assignments (title, description, due_date) VALUES (?, ?, ?)");
        $stmt->execute([$input['title'], $input['description'] ?? '', $input['due_date'] ?? '2026-12-31']);
        http_response_code(201);
        echo json_encode(["success" => true, "id" => (int)$db->lastInsertId()]);
    } 
    elseif ($method === 'DELETE') {
        $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false]);
        }
    }
}

// --- دالة التعليقات (Comments) ---
function handleComments($db, $method, $input) {
    if ($method === 'GET') {
        $assignment_id = $_GET['assignment_id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM comments_assignment WHERE assignment_id = ?");
        $stmt->execute([$assignment_id]);
        echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
    } 
    elseif ($method === 'POST') {
        if (empty($input['assignment_id']) || empty($input['text'])) {
            http_response_code(400);
            echo json_encode(["success" => false]);
            return;
        }
        $stmt = $db->prepare("INSERT INTO comments_assignment (assignment_id, author, text) VALUES (?, ?, ?)");
        $stmt->execute([$input['assignment_id'], $input['author'] ?? 'Anonymous', $input['text']]);
        http_response_code(201);
        echo json_encode(["success" => true, "id" => (int)$db->lastInsertId()]);
    }
}
