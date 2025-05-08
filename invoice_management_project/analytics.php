<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get company data
$stmt = $conn->prepare("SELECT company_id FROM company_master WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();

if (!$company) {
    $_SESSION['error'] = "Please set up your company details first.";
    header("Location: company_details.php");
    exit();
}

$company_id = $company['company_id'];

// Initialize date variables
$current_month = (int)date('m');
$current_year = (int)date('Y');

// If current month is Jan-Mar, we're in the previous financial year
$current_fy_start_year = $current_month <= 3 ? $current_year - 1 : $current_year;
$selected_fy_start_year = isset($_GET['fy_year']) ? (int)$_GET['fy_year'] : $current_fy_start_year;
$selected_month = isset($_GET['month']) && $_GET['month'] !== '' ? (int)$_GET['month'] : null;

// Calculate financial year date range
$fy_start_date = sprintf('%04d-04-01', $selected_fy_start_year);
$fy_end_date = sprintf('%04d-03-31', $selected_fy_start_year + 1);

// Get available financial years for the dropdown
$stmt = $conn->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN MONTH(invoice_date) <= 3 THEN YEAR(invoice_date) - 1 
            ELSE YEAR(invoice_date) 
        END as fy_start_year 
    FROM invoice 
    WHERE company_id = ? 
    ORDER BY fy_start_year DESC
");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$available_fy_years = [];
while ($row = $result->fetch_assoc()) {
    $available_fy_years[] = $row['fy_start_year'];
}

if (empty($available_fy_years)) {
    $available_fy_years = [$current_fy_start_year];
}

// Initialize arrays for chart data with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$items_per_page = 50; // Number of invoices to load per page
$offset = ($page - 1) * $items_per_page;

// Get total number of invoices for pagination
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM invoice 
    WHERE company_id = ? 
    AND invoice_date BETWEEN ? AND ?
");
$stmt->bind_param("iss", $company_id, $fy_start_date, $fy_end_date);
$stmt->execute();
$result = $stmt->get_result();
$total_invoices = $result->fetch_assoc()['total'];
$total_pages = ceil($total_invoices / $items_per_page);

// Initialize monthly data arrays
$monthly_data = [
    'labels' => [],
    'paid' => array_fill(0, 12, 0),
    'pending' => array_fill(0, 12, 0),
    'cancelled' => array_fill(0, 12, 0),
    'amounts' => array_fill(0, 12, 0),
    'gst_amounts' => array_fill(0, 12, 0)
];

// Generate labels for all 12 months of the financial year
for ($m = 1; $m <= 12; $m++) {
    $month_num = $m <= 9 ? $m + 3 : $m - 9;
    $year = $m <= 9 ? $selected_fy_start_year : $selected_fy_start_year + 1;
    $month_date = sprintf('%04d-%02d-01', $year, $month_num);
    $monthly_data['labels'][] = date('M Y', strtotime($month_date));
}

// Function to get month name based on financial year month number
function getFinancialMonthName($month_num, $fy_start_year) {
    if ($month_num <= 9) {
        $actual_month = $month_num + 3;
        $year = $fy_start_year;
    } else {
        $actual_month = $month_num - 9;
        $year = $fy_start_year + 1;
    }
    return date('F Y', mktime(0, 0, 0, $actual_month, 1, $year));
}

// Get monthly totals with optimized query
$stmt = $conn->prepare("
    SELECT 
        CASE 
            WHEN MONTH(invoice_date) <= 3 THEN MONTH(invoice_date) + 9
            ELSE MONTH(invoice_date) - 3
        END as fy_month,
        status,
        COUNT(*) as count,
        COALESCE(SUM(net_total), 0) as total,
        COALESCE(SUM(total_tax_amount), 0) as gst_total
    FROM invoice 
    WHERE company_id = ? 
    AND invoice_date BETWEEN ? AND ?
    GROUP BY 
        CASE 
            WHEN MONTH(invoice_date) <= 3 THEN MONTH(invoice_date) + 9
            ELSE MONTH(invoice_date) - 3
        END,
        status
");
$stmt->bind_param("iss", $company_id, $fy_start_date, $fy_end_date);
$stmt->execute();
$result = $stmt->get_result();
$monthly_results = [];
while ($row = $result->fetch_assoc()) {
    $monthly_results[] = $row;
}

// Process monthly totals
foreach ($monthly_results as $result) {
    $month_index = $result['fy_month'] - 1;
    $status = $result['status'] ?: 'pending';
    
    if ($status == 'paid') {
        $monthly_data['paid'][$month_index] = (int)$result['count'];
        $monthly_data['amounts'][$month_index] += (float)$result['total'];
        $monthly_data['gst_amounts'][$month_index] += (float)$result['gst_total'];
    } else if ($status == 'pending') {
        $monthly_data['pending'][$month_index] = (int)$result['count'];
        $monthly_data['amounts'][$month_index] += (float)$result['total'];
        $monthly_data['gst_amounts'][$month_index] += (float)$result['gst_total'];
    } else if ($status == 'cancelled') {
        $monthly_data['cancelled'][$month_index] = (int)$result['count'];
    }
}

// Get individual invoice details with pagination
if ($selected_month !== null) {
    $month_num = $selected_month <= 9 ? $selected_month + 3 : $selected_month - 9;
    $year = $selected_month <= 9 ? $selected_fy_start_year : $selected_fy_start_year + 1;
    $month_start = sprintf('%04d-%02d-01', $year, $month_num);
    $month_end = date('Y-m-t', strtotime($month_start));
    
    $stmt = $conn->prepare("
        SELECT 
            invoice_number,
            invoice_date,
            net_total,
            status
        FROM invoice 
        WHERE company_id = ? 
        AND invoice_date BETWEEN ? AND ?
        ORDER BY invoice_date ASC
        LIMIT ?, ?
    ");
    $stmt->bind_param("issii", $company_id, $month_start, $month_end, $offset, $items_per_page);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("
        SELECT 
            invoice_number,
            invoice_date,
            net_total,
            status
        FROM invoice 
        WHERE company_id = ? 
        AND invoice_date BETWEEN ? AND ?
        ORDER BY invoice_date ASC
        LIMIT ?, ?
    ");
    $stmt->bind_param("issii", $company_id, $fy_start_date, $fy_end_date, $offset, $items_per_page);
    $stmt->execute();
}
$result = $stmt->get_result();
$invoice_details = [];
while ($row = $result->fetch_assoc()) {
    $invoice_details[] = $row;
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" id="analyticsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly" type="button" role="tab">
                Monthly Analysis
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="revenue-tab" data-bs-toggle="tab" data-bs-target="#revenue" type="button" role="tab">
                Revenue Trend
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="gst-tab" data-bs-toggle="tab" data-bs-target="#gst" type="button" role="tab">
                GST Analysis
            </button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content" id="analyticsTabContent">
        <!-- Monthly Invoice Analysis Tab -->
        <div class="tab-pane fade show active" id="monthly" role="tabpanel">
            <!-- Period Selection -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3 align-items-center" id="monthlyForm">
                                <input type="hidden" name="active_tab" value="monthly">
                                <div class="col-md-4">
                                    <label for="fy_year" class="form-label">Select Financial Year</label>
                                    <select name="fy_year" id="year_monthly" class="form-select" onchange="this.form.submit()">
                                        <?php foreach ($available_fy_years as $year): ?>
                                            <option value="<?php echo $year; ?>" <?php echo $year == $selected_fy_start_year ? 'selected' : ''; ?>>
                                                FY <?php echo $year; ?>-<?php echo substr($year + 1, -2); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="month_monthly" class="form-label">Select Month</label>
                                    <select name="month" id="month_monthly" class="form-select" onchange="this.form.submit()">
                                        <option value="">All Months</option>
                                        <?php for ($m = 1; $m <= 12; $m++): 
                                            $month_name = getFinancialMonthName($m, $selected_fy_start_year);
                                        ?>
                                            <option value="<?php echo $m; ?>" <?php echo $selected_month === $m ? 'selected' : ''; ?>>
                                                <?php echo $month_name; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Monthly Analysis Content -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Monthly Invoice Analysis - <?php 
                                if ($selected_month !== null) {
                                    echo getFinancialMonthName($selected_month, $selected_fy_start_year);
                                } else {
                                    echo 'FY ' . $selected_fy_start_year . '-' . ($selected_fy_start_year + 1);
                                }
                            ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <!-- Monthly Stats -->
                                <div class="col-md-4">
                                    <div class="stats-card bg-light p-3 rounded">
                                        <h6 class="text-muted mb-3"><?php echo $selected_month !== null ? 'Selected Month' : 'Year'; ?> Overview</h6>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Total Invoices:</span>
                                            <span class="fw-bold"><?php 
                                                $total_invoices = 0;
                                                if ($selected_month !== null) {
                                                    $month_index = $selected_month - 1;
                                                    $total_invoices = $monthly_data['paid'][$month_index] + 
                                                                    $monthly_data['pending'][$month_index] + 
                                                                    $monthly_data['cancelled'][$month_index];
                                                } else {
                                                    $total_invoices = array_sum($monthly_data['paid']) + 
                                                                     array_sum($monthly_data['pending']) + 
                                                                     array_sum($monthly_data['cancelled']);
                                                }
                                                echo $total_invoices;
                                            ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Paid Invoices:</span>
                                            <span class="text-success fw-bold"><?php 
                                                echo $selected_month !== null 
                                                    ? $monthly_data['paid'][$selected_month - 1] 
                                                    : array_sum($monthly_data['paid']);
                                            ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Pending Invoices:</span>
                                            <span class="text-warning fw-bold"><?php 
                                                echo $selected_month !== null 
                                                    ? $monthly_data['pending'][$selected_month - 1] 
                                                    : array_sum($monthly_data['pending']);
                                            ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Cancelled Invoices:</span>
                                            <span class="text-danger fw-bold"><?php 
                                                echo $selected_month !== null 
                                                    ? $monthly_data['cancelled'][$selected_month - 1] 
                                                    : array_sum($monthly_data['cancelled']);
                                            ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                                            <span>Total Amount:</span>
                                            <span class="fw-bold">₹<?php 
                                                echo $selected_month !== null 
                                                    ? number_format($monthly_data['amounts'][$selected_month - 1], 2)
                                                    : number_format(array_sum($monthly_data['amounts']), 2);
                                            ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Paid Amount:</span>
                                            <span class="text-success fw-bold">₹<?php 
                                                if ($selected_month !== null) {
                                                    $month_index = $selected_month - 1;
                                                    $paid_amount = 0;
                                                    foreach ($invoice_details as $invoice) {
                                                        if ($invoice['status'] == 'paid') {
                                                            $paid_amount += $invoice['net_total'];
                                                        }
                                                    }
                                                    echo number_format($paid_amount, 2);
                                                } else {
                                                    $paid_amount = 0;
                                                    foreach ($invoice_details as $invoice) {
                                                        if ($invoice['status'] == 'paid') {
                                                            $paid_amount += $invoice['net_total'];
                                                        }
                                                    }
                                                    echo number_format($paid_amount, 2);
                                                }
                                            ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Pending Amount:</span>
                                            <span class="text-warning fw-bold">₹<?php 
                                                if ($selected_month !== null) {
                                                    $month_index = $selected_month - 1;
                                                    $pending_amount = 0;
                                                    foreach ($invoice_details as $invoice) {
                                                        if ($invoice['status'] == 'pending') {
                                                            $pending_amount += $invoice['net_total'];
                                                        }
                                                    }
                                                    echo number_format($pending_amount, 2);
                                                } else {
                                                    $pending_amount = 0;
                                                    foreach ($invoice_details as $invoice) {
                                                        if ($invoice['status'] == 'pending') {
                                                            $pending_amount += $invoice['net_total'];
                                                        }
                                                    }
                                                    echo number_format($pending_amount, 2);
                                                }
                                            ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Cancelled Amount:</span>
                                            <span class="text-danger fw-bold">₹<?php 
                                                if ($selected_month !== null) {
                                                    $month_index = $selected_month - 1;
                                                    $cancelled_amount = 0;
                                                    foreach ($invoice_details as $invoice) {
                                                        if ($invoice['status'] == 'cancelled') {
                                                            $cancelled_amount += $invoice['net_total'];
                                                        }
                                                    }
                                                    echo number_format($cancelled_amount, 2);
                                                } else {
                                                    $cancelled_amount = 0;
                                                    foreach ($invoice_details as $invoice) {
                                                        if ($invoice['status'] == 'cancelled') {
                                                            $cancelled_amount += $invoice['net_total'];
                                                        }
                                                    }
                                                    echo number_format($cancelled_amount, 2);
                                                }
                                            ?></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Monthly Chart -->
                                <div class="col-md-8">
                                    <div class="chart-container">
                                        <canvas id="monthlyInvoiceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Trend Tab -->
        <div class="tab-pane fade" id="revenue" role="tabpanel">
            <!-- Period Selection -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3 align-items-center" id="revenueForm">
                                <input type="hidden" name="active_tab" value="revenue">
                                <div class="col-md-4">
                                    <label for="fy_year" class="form-label">Select Financial Year</label>
                                    <select name="fy_year" id="year_revenue" class="form-select" onchange="this.form.submit()">
                                        <?php foreach ($available_fy_years as $year): ?>
                                            <option value="<?php echo $year; ?>" <?php echo $year == $selected_fy_start_year ? 'selected' : ''; ?>>
                                                FY <?php echo $year; ?>-<?php echo substr($year + 1, -2); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Revenue Content -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Yearly Revenue Trend - FY <?php 
                                echo $selected_fy_start_year . '-' . ($selected_fy_start_year + 1);
                            ?></h5>
                        </div>
                        <div class="card-body">
                            <canvas id="yearlyRevenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- GST Analysis Tab -->
        <div class="tab-pane fade" id="gst" role="tabpanel">
            <!-- Period Selection -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3 align-items-center" id="gstForm">
                                <input type="hidden" name="active_tab" value="gst">
                                <div class="col-md-4">
                                    <label for="fy_year" class="form-label">Select Financial Year</label>
                                    <select name="fy_year" id="year_gst" class="form-select" onchange="this.form.submit()">
                                        <?php foreach ($available_fy_years as $year): ?>
                                            <option value="<?php echo $year; ?>" <?php echo $year == $selected_fy_start_year ? 'selected' : ''; ?>>
                                                FY <?php echo $year; ?>-<?php echo substr($year + 1, -2); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="month_gst" class="form-label">Select Month</label>
                                    <select name="month" id="month_gst" class="form-select" onchange="this.form.submit()">
                                        <option value="">All Months</option>
                                        <?php for ($m = 1; $m <= 12; $m++): 
                                            $month_name = getFinancialMonthName($m, $selected_fy_start_year);
                                        ?>
                                            <option value="<?php echo $m; ?>" <?php echo $selected_month === $m ? 'selected' : ''; ?>>
                                                <?php echo $month_name; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- GST Analysis Content -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">GST Analysis - <?php 
                                if ($selected_month !== null) {
                                    echo getFinancialMonthName($selected_month, $selected_fy_start_year);
                                } else {
                                    echo 'FY ' . $selected_fy_start_year . '-' . ($selected_fy_start_year + 1);
                                }
                            ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <!-- GST Stats -->
                                <div class="col-md-4">
                                    <div class="stats-card bg-light p-3 rounded">
                                        <h6 class="text-muted mb-3"><?php echo $selected_month !== null ? 'Selected Month' : 'Year'; ?> GST Overview</h6>
                                        <?php if ($selected_month === null): ?>
                                            <?php for ($m = 1; $m <= 12; $m++): 
                                                $month_index = $m - 1;
                                                if ($monthly_data['gst_amounts'][$month_index] > 0):
                                            ?>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span><?php echo getFinancialMonthName($m, $selected_fy_start_year); ?>:</span>
                                                    <span class="fw-bold">₹<?php echo number_format($monthly_data['gst_amounts'][$month_index], 2); ?></span>
                                                </div>
                                            <?php 
                                                endif;
                                            endfor; 
                                            ?>
                                            <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                                                <span>Total GST:</span>
                                                <span class="fw-bold">₹<?php echo number_format(array_sum($monthly_data['gst_amounts']), 2); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Total GST:</span>
                                                <span class="fw-bold">₹<?php echo number_format($monthly_data['gst_amounts'][$selected_month - 1], 2); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <!-- GST Chart -->
                                <div class="col-md-8">
                                    <div class="chart-container">
                                        <canvas id="gstAnalysisChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Get the active tab from URL parameter
const urlParams = new URLSearchParams(window.location.search);
const activeTab = urlParams.get('active_tab') || 'monthly';

// Set the active tab on page load
document.addEventListener('DOMContentLoaded', function() {
    const tab = document.querySelector(`#analyticsTabs button[data-bs-target="#${activeTab}"]`);
    if (tab) {
        const bsTab = new bootstrap.Tab(tab);
        bsTab.show();
    }
});

// Update form IDs when submitting
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        // Add the current active tab to the form data
        const activeTabInput = this.querySelector('input[name="active_tab"]');
        if (activeTabInput) {
            const activeTab = document.querySelector('#analyticsTabs .nav-link.active');
            if (activeTab) {
                const tabId = activeTab.getAttribute('data-bs-target').replace('#', '');
                activeTabInput.value = tabId;
            }
        }
    });
});

// Update chart options for better performance with large datasets
const commonChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 0 // Disable animations for better performance
    },
    scales: {
        x: {
            grid: {
                display: false
            },
            ticks: {
                maxRotation: 45,
                minRotation: 45,
                autoSkip: true,
                maxTicksLimit: 12
            }
        }
    },
    plugins: {
        zoom: {
            pan: {
                enabled: true,
                mode: 'x'
            },
            zoom: {
                wheel: {
                    enabled: true
                },
                pinch: {
                    enabled: true
                },
                mode: 'x'
            }
        }
    }
};

// Monthly Invoice Chart
const monthlyCtx = document.getElementById('monthlyInvoiceChart');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($monthly_data['labels']); ?>,
        datasets: [
            {
                label: 'Paid',
                data: <?php echo json_encode($monthly_data['paid']); ?>,
                backgroundColor: 'rgba(40, 199, 111, 0.2)',
                borderColor: '#28C76F',
                borderWidth: 1
            },
            {
                label: 'Pending',
                data: <?php echo json_encode($monthly_data['pending']); ?>,
                backgroundColor: 'rgba(255, 159, 67, 0.2)',
                borderColor: '#FF9F43',
                borderWidth: 1
            },
            {
                label: 'Cancelled',
                data: <?php echo json_encode($monthly_data['cancelled']); ?>,
                backgroundColor: 'rgba(234, 84, 85, 0.2)',
                borderColor: '#EA5455',
                borderWidth: 1
            }
        ]
    },
    options: {
        ...commonChartOptions,
        scales: {
            ...commonChartOptions.scales,
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Yearly Revenue Chart
new Chart(document.getElementById('yearlyRevenueChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($monthly_data['labels']); ?>,
        datasets: [{
            label: 'Revenue',
            data: <?php echo json_encode($monthly_data['amounts']); ?>,
            borderColor: '#28C76F',
            backgroundColor: 'rgba(40, 199, 111, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₹' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Revenue: ₹' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});

// GST Analysis Chart
const gstCtx = document.getElementById('gstAnalysisChart');
new Chart(gstCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($monthly_data['labels']); ?>,
        datasets: [
            {
                type: 'bar',
                label: 'GST Amount',
                data: <?php echo json_encode($monthly_data['gst_amounts']); ?>,
                backgroundColor: 'rgba(78, 84, 200, 0.2)',
                borderColor: '#4E54C8',
                borderWidth: 1,
                yAxisID: 'y'
            },
            {
                type: 'line',
                label: 'GST Trend',
                data: <?php echo json_encode($monthly_data['gst_amounts']); ?>,
                borderColor: '#FF9F43',
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                yAxisID: 'y'
            }
        ]
    },
    options: {
        ...commonChartOptions,
        scales: {
            ...commonChartOptions.scales,
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₹' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<!-- Add Chart.js Zoom Plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-zoom/2.0.1/chartjs-plugin-zoom.min.js"></script>

<style>
/* Add custom styles for tabs */
.nav-tabs {
    border-bottom: 2px solid #e9ecef;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    padding: 1rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
    border: none;
    color: #4E54C8;
}

.nav-tabs .nav-link.active {
    border: none;
    color: #4E54C8;
    position: relative;
}

.nav-tabs .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: #4E54C8;
}

/* Keep existing styles */
.chart-container {
    position: relative;
    height: 400px;
    width: 100%;
    overflow-x: auto;
}

.stats-card {
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
}

.card-header {
    background: white;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 1.5rem;
}

.card-body {
    padding: 1.5rem;
}

.form-select {
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,0.1);
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.form-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.bg-success {
    background-color: #28C76F !important;
}

.bg-warning {
    background-color: #FF9F43 !important;
}

.bg-danger {
    background-color: #EA5455 !important;
}

.legend {
    font-size: 0.875rem;
}

.badge {
    padding: 0.5em 0.75em;
    font-weight: 500;
}

.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
}

.table tbody tr:hover {
    background-color: rgba(78, 84, 200, 0.05);
}
</style>

<!-- Add pagination controls -->
<?php if ($total_pages > 1): ?>
<div class="d-flex justify-content-center mt-4">
    <nav aria-label="Invoice pagination">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?fy_year=<?php echo $selected_fy_start_year; ?>&month=<?php echo $selected_month; ?>&page=<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?> 