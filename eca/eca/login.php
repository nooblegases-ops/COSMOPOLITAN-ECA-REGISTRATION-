<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

start_app_session();

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errorMessage = 'Please enter your username and password.';
    } elseif (valid_login($username, $password)) {
        session_regenerate_id(true);
        $_SESSION['eca_logged_in'] = true;
        $_SESSION['eca_username'] = $username;

        header('Location: index.php?' . http_build_query([
            'alert_type' => 'success',
            'alert_message' => 'Welcome back.',
        ]));
        exit;
    } else {
        $errorMessage = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Club Attendance System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
<main class="auth-shell">
    <section class="auth-card">
        <div class="auth-brand">
            <span class="auth-logo">
                <img src="assets/images/cosmo-current-logo.png" alt="Cosmopolitan College logo">
            </span>
            <div>
                <p class="eyebrow">Management Portal</p>
                <h1>Sign In</h1>
            </div>
        </div>

        <form method="post" class="auth-form">
            <div>
                <label for="username">Username</label>
                <input id="username" name="username" autocomplete="username" required value="<?= h($username) ?>">
            </div>
            <div>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-primary auth-submit" type="submit">
                <i class="fa-solid fa-right-to-bracket"></i>
                Log In
            </button>
        </form>

        <a class="auth-student-link" href="student_login.php">
            <i class="fa-solid fa-user-graduate"></i>
            Student Portal
        </a>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if ($errorMessage !== ''): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: <?= json_encode($errorMessage) ?>,
            confirmButtonColor: '#202959'
        });
    </script>
<?php elseif (!empty($_GET['alert_message'])): ?>
    <script>
        Swal.fire({
            icon: <?= json_encode($_GET['alert_type'] ?? 'success') ?>,
            title: <?= json_encode($_GET['alert_message']) ?>,
            confirmButtonColor: '#202959'
        });
    </script>
<?php endif; ?>
</body>
</html>
