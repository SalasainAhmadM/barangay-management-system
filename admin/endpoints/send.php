<?php
// admin/endpoints/send_sms.php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Load .env file
$env_path = __DIR__ . '/../../.env';
if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Get environment variables
$api_key = $_ENV['SEMAPHORE_API_KEY'] ?? '';
$sender_name = $_ENV['SEMAPHORE_SENDER_NAME'] ?? 'BARANGAY';

if (empty($api_key)) {
    echo json_encode([
        'success' => false,
        'message' => 'SMS API key not configured'
    ]);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['number']) || !isset($input['message'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit();
}

$number = trim($input['number']);
$message = trim($input['message']);

// Convert phone number format from 09XXXXXXXXX to 639XXXXXXXXX
if (preg_match('/^09\d{9}$/', $number)) {
    // Remove leading 0 and add 63
    $number = '63' . substr($number, 1);
} elseif (preg_match('/^9\d{9}$/', $number)) {
    // If it starts with 9, add 63
    $number = '63' . $number;
}

// Validate phone number format (should now be 639XXXXXXXXX)
if (!preg_match('/^639\d{9}$/', $number)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid phone number format. Expected format: 09XXXXXXXXX or 639XXXXXXXXX'
    ]);
    exit();
}

if (empty($message)) {
    echo json_encode([
        'success' => false,
        'message' => 'Message cannot be empty'
    ]);
    exit();
}

// Prepare POST data for Semaphore API
$post_data = [
    'apikey' => $api_key,
    'number' => $number,
    'message' => $message,
    'sendername' => $sender_name
];

// Send request via cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Check for cURL errors
if ($response === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to connect to SMS service: ' . $curl_error
    ]);
    exit();
}

// Parse response
$response_data = json_decode($response, true);

// Check if SMS was sent successfully
if ($http_code === 200 && isset($response_data['message_id'])) {
    echo json_encode([
        'success' => true,
        'message' => 'SMS sent successfully',
        'data' => $response_data
    ]);
} else {
    // Handle API errors
    $error_message = 'Failed to send SMS';

    if (isset($response_data['message'])) {
        $error_message = $response_data['message'];
    } elseif (isset($response_data['error'])) {
        $error_message = $response_data['error'];
    }

    echo json_encode([
        'success' => false,
        'message' => $error_message,
        'data' => $response_data
    ]);
}
?>