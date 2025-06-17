<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth($pdo);
$auth->checkSession();

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_reply') {
    $report_id = intval($_POST['report_id']);
    $admin_id = $_SESSION['admin_id'] ?? 1; // Get admin ID from session
    $message = trim($_POST['reply_message']);
    $status = $_POST['status'] ?? 'replied';
    
    // Validate input
    if (!empty($report_id) && !empty($admin_id) && !empty($message)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Insert reply (let AUTO_INCREMENT handle the ID)
            $stmt = $pdo->prepare("INSERT INTO report_replies (report_id, admin_id, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$report_id, $admin_id, $message]);
            
            // Update report status
            $stmt2 = $pdo->prepare("UPDATE reports SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt2->execute([$status, $report_id]);
            
            // Commit transaction
            $pdo->commit();
            
            header('Location: reports.php?success=Reply sent successfully');
            exit;
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollback();
            header('Location: reports.php?error=Failed to send reply: ' . $e->getMessage());
            exit;
        }
    } else {
        header('Location: reports.php?error=Missing required fields');
        exit;
    }
}

// Handle status updates
if ($_POST['action'] ?? '' === 'update_status') {
    $reportId = intval($_POST['report_id']);
    $newStatus = $_POST['status'];
    
    if (!empty($reportId) && !empty($newStatus)) {
        try {
            $stmt = $pdo->prepare("UPDATE reports SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $reportId]);
            
            header('Location: reports.php?success=Status updated successfully');
            exit;
        } catch (Exception $e) {
            header('Location: reports.php?error=Failed to update status: ' . $e->getMessage());
            exit;
        }
    }
}

// Fetch reports with user information
$statusFilter = $_GET['status'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';

$sql = "SELECT r.*, u.username, u.email 
        FROM reports r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE 1=1";

$params = [];

if ($statusFilter !== 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $statusFilter;
}

if (!empty($searchTerm)) {
    $sql .= " AND (r.issue_type LIKE ? OR r.description LIKE ? OR u.username LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// Get report counts for status badges
$statusCounts = [];
$countStmt = $pdo->query("SELECT status, COUNT(*) as count FROM reports GROUP BY status");
while ($row = $countStmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports - DriveWise Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .modal-backdrop {
            backdrop-filter: blur(8px);
            background: rgba(0, 0, 0, 0.4);
        }
        
        .modal-content {
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        
        .modal-body {
            overflow-y: auto;
            max-height: calc(90vh - 120px);
        }
        
        .modal-body::-webkit-scrollbar {
            width: 6px;
        }
        
        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .modal-body::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        .template-button {
            transition: all 0.2s ease;
        }
        
        .template-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Manage Reports</h1>
            <p class="text-gray-600">View and manage user reports efficiently</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Reports</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo array_sum($statusCounts); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $statusCounts['pending'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-reply text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Replied</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $statusCounts['replied'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-gray-100 rounded-lg">
                        <i class="fas fa-check-circle text-gray-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Resolved</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $statusCounts['resolved'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Filter Tabs -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-6">
                    <a href="?status=all" class="<?php echo $statusFilter === 'all' ? 'border-teal-500 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-list mr-2"></i>All Reports
                        <span class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs">
                            <?php echo array_sum($statusCounts); ?>
                        </span>
                    </a>
                    <a href="?status=pending" class="<?php echo $statusFilter === 'pending' ? 'border-teal-500 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-clock mr-2"></i>Pending
                        <span class="ml-2 bg-yellow-100 text-yellow-800 py-0.5 px-2.5 rounded-full text-xs">
                            <?php echo $statusCounts['pending'] ?? 0; ?>
                        </span>
                    </a>
                    <a href="?status=in_progress" class="<?php echo $statusFilter === 'in_progress' ? 'border-teal-500 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-spinner mr-2"></i>In Progress
                        <span class="ml-2 bg-blue-100 text-blue-800 py-0.5 px-2.5 rounded-full text-xs">
                            <?php echo $statusCounts['in_progress'] ?? 0; ?>
                        </span>
                    </a>
                    <a href="?status=replied" class="<?php echo $statusFilter === 'replied' ? 'border-teal-500 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-reply mr-2"></i>Replied
                        <span class="ml-2 bg-green-100 text-green-800 py-0.5 px-2.5 rounded-full text-xs">
                            <?php echo $statusCounts['replied'] ?? 0; ?>
                        </span>
                    </a>
                    <a href="?status=resolved" class="<?php echo $statusFilter === 'resolved' ? 'border-teal-500 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-check-circle mr-2"></i>Resolved
                        <span class="ml-2 bg-gray-100 text-gray-800 py-0.5 px-2.5 rounded-full text-xs">
                            <?php echo $statusCounts['resolved'] ?? 0; ?>
                        </span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <form method="GET" class="flex gap-4">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" 
                           placeholder="Search reports by issue type, description, or username..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-colors">
                </div>
                <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <?php if (!empty($searchTerm)): ?>
                    <a href="?status=<?php echo $statusFilter; ?>" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Reports List -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <?php if (empty($reports)): ?>
                <div class="p-12 text-center">
                    <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-inbox text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No reports found</h3>
                    <p class="text-gray-500">No reports match your current filters.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-info-circle mr-2"></i>Report Details
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-user mr-2"></i>User
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-flag mr-2"></i>Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-calendar mr-2"></i>Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-cogs mr-2"></i>Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($reports as $report): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0">
                                                <?php
                                                $iconClass = match($report['issue_type']) {
                                                    'bug' => 'fas fa-bug text-red-500',
                                                    'feature_request' => 'fas fa-lightbulb text-yellow-500',
                                                    'account_issue' => 'fas fa-user-cog text-blue-500',
                                                    'payment_issue' => 'fas fa-credit-card text-green-500',
                                                    'technical_support' => 'fas fa-tools text-purple-500',
                                                    default => 'fas fa-question-circle text-gray-500'
                                                };
                                                ?>
                                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                                    <i class="<?php echo $iconClass; ?> text-lg"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo ucfirst(str_replace('_', ' ', $report['issue_type'])); ?>
                                                </div>
                                                <div class="text-sm text-gray-500 max-w-xs truncate">
                                                    <?php echo htmlspecialchars($report['description']); ?>
                                                </div>
                                                <?php if ($report['screenshot_path']): ?>
                                                    <div class="mt-1">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                            <i class="fas fa-image mr-1"></i>Screenshot attached
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-gray-500 text-sm"></i>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($report['username'] ?? 'Unknown'); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($report['email'] ?? ''); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $statusClasses = [
                                            'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                            'in_progress' => 'bg-blue-100 text-blue-800 border-blue-200',
                                            'replied' => 'bg-green-100 text-green-800 border-green-200',
                                            'resolved' => 'bg-gray-100 text-gray-800 border-gray-200'
                                        ];
                                        $statusClass = $statusClasses[$report['status']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                        ?>
                                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full border <?php echo $statusClass; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex flex-col">
                                            <span class="font-medium"><?php echo date('M j, Y', strtotime($report['created_at'])); ?></span>
                                            <span class="text-xs text-gray-400"><?php echo date('g:i A', strtotime($report['created_at'])); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="viewReport(<?php echo $report['id']; ?>)" 
                                                    class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-teal-600 bg-teal-100 hover:bg-teal-200 transition-colors">
                                                <i class="fas fa-eye mr-1"></i>View
                                            </button>
                                            <button onclick="replyToReport(<?php echo $report['id']; ?>)" 
                                                    class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-blue-600 bg-blue-100 hover:bg-blue-200 transition-colors">
                                                <i class="fas fa-reply mr-1"></i>Reply
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- View Report Modal -->
    <div id="viewReportModal" class="fixed inset-0 modal-backdrop hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg modal-content max-w-4xl w-full mx-4">
            <div class="p-6 border-b border-gray-200 flex-shrink-0">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-file-alt mr-2 text-teal-600"></i>Report Details
                    </h3>
                    <button onclick="closeModal('viewReportModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="reportDetails" class="modal-body p-6">
                <!-- Report details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="fixed inset-0 modal-backdrop hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg modal-content max-w-2xl w-full mx-4">
            <div class="p-6 border-b border-gray-200 flex-shrink-0">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-reply mr-2 text-blue-600"></i>Reply to Report
                    </h3>
                    <button onclick="closeModal('replyModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form method="POST" class="flex flex-col flex-1">
                <div class="modal-body p-6">
                    <input type="hidden" name="action" value="send_reply">
                    <input type="hidden" name="report_id" id="replyReportId">
                    
                    <!-- Template Messages -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            <i class="fas fa-templates mr-2"></i>Quick Templates
                        </label>
                        <div class="grid grid-cols-1 gap-3">
                            <button type="button" onclick="useTemplate('thank_you')" class="template-button text-left p-4 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors">
                                <div class="font-medium text-sm text-gray-800">
                                    <i class="fas fa-heart text-red-500 mr-2"></i>Thank You
                                </div>
                                <div class="text-xs text-gray-500 mt-1">Thank you for reporting this issue...</div>
                            </button>
                            <button type="button" onclick="useTemplate('investigating')" class="template-button text-left p-4 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors">
                                <div class="font-medium text-sm text-gray-800">
                                    <i class="fas fa-search text-blue-500 mr-2"></i>Under Investigation
                                </div>
                                <div class="text-xs text-gray-500 mt-1">We are currently investigating this issue...</div>
                            </button>
                            <button type="button" onclick="useTemplate('resolved')" class="template-button text-left p-4 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors">
                                <div class="font-medium text-sm text-gray-800">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>Issue Resolved
                                </div>
                                <div class="text-xs text-gray-500 mt-1">This issue has been resolved...</div>
                            </button>
                            <button type="button" onclick="useTemplate('more_info')" class="template-button text-left p-4 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors">
                                <div class="font-medium text-sm text-gray-800">
                                    <i class="fas fa-question-circle text-yellow-500 mr-2"></i>Need More Information
                                </div>
                                <div class="text-xs text-gray-500 mt-1">We need additional information...</div>
                            </button>
                        </div>
                    </div>

                    <!-- Reply Message -->
                    <div class="mb-6">
                        <label for="reply_message" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-comment mr-2"></i>Reply Message
                        </label>
                        <textarea name="reply_message" id="reply_message" rows="8" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-colors resize-vertical"
                                  placeholder="Type your reply here..."></textarea>
                    </div>

                    <!-- Status Update -->
                    <div class="mb-6">
                        <label for="reply_status" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-flag mr-2"></i>Update Status
                        </label>
                        <select name="status" id="reply_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-colors">
                            <option value="replied">Replied</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="p-6 border-t border-gray-200 flex-shrink-0">
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal('replyModal')" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                            <i class="fas fa-paper-plane mr-2"></i>Send Reply
                        </button>
                    </div>
                </div>
                </div>

            </form>
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
        // Template messages
        const templates = {
            thank_you: "Thank you for reporting this issue. We appreciate you taking the time to help us improve our service. We will review your report and get back to you as soon as possible.",
            investigating: "We are currently investigating this issue and working on a solution. We will keep you updated on our progress and notify you once the issue has been resolved.",
            resolved: "This issue has been resolved. The fix has been implemented and should now be working properly. If you continue to experience any problems, please don't hesitate to contact us again.",
            more_info: "We need additional information to help resolve this issue. Could you please provide more details about when this problem occurs and any steps you took before encountering it?"
        };

        function viewReport(reportId) {
            // Show loading state
            document.getElementById('reportDetails').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-teal-500"></i><p class="mt-2 text-gray-500">Loading report details...</p></div>';
            document.getElementById('viewReportModal').classList.remove('hidden');
            document.getElementById('viewReportModal').classList.add('flex');
            
            // Fetch report details via AJAX
            fetch(`get_report_details.php?id=${reportId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        document.getElementById('reportDetails').innerHTML = data.html;
                    } else {
                        document.getElementById('reportDetails').innerHTML = '<div class="text-center py-8 text-red-500"><i class="fas fa-exclamation-triangle text-2xl mb-2"></i><p>Error: ' + (data.message || 'Unknown error') + '</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('reportDetails').innerHTML = '<div class="text-center py-8 text-red-500"><i class="fas fa-exclamation-triangle text-2xl mb-2"></i><p>Error loading report details. Please try again.</p></div>';
                });
        }

        function replyToReport(reportId) {
            document.getElementById('replyReportId').value = reportId;
            document.getElementById('reply_message').value = '';
            document.getElementById('replyModal').classList.remove('hidden');
            document.getElementById('replyModal').classList.add('flex');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            document.getElementById(modalId).classList.remove('flex');
        }

        function useTemplate(templateKey) {
            document.getElementById('reply_message').value = templates[templateKey];
        }

        function updateStatus(reportId, status) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="report_id" value="${reportId}">
                <input type="hidden" name="status" value="${status}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-backdrop')) {
                e.target.classList.add('hidden');
                e.target.classList.remove('flex');
            }
        });

    </script>
</body>
</html>