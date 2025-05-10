function initOrganizationsPage() {
    console.log("Initializing Organizations Page...");
    
    // Get filter elements
    const searchInput = document.getElementById('org-search');
    const clearSearch = document.getElementById('clear-org-search');
    const statusFilter = document.getElementById('org-status-filter');
    const dateFilter = document.getElementById('org-date-filter');
    const sortBy = document.getElementById('org-sort-by');
    
    // Set up event listeners
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => loadOrganizations(1), 500));
    }
    
    if (clearSearch) {
        clearSearch.addEventListener('click', () => {
            if (searchInput) {
                searchInput.value = '';
                loadOrganizations(1);
            }
        });
    }
    
    if (statusFilter) statusFilter.addEventListener('change', () => loadOrganizations(1));
    if (dateFilter) dateFilter.addEventListener('change', () => loadOrganizations(1));
    if (sortBy) sortBy.addEventListener('change', () => loadOrganizations(1));
    
    // Select all checkbox
    const selectAll = document.getElementById('select-all-orgs');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('#organizations-table .row-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
            updateOrgBulkActionsState();
        });
    }
    
    // Bulk actions
    const bulkActionSelect = document.getElementById('bulk-action-orgs');
    const bulkActionButton = document.getElementById('apply-bulk-action-orgs');
    if (bulkActionButton) {
        bulkActionButton.addEventListener('click', () => {
            const action = bulkActionSelect ? bulkActionSelect.value : '';
            if (!action) return;
            
            const selectedIds = getSelectedOrgIds();
            if (selectedIds.length === 0) {
                showOrgNotification('error', 'No organizations selected.');
                return;
            }
            
            if (confirm(`Are you sure you want to ${action} ${selectedIds.length} selected organization(s)?`)) {
                performOrgBulkAction(action, selectedIds);
            }
        });
    }

    // Initial load
    loadOrganizations(1);
}

function loadOrganizations(page) {
    const tbody = document.querySelector('#organizations-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '<tr><td colspan="8" class="text-center"><div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading organizations...</div></td></tr>';
    
    const status = document.getElementById('org-status-filter')?.value || 'all';
    const date = document.getElementById('org-date-filter')?.value || 'all';
    const sort = document.getElementById('org-sort-by')?.value || 'newest';
    const search = document.getElementById('org-search')?.value || '';
    
    const params = new URLSearchParams({
        status,
        date,
        sort,
        page,
        limit: 10 // Adjust limit as needed
    });
    if (search) params.append('search', search);
    
    // --- TODO: Create this backend endpoint ---
    fetch(`/iteam-university-website/backend/api/admin/organizations_list.php?${params.toString()}`) 
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderOrganizations(data.organizations);
                renderOrgPagination(data.pagination);
            } else {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center error-message">${data.message || 'Error loading organizations'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading organizations:', error);
            tbody.innerHTML = '<tr><td colspan="8" class="text-center error-message">Could not fetch organizations. Please try again.</td></tr>';
        });
}

function renderOrganizations(organizations) {
    const tbody = document.querySelector('#organizations-table tbody');
    if (!tbody) return;
    tbody.innerHTML = ''; // Clear loading/previous rows
    
    if (!organizations || organizations.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No organizations found matching your criteria.</td></tr>';
        return;
    }
    
    organizations.forEach(org => {
        const row = document.createElement('tr');
        const registrationDate = new Date(org.registration_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        const statusBadge = `<span class="badge status-${org.status}">${capitalizeFirstLetter(org.status)}</span>`;
        
        row.innerHTML = `
            <td><input type="checkbox" class="row-checkbox" data-id="${org.organization_id}"></td>
            <td>${org.organization_id}</td>
            <td>
                <div class="user-info-cell">
                    <!-- Placeholder for potential logo/avatar -->
                    <div class="user-avatar-small org-avatar">${org.name.charAt(0)}</div> 
                    <span>${org.name}</span>
                </div>
            </td>
            <td>${org.email}</td>
            <td>${statusBadge}</td>
            <td>${registrationDate}</td>
            <td>${org.events_hosted || 0}</td> 
            <td>
                <div class="action-buttons">
                    <button class="action-btn" title="View Details" onclick="viewOrganization('${org.organization_id}')"><i class="fas fa-eye"></i></button>
                    <button class="action-btn" title="Edit Organization" onclick="editOrganization('${org.organization_id}')"><i class="fas fa-edit"></i></button>
                    <button class="action-btn text-danger" title="Delete Organization" onclick="deleteOrganization('${org.organization_id}')"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Add event listeners to new checkboxes
    tbody.querySelectorAll('.row-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateOrgBulkActionsState);
    });
    updateOrgBulkActionsState(); // Update state after rendering
}

function renderOrgPagination(pagination) {
    const paginationContainer = document.getElementById('orgs-pagination');
    if (!paginationContainer || !pagination || pagination.total_pages <= 0) {
        if (paginationContainer) paginationContainer.innerHTML = ''; // Clear if no pages
        return;
    }
    
    paginationContainer.innerHTML = ''; // Clear previous
    
    // Prev button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'pagination-btn';
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.disabled = pagination.current_page === 1;
    prevBtn.addEventListener('click', () => loadOrganizations(pagination.current_page - 1));
    paginationContainer.appendChild(prevBtn);

    // Page numbers (simplified for brevity, consider adding ellipsis logic like in users.js)
    for (let i = 1; i <= pagination.total_pages; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = 'pagination-btn';
        pageBtn.textContent = i;
        if (i === pagination.current_page) {
            pageBtn.classList.add('active');
        }
        pageBtn.addEventListener('click', () => loadOrganizations(i));
        paginationContainer.appendChild(pageBtn);
    }

    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'pagination-btn';
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.disabled = pagination.current_page === pagination.total_pages;
    nextBtn.addEventListener('click', () => loadOrganizations(pagination.current_page + 1));
    paginationContainer.appendChild(nextBtn);
}

function updateOrgBulkActionsState() {
    const checkboxes = document.querySelectorAll('#organizations-table .row-checkbox');
    const selectAll = document.getElementById('select-all-orgs');
    const bulkActionButton = document.getElementById('apply-bulk-action-orgs');
    
    const selectedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
    
    if (selectAll) {
        selectAll.checked = selectedCount === checkboxes.length && checkboxes.length > 0;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
    }
    
    if (bulkActionButton) {
        bulkActionButton.disabled = selectedCount === 0;
    }
}

function getSelectedOrgIds() {
    return Array.from(document.querySelectorAll('#organizations-table .row-checkbox:checked'))
           .map(checkbox => checkbox.dataset.id);
}

function performOrgBulkAction(action, orgIds) {
    console.log(`Performing bulk action '${action}' on organizations:`, orgIds);
    // --- TODO: Implement backend call for bulk actions ---
    // Example:
    /*
    fetch('/iteam-university-website/backend/api/admin/organizations_bulk.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action, ids: orgIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showOrgNotification('success', `Successfully performed ${action} on selected organizations.`);
            loadOrganizations(1); // Reload data
        } else {
            showOrgNotification('error', data.message || `Failed to perform ${action}.`);
        }
    })
    .catch(error => {
        showOrgNotification('error', 'An error occurred during bulk action.');
        console.error('Bulk action error:', error);
    });
    */
   showOrgNotification('info', `Simulated: ${action} on ${orgIds.length} organizations.`);
   loadOrganizations(1); // Simulate reload
}

function viewOrganization(id) {
    console.log(`Viewing organization ${id}`);
    // --- TODO: Implement view logic (e.g., load details page/modal) ---
    alert(`View Organization ID: ${id}`);
}

function editOrganization(id) {
    console.log(`Editing organization ${id}`);
    // --- TODO: Implement edit logic (e.g., load edit page/modal) ---
     loadContent(`dashboards/admin/pages/edit-organization.html?id=${id}`, 'edit-organization');
}

function deleteOrganization(id) {
    if (confirm(`Are you sure you want to delete organization ID ${id}? This may affect associated events.`)) {
        console.log(`Deleting organization ${id}`);
        // --- TODO: Implement backend call for deletion ---
        /*
        fetch('/iteam-university-website/backend/api/admin/organizations_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
             if (data.success) {
                 showOrgNotification('success', `Organization ID ${id} deleted.`);
                 loadOrganizations(1); // Reload
             } else {
                 showOrgNotification('error', data.message || 'Failed to delete organization.');
             }
        })
        .catch(error => {
             showOrgNotification('error', 'An error occurred during deletion.');
             console.error('Delete error:', error);
        });
        */
       showOrgNotification('info', `Simulated: Delete Organization ID ${id}.`);
       loadOrganizations(1); // Simulate reload
    }
}

// Helper functions (reuse or define if not globally available)
function capitalizeFirstLetter(string) {
    return string ? string.charAt(0).toUpperCase() + string.slice(1) : '';
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

function showOrgNotification(type, message) {
    // Simple alert for now, replace with a proper notification system if available
    console.log(`[Org Notification - ${type.toUpperCase()}]: ${message}`);
    alert(`[${type.toUpperCase()}] ${message}`);
}