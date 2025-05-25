<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Include DB connection (create this file with your credentials)
include 'includes/db_connect.php';

// Total patients
$result = $conn->query("SELECT COUNT(*) as total FROM patients");
$totalPatients = $result->fetch_assoc()['total'] ?? 0;

// New patients this month
$result = $conn->query("SELECT COUNT(*) as total FROM patients WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$newPatients = $result->fetch_assoc()['total'] ?? 0;

// Upcoming appointments
$result = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date >= CURDATE() AND status = 'Scheduled'");
$upcomingAppointments = $result->fetch_assoc()['total'] ?? 0;

// Total revenue
$result = $conn->query("SELECT IFNULL(SUM(amount), 0) as total FROM payments");
$totalRevenue = $result->fetch_assoc()['total'] ?? 0.0;

// Patient growth last 6 months
$patientGrowthLabels = [];
$patientGrowthData = [];
for ($i = 5; $i >= 0; $i--) {
    $monthLabel = date('M Y', strtotime("-$i month"));
    $patientGrowthLabels[] = $monthLabel;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM patients WHERE YEAR(created_at) = YEAR(CURRENT_DATE - INTERVAL ? MONTH) AND MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL ? MONTH)");
    $stmt->bind_param("ii", $i, $i);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $patientGrowthData[] = $res['total'] ?? 0;
    $stmt->close();
}

// Treatment popularity top 5
$treatmentPopularityLabels = [];
$treatmentPopularityData = [];

$sql = "SELECT t.name, COUNT(tr.id) as treatment_count 
        FROM treatments t
        LEFT JOIN treatment_records tr ON t.id = tr.treatment_id
        GROUP BY t.id 
        ORDER BY treatment_count DESC 
        LIMIT 5";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $treatmentPopularityLabels[] = $row['name'];
    $treatmentPopularityData[] = (int)$row['treatment_count'];
}

echo json_encode([
    'totalPatients' => (int)$totalPatients,
    'newPatients' => (int)$newPatients,
    'upcomingAppointments' => (int)$upcomingAppointments,
    'totalRevenue' => (float)$totalRevenue,
    'patientGrowth' => [
        'labels' => $patientGrowthLabels,
        'data' => $patientGrowthData,
    ],
    'treatmentPopularity' => [
        'labels' => $treatmentPopularityLabels,
        'data' => $treatmentPopularityData,
    ],
]);

$conn->close();
