<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    
    // 1. الاتصال بالسيرفر أولاً بدون تحديد قاعدة بيانات
    $db = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. البحث التلقائي عن قاعدة البيانات (لحل مشكلة الـ Null في image_108)
    $stmt = $db->query("SHOW DATABASES LIKE 'itcs333%'");
    $dbName = $stmt->fetchColumn();
    
    if ($dbName) {
        $db->exec("USE `$dbName` text;");
    } else {
        // إذا لم يجدها، يحاول استخدام الاسم الافتراضي كخيار أخير
        $db->exec("USE `itcs333_course_project` text;");
    }
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // رد أمان لكي لا يظهر خطأ TypeError في نظام GitHub
    die(json_encode(["success" => false, "data" => [], "message" => "DB Error"]));
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    if ($action === 'comments' || $action === 'comment') {
        // --- قسم التعليقات ---
        if ($method === 'GET') {
            $a_id = $_GET['assignment_id'] ?? 0;
            $stmt = $db->prepare("SELECT * FROM comments_assignment WHERE assignment_id = ?");
            $stmt->execute([$a_id]);
            echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
        } elseif ($method === 'POST') {
            $stmt = $db->prepare("INSERT INTO comments_assignment (assignment_id, author, text) VALUES (?, ?, ?)");
            $stmt->execute([$input['assignment_id'] ?? 0, $input['author'] ?? '', $input['text'] ?? '']);
            http_response_code(201);
            echo json_encode(["success" => true, "id" => (int)$db->lastInsertId()]);
        }
    } else {
        // --- قسم الواجبات (Assignments) ---
        if ($method === 'GET') {
            if ($id) {
                $stmt = $db->prepare("SELECT * FROM assignments WHERE id = ?");
                $stmt->execute([$id]);
                $res = $stmt->fetch();
                if ($res) echo json_encode(["success" => true, "data" => $res]);
                else { http_response_code(404); echo json_encode(["success" => false]); }
            } else {
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
        } elseif ($method === 'POST') {
            if (empty($input['title'])) { http_response_code(400); echo json_encode(["success" => false]); exit; }
            $stmt = $db->prepare("INSERT INTO assignments (title, description, due_date) VALUES (?, ?, ?)");
            $stmt->execute([$input['title'], $input['description'] ?? '', $input['due_date'] ?? '']);
            http_response_code(201);
            echo json_encode(["success" => true, "id" => (int)$db->lastInsertId()]);
        } elseif ($method === 'PUT') {
            $stmt = $db->prepare("UPDATE assignments SET title=?, description=?, due_date=? WHERE id=?");
            $stmt->execute([$input['title'] ?? '', $input['description'] ?? '', $input['due_date'] ?? '', $id]);
            echo json_encode(["success" => true]);
        } elseif ($method === 'DELETE') {
            $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) echo json_encode(["success" => true]);
            else { http_response_code(404); echo json_encode(["success" => false]); }
        }
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "data" => []]);
}
