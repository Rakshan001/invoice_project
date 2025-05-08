<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get statistics
$result = $conn->query("SELECT COUNT(*) as total_companies FROM company_master");
$total_companies = $result->fetch_assoc()['total_companies'];

// Get companies registered this month
$result = $conn->query("
    SELECT COUNT(*) as active_companies 
    FROM company_master 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
$active_companies = $result->fetch_assoc()['active_companies'];

$result = $conn->query("SELECT COUNT(*) as pending_companies FROM users WHERE user_id NOT IN (SELECT user_id FROM company_master)");
$pending_companies = $result->fetch_assoc()['pending_companies'];

// Fetch all companies with their user details
$result = $conn->query("
    SELECT 
        c.*,
        u.username as owner_username,
        u.email as owner_email,
        u.full_name as owner_name
    FROM company_master c
    JOIN users u ON c.user_id = u.user_id
    ORDER BY c.created_at DESC
");
$companies = [];
while ($row = $result->fetch_assoc()) {
    $companies[] = $row;
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <h1 class="page-title">Company Management</h1>

    <!-- Companies List -->
    <div class="card table-card animate-fade-in">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Companies</h5>
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
                                placeholder="Search by company name, owner, GSTIN...">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div id="searchResults" class="mt-2 text-muted small"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex justify-content-md-end align-items-center gap-2">
                            <select id="registerMonth" class="form-select">
                                <option value="">All Registration Dates</option>
                                <option value="current">This Month</option>
                                <option value="last3">Last 3 Months</option>
                                <option value="last6">Last 6 Months</option>
                                <option value="lastyear">Last Year</option>
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
                            <th>Company Name</th>
                            <th>Owner</th>
                            <th>Contact Info</th>
                            <th>Registration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies as $company): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="company-avatar bg-light rounded-circle me-2">
                                            <i class="fas fa-building text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($company['name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($company['gstin'] ?? 'No GSTIN'); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle me-2">
                                            <?php echo strtoupper(substr($company['owner_username'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($company['owner_name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($company['owner_email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <i class="fas fa-envelope me-1 text-muted"></i> <?php echo htmlspecialchars($company['email']); ?><br>
                                        <i class="fas fa-phone me-1 text-muted"></i> <?php echo htmlspecialchars($company['phone']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <i class="fas fa-calendar me-1 text-muted"></i>
                                        <?php echo date('M d, Y', strtotime($company['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-success">Registered</span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-info" title="Send Message">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
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
/* Additional styles specific to companies.php */
.company-avatar {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

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

.table td {
    vertical-align: middle;
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

#registerMonth {
    min-width: 180px;
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
    const registerMonthSelect = document.getElementById('registerMonth');
    const exportBtn = document.getElementById('exportBtn');
    const searchResults = document.getElementById('searchResults');
    
    // Update results count
    function updateResultsCount(count) {
        searchResults.textContent = `Showing ${count} of ${tableRows.length} companies`;
    }
    
    // Initialize with all results showing
    updateResultsCount(tableRows.length);
    
    // Function to perform combined search with date filter
    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedPeriod = registerMonthSelect.value;
        let visibleCount = 0;
        
        // Calculate date ranges for period filter
        const now = new Date();
        let startDate = new Date(0); // Default to beginning of time
        
        if (selectedPeriod === 'current') {
            // Current month
            startDate = new Date(now.getFullYear(), now.getMonth(), 1);
        } else if (selectedPeriod === 'last3') {
            // Last 3 months
            startDate = new Date(now);
            startDate.setMonth(now.getMonth() - 3);
        } else if (selectedPeriod === 'last6') {
            // Last 6 months
            startDate = new Date(now);
            startDate.setMonth(now.getMonth() - 6);
        } else if (selectedPeriod === 'lastyear') {
            // Last year
            startDate = new Date(now);
            startDate.setFullYear(now.getFullYear() - 1);
        }
        
        tableRows.forEach(row => {
            // Get all text content from the row for search
            const rowText = row.textContent.toLowerCase();
            
            // Get date for date filtering
            const dateCell = row.querySelector('td:nth-child(4)').textContent.trim();
            const rowDate = new Date(convertDateFormat(dateCell));
            
            // Check both search term and date range
            const matchesSearch = searchTerm === '' || rowText.includes(searchTerm);
            const matchesDate = selectedPeriod === '' || rowDate >= startDate;
            
            if (matchesSearch && matchesDate) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        updateResultsCount(visibleCount);
    }
    
    // Add event listeners for real-time filtering
    searchInput.addEventListener('input', performSearch);
    registerMonthSelect.addEventListener('change', performSearch);
    
    // Clear search
    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        registerMonthSelect.value = '';
        performSearch();
        searchInput.focus();
    });
    
    // Export visible companies
    exportBtn.addEventListener('click', function() {
        const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
        
        if (visibleRows.length === 0) {
            alert('No data to export');
            return;
        }
        
        // Create CSV content
        let csvContent = 'Company Name,GSTIN,Owner Name,Owner Email,Contact Email,Phone,Registration Date\n';
        
        visibleRows.forEach(row => {
            const companyName = row.querySelector('td:nth-child(1) h6').textContent.trim();
            const gstin = row.querySelector('td:nth-child(1) small').textContent.trim();
            const ownerName = row.querySelector('td:nth-child(2) h6').textContent.trim();
            const ownerEmail = row.querySelector('td:nth-child(2) small').textContent.trim();
            const contactInfo = row.querySelector('td:nth-child(3)').textContent.trim().split('\n');
            const contactEmail = contactInfo[0].replace('✉', '').trim();
            const phone = contactInfo[1].replace('📞', '').trim();
            const regDate = row.querySelector('td:nth-child(4)').textContent.trim().replace('📅', '').trim();
            
            csvContent += `"${companyName}","${gstin}","${ownerName}","${ownerEmail}","${contactEmail}","${phone}","${regDate}"\n`;
        });
        
        // Create download link
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', 'companies_export.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    
    // Helper function to convert date format from "Mar 15, 2023" to "2023-03-15"
    function convertDateFormat(dateStr) {
        // Extract the date part, removing any icons
        const dateOnlyStr = dateStr.replace(/\s*\n.*/g, '').replace(/[^\w\s,]/g, '').trim();
        
        const months = {
            'Jan': 0, 'Feb': 1, 'Mar': 2, 'Apr': 3, 'May': 4, 'Jun': 5,
            'Jul': 6, 'Aug': 7, 'Sep': 8, 'Oct': 9, 'Nov': 10, 'Dec': 11
        };
        
        const parts = dateOnlyStr.split(' ');
        const month = months[parts[0]];
        const day = parseInt(parts[1].replace(',', ''));
        const year = parseInt(parts[2]);
        
        return new Date(year, month, day).toISOString().split('T')[0];
    }
});
</script>

<?php include 'includes/footer.php'; ?> 