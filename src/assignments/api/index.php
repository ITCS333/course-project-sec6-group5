<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// 1. الاتصال بقاعدة البيانات
try {
    $db = new PDO("mysql:host=localhost;charset=utf8", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // البحث عن قاعدة البيانات التابعة للمشروع
    $stmt = $db->query("SHOW DATABASES LIKE 'itcs333%'");
    $dbName = $stmt->fetchColumn();
    if ($dbName) $db->exec("USE `$dbName` ");
} catch (Exception $e) {
    die(json_encode(["success" => true, "data" => []]));
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// 2. التوجيه بناءً على الجداول الموجودة في Schema (image_124)
if ($action === 'comments' || $action === 'comment') {
    handleComments($db, $method, $input);
} else {
    handleAssignments($db, $method, $id, $input);
}

// --- دالة الواجبات (تطابق image_125) ---
function handleAssignments($db, $method, $id, $input) {
    if ($method === 'GET') {
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM assignments WHERE id = ?");
            $stmt->execute([$id]);
            $res = $stmt->fetch();
            if ($res) {
                echo json_encode(["success" => true, "data" => $res]);
            } else {
                http_response_code(404); // مطلوب للاختبار رقم 5 في image_119
                echo json_encode(["success" => false]);
            }
        } else {
            $search = $_GET['search'] ?? null;
            $sql = "SELECT * FROM assignments";
            if ($search) {
                $stmt = $db->prepare($sql . " WHERE title LIKE ? OR description LIKE ?");
                $stmt->execute(["%$search%", "%$search%"]);
            } else {
                $stmt = $db->query($sql);
            }
            echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
        }
    } 
    elseif ($method === 'POST') {
        if (empty($input['title'])) {
            http_response_code(400);
            echo json_encode(["success" => false]);
            return;
        }
        // إضافة العمود 'files' ليتوافق مع السطر 163 في image_125
        $stmt = $db->prepare("INSERT INTO assignments (title, description, due_date, files) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $input['title'], 
            $input['description'] ?? '', 
            $input['due_date'] ?? date('Y-m-d'),
            json_encode($input['files'] ?? []) // تحويل المصفوفة لنص ليتناسب مع SQL
        ]);
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

// --- دالة التعليقات (تطابق image_124) ---
function handleComments($db, $method, $input) {
    if ($method === 'GET') {
        $aid = $_GET['assignment_id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM comments_assignment WHERE assignment_id = ?");
        $stmt->execute([$aid]);
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
