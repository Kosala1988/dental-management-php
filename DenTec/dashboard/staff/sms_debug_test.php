<?php
/**
 * SMS Debug Test - Save as: sms_debug_test.php
 * Run this file directly to test SMS functionality
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 SMS Debug Test for SMSlenz.lk</h2>";
echo "<hr>";

// Test 1: Check configuration file
echo "<h3>1. Configuration Check</h3>";
$sms_config_file = 'sms_config.php';

if (file_exists($sms_config_file)) {
    echo "✅ sms_config.php file exists<br>";
    $sms_config = include($sms_config_file);
    
    if (!empty($sms_config['smslenz_api_key'])) {
        echo "✅ API Key configured: " . substr($sms_config['smslenz_api_key'], 0, 10) . "...<br>";
    } else {
        echo "❌ API Key NOT configured<br>";
    }
    
    if (!empty($sms_config['smslenz_sender_id'])) {
        echo "✅ Sender ID configured: " . $sms_config['smslenz_sender_id'] . "<br>";
    } else {
        echo "❌ Sender ID NOT configured<br>";
    }
} else {
    echo "❌ sms_config.php file NOT found<br>";
    $sms_config = [
        'debug' => true,
        'smslenz_api_key' => '',
        'smslenz_sender_id' => ''
    ];
}

echo "<hr>";

// Test 2: Check cURL
echo "<h3>2. System Requirements Check</h3>";
if (function_exists('curl_init')) {
    echo "✅ cURL is available<br>";
} else {
    echo "❌ cURL is NOT available - SMS will fail<br>";
}

if (extension_loaded('json')) {
    echo "✅ JSON extension is available<br>";
} else {
    echo "❌ JSON extension is NOT available<br>";
}

echo "<hr>";

// Test 3: Test SMS Class
echo "<h3>3. SMS Gateway Class Test</h3>";

class SMSlenzGatewayTest {
    private $config = [];
    
    public function __construct($config = []) {
        $this->config = $config;
    }
    
    private function cleanPhoneNumber($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        echo "Original phone: $phone<br>";
        
        if (strlen($phone) == 12 && substr($phone, 0, 2) == '94') {
            $formatted = '0' . substr($phone, 2);
        } elseif (strlen($phone) == 9 && substr($phone, 0, 1) == '7') {
            $formatted = '0' . $phone;
        } elseif (strlen($phone) == 10 && substr($phone, 0, 2) == '07') {
            $formatted = $phone;
        } elseif (strlen($phone) == 11 && substr($phone, 0, 3) == '+94') {
            $formatted = '0' . substr($phone, 3);
        } else {
            $formatted = (substr($phone, 0, 1) !== '0') ? '0' . $phone : $phone;
        }
        
        echo "Formatted phone: $formatted<br>";
        return $formatted;
    }
    
    public function testSMS($phone, $message) {
        echo "<strong>Testing SMS to: $phone</strong><br>";
        
        // Validate configuration
        if (empty($this->config['smslenz_api_key'])) {
            echo "❌ SMSlenz API key not configured<br>";
            return false;
        }
        
        if (empty($this->config['smslenz_sender_id'])) {
            echo "❌ SMSlenz Sender ID not configured<br>";
            return false;
        }
        
        // Clean phone number
        $formatted_phone = $this->cleanPhoneNumber($phone);
        
        // Validate Sri Lankan number
        if (strlen($formatted_phone) !== 10 || substr($formatted_phone, 0, 2) !== '07') {
            echo "❌ Invalid Sri Lankan phone number format<br>";
            return false;
        }
        
        echo "✅ Phone number validation passed<br>";
        
        // SMSlenz.lk API endpoint
        $api_url = 'https://smslenz.lk/api/send-sms';
        
        // Prepare payload
        $payload = [
            'api_key' => $this->config['smslenz_api_key'],
            'sender_id' => 'SMSlenzDEMO',
            'contact' => $formatted_phone,
            'message' => $message,
            'user_id' => '187'
        ];
        
        echo "<strong>API URL:</strong> $api_url<br>";
        echo "<strong>Payload:</strong> " . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "<br>";
        
        // Test cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: DenTec-Clinic-Test/1.0'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        echo "<strong>Sending request...</strong><br>";
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        echo "<strong>HTTP Code:</strong> $http_code<br>";
        
        if ($curl_error) {
            echo "❌ <strong>cURL Error:</strong> $curl_error<br>";
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        echo "<strong>Raw Response:</strong><br>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        if ($http_code !== 200) {
            echo "❌ HTTP Error: $http_code<br>";
            return false;
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "❌ Invalid JSON response<br>";
            return false;
        }
        
        echo "<strong>Parsed Response:</strong><br>";
        echo "<pre>" . print_r($result, true) . "</pre>";
        
        if (isset($result['status']) && $result['status'] === 'success') {
            echo "✅ <strong>SMS sent successfully!</strong><br>";
            if (isset($result['message_id'])) {
                echo "Message ID: " . $result['message_id'] . "<br>";
            }
            if (isset($result['credits_remaining'])) {
                echo "Credits remaining: " . $result['credits_remaining'] . "<br>";
            }
            return true;
        } else {
            echo "❌ <strong>SMS failed:</strong> " . ($result['message'] ?? 'Unknown error') . "<br>";
            return false;
        }
    }
}

// Test 4: Run SMS test
echo "<h3>4. SMS Test</h3>";

if (!empty($sms_config['smslenz_api_key']) && !empty($sms_config['smslenz_sender_id'])) {
    echo "<form method='POST'>";
    echo "<label>Phone Number (Sri Lankan): </label>";
    echo "<input type='text' name='test_phone' value='" . ($_POST['test_phone'] ?? '0710783322') . "' placeholder='0710783322'><br><br>";
    echo "<label>Message: </label>";
    echo "<input type='text' name='test_message' value='" . ($_POST['test_message'] ?? 'Test SMS from DenTec Clinic via SMSlenz.lk') . "'><br><br>";
    echo "<input type='submit' name='send_test' value='Send Test SMS'>";
    echo "</form>";
    
    if (isset($_POST['send_test'])) {
        echo "<hr>";
        echo "<h4>SMS Test Results:</h4>";
        
        $test_gateway = new SMSlenzGatewayTest($sms_config);
        $test_gateway->testSMS($_POST['test_phone'], $_POST['test_message']);
    }
} else {
    echo "❌ Cannot test SMS - API key or Sender ID not configured<br>";
    echo "<strong>Please configure your sms_config.php file first:</strong><br>";
    echo "<pre>";
    echo "return [
    'debug' => true,
    'smslenz_api_key' => 'YOUR_API_KEY_HERE',
    'smslenz_sender_id' => 'YOUR_SENDER_ID_HERE'
];";
    echo "</pre>";
}

echo "<hr>";
echo "<h3>5. Next Steps</h3>";
echo "1. Make sure your SMSlenz.lk account is active<br>";
echo "2. Verify your API key is correct<br>";
echo "3. Ensure your Sender ID is approved<br>";
echo "4. Check that you have sufficient credits<br>";
echo "5. Test with a valid Sri Lankan mobile number<br>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
form { background: #e8f4fd; padding: 15px; border-radius: 5px; margin: 10px 0; }
input[type="text"] { width: 300px; padding: 5px; margin: 5px 0; }
input[type="submit"] { background: #007cba; color: white; padding: 8px 15px; border: none; border-radius: 3px; cursor: pointer; }
</style>