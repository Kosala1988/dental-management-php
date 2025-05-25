<?php
// get_payment_details.php - AJAX endpoint for payment details
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    http_response_code(403);
    exit('Unauthorized');
}

include '../../includes/db_connect.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Payment ID required');
}

$payment_id = intval($_GET['id']);

$query = "
    SELECT 
        pay.*,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
        p.file_number,
        p.phone,
        p.email,
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
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit('Payment not found');
}

$payment = $result->fetch_assoc();
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-muted mb-2">Patient Information</h6>
        <div class="mb-3">
            <strong><?php echo htmlspecialchars($payment['patient_name']); ?></strong><br>
            <small class="text-muted">ID: <?php echo $payment['file_number']; ?></small><br>
            <?php if ($payment['phone']): ?>
                <small class="text-muted">Phone: <?php echo htmlspecialchars($payment['phone']); ?></small><br>
            <?php endif; ?>
            <?php if ($payment['email']): ?>
                <small class="text-muted">Email: <?php echo htmlspecialchars($payment['email']); ?></small>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-6">
        <h6 class="text-muted mb-2">Payment Information</h6>
        <div class="mb-3">
            <strong>Payment ID: #<?php echo $payment['payment_id']; ?></strong><br>
            <small class="text-muted">Date: <?php echo date('F j, Y', strtotime($payment['payment_date'])); ?></small><br>
            <small class="text-muted">Time: <?php echo date('g:i A', strtotime($payment['created_at'])); ?></small><br>
            <small class="text-muted">Received by: <?php echo htmlspecialchars($payment['received_by_name']); ?></small>
        </div>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-8">
        <h6 class="text-muted mb-2">Treatment Details</h6>
        <div class="mb-3">
            <strong><?php echo htmlspecialchars($payment['treatment_name']); ?></strong> 
            <span class="badge bg-light text-dark ms-2"><?php echo $payment['treatment_code']; ?></span><br>
            <small class="text-muted">Date: <?php echo date('F j, Y', strtotime($payment['treatment_date'])); ?></small><br>
            <small class="text-muted">Diagnosis: <?php echo htmlspecialchars($payment['diagnosis']); ?></small>
        </div>
    </div>
    <div class="col-md-4">
        <h6 class="text-muted mb-2">Amount Breakdown</h6>
        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <span>Treatment Cost:</span>
                <span>$<?php echo number_format($payment['treatment_cost'], 2); ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <strong>Payment Amount:</strong>
                <strong class="text-success">$<?php echo number_format($payment['amount'], 2); ?></strong>
            </div>
        </div>
    </div>
</div>

<hr>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-muted mb-2">Payment Method</h6>
        <span class="badge bg-primary"><?php echo $payment['payment_method']; ?></span>
        <?php if ($payment['transaction_reference']): ?>
            <br><small class="text-muted mt-1">Reference: <?php echo htmlspecialchars($payment['transaction_reference']); ?></small>
        <?php endif; ?>
    </div>
    <?php if ($payment['notes']): ?>
    <div class="col-md-6">
        <h6 class="text-muted mb-2">Notes</h6>
        <p class="text-muted mb-0"><?php echo htmlspecialchars($payment['notes']); ?></p>
    </div>
    <?php endif; ?>
</div>

<script>
    document.getElementById('paymentDetailsModal').setAttribute('data-payment-id', '<?php echo $payment_id; ?>');
</script>

<?php
// print_receipt.php - Receipt printing page
if (isset($_GET['print']) && $_GET['print'] === '1') {
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
        http_response_code(403);
        exit('Unauthorized');
    }

    include '../../includes/db_connect.php';

    if (!isset($_GET['id'])) {
        http_response_code(400);
        exit('Payment ID required');
    }

    $payment_id = intval($_GET['id']);

    $query = "
        SELECT 
            pay.*,
            CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
            p.file_number,
            p.phone,
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
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        exit('Payment not found');
    }

    $payment = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt #<?php echo $payment['payment_id']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .clinic-name {
            font-size: 24px;
            font-weight: bold;
            color: #4361ee;
            margin-bottom: 5px;
        }
        .clinic-info {
            color: #666;
            font-size: 14px;
        }
        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #ccc;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #333;
        }
        .info-value {
            color: #666;
        }
        .amount-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .total-amount {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            text-align: center;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            color: #666;
            font-size: 12px;
        }
        @media print {
            body { margin: 0; padding: 15px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="background: #4361ee; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
            Print Receipt
        </button>
        <button onclick="window.close()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Close
        </button>
    </div>

    <div class="receipt-header">
        <div class="clinic-name">DenTec Dental Clinic</div>
        <div class="clinic-info">
            123 Dental Street, Healthcare City<br>
            Phone: (555) 123-4567 | Email: info@dentec.com
        </div>
    </div>

    <div class="receipt-title">PAYMENT RECEIPT</div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Receipt #:</span>
            <span class="info-value"><?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span class="info-value"><?php echo date('F j, Y', strtotime($payment['payment_date'])); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Time:</span>
            <span class="info-value"><?php echo date('g:i A', strtotime($payment['created_at'])); ?></span>
        </div>
    </div>

    <div class="info-section">
        <h4 style="margin-bottom: 10px; color: #333;">Patient Information</h4>
        <div class="info-row">
            <span class="info-label">Name:</span>
            <span class="info-value"><?php echo htmlspecialchars($payment['patient_name']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Patient ID:</span>
            <span class="info-value"><?php echo $payment['file_number']; ?></span>
        </div>
        <?php if ($payment['phone']): ?>
        <div class="info-row">
            <span class="info-label">Phone:</span>
            <span class="info-value"><?php echo htmlspecialchars($payment['phone']); ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="info-section">
        <h4 style="margin-bottom: 10px; color: #333;">Treatment Information</h4>
        <div class="info-row">
            <span class="info-label">Treatment:</span>
            <span class="info-value"><?php echo htmlspecialchars($payment['treatment_name']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Code:</span>
            <span class="info-value"><?php echo $payment['treatment_code']; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Treatment Date:</span>
            <span class="info-value"><?php echo date('F j, Y', strtotime($payment['treatment_date'])); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Diagnosis:</span>
            <span class="info-value"><?php echo htmlspecialchars($payment['diagnosis']); ?></span>
        </div>
    </div>

    <div class="amount-section">
        <div class="info-row">
            <span class="info-label">Treatment Cost:</span>
            <span class="info-value">$<?php echo number_format($payment['treatment_cost'], 2); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Payment Method:</span>
            <span class="info-value"><?php echo $payment['payment_method']; ?></span>
        </div>
        <?php if ($payment['transaction_reference']): ?>
        <div class="info-row">
            <span class="info-label">Reference:</span>
            <span class="info-value"><?php echo htmlspecialchars($payment['transaction_reference']); ?></span>
        </div>
        <?php endif; ?>
        <hr style="margin: 15px 0;">
        <div class="total-amount">
            AMOUNT PAID: $<?php echo number_format($payment['amount'], 2); ?>
        </div>
    </div>

    <?php if ($payment['notes']): ?>
    <div class="info-section">
        <h4 style="margin-bottom: 10px; color: #333;">Notes</h4>
        <p style="color: #666; margin: 0;"><?php echo htmlspecialchars($payment['notes']); ?></p>
    </div>
    <?php endif; ?>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Received By:</span>
            <span class="info-value"><?php echo htmlspecialchars($payment['received_by_name']); ?></span>
        </div>
    </div>

    <div class="footer">
        <p>Thank you for choosing DenTec Dental Clinic!</p>
        <p>This is a computer-generated receipt and does not require a signature.</p>
        <p>For any queries, please contact us at (555) 123-4567</p>
    </div>

    <script>
        // Auto-print when page loads if coming from print button
        if (window.location.search.includes('auto_print=1')) {
            window.onload = function() {
                window.print();
            };
        }
    </script>
</body>
</html>

<?php
}

// export_payments.php - Export payments to CSV
if (isset($_GET['export']) && $_GET['export'] === '1') {
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
        http_response_code(403);
        exit('Unauthorized');
    }

    include '../../includes/db_connect.php';

    // Get the same filters from payment_history.php
    $search = $_GET['search'] ?? '';
    $payment_method = $_GET['payment_method'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';

    // Build WHERE clause (same as payment_history.php)
    $where_conditions = [];
    $where_params = [];

    if (!empty($search)) {
        $where_conditions[] = "(CONCAT(p.first_name, ' ', p.last_name) LIKE ? OR p.file_number LIKE ? OR t.name LIKE ?)";
        $search_param = '%' . $search . '%';
        $where_params = array_merge($where_params, [$search_param, $search_param, $search_param]);
    }

    if (!empty($payment_method)) {
        $where_conditions[] = "pay.payment_method = ?";
        $where_params[] = $payment_method;
    }

    if (!empty($date_from)) {
        $where_conditions[] = "pay.payment_date >= ?";
        $where_params[] = $date_from;
    }

    if (!empty($date_to)) {
        $where_conditions[] = "pay.payment_date <= ?";
        $where_params[] = $date_to;
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    $export_query = "
        SELECT 
            pay.payment_id,
            pay.payment_date,
            pay.amount,
            pay.payment_method,
            pay.transaction_reference,
            pay.notes,
            CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
            p.file_number,
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
        $where_clause
        ORDER BY pay.payment_date DESC, pay.created_at DESC
    ";

    $stmt = $conn->prepare($export_query);
    if (!empty($where_params)) {
        $types = str_repeat('s', count($where_params));
        $stmt->bind_param($types, ...$where_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Set headers for CSV download
    $filename = 'payments_export_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    // Create CSV output
    $output = fopen('php://output', 'w');

    // Write CSV headers
    fputcsv($output, [
        'Payment ID',
        'Payment Date',
        'Amount',
        'Payment Method',
        'Transaction Reference',
        'Patient Name',
        'Patient ID',
        'Treatment Name',
        'Treatment Code',
        'Diagnosis',
        'Treatment Date',
        'Treatment Cost',
        'Received By',
        'Notes'
    ]);

    // Write data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['payment_id'],
            $row['payment_date'],
            $row['amount'],
            $row['payment_method'],
            $row['transaction_reference'] ?? '',
            $row['patient_name'],
            $row['file_number'],
            $row['treatment_name'],
            $row['treatment_code'],
            $row['diagnosis'],
            $row['treatment_date'],
            $row['treatment_cost'],
            $row['received_by_name'],
            $row['notes'] ?? ''
        ]);
    }

    fclose($output);
    exit();
}
?>