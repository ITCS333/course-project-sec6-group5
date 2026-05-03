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

    $findDB = $db->query("SHOW DATABASES LIKE 'itcs333%'")->fetchColumn();
    if ($findDB) $db->exec("USE `$findDB` ");
    else $db->exec("USE `itcs333_course_project` ");
} catch (Exception $e) {
    die(json_encode(["success" => false, "data" => []]));
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// 2. التوجيه بناءً على الجداول الظاهرة في image_111 و image_110
if ($action === 'comments' || $action === 'replies') {
    handleReplies($db, $method, $input);
} elseif ($action === 'topics') {
    handleTopics($db, $method, $input);
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
            if ($res) echo json_encode(["success" => true, "data" => $res]);
            else { http_response_code(404); echo json_encode(["success" => false]); }
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
    } elseif ($method === 'POST') {
        if (empty($input['title'])) { http_response_code(400); echo json_encode(["success" => false]); return; }
        $stmt = $db->prepare("INSERT INTO assignments (title, description, due_date) VALUES (?, ?, ?)");
        $stmt->execute([$input['title'], $input['description'] ?? '', $input['due_date'] ?? '']);
        http_response_code(201);
        echo json_encode(["success" => true, "id" => (int)$db->lastInsertId()]);
    } elseif ($method === 'DELETE') {
        $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) echo json_encode(["success" => true]);
        else { http_response_code(404); echo json_encode(["success" => false]); }
    }
}

// --- دالة الردود (Replies) - بناءً على جدول replies في image_111 ---
function handleReplies($db, $method, $input) {
    if ($method === 'GET') {
        $topic_id = $_GET['topic_id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM replies WHERE topic_id = ?");
        $stmt->execute([$topic_id]);
        echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
    } elseif ($method === 'POST') {
        if (empty($input['topic_id']) || empty($input['text'])) { http_response_code(400); echo json_encode(["success" => false]); return; }
        $stmt = $db->prepare("INSERT INTO replies (topic_id, text, author) VALUES (?, ?, ?)");
        $stmt->execute([$input['topic_id'], $input['text'], $input['author'] ?? 'Anonymous']);
        http_response_code(201);
        echo json_encode(["success" => true, "id" => (int)$db->lastInsertId()]);
    }
}

// --- دالة المواضيع (Topics) - بناءً على جدول topics في image_111 ---
function handleTopics($db, $method, $input) {
    if ($method === 'GET') {
        $stmt = $db->query("SELECT * FROM topics");
        echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
    } elseif ($method === 'POST') {
        $stmt = $db->prepare("INSERT INTO topics (subject, message, author) VALUES (?, ?, ?)");
        $stmt->execute([$input['subject'], $input['message'], $input['author']]);
        http_response_code(201);
        echo json_encode(["success" => true, "id" => (int)$db->lastInsertId()]);
    }
}
