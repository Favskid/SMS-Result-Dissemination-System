<?php
/**
 * admin/upload.php
 * Bulk result upload via CSV or Excel (.xlsx) file.
 * Uses PhpSpreadsheet to parse Excel files; native fgetcsv for plain CSV.
 *
 * Expected columns (case-insensitive):
 *   matric_no | course_code | course_title | credit_unit | score | semester | session
 */

require_once __DIR__ . '/auth.php';

$db        = getDB();
$pageTitle = 'Upload Results (CSV / Excel)';

$imported   = 0;
$skipped    = 0;
$errors     = [];
$uploadDone = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['result_file'])) {
    $file = $_FILES['result_file'];

    // Validate upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed (error code ' . $file['error'] . ').';
    } else {
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['csv', 'xlsx', 'xls'];

        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Invalid file type. Please upload a CSV or Excel (.xlsx / .xls) file.';
        } else {
            // Move file to uploads directory
            $uploadDir = dirname(__DIR__) . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $tmpPath   = $uploadDir . uniqid('results_', true) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
                $errors[] = 'Could not save the uploaded file. Please check folder permissions for /uploads.';
            }

            // Parse rows
            $rows = [];
            if ($ext === 'csv' && empty($errors)) {
                $rows = parseCSV($tmpPath);
            } elseif (empty($errors)) {
                // Excel — use PhpSpreadsheet if available
                $autoloader = dirname(__DIR__) . '/vendor/autoload.php';
                if (!file_exists($autoloader)) {
                    $errors[] = 'PhpSpreadsheet is not installed. Please run: composer install';
                } else {
                    require_once $autoloader;
                    try {
                        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpPath);
                        $sheet       = $spreadsheet->getActiveSheet();
                        $raw         = $sheet->toArray(null, true, true, false);

                        // First row = headers
                        $headers = array_map(fn($h) => strtolower(trim((string)$h)), $raw[0] ?? []);
                        array_shift($raw);

                        foreach ($raw as $line) {
                            if (count($line) === count($headers)) {
                                $row = array_combine($headers, array_map('trim', $line));
                                if (!empty(array_filter($row))) {
                                    $rows[] = $row;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $errors[] = 'Could not read Excel file: ' . $e->getMessage();
                    }
                }
            }

            // Process rows
            if (!empty($rows) && empty($errors)) {
                $stmtFind   = $db->prepare('SELECT id FROM students WHERE matric_no = ? LIMIT 1');
                $stmtInsert = $db->prepare(
                    'INSERT INTO results (student_id, semester, session, course_code, course_title, credit_unit, score, grade, grade_point)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );

                foreach ($rows as $lineNum => $row) {
                    // Map columns (handle alternative header names)
                    $matricNo    = $row['matric_no']    ?? $row['matric']      ?? '';
                    $courseCode  = strtoupper($row['course_code'] ?? $row['code'] ?? '');
                    $courseTitle = $row['course_title']  ?? $row['title']      ?? '';
                    $creditUnit  = (int)($row['credit_unit'] ?? $row['credit'] ?? 2);
                    $score       = (float)($row['score']      ?? $row['mark']  ?? 0);
                    $semester    = ucfirst(strtolower($row['semester'] ?? 'First'));
                    $session     = $row['session']      ?? '2023/2024';

                    if (empty($matricNo) || empty($courseCode)) {
                        $skipped++;
                        continue;
                    }

                    // Look up student
                    $stmtFind->execute([trim($matricNo)]);
                    $student = $stmtFind->fetch();

                    if (!$student) {
                        $errors[] = "Row " . ($lineNum + 2) . ": Student '{$matricNo}' not found in database. Register student first.";
                        $skipped++;
                        continue;
                    }

                    // Compute grade
                    $gradeInfo = scoreToGrade($score);

                    try {
                        $stmtInsert->execute([
                            $student['id'], $semester, $session,
                            $courseCode, $courseTitle, $creditUnit,
                            $score, $gradeInfo['grade'], $gradeInfo['point']
                        ]);
                        $imported++;
                    } catch (\PDOException $e) {
                        $errors[]  = "Row " . ($lineNum + 2) . ": " . $e->getMessage();
                        $skipped++;
                    }
                }
            }

            // Remove temp file
            @unlink($tmpPath);
            $uploadDone = true;

            if ($imported > 0 && empty($errors)) {
                setFlash('success', "Imported {$imported} result(s) successfully.");
                header('Location: ' . url('admin/results.php'));
                exit;
            }
        }
    }
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="row g-4">
    <!-- Upload form -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-cloud-upload me-2 text-primary"></i>Upload Results File</h6>
            </div>
            <div class="card-body">
                <?php if ($uploadDone && ($imported > 0 || !empty($errors))): ?>
                <div class="alert alert-<?= empty($errors) ? 'success' : 'warning' ?> mb-3">
                    <strong><?= $imported ?> imported</strong>, <?= $skipped ?> skipped.
                    <?php if (!empty($errors)): ?>
                    <ul class="mt-2 mb-0 small">
                        <?php foreach (array_slice($errors, 0, 5) as $err): ?>
                        <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                        <?php if (count($errors) > 5): ?>
                        <li>…and <?= count($errors) - 5 ?> more errors.</li>
                        <?php endif; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= e(url('admin/upload.php')) ?>" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-medium small">Select File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="result_file" accept=".csv,.xlsx,.xls" required>
                        <div class="form-text">Accepted formats: <code>.csv</code>, <code>.xlsx</code>, <code>.xls</code></div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-2"></i>Upload and Import
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>File Format Guide</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Your file must have the following column headers in the first row:</p>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered small mb-3">
                        <thead class="table-light">
                            <tr>
                                <th>Column Name</th>
                                <th>Required</th>
                                <th>Example</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><code>matric_no</code></td><td><span class="badge bg-danger">Yes</span></td><td>CSC/2021/001</td></tr>
                            <tr><td><code>course_code</code></td><td><span class="badge bg-danger">Yes</span></td><td>CSC 301</td></tr>
                            <tr><td><code>course_title</code></td><td><span class="badge bg-secondary">No</span></td><td>Data Structures</td></tr>
                            <tr><td><code>credit_unit</code></td><td><span class="badge bg-secondary">No</span></td><td>3</td></tr>
                            <tr><td><code>score</code></td><td><span class="badge bg-danger">Yes</span></td><td>72.5</td></tr>
                            <tr><td><code>semester</code></td><td><span class="badge bg-secondary">No</span></td><td>First</td></tr>
                            <tr><td><code>session</code></td><td><span class="badge bg-secondary">No</span></td><td>2023/2024</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info small p-2 mb-2">
                    <i class="bi bi-lightbulb me-1"></i>
                    <strong>Tip:</strong> Students must exist in the database before uploading results.
                    Add them on the <a href="<?= e(url('admin/students.php')) ?>">Students page</a> first.
                </div>

                <p class="text-muted small mb-2"><strong>Grade Scale:</strong></p>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge grade-badge grade-A">A ≥ 70</span>
                    <span class="badge grade-badge grade-B">B 60–69</span>
                    <span class="badge grade-badge grade-C">C 50–59</span>
                    <span class="badge grade-badge grade-D">D 45–49</span>
                    <span class="badge grade-badge grade-E">E 40–44</span>
                    <span class="badge grade-badge grade-F">F &lt; 40</span>
                </div>

                <!-- Download sample CSV -->
                <div class="mt-3">
                    <a href="data:text/csv;charset=utf-8,matric_no%2Ccourse_code%2Ccourse_title%2Ccredit_unit%2Cscore%2Csemester%2Csession%0ACSC%2F2021%2F001%2CCSC+301%2CData+Structures%2C3%2C78.5%2CFirst%2C2023%2F2024%0ACSC%2F2021%2F001%2CCSC+303%2CComputer+Architecture%2C3%2C62.0%2CFirst%2C2023%2F2024"
                       download="sample_results.csv" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-download me-1"></i>Download Sample CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
