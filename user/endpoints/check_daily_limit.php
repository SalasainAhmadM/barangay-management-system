<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION["user_id"];
$today = date('Y-m-d');
$daily_limit = 3;

try {
    // Check if record exists for today
    $check_query = "SELECT certificate_count FROM user_daily_request_limits 
                    WHERE user_id = ? AND request_date = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $current_count = $row['certificate_count'];

        if ($current_count >= $daily_limit) {
            echo json_encode([
                'success' => false,
                'limit_reached' => true,
                'current_count' => $current_count,
                'daily_limit' => $daily_limit,
                'message' => "You've reached your daily limit of {$daily_limit} document requests. Please try again tomorrow."
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'limit_reached' => false,
                'current_count' => $current_count,
                'remaining' => $daily_limit - $current_count,
                'daily_limit' => $daily_limit
            ]);
        }
    } else {
        // No record for today, user can proceed
        echo json_encode([
            'success' => true,
            'limit_reached' => false,
            'current_count' => 0,
            'remaining' => $daily_limit,
            'daily_limit' => $daily_limit
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking daily limit: ' . $e->getMessage()
    ]);
}

$conn->close();
?>