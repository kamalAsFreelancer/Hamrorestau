<?php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'restaurant';

// Connect to MySQL server
$conn = new mysqli($host, $user, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create Database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
} else {
    die("Error creating database: " . $conn->error);
}

// Select the new DB
$conn->select_db($dbname);

// Create restaurants table
$sql = "CREATE TABLE IF NOT EXISTS restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'manager', 'staff') NOT NULL,
    restaurant_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
      ON DELETE SET NULL ON UPDATE CASCADE
)";
$conn->query($sql);

// Create staff table
$sql = "CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    position VARCHAR(100),
    restaurant_id INT NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
)";
$conn->query($sql);

// Create menus table with category
$sql = "CREATE TABLE IF NOT EXISTS menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    description TEXT,
    category VARCHAR(100) NOT NULL DEFAULT 'Uncategorized',
    price DECIMAL(10,2),
    restaurant_id INT NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
)";
$conn->query($sql);

// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100),
    total_amount DECIMAL(10,2),
    status ENUM('pending', 'preparing', 'completed', 'cancelled') DEFAULT 'pending',
    restaurant_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
)";
$conn->query($sql);

// Insert demo Super Admin
$password = password_hash("admin123", PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (username, password, role) 
        VALUES ('admin', '$password', 'super_admin')";
$conn->query($sql);

?>
