document.addEventListener('DOMContentLoaded', function() {
    // Initialize users page
    initUsersPage();
});

function initUsersPage() {
    // Get filter elements
    const searchInput = document.getElementById('user-search');
    const clearSearch = document.getElementById('clear-search');
    const filterButtons = document.querySelectorAll('.filter-buttons button');
    const statusFilter = document.getElementById('status-filter');
    const dateFilter = document.getElementById('date-filter');
    const sortBy = document.getElementById('sort-by');
    
    // Set up event listeners
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => {
            loadUsers(1);
        }, 500));
    }
    
    if (clearSearch) {
        clearSearch.addEventListener('click', () => {
            if (searchInput) {
                searchInput.value = '';
                loadUsers(1);
            }
        });
    }
    
    if (filterButtons) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                loadUsers(1);
            });
        });
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', () => loadUsers(1));
    }
    
    if (dateFilter) {
        dateFilter.addEventListener('change', () => loadUsers(1));
    }
    
    if (sortBy) {
        sortBy.addEventListener('change', () => loadUsers(1));
    }
    
    // Select all checkbox
    const selectAll = document.getElementById('select-all');
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
    loadUsers(1);
    
    // Set up bulk actions
    const bulkActionSelect = document.getElementById('bulk-action');
    const bulkActionButton = document.querySelector('.bulk-actions .btn');
    
    if (bulkActionButton) {
        bulkActionButton.addEventListener('click', () => {
            const action = bulkActionSelect ? bulkActionSelect.value : '';
            if (!action) return;
            
            const selectedIds = getSelectedUserIds();
            if (selectedIds.length === 0) {
                showNotification('error', 'No users selected.');
                return;
            }
            
            // Confirm the action
            if (confirm(`Are you sure you want to ${action} ${selectedIds.length} selected user(s)?`)) {
                performBulkAction(action, selectedIds);
            }
        });
    }
}

function loadUsers(page) {
    const tbody = document.querySelector('.admin-table tbody');
    if (!tbody) return;
    
    // Show loading state
    tbody.innerHTML = '<tr><td colspan="8" class="text-center"><div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading users...</div></td></tr>';
    
    // Get current filter values
    const activeFilter = document.querySelector('.filter-buttons button.active');
    const filter = activeFilter ? activeFilter.dataset.filter : 'all';
    const status = document.getElementById('status-filter')?.value || 'all';
    const date = document.getElementById('date-filter')?.value || 'all';
    const sort = document.getElementById('sort-by')?.value || 'newest';
    const search = document.getElementById('user-search')?.value || '';
    
    // Build query string
    const params = new URLSearchParams({
        filter,
        status,
        date,
        sort,
        page,
        limit: 10
    });
    
    if (search) {
        params.append('search', search);
    }
    
    // Fetch users data
    fetch(`/iteam-university-website/backend/api/users_data.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderUsers(data.users);
                renderPagination(data.pagination);
            } else {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center">${data.message || 'Error loading users'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">Error loading users. Please try again.</td></tr>';
        });
}

function renderUsers(users) {
    const tbody = document.querySelector('.admin-table tbody');
    if (!tbody) return;
    
    // Clear existing rows
    tbody.innerHTML = '';
    
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No users found.</td></tr>';
        return;
    }
    
    // Add user rows
    users.forEach(user => {
        const row = document.createElement('tr');
        
        // Get initials for avatar
        let initials = '';
        const nameParts = user.name.split(' ');
        if (nameParts.length >= 2) {
            initials = nameParts[0].charAt(0) + nameParts[1].charAt(0);
        } else if (nameParts.length === 1) {
            initials = nameParts[0].substring(0, 2);
        }
        
        // Format date
        const registrationDate = new Date(user.registration_date);
        const formattedDate = registrationDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
        
        // Create user type badge
        let userBadge = '';
        switch(user.type) {
            case 'user':
                userBadge = '<span class="badge user-badge">Student</span>';
                break;
            case 'organization':
                userBadge = '<span class="badge org-badge">Organization</span>';
                break;
            case 'admin':
                userBadge = '<span class="badge admin-badge">Administrator</span>';
                break;
            default:
                userBadge = '<span class="badge">Unknown</span>';
        }
        
        // Create status badge
        const statusBadge = `<span class="badge status-${user.status}">${capitalizeFirstLetter(user.status)}</span>`;
        
        row.innerHTML = `
            <td><input type="checkbox" class="row-checkbox" data-id="${user.id}" data-type="${user.type}"></td>
            <td>${user.id}</td>
            <td>
                <div class="user-info-cell">
                    <div class="user-avatar-small">${initials}</div>
                    <span>${user.name}</span>
                </div>
            </td>
            <td>${user.email}</td>
            <td>${userBadge}</td>
            <td>${statusBadge}</td>
            <td>${formattedDate}</td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn" title="View" onclick="viewUser('${user.id}', '${user.type}')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn" title="Edit" onclick="editUser('${user.id}', '${user.type}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn text-danger" title="Delete" onclick="deleteUser('${user.id}', '${user.type}')">
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
    prevButton.addEventListener('click', () => loadUsers(pagination.current_page - 1));
    paginationContainer.appendChild(prevButton);
    
    // Page buttons
    let startPage = Math.max(1, pagination.current_page - 2);
    let endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    // Show at least 5 pages if available
    if (endPage - startPage < 4) {
        if (startPage === 1) {
            endPage = Math.min(5, pagination.total_pages);
        } else {
            startPage = Math.max(1, pagination.total_pages - 4);
        }
    }
    
    // First page if not already showing
    if (startPage > 1) {
        const firstPageBtn = document.createElement('button');
        firstPageBtn.className = 'pagination-btn';
        firstPageBtn.textContent = '1';
        firstPageBtn.addEventListener('click', () => loadUsers(1));
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
        pageBtn.addEventListener('click', () => loadUsers(i));
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
        lastPageBtn.addEventListener('click', () => loadUsers(pagination.total_pages));
        paginationContainer.appendChild(lastPageBtn);
    }
    
    // Next button
    const nextButton = document.createElement('button');
    nextButton.className = 'pagination-btn';
    nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextButton.disabled = pagination.current_page === pagination.total_pages;
    nextButton.addEventListener('click', () => loadUsers(pagination.current_page + 1));
    paginationContainer.appendChild(nextButton);
}

function updateBulkActionsState() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    const selectAll = document.getElementById('select-all');
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

function getSelectedUserIds() {
    const selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
    return Array.from(selectedCheckboxes).map(checkbox => checkbox.dataset.id);
}

function viewUser(id, type) {
    console.log(`Viewing ${type} with ID: ${id}`);
    // In a real application, you would redirect to a user detail page
    // or open a modal with user details
    alert(`View ${type} with ID ${id}`);
}

function editUser(id, type) {
    console.log(`Editing ${type} with ID: ${id}`);
    // In a real application, you would redirect to a user edit page
    // or open a modal with a form for editing
    alert(`Edit ${type} with ID ${id}`);
}

function deleteUser(id, type) {
    if (confirm(`Are you sure you want to delete this ${type}?`)) {
        console.log(`Deleting ${type} with ID: ${id}`);
        // In a real application, you would send a delete request to the server
        alert(`Delete ${type} with ID ${id}`);
    }
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
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
    console.log(`${type}: ${message}`);
    // In a real application, you would display a notification to the user
    alert(`${type.toUpperCase()}: ${message}`);
}

function performBulkAction(action, selectedIds) {
    console.log(`Performing ${action} on IDs: ${selectedIds.join(', ')}`);
    // In a real application, you would send the action to the server
    alert(`Action ${action} performed on IDs: ${selectedIds.join(', ')}`);
}