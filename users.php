<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth($pdo);
$auth->checkSession();

// Function to calculate level based on XP
function calculateLevel($xp) {
    if ($xp >= 500) return 'Advanced';
    if ($xp >= 100) return 'Intermediate';
    return 'Beginner';
}

// Function to calculate age from birthday
function calculateAge($birthday) {
    if (!$birthday) return 'N/A';
    $birthDate = new DateTime($birthday);
    $today = new DateTime();
    return $today->diff($birthDate)->y;
}

// Handle AJAX requests for filtering
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $levelFilter = isset($_GET['level']) ? $_GET['level'] : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    
    $conditions = [];
    $params = [];
    
    // Search by username (case-insensitive)
    if (!empty($search)) {
        $conditions[] = "username LIKE :search";
        $params[':search'] = "%$search%";
    }
    
    // Filter by level (calculated from points_earned)
    if ($levelFilter === 'Beginner') {
        $conditions[] = "points_earned < 100";
    } elseif ($levelFilter === 'Intermediate') {
        $conditions[] = "points_earned >= 100 AND points_earned < 500";
    } elseif ($levelFilter === 'Advanced') {
        $conditions[] = "points_earned >= 500";
    }
    
    $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) FROM users $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $limit);
    
    // Get users with required fields
    $query = "SELECT 
        id,
        username,
        email,
        birthday,
        play_time,
        quizzes_passed,
        modules_done,
        points_earned,
        created_at
    FROM users 
    $whereClause
    ORDER BY created_at DESC
    LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add calculated level and age to each user
    foreach ($users as &$user) {
        $user['level'] = calculateLevel($user['points_earned']);
        $user['age'] = calculateAge($user['birthday']);
    }
    
    echo json_encode([
        'users' => $users,
        'totalUsers' => $totalUsers,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ]);
    exit;
}

// Handle AJAX request for user details
if (isset($_GET['get_user']) && isset($_GET['user_id'])) {
    header('Content-Type: application/json');
    
    $userId = (int)$_GET['user_id'];
    
    try {
        $query = "SELECT 
            id,
            username,
            email,
            birthday,
            play_time,
            quizzes_passed,
            modules_done,
            points_earned,
            created_at
        FROM users 
        WHERE id = :user_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $user['level'] = calculateLevel($user['points_earned']);
            $user['age'] = calculateAge($user['birthday']);
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $userId = (int)$_POST['user_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'toggle_status') {
            $toggleQuery = "UPDATE users SET is_active = NOT is_active WHERE id = :user_id";
            $toggleStmt = $pdo->prepare($toggleQuery);
            $toggleStmt->execute([':user_id' => $userId]);
            
            echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Get initial stats
try {
    $totalUsersQuery = "SELECT COUNT(*) FROM users";
    $totalUsers = $pdo->query($totalUsersQuery)->fetchColumn();
    
    $beginnerCount = $pdo->query("SELECT COUNT(*) FROM users WHERE points_earned < 100")->fetchColumn();
    $intermediateCount = $pdo->query("SELECT COUNT(*) FROM users WHERE points_earned >= 100 AND points_earned < 500")->fetchColumn();
    $advancedCount = $pdo->query("SELECT COUNT(*) FROM users WHERE points_earned >= 500")->fetchColumn();
    
    $avgXp = round($pdo->query("SELECT AVG(points_earned) FROM users")->fetchColumn() ?? 0);
    $avgPlayTime = round($pdo->query("SELECT AVG(play_time) FROM users")->fetchColumn() ?? 0);
} catch (Exception $e) {
    $totalUsers = 0;
    $beginnerCount = 0;
    $intermediateCount = 0;
    $advancedCount = 0;
    $avgXp = 0;
    $avgPlayTime = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - DriveWise Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .modal-backdrop {
            backdrop-filter: blur(8px);
            background: rgba(0, 0, 0, 0.4);
        }
        
        .filter-input {
            transition: all 0.3s ease;
        }
        
        .filter-input:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(20, 184, 166, 0.15);
        }
        
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #14b8a6;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .table-hover:hover {
            background-color: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        
        .modal-enter {
            animation: modalEnter 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes modalEnter {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .level-badge-beginner {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
        }

        .level-badge-intermediate {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: white;
        }

        .level-badge-advanced {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            color: white;
        }

        .level-tab {
            transition: all 0.3s ease;
        }

        .level-tab.active {
            background: linear-gradient(135deg, #14b8a6, #0891b2) !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="ml-64 p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">User Management</h1>
            <p class="text-gray-600 mt-2">Manage and monitor user accounts with advanced filtering</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="glass-card rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-lg">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($totalUsers); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-lg">
                        <i class="fas fa-seedling text-white text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Beginners</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($beginnerCount); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-xl shadow-lg">
                        <i class="fas fa-chart-line text-white text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Intermediate</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($intermediateCount); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-lg">
                        <i class="fas fa-crown text-white text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Advanced</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($advancedCount); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-xl shadow-lg">
                        <i class="fas fa-star text-white text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Avg XP</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($avgXp); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dynamic Filters -->
        <div class="glass-card rounded-xl shadow-lg p-6 mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <!-- Search Bar -->
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <input 
                            type="text" 
                            id="searchInput"
                            placeholder="Start typing to search usernames..." 
                            class="filter-input w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        >
                        <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
                    </div>
                </div>
                
                <!-- Level Filter Tabs -->
                <div class="flex flex-wrap gap-2">
                    <button class="level-tab active px-4 py-2 rounded-lg font-medium text-sm bg-gradient-to-r from-teal-500 to-blue-500 text-white" data-level="">
                        <i class="fas fa-users mr-2"></i>All
                    </button>
                    <button class="level-tab px-4 py-2 rounded-lg font-medium text-sm bg-gray-100 text-gray-700 hover:bg-gray-200" data-level="Beginner">
                        <i class="fas fa-seedling mr-2"></i>Beginner
                    </button>
                    <button class="level-tab px-4 py-2 rounded-lg font-medium text-sm bg-gray-100 text-gray-700 hover:bg-gray-200" data-level="Intermediate">
                        <i class="fas fa-chart-line mr-2"></i>Intermediate
                    </button>
                    <button class="level-tab px-4 py-2 rounded-lg font-medium text-sm bg-gray-100 text-gray-700 hover:bg-gray-200" data-level="Advanced">
                        <i class="fas fa-crown mr-2"></i>Advanced
                    </button>
                </div>
                
                <!-- Results per page -->
                <select id="limitFilter" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    <option value="10">10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                </select>
            </div>
        </div>

        <!-- Users Table -->
        <div class="glass-card rounded-xl shadow-lg">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-table text-teal-600 mr-2"></i>
                        Users Table
                    </h2>
                    <div id="loadingIndicator" class="hidden">
                        <div class="loading-spinner"></div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Level</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Users will be loaded here -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div id="paginationContainer" class="px-6 py-4 border-t border-gray-200">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <div id="userDetailsModal" class="fixed inset-0 modal-backdrop hidden items-center justify-center z-50">
        <div class="glass-card rounded-2xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto modal-enter">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <i class="fas fa-user text-teal-600 mr-2"></i>
                        User Details
                    </h3>
                    <button onclick="closeModal('userDetailsModal')" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="userDetailsContent" class="p-6">
                <!-- User details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Auto Logout Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-sm mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Session Timeout Warning</h3>
            <p class="text-gray-600 mb-4">You will be logged out in <span id="countdown">10</span> seconds due to inactivity.</p>
            <div class="flex space-x-3">
                <button id="stayLoggedIn" class="flex-1 bg-teal-600 text-white py-2 px-4 rounded hover:bg-teal-700">Stay Logged In</button>
                <button id="logoutNow" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded hover:bg-gray-400">Logout Now</button>
            </div>
        </div>
    </div>

    <script src="js/auto-logout.js"></script>
    <script>
        let currentPage = 1;
        let isLoading = false;
        let currentLevel = '';

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
            
            // Search input with instant filtering (no debounce, triggers on every keystroke)
            document.getElementById('searchInput').addEventListener('input', function() {
                loadUsers(); // Instant search - no delay
            });
            
            document.getElementById('limitFilter').addEventListener('change', loadUsers);
            
            // Level filter tabs
            document.querySelectorAll('.level-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    // Update active tab
                    document.querySelectorAll('.level-tab').forEach(t => {
                        t.classList.remove('active');
                        t.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
                        t.style.background = '';
                        t.style.color = '';
                        t.style.transform = '';
                        t.style.boxShadow = '';
                    });
                    this.classList.add('active');
                    this.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
                    
                    currentLevel = this.dataset.level;
                    loadUsers();
                });
            });
        });

        // Remove the debounce function since we want instant search
        // function debounce(func, wait) { ... } - REMOVED

        // Load users with filters - now triggers instantly
        function loadUsers(page = 1) {
            if (isLoading) return;
            
            isLoading = true;
            currentPage = page;
            
            document.getElementById('loadingIndicator').classList.remove('hidden');
            
            const search = document.getElementById('searchInput').value;
            const limit = document.getElementById('limitFilter').value;
            
            // Only search if there's at least 1 character or if search is empty (show all)
            const params = new URLSearchParams({
                ajax: '1',
                search: search, // Will search immediately when 1+ characters are typed
                level: currentLevel,
                limit: limit,
                page: page
            });
            
            fetch(`users.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    renderUsersTable(data.users);
                    renderPagination(data.currentPage, data.totalPages, data.totalUsers);
                    document.getElementById('loadingIndicator').classList.add('hidden');
                    isLoading = false;
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                    document.getElementById('loadingIndicator').classList.add('hidden');
                    isLoading = false;
                });
        }

        // Render users table
        function renderUsersTable(users) {
            const tbody = document.getElementById('usersTableBody');
            
            if (users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-users text-gray-300 text-6xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No users found</h3>
                                <p class="text-gray-500">Try adjusting your search or filters.</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            const usersHTML = users.map(user => {
                const levelClass = `level-badge-${user.level.toLowerCase()}`;
                const initials = user.username.charAt(0).toUpperCase();
                
                return `
                    <tr class="table-hover">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-r from-teal-400 to-blue-500 flex items-center justify-center shadow-lg">
                                        <span class="text-white font-medium text-sm">${initials}</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">${user.username}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${user.email}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${levelClass}">
                                ${user.level}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="showUserDetails(${user.id})" 
                                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-gradient-to-r from-teal-500 to-blue-600 hover:from-teal-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition-all duration-200">
                                <i class="fas fa-eye mr-2"></i>
                                View
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
            
            tbody.innerHTML = usersHTML;
        }

        // Render pagination
        function renderPagination(currentPage, totalPages, totalUsers) {
            const container = document.getElementById('paginationContainer');
            
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            let paginationHTML = `
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing page ${currentPage} of ${totalPages} (${totalUsers} total users)
                    </div>
                    <div class="flex items-center space-x-2">
            `;
            
            // Previous button
            if (currentPage > 1) {
                paginationHTML += `
                    <button onclick="loadUsers(${currentPage - 1})" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors">
                        <i class="fas fa-chevron-left mr-1"></i> Previous
                    </button>
                `;
            }
            
            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === currentPage;
                paginationHTML += `
                    <button onclick="loadUsers(${i})" class="px-3 py-2 text-sm font-medium ${isActive ? 'text-teal-600 bg-teal-50 border-teal-500' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50'} border rounded-lg transition-colors">
                        ${i}
                    </button>
                `;
            }
            
            // Next button
            if (currentPage < totalPages) {
                paginationHTML += `
                    <button onclick="loadUsers(${currentPage + 1})" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors">
                        Next <i class="fas fa-chevron-right ml-1"></i>
                    </button>
                `;
            }
            
            paginationHTML += '</div></div>';
            container.innerHTML = paginationHTML;
        }

        // Show user details modal
        function showUserDetails(userId) {
            document.getElementById('userDetailsContent').innerHTML = `
                <div class="text-center py-8">
                    <div class="loading-spinner mx-auto mb-4"></div>
                    <p class="text-gray-600">Loading user details...</p>
                </div>
            `;
            
            document.getElementById('userDetailsModal').classList.remove('hidden');
            document.getElementById('userDetailsModal').classList.add('flex');
            
            // Fetch user details
            fetch(`users.php?get_user=1&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderUserDetails(data.user);
                    } else {
                        document.getElementById('userDetailsContent').innerHTML = `
                            <div class="text-center py-8">
                                <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                                <p class="text-red-600">Error: ${data.message}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching user details:', error);
                    document.getElementById('userDetailsContent').innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                            <p class="text-red-600">Network error loading user details</p>
                        </div>
                    `;
                });
        }

        // Render user details
        function renderUserDetails(user) {
            const levelClass = `level-badge-${user.level.toLowerCase()}`;
            const initials = user.username.charAt(0).toUpperCase();
            const playTimeHours = Math.floor(user.play_time / 60);
            const playTimeMinutes = user.play_time % 60;
            
            document.getElementById('userDetailsContent').innerHTML = `
                <div class="space-y-6">
                    <!-- User Header -->
                    <div class="flex items-center space-x-6 p-6 bg-gradient-to-r from-teal-50 to-blue-50 rounded-xl">
                        <div class="w-20 h-20 rounded-full bg-gradient-to-r from-teal-400 to-blue-500 flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-2xl">${initials}</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900">${user.username}</h3>
                            <p class="text-gray-600">${user.email}</p>
                            <div class="mt-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${levelClass}">
                                    ${user.level}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-xl text-center">
                            <div class="text-2xl font-bold text-blue-600">${user.age}</div>
                            <div class="text-sm text-blue-600 font-medium">Age</div>
                        </div>
                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 p-4 rounded-xl text-center">
                            <div class="text-2xl font-bold text-purple-600">${parseInt(user.points_earned).toLocaleString()}</div>
                            <div class="text-sm text-purple-600 font-medium">XP Points</div>
                        </div>
                        <div class="bg-gradient-to-r from-green-50 to-green-100 p-4 rounded-xl text-center">
                            <div class="text-2xl font-bold text-green-600">${user.quizzes_passed}</div>
                            <div class="text-sm text-green-600 font-medium">Quizzes Passed</div>
                        </div>
                        <div class="bg-gradient-to-r from-orange-50 to-orange-100 p-4 rounded-xl text-center">
                            <div class="text-2xl font-bold text-orange-600">${user.modules_done}</div>
                            <div class="text-sm text-orange-600 font-medium">Modules Done</div>
                        </div>
                    </div>

                    <!-- Detailed Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-xl border border-gray-200">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-user text-teal-600 mr-2"></i>
                                Personal Information
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Username:</span>
                                    <span class="font-medium">${user.username}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Email:</span>
                                    <span class="font-medium">${user.email}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Birthday:</span>
                                    <span class="font-medium">${user.birthday ? new Date(user.birthday).toLocaleDateString() : 'Not provided'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Age:</span>
                                    <span class="font-medium">${user.age} years old</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-gray-200">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-chart-bar text-purple-600 mr-2"></i>
                                Activity Statistics
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Play Time:</span>
                                    <span class="font-medium">${playTimeHours}h ${playTimeMinutes}m</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Quizzes Passed:</span>
                                    <span class="font-medium">${user.quizzes_passed}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Modules Completed:</span>
                                    <span class="font-medium">${user.modules_done}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total XP:</span>
                                    <span class="font-medium">${parseInt(user.points_earned).toLocaleString()}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Level Progress -->
                    <div class="bg-white p-6 rounded-xl border border-gray-200">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-trophy text-yellow-600 mr-2"></i>
                            Level Progress
                        </h4>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Current Level:</span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${levelClass}">
                                    ${user.level}
                                </span>
                            </div>
                            
                            ${user.level === 'Beginner' ? `
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span>Progress to Intermediate</span>
                                        <span>${user.points_earned}/100 XP</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="bg-gradient-to-r from-green-500 to-blue-500 h-3 rounded-full" style="width: ${Math.min((user.points_earned / 100) * 100, 100)}%"></div>
                                    </div>
                                </div>
                            ` : user.level === 'Intermediate' ? `
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span>Progress to Advanced</span>
                                        <span>${user.points_earned}/500 XP</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="bg-gradient-to-r from-yellow-500 to-orange-500 h-3 rounded-full" style="width: ${Math.min((user.points_earned / 500) * 100, 100)}%"></div>
                                    </div>
                                </div>
                            ` : `
                                <div class="text-center py-4">
                                    <i class="fas fa-crown text-purple-500 text-3xl mb-2"></i>
                                    <p class="text-gray-600">Congratulations! You've reached the highest level!</p>
                                </div>
                            `}
                        </div>
                    </div>
                </div>
            `;
        }

        // Toggle user status
        function toggleUserStatus(userId, isActive) {
            const action = isActive ? 'deactivate' : 'activate';
            
            if (confirm(`Are you sure you want to ${action} this user?`)) {
                const formData = new FormData();
                formData.append('action', 'toggle_status');
                formData.append('user_id', userId);
                
                fetch('users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadUsers(currentPage);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error toggling user status:', error);
                    alert('Network error occurred');
                });
            }
        }

        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            document.getElementById(modalId).classList.remove('flex');
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-backdrop')) {
                closeModal(e.target.id);
            }
        });
    </script>
</body>
</html>
