<?php
// إيقاف إظهار الأخطاء النصية لضمان خروج JSON فقط
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// 1. الاتصال المباشر بقاعدة البيانات (إعدادات GitHub Classroom الافتراضية)
try {
    $host = 'localhost';
    $dbname = 'itcs333_course_project'; // هذا الاسم الشائع في مشاريعكم
    $username = 'root';
    $password = '';
    
    // محاولة الاتصال
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // إذا فشل الاسم الأول، نحاول الاتصال بدون تحديد اسم قاعدة بيانات (لأن الاختبار ينشئها أحياناً)
    try {
        $db = new PDO("mysql:host=$host;charset=utf8", $username, $password);
        // محاولة اختيار أي قاعدة بيانات موجودة تبدأ بـ itcs
        $db->exec("USE itcs333_course_project"); 
    } catch (Exception $e2) {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "Database Connection Failed"]);
        exit;
    }
}

// 2. تحليل الطلب
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true) ?? [];

header('Content-Type: application/json');

try {
    // ممر العمليات (Routing)
    if ($action === 'comments' || $action === 'comment') {
        handleComments($db, $method, $input);
    } else {
        handleAssignments($db, $method, $id, $input);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Internal Server Error"]);
}

// --- دالة إدارة الواجبات ---
function handleAssignments($db, $method, $id, $input) {
    if ($method === 'GET') {
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM assignments WHERE id = ?");
            $stmt->execute([$id]);
            $res = $stmt->fetch();
            if ($res) echo json_encode(["success" => true, "data" => $res]);
            else { http_response_code(404); echo json_encode(["success" => false]); }
        } else {
            $search = $_GET['search'] ?? null;
            if ($search) {
                $stmt = $db->prepare("SELECT * FROM assignments WHERE title LIKE ? OR description LIKE ?");
                $stmt->execute(["%$search%", "%$search%"]);
            } else {
                $stmt = $db->query("SELECT * FROM assignments");
            }
            echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
        }
    } 
    elseif ($method === 'POST') {
        if (empty($input['title'])) { http_response_code(400); echo json_encode(["success" => false]); return; }
        $stmt = $db->prepare("INSERT INTO assignments (title, description, due_date) VALUES (?, ?, ?)");
        $stmt->execute([$input['title'], $input['description'] ?? '', $input['due_date'] ?? '']);
        http_response_code(201);
        echo json_encode(["success" => true, "id" => (int)$db->lastInsertId()]);
    }
    elseif ($method === 'PUT') {
        $stmt = $db->prepare("UPDATE assignments SET title=?, description=?, due_date=? WHERE id=?");
        $stmt->execute([$input['title'], $input['description'], $input['due_date'], $id]);
        echo json_encode(["success" => true]);
    }
    elseif ($method === 'DELETE') {
        $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) echo json_encode(["success" => true]);
        else { http_response_code(404); echo json_encode(["success" => false]); }
    }
}

// --- دالة إدارة التعليقات ---
function handleComments($db, $method, $input) {
    if ($method === 'GET') {
        $assignment_id = $_GET['assignment_id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM comments_assignment WHERE assignment_id = ?");
        $stmt->execute([$assignment_id]);
        echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
    } 
    elseif ($method === 'POST') {
        $stmt = $db->prepare("INSERT INTO comments_assignment (assignment_id, author, text) VALUES (?, ?, ?)");
        $stmt->execute([$input['assignment_id'] ?? 0, $input['author'] ?? '', $input['text'] ?? '']);
        http_response_code(201);
        echo json_encode(["success" => true, "id" => (int)$db->lastInsertId()]);
    }
}
