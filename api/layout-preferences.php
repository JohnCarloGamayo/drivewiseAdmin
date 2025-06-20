<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

$security = new Security();
$auth = new Auth($pdo);

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$adminId = $_SESSION['admin_id'] ?? 1;

try {
    if ($method === 'GET') {
        // Get layout preferences
        $stmt = $pdo->prepare("SELECT layout_preferences FROM admin_users WHERE id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetch();
        
        $preferences = $result['layout_preferences'] ?? '{}';
        echo $preferences;
        
    } elseif ($method === 'POST') {
        // Save layout preferences
        $input = file_get_contents('php://input');
        $preferences = $security->sanitizeJsonInput($input);
        
        // Validate preferences structure
        if (!is_array($preferences)) {
            throw new InvalidArgumentException('Invalid preferences format');
        }
        
        $stmt = $pdo->prepare("UPDATE admin_users SET layout_preferences = ? WHERE id = ?");
        $stmt->execute([json_encode($preferences), $adminId]);
        
        echo json_encode(['success' => true]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    error_log('Layout Preferences Error: ' . $e->getMessage());
}
?>
