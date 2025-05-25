<?php
// thermal_receipt.php - Ultra Compact Thermal Style Receipt
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
    <title>Receipt <?php echo $payment['payment_id']; ?></title>
    <style>
        /* Thermal Receipt Style - Ultra Compact */
        @page {
            size: 80mm 200mm;
            margin: 2mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.1;
            color: #000;
            background: #fff;
            width: 76mm;
            margin: 0 auto;
            padding: 2mm;
        }
        
        /* Center align text helper */
        .center { text-align: center; }
        .left { text-align: left; }
        .right { text-align: right; }
        
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 3mm;
        }
        
        .clinic-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 1mm;
        }
        
        .clinic-info {
            font-size: 10px;
            line-height: 1.2;
        }
        
        /* Divider */
        .divider {
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }
        
        /* Receipt number */
        .receipt-no {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 2mm 0;
        }
        
        /* Info lines */
        .info-line {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
            font-size: 11px;
        }
        
        .info-line .label {
            font-weight: bold;
        }
        
        /* Two column layout for compact info */
        .two-col {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
        }
        
        .col {
            width: 48%;
            font-size: 10px;
        }
        
        /* Amount highlight */
        .amount-box {
            text-align: center;
            border: 1px solid #000;
            padding: 2mm;
            margin: 3mm 0;
            font-weight: bold;
        }
        
        .amount-large {
            font-size: 18px;
            font-weight: bold;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            font-size: 9px;
            margin-top: 3mm;
        }
        
        /* Print button */
        .print-btn {
            text-align: center;
            margin: 3mm 0;
            padding: 2mm;
            background: #f0f0f0;
            border: 1px solid #ccc;
        }
        
        .btn {
            padding: 2mm 4mm;
            margin: 0 1mm;
            font-size: 10px;
            cursor: pointer;
        }
        
        /* Hide print controls when printing */
        @media print {
            .print-btn { display: none !important; }
            body { font-size: 11px; }
        }
    </style>
</head>
<body>
    <!-- Print Controls -->
    <div class="print-btn">
        <button class="btn" onclick="window.print()">PRINT</button>
        <button class="btn" onclick="window.close()">CLOSE</button>
    </div>

    <!-- Receipt Header -->
    <div class="header">
        <div class="clinic-name">DENTEC CLINIC</div>
        <div class="clinic-info">
            123 Dental Street<br>
            Tel: (555) 123-4567
        </div>
    </div>

    <div class="divider"></div>

    <!-- Receipt Number -->
    <div class="receipt-no">RECEIPT #<?php echo str_pad($payment['payment_id'], 4, '0', STR_PAD_LEFT); ?></div>

    <!-- Date & Time -->
    <div class="info-line">
        <span><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></span>
        <span>Staff: <?php echo substr($payment['received_by_name'], 0, 10); ?></span>
    </div>

    <div class="divider"></div>

    <!-- Patient Info -->
    <div class="info-line">
        <span class="label">PATIENT:</span>
        <span><?php echo htmlspecialchars($payment['patient_name']); ?></span>
    </div>
    <div class="info-line">
        <span class="label">ID:</span>
        <span><?php echo htmlspecialchars($payment['file_number']); ?></span>
    </div>
    <?php if ($payment['phone']): ?>
    <div class="info-line">
        <span class="label">Phone:</span>
        <span><?php echo htmlspecialchars($payment['phone']); ?></span>
    </div>
    <?php endif; ?>

    <div class="divider"></div>

    <!-- Treatment Info -->
    <div class="info-line">
        <span class="label">SERVICE:</span>
    </div>
    <div style="font-size: 11px; margin: 1mm 0;">
        <?php echo htmlspecialchars($payment['treatment_name']); ?>
    </div>
    <div class="two-col">
        <div class="col">Code: <?php echo htmlspecialchars($payment['treatment_code']); ?></div>
        <div class="col">Date: <?php echo date('d/m/Y', strtotime($payment['treatment_date'])); ?></div>
    </div>
    <div class="info-line">
        <span class="label">Cost:</span>
        <span>$<?php echo number_format($payment['treatment_cost'], 2); ?></span>
    </div>

    <div class="divider"></div>

    <!-- Payment Info -->
    <div class="info-line">
        <span class="label">Method:</span>
        <span><?php echo htmlspecialchars($payment['payment_method']); ?></span>
    </div>
    <?php if ($payment['transaction_reference']): ?>
    <div class="info-line">
        <span class="label">Ref:</span>
        <span><?php echo htmlspecialchars($payment['transaction_reference']); ?></span>
    </div>
    <?php endif; ?>

    <!-- Amount Paid -->
    <div class="amount-box">
        <div style="font-size: 12px;">AMOUNT PAID</div>
        <div class="amount-large">$<?php echo number_format($payment['amount'], 2); ?></div>
    </div>

    <?php if ($payment['notes']): ?>
    <div class="divider"></div>
    <div style="font-size: 9px;">
        <strong>Notes:</strong><br>
        <?php echo nl2br(htmlspecialchars($payment['notes'])); ?>
    </div>
    <?php endif; ?>

    <div class="divider"></div>

    <!-- Footer -->
    <div class="footer">
        <div>Thank you!</div>
        <div>Computer generated receipt</div>
        <div>No signature required</div>
        <div style="margin-top: 2mm; font-size: 8px;">
            <?php echo date('d/m/Y H:i'); ?> | <?php echo $payment['payment_id']; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-print
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('auto_print') === '1') {
                setTimeout(() => window.print(), 300);
            }
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                    e.preventDefault();
                    window.print();
                }
                if (e.key === 'Escape') {
                    window.close();
                }
            });
        });
    </script>
</body>
</html>