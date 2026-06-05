<?php
require_once __DIR__ . '/includes/db.php';

$clubId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM clubs WHERE id = :id');
$stmt->execute(['id' => $clubId]);
$club = $stmt->fetch();

if (!$club) {
    redirect_with_alert('register_club.php', 'error', 'Club not found.');
}

$stmt = $pdo->prepare('SELECT * FROM members WHERE club_id = :club_id ORDER BY role, full_name');
$stmt->execute(['club_id' => $clubId]);
$members = $stmt->fetchAll();

$byRole = [
    'President' => [],
    'Assistant President' => [],
    'Facilitator' => [],
    'Member' => [],
];
foreach ($members as $member) {
    $byRole[$member['role']][] = $member;
}

$pageTitle = $club['club_name'];
require_once __DIR__ . '/includes/header.php';
?>
<div class="stack">
    <div class="grid-cards">
        <?php foreach (['President', 'Assistant President', 'Facilitator'] as $role): ?>
            <div class="card">
                <h2 class="section-title"><?= e($role) ?></h2>
                <?php if ($byRole[$role]): ?>
                    <?php foreach ($byRole[$role] as $person): ?>
                        <p><strong><?= e($person['full_name']) ?></strong><br><?= e($person['phone_number']) ?></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-slate-500">No <?= e(strtolower($role)) ?> assigned.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h2 class="section-title">Members Table</h2>
        <div class="table-wrap">
            <table class="datatable plain-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Level</th>
                    <th>Intake</th>
                    <th>Group</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($members as $row): ?>
                    <tr>
                        <td><?= e($row['full_name']) ?></td>
                        <td><?= e($row['phone_number']) ?></td>
                        <td><?= e($row['role']) ?></td>
                        <td><?= e($row['level']) ?></td>
                        <td><?= e($row['intake']) ?></td>
                        <td><?= e($row['group']) ?></td>
                        <td><?= e($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
