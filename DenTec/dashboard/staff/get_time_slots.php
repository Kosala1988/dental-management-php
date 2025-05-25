<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

include '../../includes/db_connect.php';

$date = $_GET['date'] ?? date('Y-m-d');
$dentist_id = intval($_GET['dentist_id'] ?? 0);

// Get existing appointments for the selected date and dentist
$existing_appointments = [];
if ($date && $dentist_id) {
    $stmt = $conn->prepare("SELECT start_time, end_time FROM appointments WHERE dentist_id = ? AND scheduled_date = ? AND status != 'Canceled'");
    $stmt->bind_param("is", $dentist_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $existing_appointments[] = [
            'start' => new DateTime($row['start_time']),
            'end' => new DateTime($row['end_time'])
        ];
    }
    $stmt->close();
}

// Generate time slots (9:00 AM to 8:30 PM in 30-minute intervals)
$time_slots = [];
$start = new DateTime('09:00');
$end = new DateTime('20:30');
$interval = new DateInterval('PT30M');

while ($start <= $end) {
    $slot_end = clone $start;
    $slot_end->add($interval);
    
    // Check if slot is available
    $is_available = true;
    foreach ($existing_appointments as $appt) {
        if ($start < $appt['end'] && $slot_end > $appt['start']) {
            $is_available = false;
            break;
        }
    }
    
    $time_slots[] = [
        'start' => clone $start,
        'end' => $slot_end,
        'available' => $is_available
    ];
    
    $start->add($interval);
}

// Output the time slots HTML
foreach ($time_slots as $slot): ?>
    <div class="col-md-4">
        <div class="time-slot <?php echo $slot['available'] ? 'available' : 'booked'; ?>" 
             onclick="<?php echo $slot['available'] ? 'selectTimeSlot(this, \'' . $slot['start']->format('H:i:s') . '\')' : ''; ?>">
            <div class="time-slot-label">
                <?php echo $slot['start']->format('g:i A'); ?> - <?php echo $slot['end']->format('g:i A'); ?>
            </div>
            <div class="time-slot-time">
                <?php echo $slot['available'] ? 'Available' : 'Booked'; ?>
            </div>
        </div>
    </div>
<?php endforeach;
?>