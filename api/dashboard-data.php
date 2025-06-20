<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Initialize security and auth
$security = new Security();
$auth = new Auth($pdo);

// At the beginning, add session start if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Replace the auth check section with:
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Add fallback data in case of database issues
$data = [
    'totalUsers' => 0,
    'activeUsers' => 0,
    'completedExams' => 0,
    'totalReports' => 0,
    'userGrowth' => 0,
    'userActivity' => ['beginner' => 0, 'intermediate' => 0, 'advanced' => 0],
    'moduleCompletion' => 0,
    'topPerformers' => [],
    'recentActivity' => [],
    'reportStatus' => []
];

try {
    // Get dashboard metrics with proper sanitization
    
    
    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $data['totalUsers'] = (int)$stmt->fetch()['total'];
    
    // Active Users (users who logged in within last 30 days)
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM users WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $data['activeUsers'] = (int)$stmt->fetch()['active'];
    
    // Completed Exams
    $stmt = $pdo->query("SELECT COUNT(*) as completed FROM user_lessons WHERE completed = 1");
    $data['completedExams'] = (int)$stmt->fetch()['completed'];
    
    // Total Reports
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reports");
    $data['totalReports'] = (int)$stmt->fetch()['total'];
    
    // Growth calculations (compared to last month)
    $stmt = $pdo->query("SELECT COUNT(*) as last_month FROM users WHERE created_at >= DATE_SUB(DATE_SUB(NOW(), INTERVAL 1 MONTH), INTERVAL 1 MONTH) AND created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
    $lastMonthUsers = (int)$stmt->fetch()['last_month'];
    $data['userGrowth'] = $lastMonthUsers > 0 ? round((($data['totalUsers'] - $lastMonthUsers) / $lastMonthUsers) * 100, 1) : 0;
    
    // User activity by level
    $stmt = $pdo->query("
        SELECT 
            difficulty_level,
            COUNT(DISTINCT ul.user_id) as user_count
        FROM user_lessons ul 
        JOIN lessons l ON ul.lesson_id = l.id 
        WHERE ul.completed = 1 
        GROUP BY l.difficulty_level
    ");
    
    $levelData = $stmt->fetchAll();
    $totalLevelUsers = array_sum(array_column($levelData, 'user_count'));
    
    
    $data['userActivity'] = [
        'beginner' => 0,
        'intermediate' => 0,
        'advanced' => 0
    ];
    
    foreach ($levelData as $level) {
        if ($totalLevelUsers > 0) {
            $data['userActivity'][$level['difficulty_level']] = round(($level['user_count'] / $totalLevelUsers) * 100, 1);
        }
    }
    
    // Module completion rate
    $stmt = $pdo->query("
        SELECT 
            AVG(CASE WHEN completed = 1 THEN 100 ELSE 0 END) as completion_rate
        FROM user_lessons
    ");
    $data['moduleCompletion'] = round($stmt->fetch()['completion_rate'], 1);
    
    // Top performers
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.username,
            u.email,
            AVG(ul.score) as avg_score,
            COUNT(CASE WHEN ul.completed = 1 THEN 1 END) as completed_exams,
            MAX(l.difficulty_level) as max_level
        FROM users u
        LEFT JOIN user_lessons ul ON u.id = ul.user_id
        LEFT JOIN lessons l ON ul.lesson_id = l.id
        WHERE ul.score IS NOT NULL
        GROUP BY u.id, u.username, u.email
        ORDER BY avg_score DESC, completed_exams DESC
        LIMIT 5
    ");
    
    $topPerformers = [];
    $rank = 1;
    while ($performer = $stmt->fetch()) {
        $topPerformers[] = [
            'rank' => $rank++,
            'username' => $security->sanitizeOutput($performer['username']),
            'email' => $security->sanitizeOutput($performer['email']),
            'score' => round($performer['avg_score'], 1),
            'completed_exams' => (int)$performer['completed_exams'],
            'level' => $security->sanitizeOutput($performer['max_level'] ?? 'beginner')
        ];
    }
    $data['topPerformers'] = $topPerformers;
    
    // Recent activity
    $stmt = $pdo->query("
        SELECT 
            'user_registered' as type,
            u.username as title,
            'New user registered' as description,
            u.created_at as timestamp
        FROM users u
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        UNION ALL
        
        SELECT 
            'report_submitted' as type,
            CONCAT('Report #', r.id) as title,
            r.issue_type as description,
            r.created_at as timestamp
        FROM reports r
        WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        
        ORDER BY timestamp DESC
        LIMIT 10
    ");
    
    $recentActivity = [];
    while ($activity = $stmt->fetch()) {
        $recentActivity[] = [
            'type' => $security->sanitizeOutput($activity['type']),
            'title' => $security->sanitizeOutput($activity['title']),
            'description' => $security->sanitizeOutput($activity['description']),
            'timestamp' => $activity['timestamp']
        ];
    }
    $data['recentActivity'] = $recentActivity;
    
    // Report status distribution
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM reports GROUP BY status");
    $reportStatus = [];
    while ($status = $stmt->fetch()) {
        $reportStatus[$status['status']] = (int)$status['count'];
    }
    $data['reportStatus'] = $reportStatus;
    
    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log('Dashboard API Error: ' . $e->getMessage());
}
?>
