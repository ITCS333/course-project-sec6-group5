<?php
header('Content-Type: application/json');
error_reporting(0);

// اتصال مباشر ومبسط
$db = new mysqli("localhost", "root", "", "course");
if ($db->connect_error) {
    // محاولة الاتصال بدون تحديد قاعدة بيانات إذا فشل الأول
    $db = new mysqli("localhost", "root", "");
    $find = $db->query("SHOW DATABASES LIKE 'itcs333%'")->fetch_row();
    $db->select_db($find[0]);
}

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? '';

if ($action == 'comments') {
    if ($method == 'GET') {
        $aid = $_GET['assignment_id'];
        $res = $db->query("SELECT * FROM comments_assignment WHERE assignment_id = $aid");
        echo json_encode(["success" => true, "data" => $res->fetch_all(MYSQLI_ASSOC)]);
    }
} else {
    if ($method == 'GET') {
        if ($id) {
            $res = $db->query("SELECT * FROM assignments WHERE id = $id");
            $data = $res->fetch_assoc();
            if ($data) echo json_encode(["success" => true, "data" => $data]);
            else { http_response_code(404); echo json_encode(["success" => false]); }
        } else {
            $search = $_GET['search'] ?? '';
            $sql = "SELECT * FROM assignments" . ($search ? " WHERE title LIKE '%$search%'" : "");
            $res = $db->query($sql);
            echo json_encode(["success" => true, "data" => $res->fetch_all(MYSQLI_ASSOC)]);
        }
    } elseif ($method == 'POST') {
        $in = json_decode(file_get_contents('php://input'), true);
        if (!$in['title']) { http_response_code(400); die(json_encode(["success" => false])); }
        $db->query("INSERT INTO assignments (title, description, due_date) VALUES ('{$in['title']}', '{$in['description']}', '{$in['due_date']}')");
        http_response_code(201);
        echo json_encode(["success" => true, "id" => $db->insert_id]);
    }
}
