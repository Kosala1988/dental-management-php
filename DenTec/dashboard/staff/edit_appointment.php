<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

include '../../includes/db_connect.php';

/**
 * Simple Text.lk SMS Gateway
 */
class TextLkGateway {
    private $config = [];
    
    public function __construct($config = []) {
        $this->config = $config;
    }
    
    public function sendSMS($phone, $message) {
        if (empty($this->config['api_token']) || empty($this->config['sender_id'])) {
            return ['success' => false, 'message' => 'SMS not configured'];
        }
        
        $payload = [
            'recipient' => $phone,
            'sender_id' => $this->config['sender_id'],
            'type' => 'plain',
            'message' => $message
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://app.text.lk/api/v3/sms/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->config['api_token'],
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 || $http_code == 201) {
            return ['success' => true, 'message' => 'SMS sent successfully'];
        } else {
            return ['success' => false, 'message' => 'SMS failed to send'];
        }
    }
    
    public function isConfigured() {
        return !empty($this->config['api_token']) && !empty($this->config['sender_id']);
    }
}

/**
 * Simple SMS Template
 */
class SMSTemplate {
    public static function appointmentUpdate($patientName, $date, $time, $doctorName) {
        return "Dear {$patientName},\n\nYour dental appointment at The Family Dentist has been updated:\n\n Date: " . date('l, F j, Y', strtotime($date)) . 
               "\n Time: " . date('g:i A', strtotime($time)) . 
               "\n Dentist: Dr. {$doctorName}" . 
               "\n Contact: 0710783322 / 0702776622  Visit- https://www.visitthefamilydentist.com";
    }
    
    public static function appointmentCancellation($patientName) {
        return "Dear {$patientName},\n\nYour dental appointment at The Family Dentist has been cancelled. Please contact us to reschedule.\n\n Contact: 0710783322 / 0702776622  Visit- https://www.visitthefamilydentist.com";
    }
}

// Load SMS configuration
$sms_config = [];
if (file_exists('sms_config.php')) {
    $sms_config = include('sms_config.php');
} else {
    $sms_config = [
        'api_token' => '726|prcvckoEIe9vUu6cbgysoTu0A25TLEhn7W9TbBXk20990b76',
        'sender_id' => 'DENTEC'
    ];
}

$sms_gateway = new TextLkGateway($sms_config);

// Initialize variables
$error_message = '';
$success_message = '';
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($appointment_id <= 0) {
    header("Location: index.php");
    exit();
}
?>
<?php
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
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
        p.phone AS patient_phone,
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

// Get all dentists
$dentists = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'dentist' ORDER BY full_name");

// Get existing appointments for the selected date and dentist (excluding current appointment)
$existing_appointments = [];
if ($appointment['scheduled_date'] && $appointment['dentist_id']) {
    $stmt = $conn->prepare("
        SELECT start_time, end_time 
        FROM appointments 
        WHERE dentist_id = ? AND scheduled_date = ? AND status != 'Canceled' AND appointment_id != ?
    ");
    $stmt->bind_param("isi", $appointment['dentist_id'], $appointment['scheduled_date'], $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $existing_appointments[] = [
            'start' => new DateTime($row['start_time']),
            'end' => new DateTime($row['end_time'])
        ];
    }
    $stmt->close();
}

// Generate time slots
$time_slots = [];
$start = new DateTime('09:00');
$end = new DateTime('20:30');
$interval = new DateInterval('PT30M');

while ($start <= $end) {
    $slot_end = clone $start;
    $slot_end->add($interval);
    
    $is_available = true;
    $is_current = false;
    
    // Check if this is the current appointment time
    $current_start = new DateTime($appointment['start_time']);
    if ($start->format('H:i') === $current_start->format('H:i')) {
        $is_current = true;
        $is_available = true;
    } else {
        // Check against other appointments
        foreach ($existing_appointments as $appt) {
            if ($start < $appt['end'] && $slot_end > $appt['start']) {
                $is_available = false;
                break;
            }
        }
    }
    
    $time_slots[] = [
        'start' => clone $start,
        'end' => $slot_end,
        'available' => $is_available,
        'current' => $is_current
    ];
    
    $start->add($interval);
}
?>
<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        $date = $_POST['date'];
        $time = $_POST['time'];
        $dentist_id = $_POST['dentist_id'];
        $reason = $_POST['reason'];
        $notes = $_POST['notes'];
        $status = $_POST['status'];
        $send_sms = isset($_POST['send_sms']) && $_POST['send_sms'] == '1';
        
        // Get dentist name for SMS
        $dentist_stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $dentist_stmt->bind_param("i", $dentist_id);
        $dentist_stmt->execute();
        $dentist_result = $dentist_stmt->get_result();
        $dentist_data = $dentist_result->fetch_assoc();
        $dentist_stmt->close();
        
        // Calculate end time
        $start_time = new DateTime($time);
        $end_time = clone $start_time;
        $end_time->modify('+30 minutes');
        $end_time_str = $end_time->format('H:i:s');
        
        // Update appointment
        $update_stmt = $conn->prepare("
            UPDATE appointments 
            SET dentist_id = ?, scheduled_date = ?, start_time = ?, end_time = ?, 
                reason = ?, notes = ?, status = ?
            WHERE appointment_id = ?
        ");
        $update_stmt->bind_param("issssssi", $dentist_id, $date, $time, $end_time_str, $reason, $notes, $status, $appointment_id);
        
        if ($update_stmt->execute()) {
            // Send SMS if requested and phone number exists
            if ($send_sms && !empty($appointment['patient_phone'])) {
                $message = SMSTemplate::appointmentUpdate(
                    $appointment['patient_name'],
                    $date,
                    $time,
                    $dentist_data['full_name']
                );
                
                $sms_result = $sms_gateway->sendSMS($appointment['patient_phone'], $message);
                
                if ($sms_result['success']) {
                    $success_message = "Appointment updated and SMS sent successfully!";
                } else {
                    $success_message = "Appointment updated successfully!";
                    $_SESSION['sms_error'] = "SMS could not be sent.";
                }
            } else {
                $success_message = "Appointment updated successfully!";
            }
            
            // Refresh appointment data
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
                    CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
                    p.phone AS patient_phone,
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
            
        } else {
            $error_message = "Error updating appointment: " . $update_stmt->error;
        }
        $update_stmt->close();
        
    } elseif ($action === 'cancel') {
        $send_sms = isset($_POST['send_sms']) && $_POST['send_sms'] == '1';
        
        // Update appointment status to canceled
        $cancel_stmt = $conn->prepare("UPDATE appointments SET status = 'Canceled' WHERE appointment_id = ?");
        $cancel_stmt->bind_param("i", $appointment_id);
        
        if ($cancel_stmt->execute()) {
            // Send SMS if requested and phone number exists
            if ($send_sms && !empty($appointment['patient_phone'])) {
                $message = SMSTemplate::appointmentCancellation($appointment['patient_name']);
                $sms_result = $sms_gateway->sendSMS($appointment['patient_phone'], $message);
                
                if ($sms_result['success']) {
                    $_SESSION['success_message'] = "Appointment cancelled and SMS sent successfully!";
                } else {
                    $_SESSION['success_message'] = "Appointment cancelled successfully!";
                    $_SESSION['sms_error'] = "SMS could not be sent.";
                }
            } else {
                $_SESSION['success_message'] = "Appointment cancelled successfully!";
            }
            
            header("Location: index.php");
            exit();
        } else {
            $error_message = "Error canceling appointment: " . $cancel_stmt->error;
        }
        $cancel_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appointment | DenTec</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --light-text: #6c757d;
        }
        
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--light-bg);
        }
        
        .time-slot {
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #dee2e6;
        }
        
        .time-slot.available {
            background-color: #e0f7e6;
            border-color: #c3e6cb;
        }
        
        .time-slot.available:hover {
            background-color: #d4edda;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .time-slot.booked {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            cursor: not-allowed;
            opacity: 0.85;
        }
        
        .time-slot.current {
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }
        
        .time-slot.selected {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .time-slot-label {
            font-weight: 500;
        }
        
        .time-slot-time {
            font-size: 0.9rem;
            color: var(--light-text);
        }
        
        .time-slot.booked .time-slot-time {
            color: #721c24;
        }
        
        .time-slot.current .time-slot-time {
            color: #856404;
        }
        
        .selected .time-slot-time {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .patient-info-card {
            border-left: 4px solid var(--primary-color);
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .dentist-card {
            border-radius: 10px;
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .dentist-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .dentist-card.selected {
            border-color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .status-scheduled {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }
        
        .status-completed {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        
        .status-canceled {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .status-badge:hover {
            transform: scale(1.05);
        }
        
        .avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <h2>Edit Appointment</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Appointments</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit Appointment</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['sms_error'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['sms_error']; unset($_SESSION['sms_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
<!-- Left Sidebar - Patient Information -->
            <div class="col-md-4 mb-4">
                <!-- Patient Information Card -->
                <div class="patient-info-card">
                    <h5 class="mb-3">Patient Information</h5>
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-3" style="width: 50px; height: 50px; font-size: 1.25rem; background-color: var(--primary-color); color: white;">
                            <?php echo strtoupper(substr($appointment['patient_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($appointment['patient_name']); ?></h6>
                            <small class="text-muted">ID: <?php echo $appointment['patient_id']; ?></small>
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">File Number:</small>
                        <p class="mb-0"><?php echo htmlspecialchars($appointment['file_number']); ?></p>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Phone:</small>
                        <p class="mb-0"><?php echo htmlspecialchars($appointment['patient_phone'] ?? 'Not provided'); ?></p>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Current Status:</small>
                        <p class="mb-0">
                            <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                <?php echo $appointment['status']; ?>
                            </span>
                        </p>
                    </div>
                </div>
                
                <!-- SMS Option -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="smsEnabled" name="send_sms" <?php echo $sms_gateway->isConfigured() ? 'checked' : 'disabled'; ?>>
                            <label class="form-check-label" for="smsEnabled">
                                <i class="bi bi-chat-square-text me-2"></i>
                                Send SMS notification
                            </label>
                        </div>
                        <?php if (!$sms_gateway->isConfigured()): ?>
                            <small class="text-muted">SMS not configured</small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Dentist Selection -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Select Dentist</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($dentists && $dentists->num_rows > 0): ?>
                            <?php while ($dentist = $dentists->fetch_assoc()): ?>
                                <div class="dentist-card <?php echo $appointment['dentist_id'] == $dentist['user_id'] ? 'selected' : ''; ?>" 
                                     onclick="selectDentist(<?php echo $dentist['user_id']; ?>)">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3" style="width: 40px; height: 40px; background-color: var(--accent-color); color: white;">
                                            <?php echo strtoupper(substr($dentist['full_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($dentist['full_name']); ?></h6>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                No dentists available.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
<!-- Main Content - Edit Form -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Edit Appointment Details</h5>
                    </div>
                    <div class="card-body">
                        <form id="appointmentForm" method="post" action="">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" id="dentist_id" name="dentist_id" value="<?php echo $appointment['dentist_id']; ?>">
                            <input type="hidden" id="selected_time" name="time" value="<?php echo $appointment['start_time']; ?>">
                            
                            <!-- Date Selection -->
                            <div class="mb-4">
                                <label class="form-label">Appointment Date</label>
                                <input type="date" class="form-control" id="appointmentDate" name="date" 
                                       value="<?php echo $appointment['scheduled_date']; ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" onchange="updateTimeSlots()">
                            </div>
                            
                            <!-- Time Slots -->
                            <div class="mb-4">
                                <label class="form-label">Available Time Slots</label>
                                <div class="row" id="timeSlotsContainer">
                                    <?php foreach ($time_slots as $slot): ?>
                                        <div class="col-md-4 mb-2">
                                            <?php 
                                                $classes = 'time-slot';
                                                $onclick = '';
                                                $status = '';
                                                
                                                if ($slot['current']) {
                                                    $classes .= ' current selected';
                                                    $onclick = 'selectTimeSlot(this, \'' . $slot['start']->format('H:i:s') . '\')';
                                                    $status = 'Current';
                                                } elseif ($slot['available']) {
                                                    $classes .= ' available';
                                                    $onclick = 'selectTimeSlot(this, \'' . $slot['start']->format('H:i:s') . '\')';
                                                    $status = 'Available';
                                                } else {
                                                    $classes .= ' booked';
                                                    $status = 'Booked';
                                                }
                                            ?>
                                            <div class="<?php echo $classes; ?>" <?php echo $onclick ? 'onclick="' . $onclick . '"' : ''; ?>>
                                                <div class="time-slot-label">
                                                    <?php echo $slot['start']->format('g:i A'); ?> - <?php echo $slot['end']->format('g:i A'); ?>
                                                </div>
                                                <div class="time-slot-time">
                                                    <?php echo $status; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Status Selection -->
                            <div class="mb-3">
                                <label for="status" class="form-label">Appointment Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Scheduled" <?php echo $appointment['status'] === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="Completed" <?php echo $appointment['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="No Show" <?php echo $appointment['status'] === 'No Show' ? 'selected' : ''; ?>>No Show</option>
                                </select>
                            </div>
                            
                            <!-- Appointment Details -->
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Appointment</label>
                                <input type="text" class="form-control" id="reason" name="reason" 
                                       value="<?php echo htmlspecialchars($appointment['reason']); ?>" 
                                       placeholder="E.g. Routine checkup, tooth pain, etc." required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                         placeholder="Any additional information..."><?php echo htmlspecialchars($appointment['notes']); ?></textarea>
                            </div>
                            
                            <!-- SMS Option in Form -->
                            <input type="hidden" name="send_sms" value="0" id="smsHidden">
                            
                            <div class="d-flex justify-content-between">
                                <div>
                                    <!-- Cancel Appointment Button -->
                                    <?php if ($appointment['status'] !== 'Canceled'): ?>
                                        <button type="button" class="btn btn-outline-danger" onclick="cancelAppointment()">
                                            <i class="bi bi-x-circle me-1"></i>Cancel Appointment
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <a href="index.php" class="btn btn-outline-secondary me-2">Back to Dashboard</a>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Update Appointment
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Hidden form for cancellation -->
                        <form id="cancelForm" method="post" action="" style="display: none;">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="send_sms" value="0" id="cancelSmsHidden">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectDentist(dentistId) {
            document.getElementById('dentist_id').value = dentistId;
            
            document.querySelectorAll('.dentist-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            updateTimeSlots();
        }
        
        function selectTimeSlot(element, time) {
            document.getElementById('selected_time').value = time;
            
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            element.classList.add('selected');
        }
        
        function updateTimeSlots() {
            const date = document.getElementById('appointmentDate').value;
            const dentistId = document.getElementById('dentist_id').value;
            const appointmentId = <?php echo $appointment_id; ?>;
            
            if (date && dentistId) {
                // Reload page with new parameters to update time slots
                window.location.href = `edit_appointment.php?id=${appointmentId}&date=${date}&dentist_id=${dentistId}`;
            }
        }
        
        function cancelAppointment() {
            if (confirm('Are you sure you want to cancel this appointment? This action cannot be undone.')) {
                const sendSms = document.getElementById('smsEnabled').checked;
                document.getElementById('cancelSmsHidden').value = sendSms ? '1' : '0';
                document.getElementById('cancelForm').submit();
            }
        }
        
        function testSMS() {
            const phone = prompt('Enter phone number to test SMS (e.g., 0712345678):');
            if (!phone) return;
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Testing SMS...';
            submitBtn.disabled = true;
            
            fetch('test_sms.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    phone: phone,
                    message: 'Test SMS from DenTec Clinic - ' + new Date().toLocaleString()
                })
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                if (data.success) {
                    alert('SMS sent successfully!');
                } else {
                    alert('SMS failed: ' + data.message);
                }
            })
            .catch(error => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                alert('Error testing SMS: ' + error.message);
            });
        }
        
        // Event Listeners
        document.getElementById('appointmentDate').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                this.value = today.toISOString().split('T')[0];
                alert('Please select a current or future date.');
                return;
            }
            
            updateTimeSlots();
        });
        
        document.getElementById('smsEnabled').addEventListener('change', function() {
            document.getElementById('smsHidden').value = this.checked ? '1' : '0';
        });
        
        document.getElementById('appointmentForm').addEventListener('submit', function(e) {
            const timeSelected = document.getElementById('selected_time').value;
            
            if (!timeSelected) {
                e.preventDefault();
                alert('Please select an available time slot.');
                return false;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Updating...';
            submitBtn.disabled = true;
            
            document.getElementById('smsHidden').value = document.getElementById('smsEnabled').checked ? '1' : '0';
            
            return true;
        });
        
        // Initialize SMS settings on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('smsHidden').value = document.getElementById('smsEnabled').checked ? '1' : '0';
        });
    </script>
</body>
</html>
                        