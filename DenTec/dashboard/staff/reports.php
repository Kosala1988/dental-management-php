<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

include '../../includes/db_connect.php';

// Get basic metrics
$totalRevenue = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$revenueAmount = $totalRevenue ? $totalRevenue->fetch_assoc()['total'] : 0;

$totalPatients = $conn->query("SELECT COUNT(*) as total FROM patients");
$patientsCount = $totalPatients ? $totalPatients->fetch_assoc()['total'] : 0;

$completedTreatments = $conn->query("SELECT COUNT(*) as total FROM treatment_records WHERE status = 'Completed' AND treatment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$treatmentsCount = $completedTreatments ? $completedTreatments->fetch_assoc()['total'] : 0;

$pendingAppointments = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status = 'Scheduled'");
$pendingCount = $pendingAppointments ? $pendingAppointments->fetch_assoc()['total'] : 0;

// Get revenue trends for chart
$revenueTrends = $conn->query("
    SELECT 
        DATE(payment_date) as date,
        SUM(amount) as daily_revenue
    FROM payments 
    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(payment_date)
    ORDER BY date ASC
");

$trendData = [];
$trendLabels = [];
while ($row = $revenueTrends->fetch_assoc()) {
    $trendLabels[] = date('M j', strtotime($row['date']));
    $trendData[] = (float)$row['daily_revenue'];
}

// Get payment methods
$paymentMethods = $conn->query("
    SELECT 
        payment_method,
        COUNT(*) as count
    FROM payments 
    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY payment_method
    ORDER BY count DESC
");

$paymentLabels = [];
$paymentData = [];
while ($row = $paymentMethods->fetch_assoc()) {
    $paymentLabels[] = $row['payment_method'];
    $paymentData[] = (int)$row['count'];
}

// Get top treatments
$topTreatments = $conn->query("
    SELECT 
        t.name,
        COUNT(tr.record_id) as count
    FROM treatments t
    JOIN treatment_records tr ON t.treatment_id = tr.treatment_id
    WHERE tr.treatment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY t.treatment_id, t.name
    ORDER BY count DESC
    LIMIT 5
");

$treatmentLabels = [];
$treatmentData = [];
while ($row = $topTreatments->fetch_assoc()) {
    $treatmentLabels[] = $row['name'];
    $treatmentData[] = (int)$row['count'];
}

// Get top patients
$topPatients = $conn->query("
    SELECT 
        CONCAT(p.first_name, ' ', p.last_name) as patient_name,
        COUNT(tr.record_id) as visits,
        COALESCE(SUM(py.amount), 0) as revenue,
        MAX(tr.treatment_date) as last_visit
    FROM patients p
    LEFT JOIN treatment_records tr ON p.patient_id = tr.patient_id
    LEFT JOIN payments py ON tr.record_id = py.record_id
    WHERE tr.treatment_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
    GROUP BY p.patient_id
    HAVING revenue > 0
    ORDER BY revenue DESC
    LIMIT 5
");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports & Analytics | DenTec</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #f94144;
        }
        
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #f8f9fa; 
        }
        
        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, #3f37c9, var(--primary));
            color: white;
            padding: 20px 10px;
            position: fixed;
            width: 250px;
            z-index: 1000;
        }
        
        .sidebar a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 8px;
            transition: all 0.3s;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.15);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar i { width: 24px; margin-right: 12px; }
        .main { margin-left: 250px; padding: 30px; }
        
        .header {
            background: linear-gradient(135deg, var(--primary), #4895ef);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 5px solid var(--primary);
            transition: transform 0.3s;
        }
        
        .metric-card:hover { transform: translateY(-5px); }
        .metric-card.success { border-left-color: var(--success); }
        .metric-card.warning { border-left-color: var(--warning); }
        .metric-card.danger { border-left-color: var(--danger); }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.5rem;
        }
        
        .metric-label { color: #6c757d; font-size: 0.9rem; }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: none;
        }
        
        .chart-container { position: relative; height: 400px; }
        
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .table-header {
            background: linear-gradient(90deg, var(--primary), #4895ef);
            color: white;
            padding: 15px 20px;
        }
        
        .export-btn {
            background: linear-gradient(135deg, var(--success), #4cc9f0);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .export-btn:hover { 
            transform: translateY(-2px); 
            color: white;
        }
        
        .ai-box {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
        }
        
        @media (max-width: 768px) {
            .sidebar { position: relative; width: 100%; height: auto; }
            .main { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <h5 class="text-white mb-4 ps-2"><?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></h5>
            <a href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="schedule_appointment.php"><i class="bi bi-calendar-check"></i> Appointments</a>
            <a href="patient.php"><i class="bi bi-people"></i> Patients</a>
            <a href="payment.php"><i class="bi bi-currency-dollar"></i> Payments</a>
            <a href="#" class="active"><i class="bi bi-file-earmark-text"></i> Reports</a>
            <a href="../../logout.php" class="mt-auto"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>

        <!-- Main Content -->
        <div class="main">
            <div class="header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="bi bi-graph-up me-3"></i>Reports & Analytics</h2>
                        <p class="mb-0">Key insights for your dental practice</p>
                    </div>
                    <button class="btn export-btn" onclick="exportAll()">
                        <i class="bi bi-download me-2"></i>Export All
                    </button>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="metric-card">
                        <div class="metric-value">LKR<?php echo number_format($revenueAmount, 0); ?></div>
                        <div class="metric-label">Revenue (30 days)</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card success">
                        <div class="metric-value"><?php echo $patientsCount; ?></div>
                        <div class="metric-label">Total Patients</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card warning">
                        <div class="metric-value"><?php echo $treatmentsCount; ?></div>
                        <div class="metric-label">Treatments (30 days)</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card danger">
                        <div class="metric-value"><?php echo $pendingCount; ?></div>
                        <div class="metric-label">Pending Appointments</div>
                    </div>
                </div>
            </div>

            <!-- AI Insights -->
            <div class="ai-box">
                <h6><i class="bi bi-robot me-2"></i>AI Insights & Predictions</h6>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Revenue Forecast:</strong><br>
                        Next month: <strong>LKR<?php echo number_format($revenueAmount * 1.15, 0); ?></strong> (+15%)
                    </div>
                    <div class="col-md-4">
                        <strong>Peak Hours:</strong><br>
                        <strong>10:00 AM - 2:00 PM</strong> (highest efficiency)
                    </div>
                    <div class="col-md-4">
                        <strong>Recommendation:</strong><br>
                        Focus on <strong>preventive treatments</strong>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5>Revenue Trends (Last 7 Days)</h5>
                            <button class="btn btn-sm export-btn" onclick="exportChart('revenueChart')">
                                <i class="bi bi-download"></i>
                            </button>
                        </div>
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <h5 class="mb-4">Payment Methods</h5>
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="paymentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card">
                        <h5 class="mb-4">Popular Treatments</h5>
                        <div class="chart-container">
                            <canvas id="treatmentChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <h5 class="mb-4">Quick Statistics</h5>
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <h3 class="text-primary"><?php echo round($revenueAmount / max($patientsCount, 1), 0); ?></h3>
                                <small>Avg Revenue per Patient</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h3 class="text-success">94%</h3>
                                <small>Treatment Success Rate</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-warning">+12%</h3>
                                <small>Monthly Growth</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-info">25 min</h3>
                                <small>Avg Treatment Time</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Patients Table -->
            <div class="table-container">
                <div class="table-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Top Patients (Last 90 Days)</h6>
                        <button class="btn btn-sm btn-outline-light" onclick="exportTable('patientsTable')">
                            <i class="bi bi-download me-1"></i>Export
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="patientsTable">
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>Visits</th>
                                <th>Revenue</th>
                                <th>Last Visit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($topPatients && $topPatients->num_rows > 0): ?>
                                <?php while ($patient = $topPatients->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                                     style="width: 35px; height: 35px; font-size: 0.8rem;">
                                                    <?php echo strtoupper(substr($patient['patient_name'], 0, 2)); ?>
                                                </div>
                                                <?php echo htmlspecialchars($patient['patient_name']); ?>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-primary"><?php echo $patient['visits']; ?></span></td>
                                        <td><strong class="text-success">$<?php echo number_format($patient['revenue'], 0); ?></strong></td>
                                        <td><?php echo date('M j, Y', strtotime($patient['last_visit'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No patient data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chart data from PHP
        const revenueData = <?php echo json_encode($trendData); ?>;
        const revenueLabels = <?php echo json_encode($trendLabels); ?>;
        const paymentData = <?php echo json_encode($paymentData); ?>;
        const paymentLabels = <?php echo json_encode($paymentLabels); ?>;
        const treatmentData = <?php echo json_encode($treatmentData); ?>;
        const treatmentLabels = <?php echo json_encode($treatmentLabels); ?>;

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Revenue Chart
            new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: {
                    labels: revenueLabels,
                    datasets: [{
                        label: 'Revenue ($)',
                        data: revenueData,
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });

            // Payment Methods Chart
            new Chart(document.getElementById('paymentChart'), {
                type: 'doughnut',
                data: {
                    labels: paymentLabels,
                    datasets: [{
                        data: paymentData,
                        backgroundColor: ['#4361ee', '#4895ef', '#4cc9f0', '#f8961e', '#f94144']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            // Treatment Chart
            new Chart(document.getElementById('treatmentChart'), {
                type: 'bar',
                data: {
                    labels: treatmentLabels,
                    datasets: [{
                        label: 'Count',
                        data: treatmentData,
                        backgroundColor: ['#4361ee', '#4895ef', '#4cc9f0', '#f8961e', '#f94144']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        });

        // Export functions
        function exportAll() {
            const btn = event.target;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';
            btn.disabled = true;

            setTimeout(() => {
                const wb = XLSX.utils.book_new();
                
                // Financial data
                const financialData = [
                    ['Metric', 'Value'],
                    ['Total Revenue (30 days)', '$<?php echo number_format($revenueAmount, 0); ?>'],
                    ['Total Patients', '<?php echo $patientsCount; ?>'],
                    ['Treatments (30 days)', '<?php echo $treatmentsCount; ?>'],
                    ['Pending Appointments', '<?php echo $pendingCount; ?>']
                ];
                XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(financialData), 'Summary');

                // Top patients
                const table = document.getElementById('patientsTable');
                XLSX.utils.book_append_sheet(wb, XLSX.utils.table_to_sheet(table), 'Top Patients');

                const fileName = `DenTec_Reports_${new Date().toISOString().split('T')[0]}.xlsx`;
                XLSX.writeFile(wb, fileName);

                btn.innerHTML = '<i class="bi bi-download me-2"></i>Export All';
                btn.disabled = false;
                showAlert('Reports exported successfully!', 'success');
            }, 1500);
        }

        function exportChart(chartId) {
            showAlert('Chart export feature coming soon!', 'info');
        }

        function exportTable(tableId) {
            const table = document.getElementById(tableId);
            const wb = XLSX.utils.table_to_book(table);
            const fileName = `${tableId}_${new Date().toISOString().split('T')[0]}.xlsx`;
            XLSX.writeFile(wb, fileName);
            showAlert('Table exported successfully!', 'success');
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 5000);
        }
    </script>
</body>
</html>