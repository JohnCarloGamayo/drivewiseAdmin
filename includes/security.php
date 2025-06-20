<?php
class Security {
    
    /**
     * Sanitize output to prevent XSS attacks
     */
    public function sanitizeOutput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeOutput'], $data);
        }
        
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        // Remove null bytes
        $data = str_replace(chr(0), '', $data);
        
        // Trim whitespace
        $data = trim($data);
        
        return $data;
    }
    
    /**
     * Validate and sanitize JSON input
     */
    public function sanitizeJsonInput($json) {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON data');
        }
        
        return $this->sanitizeInput($data);
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Rate limiting
     */
    public function checkRateLimit($identifier, $maxRequests = 100, $timeWindow = 3600) {
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $timeWindow];
        }
        
        $rateData = $_SESSION[$key];
        
        if (time() > $rateData['reset_time']) {
            $_SESSION[$key] = ['count' => 1, 'reset_time' => time() + $timeWindow];
            return true;
        }
        
        if ($rateData['count'] >= $maxRequests) {
            return false;
        }
        
        $_SESSION[$key]['count']++;
        return true;
    }

    /**
     * Check if user is authenticated (for API compatibility)
     */
    public function isAuthenticated() {
        return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
    }
}
?>
