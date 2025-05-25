<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

include '../../includes/db_connect.php';

// Initialize variables
$error_message = '';
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($appointment_id <= 0) {
    $_SESSION['error_message'] = "Invalid appointment ID.";
    header("Location: index.php");
    exit();
}

// Fetch appointment details
$appointment = [];
$stmt = $conn->prepare("
    SELECT 
        a.appointment_id,
        a.patient_id,
        a.dentist_id,
        a.scheduled_date,
        a.start_time,
        a.end_time,
        a.reason,
        a.notes,
        a.status,
        a.created_at,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
        p.phone AS patient_phone,
        p.email AS patient_email,
        p.file_number,
        u.full_name AS dentist_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users u ON a.dentist_id = u.user_id
    WHERE a.appointment_id = ?
");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();
$stmt->close();

if (!$appointment) {
    $_SESSION['error_message'] = "Appointment not found.";
    header("Location: index.php");
    exit();
}

// Check if appointment is already canceled
if ($appointment['status'] === 'Canceled') {
    $_SESSION['info_message'] = "This appointment is already canceled.";
    header("Location: index.php");
    exit();
}

// Check if appointment is completed
if ($appointment['status'] === 'Completed') {
    $_SESSION['error_message'] = "Cannot cancel a completed appointment.";
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'cancel') {
        $cancellation_reason = trim($_POST['cancellation_reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        // Validate cancellation reason
        if (empty($cancellation_reason)) {
            $error_message = "Please provide a reason for cancellation.";
        } else {
            // Update appointment status to canceled
            $cancel_stmt = $conn->prepare("
                UPDATE appointments 
                SET status = 'Canceled', 
                    notes = CONCAT(COALESCE(notes, ''), '\n\n--- CANCELED ---\nReason: ', ?, '\nCanceled by: ', ?, '\nCanceled on: ', NOW(), 
                    CASE WHEN ? != '' THEN CONCAT('\nAdditional Notes: ', ?) ELSE '' END)
                WHERE appointment_id = ?
            ");
            
            $staff_name = $_SESSION['full_name'] ?? 'Staff';
            $cancel_stmt->bind_param("ssssi", $cancellation_reason, $staff_name, $notes, $notes, $appointment_id);
            
            if ($cancel_stmt->execute()) {
                $_SESSION['success_message'] = "Appointment has been successfully canceled.";
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Error canceling appointment: " . $cancel_stmt->error;
            }
            $cancel_stmt->close();
        }
    }
}

// Calculate time until appointment
$appointment_datetime = new DateTime($appointment['scheduled_date'] . ' ' . $appointment['start_time']);
$current_datetime = new DateTime();
$time_diff = $current_datetime->diff($appointment_datetime);
$is_future = $appointment_datetime > $current_datetime;
$is_today = $appointment_datetime->format('Y-m-d') === $current_datetime->format('Y-m-d');
$is_past = $appointment_datetime < $current_datetime;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Appointment | DenTec</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-bg: #f8fafc;
            --dark-text: #1e293b;
            --light-text: #64748b;
            --border-color: #e2e8f0;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark-text);
        }
        
        .main-container {
            background: var(--light-bg);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .cancel-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: none;
            overflow: hidden;
        }
        
        .cancel-header {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .cancel-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="25" r="1" fill="white" opacity="0.05"/><circle cx="25" cy="75" r="1" fill="white" opacity="0.05"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .cancel-header h2 {
            position: relative;
            z-index: 1;
            margin: 0;
            font-weight: 700;
            font-size: 1.75rem;
        }
        
        .cancel-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            position: relative;
            z-index: 1;
        }
        
        .cancel-icon i {
            font-size: 2.5rem;
        }
        
        .appointment-details-card {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
        }
        
        .patient-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            padding: 0.5rem 0;
        }
        
        .detail-item:last-child {
            margin-bottom: 0;
        }
        
        .detail-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .detail-content h6 {
            margin: 0;
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .detail-content p {
            margin: 0;
            color: var(--light-text);
            font-size: 0.875rem;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-scheduled {
            background: rgba(59, 130, 246, 0.1);
            color: #1d4ed8;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        .warning-box {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 1px solid #f59e0b;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .warning-box .warning-icon {
            color: #d97706;
            font-size: 1.25rem;
            margin-right: 0.5rem;
        }
        
        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 8px -1px rgba(239, 68, 68, 0.4);
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }
        
        .btn-outline-secondary {
            border: 2px solid var(--border-color);
            color: var(--light-text);
            background: white;
        }
        
        .btn-outline-secondary:hover {
            background: var(--light-bg);
            border-color: var(--light-text);
            color: var(--dark-text);
            transform: translateY(-1px);
        }
        
        .breadcrumb {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            backdrop-filter: blur(10px);
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .breadcrumb-item a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem 0;
            }
            
            .cancel-header {
                padding: 1.5rem 1rem;
            }
            
            .cancel-icon {
                width: 60px;
                height: 60px;
            }
            
            .cancel-icon i {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container">
            <!-- Breadcrumb Navigation -->
            <div class="row mb-4">
                <div class="col-12">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="index.php">
                                    <i class="bi bi-house-door me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="index.php">Appointments</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Cancel Appointment</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <!-- Error Alert -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <!-- Cancel Appointment Card -->
                    <div class="cancel-card">
                        <!-- Card Header -->
                        <div class="cancel-header">
                            <div class="cancel-icon">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <h2>Cancel Appointment</h2>
                            <p class="mb-0 opacity-75">You are about to cancel this appointment</p>
                        </div>

                        <!-- Card Body -->
                        <div class="p-4">
                            <!-- Appointment Details -->
                            <div class="appointment-details-card">
                                <h5 class="text-primary mb-3">
                                    <i class="bi bi-info-circle me-2"></i>Appointment Details
                                </h5>
                                
                                <!-- Patient Information -->
                                <div class="detail-item">
                                    <div class="patient-avatar">
                                        <?php echo strtoupper(substr($appointment['patient_name'], 0, 1)); ?>
                                    </div>
                                    <div class="detail-content ms-3">
                                        <h6><?php echo htmlspecialchars($appointment['patient_name']); ?></h6>
                                        <p>Patient ID: <?php echo $appointment['patient_id']; ?> | File: <?php echo htmlspecialchars($appointment['file_number']); ?></p>
                                        <?php if (!empty($appointment['patient_phone'])): ?>
                                            <p><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($appointment['patient_phone']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <hr class="my-3">

                                <!-- Appointment Details Grid -->
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="detail-item">
                                            <div class="detail-icon">
                                                <i class="bi bi-calendar-event"></i>
                                            </div>
                                            <div class="detail-content">
                                                <h6>Date & Time</h6>
                                                <p><?php echo date('l, F j, Y', strtotime($appointment['scheduled_date'])); ?></p>
                                                <p><?php echo date('g:i A', strtotime($appointment['start_time'])); ?> - <?php echo date('g:i A', strtotime($appointment['end_time'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="detail-item">
                                            <div class="detail-icon">
                                                <i class="bi bi-person-badge"></i>
                                            </div>
                                            <div class="detail-content">
                                                <h6>Dentist</h6>
                                                <p>Dr. <?php echo htmlspecialchars($appointment['dentist_name']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="detail-item">
                                            <div class="detail-icon">
                                                <i class="bi bi-clipboard-pulse"></i>
                                            </div>
                                            <div class="detail-content">
                                                <h6>Reason</h6>
                                                <p><?php echo htmlspecialchars($appointment['reason']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="detail-item">
                                            <div class="detail-icon">
                                                <i class="bi bi-check-circle"></i>
                                            </div>
                                            <div class="detail-content">
                                                <h6>Status</h6>
                                                <span class="status-badge status-scheduled">
                                                    <i class="bi bi-clock"></i>
                                                    <?php echo $appointment['status']; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($appointment['notes'])): ?>
                                    <hr class="my-3">
                                    <div class="detail-item">
                                        <div class="detail-icon">
                                            <i class="bi bi-sticky"></i>
                                        </div>
                                        <div class="detail-content">
                                            <h6>Notes</h6>
                                            <p><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Time Warning -->
                            <?php if ($is_future): ?>
                                <div class="warning-box">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-clock-history warning-icon"></i>
                                        <div>
                                            <h6 class="text-warning fw-bold mb-1">Appointment Timing</h6>
                                            <?php if ($is_today): ?>
                                                <p class="mb-0 text-warning">
                                                    <strong>This appointment is scheduled for today</strong> at <?php echo date('g:i A', strtotime($appointment['start_time'])); ?>.
                                                    <?php if ($time_diff->h > 0 || $time_diff->i > 30): ?>
                                                        You have <?php echo $time_diff->h > 0 ? $time_diff->h . ' hours and ' : ''; ?><?php echo $time_diff->i; ?> minutes until the appointment.
                                                    <?php else: ?>
                                                        <span class="text-danger fw-bold">The appointment is very soon!</span>
                                                    <?php endif; ?>
                                                </p>
                                            <?php else: ?>
                                                <p class="mb-0 text-warning">
                                                    This appointment is scheduled for <strong><?php echo date('l, F j, Y', strtotime($appointment['scheduled_date'])); ?></strong>.
                                                    <?php if ($time_diff->days == 1): ?>
                                                        <span class="fw-bold">That's tomorrow!</span>
                                                    <?php elseif ($time_diff->days <= 7): ?>
                                                        <span class="fw-bold">That's in <?php echo $time_diff->days; ?> days.</span>
                                                    <?php endif; ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($is_past): ?>
                                <div class="warning-box" style="background: linear-gradient(135deg, #fee2e2, #fecaca); border-color: #ef4444;">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-exclamation-triangle warning-icon" style="color: #dc2626;"></i>
                                        <div>
                                            <h6 class="fw-bold mb-1" style="color: #dc2626;">Past Appointment</h6>
                                            <p class="mb-0" style="color: #dc2626;">
                                                This appointment was scheduled for the past. Consider marking it as "No Show" instead of canceling.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Cancellation Form -->
                            <form method="post" action="" id="cancelForm">
                                <input type="hidden" name="action" value="cancel">
                                
                                <div class="mb-3">
                                    <label for="cancellation_reason" class="form-label fw-semibold">
                                        <i class="bi bi-chat-square-text me-2"></i>Reason for Cancellation *
                                    </label>
                                    <select class="form-select" id="cancellation_reason" name="cancellation_reason" required>
                                        <option value="">Select a reason...</option>
                                        <option value="Patient Request">Patient Request</option>
                                        <option value="Patient No Show">Patient No Show</option>
                                        <option value="Patient Illness">Patient Illness</option>
                                        <option value="Emergency">Emergency</option>
                                        <option value="Dentist Unavailable">Dentist Unavailable</option>
                                        <option value="Equipment Issue">Equipment Issue</option>
                                        <option value="Schedule Conflict">Schedule Conflict</option>
                                        <option value="Weather/External Factors">Weather/External Factors</option>
                                        <option value="Administrative Error">Administrative Error</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="notes" class="form-label fw-semibold">
                                        <i class="bi bi-sticky me-2"></i>Additional Notes
                                    </label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                             placeholder="Any additional information about the cancellation..."></textarea>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        These notes will be added to the appointment record for future reference.
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-between">
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                                    </a>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">
                                            <i class="bi bi-x-lg me-2"></i>Cancel Action
                                        </button>
                                        <button type="button" class="btn btn-danger" onclick="confirmCancellation()">
                                            <i class="bi bi-trash me-2"></i>Cancel Appointment
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--danger-color), #dc2626); color: white; border-radius: 16px 16px 0 0;">
                    <h5 class="modal-title" id="confirmationModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>Confirm Cancellation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <div class="mx-auto mb-3" style="width: 80px; height: 80px; background: rgba(239, 68, 68, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2rem; color: var(--danger-color);"></i>
                        </div>
                        <h6 class="fw-bold">Are you absolutely sure?</h6>
                        <p class="text-muted mb-0">This action will permanently cancel the appointment for:</p>
                    </div>
                    
                    <div class="bg-light rounded p-3 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="patient-avatar me-3" style="width: 50px; height: 50px; font-size: 1.25rem;">
                                <?php echo strtoupper(substr($appointment['patient_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($appointment['patient_name']); ?></h6>
                                <p class="mb-0 text-muted small">
                                    <?php echo date('l, F j, Y', strtotime($appointment['scheduled_date'])); ?> at 
                                    <?php echo date('g:i A', strtotime($appointment['start_time'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> This action cannot be undone. The appointment will be marked as canceled in the system.
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-2"></i>Keep Appointment
                    </button>
                    <button type="button" class="btn btn-danger" onclick="submitCancellation()">
                        <i class="bi bi-check me-2"></i>Yes, Cancel It
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmCancellation() {
            const reasonSelect = document.getElementById('cancellation_reason');
            
            if (!reasonSelect.value) {
                // Highlight the reason field
                reasonSelect.style.borderColor = 'var(--danger-color)';
                reasonSelect.focus();
                
                // Show error message
                let errorDiv = document.getElementById('reason-error');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.id = 'reason-error';
                    errorDiv.className = 'text-danger small mt-1';
                    errorDiv.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Please select a reason for cancellation.';
                    reasonSelect.parentNode.appendChild(errorDiv);
                }
                
                // Remove error styling after user selects a reason
                reasonSelect.addEventListener('change', function() {
                    if (this.value) {
                        this.style.borderColor = '';
                        const errorDiv = document.getElementById('reason-error');
                        if (errorDiv) {
                            errorDiv.remove();
                        }
                    }
                });
                
                return;
            }
            
            // Show confirmation modal
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            modal.show();
        }
        
        function submitCancellation() {
            const form = document.getElementById('cancelForm');
            const submitBtn = event.target;
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Canceling...';
            submitBtn.disabled = true;
            
            // Add smooth transition effect
            document.querySelector('.cancel-card').style.transition = 'all 0.3s ease';
            document.querySelector('.cancel-card').style.opacity = '0.7';
            
            // Submit the form
            form.submit();
        }
        
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate card entrance
            const card = document.querySelector('.cancel-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
            
            // Add hover effects to detail items
            const detailItems = document.querySelectorAll('.detail-item');
            detailItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                    this.style.transition = 'transform 0.2s ease';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
            
            // Auto-focus on reason select when page loads
            setTimeout(() => {
                document.getElementById('cancellation_reason').focus();
            }, 500);
        });
        
        // Handle escape key to close modal
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
                if (modal) {
                    modal.hide();
                }
            }
        });
        
        // Form validation
        document.getElementById('cancelForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default submission, we handle it in confirmCancellation
        });
    </script>
</body>
</html>