<?php
require_once __DIR__ . '/includes/db.php';

$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$member = null;

if ($editingId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM members WHERE id = :id');
    $stmt->execute(['id' => $editingId]);
    $member = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $clubId = (int) ($_POST['club_id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $role = $_POST['role'] ?? 'Member';
    $level = trim($_POST['level'] ?? '');
    $intake = trim($_POST['intake'] ?? '');
    $group = trim($_POST['group'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    $roles = ['President', 'Assistant President', 'Facilitator', 'Member'];

    if ($clubId < 1 || $fullName === '' || $phoneNumber === '' || $level === '' || $intake === '' || $group === '' || !in_array($role, $roles, true)) {
        redirect_with_alert('register_member.php', 'error', 'Please complete all required fields.');
    }

    if ($status === 'Active' && in_array($role, ['President', 'Assistant President'], true)) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM members WHERE club_id = :club_id AND role = :role AND status = "Active" AND id <> :id');
        $stmt->execute(['club_id' => $clubId, 'role' => $role, 'id' => $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            redirect_with_alert('register_member.php', 'error', "Only one active {$role} is allowed per club.");
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE members SET club_id = :club_id, full_name = :full_name, phone_number = :phone_number, role = :role, level = :level, intake = :intake, `group` = :member_group, status = :status WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'club_id' => $clubId,
            'full_name' => $fullName,
            'phone_number' => $phoneNumber,
            'role' => $role,
            'level' => $level,
            'intake' => $intake,
            'member_group' => $group,
            'status' => $status,
        ]);
        redirect_with_alert('members.php', 'success', 'Member updated successfully.');
    }

    $stmt = $pdo->prepare('INSERT INTO members (club_id, full_name, phone_number, role, level, intake, `group`, status) VALUES (:club_id, :full_name, :phone_number, :role, :level, :intake, :member_group, :status)');
    $stmt->execute([
        'club_id' => $clubId,
        'full_name' => $fullName,
        'phone_number' => $phoneNumber,
        'role' => $role,
        'level' => $level,
        'intake' => $intake,
        'member_group' => $group,
        'status' => $status,
    ]);
    redirect_with_alert('register_member.php', 'success', 'Member registered successfully.');
}

$clubs = $pdo->query('SELECT id, club_name FROM clubs ORDER BY club_name')->fetchAll();
$pageTitle = $member ? 'Edit Member' : 'Register Member';
require_once __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h2 class="section-title"><?= $member ? 'Update Member' : 'New Member' ?></h2>
    <form method="post">
        <input type="hidden" name="id" value="<?= (int) ($member['id'] ?? 0) ?>">
        <div class="form-grid">
            <div>
                <label for="full_name">Full Name</label>
                <input id="full_name" name="full_name" maxlength="100" required value="<?= e($member['full_name'] ?? '') ?>">
            </div>
            <div>
                <label for="phone_number">Phone Number</label>
                <input id="phone_number" name="phone_number" maxlength="20" required value="<?= e($member['phone_number'] ?? '') ?>">
            </div>
            <div>
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <?php foreach (['President', 'Assistant President', 'Facilitator', 'Member'] as $role): ?>
                        <option value="<?= e($role) ?>" <?= ($member['role'] ?? 'Member') === $role ? 'selected' : '' ?>><?= e($role) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="level">Level</label>
                <input id="level" name="level" maxlength="20" required value="<?= e($member['level'] ?? '') ?>">
            </div>
            <div>
                <label for="intake">Intake</label>
                <input id="intake" name="intake" maxlength="20" required value="<?= e($member['intake'] ?? '') ?>">
            </div>
            <div>
                <label for="group">Group</label>
                <input id="group" name="group" maxlength="10" required value="<?= e($member['group'] ?? '') ?>">
            </div>
            <div>
                <label for="club_id">Club</label>
                <select id="club_id" name="club_id" required>
                    <option value="">Select club</option>
                    <?php foreach ($clubs as $club): ?>
                        <option value="<?= (int) $club['id'] ?>" <?= (int) ($member['club_id'] ?? 0) === (int) $club['id'] ? 'selected' : '' ?>><?= e($club['club_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <?php foreach (['Active', 'Inactive'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= ($member['status'] ?? 'Active') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-4 actions">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Member</button>
            <a class="btn btn-light" href="members.php"><i class="fa-solid fa-users"></i> Member List</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
