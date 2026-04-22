<?php
/**
 * Course Resources API - Final Official Version
 * All TODOs implemented and sanitized.
 */

// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // تسمح بالوصول من أي مكان
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection file
require_once './config/Database.php';

// TODO: Get the PDO database connection
$database = new Database();
$db = $database->getConnection();

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// TODO: Parse query parameters from $_GET
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$resource_id = $_GET['resource_id'] ?? null;
$comment_id = $_GET['comment_id'] ?? null;

// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================

function getAllResources($db) {
    // TODO: Initialize the base SQL query
    $sql = "SELECT id, title, description, link, created_at FROM resources";
    $params = [];

    // TODO: Check if search parameter exists
    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $search = trim($_GET['search']);
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
        $params['search'] = '%' . $search . '%';
    }

    // TODO: Validate the sort parameter
    $allowedSort = ['title', 'created_at'];
    $sort = (isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort)) ? $_GET['sort'] : 'created_at';

    // TODO: Validate the order parameter
    $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

    // TODO: Add ORDER BY clause
    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);

    // TODO: Bind search value
    if (!empty($params)) {
        $stmt->bindValue(':search', $params['search']);
    }

    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success' => true, 'data' => $resources]);
}

function getResourceById($db, $resourceId) {
    // TODO: Validate resourceId
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
    // TODO: Validate required fields
    if (empty($data['title']) || empty($data['link'])) {
        sendResponse(['success' => false, 'message' => 'Title and link are required.'], 400);
    }
    $title = sanitizeInput($data['title']);
    $link = trim($data['link']);
    $description = isset($data['description']) ? sanitizeInput($data['description']) : '';

    // TODO: Validate URL
    if (!validateUrl($link)) {
        sendResponse(['success' => false, 'message' => 'Invalid link format.'], 400);
    }

    $stmt = $db->prepare("INSERT INTO resources (title, description, link) VALUES (?, ?, ?)");
    if ($stmt->execute([$title, $description, $link])) {
        sendResponse(['success' => true, 'message' => 'Resource created successfully.', 'id' => $db->lastInsertId()], 201);
    }
    sendResponse(['success' => false, 'message' => 'Failed to create resource.'], 500);
}

function updateResource($db, $data) {
    if (empty($data['id'])) {
        sendResponse(['success' => false, 'message' => 'Resource ID is required.'], 400);
    }

    // TODO: Check if exists
    $checkStmt = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $checkStmt->execute([$data['id']]);
    if (!$checkStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
    }

    $updateFields = [];
    $updateValues = [];
    if (isset($data['title'])) { $updateFields[] = "title = ?"; $updateValues[] = sanitizeInput($data['title']); }
    if (isset($data['description'])) { $updateFields[] = "description = ?"; $updateValues[] = sanitizeInput($data['description']); }
    if (isset($data['link'])) {
        if (!validateUrl($data['link'])) sendResponse(['success' => false, 'message' => 'Invalid link format.'], 400);
        $updateFields[] = "link = ?"; $updateValues[] = trim($data['link']);
    }

    if (empty($updateFields)) sendResponse(['success' => false, 'message' => 'No fields to update.'], 400);

    $sql = "UPDATE resources SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $updateValues[] = $data['id'];
    $stmt = $db->prepare($sql);
    if ($stmt->execute($updateValues)) sendResponse(['success' => true, 'message' => 'Resource updated successfully.']);
    sendResponse(['success' => false, 'message' => 'Update failed.'], 500);
}

function deleteResource($db, $resourceId) {
    if (!$resourceId || !is_numeric($resourceId)) sendResponse(['success' => false, 'message' => 'Invalid ID.'], 400);
    $stmt = $db->prepare("DELETE FROM resources WHERE id = ?");
    $stmt->execute([$resourceId]);
    if ($stmt->rowCount() > 0) sendResponse(['success' => true, 'message' => 'Resource deleted successfully.']);
    sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
}

// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

function getCommentsByResourceId($db, $resourceId) {
    if (!$resourceId || !is_numeric($resourceId)) sendResponse(['success' => false, 'message' => 'Invalid Resource ID.'], 400);
    $stmt = $db->prepare("SELECT id, resource_id, author, text, created_at FROM comments_resource WHERE resource_id = ? ORDER BY created_at ASC");
    $stmt->execute([$resourceId]);
    sendResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function createComment($db, $data) {
    if (empty($data['resource_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse(['success' => false, 'message' => 'Fields are required.'], 400);
    }
    
    // Check resource existence
    $check = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $check->execute([$data['resource_id']]);
    if (!$check->fetch()) sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);

    $stmt = $db->prepare("INSERT INTO comments_resource (resource_id, author, text) VALUES (?, ?, ?)");
    if ($stmt->execute([$data['resource_id'], sanitizeInput($data['author']), sanitizeInput($data['text'])])) {
        sendResponse(['success' => true, 'message' => 'Comment added.', 'id' => $db->lastInsertId()], 201);
    }
    sendResponse(['success' => false, 'message' => 'Failed to add comment.'], 500);
}

function deleteComment($db, $commentId) {
    if (!$commentId || !is_numeric($commentId)) sendResponse(['success' => false, 'message' => 'Invalid Comment ID.'], 400);
    $stmt = $db->prepare("DELETE FROM comments_resource WHERE id = ?");
    $stmt->execute([$commentId]);
    if ($stmt->rowCount() > 0) sendResponse(['success' => true, 'message' => 'Comment deleted.']);
    sendResponse(['success' => false, 'message' => 'Comment not found.'], 404);
}

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    if ($method === 'GET') {
        if ($action === 'comments') getCommentsByResourceId($db, $resource_id);
        elseif ($id) getResourceById($db, $id);
        else getAllResources($db);
    } elseif ($method === 'POST') {
        if ($action === 'comment') createComment($db, $data);
        else createResource($db, $data);
    } elseif ($method === 'PUT') {
        updateResource($db, $data);
    } elseif ($method === 'DELETE') {
        if ($action === 'delete_comment') deleteComment($db, $comment_id);
        else deleteResource($db, $id);
    } else {
        sendResponse(['success' => false, 'message' => 'Method Not Allowed'], 405);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    sendResponse(['success' => false, 'message' => 'Database error.'], 500);
} catch (Exception $e) {
    error_log($e->getMessage());
    sendResponse(['success' => false, 'message' => 'System error.'], 500);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function validateUrl($url) {
    return (bool)filter_var($url, FILTER_VALIDATE_URL);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateRequiredFields($data, $requiredFields) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) $missing[] = $field;
    }
    return ['valid' => (count($missing) === 0), 'missing' => $missing];
}
?>
