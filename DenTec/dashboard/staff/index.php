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

// Get analytics data for staff dashboard
$total_patients = executeQuery($conn, "SELECT COUNT(*) AS total FROM patients");
$total_appointments = executeQuery($conn, "SELECT COUNT(*) AS total FROM appointments");
$todays_total_appointments = executeQuery($conn, "SELECT COUNT(*) AS total 
                                                FROM appointments 
                                                WHERE scheduled_date = CURDATE()");
$pending_appointments = executeQuery($conn, "SELECT COUNT(*) AS total 
                                           FROM appointments 
                                           WHERE status = 'Scheduled'");
$this_week_appointments = executeQuery($conn, "SELECT COUNT(*) AS total 
                                              FROM appointments 
                                              WHERE YEARWEEK(scheduled_date, 1) = YEARWEEK(CURDATE(), 1)");

// Get today's appointments for all dentists
$todays_appointments = executeQuery($conn, "
    SELECT 
        a.appointment_id,
        a.scheduled_date, 
        a.start_time, 
        a.end_time,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name, 
        u.full_name AS dentist_name,
        a.status,
        p.patient_id,
        a.reason,
        a.dentist_id
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users u ON a.dentist_id = u.user_id
    WHERE a.scheduled_date = CURDATE()
    ORDER BY a.start_time ASC
");

// Get upcoming appointments for next 7 days
$upcoming_appointments = executeQuery($conn, "
    SELECT 
        a.appointment_id,
        a.scheduled_date, 
        a.start_time, 
        a.end_time,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name, 
        u.full_name AS dentist_name,
        a.status,
        p.patient_id,
        a.reason,
        a.dentist_id
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users u ON a.dentist_id = u.user_id
    WHERE a.scheduled_date > CURDATE()
    AND a.scheduled_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY a.scheduled_date ASC, a.start_time ASC
    LIMIT 10
");

// Get recent patients registered
$recent_patients = executeQuery($conn, "
    SELECT 
        patient_id,
        CONCAT(first_name, ' ', last_name) AS patient_name,
        phone,
        email,
        created_at
    FROM patients
    ORDER BY created_at DESC
    LIMIT 5
");

// Get all dentists to show for scheduling
$dentists = executeQuery($conn, "
    SELECT 
        user_id,
        full_name
    FROM users
    WHERE role = 'dentist'
    ORDER BY full_name ASC
");

// Process query results
$patients_count = $total_patients ? $total_patients->fetch_assoc()['total'] : 0;
$appointments_count = $total_appointments ? $total_appointments->fetch_assoc()['total'] : 0;
$today_appts = $todays_total_appointments ? $todays_total_appointments->fetch_assoc()['total'] : 0;
$pending_appts = $pending_appointments ? $pending_appointments->fetch_assoc()['total'] : 0;
$this_week_appts = $this_week_appointments ? $this_week_appointments->fetch_assoc()['total'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Staff Dashboard | DenTec</title>
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
    
    .dentist-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      background-color: rgba(67, 97, 238, 0.1);
      color: var(--primary-color);
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
    
    /* Quick action buttons */
    .quick-action {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: white;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      padding: 20px;
      height: 100%;
      transition: all 0.3s ease;
      text-decoration: none;
      color: var(--dark-text);
    }
    
    .quick-action:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px rgba(0,0,0,0.1);
      color: var(--primary-color);
    }
    
    .quick-action i {
      font-size: 2.5rem;
      margin-bottom: 10px;
      color: var(--primary-color);
    }
    
    .quick-action span {
      font-weight: 500;
    }
  </style>
</head>
<body>
  <div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar">
      <h5 class="text-white mb-4 ps-2"><?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></h5>
      <a href="index.php" class="active">
        <i class="bi bi-speedometer2"></i> 
        <span>Dashboard</span>
      </a>
<a href="schedule_appointment.php">
  <i class="bi bi-calendar-check"></i> 
  <span>Appointments</span>
  <?php if ($pending_appts > 0): ?>
    <span class="badge bg-warning ms-auto"><?php echo $pending_appts; ?></span>
  <?php endif; ?>
</a>
      <a href="patient.php">
        <i class="bi bi-people"></i> 
        <span>Patients</span>
      </a>
      <a href="#">
        <i class="bi bi-person-badge"></i> 
        <span>Dentists</span>
      </a>
            <a href="payment.php">
        <i class="bi bi-currency-dollar"></i> 
        <span>Payments</span>
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
      <div class="welcome-header mb-4 p-4 rounded" style="background: linear-gradient(90deg, var(--primary-color), var(--accent-color)); color: white;">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h3 class="mb-2">Good <?php echo date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening'); ?>, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'] ?? '')[0]); ?></h3>
            <p class="mb-0">There <?php echo $today_appts != 1 ? 'are' : 'is'; ?> <?php echo $today_appts; ?> appointment<?php echo $today_appts != 1 ? 's' : ''; ?> scheduled for today</p>
          </div>
          <div class="text-end">
            <p class="mb-1"><i class="bi bi-calendar-check me-2"></i> <?php echo date('l, F j, Y'); ?></p>
            <p class="mb-0"><i class="bi bi-clock-history me-2"></i> <span id="current-time"><?php echo date('h:i A'); ?></span></p>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Quick Actions</h5>
          </div>
          <div class="row g-3">
            <div class="col-md-3 col-6">
              <a href="schedule_appointment.php" class="quick-action">
                <i class="bi bi-calendar-plus"></i>
                <span>New Appointment</span>
              </a>
            </div>

            <div class="col-md-3 col-6">
              <a href="register_patient.php" class="quick-action">
                <i class="bi bi-person-plus"></i>
                <span>Register Patient</span>
                <small class="d-block mt-1 text-primary">Add new patient</small>
              </a>
            </div>

            <div class="col-md-3 col-6">
              <a href="payment.php" class="quick-action">
                <i class="bi bi-currency-dollar"></i>
                <span>Record Payment</span>
              </a>


            </div>
            <div class="col-md-3 col-6">
              <a href="reports.php" class="quick-action">
                <i class="bi bi-file-earmark-text"></i>
                <span>Generate Report</span>
              </a>
            </div>
          </div>
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
              <h5 class="card-title">Today's Appointments</h5>
              <h2><?php echo $today_appts; ?></h2>
              <p class="text-muted mb-0"><small>Scheduled for today</small></p>
              <i class="bi bi-calendar-check icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-warning">
            <div class="card-body position-relative">
              <h5 class="card-title">Pending Appointments</h5>
              <h2><?php echo $pending_appts; ?></h2>
              <p class="text-muted mb-0"><small>Yet to be completed</small></p>
              <i class="bi bi-hourglass-split icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-info">
            <div class="card-body position-relative">
              <h5 class="card-title">Weekly Schedule</h5>
              <h2><?php echo $this_week_appts; ?></h2>
              <p class="text-muted mb-0"><small>This week's appointments</small></p>
              <i class="bi bi-calendar-week icon"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Today's Appointments -->
      <div class="row g-4 mb-4">
        <div class="col-12">
          <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="mb-0">Today's Appointments</h5>
              <a href="schedule_appointment.php" class="btn btn-sm btn-outline-primary rounded-pill">
                <i class="bi bi-calendar"></i> View All
              </a>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Patient</th>
                    <th>Time</th>
                    <th>Dentist</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($todays_appointments && $todays_appointments->num_rows > 0): ?>
                    <?php while ($row = $todays_appointments->fetch_assoc()): ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center">
                            <div class="avatar me-3">
                              <?php echo strtoupper(substr(explode(' ', $row['patient_name'])[0], 0, 1)); ?>
                            </div>
                            <div>
                              <div><?php echo htmlspecialchars($row['patient_name']); ?></div>
                              <small class="text-muted">ID: <?php echo $row['patient_id']; ?></small>
                            </div>
                          </div>
                        </td>
                        <td><?php echo date('h:i A', strtotime($row['start_time'])); ?> - <?php echo date('h:i A', strtotime($row['end_time'])); ?></td>
                        <td>
                          <span class="dentist-badge">
                            <i class="bi bi-person-badge me-1"></i>
                            <?php echo htmlspecialchars($row['dentist_name']); ?>
                          </span>
                        </td>
                        <td>
                          <?php
                            $status_class = '';
                            if ($row['status'] === 'Completed') $status_class = 'status-completed';
                            elseif ($row['status'] === 'Scheduled') $status_class = 'status-scheduled';
                            elseif ($row['status'] === 'Canceled') $status_class = 'status-canceled';
                          ?>
                          <span class="status-badge <?php echo $status_class; ?>"><?php echo $row['status']; ?></span>
                        </td>
                        <td>
                          <div class="btn-group">
                            <a href="patient_record.php?id=<?php echo $row['patient_id']; ?>" class="btn btn-sm btn-outline-primary">
                              <i class="bi bi-file-earmark-text"></i>
                            </a>
                            <a href="check_in.php?id=<?php echo $row['appointment_id']; ?>" class="btn btn-sm btn-outline-success">
                              <i class="bi bi-check-circle"></i>
                            </a>
                            <a href="cancel_appointment.php?id=<?php echo $row['appointment_id']; ?>" class="btn btn-sm btn-outline-danger">
                              <i class="bi bi-x-circle"></i>
                            </a>
                          </div>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="5" class="text-center text-muted py-4">
                        <i class="bi bi-calendar-x" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p class="mt-2">No appointments scheduled for today</p>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Recently Registered Patients and Upcoming Appointments -->
      <div class="row g-4 mb-4">
        <div class="col-lg-6">
          <div class="table-container h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="mb-0">Recently Registered Patients</h5>
              <a href="patient.php" class="btn btn-sm btn-outline-primary rounded-pill">
                <i class="bi bi-people"></i> View All
              </a>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Patient</th>
                    <th>Contact</th>
                    <th>Registered</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($recent_patients && $recent_patients->num_rows > 0): ?>
                    <?php while ($row = $recent_patients->fetch_assoc()): ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center">
                            <div class="avatar me-3">
                              <?php echo strtoupper(substr(explode(' ', $row['patient_name'])[0], 0, 1)); ?>
                            </div>
                            <div>
                              <div><?php echo htmlspecialchars($row['patient_name']); ?></div>
                              <small class="text-muted">ID: <?php echo $row['patient_id']; ?></small>
                            </div>
                          </div>
                        </td>
                        <td>
                          <?php echo htmlspecialchars($row['phone']); ?>
                          <?php if (!empty($row['email'])): ?>
                            <small class="text-muted d-block"><?php echo htmlspecialchars($row['email']); ?></small>
                          <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                        <td>
                          <div class="btn-group">
                            <a href="patient_profile.php?id=<?php echo $row['patient_id']; ?>" class="btn btn-sm btn-outline-primary">
                              <i class="bi bi-eye"></i>
                            </a>
                            <a href="schedule_appointment.php?patient_id=<?php echo $row['patient_id']; ?>" class="btn btn-sm btn-outline-success">
                              <i class="bi bi-calendar-plus"></i>
                            </a>
                          </div>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="4" class="text-center text-muted py-4">
                        <i class="bi bi-people" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p class="mt-2">No recently registered patients</p>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      
        <div class="col-lg-6">
          <div class="table-container h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="mb-0">Upcoming Appointments</h5>
              <a href="schedule_appointment.php" class="btn btn-sm btn-outline-primary rounded-pill">
                <i class="bi bi-calendar-week"></i> View All
              </a>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Patient</th>
                    <th>Date</th>
                    <th>Dentist</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($upcoming_appointments && $upcoming_appointments->num_rows > 0): ?>
                    <?php while ($row = $upcoming_appointments->fetch_assoc()): ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center">
                            <div class="avatar me-3">
                              <?php echo strtoupper(substr(explode(' ', $row['patient_name'])[0], 0, 1)); ?>
                            </div>
                            <div><?php echo htmlspecialchars($row['patient_name']); ?></div>
                          </div>
                        </td>
                        <td>
                          <?php 
                            $date = new DateTime($row['scheduled_date']);
                            $tomorrow = new DateTime('tomorrow');
                            if ($date->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
                                echo '<span class="text-primary fw-medium">Tomorrow</span><br>';
                                echo '<small class="text-muted">' . date('h:i A', strtotime($row['start_time'])) . '</small>';
                            } else {
                                echo date('D, M j', strtotime($row['scheduled_date'])) . '<br>';
                                echo '<small class="text-muted">' . date('h:i A', strtotime($row['start_time'])) . '</small>';
                            }
                          ?>
                        </td>
                        <td>
                          <span class="dentist-badge">
                            <i class="bi bi-person-badge me-1"></i>
                            <?php echo htmlspecialchars($row['dentist_name']); ?>
                          </span>
                        </td>
                        <td>
                          <div class="btn-group">
                            <a href="edit_appointment.php?id=<?php echo $row['appointment_id']; ?>" class="btn btn-sm btn-outline-primary">
                              <i class="bi bi-pencil"></i>
                            </a>
                            <a href="cancel_appointment.php?id=<?php echo $row['appointment_id']; ?>" class="btn btn-sm btn-outline-danger">
                              <i class="bi bi-x-circle"></i>
                            </a>
                          </div>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="4" class="text-center text-muted py-4">
                        <i class="bi bi-calendar-check" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p class="mt-2">No upcoming appointments</p>
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
    // Update the clock every second
    function updateClock() {
      const now = new Date();
      document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
      });
      setTimeout(updateClock, 1000);
    }
    updateClock();
  </script>
</body>
</html>