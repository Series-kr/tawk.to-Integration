<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database connection
$host = 'localhost';
$dbname = 'tawkto_chat';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Check if specific chat is requested
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM chat_history WHERE id = :id");
    $stmt->execute([':id' => $_GET['id']]);
    $chat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Try to get visitor info from other chats if this one is missing
    if ($chat && (empty($chat['visitor_name']) || empty($chat['visitor_email']))) {
        $stmt = $pdo->prepare("SELECT visitor_name, visitor_email FROM chat_history 
                              WHERE visitor_id = :visitor_id 
                              AND (visitor_name IS NOT NULL OR visitor_email IS NOT NULL)
                              ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([':visitor_id' => $chat['visitor_id']]);
        $visitor_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($visitor_info) {
            if (empty($chat['visitor_name']) && !empty($visitor_info['visitor_name'])) {
                $chat['visitor_name'] = $visitor_info['visitor_name'];
            }
            if (empty($chat['visitor_email']) && !empty($visitor_info['visitor_email'])) {
                $chat['visitor_email'] = $visitor_info['visitor_email'];
            }
        }
    }
    
    echo json_encode(['success' => true, 'chat' => $chat]);
    exit();
}

// Get statistics
$stats = $pdo->query("SELECT 
    COUNT(DISTINCT visitor_id) as unique_visitors,
    COUNT(*) as total_chats,
    SUM(CASE WHEN chat_status = 'open' THEN 1 ELSE 0 END) as open_chats,
    SUM(CASE WHEN chat_status = 'closed' THEN 1 ELSE 0 END) as closed_chats
    FROM chat_history")->fetch(PDO::FETCH_ASSOC);

// Get chats with visitor info
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$all = isset($_GET['all']) && $_GET['all'] === 'true';

// Group chats by visitor to show most recent for each visitor
if ($all) {
    $query = "SELECT c1.* FROM chat_history c1
              INNER JOIN (
                  SELECT visitor_id, MAX(created_at) as max_date 
                  FROM chat_history 
                  GROUP BY visitor_id
              ) c2 ON c1.visitor_id = c2.visitor_id AND c1.created_at = c2.max_date
              ORDER BY c1.created_at DESC";
    $stmt = $pdo->query($query);
} else {
    $query = "SELECT * FROM chat_history 
              WHERE visitor_name IS NOT NULL OR visitor_email IS NOT NULL
              ORDER BY created_at DESC LIMIT :limit";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
}

$chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Clean up the data
foreach ($chats as &$chat) {
    if (empty($chat['visitor_name'])) {
        $chat['visitor_name'] = 'Anonymous';
    }
    if (empty($chat['visitor_email'])) {
        $chat['visitor_email'] = 'No email';
    }
    // Format message if it's an array (JSON)
    if (strpos($chat['message'], 'Array') === 0 || strpos($chat['message'], '[') === 0) {
        $chat['message'] = 'Chat message (view details)';
    }
}

echo json_encode([
    'success' => true,
    'stats' => $stats,
    'chats' => $chats,
    'total_count' => count($chats)
]);
?>