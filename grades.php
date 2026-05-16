<?php
/**
 * grades.php — Full CRUD for the Grades (child) entity.
 * Each grade belongs to a student (one-to-many).
 * Every DB operation is recorded to activity_logs.
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireLogin();

$conn    = getConnection();
$msg     = '';
$msgType = 'success';
$editing = null;

// Pre-filter by student if ?student_id is in the URL
$filterStudentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

// ── HANDLE POST ACTIONS ─────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── INSERT ──
    if ($action === 'insert') {
        $sid   = (int)($_POST['student_id'] ?? 0);
        $subj  = trim($_POST['subject']     ?? '');
        $grade = trim($_POST['grade']       ?? '');
        $sem   = trim($_POST['semester']    ?? '');
        $sy    = trim($_POST['school_year'] ?? '');

        if ($sid && $subj && $grade !== '' && $sem && $sy) {
            $stmt = $conn->prepare(
                "INSERT INTO grades (student_id, subject, grade, semester, school_year) VALUES (?,?,?,?,?)"
            );
            $stmt->bind_param("issss", $sid, $subj, $grade, $sem, $sy);
            $stmt->execute();
            $stmt->close();

            // Fetch student name for log
            $srow = $conn->query("SELECT first_name, last_name FROM students WHERE id=$sid")->fetch_assoc();
            $name = $srow ? "{$srow['first_name']} {$srow['last_name']}" : "ID #$sid";
            logActivity($conn, 'CREATE', 'grades',
                "Added grade for {$name}: {$subj} = {$grade} ({$sem}, {$sy})");
            $msg = "Grade added for {$name}.";
            $filterStudentId = $sid;
        } else {
            $msg     = "All fields are required.";
            $msgType = 'danger';
        }
    }

    // ── UPDATE ──
    if ($action === 'update') {
        $id    = (int)($_POST['id']          ?? 0);
        $sid   = (int)($_POST['student_id']  ?? 0);
        $subj  = trim($_POST['subject']      ?? '');
        $grade = trim($_POST['grade']        ?? '');
        $sem   = trim($_POST['semester']     ?? '');
        $sy    = trim($_POST['school_year']  ?? '');

        if ($id && $sid && $subj && $grade !== '' && $sem && $sy) {
            $stmt = $conn->prepare(
                "UPDATE grades SET student_id=?, subject=?, grade=?, semester=?, school_year=? WHERE id=?"
            );
            $stmt->bind_param("issssi", $sid, $subj, $grade, $sem, $sy, $id);
            $stmt->execute();
            $stmt->close();

            $srow = $conn->query("SELECT first_name, last_name FROM students WHERE id=$sid")->fetch_assoc();
            $name = $srow ? "{$srow['first_name']} {$srow['last_name']}" : "ID #$sid";
            logActivity($conn, 'UPDATE', 'grades',
                "Updated grade ID #{$id} for {$name}: {$subj} = {$grade} ({$sem}, {$sy})");
            $msg = "Grade updated.";
            $filterStudentId = $sid;
        } else {
            $msg     = "All fields are required.";
            $msgType = 'danger';
        }
    }

    // ── DELETE ──
    if ($action === 'delete') {
        $id  = (int)($_POST['id']         ?? 0);
        $sid = (int)($_POST['student_id'] ?? 0);
        if ($id) {
            $row  = $conn->query("SELECT g.subject, g.grade, s.first_name, s.last_name
                                   FROM grades g JOIN students s ON s.id = g.student_id
                                   WHERE g.id = $id")->fetch_assoc();
            $stmt = $conn->prepare("DELETE FROM grades WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            if ($row) {
                logActivity($conn, 'DELETE', 'grades',
                    "Deleted grade for {$row['first_name']} {$row['last_name']}: {$row['subject']} = {$row['grade']}");
            }
            $msg = "Grade deleted.";
            $filterStudentId = $sid;
        }
    }
}

// ── EDIT MODE ──
if (isset($_GET['edit'])) {
    $editId  = (int)$_GET['edit'];
    $editing = $conn->query("SELECT * FROM grades WHERE id=$editId")->fetch_assoc();
    if ($editing) {
        $filterStudentId = (int)$editing['student_id'];
        logActivity($conn, 'READ', 'grades', "Viewed grade record ID #{$editId} for editing");
    }
}

// ── FETCH STUDENTS (for dropdown) ────────────────────────────────────────────
$allStudents = $conn->query(
    "SELECT id, student_no, first_name, last_name FROM students ORDER BY last_name, first_name"
);

// ── FETCH GRADES (filtered or all) ───────────────────────────────────────────
$whereClause = $filterStudentId ? "WHERE g.student_id = $filterStudentId" : "";
$grades = $conn->query(
    "SELECT g.*, s.first_name, s.last_name, s.student_no
     FROM grades g
     JOIN students s ON s.id = g.student_id
     $whereClause
     ORDER BY s.last_name, s.first_name, g.school_year DESC, g.semester"
);

// Fetch the filtered student name for heading
$filterStudent = null;
if ($filterStudentId) {
    $filterStudent = $conn->query(
        "SELECT * FROM students WHERE id=$filterStudentId"
    )->fetch_assoc();
}

$conn->close();
renderHeader("Grades");
?>

<div class="page-header">
    <h1>📝 Grades</h1>
    <p>Child entity — each student can have many grade records across subjects and semesters.</p>
</div>

<?php if ($filterStudent): ?>
    <div class="alert alert-read" style="background:rgba(91,138,245,.08);border:1px solid var(--accent);color:var(--accent);padding:.7rem 1rem;border-radius:7px;margin-bottom:1rem;font-size:.875rem;display:flex;align-items:center;gap:.5rem;">
        🔎 Showing grades for <strong><?= htmlspecialchars($filterStudent['first_name'] . ' ' . $filterStudent['last_name']) ?></strong>
        (<?= htmlspecialchars($filterStudent['student_no']) ?>)
        — <a href="/student_grades/grades.php" style="color:var(--accent2);text-decoration:none;font-weight:600;">Show all →</a>
    </div>
<?php endif; ?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>">
        <?= $msgType === 'success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<!-- ── ADD / EDIT FORM ── -->
<div class="card">
    <div class="card-title"><?= $editing ? '✏️ Edit Grade' : '➕ Add Grade Record' ?></div>
    <form method="POST" action="">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'insert' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= $editing['id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <label>Student</label>
                <select name="student_id" required>
                    <option value="">— Select student —</option>
                    <?php $allStudents->data_seek(0); while ($s = $allStudents->fetch_assoc()): ?>
                        <?php $sel = ($editing['student_id'] ?? $filterStudentId) == $s['id'] ? 'selected' : ''; ?>
                        <option value="<?= $s['id'] ?>" <?= $sel ?>>
                            <?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name'] . ' (' . $s['student_no'] . ')') ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" placeholder="e.g. Mathematics"
                       value="<?= htmlspecialchars($editing['subject'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Grade (0–100)</label>
                <input type="number" name="grade" placeholder="e.g. 87.50"
                       min="0" max="100" step="0.01"
                       value="<?= htmlspecialchars($editing['grade'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Semester</label>
                <select name="semester" required>
                    <?php foreach (['1st Semester','2nd Semester','Summer'] as $sem): ?>
                        <option <?= ($editing['semester'] ?? '') === $sem ? 'selected' : '' ?>>
                            <?= $sem ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>School Year</label>
                <input type="text" name="school_year" placeholder="e.g. 2024-2025"
                       value="<?= htmlspecialchars($editing['school_year'] ?? '') ?>" required>
            </div>
        </div>

        <div class="flex-row mt-2">
            <button type="submit" class="btn btn-<?= $editing ? 'success' : 'primary' ?>">
                <?= $editing ? '💾 Save Changes' : '➕ Add Grade' ?>
            </button>
            <?php if ($editing): ?>
                <a href="/student_grades/grades.php<?= $filterStudentId ? "?student_id=$filterStudentId" : '' ?>"
                   class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ── GRADES TABLE ── -->
<div class="card">
    <div class="card-title">📋 Grade Records</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Subject</th>
                    <th>Grade</th>
                    <th>Semester</th>
                    <th>School Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($grades->num_rows === 0): ?>
                    <tr><td colspan="7" class="empty-state">No grade records found.</td></tr>
                <?php else: $i = 1; while ($g = $grades->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted"><?= $i++ ?></td>
                    <td>
                        <strong><?= htmlspecialchars($g['last_name'] . ', ' . $g['first_name']) ?></strong>
                        <br><span class="text-muted"><?= htmlspecialchars($g['student_no']) ?></span>
                    </td>
                    <td><?= htmlspecialchars($g['subject']) ?></td>
                    <td>
                        <?php $gv = (float)$g['grade']; ?>
                        <span class="grade-pill <?= $gv >= 75 ? 'grade-pass' : 'grade-fail' ?>">
                            <?= number_format($gv, 2) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($g['semester']) ?></td>
                    <td><?= htmlspecialchars($g['school_year']) ?></td>
                    <td>
                        <div class="flex-row" style="gap:.4rem">
                            <a href="/student_grades/grades.php?edit=<?= $g['id'] ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('Delete this grade record?')">
                                <input type="hidden" name="action"     value="delete">
                                <input type="hidden" name="id"        value="<?= $g['id'] ?>">
                                <input type="hidden" name="student_id" value="<?= $g['student_id'] ?>">
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
