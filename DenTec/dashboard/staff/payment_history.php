<?php
// payment.php - Main payment management page
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

include '../../includes/db_connect.php';
$staff_id = $_SESSION['user_id'];

// Function to safely execute queries with error handling
function executeQuery($conn, $query) {
    $result = $conn->query($query);
    if (!$result) {
        error_log("Query failed: " . $conn->error);
        return false;
    }
    return $result;
}

// Handle payment recording - CORRECTED VERSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    
    // Debug logging
    error_log("Payment POST data received: " . json_encode($_POST));
    
    // Initialize variables
    $error_message = '';
    $success_message = '';
    
    // Validate required fields
    $required_fields = ['record_id', 'patient_id', 'amount', 'payment_method', 'payment_date'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $error_message = "Missing required fields: " . implode(', ', $missing_fields);
        error_log("Payment validation error: " . $error_message);
    } else {
        // Sanitize and validate input
        $record_id = intval($_POST['record_id']);
        $patient_id = intval($_POST['patient_id']);
        $amount = floatval($_POST['amount']);
        $payment_method = trim($_POST['payment_method']);
        $payment_date = trim($_POST['payment_date']);
        $transaction_reference = isset($_POST['transaction_reference']) ? trim($_POST['transaction_reference']) : null;
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
        
        // Additional validation
        if ($record_id <= 0) {
            $error_message = "Invalid record ID";
        } elseif ($patient_id <= 0) {
            $error_message = "Invalid patient ID";
        } elseif ($amount <= 0) {
            $error_message = "Amount must be greater than 0";
        } elseif (!in_array($payment_method, ['Cash', 'Credit Card', 'Debit Card', 'Bank Transfer', 'Insurance', 'Other'])) {
            $error_message = "Invalid payment method: " . $payment_method;
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $payment_date)) {
            $error_message = "Invalid payment date format. Expected YYYY-MM-DD";
        } else {
            // Validate the date is not in the future
            $date_obj = DateTime::createFromFormat('Y-m-d', $payment_date);
            if (!$date_obj || $date_obj > new DateTime()) {
                $error_message = "Payment date cannot be in the future";
            } else {
                // Check if the treatment record exists and calculate balance
                $check_query = "
                    SELECT 
                        tr.record_id,
                        tr.cost,
                        tr.status,
                        COALESCE(SUM(p.amount), 0) as total_paid,
                        (tr.cost - COALESCE(SUM(p.amount), 0)) as remaining_balance
                    FROM treatment_records tr
                    LEFT JOIN payments p ON tr.record_id = p.record_id
                    WHERE tr.record_id = ? AND tr.patient_id = ?
                    GROUP BY tr.record_id, tr.cost, tr.status
                ";
                
                $check_stmt = $conn->prepare($check_query);
                if (!$check_stmt) {
                    $error_message = "Database prepare error: " . $conn->error;
                    error_log("Check statement prepare failed: " . $conn->error);
                } else {
                    $check_stmt->bind_param("ii", $record_id, $patient_id);
                    
                    if (!$check_stmt->execute()) {
                        $error_message = "Database execution error: " . $check_stmt->error;
                        error_log("Check statement execution failed: " . $check_stmt->error);
                    } else {
                        $result = $check_stmt->get_result();
                        
                        if ($result->num_rows === 0) {
                            $error_message = "Treatment record not found or does not belong to this patient";
                        } else {
                            $record_info = $result->fetch_assoc();
                            $remaining_balance = floatval($record_info['remaining_balance']);
                            
                            // Check if treatment is completed
                            if ($record_info['status'] !== 'Completed') {
                                $error_message = "Cannot record payment for incomplete treatment";
                            } elseif ($remaining_balance <= 0) {
                                $error_message = "This treatment is already fully paid";
                            } elseif ($amount > $remaining_balance) {
                                $error_message = "Payment amount ($" . number_format($amount, 2) . ") exceeds remaining balance ($" . number_format($remaining_balance, 2) . ")";
                            } else {
                                // All validations passed - insert the payment
                                $insert_query = "INSERT INTO payments (record_id, patient_id, amount, payment_method, payment_date, transaction_reference, notes, received_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                                
                                $insert_stmt = $conn->prepare($insert_query);
                                if (!$insert_stmt) {
                                    $error_message = "Database prepare error: " . $conn->error;
                                    error_log("Insert statement prepare failed: " . $conn->error);
                                } else {
                                    // Handle NULL values properly
                                    $insert_stmt->bind_param("iidssssi", 
                                        $record_id, 
                                        $patient_id, 
                                        $amount, 
                                        $payment_method, 
                                        $payment_date, 
                                        $transaction_reference, 
                                        $notes, 
                                        $staff_id
                                    );
                                    
                                    if ($insert_stmt->execute()) {
                                        $payment_id = $conn->insert_id;
                                        $success_message = "Payment of $" . number_format($amount, 2) . " recorded successfully! (Payment ID: #" . $payment_id . ")";
                                        
                                        // Log successful payment
                                        error_log("Payment recorded successfully: ID $payment_id, Record $record_id, Amount $amount, Method $payment_method");
                                        
                                        // Optional: Clear form data by redirecting
                                        // header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                                        // exit();
                                        
                                    } else {
                                        $error_message = "Failed to record payment: " . $insert_stmt->error;
                                        error_log("Payment insert failed: " . $insert_stmt->error);
                                    }
                                    $insert_stmt->close();
                                }
                            }
                        }
                    }
                    $check_stmt->close();
                }
            }
        }
    }
}

// Get pending payments (unpaid treatment records) - CORRECTED QUERY
$pending_payments_query = "
    SELECT 
        tr.record_id,
        tr.patient_id,
        tr.cost,
        tr.treatment_date,
        tr.diagnosis,
        tr.status,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
        p.file_number,
        t.name AS treatment_name,
        t.code AS treatment_code,
        COALESCE(SUM(pay.amount), 0) AS paid_amount,
        (tr.cost - COALESCE(SUM(pay.amount), 0)) AS balance
    FROM treatment_records tr
    INNER JOIN patients p ON tr.patient_id = p.patient_id
    INNER JOIN treatments t ON tr.treatment_id = t.treatment_id
    LEFT JOIN payments pay ON tr.record_id = pay.record_id
    WHERE tr.status = 'Completed'
    GROUP BY tr.record_id, tr.patient_id, tr.cost, tr.treatment_date, tr.diagnosis, tr.status, 
             p.first_name, p.last_name, p.file_number, t.name, t.code
    HAVING balance > 0.01
    ORDER BY tr.treatment_date DESC
";

$pending_payments = executeQuery($conn, $pending_payments_query);

// Get recent payments - CORRECTED QUERY
$recent_payments_query = "
    SELECT 
        pay.payment_id,
        pay.amount,
        pay.payment_method,
        pay.payment_date,
        pay.transaction_reference,
        pay.created_at,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
        p.file_number,
        t.name AS treatment_name,
        t.code AS treatment_code,
        u.full_name AS received_by_name
    FROM payments pay
    INNER JOIN treatment_records tr ON pay.record_id = tr.record_id
    INNER JOIN patients p ON pay.patient_id = p.patient_id
    INNER JOIN treatments t ON tr.treatment_id = t.treatment_id
    INNER JOIN users u ON pay.received_by = u.user_id
    ORDER BY pay.created_at DESC
    LIMIT 10
";

$recent_payments = executeQuery($conn, $recent_payments_query);

// Get payment statistics - CORRECTED QUERY
$payment_stats_query = "
    SELECT 
        COUNT(*) AS total_payments,
        COALESCE(SUM(amount), 0) AS total_amount,
        COALESCE(SUM(CASE WHEN payment_date = CURDATE() THEN amount ELSE 0 END), 0) AS today_total,
        COALESCE(SUM(CASE WHEN YEARWEEK(payment_date, 1) = YEARWEEK(CURDATE(), 1) THEN amount ELSE 0 END), 0) AS week_total,
        COALESCE(SUM(CASE WHEN MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE()) THEN amount ELSE 0 END), 0) AS month_total
    FROM payments
";

$payment_stats_result = executeQuery($conn, $payment_stats_query);
$stats = $payment_stats_result ? $payment_stats_result->fetch_assoc() : [
    'total_payments' => 0, 
    'total_amount' => 0, 
    'today_total' => 0, 
    'week_total' => 0, 
    'month_total' => 0
];

// Ensure all stats are numeric
$stats['total_payments'] = intval($stats['total_payments']);
$stats['total_amount'] = floatval($stats['total_amount']);
$stats['today_total'] = floatval($stats['today_total']);
$stats['week_total'] = floatval($stats['week_total']);
$stats['month_total'] = floatval($stats['month_total']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Management | DenTec</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #f94144;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --light-text: #6c757d;
        }
        
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--light-bg);
        }
        
        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, var(--secondary-color), var(--primary-color));
            color: #fff;
            padding: 20px 10px;
            position: fixed;
            width: 250px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .sidebar a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background-color: rgba(255,255,255,0.15);
            color: #fff;
            transform: translateX(5px);
        }
        
        .sidebar i {
            width: 24px;
            text-align: center;
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .main {
            margin-left: 250px;
            padding: 30px;
            background-color: var(--light-bg);
        }
        
        .card-stat {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        
        .card-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        
        .card-stat .card-title {
            font-size: 1rem;
            color: var(--light-text);
            margin-bottom: 0.5rem;
        }
        
        .card-stat h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0;
            color: var(--dark-text);
        }
        
        .card-stat .icon {
            font-size: 2.5rem;
            opacity: 0.15;
            position: absolute;
            right: 20px;
            top: 20px;
        }
        
        .card-primary { border-left: 5px solid var(--primary-color); }
        .card-success { border-left: 5px solid var(--success-color); }
        .card-warning { border-left: 5px solid var(--warning-color); }
        .card-info { border-left: 5px solid var(--accent-color); }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .payment-method-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .payment-cash { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
        .payment-card { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .payment-bank { background-color: rgba(102, 16, 242, 0.1); color: #6610f2; }
        .payment-insurance { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .payment-other { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; }
        
        .balance-amount {
            font-weight: 600;
            color: var(--danger-color);
        }
        
        .paid-amount {
            font-weight: 600;
            color: var(--success-color);
        }
        
        .modal-header {
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            color: white;
        }
        
        .loading {
            pointer-events: none;
            opacity: 0.6;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }
            .main {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <h5 class="text-white mb-4 ps-2"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Staff User'); ?></h5>
            <a href="index.php">
                <i class="bi bi-speedometer2"></i> 
                <span>Dashboard</span>
            </a>
            <a href="schedule_appointment.php">
                <i class="bi bi-calendar-check"></i> 
                <span>Appointments</span>
            </a>
            <a href="patient.php">
                <i class="bi bi-people"></i> 
                <span>Patients</span>
            </a>
            <a href="payment.php" class="active">
                <i class="bi bi-currency-dollar"></i> 
                <span>Payments</span>
            </a>
            <a href="#">
                <i class="bi bi-file-earmark-text"></i> 
                <span>Reports</span>
            </a>
            <a href="#">
                <i class="bi bi-gear"></i> 
                <span>Settings</span>
            </a>
            <a href="../../logout.php" class="mt-auto">
                <i class="bi bi-box-arrow-right"></i> 
                <span>Logout</span>
            </a>
        </div>

        <!-- Main Content -->
        <div class="main flex-fill">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">Payment Management</h2>
                    <p class="text-muted mb-0">Record payments and track financial transactions</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                    <i class="bi bi-plus-circle me-2"></i>Record Payment
                </button>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($success_message) && !empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message) && !empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Payment Statistics -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card card-stat card-success">
                        <div class="card-body position-relative">
                            <h5 class="card-title">Today's Payments</h5>
                            <h2>LKR<?php echo number_format($stats['today_total'], 2); ?></h2>
                            <p class="text-muted mb-0"><small>Revenue today</small></p>
                            <i class="bi bi-currency-dollar icon"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card card-stat card-info">
                        <div class="card-body position-relative">
                            <h5 class="card-title">This Week</h5>
                            <h2>LKR<?php echo number_format($stats['week_total'], 2); ?></h2>
                            <p class="text-muted mb-0"><small>Weekly revenue</small></p>
                            <i class="bi bi-calendar-week icon"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card card-stat card-primary">
                        <div class="card-body position-relative">
                            <h5 class="card-title">This Month</h5>
                            <h2>LKR<?php echo number_format($stats['month_total'], 2); ?></h2>
                            <p class="text-muted mb-0"><small>Monthly revenue</small></p>
                            <i class="bi bi-calendar-month icon"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card card-stat card-warning">
                        <div class="card-body position-relative">
                            <h5 class="card-title">Total Payments</h5>
                            <h2><?php echo number_format($stats['total_payments']); ?></h2>
                            <p class="text-muted mb-0"><small>All time transactions</small></p>
                            <i class="bi bi-receipt icon"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Payments -->
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0">Outstanding Payments</h5>
                    <span class="badge bg-warning">
                        <?php echo $pending_payments ? $pending_payments->num_rows : 0; ?> Pending
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Patient</th>
                                <th>Treatment</th>
                                <th>Date</th>
                                <th>Total Cost</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pending_payments && $pending_payments->num_rows > 0): ?>
                                <?php while ($row = $pending_payments->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($row['patient_name']); ?></strong>
                                                <small class="text-muted d-block">ID: <?php echo htmlspecialchars($row['file_number']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($row['treatment_name']); ?></strong>
                                                <small class="text-muted d-block"><?php echo htmlspecialchars($row['diagnosis']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($row['treatment_date'])); ?></td>
                                        <td>$<?php echo number_format($row['cost'], 2); ?></td>
                                        <td><span class="paid-amount">$<?php echo number_format($row['paid_amount'], 2); ?></span></td>
                                        <td><span class="balance-amount">$<?php echo number_format($row['balance'], 2); ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="openPaymentModal(<?php echo $row['record_id']; ?>, <?php echo $row['patient_id']; ?>, '<?php echo htmlspecialchars($row['patient_name'], ENT_QUOTES); ?>', <?php echo $row['balance']; ?>)">
                                                <i class="bi bi-currency-dollar me-1"></i>Pay
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-check-circle" style="font-size: 2rem; opacity: 0.3;"></i>
                                        <p class="mt-2">No outstanding payments</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0">Recent Payments</h5>
                    <a href="payment_history.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-clock-history me-1"></i>View All
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Treatment</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Received By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_payments && $recent_payments->num_rows > 0): ?>
                                <?php while ($row = $recent_payments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($row['payment_date'])); ?></td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($row['patient_name']); ?></strong>
                                                <small class="text-muted d-block"><?php echo htmlspecialchars($row['file_number']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['treatment_name']); ?></td>
                                        <td><span class="paid-amount">$<?php echo number_format($row['amount'], 2); ?></span></td>
                                        <td>
                                            <?php
                                                $method_class = '';
                                                switch($row['payment_method']) {
                                                    case 'Cash': $method_class = 'payment-cash'; break;
                                                    case 'Credit Card':
                                                    case 'Debit Card': $method_class = 'payment-card'; break;
                                                    case 'Bank Transfer': $method_class = 'payment-bank'; break;
                                                    case 'Insurance': $method_class = 'payment-insurance'; break;
                                                    default: $method_class = 'payment-other';
                                                }
                                            ?>
                                            <span class="payment-method-badge <?php echo $method_class; ?>">
                                                <?php echo htmlspecialchars($row['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['transaction_reference'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['received_by_name']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-receipt" style="font-size: 2rem; opacity: 0.3;"></i>
                                        <p class="mt-2">No recent payments</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Record Payment Modal -->
    <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-labelledby="recordPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recordPaymentModalLabel">
                        <i class="bi bi-currency-dollar me-2"></i>Record Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="paymentForm" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="record_payment">
                        <input type="hidden" name="record_id" id="payment_record_id">
                        <input type="hidden" name="patient_id" id="payment_patient_id">
                        
                        <div class="mb-3">
                            <label for="payment_patient_name" class="form-label">Patient</label>
                            <input type="text" class="form-control" id="payment_patient_name" readonly>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="amount" id="payment_amount" 
                                               step="0.01" min="0.01" required>
                                        <div class="invalid-feedback">
                                            Please enter a valid amount.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="payment_date" id="payment_date" 
                                           value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                                    <div class="invalid-feedback">
                                        Please select a valid payment date.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_method" id="payment_method" required>
                                <option value="">Select payment method</option>
                                <option value="Cash">Cash</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Debit Card">Debit Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Insurance">Insurance</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a payment method.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="transaction_reference" class="form-label">Transaction Reference</label>
                            <input type="text" class="form-control" name="transaction_reference" id="transaction_reference" 
                                   placeholder="Check number, transaction ID, etc." maxlength="100">
                            <small class="form-text text-muted">Optional - Check number, card transaction ID, etc.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_notes" class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="payment_notes" rows="3" 
                                      placeholder="Additional notes..." maxlength="500"></textarea>
                            <small class="form-text text-muted">Optional additional information</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="submitPaymentBtn">
                            <i class="bi bi-check-circle me-2"></i>Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let paymentModal;
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            paymentModal = new bootstrap.Modal(document.getElementById('recordPaymentModal'));
            
            // Form validation
            const paymentForm = document.getElementById('paymentForm');
            if (paymentForm) {
                paymentForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    if (validatePaymentForm()) {
                        // Show loading state
                        const submitBtn = document.getElementById('submitPaymentBtn');
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
                        submitBtn.disabled = true;
                        
                        // Submit the form
                        this.submit();
                    }
                    
                    paymentForm.classList.add('was-validated');
                });
            }
            
            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
        
        // Function to open payment modal
        function openPaymentModal(recordId, patientId, patientName, balance) {
            console.log('Opening payment modal:', {
                recordId: recordId,
                patientId: patientId,
                patientName: patientName,
                balance: balance
            });
            
            // Validate inputs
            if (!recordId || !patientId || !patientName || balance === undefined) {
                alert('Invalid payment data. Please refresh the page and try again.');
                return;
            }
            
            // Reset form
            document.getElementById('paymentForm').reset();
            document.getElementById('paymentForm').classList.remove('was-validated');
            
            // Set form values
            document.getElementById('payment_record_id').value = recordId;
            document.getElementById('payment_patient_id').value = patientId;
            document.getElementById('payment_patient_name').value = patientName;
            document.getElementById('payment_amount').value = parseFloat(balance).toFixed(2);
            document.getElementById('payment_date').value = new Date().toISOString().split('T')[0];
            
            // Reset submit button
            const submitBtn = document.getElementById('submitPaymentBtn');
            submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Record Payment';
            submitBtn.disabled = false;
            
            // Show modal
            paymentModal.show();
        }
        
        // Form validation function
        function validatePaymentForm() {
            let isValid = true;
            
            // Validate amount
            const amount = parseFloat(document.getElementById('payment_amount').value);
            if (isNaN(amount) || amount <= 0) {
                isValid = false;
            }
            
            // Validate payment method
            const paymentMethod = document.getElementById('payment_method').value;
            if (!paymentMethod) {
                isValid = false;
            }
            
            // Validate payment date
            const paymentDate = document.getElementById('payment_date').value;
            if (!paymentDate) {
                isValid = false;
            } else {
                const selectedDate = new Date(paymentDate);
                const today = new Date();
                today.setHours(23, 59, 59, 999); // End of today
                
                if (selectedDate > today) {
                    alert('Payment date cannot be in the future.');
                    isValid = false;
                }
            }
            
            // Validate required fields exist
            const recordId = document.getElementById('payment_record_id').value;
            const patientId = document.getElementById('payment_patient_id').value;
            
            if (!recordId || !patientId) {
                alert('Missing required payment information. Please close and reopen the modal.');
                isValid = false;
            }
            
            return isValid;
        }
        
        // Format currency inputs
        document.addEventListener('input', function(e) {
            if (e.target.id === 'payment_amount') {
                let value = e.target.value;
                // Remove any non-numeric characters except decimal point
                value = value.replace(/[^0-9.]/g, '');
                
                // Ensure only one decimal point
                const parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }
                
                // Limit to 2 decimal places
                if (parts[1] && parts[1].length > 2) {
                    value = parts[0] + '.' + parts[1].substring(0, 2);
                }
                
                e.target.value = value;
            }
        });
        
        // Prevent form submission on Enter key in amount field (to avoid accidental submissions)
        document.getElementById('payment_amount').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('payment_method').focus();
            }
        });
        
        // Clear modal on close
        document.getElementById('recordPaymentModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('paymentForm').reset();
            document.getElementById('paymentForm').classList.remove('was-validated');
        });
        
        // Console logging for debugging
        console.log('Payment management system loaded successfully');
    </script>
</body>
</html>