<?php
session_start();
require_once 'config/database.php';
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM company_master WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();
$stmt->close();

// Define available templates and their sections
$template_types = [
    'invoice' => 'Invoice Email Template',
    'reminder' => 'Payment Reminder Template'
];

// Define HTML designs for each template type
$html_designs = [
    'invoice' => '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { background: #6366f1; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8fafc; }
        .invoice-details { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #6366f1; }
        .footer { background: #f1f5f9; padding: 15px; text-align: center; font-size: 12px; }
        .company-info { color: #4f46e5; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Invoice #{{INVOICE_NUMBER}}</h2>
    </div>
    <div class="content">
        {{GREETING}}
        <p>{{MAIN_CONTENT}}</p>
        <div class="invoice-details">
            <p><strong>Invoice Number:</strong> {{INVOICE_NUMBER}}</p>
            <p><strong>Date:</strong> {{INVOICE_DATE}}</p>
            <p><strong>Amount:</strong> Rs. {{INVOICE_AMOUNT}}</p>
        </div>
        <div class="company-info">
            <p>For any queries regarding this invoice, please contact us:</p>
            <p><strong>{{COMPANY_NAME}}</strong></p>
            <p>Phone: {{COMPANY_PHONE}}</p>
            <p>Email: {{COMPANY_EMAIL}}</p>
        </div>
    </div>
    <div class="footer">
        &copy; {{CURRENT_YEAR}} {{COMPANY_NAME}}. All rights reserved.
    </div>
</body>
</html>',
    'reminder' => '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { background: #dc2626; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8fafc; }
        .reminder-details { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #dc2626; }
        .footer { background: #f1f5f9; padding: 15px; text-align: center; font-size: 12px; }
        .company-info { color: #b91c1c; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Payment Reminder</h2>
    </div>
    <div class="content">
        {{GREETING}}
        <p>{{MAIN_CONTENT}}</p>
        <div class="reminder-details">
            <p><strong>Invoice Number:</strong> {{INVOICE_NUMBER}}</p>
            <p><strong>Due Date:</strong> {{DUE_DATE}}</p>
            <p><strong>Days Overdue:</strong> {{DAYS_OVERDUE}}</p>
            <p><strong>Amount Due:</strong> Rs. {{INVOICE_AMOUNT}}</p>
        </div>
        <div class="company-info">
            <p>For any queries, please contact us:</p>
            <p><strong>{{COMPANY_NAME}}</strong></p>
            <p>Phone: {{COMPANY_PHONE}}</p>
            <p>Email: {{COMPANY_EMAIL}}</p>
        </div>
    </div>
    <div class="footer">
        &copy; {{CURRENT_YEAR}} {{COMPANY_NAME}}. All rights reserved.
    </div>
</body>
</html>'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $template_type = $_POST['template_type'];
        $template_content = [
            'subject' => $_POST['subject'],
            'greeting' => $_POST['greeting'],
            'main_content' => $_POST['main_content'],
            'signature' => $_POST['signature']
        ];

        // Store both content and design
        $stmt = $conn->prepare("
            INSERT INTO email_templates (company_id, template_type, template_content, design) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            template_content = VALUES(template_content),
            design = VALUES(design)
        ");
        
        $json_content = json_encode($template_content);
        $design = $html_designs[$template_type];
        
        $stmt->bind_param("isss", 
            $company['company_id'],
            $template_type,
            $json_content,
            $design
        );
        
        $stmt->execute();
        $stmt->close();

        $_SESSION['success'] = 'Email template saved successfully!';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error saving template: ' . $e->getMessage();
    }
}

// Get saved templates
$templates = [];
$stmt = $conn->prepare("SELECT template_type, template_content, design FROM email_templates WHERE company_id = ?");
$stmt->bind_param("i", $company['company_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $templates[$row['template_type']] = [
        'content' => json_decode($row['template_content'], true),
        'design' => $row['design']
    ];
}
$stmt->close();

// Default template sections
$default_sections = [
    'subject' => 'Invoice #{{INVOICE_NUMBER}} from {{COMPANY_NAME}}',
    'greeting' => 'Dear {{CLIENT_NAME}},',
    'main_content' => "Please find attached the invoice #{{INVOICE_NUMBER}} dated {{INVOICE_DATE}} for amount {{INVOICE_AMOUNT}}.\n\nIf you have any questions, please don't hesitate to contact us.",
    'signature' => "Best Regards,\n{{COMPANY_NAME}}\nPhone: {{COMPANY_PHONE}}\nEmail: {{COMPANY_EMAIL}}"
];

?>

<div class="container-fluid py-4">
    <div class="row align-items-center">
        <div class="col-lg-6">
            <h1 class="h3 mb-0">Email Templates</h1>
            <p class="text-muted mb-0">Customize your email templates</p>
        </div>
        <div class="col-lg-6 text-lg-end">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-lg-end mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Email Templates</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mt-4">
        <div class="col-md-3">
            <!-- Variables Panel -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Available Variables</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="fw-bold">Company Variables</h6>
                        <div class="list-group">
                            <button type="button" class="list-group-item list-group-item-action variable-btn" data-variable="{{COMPANY_NAME}}">
                                Company Name
                            </button>
                            <button type="button" class="list-group-item list-group-item-action variable-btn" data-variable="{{COMPANY_PHONE}}">
                                Company Phone
                            </button>
                            <button type="button" class="list-group-item list-group-item-action variable-btn" data-variable="{{COMPANY_EMAIL}}">
                                Company Email
                            </button>
                        </div>
                    </div>
                    <div>
                        <h6 class="fw-bold">Invoice Variables</h6>
                        <div class="list-group">
                            <button type="button" class="list-group-item list-group-item-action variable-btn" data-variable="{{INVOICE_NUMBER}}">
                                Invoice Number
                            </button>
                            <button type="button" class="list-group-item list-group-item-action variable-btn" data-variable="{{INVOICE_DATE}}">
                                Invoice Date
                            </button>
                            <button type="button" class="list-group-item list-group-item-action variable-btn" data-variable="{{INVOICE_AMOUNT}}">
                                Invoice Amount
                            </button>
                            <button type="button" class="list-group-item list-group-item-action variable-btn" data-variable="{{CLIENT_NAME}}">
                                Client Name
                            </button>
                            <button type="button" class="list-group-item list-group-item-action variable-btn" data-variable="{{DUE_DATE}}">
                                Due Date
                            </button>
                            <button type="button" class="list-group-item list-group-item-action variable-btn" data-variable="{{DAYS_OVERDUE}}">
                                Days Overdue
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" id="templateForm">
                        <div class="mb-3">
                            <label class="form-label">Template Type</label>
                            <select name="template_type" id="template_type" class="form-select">
                                <?php foreach ($template_types as $type => $label): ?>
                                    <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <div class="input-group">
                                <input type="text" name="subject" id="subject" class="form-control template-field">
                                <button type="button" class="btn btn-outline-secondary reset-btn" data-field="subject">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Greeting</label>
                            <div class="input-group">
                                <textarea name="greeting" id="greeting" rows="2" class="form-control template-field"></textarea>
                                <button type="button" class="btn btn-outline-secondary reset-btn" data-field="greeting">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Main Content</label>
                            <div class="input-group">
                                <textarea name="main_content" id="main_content" rows="4" class="form-control template-field"></textarea>
                                <button type="button" class="btn btn-outline-secondary reset-btn" data-field="main_content">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Signature</label>
                            <div class="input-group">
                                <textarea name="signature" id="signature" rows="3" class="form-control template-field"></textarea>
                                <button type="button" class="btn btn-outline-secondary reset-btn" data-field="signature">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Template
                            </button>
                            <button type="button" class="btn btn-success" onclick="previewTemplate()">
                                <i class="fas fa-eye me-2"></i>Preview
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetToDefault()">
                                <i class="fas fa-undo me-2"></i>Reset All
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Email Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewFrame" class="bg-light p-3 rounded"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Load saved template on type change
    const templates = <?php echo json_encode($templates); ?>;
    const defaultSections = <?php echo json_encode($default_sections); ?>;
    const htmlDesigns = <?php echo json_encode($html_designs); ?>;

    document.getElementById('template_type').addEventListener('change', loadTemplate);

    // Handle variable insertion
    document.querySelectorAll('.variable-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const variable = this.dataset.variable;
            const activeElement = document.activeElement;
            
            if (activeElement && activeElement.classList.contains('template-field')) {
                const start = activeElement.selectionStart;
                const end = activeElement.selectionEnd;
                const text = activeElement.value;
                
                activeElement.value = text.substring(0, start) + variable + text.substring(end);
                activeElement.focus();
                activeElement.setSelectionRange(start + variable.length, start + variable.length);
            } else {
                // If no field is focused, insert into main content
                const mainContent = document.getElementById('main_content');
                const curPos = mainContent.selectionStart;
                mainContent.value = mainContent.value.slice(0, curPos) + variable + mainContent.value.slice(curPos);
                mainContent.focus();
            }
        });
    });

    // Handle individual field reset
    document.querySelectorAll('.reset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const field = this.dataset.field;
            document.getElementById(field).value = defaultSections[field];
        });
    });

    function loadTemplate() {
        const type = document.getElementById('template_type').value;
        const template = templates[type] || { content: defaultSections };
        
        document.getElementById('subject').value = template.content.subject || defaultSections.subject;
        document.getElementById('greeting').value = template.content.greeting || defaultSections.greeting;
        document.getElementById('main_content').value = template.content.main_content || defaultSections.main_content;
        document.getElementById('signature').value = template.content.signature || defaultSections.signature;
    }

    function resetToDefault() {
        document.getElementById('subject').value = defaultSections.subject;
        document.getElementById('greeting').value = defaultSections.greeting;
        document.getElementById('main_content').value = defaultSections.main_content;
        document.getElementById('signature').value = defaultSections.signature;
    }

    function previewTemplate() {
        const type = document.getElementById('template_type').value;
        const design = htmlDesigns[type];
        
        // Sample data for preview
        const previewData = {
            '{{COMPANY_NAME}}': '<?php echo htmlspecialchars($company['name']); ?>',
            '{{COMPANY_PHONE}}': '<?php echo htmlspecialchars($company['phone']); ?>',
            '{{COMPANY_EMAIL}}': '<?php echo htmlspecialchars($company['email']); ?>',
            '{{INVOICE_NUMBER}}': '123456',
            '{{INVOICE_DATE}}': '<?php echo date('d/m/Y'); ?>',
            '{{INVOICE_AMOUNT}}': '1,000.00',
            '{{CLIENT_NAME}}': 'Sample Client',
            '{{DUE_DATE}}': '<?php echo date('d/m/Y', strtotime('+30 days')); ?>',
            '{{DAYS_OVERDUE}}': '0',
            '{{CURRENT_YEAR}}': '<?php echo date('Y'); ?>',
            '{{GREETING}}': document.getElementById('greeting').value,
            '{{MAIN_CONTENT}}': document.getElementById('main_content').value
        };

        let previewHtml = design;
        for (const [key, value] of Object.entries(previewData)) {
            previewHtml = previewHtml.replace(new RegExp(key, 'g'), value);
        }

        document.getElementById('previewFrame').innerHTML = previewHtml;
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    }

    // Load initial template
    loadTemplate();
</script>

<?php include 'includes/footer.php'; ?>