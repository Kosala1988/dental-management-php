<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

include '../../includes/db_connect.php';

// Function to safely execute queries with error handling
function executeQuery($conn, $query) {
    $result = $conn->query($query);
    if (!$result) {
        error_log("Query failed: " . $conn->error);
        return false;
    }
    return $result;
}

// Get patient ID from URL
$patient_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($patient_id <= 0) {
    $_SESSION['error'] = "Invalid patient ID.";
    header("Location: patient.php");
    exit();
}

// Fetch patient details
$patient_query = "SELECT * FROM patients WHERE patient_id = ?";
$stmt = $conn->prepare($patient_query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient_result = $stmt->get_result();

if ($patient_result->num_rows === 0) {
    $_SESSION['error'] = "Patient not found.";
    header("Location: patient.php");
    exit();
}

$patient = $patient_result->fetch_assoc();

// Initialize variables for form
$errors = [];
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $allergies = trim($_POST['allergies'] ?? '');
    $medical_history = trim($_POST['medical_history'] ?? '');

    // Validation
    if (empty($first_name)) {
        $errors[] = "First name is required.";
    }
    if (empty($last_name)) {
        $errors[] = "Last name is required.";
    }
    if (empty($gender) || !in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors[] = "Please select a valid gender.";
    }
    if (empty($date_of_birth)) {
        $errors[] = "Date of birth is required.";
    } else {
        $dob = DateTime::createFromFormat('Y-m-d', $date_of_birth);
        if (!$dob || $dob > new DateTime()) {
            $errors[] = "Please enter a valid date of birth.";
        }
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // Check if email is already taken by another patient
    if (!empty($email)) {
        $email_check = $conn->prepare("SELECT patient_id FROM patients WHERE email = ? AND patient_id != ?");
        $email_check->bind_param("si", $email, $patient_id);
        $email_check->execute();
        if ($email_check->get_result()->num_rows > 0) {
            $errors[] = "This email address is already registered to another patient.";
        }
    }

    // Check if phone is already taken by another patient
    $phone_check = $conn->prepare("SELECT patient_id FROM patients WHERE phone = ? AND patient_id != ?");
    $phone_check->bind_param("si", $phone, $patient_id);
    $phone_check->execute();
    if ($phone_check->get_result()->num_rows > 0) {
        $errors[] = "This phone number is already registered to another patient.";
    }

    // If no errors, update the patient
    if (empty($errors)) {
        $update_query = "UPDATE patients SET 
                        first_name = ?, 
                        last_name = ?, 
                        gender = ?, 
                        date_of_birth = ?, 
                        phone = ?, 
                        email = ?, 
                        address = ?, 
                        city = ?, 
                        state = ?, 
                        postal_code = ?, 
                        blood_group = ?, 
                        allergies = ?, 
                        medical_history = ?,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE patient_id = ?";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssssssssssssi", 
            $first_name, $last_name, $gender, $date_of_birth, 
            $phone, $email, $address, $city, $state, 
            $postal_code, $blood_group, $allergies, $medical_history, $patient_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Patient information updated successfully!";
            header("Location: patient_profile.php?id=" . $patient_id);
            exit();
        } else {
            $errors[] = "Failed to update patient information. Please try again.";
        }
    }
} else {
    // Pre-populate form with existing data
    $first_name = $patient['first_name'];
    $last_name = $patient['last_name'];
    $gender = $patient['gender'];
    $date_of_birth = $patient['date_of_birth'];
    $phone = $patient['phone'];
    $email = $patient['email'];
    $address = $patient['address'];
    $city = $patient['city'];
    $state = $patient['state'];
    $postal_code = $patient['postal_code'];
    $blood_group = $patient['blood_group'];
    $allergies = $patient['allergies'];
    $medical_history = $patient['medical_history'];
}

// Calculate age for display
$dob = new DateTime($patient['date_of_birth']);
$now = new DateTime();
$age = $now->diff($dob)->y;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Patient - <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?> | DenTec</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
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
    
    .form-container {
      background: white;
      border-radius: 15px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      padding: 30px;
      margin-bottom: 30px;
    }
    
    .avatar {
      width: 80px;
      height: 80px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      font-weight: 600;
      background-color: var(--primary-color);
      color: white;
      font-size: 2rem;
    }
    
    .back-button {
      background: rgba(255,255,255,0.9);
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: 50px;
      padding: 8px 20px;
      text-decoration: none;
      color: var(--dark-text);
      display: inline-flex;
      align-items: center;
      transition: all 0.3s;
      margin-bottom: 20px;
    }
    
    .back-button:hover {
      background: white;
      color: var(--primary-color);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .form-section {
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid rgba(0,0,0,0.1);
    }
    
    .form-section:last-child {
      border-bottom: none;
      margin-bottom: 0;
    }
    
    .form-section h5 {
      color: var(--primary-color);
      margin-bottom: 1rem;
      font-weight: 600;
    }
    
    .form-control:focus {
      border-color: var(--accent-color);
      box-shadow: 0 0 0 0.2rem rgba(73, 149, 239, 0.25);
    }
    
    .form-select:focus {
      border-color: var(--accent-color);
      box-shadow: 0 0 0 0.2rem rgba(73, 149, 239, 0.25);
    }
    
    .btn-primary {
      background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
      border: none;
      border-radius: 50px;
      padding: 12px 30px;
      font-weight: 600;
      transition: all 0.3s;
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 15px rgba(67, 97, 238, 0.4);
    }
    
    .btn-outline-secondary {
      border-radius: 50px;
      padding: 12px 30px;
      font-weight: 600;
    }
    
    .alert {
      border-radius: 10px;
      border: none;
    }
    
    .required {
      color: var(--danger-color);
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
      <a href="schedule_appointment.php">
        <i class="bi bi-calendar-check"></i> 
        <span>Appointments</span>
      </a>
      <a href="patient.php" class="active">
        <i class="bi bi-people"></i> 
        <span>Patients</span>
      </a>
      <a href="#">
        <i class="bi bi-person-badge"></i> 
        <span>Dentists</span>
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
      <!-- Back Button -->
      <a href="patient_profile.php?id=<?php echo $patient_id; ?>" class="back-button">
        <i class="bi bi-arrow-left me-2"></i> Back to Patient Profile
      </a>

      <!-- Patient Header -->
      <div class="form-container mb-4">
        <div class="row align-items-center">
          <div class="col-auto">
            <div class="avatar">
              <?php echo strtoupper(substr($patient['first_name'], 0, 1)); ?>
            </div>
          </div>
          <div class="col">
            <h2 class="mb-1">Edit Patient Information</h2>
            <p class="text-muted mb-0">
              <strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></strong> 
              (<?php echo $age; ?> years old) • File: <?php echo htmlspecialchars($patient['file_number']); ?>
            </p>
          </div>
        </div>
      </div>

      <!-- Error/Success Messages -->
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>Please correct the following errors:</strong>
          <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Edit Form -->
      <form method="POST" class="form-container">
        <!-- Personal Information -->
        <div class="form-section">
          <h5><i class="bi bi-person me-2"></i>Personal Information</h5>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="first_name" class="form-label">First Name <span class="required">*</span></label>
              <input type="text" class="form-control" id="first_name" name="first_name" 
                     value="<?php echo htmlspecialchars($first_name); ?>" required>
            </div>
            <div class="col-md-6">
              <label for="last_name" class="form-label">Last Name <span class="required">*</span></label>
              <input type="text" class="form-control" id="last_name" name="last_name" 
                     value="<?php echo htmlspecialchars($last_name); ?>" required>
            </div>
            <div class="col-md-4">
              <label for="gender" class="form-label">Gender <span class="required">*</span></label>
              <select class="form-select" id="gender" name="gender" required>
                <option value="">Select Gender</option>
                <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo $gender === 'Other' ? 'selected' : ''; ?>>Other</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="date_of_birth" class="form-label">Date of Birth <span class="required">*</span></label>
              <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                     value="<?php echo htmlspecialchars($date_of_birth); ?>" required>
            </div>
            <div class="col-md-4">
              <label for="blood_group" class="form-label">Blood Group</label>
              <select class="form-select" id="blood_group" name="blood_group">
                <option value="">Select Blood Group</option>
                <option value="A+" <?php echo $blood_group === 'A+' ? 'selected' : ''; ?>>A+</option>
                <option value="A-" <?php echo $blood_group === 'A-' ? 'selected' : ''; ?>>A-</option>
                <option value="B+" <?php echo $blood_group === 'B+' ? 'selected' : ''; ?>>B+</option>
                <option value="B-" <?php echo $blood_group === 'B-' ? 'selected' : ''; ?>>B-</option>
                <option value="AB+" <?php echo $blood_group === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                <option value="AB-" <?php echo $blood_group === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                <option value="O+" <?php echo $blood_group === 'O+' ? 'selected' : ''; ?>>O+</option>
                <option value="O-" <?php echo $blood_group === 'O-' ? 'selected' : ''; ?>>O-</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Contact Information -->
        <div class="form-section">
          <h5><i class="bi bi-telephone me-2"></i>Contact Information</h5>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="phone" class="form-label">Phone Number <span class="required">*</span></label>
              <input type="tel" class="form-control" id="phone" name="phone" 
                     value="<?php echo htmlspecialchars($phone); ?>" required>
            </div>
            <div class="col-md-6">
              <label for="email" class="form-label">Email Address</label>
              <input type="email" class="form-control" id="email" name="email" 
                     value="<?php echo htmlspecialchars($email); ?>">
            </div>
            <div class="col-12">
              <label for="address" class="form-label">Address</label>
              <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($address); ?></textarea>
            </div>
            <div class="col-md-6">
              <label for="city" class="form-label">City</label>
              <input type="text" class="form-control" id="city" name="city" 
                     value="<?php echo htmlspecialchars($city); ?>">
            </div>
            <div class="col-md-3">
              <label for="state" class="form-label">State/Province</label>
              <input type="text" class="form-control" id="state" name="state" 
                     value="<?php echo htmlspecialchars($state); ?>">
            </div>
            <div class="col-md-3">
              <label for="postal_code" class="form-label">Postal Code</label>
              <input type="text" class="form-control" id="postal_code" name="postal_code" 
                     value="<?php echo htmlspecialchars($postal_code); ?>">
            </div>
          </div>
        </div>

        <!-- Medical Information -->
        <div class="form-section">
          <h5><i class="bi bi-heart-pulse me-2"></i>Medical Information</h5>
          <div class="row g-3">
            <div class="col-12">
              <label for="allergies" class="form-label">Allergies</label>
              <textarea class="form-control" id="allergies" name="allergies" rows="3" 
                        placeholder="List any known allergies (medications, foods, materials, etc.)"><?php echo htmlspecialchars($allergies); ?></textarea>
            </div>
            <div class="col-12">
              <label for="medical_history" class="form-label">Medical History</label>
              <textarea class="form-control" id="medical_history" name="medical_history" rows="4" 
                        placeholder="Previous medical conditions, surgeries, current medications, etc."><?php echo htmlspecialchars($medical_history); ?></textarea>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex gap-3 justify-content-end">
          <a href="patient_profile.php?id=<?php echo $patient_id; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-2"></i>Cancel
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i>Update Patient
          </button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Form validation
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('form');
      const phoneInput = document.getElementById('phone');
      
      // Phone number formatting
      phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 10) {
          value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
        }
        e.target.value = value;
      });
      
      // Form submission validation
      form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('[required]');
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
          e.preventDefault();
          alert('Please fill in all required fields.');
        }
      });
      
      // Real-time validation feedback
      const inputs = form.querySelectorAll('input, select, textarea');
      inputs.forEach(input => {
        input.addEventListener('blur', function() {
          if (this.hasAttribute('required') && !this.value.trim()) {
            this.classList.add('is-invalid');
          } else {
            this.classList.remove('is-invalid');
          }
        });
      });
    });
  </script>
</body>
</html>