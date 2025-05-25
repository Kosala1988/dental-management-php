<?php
// get_receipt_preview.php - AJAX endpoint for receipt preview in modal
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    http_response_code(403);
    exit('Unauthorized');
}

include '../../includes/db_connect.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Payment ID required</div>');
}

$payment_id = intval($_GET['id']);

$query = "
    SELECT 
        pay.*,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
        p.file_number,
        p.phone,
        p.email,
        p.address,
        t.name AS treatment_name,
        t.code AS treatment_code,
        tr.diagnosis,
        tr.treatment_date,
        tr.cost AS treatment_cost,
        u.full_name AS received_by_name
    FROM payments pay
    JOIN treatment_records tr ON pay.record_id = tr.record_id
    JOIN patients p ON pay.patient_id = p.patient_id
    JOIN treatments t ON tr.treatment_id = t.treatment_id
    JOIN users u ON pay.received_by = u.user_id
    WHERE pay.payment_id = ?
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500);
    exit('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Database error: ' . $conn->error . '</div>');
}

$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit('<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Payment record not found</div>');
}

$payment = $result->fetch_assoc();

// Calculate remaining balance for this treatment
$balance_query = "
    SELECT 
        tr.cost,
        COALESCE(SUM(p.amount), 0) as total_paid,
        (tr.cost - COALESCE(SUM(p.amount), 0)) as remaining_balance
    FROM treatment_records tr
    LEFT JOIN payments p ON tr.record_id = p.record_id
    WHERE tr.record_id = ?
    GROUP BY tr.record_id, tr.cost
";

$balance_stmt = $conn->prepare($balance_query);
$balance_stmt->bind_param("i", $payment['record_id']);
$balance_stmt->execute();
$balance_result = $balance_stmt->get_result();
$balance_info = $balance_result->fetch_assoc();
?>

<div class="receipt-preview">
    <!-- Clinic Header -->
    <div class="text-center mb-4" style="border-bottom: 2px solid #4361ee; padding-bottom: 15px;">
        <h3 class="text-primary mb-1" style="font-weight: bold;">DenTec Dental Clinic</h3>
        <small class="text-muted">
            123 Dental Street, Healthcare City<br>
            Phone: (555) 123-4567 | Email: info@dentec.com
        </small>
    </div>

    <!-- Receipt Title -->
    <div class="text-center mb-4">
        <h4 class="badge bg-primary fs-6 px-3 py-2">PAYMENT RECEIPT</h4>
    </div>

    <!-- Receipt Information -->
    <div class="row mb-2">
        <div class="col-5">
            <strong>Receipt #:</strong>
        </div>
        <div class="col-7 text-end">
            <code>#<?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?></code>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-5">
            <strong>Date:</strong>
        </div>
        <div class="col-7 text-end">
            <?php echo date('M j, Y', strtotime($payment['payment_date'])); ?>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-5">
            <strong>Time:</strong>
        </div>
        <div class="col-7 text-end">
            <?php echo date('g:i A', strtotime($payment['created_at'])); ?>
        </div>
    </div>

    <!-- Patient Information -->
    <div class="bg-light p-3 rounded mb-3">
        <h6 class="fw-bold mb-3 text-primary">
            <i class="bi bi-person-circle me-2"></i>Patient Information
        </h6>
        <div class="row mb-2">
            <div class="col-4"><strong>Name:</strong></div>
            <div class="col-8"><?php echo htmlspecialchars($payment['patient_name']); ?></div>
        </div>
        <div class="row mb-2">
            <div class="col-4"><strong>Patient ID:</strong></div>
            <div class="col-8"><code><?php echo htmlspecialchars($payment['file_number']); ?></code></div>
        </div>
        <?php if ($payment['phone']): ?>
        <div class="row">
            <div class="col-4"><strong>Phone:</strong></div>
            <div class="col-8"><?php echo htmlspecialchars($payment['phone']); ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Treatment Information -->
    <div class="bg-light p-3 rounded mb-3">
        <h6 class="fw-bold mb-3 text-primary">
            <i class="bi bi-clipboard2-pulse me-2"></i>Treatment Information
        </h6>
        <div class="row mb-2">
            <div class="col-4"><strong>Treatment:</strong></div>
            <div class="col-8"><?php echo htmlspecialchars($payment['treatment_name']); ?></div>
        </div>
        <div class="row mb-2">
            <div class="col-4"><strong>Code:</strong></div>
            <div class="col-8"><span class="badge bg-secondary"><?php echo htmlspecialchars($payment['treatment_code']); ?></span></div>
        </div>
        <div class="row mb-2">
            <div class="col-4"><strong>Date:</strong></div>
            <div class="col-8"><?php echo date('M j, Y', strtotime($payment['treatment_date'])); ?></div>
        </div>
        <div class="row">
            <div class="col-4"><strong>Diagnosis:</strong></div>
            <div class="col-8"><small><?php echo htmlspecialchars($payment['diagnosis']); ?></small></div>
        </div>
    </div>

    <!-- Payment Information -->
    <div class="bg-success bg-opacity-10 p-3 rounded mb-3 border border-success border-opacity-25">
        <h6 class="fw-bold mb-3 text-success">
            <i class="bi bi-credit-card me-2"></i>Payment Details
        </h6>
        
        <div class="row mb-2">
            <div class="col-6"><strong>Treatment Cost:</strong></div>
            <div class="col-6 text-end">$<?php echo number_format($payment['treatment_cost'], 2); ?></div>
        </div>
        
        <div class="row mb-2">
            <div class="col-6"><strong>Total Paid:</strong></div>
            <div class="col-6 text-end text-success"><strong>$<?php echo number_format($balance_info['total_paid'], 2); ?></strong></div>
        </div>
        
        <?php if ($balance_info['remaining_balance'] > 0): ?>
        <div class="row mb-3">
            <div class="col-6"><strong>Remaining Balance:</strong></div>
            <div class="col-6 text-end text-warning"><strong>$<?php echo number_format($balance_info['remaining_balance'], 2); ?></strong></div>
        </div>
        <?php else: ?>
        <div class="row mb-3">
            <div class="col-6"><strong>Status:</strong></div>
            <div class="col-6 text-end"><span class="badge bg-success">PAID IN FULL</span></div>
        </div>
        <?php endif; ?>
        
        <hr class="my-3">
        
        <!-- This Payment Details -->
        <div class="bg-white p-3 rounded border">
            <div class="row mb-2">
                <div class="col-6"><strong>Payment Method:</strong></div>
                <div class="col-6 text-end">
                    <?php
                        $method_class = '';
                        $method_icon = '';
                        switch($payment['payment_method']) {
                            case 'Cash': 
                                $method_class = 'bg-success'; 
                                $method_icon = 'bi-cash-stack';
                                break;
                            case 'Credit Card':
                            case 'Debit Card': 
                                $method_class = 'bg-primary'; 
                                $method_icon = 'bi-credit-card';
                                break;
                            case 'Bank Transfer': 
                                $method_class = 'bg-info'; 
                                $method_icon = 'bi-bank';
                                break;
                            case 'Insurance': 
                                $method_class = 'bg-warning'; 
                                $method_icon = 'bi-shield-check';
                                break;
                            default: 
                                $method_class = 'bg-secondary';
                                $method_icon = 'bi-question-circle';
                        }
                    ?>
                    <span class="badge <?php echo $method_class; ?>">
                        <i class="<?php echo $method_icon; ?> me-1"></i>
                        <?php echo htmlspecialchars($payment['payment_method']); ?>
                    </span>
                </div>
            </div>
            
            <?php if ($payment['transaction_reference']): ?>
            <div class="row mb-2">
                <div class="col-6"><strong>Reference:</strong></div>
                <div class="col-6 text-end"><code><?php echo htmlspecialchars($payment['transaction_reference']); ?></code></div>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-6"><h5 class="text-success mb-0"><strong>THIS PAYMENT:</strong></h5></div>
                <div class="col-6 text-end">
                    <h4 class="text-success mb-0">
                        <strong>$<?php echo number_format($payment['amount'], 2); ?></strong>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes Section -->
    <?php if ($payment['notes']): ?>
    <div class="bg-light p-3 rounded mb-3">
        <h6 class="fw-bold mb-2 text-primary">
            <i class="bi bi-sticky me-2"></i>Payment Notes
        </h6>
        <p class="mb-0 text-muted"><em><?php echo nl2br(htmlspecialchars($payment['notes'])); ?></em></p>
    </div>
    <?php endif; ?>

    <!-- Staff Information -->
    <div class="row mb-3">
        <div class="col-6"><strong>Received By:</strong></div>
        <div class="col-6 text-end">
            <span class="badge bg-info">
                <i class="bi bi-person-check me-1"></i>
                <?php echo htmlspecialchars($payment['received_by_name']); ?>
            </span>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-4 pt-3" style="border-top: 1px solid #ddd;">
        <small class="text-muted">
            <p class="mb-1"><strong>Thank you for choosing DenTec Dental Clinic!</strong></p>
            <p class="mb-1">This is a computer-generated receipt.</p>
            <p class="mb-0">For any queries, please contact us at (555) 123-4567</p>
        </small>
    </div>
</div>

<style>
.receipt-preview {
    max-width: 500px;
    margin: 0 auto;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.4;
    font-size: 14px;
}

.receipt-preview .row {
    --bs-gutter-x: 0.5rem;
}

.receipt-preview h3, 
.receipt-preview h4, 
.receipt-preview h5, 
.receipt-preview h6 {
    margin-bottom: 0.5rem;
}

.receipt-preview .badge {
    font-size: 0.8em;
}

.receipt-preview code {
    background-color: #f1f3f4;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.9em;
}

.receipt-preview .border-success {
    border-width: 2px !important;
}

/* Animation for success highlight */
@keyframes highlightPayment {
    0% { background-color: rgba(40, 167, 69, 0.1); }
    50% { background-color: rgba(40, 167, 69, 0.2); }
    100% { background-color: rgba(40, 167, 69, 0.1); }
}

.receipt-preview .bg-success.bg-opacity-10 {
    animation: highlightPayment 2s ease-in-out;
}

@media print {
    .receipt-preview {
        max-width: none;
        margin: 0;
        font-size: 12px;
    }
    
    .receipt-preview .badge {
        border: 1px solid #000 !important;
        background: #f0f0f0 !important;
        color: #000 !important;
    }
}
</style>

<script>
// Set the payment ID in the modal for print function
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('receiptPreviewModal');
    if (modal) {
        modal.setAttribute('data-payment-id', '<?php echo $payment_id; ?>');
    }
    
    // Add some interactive features
    const receiptPreview = document.querySelector('.receipt-preview');
    if (receiptPreview) {
        receiptPreview.addEventListener('click', function(e) {
            // Add a subtle click effect
            this.style.transform = 'scale(0.99)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
        });
    }
});
</script>