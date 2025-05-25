<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dentist') {
    header("Location: ../../login.php");
    exit();
}

include '../../includes/db_connect.php';
$dentist_id = $_SESSION['user_id'];

// Function to safely execute queries with error handling
function executeQuery($conn, $query) {
    $result = $conn->query($query);
    if (!$result) {
        error_log("Query failed: " . $conn->error);
        return false;
    }
    return $result;
}

// Get analytics data for this dentist only
$patients_result = executeQuery($conn, "SELECT COUNT(DISTINCT a.patient_id) AS total 
                                        FROM appointments a
                                        WHERE a.dentist_id = $dentist_id");
$appointments_result = executeQuery($conn, "SELECT COUNT(*) AS total 
                                           FROM appointments 
                                           WHERE dentist_id = $dentist_id");

// Dentist-specific analytics
$today_appointments = executeQuery($conn, "SELECT COUNT(*) AS total 
                                          FROM appointments 
                                          WHERE dentist_id = $dentist_id 
                                          AND scheduled_date = CURDATE()");
$pending_appointments = executeQuery($conn, "SELECT COUNT(*) AS total 
                                            FROM appointments 
                                            WHERE dentist_id = $dentist_id 
                                            AND status = 'Scheduled'");
$completed_appointments = executeQuery($conn, "SELECT COUNT(*) AS total 
                                              FROM appointments 
                                              WHERE dentist_id = $dentist_id 
                                              AND status = 'Completed'");
// This week's appointments
$this_week_appointments = executeQuery($conn, "SELECT COUNT(*) AS total 
                                              FROM appointments 
                                              WHERE dentist_id = $dentist_id 
                                              AND YEARWEEK(scheduled_date, 1) = YEARWEEK(CURDATE(), 1)");

// Get upcoming appointments for this dentist
$upcoming_appointments = executeQuery($conn, "
    SELECT 
        a.scheduled_date, 
        a.start_time, 
        a.end_time,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name, 
        a.status,
        p.patient_id,
        a.reason
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.dentist_id = $dentist_id
    AND a.scheduled_date >= CURDATE()
    ORDER BY a.scheduled_date ASC, a.start_time ASC
    LIMIT 5
");

// Get recent treatments performed by this dentist
$recent_treatments = executeQuery($conn, "
    SELECT 
        tr.treatment_date,
        t.name AS treatment_name,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
        tr.cost,
        tr.status,
        dt.name AS tooth_name,
        p.patient_id
    FROM treatment_records tr
    JOIN treatments t ON tr.treatment_id = t.treatment_id
    JOIN patients p ON tr.patient_id = p.patient_id
    LEFT JOIN dental_teeth dt ON tr.tooth_id = dt.tooth_id
    WHERE tr.dentist_id = $dentist_id
    ORDER BY tr.treatment_date DESC
    LIMIT 5
");

// Get most common treatments for this dentist
$common_treatments = executeQuery($conn, "
    SELECT 
        t.name AS treatment_name,
        COUNT(*) AS treatment_count
    FROM treatment_records tr
    JOIN treatments t ON tr.treatment_id = t.treatment_id
    WHERE tr.dentist_id = $dentist_id
    GROUP BY t.name
    ORDER BY treatment_count DESC
    LIMIT 5
");

// Process query results
$patients = $patients_result ? $patients_result->fetch_assoc()['total'] : 0;
$appointments = $appointments_result ? $appointments_result->fetch_assoc()['total'] : 0;
$today_appts = $today_appointments ? $today_appointments->fetch_assoc()['total'] : 0;
$pending_appts = $pending_appointments ? $pending_appointments->fetch_assoc()['total'] : 0;
$completed_appts = $completed_appointments ? $completed_appointments->fetch_assoc()['total'] : 0;
$this_week_appts = $this_week_appointments ? $this_week_appointments->fetch_assoc()['total'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Dentist Dashboard | DenTec</title>
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
      <h5 class="text-white mb-4 ps-2">Dr. <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></h5>
      <a href="#" class="active">
        <i class="bi bi-speedometer2"></i> 
        <span>Dashboard</span>
      </a>
      <a href="#">
        <i class="bi bi-calendar-check"></i> 
        <span>Appointments</span>
        <?php if ($pending_appts > 0): ?>
          <span class="badge bg-warning ms-auto"><?php echo $pending_appts; ?></span>
        <?php endif; ?>
      </a>
      <a href="#">
        <i class="bi bi-people"></i> 
        <span>Patients</span>
      </a>
      <a href="#">
        <i class="bi bi-clipboard2-pulse"></i> 
        <span>Treatment Records</span>
      </a>
      <a href="#">
        <i class="bi bi-graph-up"></i> 
        <span>Performance</span>
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
            <h3 class="mb-2">Good <?php echo date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening'); ?>, Dr. <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'] ?? '')[0]); ?></h3>
            <p class="mb-0">You have <?php echo $today_appts; ?> appointment<?php echo $today_appts != 1 ? 's' : ''; ?> today</p>
          </div>
          <div class="text-end">
            <p class="mb-1"><i class="bi bi-calendar-check me-2"></i> <?php echo date('l, F j, Y'); ?></p>
            <p class="mb-0"><i class="bi bi-clock-history me-2"></i> <span id="current-time"><?php echo date('h:i A'); ?></span></p>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-primary">
            <div class="card-body position-relative">
              <h5 class="card-title">Total Patients</h5>
              <h2><?php echo $patients; ?></h2>
              <p class="text-muted mb-0"><small>Your patients</small></p>
              <i class="bi bi-people icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-success">
            <div class="card-body position-relative">
              <h5 class="card-title">Total Appointments</h5>
              <h2><?php echo $appointments; ?></h2>
              <p class="text-muted mb-0"><small><?php echo $pending_appts; ?> pending</small></p>
              <i class="bi bi-calendar-check icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-warning">
            <div class="card-body position-relative">
              <h5 class="card-title">Completed Treatments</h5>
              <h2><?php echo $completed_appts; ?></h2>
              <p class="text-muted mb-0"><small>Total completed</small></p>
              <i class="bi bi-check-circle icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-info">
            <div class="card-body position-relative">
              <h5 class="card-title">Weekly Appointments</h5>
              <h2><?php echo $this_week_appts; ?></h2>
              <p class="text-muted mb-0"><small>This week's schedule</small></p>
              <i class="bi bi-calendar-week icon"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Upcoming Appointments -->
      <div class="row g-4 mb-4">
        <div class="col-lg-6">
          <div class="table-container">
            <h5 class="mb-4">Today's Appointments</h5>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Patient</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $shown_today = false;
                  if ($upcoming_appointments && $upcoming_appointments->num_rows > 0): 
                    while ($row = $upcoming_appointments->fetch_assoc()): 
                      if (date('Y-m-d', strtotime($row['scheduled_date'])) == date('Y-m-d')):
                        $shown_today = true;
                  ?>
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
                      <td><?php echo date('h:i A', strtotime($row['start_time'])); ?></td>
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
                        <a href="patient_record.php?id=<?php echo $row['patient_id']; ?>" class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-file-earmark-text"></i> Record
                        </a>
                      </td>
                    </tr>
                  <?php 
                      endif;
                    endwhile;
                    
                    if (!$shown_today):
                  ?>
                    <tr>
                      <td colspan="4" class="text-center text-muted py-4">No appointments scheduled for today</td>
                    </tr>
                  <?php 
                    endif;
                  else: 
                  ?>
                    <tr>
                      <td colspan="4" class="text-center text-muted py-4">No appointments scheduled for today</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        
        <!-- Recent Treatments -->
        <div class="col-lg-6">
          <div class="table-container">
            <h5 class="mb-4">Recent Treatments</h5>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Patient</th>
                    <th>Treatment</th>
                    <th>Date</th>
                    <th>Cost</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($recent_treatments && $recent_treatments->num_rows > 0): ?>
                    <?php while ($row = $recent_treatments->fetch_assoc()): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                        <td>
                          <?php echo htmlspecialchars($row['treatment_name']); ?>
                          <?php if (!empty($row['tooth_name'])): ?>
                            <small class="text-muted d-block"><?php echo $row['tooth_name']; ?></small>
                          <?php endif; ?>
                        </td>
                        <td><?php echo date('M j', strtotime($row['treatment_date'])); ?></td>
                        <td>LKR <?php echo number_format($row['cost'], 2); ?></td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="4" class="text-center text-muted py-4">No recent treatments found</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Common Treatments and Upcoming Appointments -->
      <div class="row">
        <div class="col-12">
          <div class="table-container">
            <h5 class="mb-4">Your Most Common Treatments</h5>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Treatment</th>
                    <th>Frequency</th>
                    <th>Percentage</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($common_treatments && $common_treatments->num_rows > 0): ?>
                    <?php 
                      $total_treatments = $completed_appts;
                      while ($row = $common_treatments->fetch_assoc()): 
                        $percentage = $total_treatments > 0 ? round(($row['treatment_count'] / $total_treatments) * 100) : 0;
                    ?>
                      <tr>
                        <td><?php echo htmlspecialchars($row['treatment_name']); ?></td>
                        <td><?php echo $row['treatment_count']; ?></td>
                        <td>
                          <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $percentage; ?>%" 
                                 aria-valuenow="<?php echo $percentage; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                              <?php echo $percentage; ?>%
                            </div>
                          </div>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="3" class="text-center text-muted py-4">No treatment data available</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Future Appointments -->
      <div class="row">
        <div class="col-12">
          <div class="table-container">
            <h5 class="mb-4">Upcoming Appointments</h5>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Patient</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $shown_future = false;
                  if ($upcoming_appointments && $upcoming_appointments->num_rows > 0): 
                    // Reset the result set pointer to the beginning
                    if ($upcoming_appointments instanceof mysqli_result) {
                        $upcoming_appointments->data_seek(0);
                    }
                    
                    while ($row = $upcoming_appointments->fetch_assoc()): 
                      if (date('Y-m-d', strtotime($row['scheduled_date'])) > date('Y-m-d')):
                        $shown_future = true;
                  ?>
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
                        <?php 
                          $date = new DateTime($row['scheduled_date']);
                          $tomorrow = new DateTime('tomorrow');
                          if ($date->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
                              echo '<span class="text-primary">Tomorrow</span>';
                          } else {
                              echo date('D, M j', strtotime($row['scheduled_date']));
                          }
                        ?>
                      </td>
                      <td><?php echo date('h:i A', strtotime($row['start_time'])); ?></td>
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
                        <a href="patient_record.php?id=<?php echo $row['patient_id']; ?>" class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-file-earmark-text"></i> Record
                        </a>
                      </td>
                    </tr>
                  <?php 
                      endif;
                    endwhile;
                    
                    if (!$shown_future):
                  ?>
                    <tr>
                      <td colspan="5" class="text-center text-muted py-4">No upcoming appointments</td>
                    </tr>
                  <?php 
                    endif;
                  else: 
                  ?>
                    <tr>
                      <td colspan="5" class="text-center text-muted py-4">No upcoming appointments</td>
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