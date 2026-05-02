<?php
// منع ظهور الأخطاء كـ HTML لضمان استلام JSON نظيف في الاختبارات
ini_set('display_errors', 0);
error_reporting(E_ALL);

// إعدادات الـ Headers لضمان توافق الـ API
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// معالجة طلب Preflight الخاص بـ CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// تصحيح المسار للوصول إلى ملف db.php داخل مجلد common
// نخرج مرتين (من api ثم من admin) للدخول إلى common
require_once __DIR__ . "/../../common/db.php"; 

try {
    $db = getDBConnection();
} catch (Exception $e) {
    sendResponse("Database connection failed: " . $e->getMessage(), 500);
}

// قراءة طريقة الطلب والبيانات القادمة
$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true) ?? [];

// استخراج المعاملات من الرابط (URL)
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($data['id']) ? (int)$data['id'] : null);
$action = $_GET['action'] ?? null;
$search = $_GET['search'] ?? null;
$sort = $_GET['sort'] ?? null;
$order = $_GET['order'] ?? 'asc';

// --- Functions ---

function getUsers($db) {
    global $search, $sort, $order;
    $query = "SELECT id, name, email, is_admin, created_at FROM users";
    $params = [];

    if (!empty($search)) {
        $query .= " WHERE name LIKE :search OR email LIKE :search";
        $params[':search'] = "%$search%";
    }

    $allowedSort = ["name", "email", "is_admin"];
    if (!empty($sort) && in_array($sort, $allowedSort)) {
        $orderDir = strtoupper($order) === "DESC" ? "DESC" : "ASC";
        $query .= " ORDER BY $sort $orderDir";
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function getUserById($db, $id) {
    $stmt = $db->prepare("SELECT id, name, email, is_admin, created_at FROM users WHERE id = :id");
    $stmt->execute([":id" => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) sendResponse("User not found", 404);
    sendResponse($user);
}

function createUser($db, $data) {
    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        sendResponse("Missing required fields", 400);
    }

    $email = trim($data['email']);
    if (!validateEmail($email)) sendResponse("Invalid email format", 400);
    if (strlen($data['password']) < 8) sendResponse("Password too short", 400);

    $check = $db->prepare("SELECT id FROM users WHERE email = :email");
    $check->execute([":email" => $email]);
    if ($check->fetch()) sendResponse("Email already exists", 409);

    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $is_admin = isset($data['is_admin']) ? (int)$data['is_admin'] : 0;

    $stmt = $db->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (:name, :email, :password, :is_admin)");
    $stmt->execute([
        ":name" => sanitizeInput($data['name']),
        ":email" => $email,
        ":password" => $hash,
        ":is_admin" => $is_admin
    ]);

    sendResponse(["id" => (int)$db->lastInsertId()], 201);
}

function updateUser($db, $data) {
    if (empty($data['id'])) sendResponse("ID required", 400);

    $stmt = $db->prepare("SELECT id FROM users WHERE id = :id");
    $stmt->execute([":id" => $data['id']]);
    if (!$stmt->fetch()) sendResponse("User not found", 404);

    $fields = [];
    $params = [":id" => $data['id']];

    if (!empty($data['name'])) {
        $fields[] = "name = :name";
        $params[':name'] = sanitizeInput($data['name']);
    }
    if (!empty($data['email'])) {
        $check = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $check->execute([":email" => $data['email'], ":id" => $data['id']]);
        if ($check->fetch()) sendResponse("Email already exists", 409);
        $fields[] = "email = :email";
        $params[':email'] = trim($data['email']);
    }
    if (isset($data['is_admin'])) {
        $fields[] = "is_admin = :is_admin";
        $params[':is_admin'] = (int)$data['is_admin'];
    }

    if (!$fields) sendResponse("No changes updated", 200);

    $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = :id";
    $db->prepare($sql)->execute($params);
    sendResponse("User updated successfully");
}

function deleteUser($db, $id) {
    if (!$id) sendResponse("ID required", 400);

    $stmt = $db->prepare("SELECT id FROM users WHERE id = :id");
    $stmt->execute([":id" => $id]);
    if (!$stmt->fetch()) sendResponse("User not found", 404);

    $db->prepare("DELETE FROM users WHERE id = :id")->execute([":id" => $id]);
    sendResponse("User deleted successfully");
}

function changePassword($db, $data) {
    if (empty($data['id']) || empty($data['current_password']) || empty($data['new_password'])) {
        sendResponse("Missing required fields", 400);
    }
    if (strlen($data['new_password']) < 8) sendResponse("New password too short", 400);

    $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([":id" => $data['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) sendResponse("User not found", 404);
    if (!password_verify($data['current_password'], $user['password'])) {
        sendResponse("Current password incorrect", 401);
    }

    $hash = password_hash($data['new_password'], PASSWORD_DEFAULT);
    $db->prepare("UPDATE users SET password = :p WHERE id = :id")->execute([":p" => $hash, ":id" => $data['id']]);
    sendResponse("Password updated successfully");
}

// --- Router ---

try {
   if ($method === 'GET') {
        if ($id) {
            getUserById($db, $id);
        } else {
            getUsers($db);
        }
    } elseif ($method === 'POST') {
        if ($action === 'change_password') {
            changePassword($db, $data);
        } else {
            createUser($db, $data);
        }
    } elseif ($method === 'PUT') {
        updateUser($db, $data);
    } elseif ($method === 'DELETE') {
        deleteUser($db, $id);
    } else {
        sendResponse("Method not allowed", 405);
    }
} catch (Exception $e) {
    sendResponse("Server error: " . $e->getMessage(), 500);
}

// --- Helpers ---

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    if ($statusCode < 400) {
        echo json_encode(["success" => true, "data" => $data]);
    } else {
        echo json_encode(["success" => false, "message" => $data]);
    }
    exit;
}

function validateEmail($email) {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
?> 
