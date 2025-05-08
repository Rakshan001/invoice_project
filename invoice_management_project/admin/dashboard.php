<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get statistics
$result = $conn->query("SELECT COUNT(*) as total_users FROM users");
$total_users = $result->fetch_assoc()['total_users'];

$result = $conn->query("SELECT COUNT(*) as total_companies FROM company_master");
$total_companies = $result->fetch_assoc()['total_companies'];

$result = $conn->query("SELECT COUNT(*) as pending_companies FROM users WHERE user_id NOT IN (SELECT user_id FROM company_master)");
$pending_companies = $result->fetch_assoc()['pending_companies'];

// Get recent users
$result = $conn->query("
    SELECT 
        u.username,
        u.email,
        u.created_at,
        CASE 
            WHEN c.name IS NOT NULL THEN 'Active'
            ELSE 'Pending'
        END as status
    FROM users u
    LEFT JOIN company_master c ON u.user_id = c.user_id
    ORDER BY u.created_at DESC
    LIMIT 5
");
$recent_users = [];
while ($row = $result->fetch_assoc()) {
    $recent_users[] = $row;
}

// Get recent companies
$result = $conn->query("
    SELECT 
        c.name as company_name,
        c.email,
        c.created_at,
        u.username as owner
    FROM company_master c
    JOIN users u ON c.user_id = u.user_id
    ORDER BY c.created_at DESC
    LIMIT 5
");
$recent_companies = [];
while ($row = $result->fetch_assoc()) {
    $recent_companies[] = $row;
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <h1 class="page-title">Admin Dashboard</h1>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stats-card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="display-4 mb-0"><?php echo $total_users; ?></h2>
                            <p class="mb-0">Total Users</p>
                        </div>
                    </div>
                    <i class="fas fa-users stats-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card bg-gradient-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="display-4 mb-0"><?php echo $total_companies; ?></h2>
                            <p class="mb-0">Active Companies</p>
                        </div>
                    </div>
                    <i class="fas fa-building stats-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card bg-gradient-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="display-4 mb-0"><?php echo $pending_companies; ?></h2>
                            <p class="mb-0">Pending Companies</p>
                        </div>
                    </div>
                    <i class="fas fa-clock stats-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="users.php" class="btn btn-light w-100 p-3 d-flex align-items-center">
                                <i class="fas fa-user-plus fa-2x me-3 text-primary"></i>
                                <div>
                                    <h6 class="mb-0">Manage Users</h6>
                                    <small class="text-muted">View and manage users</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="companies.php" class="btn btn-light w-100 p-3 d-flex align-items-center">
                                <i class="fas fa-building fa-2x me-3 text-success"></i>
                                <div>
                                    <h6 class="mb-0">Manage Companies</h6>
                                    <small class="text-muted">View company details</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="settings.php" class="btn btn-light w-100 p-3 d-flex align-items-center">
                                <i class="fas fa-cog fa-2x me-3 text-info"></i>
                                <div>
                                    <h6 class="mb-0">Settings</h6>
                                    <small class="text-muted">Configure system</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="reports.php" class="btn btn-light w-100 p-3 d-flex align-items-center">
                                <i class="fas fa-chart-bar fa-2x me-3 text-warning"></i>
                                <div>
                                    <h6 class="mb-0">Reports</h6>
                                    <small class="text-muted">View analytics</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card table-card animate-fade-in h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Users</h5>
                    <a href="users.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle me-2">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </div>
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php if ($user['status'] == 'Active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card table-card animate-fade-in h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Companies</h5>
                    <a href="companies.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Owner</th>
                                    <th>Email</th>
                                    <th>Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_companies as $company): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-building text-success me-2"></i>
                                            <?php echo htmlspecialchars($company['company_name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($company['owner']); ?></td>
                                    <td><?php echo htmlspecialchars($company['email']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($company['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Additional styles specific to dashboard */
.btn-light {
    background: #fff;
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.btn-light:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
</style>

<?php include 'includes/footer.php'; ?> 