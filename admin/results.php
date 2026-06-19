<?php
/**
 * admin/results.php
 * View and manage academic result entries.
 * Supports filtering by student, semester, and session.
 * Allows adding individual results and deleting entries.
 */

require_once __DIR__ . '/auth.php';

$db        = getDB();
$pageTitle = 'Results';

// ── Handle add/delete ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = post('action');

    if ($postAction === 'add') {
        $studentId   = (int)post('student_id');
        $semester    = post('semester');
        $session     = post('session');
        $courseCode  = strtoupper(post('course_code'));
        $courseTitle = post('course_title');
        $creditUnit  = (int)post('credit_unit', '2');
        $score       = (float)post('score');

        if (!$studentId || !$courseCode || !$courseTitle || $creditUnit < 1) {
            setFlash('error', 'Please fill in all required fields.');
        } elseif (!in_array($semester, ['First', 'Second'], true)) {
            setFlash('error', 'Please select a valid semester.');
        } elseif ($score < 0 || $score > 100) {
            setFlash('error', 'Score must be between 0 and 100.');
        } else {
            // Derive grade and grade point from score
            $gradeInfo = scoreToGrade($score);
            $stmt = $db->prepare(
                'INSERT INTO results (student_id, semester, session, course_code, course_title, credit_unit, score, grade, grade_point)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $studentId, $semester, $session, $courseCode, $courseTitle,
                $creditUnit, $score, $gradeInfo['grade'], $gradeInfo['point']
            ]);
            setFlash('success', 'Result entry added successfully.');
        }
        header('Location: ' . url('admin/results.php'));
        exit;
    }

    if ($postAction === 'delete') {
        $db->prepare('DELETE FROM results WHERE id = ?')->execute([(int)post('id')]);
        setFlash('success', 'Result deleted.');
        header('Location: ' . url('admin/results.php'));
        exit;
    }
}

// ── Filters ────────────────────────────────────────────────────────────────
$filterSemester = get('semester');
$filterSession  = get('session', '2023/2024');
$filterDept     = get('department');

$sql    = 'SELECT r.*, s.full_name, s.matric_no, s.department FROM results r JOIN students s ON r.student_id = s.id WHERE 1=1';
$params = [];

if ($filterSemester) { $sql .= ' AND r.semester = ?';    $params[] = $filterSemester; }
if ($filterSession)  { $sql .= ' AND r.session = ?';     $params[] = $filterSession; }
if ($filterDept)     { $sql .= ' AND s.department = ?';  $params[] = $filterDept; }

$sql .= ' ORDER BY s.matric_no, r.course_code';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

// For add-result modal: all students
$allStudents = $db->query('SELECT id, matric_no, full_name FROM students ORDER BY matric_no')->fetchAll();

// Unique sessions for filter
$sessions = $db->query('SELECT DISTINCT session FROM results ORDER BY session DESC')->fetchAll(PDO::FETCH_COLUMN);
$depts    = $db->query('SELECT DISTINCT department FROM students ORDER BY department')->fetchAll(PDO::FETCH_COLUMN);

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Add Result Modal -->
<div class="modal fade" id="addResultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= e(url('admin/results.php')) ?>">
                <input type="hidden" name="action" value="add">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold">Add Result Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-medium small">Student <span class="text-danger">*</span></label>
                            <select class="form-select" name="student_id" required>
                                <option value="">— Select student —</option>
                                <?php foreach ($allStudents as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['matric_no']) ?> — <?= e($s['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Semester</label>
                            <select class="form-select" name="semester">
                                <option value="First">First</option>
                                <option value="Second">Second</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Session</label>
                            <input type="text" class="form-control" name="session" value="2023/2024" placeholder="2023/2024">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-medium small">Course Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" name="course_code" placeholder="CSC 301" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-medium small">Course Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="course_title" placeholder="Data Structures…" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Credit Units</label>
                            <select class="form-select" name="credit_unit">
                                <?php foreach ([1,2,3,4,6] as $u): ?>
                                <option value="<?= $u ?>" <?= $u === 3 ? 'selected' : '' ?>><?= $u ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Score (0–100) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="score" min="0" max="100" step="0.01" required>
                            <div class="form-text">Grade computed automatically.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Result</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Filter bar -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="<?= e(url('admin/results.php')) ?>" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label form-label-sm mb-1">Semester</label>
                <select class="form-select form-select-sm" name="semester">
                    <option value="">All Semesters</option>
                    <option value="First"  <?= $filterSemester === 'First'  ? 'selected' : '' ?>>First</option>
                    <option value="Second" <?= $filterSemester === 'Second' ? 'selected' : '' ?>>Second</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label form-label-sm mb-1">Session</label>
                <select class="form-select form-select-sm" name="session">
                    <option value="">All Sessions</option>
                    <?php foreach ($sessions as $sess): ?>
                    <option value="<?= e($sess) ?>" <?= $filterSession === $sess ? 'selected' : '' ?>><?= e($sess) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label form-label-sm mb-1">Department</label>
                <select class="form-select form-select-sm" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($depts as $d): ?>
                    <option value="<?= e($d) ?>" <?= $filterDept === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary">Filter</button>
                <a href="<?= e(url('admin/results.php')) ?>" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addResultModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Result
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Results Table -->
<div class="table-wrapper">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Dept</th>
                    <th>Semester / Session</th>
                    <th>Course</th>
                    <th>Units</th>
                    <th>Score</th>
                    <th>Grade</th>
                    <th>GP</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No results found for the selected filters.</td></tr>
                <?php else: ?>
                <?php foreach ($results as $r): ?>
                <tr>
                    <td>
                        <div class="fw-medium small"><?= e($r['full_name']) ?></div>
                        <div class="text-muted" style="font-size:.75rem"><?= e($r['matric_no']) ?></div>
                    </td>
                    <td class="small text-muted"><?= e($r['department']) ?></td>
                    <td class="small"><?= e($r['semester']) ?> / <?= e($r['session']) ?></td>
                    <td>
                        <div class="fw-medium small"><?= e($r['course_code']) ?></div>
                        <div class="text-muted" style="font-size:.72rem"><?= e($r['course_title']) ?></div>
                    </td>
                    <td><?= (int)$r['credit_unit'] ?></td>
                    <td><?= number_format((float)$r['score'], 1) ?></td>
                    <td><span class="grade-badge grade-<?= e($r['grade']) ?>"><?= e($r['grade']) ?></span></td>
                    <td><?= number_format((float)$r['grade_point'], 1) ?></td>
                    <td>
                        <form method="POST" action="<?= e(url('admin/results.php')) ?>" style="display:inline"
                              onsubmit="return confirm('Delete this result entry?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger p-1">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-3 py-2 bg-light border-top text-muted small">
        <?= count($results) ?> result <?= count($results) !== 1 ? 'entries' : 'entry' ?> found.
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
