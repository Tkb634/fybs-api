// Mobile App Functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // Check if running as PWA
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
    
    if (isStandalone) {
        console.log('Running as PWA');
        document.body.classList.add('pwa-mode');
    }
    
    // Add pull-to-refresh functionality
    let touchStartY = 0;
    let isRefreshing = false;
    
    document.addEventListener('touchstart', (e) => {
        touchStartY = e.touches[0].clientY;
    });
    
    document.addEventListener('touchmove', (e) => {
        const touchY = e.touches[0].clientY;
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // If at top and pulling down
        if (scrollTop === 0 && touchY > touchStartY + 50 && !isRefreshing) {
            e.preventDefault();
            refreshPage();
        }
    });
    
    function refreshPage() {
        isRefreshing = true;
        
        // Show refresh indicator
        const indicator = document.createElement('div');
        indicator.className = 'pull-to-refresh';
        indicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
        document.body.insertBefore(indicator, document.body.firstChild);
        
        // Refresh after 1 second
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
    
    // Handle offline status
    window.addEventListener('online', () => {
        showNotification('You are back online!', 'success');
    });
    
    window.addEventListener('offline', () => {
        showNotification('You are offline. Some features may be limited.', 'warning');
    });
    
    // Notification function
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    // Add to home screen prompt (for browsers that support it)
    let deferredPrompt;
    
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent Chrome 67 and earlier from automatically showing the prompt
        e.preventDefault();
        deferredPrompt = e;
        
        // Show install button
        const installBtn = document.getElementById('installApp');
        if (installBtn) {
            installBtn.style.display = 'flex';
            installBtn.addEventListener('click', () => {
                // Show the install prompt
                deferredPrompt.prompt();
                
                // Wait for the user to respond to the prompt
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    } else {
                        console.log('User dismissed the install prompt');
                    }
                    deferredPrompt = null;
                });
            });
        }
    });
    
    // Handle background sync for offline actions
    if ('serviceWorker' in navigator && 'SyncManager' in window) {
        navigator.serviceWorker.ready.then(registration => {
            // Register sync for quiz submissions
            registration.sync.register('sync-quiz-results');
        });
    }
    
    // Add viewport height fix for mobile (iOS 100vh issue)
    function setVH() {
        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
    
    setVH();
    window.addEventListener('resize', setVH);
});

// CSS for notifications (add to your main CSS)
const notificationStyles = `
    .notification {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%) translateY(-100%);
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        padding: 12px 20px;
        z-index: 10000;
        transition: transform 0.3s ease;
        max-width: 90%;
        width: auto;
        min-width: 200px;
    }
    
    .notification.show {
        transform: translateX(-50%) translateY(0);
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .notification-success {
        background: #10b981;
        color: white;
    }
    
    .notification-warning {
        background: #f59e0b;
        color: white;
    }
    
    .notification-info {
        background: #3b82f6;
        color: white;
    }
    
    .notification-error {
        background: #ef4444;
        color: white;
    }
`;

// Add styles to document
const styleSheet = document.createElement("style");
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);