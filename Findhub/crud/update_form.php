<?php
session_start();
include("connection.php");

/* =======================
   AUTH & ROLE PROTECTION
   ======================= */
if (!isset($_SESSION['e_mail']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

/* =======================
   GET USER BY EMAIL
   ======================= */
if (!isset($_GET['em'])) {
    header("Location: update_form.php");
    exit();
}

$em = trim($_GET['em']);

/* =======================
   FETCH USER DATA
   ======================= */
$stmt = $conn->prepare(
    "SELECT first_name, last_name, email, phone_number 
     FROM register_account 
     WHERE email = ?"
);
$stmt->bind_param("s", $em);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: display.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="form.css">
    <title>Update Profile</title>
</head>
<body>
<div class="container">
    <form method="post">
        <div class="title">Update Profile</div>

        <div class="form">
            <div class="input_field">
                <label>First Name</label>
                <input type="text" name="first_name" class="input"
                       value="<?= htmlspecialchars($user['first_name']) ?>" required>
            </div>

            <div class="input_field">
                <label>Last Name</label>
                <input type="text" name="last_name" class="input"
                       value="<?= htmlspecialchars($user['last_name']) ?>" required>
            </div>

            <div class="input_field">
                <label>Email</label>
                <input type="email" name="email" class="input"
                       value="<?= htmlspecialchars($user['email']) ?>" required readonly>
            </div>

            <div class="input_field">
                <label>Phone Number</label>
                <input type="text" name="ph_number" class="input"
                       value="<?= htmlspecialchars($user['phone_number']) ?>" required>
            </div>

            <hr>

            <div class="input_field">
                <label>New Password (optional)</label>
                <input type="password" name="password" class="input"
                       placeholder="Leave blank to keep current password">
            </div>

            <div class="input_field">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="input">
            </div>

            <div class="input_field">
                <input type="submit" name="update" value="Update" class="btn">
            </div>
        </div>
    </form>
</div>
</body>
</html>

<?php
/* =======================
   UPDATE LOGIC
   ======================= */
if (isset($_POST['update'])) {

    $fname  = trim($_POST['first_name']);
    $lname  = trim($_POST['last_name']);
    $number = trim($_POST['ph_number']);
    $psw    = $_POST['password'];
    $cpsw   = $_POST['confirm_password'];

    if ($fname === "" || $lname === "" || $number === "") {
        echo "<script>alert('All fields are required!')</script>";
        exit();
    }

    /* =======================
       PASSWORD UPDATE (OPTIONAL)
       ======================= */
    if ($psw !== "") {

        if ($psw !== $cpsw) {
            echo "<script>alert('Passwords do not match!')</script>";
            exit();
        }

        if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/", $psw)) {
            echo "<script>alert('Weak password!')</script>";
            exit();
        }

        $hashedPassword = password_hash($psw, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "UPDATE register_account 
             SET first_name=?, last_name=?, phone_number=?, password=? 
             WHERE email=?"
        );
        $stmt->bind_param(
            "sssss",
            $fname,
            $lname,
            $number,
            $hashedPassword,
            $em
        );

    } else {
        // Update WITHOUT changing password
        $stmt = $conn->prepare(
            "UPDATE register_account 
             SET first_name=?, last_name=?, phone_number=? 
             WHERE email=?"
        );
        $stmt->bind_param(
            "ssss",
            $fname,
            $lname,
            $number,
            $em
        );
    }

    if ($stmt->execute()) {
        echo "<script>
            alert('Profile updated successfully!');
            window.location.href='display.php';
        </script>";
        exit();
    } else {
        echo "Update failed: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
