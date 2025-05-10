document.addEventListener('DOMContentLoaded', function() {
    loadAdminDashboardData();
});

function loadAdminDashboardData() {
    // Load overview dashboard data
    fetch('/iteam-university-website/backend/api/admin_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStats(data.stats);
                updateActivity(data.activity);
            } else {
                console.error('Failed to load dashboard data:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function updateStats(stats) {
    // Update users stats
    const totalUsers = document.querySelector('.stat-card:nth-child(1) .stat-value');
    const userChange = document.querySelector('.stat-card:nth-child(1) .stat-change');
    if (totalUsers && stats.users) {
        totalUsers.textContent = numberWithCommas(stats.users.total);
        updateStatChange(userChange, stats.users.change, stats.users.period);
    }
    
    // Update events stats
    const activeEvents = document.querySelector('.stat-card:nth-child(2) .stat-value');
    const eventChange = document.querySelector('.stat-card:nth-child(2) .stat-change');
    if (activeEvents && stats.events) {
        activeEvents.textContent = stats.events.total;
        updateStatChange(eventChange, stats.events.change, stats.events.period);
    }
    
    // Update organizations stats
    const totalOrgs = document.querySelector('.stat-card:nth-child(3) .stat-value');
    const orgChange = document.querySelector('.stat-card:nth-child(3) .stat-change');
    if (totalOrgs && stats.organizations) {
        totalOrgs.textContent = stats.organizations.total;
        updateStatChange(orgChange, stats.organizations.change, stats.organizations.period);
    }
    
    // Update registrations stats
    const totalRegs = document.querySelector('.stat-card:nth-child(4) .stat-value');
    const regChange = document.querySelector('.stat-card:nth-child(4) .stat-change');
    if (totalRegs && stats.registrations) {
        totalRegs.textContent = numberWithCommas(stats.registrations.total);
        updateStatChange(regChange, stats.registrations.change, stats.registrations.period);
    }
}

function updateStatChange(element, change, period) {
    if (!element) return;
    
    // Remove existing classes
    element.classList.remove('positive', 'negative', 'neutral');
    
    // Determine class and icon based on change value
    let iconClass = 'fa-minus';
    let changeClass = 'neutral';
    
    if (change > 0) {
        iconClass = 'fa-arrow-up';
        changeClass = 'positive';
    } else if (change < 0) {
        iconClass = 'fa-arrow-down';
        changeClass = 'negative';
        // Make change value positive for display
        change = Math.abs(change);
    }
    
    // Add appropriate class
    element.classList.add(changeClass);
    
    // Update content
    let periodText = period === 'week' ? 'this week' : 'this month';
    
    if (change === 0) {
        element.innerHTML = `<i class="fas ${iconClass}"></i> No change`;
    } else {
        element.innerHTML = `<i class="fas ${iconClass}"></i> ${change}% ${periodText}`;
    }
}

function updateActivity(activities) {
    const activityList = document.querySelector('.activity-list');
    if (!activityList || !activities) return;
    
    // Clear existing activities
    activityList.innerHTML = '';
    
    // Add new activities
    activities.forEach(activity => {
        const activityItem = document.createElement('div');
        activityItem.className = 'activity-item';
        
        // Determine icon based on activity type
        let iconClass = 'fa-info-circle';
        let content = '';
        
        if (activity.type === 'registration') {
            if (activity.entity_type === 'user') {
                iconClass = 'fa-user-plus';
                content = `New user registered: <strong>${activity.name}</strong>`;
            } else if (activity.entity_type === 'organization') {
                iconClass = 'fa-building';
                content = `New organization registered: <strong>${activity.name}</strong>`;
            }
        } else if (activity.type === 'event_registration') {
            iconClass = 'fa-calendar-check';
            content = `<strong>${activity.name}</strong> registered for <strong>${activity.title}</strong>`;
        }
        
        // Calculate time ago
        const timeAgo = getTimeAgo(new Date(activity.date));
        
        activityItem.innerHTML = `
            <div class="activity-icon">
                <i class="fas ${iconClass}"></i>
            </div>
            <div class="activity-details">
                <p>${content}</p>
                <span class="activity-time">${timeAgo}</span>
            </div>
        `;
        
        activityList.appendChild(activityItem);
    });
}

function getTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    
    let interval = Math.floor(seconds / 31536000);
    if (interval >= 1) {
        return interval + " year" + (interval === 1 ? "" : "s") + " ago";
    }
    
    interval = Math.floor(seconds / 2592000);
    if (interval >= 1) {
        return interval + " month" + (interval === 1 ? "" : "s") + " ago";
    }
    
    interval = Math.floor(seconds / 86400);
    if (interval >= 1) {
        return interval + " day" + (interval === 1 ? "" : "s") + " ago";
    }
    
    interval = Math.floor(seconds / 3600);
    if (interval >= 1) {
        return interval + " hour" + (interval === 1 ? "" : "s") + " ago";
    }
    
    interval = Math.floor(seconds / 60);
    if (interval >= 1) {
        return interval + " minute" + (interval === 1 ? "" : "s") + " ago";
    }
    
    return "just now";
}

function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}