<?php
require_once __DIR__ . '/includes/db.php';

$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$member = null;

function save_member_photo(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        redirect_with_alert('register_member.php', 'error', 'The member picture could not be uploaded.');
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $mimeType = mime_content_type($file['tmp_name']);

    if (!isset($allowedTypes[$mimeType])) {
        redirect_with_alert('register_member.php', 'error', 'Please upload a JPG, PNG, WEBP, or GIF picture.');
    }

    $uploadDir = __DIR__ . '/assets/uploads/members';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $fileName = 'member_' . bin2hex(random_bytes(8)) . '.' . $allowedTypes[$mimeType];
    $targetPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        redirect_with_alert('register_member.php', 'error', 'The member picture could not be saved.');
    }

    return 'assets/uploads/members/' . $fileName;
}

function save_cropped_member_photo(string $photoData): ?string
{
    if ($photoData === '' || !preg_match('/^data:image\/(png|jpeg|webp);base64,/', $photoData, $matches)) {
        return null;
    }

    $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
    $base64 = substr($photoData, strpos($photoData, ',') + 1);
    $imageData = base64_decode($base64, true);

    if ($imageData === false) {
        redirect_with_alert('register_member.php', 'error', 'The cropped picture could not be processed.');
    }

    $uploadDir = __DIR__ . '/assets/uploads/members';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $fileName = 'member_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $targetPath = $uploadDir . '/' . $fileName;

    if (file_put_contents($targetPath, $imageData) === false) {
        redirect_with_alert('register_member.php', 'error', 'The cropped picture could not be saved.');
    }

    return 'assets/uploads/members/' . $fileName;
}

if ($editingId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM members WHERE id = :id');
    $stmt->execute(['id' => $editingId]);
    $member = $stmt->fetch();
}

$intakeOptions = $pdo->query('SELECT intake_name FROM intakes ORDER BY intake_name')->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_intake'])) {
        $newIntake = trim($_POST['intake_name'] ?? '');

        if ($newIntake === '') {
            redirect_with_alert('register_member.php', 'error', 'Intake name is required.');
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO intakes (intake_name) VALUES (:intake_name)');
            $stmt->execute(['intake_name' => $newIntake]);
            redirect_with_alert('register_member.php', 'success', 'Intake created successfully.');
        } catch (PDOException $e) {
            redirect_with_alert('register_member.php', 'error', 'This intake already exists.');
        }
    }

    $id = (int) ($_POST['id'] ?? 0);
    $clubId = (int) ($_POST['club_id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $sportsHouse = $_POST['sports_house'] ?? '';
    $role = $_POST['role'] ?? 'Member';
    $level = trim($_POST['level'] ?? '');
    $intake = trim($_POST['intake'] ?? '');
    $group = trim($_POST['group'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    $roles = ['President', 'Assistant President', 'Facilitator', 'Member'];
    $sportsHouses = ['Amethyst', 'Amber', 'Sapphire', 'Jade'];
    $levelOptions = ['Level 1', 'Level 2', 'Level 3', 'Level 5'];
    $groupOptions = ['Group 1', 'Group 2'];
    $photoPath = save_cropped_member_photo(trim($_POST['cropped_photo_data'] ?? '')) ?? save_member_photo($_FILES['photo'] ?? []);

    if ($clubId < 1 || $fullName === '' || $course === '' || $phoneNumber === '' || !in_array($level, $levelOptions, true) || !in_array($intake, $intakeOptions, true) || !in_array($group, $groupOptions, true) || !in_array($role, $roles, true) || !in_array($sportsHouse, $sportsHouses, true)) {
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
        $sql = 'UPDATE members SET club_id = :club_id, full_name = :full_name, course = :course, phone_number = :phone_number, sports_house = :sports_house, role = :role, level = :level, intake = :intake, `group` = :member_group, status = :status';
        $params = [
            'id' => $id,
            'club_id' => $clubId,
            'full_name' => $fullName,
            'course' => $course,
            'phone_number' => $phoneNumber,
            'sports_house' => $sportsHouse,
            'role' => $role,
            'level' => $level,
            'intake' => $intake,
            'member_group' => $group,
            'status' => $status,
        ];

        if ($photoPath !== null) {
            $sql .= ', photo_path = :photo_path';
            $params['photo_path'] = $photoPath;
        }

        $sql .= ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        redirect_with_alert('members.php', 'success', 'Member updated successfully.');
    }

    $stmt = $pdo->prepare('INSERT INTO members (club_id, full_name, course, phone_number, photo_path, sports_house, role, level, intake, `group`, status) VALUES (:club_id, :full_name, :course, :phone_number, :photo_path, :sports_house, :role, :level, :intake, :member_group, :status)');
    $stmt->execute([
        'club_id' => $clubId,
        'full_name' => $fullName,
        'course' => $course,
        'phone_number' => $phoneNumber,
        'photo_path' => $photoPath,
        'sports_house' => $sportsHouse,
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
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= (int) ($member['id'] ?? 0) ?>">
        <div class="form-grid">
            <div>
                <label for="photo">Member Picture</label>
                <label class="file-upload" for="photo">
                    <span class="photo-preview">
                        <?php if (!empty($member['photo_path'])): ?>
                            <img src="<?= e($member['photo_path']) ?>" alt="<?= e($member['full_name'] ?? 'Member') ?>">
                        <?php else: ?>
                            <i class="fa-solid fa-user"></i>
                        <?php endif; ?>
                    </span>
                    <span><i class="fa-solid fa-upload"></i> Upload Picture</span>
                </label>
                <input class="sr-only-file" id="photo" name="photo" type="file" accept="image/*">
                <input id="cropped_photo_data" name="cropped_photo_data" type="hidden">
            </div>
            <div>
                <label for="full_name">Full Name</label>
                <input id="full_name" name="full_name" maxlength="100" required value="<?= e($member['full_name'] ?? '') ?>">
            </div>
            <div>
                <label for="course">Course</label>
                <input id="course" name="course" maxlength="100" required value="<?= e($member['course'] ?? '') ?>">
            </div>
            <div>
                <label for="phone_number">Phone Number</label>
                <input id="phone_number" name="phone_number" maxlength="20" required value="<?= e($member['phone_number'] ?? '') ?>">
            </div>
            <div>
                <label for="sports_house">Sports House</label>
                <select id="sports_house" name="sports_house" required>
                    <option value="">Select sports house</option>
                    <?php foreach (['Amethyst', 'Amber', 'Sapphire', 'Jade'] as $house): ?>
                        <option value="<?= e($house) ?>" <?= ($member['sports_house'] ?? '') === $house ? 'selected' : '' ?>><?= e($house) ?></option>
                    <?php endforeach; ?>
                </select>
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
                <div class="choice-group" id="level">
                    <?php foreach (['Level 1', 'Level 2', 'Level 3', 'Level 5'] as $level): ?>
                        <label class="choice-pill">
                            <input type="radio" name="level" value="<?= e($level) ?>" <?= ($member['level'] ?? 'Level 1') === $level ? 'checked' : '' ?> required>
                            <span><?= e($level) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <div class="field-label-row">
                    <label for="intake">Intake</label>
                    <button class="icon-btn" id="openIntakeModal" type="button" aria-label="Add intake"><i class="fa-solid fa-plus"></i></button>
                </div>
                <div class="choice-group" id="intake">
                    <?php foreach ($intakeOptions as $intake): ?>
                        <label class="choice-pill">
                            <input type="radio" name="intake" value="<?= e($intake) ?>" <?= ($member['intake'] ?? ($intakeOptions[0] ?? '')) === $intake ? 'checked' : '' ?> required>
                            <span><?= e($intake) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <label for="group">Group</label>
                <div class="choice-group" id="group">
                    <?php foreach (['Group 1', 'Group 2'] as $group): ?>
                        <label class="choice-pill">
                            <input type="radio" name="group" value="<?= e($group) ?>" <?= ($member['group'] ?? 'Group 1') === $group ? 'checked' : '' ?> required>
                            <span><?= e($group) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
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
<div class="crop-modal" id="photoCropModal" aria-hidden="true">
    <div class="crop-dialog">
        <div class="crop-header">
            <div>
                <p class="eyebrow">Member Picture</p>
                <h2>Adjust Square Crop</h2>
            </div>
            <button class="btn btn-light" type="button" id="cancelCrop"><i class="fa-solid fa-xmark"></i> Close</button>
        </div>
        <div class="crop-stage">
            <canvas id="cropCanvas" width="420" height="420"></canvas>
        </div>
        <div class="crop-controls">
            <label for="cropZoom">Zoom</label>
            <input id="cropZoom" type="range" min="1" max="3" step="0.01" value="1">
        </div>
        <div class="actions">
            <button class="btn btn-primary" type="button" id="applyCrop"><i class="fa-solid fa-check"></i> Use Picture</button>
        </div>
    </div>
</div>
<div class="crop-modal" id="intakeModal" aria-hidden="true">
    <div class="crop-dialog">
        <div class="crop-header">
            <div>
                <p class="eyebrow">Intake</p>
                <h2>Create New Intake</h2>
            </div>
            <button class="btn btn-light" type="button" id="closeIntakeModal"><i class="fa-solid fa-xmark"></i> Close</button>
        </div>
        <form method="post">
            <input type="hidden" name="create_intake" value="1">
            <label for="intake_name">Intake Name</label>
            <input id="intake_name" name="intake_name" maxlength="50" placeholder="Example: Intake 21" required>
            <div class="actions mt-4">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus"></i> Save Intake</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
