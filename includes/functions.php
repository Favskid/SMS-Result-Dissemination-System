<?php
/**
 * includes/functions.php
 * Shared helper functions used across the entire application.
 * Topics covered: input sanitisation, grade calculation, GPA/CGPA, SMS formatting.
 */

require_once __DIR__ . '/db.php';

// ─── Input Helpers ────────────────────────────────────────────────────────

function url(string $path = ''): string
{
    $base = BASE_URL;
    $path = ltrim($path, '/');

    if ($base === '') {
        return '/' . $path;
    }

    return $path === '' ? $base . '/' : $base . '/' . $path;
}

/**
 * Sanitise a plain text string for safe display in HTML.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Return a value from $_POST, falling back to $default.
 * Trims whitespace automatically.
 */
function post(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

/**
 * Return a value from $_GET, falling back to $default.
 */
function get(string $key, string $default = ''): string
{
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

// ─── Flash Messages ───────────────────────────────────────────────────────

/**
 * Store a flash message that will be displayed once on the next page load.
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear the flash message (returns null if none).
 */
function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ─── Grade Utilities ──────────────────────────────────────────────────────

/**
 * Convert a numeric score (0–100) to a letter grade and grade point.
 * Uses the Nigerian university 5-point scale defined in config.php.
 *
 * @return array{grade: string, point: float}
 */
function scoreToGrade(float $score): array
{
    foreach (GRADE_SCALE as $scale) {
        if ($score >= $scale['min'] && $score <= $scale['max']) {
            return ['grade' => $scale['grade'], 'point' => $scale['point']];
        }
    }
    return ['grade' => 'F', 'point' => 0.0]; // Fallback
}

/**
 * Calculate GPA for an array of result rows.
 * Each row must have 'credit_unit' and 'grade_point' keys.
 *
 * Formula: GPA = Σ(credit_unit × grade_point) / Σ(credit_unit)
 */
function calculateGPA(array $results): float
{
    $totalQualityPoints = 0.0;
    $totalUnits         = 0;

    foreach ($results as $row) {
        $totalQualityPoints += (float)$row['credit_unit'] * (float)$row['grade_point'];
        $totalUnits         += (int)$row['credit_unit'];
    }

    return $totalUnits > 0 ? round($totalQualityPoints / $totalUnits, 2) : 0.0;
}

// ─── Student / Result Fetchers ────────────────────────────────────────────

/**
 * Look up a student by their matriculation number.
 * Returns the student row array or null if not found.
 */
function findStudentByMatric(string $matricNo): ?array
{
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM students WHERE matric_no = ? LIMIT 1');
    $stmt->execute([$matricNo]);
    $row  = $stmt->fetch();
    return $row ?: null;
}

/**
 * Fetch all result rows for a student, optionally filtered by semester and session.
 */
function getStudentResults(int $studentId, string $semester = '', string $session = ''): array
{
    $db  = getDB();
    $sql = 'SELECT * FROM results WHERE student_id = ?';
    $params = [$studentId];

    if ($semester !== '') {
        $sql .= ' AND semester = ?';
        $params[] = $semester;
    }
    if ($session !== '') {
        $sql .= ' AND session = ?';
        $params[] = $session;
    }

    $sql .= ' ORDER BY semester, course_code';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ─── SMS Helpers ──────────────────────────────────────────────────────────

/**
 * Build the formatted SMS body for a student's semester results.
 *
 * Sample output:
 * Dear Aminu, Your First Semester 2023/2024 Results:
 * CSC 301: A (3 units)
 * CSC 303: B (3 units)
 * GPA: 4.43 | Total Units: 12
 * - FULafia Result System
 */
function buildResultSMS(array $student, array $results, string $semester, string $session): string
{
    $firstName = explode(' ', $student['full_name'])[0];
    $gpa       = calculateGPA($results);
    $totalUnits = array_sum(array_column($results, 'credit_unit'));

    $lines   = [];
    $lines[] = "Dear {$firstName}, Your {$semester} Semester {$session} Results:";

    foreach ($results as $r) {
        $lines[] = "{$r['course_code']}: {$r['grade']} ({$r['credit_unit']} units)";
    }

    $lines[] = "GPA: {$gpa} | Total Units: {$totalUnits}";
    $lines[] = "- " . APP_NAME;

    return implode("\n", $lines);
}

/**
 * Send an SMS via Twilio and log the outcome in the sms_logs table.
 *
 * @return array{success: bool, message: string}
 */
function sendSMS(int $studentId, string $toPhone, string $body): array
{
    // Guard: check Twilio credentials are configured
    if (empty(TWILIO_SID) || empty(TWILIO_TOKEN) || empty(TWILIO_PHONE)) {
        logSMS($studentId, $body, 'failed', null);
        return ['success' => false, 'message' => 'Twilio credentials not configured. Set TWILIO_SID, TWILIO_TOKEN, and TWILIO_PHONE in your environment or config.php.'];
    }

    // Ensure Composer autoloader is loaded
    $autoloader = dirname(__DIR__) . '/vendor/autoload.php';
    if (!file_exists($autoloader)) {
        return ['success' => false, 'message' => 'Composer vendor directory not found. Run: composer install'];
    }
    require_once $autoloader;

    try {
        $twilio = new \Twilio\Rest\Client(TWILIO_SID, TWILIO_TOKEN);

        $msg = $twilio->messages->create(
            $toPhone,                     // Destination number (E.164 format)
            ['from' => TWILIO_PHONE, 'body' => $body]
        );

        logSMS($studentId, $body, 'sent', $msg->sid);
        return ['success' => true, 'message' => 'SMS sent successfully (SID: ' . $msg->sid . ')'];

    } catch (\Exception $e) {
        logSMS($studentId, $body, 'failed', null);
        return ['success' => false, 'message' => 'Twilio error: ' . $e->getMessage()];
    }
}

/**
 * Insert an entry into the sms_logs table.
 * Called automatically by sendSMS(); you do not need to call this directly.
 */
function logSMS(int $studentId, string $message, string $status, ?string $twilioSid): void
{
    try {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO sms_logs (student_id, message, status, twilio_sid) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$studentId, $message, $status, $twilioSid]);
    } catch (\Exception $e) {
        // Silently fail – we do not want an SMS log failure to break the page
        error_log('SMS log error: ' . $e->getMessage());
    }
}

// ─── CSV / Excel Import ───────────────────────────────────────────────────

/**
 * Parse a CSV file (first row = headers) and return an array of associative rows.
 * Expected columns: matric_no, course_code, course_title, credit_unit, score, semester, session
 */
function parseCSV(string $filePath): array
{
    $rows   = [];
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        return $rows;
    }

    // Read header row
    $headers = fgetcsv($handle);
    if (!$headers) {
        fclose($handle);
        return $rows;
    }

    // Normalise header names (trim + lowercase)
    $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

    while (($line = fgetcsv($handle)) !== false) {
        if (count($line) === count($headers)) {
            $rows[] = array_combine($headers, array_map('trim', $line));
        }
    }

    fclose($handle);
    return $rows;
}
