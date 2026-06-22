<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

start_app_session();
unset($_SESSION['eca_student_id'], $_SESSION['eca_student_name']);

header('Location: student_login.php?' . http_build_query([
    'alert_type' => 'success',
    'alert_message' => 'You have been logged out of the student portal.',
]));
exit;
