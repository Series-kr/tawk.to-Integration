<?php
session_start();
// Simple authentication - in production, use proper authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin-login.php');
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'tawkto_chat';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get chat statistics
$stmt = $pdo->query("SELECT 
    COUNT(*) as total_chats,
    SUM(CASE WHEN chat_status = 'open' THEN 1 ELSE 0 END) as open_chats,
    SUM(CASE WHEN chat_status = 'closed' THEN 1 ELSE 0 END) as closed_chats
    FROM chat_history");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent chats
$stmt = $pdo->query("SELECT * FROM chat_history ORDER BY created_at DESC LIMIT 10");
$recent_chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Tawk.to Integration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3><i class="bi bi-chat-dots"></i> Chat Admin</h3>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item active">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#chats" onclick="loadChats()">
                        <i class="bi bi-chat-text"></i> All Chats
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#agents">
                        <i class="bi bi-people"></i> Agents
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#analytics">
                        <i class="bi bi-graph-up"></i> Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="container-fluid py-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Dashboard Overview</h1>
                    <div class="d-flex gap-3">
                        <button class="btn btn-primary" onclick="refreshDashboard()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="liveUpdates" checked>
                            <label class="form-check-label" for="liveUpdates">Live Updates</label>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-subtitle">Total Chats</h6>
                                        <h2 class="card-title"><?php echo $stats['total_chats']; ?></h2>
                                    </div>
                                    <i class="bi bi-chat-left-text display-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-subtitle">Open Chats</h6>
                                        <h2 class="card-title"><?php echo $stats['open_chats']; ?></h2>
                                    </div>
                                    <i class="bi bi-clock display-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-subtitle">Closed Chats</h6>
                                        <h2 class="card-title"><?php echo $stats['closed_chats']; ?></h2>
                                    </div>
                                    <i class="bi bi-check-circle display-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Chats Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Chats</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Visitor</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="chatTableBody">
                                    <?php foreach($recent_chats as $chat): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($chat['visitor_name'] ?? 'Anonymous'); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($chat['visitor_email'] ?? 'No email'); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="message-preview">
                                                <?php echo htmlspecialchars(substr($chat['message'], 0, 50) . '...'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $chat['chat_status'] == 'open' ? 'warning' : 
                                                    ($chat['chat_status'] == 'closed' ? 'success' : 'secondary'); 
                                            ?>">
                                                <?php echo ucfirst($chat['chat_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M d, H:i', strtotime($chat['created_at'])); ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewChat(<?php echo $chat['id']; ?>)">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="updateStatus(<?php echo $chat['id']; ?>, 'closed')">
                                                <i class="bi bi-check"></i> Close
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>



                <!-- Chat Preview Modal -->
                <div class="modal fade" id="chatModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Chat Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="chatDetails">
                                <!-- Chat details will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/dashboard.js"></script>
</body>
</html>