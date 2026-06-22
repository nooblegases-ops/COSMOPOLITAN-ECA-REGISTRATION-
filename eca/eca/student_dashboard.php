<?php
require_once __DIR__ . '/includes/db.php';

require_student_login();

$studentId = (int) ($_SESSION['eca_student_id'] ?? 0);
$stmt = $pdo->prepare('
    SELECT m.*, c.club_name
    FROM members m
    INNER JOIN clubs c ON c.id = m.club_id
    WHERE m.id = :id
        AND m.status = "Active"
    LIMIT 1
');
$stmt->execute(['id' => $studentId]);
$student = $stmt->fetch();

if (!$student) {
    unset($_SESSION['eca_student_id'], $_SESSION['eca_student_name']);
    redirect_with_alert('student_login.php', 'warning', 'Please log in again.');
}

$stmt = $pdo->prepare('
    SELECT full_name, phone_number, photo_path
    FROM members
    WHERE club_id = :club_id
        AND role = "President"
        AND status = "Active"
    LIMIT 1
');
$stmt->execute(['club_id' => (int) $student['club_id']]);
$clubPresident = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT
        SUM(status = 'Present') AS total_present,
        SUM(status = 'Absent') AS total_absent,
        SUM(status = 'Late') AS total_late
    FROM attendance
    WHERE member_id = :member_id
");
$stmt->execute(['member_id' => $studentId]);
$attendanceSummary = $stmt->fetch() ?: ['total_present' => 0, 'total_absent' => 0, 'total_late' => 0];

$stmt = $pdo->prepare('
    SELECT date, status
    FROM attendance
    WHERE member_id = :member_id
    ORDER BY date DESC
    LIMIT 8
');
$stmt->execute(['member_id' => $studentId]);
$recentAttendance = $stmt->fetchAll();

$stmt = $pdo->prepare('
    SELECT event_name, event_date, start_time, location, latitude, longitude
    FROM events
    WHERE club_id = :club_id
        AND event_date >= CURDATE()
    ORDER BY event_date ASC, start_time ASC
    LIMIT 5
');
$stmt->execute(['club_id' => (int) $student['club_id']]);
$upcomingEvents = $stmt->fetchAll();

function student_dashboard_whatsapp_link(string $phoneNumber): string
{
    $digits = preg_replace('/\D+/', '', $phoneNumber);

    if (str_starts_with($digits, '0')) {
        $digits = '673' . substr($digits, 1);
    }

    return 'https://wa.me/' . $digits;
}

function student_event_map_url(array $event): string
{
    $query = !empty($event['latitude']) && !empty($event['longitude'])
        ? $event['latitude'] . ',' . $event['longitude']
        : $event['location'];

    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($query);
}

$pageTitle = 'Student Dashboard';
$portalEyebrow = 'Student Portal';
require_once __DIR__ . '/includes/header.php';
?>
<div class="stack">
    <div class="grid-cards">
        <div class="card student-profile-card">
            <div class="student-profile-main">
                <span class="member-photo student-profile-photo">
                    <?php if (!empty($student['photo_path'])): ?>
                        <img src="<?= e($student['photo_path']) ?>" alt="<?= e($student['full_name']) ?>">
                    <?php else: ?>
                        <i class="fa-solid fa-user-graduate"></i>
                    <?php endif; ?>
                </span>
                <div>
                    <p class="eyebrow">Logged In Student</p>
                    <h2><?= e($student['full_name']) ?></h2>
                    <span class="badge <?= e('badge-' . strtolower(str_replace(' ', '-', $student['role']))) ?>"><?= e($student['role']) ?></span>
                </div>
            </div>
            <div class="student-result">
                <div class="detail-row">
                    <span>Club</span>
                    <strong><?= e($student['club_name']) ?></strong>
                </div>
                <div class="detail-row">
                    <span>Level</span>
                    <strong><?= e($student['level']) ?></strong>
                </div>
                <div class="detail-row">
                    <span>Intake</span>
                    <strong><?= e($student['intake']) ?></strong>
                </div>
                <div class="detail-row">
                    <span>Group</span>
                    <strong><?= e($student['group']) ?></strong>
                </div>
                <div class="detail-row">
                    <span>Sports House</span>
                    <strong><?= e($student['sports_house'] ?? '-') ?></strong>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="section-title">Club President</h2>
            <?php if ($clubPresident): ?>
                <div class="president-item">
                    <span class="member-photo">
                        <?php if (!empty($clubPresident['photo_path'])): ?>
                            <img src="<?= e($clubPresident['photo_path']) ?>" alt="<?= e($clubPresident['full_name']) ?>">
                        <?php else: ?>
                            <i class="fa-solid fa-user-tie"></i>
                        <?php endif; ?>
                    </span>
                    <div>
                        <strong><?= e($clubPresident['full_name']) ?></strong>
                        <a class="phone-link" href="<?= e(student_dashboard_whatsapp_link($clubPresident['phone_number'])) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> <?= e($clubPresident['phone_number']) ?></a>
                    </div>
                </div>
            <?php else: ?>
                <p class="empty-text">No active president assigned yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid-cards">
        <div class="card stat-card">
            <p class="eyebrow">Present</p>
            <div class="value"><?= (int) $attendanceSummary['total_present'] ?></div>
        </div>
        <div class="card stat-card">
            <p class="eyebrow">Absent</p>
            <div class="value"><?= (int) $attendanceSummary['total_absent'] ?></div>
        </div>
        <div class="card stat-card">
            <p class="eyebrow">Late</p>
            <div class="value"><?= (int) $attendanceSummary['total_late'] ?></div>
        </div>
    </div>

    <div class="grid-cards">
        <div class="card">
            <h2 class="section-title">Recent Attendance</h2>
            <?php if ($recentAttendance): ?>
                <div class="table-wrap">
                    <table class="plain-table">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentAttendance as $record): ?>
                            <tr>
                                <td><?= e($record['date']) ?></td>
                                <td><?= e($record['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-text">No attendance records yet.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 class="section-title">Upcoming Events</h2>
            <div class="student-event-list">
                <?php foreach ($upcomingEvents as $event): ?>
                    <div class="student-event-item">
                        <div>
                            <strong><?= e($event['event_name']) ?></strong>
                            <span><?= e($event['event_date']) ?><?= $event['start_time'] ? ' at ' . e(substr($event['start_time'], 0, 5)) : '' ?></span>
                            <span><i class="fa-solid fa-location-dot"></i> <?= e($event['location']) ?></span>
                        </div>
                        <a class="btn btn-light" href="<?= e(student_event_map_url($event)) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-map-location-dot"></i> Map</a>
                    </div>
                <?php endforeach; ?>
                <?php if (!$upcomingEvents): ?>
                    <p class="empty-text">No upcoming events for your club.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="actions">
        <a class="btn btn-light" href="student_logout.php"><i class="fa-solid fa-right-from-bracket"></i> Student Logout</a>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
