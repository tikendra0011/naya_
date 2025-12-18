<?php
session_start();
include('connection.php');

// Check if admin is logged in
if (!isset($_SESSION['e_mail']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if email and action are provided
if (!isset($_GET['em']) || !isset($_GET['action'])) {
    header("Location: display.php?error=Missing parameters");
    exit();
}

$email = mysqli_real_escape_string($conn, $_GET['em']);
$action = $_GET['action'];

// Get user details first
$user_query = "SELECT * FROM register_account WHERE email = '$email'";
$user_result = mysqli_query($conn, $user_query);

if (mysqli_num_rows($user_result) != 1) {
    header("Location: display.php?error=User not found");
    exit();
}

$user = mysqli_fetch_assoc($user_result);

// Don't allow blocking admin users
if ($user['role'] == 'admin' && $action == 'block') {
    header("Location: display.php?error=Cannot block admin users");
    exit();
}

// Determine new status based on action
switch ($action) {
    case 'block':
        $new_status = 'blocked';
        $message = "User blocked successfully!";
        break;
        
    case 'unblock':
        $new_status = 'active';
        $message = "User unblocked successfully!";
        break;
        
    case 'activate':
        $new_status = 'active';
        $message = "User activated successfully!";
        break;
        
    default:
        header("Location: display.php?error=Invalid action");
        exit();
}

// Update user status
$update_query = "UPDATE register_account SET status = '$new_status' WHERE email = '$email'";

if (mysqli_query($conn, $update_query)) {
    // Log the action (optional - you can create an admin_logs table later)
    $admin_email = $_SESSION['e_mail'];
    $log_message = "$admin_email $action user $email";
    
    echo "<script>
        alert('$message');
        window.location.href = 'display.php';
    </script>";
} else {
    echo "<script>
        alert('Error: " . mysqli_error($conn) . "');
        window.history.back();
    </script>";
}
?>