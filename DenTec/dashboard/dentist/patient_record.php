<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dentist') {
    header("Location: ../../login.php");
    exit();
}

include '../../includes/db_connect.php';
$dentist_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$patient_id = intval($_GET['id']);

// Get patient details
$patient_result = $conn->query("
    SELECT p.*, TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS age
    FROM patients p WHERE p.patient_id = $patient_id
");

if (!$patient_result || $patient_result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$patient = $patient_result->fetch_assoc();

$success_message = '';
$error_message = '';

// HANDLE TREATMENT COMPLETION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_treatment'])) {
    $record_id = intval($_POST['record_id']);
    
    $update_query = "UPDATE treatment_records SET status = 'Completed' WHERE record_id = ? AND dentist_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $record_id, $dentist_id);
    
    if ($stmt->execute()) {
        $success_message = "Treatment marked as completed successfully!";
    } else {
        $error_message = "Error updating treatment status: " . $conn->error;
    }
    $stmt->close();
    
    // Redirect to prevent form resubmission
    if (!empty($success_message)) {
        header("Location: patient_record.php?id=$patient_id&success=" . urlencode($success_message));
        exit();
    }
}

// HANDLE TREATMENT CANCELLATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_treatment'])) {
    $record_id = intval($_POST['record_id']);
    
    $update_query = "UPDATE treatment_records SET status = 'Cancelled' WHERE record_id = ? AND dentist_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $record_id, $dentist_id);
    
    if ($stmt->execute()) {
        $success_message = "Treatment cancelled successfully!";
    } else {
        $error_message = "Error cancelling treatment: " . $conn->error;
    }
    $stmt->close();
    
    // Redirect to prevent form resubmission
    if (!empty($success_message)) {
        header("Location: patient_record.php?id=$patient_id&success=" . urlencode($success_message));
        exit();
    }
}

// ADD TREATMENT PROCESSING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_treatment'])) {
    $selected_teeth = isset($_POST['selected_teeth']) ? $_POST['selected_teeth'] : [];
    $treatment_id = isset($_POST['treatment_id']) ? intval($_POST['treatment_id']) : 0;
    $treatment_cost = isset($_POST['treatment_cost']) ? floatval($_POST['treatment_cost']) : 0;
    $treatment_notes = isset($_POST['treatment_notes']) ? $conn->real_escape_string($_POST['treatment_notes']) : '';
    $treatment_date = date('Y-m-d');
    
    if ($treatment_id > 0 && $treatment_cost > 0) {
        if (!empty($selected_teeth)) {
            // Insert for selected teeth
            $successful_inserts = 0;
            foreach ($selected_teeth as $tooth_id) {
                $tooth_id = intval($tooth_id);
                
                $insert_query = "
                    INSERT INTO treatment_records 
                        (patient_id, dentist_id, treatment_id, tooth_id, diagnosis, treatment_notes, cost, status, treatment_date) 
                    VALUES 
                        (?, ?, ?, ?, 'Routine care', ?, ?, 'Planned', ?)
                ";
                
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("iiiisds", $patient_id, $dentist_id, $treatment_id, $tooth_id, $treatment_notes, $treatment_cost, $treatment_date);
                
                if ($stmt->execute()) {
                    $successful_inserts++;
                } else {
                    $error_message = "Error adding treatment: " . $conn->error;
                    break;
                }
            }
            
            if ($successful_inserts > 0) {
                $success_message = "Treatment plan added successfully for $successful_inserts teeth.";
            }
        } else {
            // Insert general treatment
            $insert_query = "
                INSERT INTO treatment_records 
                    (patient_id, dentist_id, treatment_id, tooth_id, diagnosis, treatment_notes, cost, status, treatment_date) 
                VALUES 
                    (?, ?, ?, NULL, 'General treatment', ?, ?, 'Planned', ?)
            ";
            
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iiisds", $patient_id, $dentist_id, $treatment_id, $treatment_notes, $treatment_cost, $treatment_date);
            
            if ($stmt->execute()) {
                $success_message = "Treatment plan added successfully.";
            } else {
                $error_message = "Error adding treatment: " . $conn->error;
            }
        }
        
        // Redirect to prevent form resubmission
        if (!empty($success_message)) {
            header("Location: patient_record.php?id=$patient_id&success=" . urlencode($success_message));
            exit();
        }
    } else {
        if ($treatment_id <= 0) {
            $error_message = "Please select a treatment.";
        }
        if ($treatment_cost <= 0) {
            $error_message = "Please enter a valid cost.";
        }
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Get patient treatments
$treatments = $conn->query("
    SELECT tr.*, t.name AS treatment_name, dt.universal_number AS tooth_number,
           dt.name AS tooth_name, u.full_name AS dentist_name
    FROM treatment_records tr
    JOIN treatments t ON tr.treatment_id = t.treatment_id
    LEFT JOIN dental_teeth dt ON tr.tooth_id = dt.tooth_id
    LEFT JOIN users u ON tr.dentist_id = u.user_id
    WHERE tr.patient_id = $patient_id
    ORDER BY tr.treatment_date DESC, tr.created_at DESC
");

// Get available treatments
$available_treatments = $conn->query("
    SELECT treatment_id, name, cost FROM treatments WHERE is_active = 1 ORDER BY name ASC
");

// Get all teeth
$teeth = $conn->query("
    SELECT * FROM dental_teeth ORDER BY quadrant, universal_number ASC
");

// Get treatments by tooth for coloring
$tooth_treatments = $conn->query("
    SELECT dt.tooth_id, dt.universal_number AS tooth_number, t.name AS treatment_name,
           tr.treatment_date, tr.status
    FROM treatment_records tr
    JOIN treatments t ON tr.treatment_id = t.treatment_id
    JOIN dental_teeth dt ON tr.tooth_id = dt.tooth_id
    WHERE tr.patient_id = $patient_id
    ORDER BY tr.treatment_date DESC
");

// Process tooth treatments for chart
$tooth_status = [];
if ($tooth_treatments && $tooth_treatments->num_rows > 0) {
    while ($row = $tooth_treatments->fetch_assoc()) {
        if (!isset($tooth_status[$row['tooth_number']])) {
            $tooth_status[$row['tooth_number']] = [
                'treatment' => $row['treatment_name'],
                'date' => $row['treatment_date'],
                'status' => $row['status']
            ];
        }
    }
}

// Treatment colors
$treatment_colors = [
    'Extraction' => '#f94144', 'Filling' => '#90be6d', 'Root Canal' => '#f8961e',
    'Crown' => '#f9c74f', 'Cleaning' => '#43aa8b', 'Examination' => '#adb5bd'
];
$default_color = '#adb5bd';

function getTreatmentColor($treatment_name, $treatment_colors, $default_color) {
    foreach ($treatment_colors as $key => $color) {
        if (stripos($treatment_name, $key) !== false) {
            return $color;
        }
    }
    return $default_color;
}

function getQuadrantNumber($universal_number) {
    if ($universal_number >= 1 && $universal_number <= 8) {
        return 9 - $universal_number;
    } else if ($universal_number >= 9 && $universal_number <= 16) {
        return $universal_number - 8;
    } else if ($universal_number >= 17 && $universal_number <= 24) {
        return $universal_number - 16;
    } else if ($universal_number >= 25 && $universal_number <= 32) {
        return 33 - $universal_number;
    }
    return $universal_number;
}

function getQuadrantLabel($universal_number) {
    if ($universal_number >= 1 && $universal_number <= 8) return "UR";
    else if ($universal_number >= 9 && $universal_number <= 16) return "UL";
    else if ($universal_number >= 17 && $universal_number <= 24) return "LL";
    else if ($universal_number >= 25 && $universal_number <= 32) return "LR";
    return "";
}

function displayToothNumber($tooth, $tooth_status, $treatment_colors, $default_color) {
    $universal_number = $tooth['universal_number'];
    $local_number = getQuadrantNumber($universal_number);
    
    $tooth_status_class = '';
    $tooth_color = '';
    $tooltip_text = '';
    
    if (isset($tooth_status[$universal_number])) {
        $status = $tooth_status[$universal_number];
        $tooth_status_class = 'treated';
        $tooth_color = getTreatmentColor($status['treatment'], $treatment_colors, $default_color);
        $tooltip_text = $status['treatment'] . ' - ' . date('M j, Y', strtotime($status['date'])) . ' (' . $status['status'] . ')';
    }
    
    echo '<div class="tooth-compact ' . $tooth_status_class . '" data-tooth-id="' . $tooth['tooth_id'] . '" data-tooth-number="' . $universal_number . '">';
    echo '<div class="tooth-graphic" style="' . ($tooth_color ? 'border-color: ' . $tooth_color . '; background-color: ' . $tooth_color . '15;' : '') . '">' . $local_number . '</div>';
    
    if ($tooltip_text) {
        echo '<div class="tooth-tooltip">' . htmlspecialchars($tooltip_text) . '</div>';
    }
    
    echo '</div>';
}

// Process treatments data for display
$treatments_data = [];
if ($treatments && $treatments->num_rows > 0) {
    while ($row = $treatments->fetch_assoc()) {
        $treatments_data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Record | DenTec</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4cc9f0;
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
        
        .patient-header {
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .patient-avatar {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 700;
            font-size: 2rem;
            background-color: white;
            color: var(--primary-color);
            border: 3px solid white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .info-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .info-box:hover {
            box-shadow: 0 6px 10px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        
        .info-box h5 {
            color: var(--primary-color);
            border-bottom: 2px solid rgba(67, 97, 238, 0.2);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .nav-tabs {
            border-bottom: 2px solid rgba(67, 97, 238, 0.2);
            margin-bottom: 20px;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: var(--light-text);
            font-weight: 500;
            padding: 10px 15px;
            margin-right: 5px;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border: none;
            border-bottom: 2px solid var(--primary-color);
            background-color: transparent;
            font-weight: 600;
        }
        
        /* FIXED DENTAL CHART STYLES */
        .dental-chart-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .dental-row {
            margin-bottom: 30px;
        }

        .quadrant-label {
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
            font-size: 1.1rem;
        }

        .teeth-row {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .quadrant-section {
            display: flex;
            gap: 8px;
            padding: 10px;
            border-radius: 8px;
            background-color: #f8f9fa;
        }

        .tooth-compact {
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tooth-compact:hover {
            transform: scale(1.1);
        }

        .tooth-compact .tooth-graphic {
            width: 40px;
            height: 40px;
            border: 3px solid #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: white;
        }

        .tooth-compact.selected .tooth-graphic {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 12px rgba(67, 97, 238, 0.6);
            transform: scale(1.1);
        }

        .tooth-compact.treated .tooth-graphic {
            border-width: 4px;
            font-weight: bold;
        }

        .dental-midline {
            text-align: center;
            font-size: 1rem;
            color: var(--light-text);
            position: relative;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
            font-weight: 500;
        }

        .dental-midline::before,
        .dental-midline::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
            margin: 0 20px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-completed { 
            background-color: rgba(25, 135, 84, 0.1); 
            color: #198754; 
            border: 1px solid rgba(25, 135, 84, 0.3);
        }
        .status-planned { 
            background-color: rgba(255, 193, 7, 0.1); 
            color: #ffc107; 
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        .status-cancelled { 
            background-color: rgba(220, 53, 69, 0.1); 
            color: #dc3545; 
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .tooth-tooltip {
            position: absolute;
            bottom: 120%;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(33, 37, 41, 0.95);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            white-space: nowrap;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .tooth-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: rgba(33, 37, 41, 0.95);
        }
        
        .tooth-compact:hover .tooth-tooltip {
            opacity: 1;
        }
        
        .treatment-form {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            position: sticky;
            top: 20px;
        }
        
        .alert-floating {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn-complete {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }
        
        .btn-complete:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: white;
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
            .quadrant-section {
                gap: 4px;
            }
            .tooth-compact .tooth-graphic {
                width: 35px;
                height: 35px;
                font-size: 12px;
            }
            .teeth-row {
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <h5 class="text-white mb-4 ps-2">Dr. <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></h5>
            <a href="index.php">
                <i class="bi bi-speedometer2"></i> 
                <span>Dashboard</span>
            </a>
            <a href="#" class="active">
                <i class="bi bi-people"></i> 
                <span>Patients</span>
            </a>
            <a href="#">
                <i class="bi bi-clipboard2-pulse"></i> 
                <span>Treatment Records</span>
            </a>
            <a href="../../logout.php" class="mt-auto">
                <i class="bi bi-box-arrow-right"></i> 
                <span>Logout</span>
            </a>
        </div>

        <!-- Main Content -->
        <div class="main flex-fill">
            <!-- Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show alert-floating" role="alert">
                    <i class="bi bi-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show alert-floating" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Back Button -->
            <div class="mb-3">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            
            <!-- Patient Header -->
            <div class="patient-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="patient-avatar me-4">
                        <?php echo strtoupper(substr($patient['first_name'] ?? 'P', 0, 1)); ?>
                    </div>
                    <div>
                        <h2 class="mb-1"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h2>
                        <p class="mb-0">ID: <?php echo $patient['patient_id']; ?> • Age: <?php echo $patient['age']; ?> • 
                            <?php echo $patient['gender']; ?> • 
                            <i class="bi bi-telephone me-1"></i> <?php echo htmlspecialchars($patient['phone']); ?>
                        </p>
                    </div>
                </div>
                <div class="text-end">
                    <a href="#" class="btn btn-light me-2">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a href="#" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> New Appointment
                    </a>
                </div>
            </div>
            
            <!-- Patient Info Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="info-box">
                        <h5><i class="bi bi-person-lines-fill me-2"></i> Personal Info</h5>
                        <div class="row mb-2">
                            <div class="col-4 text-muted">Email:</div>
                            <div class="col-8"><?php echo htmlspecialchars($patient['email'] ?? 'Not provided'); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 text-muted">Address:</div>
                            <div class="col-8"><?php echo htmlspecialchars($patient['address'] ?? 'Not provided'); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 text-muted">Birth Date:</div>
                            <div class="col-8"><?php echo date('M j, Y', strtotime($patient['date_of_birth'])); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-4 text-muted">Blood Type:</div>
                            <div class="col-8"><?php echo htmlspecialchars($patient['blood_group'] ?? 'Not recorded'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="info-box">
                        <h5><i class="bi bi-activity me-2"></i> Treatment Summary</h5>
                        <?php if (!empty($treatments_data)): 
                            $total_cost = array_sum(array_column($treatments_data, 'cost'));
                            $completed_count = count(array_filter($treatments_data, function($t) { return $t['status'] === 'Completed'; }));
                            $planned_count = count(array_filter($treatments_data, function($t) { return $t['status'] === 'Planned'; }));
                        ?>
                            <div class="row text-center">
                                <div class="col-3">
                                    <div class="text-primary h4"><?php echo count($treatments_data); ?></div>
                                    <small class="text-muted">Total</small>
                                </div>
                                <div class="col-3">
                                    <div class="text-success h4"><?php echo $completed_count; ?></div>
                                    <small class="text-muted">Completed</small>
                                </div>
                                <div class="col-3">
                                    <div class="text-warning h4"><?php echo $planned_count; ?></div>
                                    <small class="text-muted">Planned</small>
                                </div>
                                <div class="col-3">
                                    <div class="text-info h4">$<?php echo number_format($total_cost, 0); ?></div>
                                    <small class="text-muted">Cost</small>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">No treatments recorded yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Main Tabs -->
            <ul class="nav nav-tabs" id="patientTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="dental-tab" data-bs-toggle="tab" data-bs-target="#dental-tab-pane" type="button" role="tab">
                        <i class="bi bi-tooth me-1"></i> Dental Chart
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="treatment-tab" data-bs-toggle="tab" data-bs-target="#treatment-tab-pane" type="button" role="tab">
                        <i class="bi bi-clipboard2-pulse me-1"></i> Treatment History
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="patientTabsContent">
                <!-- Dental Chart Tab -->
                <div class="tab-pane fade show active" id="dental-tab-pane" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="info-box">
                                <h5><i class="bi bi-tooth me-2"></i> Dental Chart</h5>
                                <p class="text-muted mb-4">Click on teeth to select for treatment. Colors show treatment status.</p>
                                
                                <div class="dental-chart-container">
                                    <!-- Upper Jaw -->
                                    <div class="dental-row upper-jaw">
                                        <div class="quadrant-label">Upper Teeth</div>
                                        <div class="teeth-row">
                                            <!-- Upper Right (8 to 1) -->
                                            <div class="quadrant-section">
                                                <small class="text-muted mb-2 d-block text-center">Upper Right</small>
                                                <div class="d-flex gap-2">
                                                    <?php
                                                    if ($teeth && $teeth->num_rows > 0) {
                                                        $teeth_data = [];
                                                        $teeth->data_seek(0);
                                                        while ($tooth = $teeth->fetch_assoc()) {
                                                            $teeth_data[] = $tooth;
                                                        }
                                                        
                                                        $upper_right = array_filter($teeth_data, function($tooth) {
                                                            return $tooth['quadrant'] === 'Upper Right';
                                                        });
                                                        
                                                        usort($upper_right, function($a, $b) {
                                                            return $b['universal_number'] - $a['universal_number'];
                                                        });
                                                        
                                                        foreach ($upper_right as $tooth) {
                                                            displayToothNumber($tooth, $tooth_status, $treatment_colors, $default_color);
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Upper Left (1 to 8) -->
                                            <div class="quadrant-section">
                                                <small class="text-muted mb-2 d-block text-center">Upper Left</small>
                                                <div class="d-flex gap-2">
                                                    <?php
                                                    $upper_left = array_filter($teeth_data, function($tooth) {
                                                        return $tooth['quadrant'] === 'Upper Left';
                                                    });
                                                    
                                                    usort($upper_left, function($a, $b) {
                                                        return ($a['universal_number'] - 8) - ($b['universal_number'] - 8);
                                                    });
                                                    
                                                    foreach ($upper_left as $tooth) {
                                                        displayToothNumber($tooth, $tooth_status, $treatment_colors, $default_color);
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="dental-midline">Dental Midline</div>
                                    
                                    <!-- Lower Jaw -->
                                    <div class="dental-row lower-jaw">
                                        <div class="quadrant-label">Lower Teeth</div>
                                        <div class="teeth-row">
                                            <!-- Lower Left (1 to 8) -->
                                            <div class="quadrant-section">
                                                <small class="text-muted mb-2 d-block text-center">Lower Left</small>
                                                <div class="d-flex gap-2">
                                                    <?php
                                                    $lower_left = array_filter($teeth_data, function($tooth) {
                                                        return $tooth['quadrant'] === 'Lower Left';
                                                    });
                                                    
                                                    usort($lower_left, function($a, $b) {
                                                        return ($a['universal_number'] - 16) - ($b['universal_number'] - 16);
                                                    });
                                                    
                                                    foreach ($lower_left as $tooth) {
                                                        displayToothNumber($tooth, $tooth_status, $treatment_colors, $default_color);
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Lower Right (8 to 1) -->
                                            <div class="quadrant-section">
                                                <small class="text-muted mb-2 d-block text-center">Lower Right</small>
                                                <div class="d-flex gap-2">
                                                    <?php
                                                    $lower_right = array_filter($teeth_data, function($tooth) {
                                                        return $tooth['quadrant'] === 'Lower Right';
                                                    });
                                                    
                                                    usort($lower_right, function($a, $b) {
                                                        return ($b['universal_number'] - 24) - ($a['universal_number'] - 24);
                                                    });
                                                    
                                                    foreach ($lower_right as $tooth) {
                                                        displayToothNumber($tooth, $tooth_status, $treatment_colors, $default_color);
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Legend -->
                                <div class="mt-4">
                                    <h6>Treatment Status Legend:</h6>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="d-flex align-items-center">
                                            <div style="width: 20px; height: 20px; border: 3px solid #90be6d; border-radius: 4px; background-color: #90be6d15; margin-right: 8px;"></div>
                                            <small>Filling</small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div style="width: 20px; height: 20px; border: 3px solid #f94144; border-radius: 4px; background-color: #f9414415; margin-right: 8px;"></div>
                                            <small>Extraction</small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div style="width: 20px; height: 20px; border: 3px solid #f8961e; border-radius: 4px; background-color: #f8961e15; margin-right: 8px;"></div>
                                            <small>Root Canal</small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div style="width: 20px; height: 20px; border: 3px solid #43aa8b; border-radius: 4px; background-color: #43aa8b15; margin-right: 8px;"></div>
                                            <small>Cleaning</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Treatment Form -->
                        <div class="col-lg-4">
                            <div class="treatment-form">
                                <h5><i class="bi bi-clipboard-plus me-2"></i> Add Treatment Plan</h5>
                                <p class="text-muted small mb-4">Select teeth and add treatment to create a plan</p>
                                
                                <form method="POST" action="patient_record.php?id=<?php echo $patient_id; ?>">
                                    <input type="hidden" name="add_treatment" value="1">
                                    
                                    <div class="mb-3">
                                        <label for="treatment_id" class="form-label">Treatment <span class="text-danger">*</span></label>
                                        <select class="form-select" id="treatment_id" name="treatment_id" required>
                                            <option value="">Select treatment</option>
                                            <?php if ($available_treatments && $available_treatments->num_rows > 0):
                                                $available_treatments->data_seek(0);
                                                while ($tr = $available_treatments->fetch_assoc()): ?>
                                                    <option value="<?php echo $tr['treatment_id']; ?>" 
                                                            data-cost="<?php echo $tr['cost']; ?>">
                                                        <?php echo htmlspecialchars($tr['name']); ?> - $<?php echo number_format($tr['cost'], 2); ?>
                                                    </option>
                                                <?php endwhile;
                                            endif; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="treatment_cost" class="form-label">Cost ($) <span class="text-danger">*</span></label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="treatment_cost" 
                                               name="treatment_cost" 
                                               step="0.01" 
                                               min="0" 
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="treatment_notes" class="form-label">Treatment Notes</label>
                                        <textarea class="form-control" 
                                                  id="treatment_notes" 
                                                  name="treatment_notes" 
                                                  rows="3" 
                                                  placeholder="Additional treatment notes..."></textarea>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Selected Teeth <span class="text-muted">(Optional)</span></label>
                                        <div id="selected-teeth-display" class="p-3 border rounded bg-light">
                                            <span class="text-muted">No teeth selected - General treatment</span>
                                        </div>
                                        <div id="selected-teeth-container">
                                            <!-- Hidden inputs for selected teeth will be added here by JavaScript -->
                                        </div>
                                        <small class="form-text text-muted">Click on teeth above to select specific teeth for treatment</small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-plus-circle me-2"></i> Add Treatment Plan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Treatment History Tab -->
                <div class="tab-pane fade" id="treatment-tab-pane" role="tabpanel">
                    <div class="info-box">
                        <h5><i class="bi bi-clipboard2-pulse me-2"></i> Treatment History</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Treatment</th>
                                        <th>Tooth</th>
                                        <th>Dentist</th>
                                        <th>Cost</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if (!empty($treatments_data)): 
                                        foreach ($treatments_data as $tr):
                                            $status_class = '';
                                            if ($tr['status'] === 'Completed') $status_class = 'status-completed';
                                            elseif ($tr['status'] === 'Planned') $status_class = 'status-planned';
                                            elseif ($tr['status'] === 'Cancelled') $status_class = 'status-cancelled';
                                    ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($tr['treatment_date'])); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($tr['treatment_name']); ?></strong>
                                                <?php if (!empty($tr['treatment_notes'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($tr['treatment_notes']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($tr['tooth_name'])): ?>
                                                    <?php 
                                                    if (isset($tr['tooth_number'])) {
                                                        $quad_label = getQuadrantLabel($tr['tooth_number']);
                                                        $quad_num = getQuadrantNumber($tr['tooth_number']);
                                                        echo '<span class="badge bg-light text-dark">' . $quad_label . $quad_num . '</span><br>';
                                                        echo '<small class="text-muted">' . htmlspecialchars($tr['tooth_name']) . '</small>';
                                                    } else {
                                                        echo htmlspecialchars($tr['tooth_name']);
                                                    }
                                                    ?>
                                                <?php else: ?>
                                                    <span class="text-muted">General</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($tr['dentist_name']); ?></td>
                                            <td><strong>$<?php echo number_format($tr['cost'], 2); ?></strong></td>
                                            <td>
                                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $tr['status']; ?></span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($tr['status'] === 'Planned'): ?>
                                                        <form method="POST" style="display: inline-block;" 
                                                              onsubmit="return confirm('Mark this treatment as completed?')">
                                                            <input type="hidden" name="complete_treatment" value="1">
                                                            <input type="hidden" name="record_id" value="<?php echo $tr['record_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-complete" title="Complete Treatment">
                                                                <i class="bi bi-check-circle"></i>
                                                            </button>
                                                        </form>
                                                        
                                                        <form method="POST" style="display: inline-block;" 
                                                              onsubmit="return confirm('Cancel this treatment?')">
                                                            <input type="hidden" name="cancel_treatment" value="1">
                                                            <input type="hidden" name="record_id" value="<?php echo $tr['record_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel Treatment">
                                                                <i class="bi bi-x-circle"></i>
                                                            </button>
                                                        </form>
                                                    <?php elseif ($tr['status'] === 'Completed'): ?>
                                                        <span class="text-success small">
                                                            <i class="bi bi-check-circle-fill me-1"></i>Completed
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">
                                                            <i class="bi bi-x-circle-fill me-1"></i>Cancelled
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                        endforeach; 
                                    else: 
                                    ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-5">
                                                <div>
                                                    <i class="bi bi-clipboard-x" style="font-size: 3rem; opacity: 0.3;"></i>
                                                    <p class="mt-3 mb-0">No treatment records found</p>
                                                    <small>Add treatments using the dental chart tab</small>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Patient Record JS loaded');
            
            // Tooth selection functionality
            const teeth = document.querySelectorAll('.tooth-compact');
            const selectedTeethDisplay = document.getElementById('selected-teeth-display');
            const selectedTeethContainer = document.getElementById('selected-teeth-container');
            let selectedTeeth = [];
            
            teeth.forEach(tooth => {
                tooth.addEventListener('click', function() {
                    const toothId = this.dataset.toothId;
                    const toothNumber = this.dataset.toothNumber;
                    const index = selectedTeeth.findIndex(t => t.id === toothId);
                    
                    if (index > -1) {
                        // Deselect tooth
                        selectedTeeth.splice(index, 1);
                        this.classList.remove('selected');
                    } else {
                        // Select tooth
                        selectedTeeth.push({
                            id: toothId,
                            number: parseInt(toothNumber)
                        });
                        this.classList.add('selected');
                    }
                    
                    updateSelectedTeethDisplay();
                });
            });
            
            function updateSelectedTeethDisplay() {
                if (selectedTeeth.length > 0) {
                    const teethLabels = selectedTeeth.map(t => {
                        const quadrant = getQuadrantLabel(t.number);
                        const localNum = getQuadrantNumber(t.number);
                        return `${quadrant}${localNum}`;
                    }).sort();
                    
                    selectedTeethDisplay.innerHTML = `
                        <div class="d-flex flex-wrap gap-1">
                            ${teethLabels.map(label => `<span class="badge bg-primary">${label}</span>`).join('')}
                        </div>
                    `;
                } else {
                    selectedTeethDisplay.innerHTML = '<span class="text-muted">No teeth selected - General treatment</span>';
                }
                
                // Update hidden inputs
                selectedTeethContainer.innerHTML = '';
                selectedTeeth.forEach(tooth => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_teeth[]';
                    input.value = tooth.id;
                    selectedTeethContainer.appendChild(input);
                });
            }
            
            // Helper functions
            function getQuadrantLabel(universalNumber) {
                if (universalNumber >= 1 && universalNumber <= 8) return "UR";
                else if (universalNumber >= 9 && universalNumber <= 16) return "UL";
                else if (universalNumber >= 17 && universalNumber <= 24) return "LL";
                else if (universalNumber >= 25 && universalNumber <= 32) return "LR";
                return "";
            }
            
            function getQuadrantNumber(universalNumber) {
                if (universalNumber >= 1 && universalNumber <= 8) {
                    return 9 - universalNumber;
                } else if (universalNumber >= 9 && universalNumber <= 16) {
                    return universalNumber - 8;
                } else if (universalNumber >= 17 && universalNumber <= 24) {
                    return universalNumber - 16;
                } else if (universalNumber >= 25 && universalNumber <= 32) {
                    return 33 - universalNumber;
                }
                return universalNumber;
            }
            
            // Treatment cost auto-update
            const treatmentSelect = document.getElementById('treatment_id');
            const costInput = document.getElementById('treatment_cost');
            
            if (treatmentSelect && costInput) {
                treatmentSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const defaultCost = selectedOption.dataset.cost;
                    
                    if (defaultCost && defaultCost !== '') {
                        costInput.value = parseFloat(defaultCost).toFixed(2);
                    } else {
                        costInput.value = '';
                    }
                });
            }
            
            // Form submission validation
            const form = document.querySelector('form[method="POST"]');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const treatmentId = document.getElementById('treatment_id').value;
                    const cost = document.getElementById('treatment_cost').value;
                    
                    if (!treatmentId) {
                        alert('Please select a treatment');
                        e.preventDefault();
                        return false;
                    }
                    
                    if (!cost || cost <= 0) {
                        alert('Please enter a valid cost');
                        e.preventDefault();
                        return false;
                    }
                });
            }
            
            // Auto-dismiss alerts
            const alerts = document.querySelectorAll('.alert-floating');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>