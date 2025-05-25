<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

include '../../includes/db_connect.php';
$staff_id = $_SESSION['user_id'];

// Function to safely execute queries with error handling
function executeQuery($conn, $query) {
    $result = $conn->query($query);
    if (!$result) {
        error_log("Query failed: " . $conn->error);
        return false;
    }
    return $result;
}

// Get analytics data for patients
$total_patients = executeQuery($conn, "SELECT COUNT(*) AS total FROM patients");
$new_patients_this_month = executeQuery($conn, "SELECT COUNT(*) AS total 
                                            FROM patients 
                                            WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                                            AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$patients_with_appointments = executeQuery($conn, "SELECT COUNT(DISTINCT patient_id) AS total 
                                                FROM appointments 
                                                WHERE status != 'Canceled'");
$patients_with_pending_payments = executeQuery($conn, "SELECT COUNT(DISTINCT p.patient_id) AS total 
                                                    FROM patients p
                                                    JOIN treatment_records tr ON p.patient_id = tr.patient_id
                                                    LEFT JOIN payments py ON tr.record_id = py.record_id
                                                    WHERE tr.cost > IFNULL(py.amount, 0)");

// Get recent patients
$recent_patients = executeQuery($conn, "
    SELECT 
        p.patient_id,
        p.file_number,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
        p.gender,
        p.date_of_birth,
        p.phone,
        p.email,
        p.created_at,
        (SELECT COUNT(*) FROM appointments a WHERE a.patient_id = p.patient_id) AS total_appointments,
        (SELECT MAX(scheduled_date) FROM appointments a WHERE a.patient_id = p.patient_id) AS last_visit
    FROM patients p
    ORDER BY p.created_at DESC
    LIMIT 10
");

// Search functionality
$search_query = '';
$searched_patients = null;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $conn->real_escape_string($_GET['search']);
    $search_query = $search_term;
    
    $searched_patients = executeQuery($conn, "
        SELECT 
            p.patient_id,
            p.file_number,
            CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
            p.gender,
            p.date_of_birth,
            p.phone,
            p.email,
            p.created_at,
            (SELECT COUNT(*) FROM appointments a WHERE a.patient_id = p.patient_id) AS total_appointments,
            (SELECT MAX(scheduled_date) FROM appointments a WHERE a.patient_id = p.patient_id) AS last_visit
        FROM patients p
        WHERE 
            p.file_number LIKE '%$search_term%' OR 
            p.first_name LIKE '%$search_term%' OR 
            p.last_name LIKE '%$search_term%' OR
            p.phone LIKE '%$search_term%'
        ORDER BY p.created_at DESC
        LIMIT 50
    ");
}

// Process query results
$patients_count = $total_patients ? $total_patients->fetch_assoc()['total'] : 0;
$new_patients = $new_patients_this_month ? $new_patients_this_month->fetch_assoc()['total'] : 0;
$patients_with_appts = $patients_with_appointments ? $patients_with_appointments->fetch_assoc()['total'] : 0;
$patients_with_pending = $patients_with_pending_payments ? $patients_with_pending_payments->fetch_assoc()['total'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Patient Management | DenTec</title>
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
    
    .avatar {
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      font-weight: 600;
      background-color: var(--primary-color);
      color: white;
    }
    
    /* Patient card specific styles */
    .patient-card {
      border-radius: 10px;
      box-shadow: 0 3px 6px rgba(0,0,0,0.05);
      transition: all 0.3s;
      border: 1px solid rgba(0,0,0,0.05);
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    
    .patient-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 15px rgba(0,0,0,0.1);
      border-color: var(--primary-color);
    }
    
    .patient-card .card-body {
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    
    .patient-card .card-body > div:last-child {
      margin-top: auto;
    }
    
    .patient-card .patient-avatar {
      width: 60px;
      height: 60px;
      font-size: 1.5rem;
    }
    
    .gender-badge {
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
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
    
    .patient-actions a {
      width: 34px;
      height: 34px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: all 0.3s;
      margin: 0 2px;
    }
    
    .patient-actions a:hover {
      transform: scale(1.1);
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
    
    /* Search form style */
    .search-form {
      background: white;
      border-radius: 50px;
      padding: 8px 15px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      display: flex;
      align-items: center;
    }
    
    .search-form input {
      border: none;
      outline: none;
      width: 100%;
      padding: 5px 10px;
    }
    
    .search-form button {
      background: var(--primary-color);
      border: none;
      border-radius: 50%;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .search-form button:hover {
      background: var(--secondary-color);
      transform: scale(1.05);
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
      <a href="dentists.php">
        <i class="bi bi-person-badge"></i> 
        <span>Dentists</span>
      </a>
      <a href="payments.php">
        <i class="bi bi-cash-coin"></i> 
        <span>Payments</span>
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
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h3 class="mb-1">Patient Management</h3>
          <p class="text-muted mb-0">Manage all patient records</p>
        </div>
        <div>
          <a href="register_patient.php" class="btn btn-primary rounded-pill">
            <i class="bi bi-person-plus me-2"></i> Register New Patient
          </a>
        </div>
      </div>

      <!-- Search & Filter Section -->
      <div class="card mb-4">
        <div class="card-body">
          <form action="" method="GET" class="row g-3">
            <div class="col-lg-8">
              <div class="search-form">
                <i class="bi bi-search text-muted"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by name, file number, phone, or email..." class="ms-2">
                <button type="submit"><i class="bi bi-arrow-right"></i></button>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary rounded-pill flex-fill">
                  <i class="bi bi-search me-1"></i> Search
                </button>
                <a href="patients.php" class="btn btn-outline-secondary rounded-pill">
                  <i class="bi bi-x-circle me-1"></i> Clear
                </a>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-primary">
            <div class="card-body position-relative">
              <h5 class="card-title">Total Patients</h5>
              <h2><?php echo $patients_count; ?></h2>
              <p class="text-muted mb-0"><small>Registered patients</small></p>
              <i class="bi bi-people icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-success">
            <div class="card-body position-relative">
              <h5 class="card-title">New This Month</h5>
              <h2><?php echo $new_patients; ?></h2>
              <p class="text-muted mb-0"><small>New patients this month</small></p>
              <i class="bi bi-person-plus icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-warning">
            <div class="card-body position-relative">
              <h5 class="card-title">Active Patients</h5>
              <h2><?php echo $patients_with_appts; ?></h2>
              <p class="text-muted mb-0"><small>With appointments</small></p>
              <i class="bi bi-calendar-check icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-info">
            <div class="card-body position-relative">
              <h5 class="card-title">Pending Payments</h5>
              <h2><?php echo $patients_with_pending; ?></h2>
              <p class="text-muted mb-0"><small>Patients with balances</small></p>
              <i class="bi bi-cash-coin icon"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Patients List -->
      <div class="table-container mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="mb-0">
            <?php if (isset($searched_patients)): ?>
              Search Results (<?php echo $searched_patients->num_rows; ?>)
            <?php else: ?>
              Recent Patients
            <?php endif; ?>
          </h5>
          <a href="all_patients.php" class="btn btn-sm btn-outline-primary rounded-pill">
            <i class="bi bi-list-ul me-1"></i> View All Patients
          </a>
        </div>
        
        <?php 
        $patients_to_display = isset($searched_patients) ? $searched_patients : $recent_patients;
        ?>

        <?php if ($patients_to_display && $patients_to_display->num_rows > 0): ?>
          <div class="row g-3">
            <?php while ($patient = $patients_to_display->fetch_assoc()): ?>
              <div class="col-md-6 col-lg-4">
                <div class="card patient-card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                      <div class="d-flex align-items-center">
                        <div class="avatar patient-avatar me-3">
                          <?php echo strtoupper(substr($patient['patient_name'], 0, 1)); ?>
                        </div>
                        <div>
                          <h6 class="mb-1"><?php echo htmlspecialchars($patient['patient_name']); ?></h6>
                          <small class="text-muted d-block"><?php echo htmlspecialchars($patient['file_number']); ?></small>
                        </div>
                      </div>
                      <div>
                        <span class="gender-badge <?php echo $patient['gender'] === 'Male' ? 'gender-male' : 'gender-female'; ?>">
                          <?php echo $patient['gender']; ?>
                        </span>
                      </div>
                    </div>
                    
                    <div class="mb-3">
                      <div class="d-flex mb-2">
                        <div style="width: 24px"><i class="bi bi-telephone text-muted"></i></div>
                        <div><?php echo htmlspecialchars($patient['phone']); ?></div>
                      </div>
                      
                      <?php if (!empty($patient['email'])): ?>
                      <div class="d-flex mb-2">
                        <div style="width: 24px"><i class="bi bi-envelope text-muted"></i></div>
                        <div><?php echo htmlspecialchars($patient['email']); ?></div>
                      </div>
                      <?php endif; ?>
                      
                      <div class="d-flex mb-2">
                        <div style="width: 24px"><i class="bi bi-calendar text-muted"></i></div>
                        <div>
                          <?php 
                            $dob = new DateTime($patient['date_of_birth']);
                            $now = new DateTime();
                            $age = $now->diff($dob)->y;
                            echo date('M j, Y', strtotime($patient['date_of_birth'])) . ' (' . $age . ' years)';
                          ?>
                        </div>
                      </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-end">
                      <div>
                        <small class="text-muted d-block">Appointments</small>
                        <span class="fw-medium"><?php echo $patient['total_appointments']; ?></span>
                        
                        <?php if (!empty($patient['last_visit'])): ?>
                        <small class="text-muted d-block mt-1">Last visit</small>
                        <span class="fw-medium"><?php echo date('M j, Y', strtotime($patient['last_visit'])); ?></span>
                        <?php endif; ?>
                      </div>
                      
                      <div class="patient-actions d-flex">
                        <a href="patient_profile.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-eye"></i>
                        </a>
                        <a href="edit_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-sm btn-outline-secondary">
                          <i class="bi bi-pencil"></i>
                        </a>
                        <a href="schedule_appointment.php?patient_id=<?php echo $patient['patient_id']; ?>" class="btn btn-sm btn-outline-success">
                          <i class="bi bi-calendar-plus"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="bi bi-search" style="font-size: 3rem; opacity: 0.2;"></i>
            <p class="mt-3 text-muted">
              <?php echo isset($searched_patients) ? 'No patients found matching your search.' : 'No patients have been registered yet.'; ?>
            </p>
            <a href="register_patient.php" class="btn btn-primary mt-2">
              <i class="bi bi-person-plus me-2"></i> Register New Patient
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
