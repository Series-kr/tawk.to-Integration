<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$host = 'localhost';
$dbname = 'tawkto_chat';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get visitor data from different possible sources
    $visitor_name = '';
    $visitor_email = '';
    $visitor_id = '';
    $message = '';
    $event_type = 'chat_message';
    
    // Extract visitor data
    if (isset($data['visitor_data'])) {
        // From visitor_data object
        $visitor_name = $data['visitor_data']['name'] ?? '';
        $visitor_email = $data['visitor_data']['email'] ?? '';
        $visitor_id = $data['visitor_data']['id'] ?? ($data['visitor_id'] ?? 'unknown');
    } else if (isset($data['name']) && isset($data['email'])) {
        // Direct from contact form
        $visitor_name = $data['name'];
        $visitor_email = $data['email'];
        $visitor_id = $data['visitor_id'] ?? 'unknown';
    } else {
        // Try to extract from message or other fields
        $visitor_id = $data['visitor_id'] ?? 'unknown';
        $visitor_name = $data['visitor_name'] ?? '';
        $visitor_email = $data['visitor_email'] ?? '';
    }
    
    // Extract message
    if (isset($data['message'])) {
        $message = $data['message'];
    } else if (isset($data['content'])) {
        $message = $data['content'];
    }
    
    // Extract event type
    $event_type = $data['event_type'] ?? 'chat_message';
    
    // Get chat status based on event type
    $chat_status = 'open';
    if ($event_type === 'chat_ended') {
        $chat_status = 'closed';
    }
    
    // Get IP address
    $visitor_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Check if this visitor already exists in database
    $stmt = $pdo->prepare("SELECT id, visitor_name, visitor_email FROM chat_history 
                          WHERE visitor_id = :visitor_id 
                          ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([':visitor_id' => $visitor_id]);
    $existing_visitor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If visitor exists and we have missing info, use existing info
    if ($existing_visitor) {
        if (empty($visitor_name) && !empty($existing_visitor['visitor_name'])) {
            $visitor_name = $existing_visitor['visitor_name'];
        }
        if (empty($visitor_email) && !empty($existing_visitor['visitor_email'])) {
            $visitor_email = $existing_visitor['visitor_email'];
        }
    }
    
    // Save to database
    $stmt = $pdo->prepare("INSERT INTO chat_history 
                          (visitor_id, visitor_name, visitor_email, message, 
                           event_type, chat_status, visitor_ip, page_url) 
                          VALUES 
                          (:visitor_id, :visitor_name, :visitor_email, :message, 
                           :event_type, :chat_status, :visitor_ip, :page_url)");
    
    $success = $stmt->execute([
        ':visitor_id' => $visitor_id,
        ':visitor_name' => $visitor_name,
        ':visitor_email' => $visitor_email,
        ':message' => $message,
        ':event_type' => $event_type,
        ':chat_status' => $chat_status,
        ':visitor_ip' => $visitor_ip,
        ':page_url' => $data['visitor_data']['page_url'] ?? ($data['page_url'] ?? '')
    ]);
    
    echo json_encode([
        'success' => $success,
        'message_id' => $pdo->lastInsertId(),
        'visitor_data' => [
            'name' => $visitor_name,
            'email' => $visitor_email,
            'id' => $visitor_id
        ]
    ]);
    
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>