<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION["user_id"];

// Get the JSON data from request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!isset($data['request_id'])) {
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit();
}

$request_id = intval($data['request_id']);

// Verify that the request belongs to the user and is in pending status
$verify_query = "SELECT id, status FROM document_requests WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Request not found or unauthorized']);
    exit();
}

if ($request['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Only pending requests can be cancelled']);
    exit();
}

// Update the request status to cancelled
$update_query = "UPDATE document_requests SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("i", $request_id);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Request cancelled successfully']);
} else {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Failed to cancel request']);
}

$conn->close();
?>