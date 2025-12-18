<?php
// To hide lengthy error message
// error_reporting(0);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "accounts";

$conn = mysqli_connect($servername,$username,$password,$dbname);

if ($conn)
{
    // echo "CONNECTED";
}
else
{
    echo"CONNECTION FAILED".mysqli_connect_error();
}
?>