<?php
/**
 * index.php — Student Pull Portal
 * Students enter their matric number to have their results sent via SMS.
 * This is the public-facing page (no login required).
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$error   = '';
$success = '';
$student = null;

// Handle form submission (Pull SMS request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricNo = post('matric_no');
    $semester  = post('semester');
    $session   = post('session');

    if (empty($matricNo)) {
        $error = 'Please enter your matriculation number.';
    } elseif (!in_array($semester, ['First', 'Second'], true)) {
        $error = 'Please select a valid semester.';
    } else {
        // Look up the student
        $student = findStudentByMatric($matricNo);

        if (!$student) {
            $error = 'Matriculation number not found. Please check and try again.';
        } else {
            // Fetch the student's results for the chosen semester/session
            $results = getStudentResults((int)$student['id'], $semester, $session);

            if (empty($results)) {
                $error = 'No results found for the selected semester/session. Please try a different combination.';
            } else {
                // Build and send the SMS
                $message  = buildResultSMS($student, $results, $semester, $session);
                $outcome  = sendSMS((int)$student['id'], $student['phone_number'], $message);

                if ($outcome['success']) {
                    $success = 'Your results have been sent to your registered phone number ending in '
                             . substr($student['phone_number'], -4) . '. '
                             . 'Please check your SMS inbox.';
                } else {
                    // SMS could not be sent — show results on screen instead
                    $error = 'SMS could not be sent at this time (' . $outcome['message'] . '). '
                           . 'Your results are shown below.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Result Portal — <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex; flex-direction: column; justify-content: center;
        }
        .hero-card {
            max-width: 520px;
            margin: 2rem auto;
            background: rgba(255,255,255,.97);
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0,0,0,.4);
            overflow: hidden;
        }
        .hero-header {
            background: linear-gradient(135deg, #1a2332, #243447);
            padding: 2.5rem 2rem 2rem;
            text-align: center;
        }
        .hero-header .emblem {
            width: 70px; height: 70px;
            background: rgba(59,130,246,.2);
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 1rem;
        }
        .hero-header h1 { font-size: 1.25rem; font-weight: 700; color: #fff; margin: 0; }
        .hero-header p  { color: #94a3b8; font-size: .85rem; margin: .25rem 0 0; }
        .hero-body { padding: 2rem; }
        .form-label { font-weight: 500; font-size: .875rem; color: #374151; }
        .form-control, .form-select {
            border-radius: 8px;
            border-color: #e2e8f0;
            padding: .65rem .9rem;
            font-size: .9rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.15);
        }
        .btn-primary {
            background: #3b82f6; border: none;
            border-radius: 8px; padding: .7rem;
            font-weight: 600; font-size: .9rem;
        }
        .btn-primary:hover { background: #2563eb; }
        .result-table th { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; }
        .admin-link { text-align: center; padding: 1rem; background: #f8fafc; border-top: 1px solid #e2e8f0; }
        .admin-link a { color: #64748b; font-size: .8rem; text-decoration: none; }
        .admin-link a:hover { color: #3b82f6; }
        .grade-badge { padding: .2rem .5rem; border-radius: 5px; font-size: .75rem; font-weight: 600; }
        .grade-A { background: #dcfce7; color: #166534; }
        .grade-B { background: #dbeafe; color: #1e40af; }
        .grade-C { background: #fef9c3; color: #854d0e; }
        .grade-D,.grade-E { background: #ffedd5; color: #9a3412; }
        .grade-F { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
<div class="container">
    <div class="hero-card">
        <!-- Header -->
        <div class="hero-header">
            <div class="emblem">
                <i class="bi bi-mortarboard-fill text-primary fs-2"></i>
            </div>
            <h1><?= e(APP_INST) ?></h1>
            <p>Student Academic Result Portal &mdash; Enter your matric number to receive your results via SMS</p>
        </div>

        <!-- Form -->
        <div class="hero-body">
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?>
            </div>
            <?php else: ?>
            <form method="POST" action="<?= e(url('index.php')) ?>" novalidate>
                <div class="mb-3">
                    <label class="form-label" for="matric_no">Matriculation Number</label>
                    <input type="text" class="form-control" id="matric_no" name="matric_no"
                           placeholder="e.g. CSC/2021/001"
                           value="<?= e(post('matric_no')) ?>" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label" for="semester">Semester</label>
                        <select class="form-select" id="semester" name="semester">
                            <option value="First"  <?= post('semester') === 'First'  ? 'selected' : '' ?>>First</option>
                            <option value="Second" <?= post('semester') === 'Second' ? 'selected' : '' ?>>Second</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="session">Academic Session</label>
                        <input type="text" class="form-control" id="session" name="session"
                               placeholder="e.g. 2023/2024"
                               value="<?= e(post('session', '2023/2024')) ?>">
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send-fill me-2"></i>Receive Results via SMS
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <?php
            // If SMS failed, show the results on screen as a fallback
            if ($error && $student && isset($results) && !empty($results)):
                $gpa = calculateGPA($results);
            ?>
            <hr>
            <h6 class="fw-semibold mb-3">
                Results for <?= e($student['full_name']) ?> (<?= e($student['matric_no']) ?>)
            </h6>
            <div class="table-responsive">
                <table class="table table-sm result-table">
                    <thead class="table-light">
                        <tr>
                            <th>Course</th>
                            <th>Units</th>
                            <th>Grade</th>
                            <th>GP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $r): ?>
                        <tr>
                            <td>
                                <strong><?= e($r['course_code']) ?></strong><br>
                                <small class="text-muted"><?= e($r['course_title']) ?></small>
                            </td>
                            <td><?= (int)$r['credit_unit'] ?></td>
                            <td><span class="grade-badge grade-<?= e($r['grade']) ?>"><?= e($r['grade']) ?></span></td>
                            <td><?= number_format((float)$r['grade_point'], 1) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-semibold">
                            <td colspan="3">Semester GPA</td>
                            <td><?= number_format($gpa, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="admin-link">
            <a href="<?= e(url('admin/login.php')) ?>"><i class="bi bi-shield-lock me-1"></i>Staff / Admin Login</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
