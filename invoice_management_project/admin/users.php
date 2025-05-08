<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get statistics
$result = $conn->query("SELECT COUNT(*) as total_users FROM users"); // Count all users
$total_users = $result->fetch_assoc()['total_users'];

// Count companies (all companies in company_master are considered registered)
$result = $conn->query("SELECT COUNT(*) as total_companies FROM company_master");
$registered_companies = $result->fetch_assoc()['total_companies'];

// Count users without companies (pending)
$result = $conn->query("SELECT COUNT(*) as pending_companies FROM users WHERE user_id NOT IN (SELECT user_id FROM company_master)");
$pending_companies = $result->fetch_assoc()['pending_companies'];

// Fetch all users with their company details
$result = $conn->query("
    SELECT 
        u.user_id,
        u.username,
        u.email,
        u.full_name,
        u.created_at as registration_date,
        c.name as company_name,
        c.email as company_email,
        c.phone as company_phone,
        CASE 
            WHEN c.name IS NOT NULL THEN 'Active'
            ELSE 'Pending'
        END as status
    FROM users u
    LEFT JOIN company_master c ON u.user_id = c.user_id
    ORDER BY u.created_at DESC
");
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <h1 class="page-title">User Management</h1>

    <!-- Users List -->
    <div class="card table-card animate-fade-in">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Users</h5>
            <div class="header-actions">
                <button type="button" class="btn btn-primary btn-sm">
                    <i class="fas fa-download me-2"></i>Export
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Filtering Options -->
            <div class="filtering-options mb-3">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" id="searchInput" class="form-control form-control-lg" 
                                placeholder="Search by any field (name, email, username...)">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div id="searchResults" class="mt-2 text-muted small"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex justify-content-md-end align-items-center gap-2">
                            <select id="statusFilter" class="form-select">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Pending">Pending</option>
                            </select>
                            <button type="button" class="btn btn-primary" id="exportBtn">
                                <i class="fas fa-download me-2"></i>Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Company Name</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle me-2">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['company_name']): ?>
                                        <span class="text-success">
                                            <i class="fas fa-building me-1"></i>
                                            <?php echo htmlspecialchars($user['company_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Not Added</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['registration_date'])); ?></td>
                                <td>
                                    <?php if ($user['status'] == 'Active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-info" title="Send Message">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* Additional styles specific to users.php */
.avatar-sm {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
}

.header-actions .btn {
    padding: 0.5rem 1rem;
    font-weight: 600;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
}

.btn-group .btn i {
    font-size: 0.875rem;
}

/* Filter form styles */
.filtering-options {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border: 1px solid #e9ecef;
}

/* Search styles */
.filtering-options {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border: 1px solid #e9ecef;
}

.input-group-text {
    border: none;
}

#statusFilter {
    min-width: 140px;
}

#searchInput:focus {
    box-shadow: none;
    border-color: #ced4da;
}
</style>

<script>
// Real-time search functionality
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('tbody tr');
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');
    const statusFilter = document.getElementById('statusFilter');
    const exportBtn = document.getElementById('exportBtn');
    const searchResults = document.getElementById('searchResults');
    
    // Update results count
    function updateResultsCount(count) {
        searchResults.textContent = `Showing ${count} of ${tableRows.length} users`;
    }
    
    // Initialize with all results showing
    updateResultsCount(tableRows.length);
    
    // Function to perform search across all fields
    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedStatus = statusFilter.value;
        let visibleCount = 0;
        
        tableRows.forEach(row => {
            // Get all text content from the row (searches across all fields)
            const rowText = row.textContent.toLowerCase();
            const statusCell = row.querySelector('td:nth-child(7) .badge').textContent.trim();
            
            // Check if row matches both search term and status filter
            const matchesSearch = searchTerm === '' || rowText.includes(searchTerm);
            const matchesStatus = selectedStatus === '' || statusCell === selectedStatus;
            
            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        updateResultsCount(visibleCount);
    }
    
    // Add event listeners for real-time search
    searchInput.addEventListener('input', performSearch);
    statusFilter.addEventListener('change', performSearch);
    
    // Clear search
    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        statusFilter.value = '';
        performSearch();
        searchInput.focus();
    });
    
    // Export visible users
    exportBtn.addEventListener('click', function() {
        const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
        
        if (visibleRows.length === 0) {
            alert('No data to export');
            return;
        }
        
        // Create CSV content
        let csvContent = 'User ID,Username,Full Name,Email,Company Name,Registration Date,Status\n';
        
        visibleRows.forEach(row => {
            const columns = row.querySelectorAll('td');
            const userId = columns[0].textContent.trim();
            const username = columns[1].textContent.trim().replace(/\s+/g, ' ');
            const fullName = columns[2].textContent.trim();
            const email = columns[3].textContent.trim();
            const company = columns[4].textContent.trim().replace(/\s+/g, ' ');
            const regDate = columns[5].textContent.trim();
            const status = columns[6].textContent.trim();
            
            csvContent += `"${userId}","${username}","${fullName}","${email}","${company}","${regDate}","${status}"\n`;
        });
        
        // Create download link
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', 'users_export.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

<?php include 'includes/footer.php'; ?> 