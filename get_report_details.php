<?php
header('Content-Type: application/json');
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

$auth = new Auth($pdo);
$security = new Security();

// Check authentication
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$reportId = intval($_GET['id'] ?? 0);

if (!$reportId) {
    echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
    exit;
}

try {
    // Get report details with user information and replies
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, u.email, u.created_at as user_joined
        FROM reports r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();
    
    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit;
    }
    
    // Get replies for this report
    $stmt = $pdo->prepare("
        SELECT rr.*, au.email as admin_email
        FROM report_replies rr
        LEFT JOIN admin_users au ON rr.admin_id = au.id
        WHERE rr.report_id = ?
        ORDER BY rr.created_at ASC
    ");
    $stmt->execute([$reportId]);
    $replies = $stmt->fetchAll();
    
    // Generate HTML for the modal
    $iconClass = match($report['issue_type']) {
        'bug' => 'fas fa-bug text-red-500',
        'feature_request' => 'fas fa-lightbulb text-yellow-500',
        'account_issue' => 'fas fa-user-cog text-blue-500',
        'payment_issue' => 'fas fa-credit-card text-green-500',
        'technical_support' => 'fas fa-tools text-purple-500',
        default => 'fas fa-question-circle text-gray-500'
    };
    
    $statusClasses = [
        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'in_progress' => 'bg-blue-100 text-blue-800 border-blue-200',
        'replied' => 'bg-green-100 text-green-800 border-green-200',
        'resolved' => 'bg-gray-100 text-gray-800 border-gray-200'
    ];
    $statusClass = $statusClasses[$report['status']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
    
    $html = '
    <div class="space-y-6">
        <!-- Report Header -->
        <div class="bg-gray-50 rounded-lg p-6">
            <div class="flex items-start justify-between">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                        <i class="' . $iconClass . ' text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">' . $security->sanitizeOutput(ucfirst(str_replace('_', ' ', $report['issue_type']))) . '</h4>
                        <p class="text-sm text-gray-600">Report #' . $report['id'] . ' • ' . date('M j, Y g:i A', strtotime($report['created_at'])) . '</p>
                    </div>
                </div>
                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full border ' . $statusClass . '">
                    ' . $security->sanitizeOutput(ucfirst(str_replace('_', ' ', $report['status']))) . '
                </span>
            </div>
        </div>
        
        <!-- User Information -->
        <div class="bg-white border rounded-lg p-6">
            <h5 class="text-sm font-medium text-gray-900 mb-4">
                <i class="fas fa-user mr-2"></i>User Information
            </h5>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Username</label>
                    <p class="mt-1 text-sm text-gray-900">' . $security->sanitizeOutput($report['username'] ?? 'Unknown') . '</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Email</label>
                    <p class="mt-1 text-sm text-gray-900">' . $security->sanitizeOutput($report['email'] ?? 'N/A') . '</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Member Since</label>
                    <p class="mt-1 text-sm text-gray-900">' . ($report['user_joined'] ? date('M j, Y', strtotime($report['user_joined'])) : 'N/A') . '</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</label>
                    <p class="mt-1 text-sm text-gray-900">' . ($report['user_id'] ?? 'N/A') . '</p>
                </div>
            </div>
        </div>
        
        <!-- Report Description -->
        <div class="bg-white border rounded-lg p-6">
            <h5 class="text-sm font-medium text-gray-900 mb-4">
                <i class="fas fa-file-alt mr-2"></i>Report Description
            </h5>
            <div class="prose prose-sm max-w-none">
                <p class="text-gray-700 whitespace-pre-wrap">' . $security->sanitizeOutput($report['description']) . '</p>
            </div>
        </div>';
        
    // Screenshot section
    if ($report['screenshot_path']) {
        $html .= '
        <div class="bg-white border rounded-lg p-6">
            <h5 class="text-sm font-medium text-gray-900 mb-4">
                <i class="fas fa-image mr-2"></i>Screenshot
            </h5>
            <div class="bg-gray-100 rounded-lg p-4 text-center">
                <i class="fas fa-image text-4xl text-gray-400 mb-2"></i>
                <p class="text-sm text-gray-600">Screenshot: ' . $security->sanitizeOutput(basename($report['screenshot_path'])) . '</p>
                <p class="text-xs text-gray-500 mt-1">Screenshot viewing not implemented in demo</p>
            </div>
        </div>';
    }
    
    // Replies section
    if (!empty($replies)) {
        $html .= '
        <div class="bg-white border rounded-lg p-6">
            <h5 class="text-sm font-medium text-gray-900 mb-4">
                <i class="fas fa-comments mr-2"></i>Admin Replies (' . count($replies) . ')
            </h5>
            <div class="space-y-4">';
            
        foreach ($replies as $reply) {
            $html .= '
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-user-shield text-white text-xs"></i>
                        </div>
                        <span class="text-sm font-medium text-blue-900">Admin</span>
                        <span class="text-xs text-blue-600">(' . $security->sanitizeOutput($reply['admin_email']) . ')</span>
                    </div>
                    <span class="text-xs text-blue-600">' . date('M j, Y g:i A', strtotime($reply['created_at'])) . '</span>
                </div>
                <p class="text-sm text-blue-800 whitespace-pre-wrap">' . $security->sanitizeOutput($reply['message']) . '</p>
            </div>';
        }
        
        $html .= '
            </div>
        </div>';
    }
    
    // Quick Actions
    $html .= '
        <div class="bg-white border rounded-lg p-6">
            <h5 class="text-sm font-medium text-gray-900 mb-4">
                <i class="fas fa-bolt mr-2"></i>Quick Actions
            </h5>
            <div class="flex flex-wrap gap-2">
                <button onclick="updateStatus(' . $report['id'] . ', \'in_progress\')" class="inline-flex items-center px-3 py-1 border border-blue-300 text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 transition-colors">
                    <i class="fas fa-play mr-1"></i>Mark In Progress
                </button>
                <button onclick="updateStatus(' . $report['id'] . ', \'resolved\')" class="inline-flex items-center px-3 py-1 border border-green-300 text-xs font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 transition-colors">
                    <i class="fas fa-check mr-1"></i>Mark Resolved
                </button>
                <button onclick="closeModal(\'viewReportModal\'); replyToReport(' . $report['id'] . ');" class="inline-flex items-center px-3 py-1 border border-teal-300 text-xs font-medium rounded-md text-teal-700 bg-teal-100 hover:bg-teal-200 transition-colors">
                    <i class="fas fa-reply mr-1"></i>Send Reply
                </button>
            </div>
        </div>
    </div>';
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch (Exception $e) {
    error_log('Report Details Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
