<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['e_mail'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: browse_items.php");
    exit();
}

$item_id = mysqli_real_escape_string($conn, $_GET['id']);
$claimer_email = $_SESSION['e_mail'];

// Fetch item details
$item_query = "SELECT i.*, r.first_name, r.last_name, r.phone_number 
               FROM items i 
               LEFT JOIN register_account r ON i.user_email = r.email 
               WHERE i.id = '$item_id' AND i.status = 'open'";
$item_result = mysqli_query($conn, $item_query);

if (mysqli_num_rows($item_result) != 1) {
    header("Location: browse_items.php?error=Item not found or already claimed");
    exit();
}

$item = mysqli_fetch_assoc($item_result);

// Check if user is trying to claim their own item
if ($item['user_email'] == $claimer_email) {
    header("Location: browse_items.php?error=You cannot claim your own item");
    exit();
}

// Get claimer info
$claimer_query = "SELECT first_name, last_name, phone_number FROM register_account WHERE email = '$claimer_email'";
$claimer_result = mysqli_query($conn, $claimer_query);
$claimer = mysqli_fetch_assoc($claimer_result);

// Handle claim submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_claim'])) {
    $claim_message = mysqli_real_escape_string($conn, $_POST['claim_message']);
    $claimer_contact = mysqli_real_escape_string($conn, $_POST['claimer_contact']);
    
    // Create claims table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS claims (
        id INT PRIMARY KEY AUTO_INCREMENT,
        item_id INT NOT NULL,
        item_type VARCHAR(10) NOT NULL,
        item_title VARCHAR(100) NOT NULL,
        item_owner_email VARCHAR(100) NOT NULL,
        claimer_email VARCHAR(100) NOT NULL,
        claimer_name VARCHAR(100) NOT NULL,
        claimer_contact VARCHAR(100) NOT NULL,
        claim_message TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_item_id (item_id),
        INDEX idx_claimer_email (claimer_email)
    )";
    
    mysqli_query($conn, $create_table_sql);
    
    // Insert claim
    $insert_query = "INSERT INTO claims (item_id, item_type, item_title, item_owner_email, 
                                         claimer_email, claimer_name, claimer_contact, claim_message) 
                     VALUES ('$item_id', '{$item['item_type']}', '{$item['title']}', 
                             '{$item['user_email']}', '$claimer_email', 
                             '{$claimer['first_name']} {$claimer['last_name']}', 
                             '$claimer_contact', '$claim_message')";
    
    if (mysqli_query($conn, $insert_query)) {
        // Update item status to claimed
        $update_query = "UPDATE items SET status = 'claimed' WHERE id = '$item_id'";
        mysqli_query($conn, $update_query);
        
        $success_message = "Claim submitted successfully! The item owner will contact you.";
    } else {
        $error_message = "Error submitting claim: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Item - Find-Hub</title>
    <link rel="stylesheet" type="text/css" href="form.css">
    <link rel="stylesheet" href="../Frontend/claim_item.css">
 </head>
<body>
    <div class="claim-container">
        <div class="title" style="text-align: center; margin-bottom: 30px;">
            Claim Item
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <h3>✅ Claim Submitted Successfully!</h3>
                <p><?php echo $success_message; ?></p>
                <div style="margin-top: 20px;">
                    <a href="browse_items.php" class="btn">Browse More Items</a>
                    <a href="user_dashboard.php" class="btn" style="margin-left: 10px;">Go to Dashboard</a>
                </div>
            </div>
        <?php else: ?>
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Item Preview -->
            <div class="item-preview">
                <div class="preview-header">
                    <div class="preview-title"><?php echo htmlspecialchars($item['title']); ?></div>
                    <div class="preview-badge badge-<?php echo $item['item_type']; ?>">
                        <?php echo ucfirst($item['item_type']); ?> Item
                    </div>
                </div>
                
                <div class="preview-details">
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($item['description']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                    <p><strong>Reported by:</strong> <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></p>
                    <p><strong>Reported on:</strong> <?php echo date('F j, Y', strtotime($item['date_reported'])); ?></p>
                </div>
            </div>
            
            <!-- Claimer Information -->
            <div class="claimer-info">
                <h3 style="color: rgb(2, 132, 89); margin-bottom: 15px;">Your Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Your Name:</span>
                        <?php echo htmlspecialchars($claimer['first_name'] . ' ' . $claimer['last_name']); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Your Email:</span>
                        <?php echo htmlspecialchars($claimer_email); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Your Phone:</span>
                        <?php echo htmlspecialchars($claimer['phone_number'] ?? 'Not provided'); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Item Type:</span>
                        <?php echo ucfirst($item['item_type']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Claim Form -->
            <form method="post" action="">
                <div class="form">
                    <!-- Contact Information -->
                    <div class="input_field">
                        <label>Contact Information for Owner*</label>
                        <input type="text" class="input" name="claimer_contact" 
                               value="<?php echo htmlspecialchars($claimer['phone_number'] ?? $claimer_email); ?>"
                               placeholder="Phone number or email where owner can reach you" required>
                    </div>
                    
                    <!-- Claim Message -->
                    <div class="input_field">
                        <label>Message to Item Owner*</label>
                        <div class="message-box">
                            <textarea name="claim_message" placeholder="Explain why you think this is your item or how you found it..." required><?php echo isset($_POST['claim_message']) ? htmlspecialchars($_POST['claim_message']) : ''; ?></textarea>
                        </div>
                        <small>This message will be sent to the item owner</small>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="input_field">
                        <div class="btn-container">
                            <a href="browse_items.php" class="btn-cancel">Cancel</a>
                            <input type="submit" value="Submit Claim" class="btn" name="submit_claim">
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>