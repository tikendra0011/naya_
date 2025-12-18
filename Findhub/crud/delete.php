<?php
include("connection.php");
if (isset($_GET['em'])) {
    $em = $_GET['em'];

$query = "DELETE FROM register_account WHERE email='$em'";

$data = mysqli_query($conn,$query);

if ($data) {
        echo "<script>alert('Record deleted successfully')</script>";
        echo "<meta http-equiv='refresh' content='2; URL=display.php'>";
    } else {
        echo "Delete operation failed: " . mysqli_error($conn);
    }
} else {
    echo "<script>alert('No email found in URL! Delete operation cancelled.')</script>";
}

?>
