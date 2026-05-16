<?php
/**
 * search.php — Full-text search for both Students (parent) and Grades (child).
 * Logs every READ action triggered by a search query.
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireLogin();

$conn = getConnection();
$q    = trim($_GET['q'] ?? '');

$studentResults = [];
$gradeResults   = [];

if ($q !== '') {
    $like = "%{$q}%";

    // ── SEARCH STUDENTS (parent) ──────────────────────────────────────────────
    $stmt = $conn->prepare(
        "SELECT s.*, COUNT(g.id) AS grade_count
         FROM students s
         LEFT JOIN grades g ON g.student_id = s.id
         WHERE s.student_no  LIKE ?
            OR s.first_name  LIKE ?
            OR s.last_name   LIKE ?
            OR s.email       LIKE ?
            OR s.course      LIKE ?
         GROUP BY s.id
         ORDER BY s.last_name, s.first_name
         LIMIT 50"
    );
    $stmt->bind_param("sssss", $like, $like, $like, $like, $like);
    $stmt->execute();
    $studentResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ── SEARCH GRADES (child) ─────────────────────────────────────────────────
    $stmt = $conn->prepare(
        "SELECT g.*, s.first_name, s.last_name, s.student_no
         FROM grades g
         JOIN students s ON s.id = g.student_id
         WHERE g.subject      LIKE ?
            OR g.semester     LIKE ?
            OR g.school_year  LIKE ?
            OR s.first_name   LIKE ?
            OR s.last_name    LIKE ?
            OR s.student_no   LIKE ?
         ORDER BY s.last_name, g.school_year DESC
         LIMIT 50"
    );
    $stmt->bind_param("ssssss", $like, $like, $like, $like, $like, $like);
    $stmt->execute();
    $gradeResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Log the READ operation
    $total = count($studentResults) + count($gradeResults);
    logActivity($conn, 'READ', 'search',
        "Searched for \"{$q}\" — {$total} result(s) found");
}

$conn->close();
renderHeader("Search");
?>

<div class="page-header">
    <h1>🔍 Search</h1>
    <p>Search across students and grade records simultaneously.</p>
</div>

<!-- ── SEARCH FORM ── -->
<div class="card">
    <form method="GET" action="" style="display:flex;gap:.75rem;align-items:flex-end">
        <div class="form-group" style="flex:1;margin:0">
            <label>Search Query</label>
            <input type="text" name="q" placeholder="Name, student no., subject, semester…"
                   value="<?= htmlspecialchars($q) ?>" autofocus>
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($q): ?>
            <a href="/search.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($q): ?>

<!-- ── STUDENT RESULTS ── -->
<div class="card">
    <div class="card-title">
        🎓 Students
        <span class="badge badge-read" style="margin-left:.5rem"><?= count($studentResults) ?></span>
    </div>

    <?php if (empty($studentResults)): ?>
        <div class="empty-state">No students matched "<?= htmlspecialchars($q) ?>".</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student No.</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Grades</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($studentResults as $s): ?>
                    <tr>
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
                            <a href="/student_grades/students.php?edit=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ── GRADE RESULTS ── -->
<div class="card">
    <div class="card-title">
        📝 Grade Records
        <span class="badge badge-read" style="margin-left:.5rem"><?= count($gradeResults) ?></span>
    </div>

    <?php if (empty($gradeResults)): ?>
        <div class="empty-state">No grade records matched "<?= htmlspecialchars($q) ?>".</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Subject</th>
                        <th>Grade</th>
                        <th>Semester</th>
                        <th>School Year</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gradeResults as $g): ?>
                    <?php $gv = (float)$g['grade']; ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($g['last_name'] . ', ' . $g['first_name']) ?></strong>
                            <br><span class="text-muted"><?= htmlspecialchars($g['student_no']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($g['subject']) ?></td>
                        <td>
                            <span class="grade-pill <?= $gv >= 75 ? 'grade-pass' : 'grade-fail' ?>">
                                <?= number_format($gv, 2) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($g['semester']) ?></td>
                        <td><?= htmlspecialchars($g['school_year']) ?></td>
                        <td>
                            <a href="/student_grades/grades.php?edit=<?= $g['id'] ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php else: ?>
    <div class="empty-state" style="padding:4rem 1rem">
        <div style="font-size:3rem;margin-bottom:1rem">🔍</div>
        <div>Enter a search term above to find students or grades.</div>
    </div>
<?php endif; ?>

<?php renderFooter(); ?>
