<?php

/**
 * config.php
 * Central configuration for the Student Academic Result Dissemination System
 * Federal University of Lafia — BSc Final Year Project
 */

// ─── Base URL (works in XAMPP whether the app is in /htdocs or /htdocs/<folder>) ───
$baseUrl = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$baseUrl = preg_replace('#/admin$#', '', $baseUrl);
$baseUrl = $baseUrl === '/' ? '' : rtrim($baseUrl, '/');
define('BASE_URL', $baseUrl);

// ─── Database: MySQL (XAMPP) ───────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'student_results');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// ─── Load .env file manually if it exists ────────────────────────────────────
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

// ─── Twilio SMS Configuration ──────────────────────────────────────────────
// Now safely fetching from .env file!
// define('TWILIO_SID',   getenv('TWILIO_SID')   ?: '');
// define('TWILIO_TOKEN', getenv('TWILIO_TOKEN') ?: '');
// define('TWILIO_PHONE', getenv('TWILIO_PHONE') ?: '');

// ─── Infobip SMS Configuration ─────────────────────────────────────────────
define('INFOBIP_BASE_URL', getenv('INFOBIP_BASE_URL') ?: '');
define('INFOBIP_API_KEY',  getenv('INFOBIP_API_KEY')  ?: '');
define('INFOBIP_SENDER',   getenv('INFOBIP_SENDER')   ?: 'FULafia');

// ─── Application Constants ─────────────────────────────────────────────────
define('APP_NAME',    'FULafia Result System');
define('APP_VERSION', '1.0.0');
define('APP_INST',    'Federal University of Lafia');

// ─── Grade Scale (Nigerian University 5-point system) ──────────────────────
define('GRADE_SCALE', [
    ['min' => 70, 'max' => 100, 'grade' => 'A', 'point' => 5.0],
    ['min' => 60, 'max' => 69,  'grade' => 'B', 'point' => 4.0],
    ['min' => 50, 'max' => 59,  'grade' => 'C', 'point' => 3.0],
    ['min' => 45, 'max' => 49,  'grade' => 'D', 'point' => 2.0],
    ['min' => 40, 'max' => 44,  'grade' => 'E', 'point' => 1.0],
    ['min' => 0,  'max' => 39,  'grade' => 'F', 'point' => 0.0],
]);

// ─── PHP Session Security (only configurable before session starts) ────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
}
