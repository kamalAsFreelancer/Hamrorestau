<?php

/*
|--------------------------------------------------------------------------
| HamroResto - Complete Database Setup
|--------------------------------------------------------------------------
| XAMPP / Local MySQL
| Database: restaurant
|--------------------------------------------------------------------------
*/

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*
|--------------------------------------------------------------------------
| DATABASE CONFIGURATION
|--------------------------------------------------------------------------
*/

$host = "localhost";
$user = "root";
$password = "";
$dbname = "restaurant";
$port = 3306;


/*
|--------------------------------------------------------------------------
| CONNECT TO MYSQL
|--------------------------------------------------------------------------
*/

try {

    $conn = new mysqli(
        $host,
        $user,
        $password,
        "",
        $port
    );

    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {

    die(
        "MySQL connection failed: " .
        htmlspecialchars($e->getMessage())
    );

}


/*
|--------------------------------------------------------------------------
| CREATE DATABASE
|--------------------------------------------------------------------------
*/

try {

    $conn->query("
        CREATE DATABASE IF NOT EXISTS `$dbname`
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci
    ");

    $conn->select_db($dbname);

    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {

    die(
        "Database creation/selection failed: " .
        htmlspecialchars($e->getMessage())
    );

}


/*
|--------------------------------------------------------------------------
| 1. RESTAURANTS
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS restaurants (

        id INT AUTO_INCREMENT PRIMARY KEY,

        name VARCHAR(150) NOT NULL,

        owner_name VARCHAR(150) NULL,

        phone VARCHAR(30) NULL,

        email VARCHAR(150) NULL,

        address VARCHAR(255) NULL,

        city VARCHAR(100) NULL,

        state VARCHAR(100) NULL,

        country VARCHAR(100) DEFAULT 'Nepal',

        logo VARCHAR(255) NULL,

        status ENUM(
            'active',
            'inactive',
            'suspended'
        ) DEFAULT 'active',

        subscription_status ENUM(
            'trial',
            'active',
            'expired',
            'cancelled'
        ) DEFAULT 'trial',

        subscription_start DATE NULL,

        subscription_end DATE NULL,

        created_by INT NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        INDEX idx_restaurant_status (status),

        INDEX idx_subscription_status (subscription_status),

        INDEX idx_subscription_end (subscription_end)

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 2. USERS
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS users (

        id INT AUTO_INCREMENT PRIMARY KEY,

        username VARCHAR(100) NOT NULL UNIQUE,

        email VARCHAR(150) NULL UNIQUE,

        password VARCHAR(255) NOT NULL,

        role ENUM(
            'super_admin',
            'manager',
            'staff'
        ) NOT NULL,

        restaurant_id INT NULL,

        status ENUM(
            'active',
            'inactive',
            'blocked'
        ) DEFAULT 'active',

        last_login DATETIME NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        INDEX idx_user_restaurant (restaurant_id),

        INDEX idx_user_role (role),

        CONSTRAINT fk_users_restaurant

            FOREIGN KEY (restaurant_id)

            REFERENCES restaurants(id)

            ON DELETE SET NULL

            ON UPDATE CASCADE

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 3. STAFF
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS staff (

        id INT AUTO_INCREMENT PRIMARY KEY,

        user_id INT NULL,

        restaurant_id INT NOT NULL,

        name VARCHAR(150) NOT NULL,

        phone VARCHAR(30) NULL,

        email VARCHAR(150) NULL,

        position ENUM(
            'waiter',
            'kitchen',
            'bartender',
            'cashier',
            'manager',
            'other'
        ) DEFAULT 'other',

        salary DECIMAL(10,2) DEFAULT 0.00,

        status ENUM(
            'active',
            'inactive'
        ) DEFAULT 'active',

        created_by INT NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_staff_restaurant (restaurant_id),

        INDEX idx_staff_position (position),

        CONSTRAINT fk_staff_user

            FOREIGN KEY (user_id)

            REFERENCES users(id)

            ON DELETE SET NULL,

        CONSTRAINT fk_staff_restaurant

            FOREIGN KEY (restaurant_id)

            REFERENCES restaurants(id)

            ON DELETE CASCADE,

        CONSTRAINT fk_staff_created_by

            FOREIGN KEY (created_by)

            REFERENCES users(id)

            ON DELETE SET NULL

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 4. CATEGORIES
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS categories (

        id INT AUTO_INCREMENT PRIMARY KEY,

        restaurant_id INT NOT NULL,

        name VARCHAR(100) NOT NULL,

        description TEXT NULL,

        status ENUM(
            'active',
            'inactive'
        ) DEFAULT 'active',

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_category_restaurant (restaurant_id),

        CONSTRAINT fk_categories_restaurant

            FOREIGN KEY (restaurant_id)

            REFERENCES restaurants(id)

            ON DELETE CASCADE

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 5. MENUS
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS menus (

        id INT AUTO_INCREMENT PRIMARY KEY,

        restaurant_id INT NOT NULL,

        category_id INT NULL,

        name VARCHAR(150) NOT NULL,

        description TEXT NULL,

        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,

        image VARCHAR(255) NULL,

        item_type ENUM(
            'food',
            'drink',
            'dessert',
            'other'
        ) DEFAULT 'food',

        preparation_area ENUM(
            'kitchen',
            'bar',
            'dessert',
            'other'
        ) DEFAULT 'kitchen',

        status ENUM(
            'available',
            'unavailable'
        ) DEFAULT 'available',

        created_by INT NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        INDEX idx_menu_restaurant (restaurant_id),

        INDEX idx_menu_category (category_id),

        CONSTRAINT fk_menus_restaurant

            FOREIGN KEY (restaurant_id)

            REFERENCES restaurants(id)

            ON DELETE CASCADE,

        CONSTRAINT fk_menus_category

            FOREIGN KEY (category_id)

            REFERENCES categories(id)

            ON DELETE SET NULL,

        CONSTRAINT fk_menus_created_by

            FOREIGN KEY (created_by)

            REFERENCES users(id)

            ON DELETE SET NULL

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 6. RESTAURANT TABLES
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS restaurant_tables (

        id INT AUTO_INCREMENT PRIMARY KEY,

        restaurant_id INT NOT NULL,

        table_number VARCHAR(30) NOT NULL,

        capacity INT NOT NULL DEFAULT 4,

        location VARCHAR(100) NULL,

        status ENUM(
            'available',
            'occupied',
            'reserved',
            'maintenance'
        ) DEFAULT 'available',

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_table_restaurant (restaurant_id),

        CONSTRAINT fk_tables_restaurant

            FOREIGN KEY (restaurant_id)

            REFERENCES restaurants(id)

            ON DELETE CASCADE

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 7. TABLE SESSIONS
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS table_sessions (

        id INT AUTO_INCREMENT PRIMARY KEY,

        restaurant_id INT NOT NULL,

        table_id INT NOT NULL,

        opened_by INT NULL,

        closed_by INT NULL,

        started_at DATETIME DEFAULT CURRENT_TIMESTAMP,

        closed_at DATETIME NULL,

        status ENUM(
            'open',
            'closed'
        ) DEFAULT 'open',

        INDEX idx_session_restaurant (restaurant_id),

        INDEX idx_session_table (table_id),

        CONSTRAINT fk_sessions_restaurant

            FOREIGN KEY (restaurant_id)

            REFERENCES restaurants(id)

            ON DELETE CASCADE,

        CONSTRAINT fk_sessions_table

            FOREIGN KEY (table_id)

            REFERENCES restaurant_tables(id)

            ON DELETE CASCADE,

        CONSTRAINT fk_sessions_opened_by

            FOREIGN KEY (opened_by)

            REFERENCES users(id)

            ON DELETE SET NULL,

        CONSTRAINT fk_sessions_closed_by

            FOREIGN KEY (closed_by)

            REFERENCES users(id)

            ON DELETE SET NULL

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 8. ORDERS
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS orders (

        id INT AUTO_INCREMENT PRIMARY KEY,

        restaurant_id INT NOT NULL,

        table_id INT NULL,

        table_session_id INT NULL,

        customer_name VARCHAR(150) NULL,

        customer_phone VARCHAR(30) NULL,

        order_type ENUM(
            'dine_in',
            'takeaway',
            'delivery'
        ) DEFAULT 'dine_in',

        subtotal DECIMAL(10,2) DEFAULT 0.00,

        tax_amount DECIMAL(10,2) DEFAULT 0.00,

        service_charge DECIMAL(10,2) DEFAULT 0.00,

        discount_amount DECIMAL(10,2) DEFAULT 0.00,

        total_amount DECIMAL(10,2) DEFAULT 0.00,

        status ENUM(
            'pending',
            'confirmed',
            'preparing',
            'ready',
            'served',
            'completed',
            'cancelled'
        ) DEFAULT 'pending',

        payment_status ENUM(
            'unpaid',
            'partial',
            'paid',
            'refunded'
        ) DEFAULT 'unpaid',

        notes TEXT NULL,

        created_by INT NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        INDEX idx_order_restaurant (restaurant_id),

        INDEX idx_order_status (status),

        INDEX idx_order_date (created_at),

        CONSTRAINT fk_orders_restaurant

            FOREIGN KEY (restaurant_id)

            REFERENCES restaurants(id)

            ON DELETE CASCADE,

        CONSTRAINT fk_orders_table

            FOREIGN KEY (table_id)

            REFERENCES restaurant_tables(id)

            ON DELETE SET NULL,

        CONSTRAINT fk_orders_table_session

            FOREIGN KEY (table_session_id)

            REFERENCES table_sessions(id)

            ON DELETE SET NULL,

        CONSTRAINT fk_orders_created_by

            FOREIGN KEY (created_by)

            REFERENCES users(id)

            ON DELETE SET NULL

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 9. ORDER ITEMS
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS order_items (

        id INT AUTO_INCREMENT PRIMARY KEY,

        order_id INT NOT NULL,

        menu_id INT NOT NULL,

        quantity INT NOT NULL DEFAULT 1,

        unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,

        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,

        notes TEXT NULL,

        status ENUM(
            'pending',
            'preparing',
            'ready',
            'served',
            'cancelled'
        ) DEFAULT 'pending',

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        INDEX idx_order_item_order (order_id),

        INDEX idx_order_item_menu (menu_id),

        INDEX idx_order_item_status (status),

        CONSTRAINT fk_order_items_order

            FOREIGN KEY (order_id)

            REFERENCES orders(id)

            ON DELETE CASCADE,

        CONSTRAINT fk_order_items_menu

            FOREIGN KEY (menu_id)

            REFERENCES menus(id)

            ON DELETE RESTRICT

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 10. PAYMENTS
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS payments (

        id INT AUTO_INCREMENT PRIMARY KEY,

        restaurant_id INT NOT NULL,

        order_id INT NOT NULL,

        amount DECIMAL(10,2) NOT NULL,

        payment_method ENUM(
            'cash',
            'card',
            'esewa',
            'khalti',
            'bank_transfer',
            'other'
        ) NOT NULL,

        transaction_reference VARCHAR(150) NULL,

        status ENUM(
            'pending',
            'completed',
            'failed',
            'refunded'
        ) DEFAULT 'completed',

        paid_by INT NULL,

        paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_payment_restaurant (restaurant_id),

        INDEX idx_payment_order (order_id),

        CONSTRAINT fk_payments_restaurant

            FOREIGN KEY (restaurant_id)

            REFERENCES restaurants(id)

            ON DELETE CASCADE,

        CONSTRAINT fk_payments_order

            FOREIGN KEY (order_id)

            REFERENCES orders(id)

            ON DELETE CASCADE,

        CONSTRAINT fk_payments_paid_by

            FOREIGN KEY (paid_by)

            REFERENCES users(id)

            ON DELETE SET NULL

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 11. RESERVATIONS
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS reservations (

        id INT AUTO_INCREMENT PRIMARY KEY,

        restaurant_id INT NOT NULL,

        table_id INT NULL,

        customer_name VARCHAR(150) NOT NULL,

        customer_phone VARCHAR(30) NULL,

        customer_email VARCHAR(150) NULL,

        reservation_date DATE NOT NULL,

        reservation_time TIME NOT NULL,

        guest_count INT NOT NULL DEFAULT 1,

        status ENUM(
            'pending',
            'confirmed',
            'arrived',
            'completed',
            'cancelled',
            'no_show'
        ) DEFAULT 'pending',

        notes TEXT NULL,

        created_by INT NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_reservation_restaurant (restaurant_id),

        INDEX idx_reservation_date (reservation_date),

        INDEX idx_reservation_status (status),

        CONSTRAINT fk_reservations_restaurant

            FOREIGN KEY (restaurant_id)

            REFERENCES restaurants(id)

            ON DELETE CASCADE,

        CONSTRAINT fk_reservations_table

            FOREIGN KEY (table_id)

            REFERENCES restaurant_tables(id)

            ON DELETE SET NULL,

        CONSTRAINT fk_reservations_created_by

            FOREIGN KEY (created_by)

            REFERENCES users(id)

            ON DELETE SET NULL

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 12. RESTAURANT SETTINGS
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS restaurant_settings (

        id INT AUTO_INCREMENT PRIMARY KEY,

        restaurant_id INT NOT NULL UNIQUE,

        currency VARCHAR(10) DEFAULT 'NPR',

        tax_percent DECIMAL(5,2) DEFAULT 0.00,

        service_charge_percent DECIMAL(5,2) DEFAULT 0.00,

        invoice_prefix VARCHAR(20) DEFAULT 'INV',

        receipt_footer TEXT NULL,

        address VARCHAR(255) NULL,

        phone VARCHAR(30) NULL,

        email VARCHAR(150) NULL,

        opening_time TIME NULL,

        closing_time TIME NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        CONSTRAINT fk_settings_restaurant

            FOREIGN KEY (restaurant_id)

            REFERENCES restaurants(id)

            ON DELETE CASCADE

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 13. SUBSCRIPTIONS
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS subscriptions (

        id INT AUTO_INCREMENT PRIMARY KEY,

        restaurant_id INT NOT NULL,

        plan_name VARCHAR(100) DEFAULT 'Annual',

        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,

        start_date DATE NOT NULL,

        end_date DATE NOT NULL,

        status ENUM(
            'trial',
            'active',
            'expired',
            'cancelled'
        ) DEFAULT 'active',

        payment_method ENUM(
            'cash',
            'esewa',
            'khalti',
            'bank_transfer',
            'other'
        ) NULL,

        transaction_reference VARCHAR(150) NULL,

        created_by INT NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_subscription_restaurant (restaurant_id),

        INDEX idx_subscription_status (status),

        INDEX idx_subscription_end (end_date),

        CONSTRAINT fk_subscriptions_restaurant

            FOREIGN KEY (restaurant_id)

            REFERENCES restaurants(id)

            ON DELETE CASCADE,

        CONSTRAINT fk_subscriptions_created_by

            FOREIGN KEY (created_by)

            REFERENCES users(id)

            ON DELETE SET NULL

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 14. NOTIFICATIONS
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS notifications (

        id INT AUTO_INCREMENT PRIMARY KEY,

        restaurant_id INT NULL,

        user_id INT NULL,

        title VARCHAR(200) NOT NULL,

        message TEXT NOT NULL,

        type ENUM(
            'order',
            'payment',
            'reservation',
            'subscription',
            'system',
            'other'
        ) DEFAULT 'system',

        is_read BOOLEAN DEFAULT FALSE,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_notification_user (user_id),

        INDEX idx_notification_restaurant (restaurant_id),

        INDEX idx_notification_read (is_read),

        CONSTRAINT fk_notifications_restaurant

            FOREIGN KEY (restaurant_id)

            REFERENCES restaurants(id)

            ON DELETE CASCADE,

        CONSTRAINT fk_notifications_user

            FOREIGN KEY (user_id)

            REFERENCES users(id)

            ON DELETE CASCADE

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 15. ORDER STATUS HISTORY
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS order_status_history (

        id INT AUTO_INCREMENT PRIMARY KEY,

        order_id INT NOT NULL,

        restaurant_id INT NOT NULL,

        old_status VARCHAR(50) NULL,

        new_status VARCHAR(50) NOT NULL,

        changed_by INT NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_status_order (order_id),

        INDEX idx_status_restaurant (restaurant_id),

        CONSTRAINT fk_status_history_order

            FOREIGN KEY (order_id)

            REFERENCES orders(id)

            ON DELETE CASCADE,

        CONSTRAINT fk_status_history_restaurant

            FOREIGN KEY (restaurant_id)

            REFERENCES restaurants(id)

            ON DELETE CASCADE,

        CONSTRAINT fk_status_history_user

            FOREIGN KEY (changed_by)

            REFERENCES users(id)

            ON DELETE SET NULL

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| 16. ACTIVITY LOGS
|--------------------------------------------------------------------------
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS activity_logs (

        id INT AUTO_INCREMENT PRIMARY KEY,

        user_id INT NULL,

        restaurant_id INT NULL,

        action VARCHAR(100) NOT NULL,

        description TEXT NULL,

        ip_address VARCHAR(45) NULL,

        user_agent TEXT NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_activity_user (user_id),

        INDEX idx_activity_restaurant (restaurant_id),

        INDEX idx_activity_date (created_at),

        CONSTRAINT fk_activity_user

            FOREIGN KEY (user_id)

            REFERENCES users(id)

            ON DELETE SET NULL,

        CONSTRAINT fk_activity_restaurant

            FOREIGN KEY (restaurant_id)

            REFERENCES restaurants(id)

            ON DELETE SET NULL

    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
");


/*
|--------------------------------------------------------------------------
| CREATE DEFAULT SUPER ADMIN
|--------------------------------------------------------------------------
|
| Username: admin
| Password: admin123
|
| CHANGE THIS PASSWORD AFTER FIRST LOGIN.
|--------------------------------------------------------------------------
*/

$adminUsername = "admin";
$adminEmail = "admin@hamroresto.local";
$adminPassword = password_hash(
    "admin123",
    PASSWORD_DEFAULT
);

$checkAdmin = $conn->prepare("
    SELECT id
    FROM users
    WHERE username = ?
    LIMIT 1
");

$checkAdmin->bind_param(
    "s",
    $adminUsername
);

$checkAdmin->execute();

$adminResult = $checkAdmin->get_result();

if ($adminResult->num_rows === 0) {

    $insertAdmin = $conn->prepare("
        INSERT INTO users (
            username,
            email,
            password,
            role,
            restaurant_id,
            status
        )
        VALUES (?, ?, ?, 'super_admin', NULL, 'active')
    ");

    $insertAdmin->bind_param(
        "sss",
        $adminUsername,
        $adminEmail,
        $adminPassword
    );

    $insertAdmin->execute();

    $insertAdmin->close();
}

$checkAdmin->close();


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION READY
|--------------------------------------------------------------------------
|
| Other PHP files can now use:
|
| $conn
|
|--------------------------------------------------------------------------
*/

?>
