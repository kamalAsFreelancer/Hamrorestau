<?php
/**
 * Hamrorestau database connection for local XAMPP.
 *
 * Local XAMPP defaults:
 *   host     = 127.0.0.1
 *   user     = root
 *   password = ''
 *   database = restaurant
 *
 * Do not commit production database credentials to Git.
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = '127.0.0.1';
$user = 'root';
$password = '';
$dbname = 'restaurant';

try {
    $conn = new mysqli($host, $user, $password, $dbname);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Database connection failed. Make sure MySQL is running in XAMPP and the database "restaurant" exists.');
}
?>
