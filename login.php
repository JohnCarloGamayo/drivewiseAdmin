<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth($pdo);

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$step = 1; // 1 = email/password, 2 = token verification

if ($_POST) {
    if (isset($_POST['send_token'])) {
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];
        
        if ($email && $password) {
            // Verify credentials first
            $stmt = $pdo->prepare("SELECT id, password FROM admin_users WHERE email = ? AND is_verified = 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                $result = $auth->sendToken($email);
                if ($result['success']) {
                    $step = 2;
                    $_SESSION['temp_email'] = $email;
                    $_SESSION['temp_password'] = $password;
                    $message = 'Token sent to your email. Please check your inbox.';
                } else {
                    $message = $result['message'];
                }
            } else {
                $message = 'Invalid email or password';
            }
        } else {
            $message = 'Please enter valid email and password';
        }
    } elseif (isset($_POST['verify_token'])) {
        $token = $_POST['token'];
        $email = $_SESSION['temp_email'] ?? '';
        $password = $_SESSION['temp_password'] ?? '';
        
        if ($token && $email && $password) {
            $result = $auth->login($email, $password, $token);
            if ($result['success']) {
                unset($_SESSION['temp_email'], $_SESSION['temp_password']);
                header('Location: dashboard.php');
                exit;
            } else {
                $message = $result['message'];
                $step = 2;
            }
        } else {
            $message = 'Invalid token or session expired';
            $step = 1;
        }
    }
}

// Check if we're in token verification step
if (isset($_SESSION['temp_email']) && !isset($_POST['send_token'])) {
    $step = 2;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveWise - Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #f8fafc;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-6">
            <img src="img/drivewiseLogo.png" alt="DriveWise Logo" width="150" class="mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-800">Admin Portal</h1>
            <p class="text-gray-600 text-sm mt-2">
                <?php echo $step == 1 ? 'Enter your credentials to access the admin panel' : 'Enter the 6-digit token sent to your email'; ?>
            </p>
        </div>

        <?php if ($message): ?>
            <div class="mb-4 p-3 rounded <?php echo strpos($message, 'sent') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <!-- Email and Password Form -->
            <form method="POST" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        placeholder="admin@drivewise.com"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    >
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                    >
                </div>
                
                <button 
                    type="submit" 
                    name="send_token"
                    class="w-full bg-teal-600 text-white py-2 px-4 rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition duration-200"
                >
                    Send Token
                </button>
            </form>
        <?php else: ?>
            <!-- Token Verification Form -->
            <form method="POST" class="space-y-4">
                <div>
                    <label for="token" class="block text-sm font-medium text-gray-700 mb-1">6-Digit Token</label>
                    <input 
                        type="text" 
                        id="token" 
                        name="token" 
                        required
                        maxlength="6"
                        pattern="[0-9]{6}"
                        placeholder="000000"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent text-center text-2xl tracking-widest"
                    >
                </div>
                
                <button 
                    type="submit" 
                    name="verify_token"
                    class="w-full bg-teal-600 text-white py-2 px-4 rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition duration-200"
                >
                    Sign In
                </button>
                
                <button 
                    type="button" 
                    onclick="window.location.href='login.php'"
                    class="w-full bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition duration-200"
                >
                    Back
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Auto-format token input
        document.getElementById('token')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 6);
        });
    </script>
</body>
</html>