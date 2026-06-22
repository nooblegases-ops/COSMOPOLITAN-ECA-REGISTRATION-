<?php
require_once __DIR__ . '/includes/db.php';

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM members WHERE id = :id');
    $stmt->execute(['id' => $id]);
    redirect_with_alert('members.php', 'success', 'Member deleted successfully.');
}

$filters = [
    'club_id' => $_GET['club_id'] ?? '',
    'role' => $_GET['role'] ?? '',
    'course' => trim($_GET['course'] ?? ''),
    'level' => trim($_GET['level'] ?? ''),
    'intake' => trim($_GET['intake'] ?? ''),
    'group' => trim($_GET['group'] ?? ''),
    'sports_house' => $_GET['sports_house'] ?? '',
    'status' => $_GET['status'] ?? '',
];

$where = [];
$params = [];

foreach ($filters as $key => $value) {
    if ($value === '') {
        continue;
    }

    if ($key === 'club_id') {
        $where[] = 'm.club_id = :club_id';
        $params['club_id'] = (int) $value;
    } elseif ($key === 'group') {
        $where[] = 'm.`group` LIKE :member_group';
        $params['member_group'] = '%' . $value . '%';
    } else {
        $where[] = "m.{$key} LIKE :{$key}";
        $params[$key] = '%' . $value . '%';
    }
}

$sql = 'SELECT m.*, c.club_name FROM members m INNER JOIN clubs c ON c.id = m.club_id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY c.club_name, m.role, m.full_name';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();
$clubs = $pdo->query('SELECT id, club_name FROM clubs ORDER BY club_name')->fetchAll();

function role_badge_class(string $role): string
{
    return 'badge-' . strtolower(str_replace(' ', '-', $role));
}

function whatsapp_link(string $phoneNumber): string
{
    $digits = preg_replace('/\D+/', '', $phoneNumber);

    if (str_starts_with($digits, '0')) {
        $digits = '673' . substr($digits, 1);
    }

    return 'https://wa.me/' . $digits;
}

$pageTitle = 'Member List';
require_once __DIR__ . '/includes/header.php';
?>
<div class="stack">
    <div class="card">
        <h2 class="section-title">Filters</h2>
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
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="">All roles</option>
                        <?php foreach (['President', 'Assistant President', 'Facilitator', 'Member'] as $role): ?>
                            <option value="<?= e($role) ?>" <?= $filters['role'] === $role ? 'selected' : '' ?>><?= e($role) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label for="course">Course</label><input id="course" name="course" value="<?= e($filters['course']) ?>"></div>
                <div><label for="level">Level</label><input id="level" name="level" value="<?= e($filters['level']) ?>"></div>
                <div><label for="intake">Intake</label><input id="intake" name="intake" value="<?= e($filters['intake']) ?>"></div>
                <div><label for="group">Group</label><input id="group" name="group" value="<?= e($filters['group']) ?>"></div>
                <div>
                    <label for="sports_house">Sports House</label>
                    <select id="sports_house" name="sports_house">
                        <option value="">All houses</option>
                        <?php foreach (['Amethyst', 'Amber', 'Sapphire', 'Jade'] as $house): ?>
                            <option value="<?= e($house) ?>" <?= $filters['sports_house'] === $house ? 'selected' : '' ?>><?= e($house) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All statuses</option>
                        <?php foreach (['Active', 'Inactive'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4 actions">
                <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Apply Filters</button>
                <a class="btn btn-light" href="members.php"><i class="fa-solid fa-rotate-left"></i> Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 class="section-title">Members</h2>
        <div class="table-wrap">
            <table class="datatable plain-table">
                <thead>
                <tr>
                    <th>Picture</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Level</th>
                    <th>Intake</th>
                    <th>Group</th>
                    <th>Sports House</th>
                    <th>Club</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($members as $row): ?>
                    <tr>
                        <td>
                            <span class="member-photo">
                                <?php if (!empty($row['photo_path'])): ?>
                                    <img src="<?= e($row['photo_path']) ?>" alt="<?= e($row['full_name']) ?>">
                                <?php else: ?>
                                    <i class="fa-solid fa-user"></i>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td><?= e($row['full_name']) ?></td>
                        <td><?= e($row['course'] ?? '-') ?></td>
                        <td><a class="phone-link" href="<?= e(whatsapp_link($row['phone_number'])) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> <?= e($row['phone_number']) ?></a></td>
                        <td><span class="badge <?= e(role_badge_class($row['role'])) ?>"><?= e($row['role']) ?></span></td>
                        <td><?= e($row['level']) ?></td>
                        <td><?= e($row['intake']) ?></td>
                        <td><?= e($row['group']) ?></td>
                        <td><?= e($row['sports_house'] ?? '-') ?></td>
                        <td><?= e($row['club_name']) ?></td>
                        <td><?= e($row['status']) ?></td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-light" href="register_member.php?edit=<?= (int) $row['id'] ?>#photo"><i class="fa-solid fa-upload"></i> Upload Picture</a>
                                <a class="btn btn-light" href="register_member.php?edit=<?= (int) $row['id'] ?>"><i class="fa-solid fa-pen"></i> Edit</a>
                                <a class="btn btn-light confirm-delete" href="members.php?delete=<?= (int) $row['id'] ?>"><i class="fa-solid fa-trash"></i> Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
