<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['e_mail'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: user_dashboard.php");
    exit();
}

$item_id = mysqli_real_escape_string($conn, $_GET['id']);
$user_email = $_SESSION['e_mail'];

// Check if item belongs to user
$query = "SELECT * FROM items WHERE id = '$item_id' AND user_email = '$user_email'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 1) {
    // Delete the item
    $delete_query = "DELETE FROM items WHERE id = '$item_id'";
    
    if (mysqli_query($conn, $delete_query)) {
        echo "<script>
            alert('Item deleted successfully!');
            window.location.href='user_dashboard.php';
        </script>";
    } else {
        echo "<script>
            alert('Error deleting item: " . mysqli_error($conn) . "');
            window.history.back();
        </script>";
    }
} else {
    echo "<script>
        alert('You cannot delete this item!');
        window.location.href='user_dashboard.php';
    </script>";
}
?>