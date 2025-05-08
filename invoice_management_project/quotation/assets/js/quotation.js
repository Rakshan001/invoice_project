document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Handle status filter
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            filterQuotations(this.value);
        });
    }

    // Handle date range filter
    const dateFilter = document.getElementById('dateFilter');
    if (dateFilter) {
        dateFilter.addEventListener('change', function() {
            filterByDate(this.value);
        });
    }

    // Handle search functionality
    const searchInput = document.getElementById('searchQuotation');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            searchQuotations(this.value);
        }, 300));
    }

    // Handle bulk actions
    const bulkActionSelect = document.getElementById('bulkAction');
    const applyBulkAction = document.getElementById('applyBulkAction');
    if (bulkActionSelect && applyBulkAction) {
        applyBulkAction.addEventListener('click', function() {
            const selectedQuotations = getSelectedQuotations();
            if (selectedQuotations.length === 0) {
                showAlert('Please select at least one quotation', 'warning');
                return;
            }
            executeBulkAction(bulkActionSelect.value, selectedQuotations);
        });
    }

    // Handle select all checkbox
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.quotation-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    // Handle quotation deletion
    document.querySelectorAll('.delete-quotation').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const quotationId = this.dataset.id;
            confirmDelete(quotationId);
        });
    });
});

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.querySelector('.alert-container').appendChild(alertDiv);
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Filter Functions
function filterQuotations(status) {
    const rows = document.querySelectorAll('.quotation-row');
    rows.forEach(row => {
        const rowStatus = row.dataset.status;
        if (status === 'all' || rowStatus === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filterByDate(range) {
    // Implementation for date range filtering
    console.log('Filtering by date range:', range);
}

function searchQuotations(query) {
    const rows = document.querySelectorAll('.quotation-row');
    query = query.toLowerCase();
    
    rows.forEach(row => {
        const searchableContent = row.dataset.searchContent.toLowerCase();
        if (searchableContent.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Bulk Actions
function getSelectedQuotations() {
    const checkboxes = document.querySelectorAll('.quotation-checkbox:checked');
    return Array.from(checkboxes).map(checkbox => checkbox.value);
}

function executeBulkAction(action, quotationIds) {
    if (!action) {
        showAlert('Please select an action', 'warning');
        return;
    }

    // Send AJAX request to handle bulk action
    fetch('quotation/bulk-action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: action,
            quotationIds: quotationIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            if (data.reload) {
                window.location.reload();
            }
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('An error occurred while processing your request', 'danger');
        console.error('Error:', error);
    });
}

// Delete Confirmation
function confirmDelete(quotationId) {
    if (confirm('Are you sure you want to delete this quotation?')) {
        fetch(`quotation/delete.php?id=${quotationId}`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Quotation deleted successfully', 'success');
                document.querySelector(`tr[data-id="${quotationId}"]`).remove();
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            showAlert('An error occurred while deleting the quotation', 'danger');
            console.error('Error:', error);
        });
    }
}

// Print Quotation
function printQuotation(quotationId) {
    window.open(`quotation/print.php?id=${quotationId}`, '_blank');
}

// Download Quotation
function downloadQuotation(quotationId) {
    window.location.href = `quotation/download.php?id=${quotationId}`;
}

// Preview Quotation
function previewQuotation(quotationId) {
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    
    fetch(`quotation/preview.php?id=${quotationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('previewContent').innerHTML = data.html;
                modal.show();
            } else {
                showAlert('Failed to load quotation preview', 'danger');
            }
        })
        .catch(error => {
            showAlert('An error occurred while loading the preview', 'danger');
            console.error('Error:', error);
        });
} 