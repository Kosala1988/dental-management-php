<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

include '../../includes/db_connect.php';

// Get appointment ID from URL
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($appointment_id <= 0) {
    $_SESSION['error_message'] = "Invalid appointment ID.";
    header("Location: index.php");
    exit();
}

// Fetch appointment details with patient and dentist information
$stmt = $conn->prepare("
    SELECT 
        a.appointment_id, 
        a.scheduled_date, 
        a.start_time, 
        a.end_time, 
        a.reason, 
        a.notes, 
        a.status,
        p.patient_id,
        p.file_number,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
        p.phone AS patient_phone,
        p.email AS patient_email,
        u.full_name AS dentist_name
    FROM 
        appointments a
    JOIN 
        patients p ON a.patient_id = p.patient_id
    JOIN 
        users u ON a.dentist_id = u.user_id
    WHERE 
        a.appointment_id = ?
");

$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Appointment not found.";
    header("Location: index.php");
    exit();
}

$appointment = $result->fetch_assoc();
$stmt->close();

// Format date and time for display
$appointment_date = new DateTime($appointment['scheduled_date']);
$start_time = new DateTime($appointment['start_time']);
$end_time = new DateTime($appointment['end_time']);

// Success message from session if available
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Confirmation | DenTec</title>
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
        
        .status-badge {
            font-size: 0.85rem;
            padding: 0.35rem 0.65rem;
            border-radius: 50rem;
        }
        
        .confirmation-card {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: none;
        }
        
        .card-header-gradient {
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 30px;
            position: relative;
        }
        
        .success-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: white;
            color: var(--success-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .info-row {
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: var(--light-text);
        }
        
        .info-value {
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .appointment-actions .btn {
            padding: 12px 24px;
            border-radius: 50rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .appointment-actions .btn-outline-primary {
            border-width: 2px;
        }
        
        .appointment-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .qr-code {
            max-width: 130px;
            margin: 0 auto;
            padding: 15px;
            background-color: white;
            border-radius: 10px;
            border: 1px solid #eeeeee;
        }
        
        .dentist-card {
            padding: 20px;
            border-radius: 12px;
            background-color: rgba(72, 149, 239, 0.05);
            border: 1px solid rgba(72, 149, 239, 0.1);
        }
        
        .dentist-avatar {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background-color: var(--accent-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .patient-avatar {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                background-color: white !important;
            }
            
            .confirmation-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            
            .container {
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb" class="no-print">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="appointments.php">Appointments</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Confirmation</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="confirmation-card mb-4">
                    <div class="card-header-gradient">
                        <div class="success-icon">
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <h2 class="mb-1">Appointment Confirmed</h2>
                        <p class="mb-0">The appointment has been successfully scheduled.</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-7">
                                <!-- Appointment details -->
                                <h5 class="mb-4">Appointment Details</h5>
                                
                                <div class="info-row d-flex">
                                    <div class="col-5">
                                        <div class="info-label">Appointment ID</div>
                                    </div>
                                    <div class="col-7">
                                        <div class="info-value"><?php echo $appointment['appointment_id']; ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-row d-flex">
                                    <div class="col-5">
                                        <div class="info-label">Date</div>
                                    </div>
                                    <div class="col-7">
                                        <div class="info-value">
                                            <?php echo $appointment_date->format('l, F j, Y'); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-row d-flex">
                                    <div class="col-5">
                                        <div class="info-label">Time</div>
                                    </div>
                                    <div class="col-7">
                                        <div class="info-value">
                                            <?php echo $start_time->format('g:i A'); ?> - <?php echo $end_time->format('g:i A'); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-row d-flex">
                                    <div class="col-5">
                                        <div class="info-label">Status</div>
                                    </div>
                                    <div class="col-7">
                                        <div class="status-badge bg-success text-white">
                                            <?php echo $appointment['status']; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="info-row d-flex">
                                    <div class="col-5">
                                        <div class="info-label">Reason</div>
                                    </div>
                                    <div class="col-7">
                                        <div class="info-value">
                                            <?php echo empty($appointment['reason']) ? 'Not specified' : htmlspecialchars($appointment['reason']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($appointment['notes'])): ?>
                                <div class="info-row d-flex">
                                    <div class="col-5">
                                        <div class="info-label">Notes</div>
                                    </div>
                                    <div class="col-7">
                                        <div class="info-value">
                                            <?php echo htmlspecialchars($appointment['notes']); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-5">
                                <div class="text-center mb-4">
                                    <div class="qr-code mb-2">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=APP<?php echo $appointment['appointment_id']; ?>" 
                                             alt="Appointment QR Code" class="img-fluid">
                                    </div>
                                    <small class="text-muted">Scan for appointment details</small>
                                </div>
                                
                                <!-- Dentist info -->
                                <div class="dentist-card mb-3">
                                    <h6 class="mb-3">Dentist</h6>
                                    <div class="d-flex align-items-center">
                                        <div class="dentist-avatar me-3">
                                            <?php echo strtoupper(substr($appointment['dentist_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($appointment['dentist_name']); ?></h6>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Patient info -->
                                <div class="patient-card">
                                    <h6 class="mb-3">Patient</h6>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="patient-avatar me-3">
                                            <?php echo strtoupper(substr($appointment['patient_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($appointment['patient_name']); ?></h6>
                                            <small class="text-muted">ID: <?php echo $appointment['patient_id']; ?></small>
                                        </div>
                                    </div>
                                    <?php if (!empty($appointment['patient_phone'])): ?>
                                        <div class="mb-1">
                                            <i class="bi bi-telephone text-muted me-2"></i>
                                            <?php echo htmlspecialchars($appointment['patient_phone']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($appointment['patient_email'])): ?>
                                        <div>
                                            <i class="bi bi-envelope text-muted me-2"></i>
                                            <?php echo htmlspecialchars($appointment['patient_email']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-white py-4 no-print">
                        <div class="appointment-actions d-flex justify-content-between">
                            <a href="patient_detail.php?id=<?php echo $appointment['patient_id']; ?>" class="btn btn-outline-primary">
                                <i class="bi bi-person me-2"></i> View Patient
                            </a>
                            <button onclick="window.print()" class="btn btn-outline-secondary">
                                <i class="bi bi-printer me-2"></i> Print
                            </button>
                            <a href="edit_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-primary">
                                <i class="bi bi-pencil me-2"></i> Edit Appointment
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center no-print">
                    <a href="schedule_appointment.php" class="btn btn-success me-2">
                        <i class="bi bi-plus-circle me-2"></i> Schedule Another
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-house me-2"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>