document.addEventListener("DOMContentLoaded", function () {
  // Dark mode toggle
  const darkModeToggle = document.getElementById("darkModeToggle");
  const htmlElement = document.documentElement;
  const savedTheme = localStorage.getItem("theme");
  const systemPrefersDark = window.matchMedia(
    "(prefers-color-scheme: dark)"
  ).matches;
  if (savedTheme === "dark" || (!savedTheme && systemPrefersDark)) {
    htmlElement.classList.add("dark");
    if (darkModeToggle) darkModeToggle.checked = true; 
  } else {
    htmlElement.classList.remove("dark");
    if (darkModeToggle) darkModeToggle.checked = false; 
  }

  if (darkModeToggle) { 
    darkModeToggle.addEventListener("change", function () {
      if (this.checked) {
        htmlElement.classList.add("dark");
        localStorage.setItem("theme", "dark");
      } else {
        htmlElement.classList.remove("dark");
        localStorage.setItem("theme", "light");
      }
    });
  }
}); // END OF FIRST DOMContentLoaded

document.addEventListener("DOMContentLoaded", function () {
  // Notification panel functionality
  const notificationButton = document.getElementById("notificationButton");
  const notificationPanel = document.getElementById("notificationPanel");
  const notificationBadge = document.getElementById("notificationBadge");
  const notificationList = document.getElementById("notificationList"); // Target for adding notifications
  const markAllReadBtn = document.getElementById("markAllReadBtn");
  let unreadCount = 0; 

  // Helper function to format time ago
  function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.round((now - date) / 1000);
    const minutes = Math.round(seconds / 60);
    const hours = Math.round(minutes / 60);
    const days = Math.round(hours / 24);

    if (seconds < 60) return `${seconds} seconds ago`;
    if (minutes < 60) return `${minutes} minutes ago`;
    if (hours < 24) return `${hours} hours ago`;
    if (days === 1) return `1 day ago`;
    return `${days} days ago`;
  }

  async function fetchAndDisplayNotifications() {
    if (!notificationList) return;

    try {
      const response = await fetch('/iteam-university-website/admin/api/notifications.php');
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP error! Status: ${response.status}`);
      }
      const data = await response.json();

      notificationList.innerHTML = ''; // Clear existing static or old notifications

      if (data.success && data.notifications && data.notifications.length > 0) {
        data.notifications.forEach(notif => {
          const itemDiv = document.createElement('div');
          itemDiv.className = `notification-item p-4 border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/70 transition-colors duration-150 ${notif.is_read ? 'opacity-70 dark:opacity-60' : ''}`;
          
          const defaultAvatar = '/iteam-university-website/admin/assets/images/default-avatar.png';
          const profilePicSrc = notif.actor_profile_picture || defaultAvatar;

          itemDiv.innerHTML = `
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 rounded-full overflow-hidden border border-slate-200 dark:border-slate-600">
                  <img src="${profilePicSrc}" alt="${notif.actor_name || 'Student'}" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='${defaultAvatar}';">
                </div>
              </div>
              <div class="ml-3 flex-1">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <i class="${getNotificationIcon(notif.type)} ${getNotificationColor(notif.type)} mr-2"></i>
                    <span class="font-medium text-slate-700 dark:text-slate-200">${notif.actor_name || 'System'}</span>
                    ${!notif.is_read ? '<span class="ml-2 w-2 h-2 bg-primary rounded-full"></span>' : ''}
                  </div>
                  <div class="flex items-center space-x-2">
                    <span class="text-xs text-slate-500 dark:text-slate-400">${timeAgo(notif.date)}</span>
                    ${!notif.is_read ? `
                      <button onclick="markAsRead('${notif.id}')" 
                              class="text-slate-400 hover:text-primary dark:hover:text-sky-400 p-1 rounded-md transition-colors duration-150">
                        <i class="ri-check-line"></i>
                      </button>
                    ` : ''}
                  </div>
                </div>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">${notif.message}</p>
                ${notif.event_title ? `
                  <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <i class="ri-calendar-event-line mr-1"></i>
                    ${notif.event_title}
                  </div>
                ` : ''}
              </div>
            </div>
          `;
          notificationList.appendChild(itemDiv);
        });
        unreadCount = data.unread_count || 0;
      } else if (data.success && data.notifications.length === 0) {
        notificationList.innerHTML = '<p class="p-4 text-center text-sm text-slate-500 dark:text-slate-400">No new notifications.</p>';
        unreadCount = 0;
      } else {
        throw new Error(data.message || 'Failed to load notifications.');
      }
    } catch (error) {
      console.error('Error fetching notifications:', error);
      if (notificationList) {
        notificationList.innerHTML = `<p class="p-4 text-center text-sm text-red-500 dark:text-red-400">Could not load notifications. ${error.message}</p>`;
      }
      unreadCount = 0;
    }
    updateNotificationCount();
  }

  function getNotificationIcon(type) {
    const icons = {
      confirmation: 'ri-checkbox-circle-line',
      reminder: 'ri-time-line',
      cancellation: 'ri-close-circle-line',
      event_update: 'ri-calendar-event-line',
      new_event_nearby: 'ri-map-pin-line',
      registration_open: 'ri-user-add-line',
      feedback_request: 'ri-feedback-line'
    };
    return icons[type] || 'ri-notification-line';
  }

  function getNotificationColor(type) {
    const colors = {
      confirmation: 'text-green-500',
      reminder: 'text-blue-500',
      cancellation: 'text-red-500',
      event_update: 'text-purple-500',
      new_event_nearby: 'text-yellow-500',
      registration_open: 'text-indigo-500',
      feedback_request: 'text-pink-500'
    };
    return colors[type] || 'text-gray-500';
  }

  // Mark notification as read
  window.markAsRead = async function(notificationId) {
    try {
      const response = await fetch('/iteam-university-website/admin/api/mark_notification_read.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
      });

      if (!response.ok) throw new Error(`HTTP error ${response.status}`);
      const result = await response.json();

      if (result.success) {
        showToast('Notification marked as read');
        fetchAndDisplayNotifications(); // Refresh the list
      } else {
        throw new Error(result.message || 'Failed to mark notification as read');
      }
    } catch (error) {
      console.error('Error marking notification as read:', error);
      showToast(error.message || 'Failed to mark notification as read', 'error');
    }
  };

  // Mark all notifications as read
  async function markAllAsRead() {
    try {
      const response = await fetch('/iteam-university-website/admin/api/mark_all_notifications_read.php', {
        method: 'POST'
      });

      if (!response.ok) throw new Error(`HTTP error ${response.status}`);
      const result = await response.json();

      if (result.success) {
        showToast(`Marked ${result.updated_count || 'all'} notifications as read`);
        fetchAndDisplayNotifications(); // Refresh the list
      } else {
        throw new Error(result.message || 'Failed to mark all notifications as read');
      }
    } catch (error) {
      console.error('Error marking all notifications as read:', error);
      showToast(error.message || 'Failed to mark all notifications as read', 'error');
    }
  }

  function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-4 py-2 rounded-lg text-white ${
      type === 'success' ? 'bg-green-500' : 'bg-red-500'
    } shadow-lg z-50 transition-opacity duration-300`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  function updateNotificationCount() {
    if (!notificationBadge) return;
    if (unreadCount > 0) {
      notificationBadge.textContent = unreadCount;
      notificationBadge.classList.remove("hidden");
    } else {
      notificationBadge.classList.add("hidden");
    }
  }
  fetchAndDisplayNotifications(); 

  if (notificationButton && notificationPanel) {
    notificationButton.addEventListener("click", function (e) {
      e.stopPropagation();
      const isHidden = notificationPanel.classList.toggle("hidden");
      if (!isHidden) { 
        fetchAndDisplayNotifications();
      }
    });
  }

  document.addEventListener("click", function (e) {
    if (notificationPanel && notificationButton && 
        !notificationPanel.classList.contains("hidden") && 
        !notificationPanel.contains(e.target) &&
        e.target !== notificationButton && !notificationButton.contains(e.target)
    ) {
      notificationPanel.classList.add("hidden");
    }
  });

  if (markAllReadBtn && notificationPanel) {
    markAllReadBtn.addEventListener("click", function () {
      const itemsToMark = notificationPanel.querySelectorAll(".notification-item");
      itemsToMark.forEach((item) => {
        const dot = item.querySelector(".notification-dot-unread"); 
        if (dot) {
          dot.classList.remove("bg-primary", "notification-dot-unread");
          dot.classList.add("bg-slate-300", "dark:bg-slate-600"); 
          item.classList.add("opacity-70", "dark:opacity-60");
          const individualMarkReadBtn = item.querySelector('.mark-read-btn');
          if(individualMarkReadBtn) {
            individualMarkReadBtn.innerHTML = '<i class="ri-check-double-line"></i>';
            individualMarkReadBtn.classList.remove('hover:text-primary', 'dark:hover:text-sky-400');
            individualMarkReadBtn.classList.add('text-slate-300', 'dark:text-slate-500', 'cursor-default');
            individualMarkReadBtn.disabled = true;
          }
        }
      });
      unreadCount = 0;
      updateNotificationCount();
    });
  }

  if (notificationPanel) {
    notificationPanel.addEventListener('click', function(e) {
        const markReadButton = e.target.closest('.mark-read-btn:not(:disabled)'); 
        if (markReadButton) {
            const notificationItem = markReadButton.closest('.notification-item');
            if (notificationItem) {
                const dot = notificationItem.querySelector(".notification-dot-unread"); 
                if (dot) {
                    dot.classList.remove("bg-primary", "notification-dot-unread");
                    dot.classList.add("bg-slate-300", "dark:bg-slate-600"); 
                    notificationItem.classList.add("opacity-70", "dark:opacity-60");
                    markReadButton.innerHTML = '<i class="ri-check-double-line"></i>';
                    markReadButton.classList.remove('hover:text-primary', 'dark:hover:text-sky-400');
                    markReadButton.classList.add('text-slate-300', 'dark:text-slate-500', 'cursor-default');
                    markReadButton.disabled = true;
                    
                    if (unreadCount > 0) unreadCount--;
                    updateNotificationCount();
                }
            }
        }
    });
  }

  // Mobile menu toggle
  const menuToggle = document.getElementById("menuToggle");
  const mobileSidebar = document.getElementById("mobileSidebar");
  const closeMobileMenu = document.getElementById("closeMobileMenu");
  const mainContent = document.getElementById("content"); 
  const sidebar = document.querySelector(".sidebar"); 

  if (menuToggle && mobileSidebar && closeMobileMenu) {
    menuToggle.addEventListener("click", function () {
      mobileSidebar.style.display = "block"; 
    });

    closeMobileMenu.addEventListener("click", function () {
      mobileSidebar.style.display = "none";
    });

    mobileSidebar.addEventListener("click", function (event) {
      if (event.target === mobileSidebar) {
        mobileSidebar.style.display = "none";
      }
    });
  }

  async function fetchAndDisplayAdminProfile() {
    const adminNameElement = document.getElementById("headerAdminName");
    const adminProfilePicElement = document.getElementById("headerAdminProfilePic");
    const defaultProfilePic = "assets/images/default-avatar.png"; 

    if (!adminNameElement || !adminProfilePicElement) {
      console.warn("Admin profile elements not found in the header.");
      return;
    }

    try {
      const response = await fetch("/iteam-university-website/admin/api/admin_profile.php");
      const data = await response.json();

      if (data.success && data.profile) {
        adminNameElement.textContent = data.profile.full_name || "Admin";
        if (data.profile.profile_picture) {
          let profilePicPath = data.profile.profile_picture;
          if (!profilePicPath.startsWith('http') && !profilePicPath.startsWith('/')) {
            profilePicPath = `/iteam-university-website/admin/${profilePicPath}`;
          }
          adminProfilePicElement.src = profilePicPath;
        } else {
          adminProfilePicElement.src = defaultProfilePic;
        }
        adminProfilePicElement.onerror = function () {
          this.src = defaultProfilePic;
          console.warn("Admin profile picture failed to load, using default.");
        };
      } else {
        adminNameElement.textContent = "Admin"; 
        adminProfilePicElement.src = defaultProfilePic;
        console.error("Failed to load admin profile:", data.message || "Unknown error");
      }
    } catch (error) {
      console.error("Error fetching admin profile:", error);
      adminNameElement.textContent = "Admin"; 
      if (adminProfilePicElement) {
        adminProfilePicElement.src = defaultProfilePic;
      }
    }
  }

  fetchAndDisplayAdminProfile();

  const logoutLink = document.getElementById("logoutLink");
  const mobileLogoutLink = document.getElementById("mobileLogoutLink");
  
  const logoutConfirmModal = document.getElementById("logoutConfirmModal");
  const confirmLogoutBtn = document.getElementById("confirmLogoutBtn");
  const cancelLogoutBtn = document.getElementById("cancelLogoutBtn"); 

  function showLogoutConfirmModal() {
    if (logoutConfirmModal) {
      logoutConfirmModal.classList.remove("hidden");
    }
  }

  function hideLogoutConfirmModal() {
    if (logoutConfirmModal) {
      logoutConfirmModal.classList.add("hidden");
    }
  }

  const performLogout = async () => {
    if (confirmLogoutBtn) {
        confirmLogoutBtn.disabled = true;
        confirmLogoutBtn.innerHTML = '<i class="ri-loader-4-line ri-spin mr-2"></i> Logging out...';
    }
    try {
      const response = await fetch("/iteam-university-website/admin/api/logout.php", { method: "POST" });
      const data = await response.json();
      if (data.success) {
        localStorage.removeItem("dashboardAuth");
        window.location.href = data.redirect_url || "/iteam-university-website/admin/auth/admin-login.html";
      } else {
        alert("Logout failed: " + (data.message || "Unknown error"));
        if (confirmLogoutBtn) {
            confirmLogoutBtn.disabled = false;
            confirmLogoutBtn.textContent = "Yes, I'm sure";
        }
      }
    } catch (error) {
      console.error("Logout error:", error);
      alert("Logout failed: An error occurred.");
      if (confirmLogoutBtn) {
        confirmLogoutBtn.disabled = false;
        confirmLogoutBtn.textContent = "Yes, I'm sure";
      }
    }
  };

  function handleLogoutRequest(e) {
    if (e) e.preventDefault();
    showLogoutConfirmModal();
  }

  if (logoutLink) {
    logoutLink.addEventListener("click", handleLogoutRequest);
  }
  if (mobileLogoutLink) {
    mobileLogoutLink.addEventListener("click", handleLogoutRequest);
  }

  if (confirmLogoutBtn) {
    confirmLogoutBtn.addEventListener("click", performLogout);
  }
  if (cancelLogoutBtn) { 
    cancelLogoutBtn.addEventListener("click", hideLogoutConfirmModal);
  }
  if (logoutConfirmModal) {
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !logoutConfirmModal.classList.contains('hidden')) {
            hideLogoutConfirmModal();
        }
    });
    logoutConfirmModal.addEventListener('click', function(event) {
        if (event.target === logoutConfirmModal) { 
            hideLogoutConfirmModal();
        }
    });
  }

}); // END OF SECOND DOMContentLoaded

