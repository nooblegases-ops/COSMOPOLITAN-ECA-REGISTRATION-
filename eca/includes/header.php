<?php
$pageTitle = $pageTitle ?? 'Club Attendance System';
$currentPage = basename($_SERVER['PHP_SELF']);

$navItems = [
    ['href' => 'index.php', 'icon' => 'fa-gauge-high', 'label' => 'Dashboard'],
    ['href' => 'register_club.php', 'icon' => 'fa-building-columns', 'label' => 'Register Club'],
    ['href' => 'register_member.php', 'icon' => 'fa-user-plus', 'label' => 'Register Member'],
    ['href' => 'members.php', 'icon' => 'fa-users', 'label' => 'Members'],
    ['href' => 'attendance.php', 'icon' => 'fa-clipboard-check', 'label' => 'Attendance'],
    ['href' => 'attendance_report.php', 'icon' => 'fa-chart-column', 'label' => 'Reports'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <span class="brand-mark">CA</span>
            <span>Club Attendance</span>
        </div>
        <nav class="nav-list">
            <?php foreach ($navItems as $item): ?>
                <a class="nav-link <?= $currentPage === $item['href'] ? 'active' : '' ?>" href="<?= e($item['href']) ?>">
                    <i class="fa-solid <?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <div>
                <p class="eyebrow">Management Portal</p>
                <h1><?= e($pageTitle) ?></h1>
            </div>
        </header>
        <section class="content-area">
