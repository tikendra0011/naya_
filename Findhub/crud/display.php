<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['e_mail']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include("connection.php");

// Handle search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query with filters
$query = "SELECT * FROM register_account WHERE 1=1";

// Apply search filter
if (!empty($search)) {
    $query .= " AND (first_name LIKE '%$search%' 
                    OR last_name LIKE '%$search%' 
                    OR email LIKE '%$search%'
                    OR phone_number LIKE '%$search%')";
}

// Apply status filter
if ($status_filter != 'all') {
    $query .= " AND status = '$status_filter'";
}

$query .= " ORDER BY created_at DESC";
$data = mysqli_query($conn, $query);

$total = mysqli_num_rows($data);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
    SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_users,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users
    FROM register_account";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Find-Hub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #3aaf9f 0%, rgb(2, 132, 89) 100%);
            min-height: 100vh;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header Styles */
        .admin-header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header h1 {
            color: rgb(2, 132, 89);
            font-size: 1.8em;
        }
        
        .admin-info {
            text-align: right;
        }
        
        .admin-info p {
            margin: 5px 0;
            color: #666;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: rgb(2, 132, 89);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        /* Search and Filter Section */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 300px;
            padding: 12px 15px;
            border: 2px solid #3aaf9f;
            border-radius: 25px;
            font-size: 16px;
        }
        
        .filter-select {
            padding: 12px 20px;
            border: 2px solid #3aaf9f;
            border-radius: 25px;
            background: white;
            font-size: 16px;
        }
        
        .btn-search {
            background: rgb(2, 132, 89);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn-clear {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        /* Users Table */
        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(to right, #3aaf9f, rgb(2, 132, 89));
            color: white;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85em;
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
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9em;
            transition: all 0.3s;
        }
        
        .btn-view {
            background: #17a2b8;
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
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-action:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        /* Role Badges */
        .role-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
            display: inline-block;
        }
        
        .role-admin {
            background: #6f42c1;
            color: white;
        }
        
        .role-user {
            background: #20c997;
            color: white;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px;
        }
        
        .empty-state h3 {
            color: rgb(2, 132, 89);
            margin-bottom: 15px;
        }
        
        /* Logout Button */
        .logout-section {
            text-align: center;
            margin-top: 40px;
        }
        
        .btn-logout {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .admin-info {
                text-align: center;
            }
            
            .search-box {
                min-width: 100%;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Admin Header -->
        <div class="admin-header">
            <h1>👑 Admin Dashboard - Find-Hub</h1>
            <div class="admin-info">
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['e_mail']); ?></p>
                <p>Role: <span style="color: rgb(2, 132, 89); font-weight: bold;">Administrator</span></p>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo $stats['total_users'] ?? 0; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo $stats['active_users'] ?? 0; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🚫</div>
                <div class="stat-number"><?php echo $stats['blocked_users'] ?? 0; ?></div>
                <div class="stat-label">Blocked Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏸️</div>
                <div class="stat-number"><?php echo $stats['inactive_users'] ?? 0; ?></div>
                <div class="stat-label">Inactive Users</div>
            </div>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <input type="text" 
                       name="search" 
                       class="search-box" 
                       placeholder="Search users by name, email, or phone..."
                       value="<?php echo htmlspecialchars($search); ?>">
                
                <select name="status" class="filter-select">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Users</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active Users</option>
                    <option value="blocked" <?php echo $status_filter == 'blocked' ? 'selected' : ''; ?>>Blocked Users</option>
                    <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive Users</option>
                </select>
                
                <button type="submit" class="btn-search">🔍 Search</button>
                
                <?php if (!empty($search) || $status_filter != 'all'): ?>
                    <a href="display.php" class="btn-clear">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="table-container">
            <?php if ($total > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($result = mysqli_fetch_assoc($data)): ?>
                            <tr>
                                <td>#<?php echo $result['id'] ?? 'N/A'; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($result['email']); ?></td>
                                <td><?php echo htmlspecialchars($result['phone_number'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $result['role'] ?? 'user'; ?>">
                                        <?php echo ucfirst($result['role'] ?? 'user'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $result['status'] ?? 'active'; ?>">
                                        <?php echo ucfirst($result['status'] ?? 'active'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo isset($result['created_at']) ? date('M j, Y', strtotime($result['created_at'])) : 'N/A'; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- View Button -->
                                        <a href="view_user.php?em=<?php echo urlencode($result['email']); ?>" 
                                           class="btn-action btn-view">👁️ View</a>
                                        
                                        <!-- Edit Button -->
                                        <a href="update_form.php?em=<?php echo urlencode($result['email']); ?>" 
                                           class="btn-action btn-edit">✏️ Edit</a>
                                        
                                        <!-- Block/Unblock Button -->
                                        <?php if (($result['status'] ?? 'active') == 'active'): ?>
                                            <a href="block_user.php?em=<?php echo urlencode($result['email']); ?>&action=block" 
                                               class="btn-action btn-block"
                                               onclick="return confirm('Are you sure you want to BLOCK this user?')">🚫 Block</a>
                                        <?php elseif (($result['status'] ?? 'active') == 'blocked'): ?>
                                            <a href="block_user.php?em=<?php echo urlencode($result['email']); ?>&action=unblock" 
                                               class="btn-action btn-unblock"
                                               onclick="return confirm('Are you sure you want to UNBLOCK this user?')">✅ Unblock</a>
                                        <?php elseif (($result['status'] ?? 'active') == 'inactive'): ?>
                                            <a href="block_user.php?em=<?php echo urlencode($result['email']); ?>&action=activate" 
                                               class="btn-action btn-unblock"
                                               onclick="return confirm('Are you sure you want to ACTIVATE this user?')">▶️ Activate</a>
                                        <?php endif; ?>
                                        
                                        <!-- Delete Button (Soft Delete Only) -->
                                        <?php if (($result['role'] ?? 'user') != 'admin'): ?>
                                            <a href="delete_user.php?em=<?php echo urlencode($result['email']); ?>" 
                                               class="btn-action btn-delete"
                                               onclick="return confirm('Are you sure you want to Stop this user? This will mark them as inactive.')">🗑️ Inactive</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Users Found</h3>
                    <p><?php echo !empty($search) ? "No users match your search criteria." : "No users in the system yet."; ?></p>
                    <?php if (!empty($search)): ?>
                        <a href="display.php" class="btn-action btn-edit" style="margin-top: 15px;">Show All Users</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Logout Button -->
        <div class="logout-section">
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
    
    <script>
        // Confirm before any action
        document.querySelectorAll('.btn-action').forEach(button => {
            button.addEventListener('click', function(e) {
                if (this.classList.contains('btn-block') || 
                    this.classList.contains('btn-delete') || 
                    this.classList.contains('btn-unblock')) {
                    
                    const action = this.classList.contains('btn-block') ? 'block' : 
                                  this.classList.contains('btn-unblock') ? 'unblock/activate' : 'delete';
                    
                    if (!confirm(`Are you sure you want to ${action} this user?`)) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>