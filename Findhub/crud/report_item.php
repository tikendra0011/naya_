<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['e_mail'])) {
    header("Location: login.php");
    exit();
}

// Get user email from session
$user_email = $_SESSION['e_mail'];

// Initialize variables
$title = $description = $location = $contact = $item_type = "";
$success_message = $error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_report'])) {
    // Get form data
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $contact = $_POST['contact'];
    $item_type = $_POST['item_type'];
    
    // Basic validation
    if (empty($title) || empty($description) || empty($location) || empty($contact)) {
        $error_message = "Please fill all required fields marked with *";
    } else {
        // Handle image upload
        $image_path = "";
        if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $file_type = $_FILES['item_image']['type'];
            $file_size = $_FILES['item_image']['size'];
            
            if (in_array($file_type, $allowed_types)) {
                if ($file_size < 5000000) { // 5MB limit
                    $image_name = time() . '_' . basename($_FILES['item_image']['name']);
                    $target_dir = "../Uploads/items/";
                    $target_file = $target_dir . $image_name;
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    
                    if (move_uploaded_file($_FILES['item_image']['tmp_name'], $target_file)) {
                        $image_path = "items/" . $image_name;
                    } else {
                        $error_message = "Sorry, there was an error uploading your image.";
                    }
                } else {
                    $error_message = "Image size is too large. Max 5MB allowed.";
                }
            } else {
                $error_message = "Only JPG, JPEG, PNG & GIF files are allowed.";
            }
        }
        
        // If no error, insert into database
        if (empty($error_message)) {
            // Use prepared statement to prevent SQL injection
            $stmt = mysqli_prepare($conn, 
                "INSERT INTO items (user_email, title, description, location, contact_info, item_type, image_path) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            mysqli_stmt_bind_param($stmt, "sssssss", 
                $user_email, $title, $description, $location, $contact, $item_type, $image_path);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Item reported successfully!";
                // Clear form
                $title = $description = $location = $contact = $item_type = "";
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Item - Find-Hub</title>
    <link rel="stylesheet" type="text/css" href="form.css">
    <style>
        .message {
            padding: 10px;
            margin: 15px 0;
            border-radius: 5px;
            text-align: center;
        }
        .success-msg {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .radio-group {
            display: flex;
            gap: 20px;
            margin: 10px 0;
        }
        .radio-option {
            display: flex;
            align-items: center;
        }
        .radio-option input {
            margin-right: 8px;
        }
        textarea.input {
            min-height: 100px;
            resize: vertical;
            padding: 10px;
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
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        small {
            color: #666;
            font-size: 0.9em;
        }
        .required {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <form action="" method="post" enctype="multipart/form-data">
            <div class="title">
                Report Lost/Found Item
            </div>
            
            <!-- Display Messages -->
            <?php if ($success_message): ?>
                <div class="message success-msg"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="message error-msg"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <div class="form">
                <!-- Item Type -->
                <div class="input_field">
                    <label>Report Type <span class="required">*</span></label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="lost" name="item_type" value="lost" <?php echo ($item_type == 'lost' || empty($item_type)) ? 'checked' : ''; ?> required>
                            <label for="lost">Lost Item</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="found" name="item_type" value="found" <?php echo ($item_type == 'found') ? 'checked' : ''; ?>>
                            <label for="found">Found Item</label>
                        </div>
                    </div>
                </div>
                
                <!-- Item Title -->
                <div class="input_field">
                    <label>Item Title <span class="required">*</span></label>
                    <input type="text" class="input" name="title" value="<?php echo htmlspecialchars($title); ?>" placeholder="What did you lose/find?" required>
                </div>
                
                <!-- Description -->
                <div class="input_field">
                    <label>Description <span class="required">*</span></label>
                    <textarea class="input" name="description" placeholder="Describe the item in detail..." required><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                
                <!-- Location -->
                <div class="input_field">
                    <label>Location <span class="required">*</span></label>
                    <input type="text" class="input" name="location" value="<?php echo htmlspecialchars($location); ?>" placeholder="Where did you lose/find it?" required>
                </div>
                
                <!-- Contact Information -->
                <div class="input_field">
                    <label>Contact Information <span class="required">*</span></label>
                    <input type="text" class="input" name="contact" value="<?php echo htmlspecialchars($contact); ?>" placeholder="Phone or email for contact" required>
                    <small>This will be shown to others</small>
                </div>
                
                <!-- Image Upload -->
                <div class="input_field">
                    <label>Upload Image (Optional)</label>
                    <input type="file" class="input" name="item_image" accept="image/*">
                    <small>Max 5MB, Allowed: JPG, PNG, GIF</small>
                </div>
                
                <!-- Button Container -->
                <div class="input_field">
                    <div class="btn-container">
                        <a href="user_dashboard.php" class="btn-secondary">Back to Dashboard</a>
                        <input type="submit" value="Submit Report" class="btn" name="submit_report">
                    </div>
                </div>
                
                <!-- User Info -->
                <div style="text-align: center; margin-top: 20px; color: #666;">
                    <small>Reporting as: <?php echo htmlspecialchars($user_email); ?></small>
                </div>
            </div>
        </form>
    </div>
</body>
</html>