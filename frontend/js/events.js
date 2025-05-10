document.addEventListener('DOMContentLoaded', function() {
    // Initialize events page
    initEventsPage();
});

function initEventsPage() {
    // Get filter elements
    const searchInput = document.getElementById('event-search');
    const filterButtons = document.querySelectorAll('.filter-buttons button');
    const statusFilter = document.getElementById('status-filter');
    const dateFilter = document.getElementById('date-filter');
    const organizerFilter = document.getElementById('organizer-filter');
    
    // Set up event listeners
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => {
            loadEvents(1);
        }, 500));
    }
    
    if (filterButtons) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                loadEvents(1);
            });
        });
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', () => loadEvents(1));
    }
    
    if (dateFilter) {
        dateFilter.addEventListener('change', () => loadEvents(1));
    }
    
    if (organizerFilter) {
        organizerFilter.addEventListener('change', () => loadEvents(1));
    }
    
    // Select all checkbox
    const selectAll = document.getElementById('select-all-events');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionsState();
        });
    }
    
    // Initial load
    loadEvents(1);
    loadRecentRegistrations();
    
    // Set up bulk actions
    const bulkActionSelect = document.getElementById('bulk-action-events');
    const bulkActionButton = document.querySelector('.bulk-actions .btn');
    
    if (bulkActionButton) {
        bulkActionButton.addEventListener('click', () => {
            const action = bulkActionSelect ? bulkActionSelect.value : '';
            if (!action) return;
            
            const selectedIds = getSelectedEventIds();
            if (selectedIds.length === 0) {
                showNotification('error', 'No events selected.');
                return;
            }
            
            // Confirm the action
            if (confirm(`Are you sure you want to ${action} ${selectedIds.length} selected event(s)?`)) {
                performBulkAction(action, selectedIds);
            }
        });
    }
}

function loadEvents(page) {
    const tbody = document.querySelector('.admin-table tbody');
    if (!tbody) return;
    
    // Show loading state
    tbody.innerHTML = '<tr><td colspan="10" class="text-center"><div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading events...</div></td></tr>';
    
    // Get current filter values
    const activeFilter = document.querySelector('.filter-buttons button.active');
    const filter = activeFilter ? activeFilter.dataset.filter : 'all';
    const status = document.getElementById('status-filter')?.value || 'all';
    const date = document.getElementById('date-filter')?.value || 'all';
    const organizer = document.getElementById('organizer-filter')?.value || 'all';
    const search = document.getElementById('event-search')?.value || '';
    
    // Build query string
    const params = new URLSearchParams({
        filter,
        status,
        date,
        organizer,
        page,
        limit: 10
    });
    
    if (search) {
        params.append('search', search);
    }
    
    // Fetch events data
    fetch(`/iteam-university-website/backend/api/events_data.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderEvents(data.events);
                renderPagination(data.pagination);
                updateEventStats(data.stats);
            } else {
                tbody.innerHTML = `<tr><td colspan="10" class="text-center">${data.message || 'Error loading events'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading events:', error);
            tbody.innerHTML = '<tr><td colspan="10" class="text-center">Error loading events. Please try again.</td></tr>';
        });
}

function loadRecentRegistrations() {
    const activityList = document.querySelector('.activity-list');
    if (!activityList) return;
    
    fetch('/iteam-university-website/backend/api/events_registrations.php?limit=4&action=recent')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderRecentRegistrations(data.registrations);
            } else {
                activityList.innerHTML = '<div class="empty-state">No recent registrations found</div>';
            }
        })
        .catch(error => {
            console.error('Error loading recent registrations:', error);
            activityList.innerHTML = '<div class="error-message">Failed to load recent registrations</div>';
        });
}

function renderEvents(events) {
    const tbody = document.querySelector('.admin-table tbody');
    if (!tbody) return;
    
    // Clear existing rows
    tbody.innerHTML = '';
    
    if (events.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center">No events found.</td></tr>';
        return;
    }
    
    // Add event rows
    events.forEach(event => {
        const row = document.createElement('tr');
        
        // Determine image path based on event type
        let imagePath = 'assets/images/events/';
        switch (event.type) {
            case 'workshop':
                imagePath += 'event-3.jpeg';
                break;
            case 'conference':
                imagePath += 'event-1.jpeg';
                break;
            case 'fair':
                imagePath += 'event-2.jpeg';
                break;
            case 'webinar':
                imagePath += 'event-5.jpeg';
                break;
            case 'social':
                imagePath += 'event-4.jpeg';
                break;
            default:
                imagePath += 'event-1.jpeg';
        }
        
        // Create event type badge
        const typeBadge = `<span class="badge event-${event.type}">${capitalizeFirstLetter(event.type)}</span>`;
        
        // Create status badge
        const statusBadge = `<span class="badge status-${event.status}">${capitalizeFirstLetter(event.status)}</span>`;
        
        row.innerHTML = `
            <td><input type="checkbox" class="row-checkbox" data-id="${event.id}"></td>
            <td>${event.id}</td>
            <td>
                <div class="event-info-cell">
                    <div class="event-img">
                        <img src="${imagePath}" alt="${event.title}" />
                    </div>
                    <span>${event.title}</span>
                </div>
            </td>
            <td>${event.formatted_date}</td>
            <td>${event.location}</td>
            <td>${event.organizer}</td>
            <td>${typeBadge}</td>
            <td>${event.registered}/${event.capacity}</td>
            <td>${statusBadge}</td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn" title="View" onclick="viewEvent('${event.id}')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn" title="Edit" onclick="editEvent('${event.id}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn" title="Manage Registrations" onclick="manageEventRegistrations('${event.id}')">
                        <i class="fas fa-user-check"></i>
                    </button>
                    <button class="action-btn text-danger" title="Delete" onclick="deleteEvent('${event.id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Add event listeners to checkboxes
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionsState);
    });
}

function renderPagination(pagination) {
    const paginationContainer = document.querySelector('.pagination');
    if (!paginationContainer) return;
    
    // Clear existing pagination
    paginationContainer.innerHTML = '';
    
    // Previous button
    const prevButton = document.createElement('button');
    prevButton.className = 'pagination-btn';
    prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevButton.disabled = pagination.current_page === 1;
    prevButton.addEventListener('click', () => loadEvents(pagination.current_page - 1));
    paginationContainer.appendChild(prevButton);
    
    // Page buttons
    let startPage = Math.max(1, pagination.current_page - 2);
    let endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    // First page if not already showing
    if (startPage > 1) {
        const firstPageBtn = document.createElement('button');
        firstPageBtn.className = 'pagination-btn';
        firstPageBtn.textContent = '1';
        firstPageBtn.addEventListener('click', () => loadEvents(1));
        paginationContainer.appendChild(firstPageBtn);
        
        // Show ellipsis if needed
        if (startPage > 2) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'pagination-ellipsis';
            ellipsis.textContent = '...';
            paginationContainer.appendChild(ellipsis);
        }
    }
    
    // Page buttons
    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = 'pagination-btn';
        if (i === pagination.current_page) {
            pageBtn.classList.add('active');
        }
        pageBtn.textContent = i;
        pageBtn.addEventListener('click', () => loadEvents(i));
        paginationContainer.appendChild(pageBtn);
    }
    
    // Last page if not already showing
    if (endPage < pagination.total_pages) {
        // Show ellipsis if needed
        if (endPage < pagination.total_pages - 1) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'pagination-ellipsis';
            ellipsis.textContent = '...';
            paginationContainer.appendChild(ellipsis);
        }
        
        const lastPageBtn = document.createElement('button');
        lastPageBtn.className = 'pagination-btn';
        lastPageBtn.textContent = pagination.total_pages;
        lastPageBtn.addEventListener('click', () => loadEvents(pagination.total_pages));
        paginationContainer.appendChild(lastPageBtn);
    }
    
    // Next button
    const nextButton = document.createElement('button');
    nextButton.className = 'pagination-btn';
    nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextButton.disabled = pagination.current_page === pagination.total_pages;
    nextButton.addEventListener('click', () => loadEvents(pagination.current_page + 1));
    paginationContainer.appendChild(nextButton);
}

function renderRecentRegistrations(registrations) {
    const activityList = document.querySelector('.activity-list');
    if (!activityList || !registrations || registrations.length === 0) {
        activityList.innerHTML = '<div class="empty-state">No recent registrations found</div>';
        return;
    }
    
    // Clear existing activities
    activityList.innerHTML = '';
    
    // Add new activities
    registrations.forEach(reg => {
        const activityItem = document.createElement('div');
        activityItem.className = 'activity-item';
        
        // Determine icon based on status
        let iconClass = 'fa-user-plus';
        if (reg.status === 'cancelled') {
            iconClass = 'fa-user-times';
        }
        
        // Calculate time ago
        const timeAgo = getTimeAgo(new Date(reg.registration_date));
        
        activityItem.innerHTML = `
            <div class="activity-icon">
                <i class="fas ${iconClass}"></i>
            </div>
            <div class="activity-details">
                <p><strong>${reg.user_name}</strong> ${reg.status === 'cancelled' ? 'cancelled registration for' : 'registered for'} <strong>${reg.event_title}</strong></p>
                <span class="activity-time">${timeAgo}</span>
            </div>
            <div class="activity-status">
                <span class="badge status-${reg.status}">${capitalizeFirstLetter(reg.status)}</span>
            </div>
        `;
        
        activityList.appendChild(activityItem);
    });
}

function updateEventStats(stats) {
    // Update total events
    const totalEvents = document.querySelector('.stat-card:nth-child(1) .stat-value');
    if (totalEvents) {
        totalEvents.textContent = stats.total || '0';
    }
    
    // Update registrations
    const totalRegs = document.querySelector('.stat-card:nth-child(2) .stat-value');
    if (totalRegs) {
        totalRegs.textContent = numberWithCommas(stats.registrations || '0');
    }
    
    // Update upcoming events
    const upcomingEvents = document.querySelector('.stat-card:nth-child(3) .stat-value');
    if (upcomingEvents) {
        upcomingEvents.textContent = stats.upcoming || '0';
    }
    
    // Update cancelled events
    const cancelledEvents = document.querySelector('.stat-card:nth-child(4) .stat-value');
    if (cancelledEvents) {
        cancelledEvents.textContent = stats.cancelled || '0';
    }
}

function updateBulkActionsState() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    const selectAll = document.getElementById('select-all-events');
    const bulkActions = document.querySelector('.bulk-actions');
    
    // Count selected checkboxes
    const selectedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
    
    // Update select all checkbox
    if (selectAll) {
        selectAll.checked = selectedCount === checkboxes.length && checkboxes.length > 0;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
    }
    
    // Update bulk actions
    if (bulkActions) {
        const bulkActionBtn = bulkActions.querySelector('.btn');
        if (bulkActionBtn) {
            bulkActionBtn.disabled = selectedCount === 0;
        }
    }
}

function getSelectedEventIds() {
    const selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
    return Array.from(selectedCheckboxes).map(checkbox => checkbox.dataset.id);
}

function viewEvent(id) {
    loadContent(`dashboards/admin/pages/event-details.html?id=${id}`, 'event-details');
}

function editEvent(id) {
    loadContent(`dashboards/admin/pages/edit-event.html?id=${id}`, 'edit-event');
}

function manageEventRegistrations(id) {
    loadContent(`dashboards/admin/pages/event-registrations.html?event_id=${id}`, 'event-registrations');
}

function deleteEvent(id) {
    if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
        fetch('/iteam-university-website/backend/api/events_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete_event',
                event_id: id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', 'Event deleted successfully!');
                loadEvents(1); // Reload the current page
            } else {
                showNotification('error', data.message || 'Failed to delete event.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'An error occurred while deleting the event.');
        });
    }
}

function performBulkAction(action, selectedIds) {
    fetch('/iteam-university-website/backend/api/events_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'bulk_' + action,
            event_ids: selectedIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', `Successfully ${action}ed ${selectedIds.length} events.`);
            loadEvents(1); // Reload the current page
        } else {
            showNotification('error', data.message || `Failed to ${action} events.`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', `An error occurred while performing ${action} action.`);
    });
}

function capitalizeFirstLetter(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1);
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

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
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