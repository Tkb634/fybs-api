// FYBS Bible Class System

// Class data
const bibleClasses = {
    beginner: [
        {
            id: 1,
            title: "Introduction to Bible Study",
            description: "Learn the fundamentals of effective Bible study methods and tools for spiritual growth.",
            duration: "45 min",
            type: "video",
            content: `
                <h6>Welcome to Bible Study Basics</h6>
                <p>This class will teach you:</p>
                <ul>
                    <li>How to approach Bible reading prayerfully</li>
                    <li>Different Bible study methods</li>
                    <li>Using study tools effectively</li>
                    <li>Applying Scripture to daily life</li>
                </ul>
                <div class="text-center my-4">
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" 
                                title="Bible Study Tutorial" 
                                frameborder="0" 
                                allowfullscreen></iframe>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h6>Key Scriptures</h6>
                        <p>2 Timothy 3:16-17 - "All Scripture is God-breathed and is useful for teaching, rebuking, correcting and training in righteousness..."</p>
                    </div>
                </div>
            `
        },
        {
            id: 2,
            title: "The Story of Salvation",
            description: "Understanding God's redemptive plan for humanity from Genesis to Revelation.",
            duration: "60 min",
            type: "reading",
            content: `
                <h6>The Grand Narrative of Salvation</h6>
                <p>Explore the key moments in God's salvation story:</p>
                <ol>
                    <li><strong>Creation</strong> - God's perfect world</li>
                    <li><strong>Fall</strong> - Sin enters the world</li>
                    <li><strong>Redemption</strong> - God's rescue plan</li>
                    <li><strong>Restoration</strong> - New creation</li>
                </ol>
                <div class="alert alert-info">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Reflection:</strong> Where do you see yourself in God's story?
                </div>
            `
        }
    ],
    intermediate: [
        {
            id: 3,
            title: "The Life of Jesus",
            description: "A comprehensive study of Jesus' ministry, teachings, and miracles.",
            duration: "75 min",
            type: "video",
            content: "Intermediate class content..."
        },
        {
            id: 4,
            title: "Understanding Prayer",
            description: "Deep dive into developing an effective prayer life.",
            duration: "90 min",
            type: "audio",
            content: "Prayer class content..."
        }
    ],
    advanced: [
        {
            id: 5,
            title: "Understanding Prophecy",
            description: "Biblical prophecy interpretation and end times study.",
            duration: "120 min",
            type: "video",
            content: "Prophecy class content..."
        },
        {
            id: 6,
            title: "Theology Fundamentals",
            description: "Core Christian doctrines and systematic theology.",
            duration: "150 min",
            type: "study",
            content: "Theology class content..."
        }
    ]
};

// User progress tracking
let userProgress = JSON.parse(localStorage.getItem('fybs_progress')) || {
    completedClasses: [],
    timeSpent: 0,
    streak: 0,
    lastAccess: null
};

// DOM Elements
const categoryCards = document.querySelectorAll('.category-card');
const classCards = document.querySelectorAll('.class-card');
const currentCategoryEl = document.getElementById('currentCategory');
const progressBadge = document.getElementById('progressBadge');
const overallProgress = document.getElementById('overallProgress');
const completedCount = document.getElementById('completedCount');
const completedBadge = document.getElementById('completedBadge');
const classModal = new bootstrap.Modal(document.getElementById('classModal'));
const modalTitle = document.getElementById('modalTitle');
const modalContent = document.getElementById('modalContent');
const markCompleteBtn = document.getElementById('markCompleteBtn');
const completedClassesBtn = document.getElementById('completedClassesBtn');
const totalClassesEl = document.getElementById('totalClasses');
const timeSpentEl = document.getElementById('timeSpent');
const streakDaysEl = document.getElementById('streakDays');

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateProgressUI();
    setupEventListeners();
    checkStreak();
});

// Setup event listeners
function setupEventListeners() {
    // Category navigation
    categoryCards.forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const level = this.dataset.level;
            showCategory(level);
        });
    });
    
    // Completed classes button
    completedClassesBtn.addEventListener('click', function(e) {
        e.preventDefault();
        showCategory('completed');
    });
    
    // Class click events
    classCards.forEach(card => {
        card.addEventListener('click', function() {
            const classId = parseInt(this.dataset.classId);
            openClassModal(classId);
        });
    });
    
    // Mark as complete button
    markCompleteBtn.addEventListener('click', markClassComplete);
}

// Show specific category
function showCategory(level) {
    // Hide all categories
    document.querySelectorAll('.class-category').forEach(cat => {
        cat.style.display = 'none';
    });
    
    // Show selected category
    const selectedCategory = document.getElementById(level);
    if (selectedCategory) {
        selectedCategory.style.display = 'block';
    }
    
    // Update category title
    const titles = {
        'beginner': 'Beginner Classes',
        'intermediate': 'Intermediate Classes',
        'advanced': 'Advanced Classes',
        'completed': 'Completed Classes'
    };
    
    currentCategoryEl.textContent = titles[level] || 'Classes';
    
    // Highlight active category
    categoryCards.forEach(card => {
        if (card.dataset.level === level) {
            card.classList.add('active-category');
        } else {
            card.classList.remove('active-category');
        }
    });
}

// Open class modal
function openClassModal(classId) {
    // Find class data
    let classData = null;
    for (const level in bibleClasses) {
        const found = bibleClasses[level].find(cls => cls.id === classId);
        if (found) {
            classData = found;
            break;
        }
    }
    
    if (!classData) return;
    
    // Update modal
    modalTitle.textContent = classData.title;
    modalContent.innerHTML = classData.content || '<p>Class content coming soon...</p>';
    
    // Update button
    markCompleteBtn.dataset.classId = classId;
    const isCompleted = userProgress.completedClasses.includes(classId);
    markCompleteBtn.innerHTML = isCompleted ? 
        '<i class="fas fa-check-circle me-2"></i>Completed' : 
        '<i class="fas fa-check me-2"></i>Mark as Complete';
    markCompleteBtn.disabled = isCompleted;
    markCompleteBtn.className = isCompleted ? 'btn btn-success' : 'btn btn-primary';
    
    // Show modal
    classModal.show();
}

// Mark class as complete
function markClassComplete() {
    const classId = parseInt(markCompleteBtn.dataset.classId);
    
    if (!userProgress.completedClasses.includes(classId)) {
        userProgress.completedClasses.push(classId);
        
        // Update time spent (estimate based on class)
        userProgress.timeSpent += 45; // 45 minutes per class
        
        // Update last access
        userProgress.lastAccess = new Date().toISOString();
        
        // Save to localStorage
        localStorage.setItem('fybs_progress', JSON.stringify(userProgress));
        
        // Update UI
        updateProgressUI();
        
        // Update button
        markCompleteBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Completed';
        markCompleteBtn.disabled = true;
        markCompleteBtn.className = 'btn btn-success';
        
        // Show success message
        showNotification('Class completed successfully!', 'success');
        
        // Update completed classes list
        updateCompletedClassesList();
    }
}

// Update progress UI
function updateProgressUI() {
    const totalClasses = 12; // Total classes in system
    const completed = userProgress.completedClasses.length;
    const progress = Math.round((completed / totalClasses) * 100);
    
    // Update progress bar
    overallProgress.style.width = `${progress}%`;
    progressBadge.textContent = `${progress}%`;
    
    // Update counts
    completedCount.textContent = completed;
    completedBadge.textContent = `${completed} classes`;
    
    // Update stats
    totalClassesEl.textContent = totalClasses;
    timeSpentEl.textContent = Math.round(userProgress.timeSpent / 60);
    streakDaysEl.textContent = userProgress.streak;
}

// Check and update streak
function checkStreak() {
    const today = new Date().toDateString();
    const lastAccess = userProgress.lastAccess ? new Date(userProgress.lastAccess).toDateString() : null;
    
    if (lastAccess === today) {
        // Already accessed today
        return;
    }
    
    if (lastAccess) {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        
        if (lastAccess === yesterday.toDateString()) {
            // Consecutive day
            userProgress.streak++;
        } else {
            // Streak broken
            userProgress.streak = 1;
        }
    } else {
        // First time
        userProgress.streak = 1;
    }
    
    userProgress.lastAccess = new Date().toISOString();
    localStorage.setItem('fybs_progress', JSON.stringify(userProgress));
    updateProgressUI();
}

// Update completed classes list
function updateCompletedClassesList() {
    const completedList = document.getElementById('completedClassesList');
    const completedClasses = userProgress.completedClasses;
    
    if (completedClasses.length === 0) {
        completedList.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-trophy display-1 text-muted mb-3"></i>
                <h6>No classes completed yet</h6>
                <p class="text-muted small">Start learning to see your progress here</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    completedClasses.forEach(classId => {
        // Find class info
        let classInfo = null;
        for (const level in bibleClasses) {
            const found = bibleClasses[level].find(cls => cls.id === classId);
            if (found) {
                classInfo = found;
                break;
            }
        }
        
        if (classInfo) {
            html += `
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-1">${classInfo.title}</h6>
                                <small class="text-muted">${classInfo.duration} • Completed</small>
                            </div>
                            <i class="fas fa-check-circle text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            `;
        }
    });
    
    completedList.innerHTML = html;
}

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notification
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `notification alert alert-${type} position-fixed`;
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Style it
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '1050';
    notification.style.minWidth = '300px';
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove
    setTimeout(() => notification.remove(), 3000);
}

// Initialize completed classes list
updateCompletedClassesList();