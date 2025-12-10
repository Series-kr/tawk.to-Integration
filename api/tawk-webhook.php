<?php
// api/tawk-webhook.php - For receiving events from Tawk.to
header('Content-Type: application/json');

// Get webhook data
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    // Log the webhook data
    file_put_contents('tawk_webhook_log.txt', 
        date('Y-m-d H:i:s') . " - " . json_encode($data) . "\n", 
        FILE_APPEND);
    
    // Handle different webhook events
    switch ($data['event']) {
        case 'chat.start':
            // Chat started
            saveChatStart($data);
            break;
            
        case 'chat.end':
            // Chat ended
            saveChatEnd($data);
            break;
            
        case 'chat.message':
            // New message
            saveChatMessage($data);
            break;
            
        case 'chat.assign':
            // Chat assigned to agent
            saveChatAssignment($data);
            break;
    }
    
    echo json_encode(['success' => true]);
}

function saveChatStart($data) {
    // Save to your database
    $chatData = [
        'tawk_chat_id' => $data['chatId'],
        'visitor_name' => $data['visitor']['name'] ?? 'Unknown',
        'visitor_email' => $data['visitor']['email'] ?? '',
        'start_time' => date('Y-m-d H:i:s', $data['timestamp']/1000),
        'status' => 'open'
    ];
    
    // Save to database or log
    error_log("Chat started: " . json_encode($chatData));
}

function saveChatMessage($data) {
    $messageData = [
        'tawk_chat_id' => $data['chatId'],
        'sender' => $data['sender'],
        'message' => $data['message'],
        'timestamp' => date('Y-m-d H:i:s', $data['timestamp']/1000)
    ];
    
    error_log("Chat message: " . json_encode($messageData));
}
?>