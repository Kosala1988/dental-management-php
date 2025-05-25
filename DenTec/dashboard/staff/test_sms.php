<?php
// test_sms.php - Optional SMS testing endpoint
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['phone']) || !isset($input['message'])) {
    echo json_encode(['success' => false, 'message' => 'Missing phone or message']);
    exit();
}

// Load SMS configuration
$sms_config = [];
if (file_exists('sms_config.php')) {
    $sms_config = include('sms_config.php');
} else {
    echo json_encode(['success' => false, 'message' => 'SMS not configured']);
    exit();
}

// Simple SMS Gateway
class TextLkGateway {
    private $config = [];
    
    public function __construct($config = []) {
        $this->config = $config;
    }
    
    public function sendSMS($phone, $message) {
        if (empty($this->config['api_token']) || empty($this->config['sender_id'])) {
            return ['success' => false, 'message' => 'SMS not configured'];
        }
        
        $payload = [
            'recipient' => $phone,
            'sender_id' => $this->config['sender_id'],
            'type' => 'plain',
            'message' => $message
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://app.text.lk/api/http/sms/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->config['api_token'],
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 || $http_code == 201) {
            return ['success' => true, 'message' => 'SMS sent successfully'];
        } else {
            return ['success' => false, 'message' => 'SMS failed to send'];
        }
    }
}

$sms_gateway = new TextLkGateway($sms_config);
$result = $sms_gateway->sendSMS($input['phone'], $input['message']);

echo json_encode($result);
?>