<?php
/**
 * admin/send-results.php
 * Push SMS functionality: send academic results to students via Twilio.
 * Supports sending to all students or selected individual students.
 */

require_once __DIR__ . '/auth.php';

$db        = getDB();
$pageTitle = 'Send Results via SMS';

$results_send = [];
$sent = $failed = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $semester    = post('semester');
    $session     = post('session', '2023/2024');
    $sendTo      = post('send_to');          // 'all' | 'selected'
    $selectedIds = $_POST['student_ids'] ?? []; // Array of student IDs

    if (!in_array($semester, ['First', 'Second'], true)) {
        setFlash('error', 'Please select a valid semester.');
        header('Location: ' . url('admin/send-results.php'));
        exit;
    }

    // Build list of students to send to
    if ($sendTo === 'all') {
        $stmtStudents = $db->query('SELECT id FROM students');
    } else {
        if (empty($selectedIds)) {
            setFlash('error', 'Please select at least one student.');
            header('Location: ' . url('admin/send-results.php'));
            exit;
        }
        // Build safe IN clause using positional placeholders
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        $stmtStudents = $db->prepare("SELECT id FROM students WHERE id IN ({$placeholders})");
        $stmtStudents->execute(array_map('intval', $selectedIds));
    }

    $studentRows = $stmtStudents->fetchAll();

    foreach ($studentRows as $sRow) {
        $studentId = (int)$sRow['id'];

        // Fetch student detail
        $stmtS = $db->prepare('SELECT * FROM students WHERE id = ?');
        $stmtS->execute([$studentId]);
        $student = $stmtS->fetch();

        if (!$student) continue;

        // Fetch results for this student / semester / session
        $semResults = getStudentResults($studentId, $semester, $session);
        if (empty($semResults)) {
            $results_send[] = ['student' => $student, 'status' => 'skip', 'note' => 'No results found'];
            continue;
        }

        // Build and send SMS
        $message = buildResultSMS($student, $semResults, $semester, $session);
        $outcome = sendSMS($studentId, $student['phone_number'], $message);

        $results_send[] = [
            'student' => $student,
            'status'  => $outcome['success'] ? 'sent' : 'failed',
            'note'    => $outcome['message'],
            'gpa'     => calculateGPA($semResults),
        ];

        $outcome['success'] ? $sent++ : $failed++;
    }
}

// Fetch all students for the "send selected" list
$allStudents = $db->query(
    'SELECT s.*, COUNT(r.id) AS result_count
     FROM students s
     LEFT JOIN results r ON r.student_id = s.id
     GROUP BY s.id
     ORDER BY s.matric_no'
)->fetchAll();

$sessions = $db->query('SELECT DISTINCT session FROM results ORDER BY session DESC')->fetchAll(PDO::FETCH_COLUMN);

// SMS log summary
$smsLog = $db->query(
    'SELECT sl.*, s.full_name, s.matric_no
     FROM sms_logs sl JOIN students s ON sl.student_id = s.id
     ORDER BY sl.date_sent DESC LIMIT 20'
)->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<?php if (!empty($results_send)): ?>
<!-- Send Results Summary -->
<div class="card mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-check-circle me-2 text-success"></i>Dispatch Summary</h6>
        <div>
            <span class="badge bg-success me-1"><?= $sent ?> Sent</span>
            <span class="badge bg-danger me-1"><?= $failed ?> Failed</span>
            <span class="badge bg-secondary"><?= count($results_send) - $sent - $failed ?> Skipped</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Phone</th>
                        <th>GPA</th>
                        <th>Status</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results_send as $r): ?>
                    <tr>
                        <td>
                            <div class="fw-medium small"><?= e($r['student']['full_name']) ?></div>
                            <div class="text-muted" style="font-size:.72rem"><?= e($r['student']['matric_no']) ?></div>
                        </td>
                        <td class="small"><?= e($r['student']['phone_number']) ?></td>
                        <td><?= isset($r['gpa']) ? number_format($r['gpa'], 2) : '—' ?></td>
                        <td>
                            <?php if ($r['status'] === 'sent'): ?>
                            <span class="badge bg-success">Sent</span>
                            <?php elseif ($r['status'] === 'failed'): ?>
                            <span class="badge bg-danger">Failed</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Skipped</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= e($r['note'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Send Form -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-send me-2 text-primary"></i>Send Results</h6>
            </div>
            <div class="card-body">
                <!--
                <?php if (!defined('TWILIO_SID') || empty(TWILIO_SID)): ?>
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Twilio not configured.</strong> Set <code>TWILIO_SID</code>, <code>TWILIO_TOKEN</code>, and
                    <code>TWILIO_PHONE</code> (environment variables or in your server config) to enable live SMS sending.
                    Results will still be logged as "failed" for testing.
                </div>
                <?php endif; ?>
                -->

                <?php if (empty(INFOBIP_API_KEY) || empty(INFOBIP_BASE_URL)): ?>
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Infobip not configured.</strong> Set <code>INFOBIP_API_KEY</code> and
                    <code>INFOBIP_BASE_URL</code> (in <code>.env</code> or server config) to enable live SMS sending.
                    Results will still be logged as "failed" for testing.
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= e(url('admin/send-results.php')) ?>" id="sendForm">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Semester <span class="text-danger">*</span></label>
                            <select class="form-select" name="semester" required>
                                <option value="">— Select —</option>
                                <option value="First">First Semester</option>
                                <option value="Second">Second Semester</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Academic Session</label>
                            <select class="form-select" name="session">
                                <?php if (empty($sessions)): ?>
                                <option value="2023/2024">2023/2024</option>
                                <?php else: ?>
                                <?php foreach ($sessions as $sess): ?>
                                <option value="<?= e($sess) ?>"><?= e($sess) ?></option>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Send scope -->
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Send To</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="send_to" id="sendAll"
                                       value="all" checked onchange="toggleStudentList(false)">
                                <label class="form-check-label small" for="sendAll">All Students</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="send_to" id="sendSelected"
                                       value="selected" onchange="toggleStudentList(true)">
                                <label class="form-check-label small" for="sendSelected">Selected Students</label>
                            </div>
                        </div>
                    </div>

                    <!-- Student multi-select (hidden by default) -->
                    <div id="studentListWrap" class="mb-3 d-none">
                        <label class="form-label fw-medium small">Select Students</label>
                        <div style="max-height:200px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; padding:.5rem">
                            <?php foreach ($allStudents as $s): ?>
                            <div class="form-check py-1">
                                <input class="form-check-input" type="checkbox"
                                       name="student_ids[]" value="<?= $s['id'] ?>"
                                       id="sid_<?= $s['id'] ?>">
                                <label class="form-check-label small" for="sid_<?= $s['id'] ?>">
                                    <?= e($s['matric_no']) ?> — <?= e($s['full_name']) ?>
                                    <span class="text-muted">(<?= $s['result_count'] ?> results)</span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-1 d-flex gap-2">
                            <button type="button" class="btn btn-link btn-sm p-0" onclick="selectAll(true)">Select All</button>
                            <button type="button" class="btn btn-link btn-sm p-0 text-muted" onclick="selectAll(false)">Clear</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100"
                            onclick="return confirm('Send SMS results now? This will charge your Infobip account.')">
                        <i class="bi bi-send-fill me-2"></i>Send SMS Results Now
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- SMS Log -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2 text-muted"></i>SMS Log (Last 20)</h6>
            </div>
            <div class="card-body p-0" style="max-height:450px; overflow-y:auto">
                <?php if (empty($smsLog)): ?>
                <div class="p-4 text-center text-muted small">No SMS history yet.</div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($smsLog as $log): ?>
                    <li class="list-group-item py-2 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-medium small"><?= e($log['full_name']) ?></div>
                                <div class="text-muted" style="font-size:.72rem"><?= e($log['matric_no']) ?></div>
                            </div>
                            <?php if ($log['status'] === 'sent'): ?>
                            <span class="badge bg-success">Sent</span>
                            <?php elseif ($log['status'] === 'failed'): ?>
                            <span class="badge bg-danger">Failed</span>
                            <?php else: ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted mt-1" style="font-size:.72rem">
                            <?= date('d M Y, H:i', strtotime($log['date_sent'])) ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleStudentList(show) {
        document.getElementById('studentListWrap').classList.toggle('d-none', !show);
    }
    function selectAll(val) {
        document.querySelectorAll('#studentListWrap input[type=checkbox]')
                .forEach(cb => cb.checked = val);
    }
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
