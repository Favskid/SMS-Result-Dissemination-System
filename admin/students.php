<?php
/**
 * admin/students.php
 * View and manage students in the system.
 */

require_once __DIR__ . '/auth.php';

$db        = getDB();
$pageTitle = 'Students';

// ── Handle add/delete ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = post('action');

    if ($postAction === 'add') {
        $matricNo   = strtoupper(post('matric_no'));
        $fullName   = post('full_name');
        $department = post('department');
        $level      = (int)post('level', '100');
        $email      = post('email');
        $phone      = post('phone_number');

        if (!$matricNo || !$fullName || !$department || !$phone) {
            setFlash('error', 'Please fill in all required fields (Matric No, Name, Dept, Phone).');
        } else {
            // Check if matric no exists
            $stmt = $db->prepare('SELECT id FROM students WHERE matric_no = ? LIMIT 1');
            $stmt->execute([$matricNo]);
            if ($stmt->fetch()) {
                setFlash('error', 'A student with that Matriculation Number already exists.');
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO students (matric_no, full_name, department, level, email, phone_number)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$matricNo, $fullName, $department, $level, $email, $phone]);
                setFlash('success', 'Student added successfully.');
            }
        }
        header('Location: ' . url('admin/students.php'));
        exit;
    }

    if ($postAction === 'delete') {
        $db->prepare('DELETE FROM students WHERE id = ?')->execute([(int)post('id')]);
        setFlash('success', 'Student deleted.');
        header('Location: ' . url('admin/students.php'));
        exit;
    }
}

// ── Fetch Students ─────────────────────────────────────────────────────────
$sql = 'SELECT * FROM students ORDER BY matric_no ASC';
$stmt = $db->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= e(url('admin/students.php')) ?>">
                <input type="hidden" name="action" value="add">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Matric No <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" name="matric_no" placeholder="e.g. CSC/2021/001" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Level</label>
                            <select class="form-select" name="level">
                                <?php foreach ([100, 200, 300, 400, 500] as $lvl): ?>
                                <option value="<?= $lvl ?>"><?= $lvl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium small">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" placeholder="John Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Department <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="department" placeholder="Computer Science" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Phone (E.164) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="phone_number" placeholder="+2348012345678" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium small">Email Address</label>
                            <input type="email" class="form-control" name="email" placeholder="student@example.com">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Page Toolbar -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-semibold mb-0">Student List</h5>
    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="bi bi-person-plus me-1"></i>Add Student
    </button>
</div>

<!-- Students Table -->
<div class="table-wrapper">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Matric No</th>
                    <th>Full Name</th>
                    <th>Department</th>
                    <th>Level</th>
                    <th>Phone Number</th>
                    <th>Email</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No students found.</td></tr>
                <?php else: ?>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td><div class="fw-medium small"><?= e($s['matric_no']) ?></div></td>
                    <td><div class="small"><?= e($s['full_name']) ?></div></td>
                    <td class="small text-muted"><?= e($s['department']) ?></td>
                    <td><?= (int)$s['level'] ?></td>
                    <td class="small"><?= e($s['phone_number']) ?></td>
                    <td class="small"><?= e($s['email'] ?: '-') ?></td>
                    <td>
                        <form method="POST" action="<?= e(url('admin/students.php')) ?>" style="display:inline"
                              onsubmit="return confirm('Delete this student and all their associated results/SMS logs? This cannot be undone.')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
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
        <?= count($students) ?> student<?= count($students) !== 1 ? 's' : '' ?> total.
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
