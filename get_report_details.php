<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    $auth = new Auth($pdo);
    $auth->checkSession();

    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'Report ID required']);
        exit;
    }

    $reportId = $_GET['id'];

    // Fetch report details with user info
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, u.email 
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

    // Fetch replies with admin info
    $stmt = $pdo->prepare("
        SELECT rr.*, a.email as admin_email 
        FROM report_replies rr 
        LEFT JOIN admin_users a ON rr.admin_id = a.id 
        WHERE rr.report_id = ? 
        ORDER BY rr.created_at ASC
    ");
    $stmt->execute([$reportId]);
    $replies = $stmt->fetchAll();

    // Generate HTML
    ob_start();
    ?>

    <div class="space-y-6">
        <!-- Report Header -->
        <div class="flex justify-between items-start">
            <div>
                <h4 class="text-xl font-semibold text-gray-800">
                    <?php echo ucfirst(str_replace('_', ' ', $report['issue_type'])); ?>
                </h4>
                <p class="text-sm text-gray-500">
                    Report #<?php echo $report['id']; ?> • 
                    Submitted on <?php echo date('M j, Y g:i A', strtotime($report['created_at'])); ?>
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <?php
                $statusClasses = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'in_progress' => 'bg-blue-100 text-blue-800',
                    'replied' => 'bg-green-100 text-green-800',
                    'resolved' => 'bg-gray-100 text-gray-800'
                ];
                $statusClass = $statusClasses[$report['status']] ?? 'bg-gray-100 text-gray-800';
                ?>
                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full <?php echo $statusClass; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                </span>
            </div>
        </div>

        <!-- User Information -->
        <div class="bg-gray-50 rounded-lg p-4">
            <h5 class="font-medium text-gray-800 mb-2">Reporter Information</h5>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Username:</span>
                    <span class="ml-2 font-medium"><?php echo htmlspecialchars($report['username'] ?? 'Unknown'); ?></span>
                </div>
                <div>
                    <span class="text-gray-500">Email:</span>
                    <span class="ml-2 font-medium"><?php echo htmlspecialchars($report['email'] ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>

        <!-- Report Description -->
        <div>
            <h5 class="font-medium text-gray-800 mb-2">Description</h5>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($report['description']); ?></p>
            </div>
        </div>

        <!-- Screenshot -->
        <?php if ($report['screenshot_path']): ?>
            <div>
                <h5 class="font-medium text-gray-800 mb-2">Screenshot</h5>
                <div class="bg-gray-50 rounded-lg p-4">
                    <img src="<?php echo htmlspecialchars($report['screenshot_path']); ?>" 
                         alt="Report Screenshot" 
                         class="max-w-full h-auto rounded-lg shadow-sm cursor-pointer"
                         onclick="window.open(this.src, '_blank')">
                    <p class="text-sm text-gray-500 mt-2">Click to view full size</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Replies -->
        <?php if (!empty($replies)): ?>
            <div>
                <h5 class="font-medium text-gray-800 mb-4">Reply History</h5>
                <div class="space-y-4">
                    <?php foreach ($replies as $reply): ?>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-medium text-blue-800">
                                    Admin (<?php echo htmlspecialchars($reply['admin_email'] ?? 'Unknown'); ?>)
                                </span>
                                <span class="text-sm text-blue-600">
                                    <?php echo date('M j, Y g:i A', strtotime($reply['created_at'])); ?>
                                </span>
                            </div>
                            <p class="text-blue-700 whitespace-pre-wrap"><?php echo htmlspecialchars($reply['message']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <button onclick="replyToReport(<?php echo $report['id']; ?>); closeModal('viewReportModal')" 
                    class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700">
                <i class="fas fa-reply mr-2"></i>Reply
            </button>
        </div>
    </div>

    <script>
function toggleStatusDropdown(button) {
    const dropdown = button.nextElementSibling;
    dropdown.classList.toggle('hidden');
}

// Close all dropdowns when clicking outside
document.addEventListener('click', function(e) {
    // Only close if the click is not on any toggle button or dropdown
    if (!e.target.closest('.relative')) {
        document.querySelectorAll('.status-dropdown').forEach(d => d.classList.add('hidden'));
    }
});

    </script>

    <?php
    $html = ob_get_clean();
    echo json_encode(['success' => true, 'html' => $html]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
