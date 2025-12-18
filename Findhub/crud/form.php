<?php include("connection.php"); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="form.css">
    <title>Registration Form</title>
</head>
<body>
    <div class="container">
        <form action="" method="post">
            <div class="title">Registration form</div>

            <div class="form">
                <div class="input_field">
                    <label>First Name</label>
                    <input type="text" class="input" name="first_name" required>
                </div>

                <div class="input_field">
                    <label>Last Name</label>
                    <input type="text" class="input" name="last_name" required>
                </div>

                <div class="input_field">
                    <label>Password</label>
                    <input type="password" class="input" name="password" required>
                </div>

                <div class="input_field">
                    <label>Confirm Password</label>
                    <input type="password" class="input" name="confirm_password" required>
                </div>

                <div class="input_field">
                    <label>Email</label>
                    <input type="email" class="input" name="email" required>
                </div>

                <div class="input_field">
                    <label>Phone Number</label>
                    <input type="text" class="input" name="ph_number" required>
                </div>

                <div class="input_field">
                    <input type="submit" value="Register" class="btn" name="register">
                </div>
            </div>
        </form>
    </div>
</body>
</html>

<?php  
if (isset($_POST['register'])) {
    $fname  = trim($_POST['first_name']);
    $lname  = trim($_POST['last_name']);
    $psw    = $_POST['password'];
    $cpsw   = $_POST['confirm_password'];
    $email  = trim($_POST['email']);
    $number = trim($_POST['ph_number']);

    // To check Password is match or not
    if ($psw !== $cpsw) {
        echo "<script>alert('Passwords do not match!')</script>";
        exit;
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format!')</script>";
        exit;
    }

    // Phone Number validation
    //1. Mobile (98/974/976 prefixed, 10 digits)
    // 2. Landline (8 digits, often prefixed with '0' for domestic calls)
    // 3. Optional Country Code (+977 or 00977)

    if (!preg_match("/^(\+977|00977|0)?[\s-]*(?:(?:9[6-8][0-9]{8})|(?:1660[0-9]{7})|(?:[1-9][0-9]{7}))$/", $number)) {
        echo "<script>alert('Invalid phone number!')</script>";
        exit;
    }

    // Password strength
    if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/", $psw)) {
        echo "<script>alert('Password must be at least 8 characters long and contain letters & numbers!')</script>";
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($psw, PASSWORD_DEFAULT);

    // Prepared statement
    $stmt = $conn->prepare("INSERT INTO register_account (first_name, last_name, password, email, phone_number, role) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        exit;
    }

    $role = "user"; 

    $stmt->bind_param("ssssss", $fname, $lname, $hashedPassword, $email, $number, $role);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful!'); window.location.href='login.php';</script>";
        exit();
    } else {
        echo "Registration Failed! " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>