 <?php

// ==========================================
// الدوال الخاصة بالتعليقات (Comments Functions)
// ==========================================

/**
 * جلب جميع التعليقات الخاصة بواجب معين
 * [image_94.jpg]
 */
function getCommentsByAssignment(PDO $db, $assignmentId): void 
{
    $sql = "SELECT id, assignment_id, author, text, created_at 
            FROM comments_assignment 
            WHERE assignment_id = ? 
            ORDER BY created_at ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute([$assignmentId]);

    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(["success" => true, "data" => $comments]);
}

/**
 * إنشاء تعليق جديد لواجـب محدد
 * [image_94.jpg & image_95.jpg]
 */
function createComment(PDO $db, array $data): void
{
    // التحقق من وجود الحقول المطلوبة
    if (!isset($data['assignment_id']) || !isset($data['author']) || !isset($data['text'])) {
        sendResponse(["success" => false, "message" => "Missing required fields"], 400);
    }

    $assignmentId = $data['assignment_id'];
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);

    // التحقق من صحة البيانات
    if (empty($author) || empty($text) || !is_numeric($assignmentId)) {
        sendResponse(["success" => false, "message" => "Invalid or empty fields"], 400);
    }

    // التحقق من وجود الواجب المرتبط في قاعدة البيانات [image_95.jpg]
    $check = $db->prepare("SELECT id FROM assignments WHERE id = ?");
    $check->execute([$assignmentId]);
    if (!$check->fetch()) {
        sendResponse(["success" => false, "message" => "Assignment not found"], 404);
    }

    // إدراج التعليق الجديد
    $sql = "INSERT INTO comments_assignment (assignment_id, author, text) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$assignmentId, $author, $text]);

    // التأكد من نجاح الإضافة وإرجاع الكائن الجديد
    if ($stmt->rowCount() > 0) {
        $newId = $db->lastInsertId();
        
        $fetchStmt = $db->prepare("SELECT * FROM comments_assignment WHERE id = ?");
        $fetchStmt->execute([$newId]);
        $newComment = $fetchStmt->fetch(PDO::FETCH_ASSOC);

        sendResponse([
            "success" => true, 
            "id" => $newId, 
            "data" => $newComment
        ], 201);
    } else {
        sendResponse(["success" => false, "message" => "Failed to create comment"], 500);
    }
}

/**
 * حذف تعليق واحد بناءً على معرفه
 * [image_95.jpg & image_96.jpg]
 */
function deleteComment(PDO $db, $commentId): void
{
    // التحقق من المعرف
    if (!$commentId || !is_numeric($commentId)) {
        sendResponse(["success" => false, "message" => "A valid numeric ID is required"], 400);
    }

    // التحقق من وجود التعليق قبل الحذف
    $checkStmt = $db->prepare("SELECT id FROM comments_assignment WHERE id = ?");
    $checkStmt->execute([$commentId]);
    if (!$checkStmt->fetch()) {
        sendResponse(["success" => false, "message" => "Comment not found"], 404);
    }

    // تنفيذ عملية الحذف
    $stmt = $db->prepare("DELETE FROM comments_assignment WHERE id = ?");
    $stmt->execute([$commentId]);

    if ($stmt->rowCount() > 0) {
        sendResponse(["success" => true, "message" => "Deleted successfully"], 200);
    } else {
        sendResponse(["success" => false, "message" => "Delete failed"], 500);
    }
}

// ==========================================
// موجه الطلبات الأساسي (MAIN REQUEST ROUTER)
// [image_96.jpg & image_97.jpg]
// ==========================================

try {
    if ($method === 'GET') {
        // طلب التعليقات لواجب معين
        if ($action === 'comments') {
            $assignmentId = $_GET['assignment_id'] ?? null;
            getCommentsByAssignment($db, $assignmentId);
        } 
        // طلب واجب واحد بواسطة ID
        elseif (isset($_GET['id'])) {
            getAssignmentById($db, $_GET['id']);
        } 
        // طلب كافة الواجبات
        else {
            getAllAssignments($db);
        }
    } 
    elseif ($method === 'POST') {
        // إضافة تعليق
        if ($action === 'comment') {
            createComment($db, $data);
        } 
        // إضافة واجب جديد
        else {
            createAssignment($db, $data);
        }
    } 
    elseif ($method === 'PUT') {
        // تحديث واجب
        updateAssignment($db, $data);
    }
    elseif ($method === 'DELETE') {
        // حذف تعليق محدد
        if ($action === 'delete_comment') {
            $commentId = $_GET['comment_id'] ?? null;
            deleteComment($db, $commentId);
        } 
        // حذف واجب بالكامل
        else {
            $id = $_GET['id'] ?? null;
            deleteAssignment($db, $id);
        }
    } 
    else {
        // طريقة طلب غير مدعومة
        sendResponse(["success" => false, "message" => "Method Not Allowed"], 405);
    }

} catch (PDOException $e) {
    // تسجيل الخطأ في السيرفر وإرسال رد عام [image_97.jpg]
    error_log("Database Error: " . $e->getMessage());
    sendResponse(["success" => false, "message" => "A database error occurred"], 500);

} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    sendResponse(["success" => false, "message" => "Internal server error"], 500);
}

// ==========================================
// الدوال المساعدة (HELPER FUNCTIONS)
// [image_98.jpg & image_99.jpg]
// ==========================================

/**
 * إرسال استجابة بصيغة JSON وإنهاء التنفيذ
 */
function sendResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * التحقق من صحة تنسيق التاريخ YYYY-MM-DD
 */
function validateDate(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * تنظيف المدخلات النصية من أي أكواد ضارة
 */
function sanitizeInput(string $data): string
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
