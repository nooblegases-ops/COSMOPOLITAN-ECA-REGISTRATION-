<?php
require_once __DIR__ . '/includes/db.php';

if (is_student_logged_in()) {
    header('Location: student_dashboard.php');
    exit;
}

$fullName = trim($_POST['full_name'] ?? '');
$phoneNumber = trim($_POST['phone_number'] ?? '');
$course = trim($_POST['course'] ?? '');
$group = trim($_POST['group'] ?? '');
$intake = trim($_POST['intake'] ?? '');
$loginFailed = false;
$courseOptions = ['IT Computing', 'Creative Media', 'Business'];
$groupOptions = ['Group 1', 'Group 2'];
$intakeOptions = $pdo->query('SELECT intake_name FROM intakes ORDER BY intake_name')->fetchAll(PDO::FETCH_COLUMN);
$clubPresidents = $pdo->query('
    SELECT c.club_name, m.full_name, m.phone_number, m.photo_path
    FROM clubs c
    LEFT JOIN members m ON m.club_id = c.id
        AND m.role = "President"
        AND m.status = "Active"
    ORDER BY c.club_name
')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($fullName === '' || $phoneNumber === '' || !in_array($course, $courseOptions, true) || !in_array($group, $groupOptions, true) || $intake === '') {
        redirect_with_alert('student_login.php', 'error', 'Please enter your name, phone number, course, group, and intake.');
    }

    $stmt = $pdo->prepare('
        SELECT m.id, m.full_name
        FROM members m
        WHERE m.status = "Active"
            AND REPLACE(REPLACE(REPLACE(m.phone_number, " ", ""), "-", ""), "+", "") = REPLACE(REPLACE(REPLACE(:phone_number, " ", ""), "-", ""), "+", "")
            AND LOWER(TRIM(m.full_name)) = LOWER(TRIM(:full_name))
            AND LOWER(TRIM(m.course)) = LOWER(TRIM(:course))
            AND m.`group` = :member_group
            AND m.intake = :intake
        LIMIT 1
    ');
    $stmt->execute([
        'phone_number' => $phoneNumber,
        'full_name' => $fullName,
        'course' => $course,
        'member_group' => $group,
        'intake' => $intake,
    ]);
    $student = $stmt->fetch();

    if ($student) {
        session_regenerate_id(true);
        $_SESSION['eca_student_id'] = (int) $student['id'];
        $_SESSION['eca_student_name'] = $student['full_name'];

        header('Location: student_dashboard.php?' . http_build_query([
            'alert_type' => 'success',
            'alert_message' => 'Welcome to your student portal.',
        ]));
        exit;
    }

    $loginFailed = true;
}

function student_whatsapp_link(string $phoneNumber): string
{
    $digits = preg_replace('/\D+/', '', $phoneNumber);

    if (str_starts_with($digits, '0')) {
        $digits = '673' . substr($digits, 1);
    }

    return 'https://wa.me/' . $digits;
}

$pageTitle = 'Student Login';
$portalEyebrow = 'Student Portal';
require_once __DIR__ . '/includes/header.php';
?>
<div class="student-login-shell">
    <div class="card login-panel">
        <div>
            <p class="eyebrow">Student Portal</p>
            <h2>Student Login</h2>
        </div>
        <form method="post">
            <div class="form-grid">
                <div>
                    <label for="full_name">Full Name</label>
                    <input id="full_name" name="full_name" maxlength="100" autocomplete="name" required value="<?= e($fullName) ?>">
                </div>
                <div>
                    <label for="phone_number">Registered Phone Number</label>
                    <input id="phone_number" name="phone_number" maxlength="20" autocomplete="tel" required value="<?= e($phoneNumber) ?>">
                </div>
                <div>
                    <label for="course">Course</label>
                    <select id="course" name="course" required>
                        <option value="">Select course</option>
                        <?php foreach ($courseOptions as $courseOption): ?>
                            <option value="<?= e($courseOption) ?>" <?= $course === $courseOption ? 'selected' : '' ?>><?= e($courseOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="group">Group</label>
                    <select id="group" name="group" required>
                        <option value="">Select group</option>
                        <?php foreach ($groupOptions as $groupOption): ?>
                            <option value="<?= e($groupOption) ?>" <?= $group === $groupOption ? 'selected' : '' ?>><?= e($groupOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="intake">Intake</label>
                    <select id="intake" name="intake" required>
                        <option value="">Select intake</option>
                        <?php foreach ($intakeOptions as $intakeOption): ?>
                            <option value="<?= e($intakeOption) ?>" <?= $intake === $intakeOption ? 'selected' : '' ?>><?= e($intakeOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4 actions">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-right-to-bracket"></i> Login</button>
                <a class="btn btn-light" href="login.php"><i class="fa-solid fa-user-tie"></i> Management Login</a>
            </div>
        </form>

        <?php if ($loginFailed): ?>
            <div class="login-message">
                <h3>No Active Member Found</h3>
                <p>These details do not match an active registered club member.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="card president-panel">
        <div>
            <p class="eyebrow">Club Directory</p>
            <h2>Club Presidents</h2>
        </div>
        <div class="president-list">
            <?php foreach ($clubPresidents as $president): ?>
                <div class="president-item">
                    <span class="member-photo">
                        <?php if (!empty($president['photo_path'])): ?>
                            <img src="<?= e($president['photo_path']) ?>" alt="<?= e($president['full_name'] ?? 'President') ?>">
                        <?php else: ?>
                            <i class="fa-solid fa-user-tie"></i>
                        <?php endif; ?>
                    </span>
                    <div>
                        <strong><?= e($president['club_name']) ?></strong>
                        <?php if (!empty($president['full_name'])): ?>
                            <span><?= e($president['full_name']) ?></span>
                            <a class="phone-link" href="<?= e(student_whatsapp_link($president['phone_number'])) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> <?= e($president['phone_number']) ?></a>
                        <?php else: ?>
                            <span>President not assigned</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
