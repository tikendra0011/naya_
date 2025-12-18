<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['e_mail'])) {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['e_mail'];

// Get user information
$user_query = "SELECT * FROM register_account WHERE email = '$user_email'";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get user's reported items using email
$items_query = "SELECT * FROM items WHERE user_email = '$user_email' ORDER BY date_reported DESC";
$items_result = mysqli_query($conn, $items_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Find-Hub</title>
    <link rel="stylesheet" type="text/css" href="form.css">
    <style>
        body {
            background: #3aaf9f;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .dashboard-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-section h1 {
            margin: 0;
            color: rgb(2, 132, 89);
        }
        .welcome-section p {
            margin: 5px 0;
            color: #666;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        .btn-action {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-report {
            background: rgb(2, 132, 89);
            color: white;
        }
        .btn-logout {
            background: #6c757d;
            color: white;
        }
        .btn-report:hover {
            background: rgb(1, 110, 74);
        }
        .btn-logout:hover {
            background: #5a6268;
        }
        .section-title {
            color: white;
            font-size: 1.5em;
            margin: 30px 0 20px 0;
        }
        .items-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        .item-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .item-card:hover {
            transform: translateY(-5px);
        }
        .item-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .no-image {
            width: 100%;
            height: 200px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }
        .item-content {
            padding: 20px;
        }
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .item-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        .badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .badge-lost {
            background: #ff6b6b;
            color: white;
        }
        .badge-found {
            background: #4ecdc4;
            color: white;
        }
        .badge-open {
            background: #ffe66d;
            color: #333;
        }
        .badge-claimed {
            background: #1a535c;
            color: white;
        }
        .item-details {
            margin: 15px 0;
        }
        .item-details p {
            margin: 8px 0;
            color: #555;
        }
        .item-details strong {
            color: #333;
        }
        .item-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-small {
            flex: 1;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
        }
        .btn-edit {
            background: #3aaf9f;
            color: white;
        }
        .btn-delete {
            background: #ff6b6b;
            color: white;
        }
        .empty-state {
            background: white;
            padding: 50px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
        }
        .empty-state h3 {
            color: rgb(2, 132, 89);
            margin-bottom: 15px;
        }
        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }
        .description-text {
            line-height: 1.6;
            color: #555;
            margin: 10px 0;
        }
        .date-info {
            font-size: 0.9em;
            color: #888;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Header -->
        <div class="header-bar">
            <div class="welcome-section">
                <h1>Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h1>
                <p>Email: <?php echo htmlspecialchars($user_email); ?></p>
                <p>Manage your reported lost and found items</p>
            </div>
            <div class="action-buttons">
                <a href="report_item.php" class="btn-action btn-report">+ Report New Item</a>
                <a href="http://localhost/Findhub/crud/browse_items.php" class="btn-action btn-report">Browse Items</a>
                <a href="logout.php" class="btn-action btn-logout">Logout</a>                
            </div>
        </div>
        
        <!-- My Items Section -->
        <h2 class="section-title">My Reported Items</h2>
        
        <?php if (mysqli_num_rows($items_result) > 0): ?>
            <div class="items-container">
                <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                    <div class="item-card">
                        <!-- Item Image -->
                        <?php if (!empty($item['image_path'])): ?>
                            <img src="../Uploads/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="item-image">
                        <?php else: ?>
                            <div class="no-image">
                                <span>No Image Available</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="item-content">
                            <!-- Item Header -->
                            <div class="item-header">
                                <h3 class="item-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <div>
                                    <span class="badge badge-<?php echo $item['item_type']; ?>">
                                        <?php echo ucfirst($item['item_type']); ?>
                                    </span>
                                    <span class="badge badge-<?php echo $item['status']; ?>" style="margin-left: 5px;">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Item Details -->
                            <div class="item-details">
                                <p><strong>📍 Location:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                                <p><strong>📞 Contact:</strong> <?php echo htmlspecialchars($item['contact_info']); ?></p>
                                
                                <div class="description-text">
                                    <strong>📝 Description:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                                </div>
                                
                                <div class="date-info">
                                    Reported on: <?php echo date('F j, Y', strtotime($item['date_reported'])); ?>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="item-actions">
                                <a href="edit_item.php?id=<?php echo $item['id']; ?>" class="btn-small btn-edit">Edit</a>
                                <a href="delete_item.php?id=<?php echo $item['id']; ?>" 
                                   class="btn-small btn-delete" 
                                   onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>No Items Reported Yet</h3>
                <p>You haven't reported any lost or found items yet. Start by reporting your first item!</p>
                <a href="report_item.php" class="btn-action btn-report">Report Your First Item</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>