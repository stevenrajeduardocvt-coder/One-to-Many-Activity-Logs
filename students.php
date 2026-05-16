<?php
/**
 * students.php — Full CRUD management for the Students (parent) entity.
 * Supports: Add, Edit, Delete with activity logging for every operation.
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireLogin();

$conn    = getConnection();
$msg     = '';
$msgType = 'success';
$editing = null;   // Holds a student row when in edit mode

// ── HANDLE POST ACTIONS ─────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── INSERT ──
    if ($action === 'insert') {
        $sno   = trim($_POST['student_no']  ?? '');
        $fname = trim($_POST['first_name']  ?? '');
        $lname = trim($_POST['last_name']   ?? '');
        $email = trim($_POST['email']       ?? '');
        $course= trim($_POST['course']      ?? '');

        if ($sno && $fname && $lname && $email && $course) {
            $stmt = $conn->prepare(
                "INSERT INTO students (student_no, first_name, last_name, email, course) VALUES (?,?,?,?,?)"
            );
            $stmt->bind_param("sssss", $sno, $fname, $lname, $email, $course);
            if ($stmt->execute()) {
                logActivity($conn, 'CREATE', 'students',
                    "Added student {$fname} {$lname} (#{$sno})");
                $msg = "Student {$fname} {$lname} added successfully.";
            } else {
                $msg     = "Error: duplicate student number or email.";
                $msgType = 'danger';
            }
            $stmt->close();
        } else {
            $msg     = "All fields are required.";
            $msgType = 'danger';
        }
    }

    // ── UPDATE ──
    if ($action === 'update') {
        $id    = (int)($_POST['id']          ?? 0);
        $sno   = trim($_POST['student_no']   ?? '');
        $fname = trim($_POST['first_name']   ?? '');
        $lname = trim($_POST['last_name']    ?? '');
        $email = trim($_POST['email']        ?? '');
        $course= trim($_POST['course']       ?? '');

        if ($id && $sno && $fname && $lname && $email && $course) {
            $stmt = $conn->prepare(
                "UPDATE students SET student_no=?, first_name=?, last_name=?, email=?, course=? WHERE id=?"
            );
            $stmt->bind_param("sssssi", $sno, $fname, $lname, $email, $course, $id);
            $stmt->execute();
            $stmt->close();
            logActivity($conn, 'UPDATE', 'students',
                "Updated student ID #{$id}: {$fname} {$lname}");
            $msg = "Student updated successfully.";
        } else {
            $msg     = "All fields are required.";
            $msgType = 'danger';
        }
    }

    // ── DELETE ──
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // Fetch name for log before deleting
            $row = $conn->query("SELECT first_name, last_name FROM students WHERE id=$id")->fetch_assoc();
            $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            if ($row) {
                logActivity($conn, 'DELETE', 'students',
                    "Deleted student {$row['first_name']} {$row['last_name']} (ID #{$id})");
            }
            $msg = "Student deleted successfully.";
        }
    }
}

// ── EDIT MODE: load student data ──
if (isset($_GET['edit'])) {
    $editId  = (int)$_GET['edit'];
    $editing = $conn->query("SELECT * FROM students WHERE id=$editId")->fetch_assoc();
    logActivity($conn, 'READ', 'students', "Viewed student record ID #{$editId} for editing");
}

// ── FETCH ALL STUDENTS ───────────────────────────────────────────────────────
$students = $conn->query(
    "SELECT s.*, COUNT(g.id) AS grade_count
     FROM students s
     LEFT JOIN grades g ON g.student_id = s.id
     GROUP BY s.id
     ORDER BY s.last_name, s.first_name"
);

$conn->close();
renderHeader("Students");
?>

<div class="page-header">
    <h1>🎓 Students</h1>
    <p>Manage the parent entity — one student can have many grade records.</p>
</div>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>">
        <?= $msgType === 'success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<!-- ── ADD / EDIT FORM ── -->
<div class="card">
    <div class="card-title">
        <?= $editing ? '✏️ Edit Student' : '➕ Add New Student' ?>
    </div>
    <form method="POST" action="">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'insert' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= $editing['id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <label>Student No.</label>
                <input type="text" name="student_no" placeholder="e.g. 2024-0001"
                       value="<?= htmlspecialchars($editing['student_no'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" placeholder="First name"
                       value="<?= htmlspecialchars($editing['first_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" placeholder="Last name"
                       value="<?= htmlspecialchars($editing['last_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="student@school.edu"
                       value="<?= htmlspecialchars($editing['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Course / Program</label>
                <input type="text" name="course" placeholder="e.g. BSCS"
                       value="<?= htmlspecialchars($editing['course'] ?? '') ?>" required>
            </div>
        </div>

        <div class="flex-row mt-2">
            <button type="submit" class="btn btn-<?= $editing ? 'success' : 'primary' ?>">
                <?= $editing ? '💾 Save Changes' : '➕ Add Student' ?>
            </button>
            <?php if ($editing): ?>
                <a href="/student_grades/students.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ── STUDENTS TABLE ── -->
<div class="card">
    <div class="card-title">📋 All Students</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student No.</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Grades</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($students->num_rows === 0): ?>
                    <tr><td colspan="7" class="empty-state">No students found. Add one above.</td></tr>
                <?php else: $i = 1; while ($s = $students->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted"><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($s['student_no']) ?></strong></td>
                    <td><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($s['email']) ?></td>
                    <td><?= htmlspecialchars($s['course']) ?></td>
                    <td>
                        <a href="/student_grades/grades.php?student_id=<?= $s['id'] ?>" class="badge badge-read">
                            <?= $s['grade_count'] ?> grade<?= $s['grade_count'] != 1 ? 's' : '' ?>
                        </a>
                    </td>
                    <td>
                        <div class="flex-row" style="gap:.4rem">
                            <a href="/student_grades/students.php?edit=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('Delete this student and all their grades?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id"     value="<?= $s['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑 Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderFooter(); ?>
