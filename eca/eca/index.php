<?php
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Dashboard';
$totalClubs = (int) $pdo->query('SELECT COUNT(*) FROM clubs')->fetchColumn();
$totalMembers = (int) $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
$todayAttendance = (int) $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE()")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>
<div class="stack">
    <div class="grid-cards">
        <div class="card stat-card">
            <p class="eyebrow">Total Clubs</p>
            <div class="value"><?= $totalClubs ?></div>
        </div>
        <div class="card stat-card">
            <p class="eyebrow">Total Members</p>
            <div class="value"><?= $totalMembers ?></div>
        </div>
        <div class="card stat-card">
            <p class="eyebrow">Today's Attendance</p>
            <div class="value"><?= $todayAttendance ?></div>
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Quick Links</h2>
        <div class="actions">
            <a class="btn btn-primary" href="register_club.php"><i class="fa-solid fa-building-columns"></i> Register Club</a>
            <a class="btn btn-primary" href="register_member.php"><i class="fa-solid fa-user-plus"></i> Register Member</a>
            <a class="btn btn-secondary" href="events.php"><i class="fa-solid fa-calendar-days"></i> Manage Events</a>
            <a class="btn btn-secondary" href="sports_houses.php"><i class="fa-solid fa-shield-halved"></i> Sports Houses</a>
            <a class="btn btn-light" href="student_login.php"><i class="fa-solid fa-right-to-bracket"></i> Student Login</a>
            <a class="btn btn-secondary" href="attendance.php"><i class="fa-solid fa-clipboard-check"></i> Take Attendance</a>
            <a class="btn btn-light" href="attendance_report.php"><i class="fa-solid fa-chart-column"></i> View Reports</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
