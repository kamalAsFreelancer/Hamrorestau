<?php
/**
 * Hamrorestau database connection.
 *
 * For local XAMPP use:
 *   host     = 127.0.0.1
 *   user     = root
 *   password = ''
 *   database = restaurant
 *
 * For production, replace these values with environment variables and never
 * commit database credentials to Git.
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$dbname = getenv('DB_NAME') ?: 'restaurant';

try {
    $conn = new mysqli($host, $user, $password, $dbname);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Database connection failed. Import database/schema.sql and check your MySQL settings.');
}
?>
