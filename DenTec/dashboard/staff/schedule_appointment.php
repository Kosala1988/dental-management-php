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
    public static function appointmentConfirmation($patientName, $date, $time, $doctorName) {
        return "Dear {$patientName},\n\nYour dental appointment at The Family Dentist is confirmed:\n\n Date: " . date('l, F j, Y', strtotime($date)) . 
               "\n Time: " . date('g:i A', strtotime($time)) . 
               "\n Dentist: Dr. {$doctorName}" . 
               "\n Contact: 0710783322 / 0702776622  Visit- https://www.visitthefamilydentist.com";
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
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_dentist = isset($_GET['dentist_id']) ? intval($_GET['dentist_id']) : 0;

// Fetch patient details if patient_id is provided
$patient = [];
if ($patient_id > 0) {
    $stmt = $conn->prepare("SELECT patient_id, file_number, CONCAT(first_name, ' ', last_name) AS name, phone FROM patients WHERE patient_id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();
    $stmt->close();
}

// Get all patients for selection if no patient selected
$all_patients = [];
if ($patient_id === 0) {
    $patients_result = $conn->query("SELECT patient_id, CONCAT(first_name, ' ', last_name) AS name, file_number, phone FROM patients ORDER BY name");
    while ($row = $patients_result->fetch_assoc()) {
        $all_patients[] = $row;
    }
}

// Get all dentists
$dentists = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'dentist' ORDER BY full_name");

// Select first dentist if none selected
if ($selected_dentist === 0 && $dentists->num_rows > 0) {
    $dentists->data_seek(0);
    $first_dentist = $dentists->fetch_assoc();
    $selected_dentist = $first_dentist['user_id'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $dentist_id = $_POST['dentist_id'];
    $reason = $_POST['reason'];
    $notes = $_POST['notes'];
    $pat_id = $_POST['patient_id'];
    $send_sms = isset($_POST['send_sms']) && $_POST['send_sms'] == '1';
    
    // Validate patient
    $validate_patient = $conn->prepare("SELECT patient_id, CONCAT(first_name, ' ', last_name) AS name, phone FROM patients WHERE patient_id = ?");
    $validate_patient->bind_param("i", $pat_id);
    $validate_patient->execute();
    $patient_result = $validate_patient->get_result();
    
    if ($patient_result->num_rows === 0) {
        $error_message = "Error: Patient does not exist in the database.";
    } else {
        $patient_data = $patient_result->fetch_assoc();
        
        // Get dentist name
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
        
        // Insert appointment
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, dentist_id, scheduled_date, start_time, end_time, reason, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssssi", $pat_id, $dentist_id, $date, $time, $end_time_str, $reason, $notes, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $appointment_id = $conn->insert_id;
            
            // Send SMS if requested and phone number exists
            if ($send_sms && !empty($patient_data['phone'])) {
                $message = SMSTemplate::appointmentConfirmation(
                    $patient_data['name'],
                    $date,
                    $time,
                    $dentist_data['full_name']
                );
                
                $sms_result = $sms_gateway->sendSMS($patient_data['phone'], $message);
                
                if ($sms_result['success']) {
                    $_SESSION['success_message'] = "Appointment scheduled and SMS sent successfully!";
                } else {
                    $_SESSION['success_message'] = "Appointment scheduled successfully!";
                    $_SESSION['sms_error'] = "SMS could not be sent.";
                }
            } else {
                $_SESSION['success_message'] = "Appointment scheduled successfully!";
            }
            
            header("Location: appointment_confirmation.php?id=$appointment_id");
            exit();
        } else {
            $error_message = "Error scheduling appointment: " . $stmt->error;
        }
        $stmt->close();
    }
    $validate_patient->close();
}

// Get existing appointments
$existing_appointments = [];
if ($selected_date && $selected_dentist) {
    $stmt = $conn->prepare("SELECT start_time, end_time FROM appointments WHERE dentist_id = ? AND scheduled_date = ? AND status != 'Canceled'");
    $stmt->bind_param("is", $selected_dentist, $selected_date);
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
    foreach ($existing_appointments as $appt) {
        if ($start < $appt['end'] && $slot_end > $appt['start']) {
            $is_available = false;
            break;
        }
    }
    
    $time_slots[] = [
        'start' => clone $start,
        'end' => $slot_end,
        'available' => $is_available
    ];
    
    $start->add($interval);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Appointment | DenTec</title>
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
        
        .calendar-day {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            margin: 2px;
        }
        
        .calendar-day:hover {
            background-color: #e9ecef;
        }
        
        .calendar-day.selected {
            background-color: var(--primary-color);
            color: white;
        }
        
        .calendar-day.today {
            border: 2px solid var(--primary-color);
        }
        
        .calendar-day.disabled {
            color: #adb5bd;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2>Schedule New Appointment</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="patient.php">Patients</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Schedule Appointment</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Patient Information -->
            <div class="col-md-4 mb-4">
                <div class="patient-info-card">
                    <h5 class="mb-3">Patient Information</h5>
                    <?php if ($patient): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar me-3" style="width: 50px; height: 50px; font-size: 1.25rem; background-color: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                <?php echo strtoupper(substr($patient['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($patient['name']); ?></h6>
                                <small class="text-muted">ID: <?php echo $patient['patient_id']; ?></small>
                            </div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">File Number:</small>
                            <p class="mb-0"><?php echo htmlspecialchars($patient['file_number']); ?></p>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Phone:</small>
                            <p class="mb-0"><?php echo htmlspecialchars($patient['phone'] ?? 'Not provided'); ?></p>
                        </div>
                    <?php elseif (!empty($all_patients)): ?>
                        <div class="mb-3">
                            <label for="patientSelect" class="form-label">Select a Patient</label>
                            <select class="form-select" id="patientSelect" onchange="selectPatient(this.value)">
                                <option value="">-- Select Patient --</option>
                                <?php foreach ($all_patients as $p): ?>
                                    <option value="<?php echo $p['patient_id']; ?>" data-phone="<?php echo htmlspecialchars($p['phone']); ?>">
                                        <?php echo htmlspecialchars($p['name']); ?> (ID: <?php echo $p['patient_id']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="text-center mt-3">
                            <p>or</p>
                            <a href="register_patient.php" class="btn btn-sm btn-primary">
                                <i class="bi bi-person-plus"></i> Register New Patient
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            No patients found in the system. <a href="register_patient.php" class="alert-link">Register a patient first</a>.
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- SMS Option -->
                <?php if ($patient || !empty($all_patients)): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="smsEnabled" name="send_sms" <?php echo $sms_gateway->isConfigured() ? 'checked' : 'disabled'; ?>>
                            <label class="form-check-label" for="smsEnabled">
                                <i class="bi bi-chat-square-text me-2"></i>
                                Send SMS confirmation
                            </label>
                        </div>
                        <?php if (!$sms_gateway->isConfigured()): ?>
                            <small class="text-muted">SMS not configured</small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
<!-- Dentist Selection -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Select Dentist</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($dentists && $dentists->num_rows > 0): ?>
                            <?php 
                            $dentists->data_seek(0); 
                            while ($dentist = $dentists->fetch_assoc()): 
                            ?>
                                <div class="dentist-card <?php echo $selected_dentist == $dentist['user_id'] ? 'selected' : ''; ?>" 
                                     onclick="selectDentist(<?php echo $dentist['user_id']; ?>)">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3" style="width: 40px; height: 40px; background-color: var(--accent-color); color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
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

            <!-- Date and Time Selection -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Select Date & Time</h5>
                    </div>
                    <div class="card-body">
                        <form id="appointmentForm" method="post" action="">
                            <input type="hidden" name="patient_id" id="selected_patient_id" value="<?php echo $patient_id; ?>">
                            <input type="hidden" id="dentist_id" name="dentist_id" value="<?php echo $selected_dentist; ?>">
                            <input type="hidden" id="selected_time" name="time">
                            
                            <!-- Date Selection -->
                            <div class="mb-4">
                                <label class="form-label">Select Date</label>
                                <input type="date" class="form-control mb-3" id="appointmentDate" name="date" 
                                       value="<?php echo $selected_date; ?>" 
                                       min="<?php echo date('Y-m-d'); ?>">
                                
                                <!-- Mini Calendar -->
                                <div class="d-flex justify-content-between mb-2">
                                    <?php
                                    $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                                    foreach ($days as $day) {
                                        echo '<div class="text-center text-muted fw-bold" style="width: 40px;">' . $day . '</div>';
                                    }
                                    ?>
                                </div>
                                
                                <div class="d-flex flex-wrap">
                                    <?php
                                    $calendar_date = new DateTime();
                                    $calendar_date->modify('first day of this month');
                                    $first_day = $calendar_date->format('w');
                                    $days_in_month = $calendar_date->format('t');
                                    
                                    // Previous month days (disabled)
                                    for ($i = 0; $i < $first_day; $i++) {
                                        echo '<div class="calendar-day disabled"></div>';
                                    }
                                    
                                    // Current month days
                                    for ($i = 1; $i <= $days_in_month; $i++) {
                                        $current_date = $calendar_date->format('Y-m') . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
                                        $is_today = $current_date == date('Y-m-d');
                                        $is_selected = $current_date == $selected_date;
                                        $is_past = $current_date < date('Y-m-d');
                                        
                                        $classes = 'calendar-day';
                                        if ($is_past) {
                                            $classes .= ' disabled';
                                        } else {
                                            if ($is_today) $classes .= ' today';
                                            if ($is_selected) $classes .= ' selected';
                                        }
                                        
                                        echo '<div class="' . $classes . '" ' . 
                                             ($is_past ? '' : 'onclick="selectDate(\'' . $current_date . '\')"') . 
                                             '>' . $i . '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
  <!-- Time Slots -->
                            <div class="mb-4">
                                <label class="form-label">Available Time Slots</label>
                                <div class="row" id="timeSlotsContainer">
                                    <?php foreach ($time_slots as $slot): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="time-slot <?php echo $slot['available'] ? 'available' : 'booked'; ?>" 
                                                 onclick="<?php echo $slot['available'] ? 'selectTimeSlot(this, \'' . $slot['start']->format('H:i:s') . '\')' : ''; ?>">
                                                <div class="time-slot-label">
                                                    <?php echo $slot['start']->format('g:i A'); ?> - <?php echo $slot['end']->format('g:i A'); ?>
                                                </div>
                                                <div class="time-slot-time">
                                                    <?php echo $slot['available'] ? 'Available' : 'Booked'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Appointment Details -->
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Appointment</label>
                                <input type="text" class="form-control" id="reason" name="reason" placeholder="E.g. Routine checkup, tooth pain, etc." required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any additional information..."></textarea>
                            </div>
                            
                            <!-- SMS Option in Form -->
                            <input type="hidden" name="send_sms" value="0" id="smsHidden">
                            
                            <div class="d-flex justify-content-between">
                                <div>
                                    <!-- Optional: Test SMS button if configured -->
                                    <?php if ($sms_gateway->isConfigured()): ?>
                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="testSMS()">
                                            <i class="bi bi-phone me-1"></i>Test SMS
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <a href="index.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary" id="submitBtn" <?php echo !$patient_id ? 'disabled' : ''; ?>>
                                        <i class="bi bi-calendar-plus me-2"></i>
                                        Schedule Appointment
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between">
                                <div>
                                    <!-- Optional: Test SMS button if configured -->
                                    <?php if ($sms_gateway->isConfigured()): ?>
                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="testSMS()">
                                            <i class="bi bi-phone me-1"></i>Test SMS
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <a href="index.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary" id="submitBtn" <?php echo !$patient_id ? 'disabled' : ''; ?>>
                                        <i class="bi bi-calendar-plus me-2"></i>
                                        Schedule Appointment
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPatient(patientId) {
            if (patientId) {
                window.location.href = 'schedule_appointment.php?patient_id=' + patientId;
            } else {
                document.getElementById('selected_patient_id').value = '';
                document.getElementById('submitBtn').disabled = true;
            }
        }
        
        function selectDentist(dentistId) {
            document.getElementById('dentist_id').value = dentistId;
            
            document.querySelectorAll('.dentist-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            updateURL();
        }
        
        function selectDate(date) {
            document.getElementById('appointmentDate').value = date;
            
            document.querySelectorAll('.calendar-day').forEach(day => {
                day.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            updateURL();
        }
        
        function selectTimeSlot(element, time) {
            document.getElementById('selected_time').value = time;
            
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            element.classList.add('selected');
            
            if (document.getElementById('selected_patient_id').value) {
                document.getElementById('submitBtn').disabled = false;
            }
        }
        
        function updateURL() {
            const patientId = document.getElementById('selected_patient_id').value;
            const date = document.getElementById('appointmentDate').value;
            const dentistId = document.getElementById('dentist_id').value;
            
            if (patientId && date && dentistId) {
                const newURL = `schedule_appointment.php?patient_id=${patientId}&date=${date}&dentist_id=${dentistId}`;
                window.history.replaceState({}, '', newURL);
                
                // Reload page to update time slots
                window.location.href = newURL;
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
        
        document.getElementById('appointmentDate').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                this.value = today.toISOString().split('T')[0];
                alert('Please select a current or future date.');
                return;
            }
            
            updateURL();
        });
        
        document.getElementById('smsEnabled').addEventListener('change', function() {
            document.getElementById('smsHidden').value = this.checked ? '1' : '0';
        });
        
        document.getElementById('appointmentForm').addEventListener('submit', function(e) {
            const patientId = document.getElementById('selected_patient_id').value;
            const timeSelected = document.getElementById('selected_time').value;
            
            if (!patientId) {
                e.preventDefault();
                alert('Please select a patient before scheduling an appointment.');
                return false;
            }
            
            if (!timeSelected) {
                e.preventDefault();
                alert('Please select an available time slot.');
                return false;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Scheduling...';
            submitBtn.disabled = true;
            
            document.getElementById('smsHidden').value = document.getElementById('smsEnabled').checked ? '1' : '0';
            
            return true;
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('smsHidden').value = document.getElementById('smsEnabled').checked ? '1' : '0';
        });
    </script>
</body>
</html>                          
                            