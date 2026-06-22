<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$publicPages = ['login.php', 'logout.php', 'student_login.php', 'student_dashboard.php', 'student_logout.php'];
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');

if (!in_array($currentScript, $publicPages, true)) {
    require_login();
}

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

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :table_name
            AND COLUMN_NAME = :column_name
    ');
    $stmt->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

if (!column_exists($pdo, 'members', 'photo_path')) {
    $pdo->exec('ALTER TABLE members ADD COLUMN photo_path VARCHAR(255) NULL AFTER phone_number');
}

if (!column_exists($pdo, 'members', 'course')) {
    $pdo->exec('ALTER TABLE members ADD COLUMN course VARCHAR(100) NULL AFTER full_name');
}

if (!column_exists($pdo, 'members', 'sports_house')) {
    $pdo->exec("ALTER TABLE members ADD COLUMN sports_house ENUM('Amethyst', 'Amber', 'Sapphire', 'Jade') NULL AFTER photo_path");
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS intakes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        intake_name VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB
");

$stmt = $pdo->prepare('INSERT IGNORE INTO intakes (intake_name) VALUES (:intake_name)');
foreach (['Intake 16', 'Intake 17', 'Intake 18', 'Intake 19', 'Intake 20'] as $intakeName) {
    $stmt->execute(['intake_name' => $intakeName]);
}
