// --- KEEP THE FOLLOWING FUNCTIONS ---

// This function will be called by dashboard.html after content is loaded
function initAdminOverview() {
    console.log("Initializing Admin Overview...");
    fetchAdminStats(); // Fetch data when initialized
}

function fetchAdminStats() {
    const statsGrid = document.querySelector('#content-container .stats-grid'); // Be more specific with selector context
    const activityList = document.querySelector('#content-container .activity-list'); // Be more specific

    // Set initial loading states
    if (statsGrid) {
        statsGrid.querySelectorAll('.stat-change').forEach(el => {
            el.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        });
        statsGrid.querySelectorAll('.stat-value').forEach(el => {
            el.textContent = '0';
        });
    }
    if (activityList) {
        activityList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading recent activity...</div>';
    }

    // Use relative path, consistent with dashboard.html's location
    fetch('../backend/api/admin/admin_stats.php')
        .then(response => {
            if (!response.ok) {
                // Try to get more specific error from backend if possible
                return response.json().then(errData => {
                    throw new Error(errData.message || `HTTP error! Status: ${response.status}`);
                }).catch(() => {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Ensure the elements are still present before updating
                if (document.querySelector('#content-container .stats-grid')) {
                   updateStatCards(data.stats);
                }
                if (document.querySelector('#content-container .activity-list')) {
                   renderRecentActivity(data.activity);
                }
            } else {
                console.error('Failed to load admin stats:', data.message);
                showOverviewError('Failed to load dashboard data: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching admin stats:', error);
            showOverviewError('Could not connect to fetch dashboard data. ' + error.message);
        });
}

function updateStatCards(stats) {
    if (!stats) return;
    // Use more specific selectors within the content container
    updateStatCard('#content-container .stat-card:nth-child(1)', stats.users);
    updateStatCard('#content-container .stat-card:nth-child(2)', stats.events);
    updateStatCard('#content-container .stat-card:nth-child(3)', stats.organizations);
    updateStatCard('#content-container .stat-card:nth-child(4)', stats.registrations);
}

function updateStatCard(selector, statData) {
    const card = document.querySelector(selector);
    if (!card || !statData) {
        console.warn(`Card or data not found for selector: ${selector}`);
        return;
    }

    const valueEl = card.querySelector('.stat-value');
    const changeEl = card.querySelector('.stat-change');

    if (valueEl) valueEl.textContent = numberWithCommas(statData.total || 0);

    if (changeEl) {
        // Ensure change is treated as a number, default to 0 if null/undefined
        const change = Number(statData.change) || 0; 
        const period = statData.period || 'period'; // Use period from data or default
        let iconClass = 'fa-minus';
        let changeClass = 'neutral';

        if (change > 0) {
            iconClass = 'fa-arrow-up';
            changeClass = 'positive';
        } else if (change < 0) {
            iconClass = 'fa-arrow-down';
            changeClass = 'negative';
        }
        
        // Update class and content
        changeEl.className = `stat-change ${changeClass}`; // Reset classes and apply new one
        changeEl.innerHTML = `<i class="fas ${iconClass}"></i> ${Math.abs(change)}% this ${period}`;
    } else {
         console.warn(`Change element (.stat-change) not found in card: ${selector}`);
    }
}

function renderRecentActivity(activity) {
    const activityList = document.querySelector('#content-container .activity-list'); // Specific selector
    if (!activityList) return;

    if (!activity || activity.length === 0) {
        activityList.innerHTML = '<div class="empty-state">No recent activity found.</div>';
        return;
    }

    activityList.innerHTML = ''; // Clear loading spinner

    activity.forEach(item => {
        const activityItem = document.createElement('div');
        activityItem.className = 'activity-item';
        let iconClass = 'fa-info-circle';
        let description = '';

        // Use the types returned by admin_stats.php
        switch(item.type) {
            case 'user_registration': // Match PHP output
                iconClass = 'fa-user-plus';
                description = `<strong>${item.name}</strong> registered as a new user.`;
                break;
            case 'org_registration': // Match PHP output
                iconClass = 'fa-building';
                description = `<strong>${item.name}</strong> registered as a new organization.`;
                break;
            case 'event_registration': // Match PHP output
                 iconClass = 'fa-ticket-alt';
                 description = `<strong>${item.name}</strong> registered for <strong>${item.title}</strong>.`;
                 break;
            case 'event_creation': // Match PHP output
                 iconClass = 'fa-calendar-plus';
                 description = `New event created: <strong>${item.title}</strong>.`;
                 break;
            default:
                 description = `Recent activity: ${item.type}`; // Fallback
        }

        const timeAgo = getTimeAgo(new Date(item.date));

        activityItem.innerHTML = `
            <div class="activity-icon"><i class="fas ${iconClass}"></i></div>
            <div class="activity-details">
                <p>${description}</p>
                <span class="activity-time">${timeAgo}</span>
            </div>
        `;
        activityList.appendChild(activityItem);
    });
}

function showOverviewError(message) {
     // Use specific selectors
     const statsGrid = document.querySelector('#content-container .stats-grid');
     const activityList = document.querySelector('#content-container .activity-list');
     const errorHtml = `<span class="error-text" style="color: #dc3545;"><i class="fas fa-exclamation-circle"></i> ${message}</span>`;

     if (statsGrid) {
         statsGrid.querySelectorAll('.stat-change').forEach(el => { el.innerHTML = errorHtml; });
         statsGrid.querySelectorAll('.stat-value').forEach(el => { el.textContent = '-'; }); // Indicate error in value too
     }
     if (activityList) {
         activityList.innerHTML = `<div class="error-message" style="padding: 1rem; text-align: center; color: #dc3545;">${message}</div>`;
     }
}

// --- Helper Functions (getTimeAgo, numberWithCommas) remain the same ---
function getTimeAgo(date) {
    if (!(date instanceof Date) || isNaN(date)) {
        return 'Invalid date';
    }
    const seconds = Math.floor((new Date() - date) / 1000);
    let interval = Math.floor(seconds / 31536000);
    if (interval >= 1) return interval + " year" + (interval === 1 ? "" : "s") + " ago";
    interval = Math.floor(seconds / 2592000);
    if (interval >= 1) return interval + " month" + (interval === 1 ? "" : "s") + " ago";
    interval = Math.floor(seconds / 86400);
    if (interval >= 1) return interval + " day" + (interval === 1 ? "" : "s") + " ago";
    interval = Math.floor(seconds / 3600);
    if (interval >= 1) return interval + " hour" + (interval === 1 ? "" : "s") + " ago";
    interval = Math.floor(seconds / 60);
    if (interval >= 1) return interval + " minute" + (interval === 1 ? "" : "s") + " ago";
    return "just now";
}

function numberWithCommas(x) {
    if (x === null || x === undefined) return '0';
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}