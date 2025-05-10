function initAdminProfile() {
    console.log("Initializing Admin Profile...");
    // Load profile data when the page loads
    loadAdminProfile();
    
    // Set up form submission handlers
    setupProfileForms();
}

function loadAdminProfile() {
    fetch('/iteam-university-website/backend/api/admin/admin_profile.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateProfileData(data.profile);
            } else {
                console.error('Failed to load profile:', data.message);
                showNotification('error', 'Failed to load profile data. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'An error occurred while loading profile data.');
        });
}

function populateProfileData(profile) {
    // Populate profile information form
    if (document.getElementById('email')) {
        document.getElementById('email').value = profile.email || '';
    }
    
    if (document.getElementById('first-name')) {
        document.getElementById('first-name').value = profile.first_name || '';
    }
    
    if (document.getElementById('last-name')) {
        document.getElementById('last-name').value = profile.last_name || '';
    }
    
    if (document.getElementById('phone')) {
        document.getElementById('phone').value = profile.phone || '';
    }
    
    if (document.getElementById('username')) {
        document.getElementById('username').value = profile.username || '';
    }
    
    // Update user display in header and sidebar
    updateUserDisplay(profile);
}

function updateUserDisplay(profile) {
    const fullName = `${profile.first_name} ${profile.last_name}`;
    const initials = getInitials(fullName);
    
    // Update sidebar user info
    const sidebarInitials = document.querySelector('.sidebar-user .profile-initials');
    const sidebarName = document.querySelector('.sidebar-user .user-name');
    
    if (sidebarInitials) sidebarInitials.textContent = initials;
    if (sidebarName) sidebarName.textContent = fullName;
    
    // Update header dropdown user info
    const headerInitials = document.querySelector('.dashboard-nav-right .profile-initials');
    const headerName = document.querySelector('.dashboard-nav-right .user-name');
    
    if (headerInitials) headerInitials.textContent = initials;
    if (headerName) headerName.textContent = fullName;
}

function getInitials(name) {
    return name
        .split(' ')
        .map(part => part.charAt(0))
        .join('')
        .toUpperCase()
        .substring(0, 2);
}

function setupProfileForms() {
    // Profile form submission
    const profileForm = document.getElementById('admin-profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const profileData = {
                email: document.getElementById('email').value,
                first_name: document.getElementById('first-name').value,
                last_name: document.getElementById('last-name').value,
                phone: document.getElementById('phone').value
            };
            
            updateProfile(profileData);
        });
    }
    
    // Password form submission
    const passwordForm = document.getElementById('change-password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (newPassword !== confirmPassword) {
                showNotification('error', 'New passwords do not match.');
                return;
            }
            
            changePassword(currentPassword, newPassword);
        });
    }
    
    // Notification preferences form
    const preferencesForm = document.getElementById('notification-preferences-form');
    if (preferencesForm) {
        preferencesForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const preferences = {
                email_new_users: document.getElementById('email-new-users').checked,
                email_new_events: document.getElementById('email-new-events').checked,
                email_reports: document.getElementById('email-reports').checked,
                browser_notifications: document.getElementById('browser-notifications').checked
            };
            
            savePreferences(preferences);
        });
    }
    
    // Password toggle functionality
    const toggleButtons = document.querySelectorAll('.password-toggle');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            // Toggle eye icon
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });
}

function updateProfile(profileData) {
    fetch('/iteam-university-website/backend/api/admin/admin_profile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'update_profile',
            data: profileData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'Profile updated successfully!');
            
            // Update display with new data
            populateProfileData({
                ...profileData,
                username: document.getElementById('username').value
            });
        } else {
            showNotification('error', data.message || 'Failed to update profile.');
        }
    })
    .catch(error => {
        console.error('Error updating profile:', error);
        showNotification('error', 'An error occurred while updating your profile.');
    });
}

function changePassword(currentPassword, newPassword) {
    fetch('/iteam-university-website/backend/api/admin/admin_profile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'change_password',
            current_password: currentPassword,
            new_password: newPassword
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'Password changed successfully!');
            document.getElementById('change-password-form').reset();
        } else {
            showNotification('error', data.message || 'Failed to change password.');
        }
    })
    .catch(error => {
        console.error('Error changing password:', error);
        showNotification('error', 'An error occurred while changing your password.');
    });
}

function savePreferences(preferences) {
    fetch('/iteam-university-website/backend/api/admin/admin_profile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'update_preferences',
            preferences: preferences
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'Notification preferences saved!');
        } else {
            showNotification('error', data.message || 'Failed to save preferences.');
        }
    })
    .catch(error => {
        console.error('Error saving preferences:', error);
        showNotification('error', 'An error occurred while saving your preferences.');
    });
}

function showNotification(type, message) {
    // Create notification element if not exists
    let notification = document.querySelector('.notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.className = 'notification';
        document.body.appendChild(notification);
    }
    
    // Add appropriate class
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.display = 'block';
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        notification.style.display = 'none';
    }, 3000);
}