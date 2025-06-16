<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth($pdo);
$auth->checkSession();

// Get current admin info
$stmt = $pdo->prepare("SELECT email FROM admin_users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

$message = '';
$messageType = '';
$activeTab = $_GET['tab'] ?? 'profile';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['send_email_pin'])) {
        $newEmail = filter_var($_POST['new_email'], FILTER_VALIDATE_EMAIL);
        
        if ($newEmail && $newEmail !== $admin['email']) {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
            $stmt->execute([$newEmail, $_SESSION['admin_id']]);
            
            if (!$stmt->fetch()) {
                $result = sendEmailChangePin($admin['email'], $newEmail);
                if ($result['success']) {
                    $_SESSION['temp_new_email'] = $newEmail;
                    $message = 'PIN sent to your current email address.';
                    $messageType = 'success';
                } else {
                    $message = $result['message'];
                    $messageType = 'error';
                }
            } else {
                $message = 'Email address already exists.';
                $messageType = 'error';
            }
        } else {
            $message = 'Please enter a valid email address.';
            $messageType = 'error';
        }
    } elseif (isset($_POST['verify_email_pin'])) {
        $pin = $_POST['email_pin'];
        $newEmail = $_SESSION['temp_new_email'] ?? '';
        
        if ($pin && $newEmail) {
            $result = verifyEmailChangePin($_SESSION['admin_id'], $pin, $newEmail);
            if ($result['success']) {
                unset($_SESSION['temp_new_email']);
                $admin['email'] = $newEmail;
                $_SESSION['admin_email'] = $newEmail;
                $message = 'Email updated successfully.';
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        } else {
            $message = 'Invalid PIN or session expired.';
            $messageType = 'error';
        }
    } elseif (isset($_POST['cancel_email_change'])) {
        // Handle email change cancellation
        unset($_SESSION['temp_new_email']);
        $message = 'Email change cancelled.';
        $messageType = 'success';
    } elseif (isset($_POST['change_password'])) {
        $activeTab = 'security';
        
        // Verify reCAPTCHA
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        $recaptcha_secret = '6Le4LWIrAAAAAM-VN0mOEboCq55cx0-hGTUyMf3Q';
        
        $recaptcha_verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}");
        $recaptcha_result = json_decode($recaptcha_verify, true);
        
        if (!$recaptcha_result['success']) {
            $message = 'Please complete the reCAPTCHA verification.';
            $messageType = 'error';
        } else {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Validate current password
            $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $adminData = $stmt->fetch();
            
            if (!password_verify($currentPassword, $adminData['password'])) {
                $message = 'Current password is incorrect.';
                $messageType = 'error';
            } elseif ($newPassword !== $confirmPassword) {
                $message = 'New passwords do not match.';
                $messageType = 'error';
            } elseif (!isValidPassword($newPassword)) {
                $message = 'Password does not meet requirements.';
                $messageType = 'error';
            } else {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['admin_id']]);
                
                $message = 'Password updated successfully.';
                $messageType = 'success';
            }
        }
    }
}

// Handle GET request for cancellation (for JavaScript cancel)
if (isset($_GET['cancel_email'])) {
    unset($_SESSION['temp_new_email']);
    header('Location: account.php?tab=profile');
    exit;
}

function sendEmailChangePin($currentEmail, $newEmail) {
    global $pdo;
    
    // Generate 6-digit PIN
    $pin = sprintf("%06d", mt_rand(1, 999999));
    
    // Store PIN in database
    $stmt = $pdo->prepare("
        INSERT INTO email_change_pins (admin_id, pin, new_email, expires_at) 
        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
        ON DUPLICATE KEY UPDATE pin = VALUES(pin), new_email = VALUES(new_email), expires_at = VALUES(expires_at), used = 0
    ");
    $stmt->execute([$_SESSION['admin_id'], $pin, $newEmail]);
    
    // Send email
    $phpmailer_path = 'PHPMailer/src/';
    if (!file_exists($phpmailer_path . 'Exception.php')) {
        return ['success' => false, 'message' => 'PHPMailer not found.'];
    }

    require_once $phpmailer_path . 'Exception.php';
    require_once $phpmailer_path . 'PHPMailer.php';
    require_once $phpmailer_path . 'SMTP.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'johncarlogamayo@gmail.com';
        $mail->Password = 'oewz nopj xfqk obux';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('johncarlogamayo@gmail.com', 'DriveWise Admin');
        $mail->addAddress($currentEmail);
        
        $mail->isHTML(true);
        $mail->Subject = 'Email Change Verification - DriveWise Admin';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #2dd4bf; margin: 0;'>DriveWise</h1>
                    <h2 style='color: #374151; margin: 10px 0;'>Email Change Verification</h2>
                </div>
                
                <div style='background-color: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p style='color: #6b7280; margin: 0 0 15px 0;'>You requested to change your email address to:</p>
                    <p style='color: #374151; font-weight: bold; margin: 0 0 15px 0;'>{$newEmail}</p>
                    <p style='color: #6b7280; margin: 0 0 15px 0;'>Your 6-digit verification PIN is:</p>
                    <div style='font-size: 32px; font-weight: bold; color: #2dd4bf; letter-spacing: 8px; text-align: center; margin: 15px 0;'>{$pin}</div>
                    <p style='color: #ef4444; margin: 15px 0 0 0; font-size: 14px; text-align: center;'>This PIN will expire in 10 minutes.</p>
                </div>
                
                <div style='background-color: #fef3c7; padding: 15px; border-radius: 6px; border-left: 4px solid #f59e0b;'>
                    <p style='color: #92400e; margin: 0; font-size: 14px;'>
                        <strong>Security Notice:</strong> If you didn't request this email change, please ignore this message and contact support immediately.
                    </p>
                </div>
            </div>
        ";
        
        $mail->send();
        return ['success' => true, 'message' => 'PIN sent successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to send email'];
    }
}

function verifyEmailChangePin($adminId, $pin, $newEmail) {
    global $pdo;
    
    // Verify PIN
    $stmt = $pdo->prepare("
        SELECT id FROM email_change_pins 
        WHERE admin_id = ? AND pin = ? AND new_email = ? AND expires_at > NOW() AND used = 0
    ");
    $stmt->execute([$adminId, $pin, $newEmail]);
    $pinRecord = $stmt->fetch();
    
    if (!$pinRecord) {
        return ['success' => false, 'message' => 'Invalid or expired PIN'];
    }
    
    // Update email
    $stmt = $pdo->prepare("UPDATE admin_users SET email = ? WHERE id = ?");
    $stmt->execute([$newEmail, $adminId]);
    
    // Mark PIN as used
    $stmt = $pdo->prepare("UPDATE email_change_pins SET used = 1 WHERE id = ?");
    $stmt->execute([$pinRecord['id']]);
    
    return ['success' => true, 'message' => 'Email updated successfully'];
}

function isValidPassword($password) {
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password) &&
           preg_match('/[^A-Za-z0-9]/', $password);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Account - DriveWise Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .input-modern {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid #e2e8f0;
        }
        
        .input-modern:focus {
            border-color: #14b8a6;
            box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.1);
            transform: translateY(-1px);
        }
        
        .btn-modern {
            background: linear-gradient(135deg, #14b8a6, #0d9488);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 14px rgba(20, 184, 166, 0.3);
        }
        
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(20, 184, 166, 0.4);
        }
        
        .btn-cancel {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 14px rgba(107, 114, 128, 0.3);
        }
        
        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(107, 114, 128, 0.4);
        }
        
        .tab-modern {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .tab-modern.active {
            background: linear-gradient(135deg, #14b8a6, #0d9488);
            color: white;
            box-shadow: 0 4px 14px rgba(20, 184, 166, 0.3);
        }
        
        .tab-modern:not(.active) {
            background: rgba(255, 255, 255, 0.7);
            color: #64748b;
        }
        
        .tab-modern:not(.active):hover {
            background: rgba(255, 255, 255, 0.9);
            color: #475569;
            transform: translateY(-1px);
        }
        
        .avatar-glow {
            position: relative;
        }
        
        .avatar-glow::before {
            content: '';
            position: absolute;
            inset: -4px;
            background: linear-gradient(45deg, #14b8a6, #06b6d4, #8b5cf6, #14b8a6);
            border-radius: 50%;
            opacity: 0.7;
            animation: rotate 3s linear infinite;
            z-index: -1;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: linear-gradient(90deg, #ef4444, #f87171); width: 25%; }
        .strength-fair { background: linear-gradient(90deg, #f59e0b, #fbbf24); width: 50%; }
        .strength-good { background: linear-gradient(90deg, #10b981, #34d399); width: 75%; }
        .strength-strong { background: linear-gradient(90deg, #059669, #10b981); width: 100%; }
        
        .notification-slide {
            animation: slideInRight 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .modal-backdrop {
            backdrop-filter: blur(8px);
            background: rgba(0, 0, 0, 0.4);
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
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
    </style>
</head>
<body class="min-h-screen">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="ml-64 min-h-screen flex flex-col items-center justify-center p-6">
        <!-- Centered Container -->
        <div class="w-full max-w-2xl">
            <!-- Modern Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-teal-500 to-blue-600 rounded-2xl mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent mb-2">Admin Account</h1>
                <p class="text-gray-600">Manage your profile and security settings</p>
            </div>

            <!-- Notification Toast -->
            <?php if ($message): ?>
                <div class="fixed top-6 right-6 z-50 notification-slide">
                    <div class="glass-card rounded-xl p-4 max-w-sm shadow-lg">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <?php if ($messageType === 'success'): ?>
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                <?php else: ?>
                                    <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    <?php echo $messageType === 'success' ? 'Success!' : 'Error!'; ?>
                                </p>
                                <p class="text-sm text-gray-600 mt-1">
                                    <?php echo htmlspecialchars($message); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Modern Tab Navigation -->
            <div class="flex justify-center mb-8">
                <div class="glass-card rounded-2xl p-2 inline-flex space-x-2">
                    <a href="?tab=profile" class="tab-modern px-6 py-3 rounded-xl font-medium text-sm flex items-center space-x-2 <?php echo $activeTab === 'profile' ? 'active' : ''; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span>Profile</span>
                    </a>
                    <a href="?tab=security" class="tab-modern px-6 py-3 rounded-xl font-medium text-sm flex items-center space-x-2 <?php echo $activeTab === 'security' ? 'active' : ''; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <span>Security</span>
                    </a>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="glass-card rounded-3xl p-8 card-hover">
                <?php if ($activeTab === 'profile'): ?>
                    <!-- Profile Tab -->
                    <div class="text-center">
                        <div class="mb-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Profile Information</h3>
                            <p class="text-gray-600">Update your admin profile details</p>
                        </div>

                        <!-- Modern Avatar with Glow -->
                        <div class="flex justify-center mb-8">
                            <div class="avatar-glow">
                                <div class="w-24 h-24 bg-gradient-to-r from-teal-400 to-blue-500 rounded-full flex items-center justify-center shadow-xl">
                                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <form method="POST" class="space-y-6 max-w-md mx-auto">
                            <!-- Name Field -->
                            <div class="text-left">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Name</label>
                                <div class="relative">
                                    <input type="text" value="Admin User" readonly class="w-full px-4 py-3 pl-12 bg-gray-50 border-2 border-gray-200 rounded-xl text-gray-500 cursor-not-allowed">
                                    <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                        <div class="w-5 h-5 bg-gray-400 rounded-full flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Name cannot be changed</p>
                            </div>

                            <!-- Email Field -->
                            <div class="text-left">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                                <div class="relative">
                                    <input type="email" name="new_email" value="<?php echo htmlspecialchars($admin['email']); ?>" class="w-full px-4 py-3 pl-12 input-modern rounded-xl focus:outline-none" required>
                                    <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                        <div class="w-5 h-5 bg-teal-500 rounded-full flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4">
                                <button type="submit" name="send_email_pin" class="w-full btn-modern text-white font-semibold py-3 px-6 rounded-xl focus:outline-none focus:ring-4 focus:ring-teal-300">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Security Tab -->
                    <div class="text-center">
                        <div class="mb-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Security Settings</h3>
                            <p class="text-gray-600">Update your password and security preferences</p>
                        </div>

                        <form method="POST" class="space-y-6 max-w-md mx-auto">
                            <!-- Current Password -->
                            <div class="text-left">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Current Password</label>
                                <div class="relative">
                                    <input type="password" name="current_password" required class="w-full px-4 py-3 pl-12 pr-12 input-modern rounded-xl focus:outline-none" placeholder="Enter current password">
                                    <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                        <div class="w-5 h-5 bg-gray-500 rounded-full flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <button type="button" onclick="togglePassword(this)" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- New Password -->
                            <div class="text-left">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">New Password</label>
                                <div class="relative">
                                    <input type="password" name="new_password" required class="w-full px-4 py-3 pl-12 pr-12 input-modern rounded-xl focus:outline-none" placeholder="Enter new password" onkeyup="checkPasswordStrength(this)">
                                    <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                        <div class="w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <button type="button" onclick="togglePassword(this)" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <!-- Password Strength -->
                                <div class="mt-2">
                                    <div class="w-full bg-gray-200 rounded-full h-1">
                                        <div id="passwordStrength" class="password-strength bg-gray-200 rounded-full"></div>
                                    </div>
                                    <p id="strengthText" class="text-xs text-gray-500 mt-1">Password strength</p>
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="text-left">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Confirm New Password</label>
                                <div class="relative">
                                    <input type="password" name="confirm_password" required class="w-full px-4 py-3 pl-12 pr-12 input-modern rounded-xl focus:outline-none" placeholder="Confirm new password">
                                    <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                        <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <button type="button" onclick="togglePassword(this)" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Password Requirements -->
                            <div class="glass-card rounded-xl p-4 border border-blue-200">
                                <div class="flex items-start space-x-3">
                                    <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <p class="text-sm font-semibold text-blue-900 mb-1">Password Requirements</p>
                                        <p class="text-xs text-blue-700">8+ characters, uppercase, lowercase, number, and special character</p>
                                    </div>
                                </div>
                            </div>

                            <!-- reCAPTCHA -->
                            <div class="flex justify-center py-2">
                                <div class="g-recaptcha" data-sitekey="6Le4LWIrAAAAAIixs_5PL0G9LcnAQ1YhHHJATMnB"></div>
                            </div>

                            <div class="pt-4">
                                <button type="submit" name="change_password" class="w-full btn-modern text-white font-semibold py-3 px-6 rounded-xl focus:outline-none focus:ring-4 focus:ring-teal-300">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Enhanced Email PIN Modal with Cancel Options -->
    <?php if (isset($_SESSION['temp_new_email'])): ?>
    <div id="pinModal" class="fixed inset-0 modal-backdrop flex items-center justify-center z-50">
        <div class="glass-card rounded-3xl p-8 max-w-sm mx-4 w-full shadow-2xl modal-enter">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-r from-teal-400 to-blue-500 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Email Verification</h3>
                <p class="text-gray-600 mb-2">Enter the 6-digit PIN sent to your current email</p>
                <p class="text-sm text-gray-500">Changing to: <span class="font-semibold text-teal-600"><?php echo htmlspecialchars($_SESSION['temp_new_email']); ?></span></p>
            </div>
            
            <form method="POST" class="space-y-6">
                <input type="text" name="email_pin" maxlength="6" pattern="[0-9]{6}" required placeholder="000000" class="w-full px-4 py-3 input-modern rounded-xl text-center text-2xl tracking-widest font-mono focus:outline-none">
                
                <div class="space-y-3">
                    <button type="submit" name="verify_email_pin" class="w-full btn-modern text-white font-semibold py-3 px-4 rounded-xl focus:outline-none">
                        <span class="flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Verify PIN</span>
                        </span>
                    </button>
                    
                    <div class="flex space-x-3">
                        <button type="submit" name="cancel_email_change" class="flex-1 btn-cancel text-white font-semibold py-2 px-4 rounded-xl focus:outline-none">
                            <span class="flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span>Cancel</span>
                            </span>
                        </button>
                        
                        <button type="button" onclick="cancelEmailChange()" class="flex-1 bg-gray-100 text-gray-700 font-semibold py-2 px-4 rounded-xl hover:bg-gray-200 transition-colors focus:outline-none">
                            <span class="flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                <span>Back</span>
                            </span>
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Close button (X) -->
            <button type="button" onclick="cancelEmailChange()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
    <?php endif; ?>

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
        function togglePassword(button) {
            const input = button.previousElementSibling.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
        }

        function checkPasswordStrength(input) {
            const password = input.value;
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            let text = 'Very Weak';
            
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength rounded-full transition-all duration-300';
            
            switch(strength) {
                case 0:
                case 1:
                    strengthBar.classList.add('strength-weak');
                    text = 'Very Weak';
                    break;
                case 2:
                    strengthBar.classList.add('strength-fair');
                    text = 'Fair';
                    break;
                case 3:
                case 4:
                    strengthBar.classList.add('strength-good');
                    text = 'Good';
                    break;
                case 5:
                    strengthBar.classList.add('strength-strong');
                    text = 'Strong';
                    break;
            }
            
            strengthText.textContent = text;
        }

        function cancelEmailChange() {
            window.location.href = 'account.php?cancel_email=1';
        }

        // Auto-format PIN input
        document.querySelector('input[name="email_pin"]')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 6);
        });

        // Auto-hide notification
        setTimeout(() => {
            const notification = document.querySelector('.notification-slide');
            if (notification) {
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            }
        }, 5000);

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('pinModal')) {
                cancelEmailChange();
            }
        });

        // Close modal on backdrop click
        document.getElementById('pinModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                cancelEmailChange();
            }
        });
    </script>
</body>
</html>