<?php
/**
 * logs.php — Activity Logs viewer (READ ONLY).
 * Shows all system actions with user, action type, entity, and timestamp.
 * No editing or deletion is allowed from this page.
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/layout.php';
requireLogin();

$conn = getConnection();

// Optional filters
$filterAction   = $_GET['action']   ?? '';
$filterEntity   = $_GET['entity']   ?? '';
$filterUsername = trim($_GET['user'] ?? '');

$where  = [];
$params = [];
$types  = '';

if ($filterAction) {
    $where[]  = "action = ?";
    $params[] = $filterAction;
    $types   .= 's';
}
if ($filterEntity) {
    $where[]  = "entity = ?";
    $params[] = $filterEntity;
    $types   .= 's';
}
if ($filterUsername) {
    $where[]  = "username LIKE ?";
    $params[] = "%{$filterUsername}%";
    $types   .= 's';
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// Pagination
$perPage    = 25;
$page       = max(1, (int)($_GET['page'] ?? 1));
$offset     = ($page - 1) * $perPage;

// Total count for pagination
$countSQL  = "SELECT COUNT(*) FROM activity_logs $whereSQL";
$countStmt = $conn->prepare($countSQL);
if ($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows  = $countStmt->get_result()->fetch_row()[0];
$totalPages = (int)ceil($totalRows / $perPage);
$countStmt->close();

// Fetch logs
$sql  = "SELECT * FROM activity_logs $whereSQL ORDER BY performed_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Distinct values for filter dropdowns
$actions  = $conn->query("SELECT DISTINCT action  FROM activity_logs ORDER BY action")->fetch_all(MYSQLI_ASSOC);
$entities = $conn->query("SELECT DISTINCT entity  FROM activity_logs ORDER BY entity")->fetch_all(MYSQLI_ASSOC);

$conn->close();
renderHeader("Activity Logs");

// Build query string for pagination links
function pageLink($p) {
    $params        = $_GET;
    $params['page'] = $p;
    return '/logs.php?' . http_build_query($params);
}
?>

<div class="page-header">
    <h1>📋 Activity Logs</h1>
    <p>Immutable audit trail — every CRUD operation is recorded here. No editing or deletion permitted.</p>
</div>

<!-- ── FILTER BAR ── -->
<div class="card">
    <form method="GET" action="" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="min-width:150px;margin:0">
            <label>Action</label>
            <select name="action">
                <option value="">All Actions</option>
                <?php foreach ($actions as $a): ?>
                    <option <?= $filterAction === $a['action'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a['action']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="min-width:150px;margin:0">
            <label>Entity</label>
            <select name="entity">
                <option value="">All Entities</option>
                <?php foreach ($entities as $e): ?>
                    <option <?= $filterEntity === $e['entity'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['entity']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="min-width:160px;margin:0">
            <label>Username</label>
            <input type="text" name="user" placeholder="Search user…"
                   value="<?= htmlspecialchars($filterUsername) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="/student_grades/logs.php" class="btn btn-secondary">Reset</a>
    </form>
</div>

<!-- ── STATS ROW ── -->
<div class="flex-row" style="margin-bottom:1.25rem;gap:.5rem">
    <span class="text-muted">
        Showing <strong><?= count($logs) ?></strong> of <strong><?= $totalRows ?></strong> log entries
        <?= $filterAction || $filterEntity || $filterUsername ? '(filtered)' : '' ?>
    </span>
</div>

<!-- ── LOGS TABLE ── -->
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="empty-state">No log entries match your filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $i => $log): ?>
                    <tr>
                        <td class="text-muted"><?= $totalRows - $offset - $i ?></td>
                        <td class="text-muted" style="white-space:nowrap"><?= $log['performed_at'] ?></td>
                        <td><strong><?= htmlspecialchars($log['username']) ?></strong></td>
                        <td>
                            <span class="badge badge-<?= strtolower($log['action']) ?>">
                                <?= htmlspecialchars($log['action']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($log['entity']) ?></td>
                        <td><?= htmlspecialchars($log['description']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ── PAGINATION ── -->
    <?php if ($totalPages > 1): ?>
    <div class="flex-row mt-2" style="justify-content:center;gap:.35rem">
        <?php if ($page > 1): ?>
            <a href="<?= pageLink($page - 1) ?>" class="btn btn-secondary btn-sm">← Prev</a>
        <?php endif; ?>
        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
            <a href="<?= pageLink($p) ?>"
               class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>">
                <?= $p ?>
            </a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="<?= pageLink($page + 1) ?>" class="btn btn-secondary btn-sm">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<p class="text-muted" style="margin-top:.5rem;font-size:.8rem">
    ⚠️ Activity logs are read-only. Editing or deleting log entries is not permitted.
</p>

<?php renderFooter(); ?>
