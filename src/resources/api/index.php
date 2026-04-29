<?php
/*
  Requirement: API for Managing Resources and Comments 
  Status: Final Version - URL Validation & Created_at Field Included
*/

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once __DIR__ . '/../../common/db.php';
    $db = getDBConnection();

    $method = $_SERVER['REQUEST_METHOD'];
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true) ?? [];

    $action = $_GET['action'] ?? null;
    $id = $_GET['id'] ?? null;
    $resourceId = $_GET['resource_id'] ?? null; 
    $commentId = $_GET['comment_id'] ?? null;

    if ($method === 'GET') {
        if ($action === 'comments') {
            getCommentsByResource($db, $resourceId);
        } elseif ($id !== null) {
            getResourceById($db, $id);
        } else {
            getAllResources($db);
        }
    } elseif ($method === 'POST') {
        if ($action === 'comment') {
            createComment($db, $data);
        } else {
            createResource($db, $data);
        }
    } elseif ($method === 'PUT') {
        updateResource($db, $data);
    } elseif ($method === 'DELETE') {
        if ($action === 'delete_comment') {
            deleteComment($db, $commentId);
        } else {
            deleteResource($db, $id);
        }
    } else {
        sendResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    sendResponse(['success' => false, 'message' => 'Database error.'], 500);
} catch (Exception $e) {
    error_log($e->getMessage());
    sendResponse(['success' => false, 'message' => 'Server error.'], 500);
}

// --- Functions ---

function getAllResources(PDO $db): void {
    // تم إضافة created_at هنا
    $query = "SELECT id, title, description, link, created_at FROM resources";
    $params = [];

    if (isset($_GET['search']) && trim($_GET['search']) !== '') {
        $search = trim($_GET['search']);
        $query .= " WHERE title LIKE :search OR description LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success' => true, 'data' => $results]);
}

function getResourceById(PDO $db, $id): void {
    // تم إضافة created_at هنا
    $stmt = $db->prepare("SELECT id, title, description, link, created_at FROM resources WHERE id = ?");
    $stmt->execute([(int)$id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($res) {
        sendResponse(['success' => true, 'data' => $res]);
    } else {
        sendResponse(['success' => false, 'message' => 'Not found.'], 404);
    }
}

function createResource(PDO $db, array $data): void {
    if (empty($data['title']) || empty($data['link'])) {
        sendResponse(['success' => false, 'message' => 'Missing fields.'], 400);
    }

    // التحقق من صحة الرابط (URL Validation)
    if (!filter_var($data['link'], FILTER_VALIDATE_URL)) {
        sendResponse(['success' => false, 'message' => 'Invalid URL format.'], 400);
    }

    $stmt = $db->prepare("INSERT INTO resources (title, description, link) VALUES (?, ?, ?)");
    $stmt->execute([
        sanitizeInput($data['title']),
        sanitizeInput($data['description'] ?? ''),
        trim($data['link'])
    ]);

    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
    } else {
        sendResponse(['success' => false], 500);
    }
}

function updateResource(PDO $db, array $data): void {
    if (empty($data['id'])) sendResponse(['success' => false], 400);
    
    // التحقق من صحة الرابط إذا تم إرساله للتحديث
    if (isset($data['link']) && !filter_var($data['link'], FILTER_VALIDATE_URL)) {
        sendResponse(['success' => false, 'message' => 'Invalid URL format.'], 400);
    }

    $check = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $check->execute([$data['id']]);
    if (!$check->fetch()) sendResponse(['success' => false], 404);

    $fields = []; $values = [];
    if (isset($data['title'])) { $fields[] = "title = ?"; $values[] = sanitizeInput($data['title']); }
    if (isset($data['description'])) { $fields[] = "description = ?"; $values[] = sanitizeInput($data['description']); }
    if (isset($data['link'])) { $fields[] = "link = ?"; $values[] = trim($data['link']); }

    $values[] = $data['id'];
    $stmt = $db->prepare("UPDATE resources SET " . implode(', ', $fields) . " WHERE id = ?");
    sendResponse(['success' => $stmt->execute($values)]);
}

function deleteResource(PDO $db, $id): void {
    if (!$id) sendResponse(['success' => false], 400);
    
    $stmt = $db->prepare("DELETE FROM resources WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) sendResponse(['success' => true]);
    else sendResponse(['success' => false], 404);
}

function getCommentsByResource(PDO $db, $resourceId): void {
    if (!$resourceId) sendResponse(['success' => false], 400);
    $stmt = $db->prepare("SELECT id, resource_id, author, text, created_at FROM comments_resource WHERE resource_id = ?");
    $stmt->execute([$resourceId]);
    sendResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function createComment(PDO $db, array $data): void {
    if (empty($data['resource_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse(['success' => false], 400);
    }
    
    $check = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $check->execute([$data['resource_id']]);
    if (!$check->fetch()) sendResponse(['success' => false], 404);

    $stmt = $db->prepare("INSERT INTO comments_resource (resource_id, author, text) VALUES (?, ?, ?)");
    $stmt->execute([(int)$data['resource_id'], sanitizeInput($data['author']), sanitizeInput($data['text'])]);
    
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
    } else {
        sendResponse(['success' => false], 500);
    }
}

function deleteComment(PDO $db, $id): void {
    if (!$id) sendResponse(['success' => false], 400);
    $stmt = $db->prepare("DELETE FROM comments_resource WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) sendResponse(['success' => true]);
    else sendResponse(['success' => false], 404);
}

function sendResponse(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function sanitizeInput(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
} 
