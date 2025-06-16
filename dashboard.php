<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth($pdo);
$auth->checkSession();

// Sample data - replace with actual database queries
$totalUsers = 2845;
$activeUsers = 1924;
$completedExams = 756;
$reports = 142;

$userGrowth = 18;
$activeGrowth = 9;
$examGrowth = 22;
$reportGrowth = -4;

// User activity data
$beginnerPercent = 68;
$intermediatePercent = 24;
$advancedPercent = 8;

// Module completion
$moduleCompletion = 72;
$completionGrowth = 8;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DriveWise Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .metric-card {
            transition: transform 0.2s ease-in-out;
        }
        .metric-card:hover {
            transform: translateY(-2px);
        }
        .progress-circle {
            transform: rotate(-90deg);
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
            <p class="text-gray-600 mt-1">Welcome to DriveWise Admin. Here's what's happening with your app.</p>
        </div>
        
        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Users -->
            <div class="bg-white rounded-lg shadow-sm p-6 metric-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($totalUsers); ?></p>
                        <div class="flex items-center mt-2">
                            <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                            </svg>
                            <span class="text-sm text-green-600 font-medium">+<?php echo $userGrowth; ?>%</span>
                        </div>
                    </div>
                    <div class="p-3 bg-teal-100 rounded-full">
                        <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active Users -->
            <div class="bg-white rounded-lg shadow-sm p-6 metric-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Active Users</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($activeUsers); ?></p>
                        <div class="flex items-center mt-2">
                            <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                            </svg>
                            <span class="text-sm text-green-600 font-medium">+<?php echo $activeGrowth; ?>%</span>
                        </div>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Completed Exams -->
            <div class="bg-white rounded-lg shadow-sm p-6 metric-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Completed Exams</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($completedExams); ?></p>
                        <div class="flex items-center mt-2">
                            <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                            </svg>
                            <span class="text-sm text-green-600 font-medium">+<?php echo $examGrowth; ?>%</span>
                        </div>
                    </div>
                    <div class="p-3 bg-teal-100 rounded-full">
                        <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Reports -->
            <div class="bg-white rounded-lg shadow-sm p-6 metric-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Reports</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($reports); ?></p>
                        <div class="flex items-center mt-2">
                            <svg class="w-4 h-4 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                            </svg>
                            <span class="text-sm text-red-600 font-medium"><?php echo $reportGrowth; ?>%</span>
                        </div>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- User Activity Chart -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">User Activity</h3>
                        <p class="text-sm text-gray-600">Distribution by level</p>
                    </div>
                    <button class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <!-- Beginner -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-teal-500 rounded-full mr-3"></div>
                            <span class="text-sm font-medium text-gray-700">Beginner</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900"><?php echo $beginnerPercent; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-teal-500 h-2 rounded-full" style="width: <?php echo $beginnerPercent; ?>%"></div>
                    </div>

                    <!-- Intermediate -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                            <span class="text-sm font-medium text-gray-700">Intermediate</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900"><?php echo $intermediatePercent; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $intermediatePercent; ?>%"></div>
                    </div>

                    <!-- Advanced -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-purple-500 rounded-full mr-3"></div>
                            <span class="text-sm font-medium text-gray-700">Advanced</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900"><?php echo $advancedPercent; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-purple-500 h-2 rounded-full" style="width: <?php echo $advancedPercent; ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Module Completion Chart -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Module Completion</h3>
                        <p class="text-sm text-gray-600">Average completion rate</p>
                    </div>
                    <button class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="flex items-center justify-center">
                    <div class="relative w-40 h-40">
                        <svg class="w-full h-full progress-circle" viewBox="0 0 100 100">
                            <!-- Background circle -->
                            <circle cx="50" cy="50" r="40" stroke="#e5e7eb" stroke-width="8" fill="none"/>
                            <!-- Progress circle -->
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
                    <span class="text-sm text-green-600 font-medium">+<?php echo $completionGrowth; ?>% from last month</span>
                </div>
            </div>
        </div>

        <!-- Top Performers -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-800">Top Performers</h3>
                <button class="text-teal-600 hover:text-teal-700 text-sm font-medium">View All</button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-medium text-gray-600">Rank</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-600">User</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-600">Score</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-600">Exams Completed</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-600">Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-yellow-100 text-yellow-800 text-sm font-medium rounded-full">1</span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-teal-600 font-medium text-sm">JD</span>
                                    </div>
                                    <span class="font-medium text-gray-900">John Doe</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 font-semibold text-gray-900">98.5%</td>
                            <td class="py-3 px-4 text-gray-600">15</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">Advanced</span>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-100 text-gray-800 text-sm font-medium rounded-full">2</span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-blue-600 font-medium text-sm">JS</span>
                                    </div>
                                    <span class="font-medium text-gray-900">Jane Smith</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 font-semibold text-gray-900">96.2%</td>
                            <td class="py-3 px-4 text-gray-600">12</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Intermediate</span>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-100 text-gray-800 text-sm font-medium rounded-full">3</span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-green-600 font-medium text-sm">MB</span>
                                    </div>
                                    <span class="font-medium text-gray-900">Mike Brown</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 font-semibold text-gray-900">94.8%</td>
                            <td class="py-3 px-4 text-gray-600">14</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">Advanced</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
</body>
</html>