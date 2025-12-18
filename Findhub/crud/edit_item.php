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

// Fetch item details
$query = "SELECT * FROM items WHERE id = '$item_id' AND user_email = '$user_email'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) != 1) {
    // Item doesn't exist or doesn't belong to user
    header("Location: user_dashboard.php");
    exit();
}

$item = mysqli_fetch_assoc($result);

// Handle form submission for update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $contact = $_POST['contact'];
    $status = $_POST['status'];
    
    // Validate
    if (empty($title) || empty($description) || empty($location) || empty($contact)) {
        $error_message = "Please fill all required fields.";
    } else {
        $update_query = "UPDATE items SET 
                        title = '$title',
                        description = '$description',
                        location = '$location',
                        contact_info = '$contact',
                        status = '$status'
                        WHERE id = '$item_id' AND user_email = '$user_email'";
        
        if (mysqli_query($conn, $update_query)) {
            echo "<script>
                alert('Item updated successfully!');
                window.location.href='user_dashboard.php';
            </script>";
            exit();
        } else {
            $error_message = "Error updating item: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item - Find-Hub</title>
    <link rel="stylesheet" type="text/css" href="form.css">
    <style>
        .message {
            padding: 10px;
            margin: 15px 0;
            border-radius: 5px;
            text-align: center;
        }
        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        textarea.input {
            min-height: 100px;
            resize: vertical;
        }
        .item-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .item-info p {
            margin: 5px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <form method="post">
            <div class="title">
                Edit Item
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="message error-msg"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <div class="item-info">
                <p><strong>Reported by:</strong> <?php echo htmlspecialchars($user_email); ?></p>
                <p><strong>Reported on:</strong> <?php echo date('F j, Y', strtotime($item['date_reported'])); ?></p>
                <p><strong>Type:</strong> <?php echo ucfirst($item['item_type']); ?></p>
            </div>
            
            <div class="form">
                <!-- Item Title -->
                <div class="input_field">
                    <label>Item Title*</label>
                    <input type="text" class="input" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                </div>
                
                <!-- Description -->
                <div class="input_field">
                    <label>Description*</label>
                    <textarea class="input" name="description" required><?php echo htmlspecialchars($item['description']); ?></textarea>
                </div>
                
                <!-- Location -->
                <div class="input_field">
                    <label>Location*</label>
                    <input type="text" class="input" name="location" value="<?php echo htmlspecialchars($item['location']); ?>" required>
                </div>
                
                <!-- Contact Information -->
                <div class="input_field">
                    <label>Contact Information*</label>
                    <input type="text" class="input" name="contact" value="<?php echo htmlspecialchars($item['contact_info']); ?>" required>
                </div>
                
                <!-- Status -->
                <div class="input_field">
                    <label>Status</label>
                    <select name="status" class="input" required>
                        <option value="open" <?php echo ($item['status'] == 'open') ? 'selected' : ''; ?>>Open</option>
                        <option value="claimed" <?php echo ($item['status'] == 'claimed') ? 'selected' : ''; ?>>Claimed</option>
                        <option value="resolved" <?php echo ($item['status'] == 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>
                
                <!-- Buttons -->
                <div class="input_field">
                    <div class="btn-container">
                        <a href="user_dashboard.php" class="btn-secondary">Cancel</a>
                        <input type="submit" value="Update Item" class="btn" name="update_item">
                    </div>
                </div>
            </div>
        </form>
    </div>
</body>
</html>