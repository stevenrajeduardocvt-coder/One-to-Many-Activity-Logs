<?php
/**
 * Session & Authentication Helpers
 * Handles session start, login checks, and activity logging.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirects to login page if user is not authenticated.
 */
function requireLogin() {
    if (!isset($_SESSION['username'])) {
        header("Location: /student_grades/login.php");
        exit();
    }
}

/**
 * Returns the currently logged-in username, or null if not logged in.
 */
function currentUser() {
    return $_SESSION['username'] ?? null;
}

/**
 * Inserts a record into activity_logs for every CRUD operation.
 *
 * @param mysqli $conn       Active database connection
 * @param string $action     One of: CREATE, READ, UPDATE, DELETE
 * @param string $entity     Table/entity name (e.g., 'students', 'grades')
 * @param string $description Human-readable description of what happened
 */
function logActivity($conn, $action, $entity, $description) {
    $username = currentUser() ?? 'system';
    $stmt = $conn->prepare(
        "INSERT INTO activity_logs (username, action, entity, description) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $username, $action, $entity, $description);
    $stmt->execute();
    $stmt->close();
}
?>
