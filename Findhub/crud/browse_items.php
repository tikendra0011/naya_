<?php
session_start();
include('connection.php');

// Initialize variables
$search_query = "";
$item_type_filter = "all";
$items_result = null;

// Handle search form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_GET['search'])) {
    // Get search parameters
    $search_query = isset($_POST['search_query']) ? $_POST['search_query'] : (isset($_GET['search']) ? $_GET['search'] : "");
    $item_type_filter = isset($_POST['item_type']) ? $_POST['item_type'] : (isset($_GET['type']) ? $_GET['type'] : "all");
    
    // Build the SQL query
    $sql = "SELECT i.*, 
                   r.first_name, 
                   r.last_name 
            FROM items i 
            LEFT JOIN register_account r ON i.user_email = r.email 
            WHERE i.status = 'open'";
    
    // Add search conditions
    if (!empty($search_query)) {
        $search_clean = mysqli_real_escape_string($conn, $search_query);
        $sql .= " AND (i.title LIKE '%$search_clean%' 
                   OR i.description LIKE '%$search_clean%' 
                   OR i.location LIKE '%$search_clean%')";
    }
    
    // Filter by item type
    if ($item_type_filter != "all") {
        $sql .= " AND i.item_type = '$item_type_filter'";
    }
    
    // Order by most recent
    $sql .= " ORDER BY i.date_reported DESC";
    
    $items_result = mysqli_query($conn, $sql);
    
    if (!$items_result) {
        $error_message = "Error searching items: " . mysqli_error($conn);
    }
} else {
    // Show all open items by default
    $sql = "SELECT i.*, 
                   r.first_name, 
                   r.last_name 
            FROM items i 
            LEFT JOIN register_account r ON i.user_email = r.email 
            WHERE i.status = 'open' 
            ORDER BY i.date_reported DESC 
            LIMIT 50";
    
    $items_result = mysqli_query($conn, $sql);
}

// Get total item counts for stats
$count_query = "SELECT 
    SUM(CASE WHEN item_type = 'lost' THEN 1 ELSE 0 END) as lost_count,
    SUM(CASE WHEN item_type = 'found' THEN 1 ELSE 0 END) as found_count
    FROM items WHERE status = 'open'";
$count_result = mysqli_query($conn, $count_query);
$counts = mysqli_fetch_assoc($count_result);
$lost_count = $counts['lost_count'] ?? 0;
$found_count = $counts['found_count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Items - Find-Hub</title>
    <link rel="stylesheet" href="../Frontend/style.css">
    <link rel="stylesheet" href="../Frontend/browse_items.css">  
</head>
<body>
    
    <!-- Login Prompt Modal -->
    <div class="overlay" id="loginOverlay"></div>
    <div class="login-prompt" id="loginPrompt">
        <h3>Login Required</h3>
        <p>You need to be logged in to claim items.</p>
        <div class="login-prompt-buttons">
            <a href="login.php" class="btn-action btn-report" style="padding: 10px 20px;">Login Now</a>
            <button onclick="closeLoginPrompt()" class="btn-secondary" style="padding: 10px 20px;">Cancel</button>
        </div>
    </div>

    <!-- Header (Same as index.html) -->
    <header>
        <div class="container">
            <div class="logo">
                <h1>Find-Hub</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="../Frontend/index.html">Home</a></li>
                    <li><a href="browse_items.php">Browse Items</a></li>
                    <?php if(isset($_SESSION['e_mail'])): ?>
                        <li><a href="user_dashboard.php">Dashboard</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="form.php">Register</a></li>
                        <li><a href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero-section" style="min-height: 200px; padding: 40px 0;">
            <div class="container">
                <div class="hero-content">
                    <h2>Find Lost Items & Return Found Items</h2>
                    <p>Browse through lost and found items in our community</p>
                </div>
            </div>
        </section>

        <section class="info-section">
            <div class="container">
                <!-- Search Form -->
                <div class="search-container">
                    <form method="post" action="" class="search-form">
                        <input type="text" 
                               name="search_query" 
                               class="search-input" 
                               placeholder="Search items by title, description, or location..."
                               value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit" class="search-btn">🔍 Search</button>
                    </form>
                    
                    <!-- Filter Options -->
                    <div class="filter-options">
                        <a href="?type=all" class="filter-btn <?php echo ($item_type_filter == 'all') ? 'active' : ''; ?>">
                            All Items (<?php echo $lost_count + $found_count; ?>)
                        </a>
                        <a href="?type=lost" class="filter-btn <?php echo ($item_type_filter == 'lost') ? 'active' : ''; ?>">
                            Lost Items (<?php echo $lost_count; ?>)
                        </a>
                        <a href="?type=found" class="filter-btn <?php echo ($item_type_filter == 'found') ? 'active' : ''; ?>">
                            Found Items (<?php echo $found_count; ?>)
                        </a>
                    </div>
                    
                    <!-- Stats -->
                    <div class="stats-container">
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $lost_count; ?></div>
                            <div class="stat-label">Lost Items</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $found_count; ?></div>
                            <div class="stat-label">Found Items</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $lost_count + $found_count; ?></div>
                            <div class="stat-label">Total Open Reports</div>
                        </div>
                    </div>
                </div>
                
                <!-- Results -->
                <h2 style="color: white; margin-bottom: 20px;">
                    <?php 
                    if (!empty($search_query)) {
                        echo "Search Results for: " . htmlspecialchars($search_query);
                    } else if ($item_type_filter != 'all') {
                        echo ucfirst($item_type_filter) . " Items";
                    } else {
                        echo "All Available Items";
                    }
                    ?>
                </h2>
                
                <?php if (isset($error_message)): ?>
                    <div style="background: white; padding: 20px; border-radius: 10px; color: red; text-align: center;">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($items_result && mysqli_num_rows($items_result) > 0): ?>
                    <div class="items-grid">
                        <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                            <div class="item-card">
                                <!-- Item Image -->
                                <?php if (!empty($item['image_path'])): ?>
                                    <img src="../Uploads/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                         class="item-image">
                                <?php else: ?>
                                    <div class="no-image">
                                        <?php echo $item['item_type'] == 'lost' ? '📦 Lost Item' : '🎁 Found Item'; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="item-content">
                                    <!-- Item Header -->
                                    <div class="item-header">
                                        <h3 class="item-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                        <span class="item-badge badge-<?php echo $item['item_type']; ?>">
                                            <?php echo ucfirst($item['item_type']); ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Location -->
                                    <div class="item-location"><?php echo htmlspecialchars($item['location']); ?></div>
                                    
                                    <!-- Description -->
                                    <div class="item-description">
                                        <?php 
                                        $desc = htmlspecialchars($item['description']);
                                        echo (strlen($desc) > 100) ? substr($desc, 0, 100) . '...' : $desc;
                                        ?>
                                    </div>
                                    
                                    <!-- Footer -->
                                    <div class="item-footer">
                                        <div>
                                            <div class="item-date">
                                                Reported: <?php echo date('M j, Y', strtotime($item['date_reported'])); ?>
                                            </div>
                                            <?php if (!empty($item['first_name'])): ?>
                                                <div class="item-reporter">
                                                    By: <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Claim Button -->
                                        <?php if (isset($_SESSION['e_mail'])): ?>
                                            <!-- Logged in users can claim -->
                                            <button onclick="claimItem(<?php echo $item['id']; ?>)" 
                                                    class="claim-btn">
                                                Claim Item
                                            </button>
                                        <?php else: ?>
                                            <!-- Not logged in - show disabled button with login prompt -->
                                            <button onclick="showLoginPrompt()" 
                                                    class="claim-btn disabled">
                                                Claim Item
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php elseif ($items_result && mysqli_num_rows($items_result) == 0): ?>
                    <div class="no-results">
                        <h3>No items found</h3>
                        <p>Try changing your search criteria or browse all items.</p>
                        <a href="browse_items.php" class="btn-primary" style="display: inline-block; margin-top: 15px;">
                            Browse All Items
                        </a>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <h3>Loading items...</h3>
                        <p>If items don't appear, make sure you have items in your database.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Find-Hub. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        // Show login prompt for unauthenticated users
        function showLoginPrompt() {
            document.getElementById('loginOverlay').style.display = 'block';
            document.getElementById('loginPrompt').style.display = 'block';
        }
        
        // Close login prompt
        function closeLoginPrompt() {
            document.getElementById('loginOverlay').style.display = 'none';
            document.getElementById('loginPrompt').style.display = 'none';
        }
        
        // Handle claim item for logged-in users
        function claimItem(itemId) {
            if (confirm('Are you sure you want to claim this item? The owner will be notified.')) {
                // Redirect to claim form (we'll create this next)
                window.location.href = 'claim_item.php?id=' + itemId;
            }
        }
        
        // Close modal when clicking overlay
        document.getElementById('loginOverlay').addEventListener('click', closeLoginPrompt);
        
        // Add active class to clicked filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!this.classList.contains('active')) {
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                }
            });
        });
        
        // Auto-close login after 5 seconds if user doesn't interact
        let loginPromptTimeout;
        function showLoginPrompt() {
            document.getElementById('loginOverlay').style.display = 'block';
            document.getElementById('loginPrompt').style.display = 'block';
            
            clearTimeout(loginPromptTimeout);
            loginPromptTimeout = setTimeout(closeLoginPrompt, 5000);
        }
    </script>
</body>
</html>