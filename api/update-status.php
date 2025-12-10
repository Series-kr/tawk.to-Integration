<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
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
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['chat_id'])) {
    // Build update query based on provided data
    $updates = [];
    $params = [':id' => $data['chat_id']];
    
    if (isset($data['status'])) {
        $updates[] = 'chat_status = :status';
        $params[':status'] = $data['status'];
    }
    
    if (isset($data['agent_response'])) {
        $updates[] = 'agent_response = :agent_response';
        $params[':agent_response'] = $data['agent_response'];
    }
    
    if (!empty($updates)) {
        $query = "UPDATE chat_history SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $success = $stmt->execute($params);
        
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No updates provided']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>