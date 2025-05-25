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

// Get patient's appointment history
$appointments_query = "
    SELECT 
        a.appointment_id,
        a.scheduled_date, 
        a.start_time, 
        a.end_time,
        u.full_name AS dentist_name,
        a.status,
        a.reason,
        a.created_at
    FROM appointments a
    JOIN users u ON a.dentist_id = u.user_id
    WHERE a.patient_id = ?
    ORDER BY a.scheduled_date DESC, a.start_time DESC
    LIMIT 10
";
$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments = $stmt->get_result();

// Get appointment statistics
$total_appointments = executeQuery($conn, "SELECT COUNT(*) AS total FROM appointments WHERE patient_id = $patient_id");
$completed_appointments = executeQuery($conn, "SELECT COUNT(*) AS total FROM appointments WHERE patient_id = $patient_id AND status = 'Completed'");
$upcoming_appointments = executeQuery($conn, "SELECT COUNT(*) AS total FROM appointments WHERE patient_id = $patient_id AND scheduled_date > CURDATE()");

$total_appts = $total_appointments ? $total_appointments->fetch_assoc()['total'] : 0;
$completed_appts = $completed_appointments ? $completed_appointments->fetch_assoc()['total'] : 0;
$upcoming_appts = $upcoming_appointments ? $upcoming_appointments->fetch_assoc()['total'] : 0;

// Calculate age
$dob = new DateTime($patient['date_of_birth']);
$now = new DateTime();
$age = $now->diff($dob)->y;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Patient Profile - <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?> | DenTec</title>
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
    
    .card-primary {
      border-left: 5px solid var(--primary-color);
    }
    
    .card-success {
      border-left: 5px solid var(--success-color);
    }
    
    .card-warning {
      border-left: 5px solid var(--warning-color);
    }
    
    .card-info {
      border-left: 5px solid var(--accent-color);
    }
    
    .table-container {
      background: white;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      padding: 20px;
      margin-bottom: 30px;
    }
    
    .status-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
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
    
    .patient-info-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      padding: 25px;
      margin-bottom: 30px;
    }
    
    .gender-badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    
    .gender-male {
      background-color: rgba(73, 149, 239, 0.1);
      color: #4995ef;
    }
    
    .gender-female {
      background-color: rgba(243, 92, 184, 0.1);
      color: #f35cb8;
    }
    
    .dentist-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      background-color: rgba(67, 97, 238, 0.1);
      color: var(--primary-color);
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
      <a href="patient.php" class="back-button">
        <i class="bi bi-arrow-left me-2"></i> Back to Patients
      </a>

      <!-- Patient Header -->
      <div class="patient-info-card">
        <div class="row align-items-center">
          <div class="col-auto">
            <div class="avatar">
              <?php echo strtoupper(substr($patient['first_name'], 0, 1)); ?>
            </div>
          </div>
          <div class="col">
            <div class="d-flex align-items-center gap-3 mb-2">
              <h2 class="mb-0"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h2>
              <span class="gender-badge <?php echo $patient['gender'] === 'Male' ? 'gender-male' : 'gender-female'; ?>">
                <?php echo $patient['gender']; ?>
              </span>
            </div>
            <p class="text-muted mb-3">File Number: <?php echo htmlspecialchars($patient['file_number']); ?></p>
            
            <div class="row g-4">
              <div class="col-md-3">
                <div class="d-flex align-items-center">
                  <i class="bi bi-telephone me-2 text-primary"></i>
                  <div>
                    <small class="text-muted d-block">Phone</small>
                    <span><?php echo htmlspecialchars($patient['phone']); ?></span>
                  </div>
                </div>
              </div>
              
              <?php if (!empty($patient['email'])): ?>
              <div class="col-md-3">
                <div class="d-flex align-items-center">
                  <i class="bi bi-envelope me-2 text-primary"></i>
                  <div>
                    <small class="text-muted d-block">Email</small>
                    <span><?php echo htmlspecialchars($patient['email']); ?></span>
                  </div>
                </div>
              </div>
              <?php endif; ?>
              
              <div class="col-md-3">
                <div class="d-flex align-items-center">
                  <i class="bi bi-calendar me-2 text-primary"></i>
                  <div>
                    <small class="text-muted d-block">Age</small>
                    <span><?php echo $age; ?> years old</span>
                  </div>
                </div>
              </div>
              
              <div class="col-md-3">
                <div class="d-flex align-items-center">
                  <i class="bi bi-calendar-plus me-2 text-primary"></i>
                  <div>
                    <small class="text-muted d-block">Registered</small>
                    <span><?php echo date('M j, Y', strtotime($patient['created_at'])); ?></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-auto">
            <div class="d-flex gap-2">
              <a href="edit_patient.php?id=<?php echo $patient_id; ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit
              </a>
              <a href="schedule_appointment.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-primary">
                <i class="bi bi-calendar-plus me-1"></i> Schedule
              </a>
            </div>
          </div>
        </div>
        
        <?php if (!empty($patient['address'])): ?>
        <div class="row mt-3">
          <div class="col-12">
            <div class="d-flex align-items-center">
              <i class="bi bi-geo-alt me-2 text-primary"></i>
              <div>
                <small class="text-muted d-block">Address</small>
                <span><?php echo htmlspecialchars($patient['address']); ?></span>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Statistics Cards -->
      <div class="row g-4 mb-4">
        <div class="col-md-4">
          <div class="card card-stat card-primary">
            <div class="card-body position-relative">
              <h5 class="card-title">Total Appointments</h5>
              <h2><?php echo $total_appts; ?></h2>
              <p class="text-muted mb-0"><small>All time appointments</small></p>
              <i class="bi bi-calendar-check icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card card-stat card-success">
            <div class="card-body position-relative">
              <h5 class="card-title">Completed</h5>
              <h2><?php echo $completed_appts; ?></h2>
              <p class="text-muted mb-0"><small>Completed appointments</small></p>
              <i class="bi bi-check-circle icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card card-stat card-warning">
            <div class="card-body position-relative">
              <h5 class="card-title">Upcoming</h5>
              <h2><?php echo $upcoming_appts; ?></h2>
              <p class="text-muted mb-0"><small>Future appointments</small></p>
              <i class="bi bi-calendar-week icon"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Appointment History -->
      <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="mb-0">Appointment History</h5>
          <a href="schedule_appointment.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-sm btn-primary rounded-pill">
            <i class="bi bi-calendar-plus me-1"></i> New Appointment
          </a>
        </div>
        
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Date & Time</th>
                <th>Dentist</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($appointments && $appointments->num_rows > 0): ?>
                <?php while ($appointment = $appointments->fetch_assoc()): ?>
                  <tr>
                    <td>
                      <div>
                        <span class="fw-medium"><?php echo date('M j, Y', strtotime($appointment['scheduled_date'])); ?></span>
                        <small class="text-muted d-block">
                          <?php echo date('h:i A', strtotime($appointment['start_time'])); ?> - 
                          <?php echo date('h:i A', strtotime($appointment['end_time'])); ?>
                        </small>
                      </div>
                    </td>
                    <td>
                      <span class="dentist-badge">
                        <i class="bi bi-person-badge me-1"></i>
                        <?php echo htmlspecialchars($appointment['dentist_name']); ?>
                      </span>
                    </td>
                    <td><?php echo htmlspecialchars($appointment['reason'] ?? 'General Checkup'); ?></td>
                    <td>
                      <?php
                        $status_class = '';
                        if ($appointment['status'] === 'Completed') $status_class = 'status-completed';
                        elseif ($appointment['status'] === 'Scheduled') $status_class = 'status-scheduled';
                        elseif ($appointment['status'] === 'Canceled') $status_class = 'status-canceled';
                      ?>
                      <span class="status-badge <?php echo $status_class; ?>"><?php echo $appointment['status']; ?></span>
                    </td>
                    <td>
                      <div class="btn-group">
                        <a href="appointment_details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-eye"></i>
                        </a>
                        <?php if ($appointment['status'] === 'Scheduled'): ?>
                        <a href="edit_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-outline-secondary">
                          <i class="bi bi-pencil"></i>
                        </a>
                        <a href="cancel_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-outline-danger">
                          <i class="bi bi-x-circle"></i>
                        </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">
                    <i class="bi bi-calendar-x" style="font-size: 2rem; opacity: 0.3;"></i>
                    <p class="mt-2">No appointments found for this patient</p>
                    <a href="schedule_appointment.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-primary mt-2">
                      <i class="bi bi-calendar-plus me-2"></i> Schedule First Appointment
                    </a>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>