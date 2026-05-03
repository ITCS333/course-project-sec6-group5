<?php
// إعدادات لضمان عدم خروج أي أخطاء نصية تفسد الـ JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// --- 1. التودو: الاتصال بقاعدة البيانات ---
try {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db = new PDO("mysql:host=$host;charset=utf8", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // البحث عن قاعدة البيانات المناسبة (itcs333)
    $findDB = $db->query("SHOW DATABASES LIKE 'itcs333%'")->fetchColumn();
    if ($findDB) $db->exec("USE `$findDB` ");
    else $db->exec("USE `itcs333_course_project` ");

} catch (Exception $e) {
    die(json_encode(["success" => false, "message" => "Database connection error"]));
}

// تحليل الطلب
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// --- 2. التودو: توجيه العمليات بناءً على الطلب ---
if ($action === 'comments' || $action === 'comment') {
    handleComments($db, $method, $input);
} else {
    handleAssignments($db, $method, $id, $input);
}

// --- 3. فنكشن إدارة الواجبات (Assignments) ---
function handleAssignments($db, $method, $id, $input) {
    if ($method === 'GET') {
        if ($id) {
            // جلب واجب واحد حسب الـ ID
            $stmt = $db->prepare("SELECT * FROM assignments WHERE id = ?");
            $stmt->execute([$id]);
            $res = $stmt->fetch();
            if ($res) echo json_encode(["success" => true, "data" => $res]);
            else { http_response_code(404); echo json_encode(["success" => false]); }
        } else {
            // جلب الكل أو البحث (Search Filter)
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
        // إضافة واجب جديد (يرجع 201)
        if (empty($input['title'])) { http_response_code(400); echo json_encode(["success" => false]); return; }
        $stmt = $db->prepare("INSERT INTO assignments (title, description, due_date) VALUES (?, ?, ?)");
        $stmt->execute([$input['title'], $input['description'] ?? '', $input['due_date'] ?? '']);
        http_response_code(201);
        echo json_encode(["success" => true, "id" => (int)$db->lastInsertId()]);
    } 
    elseif ($method === 'PUT') {
        // تعديل واجب
        $stmt = $db->prepare("UPDATE assignments SET title=?, description=?, due_date=? WHERE id=?");
        $stmt->execute([$input['title'] ?? '', $input['description'] ?? '', $input['due_date'] ?? '', $id]);
        echo json_encode(["success" => true]);
    } 
    elseif ($method === 'DELETE') {
        // حذف واجب (يرجع 404 إذا الـ ID غلط)
        $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) echo json_encode(["success" => true]);
        else { http_response_code(404); echo json_encode(["success" => false]); }
    }
}

// --- 4. فنكشن إدارة التعليقات (Comments) ---
function handleComments($db, $method, $input) {
    if ($method === 'GET') {
        // جلب تعليقات لواجب معين
        $a_id = $_GET['assignment_id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM comments_assignment WHERE assignment_id = ?");
        $stmt->execute([$a_id]);
        echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
    } 
    elseif ($method === 'POST') {
        // إضافة تعليق جديد (يرجع 201)
        $stmt = $db->prepare("INSERT INTO comments_assignment (assignment_id, author, text) VALUES (?, ?, ?)");
        $stmt->execute([$input['assignment_id'] ?? 0, $input['author'] ?? '', $input['text'] ?? '']);
        http_response_code(201);
        echo json_encode(["success" => true, "id" => (int)$db->lastInsertId()]);
    }
}
