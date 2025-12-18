<?php
session_start();
include('connection.php');

// Check if admin is logged in
if (!isset($_SESSION['e_mail']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if email is provided
if (!isset($_GET['em'])) {
    header("Location: display.php");
    exit();
}

$email = mysqli_real_escape_string($conn, $_GET['em']);

// Get user details
$user_query = "SELECT * FROM register_account WHERE email = '$email'";
$user_result = mysqli_query($conn, $user_query);

if (mysqli_num_rows($user_result) != 1) {
    header("Location: display.php?error=User not found");
    exit();
}

$user = mysqli_fetch_assoc($user_result);

// Get user's reported items count
$items_query = "SELECT COUNT(*) as item_count FROM items WHERE user_email = '$email'";
$items_result = mysqli_query($conn, $items_query);
$items_data = mysqli_fetch_assoc($items_result);
$item_count = $items_data['item_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User - Find-Hub</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #3aaf9f 0%, rgb(2, 132, 89) 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .user-container {
            background: white;
            width: 100%;
            max-width: 800px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .user-header {
            background: linear-gradient(to right, #3aaf9f, rgb(2, 132, 89));
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .user-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5em;
            color: rgb(2, 132, 89);
            border: 5px solid white;
        }
        
        .user-name {
            font-size: 1.8em;
            margin-bottom: 10px;
        }
        
        .user-email {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .user-content {
            padding: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid #3aaf9f;
        }
        
        .info-label {
            font-weight: bold;
            color: #333;
            display: block;
            margin-bottom: 8px;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .info-value {
            font-size: 1.2em;
            color: rgb(2, 132, 89);
        }
        
        .badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-blocked {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-inactive {
            background: #fff3cd;
            color: #856404;
        }
        
        .role-admin {
            background: #6f42c1;
            color: white;
        }
        
        .role-user {
            background: #20c997;
            color: white;
        }
        
        .stats-section {
            background: #e8f4f1;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: rgb(2, 132, 89);
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
        }
        
        .btn-edit {
            background: #3aaf9f;
            color: white;
        }
        
        .btn-block {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-unblock {
            background: #28a745;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .info-grid, .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="user-container">
        <!-- User Header -->
        <div class="user-header">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
            </div>
            <h1 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
            <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
        
        <!-- User Information -->
        <div class="user-content">
            <div class="info-grid">
                <div class="info-card">
                    <span class="info-label">Phone Number</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['phone_number'] ?? 'Not provided'); ?></span>
                </div>
                
                <div class="info-card">
                    <span class="info-label">Role</span>
                    <span class="badge role-<?php echo $user['role']; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
                
                <div class="info-card">
                    <span class="info-label">Status</span>
                    <span class="badge status-<?php echo $user['status']; ?>">
                        <?php echo ucfirst($user['status']); ?>
                    </span>
                </div>
                
                <div class="info-card">
                    <span class="info-label">Member Since</span>
                    <span class="info-value">
                        <?php echo isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A'; ?>
                    </span>
                </div>
            </div>
            
            <!-- User Statistics -->
            <div class="stats-section">
                <h3 style="color: rgb(2, 132, 89); margin-bottom: 20px; text-align: center;">User Activity</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $item_count; ?></div>
                        <div class="stat-label">Items Reported</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">
                            <?php 
                            // Get active items count
                            $active_items_query = "SELECT COUNT(*) as active_count FROM items 
                                                   WHERE user_email = '$email' AND status = 'open'";
                            $active_items_result = mysqli_query($conn, $active_items_query);
                            $active_items = mysqli_fetch_assoc($active_items_result);
                            echo $active_items['active_count'];
                            ?>
                        </div>
                        <div class="stat-label">Active Items</div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="display.php" class="btn btn-back">← Back to Users</a>
                
                <?php if ($user['role'] != 'admin'): ?>
                    <a href="update_form.php?em=<?php echo urlencode($user['email']); ?>" 
                       class="btn btn-edit">✏️ Edit User</a>
                    
                    <?php if ($user['status'] == 'active'): ?>
                        <a href="block_user.php?em=<?php echo urlencode($user['email']); ?>&action=block" 
                           class="btn btn-block"
                           onclick="return confirm('Are you sure you want to BLOCK this user?')">🚫 Block User</a>
                    <?php elseif ($user['status'] == 'blocked'): ?>
                        <a href="block_user.php?em=<?php echo urlencode($user['email']); ?>&action=unblock" 
                           class="btn btn-unblock"
                           onclick="return confirm('Are you sure you want to UNBLOCK this user?')">✅ Unblock User</a>
                    <?php elseif ($user['status'] == 'inactive'): ?>
                        <a href="block_user.php?em=<?php echo urlencode($user['email']); ?>&action=activate" 
                           class="btn btn-unblock"
                           onclick="return confirm('Are you sure you want to ACTIVATE this user?')">▶️ Activate User</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Confirm before blocking/unblocking
        document.querySelectorAll('.btn-block, .btn-unblock').forEach(button => {
            button.addEventListener('click', function(e) {
                const action = this.classList.contains('btn-block') ? 'block' : 
                             this.classList.contains('btn-unblock') ? 'unblock/activate' : '';
                
                if (!confirm(`Are you sure you want to ${action} this user?`)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>