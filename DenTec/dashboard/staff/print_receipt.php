<?php
// print_receipt.php - Complete Professional Invoice-Style Receipt for A5 Paper
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
        p.gender,
        p.date_of_birth,
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

// Calculate patient age
function calculateAge($date_of_birth) {
    if (!$date_of_birth) return 'N/A';
    $dob = new DateTime($date_of_birth);
    $now = new DateTime();
    $age = $now->diff($dob)->y;
    return $age . ' years';
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit('Payment not found');
}

$payment = $result->fetch_assoc();

// Calculate any remaining balance
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt #<?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* A5 Paper Layout with Margins */
        @page {
            size: A5;
            margin: 12mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            max-width: 148mm;
            margin: 0 auto;
            padding: 8mm;
        }
        
        /* Container without border */
        .receipt-container {
            padding: 8mm;
            min-height: auto;
            margin: 0;
        }
        
        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8mm;
        }
        
        .clinic-info {
            flex: 1;
        }
        
        .clinic-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .clinic-subtitle {
            font-size: 10px;
            margin-bottom: 4px;
        }
        
        .clinic-address {
            font-size: 12px;
            line-height: 1.2;
            color: #666;
        }
        
        .receipt-info {
            text-align: right;
            font-size: 9px;
        }
        
        .receipt-number {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        /* Bill To Section - Now Patient Info */
        .patient-info {
            margin-bottom: 6mm;
        }
        
        .patient-info-title {
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 12px;
        }
        
        .patient-details {
            font-size: 10px;
            line-height: 1.3;
        }
        
        .patient-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
        }
        
        .patient-label {
            font-weight: bold;
            width: 30%;
        }
        
        .patient-value {
            width: 70%;
        }
        
        /* Services Table */
        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6mm;
        }
        
        .services-table th,
        .services-table td {
            padding: 4px 6px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
        }
        
        .services-table th {
            font-weight: bold;
            color: #333;
        }
        
        .services-table .qty-col { width: 12%; text-align: center; }
        .services-table .price-col { width: 18%; text-align: right; }
        .services-table .desc-col { width: 52%; }
        .services-table .total-col { width: 18%; text-align: right; }
        
        /* Totals Section */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 8mm;
        }
        
        .totals-box {
            width: 50%;
            border: 1px solid #ddd;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 8px;
            border-bottom: 1px solid #eee;
            font-size: 9px;
        }
        
        .total-row:last-child {
            border-bottom: none;
            font-weight: bold;
        }
        
        .total-row.subtotal {
            font-weight: bold;
        }
        
        /* Payment Methods */
        .payment-methods {
            margin-bottom: 6mm;
        }
        
        .payment-methods-title {
            font-weight: bold;
            margin-bottom: 3mm;
            font-size: 10px;
        }
        
        .payment-method {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 9px;
        }
        
        .payment-method.paid {
            font-weight: bold;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 4mm;
            margin-top: 8mm;
        }
        
        /* Print Controls */
        .print-controls {
            text-align: center;
            margin-bottom: 5mm;
            padding: 3mm;
            background: #f0f0f0;
            border: 1px solid #ccc;
        }
        
        .btn {
            padding: 2mm 4mm;
            margin: 0 2mm;
            border: 1px solid #000;
            background: #fff;
            cursor: pointer;
            font-size: 10px;
        }
        
        .btn:hover {
            background: #e0e0e0;
        }
        
        /* Print Styles */
        @media print {
            .print-controls {
                display: none !important;
            }
            
            body {
                padding: 0;
                margin: 0;
                font-size: 18px;
            }
            
            .receipt-container {
                padding: 5mm;
                margin: 0;
            }
            
            .services-table th {
                font-weight: bold !important;
                color: #333 !important;
            }
            
            .total-row:last-child {
                font-weight: bold !important;
                font-size: 12px !important;
            }
            
            .header {
                margin-bottom: 6mm;
            }
            
            .patient-info {
                margin-bottom: 6mm;
            }
            
            .services-table {
                margin-bottom: 6mm;
            }
            
            .totals-section {
                margin-bottom: 6mm;
            }
            
            .footer {
                margin-top: 5mm;
                padding-top: 3mm;
                font-size: 8px;
            }
        }
        
        /* Mobile Responsive */
        @media screen and (max-width: 400px) {
            body {
                font-size: 12px;
                padding: 6mm;
            }
            
            .receipt-container {
                padding: 5mm;
                margin: 0;
            }
            
            .clinic-name {
                font-size: 12px;
            }
            
            .services-table th,
            .services-table td {
                padding: 3mm 4mm;
                font-size: 9px;
            }
        }
        
        /* Mobile Responsive */
        @media screen and (max-width: 400px) {
            body {
                font-size: 9px;
                padding: 4mm;
            }
            
            .receipt-container {
                padding: 6mm;
                margin: 1mm;
            }
            
            .clinic-name {
                font-size: 11px;
            }
            
            .services-table th,
            .services-table td {
                padding: 3px 4px;
                font-size: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Print Controls (Hidden when printing) -->
    <div class="print-controls">
        <button class="btn" onclick="window.print()">🖨️ PRINT</button>
        <button class="btn" onclick="window.close()">❌ CLOSE</button>
    </div>

    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            <div class="clinic-info">
                <div class="clinic-name">The Family Dentist</div>
                <div class="clinic-address">
                    441/A Maampe North, Colombo Road, Piliyandala<br>
                    Email: Contactthefamilydentist@gmail.com<br>
                    Web : www.visitthefamilydentist.com<br>
                    Hot Line: 071 078 33 22 | 070 277 66 22
                </div>
            </div>
            <div class="receipt-info">
                <div class="receipt-number">RECEIPT NO</div>
                <div><?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?></div>
                <div style="margin-top: 4mm;">
                    <div><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></div>
                    <div><?php echo date('H:i', strtotime($payment['created_at'])); ?></div>
                </div>
            </div>
        </div>

        <!-- Patient Information -->
        <div class="patient-info">
            <div class="patient-info-title">PATIENT INFORMATION:</div>
            <div class="patient-details">
                <div class="patient-row">
                    <span class="patient-label">Patient ID:</span>
                    <span class="patient-value"><?php echo htmlspecialchars($payment['file_number']); ?></span>
                </div>
                <div class="patient-row">
                    <span class="patient-label">Name:</span>
                    <span class="patient-value"><?php echo htmlspecialchars($payment['patient_name']); ?></span>
                </div>
                <div class="patient-row">
                    <span class="patient-label">Gender:</span>
                    <span class="patient-value"><?php echo htmlspecialchars($payment['gender'] ?: 'Not specified'); ?></span>
                </div>
                <div class="patient-row">
                    <span class="patient-label">Age:</span>
                    <span class="patient-value"><?php echo calculateAge($payment['date_of_birth']); ?></span>
                </div>
                <?php if ($payment['phone']): ?>
                <div class="patient-row">
                    <span class="patient-label">Phone:</span>
                    <span class="patient-value"><?php echo htmlspecialchars($payment['phone']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Services Table -->
        <table class="services-table">
            <thead>
                <tr>
                    <th class="qty-col">Qty</th>
                    <th class="price-col">Price</th>
                    <th class="desc-col">Description</th>
                    <th class="total-col">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="qty-col">1</td>
                    <td class="price-col">LKR <?php echo number_format($payment['treatment_cost'], 2); ?></td>
                    <td class="desc-col">
                        <?php echo htmlspecialchars($payment['treatment_name']); ?><br>
                        <small style="color: #666;">
                            Code: <?php echo htmlspecialchars($payment['treatment_code']); ?> | 
                            Date: <?php echo date('d/m/Y', strtotime($payment['treatment_date'])); ?>
                        </small>
                    </td>
                    <td class="total-col">LKR <?php echo number_format($payment['treatment_cost'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-box">
                <div class="total-row subtotal">
                    <span>Subtotal</span>
                    <span>LKR <?php echo number_format($payment['treatment_cost'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span><strong>Total:</strong></span>
                    <span><strong>LKR <?php echo number_format($payment['treatment_cost'], 2); ?></strong></span>
                </div>
            </div>
        </div>

        <?php if ($payment['notes']): ?>
        <!-- Notes Section -->
        <div style="margin-bottom: 6mm;">
            <div style="font-weight: bold; margin-bottom: 2mm; font-size: 10px;">NOTES:</div>
            <div style="font-size: 9px; color: #666; padding: 2mm; border: 1px solid #ddd;">
                <?php echo nl2br(htmlspecialchars($payment['notes'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            Thank you for choosing The Family  Dentist<br> 
            This is a computer-generated receipt.<br>
            For questions, please call  071 0783322 | 070 277 66 22
            <div style="margin-top: 3mm; font-size: 7px;">
                Staff: <?php echo htmlspecialchars($payment['received_by_name']); ?> | 
                Generated: <?php echo date('d/m/Y H:i'); ?>
                <?php if ($payment['transaction_reference']): ?>
                | Ref: <?php echo htmlspecialchars($payment['transaction_reference']); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-print functionality
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('auto_print') === '1') {
                setTimeout(function() {
                    window.print();
                }, 500);
            }
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl+P or Cmd+P for print
                if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                    e.preventDefault();
                    window.print();
                }
                
                // Escape key to close
                if (e.key === 'Escape') {
                    window.close();
                }
            });
            
            // Focus on window for keyboard shortcuts
            window.focus();
        });

        // Print event handlers
        window.addEventListener('beforeprint', function() {
            console.log('Printing receipt #<?php echo $payment['payment_id']; ?>');
            
            // Hide any potential error messages during print
            const alerts = document.querySelectorAll('.alert, .error, .warning');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        });

        window.addEventListener('afterprint', function() {
            console.log('Print completed for receipt #<?php echo $payment['payment_id']; ?>');
            
            // Optional: Auto-close after printing
            setTimeout(function() {
                if (confirm('Receipt printed successfully! Close this window?')) {
                    window.close();
                }
            }, 1000);
        });

        // Handle print errors
        window.addEventListener('error', function(e) {
            console.error('Print error:', e);
            alert('There was an error preparing the receipt for printing. Please try again.');
        });

        // Ensure proper loading
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                console.log('Receipt loaded successfully');
            });
        } else {
            console.log('Receipt ready for printing');
        }
    </script>
</body>
</html>