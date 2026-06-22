<?php
declare(strict_types=1);

function start_app_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function admin_username(): string
{
    $username = getenv('ECA_ADMIN_USERNAME');

    return $username !== false && $username !== '' ? $username : 'admin';
}

function admin_password(): string
{
    $password = getenv('ECA_ADMIN_PASSWORD');

    return $password !== false && $password !== '' ? $password : 'admin123';
}

function is_logged_in(): bool
{
    start_app_session();

    return !empty($_SESSION['eca_logged_in']);
}

function is_student_logged_in(): bool
{
    start_app_session();

    return !empty($_SESSION['eca_student_id']);
}

function valid_login(string $username, string $password): bool
{
    return hash_equals(admin_username(), $username)
        && hash_equals(admin_password(), $password);
}

function require_login(): void
{
    if (is_logged_in()) {
        return;
    }

    header('Location: student_login.php?' . http_build_query([
        'alert_type' => 'warning',
        'alert_message' => 'Please log in to continue.',
    ]));
    exit;
}

function require_student_login(): void
{
    if (is_student_logged_in()) {
        return;
    }

    header('Location: student_login.php?' . http_build_query([
        'alert_type' => 'warning',
        'alert_message' => 'Please log in as a student to continue.',
    ]));
    exit;
}
