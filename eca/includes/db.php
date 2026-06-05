<?php
declare(strict_types=1);

$host = 'localhost';
$dbName = 'club_attendance';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbName};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Please import sql/schema.sql and check includes/db.php.');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect_with_alert(string $url, string $type, string $message): void
{
    $separator = str_contains($url, '?') ? '&' : '?';
    header('Location: ' . $url . $separator . http_build_query([
        'alert_type' => $type,
        'alert_message' => $message,
    ]));
    exit;
}
