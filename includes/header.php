<?php
/**
 * includes/header.php
 * Shared HTML header for all admin pages.
 * Includes Bootstrap 5, Bootstrap Icons, and the custom stylesheet.
 *
 * Variables available to the calling page:
 *   $pageTitle  (string) — used in <title> and the breadcrumb
 */
if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ── Global ─────────────────────────────────── */
        :root {
            --sidebar-width: 240px;
            --sidebar-bg: #1a2332;
            --sidebar-hover: #243447;
            --accent: #3b82f6;
            --accent-light: #eff6ff;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
        }

        /* ── Sidebar ────────────────────────────────── */
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--sidebar-bg);
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            transition: transform .3s ease;
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand {
            padding: 1.5rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-brand h6 {
            color: #fff;
            font-weight: 700;
            font-size: .85rem;
            letter-spacing: .05em;
            text-transform: uppercase;
            margin: 0;
        }
        .sidebar-brand small {
            color: #94a3b8;
            font-size: .7rem;
        }
        .sidebar-nav {
            padding: .75rem 0;
            flex: 1;
        }
        .sidebar-nav .nav-label {
            padding: .5rem 1.25rem .25rem;
            font-size: .65rem;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #64748b;
        }
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .55rem 1.25rem;
            color: #94a3b8;
            border-radius: 0;
            font-size: .875rem;
            transition: all .15s;
        }
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            background: var(--sidebar-hover);
            color: #fff;
        }
        .sidebar-nav .nav-link.active {
            border-left: 3px solid var(--accent);
        }
        .sidebar-nav .nav-link i { font-size: 1rem; }
        .sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-footer small { color: #64748b; font-size: .7rem; }

        /* ── Main content ───────────────────────────── */
        #main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .top-nav {
            background: #fff;
            padding: .75rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .top-nav .page-title {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
        }
        .content-area {
            padding: 1.75rem;
            flex: 1;
        }

        /* ── Cards ──────────────────────────────────── */
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.07);
        }
        .stat-card .icon-box {
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
        }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,.07); border-radius: 12px; }

        /* ── Tables ─────────────────────────────────── */
        .table-wrapper {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,.07);
        }
        .table thead th {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
        }
        .table tbody tr:hover { background: #f8fafc; }

        /* ── Badges ─────────────────────────────────── */
        .grade-badge { font-size: .75rem; font-weight: 600; padding: .3rem .6rem; border-radius: 6px; }
        .grade-A { background: #dcfce7; color: #166534; }
        .grade-B { background: #dbeafe; color: #1e40af; }
        .grade-C { background: #fef9c3; color: #854d0e; }
        .grade-D { background: #ffedd5; color: #9a3412; }
        .grade-E { background: #fce7f3; color: #9d174d; }
        .grade-F { background: #fee2e2; color: #991b1b; }

        /* ── Alerts ─────────────────────────────────── */
        .alert { border-radius: 10px; border: none; }

        /* ── Mobile ─────────────────────────────────── */
        @media (max-width: 767.98px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #main { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- ══════════════════════════ SIDEBAR ══════════════════════════ -->
<nav id="sidebar">
    <div class="sidebar-brand">
        <h6><i class="bi bi-mortarboard-fill me-2" style="color:#3b82f6"></i>FULafia</h6>
        <small>Result Dissemination System</small>
    </div>

    <div class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a href="<?= e(url('admin/dashboard.php')) ?>" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a href="<?= e(url('admin/students.php')) ?>" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'students.php' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Students
        </a>
        <a href="<?= e(url('admin/results.php')) ?>" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'results.php' ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i> Results
        </a>

        <div class="nav-label mt-2">Operations</div>
        <a href="<?= e(url('admin/upload.php')) ?>" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'upload.php' ? 'active' : '' ?>">
            <i class="bi bi-cloud-upload"></i> Upload CSV/Excel
        </a>
        <a href="<?= e(url('admin/send-results.php')) ?>" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'send-results.php' ? 'active' : '' ?>">
            <i class="bi bi-send"></i> Send Results via SMS
        </a>

        <div class="nav-label mt-2">System</div>
        <a href="<?= e(url('')) ?>" class="nav-link" target="_blank">
            <i class="bi bi-box-arrow-up-right"></i> Student Portal
        </a>
        <a href="<?= e(url('admin/logout.php')) ?>" class="nav-link text-danger">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </div>

    <div class="sidebar-footer">
        <small><?= e(APP_NAME) ?> v<?= e(APP_VERSION) ?></small>
    </div>
</nav>

<!-- ══════════════════════════ MAIN ══════════════════════════════ -->
<div id="main">
    <div class="top-nav">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-outline-secondary d-md-none" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <span class="page-title"><?= e($pageTitle) ?></span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small d-none d-sm-inline">
                <i class="bi bi-person-circle me-1"></i>
                <?= e($_SESSION['admin_name'] ?? 'Admin') ?>
            </span>
        </div>
    </div>

    <div class="content-area">

        <?php
        // Display flash message if any
        $flash = getFlash();
        if ($flash):
            $alertClass = $flash['type'] === 'success' ? 'alert-success' : ($flash['type'] === 'error' ? 'alert-danger' : 'alert-info');
        ?>
        <div class="alert <?= $alertClass ?> alert-dismissible fade show" role="alert">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
