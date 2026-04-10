<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/javascript');
    echo 'console.error("User not logged in");';
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// FIX: Safely get user name to avoid undefined session key
$user_name = $_SESSION['user_name'] ?? 'Guest';

// Set JavaScript content type
header('Content-Type: application/javascript');
?>


// LIFE Hacks JavaScript - FIXED VERSION

// User data from PHP session
const userId = <?php echo $user_id; ?>;
const userName = "<?php echo addslashes($user_name); ?>";

// Global variables
let currentView = 'grid';
let currentTimer = null;
let timerStart = null;
let timerInterval = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== LIFE Hacks Initializing ===');
    console.log('User ID:', userId);
    console.log('User Name:', userName);
    
    if (!userId) {
        showNotification('Please login to use LIFE Hacks', 'warning');
        setTimeout(() => window.location.href = 'login.php', 2000);
        return;
    }
    
    // Initialize AOS if available
    if (typeof AOS !== 'undefined') {
        AOS.init();
    }
    
    loadStats();
    loadHabits();
    loadTimeEntries();
    loadBusinessTips();
    loadHealthTips();
    loadKnowledgeArticles();
    setupEventListeners();
    
    // Set daily motivation
    setDailyMotivation();
    
    // Check for missed habits
    checkMissedHabits();
});

// Load user stats
async function loadStats() {
    try {
        const response = await fetch(`life-api.php?action=get_stats&user_id=${userId}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('activeHabits').textContent = data.stats.active_habits || 0;
            document.getElementById('currentStreak').textContent = data.stats.current_streak || 0;
            document.getElementById('streakCount').textContent = (data.stats.current_streak || 0) + ' 🔥';
            document.getElementById('timeTracked').textContent = (data.stats.total_minutes || 0) + 'm';
            document.getElementById('completedToday').textContent = data.stats.completed_today || 0;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Load habits
async function loadHabits() {
    try {
        const response = await fetch(`life-api.php?action=get_habits&user_id=${userId}`);
        const data = await response.json();
        
        const container = document.getElementById('habitsList');
        if (data.success && data.habits) {
            container.innerHTML = renderHabits(data.habits);
        } else {
            container.innerHTML = '<div class="text-center py-5"><p class="text-muted">No habits yet. Create your first habit!</p></div>';
        }
    } catch (error) {
        console.error('Error loading habits:', error);
    }
}

// Render habits
function renderHabits(habits) {
    if (!habits || habits.length === 0) {
        return '<div class="text-center py-5"><p class="text-muted">No habits yet. Create your first habit!</p></div>';
    }
    
    let html = '';
    habits.forEach(habit => {
        const isCompleted = habit.completed_today;
        const cardClass = isCompleted ? 'completed' : '';
        const today = new Date().toISOString().split('T')[0];
        
        html += `
            <div class="card habit-card ${cardClass} category-${habit.category}" data-habit-id="${habit.id}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="habit-checkbox ${isCompleted ? 'checked' : ''}" 
                                 onclick="toggleHabit(${habit.id}, '${today}')">
                                <i class="fas fa-check ${isCompleted ? '' : 'invisible'}"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="fw-bold mb-1">${escapeHtml(habit.habit_name)}</h6>
                                <small class="text-muted">
                                    <i class="fas fa-${habit.frequency === 'daily' ? 'calendar-day' : 
                                                     habit.frequency === 'weekly' ? 'calendar-week' : 
                                                     'calendar-alt'} me-1"></i>
                                    ${habit.frequency} • 
                                    <span class="streak-badge bg-success text-white ms-2">
                                        ${habit.current_streak} day streak
                                    </span>
                                </small>
                            </div>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-sm btn-outline-secondary" onclick="showHabitDetails(${habit.id})">
                                <i class="fas fa-chart-line"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger ms-1" onclick="deleteHabit(${habit.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    ${habit.notes ? `<p class="mt-2 mb-0 small">${escapeHtml(habit.notes)}</p>` : ''}
                    
                    <div class="progress mt-2">
                        <div class="progress-bar bg-success" style="width: ${(habit.current_streak / habit.longest_streak) * 100 || 0}%"></div>
                    </div>
                    <small class="text-muted">Best: ${habit.longest_streak} days</small>
                </div>
            </div>
        `;
    });
    
    return html;
}

// Toggle habit completion
async function toggleHabit(habitId, date) {
    try {
        const formData = new FormData();
        formData.append('habit_id', habitId);
        formData.append('date', date);
        
        const response = await fetch('life-api.php?action=toggle_habit', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message || 'Habit updated!', 'success');
            loadHabits();
            loadStats();
        } else {
            showNotification(data.error || 'Failed to update habit', 'error');
        }
    } catch (error) {
        showNotification('Network error', 'error');
    }
}

// Add new habit - FIXED VERSION
async function addHabit(event) {
    event.preventDefault();
    console.log('Add habit function called');
    
    // Get form values
    const habitName = document.getElementById('habitName')?.value;
    const habitCategory = document.getElementById('habitCategory')?.value;
    const habitFrequency = document.getElementById('habitFrequency')?.value;
    const habitTarget = document.getElementById('habitTarget')?.value;
    
    console.log('Form values:', { habitName, habitCategory, habitFrequency, habitTarget });
    
    // Validate
    if (!habitName || !habitName.trim()) {
        showNotification('Please enter a habit name', 'error');
        return;
    }
    
    // Create URLSearchParams instead of FormData (more reliable)
    const params = new URLSearchParams();
    params.append('habit_name', habitName);
    params.append('category', habitCategory);
    params.append('frequency', habitFrequency);
    params.append('target', habitTarget || '1');
    params.append('user_id', userId);
    
    console.log('Sending data:', params.toString());
    
    try {
        // Get submit button
        const submitBtn = document.getElementById('submitHabitBtn') || 
                         document.querySelector('#addHabitForm button[type="submit"]');
        
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
            
            // Send request
            const response = await fetch('life-api.php?action=add_habit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            });
            
            console.log('Response status:', response.status);
            
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.success) {
                showNotification('✓ Habit created successfully!', 'success');
                
                // Close modal
                const modalElement = document.getElementById('addHabitModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) modal.hide();
                }
                
                // Reset form
                const form = document.getElementById('addHabitForm');
                if (form) form.reset();
                
                // Reload data after a short delay
                setTimeout(() => {
                    loadHabits();
                    loadStats();
                }, 500);
                
            } else {
                showNotification('✗ ' + (data.error || 'Failed to create habit'), 'error');
            }
            
            // Reset button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
        
    } catch (error) {
        console.error('Network error:', error);
        showNotification('Network error. Please check your connection.', 'error');
        
        // Reset button if it exists
        const submitBtn = document.getElementById('submitHabitBtn') || 
                         document.querySelector('#addHabitForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Create Habit';
        }
    }
}

// Time Tracking Functions
function startTimer() {
    if (!currentTimer) {
        const activity = document.getElementById('activityName').value || 'General Activity';
        const category = document.getElementById('activityCategory').value;
        
        currentTimer = {
            activity: activity,
            category: category,
            start: new Date()
        };
        
        timerStart = new Date();
        
        // Show timer card
        document.getElementById('currentTimerCard').style.display = 'block';
        
        // Start timer display
        timerInterval = setInterval(updateTimerDisplay, 1000);
        
        showNotification('Timer started! ⏱️', 'success');
    }
}

function updateTimerDisplay() {
    if (timerStart) {
        const now = new Date();
        const diff = now - timerStart;
        const hours = Math.floor(diff / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        
        document.getElementById('timerDisplay').textContent = 
            `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
}

function pauseTimer() {
    clearInterval(timerInterval);
    timerInterval = null;
    showNotification('Timer paused', 'info');
}

async function stopTimer() {
    if (currentTimer && timerStart) {
        clearInterval(timerInterval);
        
        const endTime = new Date();
        const duration = Math.floor((endTime - timerStart) / 60000); // in minutes
        
        // Save time entry
        try {
            const params = new URLSearchParams();
            params.append('user_id', userId);
            params.append('activity', currentTimer.activity);
            params.append('category', currentTimer.category);
            params.append('start_time', currentTimer.start.toISOString());
            params.append('end_time', endTime.toISOString());
            params.append('duration', duration);
            
            const response = await fetch('life-api.php?action=save_time_entry', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification(`Time entry saved (${duration} minutes)`, 'success');
                loadTimeEntries();
                loadStats();
            }
        } catch (error) {
            showNotification('Failed to save time entry', 'error');
        }
        
        // Reset timer
        currentTimer = null;
        timerStart = null;
        document.getElementById('currentTimerCard').style.display = 'none';
        document.getElementById('timerDisplay').textContent = '00:00:00';
    }
}

// Load time entries
async function loadTimeEntries() {
    try {
        const response = await fetch(`life-api.php?action=get_time_entries&user_id=${userId}&limit=10`);
        const data = await response.json();
        
        const container = document.getElementById('timeEntries');
        if (data.success && data.entries && data.entries.length > 0) {
            container.innerHTML = renderTimeEntries(data.entries);
        }
    } catch (error) {
        console.error('Error loading time entries:', error);
    }
}

function renderTimeEntries(entries) {
    let html = '';
    entries.forEach(entry => {
        const start = new Date(entry.start_time);
        const end = new Date(entry.end_time);
        const duration = entry.duration_minutes;
        
        html += `
            <div class="card mb-2">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">${escapeHtml(entry.activity_name)}</h6>
                            <small class="text-muted">
                                ${start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - 
                                ${end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                            </small>
                        </div>
                        <span class="badge bg-primary">${duration} min</span>
                    </div>
                </div>
            </div>
        `;
    });
    
    return html;
}

// Business Tips
async function loadBusinessTips() {
    try {
        const response = await fetch('life-api.php?action=get_business_tips');
        const data = await response.json();
        
        const container = document.getElementById('businessTipsContainer');
        if (data.success && data.tips) {
            container.innerHTML = renderBusinessTips(data.tips);
        }
    } catch (error) {
        console.error('Error loading business tips:', error);
    }
}

function renderBusinessTips(tips) {
    let html = '<h6 class="fw-bold mt-4 mb-3">Business Tips</h6>';
    
    tips.forEach(tip => {
        html += `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="fw-bold">${escapeHtml(tip.title)}</h6>
                        <span class="badge bg-primary">${tip.category}</span>
                    </div>
                    <p class="mt-2">${escapeHtml(tip.content)}</p>
                    ${tip.author ? `<small class="text-muted">By: ${escapeHtml(tip.author)}</small>` : ''}
                </div>
            </div>
        `;
    });
    
    return html;
}

// Generate business idea
function generateBusinessIdea() {
    const ideas = [
        "Create a mobile app for local farmers to sell directly to consumers",
        "Start a subscription box service for spiritual growth materials",
        "Develop an online course teaching digital skills to youth",
        "Create a platform connecting mentors with young entrepreneurs",
        "Start a eco-friendly products business targeting millennials"
    ];
    
    const randomIdea = ideas[Math.floor(Math.random() * ideas.length)];
    showNotification(`💡 Idea: ${randomIdea}`, 'info');
}

// Health Tips
async function loadHealthTips() {
    try {
        const response = await fetch('life-api.php?action=get_health_tips');
        const data = await response.json();
        
        if (data.success && data.tips) {
            // Update nutrition tip
            const nutritionTip = data.tips.find(tip => tip.category === 'nutrition');
            if (nutritionTip) {
                document.getElementById('nutritionTip').innerHTML = 
                    `<p class="small">${escapeHtml(nutritionTip.content)}</p>`;
            }
        }
    } catch (error) {
        console.error('Error loading health tips:', error);
    }
}

function generateWorkout() {
    const workouts = [
        "10 min warm-up, 20 min cardio, 10 min strength training",
        "15 min HIIT workout: 30s work, 15s rest x 10 rounds",
        "Full body stretch: 5 min each for legs, back, arms",
        "Yoga flow: Sun salutations x 5, Warrior poses, Savasana"
    ];
    
    const randomWorkout = workouts[Math.floor(Math.random() * workouts.length)];
    document.getElementById('exercisePlan').innerHTML = 
        `<p class="small">Today's workout: ${randomWorkout}</p>`;
    showNotification('New workout generated! 🏋️', 'success');
}

// Knowledge Base
async function loadKnowledgeArticles() {
    try {
        const response = await fetch('life-api.php?action=get_knowledge_articles&limit=5');
        const data = await response.json();
        
        const container = document.getElementById('knowledgeArticles');
        if (data.success && data.articles) {
            container.innerHTML = renderKnowledgeArticles(data.articles);
        }
    } catch (error) {
        console.error('Error loading knowledge articles:', error);
    }
}

function renderKnowledgeArticles(articles) {
    let html = '';
    articles.forEach(article => {
        html += `
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title fw-bold">${escapeHtml(article.title)}</h6>
                    <p class="card-text">${escapeHtml(article.content.substring(0, 150))}...</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-primary">${article.category}</span>
                        <small class="text-muted">${article.views} views</small>
                    </div>
                </div>
            </div>
        `;
    });
    
    return html;
}

function searchKnowledge(query) {
    if (query.length > 2) {
        showNotification(`Searching for: ${query}`, 'info');
    }
}

// Tab Navigation
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-pane').forEach(tab => {
        tab.style.display = 'none';
        tab.classList.remove('active');
    });
    
    // Remove active from all nav cards
    document.querySelectorAll('.nav-card').forEach(card => {
        card.classList.remove('active');
    });
    
    // Show selected tab
    const tabElement = document.getElementById(tabName + 'Tab');
    if (tabElement) {
        tabElement.style.display = 'block';
        tabElement.classList.add('active');
    }
    
    // Load tab data if needed
    switch(tabName) {
        case 'analytics':
            loadAnalytics();
            break;
        case 'business':
            loadBusinessTips();
            break;
        case 'health':
            loadHealthTips();
            break;
        case 'knowledge':
            loadKnowledgeArticles();
            break;
    }
}

// Filter habits by category
function filterHabits(category) {
    const buttons = document.querySelectorAll('.btn-outline-success, .btn-outline-primary, .btn-outline-warning, .btn-outline-info, .btn-outline-secondary, .btn-outline-danger');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    event.target.classList.add('active');
    
    // Filter habits
    const habitCards = document.querySelectorAll('.habit-card');
    habitCards.forEach(card => {
        if (category === 'all' || card.classList.contains(`category-${category}`)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Toggle view (grid/list)
function toggleView() {
    const container = document.getElementById('habitsList');
    if (currentView === 'grid') {
        container.classList.add('list-view');
        container.classList.remove('grid-view');
        currentView = 'list';
    } else {
        container.classList.add('grid-view');
        container.classList.remove('list-view');
        currentView = 'grid';
    }
}

// Set daily motivation
function setDailyMotivation() {
    const quotes = [
        "Small daily improvements lead to stunning results.",
        "Your habits determine your future.",
        "Discipline is choosing between what you want now and what you want most.",
        "Success is the sum of small efforts repeated day in and day out.",
        "The only bad workout is the one that didn't happen.",
        "Don't watch the clock; do what it does. Keep going.",
        "The secret of getting ahead is getting started."
    ];
    
    const randomQuote = quotes[Math.floor(Math.random() * quotes.length)];
    document.getElementById('dailyQuote').textContent = `"${randomQuote}"`;
}

function refreshQuote() {
    setDailyMotivation();
    showNotification('New motivation loaded! ✨', 'info');
}

// Show modals
function showAddHabitModal() {
    const modal = new bootstrap.Modal(document.getElementById('addHabitModal'));
    modal.show();
}

function showQuickAdd() {
    const modal = new bootstrap.Modal(document.getElementById('quickAddModal'));
    modal.show();
}

function quickAdd(type) {
    const modal = bootstrap.Modal.getInstance(document.getElementById('quickAddModal'));
    modal.hide();
    
    const habits = {
        water: { name: "Drink Water", category: "health" },
        prayer: { name: "Prayer Time", category: "spiritual" },
        exercise: { name: "Quick Exercise", category: "health" },
        reading: { name: "Read 10 Pages", category: "productivity" }
    };
    
    if (habits[type]) {
        showNotification(`${habits[type].name} added!`, 'success');
    }
}

// Check for missed habits
function checkMissedHabits() {
    const today = new Date().toDateString();
    const lastCheck = localStorage.getItem('lastHabitCheck');
    
    if (lastCheck !== today) {
        showNotification('Check your habits for today! 📋', 'info');
        localStorage.setItem('lastHabitCheck', today);
    }
}

// Delete habit
async function deleteHabit(habitId) {
    if (confirm('Are you sure you want to delete this habit?')) {
        try {
            const params = new URLSearchParams();
            params.append('habit_id', habitId);
            
            const response = await fetch('life-api.php?action=delete_habit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Habit deleted', 'success');
                loadHabits();
                loadStats();
            }
        } catch (error) {
            showNotification('Failed to delete habit', 'error');
        }
    }
}

// Show habit details
function showHabitDetails(habitId) {
    showNotification('Habit details feature coming soon!', 'info');
}

// Setup event listeners
function setupEventListeners() {
    console.log('Setting up event listeners...');
    
    // Add habit form - FIXED
    const addHabitForm = document.getElementById('addHabitForm');
    if (addHabitForm) {
        console.log('Found add habit form, adding event listener');
        addHabitForm.addEventListener('submit', function(e) {
            console.log('Form submit event fired');
            addHabit(e);
        });
    } else {
        console.error('Add habit form NOT found!');
    }
    
    // Time tracking form
    const timeForm = document.getElementById('timeTrackingForm');
    if (timeForm) {
        timeForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await saveManualTimeEntry();
        });
    }
    
    // Debug: Log all form elements
    console.log('All form elements:');
    const forms = document.querySelectorAll('form');
    forms.forEach((form, index) => {
        console.log(`Form ${index}:`, form.id, form);
    });
}

async function saveManualTimeEntry() {
    const form = document.getElementById('timeTrackingForm');
    const activityName = document.getElementById('activityName')?.value;
    const activityCategory = document.getElementById('activityCategory')?.value;
    
    if (!activityName || !activityName.trim()) {
        showNotification('Please enter an activity name', 'error');
        return;
    }
    
    const params = new URLSearchParams();
    params.append('activityName', activityName);
    params.append('activityCategory', activityCategory);
    params.append('user_id', userId);
    
    try {
        const response = await fetch('life-api.php?action=save_manual_time', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Time entry saved!', 'success');
            form.reset();
            loadTimeEntries();
        }
    } catch (error) {
        showNotification('Failed to save time entry', 'error');
    }
}

// Analytics
async function loadAnalytics() {
    try {
        const response = await fetch(`life-api.php?action=get_analytics&user_id=${userId}`);
        const data = await response.json();
        
        if (data.success) {
            renderHabitChart(data.habit_data);
            renderTimeChart(data.time_data);
            renderMonthlyReport(data.monthly_data);
        }
    } catch (error) {
        console.error('Error loading analytics:', error);
    }
}

// Render charts
function renderHabitChart(habitData) {
    const ctx = document.getElementById('habitChart')?.getContext('2d');
    if (!ctx) return;
    
    const labels = habitData.map(item => new Date(item.date).toLocaleDateString());
    const counts = habitData.map(item => item.count);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Habits Completed',
                data: counts,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function renderTimeChart(timeData) {
    const ctx = document.getElementById('timeChart')?.getContext('2d');
    if (!ctx) return;
    
    const labels = timeData.map(item => item.category);
    const data = timeData.map(item => item.total);
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#3b82f6', // work
                    '#8b5cf6', // study
                    '#10b981', // exercise
                    '#f59e0b', // prayer
                    '#6366f1', // business
                    '#6b7280'  // leisure
                ]
            }]
        }
    });
}

function renderMonthlyReport(monthlyData) {
    const container = document.getElementById('monthlyReport');
    if (!container) return;
    
    container.innerHTML = `
        <div class="col-md-4 text-center">
            <h4 class="text-success">${monthlyData.habits_completed || 0}</h4>
            <small>Habits Completed</small>
        </div>
        <div class="col-md-4 text-center">
            <h4 class="text-primary">${Math.floor((monthlyData.minutes_tracked || 0) / 60)}h</h4>
            <small>Time Tracked</small>
        </div>
        <div class="col-md-4 text-center">
            <h4 class="text-warning">${monthlyData.active_days || 0}</h4>
            <small>Active Days</small>
        </div>
    `;
}

// Utility functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(message, type = 'info') {
    // Remove existing notification
    const existing = document.querySelector('.life-notification');
    if (existing) existing.remove();
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `life-notification alert alert-${type} position-fixed`;
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 
                              type === 'error' ? 'exclamation-circle' : 
                              'info-circle'} me-2"></i>
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

// Initialize AOS
if (typeof AOS !== 'undefined') {
    AOS.init({
        duration: 1000,
        once: true
    });
}

// Test function - run in browser console
function testFormSubmission() {
    console.log('Testing form submission...');
    
    // Simulate form values
    document.getElementById('habitName').value = 'Test Reading';
    document.getElementById('habitCategory').value = 'productivity';
    document.getElementById('habitFrequency').value = 'daily';
    document.getElementById('habitTarget').value = '1';
    
    // Create params
    const params = new URLSearchParams();
    params.append('habit_name', 'Test Reading');
    params.append('category', 'productivity');
    params.append('frequency', 'daily');
    params.append('target', '1');
    params.append('user_id', userId);
    
    console.log('Params:', params.toString());
    
    // Test API directly
    fetch('life-api.php?action=add_habit', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params
    })
    .then(response => response.json())
    .then(data => console.log('Direct API test:', data))
    .catch(error => console.error('Direct API test error:', error));
}