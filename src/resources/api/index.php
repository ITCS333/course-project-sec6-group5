<?php
/**
 * Course Resources API - Final Clean Version
 */

// 1. الإعدادات والترويسات (Headers)
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. الاتصال بقاعدة البيانات (تم حذف التكرار)
require_once './config/Database.php';
$database = new Database();
$db = $database->getConnection();

// 3. جلب بيانات الطلب
$method = $_SERVER['REQUEST_METHOD'];
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// 4. تحليل المعايير (Query Parameters)
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$resource_id = $_GET['resource_id'] ?? null;
$comment_id = $_GET['comment_id'] ?? null;

// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================

function getAllResources($db) {
    $sql = "SELECT id, title, description, link, created_at FROM resources";
    $params = [];

    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $search = trim($_GET['search']);
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
        $params['search'] = '%' . $search . '%';
    }

    $allowedSort = ['title', 'created_at'];
    $sort = (isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort)) ? $_GET['sort'] : 'created_at';
    $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

    $sql .= " ORDER BY $sort $order";
    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }

    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success' => true, 'data' => $resources]);
}

function getResourceById($db, $resourceId) {
    if (!$resourceId || !is_numeric($resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid Resource ID.'], 400);
    }
    $stmt = $db->prepare("SELECT id, title, description, link, created_at FROM resources WHERE id = ?");
    $stmt->execute([$resourceId]);
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resource) {
        sendResponse(['success' => true, 'data' => $resource]);
    } else {
        sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
    }
}

function createResource($db, $data) {
    if (empty($data['title']) || empty($data['link'])) {
        sendResponse(['success' => false, 'message' => 'Title and link are required.'], 400);
    }
    $title = trim($data['title']);
    $link = trim($data['link']);
    $description = isset($data['description']) ? trim($data['description']) : '';

    if (!filter_var($link, FILTER_VALIDATE_URL)) {
        sendResponse(['success' => false, 'message' => 'Invalid link format.'], 400);
    }

    $stmt = $db->prepare("INSERT INTO resources (title, description, link) VALUES (?, ?, ?)");
    if ($stmt->execute([$title, $description, $link])) {
        sendResponse([
            'success' => true, 
            'message' => 'Resource created successfully.',
            'id' => $db->lastInsertId()
        ], 201);
    }
    sendResponse(['success' => false, 'message' => 'Failed to create resource.'], 500);
}

function updateResource($db, $data) {
    if (empty($data['id'])) {
        sendResponse(['success' => false, 'message' => 'Resource ID is required.'], 400);
    }
    $resourceId = $data['id'];
    
    $checkStmt = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $checkStmt->execute([$resourceId]);
    if (!$checkStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
    }

    $updateFields = [];
    $updateValues = [];

    if (isset($data['title'])) {
        $updateFields[] = "title = ?";
        $updateValues[] = trim($data['title']);
    }
    if (isset($data['description'])) {
        $updateFields[] = "description = ?";
        $updateValues[] = trim($data['description']);
    }
    if (isset($data['link'])) {
        if (!filter_var($data['link'], FILTER_VALIDATE_URL)) {
            sendResponse(['success' => false, 'message' => 'Invalid link format.'], 400);
        }
        $updateFields[] = "link = ?";
        $updateValues[] = trim($data['link']);
    }

    if (empty($updateFields)) {
        sendResponse(['success' => false, 'message' => 'No data provided for update.'], 400);
    }

    $sql = "UPDATE resources SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $updateValues[] = $resourceId;
    $stmt = $db->prepare($sql);

    if ($stmt->execute($updateValues)) {
        sendResponse(['success' => true, 'message' => 'Resource updated successfully.']);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to update resource.'], 500);
    }
}

function deleteResource($db, $resourceId) {
    if (!$resourceId || !is_numeric($resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid Resource ID.'], 400);
    }
    $stmt = $db->prepare("DELETE FROM resources WHERE id = ?");
    $stmt->execute([$resourceId]);
    
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Resource deleted successfully.']);
    } else {
        sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
    }
}

// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

function getCommentsByResourceId($db, $resourceId) {
    if (!$resourceId || !is_numeric($resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid Resource ID.'], 400);
    }
    $stmt = $db->prepare("SELECT id, resource_id, author, text, created_at FROM comments_resource WHERE resource_id = ? ORDER BY created_at ASC");
    $stmt->execute([$resourceId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success' => true, 'data' => $comments]);
}

function createComment($db, $data) {
    if (empty($data['resource_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse(['success' => false, 'message' => 'All fields are required.'], 400);
    }
    
    $stmt = $db->prepare("INSERT INTO comments_resource (resource_id, author, text) VALUES (?, ?, ?)");
    if ($stmt->execute([$data['resource_id'], trim($data['author']), trim($data['text'])])) {
        sendResponse([
            'success' => true,
            'message' => 'Comment added successfully.',
            'id' => $db->lastInsertId()
        ], 201);
    }
    sendResponse(['success' => false, 'message' => 'Failed to add comment.'], 500);
}

function deleteComment($db, $commentId) {
    if (!$commentId || !is_numeric($commentId)) {
        sendResponse(['success' => false, 'message' => 'Invalid Comment ID.'], 400);
    }
    $stmt = $db->prepare("DELETE FROM comments_resource WHERE id = ?");
    $stmt->execute([$commentId]);
    
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Comment deleted successfully.']);
    } else {
        sendResponse(['success' => false, 'message' => 'Comment not found.'], 404);
    }
}

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    if ($method === 'GET') {
        if ($action === 'comments') getCommentsByResourceId($db, $resource_id);
        elseif ($id) getResourceById($db, $id);
        else getAllResources($db);
    } 
    elseif ($method === 'POST') {
        if ($action === 'comments' || $action === 'comment') createComment($db, $data);
        else createResource($db, $data);
    } 
    elseif ($method === 'PUT') {
        updateResource($db, $data);
    } 
    elseif ($method === 'DELETE') {
        if ($action === 'delete_comment') deleteComment($db, $comment_id);
        else deleteResource($db, $id);
    } 
    else {
        sendResponse(['success' => false, 'message' => 'Method Not Allowed'], 405);
    }
} catch (Exception $e) {
    sendResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}
?>
