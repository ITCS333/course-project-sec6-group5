 <?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once './config/Database.php';
$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$resource_id = $_GET['resource_id'] ?? null;
$comment_id = $_GET['comment_id'] ?? null;

// --- FUNCTIONS ---

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
    if (!empty($params)) { $stmt->bindValue(':search', $params['search']); }
    $stmt->execute();
    sendResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function getResourceById($db, $resourceId) {
    if (!$resourceId || !is_numeric($resourceId)) sendResponse(['success' => false, 'message' => 'Invalid ID.'], 400);
    $stmt = $db->prepare("SELECT id, title, description, link, created_at FROM resources WHERE id = ?");
    $stmt->execute([$resourceId]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) sendResponse(['success' => true, 'data' => $res]);
    else sendResponse(['success' => false, 'message' => 'Not found.'], 404);
}

function createResource($db, $data) {
    if (empty($data['title']) || empty($data['link'])) sendResponse(['success' => false, 'message' => 'Missing fields.'], 400);
    $stmt = $db->prepare("INSERT INTO resources (title, description, link) VALUES (?, ?, ?)");
    if ($stmt->execute([sanitizeInput($data['title']), sanitizeInput($data['description'] ?? ''), trim($data['link'])])) {
        sendResponse(['success' => true, 'message' => 'Created.', 'id' => $db->lastInsertId()], 201);
    }
}

function updateResource($db, $data) {
    if (empty($data['id'])) sendResponse(['success' => false, 'message' => 'ID required.'], 400);
    $stmt = $db->prepare("UPDATE resources SET title = ?, description = ?, link = ? WHERE id = ?");
    if ($stmt->execute([sanitizeInput($data['title']), sanitizeInput($data['description']), trim($data['link']), $data['id']])) {
        sendResponse(['success' => true, 'message' => 'Updated.']);
    }
}

function deleteResource($db, $id) {
    $stmt = $db->prepare("DELETE FROM resources WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) sendResponse(['success' => true, 'message' => 'Deleted.']);
    else sendResponse(['success' => false, 'message' => 'Not found.'], 404);
}

function getCommentsByResourceId($db, $resId) {
    $stmt = $db->prepare("SELECT * FROM comments_resource WHERE resource_id = ? ORDER BY created_at ASC");
    $stmt->execute([$resId]);
    sendResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function createComment($db, $data) {
    $stmt = $db->prepare("INSERT INTO comments_resource (resource_id, author, text) VALUES (?, ?, ?)");
    if ($stmt->execute([$data['resource_id'], sanitizeInput($data['author']), sanitizeInput($data['text'])])) {
        sendResponse(['success' => true, 'message' => 'Comment added.', 'id' => $db->lastInsertId()], 201);
    }
}

function deleteComment($db, $id) {
    $stmt = $db->prepare("DELETE FROM comments_resource WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) sendResponse(['success' => true, 'message' => 'Deleted.']);
    else sendResponse(['success' => false, 'message' => 'Not found.'], 404);
}

// --- ROUTER ---
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
    }
} catch (Exception $e) {
    sendResponse(['success' => false, 'message' => 'Error'], 500);
}

// --- HELPERS ---
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
function sanitizeInput($d) { return htmlspecialchars(strip_tags(trim($d)), ENT_QUOTES, 'UTF-8'); }
function validateUrl($u) { return (bool)filter_var($u, FILTER_VALIDATE_URL); }
?>
