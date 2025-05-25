<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

include '../../includes/db_connect.php';

// Initialize variables
$success_message = '';
$error_message = '';
$form_data = [
    'file_number' => '',
    'first_name' => '',
    'last_name' => '',
    'gender' => '',
    'date_of_birth' => '',
    'phone' => '',
    'email' => '',
    'address' => '',
    'city' => '',
    'state' => '',
    'postal_code' => '',
    'blood_group' => '',
    'allergies' => '',
    'medical_history' => ''
];

// Generate a new file number (Format: PT + Year + Sequential Number)
$current_year = date('Y');
$query = "SELECT MAX(SUBSTRING_INDEX(file_number, '-', -1)) as last_number FROM patients WHERE file_number LIKE 'PT$current_year-%'";
$result = $conn->query($query);
$row = $result->fetch_assoc();
$next_number = 1;
if ($row && $row['last_number']) {
    $next_number = (int)$row['last_number'] + 1;
}
$file_number = "PT$current_year-" . str_pad($next_number, 4, '0', STR_PAD_LEFT);
$form_data['file_number'] = $file_number;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $form_data = [
        'file_number' => $_POST['file_number'],
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'gender' => $_POST['gender'],
        'date_of_birth' => $_POST['date_of_birth'],
        'phone' => $_POST['phone'],
        'email' => $_POST['email'] ?: null,
        'address' => $_POST['address'] ?: null,
        'city' => $_POST['city'] ?: null,
        'state' => $_POST['state'] ?: null,
        'postal_code' => $_POST['postal_code'] ?: null,
        'blood_group' => $_POST['blood_group'] ?: null,
        'allergies' => $_POST['allergies'] ?: null,
        'medical_history' => $_POST['medical_history'] ?: null
    ];
    
    // Validate required fields
    $required_fields = ['file_number', 'first_name', 'last_name', 'gender', 'date_of_birth', 'phone'];
    $is_valid = true;
    
    foreach ($required_fields as $field) {
        if (empty($form_data[$field])) {
            $is_valid = false;
            $error_message = "Please fill all required fields.";
            break;
        }
    }
    
    // Process photo upload if present
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/patients/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $new_filename = 'patient_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;
        
        // Check file type
        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($file_extension), $allowed_types)) {
            $is_valid = false;
            $error_message = "Only JPG, JPEG, and PNG files are allowed.";
        } else if ($_FILES['photo']['size'] > 5000000) { // 5MB max
            $is_valid = false;
            $error_message = "File size cannot exceed 5MB.";
        } else if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            $photo_path = 'uploads/patients/' . $new_filename;
        } else {
            $is_valid = false;
            $error_message = "Failed to upload image.";
        }
    }
    
    // Insert data into database if valid
    if ($is_valid) {
        $sql = "INSERT INTO patients (
                    file_number, photo, first_name, last_name, gender, date_of_birth, 
                    phone, email, address, city, state, postal_code, 
                    blood_group, allergies, medical_history
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssssssssss", 
            $form_data['file_number'],
            $photo_path,
            $form_data['first_name'],
            $form_data['last_name'],
            $form_data['gender'],
            $form_data['date_of_birth'],
            $form_data['phone'],
            $form_data['email'],
            $form_data['address'],
            $form_data['city'],
            $form_data['state'],
            $form_data['postal_code'],
            $form_data['blood_group'],
            $form_data['allergies'],
            $form_data['medical_history']
        );
        
        if ($stmt->execute()) {
            $patient_id = $conn->insert_id;
            $success_message = "Patient registered successfully.";
            
            // Check if user wants to schedule an appointment immediately
            if (isset($_POST['schedule_appointment']) && $_POST['schedule_appointment'] === 'yes') {
                header("Location: schedule_appointment.php?patient=$patient_id");
                exit();
            } else {
                // Clear form data for new entry
                $form_data = [
                    'file_number' => '',
                    'first_name' => '',
                    'last_name' => '',
                    'gender' => '',
                    'date_of_birth' => '',
                    'phone' => '',
                    'email' => '',
                    'address' => '',
                    'city' => '',
                    'state' => '',
                    'postal_code' => '',
                    'blood_group' => '',
                    'allergies' => '',
                    'medical_history' => ''
                ];
                
                // Generate new file number for next patient
                $next_number++;
                $file_number = "PT$current_year-" . str_pad($next_number, 4, '0', STR_PAD_LEFT);
                $form_data['file_number'] = $file_number;
            }
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Patient | DenTec</title>
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
        }
        
        .page-header {
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            color: white;
            position: relative;
            margin-bottom: 25px;
        }
        
        .end-3 {
            right: 1rem;
        }
        
        .top-3 {
            top: 1rem;
        }
        
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
        
        .required-field::after {
            content: "*";
            color: var(--danger-color);
            margin-left: 4px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .btn-outline-secondary {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-outline-secondary:hover {
            transform: translateY(-2px);
        }
        
        .photo-preview {
            width: 150px;
            height: 150px;
            border-radius: 8px;
            border: 2px dashed #ced4da;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            overflow: hidden;
        }
        
        .photo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        /* Form sections */
        .form-section {
            border-left: 4px solid var(--primary-color);
            padding-left: 15px;
            margin-bottom: 20px;
        }
        
        .form-section-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1060;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .sidebar {
                width: 200px;
            }
            .main {
                margin-left: 200px;
            }
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
            .d-flex.flex-column.flex-md-row {
                flex-direction: column !important;
            }
            .col-md-6 {
                margin-bottom: 15px;
            }
        }
        
        /* Quick action buttons */
        .action-bar {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .toggle-sections {
            display: none;
        }
        
        .section-toggle {
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .section-toggle:hover {
            color: var(--primary-color);
        }
        
        /* Advanced smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Custom switch styling */
        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
        }
        
        .form-switch .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <h5 class="text-white mb-4 ps-2"><?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></h5>
            <a href="index.php">
                <i class="bi bi-speedometer2"></i> 
                <span>Dashboard</span>
            </a>
            <a href="appointments.php">
                <i class="bi bi-calendar-check"></i> 
                <span>Appointments</span>
            </a>
            <a href="patient.php" class="active">
                <i class="bi bi-people"></i> 
                <span>Patients</span>
            </a>
            <a href="dentists.php">
                <i class="bi bi-person-badge"></i> 
                <span>Dentists</span>
            </a>
            <a href="reports.php">
                <i class="bi bi-file-earmark-text"></i> 
                <span>Reports</span>
            </a>
            <a href="settings.php">
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
            <!-- Page Header -->
            <div class="page-header bg-primary text-white">
                <div class="container-fluid py-4">
                    <h4 class="mb-1">Register New Patient</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="patients.php" class="text-white opacity-75">Patients</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Register New Patient</li>
                        </ol>
                    </nav>
                    <a href="patients.php" class="btn btn-light rounded-pill px-4 py-2 shadow-sm position-absolute end-3 top-3">
                        <i class="bi bi-arrow-left me-2"></i>Back to Patients
                    </a>
                </div>
            </div>
            
            <!-- Action Bar -->
            <div class="action-bar">
                <div>
                    <h5 class="mb-0">New Patient Registration</h5>
                    <p class="text-muted mb-0">Fill in the patient details below</p>
                </div>
                <div class="d-flex">
                    <button type="button" class="btn btn-outline-secondary me-2 d-none d-md-block" id="clearFormBtn">
                        <i class="bi bi-eraser me-2"></i>Clear Form
                    </button>
                    <div class="d-md-none">
                        <button class="btn btn-sm btn-primary toggle-sections" type="button" data-bs-toggle="collapse" data-bs-target="#formSectionsNav">
                            <i class="bi bi-list"></i> Jump to Section
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Section Navigation -->
            <div class="collapse mb-3" id="formSectionsNav">
                <div class="card">
                    <div class="list-group list-group-flush">
                        <a href="#personalInfo" class="list-group-item section-toggle">Personal Information</a>
                        <a href="#contactInfo" class="list-group-item section-toggle">Contact Information</a>
                        <a href="#medicalInfo" class="list-group-item section-toggle">Medical Information</a>
                    </div>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Registration Form -->
            <form action="register_patient.php" method="post" enctype="multipart/form-data" id="patientRegistrationForm">
                <!-- Personal Information Section -->
                <div class="card" id="personalInfo">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person me-2 text-primary"></i>Personal Information</span>
                        <span class="badge bg-primary">Step 1 of 3</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div>
                                    <label class="form-label">Patient Photo</label>
                                    <div class="photo-preview" id="photoPreview">
                                        <i class="bi bi-person-bounding-box" style="font-size: 3rem; opacity: 0.5;"></i>
                                    </div>
                                    <input type="file" class="form-control" name="photo" id="photoInput" accept="image/*">
                                    <div class="form-text">Max file size: 5MB</div>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label required-field">File Number</label>
                                        <input type="text" class="form-control" name="file_number" value="<?php echo htmlspecialchars($form_data['file_number']); ?>" readonly>
                                        <div class="form-text">Auto-generated patient ID</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required-field">Date of Birth</label>
                                        <input type="date" class="form-control" name="date_of_birth" value="<?php echo htmlspecialchars($form_data['date_of_birth']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required-field">First Name</label>
                                        <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($form_data['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required-field">Last Name</label>
                                        <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($form_data['last_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label required-field">Gender</label>
                                        <select class="form-select" name="gender" required>
                                            <option value="" disabled <?php echo empty($form_data['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                                            <option value="Male" <?php echo $form_data['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $form_data['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo $form_data['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Blood Group</label>
                                        <select class="form-select" name="blood_group">
                                            <option value="" <?php echo empty($form_data['blood_group']) ? 'selected' : ''; ?>>Select Blood Group</option>
                                            <option value="A+" <?php echo $form_data['blood_group'] === 'A+' ? 'selected' : ''; ?>>A+</option>
                                            <option value="A-" <?php echo $form_data['blood_group'] === 'A-' ? 'selected' : ''; ?>>A-</option>
                                            <option value="B+" <?php echo $form_data['blood_group'] === 'B+' ? 'selected' : ''; ?>>B+</option>
                                            <option value="B-" <?php echo $form_data['blood_group'] === 'B-' ? 'selected' : ''; ?>>B-</option>
                                            <option value="AB+" <?php echo $form_data['blood_group'] === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                            <option value="AB-" <?php echo $form_data['blood_group'] === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                            <option value="O+" <?php echo $form_data['blood_group'] === 'O+' ? 'selected' : ''; ?>>O+</option>
                                            <option value="O-" <?php echo $form_data['blood_group'] === 'O-' ? 'selected' : ''; ?>>O-</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information Section -->
                <div class="card" id="contactInfo">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-telephone me-2 text-primary"></i>Contact Information</span>
                        <span class="badge bg-primary">Step 2 of 3</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required-field">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($form_data['phone']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($form_data['email']); ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($form_data['address']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($form_data['city']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State/Province</label>
                                <input type="text" class="form-control" name="state" value="<?php echo htmlspecialchars($form_data['state']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Postal Code</label>
                                <input type="text" class="form-control" name="postal_code" value="<?php echo htmlspecialchars($form_data['postal_code']); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Medical Information Section -->
                <div class="card" id="medicalInfo">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-hospital me-2 text-primary"></i>Medical Information</span>
                        <span class="badge bg-primary">Step 3 of 3</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Allergies</label>
                                <textarea class="form-control" name="allergies" rows="2"><?php echo htmlspecialchars($form_data['allergies']); ?></textarea>
                                <div class="form-text">List any known allergies to medications, materials, or food</div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Medical History</label>
                                <textarea class="form-control" name="medical_history" rows="3"><?php echo htmlspecialchars($form_data['medical_history']); ?></textarea>
                                <div class="form-text">Include any relevant medical conditions, previous dental work, or ongoing treatments</div>
                            </div>
                            <div class="col-md-12 mt-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="schedule_appointment" value="yes" id="scheduleAppointment">
                                    <label class="form-check-label" for="scheduleAppointment">
                                        Schedule an appointment for this patient after registration
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="d-flex justify-content-between mb-4">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php'">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <div>
                        <button type="button" class="btn btn-outline-primary me-2 d-none d-md-inline-block" id="saveAsDraft">
                            <i class="bi bi-save me-2"></i>Save as Draft
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-check me-2"></i>Register Patient
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Toast Notification Container -->
    <div class="toast-container"></div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Photo Preview
        document.getElementById('photoInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewContainer = document.getElementById('photoPreview');
                    previewContainer.innerHTML = '';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    previewContainer.appendChild(img);
                };
                
                reader.readAsDataURL(file);
            }
        });
        
        // Form Validation and Submission
        document.getElementById('patientRegistrationForm').addEventListener('submit', function(event) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                event.preventDefault();
                showToast('Please fill all required fields.', 'danger');
                
                // Scroll to the first invalid field
                const firstInvalidField = this.querySelector('.is-invalid');
                if (firstInvalidField) {
                    firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalidField.focus();
                }
            }
        });
        
        // Clear Form Button
        document.getElementById('clearFormBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear the form? All entered data will be lost.')) {
                const form = document.getElementById('patientRegistrationForm');
                
                // Reset all inputs except file_number
                const inputs = form.querySelectorAll('input:not([name="file_number"]), select, textarea');
                inputs.forEach(input => {
                    if (input.type === 'checkbox') {
                        input.checked = false;
                    } else {
                        input.value = '';
                    }
                    input.classList.remove('is-invalid');
                });
                
                // Reset photo preview
                document.getElementById('photoPreview').innerHTML = '<i class="bi bi-person-bounding-box" style="font-size: 3rem; opacity: 0.5;"></i>';
                
                showToast('Form has been cleared', 'info');
            }
        });
        
        // Save as Draft functionality
        document.getElementById('saveAsDraft').addEventListener('click', function() {
            // In a real implementation, this would save the data to localStorage or to the server
            // For demo purposes, we'll just show a toast
            showToast('Draft saved successfully', 'success');
        });
        
        // Section navigation for mobile
        document.querySelectorAll('.section-toggle').forEach(link => {
            link.addEventListener('click', function() {
                // Close the mobile navigation dropdown
                const bsCollapse = bootstrap.Collapse.getInstance(document.getElementById('formSectionsNav'));
                if (bsCollapse) {
                    bsCollapse.hide();
                }
            });
        });
        
        // Toast Notification Function
        function showToast(message, type = 'info') {
            const toastContainer = document.querySelector('.toast-container');
            
            const toastElement = document.createElement('div');
            toastElement.classList.add('toast', 'align-items-center', 'border-0');
            toastElement.classList.add(`bg-${type === 'info' ? 'primary' : type}`);
            toastElement.classList.add('text-white');
            toastElement.setAttribute('role', 'alert');
            toastElement.setAttribute('aria-live', 'assertive');
            toastElement.setAttribute('aria-atomic', 'true');
            
            const iconClass = type === 'success' ? 'bi-check-circle' : 
                             type === 'danger' ? 'bi-exclamation-triangle' :
                             type === 'warning' ? 'bi-exclamation-circle' : 'bi-info-circle';
            
            toastElement.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${iconClass} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            toastContainer.appendChild(toastElement);
            
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 3000
            });
            
            toast.show();
            
            // Remove the toast element after it's hidden
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }
        
        // Phone number formatting
        const phoneInput = document.querySelector('input[name="phone"]');
        phoneInput.addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
        
        // Confirm before leaving page with unsaved changes
        let formChanged = false;
        
        document.getElementById('patientRegistrationForm').addEventListener('change', function() {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        // Input validation for specific fields
        document.querySelector('input[name="email"]').addEventListener('blur', function() {
            if (this.value && !isValidEmail(this.value)) {
                this.classList.add('is-invalid');
                showToast('Please enter a valid email address', 'warning');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        function isValidEmail(email) {
            const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        }
        
        // Date of birth validation (must be in the past)
        document.querySelector('input[name="date_of_birth"]').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            
            if (selectedDate > today) {
                this.classList.add('is-invalid');
                showToast('Date of birth cannot be in the future', 'warning');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        // Calculate age automatically from date of birth
        document.querySelector('input[name="date_of_birth"]').addEventListener('change', function() {
            const dob = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            
            // We could display this age somewhere in the form if needed
            console.log('Patient age: ' + age);
        });
        
        // Initialize any needed Bootstrap components
        document.addEventListener('DOMContentLoaded', function() {
            // Enable all tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>