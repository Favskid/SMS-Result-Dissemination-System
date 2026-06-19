<?php
/**
 * admin/dashboard.php
 * Main admin dashboard showing key system statistics and recent SMS logs.
 */

require_once __DIR__ . '/auth.php';   // Enforce login; starts session

$db = getDB();
$pageTitle = 'Dashboard';

// ── Aggregate statistics ────────────────────────────────────────────────────
$totalStudents = (int)$db->query('SELECT COUNT(*) FROM students')->fetchColumn();
$totalResults  = (int)$db->query('SELECT COUNT(*) FROM results')->fetchColumn();
$smsSent       = (int)$db->query("SELECT COUNT(*) FROM sms_logs WHERE status='sent'")->fetchColumn();
$smsFailed     = (int)$db->query("SELECT COUNT(*) FROM sms_logs WHERE status='failed'")->fetchColumn();

// ── Recent SMS logs (last 10) ───────────────────────────────────────────────
$recentSMS = $db->query(
    'SELECT sl.*, s.full_name, s.matric_no
     FROM sms_logs sl
     JOIN students s ON sl.student_id = s.id
     ORDER BY sl.date_sent DESC LIMIT 10'
)->fetchAll();

// ── Results per department ──────────────────────────────────────────────────
$byDept = $db->query(
    'SELECT s.department, COUNT(r.id) AS total_results, COUNT(DISTINCT r.student_id) AS student_count
     FROM results r JOIN students s ON r.student_id = s.id
     GROUP BY s.department ORDER BY total_results DESC'
)->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- ── Stat Cards ────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3 h-100">
            <div class="d-flex align-items-start gap-3">
                <div class="icon-box bg-primary bg-opacity-10">
                    <i class="bi bi-people-fill text-primary"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format($totalStudents) ?></div>
                    <div class="text-muted small">Total Students</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3 h-100">
            <div class="d-flex align-items-start gap-3">
                <div class="icon-box bg-success bg-opacity-10">
                    <i class="bi bi-journal-check text-success"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format($totalResults) ?></div>
                    <div class="text-muted small">Result Entries</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3 h-100">
            <div class="d-flex align-items-start gap-3">
                <div class="icon-box bg-info bg-opacity-10">
                    <i class="bi bi-chat-dots-fill text-info"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format($smsSent) ?></div>
                    <div class="text-muted small">SMS Sent</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3 h-100">
            <div class="d-flex align-items-start gap-3">
                <div class="icon-box bg-danger bg-opacity-10">
                    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format($smsFailed) ?></div>
                    <div class="text-muted small">SMS Failed</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Quick Actions ──────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card p-3">
            <h6 class="fw-semibold mb-3"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Quick Actions</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= e(url('admin/upload.php')) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-cloud-upload me-1"></i>Upload Results
                </a>
                <a href="<?= e(url('admin/send-results.php')) ?>" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-send me-1"></i>Send SMS Results
                </a>
                <a href="<?= e(url('admin/students.php')) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-person-plus me-1"></i>Manage Students
                </a>
                <a href="<?= e(url('admin/results.php')) ?>" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-journal-text me-1"></i>View All Results
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Recent SMS Log -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2 text-muted"></i>Recent SMS Activity</h6>
                <a href="<?= e(url('admin/send-results.php')) ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentSMS)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-chat-square-dots fs-2 d-block mb-2 opacity-50"></i>
                    No SMS messages sent yet.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Status</th>
                                <th>Sent At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSMS as $log): ?>
                            <tr>
                                <td>
                                    <div class="fw-medium small"><?= e($log['full_name']) ?></div>
                                    <div class="text-muted" style="font-size:.75rem"><?= e($log['matric_no']) ?></div>
                                </td>
                                <td>
                                    <?php if ($log['status'] === 'sent'): ?>
                                    <span class="badge bg-success">Sent</span>
                                    <?php elseif ($log['status'] === 'failed'): ?>
                                    <span class="badge bg-danger">Failed</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?= date('d M Y, H:i', strtotime($log['date_sent'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Results by Department -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart me-2 text-muted"></i>By Department</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($byDept)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-building fs-2 d-block mb-2 opacity-50"></i>
                    No data yet.
                </div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($byDept as $dept): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <div class="small fw-medium"><?= e($dept['department']) ?></div>
                            <div class="text-muted" style="font-size:.75rem"><?= $dept['student_count'] ?> students</div>
                        </div>
                        <span class="badge bg-primary rounded-pill"><?= $dept['total_results'] ?> entries</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
