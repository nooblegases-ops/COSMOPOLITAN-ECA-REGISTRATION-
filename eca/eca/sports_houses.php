<?php
require_once __DIR__ . '/includes/db.php';

$houses = [
    ['name' => 'Amethyst', 'color_name' => 'Purple', 'color' => '#7c3aed'],
    ['name' => 'Amber', 'color_name' => 'Yellow Orange', 'color' => '#f59e0b'],
    ['name' => 'Sapphire', 'color_name' => 'Blue Ocean', 'color' => '#0284c7'],
    ['name' => 'Jade', 'color_name' => 'Green', 'color' => '#16a34a'],
];
$houseNames = array_column($houses, 'name');
$selectedHouse = $_GET['house'] ?? '';
$selectedIntake = trim($_GET['intake'] ?? '');
$search = trim($_GET['search'] ?? '');

if (!in_array($selectedHouse, $houseNames, true)) {
    $selectedHouse = '';
}

$intakes = $pdo->query('SELECT DISTINCT intake FROM members WHERE intake <> "" ORDER BY intake')->fetchAll(PDO::FETCH_COLUMN);
$membersByHouse = [];
$where = [
    'm.sports_house IS NOT NULL',
    'm.sports_house <> ""',
];
$params = [];

if ($selectedIntake !== '') {
    $where[] = 'm.intake = :intake';
    $params['intake'] = $selectedIntake;
}

if ($search !== '') {
    $where[] = '(m.full_name LIKE :search OR m.course LIKE :search OR c.club_name LIKE :search OR m.role LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$stmt = $pdo->prepare('
    SELECT m.full_name, m.course, m.role, m.sports_house, c.club_name
    FROM members m
    INNER JOIN clubs c ON c.id = m.club_id
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY m.sports_house, m.full_name
');
$stmt->execute($params);

foreach ($stmt->fetchAll() as $member) {
    $membersByHouse[$member['sports_house']][] = $member;
}

function house_url(string $house, string $selectedIntake, string $search): string
{
    return 'sports_houses.php?' . http_build_query(array_filter([
        'house' => $house,
        'intake' => $selectedIntake,
        'search' => $search,
    ], static fn ($value) => $value !== ''));
}

$pageTitle = 'Sports Houses';
require_once __DIR__ . '/includes/header.php';
?>
<div class="stack">
    <div class="card">
        <h2 class="section-title">Filters</h2>
        <form method="get">
            <input type="hidden" name="house" value="<?= e($selectedHouse) ?>">
            <div class="form-grid">
                <div>
                    <label for="intake">Intake</label>
                    <select id="intake" name="intake">
                        <option value="">All intakes</option>
                        <?php foreach ($intakes as $intake): ?>
                            <option value="<?= e($intake) ?>" <?= $selectedIntake === $intake ? 'selected' : '' ?>><?= e($intake) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="search">Search</label>
                    <input id="search" name="search" value="<?= e($search) ?>" placeholder="Member, club, or role">
                </div>
            </div>
            <div class="mt-4 actions">
                <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Apply Filters</button>
                <a class="btn btn-light" href="sports_houses.php"><i class="fa-solid fa-rotate-left"></i> Reset</a>
            </div>
        </form>
    </div>

    <div class="house-grid">
        <?php foreach ($houses as $house): ?>
            <?php $memberCount = count($membersByHouse[$house['name']] ?? []); ?>
            <a class="card house-card house-card-link <?= $selectedHouse === $house['name'] ? 'active' : '' ?>" href="<?= e(house_url($house['name'], $selectedIntake, $search)) ?>" style="--house-color: <?= e($house['color']) ?>;">
                <div class="house-row">
                    <span class="house-swatch" aria-hidden="true"></span>
                    <div>
                        <p class="eyebrow"><?= e($house['color_name']) ?></p>
                        <h2><?= e($house['name']) ?></h2>
                    </div>
                </div>
                <div class="house-count">
                    <strong><?= $memberCount ?></strong>
                    <span><?= $memberCount === 1 ? 'member' : 'members' ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($selectedHouse !== ''): ?>
        <div class="card">
            <h2 class="section-title"><?= e($selectedHouse) ?> Members</h2>
            <div class="house-members">
                <?php if (!empty($membersByHouse[$selectedHouse])): ?>
                    <?php foreach ($membersByHouse[$selectedHouse] as $member): ?>
                        <div class="house-member">
                            <strong><?= e($member['full_name']) ?></strong>
                            <span><?= e($member['club_name']) ?> - <?= e($member['course'] ?? '-') ?> - <?= e($member['role']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-text">No members registered yet.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
