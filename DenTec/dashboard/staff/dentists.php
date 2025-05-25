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

// Get analytics data for dentists
$total_dentists = executeQuery($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'dentist' AND is_active = 1");
$active_dentists = executeQuery($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'dentist' AND is_active = 1 AND last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$busy_dentists = executeQuery($conn, "SELECT COUNT(DISTINCT dentist_id) AS total 
                                     FROM appointments 
                                     WHERE scheduled_date >= CURDATE() 
                                     AND status = 'Scheduled'");
$available_dentists = executeQuery($conn, "SELECT COUNT(*) AS total 
                                         FROM users u 
                                         WHERE u.role = 'dentist' 
                                         AND u.is_active = 1 
                                         AND u.user_id NOT IN (
                                             SELECT DISTINCT dentist_id 
                                             FROM appointments 
                                             WHERE scheduled_date = CURDATE() 
                                             AND status = 'Scheduled'
                                         )");

// Get all dentists with their statistics
$dentists_query = "
    SELECT 
        u.user_id,
        u.full_name,
        u.email,
        u.username,
        u.is_active,
        u.last_login,
        u.created_at,
        COALESCE(stats.total_appointments, 0) as total_appointments,
        COALESCE(stats.completed_appointments, 0) as completed_appointments,
        COALESCE(stats.upcoming_appointments, 0) as upcoming_appointments,
        COALESCE(stats.today_appointments, 0) as today_appointments
    FROM users u
    LEFT JOIN (
        SELECT 
            dentist_id,
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_appointments,
            SUM(CASE WHEN scheduled_date > CURDATE() AND status = 'Scheduled' THEN 1 ELSE 0 END) as upcoming_appointments,
            SUM(CASE WHEN scheduled_date = CURDATE() AND status = 'Scheduled' THEN 1 ELSE 0 END) as today_appointments
        FROM appointments
        GROUP BY dentist_id
    ) stats ON u.user_id = stats.dentist_id
    WHERE u.role = 'dentist'
    ORDER BY u.full_name ASC
";

$dentists = executeQuery($conn, $dentists_query);

// Search functionality
$search_query = '';
$searched_dentists = null;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $conn->real_escape_string($_GET['search']);
    $search_query = $search_term;
    
    $searched_dentists = executeQuery($conn, "
        SELECT 
            u.user_id,
            u.full_name,
            u.email,
            u.username,
            u.is_active,
            u.last_login,
            u.created_at,
            COALESCE(stats.total_appointments, 0) as total_appointments,
            COALESCE(stats.completed_appointments, 0) as completed_appointments,
            COALESCE(stats.upcoming_appointments, 0) as upcoming_appointments,
            COALESCE(stats.today_appointments, 0) as today_appointments
        FROM users u
        LEFT JOIN (
            SELECT 
                dentist_id,
                COUNT(*) as total_appointments,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_appointments,
                SUM(CASE WHEN scheduled_date > CURDATE() AND status = 'Scheduled' THEN 1 ELSE 0 END) as upcoming_appointments,
                SUM(CASE WHEN scheduled_date = CURDATE() AND status = 'Scheduled' THEN 1 ELSE 0 END) as today_appointments
            FROM appointments
            GROUP BY dentist_id
        ) stats ON u.user_id = stats.dentist_id
        WHERE u.role = 'dentist'
        AND (
            u.full_name LIKE '%$search_term%' OR 
            u.email LIKE '%$search_term%' OR
            u.username LIKE '%$search_term%'
        )
        ORDER BY u.full_name ASC
    ");
}

// Process query results
$dentists_count = $total_dentists ? $total_dentists->fetch_assoc()['total'] : 0;
$active_count = $active_dentists ? $active_dentists->fetch_assoc()['total'] : 0;
$busy_count = $busy_dentists ? $busy_dentists->fetch_assoc()['total'] : 0;
$available_count = $available_dentists ? $available_dentists->fetch_assoc()['total'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Dentist Management | DenTec</title>
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
      width: 60px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      font-weight: 600;
      background-color: var(--primary-color);
      color: white;
      font-size: 1.5rem;
    }
    
    .dentist-card {
      border-radius: 15px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      transition: all 0.3s;
      border: 1px solid rgba(0,0,0,0.05);
      height: 100%;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    
    .dentist-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 25px rgba(0,0,0,0.15);
      border-color: var(--primary-color);
    }
    
    .dentist-card .card-body {
      flex: 1;
      display: flex;
      flex-direction: column;
      padding: 25px;
    }
    
    .dentist-card .card-header {
      background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
      color: white;
      border: none;
      padding: 20px 25px;
      position: relative;
      overflow: hidden;
    }
    
    .dentist-card .card-header::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 100%;
      height: 200%;
      background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
      transform: rotate(45deg);
      transition: all 0.6s;
      opacity: 0;
    }
    
    .dentist-card:hover .card-header::before {
      opacity: 1;
      right: 100%;
    }
    
    .status-badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    
    .status-active {
      background-color: rgba(25, 135, 84, 0.1);
      color: #198754;
    }
    
    .status-inactive {
      background-color: rgba(220, 53, 69, 0.1);
      color: #dc3545;
    }
    
    .stat-item {
      text-align: center;
      padding: 15px;
      background: rgba(67, 97, 238, 0.05);
      border-radius: 10px;
      margin: 5px;
      transition: all 0.3s;
    }
    
    .stat-item:hover {
      background: rgba(67, 97, 238, 0.1);
      transform: scale(1.05);
    }
    
    .stat-number {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--primary-color);
      display: block;
    }
    
    .stat-label {
      font-size: 0.8rem;
      color: var(--light-text);
      margin-top: 5px;
    }
    
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
    
    .online-indicator {
      width: 12px;
      height: 12px;
      background: #28a745;
      border-radius: 50%;
      border: 2px solid white;
      position: absolute;
      bottom: 5px;
      right: 5px;
    }
    
    .offline-indicator {
      width: 12px;
      height: 12px;
      background: #dc3545;
      border-radius: 50%;
      border: 2px solid white;
      position: absolute;
      bottom: 5px;
      right: 5px;
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
      <a href="patient.php">
        <i class="bi bi-people"></i> 
        <span>Patients</span>
      </a>
      <a href="dentists.php" class="active">
        <i class="bi bi-person-badge"></i> 
        <span>Dentists</span>
      </a>
      <a href="#">
        <i class="bi bi-cash-coin"></i> 
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
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h3 class="mb-1">Dentist Management</h3>
          <p class="text-muted mb-0">Manage dental professionals and their schedules</p>
        </div>
        <div>
          <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addDentistModal">
            <i class="bi bi-person-plus-fill me-2"></i> Add New Dentist
          </button>
        </div>
      </div>

      <!-- Search & Filter Section -->
      <div class="card mb-4">
        <div class="card-body">
          <form action="" method="GET" class="row g-3">
            <div class="col-lg-8">
              <div class="search-form">
                <i class="bi bi-search text-muted"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by name, email, or username..." class="ms-2">
                <button type="submit"><i class="bi bi-arrow-right"></i></button>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary rounded-pill flex-fill">
                  <i class="bi bi-search me-1"></i> Search
                </button>
                <a href="dentists.php" class="btn btn-outline-secondary rounded-pill">
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
              <h5 class="card-title">Total Dentists</h5>
              <h2><?php echo $dentists_count; ?></h2>
              <p class="text-muted mb-0"><small>Registered dentists</small></p>
              <i class="bi bi-person-badge icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-success">
            <div class="card-body position-relative">
              <h5 class="card-title">Active This Month</h5>
              <h2><?php echo $active_count; ?></h2>
              <p class="text-muted mb-0"><small>Logged in recently</small></p>
              <i class="bi bi-activity icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-warning">
            <div class="card-body position-relative">
              <h5 class="card-title">Busy Today</h5>
              <h2><?php echo $busy_count; ?></h2>
              <p class="text-muted mb-0"><small>With appointments</small></p>
              <i class="bi bi-calendar-check icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-info">
            <div class="card-body position-relative">
              <h5 class="card-title">Available Today</h5>
              <h2><?php echo $available_count; ?></h2>
              <p class="text-muted mb-0"><small>No appointments today</small></p>
              <i class="bi bi-check-circle icon"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Dentists List -->
      <div class="table-container mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h5 class="mb-0">
            <?php if (isset($searched_dentists)): ?>
              Search Results (<?php echo $searched_dentists->num_rows; ?>)
            <?php else: ?>
              All Dentists
            <?php endif; ?>
          </h5>
          <div class="btn-group">
            <button class="btn btn-sm btn-outline-primary" id="cardView">
              <i class="bi bi-grid-3x3-gap me-1"></i> Cards
            </button>
            <button class="btn btn-sm btn-outline-secondary" id="listView">
              <i class="bi bi-list-ul me-1"></i> List
            </button>
          </div>
        </div>
        
        <?php 
        $dentists_to_display = isset($searched_dentists) ? $searched_dentists : $dentists;
        ?>

        <?php if ($dentists_to_display && $dentists_to_display->num_rows > 0): ?>
          <div class="row g-4" id="dentistCards">
            <?php while ($dentist = $dentists_to_display->fetch_assoc()): ?>
              <div class="col-md-6 col-lg-4">
                <div class="card dentist-card">
                  <div class="card-header">
                    <div class="d-flex align-items-center">
                      <div class="position-relative me-3">
                        <div class="avatar">
                          <?php echo strtoupper(substr($dentist['full_name'], 0, 1)); ?>
                        </div>
                        <?php if ($dentist['last_login'] && strtotime($dentist['last_login']) > strtotime('-24 hours')): ?>
                          <div class="online-indicator"></div>
                        <?php else: ?>
                          <div class="offline-indicator"></div>
                        <?php endif; ?>
                      </div>
                      <div class="flex-grow-1">
                        <h6 class="mb-1 text-white"><?php echo htmlspecialchars($dentist['full_name']); ?></h6>
                        <small class="text-white-50">@<?php echo htmlspecialchars($dentist['username']); ?></small>
                      </div>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="mb-3">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="status-badge <?php echo $dentist['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                          <?php echo $dentist['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                        <small class="text-muted">
                          <?php if ($dentist['last_login']): ?>
                            Last seen: <?php echo date('M j, Y', strtotime($dentist['last_login'])); ?>
                          <?php else: ?>
                            Never logged in
                          <?php endif; ?>
                        </small>
                      </div>
                      
                      <div class="d-flex mb-2">
                        <div style="width: 24px"><i class="bi bi-envelope text-muted"></i></div>
                        <div><small><?php echo htmlspecialchars($dentist['email']); ?></small></div>
                      </div>
                      
                      <div class="d-flex mb-2">
                        <div style="width: 24px"><i class="bi bi-calendar-plus text-muted"></i></div>
                        <div><small>Joined <?php echo date('M j, Y', strtotime($dentist['created_at'])); ?></small></div>
                      </div>
                    </div>
                    
                    <!-- Statistics -->
                    <div class="row g-2 mb-3">
                      <div class="col-6">
                        <div class="stat-item">
                          <span class="stat-number"><?php echo $dentist['total_appointments']; ?></span>
                          <div class="stat-label">Total Appointments</div>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="stat-item">
                          <span class="stat-number"><?php echo $dentist['completed_appointments']; ?></span>
                          <div class="stat-label">Completed</div>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="stat-item">
                          <span class="stat-number"><?php echo $dentist['today_appointments']; ?></span>
                          <div class="stat-label">Today</div>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="stat-item">
                          <span class="stat-number"><?php echo $dentist['upcoming_appointments']; ?></span>
                          <div class="stat-label">Upcoming</div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-auto">
                      <a href="dentist_profile.php?id=<?php echo $dentist['user_id']; ?>" class="btn btn-sm btn-outline-primary flex-fill">
                        <i class="bi bi-eye me-1"></i> View Profile
                      </a>
                      <a href="schedule_appointment.php?dentist_id=<?php echo $dentist['user_id']; ?>" class="btn btn-sm btn-outline-success flex-fill">
                        <i class="bi bi-calendar-plus me-1"></i> Schedule
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="bi bi-person-badge" style="font-size: 3rem; opacity: 0.2;"></i>
            <p class="mt-3 text-muted">
              <?php echo isset($searched_dentists) ? 'No dentists found matching your search.' : 'No dentists have been registered yet.'; ?>
            </p>
            <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addDentistModal">
              <i class="bi bi-person-plus-fill me-2"></i> Add New Dentist
            </button>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Add Dentist Modal -->
  <div class="modal fade" id="addDentistModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Dentist</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="addDentistForm">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="full_name" name="full_name" required>
              </div>
              <div class="col-md-6">
                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="username" name="username" required>
              </div>
              <div class="col-md-6">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" required>
              </div>
              <div class="col-md-6">
                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" required>
              </div>
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                  <label class="form-check-label" for="is_active">
                    Active (dentist can log in and receive appointments)
                  </label>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" form="addDentistForm" class="btn btn-primary">Add Dentist</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // View toggle functionality
    document.getElementById('cardView').addEventListener('click', function() {
      this.classList.remove('btn-outline-primary');
      this.classList.add('btn-primary');
      document.getElementById('listView').classList.remove('btn-primary');
      document.getElementById('listView').classList.add('btn-outline-secondary');
      // Add card view logic here if needed
    });

    document.getElementById('listView').addEventListener('click', function() {
      this.classList.remove('btn-outline-secondary');
      this.classList.add('btn-primary');
      document.getElementById('cardView').classList.remove('btn-primary');
      document.getElementById('cardView').classList.add('btn-outline-primary');
      // Add list view logic here if needed
    });

    // Form submission
    document.getElementById('addDentistForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      formData.append('action', 'add_dentist');
      
      fetch('process_dentist.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Dentist added successfully!');
          location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the dentist.');
      });
    });

    // Real-time search
    let searchTimeout;
    const searchInput = document.querySelector('input[name="search"]');
    
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          if (this.value.length >= 2 || this.value.length === 0) {
            const form = this.closest('form');
            form.submit();
          }
        }, 500);
      });
    }
  </script>
</body>
</html>