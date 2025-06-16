<?php
session_start();
require_once 'config/database.php';

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function login($email, $password, $token) {
        try {
            // Check if admin exists and is verified
            $stmt = $this->pdo->prepare("SELECT id, password FROM admin_users WHERE email = ? AND is_verified = 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if (!$admin || !password_verify($password, $admin['password'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Verify token
            $stmt = $this->pdo->prepare("
                SELECT id FROM email_tokens 
                WHERE admin_id = ? AND token = ? AND expires_at > NOW() AND used = 0
            ");
            $stmt->execute([$admin['id'], $token]);
            $tokenRecord = $stmt->fetch();
            
            if (!$tokenRecord) {
                return ['success' => false, 'message' => 'Invalid or expired token'];
            }
            
            // Mark token as used
            $stmt = $this->pdo->prepare("UPDATE email_tokens SET used = 1 WHERE id = ?");
            $stmt->execute([$tokenRecord['id']]);
            
            // Set session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $email;
            $_SESSION['last_activity'] = time();
            
            return ['success' => true, 'message' => 'Login successful'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login failed'];
        }
    }
    
    public function sendToken($email) {
        try {
            // Check if admin exists
            $stmt = $this->pdo->prepare("SELECT id FROM admin_users WHERE email = ? AND is_verified = 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if (!$admin) {
                return ['success' => false, 'message' => 'Admin not found'];
            }
            
            // Generate 6-digit token
            $token = sprintf("%06d", mt_rand(1, 999999));
            
            // Store token (expires in 10 minutes)
            $stmt = $this->pdo->prepare("
                INSERT INTO email_tokens (admin_id, token, expires_at) 
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
            ");
            $stmt->execute([$admin['id'], $token]);
            
            // Send email
            return $this->sendTokenEmail($email, $token);
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to send token'];
        }
    }
    
    private function sendTokenEmail($email, $token) {
        // Check if PHPMailer files exist and include them
        $phpmailer_path = 'PHPMailer/src/';
        if (!file_exists($phpmailer_path . 'Exception.php') || 
            !file_exists($phpmailer_path . 'PHPMailer.php') || 
            !file_exists($phpmailer_path . 'SMTP.php')) {
            return ['success' => false, 'message' => 'PHPMailer files not found. Please install PHPMailer correctly.'];
        }

        require_once $phpmailer_path . 'Exception.php';
        require_once $phpmailer_path . 'PHPMailer.php';
        require_once $phpmailer_path . 'SMTP.php';
        
        // Create PHPMailer instance using full class names
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
            $mail->addAddress($email);
            
            $mail->isHTML(true);
            $mail->Subject = 'DriveWise Admin Login Token';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <div style='text-align: center; margin-bottom: 30px;'>
                        <h1 style='color: #2dd4bf; margin: 0;'>DriveWise</h1>
                        <h2 style='color: #374151; margin: 10px 0;'>Admin Login Token</h2>
                    </div>
                    
                    <div style='background-color: #f8fafc; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;'>
                        <p style='color: #6b7280; margin: 0 0 15px 0;'>Your 6-digit access token is:</p>
                        <div style='font-size: 32px; font-weight: bold; color: #2dd4bf; letter-spacing: 8px; margin: 15px 0;'>{$token}</div>
                        <p style='color: #ef4444; margin: 15px 0 0 0; font-size: 14px;'>This token will expire in 10 minutes.</p>
                    </div>
                    
                    <div style='background-color: #fef3c7; padding: 15px; border-radius: 6px; border-left: 4px solid #f59e0b;'>
                        <p style='color: #92400e; margin: 0; font-size: 14px;'>
                            <strong>Security Notice:</strong> If you didn't request this token, please ignore this email and contact your system administrator.
                        </p>
                    </div>
                    
                    <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                        <p style='color: #9ca3af; font-size: 12px; margin: 0;'>
                            This is an automated message from DriveWise Admin Panel.<br>
                            Please do not reply to this email.
                        </p>
                    </div>
                </div>
            ";
            
            $mail->send();
            return ['success' => true, 'message' => 'Token sent successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to send email: ' . $mail->ErrorInfo];
        }
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['admin_id']);
    }
    
    public function logout() {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    public function checkSession() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
    }
}
?>