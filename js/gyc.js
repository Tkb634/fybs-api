// GYC - Global Youth Chat System

// Sample data for demonstration
let gycData = {
    onlineUsers: 24,
    prayerRequests: [
        {
            id: 1,
            title: "Praying for Exam Success",
            content: "Please pray for my final exams this week. I need wisdom and clarity.",
            author: "John D.",
            timestamp: "2 hours ago",
            status: "pending",
            prayers: 24,
            encouragements: 12,
            comments: 5,
            isAnonymous: false
        },
        {
            id: 2,
            title: "Healing for Mother",
            content: "Update: God healed my mother completely! Thank you all for praying!",
            author: "Sarah M.",
            timestamp: "5 hours ago",
            status: "answered",
            prayers: 156,
            encouragements: 89,
            comments: 42,
            isAnonymous: false,
            praiseReport: "Medical tests came back clear!"
        }
    ],
    testimonies: [
        {
            id: 1,
            title: "God Provided a Job!",
            content: "After months of unemployment, God opened a door for a perfect position!",
            author: "Michael T.",
            timestamp: "1 day ago",
            category: "provision",
            likes: 45
        }
    ],
    chatMessages: [
        {
            id: 1,
            user: "Admin",
            message: "Welcome to GYC! Let's build each other up in faith.",
            timestamp: "10:00 AM",
            isSelf: false
        },
        {
            id: 2,
            user: "You",
            message: "Glad to be here! Looking forward to growing together.",
            timestamp: "10:05 AM",
            isSelf: true
        }
    ]
};

// User data from localStorage
let userData = JSON.parse(localStorage.getItem('gyc_user')) || {
    name: "Guest",
    prayerCount: 0,
    testimonyCount: 0,
    chatHistory: []
};

// DOM Elements
const onlineCount = document.getElementById('onlineCount');
const prayerCount = document.getElementById('prayerCount');
const testimonyCount = document.getElementById('testimonyCount');
const answeredCount = document.getElementById('answeredCount');
const prayerRequestsDiv = document.getElementById('prayerRequests');
const testimoniesList = document.getElementById('testimoniesList');
const chatContainer = document.getElementById('chatContainer');
const chatInput = document.getElementById('chatInput');
const prayerForm = document.getElementById('prayerForm');
const testimonyForm = document.getElementById('testimonyForm');

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initGYC();
    loadPrayerRequests();
    loadTestimonies();
    loadChatMessages();
    setupEventListeners();
});

function initGYC() {
    // Update stats
    onlineCount.textContent = gycData.onlineUsers;
    prayerCount.textContent = gycData.prayerRequests.length;
    testimonyCount.textContent = gycData.testimonies.length;
    
    // Calculate answered prayers
    const answeredPrayers = gycData.prayerRequests.filter(p => p.status === 'answered').length;
    answeredCount.textContent = answeredPrayers;
    
    // Simulate online user count changes
    setInterval(() => {
        const change = Math.floor(Math.random() * 3) - 1; // -1, 0, or 1
        gycData.onlineUsers = Math.max(10, gycData.onlineUsers + change);
        onlineCount.textContent = gycData.onlineUsers;
    }, 30000);
}

function loadPrayerRequests() {
    let html = '';
    
    gycData.prayerRequests.forEach(prayer => {
        const isAnswered = prayer.status === 'answered';
        const badgeClass = isAnswered ? 'bg-success' : 'bg-warning';
        const badgeText = isAnswered ? 'Answered' : 'Needs Prayer';
        
        html += `
            <div class="card mb-3 prayer-card ${isAnswered ? 'answered' : ''}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold mb-1">${prayer.title}</h6>
                            <small class="text-muted">By: ${prayer.isAnonymous ? 'Anonymous' : prayer.author} • ${prayer.timestamp}</small>
                        </div>
                        <span class="badge ${badgeClass}">${badgeText}</span>
                    </div>
                    <p class="mb-3">${prayer.content}</p>
                    
                    ${prayer.praiseReport ? `
                        <div class="alert alert-success mb-3">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Praise Report:</strong> ${prayer.praiseReport}
                        </div>
                    ` : ''}
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button class="btn btn-sm btn-outline-primary me-2 prayer-action" 
                                    onclick="handlePrayerAction(${prayer.id}, 'pray')"
                                    ${isAnswered ? 'disabled' : ''}>
                                <i class="fas fa-hands-praying me-1"></i>Pray (${prayer.prayers})
                            </button>
                            <button class="btn btn-sm btn-outline-success prayer-action" 
                                    onclick="handlePrayerAction(${prayer.id}, 'encourage')"
                                    ${isAnswered ? 'disabled' : ''}>
                                <i class="fas fa-heart me-1"></i>Encourage (${prayer.encouragements})
                            </button>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary"
                                onclick="showComments(${prayer.id})">
                            <i class="fas fa-comment me-1"></i>Comment (${prayer.comments})
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    prayerRequestsDiv.innerHTML = html;
}

function loadTestimonies() {
    let html = '';
    
    if (gycData.testimonies.length === 0) {
        html = `
            <div class="text-center py-5">
                <i class="fas fa-star display-1 text-muted mb-3"></i>
                <h6>No testimonies yet</h6>
                <p class="text-muted small">Be the first to share what God has done!</p>
            </div>
        `;
    } else {
        gycData.testimonies.forEach(testimony => {
            html += `
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold mb-1">${testimony.title}</h6>
                                <small class="text-muted">By: ${testimony.author} • ${testimony.timestamp}</small>
                            </div>
                            <span class="badge bg-warning">${testimony.category}</span>
                        </div>
                        <p class="mb-3">${testimony.content}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <button class="btn btn-sm btn-outline-warning" 
                                    onclick="likeTestimony(${testimony.id})">
                                <i class="fas fa-heart me-1"></i>Bless This (${testimony.likes})
                            </button>
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-share me-1"></i>Share
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    testimoniesList.innerHTML = html;
}

function loadChatMessages() {
    let html = '';
    
    gycData.chatMessages.forEach(msg => {
        const messageClass = msg.isSelf ? 'self' : 'other';
        html += `
            <div class="chat-message ${messageClass}">
                <div class="chat-message-content">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <strong>${msg.user}</strong>
                        <small class="chat-message-time">${msg.timestamp}</small>
                    </div>
                    <p class="mb-0">${msg.message}</p>
                </div>
            </div>
        `;
    });
    
    chatContainer.innerHTML = html;
    scrollToBottom();
}

function setupEventListeners() {
    // Prayer form submission
    if (prayerForm) {
        prayerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const title = document.getElementById('prayerTitle').value;
            const content = document.getElementById('prayerContent').value;
            const isAnonymous = document.getElementById('anonymousPrayer').checked;
            
            if (title && content) {
                const newPrayer = {
                    id: Date.now(),
                    title: title,
                    content: content,
                    author: isAnonymous ? 'Anonymous' : userData.name,
                    timestamp: 'Just now',
                    status: 'pending',
                    prayers: 0,
                    encouragements: 0,
                    comments: 0,
                    isAnonymous: isAnonymous
                };
                
                gycData.prayerRequests.unshift(newPrayer);
                loadPrayerRequests();
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('prayerModal'));
                modal.hide();
                
                // Reset form
                prayerForm.reset();
                
                // Show success message
                showNotification('Prayer request submitted successfully!', 'success');
                
                // Update stats
                prayerCount.textContent = gycData.prayerRequests.length;
            }
        });
    }
    
    // Testimony form submission
    if (testimonyForm) {
        testimonyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const title = document.getElementById('testimonyTitle').value;
            const content = document.getElementById('testimonyContent').value;
            const category = document.getElementById('testimonyCategory').value;
            
            if (title && content) {
                const newTestimony = {
                    id: Date.now(),
                    title: title,
                    content: content,
                    author: userData.name,
                    timestamp: 'Just now',
                    category: category,
                    likes: 0
                };
                
                gycData.testimonies.unshift(newTestimony);
                loadTestimonies();
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('testimonyModal'));
                modal.hide();
                
                // Reset form
                testimonyForm.reset();
                
                // Show success message
                showNotification('Testimony shared successfully!', 'success');
                
                // Update stats
                testimonyCount.textContent = gycData.testimonies.length;
                
                // Update user data
                userData.testimonyCount++;
                localStorage.setItem('gyc_user', JSON.stringify(userData));
            }
        });
    }
    
    // Load more prayers button
    const loadMoreBtn = document.getElementById('loadMorePrayers');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            // Simulate loading more prayers
            for (let i = 0; i < 2; i++) {
                gycData.prayerRequests.push({
                    id: Date.now() + i,
                    title: "Sample Prayer Request " + (gycData.prayerRequests.length + 1),
                    content: "This is a sample prayer request content.",
                    author: "User " + (i + 1),
                    timestamp: "Recently",
                    status: 'pending',
                    prayers: Math.floor(Math.random() * 20),
                    encouragements: Math.floor(Math.random() * 10),
                    comments: Math.floor(Math.random() * 5),
                    isAnonymous: false
                });
            }
            
            loadPrayerRequests();
            showNotification('Loaded more prayer requests', 'info');
        });
    }
}

function sendMessage() {
    const message = chatInput.value.trim();
    
    if (message) {
        const newMessage = {
            id: Date.now(),
            user: "You",
            message: message,
            timestamp: getCurrentTime(),
            isSelf: true
        };
        
        gycData.chatMessages.push(newMessage);
        loadChatMessages();
        
        // Clear input
        chatInput.value = '';
        
        // Simulate response after 1-3 seconds
        setTimeout(() => {
            const responses = [
                "Amen! Praying with you!",
                "That's wonderful news!",
                "God is faithful!",
                "Thanks for sharing that!",
                "Let's continue to trust in God!"
            ];
            
            const botResponse = {
                id: Date.now() + 1,
                user: "Community",
                message: responses[Math.floor(Math.random() * responses.length)],
                timestamp: getCurrentTime(),
                isSelf: false
            };
            
            gycData.chatMessages.push(botResponse);
            loadChatMessages();
        }, 1000 + Math.random() * 2000);
    }
}

function handlePrayerAction(prayerId, action) {
    const prayer = gycData.prayerRequests.find(p => p.id === prayerId);
    
    if (prayer && prayer.status !== 'answered') {
        if (action === 'pray') {
            prayer.prayers++;
            showNotification('You prayed for this request 🙏', 'success');
        } else if (action === 'encourage') {
            prayer.encouragements++;
            showNotification('You encouraged this request ❤️', 'success');
        }
        
        loadPrayerRequests();
        
        // Update user data
        userData.prayerCount++;
        localStorage.setItem('gyc_user', JSON.stringify(userData));
    }
}

function likeTestimony(testimonyId) {
    const testimony = gycData.testimonies.find(t => t.id === testimonyId);
    
    if (testimony) {
        testimony.likes++;
        loadTestimonies();
        showNotification('You blessed this testimony 🌟', 'success');
    }
}

function showComments(prayerId) {
    showNotification('Comments feature coming soon!', 'info');
}

function getCurrentTime() {
    const now = new Date();
    return `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
}

function scrollToBottom() {
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

function showNotification(message, type = 'info') {
    // Remove existing notification
    const existing = document.querySelector('.gyc-notification');
    if (existing) existing.remove();
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `gyc-notification alert alert-${type} position-fixed`;
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Style it
    notification.style.top = '70px';
    notification.style.right = '20px';
    notification.style.zIndex = '1050';
    notification.style.minWidth = '300px';
    notification.style.maxWidth = '90vw';
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove
    setTimeout(() => notification.remove(), 3000);
}

// Auto-refresh data every 30 seconds
setInterval(() => {
    // Simulate new prayer request
    if (Math.random() > 0.7) { // 30% chance
        const notificationCount = document.querySelector('.notification-count');
        if (notificationCount) {
            const current = parseInt(notificationCount.textContent) || 0;
            notificationCount.textContent = current + 1;
            notificationCount.style.animation = 'pulse 1s';
            
            setTimeout(() => {
                notificationCount.style.animation = '';
            }, 1000);
        }
    }
}, 30000);

// Enter key support for chat
chatInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        sendMessage();
    }
});