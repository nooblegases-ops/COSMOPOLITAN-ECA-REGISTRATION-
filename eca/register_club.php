<?php
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clubName = trim($_POST['club_name'] ?? '');

    if ($clubName === '') {
        redirect_with_alert('register_club.php', 'error', 'Club name is required.');
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO clubs (club_name) VALUES (:club_name)');
        $stmt->execute(['club_name' => $clubName]);
        redirect_with_alert('register_club.php', 'success', 'Club registered successfully.');
    } catch (PDOException $e) {
        redirect_with_alert('register_club.php', 'error', 'This club may already exist.');
    }
}

$clubs = $pdo->query('SELECT * FROM clubs ORDER BY created_at DESC')->fetchAll();
$pageTitle = 'Register Club';
require_once __DIR__ . '/includes/header.php';
?>
<div class="stack">
    <div class="card">
        <h2 class="section-title">New Club</h2>
        <form method="post">
            <label for="club_name">Club Name</label>
            <input id="club_name" name="club_name" maxlength="100" required>
            <div class="mt-4">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus"></i> Save Club</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 class="section-title">Registered Clubs</h2>
        <div class="table-wrap">
            <table class="datatable plain-table">
                <thead>
                <tr>
                    <th>Club</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($clubs as $club): ?>
                    <tr>
                        <td><?= e($club['club_name']) ?></td>
                        <td><?= e($club['created_at']) ?></td>
                        <td><a class="btn btn-light" href="club_detail.php?id=<?= (int) $club['id'] ?>"><i class="fa-solid fa-eye"></i> View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
