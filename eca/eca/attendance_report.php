<?php
require_once __DIR__ . '/includes/db.php';

$filters = [
    'club_id' => $_GET['club_id'] ?? '',
    'member_id' => $_GET['member_id'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
];

$where = [];
$params = [];

if ($filters['club_id'] !== '') {
    $where[] = 'a.club_id = :club_id';
    $params['club_id'] = (int) $filters['club_id'];
}
if ($filters['member_id'] !== '') {
    $where[] = 'a.member_id = :member_id';
    $params['member_id'] = (int) $filters['member_id'];
}
if ($filters['date_from'] !== '') {
    $where[] = 'a.date >= :date_from';
    $params['date_from'] = $filters['date_from'];
}
if ($filters['date_to'] !== '') {
    $where[] = 'a.date <= :date_to';
    $params['date_to'] = $filters['date_to'];
}

$baseSql = 'FROM attendance a INNER JOIN members m ON m.id = a.member_id INNER JOIN clubs c ON c.id = a.club_id';
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT a.*, m.full_name, c.club_name {$baseSql}{$whereSql} ORDER BY a.date DESC, c.club_name, m.full_name");
$stmt->execute($params);
$records = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT m.full_name,
        SUM(a.status = 'Present') AS total_present,
        SUM(a.status = 'Absent') AS total_absent,
        SUM(a.status = 'Late') AS total_late
     {$baseSql}{$whereSql}
     GROUP BY a.member_id, m.full_name
     ORDER BY m.full_name"
);
$stmt->execute($params);
$summary = $stmt->fetchAll();

$clubs = $pdo->query('SELECT id, club_name FROM clubs ORDER BY club_name')->fetchAll();
$members = $pdo->query('SELECT id, full_name FROM members ORDER BY full_name')->fetchAll();

$pageTitle = 'Attendance Report';
require_once __DIR__ . '/includes/header.php';
?>
<div class="stack">
    <div class="card">
        <h2 class="section-title">Report Filters</h2>
        <form method="get">
            <div class="form-grid">
                <div>
                    <label for="club_id">Club</label>
                    <select id="club_id" name="club_id">
                        <option value="">All clubs</option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?= (int) $club['id'] ?>" <?= (string) $filters['club_id'] === (string) $club['id'] ? 'selected' : '' ?>><?= e($club['club_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="member_id">Member</label>
                    <select id="member_id" name="member_id">
                        <option value="">All members</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?= (int) $member['id'] ?>" <?= (string) $filters['member_id'] === (string) $member['id'] ? 'selected' : '' ?>><?= e($member['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label for="date_from">Date From</label><input id="date_from" name="date_from" type="date" value="<?= e($filters['date_from']) ?>"></div>
                <div><label for="date_to">Date To</label><input id="date_to" name="date_to" type="date" value="<?= e($filters['date_to']) ?>"></div>
            </div>
            <div class="mt-4 actions">
                <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Apply Filters</button>
                <a class="btn btn-light" href="attendance_report.php"><i class="fa-solid fa-rotate-left"></i> Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 class="section-title">Per-Member Summary</h2>
        <div class="table-wrap">
            <table class="plain-table">
                <thead>
                <tr>
                    <th>Member</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Late</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($summary as $row): ?>
                    <tr>
                        <td><?= e($row['full_name']) ?></td>
                        <td><?= (int) $row['total_present'] ?></td>
                        <td><?= (int) $row['total_absent'] ?></td>
                        <td><?= (int) $row['total_late'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2 class="section-title">Attendance Records</h2>
        <div class="table-wrap">
            <table class="datatable plain-table">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Club</th>
                    <th>Member</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td><?= e($record['date']) ?></td>
                        <td><?= e($record['club_name']) ?></td>
                        <td><?= e($record['full_name']) ?></td>
                        <td><?= e($record['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
