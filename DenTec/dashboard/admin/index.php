<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Include database connection
include '../../includes/db_connect.php';

// Function to safely execute queries with error handling
function executeQuery($conn, $query, $params = []) {
    try {
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            if (!empty($params)) {
                $types = str_repeat('s', count($params)); // Assuming all string parameters
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        } else {
            $result = $conn->query($query);
            if (!$result) {
                throw new Exception("Query failed: " . $conn->error);
            }
            return $result;
        }
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
        return false;
    }
}

// Function to get safe count from query result
function getSafeCount($result) {
    if ($result && $row = $result->fetch_assoc()) {
        return (int)($row['total'] ?? $row['count'] ?? 0);
    }
    return 0;
}

// Function to get safe sum from query result
function getSafeSum($result) {
    if ($result && $row = $result->fetch_assoc()) {
        return (float)($row['total'] ?? 0);
    }
    return 0.0;
}

// Basic analytics queries
$patients_result = executeQuery($conn, "SELECT COUNT(*) AS total FROM patients");
$appointments_result = executeQuery($conn, "SELECT COUNT(*) AS total FROM appointments");
$income_result = executeQuery($conn, "SELECT COALESCE(SUM(amount), 0) AS total FROM payments");

// Today's analytics
$today = date('Y-m-d');
$new_patients = executeQuery($conn, "SELECT COUNT(*) AS total FROM patients WHERE DATE(created_at) = ?", [$today]);
$today_appointments = executeQuery($conn, "SELECT COUNT(*) AS total FROM appointments WHERE scheduled_date = ?", [$today]);

// Status-based analytics
$pending_appointments = executeQuery($conn, "SELECT COUNT(*) AS total FROM appointments WHERE status = 'Scheduled'");
$completed_appointments = executeQuery($conn, "SELECT COUNT(*) AS total FROM appointments WHERE status = 'Completed'");
$canceled_appointments = executeQuery($conn, "SELECT COUNT(*) AS total FROM appointments WHERE status = 'Canceled'");
$no_show_appointments = executeQuery($conn, "SELECT COUNT(*) AS total FROM appointments WHERE status = 'No-Show'");

// Monthly income
$current_month = date('Y-m');
$monthly_income = executeQuery($conn, "SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?", [$current_month]);

// Appointments by status for chart
$appointments_by_status = executeQuery($conn, "
    SELECT status, COUNT(*) AS count 
    FROM appointments 
    GROUP BY status 
    ORDER BY count DESC
");

// Monthly trend data (last 6 months)
$monthly_trend = executeQuery($conn, "
    SELECT 
        DATE_FORMAT(scheduled_date, '%Y-%m') AS month,
        COUNT(*) AS appointment_count
    FROM appointments
    WHERE scheduled_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(scheduled_date, '%Y-%m')
    ORDER BY month ASC
    LIMIT 6
");

// Top dentists by appointments
$top_dentists = executeQuery($conn, "
    SELECT 
        u.full_name AS dentist_name,
        COUNT(a.appointment_id) AS appointment_count,
        ROUND(
            (COUNT(CASE WHEN a.status = 'Completed' THEN 1 END) / COUNT(*)) * 100, 
            1
        ) AS completion_rate
    FROM appointments a
    JOIN users u ON a.dentist_id = u.user_id
    WHERE u.role = 'dentist'
    GROUP BY u.user_id, u.full_name
    ORDER BY appointment_count DESC
    LIMIT 5
");

// Upcoming appointments
$upcoming_appointments = executeQuery($conn, "
    SELECT 
        a.appointment_id,
        a.scheduled_date, 
        a.start_time, 
        a.end_time,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name, 
        u.full_name AS dentist_name, 
        a.status,
        a.reason
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users u ON a.dentist_id = u.user_id
    WHERE a.scheduled_date >= CURDATE() 
    AND a.status IN ('Scheduled')
    ORDER BY a.scheduled_date ASC, a.start_time ASC
    LIMIT 10
");

// Recent appointments/activity
$recent_appointments = executeQuery($conn, "
    SELECT 
        a.appointment_id,
        a.scheduled_date, 
        a.start_time,
        a.end_time, 
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name, 
        u.full_name AS dentist_name, 
        a.status,
        a.reason,
        COALESCE(
            (SELECT SUM(amount) 
             FROM payments pay 
             JOIN treatment_records tr ON pay.record_id = tr.record_id 
             WHERE tr.appointment_id = a.appointment_id), 
            0
        ) AS total_amount
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users u ON a.dentist_id = u.user_id
    ORDER BY a.scheduled_date DESC, a.start_time DESC
    LIMIT 15
");

// Process all the results safely
$patients = getSafeCount($patients_result);
$appointments = getSafeCount($appointments_result);
$income = getSafeSum($income_result);
$today_patients = getSafeCount($new_patients);
$today_appts = getSafeCount($today_appointments);
$pending_appts = getSafeCount($pending_appointments);
$completed_appts = getSafeCount($completed_appointments);
$canceled_appts = getSafeCount($canceled_appointments);
$no_show_appts = getSafeCount($no_show_appointments);
$current_month_income = getSafeSum($monthly_income);

// Calculate completion rate
$completion_rate = $appointments > 0 ? round(($completed_appts / $appointments) * 100, 1) : 0;

// Prepare data for charts
$status_data = [];
$status_labels = [];
if ($appointments_by_status) {
    while ($row = $appointments_by_status->fetch_assoc()) {
        $status_labels[] = $row['status'];
        $status_data[] = (int)$row['count'];
    }
}

// Prepare trend data for chart
$trend_data = [];
$trend_labels = [];
if ($monthly_trend) {
    while ($row = $monthly_trend->fetch_assoc()) {
        $trend_labels[] = date('M Y', strtotime($row['month'] . '-01'));
        $trend_data[] = (int)$row['appointment_count'];
    }
}

// Weather API integration (optional)
$weather_data = null;
$weather_error = false;
$city = 'Colombo';
$config_path = '../../config/app_config.php';
$api_key = '';

if (file_exists($config_path)) {
    $config = include($config_path);
    $api_key = $config['openweathermap_api_key'] ?? '';
}

if ($api_key) {
    $weather_url = "https://api.openweathermap.org/data/2.5/weather?q=$city&units=metric&appid=$api_key";
    
    $ch = curl_init($weather_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    
    $weather_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($weather_response !== false && $http_code === 200) {
        $weather_data = json_decode($weather_response, true);
    } else {
        $weather_error = true;
        error_log("Weather API error: " . curl_error($ch));
    }
    curl_close($ch);
}

// Helper function to get weather icon
function getWeatherIcon($weather_data) {
    if (!$weather_data || !isset($weather_data['weather'][0]['main'])) {
        return 'cloud';
    }
    
    $main = strtolower($weather_data['weather'][0]['main']);
    if (strpos($main, 'rain') !== false) return 'cloud-rain';
    if (strpos($main, 'sun') !== false || strpos($main, 'clear') !== false) return 'sun';
    if (strpos($main, 'cloud') !== false) return 'cloud';
    if (strpos($main, 'thunder') !== false) return 'lightning';
    return 'cloud';
}

// Helper function to generate avatar initials
function getInitials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        if (strlen($initials) >= 2) break;
    }
    return $initials ?: 'U';
}

// Helper function to format currency
function formatCurrency($amount, $currency = 'LKR') {
    return $currency . ' ' . number_format($amount, 2);
}

// Helper function to format date
function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

// Helper function to format time
function formatTime($time, $format = 'h:i A') {
    return date($format, strtotime($time));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard | DenTec</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    :root {
      --primary-color: #4361ee;
      --secondary-color: #3f37c9;
      --accent-color: #4895ef;
      --success-color: #10b981;
      --warning-color: #f59e0b;
      --danger-color: #ef4444;
      --light-bg: #f8f9fa;
      --dark-text: #212529;
      --light-text: #6c757d;
    }
    
    * {
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--light-bg);
      margin: 0;
      padding: 0;
      overflow-x: hidden;
    }
    
    /* Sidebar Styles */
    .sidebar {
      height: 100vh;
      background: linear-gradient(180deg, var(--secondary-color), var(--primary-color));
      color: #fff;
      padding: 20px 10px;
      position: fixed;
      width: 250px;
      box-shadow: 2px 0 10px rgba(0,0,0,0.1);
      z-index: 1000;
      transition: transform 0.3s ease;
      overflow-y: auto;
    }
    
    .sidebar.collapsed {
      transform: translateX(-100%);
    }
    
    .sidebar-header {
      text-align: center;
      margin-bottom: 30px;
      padding: 0 10px;
    }
    
    .sidebar-header h4 {
      margin: 0;
      font-weight: 700;
      font-size: 1.5rem;
    }
    
    .sidebar a {
      color: rgba(255,255,255,0.8);
      text-decoration: none;
      display: flex;
      align-items: center;
      padding: 12px 15px;
      border-radius: 8px;
      margin-bottom: 5px;
      transition: all 0.3s ease;
      white-space: nowrap;
    }
    
    .sidebar a:hover, .sidebar a.active {
      background-color: rgba(255,255,255,0.15);
      color: #fff;
      transform: translateX(5px);
    }
    
    .sidebar i {
      width: 20px;
      text-align: center;
      margin-right: 12px;
      font-size: 1.1rem;
      flex-shrink: 0;
    }
    
    .sidebar .badge {
      margin-left: auto;
      font-size: 0.7rem;
      flex-shrink: 0;
    }
    
    /* Main Content Styles */
    .main {
      margin-left: 250px;
      padding: 30px;
      background-color: var(--light-bg);
      transition: margin-left 0.3s ease;
      min-height: 100vh;
    }
    
    .main.expanded {
      margin-left: 0;
    }
    
    .mobile-toggle {
      display: none;
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 1001;
      background: var(--primary-color);
      color: white;
      border: none;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    /* Card Statistics */
    .card-stat {
      border-radius: 15px;
      border: none;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      height: 100%;
      position: relative;
      overflow: hidden;
      background: white;
    }
    
    .card-stat:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
    
    .card-stat .card-body {
      padding: 1.5rem;
      position: relative;
      z-index: 1;
    }
    
    .card-stat .card-title {
      font-size: 0.85rem;
      color: var(--light-text);
      margin-bottom: 0.5rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .card-stat h2 {
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      color: var(--dark-text);
    }
    
    .card-stat .icon {
      font-size: 2.5rem;
      opacity: 0.1;
      position: absolute;
      right: 20px;
      top: 20px;
      z-index: 0;
    }
    
    .card-stat .trend {
      font-size: 0.8rem;
      display: flex;
      align-items: center;
      gap: 5px;
      margin-top: 0.5rem;
    }
    
    .trend.up {
      color: var(--success-color);
    }
    
    .trend.down {
      color: var(--danger-color);
    }
    
    .trend.neutral {
      color: var(--light-text);
    }
    
    .card-primary {
      border-left: 4px solid var(--primary-color);
    }
    
    .card-primary .icon {
      color: var(--primary-color);
    }
    
    .card-success {
      border-left: 4px solid var(--success-color);
    }
    
    .card-success .icon {
      color: var(--success-color);
    }
    
    .card-warning {
      border-left: 4px solid var(--warning-color);
    }
    
    .card-warning .icon {
      color: var(--warning-color);
    }
    
    .card-danger {
      border-left: 4px solid var(--danger-color);
    }
    
    .card-danger .icon {
      color: var(--danger-color);
    }
    
    /* Chart Container */
    .chart-container {
      background: white;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      margin-bottom: 30px;
    }
    
    /* Welcome Header */
    .welcome-header {
      background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
      color: white;
      padding: 25px;
      border-radius: 15px;
      margin-bottom: 30px;
      box-shadow: 0 4px 20px rgba(67, 97, 238, 0.2);
    }
    
    /* Table Container */
    .table-container {
      background: white;
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      padding: 25px;
      margin-bottom: 30px;
    }
    
    /* Status Badges */
    .status-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      display: inline-block;
    }
    
    .status-scheduled {
      background-color: rgba(13, 110, 253, 0.1);
      color: #0d6efd;
      border: 1px solid rgba(13, 110, 253, 0.2);
    }
    
    .status-completed {
      background-color: rgba(16, 185, 129, 0.1);
      color: var(--success-color);
      border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    .status-canceled {
      background-color: rgba(239, 68, 68, 0.1);
      color: var(--danger-color);
      border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .status-no-show {
      background-color: rgba(245, 158, 11, 0.1);
      color: var(--warning-color);
      border: 1px solid rgba(245, 158, 11, 0.2);
    }
    
    /* Avatar */
    .avatar {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      font-weight: 600;
      font-size: 0.9rem;
    }
    
    /* Weather Widget */
    .weather-widget {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 15px;
      background: rgba(255,255,255,0.1);
      border-radius: 10px;
      margin-top: 20px;
      backdrop-filter: blur(10px);
    }
    
    .weather-widget i {
      font-size: 1.8rem;
    }
    
    .weather-info {
      display: flex;
      flex-direction: column;
    }
    
    .temp {
      font-size: 1.2rem;
      font-weight: 600;
    }
    
    .condition {
      font-size: 0.8rem;
      opacity: 0.9;
    }
    
    /* Progress */
    .progress-thin {
      height: 6px;
      border-radius: 3px;
    }
    
    .dentist-progress {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .progress-container {
      flex-grow: 1;
    }
    
    /* Search Widget */
    .search-widget {
      position: relative;
      margin-bottom: 20px;
    }
    
    .search-input {
      width: 100%;
      padding: 12px 20px 12px 45px;
      border: 2px solid #e9ecef;
      border-radius: 10px;
      font-size: 0.9rem;
      transition: all 0.3s ease;
    }
    
    .search-input:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
      outline: none;
    }
    
    .search-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--light-text);
    }
    
    /* Loading Overlay */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.9);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }
    
    .loading-overlay.show {
      opacity: 1;
      visibility: visible;
    }
    
    .spinner {
      width: 50px;
      height: 50px;
      border: 4px solid #f3f3f3;
      border-top: 4px solid var(--primary-color);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .mobile-toggle {
        display: block;
      }
      
      .sidebar {
        transform: translateX(-100%);
        width: 250px;
      }
      
      .sidebar.show {
        transform: translateX(0);
      }
      
      .main {
        margin-left: 0;
        padding: 90px 20px 20px;
      }
      
      .weather-widget {
        display: none;
      }
      
      .card-stat h2 {
        font-size: 1.8rem;
      }
      
      .welcome-header {
        padding: 20px;
      }
      
      .chart-container,
      .table-container {
        padding: 20px;
      }
    }
    
    @media (max-width: 576px) {
      .main {
        padding: 90px 15px 15px;
      }
      
      .welcome-header .d-flex {
        flex-direction: column;
        text-align: center;
      }
      
      .welcome-header .text-end {
        text-align: center !important;
        margin-top: 15px;
      }
    }
  </style>
</head>

<body>
  <div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
  </div>

  <button class="mobile-toggle" id="mobileToggle">
    <i class="bi bi-list"></i>
  </button>

  <div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <h4><i class="bi bi-heart-pulse me-2"></i>DenTec</h4>
      </div>
      
      <a href="#" class="active">
        <i class="bi bi-speedometer2"></i> 
        <span>Dashboard</span>
        <span class="badge bg-light text-primary">Live</span>
      </a>
      <a href="patients.php">
        <i class="bi bi-person-plus"></i> 
        <span>Patients</span>
        <?php if ($patients > 0): ?>
          <span class="badge bg-info"><?php echo $patients; ?></span>
        <?php endif; ?>
      </a>
      <a href="appointments.php">
        <i class="bi bi-calendar-check"></i> 
        <span>Appointments</span>
        <?php if ($pending_appts > 0): ?>
          <span class="badge bg-warning"><?php echo $pending_appts; ?></span>
        <?php endif; ?>
      </a>
      <a href="treatments.php">
        <i class="bi bi-medical-bag"></i> 
        <span>Treatments</span>
      </a>
      <a href="staff.php">
        <i class="bi bi-people"></i> 
        <span>Staff</span>
      </a>
      <a href="reports.php">
        <i class="bi bi-graph-up"></i> 
        <span>Reports</span>
      </a>
      <a href="billing.php">
        <i class="bi bi-credit-card"></i> 
        <span>Billing</span>
      </a>
      <a href="settings.php">
        <i class="bi bi-gear"></i> 
        <span>Settings</span>
      </a>
      
      <!-- Weather Widget -->
      <div class="weather-widget">
        <?php if (!$weather_error && $weather_data): ?>
          <i class="bi bi-<?php echo getWeatherIcon($weather_data); ?>"></i>
          <div class="weather-info">
            <span class="temp"><?php echo round($weather_data['main']['temp']); ?>°C</span>
            <span class="condition"><?php echo $weather_data['weather'][0]['main'] ?? 'N/A'; ?></span>
          </div>
        <?php else: ?>
          <i class="bi bi-sun"></i>
          <div class="weather-info">
            <span class="temp">29°C</span>
            <span class="condition">Sunny</span>
          </div>
        <?php endif; ?>
      </div>
      
      <a href="../../logout.php" class="mt-2">
        <i class="bi bi-box-arrow-right"></i> 
        <span>Logout</span>
      </a>
    </div>

    <!-- Main Content -->
    <div class="main" id="mainContent">
      <!-- Search Widget -->
      <div class="search-widget">
        <i class="bi bi-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search patients, appointments, treatments..." id="globalSearch">
      </div>

      <div class="welcome-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h3 class="mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Administrator'); ?></h3>
            <p class="mb-0">Here's what's happening with your clinic today.</p>
          </div>
          <div class="text-end">
            <p class="mb-1"><i class="bi bi-calendar-check me-2"></i><?php echo date('l, F j, Y'); ?></p>
            <p class="mb-0"><i class="bi bi-clock-history me-2"></i><?php echo date('h:i A'); ?></p>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-primary">
            <div class="card-body">
              <h5 class="card-title">Total Patients</h5>
              <h2><?php echo $patients; ?></h2>
              <div class="trend <?php echo ($today_patients > 0) ? 'up' : 'neutral'; ?>">
                <i class="bi bi-arrow-<?php echo ($today_patients > 0) ? 'up' : 'right'; ?>"></i>
                <span><?php echo $today_patients; ?> new today</span>
              </div>
              <i class="bi bi-people icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-success">
            <div class="card-body">
              <h5 class="card-title">Total Appointments</h5>
              <h2><?php echo $appointments; ?></h2>
              <div class="trend <?php echo ($pending_appts > 0) ? 'down' : 'neutral'; ?>">
                <i class="bi bi-arrow-<?php echo ($pending_appts > 0) ? 'down' : 'right'; ?>"></i>
                <span><?php echo $pending_appts; ?> pending</span>
              </div>
              <i class="bi bi-calendar-check icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-warning">
            <div class="card-body">
              <h5 class="card-title">Completed</h5>
              <h2><?php echo $completed_appts; ?></h2>
              <div class="trend up">
                <i class="bi bi-arrow-up"></i>
                <span><?php echo $completion_rate; ?>% completion rate</span>
              </div>
              <i class="bi bi-check-circle icon"></i>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="card card-stat card-danger">
            <div class="card-body">
              <h5 class="card-title">Total Income</h5>
              <h2><?php echo formatCurrency($income); ?></h2>
              <div class="trend <?php echo ($current_month_income > 0) ? 'up' : 'neutral'; ?>">
                <i class="bi bi-arrow-<?php echo ($current_month_income > 0) ? 'up' : 'right'; ?>"></i>
                <span><?php echo formatCurrency($current_month_income); ?> this month</span>
              </div>
              <i class="bi bi-currency-exchange icon"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="row g-4 mb-4">
        <div class="col-lg-8">
          <div class="chart-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="mb-0">Appointments Trend (Last 6 Months)</h5>
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary active" onclick="updateChart('monthly')">Monthly</button>
                <button class="btn btn-outline-secondary" onclick="updateChart('weekly')">Weekly</button>
                <button class="btn btn-outline-secondary" onclick="updateChart('daily')">Daily</button>
              </div>
            </div>
            <canvas id="trendChart" style="max-height: 300px;"></canvas>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="chart-container">
            <h5 class="mb-4">Appointments by Status</h5>
            <canvas id="statusChart" style="max-height: 300px;"></canvas>
            <div class="mt-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Efficiency Rate</span>
                <span class="fw-bold text-success"><?php echo $completion_rate; ?>%</span>
              </div>
              <div class="progress progress-thin">
                <div class="progress-bar bg-success" style="width: <?php echo $completion_rate; ?>%"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tables Section -->
      <div class="row g-4 mb-4">
        <div class="col-lg-6">
          <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="mb-0">Upcoming Appointments</h5>
              <button class="btn btn-sm btn-primary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Patient</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Dentist</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($upcoming_appointments && $upcoming_appointments->num_rows > 0): ?>
                    <?php while ($row = $upcoming_appointments->fetch_assoc()): ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center">
                            <div class="avatar bg-primary text-white me-3">
                              <?php echo getInitials($row['patient_name']); ?>
                            </div>
                            <div>
                              <div class="fw-semibold"><?php echo htmlspecialchars($row['patient_name']); ?></div>
                              <?php if (!empty($row['reason'])): ?>
                                <small class="text-muted"><?php echo htmlspecialchars($row['reason']); ?></small>
                              <?php endif; ?>
                            </div>
                          </div>
                        </td>
                        <td><?php echo formatDate($row['scheduled_date']); ?></td>
                        <td><?php echo formatTime($row['start_time']); ?></td>
                        <td><?php echo htmlspecialchars($row['dentist_name']); ?></td>
                        <td>
                          <button class="btn btn-sm btn-outline-primary" onclick="viewAppointment(<?php echo $row['appointment_id']; ?>)">
                            <i class="bi bi-eye"></i>
                          </button>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="5" class="text-center text-muted py-4">No upcoming appointments</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        
        <div class="col-lg-6">
          <div class="table-container">
            <h5 class="mb-4">Top Dentists</h5>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Dentist</th>
                    <th>Appointments</th>
                    <th>Performance</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($top_dentists && $top_dentists->num_rows > 0): ?>
                    <?php 
                      $max_appointments = 0;
                      $dentists_data = [];
                      while ($row = $top_dentists->fetch_assoc()) {
                        $dentists_data[] = $row;
                        if ($row['appointment_count'] > $max_appointments) {
                          $max_appointments = $row['appointment_count'];
                        }
                      }
                    ?>
                    <?php foreach ($dentists_data as $dentist): ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center">
                            <div class="avatar bg-success text-white me-3">
                              <?php echo getInitials($dentist['dentist_name']); ?>
                            </div>
                            <div><?php echo htmlspecialchars($dentist['dentist_name']); ?></div>
                          </div>
                        </td>
                        <td><?php echo $dentist['appointment_count']; ?></td>
                        <td>
                          <div class="dentist-progress">
                            <div class="progress-container">
                              <div class="progress progress-thin">
                                <div class="progress-bar bg-success" 
                                     style="width: <?php echo $max_appointments > 0 ? ($dentist['appointment_count'] / $max_appointments) * 100 : 0; ?>%"></div>
                              </div>
                            </div>
                            <small class="text-muted"><?php echo $dentist['completion_rate']; ?>%</small>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="3" class="text-center text-muted py-4">No dentist data available</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="row">
        <div class="col-12">
          <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="mb-0">Recent Activity</h5>
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary active">All</button>
                <button class="btn btn-outline-secondary">Appointments</button>
                <button class="btn btn-outline-secondary">Payments</button>
                <button class="btn btn-outline-secondary">Treatments</button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Patient</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Dentist</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($recent_appointments && $recent_appointments->num_rows > 0): ?>
                    <?php while ($row = $recent_appointments->fetch_assoc()): ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center">
                            <div class="avatar bg-primary text-white me-3">
                              <?php echo getInitials($row['patient_name']); ?>
                            </div>
                            <div>
                              <div class="fw-semibold"><?php echo htmlspecialchars($row['patient_name']); ?></div>
                              <?php if (!empty($row['reason'])): ?>
                                <small class="text-muted"><?php echo htmlspecialchars($row['reason']); ?></small>
                              <?php endif; ?>
                            </div>
                          </div>
                        </td>
                        <td><?php echo formatDate($row['scheduled_date']); ?></td>
                        <td><?php echo formatTime($row['start_time']); ?> - <?php echo formatTime($row['end_time']); ?></td>
                        <td><?php echo htmlspecialchars($row['dentist_name']); ?></td>
                        <td>
                          <?php
                            $status_class = '';
                            switch($row['status']) {
                              case 'Completed': $status_class = 'status-completed'; break;
                              case 'Scheduled': $status_class = 'status-scheduled'; break;
                              case 'Canceled': $status_class = 'status-canceled'; break;
                              case 'No-Show': $status_class = 'status-no-show'; break;
                              default: $status_class = 'status-scheduled';
                            }
                          ?>
                          <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                          </span>
                        </td>
                        <td><?php echo formatCurrency($row['total_amount']); ?></td>
                        <td>
                          <button class="btn btn-sm btn-outline-primary me-1" onclick="viewAppointment(<?php echo $row['appointment_id']; ?>)">
                            <i class="bi bi-eye"></i>
                          </button>
                          <button class="btn btn-sm btn-outline-secondary" onclick="editAppointment(<?php echo $row['appointment_id']; ?>)">
                            <i class="bi bi-pencil"></i>
                          </button>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="7" class="text-center text-muted py-4">No recent activity</td>
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

  <!-- JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
  
  <script>
    // PHP data for JavaScript
    const phpData = {
      trendLabels: <?php echo json_encode($trend_labels); ?>,
      trendData: <?php echo json_encode($trend_data); ?>,
      statusLabels: <?php echo json_encode($status_labels); ?>,
      statusData: <?php echo json_encode($status_data); ?>,
      completionRate: <?php echo $completion_rate; ?>
    };

    // Global variables
    let trendChart = null;
    let statusChart = null;

    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
      console.log('DOM loaded - initializing dashboard');
      
      // Initialize components
      initializeEventListeners();
      initializeCharts();
      startRealTimeUpdates();
      
      // Hide loading overlay
      setTimeout(() => {
        document.getElementById('loadingOverlay').classList.remove('show');
      }, 1000);
    });

    // Initialize event listeners
    function initializeEventListeners() {
      // Mobile toggle
      const mobileToggle = document.getElementById('mobileToggle');
      const sidebar = document.getElementById('sidebar');
      const mainContent = document.getElementById('mainContent');

      if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
          sidebar.classList.toggle('show');
          mainContent.classList.toggle('expanded');
        });
      }

      // Search functionality
      const searchInput = document.getElementById('globalSearch');
      if (searchInput) {
        searchInput.addEventListener('input', function(e) {
          handleSearch(e.target.value);
        });
      }

      // Close sidebar when clicking outside on mobile
      document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
          if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
            sidebar.classList.remove('show');
            mainContent.classList.remove('expanded');
          }
        }
      });

      // Resize handler
      window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
          sidebar.classList.remove('show');
          mainContent.classList.remove('expanded');
        }
      });
    }

    // Initialize charts
    function initializeCharts() {
      const trendElement = document.getElementById('trendChart');
      const statusElement = document.getElementById('statusChart');
      
      if (trendElement && typeof Chart !== 'undefined') {
        initializeTrendChart(trendElement);
      }
      
      if (statusElement && typeof Chart !== 'undefined') {
        initializeStatusChart(statusElement);
      }
    }

    // Initialize trend chart
    function initializeTrendChart(element) {
      const ctx = element.getContext('2d');
      
      // Use PHP data if available, otherwise use default data
      const labels = phpData.trendLabels.length > 0 ? phpData.trendLabels : ['Dec 2024', 'Jan 2025', 'Feb 2025', 'Mar 2025', 'Apr 2025', 'May 2025'];
      const data = phpData.trendData.length > 0 ? phpData.trendData : [20, 19, 30, 29, 25, 22];

      const chartData = {
        labels: labels,
        datasets: [{
          label: 'Appointments',
          data: data,
          backgroundColor: 'rgba(67, 97, 238, 0.1)',
          borderColor: '#4361ee',
          borderWidth: 3,
          tension: 0.4,
          fill: true,
          pointBackgroundColor: '#4361ee',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7
        }]
      };

      trendChart = new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              mode: 'index',
              intersect: false,
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              titleColor: '#fff',
              bodyColor: '#fff',
              borderColor: '#4361ee',
              borderWidth: 1
            }
          },
          scales: {
            x: {
              grid: {
                display: false
              },
              ticks: {
                color: '#6c757d'
              }
            },
            y: {
              beginAtZero: true,
              grid: {
                borderDash: [5, 5],
                color: 'rgba(0, 0, 0, 0.1)'
              },
              ticks: {
                precision: 0,
                color: '#6c757d'
              }
            }
          },
          interaction: {
            intersect: false,
            mode: 'index'
          }
        }
      });
    }

    // Initialize status chart
    function initializeStatusChart(element) {
      const ctx = element.getContext('2d');
      
      // Use PHP data if available, otherwise use default data
      const labels = phpData.statusLabels.length > 0 ? phpData.statusLabels : ['Completed', 'Scheduled', 'Canceled', 'No-Show'];
      const data = phpData.statusData.length > 0 ? phpData.statusData : [126, 49, 20, 10];
      
      statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: labels,
          datasets: [{
            data: data,
            backgroundColor: [
              '#10b981',  // Completed - Green
              '#4361ee',  // Scheduled - Blue
              '#ef4444',  // Canceled - Red
              '#f59e0b'   // No-Show - Yellow
            ],
            borderWidth: 0,
            hoverOffset: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '65%',
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                padding: 20,
                boxWidth: 12,
                usePointStyle: true,
                pointStyle: 'circle'
              }
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = context.raw;
                  const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                  const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                  return `${label}: ${value} (${percentage}%)`;
                }
              }
            }
          }
        }
      });
    }

    // Update chart based on time period
    function updateChart(period) {
      if (!trendChart) return;

      // Update active button
      document.querySelectorAll('.btn-group .btn').forEach(btn => {
        btn.classList.remove('active');
      });
      event.target.classList.add('active');

      let newData = {};
      
      switch(period) {
        case 'daily':
          newData = {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
              ...trendChart.data.datasets[0],
              data: [8, 12, 6, 9, 15, 10, 7]
            }]
          };
          break;
        case 'weekly':
          newData = {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
              ...trendChart.data.datasets[0],
              data: [45, 52, 38, 47]
            }]
          };
          break;
        default: // monthly
          newData = {
            labels: phpData.trendLabels.length > 0 ? phpData.trendLabels : ['Dec 2024', 'Jan 2025', 'Feb 2025', 'Mar 2025', 'Apr 2025', 'May 2025'],
            datasets: [{
              ...trendChart.data.datasets[0],
              data: phpData.trendData.length > 0 ? phpData.trendData : [20, 19, 30, 29, 25, 22]
            }]
          };
      }

      trendChart.data = newData;
      trendChart.update('active');
    }

    // Search functionality
    function handleSearch(query) {
      console.log('Searching for:', query);
      
      if (query.length < 2) {
        return;
      }

      // Show loading state briefly
      showLoading();

      // In a real implementation, this would make an AJAX call to search.php
      setTimeout(() => {
        hideLoading();
        // This would handle the search results
        console.log('Search results would be displayed here');
      }, 500);
    }

    // Show loading overlay
    function showLoading() {
      document.getElementById('loadingOverlay').classList.add('show');
    }

    // Hide loading overlay
    function hideLoading() {
      document.getElementById('loadingOverlay').classList.remove('show');
    }

    // Appointment functions
    function viewAppointment(id) {
      console.log('Viewing appointment:', id);
      window.location.href = `view_appointment.php?id=${id}`;
    }

    function editAppointment(id) {
      console.log('Editing appointment:', id);
      window.location.href = `edit_appointment.php?id=${id}`;
    }

    // Real-time updates
    function startRealTimeUpdates() {
      // Update time every minute
      setInterval(() => {
        updateDateTime();
      }, 60000);

      // Refresh dashboard data every 5 minutes
      setInterval(() => {
        refreshDashboardData();
      }, 300000);
    }

    // Update date and time
    function updateDateTime() {
      const now = new Date();
      const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      };
      const dateStr = now.toLocaleDateString('en-US', options);
      const timeStr = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit' 
      });

      // Update the welcome header if it exists
      const dateElements = document.querySelectorAll('.welcome-header p');
      if (dateElements.length >= 2) {
        dateElements[0].innerHTML = `<i class="bi bi-calendar-check me-2"></i>${dateStr}`;
        dateElements[1].innerHTML = `<i class="bi bi-clock-history me-2"></i>${timeStr}`;
      }
    }

    // Refresh dashboard data
    function refreshDashboardData() {
      // In a real implementation, this would make an AJAX call to refresh data
      console.log('Refreshing dashboard data...');
      
      fetch('refresh_dashboard.php', {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => response.json())
      .then(data => {
        // Update dashboard with fresh data
        console.log('Dashboard data refreshed');
      })
      .catch(error => {
        console.error('Error refreshing dashboard:', error);
      });
    }

    // Error handling
    window.addEventListener('error', function(e) {
      console.error('An error occurred:', e.error);
    });

    // Performance monitoring
    window.addEventListener('load', function() {
      const loadTime = performance.now();
      console.log(`Dashboard loaded in ${Math.round(loadTime)}ms`);
    });
  </script>
</body>
</html>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>