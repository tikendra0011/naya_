<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="form.css">
    <title>Login Form</title>
</head>

<body>
    <div class="container">
        <form action="#" method="post">
            <div class="title">
                Login
            </div>


            <div class="form">
                <div class="input_field">
                    <label>Email</label>
                    <input type="email" name="email" class="input" placeholder="Email">
                </div>

                <div class="input_field">
                    <label>Password</label>
                    <input type="password" name="password" class="input" placeholder="Password">
                </div>

                <div class="input_field">
                    <input type="submit" value="Login" class="btn" name="login">
                </div>

                <div>New Member ?<a href="form.php" class="link">Sign Up Here</a></div>


            </div>
    </div>
    </form>

</body>

</html>

<?php
// session_start();
include("connection.php");

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    // 1. Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format!');</script>";
        exit;
    }

    // 2. Prepared statement to fetch user
    $stmt = $conn->prepare("SELECT password, role FROM register_account WHERE email = ?");
    if ($stmt === false) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        exit;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // 3. Check if user exists
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $hashedPassword = $row['password'];
        $role = $row['role'];

        // 4. Verify password
        if (password_verify($pass, $hashedPassword)) {

               // ADD THIS CHECK FOR USER STATUS
        $status = $row['status'] ?? 'active';
        
        // Check if user is blocked or inactive
        if ($status == 'blocked') {
            echo "<script>alert('Your account has been blocked. Please contact administrator.');</script>";
            exit;
        } elseif ($status == 'inactive') {
            echo "<script>alert('Your account is inactive. Please contact administrator.');</script>";
            exit;
        }
        
            $_SESSION['e_mail'] = $email;
            $_SESSION['role']   = $role;


            // Role-based redirect
            
            if ($role === 'admin') {
                header("Location: display.php");
            } else {
                header("Location: user_dashboard.php");  // Redirect to dashboard
            }
            exit();

        } else {
            echo "<script>alert('Invalid email or password!');</script>";
        }
    } else {
        echo "<script>alert('Invalid email or password!');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>