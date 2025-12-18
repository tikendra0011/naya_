<?php
session_start();
include('connection.php');

// Check if admin is logged in
if (!isset($_SESSION['e_mail']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if email is provided
if (!isset($_GET['em'])) {
    echo "<script>
        alert('No email specified!');
        window.location.href='display.php';
    </script>";
    exit();
}

$email = mysqli_real_escape_string($conn, $_GET['em']);

// Get user details
$user_query = "SELECT * FROM register_account WHERE email = '$email'";
$user_result = mysqli_query($conn, $user_query);

if (mysqli_num_rows($user_result) != 1) {
    echo "<script>
        alert('User not found!');
        window.location.href='display.php';
    </script>";
    exit();
}

$user = mysqli_fetch_assoc($user_result);

// Don't allow deleting admin users
if ($user['role'] == 'admin') {
    echo "<script>
        alert('Cannot delete admin users!');
        window.location.href='display.php';
    </script>";
    exit();
}

// SOFT DELETE: Mark user as inactive instead of deleting
$update_query = "UPDATE register_account SET status = 'inactive' WHERE email = '$email'";

if (mysqli_query($conn, $update_query)) {
    echo "<script>
        alert('User marked as inactive. Data preserved!');
        window.location.href='display.php';
    </script>";
} else {
    echo "<script>
        alert('Error: " . mysqli_error($conn) . "');
        window.history.back();
    </script>";
}
?>