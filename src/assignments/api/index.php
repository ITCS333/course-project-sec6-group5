<?php

// ============================================================================
// HEADERS
// ============================================================================
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================================================
// DB CONNECTION
// ============================================================================
require_once __DIR__ . '/../../common/db.php';
$db = getDBConnection();

// ============================================================================
// REQUEST DATA
// ============================================================================
$method = $_SERVER['REQUEST_METHOD'];

$rawData = file_get_contents('php://input');
$data    = json_decode($rawData, true) ?? [];

$action       = $_GET['action'] ?? null;
$id           = $_GET['id'] ?? null;
$assignmentId = $_GET['assignment_id'] ?? null;
$commentId    = $_GET['comment_id'] ?? null;

// ============================================================================
// FUNCTIONS
// ============================================================================

function getAllAssignments(PDO $db): void
{
    $query = "SELECT * FROM assignments";
    $params = [];

    if (!empty($_GET['search'])) {
        $query .= " WHERE title LIKE :search OR description LIKE :search";
        $params[':search'] = "%" . $_GET['search'] . "%";
    }

    $allowedSort = ['title', 'due_date', 'created_at'];
    $sort = $_GET['sort'] ?? 'due_date';
    if (!in_array($sort, $allowedSort)) {
        $sort = 'due_date';
    }

    $order = strtolower($_GET['order'] ?? 'asc');
    $order = ($order === 'desc') ? 'desc' : 'asc';

    $query .= " ORDER BY $sort $order";

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['files'] = json_decode($row['files'], true) ?? [];
    }

    sendResponse(['success' => true, 'data' => $rows]);
}

function getAssignmentById(PDO $db, $id): void
{
    if (!is_numeric($id)) {
        sendResponse(['success' => false, 'message' => 'Invalid ID'], 400);
    }

    $stmt = $db->prepare("SELECT * FROM assignments WHERE id = ?");
    $stmt->execute([$id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        sendResponse(['success' => false, 'message' => 'Not found'], 404);
    }

    $row['files'] = json_decode($row['files'], true) ?? [];

    sendResponse(['success' => true, 'data' => $row]);
}

function createAssignment(PDO $db, array $data): void
{
    if (empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        sendResponse(['success' => false, 'message' => 'Missing fields'], 400);
    }

    if (!validateDate($data['due_date'])) {
        sendResponse(['success' => false, 'message' => 'Invalid date'], 400);
    }

    $files = json_encode($data['files'] ?? []);

    $stmt = $db->prepare("INSERT INTO assignments (title, description, due_date, files) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        sanitizeInput($data['title']),
        sanitizeInput($data['description']),
        $data['due_date'],
        $files
    ]);

    sendResponse([
        'success' => true,
        'id' => $db->lastInsertId()
    ], 201);
}

function updateAssignment(PDO $db, array $data): void
{
    if (empty($data['id'])) {
        sendResponse(['success' => false, 'message' => 'ID required'], 400);
    }

    $stmt = $db->prepare("SELECT id FROM assignments WHERE id = ?");
    $stmt->execute([$data['id']]);

    if (!$stmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Not found'], 404);
    }

    $fields = [];
    $values = [];

    if (isset($data['title'])) {
        $fields[] = "title = ?";
        $values[] = sanitizeInput($data['title']);
    }

    if (isset($data['description'])) {
        $fields[] = "description = ?";
        $values[] = sanitizeInput($data['description']);
    }

    if (isset($data['due_date'])) {
        if (!validateDate($data['due_date'])) {
            sendResponse(['success' => false, 'message' => 'Invalid date'], 400);
        }
        $fields[] = "due_date = ?";
        $values[] = $data['due_date'];
    }

    if (isset($data['files'])) {
        $fields[] = "files = ?";
        $values[] = json_encode($data['files']);
    }

    if (empty($fields)) {
        sendResponse(['success' => false, 'message' => 'No data to update'], 400);
    }

    $values[] = $data['id'];

    $sql = "UPDATE assignments SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    sendResponse(['success' => true]);
}

function deleteAssignment(PDO $db, $id): void
{
    if (!is_numeric($id)) {
        sendResponse(['success' => false, 'message' => 'Invalid ID'], 400);
    }

    $stmt = $db->prepare("DELETE FROM assignments WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        sendResponse(['success' => false, 'message' => 'Not found'], 404);
    }

    sendResponse(['success' => true]);
}

function getCommentsByAssignment(PDO $db, $assignmentId): void
{
    if (!is_numeric($assignmentId)) {
        sendResponse(['success' => false], 400);
    }

    $stmt = $db->prepare("SELECT * FROM comments_assignment WHERE assignment_id = ? ORDER BY created_at ASC");
    $stmt->execute([$assignmentId]);

    sendResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function createComment(PDO $db, array $data): void
{
    if (empty($data['assignment_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse(['success' => false], 400);
    }

    $stmt = $db->prepare("SELECT id FROM assignments WHERE id = ?");
    $stmt->execute([$data['assignment_id']]);

    if (!$stmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Assignment not found'], 404);
    }

    $stmt = $db->prepare("INSERT INTO comments_assignment (assignment_id, author, text) VALUES (?, ?, ?)");
    $stmt->execute([
        $data['assignment_id'],
        sanitizeInput($data['author']),
        sanitizeInput($data['text'])
    ]);

    sendResponse([
        'success' => true,
        'id' => $db->lastInsertId()
    ], 201);
}

function deleteComment(PDO $db, $commentId): void
{
    if (!is_numeric($commentId)) {
        sendResponse(['success' => false], 400);
    }

    $stmt = $db->prepare("DELETE FROM comments_assignment WHERE id = ?");
    $stmt->execute([$commentId]);

    if ($stmt->rowCount() === 0) {
        sendResponse(['success' => false], 404);
    }

    sendResponse(['success' => true]);
}

// ============================================================================
// ROUTER
// ============================================================================
try {

    if ($method === 'GET') {

        if ($action === 'comments') {
            getCommentsByAssignment($db, $assignmentId);
        } elseif ($id) {
            getAssignmentById($db, $id);
        } else {
            getAllAssignments($db);
        }

    } elseif ($method === 'POST') {

        if ($action === 'comment') {
            createComment($db, $data);
        } else {
            createAssignment($db, $data);
        }

    } elseif ($method === 'PUT') {

        updateAssignment($db, $data);

    } elseif ($method === 'DELETE') {

        if ($action === 'delete_comment') {
            deleteComment($db, $commentId);
        } else {
            deleteAssignment($db, $id);
        }

    } else {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    sendResponse(['success' => false, 'message' => 'Server error'], 500);
}

// ============================================================================
// HELPERS
// ============================================================================
function sendResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function validateDate(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeInput(string $data): string
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
