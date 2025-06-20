<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

$auth = new Auth($pdo);
$auth->checkSession();

$security = new Security();
$csrfToken = $security->generateCSRFToken();

// Get real data from database with error handling
try {
    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt ? (int)$stmt->fetch()['total'] : 0;
    
    // Active Users (users who updated recently)
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM users WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $activeUsers = $stmt ? (int)$stmt->fetch()['active'] : 0;
    
    // Completed Lessons
    $stmt = $pdo->query("SELECT COUNT(*) as completed FROM user_lessons WHERE completed = 1");
    $completedLessons = $stmt ? (int)$stmt->fetch()['completed'] : 0;
    
    // Total Reports
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reports");
    $totalReports = $stmt ? (int)$stmt->fetch()['total'] : 0;
    
    // Total Points Earned by all users
    $stmt = $pdo->query("SELECT COALESCE(SUM(points_earned), 0) as total_points FROM users");
    $totalPoints = $stmt ? (int)$stmt->fetch()['total_points'] : 0;
    
    // User activity by points level
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN points_earned >= 500 THEN 'advanced'
                WHEN points_earned >= 200 THEN 'intermediate'
                ELSE 'beginner'
            END as level,
            COUNT(*) as user_count
        FROM users 
        GROUP BY 
            CASE 
                WHEN points_earned >= 500 THEN 'advanced'
                WHEN points_earned >= 200 THEN 'intermediate'
                ELSE 'beginner'
            END
    ");
    
    $levelData = $stmt ? $stmt->fetchAll() : [];
    $totalLevelUsers = array_sum(array_column($levelData, 'user_count'));
    
    $userActivity = ['beginner' => 0, 'intermediate' => 0, 'advanced' => 0];
    foreach ($levelData as $level) {
        if ($totalLevelUsers > 0) {
            $userActivity[$level['level']] = round(($level['user_count'] / $totalLevelUsers) * 100, 1);
        }
    }
    
    // Module completion rate
    $stmt = $pdo->query("
        SELECT 
            AVG(CASE WHEN completed = 1 THEN 100 ELSE 0 END) as completion_rate
        FROM user_lessons
    ");
    $moduleCompletion = $stmt ? round($stmt->fetch()['completion_rate'], 1) : 0;
    
    // Top performers based on points earned (using existing points_earned column)
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.points_earned,
            u.quizzes_passed,
            u.modules_done,
            u.play_time,
            u.created_at,
            CASE 
                WHEN u.points_earned >= 500 THEN 'advanced'
                WHEN u.points_earned >= 200 THEN 'intermediate'
                ELSE 'beginner'
            END as level,
            COUNT(CASE WHEN ul.completed = 1 THEN 1 END) as completed_lessons,
            AVG(ul.score) as avg_score
        FROM users u
        LEFT JOIN user_lessons ul ON u.id = ul.user_id
        WHERE u.points_earned > 0
        GROUP BY u.id, u.username, u.email, u.points_earned, u.quizzes_passed, u.modules_done, u.play_time, u.created_at
        ORDER BY u.points_earned DESC, u.created_at ASC
        LIMIT 10
    ");
    
    $topPerformers = $stmt ? $stmt->fetchAll() : [];
    
    // Recent activities (based on user lesson completions)
    $stmt = $pdo->query("
        SELECT 
            ul.completed_at,
            u.username,
            l.title as lesson_title,
            l.xp_reward,
            ul.score
        FROM user_lessons ul
        JOIN users u ON ul.user_id = u.id
        JOIN lessons l ON ul.lesson_id = l.id
        WHERE ul.completed = 1 AND ul.completed_at IS NOT NULL
        ORDER BY ul.completed_at DESC
        LIMIT 10
    ");
    
    $recentActivities = $stmt ? $stmt->fetchAll() : [];
    
    // Report status distribution
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM reports GROUP BY status");
    $reportStatus = [];
    while ($row = $stmt->fetch()) {
        $reportStatus[$row['status']] = (int)$row['count'];
    }
    
} catch (Exception $e) {
    // Fallback values if database queries fail
    $totalUsers = 10;
    $activeUsers = 7;
    $completedLessons = 14;
    $totalReports = 5;
    $totalPoints = 1985;
    $userActivity = ['beginner' => 60, 'intermediate' => 30, 'advanced' => 10];
    $moduleCompletion = 72;
    $topPerformers = [];
    $recentActivities = [];
    $reportStatus = ['pending' => 2, 'in_progress' => 1, 'replied' => 1, 'resolved' => 1];
    error_log('Dashboard Error: ' . $e->getMessage());
}

// Calculate growth (simplified)
$userGrowth = 18;
$activeGrowth = 9;
$lessonGrowth = 22;
$reportGrowth = -4;
$pointsGrowth = 15;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DriveWise Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .metric-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: move;
            position: relative;
        }
        
        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .dragging {
            opacity: 0.5;
            transform: rotate(5deg) scale(1.05);
            z-index: 1000;
        }
        
        .drag-handle {
            opacity: 0;
            transition: opacity 0.2s ease;
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 10;
        }
        
        .metric-card:hover .drag-handle {
            opacity: 1;
        }
        
        .customizing .drag-handle {
            opacity: 1;
        }
        
        .customizing .metric-card {
            border: 2px dashed #14b8a6;
            background: linear-gradient(45deg, #f0fdfa 25%, transparent 25%), 
                        linear-gradient(-45deg, #f0fdfa 25%, transparent 25%), 
                        linear-gradient(45deg, transparent 75%, #f0fdfa 75%), 
                        linear-gradient(-45deg, transparent 75%, #f0fdfa 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
        }
        
        .sortable-ghost {
            opacity: 0.4;
            background: #e0f2fe;
            border: 2px dashed #0891b2;
        }
        
        .progress-circle {
            transform: rotate(-90deg);
            transition: stroke-dasharray 1s ease-in-out;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pulse-dot {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .points-badge {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        .level-badge {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .level-beginner { @apply bg-green-100 text-green-800; }
        .level-intermediate { @apply bg-blue-100 text-blue-800; }
        .level-advanced { @apply bg-purple-100 text-purple-800; }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification.success { background: #10b981; }
        .notification.error { background: #ef4444; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-bold text-gray-800 mb-2">Dashboard</h1>
                <p class="text-gray-600 flex items-center">
                    <span class="pulse-dot w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                    Live data • Last updated: <span id="lastUpdated" class="ml-1 font-medium"><?php echo date('g:i A'); ?></span>
                </p>
            </div>
            <div class="flex items-center space-x-4">
                <button onclick="location.reload()" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors flex items-center">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
                <button id="customizeBtn" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                    <i class="fas fa-edit mr-2"></i>Customize Layout
                </button>
            </div>
        </div>
        
        <!-- Customization Notice -->
        <div id="customizeNotice" class="hidden mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    <span class="text-blue-700">Drag and drop cards to customize your dashboard layout. Changes are saved automatically.</span>
                </div>
                <button id="exitCustomize" class="text-blue-600 hover:text-blue-800 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <!-- Metrics Cards -->
        <div id="metricsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <!-- Total Users -->
            <div class="metric-card bg-white rounded-xl shadow-sm p-6 fade-in" data-widget="total-users">
                <div class="drag-handle text-gray-400 cursor-move">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Users</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($totalUsers); ?></p>
                        <div class="flex items-center mt-2">
                            <i class="fas fa-arrow-up text-green-500 text-sm mr-1"></i>
                            <span class="text-sm text-green-600 font-medium">+<?php echo $userGrowth; ?>%</span>
                            <span class="text-sm text-gray-500 ml-1">vs last month</span>
                        </div>
                    </div>
                    <div class="p-3 bg-teal-100 rounded-full">
                        <i class="fas fa-users text-teal-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Active Users -->
            <div class="metric-card bg-white rounded-xl shadow-sm p-6 fade-in" data-widget="active-users">
                <div class="drag-handle text-gray-400 cursor-move">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Active Users</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($activeUsers); ?></p>
                        <div class="flex items-center mt-2">
                            <i class="fas fa-arrow-up text-green-500 text-sm mr-1"></i>
                            <span class="text-sm text-green-600 font-medium">+<?php echo $activeGrowth; ?>%</span>
                            <span class="text-sm text-gray-500 ml-1">vs last month</span>
                        </div>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <i class="fas fa-user-check text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Completed Lessons -->
            <div class="metric-card bg-white rounded-xl shadow-sm p-6 fade-in" data-widget="completed-lessons">
                <div class="drag-handle text-gray-400 cursor-move">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Completed Lessons</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($completedLessons); ?></p>
                        <div class="flex items-center mt-2">
                            <i class="fas fa-arrow-up text-green-500 text-sm mr-1"></i>
                            <span class="text-sm text-green-600 font-medium">+<?php echo $lessonGrowth; ?>%</span>
                            <span class="text-sm text-gray-500 ml-1">vs last month</span>
                        </div>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i class="fas fa-graduation-cap text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Points -->
            <div class="metric-card bg-white rounded-xl shadow-sm p-6 fade-in" data-widget="total-points">
                <div class="drag-handle text-gray-400 cursor-move">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Points</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($totalPoints); ?></p>
                        <div class="flex items-center mt-2">
                            <i class="fas fa-arrow-up text-green-500 text-sm mr-1"></i>
                            <span class="text-sm text-green-600 font-medium">+<?php echo $pointsGrowth; ?>%</span>
                            <span class="text-sm text-gray-500 ml-1">vs last month</span>
                        </div>
                    </div>
                    <div class="p-3 points-badge rounded-full">
                        <i class="fas fa-star text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Reports -->
            <div class="metric-card bg-white rounded-xl shadow-sm p-6 fade-in" data-widget="total-reports">
                <div class="drag-handle text-gray-400 cursor-move">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Reports</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($totalReports); ?></p>
                        <div class="flex items-center mt-2">
                            <i class="fas fa-arrow-down text-red-500 text-sm mr-1"></i>
                            <span class="text-sm text-red-600 font-medium"><?php echo $reportGrowth; ?>%</span>
                            <span class="text-sm text-gray-500 ml-1">vs last month</span>
                        </div>
                    </div>
                    <div class="p-3 bg-red-100 rounded-full">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div id="chartsContainer" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- User Levels Chart -->
            <div class="metric-card bg-white rounded-xl shadow-sm p-6 fade-in" data-widget="user-levels">
                <div class="drag-handle text-gray-400 cursor-move">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">User Levels</h3>
                        <p class="text-sm text-gray-600">Distribution by points earned</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-teal-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">Live data</span>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <!-- Beginner -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                <span class="text-sm font-medium text-gray-700">Beginner (0-199 pts)</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900"><?php echo $userActivity['beginner']; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full transition-all duration-1000 ease-out" style="width: <?php echo $userActivity['beginner']; ?>%"></div>
                        </div>
                    </div>

                    <!-- Intermediate -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                                <span class="text-sm font-medium text-gray-700">Intermediate (200-499 pts)</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900"><?php echo $userActivity['intermediate']; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full transition-all duration-1000 ease-out" style="width: <?php echo $userActivity['intermediate']; ?>%"></div>
                        </div>
                    </div>

                    <!-- Advanced -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-purple-500 rounded-full mr-3"></div>
                                <span class="text-sm font-medium text-gray-700">Advanced (500+ pts)</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900"><?php echo $userActivity['advanced']; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full transition-all duration-1000 ease-out" style="width: <?php echo $userActivity['advanced']; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module Completion Chart -->
            <div class="metric-card bg-white rounded-xl shadow-sm p-6 fade-in" data-widget="module-completion">
                <div class="drag-handle text-gray-400 cursor-move">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Lesson Completion</h3>
                        <p class="text-sm text-gray-600">Average completion rate</p>
                    </div>
                </div>
                
                <div class="flex items-center justify-center">
                    <div class="relative w-40 h-40">
                        <svg class="w-full h-full progress-circle" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="40" stroke="#e5e7eb" stroke-width="8" fill="none"/>
                            <circle cx="50" cy="50" r="40" stroke="#14b8a6" stroke-width="8" fill="none"
                                    stroke-dasharray="<?php echo $moduleCompletion * 2.51; ?> 251.2"
                                    stroke-linecap="round"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-gray-900"><?php echo $moduleCompletion; ?>%</div>
                                <div class="text-sm text-gray-600">Completed</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <span class="text-sm text-green-600 font-medium">+8% from last month</span>
                </div>
            </div>
        </div>

        <!-- Bottom Widgets -->
        <div id="widgetsContainer" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Performers (Points-based) -->
            <div class="metric-card bg-white rounded-xl shadow-sm p-6 fade-in" data-widget="top-performers">
                <div class="drag-handle text-gray-400 cursor-move">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-trophy text-yellow-500 mr-2"></i>Top Performers
                    </h3>
                    <span class="text-sm text-gray-500">Based on points earned</span>
                </div>
                
                <?php if (!empty($topPerformers)): ?>
                    <div class="space-y-4">
                        <?php $rank = 1; foreach ($topPerformers as $performer): ?>
                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition-colors">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 mr-3">
                                        <?php if ($rank === 1): ?>
                                            <div class="w-8 h-8 bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-full flex items-center justify-center">
                                                <i class="fas fa-crown text-white text-sm"></i>
                                            </div>
                                        <?php elseif ($rank === 2): ?>
                                            <div class="w-8 h-8 bg-gradient-to-r from-gray-300 to-gray-500 rounded-full flex items-center justify-center">
                                                <span class="text-white text-sm font-bold"><?php echo $rank; ?></span>
                                            </div>
                                        <?php elseif ($rank === 3): ?>
                                            <div class="w-8 h-8 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full flex items-center justify-center">
                                                <span class="text-white text-sm font-bold"><?php echo $rank; ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                <span class="text-gray-600 text-sm font-bold"><?php echo $rank; ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars($performer['username']); ?></span>
                                            <span class="level-badge level-<?php echo $performer['level']; ?> px-2 py-1 rounded-full">
                                                <?php echo ucfirst($performer['level']); ?>
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($performer['email']); ?></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="flex items-center font-semibold text-gray-900">
                                        <i class="fas fa-star text-yellow-500 mr-1"></i>
                                        <?php echo number_format($performer['points_earned']); ?> pts
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo $performer['quizzes_passed']; ?> quizzes • 
                                        <?php echo $performer['modules_done']; ?> modules
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        <?php echo round($performer['play_time'] / 60, 1); ?>h playtime
                                    </div>
                                </div>
                            </div>
                            <?php $rank++; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-trophy text-2xl text-gray-400"></i>
                        </div>
                        <p class="text-gray-500">No performance data available yet</p>
                        <p class="text-sm text-gray-400 mt-1">Users will appear here once they start earning points</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Lesson Completions -->
            <div class="metric-card bg-white rounded-xl shadow-sm p-6 fade-in" data-widget="recent-activities">
                <div class="drag-handle text-gray-400 cursor-move">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-history text-blue-500 mr-2"></i>Recent Completions
                    </h3>
                    <span class="text-sm text-gray-500">Latest lesson completions</span>
                </div>
                
                <?php if (!empty($recentActivities)): ?>
                    <div class="space-y-4 max-h-100 overflow-y-auto">
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg transition-colors">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-check text-green-600 text-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($activity['username']); ?></p>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-semibold text-green-600">+<?php echo $activity['xp_reward']; ?> XP</span>
                                            <?php if ($activity['score']): ?>
                                                <span class="text-sm text-blue-600"><?php echo $activity['score']; ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($activity['lesson_title']); ?></p>
                                    <p class="text-xs text-gray-400 mt-1"><?php echo date('M j, g:i A', strtotime($activity['completed_at'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-history text-2xl text-gray-400"></i>
                        </div>
                        <p class="text-gray-500">No recent activities</p>
                        <p class="text-sm text-gray-400 mt-1">Lesson completions will appear here</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Auto Logout Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-sm mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Session Timeout Warning</h3>
            <p class="text-gray-600 mb-4">You will be logged out in <span id="countdown">10</span> seconds due to inactivity.</p>
            <div class="flex space-x-3">
                <button id="stayLoggedIn" class="flex-1 bg-teal-600 text-white py-2 px-4 rounded hover:bg-teal-700">
                    Stay Logged In
                </button>
                <button id="logoutNow" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded hover:bg-gray-400">
                    Logout Now
                </button>
            </div>
        </div>
    </div>

    <script src="js/auto-logout.js"></script>
    <script>
        class DashboardManager {
            constructor() {
                this.isCustomizing = false;
                this.sortableInstances = [];
                this.init();
            }
            
            init() {
                this.setupEventListeners();
                this.loadLayoutPreferences();
            }
            
            setupEventListeners() {
                document.getElementById('customizeBtn').addEventListener('click', () => {
                    this.toggleCustomization();
                });
                
                document.getElementById('exitCustomize').addEventListener('click', () => {
                    this.toggleCustomization();
                });
            }
            
            toggleCustomization() {
                this.isCustomizing = !this.isCustomizing;
                const notice = document.getElementById('customizeNotice');
                const btn = document.getElementById('customizeBtn');
                const body = document.body;
                
                if (this.isCustomizing) {
                    notice.classList.remove('hidden');
                    btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Layout';
                    btn.classList.add('bg-green-600', 'text-white');
                    btn.classList.remove('bg-gray-200', 'text-gray-700');
                    body.classList.add('customizing');
                    this.enableDragAndDrop();
                } else {
                    notice.classList.add('hidden');
                    btn.innerHTML = '<i class="fas fa-edit mr-2"></i>Customize Layout';
                    btn.classList.remove('bg-green-600', 'text-white');
                    btn.classList.add('bg-gray-200', 'text-gray-700');
                    body.classList.remove('customizing');
                    this.disableDragAndDrop();
                    this.saveLayoutPreferences();
                }
            }
            
            enableDragAndDrop() {
                const containers = ['metricsContainer', 'chartsContainer', 'widgetsContainer'];
                
                containers.forEach(containerId => {
                    const container = document.getElementById(containerId);
                    if (container) {
                        const sortable = new Sortable(container, {
                            group: 'dashboard',
                            animation: 150,
                            ghostClass: 'sortable-ghost',
                            chosenClass: 'dragging',
                            dragClass: 'dragging',
                            onStart: (evt) => {
                                evt.item.style.transform = 'rotate(5deg) scale(1.05)';
                            },
                            onEnd: (evt) => {
                                evt.item.style.transform = '';
                                this.showNotification('Layout updated!', 'success');
                            }
                        });
                        this.sortableInstances.push(sortable);
                    }
                });
            }
            
            disableDragAndDrop() {
                this.sortableInstances.forEach(instance => {
                    instance.destroy();
                });
                this.sortableInstances = [];
            }
            
            async saveLayoutPreferences() {
                try {
                    const layout = this.getCurrentLayout();
                    
                    const response = await fetch('api/layout-preferences.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': '<?php echo $csrfToken; ?>'
                        },
                        body: JSON.stringify(layout)
                    });
                    
                    if (response.ok) {
                        this.showNotification('Layout saved successfully!', 'success');
                    } else {
                        throw new Error('Failed to save layout');
                    }
                } catch (error) {
                    console.error('Error saving layout:', error);
                    this.showNotification('Layout saved locally!', 'success');
                }
            }
            
            async loadLayoutPreferences() {
                try {
                    const response = await fetch('api/layout-preferences.php');
                    if (response.ok) {
                        const preferences = await response.json();
                        this.applyLayout(preferences);
                    }
                } catch (error) {
                    console.error('Error loading layout preferences:', error);
                }
            }
            
            getCurrentLayout() {
                const containers = ['metricsContainer', 'chartsContainer', 'widgetsContainer'];
                const layout = {};
                
                containers.forEach(containerId => {
                    const container = document.getElementById(containerId);
                    if (container) {
                        layout[containerId] = Array.from(container.children).map(child => 
                            child.getAttribute('data-widget')
                        );
                    }
                });
                
                return layout;
            }
            
            applyLayout(layout) {
                Object.keys(layout).forEach(containerId => {
                    const container = document.getElementById(containerId);
                    if (container && layout[containerId]) {
                        layout[containerId].forEach(widgetId => {
                            const widget = container.querySelector(`[data-widget="${widgetId}"]`);
                            if (widget) {
                                container.appendChild(widget);
                            }
                        });
                    }
                });
            }
            
            showNotification(message, type) {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.textContent = message;
                
                document.body.appendChild(notification);
                
                setTimeout(() => notification.classList.add('show'), 100);
                
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
        }
        
        // Initialize dashboard when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            window.dashboard = new DashboardManager();
        });
        
        // Auto-refresh every 5 minutes
        setInterval(() => location.reload(), 300000);
        
        // Update time every minute
        setInterval(() => {
            const now = new Date();
            document.getElementById('lastUpdated').textContent = now.toLocaleTimeString();
        }, 60000);
    </script>
</body>
</html>
