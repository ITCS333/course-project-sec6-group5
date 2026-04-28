<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
/**
 * User Management API
 *
 * A RESTful API that handles all CRUD operations for user management
 * and password changes for the Admin Portal.
 * Uses PDO to interact with a MySQL database.
 *
 * Database Table (ground truth: see schema.sql):
 * Table: users
 * Columns:
 *   - id         (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
 *   - name       (VARCHAR(100), NOT NULL)
 *   - email      (VARCHAR(100), NOT NULL, UNIQUE)
 *   - password   (VARCHAR(255), NOT NULL) - bcrypt hash
 *   - is_admin   (TINYINT(1), NOT NULL, DEFAULT 0)
 *   - created_at (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP)
 *
 * HTTP Methods Supported:
 *   - GET    : Retrieve all users (with optional search/sort query params)
 *   - GET    : Retrieve a single user by id (?id=1)
 *   - POST   : Create a new user
 *   - POST   : Change a user's password (?action=change_password)
 *   - PUT    : Update an existing user's name, email, or is_admin
 *   - DELETE : Delete a user by id (?id=1)
 *
 * Response Format: JSON
 * All responses have the shape:
 *   { "success": true,  "data": ... }
 *   { "success": false, "message": "..." }
 */


// TODO: Set headers for JSON response and CORS.
// Set Content-Type to application/json.
// Allow cross-origin requests (CORS) if needed.
// Allow specific HTTP methods: GET, POST, PUT, DELETE, OPTIONS.
// Allow specific headers: Content-Type, Authorization.

// Headers + CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Handle preflight OPTIONS request.
// If the request method is OPTIONS, return HTTP 200 and exit.


// TODO: Include the database connection file.
// Assume a function getDBConnection() is available that returns a PDO instance
// configured for the 'course' database (see schema.sql).


// TODO: Get the PDO database connection by calling getDBConnection().

require_once _DIR_ . "/../../config/db.php";
$db = getDBConnection();


// TODO: Read the HTTP request method from $_SERVER['REQUEST_METHOD'].


// TODO: Read the raw request body for POST and PUT requests.
// Use file_get_contents('php://input') and decode with json_decode($raw, true).


// TODO: Read query string parameters.
// Relevant parameters:
//   - id            (int)    : identifies a specific user by primary key
//   - action        (string) : 'change_password' to route password-change requests
//   - search        (string) : free-text filter for GET requests
//   - sort          (string) : field name to sort by
//   - order         (string) : 'asc' or 'desc'


/**
 * Function: Get all users, or search/filter users.
 * Method: GET (no ?id parameter)
 *
 * Supported query parameters:
 *   - search (string) : filters rows where name LIKE or email LIKE the term
 *   - sort   (string) : column to sort by; allowed values: name, email, is_admin
 *   - order  (string) : sort direction; allowed values: asc, desc (default: asc)
 *
 * Notes:
 *   - Never return the password column in the response.
 *   - Validate the 'sort' value against the whitelist (name, email, is_admin)
 *     to prevent SQL injection before interpolating it into the ORDER BY clause.
 *   - Validate the 'order' value; only accept 'asc' or 'desc'.
 */
$method = $_SERVER['REQUEST_METHOD'];
$raw = file_get_contents("php://input");
$data = json_decode($raw, true) ?? [];

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;
$search = $_GET['search'] ?? null;
$sort = $_GET['sort'] ?? null;
$order = $_GET['order'] ?? 'asc';;


function getUsers($dbو $search, $sort, $order) {
    // TODO: Build a SELECT query for id, name, email, is_admin, created_at.
    //       Do NOT select the password column.

    // TODO: If the 'search' query parameter is present, append a WHERE clause:
    //       WHERE name LIKE :search OR email LIKE :search
    //       Wrap the search term with '%' wildcards when binding.

    // TODO: If the 'sort' query parameter is present and is one of the allowed
    //       fields (name, email, is_admin), append an ORDER BY clause.
    //       If 'order' is 'desc', use DESC; otherwise default to ASC.

    // TODO: Prepare the statement, bind any parameters, and execute.

    // TODO: Fetch all rows as an associative array.

    // TODO: Call sendResponse() with the array and HTTP status 200.
$query = "SELECT id, name, email, is_admin, created_at FROM users";
    $params = [];

    if (!empty($search)) {
        $query .= " WHERE name LIKE :search OR email LIKE :search";
        $params[':search'] = "%$search%";
    }

    $allowedSort = ["name", "email", "is_admin"];
    if (!empty($sort) && in_array($sort, $allowedSort)) {
        $orderDir = strtolower($order) === "desc" ? "DESC" : "ASC";
        $query .= " ORDER BY $sort $orderDir";
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
}
/**
 * Function: Get a single user by primary key.
 * Method: GET with ?id=<int>
 *
 * Query parameters:
 *   - id (int, required) : the user's primary key in the users table
 */
function getUserById($db, $id) {
    // TODO: Prepare SELECT query: SELECT id, name, email, is_admin, created_at
    //       FROM users WHERE id = :id
    //       Do NOT select the password column.

    // TODO: Bind :id and execute.

    // TODO: Fetch one row.

    // TODO: If no row is found, call sendResponse() with an error message and HTTP 404.
    //       If found, call sendResponse() with the row and HTTP 200.
$stmt = $db->prepare("SELECT id, name, email, is_admin, created_at FROM users WHERE id = :id");
    $stmt->execute([":id" => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendResponse("User not found", 404);
    }
    sendResponse($user);
}

  

/**
 * Function: Create a new user.
 * Method: POST (no ?action parameter)
 *
 * Expected JSON body:
 *   - name     (string, required)
 *   - email    (string, required) - must be a valid email address and unique
 *   - password (string, required) - plaintext; will be hashed before storage
 *   - is_admin (int, optional)    - 0 (student) or 1 (admin); defaults to 0
 */
function createUser($db, $data) {
    // TODO: Check that name, email, and password are all present and non-empty.
    //       If any are missing, call sendResponse() with HTTP 400.

    // TODO: Trim whitespace from name, email, and password.
    //       Validate email format with filter_var(FILTER_VALIDATE_EMAIL).
    //       If invalid, call sendResponse() with HTTP 400.

    // TODO: Validate that password is at least 8 characters.
    //       If not, call sendResponse() with HTTP 400.

    // TODO: Check whether the email already exists in the users table.
    //       If it does, call sendResponse() with an appropriate message and HTTP 409.

    // TODO: Hash the password using password_hash($password, PASSWORD_DEFAULT).

    // TODO: Read is_admin from $data; default to 0 if not provided.
    //       Accept only the values 0 or 1.

    // TODO: Prepare and execute an INSERT INTO users (name, email, password, is_admin)
    //       VALUES (:name, :email, :password, :is_admin).

    // TODO: If the insert succeeds, call sendResponse() with the new user's id and HTTP 201.
    //       If it fails, call sendResponse() with HTTP 500.
if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        sendResponse("Missing fields", 400);
    }

    $name = sanitizeInput($data['name']);
    $email = trim($data['email']);
    $password = trim($data['password']);

    if (!validateEmail($email)) {
        sendResponse("Invalid email format", 400);
    }

    if (strlen($password) < 8) {
        sendResponse("Password must be at least 8 characters", 400);
    }

    $check = $db->prepare("SELECT id FROM users WHERE email = :email");
    $check->execute([":email" => $email]);
    if ($check->fetch()) {
        sendResponse("Email already exists", 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $is_admin = isset($data['is_admin']) ? (int)$data['is_admin'] : 0;

    $stmt = $db->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (:name, :email, :password, :is_admin)");
    $stmt->execute([
        ":name" => $name,
        ":email" => $email,
        ":password" => $hash,
        ":is_admin" => $is_admin
    ]);

    sendResponse(["id" => (int)$db->lastInsertId()], 201);
}

/**
 * Function: Update an existing user.
 * Method: PUT
 *
 * Expected JSON body:
 *   - id       (int, required)    : primary key of the user to update
 *   - name     (string, optional) : new name
 *   - email    (string, optional) : new email (must remain unique)
 *   - is_admin (int, optional)    : 0 or 1
 *
 * Note: password changes are handled by the separate changePassword endpoint.
 */
function updateUser($db, $data) {
    // TODO: Check that id is present in $data.
    //       If not, call sendResponse() with HTTP 400.

    // TODO: Look up the user by id. If not found, call sendResponse() with HTTP 404.

    // TODO: Dynamically build the SET clause for only the fields provided
    //       (name, email, is_admin). Skip any field not present in $data.

    // TODO: If email is being updated, check it is not already used by another user
    //       (exclude the current user's id from the duplicate check).
    //       If a duplicate is found, call sendResponse() with HTTP 409.

    // TODO: Prepare the UPDATE statement, bind parameters, and execute.

    // TODO: If successful, call sendResponse() with a success message and HTTP 200.
    //       If no rows were affected, still return HTTP 200 (no change is not an error).
    //       If the query fails, call sendResponse() with HTTP 500.

    if (empty($data['id'])) {
        sendResponse("ID required", 400);
    }

    $stmt = $db->prepare("SELECT id FROM users WHERE id = :id");
    $stmt->execute([":id" => $data['id']]);

    if (!$stmt->fetch()) {
        sendResponse("User not found", 404);
    }

    $fields = [];
    $params = [":id" => $data['id']];

    if (!empty($data['name'])) {
        $fields[] = "name = :name";
        $params[':name'] = sanitizeInput($data['name']);
    }

    if (!empty($data['email'])) {

        if (!validateEmail($data['email'])) {
            sendResponse("Invalid email", 400);
        }

        $check = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $check->execute([
            ":email" => $data['email'],
            ":id" => $data['id']
        ]);

        if ($check->fetch()) {
            sendResponse("Email already exists", 409);
        }

        $fields[] = "email = :email";
        $params[':email'] = $data['email'];
    }

    if (isset($data['is_admin'])) {
        $fields[] = "is_admin = :is_admin";
        $params[':is_admin'] = (int)$data['is_admin'];
    }

    if (!$fields) {
        sendResponse("No changes made", 200);
    }

    $query = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute($params);

    sendResponse("Updated successfully");
}



/**
 * Function: Delete a user by primary key.
 * Method: DELETE
 *
 * Query parameter:
 *   - id (int, required) : primary key of the user to delete
 */
function deleteUser($db, $id) {
    // TODO: Check that $id is present and non-zero.
    //       If not, call sendResponse() with HTTP 400.

    // TODO: Check that a user with this id exists.
    //       If not, call sendResponse() with HTTP 404.

    // TODO: Prepare and execute: DELETE FROM users WHERE id = :id

    // TODO: If successful, call sendResponse() with a success message and HTTP 200.
    //       If the query fails, call sendResponse() with HTTP 500.

    if (!$id) {
        sendResponse("ID required", 400);
    }

    $stmt = $db->prepare("SELECT id FROM users WHERE id = :id");
    $stmt->execute([":id" => $id]);

    if (!$stmt->fetch()) {
        sendResponse("User not found", 404);
    }

    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([":id" => $id]);

    sendResponse("Deleted successfully");
}



/**
 * Function: Change a user's password.
 * Method: POST with ?action=change_password
 *
 * Expected JSON body:
 *   - id               (int, required)    : primary key of the user whose password is changing
 *   - current_password (string, required) : must match the stored bcrypt hash
 *   - new_password     (string, required) : plaintext; will be hashed before storage
 */
function changePassword($db, $data) {
    // TODO: Check that id, current_password, and new_password are all present.
    //       If any are missing, call sendResponse() with HTTP 400.

    // TODO: Validate that new_password is at least 8 characters.
    //       If not, call sendResponse() with HTTP 400.

    // TODO: SELECT password FROM users WHERE id = :id to retrieve the current hash.
    //       If no user is found, call sendResponse() with HTTP 404.

    // TODO: Call password_verify($current_password, $hash).
    //       If verification fails, call sendResponse() with HTTP 401 (Unauthorized).

    // TODO: Hash the new password: password_hash($new_password, PASSWORD_DEFAULT).

    // TODO: Prepare and execute: UPDATE users SET password = :password WHERE id = :id

    // TODO: If successful, call sendResponse() with a success message and HTTP 200.
    //       If the query fails, call sendResponse() with HTTP 500.

    if (empty($data['id']) || empty($data['current_password']) || empty($data['new_password'])) {
        sendResponse("Missing fields", 400);
    }

    if (strlen($data['new_password']) < 8) {
        sendResponse("Password too short", 400);
    }

    $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([":id" => $data['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendResponse("User not found", 404);
    }

    if (!password_verify($data['current_password'], $user['password'])) {
        sendResponse("Wrong password", 401);
    }

    $hash = password_hash($data['new_password'], PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
    $stmt->execute([
        ":password" => $hash,
        ":id" => $data['id']
    ]);

    sendResponse("Password updated");
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
  if ($method === 'GET') {
        $id ? getUserById($db, $id) : getUsers($db, $search, $sort, $order);
    } elseif ($method === 'POST') {
        ($action === 'change_password') ? changePassword($db, $data) : createUser($db, $data);
    } elseif ($method === 'PUT') {
        updateUser($db, $data);
    } elseif ($method === 'DELETE') {
        deleteUser($db, $id);
    } else {
        sendResponse("Method not allowed", 405);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    sendResponse("Database error occurred", 500);
} catch (Exception $e) {
    error_log($e->getMessage());
    sendResponse("Server error", 500);
}
// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Sends a JSON response and terminates execution.
 *
 * @param mixed $data       Data to include in the response.
 *                          On success, pass the payload directly.
 *                          On error, pass a string message.
 * @param int   $statusCode HTTP status code (default 200).
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Call http_response_code($statusCode).

    // TODO: If $statusCode indicates success (< 400), echo:
    //         json_encode(['success' => true, 'data' => $data])
    //       Otherwise echo:
    //         json_encode(['success' => false, 'message' => $data])

    // TODO: Call exit to stop further execution.
http_response_code($statusCode);
    if ($statusCode < 400) {
        echo json_encode(["success" => true, "data" => $data]);
    } else {
        echo json_encode(["success" => false, "message" => $data]);
    }
    exit;
}




/**
 * Validates an email address.
 *
 * @param  string $email
 * @return bool   True if the email passes FILTER_VALIDATE_EMAIL, false otherwise.
 */
function validateEmail($email) {
    // TODO: return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
   return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}



/**
 * Sanitizes a string input value.
 * Use this before inserting user-supplied strings into the database.
 *
 * @param  string $data
 * @return string Trimmed, tag-stripped, and HTML-escaped string.
 */
function sanitizeInput($data) {
    // TODO: trim($data)
    // TODO: strip_tags(...)
    // TODO: htmlspecialchars(..., ENT_QUOTES, 'UTF-8')
    // TODO: Return the sanitized value.
return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, "UTF-8");
}
?>
