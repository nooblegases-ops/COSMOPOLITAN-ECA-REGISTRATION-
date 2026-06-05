<?php
require_once __DIR__ . '/includes/db.php';

$selectedClubId = (int) ($_GET['club_id'] ?? $_POST['club_id'] ?? 0);
$selectedDate = $_GET['date'] ?? $_POST['date'] ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO attendance (club_id, member_id, date, status)
             VALUES (:club_id, :member_id, :date, :status)
             ON DUPLICATE KEY UPDATE club_id = VALUES(club_id), status = VALUES(status)'
        );

        foreach ($_POST['attendance'] as $memberId => $status) {
            if (!in_array($status, ['Present', 'Absent', 'Late'], true)) {
                continue;
            }

            $stmt->execute([
                'club_id' => $selectedClubId,
                'member_id' => (int) $memberId,
                'date' => $selectedDate,
                'status' => $status,
            ]);
        }

        $pdo->commit();
        redirect_with_alert("attendance.php?club_id={$selectedClubId}&date={$selectedDate}", 'success', 'Attendance saved successfully.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        redirect_with_alert('attendance.php', 'error', 'Could not save attendance.');
    }
}

$clubs = $pdo->query('SELECT id, club_name FROM clubs ORDER BY club_name')->fetchAll();
$members = [];
$existingAttendance = [];

if ($selectedClubId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM members WHERE club_id = :club_id AND status = "Active" ORDER BY full_name');
    $stmt->execute(['club_id' => $selectedClubId]);
    $members = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT member_id, status FROM attendance WHERE club_id = :club_id AND date = :date');
    $stmt->execute(['club_id' => $selectedClubId, 'date' => $selectedDate]);
    foreach ($stmt->fetchAll() as $row) {
        $existingAttendance[(int) $row['member_id']] = $row['status'];
    }
}

$pageTitle = 'Attendance';
require_once __DIR__ . '/includes/header.php';
?>
<div class="stack">
    <div class="card">
        <h2 class="section-title">Select Club and Date</h2>
        <form method="get">
            <div class="form-grid">
                <div>
                    <label for="club_id">Club</label>
                    <select id="club_id" name="club_id" required>
                        <option value="">Select club</option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?= (int) $club['id'] ?>" <?= $selectedClubId === (int) $club['id'] ? 'selected' : '' ?>><?= e($club['club_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="date">Date</label>
                    <input id="date" name="date" type="date" required value="<?= e($selectedDate) ?>">
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Load Members</button>
            </div>
        </form>
    </div>

    <?php if ($selectedClubId > 0): ?>
        <div class="card">
            <h2 class="section-title">Mark Attendance</h2>
            <?php if ($members): ?>
                <form method="post">
                    <input type="hidden" name="club_id" value="<?= $selectedClubId ?>">
                    <input type="hidden" name="date" value="<?= e($selectedDate) ?>">
                    <div class="table-wrap">
                        <table class="plain-table">
                            <thead>
                            <tr>
                                <th>Member</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($members as $member): ?>
                                <?php $currentStatus = $existingAttendance[(int) $member['id']] ?? 'Present'; ?>
                                <tr>
                                    <td><?= e($member['full_name']) ?></td>
                                    <td><?= e($member['phone_number']) ?></td>
                                    <td><?= e($member['role']) ?></td>
                                    <td>
                                        <select name="attendance[<?= (int) $member['id'] ?>]">
                                            <?php foreach (['Present', 'Absent', 'Late'] as $status): ?>
                                                <option value="<?= e($status) ?>" <?= $currentStatus === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Attendance</button>
                    </div>
                </form>
            <?php else: ?>
                <p>No active members found for this club.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
