// Auto-logout functionality
class AutoLogout {
    constructor() {
        this.inactivityTime = 180000; // 3 minutes of inactivity 
        this.warningTime = 10000; // 10 seconds warning
        this.warningTimer = null;
        this.logoutTimer = null;
        this.countdownTimer = null;
        this.countdownSeconds = 10;
        
        this.modal = document.getElementById('logoutModal');
        this.countdownElement = document.getElementById('countdown');
        this.stayButton = document.getElementById('stayLoggedIn');
        this.logoutButton = document.getElementById('logoutNow');
        
        this.init();
    }
    
    init() {
        // Reset timers on user activity
        document.addEventListener('mousemove', () => this.resetTimer());
        document.addEventListener('keypress', () => this.resetTimer());
        document.addEventListener('click', () => this.resetTimer());
        document.addEventListener('scroll', () => this.resetTimer());
        
        // Modal button events
        this.stayButton.addEventListener('click', () => this.cancelLogout());
        this.logoutButton.addEventListener('click', () => this.logout());
        
        // Start the timer
        this.resetTimer();
    }
    
    resetTimer() {
        // Clear existing timers
        clearTimeout(this.warningTimer);
        clearTimeout(this.logoutTimer);
        clearInterval(this.countdownTimer);
        
        // Hide modal if showing
        this.hideModal();
        
        // Set new warning timer
        this.warningTimer = setTimeout(() => {
            this.showWarning();
        }, this.inactivityTime);
    }
    
    showWarning() {
        this.countdownSeconds = 10;
        this.showModal();
        this.updateCountdown();
        
        // Start countdown
        this.countdownTimer = setInterval(() => {
            this.countdownSeconds--;
            this.updateCountdown();
            
            if (this.countdownSeconds <= 0) {
                this.logout();
            }
        }, 1000);
        
        // Set logout timer as backup
        this.logoutTimer = setTimeout(() => {
            this.logout();
        }, this.warningTime);
    }
    
    updateCountdown() {
        if (this.countdownElement) {
            this.countdownElement.textContent = this.countdownSeconds;
        }
    }
    
    showModal() {
        if (this.modal) {
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
        }
    }
    
    hideModal() {
        if (this.modal) {
            this.modal.classList.add('hidden');
            this.modal.classList.remove('flex');
        }
    }
    
    cancelLogout() {
        clearTimeout(this.logoutTimer);
        clearInterval(this.countdownTimer);
        this.hideModal();
        this.resetTimer();
    }
    
    logout() {
        // Clear all timers
        clearTimeout(this.warningTimer);
        clearTimeout(this.logoutTimer);
        clearInterval(this.countdownTimer);
        
        // Redirect to logout
        window.location.href = 'logout.php';
    }
}

// Initialize auto-logout when page loads
document.addEventListener('DOMContentLoaded', function() {
    new AutoLogout();
});