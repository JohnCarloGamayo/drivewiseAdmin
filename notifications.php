<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth($pdo);
$auth->checkSession();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_notification':
                handleSendNotification($pdo);
                break;
            case 'save_template':
                handleSaveTemplate($pdo);
                break;
            case 'delete_template':
                handleDeleteTemplate($pdo);
                break;
            case 'mark_as_read':
                handleMarkAsRead($pdo);
                break;
        }
    }
}

// Functions
function handleSendNotification($pdo) {
    try {
        $title = trim($_POST['title']);
        $message = trim($_POST['message']);
        $recipient_type = $_POST['recipient_type'];
        $specific_user = $_POST['specific_user'] ?? null;

        if (empty($title) || empty($message)) {
            throw new Exception("Title and message are required");
        }

        $admin_id = $_SESSION['admin_id'];
        $sent_at = date('Y-m-d H:i:s');

        if ($recipient_type === 'all') {
            $users_stmt = $pdo->query("SELECT id FROM users WHERE created_at IS NOT NULL");
            $users = $users_stmt->fetchAll();

            if (empty($users)) {
                throw new Exception("No users found to send notifications to");
            }

            // Insert NULL for recipient fields
            $stmt = $pdo->prepare("
                INSERT INTO notifications_sent 
                (title, message, recipient_type, recipient_user_id, recipient_username, recipient_email, sent_at, status) 
                VALUES (?, ?, 'all', NULL, NULL, NULL, ?, 'sent')
            ");

            // Insert only once since recipient is "all"
            $stmt->execute([$title, $message, $sent_at]);
            $notification_id = $pdo->lastInsertId();

            foreach ($users as $user) {
                // Log for each user
                logNotificationHistory($pdo, $notification_id, 'sent', $user['id'], $admin_id, "Notification sent to user ID: " . $user['id']);
            }

            $success_message = "Notification sent successfully to " . count($users) . " users!";
        } elseif ($recipient_type === 'specific' && $specific_user) {
            $user_stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
            $user_stmt->execute([$specific_user]);
            $user = $user_stmt->fetch();

            if (!$user) {
                throw new Exception("Selected user not found");
            }

            $stmt = $pdo->prepare("
                INSERT INTO notifications_sent 
                (title, message, recipient_type, recipient_user_id, recipient_username, recipient_email, sent_at, status) 
                VALUES (?, ?, 'specific', ?, ?, ?, ?, 'sent')
            ");
            $stmt->execute([$title, $message, $user['id'], $user['username'], $user['email'], $sent_at]);
            $notification_id = $pdo->lastInsertId();

            logNotificationHistory($pdo, $notification_id, 'sent', $user['id'], $admin_id, "Notification sent to specific user: " . $user['username']);

            $success_message = "Notification sent successfully to " . $user['username'] . "!";
        } else {
            throw new Exception("Invalid recipient selection");
        }

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}


function handleSaveTemplate($pdo) {
    try {
        $name = trim($_POST['template_name']);
        $title = trim($_POST['template_title']);
        $message = trim($_POST['template_message']);
        
        if (empty($name) || empty($title) || empty($message)) {
            throw new Exception("All template fields are required");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO notification_templates (name, title, message, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$name, $title, $message]);
        
        $template_success = "Template saved successfully!";
    } catch (Exception $e) {
        $template_error = "Error: " . $e->getMessage();
    }
}

function handleDeleteTemplate($pdo) {
    try {
        $template_id = $_POST['template_id'];
        $stmt = $pdo->prepare("DELETE FROM notification_templates WHERE id = ?");
        $stmt->execute([$template_id]);
        $template_success = "Template deleted successfully!";
    } catch (Exception $e) {
        $template_error = "Error: " . $e->getMessage();
    }
}

function handleMarkAsRead($pdo) {
    try {
        $notification_id = $_POST['notification_id'];
        $notification_type = $_POST['notification_type'];
        $current_time = date('Y-m-d H:i:s');
        
        if ($notification_type === 'user') {
            $stmt = $pdo->prepare("UPDATE users SET notification_read = 1, notification_read_at = ? WHERE id = ?");
            $stmt->execute([$current_time, $notification_id]);
            logNotificationHistory($pdo, null, 'marked_read', $notification_id, $_SESSION['admin_id'], "User registration notification marked as read");
        } elseif ($notification_type === 'report') {
            $stmt = $pdo->prepare("UPDATE reports SET notification_read = 1, notification_read_at = ? WHERE id = ?");
            $stmt->execute([$current_time, $notification_id]);
            logNotificationHistory($pdo, null, 'marked_read', null, $_SESSION['admin_id'], "Report notification marked as read for report ID: " . $notification_id);
        }
        
        $success_message = "Notification marked as read!";
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

function logNotificationHistory($pdo, $notification_id, $action, $user_id = null, $admin_id = null, $details = null) {
    $stmt = $pdo->prepare("
        INSERT INTO notifications_history (notification_id, action, user_id, admin_id, details, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $stmt->execute([$notification_id, $action, $user_id, $admin_id, $details, $ip_address, $user_agent]);
}

// Get data for display
$users = $pdo->query("SELECT id, username, email FROM users ORDER BY username")->fetchAll();
$templates = $pdo->query("SELECT * FROM notification_templates ORDER BY created_at DESC")->fetchAll();

// Get notification history with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_users_count = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at IS NOT NULL")->fetchColumn();

// FIXED: Get sent notifications with proper recipient counts
$sent_stmt = $pdo->prepare("
    SELECT 
        ns.id,
        ns.title,
        ns.message,
        ns.recipient_type,
        ns.recipient_user_id,
        ns.recipient_username,
        ns.recipient_email,
        ns.sent_at,
        ns.status,
        CASE 
            WHEN ns.recipient_type = 'all' THEN (
                SELECT COUNT(DISTINCT ns2.recipient_user_id) 
                FROM notifications_sent ns2 
                WHERE ns2.title = ns.title 
                AND DATE(ns2.sent_at) = DATE(ns.sent_at)
                AND ns2.recipient_type = 'all'
            )
            ELSE 1
        END as total_recipients
    FROM notifications_sent ns
    WHERE ns.recipient_type IN ('all', 'specific')
    GROUP BY ns.title, DATE(ns.sent_at), ns.recipient_type, ns.recipient_user_id
    ORDER BY DATE(ns.sent_at) DESC, ns.sent_at DESC
    LIMIT " . $offset . ", " . $limit
);
$sent_stmt->execute();
$sent_notifications = $sent_stmt->fetchAll();

// Group notifications by date
$grouped_notifications = [];
foreach ($sent_notifications as $notification) {
    $date = date('Y-m-d', strtotime($notification['sent_at']));
    if (!isset($grouped_notifications[$date])) {
        $grouped_notifications[$date] = [];
    }
    $grouped_notifications[$date][] = $notification;
}

// Get proper counts for incoming notifications
$incoming_counts_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_notifications,
        COUNT(CASE WHEN COALESCE(notification_read, 0) = 0 THEN 1 END) as unread_count,
        COUNT(CASE WHEN COALESCE(notification_read, 0) = 1 THEN 1 END) as read_count
    FROM (
        (SELECT COALESCE(u.notification_read, 0) as notification_read
         FROM users u 
         WHERE u.created_at IS NOT NULL)
        UNION ALL
        (SELECT COALESCE(r.notification_read, 0) as notification_read
         FROM reports r 
         WHERE r.created_at IS NOT NULL)
    ) as all_notifications
");
$incoming_counts = $incoming_counts_stmt->fetch();

$total_incoming = $incoming_counts['total_notifications'];
$unread_incoming = $incoming_counts['unread_count'];
$read_incoming = $incoming_counts['read_count'];

// Get incoming notifications with pagination
$incoming_stmt = $pdo->prepare("
    (SELECT 
        u.id,
        CONCAT('New User Registration: ', u.username) as title,
        CONCAT('User ', u.username, ' (', u.email, ') has registered on the platform') as message,
        'user_registration' as type,
        u.created_at as notification_date,
        u.username as related_user,
        u.email as related_email,
        COALESCE(u.notification_read, 0) as is_read,
        u.notification_read_at,
        'user' as source_table
    FROM users u 
    WHERE u.created_at IS NOT NULL
    ORDER BY u.created_at DESC)
    
    UNION ALL
    
    (SELECT 
        r.id,
        CONCAT('New Report: ', r.issue_type) as title,
        CONCAT('User ', u.username, ' reported: ', SUBSTRING(r.description, 1, 100), '...') as message,
        CONCAT('report_', r.status) as type,
        r.created_at as notification_date,
        u.username as related_user,
        u.email as related_email,
        COALESCE(r.notification_read, 0) as is_read,
        r.notification_read_at,
        'report' as source_table
    FROM reports r 
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.created_at IS NOT NULL)
    
    ORDER BY notification_date DESC 
    LIMIT " . $offset . ", " . $limit
);
$incoming_stmt->execute();
$incoming_notifications = $incoming_stmt->fetchAll();

// FIXED: Get correct total count of unique sent notifications
$total_sent = $pdo->query("
    SELECT COUNT(DISTINCT CONCAT(title, DATE(sent_at), recipient_type)) 
    FROM notifications_sent 
    WHERE recipient_type IN ('all', 'specific')
")->fetchColumn();

$total_pages_sent = ceil($total_sent / $limit);
$total_pages_incoming = ceil($total_incoming / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - DriveWise Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="ml-64 p-6" x-data="notificationApp()">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
                <p class="text-gray-600 mt-1">Manage and send notifications to DriveWise users</p>
            </div>
            <button @click="openModal()" 
                    class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-md flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Send Notification
            </button>
        </div>

        <!-- Updated tabs with proper counts -->
        <div class="mb-6">
            <nav class="flex space-x-8">
                <button @click="activeTab = 'incoming'" 
                        :class="activeTab === 'incoming' ? 'border-teal-500 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-4 4-4-4m-6 0L9 9l-4-4"></path>
                    </svg>
                    Incoming (<?php echo $total_incoming; ?>)
                </button>
                <button @click="activeTab = 'sent'" 
                        :class="activeTab === 'sent' ? 'border-teal-500 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    Sent (<?php echo $total_sent; ?>)
                </button>
                <button @click="activeTab = 'templates'" 
                        :class="activeTab === 'templates' ? 'border-teal-500 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Templates (<?php echo count($templates); ?>)
                </button>
            </nav>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Template Success/Error Messages -->
        <?php if (isset($template_success)): ?>
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <?php echo htmlspecialchars($template_success); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($template_error)): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <?php echo htmlspecialchars($template_error); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Incoming Notifications Tab -->
        <div x-show="activeTab === 'incoming'" class="bg-white rounded-lg shadow">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Incoming Notifications</h2>
                    <div class="flex items-center space-x-4 text-sm">
                        <div class="flex items-center">
                            <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                            <span class="text-gray-600">Unread: <strong><?php echo $unread_incoming; ?></strong></span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                            <span class="text-gray-600">Read: <strong><?php echo $read_incoming; ?></strong></span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                            <span class="text-gray-600">Total: <strong><?php echo $total_incoming; ?></strong></span>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($incoming_notifications)): ?>
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-4 4-4-4m-6 0L9 9l-4-4"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications</h3>
                        <p class="mt-1 text-sm text-gray-500">No incoming notifications at this time.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($incoming_notifications as $notification): ?>
                            <div class="border rounded-lg p-4 transition-all hover:shadow-md <?php echo !$notification['is_read'] ? 'border-l-4 border-l-teal-500 bg-teal-50/30' : 'border-gray-200 opacity-75'; ?>">
                                <div class="flex justify-between items-start gap-4">
                                    <div class="flex-1 space-y-3">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($notification['title']); ?></h3>
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    New
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Read
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if (strpos($notification['type'], 'user_registration') !== false): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                    User Registration
                                                </span>
                                            <?php elseif (strpos($notification['type'], 'report_') !== false): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                    </svg>
                                                    Report
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <p class="text-sm text-gray-600 leading-relaxed"><?php echo htmlspecialchars($notification['message']); ?></p>

                                        <div class="flex items-center gap-4 text-xs text-gray-500">
                                            <div class="flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <?php echo htmlspecialchars($notification['related_user']); ?>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                                <?php echo htmlspecialchars($notification['related_email']); ?>
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <?php echo date('M j, Y g:i A', strtotime($notification['notification_date'])); ?>
                                            </div>
                                            <?php if ($notification['is_read'] && $notification['notification_read_at']): ?>
                                                <div class="flex items-center gap-1 text-green-600">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                    Read: <?php echo date('M j, Y g:i A', strtotime($notification['notification_read_at'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <?php if (!$notification['is_read']): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="mark_as_read">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <input type="hidden" name="notification_type" value="<?php echo $notification['source_table']; ?>">
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                    Mark as Read
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($notification['source_table'] === 'user'): ?>
                                            <a href="users.php?user_id=<?php echo $notification['id']; ?>" 
                                               class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                View User
                                            </a>
                                        <?php elseif ($notification['source_table'] === 'report'): ?>
                                            <a href="reports.php?report_id=<?php echo $notification['id']; ?>" 
                                               class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                </svg>
                                                View Report
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination for incoming notifications -->
                    <?php if ($total_pages_incoming > 1): ?>
                        <div class="mt-6 flex justify-center">
                            <nav class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages_incoming; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>" 
                                       class="px-3 py-2 text-sm font-medium <?php echo $i === $page ? 'text-white bg-teal-600 border-teal-600' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50'; ?> border rounded-md">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages_incoming): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sent Notifications Tab -->
        <div x-show="activeTab === 'sent'" class="bg-white rounded-lg shadow">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Sent Notifications</h2>
                
                <?php if (empty($sent_notifications)): ?>
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications sent</h3>
                        <p class="mt-1 text-sm text-gray-500">Start by sending your first notification.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-8">
                        <?php foreach ($grouped_notifications as $date => $notifications): ?>
                            <div class="mb-6">
                                <div class="space-y-4">
                                    <?php foreach ($notifications as $notification): ?>
                                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-all">
                                            <div class="flex justify-between items-start gap-4">
                                                <div class="flex-1 space-y-3">
                                                    <div>
                                                        <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($notification['title']); ?></h3>
                                                        <p class="text-sm text-gray-600 mt-1 line-clamp-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                    </div>

                                                    <div class="flex items-center gap-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            <?php echo $notification['recipient_type'] === 'all' ? "All Users" : "Specific User"; ?>
                                                        </span>

                                                        <div class="flex gap-2">
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                                <?php echo $notification['status'] === 'read' ? 'bg-green-100 text-green-800' : 
                                                                    ($notification['status'] === 'sent' ? 'bg-yellow-100 text-yellow-800' : 
                                                                    ($notification['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')); ?>">
                                                                <?php echo ucfirst($notification['status']); ?>
                                                            </span>
                                                        </div>

                                                        <?php if ($notification['recipient_type'] === 'specific'): ?>
                                                            <div class="flex gap-2 items-center justify-between text-xs">
                                                                <div class="flex items-center gap-1">
                                                                    <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                                    </svg>
                                                                    <span class="text-gray-500"><?php echo htmlspecialchars($notification['recipient_username']); ?></span>
                                                                </div>
                                                                <div class="text-gray-500">
                                                                    <span><?php echo htmlspecialchars($notification['recipient_email']); ?></span>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="text-xs text-gray-500">
                                                        Sent: <?php echo date('M j, Y g:i A', strtotime($notification['sent_at'])); ?>
                                                    </div>
                                                </div>

                                                <!-- FIXED: Updated button with proper recipient count -->
                                                <button @click="viewNotificationDetails('<?php echo htmlspecialchars($notification['title']); ?>', '<?php echo htmlspecialchars($notification['message']); ?>', '<?php echo $notification['recipient_type']; ?>', '<?php echo $notification['total_recipients']; ?>', '<?php echo date('M j, Y g:i A', strtotime($notification['sent_at'])); ?>', '<?php echo $notification['status'] === 'read' ? 1 : 0; ?>', '<?php echo $notification['status'] === 'sent' ? 1 : 0; ?>', '<?php echo $notification['status'] === 'delivered' ? 1 : 0; ?>', '<?php echo $notification['status'] === 'failed' ? 1 : 0; ?>', '<?php echo htmlspecialchars($notification['recipient_username'] ?? ''); ?>', '<?php echo htmlspecialchars($notification['recipient_email'] ?? ''); ?>')"
                                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    View Details
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Templates Tab -->
        <div x-show="activeTab === 'templates'" class="bg-white rounded-lg shadow">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Saved Templates</h2>
                    <button @click="openTemplateModal()" 
                            class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-md flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create Template
                    </button>
                </div>
                
                <?php if (empty($templates)): ?>
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No templates</h3>
                        <p class="mt-1 text-sm text-gray-500">Create your first notification template to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($templates as $template): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-all">
                                <div class="flex justify-between items-start gap-4">
                                    <div class="flex-1 space-y-2">
                                        <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($template['name']); ?></h3>
                                        <div>
                                            <p class="text-sm text-gray-600">
                                                <span class="font-medium">Title:</span> <?php echo htmlspecialchars($template['title']); ?>
                                            </p>
                                            <p class="text-sm text-gray-600 mt-1 line-clamp-2"><?php echo htmlspecialchars($template['message']); ?></p>
                                        </div>
                                        <p class="text-xs text-gray-400">
                                            Created: <?php echo date('M j, Y', strtotime($template['created_at'])); ?>
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button @click="useTemplate('<?php echo htmlspecialchars($template['title']); ?>', '<?php echo htmlspecialchars($template['message']); ?>')"
                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h8m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Use Template
                                        </button>

                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this template?')">
                                            <input type="hidden" name="action" value="delete_template">
                                            <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Send Notification Modal -->
        <div x-show="showModal" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeModal()"
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
             style="display: none;">
            
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto"
                 @click.stop>
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold text-gray-800">Send New Notification</h2>
                        <button @click="closeModal()" 
                                class="text-gray-400 hover:text-gray-600 focus:outline-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="send_notification">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Use Template (Optional)</label>
                            <select id="templateSelect" @change="loadTemplate()" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Select a template...</option>
                                <?php foreach ($templates as $template): ?>
                                    <option value="<?php echo $template['id']; ?>" 
                                            data-title="<?php echo htmlspecialchars($template['title']); ?>"
                                            data-message="<?php echo htmlspecialchars($template['message']); ?>">
                                        <?php echo htmlspecialchars($template['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notification Title</label>
                            <input type="text" name="title" id="notificationTitle" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                   placeholder="Enter notification title">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                            <textarea name="message" id="notificationMessage" rows="4" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                      placeholder="Enter your notification message"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Recipients</label>
                            <select name="recipient_type" id="recipientType" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                    @change="toggleSpecificUser()">
                                <option value="all">All Users (<?php echo $total_users_count; ?> users)</option>
                                <option value="specific">Specific User</option>
                            </select>
                        </div>
                        
                        <div id="specificUserDiv" x-show="showSpecificUser" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select User</label>
                            <select name="specific_user" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Choose a user...</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['username'] . ' (' . $user['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                            <button type="button" @click="closeModal()"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-teal-600 rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                Send Notification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Create Template Modal -->
        <div x-show="showTemplateModal" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeTemplateModal()"
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
             style="display: none;">
            
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto"
                 @click.stop>
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold text-gray-800">Create Notification Template</h2>
                        <button @click="closeTemplateModal()" 
                                class="text-gray-400 hover:text-gray-600 focus:outline-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="save_template">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Template Name</label>
                            <input type="text" name="template_name" id="templateName" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                   placeholder="e.g., Welcome Message, Weekly Update">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                            <input type="text" name="template_title" id="templateTitle" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                   placeholder="Notification title">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                            <textarea name="template_message" id="templateMessage" rows="4" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                      placeholder="Template message content"></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                            <button type="button" @click="closeTemplateModal()"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-teal-600 rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Save Template
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- FIXED: Notification Details Modal with proper recipient count display -->
        <div x-show="showDetailsModal" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeDetailsModal()"
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
             style="display: none;">
            
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto"
                 @click.stop>
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold text-gray-800">Notification Details</h2>
                        <button @click="closeDetailsModal()" 
                                class="text-gray-400 hover:text-gray-600 focus:outline-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-6">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-1">Title</h4>
                            <p class="text-gray-700" x-text="notificationDetails.title"></p>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-1">Message</h4>
                            <p class="text-gray-700" x-text="notificationDetails.message"></p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-medium text-gray-900 mb-1">Sent At</h4>
                                <p class="text-gray-700" x-text="notificationDetails.sentAt"></p>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900 mb-1">Status</h4>
                                <div class="flex gap-2">
                                    <template x-if="notificationDetails.readCount > 0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Read</span>
                                    </template>
                                    <template x-if="notificationDetails.sentCount > 0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Sent</span>
                                    </template>
                                    <template x-if="notificationDetails.failedCount > 0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Failed</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                        
                        <!-- FIXED: Added recipient count display -->
                        <div class="border-t pt-4 mt-4">
                            <h4 class="font-medium text-gray-900 mb-3">Recipients</h4>
                            <div class="bg-gray-50 p-4 rounded-md border">
                                <template x-if="notificationDetails.recipientType === 'all'">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <div>
                                            <span class="font-medium">All Users</span>
                                        </div>
                                    </div>
                                </template>
                                
                                <template x-if="notificationDetails.recipientType === 'specific' && notificationDetails.recipientUsername">
                                    <div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span class="font-medium" x-text="notificationDetails.recipientUsername"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            <span class="text-gray-600" x-text="notificationDetails.recipientEmail"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end mt-6 pt-6 border-t border-gray-200">
                        <button @click="closeDetailsModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function notificationApp() {
            return {
                activeTab: 'incoming',
                showModal: false,
                showTemplateModal: false,
                showDetailsModal: false,
                showSpecificUser: false,
                notificationDetails: {},
                
                openModal() {
                    this.showModal = true;
                    document.body.style.overflow = 'hidden';
                },
                
                closeModal() {
                    this.showModal = false;
                    document.body.style.overflow = 'auto';
                    this.resetForm();
                },
                
                openTemplateModal() {
                    this.showTemplateModal = true;
                    document.body.style.overflow = 'hidden';
                },
                
                closeTemplateModal() {
                    this.showTemplateModal = false;
                    document.body.style.overflow = 'auto';
                    this.resetTemplateForm();
                },
                
                openDetailsModal() {
                    this.showDetailsModal = true;
                    document.body.style.overflow = 'hidden';
                },
                
                closeDetailsModal() {
                    this.showDetailsModal = false;
                    document.body.style.overflow = 'auto';
                },
                
                resetForm() {
                    document.getElementById('notificationTitle').value = '';
                    document.getElementById('notificationMessage').value = '';
                    document.getElementById('templateSelect').value = '';
                    document.getElementById('recipientType').value = 'all';
                    this.showSpecificUser = false;
                },
                
                resetTemplateForm() {
                    document.getElementById('templateName').value = '';
                    document.getElementById('templateTitle').value = '';
                    document.getElementById('templateMessage').value = '';
                },
                
                toggleSpecificUser() {
                    const recipientType = document.getElementById('recipientType').value;
                    this.showSpecificUser = recipientType === 'specific';
                },
                
                loadTemplate() {
                    const select = document.getElementById('templateSelect');
                    const selectedOption = select.options[select.selectedIndex];
                    
                    if (selectedOption.value) {
                        const title = selectedOption.getAttribute('data-title');
                        const message = selectedOption.getAttribute('data-message');
                        
                        document.getElementById('notificationTitle').value = title;
                        document.getElementById('notificationMessage').value = message;
                    }
                },
                
                useTemplate(title, message) {
                    this.openModal();
                    setTimeout(() => {
                        document.getElementById('notificationTitle').value = title;
                        document.getElementById('notificationMessage').value = message;
                    }, 100);
                },
                
                viewNotificationDetails(title, message, recipientType, totalRecipients, sentAt, readCount, sentCount, deliveredCount, failedCount, recipientUsername = '', recipientEmail = '') {
                    this.notificationDetails = {
                        title: title,
                        message: message,
                        recipientType: recipientType,
                        totalRecipients: totalRecipients,
                        sentAt: sentAt,
                        readCount: readCount,
                        sentCount: sentCount,
                        deliveredCount: deliveredCount,
                        failedCount: failedCount,
                        recipientUsername: recipientUsername,
                        recipientEmail: recipientEmail
                    };
                    this.openDetailsModal();
                }
            }
        }
    </script>
</body>
</html>