<?php
/*
  Requirement: Create a RESTful API for managing course weeks and comments.
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
    // استدعاء ملف الاتصال بقاعدة البيانات
    require_once __DIR__ . '/../../common/db.php';
    $db = getDBConnection();

    // قراءة نوع الطلب (GET, POST, etc.)
    $method = $_SERVER['REQUEST_METHOD'];

    // قراءة البيانات المرسلة في جسم الطلب (لـ POST و PUT)
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true) ?? [];

    // قراءة المعاملات من الرابط (Query Parameters)
    $action = $_GET['action'] ?? null;
    $id = $_GET['id'] ?? null;
    $weekId = $_GET['week_id'] ?? null;
    $commentId = $_GET['comment_id'] ?? null;

    // --- توجيه الطلبات بناءً على الطريقة (Method Routing) ---

    if ($method === 'GET') {
        if ($action === 'comments') {
            getCommentsByWeek($db, $weekId);
        } elseif ($id !== null) {
            getWeekById($db, $id);
        } else {
            getAllWeeks($db);
        }

    } elseif ($method === 'POST') {
        if ($action === 'comment') {
            createComment($db, $data);
        } else {
            createWeek($db, $data);
        }

    } elseif ($method === 'PUT') {
        updateWeek($db, $data);

    } elseif ($method === 'DELETE') {
        if ($action === 'delete_comment') {
            deleteComment($db, $commentId);
        } else {
            deleteWeek($db, $id);
        }

    } else {
        sendResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    sendResponse(['success' => false, 'message' => 'Database error occurred.'], 500);
} catch (Exception $e) {
    error_log($e->getMessage());
    sendResponse(['success' => false, 'message' => 'Server error occurred.'], 500);
}

// ============================================================================
// --- الدوال الأساسية (Functions) ---
// ============================================================================

function getAllWeeks(PDO $db): void {
    $query = "SELECT id, title, start_date, description, links, created_at FROM weeks";
    $params = [];

    if (isset($_GET['search']) && trim($_GET['search']) !== '') {
        $search = trim($_GET['search']);
        $query .= " WHERE title LIKE :search OR description LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    $allowedSort = ['title', 'start_date'];
    $sort = $_GET['sort'] ?? 'start_date';
    if (!in_array($sort, $allowedSort, true)) $sort = 'start_date';

    $allowedOrder = ['asc', 'desc'];
    $order = strtolower($_GET['order'] ?? 'asc');
    if (!in_array($order, $allowedOrder, true)) $order = 'asc';

    $query .= " ORDER BY {$sort} {$order}";

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($weeks as &$row) {
        $row['links'] = json_decode($row['links'], true) ?? [];
    }

    sendResponse(['success' => true, 'data' => $weeks]);
}

function getWeekById(PDO $db, $id): void {
    if ($id === null || !is_numeric($id)) {
        sendResponse(['success' => false, 'message' => 'Invalid week id.'], 400);
    }

    $stmt = $db->prepare("SELECT id, title, start_date, description, links, created_at FROM weeks WHERE id = ?");
    $stmt->execute([(int)$id]);
    $week = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($week) {
        $week['links'] = json_decode($week['links'], true) ?? [];
        sendResponse(['success' => true, 'data' => $week]);
    } else {
        sendResponse(['success' => false, 'message' => 'Week not found.'], 404);
    }
}

function createWeek(PDO $db, array $data): void {
    if (!isset($data['title'], $data['start_date']) || trim($data['title']) === '' || trim($data['start_date']) === '') {
        sendResponse(['success' => false, 'message' => 'Title and start_date are required.'], 400);
    }

    $title = sanitizeInput($data['title']);
    $start_date = trim($data['start_date']);
    $description = sanitizeInput($data['description'] ?? '');

    if (!validateDate($start_date)) {
        sendResponse(['success' => false, 'message' => 'Invalid start_date format.'], 400);
    }

    $links = (isset($data['links']) && is_array($data['links'])) ? array_values(array_filter(array_map('trim', $data['links']))) : [];
    $linksJson = json_encode($links);

    $stmt = $db->prepare("INSERT INTO weeks (title, start_date, description, links) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $start_date, $description, $linksJson]);

    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create week.'], 500);
    }
}

function updateWeek(PDO $db, array $data): void {
    if (!isset($data['id']) || !is_numeric($data['id'])) {
        sendResponse(['success' => false, 'message' => 'Week id is required.'], 400);
    }

    $id = (int)$data['id'];
    $fields = [];
    $values = [];

    if (isset($data['title'])) { $fields[] = "title = ?"; $values[] = sanitizeInput($data['title']); }
    if (isset($data['start_date'])) {
        if (!validateDate($data['start_date'])) sendResponse(['success' => false, 'message' => 'Invalid date.'], 400);
        $fields[] = "start_date = ?"; $values[] = trim($data['start_date']);
    }
    if (isset($data['description'])) { $fields[] = "description = ?"; $values[] = sanitizeInput($data['description']); }
    if (isset($data['links'])) { $fields[] = "links = ?"; $values[] = json_encode($data['links']); }

    if (empty($fields)) sendResponse(['success' => false, 'message' => 'No fields to update.'], 400);

    $values[] = $id;
    $stmt = $db->prepare("UPDATE weeks SET " . implode(', ', $fields) . " WHERE id = ?");
    if ($stmt->execute($values)) {
        sendResponse(['success' => true, 'message' => 'Updated successfully.']);
    } else {
        sendResponse(['success' => false, 'message' => 'Update failed.'], 500);
    }
}

function deleteWeek(PDO $db, $id): void {
    if (!is_numeric($id)) sendResponse(['success' => false, 'message' => 'Invalid id.'], 400);
    $stmt = $db->prepare("DELETE FROM weeks WHERE id = ?");
    $stmt->execute([(int)$id]);
    if ($stmt->rowCount() > 0) sendResponse(['success' => true]);
    else sendResponse(['success' => false, 'message' => 'Not found.'], 404);
}

function getCommentsByWeek(PDO $db, $weekId): void {
    $stmt = $db->prepare("SELECT * FROM comments_week WHERE week_id = ? ORDER BY created_at ASC");
    $stmt->execute([(int)$weekId]);
    sendResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function createComment(PDO $db, array $data): void {
    if (empty($data['week_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse(['success' => false, 'message' => 'Missing fields.'], 400);
    }
    $stmt = $db->prepare("INSERT INTO comments_week (week_id, author, text) VALUES (?, ?, ?)");
    $stmt->execute([(int)$data['week_id'], sanitizeInput($data['author']), sanitizeInput($data['text'])]);
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'id' => (int)$db->lastInsertId()], 201);
    } else {
        sendResponse(['success' => false], 500);
    }
}

function deleteComment(PDO $db, $commentId): void {
    $stmt = $db->prepare("DELETE FROM comments_week WHERE id = ?");
    $stmt->execute([(int)$commentId]);
    sendResponse(['success' => $stmt->rowCount() > 0]);
}

// --- Utility Functions ---

function sendResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function validateDate(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeInput(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
