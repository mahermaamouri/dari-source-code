<?php
// setup_admin.php
// Run this script ONCE to create your initial admin user.
// DELETE OR SECURE THIS FILE AFTER USE.

require_once '../config/init.php'; // Ensure database connection is available

global $pdo;
if (!$pdo) {
    die("Database connection failed. Cannot setup admin.");
}

// --- Admin User Details ---
$admin_username = 'admin';
$admin_email = 'maamourimaher@gmail.com';
$admin_password_plain = '050ABAA7'; // Choose a strong password
$admin_full_name = 'Dari Administrator';
// --- End Admin User Details ---


// Check if admin already exists
try {
    $stmt_check = $pdo->prepare("SELECT admin_id FROM Admins WHERE username = :username OR email = :email");
    $stmt_check->execute([':username' => $admin_username, ':email' => $admin_email]);
    if ($stmt_check->fetch()) {
        echo "Admin user with username '{$admin_username}' or email '{$admin_email}' already exists. No action taken.";
        exit;
    }
} catch (PDOException $e) {
    die("Error checking for existing admin: " . $e->getMessage());
}


// Hash the password
$admin_password_hash = password_hash($admin_password_plain, PASSWORD_DEFAULT);

if (!$admin_password_hash) {
    die("Failed to hash the password. Please check your PHP version and configuration.");
}

// Insert the admin user
try {
    $sql = "INSERT INTO Admins (username, password_hash, email, full_name, created_at) 
            VALUES (:username, :password_hash, :email, :full_name, NOW())";
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':username', $admin_username);
    $stmt->bindParam(':password_hash', $admin_password_hash);
    $stmt->bindParam(':email', $admin_email);
    $stmt->bindParam(':full_name', $admin_full_name);
    
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo "Admin user '{$admin_username}' created successfully!<br>";
        echo "Username: " . htmlspecialchars($admin_username) . "<br>";
        echo "Password: " . htmlspecialchars($admin_password_plain) . " (This is the plain password you chose, use it to log in)<br>";
        echo "<strong>IMPORTANT: Delete or secure this setup_admin.php file immediately!</strong>";
    } else {
        echo "Failed to create admin user. No rows affected.";
    }

} catch (PDOException $e) {
    echo "Database error creating admin user: " . $e->getMessage();
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage();
}

?>
