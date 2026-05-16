<?php
/**
 * index.php — Dashboard homepage showing system statistics
 * and recent activity log entries.
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireLogin();

$conn = getConnection();

// Fetch summary counts
$totalStudents = $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
$totalGrades   = $conn->query("SELECT COUNT(*) FROM grades")->fetch_row()[0];
$totalUsers    = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$totalLogs     = $conn->query("SELECT COUNT(*) FROM activity_logs")->fetch_row()[0];

// Fetch 5 most recent activity log entries
$recent = $conn->query(
    "SELECT username, action, entity, description, performed_at
     FROM activity_logs
     ORDER BY performed_at DESC
     LIMIT 5"
);

$conn->close();

renderHeader("Dashboard");
?>

<div class="page-header">
    <h1>📊 Dashboard</h1>
    <p>Welcome back, <strong><?= htmlspecialchars(currentUser()) ?></strong>. Here's a snapshot of your system.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Students</div>
        <div class="stat-value"><?= $totalStudents ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Grade Records</div>
        <div class="stat-value"><?= $totalGrades ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">System Users</div>
        <div class="stat-value"><?= $totalUsers ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Activity Logs</div>
        <div class="stat-value"><?= $totalLogs ?></div>
    </div>
</div>

<div class="card">
    <div class="card-title">🕓 Recent Activity</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Description</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent->num_rows === 0): ?>
                    <tr><td colspan="5" class="empty-state">No activity yet.</td></tr>
                <?php else: ?>
                    <?php while ($row = $recent->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['username']) ?></strong></td>
                        <td>
                            <span class="badge badge-<?= strtolower($row['action']) ?>">
                                <?= htmlspecialchars($row['action']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['entity']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td class="text-muted"><?= $row['performed_at'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="mt-2">
        <a href="/student_grades/logs.php" class="btn btn-secondary btn-sm">View all logs →</a>
    </div>
</div>

<?php renderFooter(); ?>
